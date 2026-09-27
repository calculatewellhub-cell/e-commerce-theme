<?php
/**
 * Settings schema, storage and sanitisation.
 *
 * Non-secret settings live in one autoloaded option. Secrets (API keys, tokens)
 * live in a separate, non-autoloaded option and are never sent to the browser.
 *
 * @package Aurelia_Commerce
 */

namespace Aurelia\Commerce;

defined( 'ABSPATH' ) || exit;

/**
 * Settings registry.
 */
class Settings {

	const OPTION  = 'aurelia_commerce_settings';
	const SECRETS = 'aurelia_commerce_secrets';

	/**
	 * Cached merged settings.
	 *
	 * @var array|null
	 */
	private static $cache = null;

	/**
	 * Settings schema grouped by tab.
	 *
	 * @return array<string, array{label:string, fields:array}>
	 */
	public static function schema() {
		$store_types = array(
			'Store'                   => __( 'General store', 'aurelia-commerce' ),
			'JewelryStore'            => __( 'Jewellery store', 'aurelia-commerce' ),
			'ClothingStore'           => __( 'Clothing store', 'aurelia-commerce' ),
			'ElectronicsStore'        => __( 'Electronics store', 'aurelia-commerce' ),
			'HealthAndBeautyBusiness' => __( 'Beauty store', 'aurelia-commerce' ),
			'GroceryStore'            => __( 'Grocery store', 'aurelia-commerce' ),
			'HomeGoodsStore'          => __( 'Home goods store', 'aurelia-commerce' ),
			'OnlineStore'             => __( 'Online-only store', 'aurelia-commerce' ),
		);

		return array(
			'general'   => array(
				'label'  => __( 'Store', 'aurelia-commerce' ),
				'fields' => array(
					'store_name'      => array(
						'type'        => 'text',
						'label'       => __( 'Store name', 'aurelia-commerce' ),
						'default'     => '',
						'description' => __( 'Leave empty to use the site title.', 'aurelia-commerce' ),
					),
					'store_type'      => array(
						'type'        => 'select',
						'label'       => __( 'Store type', 'aurelia-commerce' ),
						'default'     => 'Store',
						'options'     => $store_types,
						'description' => __( 'Used in structured data and to tune the AI concierge.', 'aurelia-commerce' ),
					),
					'store_phone'     => array(
						'type'    => 'text',
						'label'   => __( 'Phone', 'aurelia-commerce' ),
						'default' => '',
					),
					'store_email'     => array(
						'type'    => 'email',
						'label'   => __( 'Email', 'aurelia-commerce' ),
						'default' => '',
					),
					'store_address'   => array(
						'type'    => 'text',
						'label'   => __( 'Street address', 'aurelia-commerce' ),
						'default' => '',
					),
					'store_city'      => array(
						'type'    => 'text',
						'label'   => __( 'City', 'aurelia-commerce' ),
						'default' => '',
					),
					'store_region'    => array(
						'type'    => 'text',
						'label'   => __( 'State / region', 'aurelia-commerce' ),
						'default' => '',
					),
					'store_postcode'  => array(
						'type'    => 'text',
						'label'   => __( 'PIN / postcode', 'aurelia-commerce' ),
						'default' => '',
					),
					'store_country'   => array(
						'type'        => 'text',
						'label'       => __( 'Country code', 'aurelia-commerce' ),
						'default'     => 'IN',
						'description' => __( 'Two-letter ISO code, e.g. IN.', 'aurelia-commerce' ),
					),
					'store_hours'     => array(
						'type'        => 'text',
						'label'       => __( 'Opening hours', 'aurelia-commerce' ),
						'default'     => 'Mo-Sa 10:30-20:30',
						'description' => __( 'schema.org format, e.g. Mo-Sa 10:30-20:30.', 'aurelia-commerce' ),
					),
					'social_profiles' => array(
						'type'        => 'textarea',
						'label'       => __( 'Social profile URLs', 'aurelia-commerce' ),
						'default'     => '',
						'description' => __( 'One URL per line (Instagram, Facebook, YouTube...). Used for structured data.', 'aurelia-commerce' ),
					),
				),
			),
			'whatsapp'  => array(
				'label'  => __( 'WhatsApp', 'aurelia-commerce' ),
				'fields' => array(
					'wa_enabled'          => array(
						'type'    => 'checkbox',
						'label'   => __( 'Enable WhatsApp ordering', 'aurelia-commerce' ),
						'default' => 1,
					),
					'wa_number'           => array(
						'type'        => 'text',
						'label'       => __( 'WhatsApp number', 'aurelia-commerce' ),
						'default'     => '',
						'description' => __( 'International format with country code, digits only, e.g. 919876543210.', 'aurelia-commerce' ),
					),
					'wa_mode'             => array(
						'type'    => 'select',
						'label'   => __( 'Checkout mode', 'aurelia-commerce' ),
						'default' => 'alongside',
						'options' => array(
							'alongside' => __( 'Show alongside normal checkout', 'aurelia-commerce' ),
							'replace'   => __( 'Replace checkout with WhatsApp ordering', 'aurelia-commerce' ),
						),
					),
					'wa_product_button'   => array(
						'type'    => 'checkbox',
						'label'   => __( '"Order on WhatsApp" on product pages', 'aurelia-commerce' ),
						'default' => 1,
					),
					'wa_cart_button'      => array(
						'type'    => 'checkbox',
						'label'   => __( '"Order on WhatsApp" in cart and side cart', 'aurelia-commerce' ),
						'default' => 1,
					),
					'wa_enquiry'          => array(
						'type'    => 'checkbox',
						'label'   => __( 'Product enquiry button', 'aurelia-commerce' ),
						'default' => 1,
					),
					'wa_video_call'       => array(
						'type'    => 'checkbox',
						'label'   => __( '"Request a video call" button', 'aurelia-commerce' ),
						'default' => 1,
					),
					'wa_float'            => array(
						'type'    => 'checkbox',
						'label'   => __( 'Floating WhatsApp button', 'aurelia-commerce' ),
						'default' => 1,
					),
					'wa_float_position'   => array(
						'type'    => 'select',
						'label'   => __( 'Floating button position', 'aurelia-commerce' ),
						'default' => 'right',
						'options' => array(
							'right' => __( 'Bottom right', 'aurelia-commerce' ),
							'left'  => __( 'Bottom left', 'aurelia-commerce' ),
						),
					),
					'wa_create_order'     => array(
						'type'    => 'checkbox',
						'label'   => __( 'Create a WooCommerce order ("Pending – WhatsApp") for each WhatsApp order', 'aurelia-commerce' ),
						'default' => 1,
					),
					'wa_ask_details'      => array(
						'type'    => 'checkbox',
						'label'   => __( 'Ask for name, phone and address before opening WhatsApp', 'aurelia-commerce' ),
						'default' => 1,
					),
					'wa_include_pay_link' => array(
						'type'    => 'checkbox',
						'label'   => __( 'Include an online payment link in the message', 'aurelia-commerce' ),
						'default' => 1,
					),
					'wa_greeting'         => array(
						'type'    => 'text',
						'label'   => __( 'Message closing line', 'aurelia-commerce' ),
						'default' => __( 'Please confirm availability and payment options. Thank you!', 'aurelia-commerce' ),
					),
				),
			),
			'concierge' => array(
				'label'  => __( 'AI concierge', 'aurelia-commerce' ),
				'fields' => array(
					'ai_enabled'      => array(
						'type'    => 'checkbox',
						'label'   => __( 'Enable the AI shopping concierge', 'aurelia-commerce' ),
						'default' => 1,
					),
					'anthropic_key'   => array(
						'type'        => 'secret',
						'label'       => __( 'Claude API key', 'aurelia-commerce' ),
						'default'     => '',
						'description' => __( 'Create a key at console.anthropic.com. It is stored on your server and never sent to the browser. Without a key, a rule-based assistant answers from your catalogue.', 'aurelia-commerce' ),
					),
					'ai_model'        => array(
						'type'        => 'text',
						'label'       => __( 'Model', 'aurelia-commerce' ),
						'default'     => 'claude-opus-5',
						'description' => __( 'Claude model ID, e.g. claude-opus-5 or claude-sonnet-5.', 'aurelia-commerce' ),
					),
					'ai_name'         => array(
						'type'    => 'text',
						'label'   => __( 'Assistant name', 'aurelia-commerce' ),
						'default' => 'Aria',
					),
					'ai_greeting'     => array(
						'type'    => 'text',
						'label'   => __( 'Greeting', 'aurelia-commerce' ),
						'default' => __( 'Namaste ✨ I am your personal shopping concierge. What are you looking for today?', 'aurelia-commerce' ),
					),
					'ai_suggestions'  => array(
						'type'        => 'textarea',
						'label'       => __( 'Suggested questions', 'aurelia-commerce' ),
						'default'     => __( "Gift ideas under ₹2,000\nWhat's new this week?\nDo you offer cash on delivery?\nHow long does delivery take?", 'aurelia-commerce' ),
						'description' => __( 'One per line.', 'aurelia-commerce' ),
					),
					'ai_policies'     => array(
						'type'    => 'textarea',
						'label'   => __( 'Shipping, returns & payment policy', 'aurelia-commerce' ),
						'default' => __( "Free shipping on orders over ₹999, otherwise ₹79. Dispatch within 48 hours; delivery in 2–7 days across India.\nEasy 15-day returns on unused items in original packaging.\nPayments: UPI (PhonePe, Google Pay, Paytm), cards, net banking and cash on delivery.", 'aurelia-commerce' ),
					),
					'ai_faq'          => array(
						'type'        => 'textarea',
						'label'       => __( 'FAQ for the assistant', 'aurelia-commerce' ),
						'default'     => '',
						'description' => __( 'Optional. Write each as "Q: ..." followed by "A: ...". FAQ blocks on your pages are included automatically.', 'aurelia-commerce' ),
					),
					'ai_instructions' => array(
						'type'        => 'textarea',
						'label'       => __( 'Extra instructions', 'aurelia-commerce' ),
						'default'     => '',
						'description' => __( 'Tone, brand voice or topics to avoid.', 'aurelia-commerce' ),
					),
					'ai_rate_limit'   => array(
						'type'    => 'number',
						'label'   => __( 'Messages per visitor per 10 minutes', 'aurelia-commerce' ),
						'default' => 20,
						'min'     => 1,
						'max'     => 200,
					),
				),
			),
			'viewer'    => array(
				'label'  => __( '3D viewer', 'aurelia-commerce' ),
				'fields' => array(
					'viewer_enabled' => array(
						'type'        => 'checkbox',
						'label'       => __( 'Enable "View in 3D" on products', 'aurelia-commerce' ),
						'default'     => 1,
						'description' => __( 'Configure each product in its "3D view" box: the built-in ring or your own GLB/GLTF model.', 'aurelia-commerce' ),
					),
					'viewer_hero'    => array(
						'type'        => 'checkbox',
						'label'       => __( 'Animate the hero 3D stage', 'aurelia-commerce' ),
						'default'     => 1,
						'description' => __( 'Turns the "Hero with 3D showcase" pattern image into a live 3D ring.', 'aurelia-commerce' ),
					),
					'viewer_metal'   => array(
						'type'    => 'color',
						'label'   => __( 'Default metal colour', 'aurelia-commerce' ),
						'default' => '#e6c068',
					),
					'viewer_gem'     => array(
						'type'    => 'color',
						'label'   => __( 'Default gem colour', 'aurelia-commerce' ),
						'default' => '#dff3ff',
					),
				),
			),
			'shop'      => array(
				'label'  => __( 'Shop features', 'aurelia-commerce' ),
				'fields' => array(
					'live_search'       => array(
						'type'    => 'checkbox',
						'label'   => __( 'Live product search suggestions', 'aurelia-commerce' ),
						'default' => 1,
					),
					'wishlist'          => array(
						'type'    => 'checkbox',
						'label'   => __( 'Wishlist', 'aurelia-commerce' ),
						'default' => 1,
					),
					'compare'           => array(
						'type'    => 'checkbox',
						'label'   => __( 'Product compare', 'aurelia-commerce' ),
						'default' => 1,
					),
					'quick_view'        => array(
						'type'    => 'checkbox',
						'label'   => __( 'Quick view', 'aurelia-commerce' ),
						'default' => 1,
					),
					'swatches'          => array(
						'type'    => 'checkbox',
						'label'   => __( 'Variation swatches (colour, image, button)', 'aurelia-commerce' ),
						'default' => 1,
					),
					'list_toggle'       => array(
						'type'    => 'checkbox',
						'label'   => __( 'Grid / list view toggle', 'aurelia-commerce' ),
						'default' => 1,
					),
					'pagination'        => array(
						'type'    => 'select',
						'label'   => __( 'Shop pagination', 'aurelia-commerce' ),
						'default' => 'load_more',
						'options' => array(
							'numbers'   => __( 'Page numbers', 'aurelia-commerce' ),
							'load_more' => __( '"Load more" button', 'aurelia-commerce' ),
							'infinite'  => __( 'Infinite scroll', 'aurelia-commerce' ),
						),
					),
					'sticky_cart'       => array(
						'type'    => 'checkbox',
						'label'   => __( 'Sticky add-to-cart bar on product pages', 'aurelia-commerce' ),
						'default' => 1,
					),
					'qty_buttons'       => array(
						'type'    => 'checkbox',
						'label'   => __( 'Quantity + / − buttons', 'aurelia-commerce' ),
						'default' => 1,
					),
					'recently_viewed'   => array(
						'type'    => 'checkbox',
						'label'   => __( 'Recently viewed products', 'aurelia-commerce' ),
						'default' => 1,
					),
					'bought_together'   => array(
						'type'    => 'checkbox',
						'label'   => __( 'Frequently bought together (uses cross-sells)', 'aurelia-commerce' ),
						'default' => 1,
					),
					'review_photos'     => array(
						'type'    => 'checkbox',
						'label'   => __( 'Allow photos in reviews', 'aurelia-commerce' ),
						'default' => 1,
					),
					'sale_countdown'    => array(
						'type'    => 'checkbox',
						'label'   => __( 'Countdown for scheduled sales', 'aurelia-commerce' ),
						'default' => 1,
					),
					'delivery_info'     => array(
						'type'    => 'checkbox',
						'label'   => __( 'Show dispatch & delivery estimate', 'aurelia-commerce' ),
						'default' => 1,
					),
					'dispatch_cutoff'   => array(
						'type'    => 'number',
						'label'   => __( 'Same-day dispatch cut-off hour (0–23)', 'aurelia-commerce' ),
						'default' => 15,
						'min'     => 0,
						'max'     => 23,
					),
					'delivery_min'      => array(
						'type'    => 'number',
						'label'   => __( 'Delivery days (minimum)', 'aurelia-commerce' ),
						'default' => 2,
						'min'     => 0,
						'max'     => 60,
					),
					'delivery_max'      => array(
						'type'    => 'number',
						'label'   => __( 'Delivery days (maximum)', 'aurelia-commerce' ),
						'default' => 6,
						'min'     => 0,
						'max'     => 90,
					),
					'free_shipping_bar' => array(
						'type'    => 'checkbox',
						'label'   => __( 'Free-shipping progress bar in cart', 'aurelia-commerce' ),
						'default' => 1,
					),
					'free_shipping_min' => array(
						'type'        => 'number',
						'label'       => __( 'Free shipping threshold', 'aurelia-commerce' ),
						'default'     => 999,
						'min'         => 0,
						'max'         => 10000000,
						'description' => __( 'Order value that unlocks free shipping (0 to hide the bar).', 'aurelia-commerce' ),
					),
					'size_guide'        => array(
						'type'        => 'html',
						'label'       => __( 'Size guide', 'aurelia-commerce' ),
						'default'     => '',
						'description' => __( 'Shown in a popup on products with a "Size" attribute. Each product can override it.', 'aurelia-commerce' ),
					),
				),
			),
			'marketing' => array(
				'label'  => __( 'Marketing', 'aurelia-commerce' ),
				'fields' => array(
					'popup_enabled'      => array(
						'type'    => 'checkbox',
						'label'   => __( 'Newsletter popup', 'aurelia-commerce' ),
						'default' => 0,
					),
					'popup_exit_intent'  => array(
						'type'    => 'checkbox',
						'label'   => __( 'Show on exit intent (desktop)', 'aurelia-commerce' ),
						'default' => 1,
					),
					'popup_delay'        => array(
						'type'    => 'number',
						'label'   => __( 'Or show after (seconds)', 'aurelia-commerce' ),
						'default' => 30,
						'min'     => 0,
						'max'     => 600,
					),
					'popup_title'        => array(
						'type'    => 'text',
						'label'   => __( 'Popup title', 'aurelia-commerce' ),
						'default' => __( 'Get 10% off your first order', 'aurelia-commerce' ),
					),
					'popup_text'         => array(
						'type'    => 'textarea',
						'label'   => __( 'Popup text', 'aurelia-commerce' ),
						'default' => __( 'Join our list for new arrivals and private sales.', 'aurelia-commerce' ),
					),
					'popup_coupon'       => array(
						'type'    => 'text',
						'label'   => __( 'Coupon shown after sign-up', 'aurelia-commerce' ),
						'default' => '',
					),
					'newsletter_webhook' => array(
						'type'        => 'url',
						'label'       => __( 'Forward sign-ups to webhook', 'aurelia-commerce' ),
						'default'     => '',
						'description' => __( 'Optional Make / Zapier / n8n / Mailchimp webhook URL.', 'aurelia-commerce' ),
					),
				),
			),
			'social'    => array(
				'label'  => __( 'Social posting', 'aurelia-commerce' ),
				'fields' => array(
					'social_webhook'     => array(
						'type'        => 'url',
						'label'       => __( 'Publishing webhook (Make, Zapier, n8n)', 'aurelia-commerce' ),
						'default'     => '',
						'description' => __( 'When set, every post is sent here as JSON and your automation fans it out to any network.', 'aurelia-commerce' ),
					),
					'meta_page_id'       => array(
						'type'    => 'text',
						'label'   => __( 'Facebook Page ID', 'aurelia-commerce' ),
						'default' => '',
					),
					'meta_ig_user_id'    => array(
						'type'    => 'text',
						'label'   => __( 'Instagram Business account ID', 'aurelia-commerce' ),
						'default' => '',
					),
					'meta_token'         => array(
						'type'        => 'secret',
						'label'       => __( 'Meta Page access token', 'aurelia-commerce' ),
						'default'     => '',
						'description' => __( 'Long-lived Page token with pages_manage_posts, instagram_basic and instagram_content_publish.', 'aurelia-commerce' ),
					),
					'meta_graph_version' => array(
						'type'    => 'text',
						'label'   => __( 'Graph API version', 'aurelia-commerce' ),
						'default' => 'v23.0',
					),
				),
			),
			'analytics' => array(
				'label'  => __( 'Analytics & SEO', 'aurelia-commerce' ),
				'fields' => array(
					'analytics_enabled'   => array(
						'type'    => 'checkbox',
						'label'   => __( 'Privacy-friendly analytics (no cookies, no personal data)', 'aurelia-commerce' ),
						'default' => 1,
					),
					'analytics_retention' => array(
						'type'    => 'number',
						'label'   => __( 'Keep analytics for (days)', 'aurelia-commerce' ),
						'default' => 180,
						'min'     => 7,
						'max'     => 1095,
					),
					'seo_schema'          => array(
						'type'        => 'checkbox',
						'label'       => __( 'Structured data (Product, Store, WebSite, Breadcrumbs, FAQ)', 'aurelia-commerce' ),
						'default'     => 1,
						'description' => __( 'When Yoast, Rank Math or AIOSEO is active, their schema is extended instead of duplicated.', 'aurelia-commerce' ),
					),
					'seo_social_meta'     => array(
						'type'    => 'checkbox',
						'label'   => __( 'Open Graph & Twitter meta (skipped when an SEO plugin is active)', 'aurelia-commerce' ),
						'default' => 1,
					),
					'seo_llms'            => array(
						'type'    => 'checkbox',
						'label'   => __( 'Publish /llms.txt and /llms-full.txt for AI assistants', 'aurelia-commerce' ),
						'default' => 1,
					),
					'seo_ai_crawlers'     => array(
						'type'    => 'checkbox',
						'label'   => __( 'Allow AI crawlers in robots.txt', 'aurelia-commerce' ),
						'default' => 1,
					),
					'seo_feed'            => array(
						'type'    => 'checkbox',
						'label'   => __( 'Google Merchant / Meta catalogue feed at /product-feed.xml', 'aurelia-commerce' ),
						'default' => 1,
					),
					'return_days'         => array(
						'type'    => 'number',
						'label'   => __( 'Return window (days)', 'aurelia-commerce' ),
						'default' => 15,
						'min'     => 0,
						'max'     => 365,
					),
					'shipping_cost'       => array(
						'type'    => 'number',
						'label'   => __( 'Standard shipping cost (for schema)', 'aurelia-commerce' ),
						'default' => 79,
						'min'     => 0,
						'max'     => 100000,
					),
					'google_category'     => array(
						'type'        => 'text',
						'label'       => __( 'Default Google product category', 'aurelia-commerce' ),
						'default'     => '',
						'description' => __( 'Optional Google taxonomy ID used in the feed, e.g. 188 for jewellery.', 'aurelia-commerce' ),
					),
				),
			),
		);
	}

