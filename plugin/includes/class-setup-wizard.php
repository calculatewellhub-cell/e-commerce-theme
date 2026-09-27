<?php
/**
 * Setup wizard: look, store details, WhatsApp & payments, AI & social,
 * demo import, launch.
 *
 * @package Aurelia_Commerce
 */

namespace Aurelia\Commerce;

defined( 'ABSPATH' ) || exit;

/**
 * Setup wizard.
 */
class Setup_Wizard {

	const SLUG = 'aurelia-setup';

	/**
	 * Hooks.
	 */
	public function init() {
		add_action( 'admin_menu', array( $this, 'menu' ), 40 );
		add_action( 'admin_init', array( $this, 'activation_redirect' ) );
		add_action( 'admin_post_aurelia_wizard', array( $this, 'save' ) );
		add_action( 'admin_notices', array( $this, 'notice' ) );
	}

	/**
	 * Step keys and labels.
	 *
	 * @return array
	 */
	private function steps() {
		return array(
			'look'     => __( 'Look', 'aurelia-commerce' ),
			'store'    => __( 'Store', 'aurelia-commerce' ),
			'payments' => __( 'WhatsApp & payments', 'aurelia-commerce' ),
			'ai'       => __( 'AI & social', 'aurelia-commerce' ),
			'demo'     => __( 'Demo content', 'aurelia-commerce' ),
			'done'     => __( 'Ready', 'aurelia-commerce' ),
		);
	}

	/**
	 * Admin page.
	 */
	public function menu() {
		add_submenu_page( Admin::SLUG, __( 'Setup wizard', 'aurelia-commerce' ), __( 'Setup wizard', 'aurelia-commerce' ), Admin::CAP, self::SLUG, array( $this, 'render' ) );
	}

