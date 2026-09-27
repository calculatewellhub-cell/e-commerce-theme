<?php
/**
 * UPI QR payments module: registers the gateway, its block-checkout
 * integration and the "I have paid" confirmation endpoint.
 *
 * @package Aurelia_Commerce
 */

namespace Aurelia\Commerce;

defined( 'ABSPATH' ) || exit;

/**
 * UPI payments module.
 */
class Upi_Payments {

	/**
	 * Hooks.
	 */
	public function init() {
		add_filter( 'woocommerce_payment_gateways', array( $this, 'register_gateway' ) );
		add_action( 'woocommerce_blocks_payment_method_type_registration', array( $this, 'register_blocks' ) );
		add_action( 'rest_api_init', array( $this, 'routes' ) );
	}

	/**
	 * Add the gateway.
	 *
	 * @param array $gateways Gateways.
	 * @return array
	 */
	public function register_gateway( $gateways ) {
		$gateways[] = Upi_Gateway::class;
		return $gateways;
	}

	/**
	 * Block checkout integration.
	 *
	 * @param \Automattic\WooCommerce\Blocks\Payments\PaymentMethodRegistry $registry Registry.
	 */
	public function register_blocks( $registry ) {
		if ( class_exists( \Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType::class ) ) {
			$registry->register( new Upi_Blocks() );
		}
	}

	/**
	 * Confirmation endpoint.
	 */
	public function routes() {
		register_rest_route(
			'aurelia/v1',
			'/upi/confirm',
			array(
				'methods'             => 'POST',
				'permission_callback' => '__return_true',
				'callback'            => array( $this, 'confirm' ),
				'args'                => array(
					'order_id'  => array(
						'type'     => 'integer',
						'required' => true,
					),
					'order_key' => array(
						'type'     => 'string',
						'required' => true,
					),
					'utr'       => array(
						'type'     => 'string',
						'required' => true,
					),
				),
			)
		);
	}

	/**
	 * Customer submits the UPI transaction reference (UTR) after paying.
	 * The order key proves ownership; the merchant verifies the payment manually.
	 *
	 * @param \WP_REST_Request $request Request.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function confirm( \WP_REST_Request $request ) {
		if ( ! Helpers::rate_limit( 'upi_confirm', 10, 10 * MINUTE_IN_SECONDS ) ) {
			return new \WP_Error( 'aurelia_rate_limited', __( 'Too many attempts. Please try again later.', 'aurelia-commerce' ), array( 'status' => 429 ) );
		}
		$order = wc_get_order( absint( $request->get_param( 'order_id' ) ) );
		$key   = sanitize_text_field( (string) $request->get_param( 'order_key' ) );
		$utr   = strtoupper( preg_replace( '/[^A-Za-z0-9]/', '', (string) $request->get_param( 'utr' ) ) );

		if ( ! $order || ! hash_equals( $order->get_order_key(), $key ) || 'aurelia_upi' !== $order->get_payment_method() ) {
			return new \WP_Error( 'aurelia_upi_order', __( 'Order not found.', 'aurelia-commerce' ), array( 'status' => 404 ) );
		}
		if ( strlen( $utr ) < 6 || strlen( $utr ) > 35 ) {
			return new \WP_Error( 'aurelia_upi_utr', __( 'Please enter the 12-digit UPI reference (UTR) shown in your payment app.', 'aurelia-commerce' ), array( 'status' => 400 ) );
		}
		if ( ! $order->has_status( array( 'on-hold', 'pending' ) ) ) {
			return rest_ensure_response( array( 'ok' => true ) );
		}

		$order->update_meta_data( '_aurelia_upi_utr', $utr );
		$order->set_transaction_id( $utr );
		/* translators: %s: UPI transaction reference. */
		$order->update_status( 'on-hold', sprintf( __( 'Customer reports UPI payment with reference %s. Verify it in your bank or UPI app, then mark the order as Processing.', 'aurelia-commerce' ), $utr ) );
		$order->save();

		Analytics::record( 'upi_payment', array( 'value' => (float) $order->get_total() ) );

		return rest_ensure_response(
			array(
				'ok'      => true,
				'message' => __( 'Thank you! We have received your payment reference and will confirm your order shortly.', 'aurelia-commerce' ),
			)
		);
	}
}
