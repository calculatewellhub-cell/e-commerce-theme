<?php
/**
 * AI shopping concierge: chat widget + REST endpoint backed by Claude.
 *
 * The system prompt is built from live WooCommerce data (products, categories,
 * policies and FAQ). Without an API key, a rule-based assistant answers from
 * the same catalogue.
 *
 * @package Aurelia_Commerce
 */

namespace Aurelia\Commerce;

defined( 'ABSPATH' ) || exit;

/**
 * Concierge.
 */
class Concierge {

	const CONTEXT_CACHE = 'aurelia_concierge_context';

	/**
	 * Hooks.
	 */
	public function init() {
		add_action( 'rest_api_init', array( $this, 'routes' ) );
		if ( ! Settings::on( 'ai_enabled' ) ) {
			return;
		}
		add_action( 'wp_enqueue_scripts', array( $this, 'assets' ), 20 );
		add_filter( 'aurelia_commerce_frontend_config', array( $this, 'config' ) );
		foreach ( array( 'save_post_product', 'woocommerce_update_product', 'created_product_cat', 'edited_product_cat', 'update_option_' . Settings::OPTION ) as $hook ) {
			add_action( $hook, array( __CLASS__, 'flush_context' ) );
		}
	}

	/**
	 * Clear the cached catalogue context.
	 */
	public static function flush_context() {
		delete_transient( self::CONTEXT_CACHE );
	}

	/**
	 * Enqueue the widget (not on checkout, where focus matters most).
	 */
	public function assets() {
		if ( is_checkout() ) {
			return;
		}
		Helpers::register_script( 'aurelia-chat', 'chat.js', array( 'aurelia-commerce' ) );
		wp_enqueue_script( 'aurelia-chat' );
	}

	/**
	 * Front-end config.
	 *
	 * @param array $config Config.
	 * @return array
	 */
	public function config( $config ) {
		$suggestions     = array_values( array_filter( array_map( 'trim', explode( "\n", (string) Settings::get( 'ai_suggestions', '' ) ) ) ) );
		$config['chat']  = array(
			'name'        => (string) Settings::get( 'ai_name', 'Aria' ),
			'greeting'    => (string) Settings::get( 'ai_greeting', '' ),
			'suggestions' => array_slice( $suggestions, 0, 4 ),
			'store'       => Settings::store_name(),
		);
		$config['i18n'] += array(
			'chatOpen'         => __( 'Open the AI shopping assistant', 'aurelia-commerce' ),
			/* translators: %s: assistant name. */
			'chatAsk'          => sprintf( __( 'Ask %s', 'aurelia-commerce' ), Settings::get( 'ai_name', 'Aria' ) ),
			'chatTitle'        => __( 'AI shopping concierge', 'aurelia-commerce' ),
			'chatStatus'       => __( 'Online · replies instantly', 'aurelia-commerce' ),
			'chatClose'        => __( 'Close chat', 'aurelia-commerce' ),
			'chatPlace'        => __( 'Ask about products, budgets, delivery…', 'aurelia-commerce' ),
			'chatSend'         => __( 'Send', 'aurelia-commerce' ),
			'chatMessage'      => __( 'Message', 'aurelia-commerce' ),
			'chatError'        => __( 'Sorry, I am having trouble connecting. Our team is always available on WhatsApp.', 'aurelia-commerce' ),
			'chatHandoff'      => __( 'Continue with a human on WhatsApp', 'aurelia-commerce' ),
			/* translators: %s: store name. */
			'chatHandoffIntro' => sprintf( __( 'Hello %s! I was chatting with your assistant on the website.', 'aurelia-commerce' ), Settings::store_name() ),
			'chatMe'           => __( 'Me', 'aurelia-commerce' ),
			'chatTyping'       => __( 'Assistant is typing', 'aurelia-commerce' ),
		);
		return $config;
	}

	/**
	 * REST routes.
	 */
	public function routes() {
		register_rest_route(
			'aurelia/v1',
			'/chat',
			array(
				'methods'             => 'POST',
				'permission_callback' => '__return_true',
				'callback'            => array( $this, 'chat' ),
				'args'                => array(
					'messages' => array(
						'type'     => 'array',
						'required' => true,
					),
				),
			)
		);
	}

