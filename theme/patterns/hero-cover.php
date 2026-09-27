<?php
/**
 * Title: Full-width image hero
 * Slug: aurelia/hero-cover
 * Categories: aurelia-hero, banner
 * Keywords: hero, cover, banner, sale
 * Viewport Width: 1400
 * Description: Full-bleed image hero with centred headline and two buttons. Works for any product category.
 *
 * @package Aurelia
 */

?>
<!-- wp:cover {"url":"<?php echo aurelia_image( 'hero-banner.webp' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in helper. ?>","alt":"<?php esc_attr_e( 'A perfume bottle and a smartwatch on a dark background', 'aurelia' ); ?>","dimRatio":50,"overlayColor":"primary","isUserOverlayColor":true,"minHeight":78,"minHeightUnit":"vh","contentPosition":"center left","align":"full","style":{"spacing":{"margin":{"top":"0","bottom":"0"},"padding":{"right":"var:preset|spacing|40","left":"var:preset|spacing|40"}}},"textColor":"on-primary","layout":{"type":"constrained","contentSize":"1280px"}} -->
<div class="wp-block-cover alignfull has-custom-content-position is-position-center-left has-on-primary-color has-text-color" style="margin-top:0;margin-bottom:0;padding-right:var(--wp--preset--spacing--40);padding-left:var(--wp--preset--spacing--40);min-height:78vh"><span aria-hidden="true" class="wp-block-cover__background has-primary-background-color has-background-dim"></span><img class="wp-block-cover__image-background" alt="<?php esc_attr_e( 'A perfume bottle and a smartwatch on a dark background', 'aurelia' ); ?>" src="<?php echo aurelia_image( 'hero-banner.webp' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in helper. ?>" data-object-fit="cover"/><div class="wp-block-cover__inner-container"><!-- wp:group {"style":{"spacing":{"blockGap":"var:preset|spacing|30"}},"layout":{"type":"constrained","contentSize":"620px","justifyContent":"left"}} -->
<div class="wp-block-group"><!-- wp:paragraph {"className":"is-style-eyebrow"} -->
<p class="is-style-eyebrow"><?php esc_html_e( 'The festive edit', 'aurelia' ); ?></p>
<!-- /wp:paragraph -->

<!-- wp:heading {"level":1,"fontSize":"huge"} -->
<h1 class="wp-block-heading has-huge-font-size"><?php esc_html_e( 'Gifts they will actually love', 'aurelia' ); ?></h1>
<!-- /wp:heading -->

<!-- wp:paragraph {"className":"au-lead"} -->
<p class="au-lead"><?php esc_html_e( 'Up to 30% off across fashion, beauty, tech and home. Free gift wrapping on every order this week.', 'aurelia' ); ?></p>
<!-- /wp:paragraph -->

<!-- wp:buttons -->
<div class="wp-block-buttons"><!-- wp:button {"className":"is-style-accent"} -->
<div class="wp-block-button is-style-accent"><a class="wp-block-button__link wp-element-button" href="<?php echo aurelia_shop_url(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in helper. ?>"><?php esc_html_e( 'Shop the sale', 'aurelia' ); ?></a></div>
<!-- /wp:button -->

<!-- wp:button {"className":"is-style-ghost-light"} -->
<div class="wp-block-button is-style-ghost-light"><a class="wp-block-button__link wp-element-button" href="#collections"><?php esc_html_e( 'Browse categories', 'aurelia' ); ?></a></div>
<!-- /wp:button --></div>
<!-- /wp:buttons --></div>
<!-- /wp:group --></div></div>
<!-- /wp:cover -->
