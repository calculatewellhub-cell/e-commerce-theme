<?php
/**
 * One-click demo import: store settings, shipping, payments, attributes,
 * categories, products (with images, variations and 3D), coupons, pages,
 * mega menu. Everything created is tracked so it can be removed again.
 *
 * Runs in small steps (REST or WP-CLI) to stay within PHP time limits.
 *
 * @package Aurelia_Commerce
 */

namespace Aurelia\Commerce;

defined( 'ABSPATH' ) || exit;

/**
 * Demo importer.
 */
class Demo_Importer {

	const TRACK = 'aurelia_demo_ids';
	const BATCH = 7;

	/**
	 * Hooks.
	 */
	public function init() {
		add_action( 'rest_api_init', array( $this, 'routes' ) );
		if ( defined( 'WP_CLI' ) && WP_CLI ) {
			\WP_CLI::add_command( 'aurelia demo', Demo_Cli::class );
		}
	}

	/**
	 * Demo data.
	 *
	 * @return array
	 */
	public static function data() {
		static $data = null;
		if ( null === $data ) {
			$data = require AURELIA_COMMERCE_DIR . 'includes/demo/data.php';
		}
		return $data;
	}

	/**
	 * Ordered list of steps.
	 *
	 * @return string[]
	 */
	public static function steps() {
		$steps   = array( 'settings', 'taxonomy' );
		$batches = (int) ceil( count( self::data()['products'] ) / self::BATCH );
		for ( $i = 0; $i < $batches; $i++ ) {
			$steps[] = 'products_' . $i;
		}
		return array_merge( $steps, array( 'pages', 'menu', 'finish' ) );
	}

	/**
	 * REST routes.
	 */
	public function routes() {
		$can = static fn() => current_user_can( 'manage_woocommerce' ) && current_user_can( 'upload_files' );
		register_rest_route(
			'aurelia/v1',
			'/demo/import',
			array(
				'methods'             => 'POST',
				'permission_callback' => $can,
				'callback'            => function ( \WP_REST_Request $r ) {
					$steps = self::steps();
					$step  = sanitize_key( (string) $r->get_param( 'step' ) );
					$step  = '' === $step ? $steps[0] : $step;
					if ( ! in_array( $step, $steps, true ) ) {
						return new \WP_Error( 'aurelia_demo_step', 'Unknown step', array( 'status' => 400 ) );
					}
					$message = $this->run( $step );
					$index   = array_search( $step, $steps, true );
					return array(
						'step'     => $step,
						'message'  => $message,
						'next'     => $steps[ $index + 1 ] ?? null,
						'progress' => round( ( $index + 1 ) / count( $steps ) * 100 ),
					);
				},
			)
		);
		register_rest_route(
			'aurelia/v1',
			'/demo/remove',
			array(
				'methods'             => 'POST',
				'permission_callback' => $can,
				'callback'            => fn() => array( 'message' => $this->remove() ),
			)
		);
	}

	/**
	 * Run one step.
	 *
	 * @param string $step Step.
	 * @return string Log message.
	 */
	public function run( $step ) {
		wp_raise_memory_limit( 'admin' );
		if ( function_exists( 'set_time_limit' ) ) {
			set_time_limit( 120 ); // phpcs:ignore Squiz.PHP.DiscouragedFunctions.Discouraged -- image processing during import.
		}
		if ( str_starts_with( $step, 'products_' ) ) {
			return $this->import_products( (int) substr( $step, 9 ) );
		}
		switch ( $step ) {
			case 'settings':
				return $this->import_settings();
			case 'taxonomy':
				return $this->import_taxonomy();
			case 'pages':
				return $this->import_pages();
			case 'menu':
				return $this->import_menu();
			case 'finish':
				return $this->finish();
		}
		return '';
	}

