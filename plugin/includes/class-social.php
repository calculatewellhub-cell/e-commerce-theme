<?php
/**
 * Social auto-posting with AI captions, WP-Cron scheduling, webhook or Meta
 * Graph API publishing, and tracked short links (/go/{post}/{platform}/).
 *
 * @package Aurelia_Commerce
 */

namespace Aurelia\Commerce;

defined( 'ABSPATH' ) || exit;

/**
 * Social.
 */
class Social {

	const CPT       = 'aurelia_social';
	const PLATFORMS = array( 'instagram', 'facebook', 'pinterest', 'x', 'linkedin', 'whatsapp_channel', 'youtube' );

	/**
	 * Hooks.
	 */
	public function init() {
		add_action( 'init', array( $this, 'register' ) );
		add_filter( 'query_vars', array( $this, 'query_vars' ) );
		add_action( 'template_redirect', array( $this, 'redirect' ), 1 );
		add_action( 'aurelia_commerce_social_tick', array( $this, 'tick' ) );
		add_action( 'rest_api_init', array( $this, 'routes' ) );
		add_action( 'admin_menu', array( $this, 'menu' ), 20 );
	}

	/**
	 * Post type and rewrite rule.
	 */
	public function register() {
		register_post_type(
			self::CPT,
			array(
				'label'           => __( 'Social posts', 'aurelia-commerce' ),
				'public'          => false,
				'show_ui'         => false,
				'show_in_rest'    => false,
				'supports'        => array( 'title' ),
				'capability_type' => 'post',
				'map_meta_cap'    => true,
			)
		);
		add_rewrite_rule( '^go/([0-9]+)/([a-z_]+)/?$', 'index.php?aurelia_go=$matches[1]&aurelia_platform=$matches[2]', 'top' );
	}

	/**
	 * Query vars.
	 *
	 * @param array $vars Vars.
	 * @return array
	 */
	public function query_vars( $vars ) {
		$vars[] = 'aurelia_go';
		$vars[] = 'aurelia_platform';
		return $vars;
	}

	/**
	 * Tracked short link for a post and platform.
	 *
	 * @param int    $post_id  Social post ID.
	 * @param string $platform Platform.
	 * @return string
	 */
	public static function tracked_link( $post_id, $platform ) {
		return home_url( '/go/' . absint( $post_id ) . '/' . sanitize_key( $platform ) . '/' );
	}

	/**
	 * Redirect /go/ links with UTM parameters and count the click.
	 */
	public function redirect() {
		$id = absint( get_query_var( 'aurelia_go' ) );
		if ( ! $id ) {
			return;
		}
		$platform = sanitize_key( (string) get_query_var( 'aurelia_platform' ) );
		$platform = in_array( $platform, self::PLATFORMS, true ) ? $platform : 'direct';
		$post     = get_post( $id );
		$target   = home_url( '/' );
		if ( $post && self::CPT === $post->post_type ) {
			$link   = (string) get_post_meta( $id, '_link', true );
			$target = '' !== $link ? $link : $target;
			Analytics::record(
				'social_click',
				array(
					'ref_id'   => $id,
					'platform' => $platform,
					'path'     => Helpers::path_of( $target ),
				)
			);
		}
		if ( wp_parse_url( $target, PHP_URL_HOST ) === wp_parse_url( home_url(), PHP_URL_HOST ) ) {
			$target = add_query_arg(
				array(
					'utm_source'   => $platform,
					'utm_medium'   => 'social',
					'utm_campaign' => 'post-' . $id,
				),
				$target
			);
			wp_safe_redirect( $target, 302 );
		} else {
			// External links are only ever the store owner's own saved link.
			wp_redirect( esc_url_raw( $target ), 302 ); // phpcs:ignore WordPress.Security.SafeRedirect.wp_redirect_wp_redirect -- target comes from an admin-authored post.
		}
		exit;
	}

