<?php
/**
 * Title: Shop archive layout
 * Slug: aurelia/template-shop
 * Inserter: no
 * Description: Shop, category and tag archive: page header, AJAX filters sidebar, sorting and product grid.
 *
 * @package Aurelia
 */

$aurelia_color_id = function_exists( 'wc_attribute_taxonomy_id_by_name' ) ? wc_attribute_taxonomy_id_by_name( 'color' ) : 0;
$aurelia_size_id  = function_exists( 'wc_attribute_taxonomy_id_by_name' ) ? wc_attribute_taxonomy_id_by_name( 'size' ) : 0;
?>
<!-- wp:group {"align":"full","className":"is-style-section-dark au-page-hero","style":{"spacing":{"margin":{"top":"0","bottom":"0"},"padding":{"top":"var:preset|spacing|50","bottom":"var:preset|spacing|50"}}},"layout":{"type":"constrained","contentSize":"1280px"}} -->
<div class="wp-block-group alignfull is-style-section-dark au-page-hero" style="margin-top:0;margin-bottom:0;padding-top:var(--wp--preset--spacing--50);padding-bottom:var(--wp--preset--spacing--50)"><!-- wp:woocommerce/breadcrumbs {"align":"wide"} /-->

<!-- wp:query-title {"type":"archive","showPrefix":false,"align":"wide","fontSize":"xx-large"} /-->

<!-- wp:term-description {"align":"wide","className":"au-lead"} /--></div>
<!-- /wp:group -->

<!-- wp:group {"align":"full","style":{"spacing":{"padding":{"top":"var:preset|spacing|50","bottom":"var:preset|spacing|60"}}},"layout":{"type":"constrained","contentSize":"1280px"}} -->
<div class="wp-block-group alignfull" style="padding-top:var(--wp--preset--spacing--50);padding-bottom:var(--wp--preset--spacing--60)"><!-- wp:woocommerce/store-notices /-->

<!-- wp:columns {"className":"au-shop","style":{"spacing":{"blockGap":{"left":"var:preset|spacing|50"}}}} -->
<div class="wp-block-columns au-shop"><!-- wp:column {"width":"260px","className":"au-shop__sidebar"} -->
<div class="wp-block-column au-shop__sidebar" style="flex-basis:260px"><!-- wp:woocommerce/product-filters -->
<div class="wp-block-woocommerce-product-filters wc-block-product-filters"><!-- wp:heading {"style":{"margin":{"top":"0","bottom":"0"},"spacing":{"margin":{"top":"0","bottom":"0"}}},"fontSize":"large"} -->
<h2 class="wp-block-heading has-large-font-size" style="margin-top:0;margin-bottom:0"><?php esc_html_e( 'Filters', 'aurelia' ); ?></h2>
<!-- /wp:heading -->

<!-- wp:woocommerce/product-filter-active -->
<div class="wp-block-woocommerce-product-filter-active"><!-- wp:woocommerce/product-filter-removable-chips -->
<div class="wp-block-woocommerce-product-filter-removable-chips wc-block-product-filter-removable-chips"></div>
<!-- /wp:woocommerce/product-filter-removable-chips -->

<!-- wp:woocommerce/product-filter-clear-button -->
<!-- wp:buttons {"layout":{"type":"flex","verticalAlignment":"stretched"}} -->
<div class="wp-block-buttons"><!-- wp:button {"className":"wc-block-product-filter-clear-button is-style-outline","style":{"border":{"width":"1px"},"typography":{"textDecoration":"none"},"outline":"none","fontSize":"medium","spacing":{"padding":{"left":"8px","right":"8px","top":"5px","bottom":"5px"}}}} -->
<div class="wp-block-button wc-block-product-filter-clear-button is-style-outline"><a class="wp-block-button__link wp-element-button" style="border-width:1px;padding-top:5px;padding-right:8px;padding-bottom:5px;padding-left:8px;text-decoration:none"><?php esc_html_e( 'Clear filters', 'aurelia' ); ?></a></div>
<!-- /wp:button --></div>
<!-- /wp:buttons -->
<!-- /wp:woocommerce/product-filter-clear-button --></div>
<!-- /wp:woocommerce/product-filter-active -->

