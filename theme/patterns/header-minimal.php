<?php
/**
 * Title: Minimal header
 * Slug: aurelia/header-minimal
 * Categories: header
 * Block Types: core/template-part/header
 * Inserter: no
 * Description: Logo-only header used on checkout and landing pages.
 *
 * @package Aurelia
 */

?>
<!-- wp:group {"className":"au-header","style":{"spacing":{"padding":{"right":"var:preset|spacing|30","left":"var:preset|spacing|30"}}},"layout":{"type":"constrained","contentSize":"1280px"}} -->
<div class="wp-block-group au-header" style="padding-right:var(--wp--preset--spacing--30);padding-left:var(--wp--preset--spacing--30)"><!-- wp:group {"className":"au-header__inner","layout":{"type":"flex","flexWrap":"nowrap","justifyContent":"space-between"}} -->
<div class="wp-block-group au-header__inner"><!-- wp:group {"style":{"spacing":{"blockGap":"0.6rem"}},"layout":{"type":"flex","flexWrap":"nowrap"}} -->
<div class="wp-block-group"><!-- wp:site-logo {"width":44,"shouldSyncIcon":false} /-->

<!-- wp:site-title {"level":0} /--></div>
<!-- /wp:group -->

<!-- wp:paragraph {"fontSize":"x-small","style":{"typography":{"letterSpacing":"0.12em","textTransform":"uppercase"}}} -->
<p class="has-x-small-font-size" style="letter-spacing:0.12em;text-transform:uppercase"><?php esc_html_e( '🔒 Secure checkout', 'aurelia' ); ?></p>
<!-- /wp:paragraph --></div>
<!-- /wp:group --></div>
<!-- /wp:group -->
