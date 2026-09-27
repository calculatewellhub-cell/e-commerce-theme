<?php
/**
 * SEO & AIO: structured data, social meta, llms.txt, AI-friendly robots rules
 * and a Google Merchant / Meta catalogue feed.
 *
 * When Yoast SEO, Rank Math or All in One SEO is active, their schema graph is
 * extended instead of printing a duplicate one.
 *
 * @package Aurelia_Commerce
 */

namespace Aurelia\Commerce;

defined( 'ABSPATH' ) || exit;

/**
 * SEO.
 */
class Seo {

	const AI_BOTS = array( 'GPTBot', 'OAI-SearchBot', 'ChatGPT-User', 'ClaudeBot', 'Claude-SearchBot', 'Claude-User', 'PerplexityBot', 'Perplexity-User', 'Google-Extended', 'Applebot-Extended', 'Bingbot', 'CCBot', 'meta-externalagent', 'Amazonbot', 'DuckAssistBot' );

	/**
	 * Hooks.
	 */
	public function init() {
		add_action( 'init', array( $this, 'rewrites' ) );
		add_filter( 'query_vars', array( $this, 'query_vars' ) );
		add_action( 'template_redirect', array( $this, 'serve' ), 0 );
		add_filter( 'robots_txt', array( $this, 'robots' ), 20, 2 );
		foreach ( array( 'save_post_product', 'woocommerce_update_product', 'edited_product_cat' ) as $hook ) {
			add_action( $hook, array( __CLASS__, 'flush_cache' ) );
		}

		if ( Settings::on( 'seo_schema' ) ) {
			add_filter( 'woocommerce_structured_data_product', array( $this, 'extend_wc_product' ), 20, 2 );
			add_filter( 'wpseo_schema_graph', array( $this, 'extend_yoast' ), 20 );
			add_filter( 'rank_math/json_ld', array( $this, 'extend_rank_math' ), 99 );
			add_filter( 'aioseo_schema_output', array( $this, 'extend_aioseo' ), 20 );
			add_action( 'wp_head', array( $this, 'print_schema' ), 30 );
		}
		if ( Settings::on( 'seo_social_meta' ) ) {
			add_action( 'wp_head', array( $this, 'social_meta' ), 5 );
		}
	}

	/**
	 * Active SEO plugin, if any.
	 *
	 * @return string yoast|rankmath|aioseo|''
	 */
	public static function seo_plugin() {
		if ( defined( 'WPSEO_VERSION' ) ) {
			return 'yoast';
		}
		if ( class_exists( 'RankMath' ) || defined( 'RANK_MATH_VERSION' ) ) {
			return 'rankmath';
		}
		if ( defined( 'AIOSEO_VERSION' ) || function_exists( 'aioseo' ) ) {
			return 'aioseo';
		}
		return '';
	}

	/**
	 * Clear cached llms/feed output.
	 */
	public static function flush_cache() {
		delete_transient( 'aurelia_llms_short' );
		delete_transient( 'aurelia_llms_full' );
		delete_transient( 'aurelia_feed' );
	}

	/**
	 * Rewrite rules.
	 */
	public function rewrites() {
		add_rewrite_rule( '^llms\.txt$', 'index.php?aurelia_llms=short', 'top' );
		add_rewrite_rule( '^llms-full\.txt$', 'index.php?aurelia_llms=full', 'top' );
		add_rewrite_rule( '^product-feed\.xml$', 'index.php?aurelia_feed=1', 'top' );
	}

	/**
	 * Query vars.
	 *
	 * @param array $vars Vars.
	 * @return array
	 */
	public function query_vars( $vars ) {
		$vars[] = 'aurelia_llms';
		$vars[] = 'aurelia_feed';
		return $vars;
	}

	/**
	 * Serve llms.txt and the product feed.
	 */
	public function serve() {
		$llms = get_query_var( 'aurelia_llms' );
		if ( $llms && Settings::on( 'seo_llms' ) ) {
			$full = 'full' === $llms;
			$key  = $full ? 'aurelia_llms_full' : 'aurelia_llms_short';
			$body = get_transient( $key );
			if ( ! is_string( $body ) ) {
				$body = $this->llms_txt( $full );
				set_transient( $key, $body, 6 * HOUR_IN_SECONDS );
			}
			status_header( 200 );
			header( 'Content-Type: text/plain; charset=utf-8' );
			header( 'X-Robots-Tag: noindex' );
			echo $body; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- plain-text markdown document built from sanitised data.
			exit;
		}
		if ( get_query_var( 'aurelia_feed' ) && Settings::on( 'seo_feed' ) ) {
			$body = get_transient( 'aurelia_feed' );
			if ( ! is_string( $body ) ) {
				$body = $this->feed();
				set_transient( 'aurelia_feed', $body, HOUR_IN_SECONDS );
			}
			status_header( 200 );
			header( 'Content-Type: application/xml; charset=utf-8' );
			echo $body; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- XML built with esc_xml().
			exit;
		}
	}

