<?php
/**
 * Title: How to order on WhatsApp
 * Slug: aurelia/whatsapp-steps
 * Categories: aurelia-marketing, aurelia-content
 * Keywords: whatsapp, how it works, steps, order
 * Viewport Width: 1400
 * Description: Three numbered steps explaining WhatsApp ordering.
 *
 * @package Aurelia
 */

$aurelia_steps = array(
	array( __( 'Pick your favourites', 'aurelia' ), __( 'Browse the store, choose sizes or shades and add to your bag — or tap "Order on WhatsApp" on any product.', 'aurelia' ) ),
	array( __( 'Send us the message', 'aurelia' ), __( 'WhatsApp opens with your items, quantities and total already written. Add your address and hit send.', 'aurelia' ) ),
	array( __( 'Confirm & relax', 'aurelia' ), __( 'We confirm stock and delivery, share a UPI or payment link, and send tracking the moment it ships.', 'aurelia' ) ),
);
?>
<!-- wp:group {"align":"full","className":"is-style-section-alt","style":{"spacing":{"margin":{"top":"0","bottom":"0"},"padding":{"top":"var:preset|spacing|60","bottom":"var:preset|spacing|60"}}},"layout":{"type":"constrained","contentSize":"1280px"}} -->
<div class="wp-block-group alignfull is-style-section-alt" style="margin-top:0;margin-bottom:0;padding-top:var(--wp--preset--spacing--60);padding-bottom:var(--wp--preset--spacing--60)"><!-- wp:group {"className":"au-section-head","style":{"spacing":{"margin":{"bottom":"var:preset|spacing|50"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group au-section-head" style="margin-bottom:var(--wp--preset--spacing--50)"><!-- wp:paragraph {"align":"center","className":"is-style-eyebrow"} -->
<p class="has-text-align-center is-style-eyebrow"><?php esc_html_e( 'How it works', 'aurelia' ); ?></p>
<!-- /wp:paragraph -->

<!-- wp:heading {"textAlign":"center"} -->
<h2 class="wp-block-heading has-text-align-center"><?php esc_html_e( 'Order on WhatsApp in 3 easy steps', 'aurelia' ); ?></h2>
<!-- /wp:heading --></div>
<!-- /wp:group -->

<!-- wp:columns {"align":"wide","className":"is-style-steps","style":{"spacing":{"blockGap":{"left":"var:preset|spacing|30"}}}} -->
<div class="wp-block-columns alignwide is-style-steps">
<?php foreach ( $aurelia_steps as $aurelia_step ) : ?>
<!-- wp:column -->
<div class="wp-block-column"><!-- wp:heading {"level":3} -->
<h3 class="wp-block-heading"><?php echo esc_html( $aurelia_step[0] ); ?></h3>
<!-- /wp:heading -->

<!-- wp:paragraph {"textColor":"contrast-2"} -->
<p class="has-contrast-2-color has-text-color"><?php echo esc_html( $aurelia_step[1] ); ?></p>
<!-- /wp:paragraph --></div>
<!-- /wp:column -->
<?php endforeach; ?>
</div>
<!-- /wp:columns --></div>
<!-- /wp:group -->