	/**
	 * Flat map of field key => definition.
	 *
	 * @return array
	 */
	public static function fields() {
		$out = array();
		foreach ( self::schema() as $tab ) {
			$out += $tab['fields'];
		}
		return $out;
	}

	/**
	 * All settings merged with defaults (secrets excluded).
	 *
	 * @return array
	 */
	public static function all() {
		if ( null === self::$cache ) {
			$defaults = array();
			foreach ( self::fields() as $key => $field ) {
				if ( 'secret' !== $field['type'] ) {
					$defaults[ $key ] = $field['default'];
				}
			}
			$saved       = get_option( self::OPTION, array() );
			self::$cache = array_merge( $defaults, is_array( $saved ) ? $saved : array() );
		}
		return self::$cache;
	}

	/**
	 * Get one setting.
	 *
	 * @param string $key      Key.
	 * @param mixed  $fallback Fallback when unknown.
	 * @return mixed
	 */
	public static function get( $key, $fallback = null ) {
		$all = self::all();
		return array_key_exists( $key, $all ) ? $all[ $key ] : $fallback;
	}

	/**
	 * Whether a checkbox setting is on.
	 *
	 * @param string $key Key.
	 * @return bool
	 */
	public static function on( $key ) {
		return (bool) self::get( $key, false );
	}