	/**
	 * Robots.txt rules: welcome search engines and AI answer engines.
	 *
	 * @param string $output Output.
	 * @param bool   $is_public Site visibility.
	 * @return string
	 */
	public function robots( $output, $is_public ) {
		if ( ! $is_public ) {
			return $output;
		}
		$disallow = array( '/wp-admin/', '/cart/', '/checkout/', '/my-account/', '/go/', '/*?add-to-cart=', '/*?orderby=' );
		$lines    = array( '', '# Aurelia Commerce' );
		$lines[]  = 'User-agent: *';
		foreach ( $disallow as $path ) {
			$lines[] = 'Disallow: ' . $path;
		}
		$lines[] = 'Allow: /wp-admin/admin-ajax.php';
		if ( Settings::on( 'seo_ai_crawlers' ) ) {
			$lines[] = '';
			$lines[] = '# AI assistants and answer engines are welcome (see /llms.txt)';
			foreach ( self::AI_BOTS as $bot ) {
				$lines[] = 'User-agent: ' . $bot;
			}
			$lines[] = 'Allow: /';
			$lines[] = 'Allow: /llms.txt';
			$lines[] = 'Allow: /llms-full.txt';
			foreach ( $disallow as $path ) {
				$lines[] = 'Disallow: ' . $path;
			}
		}
		if ( ! str_contains( $output, 'Sitemap:' ) ) {
			$lines[] = '';
			$lines[] = 'Sitemap: ' . home_url( '/wp-sitemap.xml' );
		}
		return $output . implode( "\n", $lines ) . "\n";
	}

	// Structured data.

	/**
	 * Shipping details + return policy added to an Offer.
	 *
	 * @param array $offer Offer.
	 * @return array
	 */
	private function extend_offer( array $offer ) {
		$country = (string) Settings::get( 'store_country', 'IN' );
		$free    = (float) Settings::get( 'free_shipping_min', 0 );
		$price   = (float) ( $offer['price'] ?? $offer['lowPrice'] ?? 0 );
		$cost    = ( $free > 0 && $price >= $free ) ? 0 : (float) Settings::get( 'shipping_cost', 0 );
		if ( empty( $offer['shippingDetails'] ) ) {
			$offer['shippingDetails'] = array(
				'@type'               => 'OfferShippingDetails',
				'shippingRate'        => array(
					'@type'    => 'MonetaryAmount',
					'value'    => $cost,
					'currency' => get_woocommerce_currency(),
				),
				'shippingDestination' => array(
					'@type'          => 'DefinedRegion',
					'addressCountry' => $country,
				),
				'deliveryTime'        => array(
					'@type'        => 'ShippingDeliveryTime',
					'handlingTime' => array(
						'@type'    => 'QuantitativeValue',
						'minValue' => 0,
						'maxValue' => 2,
						'unitCode' => 'DAY',
					),
					'transitTime'  => array(
						'@type'    => 'QuantitativeValue',
						'minValue' => (int) Settings::get( 'delivery_min', 2 ),
						'maxValue' => (int) Settings::get( 'delivery_max', 6 ),
						'unitCode' => 'DAY',
					),
				),
			);
		}
		$days = (int) Settings::get( 'return_days', 15 );
		if ( empty( $offer['hasMerchantReturnPolicy'] ) ) {
			$offer['hasMerchantReturnPolicy'] = $days > 0 ? array(
				'@type'                => 'MerchantReturnPolicy',
				'applicableCountry'    => $country,
				'returnPolicyCategory' => 'https://schema.org/MerchantReturnFiniteReturnWindow',
				'merchantReturnDays'   => $days,
				'returnMethod'         => 'https://schema.org/ReturnByMail',
				'returnFees'           => 'https://schema.org/FreeReturn',
			) : array(
				'@type'                => 'MerchantReturnPolicy',
				'applicableCountry'    => $country,
				'returnPolicyCategory' => 'https://schema.org/MerchantReturnNotPermitted',
			);
		}
		if ( empty( $offer['itemCondition'] ) ) {
			$offer['itemCondition'] = 'https://schema.org/NewCondition';
		}
		return $offer;
	}

