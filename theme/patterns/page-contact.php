<?php
/**
 * Title: Contact page
 * Slug: aurelia/page-contact
 * Categories: aurelia-pages
 * Block Types: core/post-content
 * Post Types: page
 * Keywords: contact, whatsapp, address, map
 * Viewport Width: 1400
 * Description: Contact details, opening hours and quick links to WhatsApp, phone and email.
 *
 * @package Aurelia
 */

?>
<!-- wp:group {"align":"full","className":"is-style-section-dark","style":{"spacing":{"margin":{"top":"0","bottom":"0"},"padding":{"top":"var:preset|spacing|60","bottom":"var:preset|spacing|60"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull is-style-section-dark" style="margin-top:0;margin-bottom:0;padding-top:var(--wp--preset--spacing--60);padding-bottom:var(--wp--preset--spacing--60)"><!-- wp:paragraph {"align":"center","className":"is-style-eyebrow"} -->
<p class="has-text-align-center is-style-eyebrow"><?php esc_html_e( 'We are here to help', 'aurelia' ); ?></p>
<!-- /wp:paragraph -->

<!-- wp:heading {"textAlign":"center","level":1,"fontSize":"xx-large"} -->
<h1 class="wp-block-heading has-text-align-center has-xx-large-font-size"><?php esc_html_e( 'Contact us', 'aurelia' ); ?></h1>
<!-- /wp:heading -->

<!-- wp:paragraph {"align":"center","className":"au-lead"} -->
<p class="has-text-align-center au-lead"><?php esc_html_e( 'The fastest way to reach us is WhatsApp — we usually reply within 10 minutes during opening hours.', 'aurelia' ); ?></p>
<!-- /wp:paragraph --></div>
<!-- /wp:group -->

<!-- wp:group {"align":"full","style":{"spacing":{"padding":{"top":"var:preset|spacing|60","bottom":"var:preset|spacing|60"}}},"layout":{"type":"constrained","contentSize":"1100px"}} -->
<div class="wp-block-group alignfull" style="padding-top:var(--wp--preset--spacing--60);padding-bottom:var(--wp--preset--spacing--60)"><!-- wp:columns {"style":{"spacing":{"blockGap":{"left":"var:preset|spacing|30"}}}} -->
<div class="wp-block-columns"><!-- wp:column {"className":"is-style-card"} -->
<div class="wp-block-column is-style-card"><!-- wp:heading {"level":3} -->
<h3 class="wp-block-heading"><?php esc_html_e( 'WhatsApp', 'aurelia' ); ?></h3>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p><?php esc_html_e( 'Orders, sizing, video calls and custom requests.', 'aurelia' ); ?></p>
<!-- /wp:paragraph -->

<!-- wp:paragraph -->
<p><a href="https://wa.me/919876543210">+91 98765 43210</a></p>
<!-- /wp:paragraph --></div>
<!-- /wp:column -->

<!-- wp:column {"className":"is-style-card"} -->
<div class="wp-block-column is-style-card"><!-- wp:heading {"level":3} -->
<h3 class="wp-block-heading"><?php esc_html_e( 'Email', 'aurelia' ); ?></h3>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p><?php esc_html_e( 'Invoices, returns and business enquiries.', 'aurelia' ); ?></p>
<!-- /wp:paragraph -->

<!-- wp:paragraph -->
<p><a href="mailto:hello@example.com">hello@example.com</a></p>
<!-- /wp:paragraph --></div>
<!-- /wp:column -->

<!-- wp:column {"className":"is-style-card"} -->
<div class="wp-block-column is-style-card"><!-- wp:heading {"level":3} -->
<h3 class="wp-block-heading"><?php esc_html_e( 'Visit the store', 'aurelia' ); ?></h3>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p><?php esc_html_e( '12 Heritage Arcade, Zaveri Bazaar, Mumbai 400002', 'aurelia' ); ?></p>
<!-- /wp:paragraph -->

<!-- wp:paragraph -->
<p><?php esc_html_e( 'Mon–Sat, 10:30–20:30', 'aurelia' ); ?></p>
<!-- /wp:paragraph --></div>
<!-- /wp:column --></div>
<!-- /wp:columns --></div>
<!-- /wp:group -->

<!-- wp:pattern {"slug":"aurelia/faq"} /-->
