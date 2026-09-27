/**
 * Aurelia Commerce — UPI payment method for the WooCommerce Checkout block.
 * No build step: uses the globals WooCommerce Blocks provides.
 */
( function () {
	'use strict';
	const registry = window.wc && window.wc.wcBlocksRegistry;
	const settings = window.wc && window.wc.wcSettings;
	const { createElement: h } = window.wp.element;
	const { decodeEntities } = window.wp.htmlEntities;
	if ( ! registry || ! settings ) {
		return;
	}
	const data = settings.getSetting( 'aurelia_upi_data', {} );
	const title = decodeEntities( data.title || 'UPI' );
	const Content = () => h( 'p', { className: 'au-upi-blocks-desc' }, decodeEntities( data.description || '' ) );
	const Label = ( props ) => {
		const { PaymentMethodLabel } = props.components;
		return h(
			'span',
			{ className: 'au-upi-blocks-label' },
			h( PaymentMethodLabel, { text: title } ),
			data.icon ? h( 'img', { src: data.icon, alt: '', width: 44, height: 18, style: { marginInlineStart: '8px', verticalAlign: 'middle' } } ) : null
		);
	};
	registry.registerPaymentMethod( {
		name: 'aurelia_upi',
		label: h( Label ),
		content: h( Content ),
		edit: h( Content ),
		canMakePayment: () => true,
		ariaLabel: title,
		supports: { features: data.supports || [ 'products' ] },
	} );
}() );
