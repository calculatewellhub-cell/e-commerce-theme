<?php
/**
 * Reviews with photos.
 *
 * @package Aurelia_Commerce
 */

namespace Aurelia\Commerce;

defined( 'ABSPATH' ) || exit;

/**
 * Review photos.
 */
class Reviews {

	const MAX_FILES = 3;
	const MAX_BYTES = 5242880;

	/**
	 * Hooks.
	 */
	public function init() {
		if ( ! Settings::on( 'review_photos' ) ) {
			return;
		}
		add_filter( 'woocommerce_product_review_comment_form_args', array( $this, 'field' ) );
		add_action( 'comment_post', array( $this, 'handle_upload' ), 10, 3 );
		add_action( 'woocommerce_review_after_comment_text', array( $this, 'display' ) );
	}

	/**
	 * Add the file input to the review form.
	 *
	 * @param array $args Comment form args.
	 * @return array
	 */
	public function field( $args ) {
		$args['comment_field'] .= sprintf(
			'<p class="comment-form-photos"><label for="aurelia-review-photos">%1$s</label><input type="file" id="aurelia-review-photos" name="aurelia_review_photos[]" accept="image/jpeg,image/png,image/webp" multiple data-max="%2$d"><small>%3$s</small>%4$s</p>',
			esc_html__( 'Add photos (optional)', 'aurelia-commerce' ),
			self::MAX_FILES,
			/* translators: %d: max photos. */
			esc_html( sprintf( __( 'Up to %d images, 5 MB each. JPG, PNG or WebP.', 'aurelia-commerce' ), self::MAX_FILES ) ),
			wp_nonce_field( 'aurelia_review_photos', 'aurelia_review_photos_nonce', true, false )
		);
		return $args;
	}

	/**
	 * Store uploaded photos as attachments on the product.
	 *
	 * @param int        $comment_id Comment ID.
	 * @param int|string $approved   Approval status.
	 * @param array      $data       Comment data.
	 */
	public function handle_upload( $comment_id, $approved, $data ) {
		if ( empty( $_FILES['aurelia_review_photos']['name'][0] ) || 'spam' === $approved || 'product' !== get_post_type( (int) $data['comment_post_ID'] ) ) {
			return;
		}
		if ( ! isset( $_POST['aurelia_review_photos_nonce'] ) || ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['aurelia_review_photos_nonce'] ) ), 'aurelia_review_photos' ) ) {
			return;
		}
		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/media.php';
		require_once ABSPATH . 'wp-admin/includes/image.php';

		$files   = $_FILES['aurelia_review_photos']; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput -- validated per file below.
		$allowed = array(
			'jpg|jpeg|jpe' => 'image/jpeg',
			'png'          => 'image/png',
			'webp'         => 'image/webp',
		);
		$ids     = array();
		$count   = min( self::MAX_FILES, count( (array) $files['name'] ) );
		for ( $i = 0; $i < $count; $i++ ) {
			if ( UPLOAD_ERR_OK !== (int) $files['error'][ $i ] || (int) $files['size'][ $i ] > self::MAX_BYTES ) {
				continue;
			}
			$check = wp_check_filetype_and_ext( $files['tmp_name'][ $i ], sanitize_file_name( $files['name'][ $i ] ), $allowed );
			if ( empty( $check['type'] ) || ! str_starts_with( $check['type'], 'image/' ) ) {
				continue;
			}
			$_FILES['aurelia_single_photo'] = array(
				'name'     => sanitize_file_name( $files['name'][ $i ] ),
				'type'     => $check['type'],
				'tmp_name' => $files['tmp_name'][ $i ],
				'error'    => 0,
				'size'     => (int) $files['size'][ $i ],
			);
			$id = media_handle_upload(
				'aurelia_single_photo',
				(int) $data['comment_post_ID'],
				array(),
				array(
					'test_form' => false,
					'mimes'     => $allowed,
				)
			);
			if ( ! is_wp_error( $id ) ) {
				$ids[] = (int) $id;
			}
		}
		unset( $_FILES['aurelia_single_photo'] );
		if ( $ids ) {
			update_comment_meta( $comment_id, '_aurelia_photos', $ids );
		}
	}

	/**
	 * Show photos under an approved review.
	 *
	 * @param \WP_Comment $comment Comment.
	 */
	public function display( $comment ) {
		$ids = array_filter( array_map( 'absint', (array) get_comment_meta( $comment->comment_ID, '_aurelia_photos', true ) ) );
		if ( ! $ids || '1' !== (string) $comment->comment_approved ) {
			return;
		}
		echo '<ul class="au-review-photos">';
		foreach ( $ids as $id ) {
			$full = wp_get_attachment_image_url( $id, 'large' );
			if ( $full ) {
				printf( '<li><a href="%1$s" target="_blank" rel="noopener">%2$s</a></li>', esc_url( $full ), wp_get_attachment_image( $id, 'thumbnail', false, array( 'loading' => 'lazy', 'alt' => esc_attr__( 'Customer photo', 'aurelia-commerce' ) ) ) );
			}
		}
		echo '</ul>';
	}
}
