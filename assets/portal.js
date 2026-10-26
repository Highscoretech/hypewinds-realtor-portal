/* Hypewinds realtor portal: password show/hide, copy buttons, confirm prompts. */
( function () {
	var svg = function ( paths ) {
		return '<svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"'
			+ ' stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' + paths + '</svg>';
	};
	var eyeOpen = svg( '<path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7S1 12 1 12z"/><circle cx="12" cy="12" r="3"/>' );
	var eyeShut = svg( '<path d="M17.9 17.9A10.6 10.6 0 0 1 12 19c-7 0-11-7-11-7a19 19 0 0 1 5.1-5.9m3.8-1A10.6 10.6 0 0 1 12 5c7 0 11 7 11 7a19 19 0 0 1-2.2 3.2M1 1l22 22"/>' );
	var tick = svg( '<path d="M20 6 9 17l-5-5"/>' );

	// Show/hide control on every password field.
	document.querySelectorAll( 'input[type="password"]' ).forEach( function ( field ) {
		var wrap = document.createElement( 'span' );
		wrap.className = 'pass-wrap';
		field.parentNode.insertBefore( wrap, field );
		wrap.appendChild( field );

		var button = document.createElement( 'button' );
		button.type = 'button';
		button.className = 'pass-eye';
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

	document.querySelectorAll( '[data-copy]' ).forEach( function ( button ) {
		button.addEventListener( 'click', function () {
			var text = button.getAttribute( 'data-copy' );
			var before = button.innerHTML;
			var label = button.querySelector( 'span' );

			// Confirm in place: a tick (and "Copied" on labelled buttons), then back.
			function done() {
				button.innerHTML = tick + ( label ? '<span>Copied</span>' : '' );
				button.classList.add( 'is-copied' );
				setTimeout( function () {
					button.innerHTML = before;
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

	document.querySelectorAll( 'input[readonly]' ).forEach( function ( input ) {
		input.addEventListener( 'focus', function () { input.select(); } );
	} );

	document.querySelectorAll( 'form[data-confirm]' ).forEach( function ( form ) {
		form.addEventListener( 'submit', function ( event ) {
			if ( ! window.confirm( form.getAttribute( 'data-confirm' ) ) ) {
				event.preventDefault();
			}
		} );
	} );

	// Close the mobile menu with Escape.
	document.addEventListener( 'keydown', function ( event ) {
		var toggle = document.getElementById( 'nav-toggle' );
		if ( 'Escape' === event.key && toggle && toggle.checked ) {
			toggle.checked = false;
		}
	} );
}() );