	/**
	 * Handle a chat turn.
	 *
	 * @param \WP_REST_Request $request Request.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function chat( \WP_REST_Request $request ) {
		if ( ! Settings::on( 'ai_enabled' ) ) {
			return new \WP_Error( 'aurelia_chat_disabled', __( 'The assistant is disabled.', 'aurelia-commerce' ), array( 'status' => 403 ) );
		}
		if ( ! Helpers::rate_limit( 'chat', max( 1, (int) Settings::get( 'ai_rate_limit', 20 ) ), 10 * MINUTE_IN_SECONDS ) ) {
			return new \WP_Error( 'aurelia_rate_limited', __( 'You are sending messages too quickly. Please wait a few minutes, or reach us on WhatsApp.', 'aurelia-commerce' ), array( 'status' => 429 ) );
		}

		$messages = $this->clean_messages( (array) $request->get_param( 'messages' ) );
		if ( ! $messages ) {
			return new \WP_Error( 'aurelia_bad_chat', __( 'Invalid conversation.', 'aurelia-commerce' ), array( 'status' => 400 ) );
		}
		$last = end( $messages );

		Analytics::record( 'chat_message' );

		if ( Claude_Client::has_key() ) {
			$result = Claude_Client::create(
				array(
					'max_tokens'    => 1024,
					'system'        => array(
						array(
							'type'          => 'text',
							'text'          => $this->system_prompt(),
							'cache_control' => array( 'type' => 'ephemeral' ),
						),
					),
					'messages'      => $messages,
					'output_config' => array( 'effort' => 'low' ),
				)
			);
			if ( ! is_wp_error( $result ) && '' !== $result['text'] ) {
				return rest_ensure_response(
					array(
						'reply'  => $result['text'],
						'source' => 'ai',
					)
				);
			}
			if ( is_wp_error( $result ) && 'aurelia_claude_refusal' === $result->get_error_code() ) {
				return rest_ensure_response(
					array(
						'reply'  => __( 'I am not able to help with that here — our team will be happy to assist you on WhatsApp.', 'aurelia-commerce' ),
						'source' => 'ai',
					)
				);
			}
		}

		return rest_ensure_response(
			array(
				'reply'  => $this->rule_based_reply( $last['content'] ),
				'source' => 'rules',
			)
		);
	}

	/**
	 * Validate and trim the conversation.
	 *
	 * @param array $raw Raw messages.
	 * @return array
	 */
	private function clean_messages( array $raw ) {
		$messages = array();
		foreach ( $raw as $m ) {
			if ( ! is_array( $m ) || ! isset( $m['role'], $m['content'] ) || ! in_array( $m['role'], array( 'user', 'assistant' ), true ) ) {
				continue;
			}
			$content = trim( sanitize_textarea_field( (string) $m['content'] ) );
			if ( '' === $content ) {
				continue;
			}
			$messages[] = array(
				'role'    => $m['role'],
				'content' => mb_substr( $content, 0, 2000 ),
			);
		}
		$messages = array_slice( $messages, -12 );
		// The conversation must start with a user turn and end with one.
		while ( $messages && 'user' !== $messages[0]['role'] ) {
			array_shift( $messages );
		}
		// Merge consecutive same-role turns so roles alternate.
		$merged = array();
		foreach ( $messages as $m ) {
			$last_index = count( $merged ) - 1;
			if ( $last_index >= 0 && $merged[ $last_index ]['role'] === $m['role'] ) {
				$merged[ $last_index ]['content'] .= "\n\n" . $m['content'];
			} else {
				$merged[] = $m;
			}
		}
		$end = end( $merged );
		return ( $end && 'user' === $end['role'] ) ? $merged : array();
	}

	/**
	 * System prompt: role, rules, then live store context.
	 *
	 * @return string
	 */
	public function system_prompt() {
		$name  = (string) Settings::get( 'ai_name', 'Aria' );
		$store = Settings::store_name();
		$extra = trim( (string) Settings::get( 'ai_instructions', '' ) );

		$prompt  = "You are \"{$name}\", the warm, knowledgeable shopping concierge for {$store}, an online store.\n\n";
		$prompt .= "How to help:\n";
		$prompt .= "- Help shoppers find the right product: when the need is unclear, ask about occasion, recipient, budget and preferences, one or two questions at a time.\n";
		$prompt .= "- Recommend only products from the catalogue below. Link each recommendation with markdown exactly as given, e.g. [Product name](/product/slug/), and include its price.\n";
		$prompt .= "- Answer questions about materials, sizing, care, delivery, payments and returns accurately and briefly, using the policies and FAQ below.\n";
		$prompt .= "- For orders, custom requests, video calls or anything you cannot resolve, invite the shopper to tap \"Order on WhatsApp\" or the WhatsApp button to reach a person.\n";
		$prompt .= "- Never invent products, prices, discounts, stock or policies that are not listed. If unsure, say the team will confirm on WhatsApp.\n";
		$prompt .= "- Reply in the shopper's language. Keep replies short and conversational (under 120 words), with plain text and markdown links only.\n";
		if ( '' !== $extra ) {
			$prompt .= "\nStore owner's instructions:\n" . $extra . "\n";
		}
		return $prompt . "\n" . $this->catalog_context();
	}

