<?php
/**
 * Title: Product assurance badges
 * Slug: aurelia/hidden-product-assurance
 * Inserter: no
 *
 * @package Aurelia
 */

$aurelia_marks = array(
	array( '<path d="M12 3l8 3v6c0 4.5-3.4 8.3-8 9-4.6-.7-8-4.5-8-9V6z"/><path d="M8.5 12l2.5 2.5 4.5-5"/>', __( 'Secure payment', 'aurelia' ) ),
	array( '<path d="M3 7h11v9H3zM14 10h4l3 3v3h-7z"/><circle cx="7" cy="17.5" r="1.8"/><circle cx="17" cy="17.5" r="1.8"/>', __( 'Fast delivery', 'aurelia' ) ),
	array( '<path d="M4 12a8 8 0 1 0 2.3-5.6"/><path d="M4 4v4h4"/>', __( '15-day returns', 'aurelia' ) ),
);
?>
<!-- wp:html -->
<ul class="au-assurance" role="list">
<?php foreach ( $aurelia_marks as $aurelia_mark ) : ?>
	<li><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><?php echo $aurelia_mark[0]; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static SVG paths defined above. ?></svg><span><?php echo esc_html( $aurelia_mark[1] ); ?></span></li>
<?php endforeach; ?>
</ul>
<!-- /wp:html -->
