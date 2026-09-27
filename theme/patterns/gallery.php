<?php
/**
 * Title: Instagram-style gallery
 * Slug: aurelia/gallery
 * Categories: aurelia-marketing, gallery
 * Keywords: instagram, gallery, social, ugc
 * Viewport Width: 1400
 * Description: Six-tile social gallery with a follow button.
 *
 * @package Aurelia
 */

$aurelia_tiles = array(
	array( 'gallery-1.webp', __( 'Gold bangles set with rubies', 'aurelia' ) ),
	array( 'gallery-2.webp', __( 'Vitamin C serum bottle', 'aurelia' ) ),
	array( 'gallery-3.webp', __( 'White leather sneaker', 'aurelia' ) ),
	array( 'gallery-4.webp', __( 'Sandalwood candle glowing', 'aurelia' ) ),
	array( 'gallery-5.webp', __( 'Green tea tin with leaves', 'aurelia' ) ),
	array( 'gallery-6.webp', __( 'Bluetooth speaker', 'aurelia' ) ),
);
?>
<!-- wp:group {"align":"full","style":{"spacing":{"margin":{"top":"0","bottom":"0"},"padding":{"top":"var:preset|spacing|60","bottom":"var:preset|spacing|60"}}},"layout":{"type":"constrained","contentSize":"1280px"}} -->
<div class="wp-block-group alignfull" style="margin-top:0;margin-bottom:0;padding-top:var(--wp--preset--spacing--60);padding-bottom:var(--wp--preset--spacing--60)"><!-- wp:group {"className":"au-section-head","style":{"spacing":{"margin":{"bottom":"var:preset|spacing|40"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group au-section-head" style="margin-bottom:var(--wp--preset--spacing--40)"><!-- wp:paragraph {"align":"center","className":"is-style-eyebrow"} -->
<p class="has-text-align-center is-style-eyebrow"><?php esc_html_e( '@yourstore', 'aurelia' ); ?></p>
<!-- /wp:paragraph -->

<!-- wp:heading {"textAlign":"center"} -->
<h2 class="wp-block-heading has-text-align-center"><?php esc_html_e( 'Seen on Instagram', 'aurelia' ); ?></h2>
<!-- /wp:heading --></div>
<!-- /wp:group -->

<!-- wp:group {"className":"au-gallery","style":{"spacing":{"blockGap":"0.75rem"}},"layout":{"type":"grid","minimumColumnWidth":"10rem"}} -->
<div class="wp-block-group au-gallery">
<?php foreach ( $aurelia_tiles as $aurelia_tile ) : ?>
<!-- wp:image {"sizeSlug":"full","linkDestination":"custom"} -->
<figure class="wp-block-image size-full"><a href="https://instagram.com/"><img src="<?php echo aurelia_image( $aurelia_tile[0] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in helper. ?>" alt="<?php echo esc_attr( $aurelia_tile[1] ); ?>"/></a></figure>
<!-- /wp:image -->
<?php endforeach; ?>
</div>
<!-- /wp:group -->

<!-- wp:buttons {"layout":{"type":"flex","justifyContent":"center"},"style":{"spacing":{"margin":{"top":"var:preset|spacing|40"}}}} -->
<div class="wp-block-buttons" style="margin-top:var(--wp--preset--spacing--40)"><!-- wp:button {"className":"is-style-outline"} -->
<div class="wp-block-button is-style-outline"><a class="wp-block-button__link wp-element-button" href="https://instagram.com/"><?php esc_html_e( 'Follow us on Instagram', 'aurelia' ); ?></a></div>
<!-- /wp:button --></div>
<!-- /wp:buttons --></div>
<!-- /wp:group -->