	/**
	 * Remember something we created.
	 *
	 * @param string $group Group (posts, terms, attachments, attributes).
	 * @param mixed  $value Value.
	 */
	private function track( $group, $value ) {
		$ids             = (array) get_option( self::TRACK, array() );
		$ids[ $group ]   = isset( $ids[ $group ] ) ? (array) $ids[ $group ] : array();
		$ids[ $group ][] = $value;
		update_option( self::TRACK, $ids, false );
	}

	/**
	 * Store-wide settings, shipping, payments, coupons.
	 *
	 * @return string
	 */
	private function import_settings() {
		update_option( 'woocommerce_currency', 'INR' );
		update_option( 'woocommerce_currency_pos', 'left' );
		update_option( 'woocommerce_price_num_decimals', 0 );
		update_option( 'woocommerce_price_thousand_sep', ',' );
		update_option( 'woocommerce_price_decimal_sep', '.' );
		if ( ! get_option( 'woocommerce_default_country' ) || str_starts_with( (string) get_option( 'woocommerce_default_country' ), 'US' ) ) {
			update_option( 'woocommerce_default_country', 'IN:MH' );
		}
		update_option( 'woocommerce_calc_taxes', 'no' );
		update_option( 'woocommerce_enable_reviews', 'yes' );
		update_option( 'woocommerce_enable_review_rating', 'yes' );
		update_option( 'woocommerce_review_rating_verification_required', 'no' );
		update_option( 'woocommerce_coming_soon', 'no' );
		update_option( 'woocommerce_manage_stock', 'yes' );
		if ( 'UTC' === wp_timezone_string() || '+00:00' === wp_timezone_string() ) {
			update_option( 'timezone_string', 'Asia/Kolkata' );
		}
		if ( '' === (string) get_option( 'permalink_structure' ) ) {
			update_option( 'permalink_structure', '/%postname%/' );
		}

		// Shipping: India, ₹79 flat, free over ₹999.
		$zone_exists = false;
		foreach ( \WC_Shipping_Zones::get_zones() as $zone ) {
			if ( 'India' === $zone['zone_name'] ) {
				$zone_exists = true;
			}
		}
		if ( ! $zone_exists ) {
			$zone = new \WC_Shipping_Zone();
			$zone->set_zone_name( 'India' );
			$zone->set_zone_order( 1 );
			$zone->add_location( 'IN', 'country' );
			$zone->save();
			$flat_id = $zone->add_shipping_method( 'flat_rate' );
			update_option(
				'woocommerce_flat_rate_' . $flat_id . '_settings',
				array(
					'title'      => __( 'Standard delivery', 'aurelia-commerce' ),
					'tax_status' => 'none',
					'cost'       => '79',
				)
			);
			$free_id = $zone->add_shipping_method( 'free_shipping' );
			update_option(
				'woocommerce_free_shipping_' . $free_id . '_settings',
				array(
					'title'            => __( 'Free delivery', 'aurelia-commerce' ),
					'requires'         => 'min_amount',
					'min_amount'       => '999',
					'ignore_discounts' => 'no',
				)
			);
		}

		// Cash on delivery on; UPI on when a UPI ID exists.
		$cod            = (array) get_option( 'woocommerce_cod_settings', array() );
		$cod['enabled'] = 'yes';
		$cod           += array(
			'title'       => __( 'Cash on delivery', 'aurelia-commerce' ),
			'description' => __( 'Pay in cash or by UPI when your order arrives.', 'aurelia-commerce' ),
		);
		update_option( 'woocommerce_cod_settings', $cod );

		foreach ( self::data()['coupons'] as $code => $coupon ) {
			if ( wc_get_coupon_id_by_code( $code ) ) {
				continue;
			}
			$c = new \WC_Coupon();
			$c->set_code( $code );
			$c->set_discount_type( 'percent' === $coupon[0] ? 'percent' : 'fixed_cart' );
			$c->set_amount( $coupon[1] );
			$c->set_description( $coupon[2] );
			$c->set_individual_use( true );
			$this->track( 'posts', $c->save() );
		}

		Settings::update(
			array(
				'free_shipping_min' => 999,
				'popup_coupon'      => 'WELCOME10',
			)
		);
		return __( 'Store settings, shipping zone, cash on delivery and coupons ready.', 'aurelia-commerce' );
	}

