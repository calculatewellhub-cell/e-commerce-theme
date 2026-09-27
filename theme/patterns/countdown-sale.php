<?php
/**
 * Title: Countdown sale
 * Slug: aurelia/countdown-sale
 * Categories: aurelia-marketing
 * Keywords: countdown, timer, sale, deal, offer
 * Viewport Width: 1400
 * Description: Deal-of-the-day band with a live countdown. Set the end date in the Custom HTML block's data-end attribute (for example 2026-12-31T23:59:59+05:30); without it the timer counts down to midnight.
 *
 * @package Aurelia
 */

?>
<!-- wp:group {"align":"full","className":"is-style-section-dark","style":{"spacing":{"margin":{"top":"0","bottom":"0"},"padding":{"top":"var:preset|spacing|60","bottom":"var:preset|spacing|60"}}},"layout":{"type":"constrained","contentSize":"1280px"}} -->
<div class="wp-block-group alignfull is-style-section-dark" style="margin-top:0;margin-bottom:0;padding-top:var(--wp--preset--spacing--60);padding-bottom:var(--wp--preset--spacing--60)"><!-- wp:columns {"verticalAlignment":"center","style":{"spacing":{"blockGap":{"left":"var:preset|spacing|50"}}}} -->
<div class="wp-block-columns are-vertically-aligned-center"><!-- wp:column {"verticalAlignment":"center"} -->
<div class="wp-block-column is-vertically-aligned-center"><!-- wp:paragraph {"className":"is-style-eyebrow"} -->
<p class="is-style-eyebrow"><?php esc_html_e( 'Deal of the day', 'aurelia' ); ?></p>
<!-- /wp:paragraph -->

<!-- wp:heading {"fontSize":"xx-large"} -->
<h2 class="wp-block-heading has-xx-large-font-size"><?php esc_html_e( 'Extra 15% off everything, today only', 'aurelia' ); ?></h2>
<!-- /wp:heading -->

<!-- wp:paragraph {"className":"au-lead"} -->
<p class="au-lead"><?php echo wp_kses( __( 'Use code <strong>TODAY15</strong> at checkout. Valid on UPI, card and cash on delivery orders.', 'aurelia' ), array( 'strong' => array() ) ); ?></p>
<!-- /wp:paragraph -->

<!-- wp:html -->
<div class="au-countdown" data-end="" aria-label="<?php esc_attr_e( 'Time left for this offer', 'aurelia' ); ?>"><p><?php esc_html_e( 'Ends at midnight', 'aurelia' ); ?></p></div>
<!-- /wp:html -->

<!-- wp:buttons {"style":{"spacing":{"margin":{"top":"var:preset|spacing|40"}}}} -->
<div class="wp-block-buttons" style="margin-top:var(--wp--preset--spacing--40)"><!-- wp:button {"className":"is-style-accent"} -->
<div class="wp-block-button is-style-accent"><a class="wp-block-button__link wp-element-button" href="<?php echo aurelia_shop_url(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in helper. ?>"><?php esc_html_e( 'Grab the deal', 'aurelia' ); ?></a></div>
<!-- /wp:button --></div>
<!-- /wp:buttons --></div>
<!-- /wp:column -->

<!-- wp:column {"verticalAlignment":"center"} -->
<div class="wp-block-column is-vertically-aligned-center"><!-- wp:image {"sizeSlug":"full","linkDestination":"none","className":"is-style-rounded-card"} -->
<figure class="wp-block-image size-full is-style-rounded-card"><img src="<?php echo aurelia_image( 'promo-tech.webp' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in helper. ?>" alt="<?php esc_attr_e( 'Headphones and a smartwatch on a navy background', 'aurelia' ); ?>"/></figure>
<!-- /wp:image --></div>
<!-- /wp:column --></div>
<!-- /wp:columns --></div>
<!-- /wp:group -->