	/**
	 * Store, category, product, policy and FAQ context (cached).
	 *
	 * @return string
	 */
	public function catalog_context() {
		$cached = get_transient( self::CONTEXT_CACHE );
		if ( is_string( $cached ) && '' !== $cached ) {
			return $cached;
		}

		$lines   = array();
		$lines[] = 'Store: ' . Settings::store_name() . ' — ' . get_bloginfo( 'description' );
		$city    = trim( Settings::get( 'store_address', '' ) . ', ' . Settings::get( 'store_city', '' ), ', ' );
		if ( '' !== $city ) {
			$lines[] = 'Location: ' . $city . '. Hours: ' . Settings::get( 'store_hours', '' ) . '.';
		}
		$lines[] = 'Currency: ' . get_woocommerce_currency();

		$cats = get_terms(
			array(
				'taxonomy'   => 'product_cat',
				'hide_empty' => true,
				'number'     => 40,
			)
		);
		if ( ! is_wp_error( $cats ) && $cats ) {
			$lines[] = 'Categories: ' . implode(
				', ',
				array_map(
					static fn( $c ) => $c->name . ' (' . Helpers::path_of( get_term_link( $c ) ) . ')',
					$cats
				)
			) . '.';
		}

		$lines[]  = "\nCatalogue (name | link | price | category | details):";
		$products = wc_get_products(
			array(
				'status'  => 'publish',
				'limit'   => 150,
				'orderby' => 'popularity',
				'order'   => 'DESC',
			)
		);
		foreach ( $products as $product ) {
			$lines[] = $this->product_line( $product );
		}

		$policies = trim( (string) Settings::get( 'ai_policies', '' ) );
		if ( '' !== $policies ) {
			$lines[] = "\nPolicies:\n" . $policies;
		}
		$faq = $this->faq_text();
		if ( '' !== $faq ) {
			$lines[] = "\nFAQ:\n" . $faq;
		}

		$context = implode( "\n", $lines );
		set_transient( self::CONTEXT_CACHE, $context, 12 * HOUR_IN_SECONDS );
		return $context;
	}

	/**
	 * One catalogue line.
	 *
	 * @param \WC_Product $product Product.
	 * @return string
	 */
	private function product_line( $product ) {
		$cats  = wp_get_post_terms( $product->get_id(), 'product_cat', array( 'fields' => 'names' ) );
		$price = $product->is_type( 'variable' ) ? Helpers::money( (float) $product->get_variation_price( 'min' ) ) . '+' : Helpers::money( (float) $product->get_price() );
		if ( $product->is_on_sale() && ! $product->is_type( 'variable' ) ) {
			$price .= ' (was ' . Helpers::money( (float) $product->get_regular_price() ) . ')';
		}
		$details = wp_strip_all_tags( $product->get_short_description() ? $product->get_short_description() : $product->get_description() );
		$details = mb_substr( preg_replace( '/\s+/', ' ', $details ), 0, 180 );
		$attrs   = array();
		foreach ( $product->get_attributes() as $attribute ) {
			$values  = $attribute->is_taxonomy() ? wc_get_product_terms( $product->get_id(), $attribute->get_name(), array( 'fields' => 'names' ) ) : $attribute->get_options();
			$attrs[] = wc_attribute_label( $attribute->get_name() ) . ': ' . implode( '/', array_slice( (array) $values, 0, 8 ) );
		}
		$stock = $product->is_in_stock() ? '' : ' | OUT OF STOCK';
		$sku   = $product->get_sku() ? ' | SKU ' . $product->get_sku() : '';
		return sprintf(
			'- [%1$s](%2$s) | %3$s | %4$s | %5$s%6$s%7$s%8$s',
			$product->get_name(),
			Helpers::path_of( $product->get_permalink() ),
			$price,
			implode( ', ', is_array( $cats ) ? $cats : array() ),
			$details,
			$attrs ? ' | ' . implode( '; ', $attrs ) : '',
			$sku,
			$stock
		);
	}

	/**
	 * FAQ from settings plus FAQ-style Details blocks on published pages.
	 *
	 * @return string
	 */
	public function faq_text() {
		$out = array( trim( (string) Settings::get( 'ai_faq', '' ) ) );
		foreach ( Seo::collect_faqs() as $faq ) {
			$out[] = 'Q: ' . $faq['q'] . "\nA: " . $faq['a'];
		}
		return trim( implode( "\n", array_unique( array_filter( $out ) ) ) );
	}