	/**
	 * Attributes (with swatch types) and product categories.
	 *
	 * @return string
	 */
	private function import_taxonomy() {
		foreach ( self::data()['attributes'] as $slug => $attribute ) {
			$id = wc_attribute_taxonomy_id_by_name( $slug );
			if ( ! $id ) {
				$id = wc_create_attribute(
					array(
						'name'         => $attribute['label'],
						'slug'         => $slug,
						'type'         => $attribute['type'],
						'order_by'     => 'menu_order',
						'has_archives' => false,
					)
				);
				if ( ! is_wp_error( $id ) ) {
					$this->track( 'attributes', $id );
				}
			}
			$taxonomy = wc_attribute_taxonomy_name( $slug );
			if ( ! taxonomy_exists( $taxonomy ) ) {
				register_taxonomy( $taxonomy, array( 'product' ), array( 'hierarchical' => false ) );
			}
			$order = 0;
			foreach ( $attribute['terms'] as $term_slug => $term ) {
				$existing = get_term_by( 'slug', (string) $term_slug, $taxonomy );
				$term_id  = $existing ? $existing->term_id : 0;
				if ( ! $term_id ) {
					$created = wp_insert_term( $term[0], $taxonomy, array( 'slug' => (string) $term_slug ) );
					if ( is_wp_error( $created ) ) {
						continue;
					}
					$term_id = $created['term_id'];
					$this->track( 'terms', array( $term_id, $taxonomy ) );
				}
				update_term_meta( $term_id, 'order', $order++ );
				if ( '' !== $term[1] ) {
					update_term_meta( $term_id, 'aurelia_swatch_color', $term[1] );
				}
			}
		}
		delete_transient( 'wc_attribute_taxonomies' );

		foreach ( self::data()['categories'] as $slug => $cat ) {
			if ( get_term_by( 'slug', $slug, 'product_cat' ) ) {
				continue;
			}
			$created = wp_insert_term(
				$cat[0],
				'product_cat',
				array(
					'slug'        => $slug,
					'description' => $cat[1],
				)
			);
			if ( ! is_wp_error( $created ) ) {
				$this->track( 'terms', array( $created['term_id'], 'product_cat' ) );
			}
		}
		return __( 'Colour and size attributes with swatches, and six categories created.', 'aurelia-commerce' );
	}

	/**
	 * Sideload a demo image into the Media Library.
	 *
	 * @param string $image Image slug.
	 * @param string $alt   Alt text.
	 * @return int Attachment ID or 0.
	 */
	private function image( $image, $alt ) {
		$existing = get_posts(
			array(
				'post_type'      => 'attachment',
				'post_status'    => 'inherit',
				'meta_key'       => '_aurelia_demo_image', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- one-off import lookup.
				'meta_value'     => $image, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value -- one-off import lookup.
				'fields'         => 'ids',
				'posts_per_page' => 1,
			)
		);
		if ( $existing ) {
			return (int) $existing[0];
		}
		$source = AURELIA_COMMERCE_DIR . 'assets/demo/' . $image . '.jpg';
		if ( ! is_readable( $source ) ) {
			return 0;
		}
		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/media.php';
		require_once ABSPATH . 'wp-admin/includes/image.php';
		$tmp = wp_tempnam( $image . '.jpg' );
		copy( $source, $tmp );
		$id = media_handle_sideload(
			array(
				'name'     => $image . '.jpg',
				'tmp_name' => $tmp,
			),
			0,
			$alt
		);
		if ( is_wp_error( $id ) ) {
			wp_delete_file( $tmp );
			return 0;
		}
		update_post_meta( $id, '_wp_attachment_image_alt', $alt );
		update_post_meta( $id, '_aurelia_demo_image', $image );
		$this->track( 'attachments', $id );
		return (int) $id;
	}

