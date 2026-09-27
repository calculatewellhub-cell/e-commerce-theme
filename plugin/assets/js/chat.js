/**
 * Aurelia Commerce — AI shopping concierge widget.
 * The Claude API is called server-side; this script only talks to /aurelia/v1/chat.
 */
( function () {
	'use strict';

	const C = window.aureliaCommerce || {};
	const CHAT = C.chat;
	const T = C.i18n || {};
	if ( ! CHAT ) {
		return;
	}
	const KEY = 'au-chat';
	let msgs = [];
	try {
		msgs = JSON.parse( sessionStorage.getItem( KEY ) ) || [];
	} catch ( e ) {}
	let busy = false;
	let panel = null;
	let list = null;
	let input = null;
	let sendBtn = null;
	let handoff = null;

	const spark = '<svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 2l1.9 5.6L19.5 9.5l-5.6 1.9L12 17l-1.9-5.6L4.5 9.5l5.6-1.9zM19 14l.9 2.6 2.6.9-2.6.9L19 21l-.9-2.6-2.6-.9 2.6-.9z"/></svg>';

	const fab = document.createElement( 'button' );
	fab.type = 'button';
	fab.className = 'au-chat-fab';
	fab.setAttribute( 'aria-label', T.chatOpen || 'Open chat' );
	fab.setAttribute( 'aria-expanded', 'false' );
	fab.innerHTML = spark + '<span></span>';
	fab.querySelector( 'span' ).textContent = T.chatAsk || 'Ask';
	if ( C.whatsapp && C.whatsapp.number ) {
		fab.classList.add( 'has-wa' );
	}
	document.body.append( fab );
	fab.addEventListener( 'click', open );

	const save = () => {
		try {
			sessionStorage.setItem( KEY, JSON.stringify( msgs.slice( -20 ) ) );
		} catch ( e ) {}
	};

	/** Plain text with markdown links and **bold**; only same-site links become anchors. */
	function rich( text ) {
		const frag = document.createDocumentFragment();
		String( text )
			.split( /(\[[^\]]+\]\([^)\s]+\)|\*\*[^*]+\*\*)/g )
			.forEach( ( part ) => {
				const link = part.match( /^\[([^\]]+)\]\(([^)\s]+)\)$/ );
				if ( link ) {
					let url = null;
					try {
						url = new URL( link[ 2 ], location.origin );
					} catch ( e ) {}
					if ( url && url.origin === location.origin ) {
						const a = document.createElement( 'a' );
						a.href = url.href;
						a.textContent = link[ 1 ];
						frag.append( a );
					} else {
						frag.append( link[ 1 ] );
					}
					return;
				}
				const bold = part.match( /^\*\*([^*]+)\*\*$/ );
				if ( bold ) {
					const b = document.createElement( 'strong' );
					b.textContent = bold[ 1 ];
					frag.append( b );
					return;
				}
				frag.append( part );
			} );
		return frag;
	}

	function bubble( role, content ) {
		const b = document.createElement( 'div' );
		b.className = 'au-bubble au-bubble--' + role;
		if ( content === null ) {
			b.innerHTML = '<span class="au-typing"><i></i><i></i><i></i></span>';
			b.setAttribute( 'aria-label', T.chatTyping || '' );
		} else {
			b.append( rich( content ) );
		}
		return b;
	}

	function render() {
		list.replaceChildren( bubble( 'assistant', CHAT.greeting ) );
		msgs.forEach( ( m ) => list.append( bubble( m.role, m.content ) ) );
		if ( ! msgs.length && CHAT.suggestions.length ) {
			const chips = document.createElement( 'div' );
			chips.className = 'au-chips';
			CHAT.suggestions.forEach( ( s ) => {
				const c = document.createElement( 'button' );
				c.type = 'button';
				c.textContent = s;
				c.addEventListener( 'click', () => send( s ) );
				chips.append( c );
			} );
			list.append( chips );
		}
		list.scrollTop = list.scrollHeight;
		updateHandoff();
	}

	function updateHandoff() {
		if ( ! handoff ) {
			return;
		}
		const transcript = msgs
			.slice( -6 )
			.map( ( m ) => ( m.role === 'user' ? T.chatMe || 'Me' : CHAT.name ) + ': ' + m.content.replace( /\[([^\]]+)\]\([^)]+\)/g, '$1' ) )
			.join( '\n' );
		handoff.href = 'https://wa.me/' + C.whatsapp.number + '?text=' + encodeURIComponent( ( T.chatHandoffIntro || '' ) + ( transcript ? '\n\n' + transcript : '' ) );
	}

	function build() {
		panel = document.createElement( 'section' );
		panel.className = 'au-chat';
		panel.setAttribute( 'role', 'dialog' );
		panel.setAttribute( 'aria-label', T.chatTitle || 'Chat' );
		panel.innerHTML =
			'<header class="au-chat__head"><span class="au-chat__avatar">' + spark + '</span><div class="au-chat__title"><strong></strong><span class="au-chat__status"></span></div><button type="button" class="au-chat__close"><span aria-hidden="true">×</span></button></header>' +
			'<div class="au-chat__body" aria-live="polite"></div>' +
			'<form class="au-chat__input"><label class="screen-reader-text" for="au-chat-input"></label><input id="au-chat-input" maxlength="500" autocomplete="off"><button type="submit"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M22 2 11 13M22 2l-7 20-4-9-9-4z"/></svg></button></form>';
		panel.querySelector( '.au-chat__title strong' ).textContent = CHAT.name + ' · ' + ( T.chatTitle || '' );
		panel.querySelector( '.au-chat__status' ).textContent = T.chatStatus || '';
		panel.querySelector( '.au-chat__close' ).setAttribute( 'aria-label', T.chatClose || 'Close' );
		panel.querySelector( 'label' ).textContent = T.chatMessage || 'Message';
		list = panel.querySelector( '.au-chat__body' );
		input = panel.querySelector( 'input' );
		input.placeholder = T.chatPlace || '';
		sendBtn = panel.querySelector( 'button[type=submit]' );
		sendBtn.setAttribute( 'aria-label', T.chatSend || 'Send' );
		if ( C.whatsapp && C.whatsapp.number ) {
			handoff = document.createElement( 'a' );
			handoff.className = 'au-chat__handoff';
			handoff.target = '_blank';
			handoff.rel = 'noopener noreferrer';
			handoff.setAttribute( 'data-au-track', 'whatsapp_enquiry' );
			handoff.textContent = T.chatHandoff || 'WhatsApp';
			panel.querySelector( 'form' ).before( handoff );
		}
		panel.querySelector( '.au-chat__close' ).addEventListener( 'click', close );
		panel.addEventListener( 'keydown', ( e ) => e.key === 'Escape' && close() );
		panel.querySelector( 'form' ).addEventListener( 'submit', ( e ) => {
			e.preventDefault();
			send( input.value );
		} );
		document.body.append( panel );
		render();
	}

	function open() {
		if ( ! panel ) {
			build();
		}
		panel.classList.add( 'is-open' );
		fab.classList.add( 'is-hidden' );
		fab.setAttribute( 'aria-expanded', 'true' );
		input.focus();
	}

	function close() {
		panel.classList.remove( 'is-open' );
		fab.classList.remove( 'is-hidden' );
		fab.setAttribute( 'aria-expanded', 'false' );
		fab.focus();
	}

	async function send( text ) {
		const content = String( text || '' ).trim();
		if ( ! content || busy ) {
			return;
		}
		busy = true;
		sendBtn.disabled = true;
		input.value = '';
		msgs.push( { role: 'user', content } );
		render();
		const typing = bubble( 'assistant', null );
		list.append( typing );
		list.scrollTop = list.scrollHeight;
		try {
			const res = await fetch( C.rest + 'chat', {
				method: 'POST',
				credentials: 'same-origin',
				headers: { 'Content-Type': 'application/json' },
				body: JSON.stringify( { messages: msgs.slice( -12 ) } ),
			} );
			const data = await res.json();
			msgs.push( { role: 'assistant', content: res.ok ? data.reply : data.message || T.chatError } );
		} catch ( e ) {
			msgs.push( { role: 'assistant', content: T.chatError || 'Error' } );
		}
		save();
		busy = false;
		sendBtn.disabled = false;
		render();
		input.focus();
	}
}() );
