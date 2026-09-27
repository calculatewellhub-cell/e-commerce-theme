/* Aurelia settings screen: colour pickers + "Test connection". */
( function ( $ ) {
	'use strict';
	$( '.aurelia-color' ).wpColorPicker();
	const s = window.aureliaSettings || {};
	document.querySelectorAll( '.aurelia-test-ai' ).forEach( ( btn ) => {
		btn.addEventListener( 'click', async () => {
			const out = btn.parentElement.querySelector( '.aurelia-test-ai-result' );
			out.textContent = s.testing;
			try {
				const r = await wp.apiFetch( { path: '/aurelia/v1/admin/test-ai', method: 'POST' } );
				out.textContent = r.ok ? s.ok + ( r.model ? ' (' + r.model + ')' : '' ) : s.failed + r.message;
			} catch ( e ) {
				out.textContent = s.failed + e.message;
			}
		} );
	} );
}( window.jQuery ) );
