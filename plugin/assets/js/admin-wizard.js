/* Aurelia setup wizard: step-by-step demo import with progress. */
( function () {
	'use strict';
	const W = window.aureliaWizard || {};
	const btn = document.getElementById( 'aurelia-demo-import' );
	const removeBtn = document.getElementById( 'aurelia-demo-remove' );
	const bar = document.getElementById( 'aurelia-demo-progress' );
	const log = document.getElementById( 'aurelia-demo-log' );
	const line = ( text ) => {
		log.hidden = false;
		const p = document.createElement( 'div' );
		p.textContent = text;
		log.append( p );
		log.scrollTop = log.scrollHeight;
	};
	btn?.addEventListener( 'click', async () => {
		btn.disabled = true;
		bar.hidden = false;
		line( W.working );
		let step = '';
		try {
			do {
				const r = await wp.apiFetch( { path: '/aurelia/v1/demo/import', method: 'POST', data: { step } } );
				line( '✓ ' + r.message );
				bar.value = r.progress;
				step = r.next;
			} while ( step );
			line( W.done );
			window.location.href = W.next;
		} catch ( e ) {
			line( '✗ ' + e.message );
			btn.disabled = false;
		}
	} );
	removeBtn?.addEventListener( 'click', async () => {
		if ( ! window.confirm( W.confirm ) ) { // eslint-disable-line no-alert
			return;
		}
		removeBtn.disabled = true;
		const r = await wp.apiFetch( { path: '/aurelia/v1/demo/remove', method: 'POST' } );
		line( r.message || W.removed );
	} );
}() );
