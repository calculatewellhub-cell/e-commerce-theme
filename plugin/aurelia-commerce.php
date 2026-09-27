<?php
/**
 * Plugin Name:       Aurelia Commerce
 * Plugin URI:        https://github.com/calculatewellhub-cell/e-commerce-theme
 * Description:       Store features for any WooCommerce shop: WhatsApp ordering, UPI QR payments, an AI shopping concierge (Claude), 3D product viewer, image-to-video studio, social auto-posting with tracked links, privacy-friendly analytics, SEO/AIO structured data, wishlist, compare, quick view, swatches and a one-click demo import.
 * Version:           1.0.0
 * Requires at least: 6.6
 * Requires PHP:      8.1
 * Requires Plugins:  woocommerce
 * Author:            Aurelia Commerce
 * Author URI:        https://github.com/calculatewellhub-cell
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       aurelia-commerce
 * Domain Path:       /languages
 * WC requires at least: 9.0
 * WC tested up to:   11.1
 *
 * @package Aurelia_Commerce
 */

defined( 'ABSPATH' ) || exit;

define( 'AURELIA_COMMERCE_VERSION', '1.0.0' );
define( 'AURELIA_COMMERCE_FILE', __FILE__ );
define( 'AURELIA_COMMERCE_DIR', plugin_dir_path( __FILE__ ) );
define( 'AURELIA_COMMERCE_URL', plugin_dir_url( __FILE__ ) );

/**
 * Autoloader: Aurelia\Commerce\Foo_Bar => includes/class-foo-bar.php.
 */
spl_autoload_register(
	static function ( $class_name ) {
		$prefix = 'Aurelia\\Commerce\\';
		if ( ! str_starts_with( $class_name, $prefix ) ) {
			return;
		}
		$relative = substr( $class_name, strlen( $prefix ) );
		$file     = AURELIA_COMMERCE_DIR . 'includes/class-' . strtolower( str_replace( array( '_', '\\' ), array( '-', '/' ), $relative ) ) . '.php';
		if ( is_readable( $file ) ) {
			require $file;
		}
	}
);

// Declare compatibility with WooCommerce High-Performance Order Storage and the Cart & Checkout blocks.
add_action(
	'before_woocommerce_init',
	static function () {
		if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', AURELIA_COMMERCE_FILE, true );
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'cart_checkout_blocks', AURELIA_COMMERCE_FILE, true );
		}
	}
);

register_activation_hook( __FILE__, array( 'Aurelia\\Commerce\\Installer', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'Aurelia\\Commerce\\Installer', 'deactivate' ) );

add_action( 'plugins_loaded', array( 'Aurelia\\Commerce\\Plugin', 'instance' ), 20 );
