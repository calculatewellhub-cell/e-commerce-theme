/**
 * Aurelia Commerce — analytics dashboard (wp-admin).
 * KPI tiles, a daily traffic line chart (3 series, crosshair tooltip, table view),
 * and single-hue bar lists. Colours are the validated categorical slots 1–3.
 */
( function () {
	'use strict';

	const A = window.aureliaAnalytics || {};
	const T = A.i18n || {};
	const app = document.getElementById( 'aurelia-analytics-app' );
	const range = document.getElementById( 'aurelia-range' );
	if ( ! app ) {
		return;
	}

	const SERIES = [
		{ key: 'page_view', label: T.pageViews, color: 'var(--au-series-1)' },
		{ key: 'product_view', label: T.productViews, color: 'var(--au-series-2)' },
		{ key: 'add_to_cart', label: T.addToCart, color: 'var(--au-series-3)' },
	];
	const nf = new Intl.NumberFormat();

	function el( tag, attrs = {}, children = [] ) {
		const n = document.createElement( tag );
		Object.entries( attrs ).forEach( ( [ k, v ] ) => {
			if ( v === null || v === undefined || v === false ) {
				return;
			}
			if ( k === 'class' ) {
				n.className = v;
			} else if ( k === 'style' ) {
				n.style.cssText = v;
			} else if ( k.startsWith( 'on' ) ) {
				n.addEventListener( k.slice( 2 ), v );
			} else {
				n.setAttribute( k, v );
			}
		} );
		( Array.isArray( children ) ? children : [ children ] ).forEach( ( c ) => c !== null && c !== undefined && n.append( c instanceof Node ? c : document.createTextNode( String( c ) ) ) );
		return n;
	}
	const svg = ( tag, attrs = {} ) => {
		const n = document.createElementNS( 'http://www.w3.org/2000/svg', tag );
		Object.entries( attrs ).forEach( ( [ k, v ] ) => n.setAttribute( k, v ) );
		return n;
	};

	function tile( label, value, sub ) {
		return el( 'div', { class: 'au-kpi' }, [ el( 'span', { class: 'au-kpi__label' }, label ), el( 'strong', { class: 'au-kpi__value' }, value ), sub ? el( 'span', { class: 'au-kpi__sub' }, sub ) : null ] );
	}

	function card( title, body, extra ) {
		return el( 'section', { class: 'au-card' }, [ el( 'div', { class: 'au-card__head' }, [ el( 'h2', {}, title ), extra || null ] ), body ] );
	}

	function barList( rows ) {
		if ( ! rows.length ) {
			return el( 'p', { class: 'au-empty' }, T.empty );
		}
		const max = Math.max( ...rows.map( ( r ) => r.count ) ) || 1;
		return el(
			'ul',
			{ class: 'au-barlist' },
			rows.map( ( r ) =>
				el( 'li', {}, [
					el( 'span', { class: 'au-barlist__label', title: r.label }, r.label ),
					el( 'span', { class: 'au-barlist__track', 'aria-hidden': 'true' }, el( 'span', { class: 'au-barlist__bar', style: 'width:' + Math.max( 2, ( r.count / max ) * 100 ) + '%' } ) ),
					el( 'span', { class: 'au-barlist__value' }, nf.format( r.count ) ),
				] )
			)
		);
	}

	function table( head, rows ) {
		if ( ! rows.length ) {
			return el( 'p', { class: 'au-empty' }, T.empty );
		}
		return el( 'table', { class: 'widefat striped au-table' }, [
			el( 'thead', {}, el( 'tr', {}, head.map( ( h, i ) => el( 'th', { scope: 'col', class: i ? 'num' : null }, h ) ) ) ),
			el( 'tbody', {}, rows.map( ( r ) => el( 'tr', {}, r.map( ( c, i ) => el( i ? 'td' : 'th', { scope: i ? null : 'row', class: i ? 'num' : null }, c ) ) ) ) ),
		] );
	}

	/** Daily line chart: 2px lines, recessive grid, crosshair + tooltip, direct end labels. */
	function lineChart( daily ) {
		const W = 900;
		const H = 280;
		const M = { t: 16, r: 110, b: 28, l: 44 };
		const days = daily.map( ( d ) => d[ 0 ] );
		const values = SERIES.map( ( s ) => daily.map( ( d ) => d[ 1 ][ s.key ] || 0 ) );
		const maxRaw = Math.max( 1, ...values.flat() );
		const step = Math.pow( 10, Math.floor( Math.log10( maxRaw ) ) );
		const max = Math.max( 4, Math.ceil( maxRaw / step ) * step );
		const x = ( i ) => M.l + ( days.length > 1 ? ( i / ( days.length - 1 ) ) * ( W - M.l - M.r ) : 0 );
		const y = ( v ) => M.t + ( 1 - v / max ) * ( H - M.t - M.b );

		const root = svg( 'svg', { viewBox: `0 0 ${ W } ${ H }`, class: 'au-line', role: 'img', 'aria-label': T.traffic } );
		for ( let g = 0; g <= 4; g++ ) {
			const v = ( max / 4 ) * g;
			root.append( svg( 'line', { x1: M.l, x2: W - M.r, y1: y( v ), y2: y( v ), class: 'au-grid' } ) );
			const t = svg( 'text', { x: M.l - 8, y: y( v ) + 4, class: 'au-axis', 'text-anchor': 'end' } );
			t.textContent = nf.format( Math.round( v ) );
			root.append( t );
		}
		const every = Math.ceil( days.length / 8 );
		days.forEach( ( d, i ) => {
			if ( i % every === 0 || i === days.length - 1 ) {
				const t = svg( 'text', { x: x( i ), y: H - 8, class: 'au-axis', 'text-anchor': 'middle' } );
				t.textContent = d.slice( 5 );
				root.append( t );
			}
		} );
		// Draw lines back to front, then end labels (direct labeling, nudged apart).
		const ends = [];
		SERIES.forEach( ( s, k ) => {
			const d = values[ k ].map( ( v, i ) => ( i ? 'L' : 'M' ) + x( i ).toFixed( 1 ) + ' ' + y( v ).toFixed( 1 ) ).join( ' ' );
			root.append( svg( 'path', { d, class: 'au-series', style: 'stroke:' + s.color } ) );
			ends.push( { y: y( values[ k ][ values[ k ].length - 1 ] ), s } );
		} );
		ends.sort( ( a, b ) => a.y - b.y );
		for ( let i = 1; i < ends.length; i++ ) {
			ends[ i ].y = Math.max( ends[ i ].y, ends[ i - 1 ].y + 14 );
		}
		// Keep the stack inside the plot area (above the x-axis labels).
		const floor = H - M.b - 4;
		for ( let i = ends.length - 1; i >= 0; i-- ) {
			ends[ i ].y = Math.min( ends[ i ].y, floor - ( ends.length - 1 - i ) * 14 );
		}
		ends.forEach( ( e ) => {
			root.append( svg( 'circle', { cx: W - M.r + 6, cy: e.y, r: 4, style: 'fill:' + e.s.color } ) );
			const t = svg( 'text', { x: W - M.r + 14, y: e.y + 4, class: 'au-endlabel' } );
			t.textContent = e.s.label;
			root.append( t );
		} );

		// Hover layer: crosshair + tooltip.
		const cross = svg( 'line', { y1: M.t, y2: H - M.b, class: 'au-cross', visibility: 'hidden' } );
		const dots = SERIES.map( ( s ) => svg( 'circle', { r: 5, class: 'au-dot', style: 'fill:' + s.color, visibility: 'hidden' } ) );
		root.append( cross, ...dots );
		const hit = svg( 'rect', { x: M.l, y: M.t, width: W - M.l - M.r, height: H - M.t - M.b, fill: 'transparent' } );
		root.append( hit );
		const wrap = el( 'div', { class: 'au-chart' }, [ root ] );
		const tip = el( 'div', { class: 'au-tip', hidden: true, role: 'status' } );
		wrap.append( tip );
		const show = ( evt ) => {
			const box = root.getBoundingClientRect();
			const px = ( ( evt.clientX - box.left ) / box.width ) * W;
			const i = Math.max( 0, Math.min( days.length - 1, Math.round( ( ( px - M.l ) / ( W - M.l - M.r ) ) * ( days.length - 1 ) ) ) );
			cross.setAttribute( 'x1', x( i ) );
			cross.setAttribute( 'x2', x( i ) );
			cross.setAttribute( 'visibility', 'visible' );
			dots.forEach( ( d, k ) => {
				d.setAttribute( 'cx', x( i ) );
				d.setAttribute( 'cy', y( values[ k ][ i ] ) );
				d.setAttribute( 'visibility', 'visible' );
			} );
			tip.replaceChildren(
				el( 'strong', {}, days[ i ] ),
				...SERIES.map( ( s, k ) => el( 'div', { class: 'au-tip__row' }, [ el( 'span', { class: 'au-swatch', style: 'background:' + s.color } ), s.label + ' ', el( 'b', {}, nf.format( values[ k ][ i ] ) ) ] ) )
			);
			tip.hidden = false;
			const left = ( x( i ) / W ) * box.width;
			tip.style.left = Math.min( box.width - 190, Math.max( 0, left + 12 ) ) + 'px';
		};
		hit.addEventListener( 'pointermove', show );
		hit.addEventListener( 'pointerleave', () => {
			tip.hidden = true;
			cross.setAttribute( 'visibility', 'hidden' );
			dots.forEach( ( d ) => d.setAttribute( 'visibility', 'hidden' ) );
		} );

		const legend = el( 'ul', { class: 'au-legend' }, SERIES.map( ( s ) => el( 'li', {}, [ el( 'span', { class: 'au-swatch', style: 'background:' + s.color } ), s.label ] ) ) );
		const tableView = table( [ '', ...SERIES.map( ( s ) => s.label ) ], daily.map( ( d, i ) => [ d[ 0 ], ...values.map( ( v ) => nf.format( v[ i ] ) ) ] ).reverse() );
		tableView.hidden = true;
		const toggle = el( 'button', { type: 'button', class: 'button button-small', 'aria-pressed': 'false' }, T.table );
		toggle.addEventListener( 'click', () => {
			const on = tableView.hidden;
			tableView.hidden = ! on;
			wrap.hidden = on;
			toggle.setAttribute( 'aria-pressed', on ? 'true' : 'false' );
		} );
		return { body: el( 'div', {}, [ legend, wrap, el( 'div', { class: 'au-table-scroll' }, tableView ) ] ), toggle };
	}

	function render( data ) {
		const t = data.totals || {};
		const cartRate = t.product_view ? ( ( t.add_to_cart / t.product_view ) * 100 ).toFixed( 1 ) + '%' : '—';
		const chart = lineChart( data.daily || [] );
		app.replaceChildren(
			...( A.enabled ? [] : [ el( 'div', { class: 'notice notice-warning inline' }, el( 'p', {}, T.disabled ) ) ] ),
			el( 'div', { class: 'au-kpis' }, [
				tile( T.pageViews, nf.format( t.page_view ) ),
				tile( T.productViews, nf.format( t.product_view ) ),
				tile( T.addToCart, nf.format( t.add_to_cart ), T.rate + ' ' + cartRate ),
				tile( T.waOrders, nf.format( t.whatsapp_order ) ),
				tile( T.waValue, data.currency + nf.format( Math.round( data.value || 0 ) ) ),
				tile( T.waEnquiries, nf.format( t.whatsapp_enquiry ) ),
				tile( T.chat, nf.format( t.chat_message ) ),
				tile( T.social, nf.format( t.social_click ) ),
				tile( T.signups, nf.format( t.newsletter_signup ) ),
			] ),
			card( T.traffic, chart.body, chart.toggle ),
			el( 'div', { class: 'au-grid-2' }, [
				card( T.topProducts, table( [ T.product, T.views, T.carts, T.rate ], ( data.top || [] ).map( ( r ) => [ r.url ? el( 'a', { href: r.url }, r.name ) : r.name, nf.format( r.views ), nf.format( r.carts ), r.views ? ( ( r.carts / r.views ) * 100 ).toFixed( 1 ) + '%' : '—' ] ) ) ),
				card( T.sources, barList( data.sources || [] ) ),
				card( T.platforms, barList( data.platforms || [] ) ),
				card( T.posts, table( [ T.post, T.platform, T.clicks ], ( data.posts || [] ).map( ( r ) => [ r.title || '#' + r.post, r.platform, nf.format( r.clicks ) ] ) ) ),
				card( T.topPages, barList( data.pages || [] ) ),
				card( T.devices, barList( data.devices || [] ) ),
			] ),
			el( 'p', { class: 'description' }, T.privacy )
		);
	}

	render( A.initial || {} );
	range?.addEventListener( 'change', async () => {
		app.setAttribute( 'aria-busy', 'true' );
		try {
			render( await wp.apiFetch( { path: '/aurelia/v1/analytics?days=' + range.value } ) );
		} finally {
			app.removeAttribute( 'aria-busy' );
		}
	} );
}() );
