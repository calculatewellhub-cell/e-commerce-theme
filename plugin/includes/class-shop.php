<?php
/**
 * Shop experience: live search, wishlist, compare, quick view, card actions,
 * grid/list toggle, load-more/infinite scroll, free-shipping bar, delivery
 * estimate, sale countdown, sticky add-to-cart, size guide, frequently bought
 * together and recently viewed.
 *
 * @package Aurelia_Commerce
 */

namespace Aurelia\Commerce;

defined( 'ABSPATH' ) || exit;

/**
 * Shop UX.
 */
class Shop {

	const PAGES_OPTION  = 'aurelia_commerce_pages';
	const WISHLIST_META = '_aurelia_wishlist';

	/**
	 * Hooks.
	 */
	public function init() {
		add_action( 'rest_api_init', array( $this, 'routes' ) );
		add_filter( 'aurelia_commerce_frontend_config', array( $this, 'config' ) );
		add_shortcode( 'aurelia_wishlist', array( $this, 'wishlist_shortcode' ) );
		add_shortcode( 'aurelia_compare', array( $this, 'compare_shortcode' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'product_assets' ), 20 );

		add_filter( 'render_block_woocommerce/product-image', array( $this, 'card_actions' ), 10, 3 );
		add_filter( 'render_block_woocommerce/customer-account', array( $this, 'header_wishlist' ) );
		add_filter( 'render_block_woocommerce/product-details', array( $this, 'recently_viewed_slot' ) );

		add_action( 'woocommerce_before_add_to_cart_form', array( $this, 'before_form' ), 5 );
		add_action( 'woocommerce_after_add_to_cart_form', array( $this, 'size_guide_link' ), 5 );
		add_action( 'woocommerce_after_add_to_cart_form', array( $this, 'bought_together' ), 20 );
		add_action( 'wp_footer', array( $this, 'sticky_bar' ) );

		add_action( 'add_meta_boxes_product', array( $this, 'size_guide_box' ) );
		add_action( 'woocommerce_process_product_meta', array( $this, 'save_size_guide' ) );

		add_filter( 'woocommerce_package_rates', array( $this, 'prefer_free_shipping' ), 100 );
	}

	/**
	 * Once an order qualifies for free shipping, drop the paid rates so shoppers are
	 * not charged by default. Local pickup stays available.
	 *
	 * @param array $rates Rates keyed by rate ID (WC_Shipping_Rate values).
	 * @return array
	 */
	public function prefer_free_shipping( $rates ) {
		if ( ! Settings::on( 'free_shipping_only' ) ) {
			return $rates;
		}
		$free = array_filter( $rates, static fn( $rate ) => 'free_shipping' === $rate->get_method_id() );
		if ( ! $free ) {
			return $rates;
		}
		$keep = array_filter( $rates, static fn( $rate ) => in_array( $rate->get_method_id(), array( 'local_pickup', 'pickup_location' ), true ) );
		return $free + $keep;
	}

	/**
	 * Wishlist, compare and tracking page IDs.
	 *
	 * @param string $key wishlist|compare|track.
	 * @return string URL.
	 */
	public static function page_url( $key ) {
		$pages = (array) get_option( self::PAGES_OPTION, array() );
		return ! empty( $pages[ $key ] ) && 'publish' === get_post_status( $pages[ $key ] ) ? (string) get_permalink( $pages[ $key ] ) : '';
	}