	/**
	 * Extend a Product node (any source).
	 *
	 * @param array            $node    Product node.
	 * @param \WC_Product|null $product Product.
	 * @return array
	 */
	private function extend_product_node( array $node, $product = null ) {
		if ( isset( $node['offers'] ) && is_array( $node['offers'] ) ) {
			if ( isset( $node['offers']['@type'] ) ) {
				$node['offers'] = $this->extend_offer( $node['offers'] );
			} else {
				$node['offers'] = array_map( fn( $o ) => is_array( $o ) ? $this->extend_offer( $o ) : $o, $node['offers'] );
			}
		}
		if ( empty( $node['brand'] ) ) {
			$brand = '';
			if ( $product ) {
				$brand = (string) $product->get_attribute( 'pa_brand' );
				$brand = '' !== $brand ? $brand : (string) $product->get_attribute( 'brand' );
			}
			$node['brand'] = array(
				'@type' => 'Brand',
				'name'  => '' !== $brand ? $brand : Settings::store_name(),
			);
		}
		return $node;
	}

	/**
	 * WooCommerce's own Product schema.
	 *
	 * @param array       $markup  Markup.
	 * @param \WC_Product $product Product.
	 * @return array
	 */
	public function extend_wc_product( $markup, $product ) {
		return $this->extend_product_node( (array) $markup, $product );
	}

	/**
	 * Yoast graph.
	 *
	 * @param array $graph Graph pieces.
	 * @return array
	 */
	public function extend_yoast( $graph ) {
		$product = is_singular( 'product' ) ? wc_get_product( get_queried_object_id() ) : null;
		foreach ( $graph as $i => $piece ) {
			$types = (array) ( $piece['@type'] ?? array() );
			if ( in_array( 'Product', $types, true ) ) {
				$graph[ $i ] = $this->extend_product_node( $piece, $product );
			}
		}
		$faq = $this->faq_node();
		if ( $faq ) {
			$graph[] = $faq;
		}
		return $graph;
	}

	/**
	 * Rank Math JSON-LD.
	 *
	 * @param array $data Entities.
	 * @return array
	 */
	public function extend_rank_math( $data ) {
		$product = is_singular( 'product' ) ? wc_get_product( get_queried_object_id() ) : null;
		foreach ( (array) $data as $key => $entity ) {
			if ( is_array( $entity ) && in_array( 'Product', (array) ( $entity['@type'] ?? array() ), true ) ) {
				$data[ $key ] = $this->extend_product_node( $entity, $product );
			}
		}
		$faq = $this->faq_node();
		if ( $faq && ! isset( $data['FAQPage'] ) ) {
			$data['AureliaFAQPage'] = $faq;
		}
		return $data;
	}

	/**
	 * AIOSEO graphs.
	 *
	 * @param array $graphs Graphs.
	 * @return array
	 */
	public function extend_aioseo( $graphs ) {
		$product = is_singular( 'product' ) ? wc_get_product( get_queried_object_id() ) : null;
		foreach ( (array) $graphs as $i => $graph ) {
			if ( is_array( $graph ) && in_array( 'Product', (array) ( $graph['@type'] ?? array() ), true ) ) {
				$graphs[ $i ] = $this->extend_product_node( $graph, $product );
			}
		}
		$faq = $this->faq_node();
		if ( $faq ) {
			$graphs[] = $faq;
		}
		return $graphs;
	}

