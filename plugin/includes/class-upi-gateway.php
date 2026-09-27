<?php
/**
 * "Pay by UPI" gateway: QR code + UPI app deep link, verified by the merchant.
 *
 * Works with any UPI app (PhonePe, Google Pay, Paytm, BHIM, bank apps) without
 * a payment processor. Orders go On hold until the payment is verified.
 *
 * @package Aurelia_Commerce
 */

namespace Aurelia\Commerce;

defined( 'ABSPATH' ) || exit;

/**
 * UPI gateway.
 */
class Upi_Gateway extends \WC_Payment_Gateway {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->id                 = 'aurelia_upi';
		$this->icon               = AURELIA_COMMERCE_URL . 'assets/img/upi.svg';
		$this->has_fields         = false;
		$this->method_title       = __( 'UPI (QR code & UPI apps)', 'aurelia-commerce' );
		$this->method_description = __( 'Customers pay to your UPI ID by scanning a QR code or tapping "Pay with UPI app" (PhonePe, Google Pay, Paytm, BHIM…), then share the UPI reference. Orders stay On hold until you verify the payment.', 'aurelia-commerce' );
		$this->supports           = array( 'products' );

		$this->init_form_fields();
		$this->init_settings();

		$this->title       = $this->get_option( 'title' );
		$this->description = $this->get_option( 'description' );

