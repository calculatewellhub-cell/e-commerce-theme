/**
 * Aurelia Commerce — product page enhancements.
 * Swatches, quantity buttons, sticky add-to-cart, sale countdown, size guide,
 * frequently bought together.
 */
( function () {
	'use strict';

	const P = window.aureliaProduct || {};
	const C = window.aureliaCommerce || {};
	const T = P.i18n || {};
	const form = document.querySelector( 'form.cart' );
	const reduced = window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches;

	/* Swatches ----------------------------------------------------------- */
	if ( P.swatches && form && form.classList.contains( 'variations_form' ) ) {
		Object.entries( P.swatches ).forEach( ( [ name, cfg ] ) => {
			const select = form.querySelector( 'select[name="' + name + '"]' );
			if ( ! select ) {
				return;
			}
			const label = form.querySelector( 'label[for="' + select.id + '"]' );
			const group = document.createElement( 'div' );
			group.className = 'au-swatches au-swatches--' + cfg.type;
			group.setAttribute( 'role', 'radiogroup' );
			if ( label ) {
				label.id = label.id || select.id + '-label';
				group.setAttribute( 'aria-labelledby', label.id );
			}
			const chosen = document.createElement( 'span' );
			chosen.className = 'au-swatch-chosen';
			if ( label ) {
				label.after( chosen );
			}

			const buttons = [];
			Object.entries( cfg.options ).forEach( ( [ slug, opt ] ) => {
				if ( ! select.querySelector( 'option[value="' + CSS.escape( slug ) + '"]' ) ) {
					return;
				}
				const b = document.createElement( 'button' );
				b.type = 'button';
				b.className = 'au-swatch';
				b.dataset.value = slug;
				b.setAttribute( 'role', 'radio' );
				b.setAttribute( 'aria-checked', 'false' );
				b.setAttribute( 'aria-label', opt.name );
				b.title = opt.name;
				if ( cfg.type === 'color' && opt.color ) {
					b.style.setProperty( '--swatch', opt.color );
					b.classList.add( 'is-color' );
				} else if ( cfg.type === 'image' && opt.image ) {
					const img = document.createElement( 'img' );
					img.src = opt.image;
					img.alt = '';
					b.append( img );
					b.classList.add( 'is-image' );
				} else {
					b.textContent = opt.name;
				}
				b.addEventListener( 'click', () => {
					if ( b.classList.contains( 'is-unavailable' ) ) {
						return;
					}
					select.value = select.value === slug ? '' : slug;
					select.dispatchEvent( new Event( 'change', { bubbles: true } ) );
					paint();
				} );
				buttons.push( b );
				group.append( b );
			} );
			if ( ! buttons.length ) {
				return;
			}
			// Arrow-key navigation inside the radio group.
			group.addEventListener( 'keydown', ( e ) => {
				const i = buttons.indexOf( document.activeElement );
				if ( i < 0 || ! [ 'ArrowRight', 'ArrowLeft', 'ArrowDown', 'ArrowUp' ].includes( e.key ) ) {
					return;
				}
				e.preventDefault();
				const dir = e.key === 'ArrowRight' || e.key === 'ArrowDown' ? 1 : -1;
				buttons[ ( i + dir + buttons.length ) % buttons.length ].focus();
			} );

			select.classList.add( 'au-visually-hidden' );
			select.setAttribute( 'tabindex', '-1' );
			select.setAttribute( 'aria-hidden', 'true' );
			select.after( group );

			function paint() {
				const available = new Set( Array.from( select.options ).filter( ( o ) => o.value && ! o.disabled ).map( ( o ) => o.value ) );
				buttons.forEach( ( b ) => {
					const on = select.value === b.dataset.value;
					b.setAttribute( 'aria-checked', on ? 'true' : 'false' );
					b.tabIndex = on || ( ! select.value && b === buttons[ 0 ] ) ? 0 : -1;
					const off = ! available.has( b.dataset.value );
					b.classList.toggle( 'is-unavailable', off );
					b.setAttribute( 'aria-disabled', off ? 'true' : 'false' );
				} );
				const current = cfg.options[ select.value ];
				chosen.textContent = current ? current.name : '';
			}
			select.addEventListener( 'change', paint );
			new MutationObserver( paint ).observe( select, { childList: true, subtree: true, attributes: true } );
			paint();
		} );
	}

	/* Quantity buttons --------------------------------------------------- */
	if ( P.qtyButtons ) {
		document.querySelectorAll( '.au-pdp .quantity, form.cart .quantity' ).forEach( ( wrap ) => {
			const input = wrap.querySelector( 'input.qty' );
			if ( ! input || input.type === 'hidden' || wrap.querySelector( '.au-qty-btn' ) ) {
				return;
			}
			const make = ( dir ) => {
				const b = document.createElement( 'button' );
				b.type = 'button';
				b.className = 'au-qty-btn';
				b.textContent = dir > 0 ? '+' : '−';
				b.setAttribute( 'aria-label', dir > 0 ? C.i18n?.increase || '+' : C.i18n?.decrease || '−' );
				b.addEventListener( 'click', () => {
					const step = Number( input.step ) || 1;
					const min = input.min !== '' ? Number( input.min ) : 1;
					const max = input.max !== '' ? Number( input.max ) : Infinity;
					input.value = String( Math.min( max, Math.max( min, ( Number( input.value ) || 0 ) + dir * step ) ) );
					input.dispatchEvent( new Event( 'change', { bubbles: true } ) );
				} );
				return b;
			};
			input.before( make( -1 ) );
			input.after( make( 1 ) );
		} );
	}

	/* Sticky add-to-cart bar -------------------------------------------- */
	const bar = document.querySelector( '.au-sticky-cart' );
	const realButton = form && form.querySelector( '.single_add_to_cart_button' );
	if ( P.sticky && bar && realButton && 'IntersectionObserver' in window ) {
		new IntersectionObserver( ( [ entry ] ) => {
			bar.hidden = entry.isIntersecting || entry.boundingClientRect.top > 0;
			document.body.classList.toggle( 'has-sticky-cart', ! bar.hidden );
		} ).observe( realButton );
		bar.querySelector( '.au-sticky-cart__btn' ).addEventListener( 'click', () => {
			if ( form.classList.contains( 'variations_form' ) && ! Number( form.querySelector( 'input[name=variation_id]' )?.value ) ) {
				form.scrollIntoView( { behavior: reduced ? 'auto' : 'smooth', block: 'center' } );
				form.querySelector( '.au-swatch, select' )?.focus();
				return;
			}
			realButton.click();
		} );
	}

	/* Sale countdown ------------------------------------------------------ */
	document.querySelectorAll( '.au-sale-timer[data-end]' ).forEach( ( el ) => {
		const end = Date.parse( el.dataset.end );
		const out = el.querySelector( 'strong' );
		const tick = () => {
			let s = Math.max( 0, Math.floor( ( end - Date.now() ) / 1000 ) );
			if ( ! s ) {
				el.remove();
				return;
			}
			const d = Math.floor( s / 86400 );
			s -= d * 86400;
			const h = Math.floor( s / 3600 );
			s -= h * 3600;
			const m = Math.floor( s / 60 );
			s -= m * 60;
			out.textContent = ( d ? d + T.d + ' ' : '' ) + h + T.h + ' ' + String( m ).padStart( 2, '0' ) + T.m + ' ' + String( s ).padStart( 2, '0' ) + T.s;
			setTimeout( tick, 1000 );
		};
		tick();
	} );

	/* Size guide ---------------------------------------------------------- */
	const sizeBtn = document.querySelector( '.au-size-guide-btn' );
	const sizeDlg = document.getElementById( 'au-size-guide' );
	if ( sizeBtn && sizeDlg && typeof sizeDlg.showModal === 'function' ) {
		document.body.append( sizeDlg );
		sizeBtn.addEventListener( 'click', () => sizeDlg.showModal() );
		sizeDlg.querySelector( '.au-dialog__close' ).addEventListener( 'click', () => sizeDlg.close() );
		sizeDlg.addEventListener( 'click', ( e ) => e.target === sizeDlg && sizeDlg.close() );
		sizeDlg.addEventListener( 'close', () => sizeBtn.focus() );
	}

	/* Frequently bought together ----------------------------------------- */
	const fbt = document.querySelector( '.au-fbt' );
	if ( fbt ) {
		const boxes = Array.from( fbt.querySelectorAll( 'input[type=checkbox]' ) );
		const sum = fbt.querySelector( '.au-fbt__sum' );
		const msg = fbt.querySelector( '.au-fbt__msg' );
		const add = fbt.querySelector( '.au-fbt__add' );
		const f = ( C.shop && C.shop.priceFormat ) || { symbol: '', decimals: 2 };
		const total = () => {
			const t = boxes.filter( ( b ) => b.checked ).reduce( ( s, b ) => s + Number( b.dataset.price ), 0 );
			sum.textContent = f.position && f.position.startsWith( 'right' ) ? t.toFixed( f.decimals ) + f.symbol : f.symbol + t.toFixed( f.decimals );
			add.disabled = ! boxes.some( ( b ) => b.checked );
		};
		boxes.forEach( ( b ) => b.addEventListener( 'change', total ) );
		total();
		add.addEventListener( 'click', async () => {
			add.disabled = true;
			msg.textContent = T.adding;
			try {
				const r = await fetch( C.storeApi + 'cart', { credentials: 'same-origin' } );
				let nonce = r.headers.get( 'Nonce' ) || '';
				for ( const b of boxes.filter( ( x ) => x.checked ) ) {
					const res = await fetch( C.storeApi + 'cart/add-item', {
						method: 'POST',
						credentials: 'same-origin',
						headers: { 'Content-Type': 'application/json', Nonce: nonce },
						body: JSON.stringify( { id: Number( b.value ), quantity: 1 } ),
					} );
					nonce = res.headers.get( 'Nonce' ) || nonce;
					if ( ! res.ok ) {
						throw new Error( ( await res.json() ).message );
					}
				}
				msg.textContent = T.addedAll;
				document.body.dispatchEvent( new CustomEvent( 'wc-blocks_added_to_cart', { bubbles: true, cancelable: true } ) );
				document.dispatchEvent( new CustomEvent( 'aurelia:cart-updated' ) );
			} catch ( err ) {
				msg.textContent = err.message;
			}
			add.disabled = false;
		} );
	}
}() );