	/**
	 * Print our own graph when no SEO plugin is active.
	 */
	public function print_schema() {
		if ( '' !== self::seo_plugin() ) {
			return;
		}
		$graph = array();
		if ( is_front_page() ) {
			$graph[] = $this->store_node();
			$graph[] = array(
				'@type'           => 'WebSite',
				'@id'             => home_url( '/#website' ),
				'url'             => home_url( '/' ),
				'name'            => Settings::store_name(),
				'description'     => get_bloginfo( 'description' ),
				'inLanguage'      => get_bloginfo( 'language' ),
				'publisher'       => array( '@id' => home_url( '/#store' ) ),
				'potentialAction' => array(
					'@type'       => 'SearchAction',
					'target'      => array(
						'@type'       => 'EntryPoint',
						'urlTemplate' => home_url( '/?s={search_term_string}&post_type=product' ),
					),
					'query-input' => 'required name=search_term_string',
				),
			);
		}
		// WooCommerce prints BreadcrumbList for shop pages; add one for regular pages and posts.
		if ( is_singular() && ! is_front_page() && ! ( function_exists( 'is_woocommerce' ) && is_woocommerce() ) ) {
			$graph[] = $this->breadcrumb_node();
		}
		$faq = $this->faq_node();
		if ( $faq ) {
			$graph[] = $faq;
		}
		$graph = array_values( array_filter( $graph ) );
		if ( ! $graph ) {
			return;
		}
		wp_print_inline_script_tag(
			wp_json_encode(
				array(
					'@context' => 'https://schema.org',
					'@graph'   => $graph,
				),
				JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
			),
			array( 'type' => 'application/ld+json' )
		);
	}

	/**
	 * Organization / Store node.
	 *
	 * @return array
	 */
	private function store_node() {
		$logo_id = (int) get_theme_mod( 'custom_logo' );
		$logo    = $logo_id ? wp_get_attachment_image_url( $logo_id, 'full' ) : get_site_icon_url( 512 );
		$node    = array(
			'@type'              => (string) Settings::get( 'store_type', 'Store' ),
			'@id'                => home_url( '/#store' ),
			'name'               => Settings::store_name(),
			'url'                => home_url( '/' ),
			'description'        => get_bloginfo( 'description' ),
			'currenciesAccepted' => get_woocommerce_currency(),
			'paymentAccepted'    => 'UPI, Credit Card, Debit Card, Net Banking, Cash on Delivery',
		);
		if ( $logo ) {
			$node['logo']  = $logo;
			$node['image'] = $logo;
		}
		foreach ( array(
			'telephone'    => 'store_phone',
			'email'        => 'store_email',
			'openingHours' => 'store_hours',
		) as $prop => $key ) {
			$value = (string) Settings::get( $key, '' );
			if ( '' !== $value ) {
				$node[ $prop ] = $value;
			}
		}
		if ( '' !== (string) Settings::get( 'store_address', '' ) ) {
			$node['address'] = array(
				'@type'           => 'PostalAddress',
				'streetAddress'   => Settings::get( 'store_address' ),
				'addressLocality' => Settings::get( 'store_city' ),
				'addressRegion'   => Settings::get( 'store_region' ),
				'postalCode'      => Settings::get( 'store_postcode' ),
				'addressCountry'  => Settings::get( 'store_country' ),
			);
		}
		$same = array_values( array_filter( array_map( 'esc_url_raw', array_map( 'trim', explode( "\n", (string) Settings::get( 'social_profiles', '' ) ) ) ) ) );
		if ( $same ) {
			$node['sameAs'] = $same;
		}
		if ( 'Store' !== $node['@type'] && 'OnlineStore' !== $node['@type'] && '' === (string) Settings::get( 'store_address', '' ) ) {
			// LocalBusiness subtypes need an address; fall back to OnlineStore.
			$node['@type'] = 'OnlineStore';
		}
		return $node;
	}

	/**
	 * BreadcrumbList for pages and posts.
	 *
	 * @return array
	 */
	private function breadcrumb_node() {
		$items = array(
			array(
				'name' => __( 'Home', 'aurelia-commerce' ),
				'url'  => home_url( '/' ),
			),
		);
		$post  = get_queried_object();
		if ( $post instanceof \WP_Post ) {
			foreach ( array_reverse( get_post_ancestors( $post ) ) as $ancestor ) {
				$items[] = array(
					'name' => get_the_title( $ancestor ),
					'url'  => get_permalink( $ancestor ),
				);
			}
			$items[] = array(
				'name' => get_the_title( $post ),
				'url'  => get_permalink( $post ),
			);
		}
		$list = array();
		foreach ( $items as $i => $item ) {
			$list[] = array(
				'@type'    => 'ListItem',
				'position' => $i + 1,
				'name'     => wp_strip_all_tags( $item['name'] ),
				'item'     => $item['url'],
			);
		}
		return array(
			'@type'           => 'BreadcrumbList',
			'itemListElement' => $list,
		);
	}

