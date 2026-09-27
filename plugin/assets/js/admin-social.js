/**
 * Aurelia Commerce — social scheduler (wp-admin).
 */
( function () {
	'use strict';
	const S = window.aureliaSocial || {};
	const T = S.i18n || {};
	const app = document.getElementById( 'aurelia-social-app' );
	if ( ! app ) {
		return;
	}
	const LABELS = { instagram: 'Instagram', facebook: 'Facebook', pinterest: 'Pinterest', x: 'X', linkedin: 'LinkedIn', whatsapp_channel: 'WhatsApp Channel', youtube: 'YouTube' };
	let catalog = [];
	let posts = [];
	let editing = null;

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
			} else if ( k === 'checked' ) {
				n.checked = !! v;
			} else {
				n.setAttribute( k, v === true ? '' : v );
			}
		} );
		( Array.isArray( children ) ? children : [ children ] ).forEach( ( c ) => c !== null && c !== undefined && n.append( c instanceof Node ? c : document.createTextNode( String( c ) ) ) );
		return n;
	}
	const field = ( label, control, id ) => el( 'p', { class: 'au-field' }, [ el( 'label', { for: id }, label ), control ] );

	const blank = () => ( { id: 0, caption: '', hashtags: '', mediaUrl: S.media || '', mediaType: S.media && /\.(mp4|webm|mov)$/i.test( S.media ) ? 'video' : 'image', link: '', productId: 0, platforms: [ 'instagram', 'facebook' ], scheduledAt: '' } );

	function composer() {
		const p = editing || blank();
		const notice = el( 'p', { class: 'au-notice', role: 'status' } );
		const product = el( 'select', { id: 'au-s-product' }, [ el( 'option', { value: '0' }, T.noProduct ), ...catalog.map( ( c ) => el( 'option', { value: String( c.id ) }, c.name + ' — ' + c.price ) ) ] );
		product.value = String( p.productId || 0 );
		const media = el( 'input', { id: 'au-s-media', type: 'url', class: 'regular-text', value: p.mediaUrl } );
		const mediaType = el( 'select', { id: 'au-s-type' }, [ el( 'option', { value: 'image' }, T.image ), el( 'option', { value: 'video' }, T.video ), el( 'option', { value: 'none' }, T.none ) ] );
		mediaType.value = p.mediaType || 'image';
		const link = el( 'input', { id: 'au-s-link', type: 'url', class: 'regular-text', value: p.link } );
		const caption = el( 'textarea', { id: 'au-s-caption', rows: '6', class: 'large-text' } );
		caption.value = p.caption;
		const tags = el( 'input', { id: 'au-s-tags', type: 'text', class: 'large-text', value: p.hashtags } );
		const when = el( 'input', { id: 'au-s-when', type: 'datetime-local', value: p.scheduledAt } );
		const preview = el( 'div', { class: 'au-s-preview' } );
		const paintPreview = () => {
			preview.replaceChildren();
			if ( media.value && mediaType.value === 'image' ) {
				preview.append( el( 'img', { src: media.value, alt: '' } ) );
			} else if ( media.value && mediaType.value === 'video' ) {
				preview.append( el( 'video', { src: media.value, controls: true, muted: true } ) );
			}
		};
		media.addEventListener( 'input', paintPreview );
		mediaType.addEventListener( 'change', paintPreview );
		product.addEventListener( 'change', () => {
			const c = catalog.find( ( x ) => String( x.id ) === product.value );
			if ( c ) {
				if ( ! media.value ) {
					media.value = c.image;
				}
				link.value = c.url;
				paintPreview();
			}
		} );
		const pick = el( 'button', { type: 'button', class: 'button' }, T.pickMedia );
		pick.addEventListener( 'click', () => {
			const frame = wp.media( { multiple: false } );
			frame.on( 'select', () => {
				const a = frame.state().get( 'selection' ).first().toJSON();
				media.value = a.url;
				mediaType.value = a.type === 'video' ? 'video' : 'image';
				paintPreview();
			} );
			frame.open();
		} );
		const ai = el( 'button', { type: 'button', class: 'button' }, T.aiWrite );
		ai.addEventListener( 'click', async () => {
			ai.disabled = true;
			ai.textContent = T.aiWriting;
			try {
				const r = await wp.apiFetch( { path: '/aurelia/v1/social/caption', method: 'POST', data: { productId: Number( product.value ), brief: caption.value } } );
				caption.value = r.caption;
				tags.value = r.hashtags;
			} catch ( e ) {
				notice.textContent = T.error + e.message;
			}
			ai.disabled = false;
			ai.textContent = T.aiWrite;
		} );
		const platforms = el( 'fieldset', { class: 'au-platforms' }, [
			el( 'legend', {}, T.platforms ),
			...( S.platforms || [] ).map( ( k ) => el( 'label', {}, [ el( 'input', { type: 'checkbox', value: k, checked: ( p.platforms || [] ).includes( k ) } ), ' ' + ( LABELS[ k ] || k ) ] ) ),
		] );
		const collect = ( publish ) => ( {
			id: p.id,
			productId: Number( product.value ),
			mediaUrl: media.value,
			mediaType: mediaType.value,
			link: link.value,
			caption: caption.value,
			hashtags: tags.value,
			platforms: Array.from( platforms.querySelectorAll( 'input:checked' ) ).map( ( i ) => i.value ),
			scheduledAt: when.value,
			publish,
		} );
		const submit = async ( publish ) => {
			try {
				await wp.apiFetch( { path: '/aurelia/v1/social/posts', method: 'POST', data: collect( publish ) } );
				notice.textContent = publish ? T.published : T.saved;
				editing = null;
				await load();
			} catch ( e ) {
				notice.textContent = T.error + e.message;
			}
		};
		paintPreview();
		return el( 'section', { class: 'au-card au-composer' }, [
			el( 'div', { class: 'au-card__head' }, [ el( 'h2', {}, editing ? T.edit : T.compose ), editing ? el( 'button', { type: 'button', class: 'button-link', onclick: () => ( ( editing = null ), draw() ) }, T.new ) : null ] ),
			! S.webhook && ! S.meta ? el( 'div', { class: 'notice notice-info inline' }, el( 'p', {}, [ T.noPublisher + ' ', el( 'a', { href: S.settings }, '→' ) ] ) ) : null,
			el( 'div', { class: 'au-composer__grid' }, [
				el( 'div', {}, [
					field( T.product, product, 'au-s-product' ),
					field( T.media, el( 'span', { class: 'au-inline' }, [ media, pick ] ), 'au-s-media' ),
					field( T.mediaType, mediaType, 'au-s-type' ),
					field( T.link, link, 'au-s-link' ),
					field( T.caption, caption, 'au-s-caption' ),
					el( 'p', {}, ai ),
					field( T.hashtags, tags, 'au-s-tags' ),
					platforms,
					field( T.schedule + ' (' + S.timezone + ')', when, 'au-s-when' ),
					el( 'p', { class: 'au-actions' }, [ el( 'button', { type: 'button', class: 'button button-primary', onclick: () => submit( false ) }, T.save ), ' ', el( 'button', { type: 'button', class: 'button', onclick: () => submit( true ) }, T.publishNow ) ] ),
					notice,
				] ),
				preview,
			] ),
		] );
	}

	function list() {
		if ( ! posts.length ) {
			return el( 'p', { class: 'au-empty' }, T.empty );
		}
		return el( 'table', { class: 'widefat striped au-table' }, [
			el( 'thead', {}, el( 'tr', {}, [ T.caption, T.status, T.when, T.links, T.actions ].map( ( h ) => el( 'th', { scope: 'col' }, h ) ) ) ),
			el(
				'tbody',
				{},
				posts.map( ( p ) =>
					el( 'tr', {}, [
						el( 'td', {}, [ p.mediaUrl && p.mediaType === 'image' ? el( 'img', { src: p.mediaUrl, alt: '', width: '44', height: '44', class: 'au-thumb' } ) : null, ( p.caption || '' ).slice( 0, 90 ) ] ),
						el( 'td', {}, [
							el( 'span', { class: 'au-status au-status--' + p.status }, p.status ),
							...Object.entries( p.results || {} ).map( ( [ k, r ] ) => el( 'div', { class: 'au-result ' + ( r.ok ? 'is-ok' : 'is-bad' ) }, ( LABELS[ k ] || k ) + ': ' + ( r.ok ? '✓' : r.error || '✗' ) ) ),
						] ),
						el( 'td', {}, p.scheduledAt || '—' ),
						el(
							'td',
							{},
							Object.entries( p.links || {} ).map( ( [ k, url ] ) =>
								el( 'div', { class: 'au-link-row' }, [
									( LABELS[ k ] || k ) + ' · ' + ( p.clicks[ k ] || 0 ) + ' ' + T.clicks + ' ',
									el(
										'button',
										{
											type: 'button',
											class: 'button-link',
											onclick: async ( e ) => {
												await navigator.clipboard.writeText( url );
												e.target.textContent = T.copied;
											},
										},
										T.copy
									),
								] )
							)
						),
						el( 'td', {}, [
							el( 'button', { type: 'button', class: 'button-link', onclick: () => ( ( editing = p ), draw(), window.scrollTo( 0, 0 ) ) }, T.edit ),
							' · ',
							el(
								'button',
								{
									type: 'button',
									class: 'button-link',
									onclick: async () => {
										await wp.apiFetch( { path: '/aurelia/v1/social/posts/' + p.id + '/publish', method: 'POST' } );
										load();
									},
								},
								T.publishNow
							),
							' · ',
							el(
								'button',
								{
									type: 'button',
									class: 'button-link is-destructive',
									onclick: async () => {
										if ( window.confirm( T.confirmDel ) ) { // eslint-disable-line no-alert
											await wp.apiFetch( { path: '/aurelia/v1/social/posts/' + p.id, method: 'DELETE' } );
											load();
										}
									},
								},
								T.delete
							),
						] ),
					] )
				)
			),
		] );
	}

	function draw() {
		app.replaceChildren( composer(), el( 'section', { class: 'au-card' }, [ el( 'div', { class: 'au-card__head' }, el( 'h2', {}, T.posts ) ), list() ] ) );
	}

	async function load() {
		posts = await wp.apiFetch( { path: '/aurelia/v1/social/posts' } );
		draw();
	}

	( async () => {
		catalog = await wp.apiFetch( { path: '/aurelia/v1/catalog' } );
		await load();
	} )();
}() );
