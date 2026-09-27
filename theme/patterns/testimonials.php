<?php
/**
 * Title: Customer testimonials
 * Slug: aurelia/testimonials
 * Categories: aurelia-content, testimonials
 * Keywords: reviews, testimonials, quotes, social proof
 * Viewport Width: 1400
 * Description: Three customer quotes with star ratings.
 *
 * @package Aurelia
 */

$aurelia_quotes = array(
	array( __( 'Ordered on WhatsApp at midnight and had a reply in minutes. The packaging felt like a gift, and the quality is better than the photos.', 'aurelia' ), __( 'Priya S., Mumbai', 'aurelia' ) ),
	array( __( 'Paid with UPI, got tracking the same day and the headphones arrived in two days. Genuinely the easiest online order I have made.', 'aurelia' ), __( 'Arjun M., Bengaluru', 'aurelia' ) ),
	array( __( 'The concierge chat helped me choose a gift for my mother within my budget. She has not stopped wearing it since.', 'aurelia' ), __( 'Fatima K., Hyderabad', 'aurelia' ) ),
);
?>
<!-- wp:group {"align":"full","className":"is-style-section-alt","style":{"spacing":{"margin":{"top":"0","bottom":"0"},"padding":{"top":"var:preset|spacing|60","bottom":"var:preset|spacing|60"}}},"layout":{"type":"constrained","contentSize":"1280px"}} -->
<div class="wp-block-group alignfull is-style-section-alt" style="margin-top:0;margin-bottom:0;padding-top:var(--wp--preset--spacing--60);padding-bottom:var(--wp--preset--spacing--60)"><!-- wp:group {"className":"au-section-head","style":{"spacing":{"margin":{"bottom":"var:preset|spacing|50"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group au-section-head" style="margin-bottom:var(--wp--preset--spacing--50)"><!-- wp:paragraph {"align":"center","className":"is-style-eyebrow"} -->
<p class="has-text-align-center is-style-eyebrow"><?php esc_html_e( 'Kind words', 'aurelia' ); ?></p>
<!-- /wp:paragraph -->

<!-- wp:heading {"textAlign":"center"} -->
<h2 class="wp-block-heading has-text-align-center"><?php esc_html_e( 'Loved across India', 'aurelia' ); ?></h2>
<!-- /wp:heading --></div>
<!-- /wp:group -->

<!-- wp:columns {"align":"wide","style":{"spacing":{"blockGap":{"left":"var:preset|spacing|30"}}}} -->
<div class="wp-block-columns alignwide">
<?php foreach ( $aurelia_quotes as $aurelia_quote ) : ?>
<!-- wp:column {"className":"au-quote au-reveal","style":{"spacing":{"padding":{"top":"var:preset|spacing|40","right":"var:preset|spacing|40","bottom":"var:preset|spacing|40","left":"var:preset|spacing|40"}}}} -->
<div class="wp-block-column au-quote au-reveal" style="padding-top:var(--wp--preset--spacing--40);padding-right:var(--wp--preset--spacing--40);padding-bottom:var(--wp--preset--spacing--40);padding-left:var(--wp--preset--spacing--40)"><!-- wp:paragraph {"className":"au-stars"} -->
<p class="au-stars">★★★★★</p>
<!-- /wp:paragraph -->

<!-- wp:quote -->
<blockquote class="wp-block-quote"><!-- wp:paragraph -->
<p><?php echo esc_html( $aurelia_quote[0] ); ?></p>
<!-- /wp:paragraph --></blockquote>
<!-- /wp:quote -->

<!-- wp:paragraph {"textColor":"contrast-2","fontSize":"small"} -->
<p class="has-contrast-2-color has-text-color has-small-font-size"><?php echo esc_html( $aurelia_quote[1] ); ?></p>
<!-- /wp:paragraph --></div>
<!-- /wp:column -->
<?php endforeach; ?>
</div>
<!-- /wp:columns --></div>
<!-- /wp:group -->