	/**
	 * Import one batch of products.
	 *
	 * @param int $batch Batch index.
	 * @return string
	 */
	private function import_products( $batch ) {
		$all     = self::data()['products'];
		$slice   = array_slice( $all, $batch * self::BATCH, self::BATCH, true );
		$reviews = array(
			array( 'Ananya R.', 5, __( 'Beautiful quality and the packaging felt like a gift. Delivered a day early!', 'aurelia-commerce' ) ),
			array( 'Rahul M.', 5, __( 'Exactly as pictured. The WhatsApp team answered all my questions before I ordered.', 'aurelia-commerce' ) ),
			array( 'Sara K.', 4, __( 'Lovely piece, great value. Would buy again.', 'aurelia-commerce' ) ),
		);
		$created = 0;
		foreach ( $slice as $slug => $p ) {
			if ( get_page_by_path( $slug, OBJECT, 'product' ) ) {
				continue;
			}
			$variable = ! empty( $p['vary'] );
			$product  = $variable ? new \WC_Product_Variable() : new \WC_Product_Simple();
			$product->set_name( $p['name'] );
			$product->set_slug( $slug );
			$product->set_status( 'publish' );
			$product->set_sku( $p['sku'] );
			$product->set_short_description( $p['short'] );
			$product->set_description( $p['desc'] );
			$product->set_featured( ! empty( $p['featured'] ) );
			$product->set_manage_stock( ! $variable );
			if ( ! $variable ) {
				$product->set_stock_quantity( 'celestial-diamond-tennis-bracelet' === $slug ? 3 : wp_rand( 12, 60 ) );
				$product->set_regular_price( (string) $p['price'] );
				if ( ! empty( $p['sale'] ) ) {
					$product->set_sale_price( (string) $p['sale'] );
					if ( ! empty( $p['featured'] ) ) {
						$product->set_date_on_sale_to( gmdate( 'Y-m-d 23:59:59', time() + 6 * DAY_IN_SECONDS ) );
					}
				}
			}
			$term = get_term_by( 'slug', $p['cat'], 'product_cat' );
			if ( $term ) {
				$product->set_category_ids( array( $term->term_id ) );
			}
			$tag_ids = array();
			foreach ( (array) ( $p['tags'] ?? array() ) as $tag ) {
				$t = term_exists( $tag, 'product_tag' );
				$t = $t ? $t : wp_insert_term( $tag, 'product_tag' );
				if ( ! is_wp_error( $t ) ) {
					$tag_ids[] = (int) $t['term_id'];
				}
			}
			$product->set_tag_ids( $tag_ids );
			$image = $this->image( $slug, $p['name'] );
			if ( $image ) {
				$product->set_image_id( $image );
			}

			$attributes = array();
			$position   = 0;
			foreach ( (array) ( $p['vary'] ?? array() ) as $attr_slug => $values ) {
				$taxonomy = wc_attribute_taxonomy_name( $attr_slug );
				$ids      = array();
				foreach ( $values as $value ) {
					$t = get_term_by( 'slug', (string) $value, $taxonomy );
					if ( $t ) {
						$ids[] = $t->term_id;
					}
				}
				$a = new \WC_Product_Attribute();
				$a->set_id( wc_attribute_taxonomy_id_by_name( $attr_slug ) );
				$a->set_name( $taxonomy );
				$a->set_options( $ids );
				$a->set_position( $position++ );
				$a->set_visible( true );
				$a->set_variation( true );
				$attributes[] = $a;
			}
			foreach ( (array) ( $p['attrs'] ?? array() ) as $label => $value ) {
				$a = new \WC_Product_Attribute();
				$a->set_name( $label );
				$a->set_options( array( $value ) );
				$a->set_position( $position++ );
				$a->set_visible( true );
				$a->set_variation( false );
				$attributes[] = $a;
			}
			$product->set_attributes( $attributes );
			$product_id = $product->save();
			$this->track( 'posts', $product_id );

			if ( $variable ) {
				$this->create_variations( $product_id, $p );
			}
			if ( ! empty( $p['3d'] ) ) {
				update_post_meta(
					$product_id,
					Viewer::META,
					array(
						'mode'   => $p['3d'][0],
						'metal'  => $p['3d'][1],
						'gem'    => $p['3d'][2],
						'no_gem' => 0,
						'model'  => '',
					)
				);
			}
			// Two demo reviews on featured products so ratings show up.
			if ( ! empty( $p['featured'] ) ) {
				foreach ( array_slice( $reviews, 0, 2 + ( $created % 2 ) ) as $review ) {
					$comment_id = wp_insert_comment(
						array(
							'comment_post_ID'  => $product_id,
							'comment_author'   => $review[0],
							'comment_content'  => $review[2],
							'comment_type'     => 'review',
							'comment_approved' => 1,
						)
					);
					if ( $comment_id ) {
						update_comment_meta( $comment_id, 'rating', $review[1] );
						update_comment_meta( $comment_id, '_aurelia_demo', 1 );
					}
				}
				\WC_Comments::clear_transients( $product_id );
			}
			++$created;
		}
		/* translators: %d: number of products. */
		return sprintf( __( 'Imported %d products with images.', 'aurelia-commerce' ), $created );
	}

