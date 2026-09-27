<?php
/**
 * Title: Brand logos strip
 * Slug: aurelia/brand-logos
 * Categories: aurelia-content
 * Keywords: brands, logos, partners, as seen in
 * Viewport Width: 1400
 * Description: A row of brand or press names. Replace the text with logo images from your media library.
 *
 * @package Aurelia
 */

$aurelia_brands = array( 'Lumière', 'NORTHWIND', 'Kalaa & Co', 'Vervé', 'OAKLINE', 'Sūtra' );
?>
<!-- wp:group {"align":"full","className":"au-logos","style":{"spacing":{"margin":{"top":"0","bottom":"0"},"padding":{"top":"var:preset|spacing|50","bottom":"var:preset|spacing|50"}},"border":{"top":{"color":"var:preset|color|line","width":"1px"},"bottom":{"color":"var:preset|color|line","width":"1px"}}},"layout":{"type":"constrained","contentSize":"1280px"}} -->
<div class="wp-block-group alignfull au-logos" style="border-top-color:var(--wp--preset--color--line);border-top-width:1px;border-bottom-color:var(--wp--preset--color--line);border-bottom-width:1px;margin-top:0;margin-bottom:0;padding-top:var(--wp--preset--spacing--50);padding-bottom:var(--wp--preset--spacing--50)"><!-- wp:paragraph {"align":"center","className":"is-style-eyebrow"} -->
<p class="has-text-align-center is-style-eyebrow"><?php esc_html_e( 'Brands we stock', 'aurelia' ); ?></p>
<!-- /wp:paragraph -->

<!-- wp:group {"style":{"spacing":{"blockGap":"var:preset|spacing|50"}},"layout":{"type":"flex","flexWrap":"wrap","justifyContent":"space-around"}} -->
<div class="wp-block-group">
<?php foreach ( $aurelia_brands as $aurelia_brand ) : ?>
<!-- wp:paragraph -->
<p><?php echo esc_html( $aurelia_brand ); ?></p>
<!-- /wp:paragraph -->
<?php endforeach; ?>
</div>
<!-- /wp:group --></div>
<!-- /wp:group -->