	/**
	 * Front-end config.
	 *
	 * @param array $config Config.
	 * @return array
	 */
	public function config( $config ) {
		$config['shop']  = array(
			'liveSearch'   => Settings::on( 'live_search' ),
			'wishlist'     => Settings::on( 'wishlist' ),
			'compare'      => Settings::on( 'compare' ),
			'quickView'    => Settings::on( 'quick_view' ),
			'listToggle'   => Settings::on( 'list_toggle' ),
			'pagination'   => Settings::get( 'pagination' ),
			'freeShipping' => Settings::on( 'free_shipping_bar' ) ? (float) Settings::get( 'free_shipping_min', 0 ) : 0,
			'recent'       => Settings::on( 'recently_viewed' ),
			'wishlistUrl'  => self::page_url( 'wishlist' ),
			'compareUrl'   => self::page_url( 'compare' ),
			'cartUrl'      => wc_get_cart_url(),
			'product'      => is_singular( 'product' ) ? get_queried_object_id() : 0,
			'priceFormat'  => array(
				'symbol'   => html_entity_decode( get_woocommerce_currency_symbol(), ENT_QUOTES, 'UTF-8' ),
				'position' => get_option( 'woocommerce_currency_pos', 'left' ),
				'decimals' => wc_get_price_decimals(),
				'thousand' => wc_get_price_thousand_separator(),
				'decimal'  => wc_get_price_decimal_separator(),
			),
		);
		$config['i18n'] += array(
			'wishlistAdd'    => __( 'Add to wishlist', 'aurelia-commerce' ),
			'wishlistRemove' => __( 'Remove from wishlist', 'aurelia-commerce' ),
			'wishlistEmpty'  => __( 'Your wishlist is empty. Tap the heart on any product to save it here.', 'aurelia-commerce' ),
			'compareAdd'     => __( 'Add to compare', 'aurelia-commerce' ),
			'compareRemove'  => __( 'Remove from compare', 'aurelia-commerce' ),
			'compareEmpty'   => __( 'Add up to 4 products to compare them side by side.', 'aurelia-commerce' ),
			'compareNow'     => __( 'Compare now', 'aurelia-commerce' ),
			'compareClear'   => __( 'Clear', 'aurelia-commerce' ),
			/* translators: %d: number of products. */
			'compareCount'   => __( 'Compare (%d)', 'aurelia-commerce' ),
			'compareMax'     => __( 'You can compare up to 4 products.', 'aurelia-commerce' ),
			'quickView'      => __( 'Quick view', 'aurelia-commerce' ),
			'close'          => __( 'Close', 'aurelia-commerce' ),
			'viewDetails'    => __( 'View full details', 'aurelia-commerce' ),
			'addToCart'      => __( 'Add to cart', 'aurelia-commerce' ),
			'added'          => __( 'Added to cart ✓', 'aurelia-commerce' ),
			'chooseOptions'  => __( 'Choose options', 'aurelia-commerce' ),
			'price'          => __( 'Price', 'aurelia-commerce' ),
			'rating'         => __( 'Rating', 'aurelia-commerce' ),
			'availability'   => __( 'Availability', 'aurelia-commerce' ),
			'inStock'        => __( 'In stock', 'aurelia-commerce' ),
			'outOfStock'     => __( 'Out of stock', 'aurelia-commerce' ),
			'searchNone'     => __( 'No products found', 'aurelia-commerce' ),
			'searchAll'      => __( 'See all results', 'aurelia-commerce' ),
			'searchLabel'    => __( 'Product suggestions', 'aurelia-commerce' ),
			'grid'           => __( 'Grid view', 'aurelia-commerce' ),
			'list'           => __( 'List view', 'aurelia-commerce' ),
			'loadMore'       => __( 'Load more products', 'aurelia-commerce' ),
			'loading'        => __( 'Loading…', 'aurelia-commerce' ),
			/* translators: %s: amount left for free shipping. */
			'freeShipLeft'   => __( 'Add %s more for FREE shipping', 'aurelia-commerce' ),
			'freeShipDone'   => __( '🎉 You have unlocked FREE shipping!', 'aurelia-commerce' ),
			'recent'         => __( 'Recently viewed', 'aurelia-commerce' ),
			'remove'         => __( 'Remove', 'aurelia-commerce' ),
			'decrease'       => __( 'Decrease quantity', 'aurelia-commerce' ),
			'increase'       => __( 'Increase quantity', 'aurelia-commerce' ),
		);
		return $config;
	}

