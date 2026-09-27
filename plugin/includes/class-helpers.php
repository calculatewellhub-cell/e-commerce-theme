<?php
/**
 * Small shared helpers.
 *
 * @package Aurelia_Commerce
 */

namespace Aurelia\Commerce;

defined( 'ABSPATH' ) || exit;

/**
 * Static helpers.
 */
class Helpers {

	/**
	 * Anonymous, rotating visitor key used only for rate limiting.
	 * The IP is hashed with a daily salt and never stored.
	 *
	 * @return string
	 */
	public static function visitor_key() {
		$ip = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
		return substr( hash_hmac( 'sha256', $ip . gmdate( 'Y-m-d' ), wp_salt( 'nonce' ) ), 0, 24 );
	}

	/**
	 * Fixed-window rate limiter backed by transients.
	 *
	 * @param string $bucket Bucket name.
	 * @param int    $max    Allowed hits per window.
	 * @param int    $window Window in seconds.
	 * @return bool True when the request is allowed.
	 */
	public static function rate_limit( $bucket, $max, $window ) {
		$key   = 'aurelia_rl_' . md5( $bucket . '|' . self::visitor_key() );
		$count = (int) get_transient( $key );
		if ( $count >= $max ) {
			return false;
		}
		set_transient( $key, $count + 1, $window );
		return true;
	}

	/**
	 * Very small bot detector for analytics.
	 *
	 * @return bool
	 */
	public static function is_bot() {
		$ua = isset( $_SERVER['HTTP_USER_AGENT'] ) ? strtolower( sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) ) : '';
		return '' === $ua || (bool) preg_match( '/bot|crawl|spider|slurp|facebookexternalhit|preview|headless|lighthouse|pingdom|monitor|curl|wget|python|http-client/', $ua );
	}

	/**
	 * Plain-text money for messages (e.g. "₹1,299.00").
	 *
	 * @param float $amount Amount.
	 * @return string
	 */
	public static function money( $amount ) {
		if ( ! function_exists( 'wc_price' ) ) {
			return number_format_i18n( (float) $amount, 2 );
		}
		return html_entity_decode( wp_strip_all_tags( wc_price( $amount ) ), ENT_QUOTES, 'UTF-8' );
	}

	/**
	 * WhatsApp click-to-chat (wa.me) link.
	 *
	 * @param string $text   Pre-filled message.
	 * @param string $number Optional number override.
	 * @return string
	 */
	public static function whatsapp_url( $text, $number = '' ) {
		$number = '' !== $number ? preg_replace( '/\D+/', '', $number ) : Settings::whatsapp_number();
		return 'https://wa.me/' . rawurlencode( $number ) . '?text=' . rawurlencode( $text );
	}

	/**
	 * Main product image URL.
	 *
	 * @param \WC_Product $product Product.
	 * @param string      $size    Image size.
	 * @return string
	 */
	public static function product_image( $product, $size = 'woocommerce_thumbnail' ) {
		$id  = $product->get_image_id();
		$src = $id ? wp_get_attachment_image_url( $id, $size ) : '';
		return $src ? $src : wc_placeholder_img_src( $size );
	}

	/**
	 * Current request path (for analytics).
	 *
	 * @param string $url URL.
	 * @return string
	 */
	public static function path_of( $url ) {
		$path = (string) wp_parse_url( (string) $url, PHP_URL_PATH );
		return substr( '' === $path ? '/' : $path, 0, 190 );
	}

	/**
	 * Is the current front-end request a WooCommerce product/shop context?
	 *
	 * @return bool
	 */
	public static function is_shop_context() {
		return function_exists( 'is_woocommerce' ) && ( is_woocommerce() || is_cart() || is_checkout() );
	}

	/**
	 * Register a front-end script with standard options.
	 *
	 * @param string $handle Handle.
	 * @param string $file   File under assets/js.
	 * @param array  $deps   Dependencies.
	 */
	public static function register_script( $handle, $file, $deps = array() ) {
		wp_register_script(
			$handle,
			AURELIA_COMMERCE_URL . 'assets/js/' . $file,
			$deps,
			AURELIA_COMMERCE_VERSION,
			array(
				'strategy'  => 'defer',
				'in_footer' => true,
			)
		);
	}
}