<!-- wp:woocommerce/product-filter-price -->
<div class="wp-block-woocommerce-product-filter-price"><!-- wp:heading {"level":3,"style":{"spacing":{"margin":{"bottom":"0.625rem","top":"0"}}}} -->
<h3 class="wp-block-heading" style="margin-top:0;margin-bottom:0.625rem"><?php esc_html_e( 'Price', 'aurelia' ); ?></h3>
<!-- /wp:heading -->

<!-- wp:woocommerce/product-filter-price-slider -->
<div class="wp-block-woocommerce-product-filter-price-slider wc-block-product-filter-price-slider"></div>
<!-- /wp:woocommerce/product-filter-price-slider --></div>
<!-- /wp:woocommerce/product-filter-price -->

<!-- wp:woocommerce/product-filter-taxonomy -->
<div class="wp-block-woocommerce-product-filter-taxonomy"><!-- wp:heading {"level":3,"style":{"spacing":{"margin":{"bottom":"0.625rem","top":"0"}}}} -->
<h3 class="wp-block-heading" style="margin-top:0;margin-bottom:0.625rem"><?php esc_html_e( 'Category', 'aurelia' ); ?></h3>
<!-- /wp:heading -->

<!-- wp:woocommerce/product-filter-checkbox-list -->
<div class="wp-block-woocommerce-product-filter-checkbox-list wc-block-product-filter-checkbox-list"></div>
<!-- /wp:woocommerce/product-filter-checkbox-list --></div>
<!-- /wp:woocommerce/product-filter-taxonomy -->
<?php if ( $aurelia_color_id ) : ?>

<!-- wp:woocommerce/product-filter-attribute {"attributeId":<?php echo (int) $aurelia_color_id; ?>,"displayStyle":"woocommerce/product-filter-chips"} -->
<div class="wp-block-woocommerce-product-filter-attribute"><!-- wp:heading {"level":3,"style":{"spacing":{"margin":{"bottom":"0.625rem","top":"0"}}}} -->
<h3 class="wp-block-heading" style="margin-top:0;margin-bottom:0.625rem"><?php esc_html_e( 'Colour', 'aurelia' ); ?></h3>
<!-- /wp:heading -->

<!-- wp:woocommerce/product-filter-chips -->
<div class="wp-block-woocommerce-product-filter-chips wc-block-product-filter-chips"></div>
<!-- /wp:woocommerce/product-filter-chips --></div>
<!-- /wp:woocommerce/product-filter-attribute -->
<?php endif; ?>
<?php if ( $aurelia_size_id ) : ?>

<!-- wp:woocommerce/product-filter-attribute {"attributeId":<?php echo (int) $aurelia_size_id; ?>,"displayStyle":"woocommerce/product-filter-chips"} -->
<div class="wp-block-woocommerce-product-filter-attribute"><!-- wp:heading {"level":3,"style":{"spacing":{"margin":{"bottom":"0.625rem","top":"0"}}}} -->
<h3 class="wp-block-heading" style="margin-top:0;margin-bottom:0.625rem"><?php esc_html_e( 'Size', 'aurelia' ); ?></h3>
<!-- /wp:heading -->

<!-- wp:woocommerce/product-filter-chips -->
<div class="wp-block-woocommerce-product-filter-chips wc-block-product-filter-chips"></div>
<!-- /wp:woocommerce/product-filter-chips --></div>
<!-- /wp:woocommerce/product-filter-attribute -->
<?php endif; ?>

<!-- wp:woocommerce/product-filter-rating -->
<div class="wp-block-woocommerce-product-filter-rating"><!-- wp:heading {"level":3,"style":{"spacing":{"margin":{"bottom":"0.625rem","top":"0"}}}} -->
<h3 class="wp-block-heading" style="margin-top:0;margin-bottom:0.625rem"><?php esc_html_e( 'Rating', 'aurelia' ); ?></h3>
<!-- /wp:heading -->