	/**
	 * Product page script.
	 */
	public function product_assets() {
		if ( ! is_singular( 'product' ) ) {
			return;
		}
		Helpers::register_script( 'aurelia-product', 'product.js', array( 'aurelia-commerce' ) );
		wp_enqueue_script( 'aurelia-product' );
		wp_localize_script(
			'aurelia-product',
			'aureliaProduct',
			array(
				'qtyButtons' => Settings::on( 'qty_buttons' ),
				'sticky'     => Settings::on( 'sticky_cart' ),
				'swatches'   => Settings::on( 'swatches' ) ? Swatches::data_for( get_queried_object_id() ) : null,
				'i18n'       => array(
					'selectOption' => __( 'Select an option', 'aurelia-commerce' ),
					'unavailable'  => __( 'Unavailable', 'aurelia-commerce' ),
					'ends'         => __( 'Sale ends in', 'aurelia-commerce' ),
					'd'            => _x( 'd', 'days abbreviation', 'aurelia-commerce' ),
					'h'            => _x( 'h', 'hours abbreviation', 'aurelia-commerce' ),
					'm'            => _x( 'm', 'minutes abbreviation', 'aurelia-commerce' ),
					's'            => _x( 's', 'seconds abbreviation', 'aurelia-commerce' ),
					'addAll'       => __( 'Add selected to cart', 'aurelia-commerce' ),
					'adding'       => __( 'Adding…', 'aurelia-commerce' ),
					'addedAll'     => __( 'Added to cart ✓', 'aurelia-commerce' ),
				),
			)
		);
	}

	/**
	 * REST routes.
	 */
	public function routes() {
		$public = array(
			'methods'             => 'GET',
			'permission_callback' => '__return_true',
		);
		register_rest_route( 'aurelia/v1', '/search', $public + array( 'callback' => array( $this, 'search' ) ) );
		register_rest_route( 'aurelia/v1', '/products', $public + array( 'callback' => array( $this, 'products' ) ) );
		register_rest_route(
			'aurelia/v1',
			'/quick-view/(?P<id>\d+)',
			$public + array( 'callback' => array( $this, 'quick_view' ) )
		);
		register_rest_route(
			'aurelia/v1',
			'/wishlist',
			array(
				array(
					'methods'             => 'GET',
					'permission_callback' => 'is_user_logged_in',
					'callback'            => static fn() => array( 'ids' => array_values( array_filter( array_map( 'absint', (array) get_user_meta( get_current_user_id(), self::WISHLIST_META, true ) ) ) ) ),
				),
				array(
					'methods'             => 'POST',
					'permission_callback' => 'is_user_logged_in',
					'callback'            => static function ( \WP_REST_Request $r ) {
						$ids = array_slice( array_values( array_unique( array_filter( array_map( 'absint', (array) $r->get_param( 'ids' ) ) ) ) ), 0, 100 );
						update_user_meta( get_current_user_id(), self::WISHLIST_META, $ids );
						return array( 'ids' => $ids );
					},
				),
			)
		);
	}

	/**
	 * Live search suggestions.
	 *
	 * @param \WP_REST_Request $request Request.
	 * @return array
	 */
	public function search( \WP_REST_Request $request ) {
		$q = sanitize_text_field( (string) $request->get_param( 'q' ) );
		if ( mb_strlen( $q ) < 2 ) {
			return array( 'products' => array() );
		}
		$ids    = wc_get_products(
			array(
				'status'     => 'publish',
				'limit'      => 6,
				's'          => $q,
				'return'     => 'ids',
				'visibility' => 'search',
			)
		);
		$by_sku = wc_get_product_id_by_sku( $q );
		if ( $by_sku ) {
			array_unshift( $ids, $by_sku );
		}
		$out = array();
		foreach ( array_slice( array_unique( $ids ), 0, 6 ) as $id ) {
			$product = wc_get_product( $id );
			if ( $product && 'publish' === $product->get_status() ) {
				$out[] = $this->card( $product );
			}
		}
		$cats = get_terms(
			array(
				'taxonomy'   => 'product_cat',
				'name__like' => $q,
				'number'     => 3,
				'hide_empty' => true,
			)
		);
		return array(
			'products'   => $out,
			'categories' => is_wp_error( $cats ) ? array() : array_map(
				static fn( $c ) => array(
					'name' => $c->name,
					'url'  => get_term_link( $c ),
				),
				$cats
			),
			'all'        => add_query_arg(
				array(
					's'         => $q,
					'post_type' => 'product',
				),
				home_url( '/' )
			),
		);
	}