	/**
	 * Read a secret (server-side only).
	 *
	 * @param string $key Secret key.
	 * @return string
	 */
	public static function secret( $key ) {
		$secrets = get_option( self::SECRETS, array() );
		return is_array( $secrets ) && isset( $secrets[ $key ] ) ? (string) $secrets[ $key ] : '';
	}

	/**
	 * Update settings programmatically (wizard, demo import).
	 *
	 * @param array $values Key => value.
	 */
	public static function update( array $values ) {
		$settings = get_option( self::OPTION, array() );
		$settings = is_array( $settings ) ? $settings : array();
		$secrets  = get_option( self::SECRETS, array() );
		$secrets  = is_array( $secrets ) ? $secrets : array();
		$fields   = self::fields();
		foreach ( $values as $key => $value ) {
			if ( ! isset( $fields[ $key ] ) ) {
				continue;
			}
			if ( 'secret' === $fields[ $key ]['type'] ) {
				if ( '' !== (string) $value ) {
					$secrets[ $key ] = sanitize_text_field( $value );
				}
				continue;
			}
			$settings[ $key ] = self::sanitize_field( $fields[ $key ], $value );
		}
		update_option( self::OPTION, $settings );
		update_option( self::SECRETS, $secrets, false );
		self::$cache = null;
	}

