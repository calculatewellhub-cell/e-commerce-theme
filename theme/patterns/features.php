<?php
/**
 * Title: Why shop with us
 * Slug: aurelia/features
 * Categories: aurelia-content, aurelia-marketing
 * Keywords: features, benefits, why us, promise
 * Viewport Width: 1400
 * Description: Dark section with four promise cards.
 *
 * @package Aurelia
 */

$aurelia_features = array(
	array( '<path d="M12 3l8 3v6c0 4.5-3.4 8.3-8 9-4.6-.7-8-4.5-8-9V6z"/>', __( 'Authentic, always', 'aurelia' ), __( 'Sourced directly from makers and authorised brands, with invoices and warranty.', 'aurelia' ) ),
	array( '<path d="M3 7h11v9H3zM14 10h4l3 3v3h-7z"/>', __( 'Insured delivery', 'aurelia' ), __( 'Every parcel is tracked and insured door to door across India.', 'aurelia' ) ),
	array( '<path d="M4 12a8 8 0 1 0 2.3-5.6M4 4v4h4"/>', __( 'Lifetime care', 'aurelia' ), __( 'Free exchanges, repairs guidance and honest after-sales support.', 'aurelia' ) ),
	array( '<path d="M12 3v3M12 18v3M3 12h3M18 12h3M5.6 5.6l2.1 2.1M16.3 16.3l2.1 2.1M5.6 18.4l2.1-2.1M16.3 7.7l2.1-2.1"/>', __( 'Personal concierge', 'aurelia' ), __( 'Ask our AI concierge anytime, or chat with a human stylist on WhatsApp.', 'aurelia' ) ),
);
?>
<!-- wp:group {"align":"full","className":"is-style-section-dark","style":{"spacing":{"margin":{"top":"0","bottom":"0"},"padding":{"top":"var:preset|spacing|60","bottom":"var:preset|spacing|60"}}},"layout":{"type":"constrained","contentSize":"1280px"}} -->
<div class="wp-block-group alignfull is-style-section-dark" style="margin-top:0;margin-bottom:0;padding-top:var(--wp--preset--spacing--60);padding-bottom:var(--wp--preset--spacing--60)"><!-- wp:group {"className":"au-section-head","style":{"spacing":{"margin":{"bottom":"var:preset|spacing|50"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group au-section-head" style="margin-bottom:var(--wp--preset--spacing--50)"><!-- wp:paragraph {"align":"center","className":"is-style-eyebrow"} -->
<p class="has-text-align-center is-style-eyebrow"><?php esc_html_e( 'Our promise', 'aurelia' ); ?></p>
<!-- /wp:paragraph -->

<!-- wp:heading {"textAlign":"center"} -->
<h2 class="wp-block-heading has-text-align-center"><?php esc_html_e( 'Why thousands shop with us', 'aurelia' ); ?></h2>
<!-- /wp:heading --></div>
<!-- /wp:group -->

<!-- wp:group {"style":{"spacing":{"blockGap":"var:preset|spacing|30"}},"layout":{"type":"grid","minimumColumnWidth":"14rem"}} -->
<div class="wp-block-group">
<?php foreach ( $aurelia_features as $aurelia_feature ) : ?>
<!-- wp:group {"className":"au-feature au-tilt au-reveal","style":{"spacing":{"padding":{"top":"var:preset|spacing|40","right":"var:preset|spacing|30","bottom":"var:preset|spacing|40","left":"var:preset|spacing|30"}}},"layout":{"type":"default"}} -->
<div class="wp-block-group au-feature au-tilt au-reveal" style="padding-top:var(--wp--preset--spacing--40);padding-right:var(--wp--preset--spacing--30);padding-bottom:var(--wp--preset--spacing--40);padding-left:var(--wp--preset--spacing--30)"><!-- wp:html -->
<svg width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><?php echo $aurelia_feature[0]; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static SVG paths defined above. ?></svg>
<!-- /wp:html -->

<!-- wp:heading {"level":3} -->
<h3 class="wp-block-heading"><?php echo esc_html( $aurelia_feature[1] ); ?></h3>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p><?php echo esc_html( $aurelia_feature[2] ); ?></p>
<!-- /wp:paragraph --></div>
<!-- /wp:group -->
<?php endforeach; ?>
</div>
<!-- /wp:group --></div>
<!-- /wp:group -->
