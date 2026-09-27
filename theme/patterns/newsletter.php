<?php
/**
 * Title: Newsletter band
 * Slug: aurelia/newsletter
 * Categories: aurelia-marketing, call-to-action
 * Keywords: newsletter, subscribe, email, signup
 * Viewport Width: 1400
 * Description: Rounded call-to-action band with an email signup form. Aurelia Commerce stores sign-ups and can forward them to your email tool; without it, link the form to your provider.
 *
 * @package Aurelia
 */

?>
<!-- wp:group {"align":"full","style":{"spacing":{"margin":{"top":"0","bottom":"0"},"padding":{"top":"var:preset|spacing|50","bottom":"var:preset|spacing|60"}}},"layout":{"type":"constrained","contentSize":"1280px"}} -->
<div class="wp-block-group alignfull" style="margin-top:0;margin-bottom:0;padding-top:var(--wp--preset--spacing--50);padding-bottom:var(--wp--preset--spacing--60)"><!-- wp:group {"className":"is-style-section-dark au-cta-band","style":{"spacing":{"padding":{"top":"var:preset|spacing|50","right":"var:preset|spacing|50","bottom":"var:preset|spacing|50","left":"var:preset|spacing|50"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group is-style-section-dark au-cta-band" style="padding-top:var(--wp--preset--spacing--50);padding-right:var(--wp--preset--spacing--50);padding-bottom:var(--wp--preset--spacing--50);padding-left:var(--wp--preset--spacing--50)"><!-- wp:columns {"verticalAlignment":"center","style":{"spacing":{"blockGap":{"left":"var:preset|spacing|50"}}}} -->
<div class="wp-block-columns are-vertically-aligned-center"><!-- wp:column {"verticalAlignment":"center","width":"56%"} -->
<div class="wp-block-column is-vertically-aligned-center" style="flex-basis:56%"><!-- wp:paragraph {"className":"is-style-eyebrow"} -->
<p class="is-style-eyebrow"><?php esc_html_e( 'The insider list', 'aurelia' ); ?></p>
<!-- /wp:paragraph -->

<!-- wp:heading {"fontSize":"x-large"} -->
<h2 class="wp-block-heading has-x-large-font-size"><?php esc_html_e( 'Get 10% off your first order', 'aurelia' ); ?></h2>
<!-- /wp:heading -->

<!-- wp:paragraph {"className":"au-lead"} -->
<p class="au-lead"><?php esc_html_e( 'New arrivals, private sales and styling tips. One email a week, never spam.', 'aurelia' ); ?></p>
<!-- /wp:paragraph --></div>
<!-- /wp:column -->

<!-- wp:column {"verticalAlignment":"center"} -->
<div class="wp-block-column is-vertically-aligned-center"><!-- wp:html -->
<form class="au-newsletter-form" method="post" action="#" data-aurelia-newsletter>
	<label class="screen-reader-text" for="au-newsletter-email"><?php esc_html_e( 'Email address', 'aurelia' ); ?></label>
	<input id="au-newsletter-email" type="email" name="email" required autocomplete="email" placeholder="<?php esc_attr_e( 'Your email address', 'aurelia' ); ?>">
	<button type="submit"><?php esc_html_e( 'Subscribe', 'aurelia' ); ?></button>
	<p class="au-form-note" aria-live="polite"><?php esc_html_e( 'By subscribing you agree to receive marketing emails. Unsubscribe anytime.', 'aurelia' ); ?></p>
</form>
<!-- /wp:html --></div>
<!-- /wp:column --></div>
<!-- /wp:columns --></div>
<!-- /wp:group --></div>
<!-- /wp:group -->
