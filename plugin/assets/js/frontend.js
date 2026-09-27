/**
 * Aurelia Commerce — storefront interactions (vanilla JS, no jQuery).
 *
 * Modules: analytics beacon, WhatsApp ordering, wishlist, compare, quick view,
 * live search, grid/list + load more, free-shipping bar, newsletter + popup,
 * recently viewed.
 */
( function () {
	'use strict';

	const C = window.aureliaCommerce || {};
	const T = C.i18n || {};
	const SHOP = C.shop || {};
	const reduced = window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches;

	/* ------------------------------------------------------------------ */
	/* Utilities                                                           */
	/* ------------------------------------------------------------------ */

	const $ = ( sel, root = document ) => root.querySelector( sel );
	const $$ = ( sel, root = document ) => Array.from( root.querySelectorAll( sel ) );

	/** Create an element: el('button', {class:'x', type:'button'}, ['text', child]). */
	function el( tag, attrs = {}, children = [] ) {
		const node = document.createElement( tag );
		Object.entries( attrs ).forEach( ( [ k, v ] ) => {
			if ( v === false || v === null || v === undefined ) {
				return;
			}
			if ( k === 'class' ) {
				node.className = v;
			} else if ( k === 'html' ) {
				node.innerHTML = v; // Only used with server-sanitised HTML (wp_kses_post).
			} else if ( k.startsWith( 'on' ) ) {
				node.addEventListener( k.slice( 2 ), v );
			} else {
				node.setAttribute( k, v === true ? '' : v );
			}
		} );
		( Array.isArray( children ) ? children : [ children ] ).forEach( ( c ) => {
			if ( c !== null && c !== undefined && c !== false ) {
				node.append( c instanceof Node ? c : document.createTextNode( String( c ) ) );
			}
		} );
		return node;
	}

	const store = {
		get( key, fallback ) {
			try {
				const v = JSON.parse( localStorage.getItem( key ) );
				return v === null ? fallback : v;
			} catch ( e ) {
				return fallback;
			}
		},
		set( key, value ) {
			try {
				localStorage.setItem( key, JSON.stringify( value ) );
			} catch ( e ) {}
		},
	};

	/** sprintf for %s, %d and %1$s style placeholders. */
	function sprintf( str, ...args ) {
		let i = 0;
		return String( str || '' )
			.replace( /\\n/g, '\n' )
			.replace( /%(?:(\d+)\$)?[sd]/g, ( m, n ) => String( n ? args[ n - 1 ] : args[ i++ ] ) );
	}

	async function api( path, opts = {} ) {
		const headers = { 'Content-Type': 'application/json', ...( opts.headers || {} ) };
		const send = ( withNonce ) =>
			fetch( C.rest + path, {
				credentials: 'same-origin',
				...opts,
				headers: withNonce && C.nonce ? { ...headers, 'X-WP-Nonce': C.nonce } : headers,
			} );
		let res = await send( true );
		// A cached page can carry an expired nonce: retry anonymously.
		if ( res.status === 403 ) {
			res = await send( false );
		}
		const data = await res.json().catch( () => ( {} ) );
		if ( ! res.ok ) {
			throw new Error( data.message || res.statusText );
		}
		return data;
	}

	function money( amount ) {
		const f = SHOP.priceFormat || { symbol: '', position: 'left', decimals: 2, thousand: ',', decimal: '.' };
		const fixed = Number( amount || 0 ).toFixed( f.decimals );
		const [ int, dec ] = fixed.split( '.' );
		const grouped = int.replace( /\B(?=(\d{3})+(?!\d))/g, f.thousand );
		const num = dec ? grouped + f.decimal + dec : grouped;
		switch ( f.position ) {
			case 'right':
				return num + f.symbol;
			case 'left_space':
				return f.symbol + ' ' + num;
			case 'right_space':
				return num + ' ' + f.symbol;
			default:
				return f.symbol + num;
		}
	}

	function debounce( fn, ms ) {
		let t;
		return ( ...a ) => {
			clearTimeout( t );
			t = setTimeout( () => fn( ...a ), ms );
		};
	}

	/** Watch the DOM for elements rendered later (block cart, mini-cart drawer). */
	function watch( selector, cb ) {
		const seen = new WeakSet();
		const run = () =>
			$$( selector ).forEach( ( n ) => {
				if ( ! seen.has( n ) ) {
					seen.add( n );
					cb( n );
				}
			} );
		run();
		new MutationObserver( debounce( run, 120 ) ).observe( document.body, { childList: true, subtree: true } );
	}

	/** Modal dialog built on <dialog> (native focus trap + Esc). */
	function dialog( { title, className = '', body } ) {
		const close = el( 'button', { type: 'button', class: 'au-dialog__close', 'aria-label': T.close || 'Close' }, '×' );
		const heading = el( 'h2', { id: 'au-dlg-' + Date.now() }, title || '' );
		const d = el( 'dialog', { class: 'au-dialog ' + className, 'aria-labelledby': heading.id }, [
			el( 'div', { class: 'au-dialog__head' }, [ heading, close ] ),
			el( 'div', { class: 'au-dialog__body' }, body || [] ),
		] );
		close.addEventListener( 'click', () => d.close() );
		d.addEventListener( 'click', ( e ) => e.target === d && d.close() );
		d.addEventListener( 'close', () => d.remove() );
		document.body.append( d );
		d.showModal();
		return d;
	}

	function beacon( payload ) {
		if ( ! C.analytics || ! C.analytics.enabled ) {
			return;
		}
		const body = JSON.stringify( payload );
		try {
			if ( navigator.sendBeacon && navigator.sendBeacon( C.rest + 'event', new Blob( [ body ], { type: 'application/json' } ) ) ) {
				return;
			}
		} catch ( e ) {}
		fetch( C.rest + 'event', { method: 'POST', body, keepalive: true, headers: { 'Content-Type': 'application/json' } } ).catch( () => {} );
	}

	/** Store API: add to cart, then tell WooCommerce blocks (mini-cart) to refresh. */
	let storeNonce = '';
	async function addToCart( id, quantity = 1 ) {
		if ( ! storeNonce ) {
			const r = await fetch( C.storeApi + 'cart', { credentials: 'same-origin' } );
			storeNonce = r.headers.get( 'Nonce' ) || '';
		}
		const res = await fetch( C.storeApi + 'cart/add-item', {
			method: 'POST',
			credentials: 'same-origin',
			headers: { 'Content-Type': 'application/json', Nonce: storeNonce },
			body: JSON.stringify( { id, quantity } ),
		} );
		storeNonce = res.headers.get( 'Nonce' ) || storeNonce;
		const data = await res.json();
		if ( ! res.ok ) {
			throw new Error( data.message || 'Error' );
		}
		document.body.dispatchEvent( new CustomEvent( 'wc-blocks_added_to_cart', { bubbles: true, cancelable: true } ) );
		document.dispatchEvent( new CustomEvent( 'aurelia:cart-updated', { detail: data } ) );
		return data;
	}

	/* ------------------------------------------------------------------ */
	/* Analytics beacon                                                    */
	/* ------------------------------------------------------------------ */
	if ( C.analytics && C.analytics.enabled ) {
		const params = new URLSearchParams( location.search );
		beacon( { type: 'page_view', path: location.pathname, ref: document.referrer, utm: params.get( 'utm_source' ) || '' } );
		if ( C.analytics.product ) {
			beacon( { type: 'product_view', path: location.pathname, product: C.analytics.product } );
		}
		document.addEventListener( 'click', ( e ) => {
			const t = e.target.closest( '[data-au-track]' );
			if ( t ) {
				beacon( { type: t.dataset.auTrack, path: location.pathname, product: t.dataset.product || 0 } );
			}
		} );
	}

	/* ------------------------------------------------------------------ */
	/* Mini product cards (wishlist, recently viewed)                      */
	/* ------------------------------------------------------------------ */
	function miniCard( p, { onRemove } = {} ) {
		const btn = p.simple
			? el( 'button', { type: 'button', class: 'au-btn au-btn--primary au-btn--sm' }, T.addToCart )
			: el( 'a', { class: 'au-btn au-btn--ghost au-btn--sm', href: p.url }, T.chooseOptions );
		if ( p.simple ) {
			btn.addEventListener( 'click', async () => {
				btn.disabled = true;
				try {
					await addToCart( p.id, 1 );
					btn.textContent = T.added;
				} catch ( e ) {
					btn.disabled = false;
				}
			} );
		}
		return el( 'article', { class: 'au-mini-card' }, [
			el( 'a', { href: p.url, class: 'au-mini-card__media', tabindex: '-1', 'aria-hidden': 'true' }, el( 'img', { src: p.image, alt: '', loading: 'lazy', width: '300', height: '300' } ) ),
			el( 'h3', { class: 'au-mini-card__title' }, el( 'a', { href: p.url }, p.name ) ),
			el( 'div', { class: 'au-mini-card__price', html: p.priceHtml } ),
			el( 'div', { class: 'au-mini-card__actions' }, [
				btn,
				onRemove ? el( 'button', { type: 'button', class: 'au-link-btn', onclick: () => onRemove( p.id ) }, T.remove ) : null,
			] ),
		] );
	}

	/* ------------------------------------------------------------------ */
	/* WhatsApp ordering                                                   */
	/* ------------------------------------------------------------------ */
	const WA = C.whatsapp;
	if ( WA && WA.number ) {
		const waIcon = '<svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 2a10 10 0 0 0-8.6 15.1L2 22l5-1.3A10 10 0 1 0 12 2zm5.3 14.1c-.2.6-1.3 1.2-1.8 1.3-.5.1-1.1.1-1.7-.1-.4-.1-.9-.3-1.6-.6-2.8-1.2-4.6-4-4.7-4.2-.1-.2-1.1-1.5-1.1-2.9s.7-2 1-2.3c.3-.3.6-.4.8-.4h.6c.2 0 .4 0 .6.5l.8 2c.1.2.1.3 0 .5l-.3.5-.4.4c-.1.1-.3.3-.1.6.2.3.7 1.2 1.5 1.9 1.1.9 1.9 1.2 2.2 1.3.3.1.4.1.6-.1.2-.2.7-.8.8-1.1.2-.3.4-.2.6-.1l1.9.9c.3.1.5.2.5.3.1.2.1.7-.1 1.2z"/></svg>';

		const askDetails = () =>
			new Promise( ( resolve ) => {
				if ( ! WA.askDetails ) {
					resolve( {} );
					return;
				}
				const saved = store.get( 'au-wa-details', {} );
				const field = ( name, label, type = 'text', auto = '' ) =>
					el( 'p', { class: 'au-field' }, [
						el( 'label', { for: 'au-wa-' + name }, label ),
						el( type === 'textarea' ? 'textarea' : 'input', { id: 'au-wa-' + name, name, type: type === 'textarea' ? null : type, autocomplete: auto || null, rows: type === 'textarea' ? '2' : null } ),
					] );
				const form = el( 'form', { class: 'au-wa-form', method: 'dialog' }, [
					el( 'p', { class: 'au-muted' }, T.waDetailsNote ),
					field( 'name', T.waName, 'text', 'name' ),
					field( 'phone', T.waPhone, 'tel', 'tel' ),
					field( 'address', T.waAddress, 'textarea', 'street-address' ),
					el( 'div', { class: 'au-field-row' }, [ field( 'city', T.waCity, 'text', 'address-level2' ), field( 'pincode', T.waPin, 'text', 'postal-code' ) ] ),
					field( 'notes', T.waNotes, 'textarea' ),
					el( 'div', { class: 'au-dialog__actions' }, [
						el( 'button', { type: 'button', class: 'au-btn au-btn--ghost', value: 'skip' }, T.waSkip ),
						el( 'button', { type: 'submit', class: 'au-btn au-btn--whatsapp', html: waIcon + '<span></span>' } ),
					] ),
				] );
				form.querySelector( 'button[type=submit] span' ).textContent = T.waContinue;
				Object.entries( saved ).forEach( ( [ k, v ] ) => {
					if ( form.elements[ k ] ) {
						form.elements[ k ].value = v;
					}
				} );
				const d = dialog( { title: T.waDetails, className: 'au-dialog--wa', body: form } );
				let done = false;
				form.addEventListener( 'submit', ( e ) => {
					e.preventDefault();
					const data = Object.fromEntries( new FormData( form ).entries() );
					store.set( 'au-wa-details', data );
					done = true;
					d.close();
					resolve( data );
				} );
				form.querySelector( '[value=skip]' ).addEventListener( 'click', () => {
					done = true;
					d.close();
					resolve( {} );
				} );
				d.addEventListener( 'close', () => ! done && resolve( null ) );
			} );

		async function sendOrder( payload, button ) {
			const customer = await askDetails();
			if ( customer === null ) {
				return;
			}
			// Open the tab synchronously so pop-up blockers allow it.
			const win = window.open( '', '_blank' );
			const label = button && button.querySelector( 'span' );
			const original = label ? label.textContent : '';
			if ( label ) {
				label.textContent = T.waOpening;
			}
			try {
				const res = await api( 'whatsapp/order', { method: 'POST', body: JSON.stringify( { ...payload, customer } ) } );
				if ( win ) {
					win.location = res.url;
				} else {
					location.href = res.url;
				}
			} catch ( err ) {
				if ( win ) {
					win.close();
				}
				window.alert( err.message ); // eslint-disable-line no-alert
			} finally {
				if ( label ) {
					label.textContent = original;
				}
			}
		}

		document.addEventListener( 'click', ( e ) => {
			const orderBtn = e.target.closest( '.au-wa-order' );
			if ( orderBtn ) {
				const form = document.querySelector( 'form.cart' );
				const variation = form && form.querySelector( 'input[name=variation_id]' );
				if ( form && form.classList.contains( 'variations_form' ) && ( ! variation || ! Number( variation.value ) ) ) {
					let msg = orderBtn.parentElement.querySelector( '.au-wa-msg' );
					if ( ! msg ) {
						msg = el( 'p', { class: 'au-wa-msg', role: 'alert' } );
						orderBtn.after( msg );
					}
					msg.textContent = T.waChoose;
					form.scrollIntoView( { behavior: reduced ? 'auto' : 'smooth', block: 'center' } );
					return;
				}
				const attributes = {};
				if ( form ) {
					$$( '[name^="attribute_"]', form ).forEach( ( s ) => ( attributes[ s.name ] = s.value ) );
				}
				const qty = form && form.querySelector( 'input.qty' );
				sendOrder(
					{
						source: 'product',
						items: [ { product_id: Number( orderBtn.dataset.product ), variation_id: variation ? Number( variation.value ) : 0, quantity: qty ? Number( qty.value ) || 1 : 1, attributes } ],
					},
					orderBtn
				);
				return;
			}
			const cartBtn = e.target.closest( '.au-wa-cart' );
			if ( cartBtn ) {
				e.preventDefault();
				sendOrder( { source: 'cart' }, cartBtn );
				return;
			}
			const enquiry = e.target.closest( '.au-wa-enquiry' );
			if ( enquiry ) {
				const tpl = enquiry.dataset.kind === 'video' ? T.waVideo : T.waEnquiry;
				const text = sprintf( tpl, WA.store, enquiry.dataset.name, enquiry.dataset.url );
				beacon( { type: 'whatsapp_enquiry', path: location.pathname, product: enquiry.dataset.product } );
				window.open( 'https://wa.me/' + WA.number + '?text=' + encodeURIComponent( text ), '_blank', 'noopener' );
			}
		} );

		// Inject "Order on WhatsApp" into block cart, mini-cart drawer and classic cart.
		if ( WA.cart ) {
			const make = () => {
				const b = el( 'button', { type: 'button', class: 'au-btn au-btn--whatsapp au-btn--block au-wa-cart', html: waIcon + '<span></span>' } );
				b.querySelector( 'span' ).textContent = T.waOrder;
				return b;
			};
			watch( '.wc-block-cart__submit-container, .wc-block-mini-cart__footer-actions', ( node ) => {
				if ( ! node.querySelector( '.au-wa-cart' ) ) {
					node.append( make() );
				}
			} );
		}
	}

	/* ------------------------------------------------------------------ */
	/* Wishlist                                                            */
	/* ------------------------------------------------------------------ */
	if ( SHOP.wishlist ) {
		let wishlist = store.get( 'au-wishlist', [] ).map( Number );
		const paint = () => {
			$$( '.au-wishlist-btn' ).forEach( ( b ) => {
				const on = wishlist.includes( Number( b.dataset.product ) );
				b.classList.toggle( 'is-active', on );
				b.setAttribute( 'aria-pressed', on ? 'true' : 'false' );
			} );
			$$( '.au-wishlist-count' ).forEach( ( c ) => {
				c.textContent = String( wishlist.length );
				c.hidden = wishlist.length === 0;
			} );
		};
		const save = () => {
			store.set( 'au-wishlist', wishlist );
			paint();
			if ( C.loggedIn ) {
				api( 'wishlist', { method: 'POST', body: JSON.stringify( { ids: wishlist } ) } ).catch( () => {} );
			}
		};
		if ( C.loggedIn ) {
			api( 'wishlist' )
				.then( ( r ) => {
					const merged = Array.from( new Set( [ ...( r.ids || [] ), ...wishlist ] ) ).map( Number ).filter( Boolean );
					if ( merged.length !== ( r.ids || [] ).length ) {
						wishlist = merged;
						save();
					} else {
						wishlist = merged;
						paint();
					}
					renderWishlistPage();
				} )
				.catch( () => {} );
		}
		document.addEventListener( 'click', ( e ) => {
			const b = e.target.closest( '.au-wishlist-btn' );
			if ( ! b ) {
				return;
			}
			e.preventDefault();
			const id = Number( b.dataset.product );
			wishlist = wishlist.includes( id ) ? wishlist.filter( ( x ) => x !== id ) : [ id, ...wishlist ].slice( 0, 100 );
			save();
		} );
		new MutationObserver( debounce( paint, 150 ) ).observe( document.body, { childList: true, subtree: true } );
		paint();

		function renderWishlistPage() {
			const page = $( '.au-wishlist-page' );
			if ( ! page ) {
				return;
			}
			const grid = $( '.au-mini-grid', page );
			if ( ! wishlist.length ) {
				grid.replaceChildren( el( 'p', { class: 'au-empty' }, page.dataset.empty || T.wishlistEmpty ) );
				return;
			}
			api( 'products?ids=' + wishlist.join( ',' ) ).then( ( r ) => {
				grid.replaceChildren(
					...( r.products || [] ).map( ( p ) =>
						miniCard( p, {
							onRemove: ( id ) => {
								wishlist = wishlist.filter( ( x ) => x !== id );
								save();
								renderWishlistPage();
							},
						} )
					)
				);
			} );
		}
		renderWishlistPage();
	}

	/* ------------------------------------------------------------------ */
	/* Compare                                                             */
	/* ------------------------------------------------------------------ */
	if ( SHOP.compare ) {
		let compare = store.get( 'au-compare', [] ).map( Number );
		const bar = el( 'div', { class: 'au-compare-bar', hidden: true, role: 'region', 'aria-label': T.compareAdd } );
		document.body.append( bar );
		const paint = () => {
			$$( '.au-compare-btn' ).forEach( ( b ) => {
				const on = compare.includes( Number( b.dataset.product ) );
				b.classList.toggle( 'is-active', on );
				b.setAttribute( 'aria-pressed', on ? 'true' : 'false' );
			} );
			bar.hidden = compare.length === 0 || !! $( '.au-compare-page' );
			bar.replaceChildren(
				...[
					el( 'span', {}, sprintf( T.compareCount, compare.length ) ),
					SHOP.compareUrl ? el( 'a', { class: 'au-btn au-btn--primary au-btn--sm', href: SHOP.compareUrl }, T.compareNow ) : null,
					el( 'button', { type: 'button', class: 'au-link-btn', onclick: () => ( ( compare = [] ), save() ) }, T.compareClear ),
				].filter( Boolean )
			);
		};
		const save = () => {
			store.set( 'au-compare', compare );
			paint();
			renderComparePage();
		};
		document.addEventListener( 'click', ( e ) => {
			const b = e.target.closest( '.au-compare-btn' );
			if ( ! b ) {
				return;
			}
			e.preventDefault();
			const id = Number( b.dataset.product );
			if ( compare.includes( id ) ) {
				compare = compare.filter( ( x ) => x !== id );
			} else if ( compare.length >= 4 ) {
				window.alert( T.compareMax ); // eslint-disable-line no-alert
				return;
			} else {
				compare = [ ...compare, id ];
			}
			save();
		} );
		new MutationObserver( debounce( paint, 150 ) ).observe( document.body, { childList: true, subtree: true } );
		paint();

		function renderComparePage() {
			const page = $( '.au-compare-page' );
			if ( ! page ) {
				return;
			}
			if ( ! compare.length ) {
				page.replaceChildren( el( 'p', { class: 'au-empty' }, T.compareEmpty ) );
				return;
			}
			api( 'products?details=1&ids=' + compare.join( ',' ) ).then( ( r ) => {
				const products = r.products || [];
				const attrs = Array.from( new Set( products.flatMap( ( p ) => Object.keys( p.attributes || {} ) ) ) );
				const row = ( label, cell ) => el( 'tr', {}, [ el( 'th', { scope: 'row' }, label ), ...products.map( ( p ) => el( 'td', {}, cell( p ) ) ) ] );
				const table = el( 'table', { class: 'au-compare-table' }, [
					el( 'thead', {}, el( 'tr', {}, [ el( 'td' ), ...products.map( ( p ) => el( 'th', { scope: 'col' }, miniCard( p, { onRemove: ( id ) => ( ( compare = compare.filter( ( x ) => x !== id ) ), save() ) } ) ) ) ] ) ),
					el( 'tbody', {}, [
						row( T.rating, ( p ) => ( p.rating ? '★ ' + p.rating.toFixed( 1 ) + ' (' + p.reviews + ')' : '—' ) ),
						row( T.availability, ( p ) => ( p.inStock ? T.inStock : T.outOfStock ) ),
						row( 'SKU', ( p ) => p.sku || '—' ),
						...attrs.map( ( a ) => row( a, ( p ) => ( p.attributes && p.attributes[ a ] ) || '—' ) ),
						row( '', ( p ) => p.excerpt || '' ),
					] ),
				] );
				page.replaceChildren( el( 'div', { class: 'au-table-scroll', tabindex: '0' }, table ) );
			} );
		}
		renderComparePage();
	}

	/* ------------------------------------------------------------------ */
	/* Quick view                                                          */
	/* ------------------------------------------------------------------ */
	if ( SHOP.quickView ) {
		document.addEventListener( 'click', async ( e ) => {
			const b = e.target.closest( '.au-quickview-btn' );
			if ( ! b ) {
				return;
			}
			e.preventDefault();
			const body = el( 'div', { class: 'au-qv' }, el( 'p', { class: 'au-muted', role: 'status' }, T.loading ) );
			const d = dialog( { title: T.quickView, className: 'au-dialog--qv', body } );
			try {
				const p = await api( 'quick-view/' + b.dataset.product );
				d.querySelector( '.au-dialog__head h2' ).textContent = p.name;
				const main = el( 'img', { src: p.gallery[ 0 ].src, alt: p.gallery[ 0 ].alt || p.name, class: 'au-qv__main' } );
				const thumbs = p.gallery.length > 1
					? el( 'div', { class: 'au-qv__thumbs' }, p.gallery.map( ( g, i ) => el( 'button', { type: 'button', 'aria-label': ( g.alt || p.name ) + ' ' + ( i + 1 ), onclick: () => ( main.src = g.src ) }, el( 'img', { src: g.src, alt: '' } ) ) ) )
					: null;
				const qty = el( 'input', { type: 'number', min: '1', value: '1', class: 'au-qv__qty', 'aria-label': 'Quantity' } );
				const add = el( 'button', { type: 'button', class: 'au-btn au-btn--primary' }, T.addToCart );
				add.addEventListener( 'click', async () => {
					add.disabled = true;
					try {
						await addToCart( p.id, Number( qty.value ) || 1 );
						add.textContent = T.added;
					} catch ( err ) {
						add.disabled = false;
						add.textContent = err.message;
					}
				} );
				body.replaceChildren(
					el( 'div', { class: 'au-qv__media' }, [ main, thumbs ].filter( Boolean ) ),
					el( 'div', { class: 'au-qv__info' }, [
						p.rating ? el( 'p', { class: 'au-qv__rating' }, '★ ' + p.rating.toFixed( 1 ) ) : null,
						el( 'div', { class: 'au-qv__price', html: p.priceHtml } ),
						el( 'div', { class: 'au-qv__desc', html: p.description } ),
						el( 'div', { html: p.stockHtml } ),
						p.simple ? el( 'div', { class: 'au-qv__buy' }, [ qty, add ] ) : el( 'a', { class: 'au-btn au-btn--primary', href: p.url }, T.chooseOptions ),
						el( 'a', { class: 'au-qv__more', href: p.url }, T.viewDetails + ' →' ),
					] )
				);
			} catch ( err ) {
				body.replaceChildren( el( 'p', { role: 'alert' }, err.message ) );
			}
		} );
	}

	/* ------------------------------------------------------------------ */
	/* Live search                                                         */
	/* ------------------------------------------------------------------ */
	if ( SHOP.liveSearch ) {
		$$( 'form.wp-block-search, form.woocommerce-product-search, form[role=search]' ).forEach( ( form, n ) => {
			const input = form.querySelector( 'input[name=s]' );
			if ( ! input || input.dataset.auLive ) {
				return;
			}
			input.dataset.auLive = '1';
			const listId = 'au-search-list-' + n;
			const list = el( 'div', { class: 'au-search-list', id: listId, role: 'listbox', 'aria-label': T.searchLabel, hidden: true } );
			form.style.position = 'relative';
			form.append( list );
			input.setAttribute( 'role', 'combobox' );
			input.setAttribute( 'aria-autocomplete', 'list' );
			input.setAttribute( 'aria-expanded', 'false' );
			input.setAttribute( 'aria-controls', listId );
			input.setAttribute( 'autocomplete', 'off' );
			let active = -1;
			let controller = null;

			const close = () => {
				list.hidden = true;
				input.setAttribute( 'aria-expanded', 'false' );
				input.removeAttribute( 'aria-activedescendant' );
				active = -1;
			};
			const options = () => $$( '[role=option]', list );
			const highlight = ( i ) => {
				const opts = options();
				if ( ! opts.length ) {
					return;
				}
				active = ( i + opts.length ) % opts.length;
				opts.forEach( ( o, k ) => o.setAttribute( 'aria-selected', k === active ? 'true' : 'false' ) );
				input.setAttribute( 'aria-activedescendant', opts[ active ].id );
				opts[ active ].scrollIntoView( { block: 'nearest' } );
			};
			const search = debounce( async () => {
				const q = input.value.trim();
				if ( q.length < 2 ) {
					close();
					return;
				}
				controller?.abort();
				controller = new AbortController();
				try {
					const res = await fetch( C.rest + 'search?q=' + encodeURIComponent( q ), { signal: controller.signal } );
					const data = await res.json();
					const items = [];
					( data.products || [] ).forEach( ( p, i ) =>
						items.push(
							el( 'a', { class: 'au-search-item', role: 'option', id: listId + '-p' + i, href: p.url, 'aria-selected': 'false' }, [
								el( 'img', { src: p.image, alt: '', width: '44', height: '44' } ),
								el( 'span', { class: 'au-search-item__name' }, p.name ),
								el( 'span', { class: 'au-search-item__price', html: p.priceHtml } ),
							] )
						)
					);
					( data.categories || [] ).forEach( ( c, i ) => items.push( el( 'a', { class: 'au-search-cat', role: 'option', id: listId + '-c' + i, href: c.url, 'aria-selected': 'false' }, '↳ ' + c.name ) ) );
					if ( ! items.length ) {
						items.push( el( 'p', { class: 'au-search-empty' }, T.searchNone ) );
					}
					items.push( el( 'a', { class: 'au-search-all', role: 'option', id: listId + '-all', href: data.all, 'aria-selected': 'false' }, T.searchAll + ' →' ) );
					list.replaceChildren( ...items );
					list.hidden = false;
					input.setAttribute( 'aria-expanded', 'true' );
					active = -1;
				} catch ( err ) {}
			}, 200 );

			input.addEventListener( 'input', search );
			input.addEventListener( 'keydown', ( e ) => {
				if ( list.hidden ) {
					return;
				}
				if ( e.key === 'ArrowDown' ) {
					e.preventDefault();
					highlight( active + 1 );
				} else if ( e.key === 'ArrowUp' ) {
					e.preventDefault();
					highlight( active - 1 );
				} else if ( e.key === 'Enter' && active >= 0 ) {
					e.preventDefault();
					options()[ active ].click();
				} else if ( e.key === 'Escape' ) {
					close();
				}
			} );
			document.addEventListener( 'click', ( e ) => ! form.contains( e.target ) && close() );
		} );
	}

	/* ------------------------------------------------------------------ */
	/* Shop archive: grid/list toggle, load more / infinite scroll         */
	/* ------------------------------------------------------------------ */
	const grid = $( '.au-shop__grid' );
	if ( grid ) {
		const toolbar = $( '.au-shop__toolbar' );
		if ( SHOP.listToggle && toolbar ) {
			const mode = store.get( 'au-view', 'grid' );
			grid.classList.toggle( 'is-list-view', mode === 'list' );
			const icon = ( d ) => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true"><path d="' + d + '"/></svg>';
			const make = ( m, label, path ) => {
				const b = el( 'button', { type: 'button', class: 'au-view-btn', 'aria-pressed': mode === m ? 'true' : 'false', 'aria-label': label, html: icon( path ) } );
				b.addEventListener( 'click', () => {
					grid.classList.toggle( 'is-list-view', m === 'list' );
					store.set( 'au-view', m );
					$$( '.au-view-btn', toolbar ).forEach( ( x ) => x.setAttribute( 'aria-pressed', x === b ? 'true' : 'false' ) );
				} );
				return b;
			};
			toolbar.append( el( 'div', { class: 'au-view-toggle', role: 'group' }, [ make( 'grid', T.grid, 'M4 4h7v7H4zM13 4h7v7h-7zM4 13h7v7H4zM13 13h7v7h-7z' ), make( 'list', T.list, 'M4 5h16M4 12h16M4 19h16' ) ] ) );
		}

		if ( SHOP.pagination === 'load_more' || SHOP.pagination === 'infinite' ) {
			const pager = $( '.wp-block-query-pagination', grid );
			const template = $( '.wc-block-product-template', grid );
			let next = pager && $( '.wp-block-query-pagination-next', pager );
			if ( pager && template && next ) {
				pager.hidden = true;
				const button = el( 'button', { type: 'button', class: 'au-btn au-btn--ghost au-load-more' }, T.loadMore );
				const status = el( 'p', { class: 'screen-reader-text', role: 'status' } );
				pager.after( el( 'div', { class: 'au-load-more-wrap' }, [ button, status ] ) );
				let busy = false;
				const load = async () => {
					if ( busy || ! next ) {
						return;
					}
					busy = true;
					button.textContent = T.loading;
					try {
						const html = await ( await fetch( next.href, { credentials: 'same-origin' } ) ).text();
						const doc = new DOMParser().parseFromString( html, 'text/html' );
						const items = $$( '.au-shop__grid .wc-block-product-template > *', doc );
						items.forEach( ( item ) => template.append( document.importNode( item, true ) ) );
						status.textContent = items.length + ' +';
						next = $( '.au-shop__grid .wp-block-query-pagination-next', doc );
						history.replaceState( null, '', location.href );
					} catch ( e ) {
						next = null;
					}
					busy = false;
					button.textContent = T.loadMore;
					if ( ! next ) {
						button.parentElement.remove();
					}
				};
				button.addEventListener( 'click', load );
				if ( SHOP.pagination === 'infinite' && 'IntersectionObserver' in window ) {
					new IntersectionObserver( ( entries ) => entries[ 0 ].isIntersecting && load(), { rootMargin: '600px' } ).observe( button );
				}
			}
		}
	}

	/* ------------------------------------------------------------------ */
	/* Free-shipping progress bar (cart page + side cart)                  */
	/* ------------------------------------------------------------------ */
	if ( SHOP.freeShipping > 0 ) {
		const bars = new Set();
		const update = debounce( async () => {
			if ( ! bars.size ) {
				return;
			}
			try {
				const cart = await ( await fetch( C.storeApi + 'cart', { credentials: 'same-origin' } ) ).json();
				const t = cart.totals || {};
				const unit = Math.pow( 10, t.currency_minor_unit || 0 );
				const subtotal = ( Number( t.total_items || 0 ) + Number( t.total_items_tax || 0 ) ) / unit;
				const left = Math.max( 0, SHOP.freeShipping - subtotal );
				const pct = Math.min( 100, ( subtotal / SHOP.freeShipping ) * 100 );
				bars.forEach( ( bar ) => {
					bar.hidden = ! cart.items_count;
					bar.querySelector( '.au-ship-bar__text' ).textContent = left > 0 ? sprintf( T.freeShipLeft, money( left ) ) : T.freeShipDone;
					bar.querySelector( '.au-ship-bar__fill' ).style.width = pct + '%';
					bar.querySelector( '.au-ship-bar__track' ).setAttribute( 'aria-valuenow', String( Math.round( pct ) ) );
					bar.classList.toggle( 'is-complete', left <= 0 );
				} );
			} catch ( e ) {}
		}, 300 );
		const makeBar = () =>
			el( 'div', { class: 'au-ship-bar', hidden: true }, [
				el( 'p', { class: 'au-ship-bar__text', role: 'status' } ),
				el( 'div', { class: 'au-ship-bar__track', role: 'progressbar', 'aria-valuemin': '0', 'aria-valuemax': '100', 'aria-valuenow': '0', 'aria-label': T.freeShipDone }, el( 'span', { class: 'au-ship-bar__fill' } ) ),
			] );
		watch( '.wc-block-mini-cart__products-table, .wp-block-woocommerce-cart .wc-block-cart, .woocommerce-cart-form', ( node ) => {
			const bar = makeBar();
			node.before( bar );
			bars.add( bar );
			update();
			new MutationObserver( update ).observe( node, { childList: true, subtree: true, characterData: true } );
		} );
		document.body.addEventListener( 'wc-blocks_added_to_cart', update );
		document.addEventListener( 'aurelia:cart-updated', update );
	}

	/* ------------------------------------------------------------------ */
	/* Newsletter forms + popup                                            */
	/* ------------------------------------------------------------------ */
	async function subscribe( form, source ) {
		const note = form.querySelector( '.au-form-note' ) || form.appendChild( el( 'p', { class: 'au-form-note', role: 'status' } ) );
		const email = form.querySelector( 'input[type=email]' );
		const button = form.querySelector( 'button[type=submit]' );
		if ( button ) {
			button.disabled = true;
		}
		try {
			const res = await api( 'subscribe', { method: 'POST', body: JSON.stringify( { email: email.value, source } ) } );
			note.textContent = res.coupon ? sprintf( T.subscribedCode, res.coupon ) : T.subscribed;
			email.value = '';
			store.set( 'au-popup', Date.now() + 365 * 864e5 );
		} catch ( err ) {
			note.textContent = err.message || T.subscribeError;
		}
		if ( button ) {
			button.disabled = false;
		}
	}
	document.addEventListener( 'submit', ( e ) => {
		const form = e.target.closest( '[data-aurelia-newsletter]' );
		if ( form ) {
			e.preventDefault();
			subscribe( form, form.dataset.source || 'footer' );
		}
	} );

	if ( C.popup && Number( store.get( 'au-popup', 0 ) ) < Date.now() ) {
		let shown = false;
		const show = () => {
			if ( shown || document.querySelector( 'dialog[open]' ) ) {
				return;
			}
			shown = true;
			store.set( 'au-popup', Date.now() + 7 * 864e5 );
			const form = el( 'form', { class: 'au-newsletter-form', 'data-aurelia-newsletter': '', 'data-source': 'popup' }, [
				el( 'label', { class: 'screen-reader-text', for: 'au-popup-email' }, T.emailLabel ),
				el( 'input', { id: 'au-popup-email', type: 'email', name: 'email', required: true, autocomplete: 'email', placeholder: T.emailPlace } ),
				el( 'button', { type: 'submit' }, T.subscribe ),
				el( 'p', { class: 'au-form-note', role: 'status', 'aria-live': 'polite' } ),
			] );
			const d = dialog( { title: C.popup.title, className: 'au-dialog--popup', body: [ el( 'p', {}, C.popup.text ), form ] } );
			d.querySelector( '.au-dialog__body' ).append( el( 'button', { type: 'button', class: 'au-link-btn', onclick: () => d.close() }, T.noThanks ) );
		};
		if ( C.popup.delay > 0 ) {
			setTimeout( show, C.popup.delay * 1000 );
		}
		if ( C.popup.exit && window.matchMedia( '(hover: hover)' ).matches ) {
			document.addEventListener( 'mouseout', ( e ) => {
				if ( ! e.relatedTarget && e.clientY <= 0 ) {
					show();
				}
			} );
		}
	}

	/* ------------------------------------------------------------------ */
	/* Recently viewed                                                     */
	/* ------------------------------------------------------------------ */
	if ( SHOP.recent ) {
		let recent = store.get( 'au-recent', [] ).map( Number );
		if ( SHOP.product ) {
			recent = [ SHOP.product, ...recent.filter( ( id ) => id !== SHOP.product ) ].slice( 0, 12 );
			store.set( 'au-recent', recent );
		}
		const section = $( '.au-recent' );
		const ids = recent.filter( ( id ) => id !== SHOP.product ).slice( 0, 4 );
		if ( section && ids.length ) {
			api( 'products?ids=' + ids.join( ',' ) )
				.then( ( r ) => {
					if ( ( r.products || [] ).length ) {
						$( '.au-mini-grid', section ).replaceChildren( ...r.products.map( ( p ) => miniCard( p ) ) );
						section.hidden = false;
					}
				} )
				.catch( () => {} );
		}
	}
}() );
