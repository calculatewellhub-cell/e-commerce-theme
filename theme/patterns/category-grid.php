<?php
/**
 * Title: Shop by category (3D tiles)
 * Slug: aurelia/category-grid
 * Categories: aurelia-shop
 * Keywords: categories, collections, grid, tiles
 * Viewport Width: 1400
 * Description: Six image tiles that tilt in 3D on hover and link to your product categories.
 *
 * @package Aurelia
 */

$aurelia_cats = array(
	array( 'jewellery', __( 'Jewellery', 'aurelia' ), 'cat-jewellery.webp', __( 'Emerald necklace on a dark background', 'aurelia' ) ),
	array( 'fashion', __( 'Fashion', 'aurelia' ), 'cat-fashion.webp', __( 'Linen wrap dress on a hanger', 'aurelia' ) ),
	array( 'electronics', __( 'Electronics', 'aurelia' ), 'cat-electronics.webp', __( 'Wireless headphones', 'aurelia' ) ),
	array( 'beauty', __( 'Beauty', 'aurelia' ), 'cat-beauty.webp', __( 'Pink perfume bottle', 'aurelia' ) ),
	array( 'grocery', __( 'Grocery', 'aurelia' ), 'cat-grocery.webp', __( 'Jar of raw honey', 'aurelia' ) ),
	array( 'home-living', __( 'Home & Living', 'aurelia' ), 'cat-home.webp', __( 'Terracotta table lamp', 'aurelia' ) ),
);
?>
<!-- wp:group {"anchor":"collections","align":"full","style":{"spacing":{"margin":{"top":"0","bottom":"0"},"padding":{"top":"var:preset|spacing|60","bottom":"var:preset|spacing|60"}}},"layout":{"type":"constrained","contentSize":"1280px"}} -->
<div id="collections" class="wp-block-group alignfull" style="margin-top:0;margin-bottom:0;padding-top:var(--wp--preset--spacing--60);padding-bottom:var(--wp--preset--spacing--60)"><!-- wp:group {"className":"au-section-head","style":{"spacing":{"margin":{"bottom":"var:preset|spacing|50"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group au-section-head" style="margin-bottom:var(--wp--preset--spacing--50)"><!-- wp:paragraph {"align":"center","className":"is-style-eyebrow"} -->
<p class="has-text-align-center is-style-eyebrow"><?php esc_html_e( 'Collections', 'aurelia' ); ?></p>
<!-- /wp:paragraph -->

<!-- wp:heading {"textAlign":"center"} -->
<h2 class="wp-block-heading has-text-align-center"><?php esc_html_e( 'Shop by category', 'aurelia' ); ?></h2>
<!-- /wp:heading --></div>
<!-- /wp:group -->

<!-- wp:group {"layout":{"type":"grid","minimumColumnWidth":"11rem"}} -->
<div class="wp-block-group">
<?php foreach ( $aurelia_cats as $aurelia_cat ) : ?>
<!-- wp:cover {"url":"<?php echo aurelia_image( $aurelia_cat[2] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in helper. ?>","alt":"<?php echo esc_attr( $aurelia_cat[3] ); ?>","dimRatio":20,"overlayColor":"primary","isUserOverlayColor":true,"contentPosition":"bottom left","className":"is-style-tilt-tile","textColor":"on-primary","layout":{"type":"constrained"}} -->
<div class="wp-block-cover has-custom-content-position is-position-bottom-left is-style-tilt-tile has-on-primary-color has-text-color"><span aria-hidden="true" class="wp-block-cover__background has-primary-background-color has-background-dim-20 has-background-dim"></span><img class="wp-block-cover__image-background" alt="<?php echo esc_attr( $aurelia_cat[3] ); ?>" src="<?php echo aurelia_image( $aurelia_cat[2] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in helper. ?>" data-object-fit="cover"/><div class="wp-block-cover__inner-container"><!-- wp:heading {"level":3,"fontSize":"large"} -->
<h3 class="wp-block-heading has-large-font-size"><a href="<?php echo esc_url( home_url( '/product-category/' . $aurelia_cat[0] . '/' ) ); ?>"><?php echo esc_html( $aurelia_cat[1] ); ?></a></h3>
<!-- /wp:heading --></div></div>
<!-- /wp:cover -->
<?php endforeach; ?>
</div>
<!-- /wp:group --></div>
<!-- /wp:group -->