	/**
	 * Variations for a variable product (all combinations).
	 *
	 * @param int   $product_id Product ID.
	 * @param array $p          Product data.
	 */
	private function create_variations( $product_id, array $p ) {
		$combos = array( array() );
		foreach ( $p['vary'] as $attr_slug => $values ) {
			$next = array();
			foreach ( $combos as $combo ) {
				foreach ( $values as $value ) {
					$next[] = $combo + array( 'pa_' . $attr_slug => (string) $value );
				}
			}
			$combos = $next;
		}
		$i = 0;
		foreach ( $combos as $combo ) {
			$variation = new \WC_Product_Variation();
			$variation->set_parent_id( $product_id );
			$variation->set_attributes( $combo );
			$bump = ( isset( $combo['pa_size'] ) && in_array( $combo['pa_size'], array( '14', '16', 'xl' ), true ) ) ? 1.04 : 1;
			$variation->set_regular_price( (string) round( $p['price'] * $bump ) );
			if ( ! empty( $p['sale'] ) ) {
				$variation->set_sale_price( (string) round( $p['sale'] * $bump ) );
			}
			$variation->set_manage_stock( true );
			// One combination sold out, to show unavailable swatches.
			$variation->set_stock_quantity( 1 === $i ? 0 : wp_rand( 5, 30 ) );
			$variation->set_sku( $p['sku'] . '-' . strtoupper( implode( '-', $combo ) ) );
			if ( isset( $combo['pa_color'], $p['variant_images'][ $combo['pa_color'] ] ) ) {
				$variation->set_image_id( $this->image( $p['variant_images'][ $combo['pa_color'] ], $p['name'] ) );
			}
			$variation->save();
			++$i;
		}
		\WC_Product_Variable::sync( $product_id );
		wc_delete_product_transients( $product_id );
	}

	/**
	 * Expand pattern references into real, editable blocks.
	 *
	 * @param string $content Block content.
	 * @param int    $depth   Recursion guard.
	 * @return string
	 */
	public static function expand_patterns( $content, $depth = 0 ) {
		if ( $depth > 4 ) {
			return $content;
		}
		return preg_replace_callback(
			'#<!-- wp:pattern \{"slug":"([a-z0-9/_-]+)"\} /-->#',
			static function ( $m ) use ( $depth ) {
				$pattern = \WP_Block_Patterns_Registry::get_instance()->get_registered( $m[1] );
				return $pattern ? self::expand_patterns( $pattern['content'], $depth + 1 ) : '';
			},
			(string) $content
		);
	}