	/**
	 * FAQPage from FAQ-style Details blocks on the current page.
	 *
	 * @return array|null
	 */
	private function faq_node() {
		if ( ! is_singular() && ! is_front_page() ) {
			return null;
		}
		$post = get_queried_object();
		if ( ! $post instanceof \WP_Post ) {
			return null;
		}
		$faqs = self::faqs_from_content( $post->post_content );
		if ( ! $faqs ) {
			return null;
		}
		return array(
			'@type'      => 'FAQPage',
			'@id'        => get_permalink( $post ) . '#faq',
			'mainEntity' => array_map(
				static fn( $f ) => array(
					'@type'          => 'Question',
					'name'           => $f['q'],
					'acceptedAnswer' => array(
						'@type' => 'Answer',
						'text'  => $f['a'],
					),
				),
				$faqs
			),
		);
	}

	/**
	 * Extract FAQs (core/details with the "FAQ" style) from block content,
	 * following pattern references.
	 *
	 * @param string $content Post content.
	 * @param int    $depth   Recursion guard.
	 * @return array<int, array{q:string, a:string}>
	 */
	public static function faqs_from_content( $content, $depth = 0 ) {
		if ( $depth > 3 || ! str_contains( (string) $content, 'wp:' ) ) {
			return array();
		}
		$out  = array();
		$walk = static function ( $blocks ) use ( &$walk, &$out, $depth ) {
			foreach ( $blocks as $block ) {
				if ( 'core/pattern' === $block['blockName'] && ! empty( $block['attrs']['slug'] ) ) {
					$pattern = \WP_Block_Patterns_Registry::get_instance()->get_registered( $block['attrs']['slug'] );
					if ( $pattern && ! empty( $pattern['content'] ) ) {
						$out = array_merge( $out, self::faqs_from_content( $pattern['content'], $depth + 1 ) );
					}
					continue;
				}
				if ( 'core/details' === $block['blockName'] && str_contains( (string) ( $block['attrs']['className'] ?? '' ), 'is-style-faq' ) ) {
					$html = (string) $block['innerHTML'] . implode( '', array_map( 'render_block', $block['innerBlocks'] ) );
					if ( preg_match( '#<summary[^>]*>(.*?)</summary>#s', $html, $m ) ) {
						$q = trim( wp_strip_all_tags( $m[1] ) );
						$a = trim( preg_replace( '/\s+/', ' ', wp_strip_all_tags( str_replace( $m[0], '', $html ) ) ) );
						if ( '' !== $q && '' !== $a ) {
							$out[] = array(
								'q' => html_entity_decode( $q, ENT_QUOTES, 'UTF-8' ),
								'a' => html_entity_decode( $a, ENT_QUOTES, 'UTF-8' ),
							);
						}
					}
				}
				if ( ! empty( $block['innerBlocks'] ) ) {
					$walk( $block['innerBlocks'] );
				}
			}
		};
		$walk( parse_blocks( $content ) );
		return $out;
	}

	/**
	 * FAQs across published pages (for the concierge and llms.txt).
	 *
	 * @return array
	 */
	public static function collect_faqs() {
		$pages = get_posts(
			array(
				'post_type'      => 'page',
				'post_status'    => 'publish',
				'posts_per_page' => 20,
				's'              => 'faq',
			)
		);
		$front = (int) get_option( 'page_on_front' );
		if ( $front ) {
			$pages[] = get_post( $front );
		}
		$out = array();
		foreach ( array_filter( $pages ) as $page ) {
			foreach ( self::faqs_from_content( $page->post_content ) as $faq ) {
				$out[ md5( $faq['q'] ) ] = $faq;
			}
		}
		return array_values( $out );
	}

	// Social meta.

