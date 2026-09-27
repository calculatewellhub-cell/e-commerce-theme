<?php
/**
 * Image → video studio: turns product photos into Reels, Shorts and feed videos
 * in the browser (canvas + MediaRecorder) and saves them to the Media Library.
 *
 * @package Aurelia_Commerce
 */

namespace Aurelia\Commerce;

defined( 'ABSPATH' ) || exit;

/**
 * Video studio.
 */
class Video_Studio {

	/**
	 * Hooks.
	 */
	public function init() {
		add_action( 'admin_menu', array( $this, 'menu' ), 30 );
	}

	/**
	 * Admin page.
	 */
	public function menu() {
		add_submenu_page( Admin::SLUG, __( 'Video studio', 'aurelia-commerce' ), __( 'Video studio', 'aurelia-commerce' ), 'upload_files', 'aurelia-video-studio', array( $this, 'render' ) );
	}

	/**
	 * Render the studio shell (app in admin-studio.js).
	 */
	public function render() {
		wp_enqueue_media();
		wp_enqueue_script( 'aurelia-admin-studio', AURELIA_COMMERCE_URL . 'assets/js/admin-studio.js', array( 'wp-api-fetch' ), AURELIA_COMMERCE_VERSION, true );
		wp_localize_script(
			'aurelia-admin-studio',
			'aureliaStudio',
			array(
				'store'  => Settings::store_name(),
				'site'   => preg_replace( '#^https?://#', '', home_url() ),
				'social' => admin_url( 'admin.php?page=aurelia-social' ),
				'fonts'  => array(
					'heading' => '"Cormorant Garamond", Georgia, serif',
					'body'    => 'Jost, Arial, sans-serif',
				),
				'i18n'   => array(
					'slides'       => __( '1 · Slides', 'aurelia-commerce' ),
					'style'        => __( '2 · Style', 'aurelia-commerce' ),
					'addProduct'   => __( '+ Add product image…', 'aurelia-commerce' ),
					'fromLibrary'  => __( 'Add from Media Library', 'aurelia-commerce' ),
					'upload'       => __( 'Upload photos', 'aurelia-commerce' ),
					'title'        => __( 'Slide title', 'aurelia-commerce' ),
					'subtitle'     => __( 'Price or tagline', 'aurelia-commerce' ),
					'up'           => __( 'Move up', 'aurelia-commerce' ),
					'down'         => __( 'Move down', 'aurelia-commerce' ),
					'remove'       => __( 'Remove', 'aurelia-commerce' ),
					'format'       => __( 'Format', 'aurelia-commerce' ),
					'reel'         => __( '9:16 Reel / Story / Short', 'aurelia-commerce' ),
					'square'       => __( '1:1 Feed post', 'aurelia-commerce' ),
					'landscape'    => __( '16:9 YouTube / Facebook', 'aurelia-commerce' ),
					'transition'   => __( 'Transition', 'aurelia-commerce' ),
					'zoom'         => __( 'Ken Burns zoom', 'aurelia-commerce' ),
					'fade'         => __( 'Cross-fade', 'aurelia-commerce' ),
					'slide'        => __( 'Slide', 'aurelia-commerce' ),
					'shine'        => __( 'Shine sweep', 'aurelia-commerce' ),
					'seconds'      => __( 'Seconds per slide', 'aurelia-commerce' ),
					'music'        => __( 'Background music (optional)', 'aurelia-commerce' ),
					'outro'        => __( 'Closing line', 'aurelia-commerce' ),
					'cta'          => __( 'Call to action', 'aurelia-commerce' ),
					'accent'       => __( 'Accent colour', 'aurelia-commerce' ),
					'background'   => __( 'Background colour', 'aurelia-commerce' ),
					'preview'      => __( 'live preview', 'aurelia-commerce' ),
					'render'       => __( '🎬 Render video', 'aurelia-commerce' ),
					'rendering'    => __( 'Rendering…', 'aurelia-commerce' ),
					'download'     => __( 'Download', 'aurelia-commerce' ),
					'save'         => __( 'Save to Media Library', 'aurelia-commerce' ),
					'saving'       => __( 'Saving…', 'aurelia-commerce' ),
					'saved'        => __( 'Saved to Media Library ✓', 'aurelia-commerce' ),
					'schedule'     => __( 'Schedule as social post →', 'aurelia-commerce' ),
					'noRecorder'   => __( 'Your browser cannot record video. Please use the latest Chrome, Edge or Safari.', 'aurelia-commerce' ),
					'loadFailed'   => __( 'Could not load an image: ', 'aurelia-commerce' ),
					'intro'        => __( 'Turn product photos into branded Reels, Shorts and Stories. Rendering happens in your browser; the finished video is saved to your Media Library.', 'aurelia-commerce' ),
					'ctaDefault'   => __( 'Order on WhatsApp', 'aurelia-commerce' ),
					/* translators: %s: store name. */
					'outroDefault' => sprintf( __( '%s · Link in bio', 'aurelia-commerce' ), Settings::store_name() ),
				),
			)
		);
		echo '<div class="wrap aurelia-admin"><h1>' . esc_html__( 'Image → video studio', 'aurelia-commerce' ) . '</h1><div id="aurelia-studio-app"></div></div>';
	}
}
