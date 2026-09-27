/* Swatch fields on attribute term screens. */
( function ( $ ) {
	'use strict';
	$( '.aurelia-color' ).wpColorPicker();
	document.addEventListener( 'click', ( e ) => {
		const btn = e.target.closest( '.aurelia-swatch-pick' );
		if ( ! btn ) {
			return;
		}
		const wrap = btn.parentElement;
		const frame = wp.media( { multiple: false, library: { type: 'image' } } );
		frame.on( 'select', () => {
			const a = frame.state().get( 'selection' ).first().toJSON();
			wrap.querySelector( 'input[type=hidden]' ).value = a.id;
			const img = document.createElement( 'img' );
			img.src = ( a.sizes && a.sizes.thumbnail ? a.sizes.thumbnail : a ).url;
			img.width = 40;
			img.height = 40;
			wrap.querySelector( '.aurelia-swatch-preview' ).replaceChildren( img );
		} );
		frame.open();
	} );
}( window.jQuery ) );