	/**
	 * Open Graph / Twitter tags and meta description (skipped with SEO plugins).
	 */
	public function social_meta() {
		if ( '' !== self::seo_plugin() || is_admin() ) {
			return;
		}
		$title = wp_get_document_title();
		$desc  = get_bloginfo( 'description' );
		$image = '';
		$type  = 'website';
		$url   = home_url( add_query_arg( array() ) );
		$extra = array();

		if ( is_singular() ) {
			$post = get_queried_object();
			$url  = get_permalink( $post );
			$desc = has_excerpt( $post ) ? get_the_excerpt( $post ) : wp_trim_words( wp_strip_all_tags( strip_shortcodes( (string) $post->post_content ) ), 30, '…' );
			if ( has_post_thumbnail( $post ) ) {
				$image = (string) get_the_post_thumbnail_url( $post, 'large' );
			}
			if ( 'product' === $post->post_type ) {
				$product = wc_get_product( $post->ID );
				if ( $product ) {
					$type  = 'product';
					$desc  = wp_strip_all_tags( $product->get_short_description() ? $product->get_short_description() : $product->get_description() );
					$extra = array(
						'product:price:amount'   => wc_format_decimal( $product->get_price(), 2 ),
						'product:price:currency' => get_woocommerce_currency(),
						'product:availability'   => $product->is_in_stock() ? 'in stock' : 'out of stock',
					);
				}
			}
		} elseif ( is_tax() || is_category() || is_tag() ) {
			$term = get_queried_object();
			$url  = get_term_link( $term );
			$desc = '' !== trim( (string) $term->description ) ? $term->description : $desc;
		}
		if ( '' === $image ) {
			$image = (string) get_site_icon_url( 512 );
		}
		$desc = mb_substr( trim( preg_replace( '/\s+/', ' ', wp_strip_all_tags( (string) $desc ) ) ), 0, 180 );

		$tags = array_filter(
			array(
				'og:type'        => $type,
				'og:site_name'   => Settings::store_name(),
				'og:title'       => $title,
				'og:description' => $desc,
				'og:url'         => is_string( $url ) ? $url : '',
				'og:image'       => $image,
				'og:locale'      => get_locale(),
			) + $extra
		);
		if ( '' !== $desc ) {
			printf( '<meta name="description" content="%s">' . "\n", esc_attr( $desc ) );
		}
		foreach ( $tags as $property => $content ) {
			printf( '<meta property="%1$s" content="%2$s">' . "\n", esc_attr( $property ), esc_attr( (string) $content ) );
		}
		printf( '<meta name="twitter:card" content="%s">' . "\n", $image ? 'summary_large_image' : 'summary' );
	}

	// llms.txt and product feed.

