/**
 * Aurelia theme interactions. Vanilla JS, no dependencies, presentation only.
 */
( function () {
	'use strict';

	const doc = document.documentElement;
	const i18n = ( window.aureliaTheme && window.aureliaTheme.i18n ) || {};
	const reduced = window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches;
	const finePointer = window.matchMedia( '(hover: hover) and (pointer: fine)' ).matches;

	doc.classList.add( 'js' );

	/* Sticky header shadow */
	const onScroll = () => document.body.classList.toggle( 'is-scrolled', window.scrollY > 8 );
	window.addEventListener( 'scroll', onScroll, { passive: true } );
	onScroll();

	/* Colour scheme toggle */
	const systemDark = window.matchMedia( '(prefers-color-scheme: dark)' );
	const currentScheme = () => {
		const s = doc.getAttribute( 'data-color-scheme' );
		if ( s === 'dark' || s === 'light' ) {
			return s;
		}
		return systemDark.matches ? 'dark' : 'light';
	};
	const labelToggles = () => {
		document.querySelectorAll( '.au-scheme-toggle' ).forEach( ( btn ) => {
			const dark = currentScheme() === 'dark';
			btn.setAttribute( 'aria-pressed', dark ? 'true' : 'false' );
			btn.setAttribute( 'aria-label', dark ? i18n.light || 'Switch to light mode' : i18n.dark || 'Switch to dark mode' );
		} );
	};
	document.addEventListener( 'click', ( e ) => {
		const btn = e.target.closest( '.au-scheme-toggle' );
		if ( ! btn ) {
			return;
		}
		const next = currentScheme() === 'dark' ? 'light' : 'dark';
		doc.setAttribute( 'data-color-scheme', next );
		try {
			localStorage.setItem( 'aurelia-scheme', next );
		} catch ( err ) {}
		labelToggles();
	} );
	labelToggles();
	if ( systemDark.addEventListener ) {
		systemDark.addEventListener( 'change', labelToggles );
	}

	/* 3D tilt on product cards */
	if ( finePointer && ! reduced ) {
		const tiltSelector = '.wc-block-product, .au-tilt';
		document.addEventListener( 'pointermove', ( e ) => {
			const card = e.target.closest && e.target.closest( tiltSelector );
			if ( ! card ) {
				return;
			}
			const r = card.getBoundingClientRect();
			const px = ( e.clientX - r.left ) / r.width;
			const py = ( e.clientY - r.top ) / r.height;
			card.style.setProperty( '--ry', ( ( px - 0.5 ) * 8 ).toFixed( 2 ) + 'deg' );
			card.style.setProperty( '--rx', ( ( 0.5 - py ) * 8 ).toFixed( 2 ) + 'deg' );
			card.style.setProperty( '--mx', ( px * 100 ).toFixed( 1 ) + '%' );
			card.style.setProperty( '--my', ( py * 100 ).toFixed( 1 ) + '%' );
		}, { passive: true } );
		document.addEventListener( 'pointerout', ( e ) => {
			const card = e.target.closest && e.target.closest( tiltSelector );
			if ( card && ! card.contains( e.relatedTarget ) ) {
				card.style.setProperty( '--rx', '0deg' );
				card.style.setProperty( '--ry', '0deg' );
			}
		}, { passive: true } );
	}

	/* Marquee: duplicate the items once (hidden from assistive tech) for a seamless loop */
	document.querySelectorAll( '.au-marquee__track' ).forEach( ( track ) => {
		if ( track.dataset.cloned ) {
			return;
		}
		Array.from( track.children ).forEach( ( item ) => {
			const copy = item.cloneNode( true );
			copy.setAttribute( 'aria-hidden', 'true' );
			track.appendChild( copy );
		} );
		track.dataset.cloned = '1';
	} );

	/* Reveal on scroll */
	const revealEls = document.querySelectorAll( '.is-style-reveal, .au-reveal' );
	if ( revealEls.length ) {
		if ( reduced || ! ( 'IntersectionObserver' in window ) ) {
			revealEls.forEach( ( el ) => el.classList.add( 'is-visible' ) );
		} else {
			const io = new IntersectionObserver( ( entries ) => {
				entries.forEach( ( entry ) => {
					if ( entry.isIntersecting ) {
						entry.target.classList.add( 'is-visible' );
						io.unobserve( entry.target );
					}
				} );
			}, { rootMargin: '0px 0px -8% 0px' } );
			revealEls.forEach( ( el ) => io.observe( el ) );
		}
	}

	/* Countdown timers: <div class="au-countdown" data-end="2026-12-31T23:59:59+05:30">.
	   Without data-end the timer counts down to local midnight (a daily deal). */
	const pad = ( n ) => String( n ).padStart( 2, '0' );
	document.querySelectorAll( '.au-countdown' ).forEach( ( el ) => {
		const attr = el.getAttribute( 'data-end' );
		let end = attr ? Date.parse( attr ) : NaN;
		if ( Number.isNaN( end ) ) {
			const d = new Date();
			d.setHours( 24, 0, 0, 0 );
			end = d.getTime();
		}
		const units = [
			[ 'days', 86400 ],
			[ 'hours', 3600 ],
			[ 'minutes', 60 ],
			[ 'seconds', 1 ],
		];
		el.setAttribute( 'role', 'timer' );
		el.innerHTML = units
			.map( ( [ key ] ) => '<div class="au-countdown__unit"><strong data-unit="' + key + '">00</strong><span>' + ( i18n[ key ] || key ) + '</span></div>' )
			.join( '' );
		const tick = () => {
			let left = Math.max( 0, Math.floor( ( end - Date.now() ) / 1000 ) );
			units.forEach( ( [ key, size ] ) => {
				const v = Math.floor( left / size );
				left -= v * size;
				const node = el.querySelector( '[data-unit="' + key + '"]' );
				if ( node ) {
					node.textContent = pad( v );
				}
			} );
			if ( end - Date.now() <= 0 ) {
				el.innerHTML = '<p>' + ( i18n.ended || 'This offer has ended' ) + '</p>';
				return;
			}
			window.setTimeout( tick, 1000 );
		};
		tick();
	} );

	/* Carousel arrows for the Product Collection "Carousel" style */
	const arrow = ( dir ) =>
		'<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="' +
		( dir < 0 ? 'M15 18l-6-6 6-6' : 'M9 18l6-6-6-6' ) +
		'"/></svg>';
	document.querySelectorAll( '.wp-block-woocommerce-product-collection.is-style-carousel' ).forEach( ( block ) => {
		const track = block.querySelector( '.wc-block-product-template' );
		if ( ! track || block.querySelector( '.au-carousel-nav' ) ) {
			return;
		}
		const nav = document.createElement( 'div' );
		nav.className = 'au-carousel-nav';
		[ -1, 1 ].forEach( ( dir ) => {
			const b = document.createElement( 'button' );
			b.type = 'button';
			b.setAttribute( 'aria-label', dir < 0 ? i18n.previous || 'Previous' : i18n.next || 'Next' );
			b.innerHTML = arrow( dir );
			b.addEventListener( 'click', () => {
				const rtl = getComputedStyle( track ).direction === 'rtl' ? -1 : 1;
				track.scrollBy( { left: dir * rtl * track.clientWidth * 0.8, behavior: reduced ? 'auto' : 'smooth' } );
			} );
			nav.appendChild( b );
		} );
		block.insertBefore( nav, track );
	} );
}() );