	/**
	 * Admin page.
	 */
	public function menu() {
		add_submenu_page( Admin::SLUG, __( 'Social posts', 'aurelia-commerce' ), __( 'Social posts', 'aurelia-commerce' ), Admin::CAP, 'aurelia-social', array( $this, 'render' ) );
	}

	/**
	 * Render the social scheduler shell (app in admin-social.js).
	 */
	public function render() {
		wp_enqueue_media();
		wp_enqueue_script( 'aurelia-admin-social', AURELIA_COMMERCE_URL . 'assets/js/admin-social.js', array( 'wp-api-fetch' ), AURELIA_COMMERCE_VERSION, true );
		wp_localize_script(
			'aurelia-admin-social',
			'aureliaSocial',
			array(
				'platforms' => self::PLATFORMS,
				'webhook'   => '' !== (string) Settings::get( 'social_webhook', '' ),
				'meta'      => '' !== Settings::secret( 'meta_token' ) && '' !== (string) Settings::get( 'meta_page_id', '' ),
				'ai'        => Claude_Client::has_key(),
				'settings'  => admin_url( 'admin.php?page=aurelia-settings&tab=social' ),
				'media'     => isset( $_GET['media'] ) ? esc_url_raw( wp_unslash( $_GET['media'] ) ) : '', // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- prefill from Video Studio link.
				'timezone'  => wp_timezone_string(),
				'i18n'      => array(
					'compose'     => __( 'Compose a post', 'aurelia-commerce' ),
					'product'     => __( 'Product', 'aurelia-commerce' ),
					'noProduct'   => __( '— No product —', 'aurelia-commerce' ),
					'media'       => __( 'Image or video URL', 'aurelia-commerce' ),
					'pickMedia'   => __( 'Choose from Media Library', 'aurelia-commerce' ),
					'mediaType'   => __( 'Media type', 'aurelia-commerce' ),
					'image'       => __( 'Image', 'aurelia-commerce' ),
					'video'       => __( 'Video / Reel', 'aurelia-commerce' ),
					'none'        => __( 'Text only', 'aurelia-commerce' ),
					'link'        => __( 'Link (tracked per platform)', 'aurelia-commerce' ),
					'caption'     => __( 'Caption', 'aurelia-commerce' ),
					'hashtags'    => __( 'Hashtags', 'aurelia-commerce' ),
					'aiWrite'     => __( '✨ Write with AI', 'aurelia-commerce' ),
					'aiWriting'   => __( 'Writing…', 'aurelia-commerce' ),
					'platforms'   => __( 'Platforms', 'aurelia-commerce' ),
					'schedule'    => __( 'Schedule (leave empty to save as draft)', 'aurelia-commerce' ),
					'save'        => __( 'Save', 'aurelia-commerce' ),
					'publishNow'  => __( 'Publish now', 'aurelia-commerce' ),
					'posts'       => __( 'Posts', 'aurelia-commerce' ),
					'status'      => __( 'Status', 'aurelia-commerce' ),
					'when'        => __( 'When', 'aurelia-commerce' ),
					'links'       => __( 'Tracked links', 'aurelia-commerce' ),
					'actions'     => __( 'Actions', 'aurelia-commerce' ),
					'edit'        => __( 'Edit', 'aurelia-commerce' ),
					'delete'      => __( 'Delete', 'aurelia-commerce' ),
					'confirmDel'  => __( 'Delete this post?', 'aurelia-commerce' ),
					'copy'        => __( 'Copy', 'aurelia-commerce' ),
					'copied'      => __( 'Copied', 'aurelia-commerce' ),
					'empty'       => __( 'No posts yet. Compose your first one above.', 'aurelia-commerce' ),
					'saved'       => __( 'Saved.', 'aurelia-commerce' ),
					'published'   => __( 'Published.', 'aurelia-commerce' ),
					'error'       => __( 'Error: ', 'aurelia-commerce' ),
					'noPublisher' => __( 'No publisher configured yet. Add a webhook (Make, Zapier, n8n) or your Meta Page token in Settings → Social posting.', 'aurelia-commerce' ),
					'clicks'      => __( 'clicks', 'aurelia-commerce' ),
					'new'         => __( 'New post', 'aurelia-commerce' ),
				),
			)
		);
		echo '<div class="wrap aurelia-admin"><h1>' . esc_html__( 'Social posts', 'aurelia-commerce' ) . '</h1><p class="description">' . esc_html__( 'Compose from a product, let AI write the caption, then schedule or publish. Every platform gets its own tracked short link, so the dashboard shows which post and network drove visits.', 'aurelia-commerce' ) . '</p><div id="aurelia-social-app"></div></div>';
	}

