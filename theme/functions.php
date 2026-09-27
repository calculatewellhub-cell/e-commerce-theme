<?php
/**
 * Aurelia theme functions.
 *
 * The theme only handles presentation: styles, scripts, block styles, pattern
 * categories and WooCommerce theme support. Store features (WhatsApp ordering,
 * AI concierge, 3D viewer, analytics...) live in the Aurelia Commerce plugin.
 *
 * @package Aurelia
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'AURELIA_VERSION', wp_get_theme( get_template() )->get( 'Version' ) );

/**
 * Theme setup.
 */
function aurelia_setup() {
	load_theme_textdomain( 'aurelia', get_template_directory() . '/languages' );

	add_theme_support( 'editor-styles' );
	add_editor_style( array( 'assets/css/theme.css', 'assets/css/woocommerce.css' ) );
	add_theme_support( 'responsive-embeds' );
	add_theme_support( 'post-thumbnails' );

	add_theme_support(
		'woocommerce',
		array(
			'thumbnail_image_width' => 600,
			'single_image_width'    => 900,
			'product_grid'          => array(
				'default_rows'    => 3,
				'min_rows'        => 1,
				'default_columns' => 4,
				'min_columns'     => 1,
				'max_columns'     => 6,
			),
		)
	);
	add_theme_support( 'wc-product-gallery-zoom' );
	add_theme_support( 'wc-product-gallery-lightbox' );
	add_theme_support( 'wc-product-gallery-slider' );
}
add_action( 'after_setup_theme', 'aurelia_setup' );

/**
 * Front-end styles and scripts. No jQuery.
 */
function aurelia_enqueue_assets() {
	$uri = get_template_directory_uri();

	wp_enqueue_style( 'aurelia-theme', $uri . '/assets/css/theme.css', array(), AURELIA_VERSION );
	if ( is_rtl() ) {
		wp_enqueue_style( 'aurelia-rtl', $uri . '/assets/css/rtl.css', array( 'aurelia-theme' ), AURELIA_VERSION );
	}
	if ( class_exists( 'WooCommerce' ) ) {
		wp_enqueue_style( 'aurelia-woocommerce', $uri . '/assets/css/woocommerce.css', array( 'aurelia-theme' ), AURELIA_VERSION );
	}

	wp_enqueue_script(
		'aurelia-theme',
		$uri . '/assets/js/theme.js',
		array(),
		AURELIA_VERSION,
		array(
			'strategy'  => 'defer',
			'in_footer' => true,
		)
	);
	wp_localize_script(
		'aurelia-theme',
		'aureliaTheme',
		array(
			'i18n' => array(
				'dark'     => __( 'Switch to dark mode', 'aurelia' ),
				'light'    => __( 'Switch to light mode', 'aurelia' ),
				'previous' => __( 'Previous products', 'aurelia' ),
				'next'     => __( 'Next products', 'aurelia' ),
				'days'     => __( 'Days', 'aurelia' ),
				'hours'    => __( 'Hours', 'aurelia' ),
				'minutes'  => __( 'Mins', 'aurelia' ),
				'seconds'  => __( 'Secs', 'aurelia' ),
				'ended'    => __( 'This offer has ended', 'aurelia' ),
			),
		)
	);
}
add_action( 'wp_enqueue_scripts', 'aurelia_enqueue_assets' );

/**
 * Apply the saved or system colour scheme before first paint to avoid a flash.
 */
function aurelia_color_scheme_script() {
	$script = "(function(){var d=document.documentElement,s;d.classList.add('js');try{s=localStorage.getItem('aurelia-scheme');}catch(e){}d.setAttribute('data-color-scheme',s==='dark'||s==='light'?s:'auto');})();";
	wp_print_inline_script_tag( $script );
}
add_action( 'wp_head', 'aurelia_color_scheme_script', 1 );

/**
 * Preload the active heading and body fonts (Core Web Vitals: faster LCP text).
 */
function aurelia_preload_fonts() {
	$families = wp_get_global_settings( array( 'typography', 'fontFamilies', 'theme' ) );
	if ( empty( $families ) || ! is_array( $families ) ) {
		return;
	}
	$seen = array();
	foreach ( $families as $family ) {
		if ( empty( $family['slug'] ) || ! in_array( $family['slug'], array( 'heading', 'body' ), true ) || empty( $family['fontFace'][0]['src'] ) ) {
			continue;
		}
		$src = (array) $family['fontFace'][0]['src'];
		$src = reset( $src );
		if ( ! is_string( $src ) || ! str_starts_with( $src, 'file:./' ) ) {
			continue;
		}
		$url = get_theme_file_uri( substr( $src, 7 ) );
		if ( isset( $seen[ $url ] ) ) {
			continue;
		}
		$seen[ $url ] = true;
		printf( '<link rel="preload" href="%s" as="font" type="font/woff2" crossorigin>' . "\n", esc_url( $url ) );
	}
}
add_action( 'wp_head', 'aurelia_preload_fonts', 2 );

