<?php
/**
 * Activation, deactivation and database schema.
 *
 * @package Aurelia_Commerce
 */

namespace Aurelia\Commerce;

defined( 'ABSPATH' ) || exit;

/**
 * Installer.
 */
class Installer {

	const DB_VERSION = '1.0.0';

	/**
	 * Activation: tables, cron, rewrite rules, wizard redirect flag.
	 */
	public static function activate() {
		self::create_tables();
		add_filter( 'cron_schedules', array( Plugin::class, 'add_cron_schedule' ) ); // phpcs:ignore WordPress.WP.CronInterval.CronSchedulesInterval -- 5-minute social publishing tick is intentional.
		if ( ! wp_next_scheduled( 'aurelia_commerce_daily' ) ) {
			wp_schedule_event( time() + HOUR_IN_SECONDS, 'daily', 'aurelia_commerce_daily' );
		}
		if ( ! wp_next_scheduled( 'aurelia_commerce_social_tick' ) ) {
			wp_schedule_event( time() + 60, 'aurelia_five_minutes', 'aurelia_commerce_social_tick' );
		}
		if ( ! get_option( 'aurelia_commerce_wizard_done' ) ) {
			set_transient( 'aurelia_commerce_activation_redirect', 1, 60 );
		}
		update_option( 'aurelia_commerce_flush_rewrites', 1 );
	}

	/**
	 * Deactivation: unschedule cron, flush rewrites.
	 */
	public static function deactivate() {
		wp_clear_scheduled_hook( 'aurelia_commerce_daily' );
		wp_clear_scheduled_hook( 'aurelia_commerce_social_tick' );
		flush_rewrite_rules();
	}

	/**
	 * Create or upgrade custom tables.
	 */
	public static function create_tables() {
		global $wpdb;
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		$charset = $wpdb->get_charset_collate();

		// Analytics events: no IP, no cookies, no user IDs.
		dbDelta(
			"CREATE TABLE {$wpdb->prefix}aurelia_events (
				id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				type varchar(32) NOT NULL,
				created_at datetime NOT NULL,
				path varchar(191) NOT NULL DEFAULT '',
				product_id bigint(20) unsigned NOT NULL DEFAULT 0,
				value decimal(14,2) NOT NULL DEFAULT 0,
				source varchar(64) NOT NULL DEFAULT '',
				ref_id bigint(20) unsigned NOT NULL DEFAULT 0,
				platform varchar(32) NOT NULL DEFAULT '',
				device varchar(16) NOT NULL DEFAULT '',
				PRIMARY KEY  (id),
				KEY type_created (type,created_at),
				KEY created_at (created_at)
			) $charset;"
		);

		// Newsletter subscribers (personal data: covered by the WP privacy exporter/eraser).
		dbDelta(
			"CREATE TABLE {$wpdb->prefix}aurelia_subscribers (
				id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				email varchar(190) NOT NULL,
				source varchar(32) NOT NULL DEFAULT '',
				created_at datetime NOT NULL,
				PRIMARY KEY  (id),
				UNIQUE KEY email (email)
			) $charset;"
		);

		update_option( 'aurelia_commerce_db_version', self::DB_VERSION );
	}

	/**
	 * Upgrade tables when the schema version changes.
	 */
	public static function maybe_upgrade() {
		if ( get_option( 'aurelia_commerce_db_version' ) !== self::DB_VERSION ) {
			self::create_tables();
		}
	}
}