	/**
	 * Create (or reuse) a page.
	 *
	 * @param string $slug     Slug.
	 * @param string $title    Title.
	 * @param string $content  Content.
	 * @param string $template Page template.
	 * @return int Page ID.
	 */
	private function page( $slug, $title, $content, $template = '' ) {
		$existing = get_page_by_path( $slug );
		if ( $existing ) {
			return (int) $existing->ID;
		}
		$id = wp_insert_post(
			array(
				'post_type'    => 'page',
				'post_status'  => 'publish',
				'post_name'    => $slug,
				'post_title'   => $title,
				'post_content' => $content,
			)
		);
		if ( $id && ! is_wp_error( $id ) ) {
			if ( '' !== $template ) {
				update_post_meta( $id, '_wp_page_template', $template );
			}
			$this->track( 'posts', $id );
			return (int) $id;
		}
		return 0;
	}

	/**
	 * Pages, front page, posts page.
	 *
	 * @return string
	 */
	private function import_pages() {
		$has_patterns = \WP_Block_Patterns_Registry::get_instance()->is_registered( 'aurelia/page-home' );
		$pattern      = static fn( $slug, $fallback ) => $has_patterns ? self::expand_patterns( '<!-- wp:pattern {"slug":"' . $slug . '"} /-->' ) : $fallback;
		$para         = static fn( $text ) => "<!-- wp:paragraph -->\n<p>" . esc_html( $text ) . "</p>\n<!-- /wp:paragraph -->";

		$home = $this->page( 'home', __( 'Home', 'aurelia-commerce' ), $pattern( 'aurelia/page-home', '<!-- wp:woocommerce/product-collection /-->' ), 'page-no-title' );
		$this->page( 'about', __( 'About us', 'aurelia-commerce' ), $pattern( 'aurelia/page-about', $para( __( 'Tell your story here.', 'aurelia-commerce' ) ) ), 'page-no-title' );
		$this->page( 'contact', __( 'Contact', 'aurelia-commerce' ), $pattern( 'aurelia/page-contact', $para( __( 'Add your contact details here.', 'aurelia-commerce' ) ) ), 'page-no-title' );
		$this->page( 'faq', __( 'FAQ', 'aurelia-commerce' ), $pattern( 'aurelia/faq', '' ) );
		$this->page(
			'shipping-returns',
			__( 'Shipping & returns', 'aurelia-commerce' ),
			implode(
				"\n\n",
				array(
					"<!-- wp:heading -->\n<h2 class=\"wp-block-heading\">" . esc_html__( 'Shipping', 'aurelia-commerce' ) . "</h2>\n<!-- /wp:heading -->",
					$para( __( 'We dispatch within 48 hours. Delivery takes 2–7 working days across India. Shipping is free on orders over ₹999; otherwise a flat ₹79 applies. You will receive tracking by SMS, email and WhatsApp.', 'aurelia-commerce' ) ),
					"<!-- wp:heading -->\n<h2 class=\"wp-block-heading\">" . esc_html__( 'Returns & exchanges', 'aurelia-commerce' ) . "</h2>\n<!-- /wp:heading -->",
					$para( __( 'Unused items in original packaging can be returned within 15 days of delivery. Message us on WhatsApp or email us with your order number and we will arrange a free pickup. Refunds are issued to the original payment method within 5–7 working days.', 'aurelia-commerce' ) ),
				)
			)
		);
		$wishlist = $this->page( 'wishlist', __( 'Wishlist', 'aurelia-commerce' ), "<!-- wp:shortcode -->\n[aurelia_wishlist]\n<!-- /wp:shortcode -->" );
		$compare  = $this->page( 'compare', __( 'Compare products', 'aurelia-commerce' ), "<!-- wp:shortcode -->\n[aurelia_compare]\n<!-- /wp:shortcode -->", 'page-wide' );
		$track    = $this->page( 'track-order', __( 'Track your order', 'aurelia-commerce' ), $para( __( 'Enter your order number and the email you used at checkout.', 'aurelia-commerce' ) ) . "\n\n<!-- wp:shortcode -->\n[woocommerce_order_tracking]\n<!-- /wp:shortcode -->" );
		$journal  = $this->page( 'journal', __( 'Journal', 'aurelia-commerce' ), '' );

		update_option(
			Shop::PAGES_OPTION,
			array(
				'wishlist' => $wishlist,
				'compare'  => $compare,
				'track'    => $track,
			)
		);
		if ( $home ) {
			update_option( 'show_on_front', 'page' );
			update_option( 'page_on_front', $home );
		}
		if ( $journal ) {
			update_option( 'page_for_posts', $journal );
		}
		return __( 'Home, About, Contact, FAQ, Shipping & returns, Wishlist, Compare and Track order pages created.', 'aurelia-commerce' );
	}

