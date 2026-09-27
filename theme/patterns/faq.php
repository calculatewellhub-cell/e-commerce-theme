<?php
/**
 * Title: FAQ accordion
 * Slug: aurelia/faq
 * Categories: aurelia-content, text
 * Keywords: faq, questions, accordion, help
 * Viewport Width: 1400
 * Description: Frequently asked questions as accessible Details blocks. Aurelia Commerce turns them into FAQPage structured data automatically.
 *
 * @package Aurelia
 */

$aurelia_faqs = array(
	array( __( 'How long does delivery take?', 'aurelia' ), __( 'Orders are dispatched within 48 hours. Metro cities usually receive them in 2–4 days and the rest of India in 3–7 days. You will get a tracking link by SMS, email and WhatsApp.', 'aurelia' ) ),
	array( __( 'Which payment methods do you accept?', 'aurelia' ), __( 'UPI (PhonePe, Google Pay, Paytm and any UPI app), debit and credit cards, net banking and cash on delivery. You can also place your order on WhatsApp and pay by UPI.', 'aurelia' ) ),
	array( __( 'Can I order on WhatsApp?', 'aurelia' ), __( 'Yes. Tap "Order on WhatsApp" on any product or in your bag. We receive your items, sizes and total instantly and a real person confirms availability, delivery and payment with you.', 'aurelia' ) ),
	array( __( 'What is your return policy?', 'aurelia' ), __( 'Unused items in original packaging can be returned within 15 days of delivery for a full refund or exchange. Hygiene products and personalised items are final sale.', 'aurelia' ) ),
	array( __( 'Is cash on delivery available?', 'aurelia' ), __( 'Cash on delivery is available on most pin codes for orders up to ₹50,000. You can check at checkout.', 'aurelia' ) ),
);
?>
<!-- wp:group {"align":"full","style":{"spacing":{"margin":{"top":"0","bottom":"0"},"padding":{"top":"var:preset|spacing|60","bottom":"var:preset|spacing|60"}}},"layout":{"type":"constrained","contentSize":"820px"}} -->
<div class="wp-block-group alignfull" style="margin-top:0;margin-bottom:0;padding-top:var(--wp--preset--spacing--60);padding-bottom:var(--wp--preset--spacing--60)"><!-- wp:group {"className":"au-section-head","style":{"spacing":{"margin":{"bottom":"var:preset|spacing|40"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group au-section-head" style="margin-bottom:var(--wp--preset--spacing--40)"><!-- wp:paragraph {"align":"center","className":"is-style-eyebrow"} -->
<p class="has-text-align-center is-style-eyebrow"><?php esc_html_e( 'Good to know', 'aurelia' ); ?></p>
<!-- /wp:paragraph -->

<!-- wp:heading {"textAlign":"center"} -->
<h2 class="wp-block-heading has-text-align-center"><?php esc_html_e( 'Frequently asked questions', 'aurelia' ); ?></h2>
<!-- /wp:heading --></div>
<!-- /wp:group -->
<?php foreach ( $aurelia_faqs as $aurelia_faq ) : ?>

<!-- wp:details {"className":"is-style-faq"} -->
<details class="wp-block-details is-style-faq"><summary><?php echo esc_html( $aurelia_faq[0] ); ?></summary><!-- wp:paragraph -->
<p><?php echo esc_html( $aurelia_faq[1] ); ?></p>
<!-- /wp:paragraph --></details>
<!-- /wp:details -->
<?php endforeach; ?></div>
<!-- /wp:group -->
