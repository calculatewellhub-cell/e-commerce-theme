<?php
/**
 * Title: Two promo banners
 * Slug: aurelia/promo-banners
 * Categories: aurelia-marketing, banner
 * Keywords: promo, banner, offer, sale
 * Viewport Width: 1400
 * Description: Two side-by-side image banners with an offer and a link.
 *
 * @package Aurelia
 */

$aurelia_banners = array(
	array( 'promo-fashion.webp', __( 'Wrap dress and leather tote on a blush background', 'aurelia' ), __( 'Fashion edit', 'aurelia' ), __( 'Linen, leather & easy layers', 'aurelia' ), __( 'Up to 30% off', 'aurelia' ), '/product-category/fashion/' ),
	array( 'promo-tech.webp', __( 'Headphones and a smartwatch on a navy background', 'aurelia' ), __( 'Tech week', 'aurelia' ), __( 'Sound & smart wearables', 'aurelia' ), __( 'Free fast delivery', 'aurelia' ), '/product-category/electronics/' ),
);
?>
<!-- wp:group {"align":"full","style":{"spacing":{"margin":{"top":"0","bottom":"0"},"padding":{"top":"var:preset|spacing|50","bottom":"var:preset|spacing|50"}}},"layout":{"type":"constrained","contentSize":"1280px"}} -->
<div class="wp-block-group alignfull" style="margin-top:0;margin-bottom:0;padding-top:var(--wp--preset--spacing--50);padding-bottom:var(--wp--preset--spacing--50)"><!-- wp:columns {"style":{"spacing":{"blockGap":{"left":"var:preset|spacing|30"}}}} -->
<div class="wp-block-columns">
<?php foreach ( $aurelia_banners as $aurelia_banner ) : ?>
<!-- wp:column -->
<div class="wp-block-column"><!-- wp:cover {"url":"<?php echo aurelia_image( $aurelia_banner[0] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in helper. ?>","alt":"<?php echo esc_attr( $aurelia_banner[1] ); ?>","dimRatio":30,"overlayColor":"primary","isUserOverlayColor":true,"minHeight":340,"contentPosition":"center left","className":"is-style-rounded-card","style":{"spacing":{"padding":{"right":"var:preset|spacing|40","left":"var:preset|spacing|40"}}},"textColor":"on-primary","layout":{"type":"constrained","contentSize":"320px","justifyContent":"left"}} -->
<div class="wp-block-cover has-custom-content-position is-position-center-left is-style-rounded-card has-on-primary-color has-text-color" style="padding-right:var(--wp--preset--spacing--40);padding-left:var(--wp--preset--spacing--40);min-height:340px"><span aria-hidden="true" class="wp-block-cover__background has-primary-background-color has-background-dim-30 has-background-dim"></span><img class="wp-block-cover__image-background" alt="<?php echo esc_attr( $aurelia_banner[1] ); ?>" src="<?php echo aurelia_image( $aurelia_banner[0] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in helper. ?>" data-object-fit="cover"/><div class="wp-block-cover__inner-container"><!-- wp:paragraph {"className":"is-style-eyebrow","textColor":"accent-2"} -->
<p class="is-style-eyebrow has-accent-2-color has-text-color"><?php echo esc_html( $aurelia_banner[2] ); ?></p>
<!-- /wp:paragraph -->

<!-- wp:heading {"fontSize":"x-large"} -->
<h2 class="wp-block-heading has-x-large-font-size"><?php echo esc_html( $aurelia_banner[3] ); ?></h2>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p><?php echo esc_html( $aurelia_banner[4] ); ?></p>
<!-- /wp:paragraph -->

<!-- wp:buttons -->
<div class="wp-block-buttons"><!-- wp:button {"className":"is-style-accent"} -->
<div class="wp-block-button is-style-accent"><a class="wp-block-button__link wp-element-button" href="<?php echo esc_url( home_url( $aurelia_banner[5] ) ); ?>"><?php esc_html_e( 'Shop now', 'aurelia' ); ?></a></div>
<!-- /wp:button --></div>
<!-- /wp:buttons --></div></div>
<!-- /wp:cover --></div>
<!-- /wp:column -->
<?php endforeach; ?>
</div>
<!-- /wp:columns --></div>
<!-- /wp:group -->