	/**
	 * Offline fallback: matches budget, category and keywords against the live catalogue.
	 *
	 * @param string $message Shopper message.
	 * @return string
	 */
	public function rule_based_reply( $message ) {
		$m      = mb_strtolower( $message );
		$name   = (string) Settings::get( 'ai_name', 'Aria' );
		$budget = null;

		if ( preg_match( '/(\d[\d,]*(?:\.\d+)?)\s*(k|lakh|lac|l)?\b/u', $m, $match ) ) {
			$n    = (float) str_replace( ',', '', $match[1] );
			$unit = $match[2] ?? '';
			if ( 'k' === $unit ) {
				$budget = $n * 1000;
			} elseif ( '' !== $unit ) {
				$budget = $n * 100000;
			} elseif ( $n >= 100 ) {
				$budget = $n;
			}
		}

		// Policy questions.
		if ( preg_match( '/ship|deliver|return|exchange|refund|payment|pay|upi|cod|cash|warranty|track|size/u', $m ) ) {
			$best  = '';
			$score = 0;
			$lines = preg_split( '/\n+/', (string) Settings::get( 'ai_policies', '' ) . "\n" . str_replace( "\nA: ", ' ', $this->faq_text() ) );
			foreach ( $lines as $line ) {
				$words = array_filter( preg_split( '/\W+/u', mb_strtolower( $line ) ), static fn( $w ) => mb_strlen( $w ) > 3 );
				$hits  = count( array_filter( $words, static fn( $w ) => str_contains( $m, $w ) ) );
				if ( $hits > $score ) {
					$score = $hits;
					$best  = $line;
				}
			}
			if ( '' !== $best ) {
				return trim( preg_replace( '/^Q:.*?\?\s*/u', '', $best ) ) . "\n\n" . __( 'Anything else I can help with? You can also chat with our team on WhatsApp.', 'aurelia-commerce' );
			}
		}

		$cat_slug = '';
		$terms    = get_terms(
			array(
				'taxonomy'   => 'product_cat',
				'hide_empty' => true,
			)
		);
		if ( ! is_wp_error( $terms ) ) {
			foreach ( $terms as $term ) {
				$stem = mb_strtolower( rtrim( $term->name, 's' ) );
				if ( mb_strlen( $stem ) > 2 && str_contains( $m, $stem ) ) {
					$cat_slug = $term->slug;
					break;
				}
			}
		}

		$words = array_values( array_filter( preg_split( '/\W+/u', $m ), static fn( $w ) => mb_strlen( $w ) > 3 ) );

		if ( preg_match( '/^(hi|hello|hey|namaste|hola)\b/u', $m ) || ( '' === $cat_slug && null === $budget && ! $words ) ) {
			/* translators: 1: assistant name, 2: store name. */
			return sprintf( __( 'Welcome to %2$s ✨ I am %1$s, your shopping concierge. Are you shopping for an occasion, a gift or something for yourself? Tell me a budget and I will suggest a few pieces.', 'aurelia-commerce' ), $name, Settings::store_name() );
		}

		$args = array(
			'status'  => 'publish',
			'limit'   => 40,
			'orderby' => 'popularity',
			'order'   => 'DESC',
		);
		if ( '' !== $cat_slug ) {
			$args['category'] = array( $cat_slug );
		}
		$candidates = wc_get_products( $args );
		if ( null !== $budget ) {
			$candidates = array_filter( $candidates, static fn( $p ) => (float) $p->get_price() <= $budget );
		}
		if ( $words ) {
			$scored = array_filter(
				$candidates,
				static function ( $p ) use ( $words ) {
					$hay = mb_strtolower( $p->get_name() . ' ' . wp_strip_all_tags( $p->get_short_description() ) . ' ' . implode( ' ', wp_get_post_terms( $p->get_id(), 'product_tag', array( 'fields' => 'names' ) ) ) );
					foreach ( $words as $w ) {
						if ( str_contains( $hay, $w ) ) {
							return true;
						}
					}
					return false;
				}
			);
			if ( $scored ) {
				$candidates = $scored;
			}
		}

		if ( ! $candidates ) {
			return __( 'I could not find an exact match right now. Our team can help you find or source it — tap the WhatsApp button and tell us what you have in mind!', 'aurelia-commerce' );
		}

		$picks = array();
		foreach ( array_slice( array_values( $candidates ), 0, 3 ) as $p ) {
			$picks[] = sprintf( '• [%1$s](%2$s) — %3$s', $p->get_name(), Helpers::path_of( $p->get_permalink() ), Helpers::money( (float) $p->get_price() ) );
		}
		return __( 'Here are a few you may love:', 'aurelia-commerce' ) . "\n" . implode( "\n", $picks ) . "\n\n" . __( 'Would you like help choosing, or shall I connect you to our team on WhatsApp?', 'aurelia-commerce' );
	}
}