	/**
	 * The llms.txt file (https://llmstxt.org) built from live data.
	 *
	 * @param bool $full Full product details.
	 * @return string
	 */
	public function llms_txt( $full = false ) {
		$clean = static fn( $s ) => trim( preg_replace( '/\s+/', ' ', html_entity_decode( wp_strip_all_tags( (string) $s ), ENT_QUOTES, 'UTF-8' ) ) );
		$lines = array( '# ' . Settings::store_name(), '' );
		$tag   = $clean( get_bloginfo( 'description' ) );
		if ( '' !== $tag ) {
			$lines[] = '> ' . $tag;
			$lines[] = '';
		}
		$about = array();
		$city  = trim( Settings::get( 'store_city', '' ) . ', ' . Settings::get( 'store_region', '' ), ', ' );
		/* translators: %s: store name. */
		$about[] = sprintf( __( '%s is an online store.', 'aurelia-commerce' ), Settings::store_name() );
		if ( '' !== $city ) {
			/* translators: %s: city. */
			$about[] = sprintf( __( 'Based in %s.', 'aurelia-commerce' ), $city );
		}
		if ( '' !== Settings::whatsapp_number() && Settings::on( 'wa_enabled' ) ) {
			/* translators: %s: WhatsApp number. */
			$about[] = sprintf( __( 'Orders can be placed on the website or on WhatsApp (+%s).', 'aurelia-commerce' ), Settings::whatsapp_number() );
		}
		$about[] = $clean( str_replace( "\n", ' ', (string) Settings::get( 'ai_policies', '' ) ) );
		$lines[] = implode( ' ', array_filter( $about ) );
		$lines[] = '';

		$cats = get_terms(
			array(
				'taxonomy'   => 'product_cat',
				'hide_empty' => true,
			)
		);
		if ( ! is_wp_error( $cats ) && $cats ) {
			$lines[] = '## ' . __( 'Collections', 'aurelia-commerce' );
			foreach ( $cats as $cat ) {
				$lines[] = sprintf( '- [%1$s](%2$s)%3$s', $cat->name, get_term_link( $cat ), '' !== $clean( $cat->description ) ? ': ' . $clean( $cat->description ) : '' );
			}
			$lines[] = '';
		}

		$lines[]  = '## ' . __( 'Products', 'aurelia-commerce' );
		$products = wc_get_products(
			array(
				'status'  => 'publish',
				'limit'   => $full ? 500 : 200,
				'orderby' => 'popularity',
				'order'   => 'DESC',
			)
		);
		foreach ( $products as $p ) {
			$price = $p->is_type( 'variable' ) ? Helpers::money( (float) $p->get_variation_price( 'min' ) ) . '+' : Helpers::money( (float) $p->get_price() );
			$short = $clean( $p->get_short_description() );
			if ( ! $full ) {
				$lines[] = sprintf( '- [%1$s](%2$s): %3$s%4$s', $p->get_name(), $p->get_permalink(), $price, '' !== $short ? ' — ' . mb_substr( $short, 0, 160 ) : '' );
				continue;
			}
			$lines[] = sprintf( '### [%1$s](%2$s)', $p->get_name(), $p->get_permalink() );
			/* translators: %s: price. */
			$lines[] = '- ' . sprintf( __( 'Price: %s', 'aurelia-commerce' ), $price );
			if ( $p->get_sku() ) {
				$lines[] = '- SKU: ' . $p->get_sku();
			}
			$lines[] = '- ' . __( 'Category', 'aurelia-commerce' ) . ': ' . implode( ', ', wp_get_post_terms( $p->get_id(), 'product_cat', array( 'fields' => 'names' ) ) );
			foreach ( $p->get_attributes() as $attribute ) {
				$values  = $attribute->is_taxonomy() ? wc_get_product_terms( $p->get_id(), $attribute->get_name(), array( 'fields' => 'names' ) ) : $attribute->get_options();
				$lines[] = '- ' . wc_attribute_label( $attribute->get_name() ) . ': ' . implode( ', ', (array) $values );
			}
			if ( $p->get_review_count() ) {
				/* translators: 1: rating, 2: review count. */
				$lines[] = '- ' . sprintf( __( 'Rating: %1$s/5 from %2$d reviews', 'aurelia-commerce' ), $p->get_average_rating(), $p->get_review_count() );
			}
			$lines[] = '- ' . ( $p->is_in_stock() ? __( 'In stock', 'aurelia-commerce' ) : __( 'Out of stock', 'aurelia-commerce' ) );
			$lines[] = '';
			$lines[] = mb_substr( $clean( $p->get_description() ? $p->get_description() : $p->get_short_description() ), 0, 1200 );
			$lines[] = '';
		}
		$lines[] = '';

		$faqs = self::collect_faqs();
		if ( $faqs ) {
			$lines[] = '## ' . __( 'Policies & FAQ', 'aurelia-commerce' );
			foreach ( $faqs as $faq ) {
				$lines[] = $full ? "### {$faq['q']}\n{$faq['a']}\n" : "- {$faq['q']} {$faq['a']}";
			}
			$lines[] = '';
		}

		$lines[] = '## ' . __( 'Key pages', 'aurelia-commerce' );
		$lines[] = '- [' . __( 'Shop all products', 'aurelia-commerce' ) . '](' . wc_get_page_permalink( 'shop' ) . ')';
		foreach ( array( 'about', 'contact', 'faq', 'shipping-returns' ) as $slug ) {
			$page = get_page_by_path( $slug );
			if ( $page && 'publish' === $page->post_status ) {
				$lines[] = '- [' . get_the_title( $page ) . '](' . get_permalink( $page ) . ')';
			}
		}
		if ( Settings::on( 'seo_feed' ) ) {
			$lines[] = '- [' . __( 'Product feed (XML)', 'aurelia-commerce' ) . '](' . home_url( '/product-feed.xml' ) . ')';
		}
		if ( ! $full ) {
			$lines[] = '';
			$lines[] = '## Optional';
			$lines[] = '- [' . __( 'Full product details', 'aurelia-commerce' ) . '](' . home_url( '/llms-full.txt' ) . ')';
		}
		return implode( "\n", $lines ) . "\n";
	}

	/**
	 * Google Merchant Center / Meta catalogue RSS feed.
	 *
	 * @return string
	 */
	public function feed() {
		$currency = get_woocommerce_currency();
		$country  = (string) Settings::get( 'store_country', 'IN' );
		$gcat     = (string) Settings::get( 'google_category', '' );
		$items    = array();
		$products = wc_get_products(
			array(
				'status' => 'publish',
				'limit'  => 2000,
				'type'   => array( 'simple', 'variable', 'external' ),
			)
		);
		foreach ( $products as $product ) {
			if ( ! $product->is_visible() ) {
				continue;
			}
			if ( $product->is_type( 'variable' ) ) {
				foreach ( $product->get_children() as $child_id ) {
					$variation = wc_get_product( $child_id );
					if ( $variation && $variation->is_purchasable() ) {
						$items[] = $this->feed_item( $variation, $product, $currency, $country, $gcat );
					}
				}
			} else {
				$items[] = $this->feed_item( $product, null, $currency, $country, $gcat );
			}
		}
		return '<?xml version="1.0" encoding="UTF-8"?>' . "\n"
			. '<rss version="2.0" xmlns:g="http://base.google.com/ns/1.0">' . "\n<channel>\n"
			. '<title>' . esc_xml( Settings::store_name() ) . "</title>\n"
			. '<link>' . esc_xml( home_url( '/' ) ) . "</link>\n"
			. '<description>' . esc_xml( get_bloginfo( 'description' ) ) . "</description>\n"
			. implode( "\n", $items ) . "\n</channel>\n</rss>\n";
	}