	/**
	 * Card data for a list of IDs (wishlist, compare, recently viewed).
	 *
	 * @param \WP_REST_Request $request Request.
	 * @return array
	 */
	public function products( \WP_REST_Request $request ) {
		$ids = array_slice( array_filter( array_map( 'absint', explode( ',', (string) $request->get_param( 'ids' ) ) ) ), 0, 24 );
		$out = array();
		foreach ( $ids as $id ) {
			$product = wc_get_product( $id );
			if ( $product && 'publish' === $product->get_status() && $product->is_visible() ) {
				$out[] = $this->card( $product, (bool) $request->get_param( 'details' ) );
			}
		}
		return array( 'products' => $out );
	}

	/**
	 * Serialise a product for the front end.
	 *
	 * @param \WC_Product $product Product.
	 * @param bool        $details Include compare details.
	 * @return array
	 */
	private function card( $product, $details = false ) {
		$data = array(
			'id'        => $product->get_id(),
			'name'      => $product->get_name(),
			'url'       => $product->get_permalink(),
			'image'     => Helpers::product_image( $product ),
			'priceHtml' => wp_kses_post( $product->get_price_html() ),
			'simple'    => $product->is_type( 'simple' ) && $product->is_purchasable() && $product->is_in_stock(),
			'inStock'   => $product->is_in_stock(),
		);
		if ( $details ) {
			$data['rating']  = (float) $product->get_average_rating();
			$data['reviews'] = (int) $product->get_review_count();
			$data['sku']     = $product->get_sku();
			$data['excerpt'] = wp_trim_words( wp_strip_all_tags( $product->get_short_description() ), 24 );
			$attrs           = array();
			foreach ( $product->get_attributes() as $attribute ) {
				$values = $attribute->is_taxonomy() ? wc_get_product_terms( $product->get_id(), $attribute->get_name(), array( 'fields' => 'names' ) ) : $attribute->get_options();
				$attrs[ wc_attribute_label( $attribute->get_name() ) ] = implode( ', ', (array) $values );
			}
			if ( $product->get_weight() ) {
				$attrs[ __( 'Weight', 'aurelia-commerce' ) ] = wc_format_weight( $product->get_weight() );
			}
			$data['attributes'] = $attrs;
		}
		return $data;
	}

	/**
	 * Quick-view fragment.
	 *
	 * @param \WP_REST_Request $request Request.
	 * @return array|\WP_Error
	 */
	public function quick_view( \WP_REST_Request $request ) {
		$product = wc_get_product( absint( $request['id'] ) );
		if ( ! $product || 'publish' !== $product->get_status() || ! $product->is_visible() ) {
			return new \WP_Error( 'aurelia_not_found', __( 'Product not found.', 'aurelia-commerce' ), array( 'status' => 404 ) );
		}
		$images  = array_slice( array_filter( array_merge( array( $product->get_image_id() ), $product->get_gallery_image_ids() ) ), 0, 5 );
		$gallery = array();
		foreach ( $images as $image_id ) {
			$gallery[] = array(
				'src' => wp_get_attachment_image_url( $image_id, 'woocommerce_single' ),
				'alt' => (string) get_post_meta( $image_id, '_wp_attachment_image_alt', true ),
			);
		}
		if ( ! $gallery ) {
			$gallery[] = array(
				'src' => wc_placeholder_img_src( 'woocommerce_single' ),
				'alt' => '',
			);
		}
		return array(
			'id'          => $product->get_id(),
			'name'        => $product->get_name(),
			'url'         => $product->get_permalink(),
			'priceHtml'   => wp_kses_post( $product->get_price_html() ),
			'description' => wp_kses_post( wpautop( $product->get_short_description() ) ),
			'gallery'     => $gallery,
			'rating'      => (float) $product->get_average_rating(),
			'simple'      => $product->is_type( 'simple' ) && $product->is_purchasable() && $product->is_in_stock(),
			'inStock'     => $product->is_in_stock(),
			'stockHtml'   => wp_kses_post( wc_get_stock_html( $product ) ),
		);
	}

