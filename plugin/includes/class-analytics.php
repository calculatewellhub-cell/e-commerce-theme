<?php
/**
 * Privacy-friendly analytics: cookie-less, no IP addresses, no user IDs.
 *
 * Events: page_view, product_view, add_to_cart, whatsapp_order,
 * whatsapp_enquiry, chat_message, social_click, newsletter_signup.
 *
 * @package Aurelia_Commerce
 */

namespace Aurelia\Commerce;

defined( 'ABSPATH' ) || exit;

/**
 * Analytics.
 */
class Analytics {

	const TYPES = array( 'page_view', 'product_view', 'add_to_cart', 'whatsapp_order', 'whatsapp_enquiry', 'chat_message', 'social_click', 'newsletter_signup', 'upi_payment' );

	/**
	 * Hooks.
	 */
	public function init() {
		add_action( 'admin_menu', array( $this, 'menu' ), 10 );
		add_action( 'aurelia_commerce_daily', array( $this, 'purge' ) );
		add_action( 'rest_api_init', array( $this, 'routes' ) );
		if ( ! Settings::on( 'analytics_enabled' ) ) {
			return;
		}
		add_action( 'woocommerce_add_to_cart', array( $this, 'on_add_to_cart' ), 10, 4 );
		add_filter( 'aurelia_commerce_frontend_config', array( $this, 'config' ) );
	}

	/**
	 * Table name.
	 *
	 * @return string
	 */
	public static function table() {
		global $wpdb;
		return $wpdb->prefix . 'aurelia_events';
	}

	/**
	 * Record an event.
	 *
	 * @param string $type Event type.
	 * @param array  $args Optional product_id, value, path, source, ref_id, platform.
	 */
	public static function record( $type, array $args = array() ) {
		if ( ! Settings::on( 'analytics_enabled' ) || ! in_array( $type, self::TYPES, true ) || Helpers::is_bot() ) {
			return;
		}
		global $wpdb;
		$wpdb->insert( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- custom analytics table.
			self::table(),
			array(
				'type'       => $type,
				'created_at' => current_time( 'mysql', true ),
				'path'       => substr( (string) ( $args['path'] ?? '' ), 0, 190 ),
				'product_id' => absint( $args['product_id'] ?? 0 ),
				'value'      => round( (float) ( $args['value'] ?? 0 ), 2 ),
				'source'     => substr( sanitize_text_field( (string) ( $args['source'] ?? '' ) ), 0, 64 ),
				'ref_id'     => absint( $args['ref_id'] ?? 0 ),
				'platform'   => substr( sanitize_key( (string) ( $args['platform'] ?? '' ) ), 0, 32 ),
				'device'     => self::device(),
			),
			array( '%s', '%s', '%s', '%d', '%f', '%s', '%d', '%s', '%s' )
		);
	}

