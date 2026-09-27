<?php
/**
 * Title: Scrolling trust marquee
 * Slug: aurelia/marquee
 * Categories: aurelia-marketing
 * Keywords: marquee, ticker, trust, promise
 * Viewport Width: 1400
 * Description: An infinitely scrolling ribbon of store promises. Pauses on hover and stops for reduced-motion users.
 *
 * @package Aurelia
 */

$aurelia_items = array(
	__( 'Free shipping over ₹999', 'aurelia' ),
	__( 'Secure UPI & card payments', 'aurelia' ),
	__( 'Cash on delivery available', 'aurelia' ),
	__( 'Easy 15-day returns', 'aurelia' ),
	__( 'Order on WhatsApp', 'aurelia' ),
	__( 'Gift wrapping on request', 'aurelia' ),
);
?>
<!-- wp:group {"align":"full","className":"au-marquee","style":{"spacing":{"margin":{"top":"0","bottom":"0"},"padding":{"top":"1rem","bottom":"1rem"}},"typography":{"fontStyle":"italic"}},"backgroundColor":"primary-2","textColor":"accent-2","fontFamily":"heading","layout":{"type":"default"}} -->
<div class="wp-block-group alignfull au-marquee has-accent-2-color has-primary-2-background-color has-text-color has-background has-heading-font-family" style="margin-top:0;margin-bottom:0;padding-top:1rem;padding-bottom:1rem;font-style:italic"><!-- wp:group {"className":"au-marquee__track","layout":{"type":"default"},"fontSize":"medium"} -->
<div class="wp-block-group au-marquee__track has-medium-font-size">
<?php
foreach ( $aurelia_items as $aurelia_item ) {
	printf( "<!-- wp:paragraph -->\n<p>%s</p>\n<!-- /wp:paragraph -->\n", esc_html( $aurelia_item ) );
}
?>
</div>
<!-- /wp:group --></div>
<!-- /wp:group -->