/**
 * Block styles that need CSS beyond what theme.json style variations provide.
 */
function aurelia_register_block_styles() {
	$styles = array(
		'core/list'                      => array(
			'checklist' => __( 'Checklist', 'aurelia' ),
		),
		'core/navigation'                => array(
			'mega' => __( 'Mega menu', 'aurelia' ),
		),
		'core/cover'                     => array(
			'tilt-tile' => __( '3D tile', 'aurelia' ),
		),
		'core/button'                    => array(
			'ghost-light' => __( 'Ghost (on dark)', 'aurelia' ),
		),
		'core/group'                     => array(
			'reveal' => __( 'Reveal on scroll', 'aurelia' ),
		),
		'core/columns'                   => array(
			'steps' => __( 'Numbered steps', 'aurelia' ),
		),
		'core/details'                   => array(
			'faq' => __( 'FAQ', 'aurelia' ),
		),
		'woocommerce/product-collection' => array(
			'carousel' => __( 'Carousel', 'aurelia' ),
		),
	);
	foreach ( $styles as $block => $variations ) {
		foreach ( $variations as $name => $label ) {
			register_block_style(
				$block,
				array(
					'name'  => $name,
					'label' => $label,
				)
			);
		}
	}
}
add_action( 'init', 'aurelia_register_block_styles' );

/**
 * Pattern categories.
 */
function aurelia_register_pattern_categories() {
	$categories = array(
		'aurelia-hero'      => __( 'Aurelia: Hero', 'aurelia' ),
		'aurelia-shop'      => __( 'Aurelia: Shop', 'aurelia' ),
		'aurelia-marketing' => __( 'Aurelia: Marketing', 'aurelia' ),
		'aurelia-content'   => __( 'Aurelia: Content', 'aurelia' ),
		'aurelia-pages'     => __( 'Aurelia: Pages', 'aurelia' ),
	);
	foreach ( $categories as $slug => $label ) {
		register_block_pattern_category( $slug, array( 'label' => $label ) );
	}
}
add_action( 'init', 'aurelia_register_pattern_categories' );

/**
 * Theme image URL helper for patterns.
 *
 * @param string $file File name inside assets/images.
 * @return string
 */
function aurelia_image( $file ) {
	return esc_url( get_theme_file_uri( 'assets/images/' . $file ) );
}

/**
 * Shop URL helper for patterns: the WooCommerce shop page when available.
 *
 * @param string $path Optional path appended to the home URL instead.
 * @return string
 */
function aurelia_shop_url( $path = '' ) {
	if ( '' === $path && function_exists( 'wc_get_page_permalink' ) ) {
		return esc_url( wc_get_page_permalink( 'shop' ) );
	}
	return esc_url( home_url( '' === $path ? '/shop/' : $path ) );
}

/**
 * Aurelia's headers already contain the account and mini-cart blocks (inside a
 * pattern), so stop WooCommerce from auto-inserting duplicates next to the menu.
 *
 * @param string[]                        $hooked_blocks Hooked block types.
 * @param string                          $position      Relative position.
 * @param string                          $anchor_block  Anchor block type.
 * @param WP_Block_Template|WP_Post|array $context       Block context.
 * @return string[]
 */
function aurelia_skip_duplicate_hooked_blocks( $hooked_blocks, $position, $anchor_block, $context ) {
	if ( 'core/navigation' !== $anchor_block ) {
		return $hooked_blocks;
	}
	$remove = array( 'woocommerce/customer-account', 'woocommerce/mini-cart' );
	// Header content is delivered through the aurelia/header pattern.
	if ( is_array( $context ) && isset( $context['name'] ) && in_array( $context['name'], array( 'aurelia/header', 'aurelia/header-minimal' ), true ) ) {
		return array_values( array_diff( $hooked_blocks, $remove ) );
	}
	if ( $context instanceof WP_Block_Template && 'wp_template_part' === $context->type && get_stylesheet() === $context->theme && in_array( $context->slug, array( 'header', 'header-minimal' ), true ) ) {
		return array_values( array_diff( $hooked_blocks, $remove ) );
	}
	return $hooked_blocks;
}
add_filter( 'hooked_block_types', 'aurelia_skip_duplicate_hooked_blocks', 20, 4 );
