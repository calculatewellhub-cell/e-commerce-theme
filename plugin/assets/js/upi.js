/**
 * Aurelia Commerce — UPI payment panel on the order-received page.
 */
( function () {
	'use strict';
	const cfg = window.aureliaUpi || {};
	const box = document.querySelector( '.au-upi' );
	if ( ! box ) {
		return;
	}

	// QR code for the exact amount (upi:// URI), drawn as crisp SVG.
	const target = box.querySelector( '.au-upi__qr-canvas' );
	if ( target && window.qrcode ) {
		const qr = window.qrcode( 0, 'M' );
		qr.addData( box.dataset.uri );
		qr.make();
		target.innerHTML = qr.createSvgTag( { cellSize: 6, margin: 2, scalable: true } );
	}

	box.querySelectorAll( '.au-upi__copy' ).forEach( ( btn ) => {
		btn.addEventListener( 'click', async () => {
			try {
				await navigator.clipboard.writeText( btn.dataset.copy );
				const label = btn.textContent;
				btn.textContent = cfg.copied || 'Copied';
				setTimeout( () => ( btn.textContent = label ), 1800 );
			} catch ( e ) {}
		} );
	} );

	const form = box.querySelector( '.au-upi__form' );
	if ( form ) {
		form.addEventListener( 'submit', async ( e ) => {
			e.preventDefault();
			const msg = form.querySelector( '.au-upi__msg' );
			const button = form.querySelector( 'button' );
			button.disabled = true;
			msg.textContent = cfg.sending || '…';
			try {
				const res = await fetch( cfg.endpoint, {
					method: 'POST',
					headers: { 'Content-Type': 'application/json' },
					body: JSON.stringify( { order_id: Number( form.dataset.order ), order_key: form.dataset.key, utr: form.elements.utr.value } ),
				} );
				const data = await res.json();
				if ( ! res.ok ) {
					throw new Error( data.message );
				}
				msg.textContent = data.message || '';
				form.elements.utr.readOnly = true;
			} catch ( err ) {
				msg.textContent = err.message || cfg.error;
				button.disabled = false;
			}
		} );
	}
}() );
