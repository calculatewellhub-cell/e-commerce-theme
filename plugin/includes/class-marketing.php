<?php
/**
 * Newsletter sign-ups and the (off by default) newsletter / exit-intent popup.
 *
 * @package Aurelia_Commerce
 */

namespace Aurelia\Commerce;

defined( 'ABSPATH' ) || exit;

/**
 * Marketing.
 */
class Marketing {

	/**
	 * Hooks.
	 */
	public function init() {
		add_action( 'rest_api_init', array( $this, 'routes' ) );
		add_filter( 'aurelia_commerce_frontend_config', array( $this, 'config' ) );
	}

	/**
	 * Front-end config.
	 *
	 * @param array $config Config.
	 * @return array
	 */
	public function config( $config ) {
		$config['popup'] = Settings::on( 'popup_enabled' ) && ! ( function_exists( 'is_checkout' ) && ( is_checkout() || is_cart() ) ) ? array(
			'exit'  => Settings::on( 'popup_exit_intent' ),
			'delay' => (int) Settings::get( 'popup_delay', 30 ),
			'title' => (string) Settings::get( 'popup_title', '' ),
			'text'  => (string) Settings::get( 'popup_text', '' ),
		) : null;
		$config['i18n'] += array(
			'subscribe'      => __( 'Subscribe', 'aurelia-commerce' ),
			'emailLabel'     => __( 'Email address', 'aurelia-commerce' ),
			'emailPlace'     => __( 'Your email address', 'aurelia-commerce' ),
			'subscribed'     => __( 'Thank you! You are on the list.', 'aurelia-commerce' ),
			/* translators: %s: coupon code. */
			'subscribedCode' => __( 'Thank you! Use code %s at checkout.', 'aurelia-commerce' ),
			'subscribeError' => __( 'Please enter a valid email address.', 'aurelia-commerce' ),
			'noThanks'       => __( 'No thanks', 'aurelia-commerce' ),
		);
		return $config;
	}

	/**
	 * Routes.
	 */
	public function routes() {
		register_rest_route(
			'aurelia/v1',
			'/subscribe',
			array(
				'methods'             => 'POST',
				'permission_callback' => '__return_true',
				'callback'            => array( $this, 'subscribe' ),
			)
		);
	}

	/**
	 * Store a subscriber and forward to the configured webhook.
	 *
	 * @param \WP_REST_Request $request Request.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function subscribe( \WP_REST_Request $request ) {
		$email = sanitize_email( (string) $request->get_param( 'email' ) );
		if ( ! is_email( $email ) ) {
			return new \WP_Error( 'aurelia_email', __( 'Please enter a valid email address.', 'aurelia-commerce' ), array( 'status' => 400 ) );
		}
		if ( ! Helpers::rate_limit( 'subscribe', 5, HOUR_IN_SECONDS ) ) {
			return new \WP_Error( 'aurelia_rate_limited', __( 'Too many attempts. Please try again later.', 'aurelia-commerce' ), array( 'status' => 429 ) );
		}
		$source = sanitize_key( (string) $request->get_param( 'source' ) );
		global $wpdb;
		$wpdb->query( // phpcs:ignore WordPress.DB.DirectDatabaseQuery -- custom subscribers table.
			$wpdb->prepare(
				'INSERT IGNORE INTO %i (email, source, created_at) VALUES (%s, %s, %s)',
				$wpdb->prefix . 'aurelia_subscribers',
				strtolower( $email ),
				substr( $source, 0, 32 ),
				current_time( 'mysql', true )
			)
		);
		Analytics::record( 'newsletter_signup' );

		$webhook = (string) Settings::get( 'newsletter_webhook', '' );
		if ( '' !== $webhook ) {
			wp_remote_post(
				$webhook,
				array(
					'blocking' => false,
					'timeout'  => 5,
					'headers'  => array( 'Content-Type' => 'application/json' ),
					'body'     => wp_json_encode(
						array(
							'email'  => $email,
							'source' => $source,
							'store'  => Settings::store_name(),
						)
					),
				)
			);
		}
		/**
		 * Fires after a newsletter sign-up.
		 *
		 * @param string $email  Email.
		 * @param string $source Source (footer, popup...).
		 */
		do_action( 'aurelia_commerce_subscribed', $email, $source );

		return rest_ensure_response(
			array(
				'ok'     => true,
				'coupon' => 'popup' === $source ? (string) Settings::get( 'popup_coupon', '' ) : '',
			)
		);
	}
}
