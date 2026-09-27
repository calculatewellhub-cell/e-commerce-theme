<?php
/**
 * Title: Minimal footer
 * Slug: aurelia/footer-minimal
 * Categories: footer
 * Block Types: core/template-part/footer
 * Inserter: no
 * Description: Single-line footer for checkout.
 *
 * @package Aurelia
 */

?>
<!-- wp:group {"align":"full","style":{"spacing":{"padding":{"top":"var:preset|spacing|30","bottom":"var:preset|spacing|30"}}},"layout":{"type":"constrained","contentSize":"1280px"}} -->
<div class="wp-block-group alignfull" style="padding-top:var(--wp--preset--spacing--30);padding-bottom:var(--wp--preset--spacing--30)"><!-- wp:paragraph {"align":"center","textColor":"contrast-2","fontSize":"x-small"} -->
<p class="has-text-align-center has-contrast-2-color has-text-color has-x-small-font-size">
<?php
/* translators: 1: current year, 2: site name. */
echo esc_html( sprintf( __( '© %1$s %2$s · Secure payments · Easy returns', 'aurelia' ), gmdate( 'Y' ), get_bloginfo( 'name' ) ) );
?>
</p>
<!-- /wp:paragraph --></div>
<!-- /wp:group -->
