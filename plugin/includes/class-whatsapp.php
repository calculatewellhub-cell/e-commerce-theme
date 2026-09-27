<?php
/**
 * WhatsApp ordering, enquiries and the floating chat button.
 *
 * Prices, names and SKUs are always read from WooCommerce on the server — the
 * browser only sends product IDs, variation IDs and quantities.
 *
 * @package Aurelia_Commerce
 */

namespace Aurelia\Commerce;

defined( 'ABSPATH' ) || exit;

/**
 * WhatsApp.
 */
class Whatsapp {

	const STATUS = 'whatsapp';

	/**
	 * Hooks.
	 */
	public function init() {
		add_action( 'init', array( $this, 'register_status' ) );
		add_filter( 'wc_order_statuses', array( $this, 'add_status' ) );
		add_filter( 'woocommerce_valid_order_statuses_for_payment', array( $this, 'payable_status' ) );
		add_filter( 'woocommerce_valid_order_statuses_for_cancel', array( $this, 'payable_status' ) );
		add_action( 'rest_api_init', array( $this, 'routes' ) );

		if ( ! Settings::on( 'wa_enabled' ) || '' === Settings::whatsapp_number() ) {
			return;
		}
		add_filter( 'aurelia_commerce_frontend_config', array( $this, 'config' ) );
		add_action( 'woocommerce_after_add_to_cart_form', array( $this, 'product_buttons' ) );
		add_action( 'woocommerce_proceed_to_checkout', array( $this, 'classic_cart_button' ), 30 );
		add_action( 'wp_footer', array( $this, 'float_button' ) );
		add_filter( 'body_class', array( $this, 'body_class' ) );
		add_action( 'template_redirect', array( $this, 'maybe_redirect_checkout' ) );
	}

	/**
	 * Register the "Pending (WhatsApp)" order status.
	 */
	public function register_status() {
		register_post_status(
			'wc-' . self::STATUS,
			array(
				'label'                     => _x( 'Pending (WhatsApp)', 'Order status', 'aurelia-commerce' ),
				'public'                    => false,
				'exclude_from_search'       => false,
				'show_in_admin_all_list'    => true,
				'show_in_admin_status_list' => true,
				/* translators: %s: number of orders. */
				'label_count'               => _n_noop( 'Pending (WhatsApp) <span class="count">(%s)</span>', 'Pending (WhatsApp) <span class="count">(%s)</span>', 'aurelia-commerce' ),
			)
		);
	}

	/**
	 * Add the status to WooCommerce's list, right after "Pending payment".
	 *
	 * @param array $statuses Statuses.
	 * @return array
	 */
	public function add_status( $statuses ) {
		$out = array();
		foreach ( $statuses as $key => $label ) {
			$out[ $key ] = $label;
			if ( 'wc-pending' === $key ) {
				$out[ 'wc-' . self::STATUS ] = _x( 'Pending (WhatsApp)', 'Order status', 'aurelia-commerce' );
			}
		}
		if ( ! isset( $out[ 'wc-' . self::STATUS ] ) ) {
			$out[ 'wc-' . self::STATUS ] = _x( 'Pending (WhatsApp)', 'Order status', 'aurelia-commerce' );
		}
		return $out;
	}

	/**
	 * WhatsApp orders can be paid online through the order-pay link.
	 *
	 * @param array $statuses Statuses.
	 * @return array
	 */
	public function payable_status( $statuses ) {
		$statuses[] = self::STATUS;
		return array_unique( $statuses );
	}

	/**
	 * Body class for "replace checkout" mode.
	 *
	 * @param array $classes Classes.
	 * @return array
	 */
	public function body_class( $classes ) {
		if ( 'replace' === Settings::get( 'wa_mode' ) ) {
			$classes[] = 'aurelia-wa-replace';
		}
		return $classes;
	}

	/**
	 * In "replace" mode the checkout page sends shoppers back to the cart,
	 * except for paying an existing order or viewing a receipt.
	 */
	public function maybe_redirect_checkout() {
		if ( 'replace' !== Settings::get( 'wa_mode' ) || ! is_checkout() || is_wc_endpoint_url( 'order-pay' ) || is_wc_endpoint_url( 'order-received' ) ) {
			return;
		}
		wp_safe_redirect( wc_get_cart_url() );
		exit;
	}

