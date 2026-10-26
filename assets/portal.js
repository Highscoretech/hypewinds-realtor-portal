/* Hypewinds realtor portal: password show/hide, copy buttons, confirm prompts. */
( function () {
	var eyeOpen = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">'
		+ '<path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7S1 12 1 12z"/><circle cx="12" cy="12" r="3"/></svg>';
	var eyeShut = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">'
		+ '<path d="M17.9 17.9A10.6 10.6 0 0 1 12 19c-7 0-11-7-11-7a19 19 0 0 1 5.1-5.9m3.8-1A10.6 10.6 0 0 1 12 5c7 0 11 7 11 7a19 19 0 0 1-2.2 3.2M1 1l22 22"/></svg>';
	var tick = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"'
		+ ' stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 6 9 17l-5-5"/></svg>';

	// Show/hide control on every password field.
	document.querySelectorAll( '.hrp input[type="password"]' ).forEach( function ( field ) {
		var wrap = document.createElement( 'span' );
		wrap.className = 'hrp-pass-wrap';
		field.parentNode.insertBefore( wrap, field );
		wrap.appendChild( field );

		var button = document.createElement( 'button' );
		button.type = 'button';
		button.className = 'hrp-eye';
		button.innerHTML = eyeOpen;
		button.setAttribute( 'aria-label', 'Show password' );

		button.addEventListener( 'click', function () {
			var hidden = 'password' === field.type;
			field.type = hidden ? 'text' : 'password';
			button.innerHTML = hidden ? eyeShut : eyeOpen;
			button.setAttribute( 'aria-label', hidden ? 'Hide password' : 'Show password' );
		} );

		wrap.appendChild( button );
	} );

	// navigator.clipboard needs a secure origin, so a hidden-textarea fallback
	// keeps the copy buttons working on plain http (e.g. local testing).
	function fallbackCopy( text ) {
		var area = document.createElement( 'textarea' );
		area.value = text;
		area.setAttribute( 'readonly', '' );
		area.style.position = 'fixed';
		area.style.opacity = '0';
		document.body.appendChild( area );
		area.select();
		try { document.execCommand( 'copy' ); } catch ( e ) {}
		document.body.removeChild( area );
	}

	document.querySelectorAll( '.hrp-copy' ).forEach( function ( button ) {
		button.addEventListener( 'click', function () {
			var text = button.getAttribute( 'data-copy' );
			var icon = button.innerHTML;

			// The button has no label, so the icon itself confirms: a tick, then back.
			function done() {
				button.innerHTML = tick;
				button.classList.add( 'is-copied' );
				setTimeout( function () {
					button.innerHTML = icon;
					button.classList.remove( 'is-copied' );
				}, 1600 );
			}

			if ( navigator.clipboard && window.isSecureContext ) {
				navigator.clipboard.writeText( text ).then( done, function () {
					fallbackCopy( text );
					done();
				} );
			} else {
				fallbackCopy( text );
				done();
			}
		} );
	} );

	document.querySelectorAll( '.hrp-copy-value' ).forEach( function ( input ) {
		input.addEventListener( 'focus', function () { input.select(); } );
	} );

	document.querySelectorAll( 'form[data-confirm]' ).forEach( function ( form ) {
		form.addEventListener( 'submit', function ( event ) {
			if ( ! window.confirm( form.getAttribute( 'data-confirm' ) ) ) {
				event.preventDefault();
			}
		} );
	} );
}() );