	/**
	 * Coarse device class from the user agent (not stored itself).
	 *
	 * @return string
	 */
	private static function device() {
		$ua = isset( $_SERVER['HTTP_USER_AGENT'] ) ? strtolower( sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) ) : '';
		if ( str_contains( $ua, 'ipad' ) || str_contains( $ua, 'tablet' ) ) {
			return 'tablet';
		}
		return preg_match( '/mobile|android|iphone/', $ua ) ? 'mobile' : 'desktop';
	}

	/**
	 * Add to cart (covers classic, AJAX and Store API).
	 *
	 * @param string $key          Cart key.
	 * @param int    $product_id   Product ID.
	 * @param int    $quantity     Quantity.
	 * @param int    $variation_id Variation ID.
	 */
	public function on_add_to_cart( $key, $product_id, $quantity, $variation_id ) {
		$product = wc_get_product( $variation_id ? $variation_id : $product_id );
		self::record(
			'add_to_cart',
			array(
				'product_id' => $product_id,
				'value'      => $product ? (float) $product->get_price() * (int) $quantity : 0,
			)
		);
	}

	/**
	 * Front-end config for the beacon.
	 *
	 * @param array $config Config.
	 * @return array
	 */
	public function config( $config ) {
		$config['analytics'] = array(
			'enabled' => ! current_user_can( 'manage_woocommerce' ),
			'product' => is_singular( 'product' ) ? get_queried_object_id() : 0,
		);
		return $config;
	}

	/**
	 * Beacon endpoint.
	 */
	public function routes() {
		register_rest_route(
			'aurelia/v1',
			'/event',
			array(
				'methods'             => 'POST',
				'permission_callback' => '__return_true',
				'callback'            => array( $this, 'beacon' ),
			)
		);
		register_rest_route(
			'aurelia/v1',
			'/analytics',
			array(
				'methods'             => 'GET',
				'permission_callback' => static fn() => current_user_can( 'manage_woocommerce' ),
				'callback'            => fn( \WP_REST_Request $r ) => self::summary( absint( $r->get_param( 'days' ) ) ),
			)
		);
	}

	/**
	 * Receive a browser beacon (page_view, product_view, whatsapp_enquiry).
	 *
	 * @param \WP_REST_Request $request Request.
	 * @return \WP_REST_Response
	 */
	public function beacon( \WP_REST_Request $request ) {
		$body = json_decode( (string) $request->get_body(), true );
		$body = is_array( $body ) ? $body : $request->get_params();
		$type = sanitize_key( (string) ( $body['type'] ?? '' ) );
		if ( in_array( $type, array( 'page_view', 'product_view', 'whatsapp_enquiry' ), true ) && Helpers::rate_limit( 'beacon', 120, MINUTE_IN_SECONDS ) ) {
			self::record(
				$type,
				array(
					'path'       => Helpers::path_of( (string) ( $body['path'] ?? '' ) ),
					'product_id' => absint( $body['product'] ?? 0 ),
					'source'     => 'page_view' === $type ? self::classify_source( (string) ( $body['ref'] ?? '' ), (string) ( $body['utm'] ?? '' ) ) : '',
				)
			);
		}
		return new \WP_REST_Response( null, 204 );
	}

	/**
	 * Traffic source from referrer / utm_source.
	 *
	 * @param string $referrer Referrer URL.
	 * @param string $utm      utm_source.
	 * @return string
	 */
	public static function classify_source( $referrer, $utm ) {
		$utm = sanitize_key( $utm );
		if ( '' !== $utm ) {
			return 'utm:' . $utm;
		}
		$host = strtolower( (string) wp_parse_url( $referrer, PHP_URL_HOST ) );
		$host = preg_replace( '/^www\./', '', $host );
		if ( '' === $host ) {
			return 'direct';
		}
		$home = (string) wp_parse_url( home_url(), PHP_URL_HOST );
		if ( $home === $host || 'www.' . $host === $home ) {
			return 'internal';
		}
		$groups = array(
			'ai'     => '/(chatgpt|openai|perplexity|claude\.ai|gemini|copilot|you\.com|phind)/',
			'search' => '/(google\.|bing\.|duckduckgo|yahoo\.|yandex|baidu|ecosia|brave)/',
			'social' => '/(facebook|instagram|t\.co|twitter|x\.com|pinterest|youtube|linkedin|whatsapp|wa\.me|reddit|threads|snapchat)/',
		);
		foreach ( $groups as $group => $pattern ) {
			if ( preg_match( $pattern, $host ) ) {
				return $group . ':' . substr( $host, 0, 40 );
			}
		}
		return 'referral:' . substr( $host, 0, 40 );
	}

	/**
	 * Delete events past the retention window.
	 */
	public function purge() {
		global $wpdb;
		$days   = max( 7, (int) Settings::get( 'analytics_retention', 180 ) );
		$cutoff = gmdate( 'Y-m-d H:i:s', time() - $days * DAY_IN_SECONDS );
		$wpdb->query( $wpdb->prepare( 'DELETE FROM %i WHERE created_at < %s', self::table(), $cutoff ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery -- custom analytics table.
	}

	/**
	 * Aggregate report.
	 *
	 * @param int $days Range in days.
	 * @return array
	 */
	public static function summary( $days = 30 ) {
		global $wpdb;
		$days  = in_array( (int) $days, array( 7, 30, 90, 365 ), true ) ? (int) $days : 30;
		$since = gmdate( 'Y-m-d 00:00:00', time() - ( $days - 1 ) * DAY_IN_SECONDS );
		$t     = self::table();

		// phpcs:disable WordPress.DB.DirectDatabaseQuery -- custom analytics table, results are aggregated and short-lived.
		$totals_raw = $wpdb->get_results( $wpdb->prepare( 'SELECT type, COUNT(*) AS c, SUM(value) AS v FROM %i WHERE created_at >= %s GROUP BY type', $t, $since ), ARRAY_A );
		$daily_raw  = $wpdb->get_results( $wpdb->prepare( 'SELECT DATE(created_at) AS d, type, COUNT(*) AS c FROM %i WHERE created_at >= %s GROUP BY d, type', $t, $since ), ARRAY_A );
		$products   = $wpdb->get_results( $wpdb->prepare( "SELECT product_id, COUNT(*) AS c FROM %i WHERE created_at >= %s AND type = 'product_view' AND product_id > 0 GROUP BY product_id ORDER BY c DESC LIMIT 10", $t, $since ), ARRAY_A );
		$carts      = $wpdb->get_results( $wpdb->prepare( "SELECT product_id, COUNT(*) AS c FROM %i WHERE created_at >= %s AND type = 'add_to_cart' AND product_id > 0 GROUP BY product_id", $t, $since ), ARRAY_A );
		$sources    = $wpdb->get_results( $wpdb->prepare( "SELECT source, COUNT(*) AS c FROM %i WHERE created_at >= %s AND type = 'page_view' GROUP BY source ORDER BY c DESC LIMIT 12", $t, $since ), ARRAY_A );
		$devices    = $wpdb->get_results( $wpdb->prepare( "SELECT device, COUNT(*) AS c FROM %i WHERE created_at >= %s AND type = 'page_view' GROUP BY device ORDER BY c DESC", $t, $since ), ARRAY_A );
		$platforms  = $wpdb->get_results( $wpdb->prepare( "SELECT platform, COUNT(*) AS c FROM %i WHERE created_at >= %s AND type = 'social_click' GROUP BY platform ORDER BY c DESC", $t, $since ), ARRAY_A );
		$posts      = $wpdb->get_results( $wpdb->prepare( "SELECT ref_id, platform, COUNT(*) AS c FROM %i WHERE created_at >= %s AND type = 'social_click' GROUP BY ref_id, platform ORDER BY c DESC LIMIT 20", $t, $since ), ARRAY_A );
		$pages      = $wpdb->get_results( $wpdb->prepare( "SELECT path, COUNT(*) AS c FROM %i WHERE created_at >= %s AND type = 'page_view' GROUP BY path ORDER BY c DESC LIMIT 10", $t, $since ), ARRAY_A );
		// phpcs:enable

		$totals = array_fill_keys( self::TYPES, 0 );
		$value  = 0.0;
		foreach ( $totals_raw as $row ) {
			$totals[ $row['type'] ] = (int) $row['c'];
			if ( 'whatsapp_order' === $row['type'] ) {
				$value = (float) $row['v'];
			}
		}

		$daily = array();
		for ( $i = $days - 1; $i >= 0; $i-- ) {
			$daily[ gmdate( 'Y-m-d', time() - $i * DAY_IN_SECONDS ) ] = array();
		}
		foreach ( $daily_raw as $row ) {
			if ( isset( $daily[ $row['d'] ] ) ) {
				$daily[ $row['d'] ][ $row['type'] ] = (int) $row['c'];
			}
		}

		$cart_by = array_column( $carts, 'c', 'product_id' );
		$top     = array();
		foreach ( $products as $row ) {
			$product = wc_get_product( (int) $row['product_id'] );
			$top[]   = array(
				'name'  => $product ? $product->get_name() : '#' . $row['product_id'],
				'url'   => $product ? get_edit_post_link( $product->get_id(), 'raw' ) : '',
				'views' => (int) $row['c'],
				'carts' => (int) ( $cart_by[ $row['product_id'] ] ?? 0 ),
			);
		}

		$post_rows = array();
		foreach ( $posts as $row ) {
			$post_rows[] = array(
				'post'     => (int) $row['ref_id'],
				'title'    => $row['ref_id'] ? wp_trim_words( get_the_title( (int) $row['ref_id'] ), 8 ) : '',
				'platform' => $row['platform'],
				'clicks'   => (int) $row['c'],
			);
		}

		return array(
			'days'      => $days,
			'currency'  => html_entity_decode( get_woocommerce_currency_symbol(), ENT_QUOTES, 'UTF-8' ),
			'totals'    => $totals,
			'value'     => round( $value, 2 ),
			'daily'     => array_map( null, array_keys( $daily ), array_values( $daily ) ),
			'top'       => $top,
			'sources'   => array_map(
				static fn( $r ) => array(
					'label' => '' !== $r['source'] ? $r['source'] : 'direct',
					'count' => (int) $r['c'],
				),
				$sources
			),
			'devices'   => array_map(
				static fn( $r ) => array(
					'label' => $r['device'],
					'count' => (int) $r['c'],
				),
				$devices
			),
			'platforms' => array_map(
				static fn( $r ) => array(
					'label' => $r['platform'],
					'count' => (int) $r['c'],
				),
				$platforms
			),
			'posts'     => $post_rows,
			'pages'     => array_map(
				static fn( $r ) => array(
					'label' => $r['path'],
					'count' => (int) $r['c'],
				),
				$pages
			),
		);
	}

	/**
	 * Dashboard submenu (first item under Aurelia).
	 */
	public function menu() {
		add_submenu_page( Admin::SLUG, __( 'Store analytics', 'aurelia-commerce' ), __( 'Dashboard', 'aurelia-commerce' ), Admin::CAP, Admin::SLUG, array( $this, 'render' ) );
	}

	/**
	 * Render the dashboard shell; the charts are drawn by admin-analytics.js.
	 */
	public function render() {
		wp_enqueue_script( 'aurelia-admin-analytics', AURELIA_COMMERCE_URL . 'assets/js/admin-analytics.js', array( 'wp-api-fetch' ), AURELIA_COMMERCE_VERSION, true );
		wp_localize_script(
			'aurelia-admin-analytics',
			'aureliaAnalytics',
			array(
				'initial' => self::summary( 30 ),
				'enabled' => Settings::on( 'analytics_enabled' ),
				'i18n'    => array(
					'pageViews'    => __( 'Page views', 'aurelia-commerce' ),
					'productViews' => __( 'Product views', 'aurelia-commerce' ),
					'addToCart'    => __( 'Add to cart', 'aurelia-commerce' ),
					'waOrders'     => __( 'WhatsApp orders', 'aurelia-commerce' ),
					'waValue'      => __( 'WhatsApp order value', 'aurelia-commerce' ),
					'waEnquiries'  => __( 'WhatsApp enquiries', 'aurelia-commerce' ),
					'chat'         => __( 'Concierge messages', 'aurelia-commerce' ),
					'social'       => __( 'Social clicks', 'aurelia-commerce' ),
					'signups'      => __( 'Newsletter sign-ups', 'aurelia-commerce' ),
					'traffic'      => __( 'Daily traffic', 'aurelia-commerce' ),
					'topProducts'  => __( 'Top products', 'aurelia-commerce' ),
					'product'      => __( 'Product', 'aurelia-commerce' ),
					'views'        => __( 'Views', 'aurelia-commerce' ),
					'carts'        => __( 'Add to cart', 'aurelia-commerce' ),
					'rate'         => __( 'Cart rate', 'aurelia-commerce' ),
					'sources'      => __( 'Traffic sources', 'aurelia-commerce' ),
					'devices'      => __( 'Devices', 'aurelia-commerce' ),
					'platforms'    => __( 'Social clicks by platform', 'aurelia-commerce' ),
					'posts'        => __( 'Social clicks by post', 'aurelia-commerce' ),
					'topPages'     => __( 'Top pages', 'aurelia-commerce' ),
					'post'         => __( 'Post', 'aurelia-commerce' ),
					'platform'     => __( 'Platform', 'aurelia-commerce' ),
					'clicks'       => __( 'Clicks', 'aurelia-commerce' ),
					'empty'        => __( 'No data yet for this period.', 'aurelia-commerce' ),
					'disabled'     => __( 'Analytics is turned off in Settings → Analytics & SEO.', 'aurelia-commerce' ),
					'table'        => __( 'Table view', 'aurelia-commerce' ),
					'privacy'      => __( 'Cookie-less and anonymous: no IP addresses, cookies or personal data are stored.', 'aurelia-commerce' ),
				),
			)
		);
		?>
		<div class="wrap aurelia-admin aurelia-dashboard">
			<div class="aurelia-dashboard__head">
				<h1><?php esc_html_e( 'Store analytics', 'aurelia-commerce' ); ?></h1>
				<label>
					<span class="screen-reader-text"><?php esc_html_e( 'Date range', 'aurelia-commerce' ); ?></span>
					<select id="aurelia-range">
						<option value="7"><?php esc_html_e( 'Last 7 days', 'aurelia-commerce' ); ?></option>
						<option value="30" selected><?php esc_html_e( 'Last 30 days', 'aurelia-commerce' ); ?></option>
						<option value="90"><?php esc_html_e( 'Last 90 days', 'aurelia-commerce' ); ?></option>
						<option value="365"><?php esc_html_e( 'Last 12 months', 'aurelia-commerce' ); ?></option>
					</select>
				</label>
			</div>
			<div id="aurelia-analytics-app" aria-live="polite"></div>
		</div>
		<?php
	}
}