	/**
	 * Wishlist / compare / quick-view buttons on product cards.
	 *
	 * @param string    $content  Block HTML.
	 * @param array     $block    Parsed block.
	 * @param \WP_Block $instance Block instance.
	 * @return string
	 */
	public function card_actions( $content, $block, $instance ) {
		$id = isset( $instance->context['postId'] ) ? absint( $instance->context['postId'] ) : 0;
		if ( ! $id || ! ( Settings::on( 'wishlist' ) || Settings::on( 'compare' ) || Settings::on( 'quick_view' ) ) ) {
			return $content;
		}
		$name    = get_the_title( $id );
		$buttons = '';
		if ( Settings::on( 'wishlist' ) ) {
			/* translators: %s: product name. */
			$buttons .= sprintf( '<button type="button" class="au-card-btn au-wishlist-btn" data-product="%1$d" aria-pressed="false" aria-label="%2$s"><svg viewBox="0 0 24 24" width="18" height="18" aria-hidden="true"><path d="M12 21s-7.5-4.6-9.5-9.3C1.2 8.4 3.2 5 6.6 5c2 0 3.4 1.1 4.2 2.3h2.4C14 6.1 15.4 5 17.4 5c3.4 0 5.4 3.4 4.1 6.7C19.5 16.4 12 21 12 21z" fill="none" stroke="currentColor" stroke-width="1.6"/></svg></button>', $id, esc_attr( sprintf( __( 'Add %s to wishlist', 'aurelia-commerce' ), $name ) ) );
		}
		if ( Settings::on( 'quick_view' ) ) {
			/* translators: %s: product name. */
			$buttons .= sprintf( '<button type="button" class="au-card-btn au-quickview-btn" data-product="%1$d" aria-haspopup="dialog" aria-label="%2$s"><svg viewBox="0 0 24 24" width="18" height="18" aria-hidden="true"><path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7S2 12 2 12z" fill="none" stroke="currentColor" stroke-width="1.6"/><circle cx="12" cy="12" r="3" fill="none" stroke="currentColor" stroke-width="1.6"/></svg></button>', $id, esc_attr( sprintf( __( 'Quick view %s', 'aurelia-commerce' ), $name ) ) );
		}
		if ( Settings::on( 'compare' ) ) {
			/* translators: %s: product name. */
			$buttons .= sprintf( '<button type="button" class="au-card-btn au-compare-btn" data-product="%1$d" aria-pressed="false" aria-label="%2$s"><svg viewBox="0 0 24 24" width="18" height="18" aria-hidden="true"><path d="M7 4v16M17 4v16M3 8l4-4 4 4M13 16l4 4 4-4" fill="none" stroke="currentColor" stroke-width="1.6"/></svg></button>', $id, esc_attr( sprintf( __( 'Compare %s', 'aurelia-commerce' ), $name ) ) );
		}
		$actions = '<div class="au-card-actions">' . $buttons . '</div>';
		// Place inside the image wrapper so it overlays the photo.
		$pos = strrpos( $content, '</div>' );
		return false === $pos ? $content . $actions : substr_replace( $content, $actions . '</div>', $pos, 6 );
	}

