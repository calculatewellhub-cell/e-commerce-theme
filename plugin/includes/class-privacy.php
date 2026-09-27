<?php
/**
 * Privacy tools integration: policy text, exporter and eraser for subscribers.
 *
 * @package Aurelia_Commerce
 */

namespace Aurelia\Commerce;

defined( 'ABSPATH' ) || exit;

/**
 * Privacy.
 */
class Privacy {

	/**
	 * Hooks.
	 */
	public function init() {
		add_action( 'admin_init', array( $this, 'policy' ) );
		add_filter( 'wp_privacy_personal_data_exporters', array( $this, 'exporters' ) );
		add_filter( 'wp_privacy_personal_data_erasers', array( $this, 'erasers' ) );
	}

	/**
	 * Suggested privacy policy text.
	 */
	public function policy() {
		if ( ! function_exists( 'wp_add_privacy_policy_content' ) ) {
			return;
		}
		$content  = '<p>' . esc_html__( 'Store analytics are cookie-less and anonymous: we record page and product views, cart additions and similar events without IP addresses, cookies or any personal identifiers.', 'aurelia-commerce' ) . '</p>';
		$content .= '<p>' . esc_html__( 'If you join our newsletter we store your email address until you unsubscribe or ask us to delete it.', 'aurelia-commerce' ) . '</p>';
		$content .= '<p>' . esc_html__( 'Messages you send to our shopping assistant are processed by Anthropic (Claude) to generate replies and are not stored on this website. WhatsApp orders are sent through WhatsApp, which is subject to its own privacy policy.', 'aurelia-commerce' ) . '</p>';
		wp_add_privacy_policy_content( 'Aurelia Commerce', wp_kses_post( wpautop( $content, false ) ) );
	}

	/**
	 * Register exporter.
	 *
	 * @param array $exporters Exporters.
	 * @return array
	 */
	public function exporters( $exporters ) {
		$exporters['aurelia-commerce'] = array(
			'exporter_friendly_name' => __( 'Newsletter (Aurelia)', 'aurelia-commerce' ),
			'callback'               => static function ( $email ) {
				global $wpdb;
				$row = $wpdb->get_row( $wpdb->prepare( 'SELECT email, source, created_at FROM %i WHERE email = %s', $wpdb->prefix . 'aurelia_subscribers', strtolower( $email ) ), ARRAY_A ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery -- custom table.
				$data = array();
				if ( $row ) {
					$data[] = array(
						'group_id'    => 'aurelia-newsletter',
						'group_label' => __( 'Newsletter', 'aurelia-commerce' ),
						'item_id'     => 'aurelia-newsletter',
						'data'        => array(
							array( 'name' => __( 'Email', 'aurelia-commerce' ), 'value' => $row['email'] ),
							array( 'name' => __( 'Signed up', 'aurelia-commerce' ), 'value' => $row['created_at'] . ' UTC' ),
							array( 'name' => __( 'Source', 'aurelia-commerce' ), 'value' => $row['source'] ),
						),
					);
				}
				return array(
					'data' => $data,
					'done' => true,
				);
			},
		);
		return $exporters;
	}

	/**
	 * Register eraser.
	 *
	 * @param array $erasers Erasers.
	 * @return array
	 */
	public function erasers( $erasers ) {
		$erasers['aurelia-commerce'] = array(
			'eraser_friendly_name' => __( 'Newsletter (Aurelia)', 'aurelia-commerce' ),
			'callback'             => static function ( $email ) {
				global $wpdb;
				$deleted = (int) $wpdb->delete( $wpdb->prefix . 'aurelia_subscribers', array( 'email' => strtolower( $email ) ), array( '%s' ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery -- custom table.
				return array(
					'items_removed'  => $deleted > 0,
					'items_retained' => false,
					'messages'       => array(),
					'done'           => true,
				);
			},
		);
		return $erasers;
	}
}