	/**
	 * Sanitize a submitted settings array (Settings API callback).
	 *
	 * @param mixed $input Raw input.
	 * @return array
	 */
	public static function sanitize( $input ) {
		$input   = is_array( $input ) ? $input : array();
		$current = get_option( self::OPTION, array() );
		$current = is_array( $current ) ? $current : array();
		$secrets = get_option( self::SECRETS, array() );
		$secrets = is_array( $secrets ) ? $secrets : array();
		$schema  = self::schema();

		if ( ! isset( $input['_tab'] ) ) {
			// Programmatic update_option() call: sanitize whichever known fields are present.
			$out = array();
			foreach ( self::fields() as $key => $field ) {
				if ( array_key_exists( $key, $input ) && 'secret' !== $field['type'] ) {
					$out[ $key ] = self::sanitize_field( $field, $input[ $key ] );
				}
			}
			return $out;
		}

		$tab    = sanitize_key( $input['_tab'] );
		$fields = isset( $schema[ $tab ] ) ? $schema[ $tab ]['fields'] : array();

		foreach ( $fields as $key => $field ) {
			if ( 'secret' === $field['type'] ) {
				$value = isset( $input[ $key ] ) ? trim( (string) $input[ $key ] ) : '';
				if ( ! empty( $input[ $key . '_clear' ] ) ) {
					unset( $secrets[ $key ] );
				} elseif ( '' !== $value ) {
					$secrets[ $key ] = sanitize_text_field( $value );
				}
				continue;
			}
			$current[ $key ] = self::sanitize_field( $field, $input[ $key ] ?? null );
		}
		update_option( self::SECRETS, $secrets, false );
		self::$cache = null;
		return $current;
	}