	/**
	 * Main navigation with a mega menu.
	 *
	 * @return string
	 */
	private function import_menu() {
		$link = static function ( $label, $url ) {
			return '<!-- wp:navigation-link ' . wp_json_encode(
				array(
					'label' => $label,
					'url'   => $url,
					'kind'  => 'custom',
				),
				JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
			) . ' /-->';
		};
		$sub  = static function ( $label, $url, array $children ) {
			return '<!-- wp:navigation-submenu ' . wp_json_encode(
				array(
					'label' => $label,
					'url'   => $url,
					'kind'  => 'custom',
				),
				JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
			) . " -->\n" . implode( "\n", $children ) . "\n<!-- /wp:navigation-submenu -->";
		};
		$cats = array();
		foreach ( self::data()['categories'] as $slug => $cat ) {
			$term = get_term_by( 'slug', $slug, 'product_cat' );
			if ( $term ) {
				$cats[ $slug ] = array( $cat[0], (string) get_term_link( $term ) );
			}
		}
		$shop    = wc_get_page_permalink( 'shop' );
		$content = implode(
			"\n",
			array(
				$sub(
					__( 'Shop', 'aurelia-commerce' ),
					$shop,
					array(
						$sub( __( 'Collections', 'aurelia-commerce' ), $shop, array_map( static fn( $c ) => $link( $c[0], $c[1] ), $cats ) ),
						$sub(
							__( 'Discover', 'aurelia-commerce' ),
							$shop,
							array(
								$link( __( 'New arrivals', 'aurelia-commerce' ), add_query_arg( 'orderby', 'date', $shop ) ),
								$link( __( 'Best sellers', 'aurelia-commerce' ), add_query_arg( 'orderby', 'popularity', $shop ) ),
								$link( __( 'Top rated', 'aurelia-commerce' ), add_query_arg( 'orderby', 'rating', $shop ) ),
								$link( __( 'Wishlist', 'aurelia-commerce' ), home_url( '/wishlist/' ) ),
								$link( __( 'Compare', 'aurelia-commerce' ), home_url( '/compare/' ) ),
							)
						),
						$sub(
							__( 'Help', 'aurelia-commerce' ),
							home_url( '/faq/' ),
							array(
								$link( __( 'Track your order', 'aurelia-commerce' ), home_url( '/track-order/' ) ),
								$link( __( 'Shipping & returns', 'aurelia-commerce' ), home_url( '/shipping-returns/' ) ),
								$link( __( 'FAQ', 'aurelia-commerce' ), home_url( '/faq/' ) ),
								$link( __( 'Contact', 'aurelia-commerce' ), home_url( '/contact/' ) ),
							)
						),
					)
				),
				isset( $cats['jewellery'] ) ? $link( $cats['jewellery'][0], $cats['jewellery'][1] ) : '',
				isset( $cats['fashion'] ) ? $link( $cats['fashion'][0], $cats['fashion'][1] ) : '',
				isset( $cats['electronics'] ) ? $link( $cats['electronics'][0], $cats['electronics'][1] ) : '',
				isset( $cats['beauty'] ) ? $link( $cats['beauty'][0], $cats['beauty'][1] ) : '',
				$link( __( 'About', 'aurelia-commerce' ), home_url( '/about/' ) ),
			)
		);
		$id      = wp_insert_post(
			array(
				'post_type'    => 'wp_navigation',
				'post_status'  => 'publish',
				'post_title'   => __( 'Main menu', 'aurelia-commerce' ),
				'post_content' => $content,
			)
		);
		if ( $id && ! is_wp_error( $id ) ) {
			$this->track( 'posts', $id );
		}
		return __( 'Main menu with a mega menu created.', 'aurelia-commerce' );
	}