	/**
	 * Front-end config.
	 *
	 * @param array $config Config.
	 * @return array
	 */
	public function config( $config ) {
		$config['whatsapp'] = array(
			'number'      => Settings::whatsapp_number(),
			'mode'        => Settings::get( 'wa_mode' ),
			'cart'        => Settings::on( 'wa_cart_button' ),
			'askDetails'  => Settings::on( 'wa_ask_details' ),
			'store'       => Settings::store_name(),
		);
		$config['i18n'] += array(
			'waOrder'       => __( 'Order on WhatsApp', 'aurelia-commerce' ),
			'waChoose'      => __( 'Please choose your options first.', 'aurelia-commerce' ),
			'waDetails'     => __( 'Your delivery details', 'aurelia-commerce' ),
			'waDetailsNote' => __( 'Optional — helps us confirm faster. Saved only on this device.', 'aurelia-commerce' ),
			'waName'        => __( 'Name', 'aurelia-commerce' ),
			'waPhone'       => __( 'Phone', 'aurelia-commerce' ),
			'waAddress'     => __( 'Address', 'aurelia-commerce' ),
			'waCity'        => __( 'City', 'aurelia-commerce' ),
			'waPin'         => __( 'PIN code', 'aurelia-commerce' ),
			'waNotes'       => __( 'Notes', 'aurelia-commerce' ),
			'waContinue'    => __( 'Continue to WhatsApp', 'aurelia-commerce' ),
			'waSkip'        => __( 'Skip', 'aurelia-commerce' ),
			'waCancel'      => __( 'Cancel', 'aurelia-commerce' ),
			'waOpening'     => __( 'Opening WhatsApp…', 'aurelia-commerce' ),
			/* translators: 1: store name, 2: product name, 3: product URL. */
			'waEnquiry'     => __( 'Hello %1$s! I am interested in the *%2$s*.\n%3$s\n\nCould you share more photos and the delivery time?', 'aurelia-commerce' ),
			/* translators: 1: store name, 2: product name, 3: product URL. */
			'waVideo'       => __( 'Hello %1$s! I would like a live video call to see the *%2$s*.\n%3$s\n\nWhen would be a good time?', 'aurelia-commerce' ),
			/* translators: %s: store name. */
			'waHello'       => __( 'Hello %s! I have a question.', 'aurelia-commerce' ),
		);
		return $config;
	}

