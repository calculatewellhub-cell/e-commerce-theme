<?php
/**
 * Plugin bootstrap and module loader.
 *
 * @package Aurelia_Commerce
 */

namespace Aurelia\Commerce;

defined( 'ABSPATH' ) || exit;

/**
 * Main plugin class.
 */
final class Plugin {

	/**
	 * Singleton.
	 *
	 * @var Plugin|null
	 */
	private static $instance = null;

	/**
	 * Get the instance.
	 *
	 * @return Plugin
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Boot.
	 */
	private function __construct() {
		add_action( 'init', array( $this, 'load_textdomain' ), 0 );
		add_filter( 'cron_schedules', array( self::class, 'add_cron_schedule' ) ); // phpcs:ignore WordPress.WP.CronInterval.CronSchedulesInterval -- 5-minute social publishing tick is intentional.

		if ( ! class_exists( 'WooCommerce' ) ) {
			add_action( 'admin_notices', array( $this, 'missing_woocommerce_notice' ) );
			return;
		}

		Installer::maybe_upgrade();

		// Modules read translated settings, so boot them right after translations load.
		add_action( 'init', array( $this, 'boot_modules' ), 1 );
		add_action( 'init', array( $this, 'maybe_flush_rewrites' ), 99 );
		add_action( 'init', array( $this, 'ensure_cron' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'register_assets' ), 5 );
	}


	/**
	 * Instantiate feature modules.
	 */
	public function boot_modules() {
		$modules = array(
			Admin::class,
			Whatsapp::class,
			Upi_Payments::class,
			Concierge::class,
			Viewer::class,
			Shop::class,
			Swatches::class,
			Reviews::class,
			Marketing::class,
			Analytics::class,
			Social::class,
			Video_Studio::class,
			Seo::class,
			Setup_Wizard::class,
			Demo_Importer::class,
			Privacy::class,
		);
		foreach ( $modules as $module ) {
			( new $module() )->init();
		}
	}

	/**
	 * Load translations.
	 */
	public function load_textdomain() {
		load_plugin_textdomain( 'aurelia-commerce', false, dirname( plugin_basename( AURELIA_COMMERCE_FILE ) ) . '/languages' );
	}

	/**
	 * Five-minute cron schedule for social publishing.
	 *
	 * @param array $schedules Schedules.
	 * @return array
	 */
	public static function add_cron_schedule( $schedules ) {
		$schedules['aurelia_five_minutes'] = array(
			'interval' => 5 * MINUTE_IN_SECONDS,
			'display'  => __( 'Every five minutes (Aurelia)', 'aurelia-commerce' ),
		);
		return $schedules;
	}

	/**
	 * Re-create scheduled events if they went missing (e.g. after a migration).
	 */
	public function ensure_cron() {
		if ( ! wp_next_scheduled( 'aurelia_commerce_daily' ) ) {
			wp_schedule_event( time() + HOUR_IN_SECONDS, 'daily', 'aurelia_commerce_daily' );
		}
		if ( ! wp_next_scheduled( 'aurelia_commerce_social_tick' ) ) {
			wp_schedule_event( time() + 60, 'aurelia_five_minutes', 'aurelia_commerce_social_tick' );
		}
	}

	/**
	 * Flush rewrite rules once after activation or when routes change.
	 */
	public function maybe_flush_rewrites() {
		if ( get_option( 'aurelia_commerce_flush_rewrites' ) ) {
			delete_option( 'aurelia_commerce_flush_rewrites' );
			flush_rewrite_rules( false );
		}
	}

	/**
	 * Shared front-end stylesheet and runtime config.
	 */
	public function register_assets() {
		wp_register_style( 'aurelia-commerce', AURELIA_COMMERCE_URL . 'assets/css/frontend.css', array(), AURELIA_COMMERCE_VERSION );
		wp_enqueue_style( 'aurelia-commerce' );

		Helpers::register_script( 'aurelia-commerce', 'frontend.js' );
		wp_enqueue_script( 'aurelia-commerce' );
		add_action( 'wp_enqueue_scripts', array( $this, 'localize' ), 100 );
	}

	/**
	 * Runtime config shared by all front-end modules (filtered by each module).
	 */
	public function localize() {
		$config = array(
			'rest'     => esc_url_raw( rest_url( 'aurelia/v1/' ) ),
			'storeApi' => esc_url_raw( rest_url( 'wc/store/v1/' ) ),
			'nonce'    => wp_create_nonce( 'wp_rest' ),
			'home'     => esc_url_raw( home_url( '/' ) ),
			'currency' => get_woocommerce_currency(),
			'loggedIn' => is_user_logged_in(),
			'i18n'     => array(),
		);
		/**
		 * Filters the front-end configuration object (window.aureliaCommerce).
		 *
		 * @param array $config Config.
		 */
		$config = apply_filters( 'aurelia_commerce_frontend_config', $config );
		wp_localize_script( 'aurelia-commerce', 'aureliaCommerce', $config );
	}

	/**
	 * Admin notice when WooCommerce is not active.
	 */
	public function missing_woocommerce_notice() {
		if ( ! current_user_can( 'activate_plugins' ) ) {
			return;
		}
		echo '<div class="notice notice-warning"><p>' . esc_html__( 'Aurelia Commerce needs WooCommerce. Please install and activate WooCommerce to use the store features.', 'aurelia-commerce' ) . '</p></div>';
	}
}