		add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
		add_action( 'woocommerce_thankyou_' . $this->id, array( $this, 'thankyou_page' ) );
		add_action( 'woocommerce_email_before_order_table', array( $this, 'email_instructions' ), 10, 3 );
	}

	/**
	 * Settings fields.
	 */
	public function init_form_fields() {
		$this->form_fields = array(
			'enabled'     => array(
				'title'   => __( 'Enable/Disable', 'aurelia-commerce' ),
				'type'    => 'checkbox',
				'label'   => __( 'Enable UPI payments', 'aurelia-commerce' ),
				'default' => 'no',
			),
			'title'       => array(
				'title'   => __( 'Title', 'aurelia-commerce' ),
				'type'    => 'text',
				'default' => __( 'UPI — PhonePe, Google Pay, Paytm & more', 'aurelia-commerce' ),
			),
			'description' => array(
				'title'   => __( 'Description', 'aurelia-commerce' ),
				'type'    => 'textarea',
				'default' => __( 'After placing your order you will see a QR code and a "Pay with UPI app" button for the exact amount. Scan or tap, pay, then enter the UPI reference number.', 'aurelia-commerce' ),
			),
			'upi_id'      => array(
				'title'       => __( 'Your UPI ID (VPA)', 'aurelia-commerce' ),
				'type'        => 'text',
				'description' => __( 'For example yourstore@okhdfcbank or 9876543210@ybl. Payments are sent here.', 'aurelia-commerce' ),
				'default'     => '',
				'placeholder' => 'yourstore@upi',
			),
			'payee_name'  => array(
				'title'       => __( 'Payee name', 'aurelia-commerce' ),
				'type'        => 'text',
				'description' => __( 'Shown in the customer\'s UPI app. Usually your business name as registered with your bank.', 'aurelia-commerce' ),
				'default'     => get_bloginfo( 'name' ),
			),
			'qr_image'    => array(
				'title'       => __( 'Static QR image URL (optional)', 'aurelia-commerce' ),
				'type'        => 'url',
				'description' => __( 'Your shop\'s printed PhonePe / Paytm / bank QR. Shown next to the amount-specific QR as a backup.', 'aurelia-commerce' ),
				'default'     => '',
			),
		);
	}

	/**
	 * Validate the UPI ID on save.
	 *
	 * @param string $key   Field key.
	 * @param string $value Value.
	 * @return string
	 */
	public function validate_upi_id_field( $key, $value ) {
		$value = trim( (string) $value );
		if ( '' !== $value && ! self::is_vpa( $value ) ) {
			\WC_Admin_Settings::add_error( __( 'That does not look like a valid UPI ID (for example name@bank).', 'aurelia-commerce' ) );
			return (string) $this->get_option( 'upi_id' );
		}
		return sanitize_text_field( $value );
	}

	/**
	 * Is this a plausible UPI VPA?
	 *
	 * @param string $vpa VPA.
	 * @return bool
	 */
	public static function is_vpa( $vpa ) {
		return (bool) preg_match( '/^[A-Za-z0-9.\-_]{2,256}@[A-Za-z][A-Za-z0-9]{1,63}$/', $vpa );
	}

	/**
	 * Only available with a UPI ID and INR.
	 *
	 * @return bool
	 */
	public function is_available() {
		return parent::is_available() && self::is_vpa( (string) $this->get_option( 'upi_id' ) ) && 'INR' === get_woocommerce_currency();
	}

	/**
	 * Place the order: On hold, stock reduced, cart emptied.
	 *
	 * @param int $order_id Order ID.
	 * @return array
	 */
	public function process_payment( $order_id ) {
		$order = wc_get_order( $order_id );
		if ( $order->get_total() > 0 ) {
			$order->update_status( 'on-hold', __( 'Awaiting UPI payment.', 'aurelia-commerce' ) );
		} else {
			$order->payment_complete();
		}
		wc_reduce_stock_levels( $order_id );
		WC()->cart->empty_cart();
		return array(
			'result'   => 'success',
			'redirect' => $this->get_return_url( $order ),
		);
	}

	/**
	 * UPI payment URI (upi://pay) for an order.
	 *
	 * @param \WC_Order $order Order.
	 * @return string
	 */
	public function upi_uri( $order ) {
		/* translators: %s: order number. */
		$note = sprintf( __( 'Order %s', 'aurelia-commerce' ), $order->get_order_number() );
		return 'upi://pay?' . http_build_query(
			array(
				'pa' => (string) $this->get_option( 'upi_id' ),
				'pn' => (string) $this->get_option( 'payee_name' ),
				'am' => number_format( (float) $order->get_total(), 2, '.', '' ),
				'cu' => 'INR',
				'tn' => $note,
			),
			'',
			'&',
			PHP_QUERY_RFC3986
		);
	}

	/**
	 * Thank-you page: QR, app button and UTR form.
	 *
	 * @param int $order_id Order ID.
	 */
	public function thankyou_page( $order_id ) {
		$order = wc_get_order( $order_id );
		if ( ! $order || ! $order->has_status( array( 'on-hold', 'pending' ) ) ) {
			return;
		}
		$utr = (string) $order->get_meta( '_aurelia_upi_utr' );
		wp_enqueue_script( 'aurelia-qrcode', AURELIA_COMMERCE_URL . 'assets/js/vendor/qrcode.min.js', array(), '2.0.4', true );
		wp_enqueue_script( 'aurelia-upi', AURELIA_COMMERCE_URL . 'assets/js/upi.js', array( 'aurelia-qrcode' ), AURELIA_COMMERCE_VERSION, true );
		wp_localize_script(
			'aurelia-upi',
			'aureliaUpi',
			array(
				'endpoint' => esc_url_raw( rest_url( 'aurelia/v1/upi/confirm' ) ),
				'copied'   => __( 'Copied', 'aurelia-commerce' ),
				'sending'  => __( 'Sending…', 'aurelia-commerce' ),
				'error'    => __( 'Something went wrong. Please try again or contact us.', 'aurelia-commerce' ),
			)
		);
		$qr_image = (string) $this->get_option( 'qr_image' );
		?>
		<section class="au-upi" data-uri="<?php echo esc_attr( $this->upi_uri( $order ) ); ?>" aria-labelledby="au-upi-title">
			<h2 id="au-upi-title"><?php esc_html_e( 'Complete your UPI payment', 'aurelia-commerce' ); ?></h2>
			<?php if ( '' !== $utr ) : ?>
				<p class="au-upi__done" role="status"><?php esc_html_e( 'Thank you! We have your payment reference and will confirm your order shortly.', 'aurelia-commerce' ); ?></p>
			<?php else : ?>
				<p class="au-upi__amount">
					<?php esc_html_e( 'Amount to pay', 'aurelia-commerce' ); ?>
					<strong><?php echo wp_kses_post( wc_price( $order->get_total(), array( 'currency' => $order->get_currency() ) ) ); ?></strong>
				</p>
				<div class="au-upi__grid">
					<figure class="au-upi__qr">
						<div class="au-upi__qr-canvas" role="img" aria-label="<?php esc_attr_e( 'UPI payment QR code', 'aurelia-commerce' ); ?>"></div>
						<figcaption><?php esc_html_e( 'Scan with any UPI app', 'aurelia-commerce' ); ?></figcaption>
					</figure>
					<div class="au-upi__steps">
						<a class="au-btn au-btn--primary au-upi__app" href="<?php echo esc_attr( $this->upi_uri( $order ) ); ?>"><?php esc_html_e( 'Pay with UPI app', 'aurelia-commerce' ); ?></a>
						<p class="au-upi__apps"><?php esc_html_e( 'PhonePe · Google Pay · Paytm · BHIM · any bank app', 'aurelia-commerce' ); ?></p>
						<p class="au-upi__vpa">
							<?php esc_html_e( 'Or pay to UPI ID', 'aurelia-commerce' ); ?>
							<code><?php echo esc_html( (string) $this->get_option( 'upi_id' ) ); ?></code>
							<button type="button" class="au-btn au-btn--ghost au-btn--sm au-upi__copy" data-copy="<?php echo esc_attr( (string) $this->get_option( 'upi_id' ) ); ?>"><?php esc_html_e( 'Copy', 'aurelia-commerce' ); ?></button>
						</p>
						<?php if ( '' !== $qr_image ) : ?>
							<details class="au-upi__static">
								<summary><?php esc_html_e( 'Show our shop QR instead', 'aurelia-commerce' ); ?></summary>
								<img src="<?php echo esc_url( $qr_image ); ?>" alt="<?php esc_attr_e( 'Shop UPI QR code', 'aurelia-commerce' ); ?>" loading="lazy" width="220" height="220">
							</details>
						<?php endif; ?>
						<form class="au-upi__form" data-order="<?php echo esc_attr( (string) $order->get_id() ); ?>" data-key="<?php echo esc_attr( $order->get_order_key() ); ?>">
							<label for="au-upi-utr"><?php esc_html_e( 'After paying, enter the UPI reference / UTR (12 digits)', 'aurelia-commerce' ); ?></label>
							<div class="au-upi__row">
								<input type="text" id="au-upi-utr" name="utr" inputmode="numeric" autocomplete="off" required minlength="6" maxlength="35" pattern="[A-Za-z0-9]{6,35}">
								<button type="submit" class="au-btn au-btn--primary"><?php esc_html_e( 'I have paid', 'aurelia-commerce' ); ?></button>
							</div>
							<p class="au-upi__msg" role="status" aria-live="polite"></p>
						</form>
					</div>
				</div>
			<?php endif; ?>
		</section>
		<?php
	}

	/**
	 * Payment instructions in the customer's on-hold email.
	 *
	 * @param \WC_Order $order         Order.
	 * @param bool      $sent_to_admin Admin email.
	 * @param bool      $plain_text    Plain text.
	 */
	public function email_instructions( $order, $sent_to_admin, $plain_text = false ) {
		if ( $sent_to_admin || $this->id !== $order->get_payment_method() || ! $order->has_status( 'on-hold' ) || $order->get_meta( '_aurelia_upi_utr' ) ) {
			return;
		}
		/* translators: 1: amount, 2: UPI ID. */
		$text = sprintf( __( 'Please pay %1$s to UPI ID %2$s and share the UPI reference on your order page:', 'aurelia-commerce' ), wp_strip_all_tags( wc_price( $order->get_total() ) ), (string) $this->get_option( 'upi_id' ) );
		if ( $plain_text ) {
			echo esc_html( $text ) . ' ' . esc_url( $order->get_checkout_order_received_url() ) . "\n\n";
			return;
		}
		echo '<p>' . esc_html( $text ) . ' <a href="' . esc_url( $order->get_checkout_order_received_url() ) . '">' . esc_html__( 'Open payment page', 'aurelia-commerce' ) . '</a></p>';
	}
}