<!-- wp:woocommerce/product-filter-checkbox-list -->
<div class="wp-block-woocommerce-product-filter-checkbox-list wc-block-product-filter-checkbox-list"></div>
<!-- /wp:woocommerce/product-filter-checkbox-list --></div>
<!-- /wp:woocommerce/product-filter-rating -->

<!-- wp:woocommerce/product-filter-status -->
<div class="wp-block-woocommerce-product-filter-status"><!-- wp:heading {"level":3,"style":{"spacing":{"margin":{"bottom":"0.625rem","top":"0"}}}} -->
<h3 class="wp-block-heading" style="margin-top:0;margin-bottom:0.625rem"><?php esc_html_e( 'Availability', 'aurelia' ); ?></h3>
<!-- /wp:heading -->

<!-- wp:woocommerce/product-filter-checkbox-list -->
<div class="wp-block-woocommerce-product-filter-checkbox-list wc-block-product-filter-checkbox-list"></div>
<!-- /wp:woocommerce/product-filter-checkbox-list --></div>
<!-- /wp:woocommerce/product-filter-status --></div>
<!-- /wp:woocommerce/product-filters --></div>
<!-- /wp:column -->

<!-- wp:column {"className":"au-shop__main"} -->
<div class="wp-block-column au-shop__main"><!-- wp:group {"className":"au-shop__toolbar","layout":{"type":"flex","flexWrap":"wrap","justifyContent":"space-between"}} -->
<div class="wp-block-group au-shop__toolbar"><!-- wp:woocommerce/product-results-count /-->

<!-- wp:woocommerce/catalog-sorting /--></div>
<!-- /wp:group -->

<!-- wp:woocommerce/product-collection {"queryId":0,"query":{"woocommerceAttributes":[],"woocommerceStockStatus":["instock","outofstock","onbackorder"],"taxQuery":{},"isProductCollectionBlock":true,"perPage":12,"pages":0,"offset":0,"postType":"product","order":"asc","orderBy":"title","author":"","search":"","exclude":[],"sticky":"","inherit":true},"tagName":"div","dimensions":{"widthType":"fill","fixedWidth":""},"displayLayout":{"type":"flex","columns":3,"shrinkColumns":true},"queryContextIncludes":["collection"],"className":"au-shop__grid"} -->
<div class="wp-block-woocommerce-product-collection au-shop__grid"><!-- wp:woocommerce/product-template -->
<!-- wp:woocommerce/product-image {"showSaleBadge":false,"isDescendentOfQueryLoop":true,"aspectRatio":"1"} -->
<!-- wp:woocommerce/product-sale-badge {"isDescendentOfQueryLoop":true,"align":"left"} /-->
<!-- /wp:woocommerce/product-image -->

<!-- wp:woocommerce/product-rating {"isDescendentOfQueryLoop":true,"fontSize":"x-small"} /-->

<!-- wp:post-title {"level":2,"isLink":true,"fontSize":"large","__woocommerceNamespace":"woocommerce/product-collection/product-title"} /-->

<!-- wp:woocommerce/product-price {"isDescendentOfQueryLoop":true,"fontSize":"medium"} /-->

<!-- wp:woocommerce/product-button {"isDescendentOfQueryLoop":true,"fontSize":"small"} /-->
<!-- /wp:woocommerce/product-template -->

<!-- wp:query-pagination {"layout":{"type":"flex","justifyContent":"center"}} -->
<!-- wp:query-pagination-previous /-->

<!-- wp:query-pagination-numbers /-->

<!-- wp:query-pagination-next /-->
<!-- /wp:query-pagination -->

<!-- wp:woocommerce/product-collection-no-results -->
<!-- wp:group {"layout":{"type":"flex","orientation":"vertical","justifyContent":"center","flexWrap":"wrap"}} -->
<div class="wp-block-group"><!-- wp:pattern {"slug":"woocommerce/no-products-found-clear-filters"} /--></div>
<!-- /wp:group -->
<!-- /wp:woocommerce/product-collection-no-results --></div>
<!-- /wp:woocommerce/product-collection --></div>
<!-- /wp:column --></div>
<!-- /wp:columns --></div>
<!-- /wp:group -->
