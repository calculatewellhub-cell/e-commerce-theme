<?php
/**
 * Title: Hero with 3D showcase
 * Slug: aurelia/hero-3d
 * Categories: aurelia-hero, banner
 * Keywords: hero, 3d, ring, banner, luxury
 * Viewport Width: 1400
 * Description: Dark hero with headline, calls to action, store stats and a stage that the Aurelia Commerce plugin turns into an interactive 3D model. Without the plugin it shows the image.
 *
 * @package Aurelia
 */

?>
<!-- wp:group {"align":"full","className":"is-style-section-dark au-hero","style":{"spacing":{"margin":{"top":"0","bottom":"0"},"padding":{"top":"var:preset|spacing|50","bottom":"var:preset|spacing|50"}}},"layout":{"type":"constrained","contentSize":"1280px"}} -->
<div class="wp-block-group alignfull is-style-section-dark au-hero" style="margin-top:0;margin-bottom:0;padding-top:var(--wp--preset--spacing--50);padding-bottom:var(--wp--preset--spacing--50)"><!-- wp:columns {"verticalAlignment":"center","style":{"spacing":{"blockGap":{"left":"var:preset|spacing|50"}}}} -->
<div class="wp-block-columns are-vertically-aligned-center"><!-- wp:column {"verticalAlignment":"center","width":"52%"} -->
<div class="wp-block-column is-vertically-aligned-center" style="flex-basis:52%"><!-- wp:paragraph {"className":"is-style-eyebrow"} -->
<p class="is-style-eyebrow"><?php esc_html_e( 'New season collection', 'aurelia' ); ?></p>
<!-- /wp:paragraph -->

<!-- wp:heading {"level":1,"fontSize":"huge"} -->
<h1 class="wp-block-heading has-huge-font-size"><?php echo wp_kses( __( 'Made to be <em class="au-sheen-text">treasured</em>, every day', 'aurelia' ), array( 'em' => array( 'class' => array() ) ) ); ?></h1>
<!-- /wp:heading -->

<!-- wp:paragraph {"className":"au-lead"} -->
<p class="au-lead"><?php esc_html_e( 'Discover pieces crafted with care and priced honestly. Shop online with secure UPI payments, or order in one tap on WhatsApp and a real person will confirm every detail.', 'aurelia' ); ?></p>
<!-- /wp:paragraph -->

<!-- wp:buttons {"style":{"spacing":{"margin":{"top":"var:preset|spacing|40"}}}} -->
<div class="wp-block-buttons" style="margin-top:var(--wp--preset--spacing--40)"><!-- wp:button {"className":"is-style-accent"} -->
<div class="wp-block-button is-style-accent"><a class="wp-block-button__link wp-element-button" href="<?php echo aurelia_shop_url(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in helper. ?>"><?php esc_html_e( 'Shop the collection', 'aurelia' ); ?></a></div>
<!-- /wp:button -->

<!-- wp:button {"className":"is-style-ghost-light"} -->
<div class="wp-block-button is-style-ghost-light"><a class="wp-block-button__link wp-element-button" href="#collections"><?php esc_html_e( 'Explore categories', 'aurelia' ); ?></a></div>
<!-- /wp:button --></div>
<!-- /wp:buttons -->

<!-- wp:group {"className":"au-hero-stats","style":{"spacing":{"margin":{"top":"var:preset|spacing|50"}}},"layout":{"type":"flex","flexWrap":"wrap"}} -->
<div class="wp-block-group au-hero-stats" style="margin-top:var(--wp--preset--spacing--50)"><!-- wp:group {"layout":{"type":"default"}} -->
<div class="wp-block-group"><!-- wp:paragraph -->
<p><strong>25k+</strong><?php esc_html_e( 'Happy customers', 'aurelia' ); ?></p>
<!-- /wp:paragraph --></div>
<!-- /wp:group -->

<!-- wp:group {"layout":{"type":"default"}} -->
<div class="wp-block-group"><!-- wp:paragraph -->
<p><strong>4.9★</strong><?php esc_html_e( 'Average rating', 'aurelia' ); ?></p>
<!-- /wp:paragraph --></div>
<!-- /wp:group -->

<!-- wp:group {"layout":{"type":"default"}} -->
<div class="wp-block-group"><!-- wp:paragraph -->
<p><strong>48h</strong><?php esc_html_e( 'Dispatch', 'aurelia' ); ?></p>
<!-- /wp:paragraph --></div>
<!-- /wp:group --></div>
<!-- /wp:group --></div>
<!-- /wp:column -->

<!-- wp:column {"verticalAlignment":"center"} -->
<div class="wp-block-column is-vertically-aligned-center"><!-- wp:group {"className":"au-3d-stage","layout":{"type":"default"}} -->
<div class="wp-block-group au-3d-stage"><!-- wp:image {"sizeSlug":"full","linkDestination":"none"} -->
<figure class="wp-block-image size-full"><img src="<?php echo aurelia_image( 'hero-ring.webp' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in helper. ?>" alt="<?php esc_attr_e( 'A gold solitaire ring with a brilliant-cut diamond', 'aurelia' ); ?>"/></figure>
<!-- /wp:image --></div>
<!-- /wp:group --></div>
<!-- /wp:column --></div>
<!-- /wp:columns --></div>
<!-- /wp:group -->
