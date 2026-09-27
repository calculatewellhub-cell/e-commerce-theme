/**
 * Aurelia 3D viewer loader (~2 KB). Loads the three.js bundle only when the
 * hero stage becomes visible or the shopper opens "View in 3D".
 */
const cfgEl = document.getElementById( 'aurelia-3d-config' );
const cfg = cfgEl ? JSON.parse( cfgEl.textContent ) : {};
const i18n = cfg.i18n || {};
let bundle = null;
const load = () => ( bundle ||= import( cfg.bundle ) );

function webgl() {
	try {
		const c = document.createElement( 'canvas' );
		return !! ( window.WebGLRenderingContext && ( c.getContext( 'webgl2' ) || c.getContext( 'webgl' ) ) );
	} catch ( e ) {
		return false;
	}
}

if ( webgl() ) {
	// Hero stage(s) from the theme pattern.
	if ( cfg.hero ) {
		const stages = document.querySelectorAll( '.au-3d-stage' );
		const io = new IntersectionObserver( ( entries ) => {
			entries.forEach( async ( entry ) => {
				if ( ! entry.isIntersecting ) {
					return;
				}
				io.unobserve( entry.target );
				try {
					const { mount } = await load();
					await mount( entry.target, { ...cfg.hero, variant: 'hero', label: i18n.label } );
					entry.target.classList.add( 'is-3d-ready' );
				} catch ( e ) {
					// Keep the static image.
				}
			} );
		}, { rootMargin: '200px' } );
		stages.forEach( ( s ) => io.observe( s ) );
	}

	// "View in 3D" on product pages.
	if ( cfg.product ) {
		const gallery = document.querySelector( '.woocommerce-product-gallery' ) || document.querySelector( '.wp-block-woocommerce-product-image-gallery' );
		if ( gallery ) {
			const button = document.createElement( 'button' );
			button.type = 'button';
			button.className = 'au-3d-toggle';
			button.innerHTML = '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true"><path d="M12 2 3 7v10l9 5 9-5V7z"/><path d="M3 7l9 5 9-5M12 12v10"/></svg><span></span>';
			button.querySelector( 'span' ).textContent = i18n.view || 'View in 3D';
			gallery.style.position = 'relative';
			gallery.appendChild( button );

			let overlay = null;
			let unmount = null;
			const close = () => {
				if ( unmount ) {
					unmount();
				}
				unmount = null;
				overlay?.remove();
				overlay = null;
				button.setAttribute( 'aria-expanded', 'false' );
				button.focus();
			};
			button.setAttribute( 'aria-expanded', 'false' );
			button.addEventListener( 'click', async () => {
				if ( overlay ) {
					close();
					return;
				}
				overlay = document.createElement( 'div' );
				overlay.className = 'au-3d-overlay';
				overlay.setAttribute( 'role', 'dialog' );
				overlay.setAttribute( 'aria-label', i18n.label || '3D' );
				overlay.innerHTML = '<div class="au-3d-overlay__stage"><p class="au-3d-overlay__status" role="status"></p></div><p class="au-3d-overlay__hint"></p><button type="button" class="au-3d-overlay__close"><span aria-hidden="true">×</span></button>';
				overlay.querySelector( '.au-3d-overlay__hint' ).textContent = i18n.hint || '';
				overlay.querySelector( '.au-3d-overlay__close' ).setAttribute( 'aria-label', i18n.close || 'Close' );
				overlay.querySelector( '.au-3d-overlay__status' ).textContent = i18n.loading || '';
				overlay.querySelector( '.au-3d-overlay__close' ).addEventListener( 'click', close );
				overlay.addEventListener( 'keydown', ( e ) => e.key === 'Escape' && close() );
				gallery.appendChild( overlay );
				button.setAttribute( 'aria-expanded', 'true' );
				overlay.querySelector( '.au-3d-overlay__close' ).focus();
				try {
					const { mount } = await load();
					const stage = overlay.querySelector( '.au-3d-overlay__stage' );
					unmount = await mount( stage, { ...cfg.product, variant: 'product', label: i18n.label } );
					overlay.querySelector( '.au-3d-overlay__status' ).textContent = '';
					stage.querySelector( 'canvas' )?.focus();
				} catch ( e ) {
					overlay.querySelector( '.au-3d-overlay__status' ).textContent = i18n.failed || '';
				}
			} );
		}
	}
}