	/**
	 * Redirect to the wizard once after activation.
	 */
	public function activation_redirect() {
		if ( ! get_transient( 'aurelia_commerce_activation_redirect' ) || wp_doing_ajax() || is_network_admin() || ! current_user_can( Admin::CAP ) ) {
			return;
		}
		delete_transient( 'aurelia_commerce_activation_redirect' );
		if ( isset( $_GET['activate-multi'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- core flag.
			return;
		}
		wp_safe_redirect( admin_url( 'admin.php?page=' . self::SLUG ) );
		exit;
	}

	/**
	 * Nudge until the wizard is finished.
	 */
	public function notice() {
		$screen = get_current_screen();
		if ( get_option( 'aurelia_commerce_wizard_done' ) || ! current_user_can( Admin::CAP ) || ( $screen && str_contains( (string) $screen->id, self::SLUG ) ) ) {
			return;
		}
		printf(
			'<div class="notice notice-info"><p><strong>%1$s</strong> %2$s <a class="button button-primary" href="%3$s">%4$s</a></p></div>',
			esc_html__( 'Welcome to Aurelia!', 'aurelia-commerce' ),
			esc_html__( 'Set up your store look, WhatsApp, payments and demo content in a few minutes.', 'aurelia-commerce' ),
			esc_url( admin_url( 'admin.php?page=' . self::SLUG ) ),
			esc_html__( 'Run the setup wizard', 'aurelia-commerce' )
		);
	}

	/**
	 * Theme style variations (Aurelia only).
	 *
	 * @return array title => variation.
	 */
	private function variations() {
		if ( 'aurelia' !== get_template() || ! class_exists( 'WP_Theme_JSON_Resolver' ) ) {
			return array();
		}
		$out = array( 'Luxe' => array() );
		foreach ( \WP_Theme_JSON_Resolver::get_style_variations() as $variation ) {
			if ( ! empty( $variation['title'] ) ) {
				$out[ $variation['title'] ] = $variation;
			}
		}
		return $out;
	}

	/**
	 * Apply a theme style variation to the site's global styles.
	 *
	 * @param string $title Variation title ("Luxe" resets to the default).
	 */
	private function apply_variation( $title ) {
		$variations = $this->variations();
		if ( ! isset( $variations[ $title ] ) ) {
			return;
		}
		$user = \WP_Theme_JSON_Resolver::get_user_data_from_wp_global_styles( wp_get_theme(), true );
		if ( empty( $user['ID'] ) ) {
			return;
		}
		$variation = $variations[ $title ];
		$config    = array(
			'version'                     => 3,
			'isGlobalStylesUserThemeJSON' => true,
			'settings'                    => $variation['settings'] ?? new \stdClass(),
			'styles'                      => $variation['styles'] ?? new \stdClass(),
		);
		if ( 'Luxe' !== $title ) {
			$config['title'] = $title;
		}
		wp_update_post(
			array(
				'ID'           => $user['ID'],
				'post_content' => wp_slash( wp_json_encode( $config ) ),
			)
		);
		if ( class_exists( 'WP_Theme_JSON_Resolver' ) ) {
			\WP_Theme_JSON_Resolver::clean_cached_data();
		}
	}

	/**
	 * Save a step.
	 */
	public function save() {
		if ( ! current_user_can( Admin::CAP ) ) {
			wp_die( esc_html__( 'You are not allowed to do this.', 'aurelia-commerce' ) );
		}
		check_admin_referer( 'aurelia_wizard' );
		$step = isset( $_POST['step'] ) ? sanitize_key( wp_unslash( $_POST['step'] ) ) : '';
		$post = static fn( $key ) => isset( $_POST[ $key ] ) ? sanitize_text_field( wp_unslash( $_POST[ $key ] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing -- nonce checked above.

		switch ( $step ) {
			case 'look':
				Settings::update( array( 'store_type' => $post( 'store_type' ) ) );
				$this->apply_variation( $post( 'variation' ) );
				break;
			case 'store':
				$country = strtoupper( substr( $post( 'country' ), 0, 2 ) );
				$state   = strtoupper( substr( $post( 'state' ), 0, 3 ) );
				update_option( 'blogname', $post( 'store_name' ) );
				update_option( 'woocommerce_store_address', $post( 'address' ) );
				update_option( 'woocommerce_store_city', $post( 'city' ) );
				update_option( 'woocommerce_store_postcode', $post( 'postcode' ) );
				update_option( 'woocommerce_default_country', $country . ( '' !== $state ? ':' . $state : '' ) );
				if ( 'IN' === $country ) {
					update_option( 'woocommerce_currency', 'INR' );
					if ( in_array( wp_timezone_string(), array( 'UTC', '+00:00' ), true ) ) {
						update_option( 'timezone_string', 'Asia/Kolkata' );
					}
				}
				Settings::update(
					array(
						'store_name'     => $post( 'store_name' ),
						'store_phone'    => $post( 'phone' ),
						'store_email'    => sanitize_email( $post( 'email' ) ),
						'store_address'  => $post( 'address' ),
						'store_city'     => $post( 'city' ),
						'store_region'   => $post( 'state' ),
						'store_postcode' => $post( 'postcode' ),
						'store_country'  => $country,
					)
				);
				break;
			case 'payments':
				Settings::update(
					array(
						'wa_number' => preg_replace( '/\D+/', '', $post( 'wa_number' ) ),
						'wa_mode'   => $post( 'wa_mode' ),
						'wa_enabled' => '' !== $post( 'wa_number' ) ? 1 : 0,
					)
				);
				$cod            = (array) get_option( 'woocommerce_cod_settings', array() );
				$cod['enabled'] = empty( $_POST['cod'] ) ? 'no' : 'yes'; // phpcs:ignore WordPress.Security.NonceVerification.Missing -- nonce checked above.
				update_option( 'woocommerce_cod_settings', $cod );
				$upi   = (array) get_option( 'woocommerce_aurelia_upi_settings', array() );
				$vpa   = $post( 'upi_id' );
				$upi   = array_merge(
					$upi,
					array(
						'enabled'    => Upi_Gateway::is_vpa( $vpa ) ? 'yes' : 'no',
						'upi_id'     => Upi_Gateway::is_vpa( $vpa ) ? $vpa : '',
						'payee_name' => '' !== $post( 'payee' ) ? $post( 'payee' ) : get_bloginfo( 'name' ),
					)
				);
				update_option( 'woocommerce_aurelia_upi_settings', $upi );
				break;
			case 'ai':
				Settings::update(
					array(
						'anthropic_key'  => $post( 'anthropic_key' ),
						'ai_name'        => '' !== $post( 'ai_name' ) ? $post( 'ai_name' ) : 'Aria',
						'social_webhook' => esc_url_raw( $post( 'social_webhook' ) ),
					)
				);
				break;
			case 'done':
				update_option( 'woocommerce_coming_soon', 'no' );
				update_option( 'aurelia_commerce_wizard_done', 1 );
				break;
		}
		$keys = array_keys( $this->steps() );
		$next = $keys[ array_search( $step, $keys, true ) + 1 ] ?? 'done';
		wp_safe_redirect( admin_url( 'admin.php?page=' . self::SLUG . '&step=' . $next ) );
		exit;
	}

	/**
	 * Render the wizard.
	 */
	public function render() {
		$steps = $this->steps();
		$step  = isset( $_GET['step'] ) ? sanitize_key( wp_unslash( $_GET['step'] ) ) : 'look'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- navigation only.
		$step  = isset( $steps[ $step ] ) ? $step : 'look';
		$keys  = array_keys( $steps );
		$index = array_search( $step, $keys, true );
		?>
		<div class="wrap aurelia-admin aurelia-wizard">
			<h1><?php esc_html_e( 'Set up your Aurelia store', 'aurelia-commerce' ); ?></h1>
			<ol class="aurelia-wizard__steps">
				<?php foreach ( $steps as $key => $label ) : ?>
					<?php $i = array_search( $key, $keys, true ); ?>
					<li class="<?php echo esc_attr( $i < $index ? 'is-done' : ( $i === $index ? 'is-current' : '' ) ); ?>" <?php echo $i === $index ? 'aria-current="step"' : ''; ?>><?php echo esc_html( $label ); ?></li>
				<?php endforeach; ?>
			</ol>
			<div class="au-card">
				<?php
				if ( 'demo' === $step ) {
					$this->render_demo();
				} else {
					echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
					wp_nonce_field( 'aurelia_wizard' );
					echo '<input type="hidden" name="action" value="aurelia_wizard"><input type="hidden" name="step" value="' . esc_attr( $step ) . '">';
					$this->{'render_' . $step}();
					echo '<div class="aurelia-wizard__footer">';
					if ( 'done' !== $step ) {
						printf( '<a href="%1$s">%2$s</a>', esc_url( admin_url( 'admin.php?page=' . self::SLUG . '&step=' . ( $keys[ $index + 1 ] ?? 'done' ) ) ), esc_html__( 'Skip this step', 'aurelia-commerce' ) );
						submit_button( __( 'Save & continue', 'aurelia-commerce' ), 'primary', 'submit', false );
					} else {
						submit_button( __( 'Launch my store', 'aurelia-commerce' ), 'primary', 'submit', false );
					}
					echo '</div></form>';
				}
				?>
			</div>
		</div>
		<?php
	}

	/**
	 * Row helper.
	 *
	 * @param string $label Label.
	 * @param string $name  Field name.
	 * @param string $value Value.
	 * @param string $type  Input type.
	 * @param string $help  Help text.
	 */
	private function row( $label, $name, $value, $type = 'text', $help = '' ) {
		printf(
			'<tr><th scope="row"><label for="aw-%1$s">%2$s</label></th><td><input id="aw-%1$s" name="%1$s" type="%3$s" value="%4$s" class="regular-text"%6$s>%5$s</td></tr>',
			esc_attr( $name ),
			esc_html( $label ),
			esc_attr( $type ),
			esc_attr( $value ),
			$help ? '<p class="description" id="aw-' . esc_attr( $name ) . '-desc">' . esc_html( $help ) . '</p>' : '',
			$help ? ' aria-describedby="aw-' . esc_attr( $name ) . '-desc"' : ''
		);
	}

	/**
	 * Step: look.
	 */
	private function render_look() {
		$current_type = (string) Settings::get( 'store_type', 'Store' );
		$types        = Settings::schema()['general']['fields']['store_type']['options'];
		echo '<h2>' . esc_html__( 'What do you sell, and how should it look?', 'aurelia-commerce' ) . '</h2>';
		echo '<table class="form-table" role="presentation"><tr><th scope="row"><label for="aw-type">' . esc_html__( 'Store type', 'aurelia-commerce' ) . '</label></th><td><select id="aw-type" name="store_type">';
		foreach ( $types as $key => $label ) {
			printf( '<option value="%1$s" %2$s>%3$s</option>', esc_attr( $key ), selected( $current_type, $key, false ), esc_html( $label ) );
		}
		echo '</select></td></tr>';
		$variations = $this->variations();
		if ( $variations ) {
			echo '<tr><th scope="row">' . esc_html__( 'Style', 'aurelia-commerce' ) . '</th><td><fieldset><legend class="screen-reader-text">' . esc_html__( 'Style', 'aurelia-commerce' ) . '</legend>';
			foreach ( array_keys( $variations ) as $i => $title ) {
				$palette = $variations[ $title ]['settings']['color']['palette']['theme'] ?? $variations[ $title ]['settings']['color']['palette'] ?? array();
				if ( ! $palette && 'Luxe' === $title ) {
					$palette = wp_get_global_settings( array( 'color', 'palette', 'theme' ) );
				}
				$dots = '';
				foreach ( (array) $palette as $color ) {
					if ( in_array( $color['slug'] ?? '', array( 'base', 'primary', 'accent', 'primary-3' ), true ) ) {
						$dots .= '<span style="display:inline-block;width:14px;height:14px;border-radius:50%;margin-inline-end:3px;border:1px solid #ccc;background:' . esc_attr( $color['color'] ) . '"></span>';
					}
				}
				printf( '<label style="display:block;margin:6px 0"><input type="radio" name="variation" value="%1$s" %2$s> %3$s %4$s</label>', esc_attr( $title ), checked( 0, $i, false ), $dots, esc_html( $title ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- $dots built with esc_attr.
			}
			echo '<p class="description">' . esc_html__( 'You can switch any time in Appearance → Editor → Styles.', 'aurelia-commerce' ) . '</p></fieldset></td></tr>';
		}
		echo '</table>';
	}

	/**
	 * Step: store.
	 */
	private function render_store() {
		$country = explode( ':', (string) get_option( 'woocommerce_default_country', 'IN:MH' ) );
		echo '<h2>' . esc_html__( 'Your store details', 'aurelia-commerce' ) . '</h2><p>' . esc_html__( 'Used in emails, invoices, WhatsApp messages and search results.', 'aurelia-commerce' ) . '</p><table class="form-table" role="presentation">';
		$this->row( __( 'Store name', 'aurelia-commerce' ), 'store_name', Settings::store_name() );
		$this->row( __( 'Phone', 'aurelia-commerce' ), 'phone', (string) Settings::get( 'store_phone' ), 'tel' );
		$this->row( __( 'Email', 'aurelia-commerce' ), 'email', (string) Settings::get( 'store_email', get_option( 'admin_email' ) ), 'email' );
		$this->row( __( 'Address', 'aurelia-commerce' ), 'address', (string) get_option( 'woocommerce_store_address' ) );
		$this->row( __( 'City', 'aurelia-commerce' ), 'city', (string) get_option( 'woocommerce_store_city' ) );
		$this->row( __( 'State code', 'aurelia-commerce' ), 'state', $country[1] ?? 'MH', 'text', __( 'For India, e.g. MH, DL, KA, TN.', 'aurelia-commerce' ) );
		$this->row( __( 'PIN / postcode', 'aurelia-commerce' ), 'postcode', (string) get_option( 'woocommerce_store_postcode' ) );
		$this->row( __( 'Country code', 'aurelia-commerce' ), 'country', $country[0] ? $country[0] : 'IN' );
		echo '</table>';
	}

	/**
	 * Step: WhatsApp & payments.
	 */
	private function render_payments() {
		$upi = (array) get_option( 'woocommerce_aurelia_upi_settings', array() );
		$cod = (array) get_option( 'woocommerce_cod_settings', array() );
		echo '<h2>' . esc_html__( 'WhatsApp ordering & payments', 'aurelia-commerce' ) . '</h2><table class="form-table" role="presentation">';
		$this->row( __( 'WhatsApp number', 'aurelia-commerce' ), 'wa_number', (string) Settings::get( 'wa_number' ), 'tel', __( 'With country code, e.g. 919876543210. Orders and enquiries open a chat with this number.', 'aurelia-commerce' ) );
		echo '<tr><th scope="row"><label for="aw-wa_mode">' . esc_html__( 'WhatsApp mode', 'aurelia-commerce' ) . '</label></th><td><select id="aw-wa_mode" name="wa_mode"><option value="alongside" ' . selected( Settings::get( 'wa_mode' ), 'alongside', false ) . '>' . esc_html__( 'Alongside normal checkout', 'aurelia-commerce' ) . '</option><option value="replace" ' . selected( Settings::get( 'wa_mode' ), 'replace', false ) . '>' . esc_html__( 'Replace checkout (WhatsApp only)', 'aurelia-commerce' ) . '</option></select></td></tr>';
		echo '<tr><th scope="row">' . esc_html__( 'Cash on delivery', 'aurelia-commerce' ) . '</th><td><label><input type="checkbox" name="cod" value="1" ' . checked( 'no' !== ( $cod['enabled'] ?? 'yes' ), true, false ) . '> ' . esc_html__( 'Accept cash on delivery', 'aurelia-commerce' ) . '</label></td></tr>';
		$this->row( __( 'UPI ID for QR payments', 'aurelia-commerce' ), 'upi_id', (string) ( $upi['upi_id'] ?? '' ), 'text', __( 'e.g. yourshop@okhdfcbank. Customers scan a QR or tap "Pay with UPI app" (PhonePe, Google Pay, Paytm…).', 'aurelia-commerce' ) );
		$this->row( __( 'Payee name', 'aurelia-commerce' ), 'payee', (string) ( $upi['payee_name'] ?? get_bloginfo( 'name' ) ) );
		echo '</table><p class="description">' . esc_html__( 'Prefer a payment gateway? Install Razorpay, PhonePe PG or Cashfree from Plugins → Add New; Aurelia styles their checkout automatically.', 'aurelia-commerce' ) . '</p>';
	}

	/**
	 * Step: AI & social.
	 */
	private function render_ai() {
		echo '<h2>' . esc_html__( 'AI concierge & social posting (optional)', 'aurelia-commerce' ) . '</h2><table class="form-table" role="presentation">';
		$this->row( __( 'Claude API key', 'aurelia-commerce' ), 'anthropic_key', '', 'password', Claude_Client::has_key() ? __( 'A key is already saved — leave blank to keep it.', 'aurelia-commerce' ) : __( 'From console.anthropic.com. Stored on your server only. Without it, a rule-based assistant still answers from your catalogue.', 'aurelia-commerce' ) );
		$this->row( __( 'Assistant name', 'aurelia-commerce' ), 'ai_name', (string) Settings::get( 'ai_name', 'Aria' ) );
		$this->row( __( 'Social webhook (Make, Zapier, n8n)', 'aurelia-commerce' ), 'social_webhook', (string) Settings::get( 'social_webhook' ), 'url', __( 'Optional. Or connect Facebook & Instagram later in Settings → Social posting.', 'aurelia-commerce' ) );
		echo '</table>';
	}

	/**
	 * Step: demo import (runs over REST with progress).
	 */
	private function render_demo() {
		$imported = get_option( 'aurelia_commerce_demo_imported' );
		wp_enqueue_script( 'aurelia-wizard', AURELIA_COMMERCE_URL . 'assets/js/admin-wizard.js', array( 'wp-api-fetch' ), AURELIA_COMMERCE_VERSION, true );
		wp_localize_script(
			'aurelia-wizard',
			'aureliaWizard',
			array(
				'next'     => admin_url( 'admin.php?page=' . self::SLUG . '&step=done' ),
				'working'  => __( 'Importing… please keep this tab open.', 'aurelia-commerce' ),
				'done'     => __( 'Done! Continue to the last step.', 'aurelia-commerce' ),
				'removed'  => __( 'Demo content removed.', 'aurelia-commerce' ),
				'confirm'  => __( 'Delete all demo products, pages, menu and images?', 'aurelia-commerce' ),
			)
		);
		echo '<h2>' . esc_html__( 'Make it look like the demo', 'aurelia-commerce' ) . '</h2>';
		echo '<p>' . esc_html__( 'Imports 26 sample products across jewellery, fashion, electronics, beauty, grocery and home — with images, variations, swatches, 3D rings, reviews and coupons — plus Home, About, Contact, FAQ, Shipping, Wishlist, Compare and Track-order pages and a mega menu. You can remove it all later in one click.', 'aurelia-commerce' ) . '</p>';
		if ( $imported ) {
			echo '<div class="notice notice-success inline"><p>' . esc_html__( 'Demo content is already imported.', 'aurelia-commerce' ) . '</p></div>';
		}
		echo '<p><button type="button" class="button button-primary button-hero" id="aurelia-demo-import">' . esc_html__( 'Import demo content', 'aurelia-commerce' ) . '</button> ';
		if ( $imported ) {
			echo '<button type="button" class="button" id="aurelia-demo-remove">' . esc_html__( 'Remove demo content', 'aurelia-commerce' ) . '</button>';
		}
		echo '</p><progress id="aurelia-demo-progress" max="100" value="0" hidden style="width:100%"></progress><div class="aurelia-wizard__log" id="aurelia-demo-log" role="log" aria-live="polite" hidden></div>';
		echo '<div class="aurelia-wizard__footer"><span></span><a class="button" href="' . esc_url( admin_url( 'admin.php?page=' . self::SLUG . '&step=done' ) ) . '">' . esc_html__( 'Skip', 'aurelia-commerce' ) . '</a></div>';
	}

	/**
	 * Step: done.
	 */
	private function render_done() {
		echo '<h2>' . esc_html__( 'Your store is ready 🎉', 'aurelia-commerce' ) . '</h2><ul class="ul-disc">';
		$links = array(
			home_url( '/' )                                       => __( 'View your store', 'aurelia-commerce' ),
			admin_url( 'site-editor.php?p=%2Fstyles' )            => __( 'Change colours, fonts and style variation', 'aurelia-commerce' ),
			admin_url( 'post-new.php?post_type=product' )         => __( 'Add your first product', 'aurelia-commerce' ),
			admin_url( 'admin.php?page=aurelia-settings' )        => __( 'Fine-tune Aurelia settings', 'aurelia-commerce' ),
			admin_url( 'admin.php?page=aurelia-video-studio' )    => __( 'Make a product video', 'aurelia-commerce' ),
			admin_url( 'admin.php?page=wc-settings&tab=checkout' ) => __( 'Review payment methods', 'aurelia-commerce' ),
		);
		foreach ( $links as $url => $label ) {
			printf( '<li><a href="%1$s">%2$s</a></li>', esc_url( $url ), esc_html( $label ) );
		}
		echo '</ul><p>' . esc_html__( 'Launching turns off WooCommerce "Coming soon" mode so shoppers can see your store.', 'aurelia-commerce' ) . '</p>';
	}
}