	/**
	 * Buttons under the add-to-cart form.
	 */
	public function product_buttons() {
		global $product;
		if ( ! $product instanceof \WC_Product ) {
			return;
		}
		$attrs = sprintf(
			'data-product="%1$d" data-name="%2$s" data-url="%3$s"',
			$product->get_id(),
			esc_attr( $product->get_name() ),
			esc_url( $product->get_permalink() )
		);
		echo '<div class="au-wa-actions">';
		if ( Settings::on( 'wa_product_button' ) && $product->is_purchasable() && $product->is_in_stock() ) {
			printf(
				'<button type="button" class="au-btn au-btn--whatsapp au-wa-order" %1$s>%2$s<span>%3$s</span></button>',
				$attrs, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped above.
				self::icon(), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static SVG.
				esc_html__( 'Order on WhatsApp', 'aurelia-commerce' )
			);
		}
		if ( Settings::on( 'wa_enquiry' ) || Settings::on( 'wa_video_call' ) ) {
			echo '<div class="au-wa-secondary">';
			if ( Settings::on( 'wa_enquiry' ) ) {
				printf( '<button type="button" class="au-btn au-btn--ghost au-wa-enquiry" data-kind="enquiry" %1$s>%2$s</button>', $attrs, esc_html__( 'Ask a question', 'aurelia-commerce' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped above.
			}
			if ( Settings::on( 'wa_video_call' ) ) {
				printf( '<button type="button" class="au-btn au-btn--ghost au-wa-enquiry" data-kind="video" %1$s>%2$s</button>', $attrs, esc_html__( 'Request a video call', 'aurelia-commerce' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped above.
			}
			echo '</div>';
		}
		echo '</div>';
	}

	/**
	 * Classic (shortcode) cart button. Block carts get the button from JS.
	 */
	public function classic_cart_button() {
		if ( ! Settings::on( 'wa_cart_button' ) ) {
			return;
		}
		printf( '<button type="button" class="au-btn au-btn--whatsapp au-wa-cart">%1$s<span>%2$s</span></button>', self::icon(), esc_html__( 'Order on WhatsApp', 'aurelia-commerce' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static SVG.
	}

	/**
	 * Floating WhatsApp button.
	 */
	public function float_button() {
		if ( ! Settings::on( 'wa_float' ) || ( function_exists( 'is_checkout' ) && is_checkout() ) ) {
			return;
		}
		/* translators: %s: store name. */
		$text = sprintf( __( 'Hello %s! I have a question.', 'aurelia-commerce' ), Settings::store_name() );
		printf(
			'<a class="au-wa-float is-%1$s" href="%2$s" target="_blank" rel="noopener noreferrer" data-au-track="whatsapp_enquiry" aria-label="%3$s">%4$s<span class="au-wa-float__label">%5$s</span></a>',
			esc_attr( 'left' === Settings::get( 'wa_float_position' ) ? 'left' : 'right' ),
			esc_url( Helpers::whatsapp_url( $text ) ),
			esc_attr__( 'Chat with us on WhatsApp (opens in a new tab)', 'aurelia-commerce' ),
			self::icon( 26 ), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static SVG.
			esc_html__( 'Chat with us', 'aurelia-commerce' )
		);
	}

	/**
	 * REST routes.
	 */
	public function routes() {
		register_rest_route(
			'aurelia/v1',
			'/whatsapp/order',
			array(
				'methods'             => 'POST',
				'permission_callback' => '__return_true',
				'callback'            => array( $this, 'order' ),
			)
		);
	}

	/**
	 * Build (and optionally record) a WhatsApp order, returning the wa.me URL.
	 *
	 * @param \WP_REST_Request $request Request.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function order( \WP_REST_Request $request ) {
		if ( ! Settings::on( 'wa_enabled' ) || '' === Settings::whatsapp_number() ) {
			return new \WP_Error( 'aurelia_wa_disabled', __( 'WhatsApp ordering is not configured.', 'aurelia-commerce' ), array( 'status' => 400 ) );
		}
		if ( ! Helpers::rate_limit( 'wa_order', 12, 10 * MINUTE_IN_SECONDS ) ) {
			return new \WP_Error( 'aurelia_rate_limited', __( 'Too many requests. Please try again shortly.', 'aurelia-commerce' ), array( 'status' => 429 ) );
		}

		$source   = 'cart' === $request->get_param( 'source' ) ? 'cart' : 'product';
		$customer = $this->clean_customer( (array) $request->get_param( 'customer' ) );
		$lines    = 'cart' === $source ? $this->cart_lines() : $this->product_lines( (array) $request->get_param( 'items' ) );

		if ( is_wp_error( $lines ) ) {
			return $lines;
		}
		if ( ! $lines ) {
			return new \WP_Error( 'aurelia_wa_empty', __( 'Your bag is empty.', 'aurelia-commerce' ), array( 'status' => 400 ) );
		}

		$total    = array_sum( array_column( $lines, 'total' ) );
		$ref      = '';
		$pay_link = '';
		if ( Settings::on( 'wa_create_order' ) ) {
			$order = $this->create_order( $lines, $customer );
			if ( ! is_wp_error( $order ) ) {
				$ref   = $order->get_order_number();
				$total = (float) $order->get_total();
				if ( Settings::on( 'wa_include_pay_link' ) && $order->needs_payment() ) {
					$pay_link = $order->get_checkout_payment_url();
				}
			}
		}
		if ( '' === $ref ) {
			$ref = strtoupper( 'W' . gmdate( 'ymd' ) . '-' . wp_generate_password( 4, false, false ) );
		}

		Analytics::record(
			'whatsapp_order',
			array(
				'value'      => $total,
				'product_id' => 1 === count( $lines ) ? $lines[0]['product_id'] : 0,
				'path'       => Helpers::path_of( (string) $request->get_header( 'referer' ) ),
			)
		);

		$message = $this->message( $lines, $total, $customer, $ref, $pay_link );

		return rest_ensure_response(
			array(
				'url' => Helpers::whatsapp_url( $message ),
				'ref' => $ref,
			)
		);
	}

	/**
	 * Lines from explicit product items.
	 *
	 * @param array $items Items.
	 * @return array|\WP_Error
	 */
	private function product_lines( array $items ) {
		$lines = array();
		foreach ( array_slice( $items, 0, 20 ) as $item ) {
			$product_id   = absint( $item['product_id'] ?? 0 );
			$variation_id = absint( $item['variation_id'] ?? 0 );
			$qty          = max( 1, min( 999, absint( $item['quantity'] ?? 1 ) ) );
			$product      = wc_get_product( $variation_id ? $variation_id : $product_id );
			if ( ! $product || 'publish' !== get_post_status( $product->get_parent_id() ? $product->get_parent_id() : $product->get_id() ) ) {
				return new \WP_Error( 'aurelia_wa_product', __( 'This product is not available.', 'aurelia-commerce' ), array( 'status' => 400 ) );
			}
			if ( $variation_id && $product->get_parent_id() !== $product_id ) {
				return new \WP_Error( 'aurelia_wa_variation', __( 'Invalid product option.', 'aurelia-commerce' ), array( 'status' => 400 ) );
			}
			if ( $product->is_type( 'variable' ) ) {
				return new \WP_Error( 'aurelia_wa_choose', __( 'Please choose your options first.', 'aurelia-commerce' ), array( 'status' => 400 ) );
			}
			$attributes = array();
			if ( $product->is_type( 'variation' ) ) {
				foreach ( (array) ( $item['attributes'] ?? array() ) as $key => $value ) {
					$attributes[ sanitize_title( (string) $key ) ] = sanitize_text_field( (string) $value );
				}
			}
			$lines[] = $this->line( $product, $qty, $attributes );
		}
		return $lines;
	}

	/**
	 * Lines from the current WooCommerce cart session.
	 *
	 * @return array
	 */
	private function cart_lines() {
		if ( function_exists( 'wc_load_cart' ) && null === WC()->cart ) {
			wc_load_cart();
		}
		$lines = array();
		foreach ( WC()->cart ? WC()->cart->get_cart() : array() as $item ) {
			if ( empty( $item['data'] ) || ! $item['data'] instanceof \WC_Product ) {
				continue;
			}
			$lines[] = $this->line( $item['data'], (int) $item['quantity'], (array) ( $item['variation'] ?? array() ), (float) $item['line_total'] + (float) $item['line_tax'] );
		}
		return $lines;
	}

	/**
	 * Normalise one order line.
	 *
	 * @param \WC_Product $product    Product or variation.
	 * @param int         $qty        Quantity.
	 * @param array       $attributes Chosen attributes (attribute_pa_x => value).
	 * @param float|null  $total      Line total override (cart).
	 * @return array
	 */
	private function line( $product, $qty, $attributes = array(), $total = null ) {
		$labels = array();
		if ( $product->is_type( 'variation' ) ) {
			$chosen = array_merge( $product->get_variation_attributes(), array_filter( $attributes ) );
			foreach ( $chosen as $key => $value ) {
				if ( '' === (string) $value ) {
					continue;
				}
				$taxonomy = str_replace( 'attribute_', '', $key );
				$term     = taxonomy_exists( $taxonomy ) ? get_term_by( 'slug', $value, $taxonomy ) : false;
				$labels[] = wc_attribute_label( $taxonomy, $product ) . ': ' . ( $term ? $term->name : $value );
			}
		}
		$parent = $product->get_parent_id() ? wc_get_product( $product->get_parent_id() ) : $product;
		return array(
			'product_id'   => $parent ? $parent->get_id() : $product->get_id(),
			'variation_id' => $product->is_type( 'variation' ) ? $product->get_id() : 0,
			'attributes'   => $product->is_type( 'variation' ) ? array_merge( $product->get_variation_attributes(), array_filter( $attributes ) ) : array(),
			'name'         => $parent ? $parent->get_name() : $product->get_name(),
			'options'      => implode( ', ', $labels ),
			'sku'          => $product->get_sku(),
			'qty'          => $qty,
			'price'        => (float) wc_get_price_to_display( $product ),
			'total'        => null !== $total ? $total : (float) wc_get_price_to_display( $product, array( 'qty' => $qty ) ),
			'url'          => $product->get_permalink(),
			'product'      => $product,
		);
	}

	/**
	 * Sanitize customer details.
	 *
	 * @param array $raw Raw input.
	 * @return array
	 */
	private function clean_customer( array $raw ) {
		$out = array();
		foreach ( array( 'name', 'phone', 'address', 'city', 'pincode', 'notes' ) as $key ) {
			$value = isset( $raw[ $key ] ) ? sanitize_text_field( (string) $raw[ $key ] ) : '';
			if ( '' !== $value ) {
				$out[ $key ] = mb_substr( $value, 0, 'notes' === $key || 'address' === $key ? 300 : 80 );
			}
		}
		return $out;
	}

	/**
	 * Create a WooCommerce order in "Pending (WhatsApp)" (HPOS-safe CRUD).
	 *
	 * @param array $lines    Lines.
	 * @param array $customer Customer details.
	 * @return \WC_Order|\WP_Error
	 */
	private function create_order( array $lines, array $customer ) {
		try {
			$order = wc_create_order(
				array(
					'status'      => self::STATUS,
					'customer_id' => get_current_user_id(),
					'created_via' => 'aurelia-whatsapp',
				)
			);
			if ( is_wp_error( $order ) ) {
				return $order;
			}
			foreach ( $lines as $line ) {
				$args = array();
				if ( $line['variation_id'] ) {
					$args['variation'] = $line['attributes'];
				}
				$order->add_product( $line['product'], $line['qty'], $args );
			}
			$name = explode( ' ', $customer['name'] ?? '', 2 );
			$order->set_billing_first_name( $name[0] ?? '' );
			$order->set_billing_last_name( $name[1] ?? '' );
			$order->set_billing_phone( $customer['phone'] ?? '' );
			$order->set_billing_address_1( $customer['address'] ?? '' );
			$order->set_billing_city( $customer['city'] ?? '' );
			$order->set_billing_postcode( $customer['pincode'] ?? '' );
			$order->set_billing_country( (string) Settings::get( 'store_country', 'IN' ) );
			$order->set_shipping_address( $order->get_address( 'billing' ) );
			if ( ! empty( $customer['notes'] ) ) {
				$order->set_customer_note( $customer['notes'] );
			}
			$order->calculate_totals();
			$order->add_order_note( __( 'Order started on WhatsApp. Confirm the details with the customer in your WhatsApp chat.', 'aurelia-commerce' ) );
			$order->update_meta_data( '_aurelia_whatsapp', 1 );
			$order->save();
			/**
			 * Fires after a WhatsApp order has been created.
			 *
			 * @param \WC_Order $order Order.
			 */
			do_action( 'aurelia_commerce_whatsapp_order_created', $order );
			return $order;
		} catch ( \Exception $e ) {
			return new \WP_Error( 'aurelia_wa_order', $e->getMessage() );
		}
	}

	/**
	 * The pre-filled WhatsApp message.
	 *
	 * @param array  $lines    Lines.
	 * @param float  $total    Total.
	 * @param array  $customer Customer.
	 * @param string $ref      Order reference.
	 * @param string $pay_link Payment link.
	 * @return string
	 */
	private function message( array $lines, $total, array $customer, $ref, $pay_link ) {
		$out = array();
		/* translators: %s: store name. */
		$out[] = '✨ *' . sprintf( __( 'New order — %s', 'aurelia-commerce' ), Settings::store_name() ) . '*';
		/* translators: %s: order reference. */
		$out[] = sprintf( __( 'Order ref: #%s', 'aurelia-commerce' ), $ref );
		$out[] = '';
		foreach ( $lines as $i => $line ) {
			$row = ( $i + 1 ) . '. *' . $line['name'] . '*' . ( '' !== $line['options'] ? ' (' . $line['options'] . ')' : '' );
			$sub = array();
			if ( '' !== $line['sku'] ) {
				/* translators: %s: SKU. */
				$sub[] = sprintf( __( 'SKU %s', 'aurelia-commerce' ), $line['sku'] );
			}
			/* translators: %d: quantity. */
			$sub[] = sprintf( __( 'Qty %d', 'aurelia-commerce' ), $line['qty'] );
			$sub[] = Helpers::money( $line['total'] );
			$out[] = $row . "\n   " . implode( ' · ', $sub ) . "\n   " . $line['url'];
		}
		$out[] = '';
		/* translators: %s: order total. */
		$out[] = '*' . sprintf( __( 'Order total: %s', 'aurelia-commerce' ), Helpers::money( $total ) ) . '*';

		$labels = array(
			'name'    => __( 'Name', 'aurelia-commerce' ),
			'phone'   => __( 'Phone', 'aurelia-commerce' ),
			'address' => __( 'Address', 'aurelia-commerce' ),
			'city'    => __( 'City', 'aurelia-commerce' ),
			'pincode' => __( 'PIN', 'aurelia-commerce' ),
			'notes'   => __( 'Notes', 'aurelia-commerce' ),
		);
		$who    = array();
		foreach ( $labels as $key => $label ) {
			if ( ! empty( $customer[ $key ] ) ) {
				$who[] = $label . ': ' . $customer[ $key ];
			}
		}
		if ( $who ) {
			$out[] = '';
			$out[] = '*' . __( 'Delivery details', 'aurelia-commerce' ) . '*';
			$out   = array_merge( $out, $who );
		}
		if ( '' !== $pay_link ) {
			$out[] = '';
			/* translators: %s: payment URL. */
			$out[] = sprintf( __( 'Pay online (UPI / card): %s', 'aurelia-commerce' ), $pay_link );
		}
		$out[] = '';
		$out[] = (string) Settings::get( 'wa_greeting', '' );
		return trim( implode( "\n", $out ) );
	}

	/**
	 * WhatsApp glyph.
	 *
	 * @param int $size Size in px.
	 * @return string
	 */
	public static function icon( $size = 18 ) {
		return '<svg width="' . (int) $size . '" height="' . (int) $size . '" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true" focusable="false"><path d="M17.5 14.4c-.3-.1-1.7-.8-2-.9-.3-.1-.5-.1-.7.1-.2.3-.8.9-.9 1.1-.2.2-.3.2-.6.1-.3-.1-1.2-.5-2.4-1.5-.9-.8-1.5-1.8-1.6-2.1-.2-.3 0-.5.1-.6l.4-.5c.1-.2.2-.3.3-.5.1-.2 0-.4 0-.5l-.9-2.2c-.2-.6-.5-.5-.7-.5h-.6c-.2 0-.5.1-.8.4-.3.3-1 1-1 2.5s1.1 2.9 1.2 3.1c.1.2 2.1 3.2 5.1 4.5.7.3 1.3.5 1.7.6.7.2 1.4.2 1.9.1.6-.1 1.7-.7 2-1.4.2-.7.2-1.3.2-1.4-.1-.1-.3-.2-.6-.3zM12 21.8c-1.8 0-3.5-.5-5-1.4l-.4-.2-3.7 1 1-3.6-.2-.4a9.8 9.8 0 1 1 8.3 4.6zM20.5 3.5A11.8 11.8 0 0 0 1.9 17.7L.2 24l6.4-1.7a11.8 11.8 0 0 0 5.6 1.4A11.8 11.8 0 0 0 20.5 3.5z"/></svg>';
	}
}