	/**
	 * REST routes (store managers only).
	 */
	public function routes() {
		$can = static fn() => current_user_can( Admin::CAP );
		register_rest_route(
			'aurelia/v1',
			'/social/posts',
			array(
				array(
					'methods'             => 'GET',
					'permission_callback' => $can,
					'callback'            => array( $this, 'list_posts' ),
				),
				array(
					'methods'             => 'POST',
					'permission_callback' => $can,
					'callback'            => array( $this, 'save_post' ),
				),
			)
		);
		register_rest_route(
			'aurelia/v1',
			'/social/posts/(?P<id>\d+)',
			array(
				'methods'             => 'DELETE',
				'permission_callback' => $can,
				'callback'            => function ( \WP_REST_Request $r ) {
					$post = get_post( absint( $r['id'] ) );
					if ( $post && self::CPT === $post->post_type ) {
						wp_delete_post( $post->ID, true );
					}
					return array( 'deleted' => true );
				},
			)
		);
		register_rest_route(
			'aurelia/v1',
			'/social/posts/(?P<id>\d+)/publish',
			array(
				'methods'             => 'POST',
				'permission_callback' => $can,
				'callback'            => fn( \WP_REST_Request $r ) => $this->export( $this->publish( absint( $r['id'] ) ) ),
			)
		);
		register_rest_route(
			'aurelia/v1',
			'/social/caption',
			array(
				'methods'             => 'POST',
				'permission_callback' => $can,
				'callback'            => array( $this, 'caption' ),
			)
		);
		register_rest_route(
			'aurelia/v1',
			'/catalog',
			array(
				'methods'             => 'GET',
				'permission_callback' => $can,
				'callback'            => array( __CLASS__, 'catalog' ),
			)
		);
	}

	/**
	 * Products for admin tools (Social composer, Video Studio).
	 *
	 * @return array
	 */
	public static function catalog() {
		$out = array();
		foreach ( wc_get_products(
			array(
				'status'  => 'publish',
				'limit'   => 200,
				'orderby' => 'date',
				'order'   => 'DESC',
			)
		) as $product ) {
			$image = $product->get_image_id() ? wp_get_attachment_image_url( $product->get_image_id(), 'large' ) : '';
			$out[] = array(
				'id'    => $product->get_id(),
				'name'  => $product->get_name(),
				'url'   => $product->get_permalink(),
				'image' => $image ? $image : '',
				'price' => Helpers::money( (float) $product->get_price() ),
			);
		}
		return $out;
	}