	/**
	 * Wishlist icon next to the account icon in the header.
	 *
	 * @param string $content Block HTML.
	 * @return string
	 */
	public function header_wishlist( $content ) {
		$url = self::page_url( 'wishlist' );
		if ( ! Settings::on( 'wishlist' ) || '' === $url ) {
			return $content;
		}
		$link = sprintf(
			'<a class="au-header-wishlist" href="%1$s" aria-label="%2$s"><svg viewBox="0 0 24 24" width="22" height="22" aria-hidden="true"><path d="M12 21s-7.5-4.6-9.5-9.3C1.2 8.4 3.2 5 6.6 5c2 0 3.4 1.1 4.2 2.3h2.4C14 6.1 15.4 5 17.4 5c3.4 0 5.4 3.4 4.1 6.7C19.5 16.4 12 21 12 21z" fill="none" stroke="currentColor" stroke-width="1.5"/></svg><span class="au-wishlist-count" hidden>0</span></a>',
			esc_url( $url ),
			esc_attr__( 'Wishlist', 'aurelia-commerce' )
		);
		return $link . $content;
	}

	/**
	 * Recently viewed products slot after the product details tabs.
	 *
	 * @param string $content Block HTML.
	 * @return string
	 */
	public function recently_viewed_slot( $content ) {
		if ( ! Settings::on( 'recently_viewed' ) || ! is_singular( 'product' ) ) {
			return $content;
		}
		return $content . '<section class="au-recent" hidden aria-labelledby="au-recent-title"><h2 id="au-recent-title">' . esc_html__( 'Recently viewed', 'aurelia-commerce' ) . '</h2><div class="au-mini-grid"></div></section>';
	}

	/**
	 * Delivery estimate, stock urgency and sale countdown above the buy form.
	 */
	public function before_form() {
		global $product;
		if ( ! $product instanceof \WC_Product ) {
			return;
		}
		echo '<div class="au-buy-meta">';

		if ( Settings::on( 'sale_countdown' ) && $product->is_on_sale() ) {
			$to = $product->get_date_on_sale_to();
			if ( $to && $to->getTimestamp() > time() && $to->getTimestamp() - time() < 30 * DAY_IN_SECONDS ) {
				printf( '<p class="au-sale-timer" data-end="%1$s"><span>%2$s</span> <strong></strong></p>', esc_attr( $to->date( 'c' ) ), esc_html__( 'Sale ends in', 'aurelia-commerce' ) );
			}
		}

		$qty = $product->managing_stock() ? (int) $product->get_stock_quantity() : null;
		if ( null !== $qty && $qty > 0 && $qty <= 5 ) {
			/* translators: %d: items left. */
			printf( '<p class="au-urgency">%s</p>', esc_html( sprintf( _n( 'Hurry — only %d left in stock', 'Hurry — only %d left in stock', $qty, 'aurelia-commerce' ), $qty ) ) );
		}

		if ( Settings::on( 'delivery_info' ) && $product->is_in_stock() && $product->needs_shipping() ) {
			echo wp_kses_post( $this->delivery_html() );
		}
		echo '</div>';
	}

	/**
	 * "Order within X for dispatch today · Delivery by D–D" text.
	 *
	 * @return string
	 */
	private function delivery_html() {
		$tz     = wp_timezone();
		$now    = new \DateTimeImmutable( 'now', $tz );
		$cutoff = $now->setTime( (int) Settings::get( 'dispatch_cutoff', 15 ), 0 );
		$ship   = $now;
		if ( $now >= $cutoff || '7' === $now->format( 'N' ) ) {
			$ship = $now->modify( '+1 day' );
			if ( '7' === $ship->format( 'N' ) ) {
				$ship = $ship->modify( '+1 day' );
			}
		}
		$from = $ship->modify( '+' . (int) Settings::get( 'delivery_min', 2 ) . ' days' );
		$to   = $ship->modify( '+' . max( (int) Settings::get( 'delivery_min', 2 ), (int) Settings::get( 'delivery_max', 6 ) ) . ' days' );
		$out  = '<p class="au-delivery"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true"><path d="M3 7h11v9H3zM14 10h4l3 3v3h-7z"/><circle cx="7" cy="17.5" r="1.8"/><circle cx="17" cy="17.5" r="1.8"/></svg><span>';
		if ( $ship->format( 'Y-m-d' ) === $now->format( 'Y-m-d' ) ) {
			$left = $cutoff->getTimestamp() - $now->getTimestamp();
			/* translators: 1: hours, 2: minutes. */
			$out .= sprintf( esc_html__( 'Order within %1$dh %2$dm for dispatch today.', 'aurelia-commerce' ), intdiv( $left, 3600 ), intdiv( $left % 3600, 60 ) ) . ' ';
		}
		/* translators: 1: earliest date, 2: latest date. */
		$out .= sprintf( esc_html__( 'Estimated delivery %1$s – %2$s', 'aurelia-commerce' ), '<strong>' . esc_html( wp_date( 'D, j M', $from->getTimestamp() ) ) . '</strong>', '<strong>' . esc_html( wp_date( 'D, j M', $to->getTimestamp() ) ) . '</strong>' );
		return $out . '</span></p>';
	}

