/**
 * Korjauspartnerit-teema: valikot, haku ja ylätunnisteen tila.
 */
( function () {
	'use strict';

	var header = document.querySelector( '[data-kp-ylatunniste]' );
	if ( ! header ) {
		return;
	}

	var menu = document.querySelector( '[data-kp-valikko]' );
	var menuBtn = document.querySelector( '[data-kp-valikko-avaa]' );
	var searchBtn = document.querySelector( '[data-kp-hakupaneeli-avaa]' );
	var search = document.querySelector( '[data-kp-hakupaneeli]' );
	var body = document.body;

	function setMenu( open ) {
		if ( ! menu || ! menuBtn ) {
			return;
		}
		menuBtn.setAttribute( 'aria-expanded', open ? 'true' : 'false' );
		body.classList.toggle( 'kp-valikko-auki', open );
		if ( open ) {
			setSearch( false );
		}
	}

	function setSearch( open ) {
		if ( ! search || ! searchBtn ) {
			return;
		}
		search.hidden = ! open;
		searchBtn.setAttribute( 'aria-expanded', open ? 'true' : 'false' );
		body.classList.toggle( 'kp-haku-auki', open );
		if ( open ) {
			setMenu( false );
			var input = search.querySelector( 'input' );
			if ( input ) {
				input.focus();
			}
		}
	}

	if ( menuBtn ) {
		menuBtn.addEventListener( 'click', function () {
			setMenu( menuBtn.getAttribute( 'aria-expanded' ) !== 'true' );
		} );
	}

	if ( searchBtn ) {
		searchBtn.addEventListener( 'click', function () {
			setSearch( search.hidden );
		} );
	}

	// Alavalikot: painike avaa (mobiili ja näppäimistö), hiiri avaa työpöydällä CSS:llä.
	document.querySelectorAll( '.kp-alavalikko-avaa' ).forEach( function ( btn ) {
		btn.addEventListener( 'click', function () {
			var li = btn.closest( 'li' );
			var open = btn.getAttribute( 'aria-expanded' ) !== 'true';
			document.querySelectorAll( '.kp-paavalikko__lista > li.is-open' ).forEach( function ( other ) {
				if ( other !== li ) {
					other.classList.remove( 'is-open' );
					var b = other.querySelector( '.kp-alavalikko-avaa' );
					if ( b ) {
						b.setAttribute( 'aria-expanded', 'false' );
					}
				}
			} );
			li.classList.toggle( 'is-open', open );
			btn.setAttribute( 'aria-expanded', open ? 'true' : 'false' );
		} );
	} );

	document.addEventListener( 'keydown', function ( e ) {
		if ( e.key !== 'Escape' ) {
			return;
		}
		setMenu( false );
		setSearch( false );
		document.querySelectorAll( '.kp-paavalikko__lista > li.is-open' ).forEach( function ( li ) {
			li.classList.remove( 'is-open' );
			var b = li.querySelector( '.kp-alavalikko-avaa' );
			if ( b ) {
				b.setAttribute( 'aria-expanded', 'false' );
			}
		} );
	} );

	document.addEventListener( 'click', function ( e ) {
		if ( ! e.target.closest( '.kp-paavalikko__lista > li' ) ) {
			document.querySelectorAll( '.kp-paavalikko__lista > li.is-open' ).forEach( function ( li ) {
				li.classList.remove( 'is-open' );
				var b = li.querySelector( '.kp-alavalikko-avaa' );
				if ( b ) {
					b.setAttribute( 'aria-expanded', 'false' );
				}
			} );
		}
	} );

	// Kuvallisen yläosan päällä ylätunniste on läpinäkyvä, vieritettäessä kiinteä.
	function onScroll() {
		header.classList.toggle( 'is-vieritetty', window.scrollY > 40 );
	}
	window.addEventListener( 'scroll', onScroll, { passive: true } );
	onScroll();

	// Ankkurilinkit valikossa sulkevat mobiilivalikon.
	if ( menu ) {
		menu.addEventListener( 'click', function ( e ) {
			var a = e.target.closest( 'a' );
			if ( a && a.hash && a.pathname === window.location.pathname ) {
				setMenu( false );
			}
		} );
	}
} )();
