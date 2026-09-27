<?php
/**
 * Title: Announcement bar
 * Slug: aurelia/announcement
 * Categories: aurelia-marketing
 * Block Types: core/template-part/header
 * Description: Slim promotional bar shown above the header.
 *
 * @package Aurelia
 */

?>
<!-- wp:group {"className":"au-announcement","style":{"spacing":{"padding":{"top":"0.55rem","bottom":"0.55rem"}}},"backgroundColor":"primary","textColor":"accent-2","layout":{"type":"constrained","contentSize":"1280px"}} -->
<div class="wp-block-group au-announcement has-accent-2-color has-primary-background-color has-text-color has-background" style="padding-top:0.55rem;padding-bottom:0.55rem"><!-- wp:paragraph {"align":"center","style":{"typography":{"letterSpacing":"0.14em","textTransform":"uppercase"}},"fontSize":"x-small"} -->
<p class="has-text-align-center has-x-small-font-size" style="letter-spacing:0.14em;text-transform:uppercase"><?php esc_html_e( 'Free shipping over ₹999 · Easy 15-day returns · Order on WhatsApp in one tap', 'aurelia' ); ?></p>
<!-- /wp:paragraph --></div>
<!-- /wp:group -->