	/**
	 * Size guide link + dialog.
	 */
	public function size_guide_link() {
		global $product;
		if ( ! $product instanceof \WC_Product ) {
			return;
		}
		$guide = (string) get_post_meta( $product->get_id(), '_aurelia_size_guide', true );
		if ( '' === trim( wp_strip_all_tags( $guide ) ) ) {
			$has_size = false;
			foreach ( array_keys( $product->get_attributes() ) as $name ) {
				if ( str_contains( strtolower( $name ), 'size' ) ) {
					$has_size = true;
				}
			}
			$guide = $has_size ? (string) Settings::get( 'size_guide', '' ) : '';
		}
		if ( '' === trim( wp_strip_all_tags( $guide ) ) ) {
			return;
		}
		printf(
			'<button type="button" class="au-size-guide-btn" aria-haspopup="dialog" aria-controls="au-size-guide"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true"><path d="M3 8h18v8H3zM7 8v3M11 8v4M15 8v3M19 8v4"/></svg>%1$s</button><dialog id="au-size-guide" class="au-dialog" aria-labelledby="au-size-guide-title"><div class="au-dialog__head"><h2 id="au-size-guide-title">%1$s</h2><button type="button" class="au-dialog__close" aria-label="%2$s">&times;</button></div><div class="au-dialog__body">%3$s</div></dialog>',
			esc_html__( 'Size guide', 'aurelia-commerce' ),
			esc_attr__( 'Close', 'aurelia-commerce' ),
			wp_kses_post( $guide )
		);
	}

	/**
	 * Frequently bought together (cross-sells, falling back to same-category best sellers).
	 */
	public function bought_together() {
		global $product;
		if ( ! Settings::on( 'bought_together' ) || ! $product instanceof \WC_Product || ! $product->is_type( 'simple' ) || ! $product->is_purchasable() ) {
			return;
		}
		$ids = array_slice( $product->get_cross_sell_ids(), 0, 2 );
		if ( ! $ids ) {
			return;
		}
		$items = array( $product );
		foreach ( $ids as $id ) {
			$p = wc_get_product( $id );
			if ( $p && $p->is_type( 'simple' ) && $p->is_purchasable() && $p->is_in_stock() && 'publish' === $p->get_status() ) {
				$items[] = $p;
			}
		}
		if ( count( $items ) < 2 ) {
			return;
		}
		?>
		<section class="au-fbt" aria-labelledby="au-fbt-title">
			<h3 id="au-fbt-title"><?php esc_html_e( 'Frequently bought together', 'aurelia-commerce' ); ?></h3>
			<ul class="au-fbt__list">
				<?php foreach ( $items as $i => $item ) : ?>
					<li>
						<label>
							<input type="checkbox" value="<?php echo esc_attr( (string) $item->get_id() ); ?>" data-price="<?php echo esc_attr( (string) wc_get_price_to_display( $item ) ); ?>" checked>
							<img src="<?php echo esc_url( Helpers::product_image( $item, 'thumbnail' ) ); ?>" alt="" width="56" height="56" loading="lazy">
							<span class="au-fbt__name"><?php echo 0 === $i ? esc_html__( 'This item:', 'aurelia-commerce' ) . ' ' : ''; ?><?php echo esc_html( $item->get_name() ); ?></span>
							<span class="au-fbt__price"><?php echo wp_kses_post( $item->get_price_html() ); ?></span>
						</label>
					</li>
				<?php endforeach; ?>
			</ul>
			<p class="au-fbt__total"><?php esc_html_e( 'Total:', 'aurelia-commerce' ); ?> <strong class="au-fbt__sum"></strong></p>
			<button type="button" class="au-btn au-btn--primary au-fbt__add"><?php esc_html_e( 'Add selected to cart', 'aurelia-commerce' ); ?></button>
			<p class="au-fbt__msg" role="status" aria-live="polite"></p>
		</section>
		<?php
	}

