<?php
/**
 * Minimal server-side client for the Claude Messages API.
 *
 * The API key is read from the non-autoloaded secrets option and only ever sent
 * to api.anthropic.com from PHP — it never reaches the browser.
 *
 * @package Aurelia_Commerce
 */

namespace Aurelia\Commerce;

defined( 'ABSPATH' ) || exit;

/**
 * Claude API client.
 */
class Claude_Client {

	const ENDPOINT = 'https://api.anthropic.com/v1/messages';
	const VERSION  = '2023-06-01';

	/**
	 * Models that support the server-side refusal fallback.
	 */
	const FALLBACK_MODELS = array( 'claude-opus-5', 'claude-fable-5-1' );

	/**
	 * Is a key configured?
	 *
	 * @return bool
	 */
	public static function has_key() {
		return '' !== Settings::secret( 'anthropic_key' );
	}

	/**
	 * Call POST /v1/messages.
	 *
	 * @param array $body Request body (model defaults to the configured model).
	 * @return array|\WP_Error { text, model, stop_reason, usage } or error.
	 */
	public static function create( array $body ) {
		$key = Settings::secret( 'anthropic_key' );
		if ( '' === $key ) {
			return new \WP_Error( 'aurelia_no_key', __( 'No Claude API key is configured.', 'aurelia-commerce' ) );
		}

		$model         = ! empty( $body['model'] ) ? $body['model'] : (string) Settings::get( 'ai_model', 'claude-opus-5' );
		$body['model'] = '' !== $model ? $model : 'claude-opus-5';
		if ( empty( $body['max_tokens'] ) ) {
			$body['max_tokens'] = 1024;
		}

		$headers = array(
			'x-api-key'         => $key,
			'anthropic-version' => self::VERSION,
			'content-type'      => 'application/json',
		);

		// Re-run a classifier refusal on Anthropic's recommended fallback model instead of failing.
		if ( in_array( $body['model'], self::FALLBACK_MODELS, true ) ) {
			$headers['anthropic-beta'] = 'server-side-fallback-2026-07-01';
			$body['fallbacks']         = 'default';
		}

		/**
		 * Filters the Claude request body before it is sent.
		 *
		 * @param array $body Request body.
		 */
		$body = apply_filters( 'aurelia_commerce_claude_request', $body );

		$response = wp_remote_post(
			self::ENDPOINT,
			array(
				'timeout' => 60,
				'headers' => $headers,
				'body'    => wp_json_encode( $body ),
			)
		);
		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$code = (int) wp_remote_retrieve_response_code( $response );
		$data = json_decode( (string) wp_remote_retrieve_body( $response ), true );
		if ( $code < 200 || $code >= 300 || ! is_array( $data ) ) {
			$message = is_array( $data ) && isset( $data['error']['message'] ) ? (string) $data['error']['message'] : sprintf( 'HTTP %d', $code );
			return new \WP_Error( 'aurelia_claude_http', $message, array( 'status' => $code ) );
		}

		// Always check stop_reason before reading content.
		if ( 'refusal' === ( $data['stop_reason'] ?? '' ) ) {
			return new \WP_Error( 'aurelia_claude_refusal', __( 'The request was declined.', 'aurelia-commerce' ) );
		}

		$text = '';
		foreach ( (array) ( $data['content'] ?? array() ) as $block ) {
			if ( isset( $block['type'], $block['text'] ) && 'text' === $block['type'] ) {
				$text .= $block['text'];
			}
		}

		return array(
			'text'        => trim( $text ),
			'model'       => (string) ( $data['model'] ?? $body['model'] ),
			'stop_reason' => (string) ( $data['stop_reason'] ?? '' ),
			'usage'       => (array) ( $data['usage'] ?? array() ),
		);
	}
}
