/**
 * Aurelia Commerce — Image → Video studio (port of the reference VideoStudio).
 * Renders in the browser with canvas + MediaRecorder, then saves to the Media Library.
 */
( function () {
	'use strict';
	const S = window.aureliaStudio || {};
	const T = S.i18n || {};
	const app = document.getElementById( 'aurelia-studio-app' );
	if ( ! app ) {
		return;
	}
	const SIZES = { reel: [ 720, 1280, T.reel ], square: [ 1080, 1080, T.square ], landscape: [ 1280, 720, T.landscape ] };
	const FPS = 30;
	const TRANSITION = 0.7;
	const OUTRO = 2.2;
	const state = { slides: [], format: 'reel', transition: 'zoom', seconds: 2.8, cta: T.ctaDefault, outro: T.outroDefault, music: null, accent: '#f1dca4', bg: '#0b1a17', recording: false };
	const images = new Map();
	let catalog = [];
	let canvas;
	let raf = 0;

	const ease = ( x ) => ( x < 0.5 ? 4 * x * x * x : 1 - Math.pow( -2 * x + 2, 3 ) / 2 );
	const load = ( src ) =>
		new Promise( ( resolve, reject ) => {
			if ( images.has( src ) ) {
				resolve( images.get( src ) );
				return;
			}
			const img = new Image();
			img.crossOrigin = 'anonymous';
			img.onload = () => {
				images.set( src, img );
				resolve( img );
			};
			img.onerror = reject;
			img.src = src;
		} );
	const pickMime = () => [ 'video/mp4;codecs=avc1.42E01E,mp4a.40.2', 'video/mp4', 'video/webm;codecs=vp9,opus', 'video/webm;codecs=vp8,opus', 'video/webm' ].find( ( m ) => window.MediaRecorder && MediaRecorder.isTypeSupported( m ) ) || '';

	function el( tag, attrs = {}, children = [] ) {
		const n = document.createElement( tag );
		Object.entries( attrs ).forEach( ( [ k, v ] ) => {
			if ( v === null || v === undefined || v === false ) {
				return;
			}
			if ( k === 'class' ) {
				n.className = v;
			} else if ( k.startsWith( 'on' ) ) {
				n.addEventListener( k.slice( 2 ), v );
			} else if ( k === 'value' ) {
				n.value = v;
			} else {
				n.setAttribute( k, v === true ? '' : v );
			}
		} );
		( Array.isArray( children ) ? children : [ children ] ).forEach( ( c ) => c !== null && c !== undefined && n.append( c instanceof Node ? c : document.createTextNode( String( c ) ) ) );
		return n;
	}

	function cover( ctx, img, W, H, scale, dx, dy ) {
		const r = Math.max( W / img.width, H / img.height ) * scale;
		ctx.drawImage( img, ( W - img.width * r ) / 2 + dx, ( H - img.height * r ) / 2 + dy, img.width * r, img.height * r );
	}
	function wrap( ctx, text, max ) {
		const lines = [];
		let line = '';
		String( text ).split( /\s+/ ).forEach( ( w ) => {
			const test = line ? line + ' ' + w : w;
			if ( ctx.measureText( test ).width > max && line ) {
				lines.push( line );
				line = w;
			} else {
				line = test;
			}
		} );
		if ( line ) {
			lines.push( line );
		}
		return lines;
	}
	function sparkle( ctx, x, y, s, a ) {
		ctx.save();
		ctx.globalAlpha = a;
		ctx.fillStyle = '#fff';
		ctx.beginPath();
		ctx.moveTo( x, y - s );
		ctx.quadraticCurveTo( x, y, x + s, y );
		ctx.quadraticCurveTo( x, y, x, y + s );
		ctx.quadraticCurveTo( x, y, x - s, y );
		ctx.quadraticCurveTo( x, y, x, y - s );
		ctx.fill();
		ctx.restore();
	}
	const total = () => state.slides.length * state.seconds + OUTRO;

	function draw( t ) {
		const [ W, H ] = SIZES[ state.format ];
		const ctx = canvas.getContext( '2d' );
		const u = Math.min( W, H ) / 100;
		const slides = state.slides;
		const secs = state.seconds;
		const bg = ctx.createRadialGradient( W / 2, H * 0.35, 0, W / 2, H * 0.35, Math.max( W, H ) );
		bg.addColorStop( 0, state.bg );
		bg.addColorStop( 1, '#050505' );
		ctx.fillStyle = bg;
		ctx.fillRect( 0, 0, W, H );

		const slideT = slides.length * secs;
		const inOutro = t >= slideT;
		const i = Math.min( slides.length - 1, Math.floor( t / secs ) );
		const local = inOutro ? 1 : ( t - i * secs ) / secs;
		const paint = ( idx, p, alpha, offsetX = 0 ) => {
			const s = slides[ idx ];
			const img = s && images.get( s.src );
			if ( ! img ) {
				return;
			}
			ctx.save();
			ctx.globalAlpha = alpha;
			const zoom = state.transition === 'zoom' || state.transition === 'shine' ? 1.02 + 0.12 * p : 1.04;
			const pan = state.transition === 'zoom' ? ( idx % 2 ? -1 : 1 ) * u * 3 * p : 0;
			cover( ctx, img, W, H, zoom, pan + offsetX, -u * 2 * p );
			ctx.restore();
		};
		if ( slides.length ) {
			if ( inOutro ) {
				paint( slides.length - 1, 1, 0.35 );
			} else {
				const tEnd = secs - TRANSITION;
				const inTrans = t - i * secs > tEnd && i < slides.length - 1;
				const k = inTrans ? ease( ( t - i * secs - tEnd ) / TRANSITION ) : 0;
				if ( state.transition === 'slide' && inTrans ) {
					paint( i, local, 1, -W * k );
					paint( i + 1, 0, 1, W * ( 1 - k ) );
				} else {
					paint( i, local, 1 );
					if ( inTrans ) {
						paint( i + 1, 0, k );
					}
				}
				if ( state.transition === 'shine' ) {
					const x = ( ( t % secs ) / secs ) * ( W + H ) * 1.2 - H;
					const g = ctx.createLinearGradient( x, 0, x + H * 0.5, H );
					g.addColorStop( 0, 'rgba(255,255,255,0)' );
					g.addColorStop( 0.5, 'rgba(255,245,220,0.22)' );
					g.addColorStop( 1, 'rgba(255,255,255,0)' );
					ctx.fillStyle = g;
					ctx.fillRect( 0, 0, W, H );
				}
			}
		}
		const shade = ctx.createLinearGradient( 0, H * 0.45, 0, H );
		shade.addColorStop( 0, 'rgba(0,0,0,0)' );
		shade.addColorStop( 1, 'rgba(0,0,0,0.78)' );
		ctx.fillStyle = shade;
		ctx.fillRect( 0, 0, W, H );

		const barW = ( W - u * 8 - ( slides.length - 1 ) * u ) / Math.max( 1, slides.length );
		slides.forEach( ( _, k ) => {
			const x = u * 4 + k * ( barW + u );
			ctx.fillStyle = 'rgba(255,255,255,0.3)';
			ctx.fillRect( x, u * 3, barW, u * 0.5 );
			ctx.fillStyle = state.accent;
			ctx.fillRect( x, u * 3, barW * ( inOutro || k < i ? 1 : k === i ? local : 0 ), u * 0.5 );
		} );
		ctx.fillStyle = state.accent;
		ctx.font = `500 ${ u * 3 }px ${ S.fonts.body }`;
		ctx.textAlign = 'center';
		ctx.fillText( String( S.store || '' ).toUpperCase(), W / 2, u * 9 );
		for ( let s = 0; s < 7; s++ ) {
			const phase = ( t * 0.8 + s * 0.37 ) % 1;
			sparkle( ctx, ( ( s * 137 ) % 100 ) * u * ( W / Math.min( W, H ) ), ( ( ( s * 71 ) % 60 ) + 10 ) * u * ( H / Math.min( W, H ) ), u * ( 1 + ( s % 3 ) ), Math.sin( phase * Math.PI ) * 0.9 );
		}
		if ( ! inOutro && slides[ i ] ) {
			const appear = ease( Math.min( 1, local * 3 ) );
			ctx.globalAlpha = appear;
			const y0 = H - u * ( state.format === 'landscape' ? 20 : 26 ) + ( 1 - appear ) * u * 4;
			ctx.fillStyle = '#fff';
			ctx.font = `500 ${ u * 7 }px ${ S.fonts.heading }`;
			const lines = wrap( ctx, slides[ i ].title, W - u * 14 );
			lines.forEach( ( l, n ) => ctx.fillText( l, W / 2, y0 + n * u * 7.5 - ( lines.length - 1 ) * u * 7.5 ) );
			if ( slides[ i ].subtitle ) {
				ctx.fillStyle = state.accent;
				ctx.font = `500 ${ u * 4 }px ${ S.fonts.body }`;
				ctx.fillText( slides[ i ].subtitle, W / 2, y0 + u * 7 );
			}
			ctx.globalAlpha = 1;
		}
		if ( inOutro ) {
			const p = ease( Math.min( 1, ( t - slideT ) / 0.6 ) );
			ctx.globalAlpha = p;
			ctx.fillStyle = '#fff';
			ctx.font = `500 ${ u * 8 }px ${ S.fonts.heading }`;
			wrap( ctx, state.outro, W - u * 14 ).forEach( ( l, n ) => ctx.fillText( l, W / 2, H / 2 - u * 4 + n * u * 9 ) );
			ctx.font = `600 ${ u * 3.6 }px ${ S.fonts.body }`;
			const tw = ctx.measureText( state.cta ).width + u * 10;
			const py = H / 2 + u * 10;
			ctx.fillStyle = '#25d366';
			ctx.beginPath();
			ctx.roundRect( W / 2 - tw / 2, py, tw, u * 9, u * 4.5 );
			ctx.fill();
			ctx.fillStyle = '#06301a';
			ctx.fillText( state.cta, W / 2, py + u * 5.8 );
			ctx.fillStyle = 'rgba(255,255,255,.75)';
			ctx.font = `400 ${ u * 3 }px ${ S.fonts.body }`;
			ctx.fillText( S.site || '', W / 2, py + u * 16 );
			ctx.globalAlpha = 1;
		}
	}

	function preview() {
		cancelAnimationFrame( raf );
		const start = performance.now();
		const loop = ( now ) => {
			if ( state.recording ) {
				return;
			}
			draw( ( ( now - start ) / 1000 ) % total() );
			raf = requestAnimationFrame( loop );
		};
		raf = requestAnimationFrame( loop );
	}

	function addSlide( src, title, subtitle ) {
		state.slides.push( { id: src + Math.random(), src, title, subtitle } );
		load( src ).catch( () => ( status.textContent = T.loadFailed + title ) );
		ui();
	}

	const status = el( 'p', { class: 'au-notice', role: 'status' } );
	const output = el( 'div' );

	function ui() {
		const [ W, H ] = SIZES[ state.format ];
		canvas = el( 'canvas', { width: String( W ), height: String( H ), 'aria-label': T.preview } );
		const slideList = el(
			'ol',
			{ class: 'au-slides' },
			state.slides.map( ( s, idx ) =>
				el( 'li', {}, [
					el( 'img', { src: s.src, alt: '', width: '52', height: '52' } ),
					el( 'div', { class: 'au-slides__fields' }, [
						el( 'input', { value: s.title, 'aria-label': T.title, oninput: ( e ) => ( s.title = e.target.value ) } ),
						el( 'input', { value: s.subtitle, 'aria-label': T.subtitle, placeholder: T.subtitle, oninput: ( e ) => ( s.subtitle = e.target.value ) } ),
					] ),
					el( 'div', { class: 'au-slides__actions' }, [
						el( 'button', { type: 'button', class: 'button button-small', 'aria-label': T.up, onclick: () => idx > 0 && ( [ state.slides[ idx - 1 ], state.slides[ idx ] ] = [ state.slides[ idx ], state.slides[ idx - 1 ] ], ui() ) }, '↑' ),
						el( 'button', { type: 'button', class: 'button button-small', 'aria-label': T.down, onclick: () => idx < state.slides.length - 1 && ( [ state.slides[ idx + 1 ], state.slides[ idx ] ] = [ state.slides[ idx ], state.slides[ idx + 1 ] ], ui() ) }, '↓' ),
						el( 'button', { type: 'button', class: 'button button-small', 'aria-label': T.remove, onclick: () => ( state.slides.splice( idx, 1 ), ui() ) }, '✕' ),
					] ),
				] )
			)
		);
		const picker = el( 'select', { 'aria-label': T.addProduct }, [ el( 'option', { value: '' }, T.addProduct ), ...catalog.filter( ( c ) => c.image ).map( ( c ) => el( 'option', { value: String( c.id ) }, c.name ) ) ] );
		picker.addEventListener( 'change', () => {
			const c = catalog.find( ( x ) => String( x.id ) === picker.value );
			if ( c ) {
				addSlide( c.image, c.name, c.price );
			}
		} );
		const library = el( 'button', { type: 'button', class: 'button' }, T.fromLibrary );
		library.addEventListener( 'click', () => {
			const frame = wp.media( { multiple: true, library: { type: 'image' } } );
			frame.on( 'select', () => frame.state().get( 'selection' ).toJSON().forEach( ( a ) => addSlide( a.sizes && a.sizes.large ? a.sizes.large.url : a.url, a.title || '', '' ) ) );
			frame.open();
		} );
		const upload = el( 'input', { type: 'file', accept: 'image/*', multiple: true, id: 'au-studio-upload', class: 'screen-reader-text' } );
		upload.addEventListener( 'change', () => Array.from( upload.files ).forEach( ( f ) => addSlide( URL.createObjectURL( f ), f.name.replace( /\.[^.]+$/, '' ).replace( /[-_]/g, ' ' ), '' ) ) );
		const control = ( label, input ) => el( 'p', { class: 'au-field' }, [ el( 'label', {}, [ label, input ] ) ] );
		const select = ( key, opts ) => {
			const s = el( 'select', { onchange: ( e ) => ( ( state[ key ] = e.target.value ), ui() ) }, Object.entries( opts ).map( ( [ k, v ] ) => el( 'option', { value: k }, v ) ) );
			s.value = state[ key ];
			return s;
		};
		const music = el( 'input', { type: 'file', accept: 'audio/*', onchange: ( e ) => ( state.music = e.target.files[ 0 ] ? URL.createObjectURL( e.target.files[ 0 ] ) : null ) } );
		const renderBtn = el( 'button', { type: 'button', class: 'button button-primary button-hero', disabled: state.slides.length ? null : true, onclick: () => record( renderBtn ) }, T.render );

		app.replaceChildren(
			el( 'p', { class: 'description' }, T.intro ),
			el( 'div', { class: 'au-studio' }, [
				el( 'div', { class: 'au-studio__controls' }, [
					el( 'section', { class: 'au-card' }, [ el( 'h2', {}, T.slides ), slideList, el( 'p', { class: 'au-inline' }, [ picker, library, el( 'label', { class: 'button', for: 'au-studio-upload' }, T.upload ), upload ] ) ] ),
					el( 'section', { class: 'au-card au-studio__style' }, [
						el( 'h2', {}, T.style ),
						control( T.format, select( 'format', { reel: T.reel, square: T.square, landscape: T.landscape } ) ),
						control( T.transition, select( 'transition', { zoom: T.zoom, fade: T.fade, slide: T.slide, shine: T.shine } ) ),
						control( T.seconds + ': ' + state.seconds.toFixed( 1 ), el( 'input', { type: 'range', min: '1.5', max: '6', step: '0.1', value: String( state.seconds ), onchange: ( e ) => ( ( state.seconds = Number( e.target.value ) ), ui() ) } ) ),
						control( T.music, music ),
						control( T.outro, el( 'input', { value: state.outro, oninput: ( e ) => ( state.outro = e.target.value ) } ) ),
						control( T.cta, el( 'input', { value: state.cta, oninput: ( e ) => ( state.cta = e.target.value ) } ) ),
						control( T.accent, el( 'input', { type: 'color', value: state.accent, oninput: ( e ) => ( state.accent = e.target.value ) } ) ),
						control( T.background, el( 'input', { type: 'color', value: state.bg, oninput: ( e ) => ( state.bg = e.target.value ) } ) ),
					] ),
				] ),
				el( 'div', { class: 'au-studio__preview' }, [
					el( 'div', { class: 'au-canvas-frame is-' + state.format }, canvas ),
					el( 'p', { class: 'description' }, `${ W }×${ H } · ${ total().toFixed( 1 ) }s · ${ T.preview }` ),
					renderBtn,
					status,
					output,
				] ),
			] )
		);
		preview();
	}

	async function record( button ) {
		const mime = pickMime();
		if ( ! mime || ! canvas.captureStream ) {
			status.textContent = T.noRecorder;
			return;
		}
		status.textContent = '';
		output.replaceChildren();
		state.recording = true;
		cancelAnimationFrame( raf );
		button.disabled = true;
		await Promise.all( state.slides.map( ( s ) => load( s.src ).catch( () => null ) ) );
		if ( document.fonts ) {
			await document.fonts.ready;
		}
		const stream = canvas.captureStream( FPS );
		let audio = null;
		let actx = null;
		const duration = total();
		if ( state.music ) {
			audio = new Audio( state.music );
			actx = new AudioContext();
			const src = actx.createMediaElementSource( audio );
			const gain = actx.createGain();
			const dest = actx.createMediaStreamDestination();
			src.connect( gain ).connect( dest );
			gain.gain.setValueAtTime( 1, actx.currentTime + Math.max( 0, duration - 1 ) );
			gain.gain.linearRampToValueAtTime( 0, actx.currentTime + duration );
			dest.stream.getAudioTracks().forEach( ( tr ) => stream.addTrack( tr ) );
		}
		const chunks = [];
		const rec = new MediaRecorder( stream, { mimeType: mime, videoBitsPerSecond: 8000000 } );
		rec.ondataavailable = ( e ) => e.data.size && chunks.push( e.data );
		const stopped = new Promise( ( r ) => ( rec.onstop = r ) );
		rec.start( 250 );
		audio?.play().catch( () => {} );
		const start = performance.now();
		await new Promise( ( resolve ) => {
			const tick = ( now ) => {
				const t = ( now - start ) / 1000;
				draw( Math.min( t, duration - 0.001 ) );
				button.textContent = T.rendering + ' ' + Math.round( Math.min( 1, t / duration ) * 100 ) + '%';
				if ( t < duration ) {
					requestAnimationFrame( tick );
				} else {
					resolve();
				}
			};
			requestAnimationFrame( tick );
		} );
		rec.stop();
		await stopped;
		audio?.pause();
		actx?.close();
		stream.getTracks().forEach( ( tr ) => tr.stop() );
		const type = mime.split( ';' )[ 0 ];
		const ext = type.includes( 'mp4' ) ? 'mp4' : 'webm';
		const blob = new Blob( chunks, { type } );
		const url = URL.createObjectURL( blob );
		const name = `aurelia-${ state.format }-${ Date.now() }.${ ext }`;
		const save = el( 'button', { type: 'button', class: 'button button-primary' }, T.save );
		const saveStatus = el( 'span', { role: 'status' } );
		save.addEventListener( 'click', async () => {
			save.disabled = true;
			saveStatus.textContent = T.saving;
			try {
				const media = await wp.apiFetch( { path: '/wp/v2/media', method: 'POST', body: blob, headers: { 'Content-Type': type, 'Content-Disposition': 'attachment; filename="' + name + '"' } } );
				saveStatus.replaceChildren( T.saved + ' ', el( 'a', { class: 'button', href: S.social + '&media=' + encodeURIComponent( media.source_url ) }, T.schedule ) );
			} catch ( e ) {
				save.disabled = false;
				saveStatus.textContent = e.message;
			}
		} );
		output.replaceChildren(
			el( 'section', { class: 'au-card' }, [
				el( 'video', { src: url, controls: true, playsinline: true, class: 'au-studio__video' } ),
				el( 'p', { class: 'description' }, type + ' · ' + ( blob.size / 1048576 ).toFixed( 1 ) + ' MB' ),
				el( 'p', { class: 'au-inline' }, [ el( 'a', { class: 'button', href: url, download: name }, T.download ), save, saveStatus ] ),
			] )
		);
		state.recording = false;
		button.disabled = false;
		button.textContent = T.render;
		preview();
	}

	( async () => {
		try {
			catalog = await wp.apiFetch( { path: '/aurelia/v1/catalog' } );
		} catch ( e ) {}
		catalog.filter( ( c ) => c.image ).slice( 0, 3 ).forEach( ( c ) => addSlide( c.image, c.name, c.price ) );
		ui();
	} )();
}() );
