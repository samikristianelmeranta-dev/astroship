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

/**
 * Vierityksessä esiin tulevat osiot ja lukujen laskuri.
 * Näkymän yläpuolella tai näkyvissä jo latautuessa olevat elementit eivät piiloudu,
 * joten sivu on valmis heti. Ohitetaan, jos käyttäjä on pyytänyt vähentämään liikettä.
 */
( function () {
	'use strict';

	if ( ! ( 'IntersectionObserver' in window ) || window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches ) {
		return;
	}

	var selectors = [
		'.kp-osio__yla',
		'.kp-osio__otsikko',
		'.kp-kohderyhma',
		'.kp-kortisto > *',
		'.kp-henkilot > *',
		'.kp-teesi__teksti',
		'.kp-luku',
		'.kp-yhteysnosto__sisus > *',
		'.kp-toimipiste',
		'.kp-tyopaikka',
		'.kp-sivupalkki__laatikko',
		'.kp-sisalto > :is(h2, .kp-kortit, .kp-palstat, .kp-cta, .kp-laatikko, blockquote, .kp-galleria)'
	];
	var items = document.querySelectorAll( selectors.join( ',' ) );
	var fold = window.innerHeight;

	var observer = new IntersectionObserver( function ( entries ) {
		entries.forEach( function ( entry ) {
			if ( entry.isIntersecting ) {
				entry.target.classList.remove( 'kp-odottaa' );
				observer.unobserve( entry.target );
			}
		} );
	}, { rootMargin: '0px 0px -8% 0px' } );

	items.forEach( function ( el ) {
		if ( el.closest( '.kp-henkilohaku' ) && el.matches( '.kp-henkilot > *' ) && el.parentElement.children.length > 12 ) {
			return; // Pitkä henkilölista ilmestyy kerralla, ei rivi kerrallaan.
		}
		el.classList.add( 'kp-ilmesty' );
		if ( el.getBoundingClientRect().top < fold ) {
			return;
		}
		// Sisarukset ilmestyvät porrastetusti.
		var index = Array.prototype.indexOf.call( el.parentElement.children, el ) % 4;
		el.style.setProperty( '--kp-viive', ( index * 0.09 ) + 's' );
		el.classList.add( 'kp-odottaa' );
		observer.observe( el );
	} );

	// Varmistus: kaikki näkyviin viimeistään, jos tarkkailija ei syystä tai toisesta laukea.
	window.addEventListener( 'load', function () {
		setTimeout( function () {
			document.querySelectorAll( '.kp-odottaa' ).forEach( function ( el ) {
				if ( el.getBoundingClientRect().top < window.innerHeight ) {
					el.classList.remove( 'kp-odottaa' );
				}
			} );
		}, 1500 );
	} );

	// Luvut (esim. "40+") lasketaan nollasta, kun ne tulevat näkyviin. Vuosilukuja ei lasketa.
	var counters = document.querySelectorAll( '.kp-luku dd' );
	var countObserver = new IntersectionObserver( function ( entries ) {
		entries.forEach( function ( entry ) {
			if ( ! entry.isIntersecting ) {
				return;
			}
			countObserver.unobserve( entry.target );
			var el = entry.target;
			var m = el.textContent.trim().match( /^(\d+)(\D*)$/ );
			if ( ! m || +m[ 1 ] >= 1000 ) {
				return;
			}
			var target = +m[ 1 ];
			var suffix = m[ 2 ];
			var start = null;
			function step( t ) {
				if ( ! start ) {
					start = t;
				}
				var p = Math.min( ( t - start ) / 1400, 1 );
				var eased = 1 - Math.pow( 1 - p, 3 );
				el.textContent = Math.round( target * eased ) + suffix;
				if ( p < 1 ) {
					window.requestAnimationFrame( step );
				}
			}
			window.requestAnimationFrame( step );
		} );
	}, { threshold: 0.6 } );
	counters.forEach( function ( el ) {
		countObserver.observe( el );
	} );
} )();