	/**
	 * Shape a post for the admin app.
	 *
	 * @param int $id Post ID.
	 * @return array
	 */
	private function export( $id ) {
		global $wpdb;
		$clicks = array();
		if ( Settings::on( 'analytics_enabled' ) ) {
			$rows   = $wpdb->get_results( $wpdb->prepare( "SELECT platform, COUNT(*) AS c FROM %i WHERE type = 'social_click' AND ref_id = %d GROUP BY platform", Analytics::table(), $id ), ARRAY_A ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery -- custom analytics table.
			$clicks = array_column( (array) $rows, 'c', 'platform' );
		}
		$platforms = (array) get_post_meta( $id, '_platforms', true );
		$links     = array();
		foreach ( $platforms as $platform ) {
			$links[ $platform ] = self::tracked_link( $id, $platform );
		}
		$when = (int) get_post_meta( $id, '_scheduled_at', true );
		return array(
			'id'          => $id,
			'caption'     => (string) get_post_meta( $id, '_caption', true ),
			'hashtags'    => (string) get_post_meta( $id, '_hashtags', true ),
			'mediaUrl'    => (string) get_post_meta( $id, '_media_url', true ),
			'mediaType'   => (string) get_post_meta( $id, '_media_type', true ),
			'link'        => (string) get_post_meta( $id, '_link', true ),
			'productId'   => (int) get_post_meta( $id, '_product_id', true ),
			'platforms'   => $platforms,
			'scheduledAt' => $when ? wp_date( 'Y-m-d\TH:i', $when ) : '',
			'status'      => (string) get_post_meta( $id, '_status', true ),
			'results'     => (array) get_post_meta( $id, '_results', true ),
			'links'       => $links,
			'clicks'      => array_map( 'intval', $clicks ),
		);
	}

	/**
	 * List posts.
	 *
	 * @return array
	 */
	public function list_posts() {
		$ids = get_posts(
			array(
				'post_type'      => self::CPT,
				'post_status'    => 'any',
				'posts_per_page' => 50,
				'fields'         => 'ids',
				'orderby'        => 'date',
				'order'          => 'DESC',
			)
		);
		return array_map( array( $this, 'export' ), $ids );
	}

	/**
	 * Create or update a post.
	 *
	 * @param \WP_REST_Request $request Request.
	 * @return array|\WP_Error
	 */
	public function save_post( \WP_REST_Request $request ) {
		$id        = absint( $request->get_param( 'id' ) );
		$caption   = sanitize_textarea_field( (string) $request->get_param( 'caption' ) );
		$platforms = array_values( array_intersect( self::PLATFORMS, array_map( 'sanitize_key', (array) $request->get_param( 'platforms' ) ) ) );
		$when_raw  = sanitize_text_field( (string) $request->get_param( 'scheduledAt' ) );
		$when      = 0;
		if ( '' !== $when_raw ) {
			$date = date_create_immutable( $when_raw, wp_timezone() );
			$when = $date ? $date->getTimestamp() : 0;
		}
		$postarr = array(
			'post_type'   => self::CPT,
			'post_status' => 'private',
			'post_title'  => wp_trim_words( '' !== $caption ? $caption : __( 'Social post', 'aurelia-commerce' ), 10 ),
		);
		if ( $id ) {
			$existing = get_post( $id );
			if ( ! $existing || self::CPT !== $existing->post_type ) {
				return new \WP_Error( 'aurelia_social_404', __( 'Post not found.', 'aurelia-commerce' ), array( 'status' => 404 ) );
			}
			$postarr['ID'] = $id;
			$id            = wp_update_post( $postarr, true );
		} else {
			$id = wp_insert_post( $postarr, true );
		}
		if ( is_wp_error( $id ) ) {
			return $id;
		}
		$media_type = in_array( $request->get_param( 'mediaType' ), array( 'image', 'video', 'none' ), true ) ? $request->get_param( 'mediaType' ) : 'image';
		update_post_meta( $id, '_caption', mb_substr( $caption, 0, 2200 ) );
		update_post_meta( $id, '_hashtags', mb_substr( sanitize_text_field( (string) $request->get_param( 'hashtags' ) ), 0, 600 ) );
		update_post_meta( $id, '_media_url', esc_url_raw( (string) $request->get_param( 'mediaUrl' ) ) );
		update_post_meta( $id, '_media_type', $media_type );
		update_post_meta( $id, '_link', esc_url_raw( (string) $request->get_param( 'link' ) ) );
		update_post_meta( $id, '_product_id', absint( $request->get_param( 'productId' ) ) );
		update_post_meta( $id, '_platforms', $platforms );
		update_post_meta( $id, '_scheduled_at', $when );
		$status = (string) get_post_meta( $id, '_status', true );
		if ( ! in_array( $status, array( 'published', 'partial', 'publishing' ), true ) ) {
			update_post_meta( $id, '_status', $when ? 'scheduled' : 'draft' );
		}
		if ( $request->get_param( 'publish' ) ) {
			$this->publish( $id );
		}
		return $this->export( $id );
	}

	/**
	 * AI caption + hashtags for a product (template fallback without a key).
	 *
	 * @param \WP_REST_Request $request Request.
	 * @return array
	 */
	public function caption( \WP_REST_Request $request ) {
		$product = wc_get_product( absint( $request->get_param( 'productId' ) ) );
		$brief   = sanitize_textarea_field( (string) $request->get_param( 'brief' ) );
		$name    = $product ? $product->get_name() : '';
		$price   = $product ? Helpers::money( (float) $product->get_price() ) : '';
		$details = $product ? wp_strip_all_tags( $product->get_short_description() . ' ' . $product->get_description() ) : '';
		$cats    = $product ? wp_get_post_terms( $product->get_id(), 'product_cat', array( 'fields' => 'names' ) ) : array();
		$tags    = $product ? wp_get_post_terms( $product->get_id(), 'product_tag', array( 'fields' => 'names' ) ) : array();

		if ( Claude_Client::has_key() ) {
			$prompt  = 'Write a scroll-stopping social media caption for ' . Settings::store_name() . ", an online store.\n";
			$prompt .= $product ? "Product: {$name}\nPrice: {$price}\nCategory: " . implode( ', ', (array) $cats ) . "\nDetails: " . mb_substr( $details, 0, 800 ) . "\n" : '';
			$prompt .= '' !== $brief ? "Brief from the store owner: {$brief}\n" : '';
			$prompt .= "Rules: 2–4 short lines, warm and premium tone, one or two emojis at most, mention the price if given, end with a call to action to order on WhatsApp or tap the link. Do not invent discounts or facts.\n";
			$prompt .= 'Reply with JSON only: {"caption": "...", "hashtags": "#tag1 #tag2 ... (8-12 relevant hashtags, mix of broad and niche, include India-relevant ones when natural)"}';
			$result  = Claude_Client::create(
				array(
					'max_tokens'    => 800,
					'messages'      => array(
						array(
							'role'    => 'user',
							'content' => $prompt,
						),
					),
					'output_config' => array( 'effort' => 'low' ),
				)
			);
			if ( ! is_wp_error( $result ) && preg_match( '/\{.*\}/s', $result['text'], $m ) ) {
				$json = json_decode( $m[0], true );
				if ( is_array( $json ) && ! empty( $json['caption'] ) ) {
					return array(
						'caption'  => sanitize_textarea_field( (string) $json['caption'] ),
						'hashtags' => sanitize_text_field( (string) ( $json['hashtags'] ?? '' ) ),
						'source'   => 'ai',
					);
				}
			}
		}

		$hashtags = array_map(
			static fn( $t ) => '#' . preg_replace( '/[^\p{L}\p{N}]+/u', '', ucwords( $t ) ),
			array_slice( array_merge( (array) $cats, (array) $tags, array( 'ShopOnline', 'MadeForYou', 'NewArrivals' ) ), 0, 10 )
		);
		/* translators: 1: product name, 2: price. */
		$caption = $product ? sprintf( __( "✨ Meet the %1\$s.\nMade to be loved, priced at %2\$s.\n🛍️ Tap the link or message us on WhatsApp to order.", 'aurelia-commerce' ), $name, $price ) : $brief;
		return array(
			'caption'  => $caption,
			'hashtags' => implode( ' ', array_unique( $hashtags ) ),
			'source'   => 'template',
		);
	}

	/**
	 * Platform-specific caption with the tracked link.
	 *
	 * @param int    $id       Post ID.
	 * @param string $platform Platform.
	 * @return string
	 */
	public static function caption_for( $id, $platform ) {
		$caption = (string) get_post_meta( $id, '_caption', true );
		$tags    = trim( (string) get_post_meta( $id, '_hashtags', true ) );
		$link    = '' !== (string) get_post_meta( $id, '_link', true ) ? self::tracked_link( $id, $platform ) : '';
		if ( 'instagram' === $platform ) {
			// Instagram captions don't make links clickable.
			return implode( "\n\n", array_filter( array( $caption, $link ? __( '🔗 Shop via the link in our bio or DM us on WhatsApp', 'aurelia-commerce' ) : '', $tags ) ) );
		}
		if ( 'x' === $platform ) {
			return implode( "\n", array_filter( array( mb_substr( $caption, 0, 200 ), $link, implode( ' ', array_slice( preg_split( '/\s+/', $tags ), 0, 3 ) ) ) ) );
		}
		/* translators: %s: link. */
		return implode( "\n\n", array_filter( array( $caption, $link ? sprintf( __( 'Shop now: %s', 'aurelia-commerce' ), $link ) : '', $tags ) ) );
	}

	/**
	 * Publish a post to its platforms.
	 *
	 * @param int $id Post ID.
	 * @return int Post ID.
	 * @throws \RuntimeException Internally, per platform; always caught and stored as a result.
	 */
	public function publish( $id ) {
		$platforms = (array) get_post_meta( $id, '_platforms', true );
		$media     = (string) get_post_meta( $id, '_media_url', true );
		$type      = (string) get_post_meta( $id, '_media_type', true );
		$results   = array();
		$now       = gmdate( 'c' );
		update_post_meta( $id, '_status', 'publishing' );

		$webhook = (string) Settings::get( 'social_webhook', '' );
		if ( '' !== $webhook ) {
			$payload  = array(
				'id'        => $id,
				'brand'     => Settings::store_name(),
				'mediaUrl'  => $media,
				'mediaType' => $type,
				'link'      => (string) get_post_meta( $id, '_link', true ),
				'posts'     => array_map(
					static fn( $p ) => array(
						'platform'    => $p,
						'caption'     => self::caption_for( $id, $p ),
						'trackedLink' => self::tracked_link( $id, $p ),
					),
					$platforms
				),
			);
			$response = wp_remote_post(
				$webhook,
				array(
					'timeout' => 20,
					'headers' => array( 'Content-Type' => 'application/json' ),
					'body'    => wp_json_encode( $payload ),
				)
			);
			$code     = is_wp_error( $response ) ? 0 : (int) wp_remote_retrieve_response_code( $response );
			foreach ( $platforms as $p ) {
				$results[ $p ] = array(
					'ok'    => $code >= 200 && $code < 300,
					'error' => is_wp_error( $response ) ? $response->get_error_message() : ( $code >= 300 ? 'HTTP ' . $code : '' ),
					'at'    => $now,
				);
			}
		} else {
			foreach ( $platforms as $p ) {
				try {
					if ( 'facebook' === $p ) {
						$results[ $p ] = array(
							'ok' => true,
							'id' => $this->publish_facebook( $id ),
							'at' => $now,
						);
					} elseif ( 'instagram' === $p ) {
						$results[ $p ] = $this->publish_instagram( $id ) + array( 'at' => $now );
					} else {
						throw new \RuntimeException( __( 'No publisher for this platform — add a webhook in Settings → Social posting.', 'aurelia-commerce' ) );
					}
				} catch ( \Exception $e ) {
					$results[ $p ] = array(
						'ok'    => false,
						'error' => $e->getMessage(),
						'at'    => $now,
					);
				}
			}
		}
		update_post_meta( $id, '_results', $results );
		$this->update_status( $id, $results );
		return $id;
	}

	/**
	 * Derive the overall status from per-platform results.
	 *
	 * @param int   $id      Post ID.
	 * @param array $results Results.
	 */
	private function update_status( $id, array $results ) {
		$pending = count( array_filter( $results, static fn( $r ) => ! empty( $r['pending'] ) ) );
		$ok      = count( array_filter( $results, static fn( $r ) => ! empty( $r['ok'] ) ) );
		if ( $pending ) {
			$status = 'publishing';
		} elseif ( $ok && count( $results ) === $ok ) {
			$status = 'published';
		} elseif ( $ok ) {
			$status = 'partial';
		} else {
			$status = 'failed';
		}
		update_post_meta( $id, '_status', $status );
	}

	/**
	 * Meta Graph API POST.
	 *
	 * @param string $path   Path.
	 * @param array  $params Params.
	 * @return array
	 * @throws \RuntimeException On API error.
	 */
	private function graph( $path, array $params ) {
		$version  = preg_replace( '/[^v0-9.]/', '', (string) Settings::get( 'meta_graph_version', 'v23.0' ) );
		$response = wp_remote_post(
			'https://graph.facebook.com/' . $version . '/' . ltrim( $path, '/' ),
			array(
				'timeout' => 45,
				'body'    => $params + array( 'access_token' => Settings::secret( 'meta_token' ) ),
			)
		);
		if ( is_wp_error( $response ) ) {
			throw new \RuntimeException( esc_html( $response->get_error_message() ) );
		}
		$data = json_decode( (string) wp_remote_retrieve_body( $response ), true );
		if ( ! is_array( $data ) || isset( $data['error'] ) ) {
			throw new \RuntimeException( esc_html( (string) ( $data['error']['message'] ?? 'Graph API error' ) ) );
		}
		return $data;
	}

	/**
	 * Publish to the Facebook Page.
	 *
	 * @param int $id Post ID.
	 * @return string External ID.
	 * @throws \RuntimeException When not configured.
	 */
	private function publish_facebook( $id ) {
		$page = (string) Settings::get( 'meta_page_id', '' );
		if ( '' === $page || '' === Settings::secret( 'meta_token' ) ) {
			throw new \RuntimeException( esc_html__( 'Facebook is not connected — add your Page ID and token in Settings → Social posting.', 'aurelia-commerce' ) );
		}
		$message = self::caption_for( $id, 'facebook' );
		$media   = (string) get_post_meta( $id, '_media_url', true );
		$type    = (string) get_post_meta( $id, '_media_type', true );
		if ( 'video' === $type && '' !== $media ) {
			return (string) ( $this->graph(
				$page . '/videos',
				array(
					'file_url'    => $media,
					'description' => $message,
				)
			)['id'] ?? '' );
		}
		if ( 'image' === $type && '' !== $media ) {
			return (string) ( $this->graph(
				$page . '/photos',
				array(
					'url'     => $media,
					'caption' => $message,
				)
			)['post_id'] ?? '' );
		}
		$params = array( 'message' => $message );
		if ( get_post_meta( $id, '_link', true ) ) {
			$params['link'] = self::tracked_link( $id, 'facebook' );
		}
		return (string) ( $this->graph( $page . '/feed', $params )['id'] ?? '' );
	}

	/**
	 * Publish to Instagram. Videos (Reels) are processed asynchronously: the
	 * container is created now and published by a later cron tick.
	 *
	 * @param int $id Post ID.
	 * @return array Result.
	 * @throws \RuntimeException When not configured.
	 */
	private function publish_instagram( $id ) {
		$ig    = (string) Settings::get( 'meta_ig_user_id', '' );
		$media = (string) get_post_meta( $id, '_media_url', true );
		$type  = (string) get_post_meta( $id, '_media_type', true );
		if ( '' === $ig || '' === Settings::secret( 'meta_token' ) ) {
			throw new \RuntimeException( esc_html__( 'Instagram is not connected — add your Instagram Business account ID and token.', 'aurelia-commerce' ) );
		}
		if ( '' === $media || 'none' === $type ) {
			throw new \RuntimeException( esc_html__( 'Instagram needs an image or video.', 'aurelia-commerce' ) );
		}
		$caption   = self::caption_for( $id, 'instagram' );
		$container = $this->graph(
			$ig . '/media',
			'video' === $type ? array(
				'media_type' => 'REELS',
				'video_url'  => $media,
				'caption'    => $caption,
			) : array(
				'image_url' => $media,
				'caption'   => $caption,
			)
		);
		if ( 'video' === $type ) {
			update_post_meta( $id, '_ig_container', (string) $container['id'] );
			return array(
				'ok'      => false,
				'pending' => true,
				'error'   => __( 'Processing video…', 'aurelia-commerce' ),
			);
		}
		$published = $this->graph( $ig . '/media_publish', array( 'creation_id' => (string) $container['id'] ) );
		return array(
			'ok' => true,
			'id' => (string) ( $published['id'] ?? '' ),
		);
	}

	/**
	 * Cron: publish due posts and finish pending Instagram Reels.
	 */
	public function tick() {
		$due = get_posts(
			array(
				'post_type'      => self::CPT,
				'post_status'    => 'any',
				'posts_per_page' => 10,
				'fields'         => 'ids',
				'meta_query'     => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- small private CPT.
					'relation' => 'AND',
					array(
						'key'   => '_status',
						'value' => 'scheduled',
					),
					array(
						'key'     => '_scheduled_at',
						'value'   => time(),
						'compare' => '<=',
						'type'    => 'NUMERIC',
					),
				),
			)
		);
		foreach ( $due as $id ) {
			$this->publish( $id );
		}

		$pending = get_posts(
			array(
				'post_type'      => self::CPT,
				'post_status'    => 'any',
				'posts_per_page' => 10,
				'fields'         => 'ids',
				'meta_key'       => '_ig_container', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- small private CPT.
			)
		);
		foreach ( $pending as $id ) {
			$container = (string) get_post_meta( $id, '_ig_container', true );
			$results   = (array) get_post_meta( $id, '_results', true );
			try {
				$version  = preg_replace( '/[^v0-9.]/', '', (string) Settings::get( 'meta_graph_version', 'v23.0' ) );
				$response = wp_remote_get( 'https://graph.facebook.com/' . $version . '/' . rawurlencode( $container ) . '?fields=status_code&access_token=' . rawurlencode( Settings::secret( 'meta_token' ) ), array( 'timeout' => 20 ) );
				$status   = json_decode( (string) wp_remote_retrieve_body( $response ), true );
				$code     = (string) ( $status['status_code'] ?? '' );
				if ( 'FINISHED' === $code ) {
					$published            = $this->graph( (string) Settings::get( 'meta_ig_user_id', '' ) . '/media_publish', array( 'creation_id' => $container ) );
					$results['instagram'] = array(
						'ok' => true,
						'id' => (string) ( $published['id'] ?? '' ),
						'at' => gmdate( 'c' ),
					);
					delete_post_meta( $id, '_ig_container' );
				} elseif ( 'ERROR' === $code || 'EXPIRED' === $code ) {
					$results['instagram'] = array(
						'ok'    => false,
						'error' => __( 'Instagram could not process the video.', 'aurelia-commerce' ),
						'at'    => gmdate( 'c' ),
					);
					delete_post_meta( $id, '_ig_container' );
				}
			} catch ( \Exception $e ) {
				$results['instagram'] = array(
					'ok'    => false,
					'error' => $e->getMessage(),
					'at'    => gmdate( 'c' ),
				);
				delete_post_meta( $id, '_ig_container' );
			}
			update_post_meta( $id, '_results', $results );
			$this->update_status( $id, $results );
		}
	}
}