	/**
	 * Cross-sells, category images, caches.
	 *
	 * @return string
	 */
	private function finish() {
		foreach ( self::data()['products'] as $slug => $p ) {
			$post = get_page_by_path( $slug, OBJECT, 'product' );
			if ( ! $post || empty( $p['cross'] ) ) {
				continue;
			}
			$ids = array();
			foreach ( $p['cross'] as $cross ) {
				$c = get_page_by_path( $cross, OBJECT, 'product' );
				if ( $c ) {
					$ids[] = $c->ID;
				}
			}
			$product = wc_get_product( $post->ID );
			$product->set_cross_sell_ids( $ids );
			$product->set_upsell_ids( array_slice( $ids, 0, 2 ) );
			$product->save();
		}
		foreach ( self::data()['categories'] as $slug => $cat ) {
			$term = get_term_by( 'slug', $slug, 'product_cat' );
			if ( $term && ! get_term_meta( $term->term_id, 'thumbnail_id', true ) ) {
				$image = $this->image( $cat[2], $cat[0] );
				if ( $image ) {
					update_term_meta( $term->term_id, 'thumbnail_id', $image );
				}
			}
		}
		update_option( 'aurelia_commerce_demo_imported', time() );
		update_option( 'aurelia_commerce_flush_rewrites', 1 );
		Concierge::flush_context();
		Seo::flush_cache();
		wc_delete_product_transients();
		return __( 'Finishing touches: cross-sells, upsells and category images. Your store is ready!', 'aurelia-commerce' );
	}

	/**
	 * Remove everything the importer created.
	 *
	 * @return string
	 */
	public function remove() {
		$ids = (array) get_option( self::TRACK, array() );
		foreach ( array_reverse( (array) ( $ids['posts'] ?? array() ) ) as $id ) {
			$post = get_post( (int) $id );
			if ( ! $post ) {
				continue;
			}
			if ( 'product' === $post->post_type ) {
				$product = wc_get_product( $post->ID );
				if ( $product && $product->is_type( 'variable' ) ) {
					foreach ( $product->get_children() as $child ) {
						wp_delete_post( $child, true );
					}
				}
			}
			wp_delete_post( $post->ID, true );
		}
		foreach ( (array) ( $ids['attachments'] ?? array() ) as $id ) {
			wp_delete_attachment( (int) $id, true );
		}
		foreach ( (array) ( $ids['terms'] ?? array() ) as $term ) {
			wp_delete_term( (int) $term[0], (string) $term[1] );
		}
		foreach ( (array) ( $ids['attributes'] ?? array() ) as $id ) {
			wc_delete_attribute( (int) $id );
		}
		delete_option( self::TRACK );
		delete_option( 'aurelia_commerce_demo_imported' );
		wc_delete_product_transients();
		return __( 'Demo content removed.', 'aurelia-commerce' );
	}
}