	/**
	 * Sanitize one value according to its field type.
	 *
	 * @param array $field Field definition.
	 * @param mixed $value Raw value.
	 * @return mixed
	 */
	public static function sanitize_field( array $field, $value ) {
		switch ( $field['type'] ) {
			case 'checkbox':
				return empty( $value ) ? 0 : 1;
			case 'number':
				$num = is_numeric( $value ) ? (float) $value : (float) $field['default'];
				if ( isset( $field['min'] ) ) {
					$num = max( (float) $field['min'], $num );
				}
				if ( isset( $field['max'] ) ) {
					$num = min( (float) $field['max'], $num );
				}
				return floor( $num ) === $num ? (int) $num : $num;
			case 'select':
				return isset( $field['options'][ $value ] ) ? $value : $field['default'];
			case 'color':
				$color = sanitize_hex_color( (string) $value );
				return $color ? $color : $field['default'];
			case 'email':
				return sanitize_email( (string) $value );
			case 'url':
				return esc_url_raw( (string) $value );
			case 'textarea':
				return sanitize_textarea_field( (string) $value );
			case 'html':
				return wp_kses_post( (string) $value );
			default:
				return sanitize_text_field( (string) $value );
		}
	}

	/**
	 * Store name with site-title fallback.
	 *
	 * @return string
	 */
	public static function store_name() {
		$name = (string) self::get( 'store_name', '' );
		return '' !== $name ? $name : get_bloginfo( 'name' );
	}

	/**
	 * Digits-only WhatsApp number.
	 *
	 * @return string
	 */
	public static function whatsapp_number() {
		return preg_replace( '/\D+/', '', (string) self::get( 'wa_number', '' ) );
	}

	/**
	 * Reset the in-memory cache (after option updates).
	 */
	public static function flush() {
		self::$cache = null;
	}
}
