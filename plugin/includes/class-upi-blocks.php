<?php
/**
 * Block checkout integration for the UPI gateway.
 *
 * @package Aurelia_Commerce
 */

namespace Aurelia\Commerce;

use Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType;

defined( 'ABSPATH' ) || exit;

/**
 * UPI payment method type for the Checkout block.
 */
final class Upi_Blocks extends AbstractPaymentMethodType {

	/**
	 * Payment method name (matches the gateway ID).
	 *
	 * @var string
	 */
	protected $name = 'aurelia_upi';

	/**
	 * Load settings.
	 */
	public function initialize() {
		$this->settings = get_option( 'woocommerce_aurelia_upi_settings', array() );
	}

	/**
	 * Active when enabled and a UPI ID is set.
	 *
	 * @return bool
	 */
	public function is_active() {
		return 'yes' === ( $this->settings['enabled'] ?? 'no' ) && Upi_Gateway::is_vpa( (string) ( $this->settings['upi_id'] ?? '' ) );
	}

	/**
	 * Script handles.
	 *
	 * @return string[]
	 */
	public function get_payment_method_script_handles() {
		wp_register_script(
			'aurelia-upi-blocks',
			AURELIA_COMMERCE_URL . 'assets/js/upi-blocks.js',
			array( 'wc-blocks-registry', 'wc-settings', 'wp-element', 'wp-html-entities' ),
			AURELIA_COMMERCE_VERSION,
			true
		);
		return array( 'aurelia-upi-blocks' );
	}

	/**
	 * Data passed to the script.
	 *
	 * @return array
	 */
	public function get_payment_method_data() {
		return array(
			'title'       => $this->settings['title'] ?? __( 'UPI', 'aurelia-commerce' ),
			'description' => $this->settings['description'] ?? '',
			'icon'        => AURELIA_COMMERCE_URL . 'assets/img/upi.svg',
			'supports'    => array( 'products' ),
		);
	}
}
