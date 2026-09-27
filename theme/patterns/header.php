<?php
/**
 * Title: Store header
 * Slug: aurelia/header
 * Categories: header
 * Block Types: core/template-part/header
 * Inserter: no
 * Description: Sticky header with logo, mega menu, product search, account, dark mode and mini-cart.
 *
 * @package Aurelia
 */

?>
<!-- wp:group {"className":"au-header","style":{"spacing":{"padding":{"right":"var:preset|spacing|30","left":"var:preset|spacing|30"}}},"layout":{"type":"constrained","contentSize":"1280px"}} -->
<div class="wp-block-group au-header" style="padding-right:var(--wp--preset--spacing--30);padding-left:var(--wp--preset--spacing--30)"><!-- wp:group {"className":"au-header__inner","style":{"spacing":{"blockGap":"var:preset|spacing|30"}},"layout":{"type":"flex","flexWrap":"nowrap","justifyContent":"space-between"}} -->
<div class="wp-block-group au-header__inner"><!-- wp:group {"style":{"spacing":{"blockGap":"0.6rem"}},"layout":{"type":"flex","flexWrap":"nowrap"}} -->
<div class="wp-block-group"><!-- wp:site-logo {"width":44,"shouldSyncIcon":false} /-->

<!-- wp:site-title {"level":0} /--></div>
<!-- /wp:group -->

<!-- wp:navigation {"className":"is-style-mega","overlayBackgroundColor":"base","overlayTextColor":"contrast","layout":{"type":"flex","justifyContent":"center"},"style":{"spacing":{"blockGap":"var:preset|spacing|40"}}} /-->

<!-- wp:group {"className":"au-header__actions","layout":{"type":"flex","flexWrap":"nowrap"}} -->
<div class="wp-block-group au-header__actions"><!-- wp:search {"label":"<?php echo esc_attr__( 'Search products', 'aurelia' ); ?>","showLabel":false,"placeholder":"<?php echo esc_attr__( 'Search products…', 'aurelia' ); ?>","buttonText":"<?php echo esc_attr__( 'Search', 'aurelia' ); ?>","buttonPosition":"button-inside","buttonUseIcon":true,"query":{"post_type":"product"}} /-->

<!-- wp:html -->
<button type="button" class="au-icon-button au-scheme-toggle" aria-pressed="false" aria-label="<?php echo esc_attr__( 'Switch to dark mode', 'aurelia' ); ?>"><svg class="au-icon-moon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true" focusable="false"><path d="M21 12.8A9 9 0 1 1 11.2 3a7 7 0 0 0 9.8 9.8Z"/></svg><svg class="au-icon-sun" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true" focusable="false"><circle cx="12" cy="12" r="4.5"/><path d="M12 2v2M12 20v2M4.9 4.9l1.4 1.4M17.7 17.7l1.4 1.4M2 12h2M20 12h2M4.9 19.1l1.4-1.4M17.7 6.3l1.4-1.4"/></svg></button>
<!-- /wp:html -->

<!-- wp:woocommerce/customer-account {"displayStyle":"icon_only","iconStyle":"line","iconClass":"wc-block-customer-account__account-icon"} /-->

<!-- wp:woocommerce/mini-cart {"addToCartBehaviour":"open_drawer","hasHiddenPrice":true} /--></div>
<!-- /wp:group --></div>
<!-- /wp:group --></div>
<!-- /wp:group -->
