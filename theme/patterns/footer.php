<?php
/**
 * Title: Store footer
 * Slug: aurelia/footer
 * Categories: footer
 * Block Types: core/template-part/footer
 * Inserter: no
 * Description: Four-column footer with brand, shop links, customer care, contact and payment marks.
 *
 * @package Aurelia
 */

$aurelia_links = array(
	'shop'    => aurelia_shop_url(),
	'account' => function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'myaccount' ) : home_url( '/my-account/' ),
	'cart'    => function_exists( 'wc_get_cart_url' ) ? wc_get_cart_url() : home_url( '/cart/' ),
);
?>
<!-- wp:group {"align":"full","className":"au-footer","style":{"spacing":{"margin":{"top":"0","bottom":"0"},"padding":{"top":"var:preset|spacing|60","bottom":"var:preset|spacing|40"}},"elements":{"link":{"color":{"text":"var:preset|color|on-primary"}}}},"backgroundColor":"primary","textColor":"on-primary","layout":{"type":"constrained","contentSize":"1280px"}} -->
<div class="wp-block-group alignfull au-footer has-on-primary-color has-primary-background-color has-text-color has-background has-link-color" style="margin-top:0;margin-bottom:0;padding-top:var(--wp--preset--spacing--60);padding-bottom:var(--wp--preset--spacing--40)"><!-- wp:columns {"style":{"spacing":{"blockGap":{"left":"var:preset|spacing|50"}}}} -->
<div class="wp-block-columns"><!-- wp:column {"width":"34%"} -->
<div class="wp-block-column" style="flex-basis:34%"><!-- wp:site-title {"level":0,"style":{"typography":{"fontSize":"1.7rem"}}} /-->

<!-- wp:paragraph {"style":{"typography":{"lineHeight":"1.7"}},"fontSize":"small"} -->
<p class="has-small-font-size" style="line-height:1.7"><?php esc_html_e( 'Thoughtfully curated products, honest prices and real people behind every order. Shop online or message us on WhatsApp — we reply within minutes.', 'aurelia' ); ?></p>
<!-- /wp:paragraph -->

<!-- wp:social-links {"size":"has-normal-icon-size","className":"is-style-default","style":{"spacing":{"blockGap":{"left":"0.5rem"}}}} -->
<ul class="wp-block-social-links has-normal-icon-size is-style-default"><!-- wp:social-link {"url":"https://instagram.com/","service":"instagram"} /-->

<!-- wp:social-link {"url":"https://facebook.com/","service":"facebook"} /-->

<!-- wp:social-link {"url":"https://youtube.com/","service":"youtube"} /-->

<!-- wp:social-link {"url":"https://pinterest.com/","service":"pinterest"} /--></ul>
<!-- /wp:social-links --></div>
<!-- /wp:column -->

<!-- wp:column -->
<div class="wp-block-column"><!-- wp:heading {"fontSize":"x-small"} -->
<h2 class="wp-block-heading has-x-small-font-size"><?php esc_html_e( 'Shop', 'aurelia' ); ?></h2>
<!-- /wp:heading -->

<!-- wp:list {"style":{"typography":{"lineHeight":"2"}},"className":"is-style-default","fontSize":"small"} -->
<ul style="line-height:2" class="wp-block-list is-style-default has-small-font-size"><!-- wp:list-item -->
<li><a href="<?php echo esc_url( $aurelia_links['shop'] ); ?>"><?php esc_html_e( 'All products', 'aurelia' ); ?></a></li>
<!-- /wp:list-item -->

<!-- wp:list-item -->
<li><a href="<?php echo esc_url( home_url( '/product-category/jewellery/' ) ); ?>"><?php esc_html_e( 'Jewellery', 'aurelia' ); ?></a></li>
<!-- /wp:list-item -->

<!-- wp:list-item -->
<li><a href="<?php echo esc_url( home_url( '/product-category/fashion/' ) ); ?>"><?php esc_html_e( 'Fashion', 'aurelia' ); ?></a></li>
<!-- /wp:list-item -->

<!-- wp:list-item -->
<li><a href="<?php echo esc_url( home_url( '/product-category/electronics/' ) ); ?>"><?php esc_html_e( 'Electronics', 'aurelia' ); ?></a></li>
<!-- /wp:list-item -->

<!-- wp:list-item -->
<li><a href="<?php echo esc_url( home_url( '/product-category/beauty/' ) ); ?>"><?php esc_html_e( 'Beauty', 'aurelia' ); ?></a></li>
<!-- /wp:list-item -->