	/**
	 * One <item>.
	 *
	 * @param \WC_Product      $product  Product or variation.
	 * @param \WC_Product|null $parent_product Parent for variations.
	 * @param string           $currency Currency.
	 * @param string           $country  Country.
	 * @param string           $gcat     Google category.
	 * @return string
	 */
	private function feed_item( $product, $parent_product, $currency, $country, $gcat ) {
		$base  = $parent_product ? $parent_product : $product;
		$desc  = wp_strip_all_tags( $base->get_description() ? $base->get_description() : $base->get_short_description() );
		$image = $product->get_image_id() ? $product->get_image_id() : $base->get_image_id();
		$cats  = wp_get_post_terms( $base->get_id(), 'product_cat', array( 'fields' => 'names' ) );
		$brand = (string) $base->get_attribute( 'pa_brand' );
		$tags  = array(
			'g:id'                      => $product->get_sku() ? $product->get_sku() : (string) $product->get_id(),
			'g:title'                   => $parent_product ? $parent_product->get_name() . ' - ' . wc_get_formatted_variation( $product, true, false, false ) : $product->get_name(),
			'g:description'             => mb_substr( trim( preg_replace( '/\s+/', ' ', $desc ) ), 0, 4900 ),
			'g:link'                    => $product->get_permalink(),
			'g:image_link'              => $image ? (string) wp_get_attachment_image_url( $image, 'full' ) : '',
			'g:availability'            => $product->is_in_stock() ? 'in_stock' : ( $product->is_on_backorder() ? 'backorder' : 'out_of_stock' ),
			'g:price'                   => wc_format_decimal( $product->get_regular_price() ? $product->get_regular_price() : $product->get_price(), 2 ) . ' ' . $currency,
			'g:brand'                   => '' !== $brand ? $brand : Settings::store_name(),
			'g:condition'               => 'new',
			'g:product_type'            => implode( ' > ', (array) $cats ),
			'g:identifier_exists'       => 'no',
			'g:google_product_category' => $gcat,
			'g:item_group_id'           => $parent_product ? (string) $parent_product->get_id() : '',
		);
		if ( $product->is_on_sale() && $product->get_sale_price() ) {
			$tags['g:sale_price'] = wc_format_decimal( $product->get_sale_price(), 2 ) . ' ' . $currency;
		}
		foreach ( array(
			'color'  => 'g:color',
			'size'   => 'g:size',
			'colour' => 'g:color',
		) as $attr => $tag ) {
			$value = $parent_product ? (string) $product->get_attribute( 'pa_' . $attr ) : (string) $product->get_attribute( 'pa_' . $attr );
			if ( '' !== $value && empty( $tags[ $tag ] ) ) {
				$tags[ $tag ] = $value;
			}
		}
		$extra = '';
		foreach ( array_slice( $base->get_gallery_image_ids(), 0, 5 ) as $gid ) {
			$extra .= '<g:additional_image_link>' . esc_xml( (string) wp_get_attachment_image_url( $gid, 'full' ) ) . '</g:additional_image_link>';
		}
		$shipping_cost = (float) Settings::get( 'shipping_cost', 0 );
		$extra        .= '<g:shipping><g:country>' . esc_xml( $country ) . '</g:country><g:price>' . esc_xml( wc_format_decimal( $shipping_cost, 2 ) . ' ' . $currency ) . '</g:price></g:shipping>';
		$xml           = '<item>';
		foreach ( array_filter( $tags, static fn( $v ) => '' !== (string) $v ) as $tag => $value ) {
			$xml .= '<' . $tag . '>' . esc_xml( (string) $value ) . '</' . $tag . '>';
		}
		return $xml . $extra . '</item>';
	}
}
