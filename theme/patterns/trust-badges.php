<?php
/**
 * Title: Trust badges
 * Slug: aurelia/trust-badges
 * Categories: aurelia-shop, aurelia-marketing
 * Keywords: trust, badges, shipping, returns, secure
 * Viewport Width: 1400
 * Description: Four icon badges: shipping, secure payments, returns and support.
 *
 * @package Aurelia
 */

$aurelia_badges = array(
	array( '<path d="M3 7h11v9H3zM14 10h4l3 3v3h-7z"/><circle cx="7" cy="17.5" r="1.8"/><circle cx="17" cy="17.5" r="1.8"/>', __( 'Fast, free shipping', 'aurelia' ), __( 'Free on orders over ₹999, dispatched in 48 hours', 'aurelia' ) ),
	array( '<path d="M12 3l8 3v6c0 4.5-3.4 8.3-8 9-4.6-.7-8-4.5-8-9V6z"/><path d="M8.5 12l2.5 2.5 4.5-5"/>', __( 'Secure payments', 'aurelia' ), __( 'UPI, cards, net banking and cash on delivery', 'aurelia' ) ),
	array( '<path d="M4 12a8 8 0 1 0 2.3-5.6"/><path d="M4 4v4h4"/>', __( 'Easy returns', 'aurelia' ), __( 'No-questions-asked 15-day returns', 'aurelia' ) ),
	array( '<path d="M20 12a8 8 0 0 1-11.6 7.1L4 20l1-4.2A8 8 0 1 1 20 12z"/><path d="M9 10h.01M12 10h.01M15 10h.01"/>', __( 'Real human support', 'aurelia' ), __( 'Chat on WhatsApp, 7 days a week', 'aurelia' ) ),
);
?>
<!-- wp:group {"align":"full","style":{"spacing":{"margin":{"top":"0","bottom":"0"},"padding":{"top":"var:preset|spacing|50","bottom":"var:preset|spacing|50"}}},"layout":{"type":"constrained","contentSize":"1280px"}} -->
<div class="wp-block-group alignfull" style="margin-top:0;margin-bottom:0;padding-top:var(--wp--preset--spacing--50);padding-bottom:var(--wp--preset--spacing--50)"><!-- wp:columns {"className":"au-trust","style":{"spacing":{"blockGap":{"left":"var:preset|spacing|30"}}}} -->
<div class="wp-block-columns au-trust">
<?php foreach ( $aurelia_badges as $aurelia_badge ) : ?>
<!-- wp:column -->
<div class="wp-block-column"><!-- wp:html -->
<svg width="34" height="34" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><?php echo $aurelia_badge[0]; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static SVG paths defined above. ?></svg>
<!-- /wp:html -->

<!-- wp:heading {"level":3} -->
<h3 class="wp-block-heading"><?php echo esc_html( $aurelia_badge[1] ); ?></h3>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p><?php echo esc_html( $aurelia_badge[2] ); ?></p>
<!-- /wp:paragraph --></div>
<!-- /wp:column -->
<?php endforeach; ?>
</div>
<!-- /wp:columns --></div>
<!-- /wp:group -->