<!-- wp:list-item -->
<li><a href="<?php echo esc_url( home_url( '/product-category/home-living/' ) ); ?>"><?php esc_html_e( 'Home & living', 'aurelia' ); ?></a></li>
<!-- /wp:list-item --></ul>
<!-- /wp:list --></div>
<!-- /wp:column -->

<!-- wp:column -->
<div class="wp-block-column"><!-- wp:heading {"fontSize":"x-small"} -->
<h2 class="wp-block-heading has-x-small-font-size"><?php esc_html_e( 'Customer care', 'aurelia' ); ?></h2>
<!-- /wp:heading -->

<!-- wp:list {"style":{"typography":{"lineHeight":"2"}},"className":"is-style-default","fontSize":"small"} -->
<ul style="line-height:2" class="wp-block-list is-style-default has-small-font-size"><!-- wp:list-item -->
<li><a href="<?php echo esc_url( $aurelia_links['account'] ); ?>"><?php esc_html_e( 'My account', 'aurelia' ); ?></a></li>
<!-- /wp:list-item -->

<!-- wp:list-item -->
<li><a href="<?php echo esc_url( home_url( '/track-order/' ) ); ?>"><?php esc_html_e( 'Track your order', 'aurelia' ); ?></a></li>
<!-- /wp:list-item -->

<!-- wp:list-item -->
<li><a href="<?php echo esc_url( home_url( '/shipping-returns/' ) ); ?>"><?php esc_html_e( 'Shipping & returns', 'aurelia' ); ?></a></li>
<!-- /wp:list-item -->

<!-- wp:list-item -->
<li><a href="<?php echo esc_url( home_url( '/faq/' ) ); ?>"><?php esc_html_e( 'FAQ', 'aurelia' ); ?></a></li>
<!-- /wp:list-item -->

<!-- wp:list-item -->
<li><a href="<?php echo esc_url( home_url( '/contact/' ) ); ?>"><?php esc_html_e( 'Contact us', 'aurelia' ); ?></a></li>
<!-- /wp:list-item --></ul>
<!-- /wp:list --></div>
<!-- /wp:column -->

<!-- wp:column {"width":"26%"} -->
<div class="wp-block-column" style="flex-basis:26%"><!-- wp:heading {"fontSize":"x-small"} -->
<h2 class="wp-block-heading has-x-small-font-size"><?php esc_html_e( 'Visit & contact', 'aurelia' ); ?></h2>
<!-- /wp:heading -->

<!-- wp:paragraph {"style":{"typography":{"lineHeight":"1.9"}},"fontSize":"small"} -->
<p class="has-small-font-size" style="line-height:1.9"><?php esc_html_e( '12 Heritage Arcade, Zaveri Bazaar', 'aurelia' ); ?><br><?php esc_html_e( 'Mumbai, Maharashtra 400002', 'aurelia' ); ?><br><a href="tel:+910000000000">+91 00000 00000</a><br><a href="mailto:hello@example.com">hello@example.com</a><br><?php esc_html_e( 'Mon–Sat, 10:30–20:30', 'aurelia' ); ?></p>
<!-- /wp:paragraph --></div>
<!-- /wp:column --></div>
<!-- /wp:columns -->

<!-- wp:group {"className":"au-footer-bottom","style":{"spacing":{"padding":{"top":"var:preset|spacing|30"},"margin":{"top":"var:preset|spacing|50"}}},"layout":{"type":"flex","flexWrap":"wrap","justifyContent":"space-between"}} -->
<div class="wp-block-group au-footer-bottom" style="margin-top:var(--wp--preset--spacing--50);padding-top:var(--wp--preset--spacing--30)"><!-- wp:paragraph -->
<p>
<?php
/* translators: 1: current year, 2: site name. */
echo esc_html( sprintf( __( '© %1$s %2$s. All rights reserved.', 'aurelia' ), gmdate( 'Y' ), get_bloginfo( 'name' ) ) );
?>
</p>
<!-- /wp:paragraph -->

<!-- wp:html -->
<div class="au-payments" aria-label="<?php echo esc_attr__( 'Accepted payments', 'aurelia' ); ?>"><span>UPI</span><span>PhonePe</span><span>GPay</span><span>Visa</span><span>Mastercard</span><span>RuPay</span><span><?php esc_html_e( 'COD', 'aurelia' ); ?></span></div>
<!-- /wp:html --></div>
<!-- /wp:group --></div>
<!-- /wp:group -->