	/**
	 * Sticky add-to-cart bar markup (shown by product.js when the form scrolls away).
	 */
	public function sticky_bar() {
		if ( ! Settings::on( 'sticky_cart' ) || ! is_singular( 'product' ) ) {
			return;
		}
		$product = wc_get_product( get_queried_object_id() );
		if ( ! $product || ! $product->is_purchasable() || ! $product->is_in_stock() ) {
			return;
		}
		printf(
			'<div class="au-sticky-cart" hidden><div class="au-sticky-cart__inner"><img src="%1$s" alt="" width="48" height="48"><div class="au-sticky-cart__text"><strong>%2$s</strong><span>%3$s</span></div><button type="button" class="au-btn au-btn--primary au-sticky-cart__btn">%4$s</button></div></div>',
			esc_url( Helpers::product_image( $product, 'thumbnail' ) ),
			esc_html( $product->get_name() ),
			wp_kses_post( $product->get_price_html() ),
			esc_html( $product->is_type( 'variable' ) ? __( 'Choose options', 'aurelia-commerce' ) : __( 'Add to cart', 'aurelia-commerce' ) )
		);
	}

	/**
	 * Wishlist page shortcode.
	 *
	 * @return string
	 */
	public function wishlist_shortcode() {
		return '<div class="au-wishlist-page" data-empty="' . esc_attr__( 'Your wishlist is empty. Tap the heart on any product to save it here.', 'aurelia-commerce' ) . '"><div class="au-mini-grid" aria-live="polite"></div></div>';
	}

	/**
	 * Compare page shortcode.
	 *
	 * @return string
	 */
	public function compare_shortcode() {
		return '<div class="au-compare-page" aria-live="polite"></div>';
	}

	/**
	 * Per-product size guide.
	 */
	public function size_guide_box() {
		add_meta_box(
			'aurelia-size-guide',
			__( 'Size guide (optional)', 'aurelia-commerce' ),
			function ( $post ) {
				wp_nonce_field( 'aurelia_size_guide', 'aurelia_size_guide_nonce' );
				echo '<p>' . esc_html__( 'Overrides the global size guide for this product.', 'aurelia-commerce' ) . '</p>';
				wp_editor(
					(string) get_post_meta( $post->ID, '_aurelia_size_guide', true ),
					'aurelia_size_guide',
					array(
						'textarea_rows' => 6,
						'media_buttons' => true,
					)
				);
			},
			'product',
			'normal',
			'low'
		);
	}

	/**
	 * Save per-product size guide.
	 *
	 * @param int $post_id Product ID.
	 */
	public function save_size_guide( $post_id ) {
		if ( ! isset( $_POST['aurelia_size_guide_nonce'] ) || ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['aurelia_size_guide_nonce'] ) ), 'aurelia_size_guide' ) || ! current_user_can( 'edit_product', $post_id ) ) {
			return;
		}
		update_post_meta( $post_id, '_aurelia_size_guide', wp_kses_post( wp_unslash( $_POST['aurelia_size_guide'] ?? '' ) ) );
	}
}
