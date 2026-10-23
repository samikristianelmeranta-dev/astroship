/**
 * Korjauspartnerit – julkisen puolen työkalut: henkilöhaku ja referenssisuodatin.
 */
( function () {
	'use strict';

	function normalize( s ) {
		return ( s || '' )
			.toLowerCase()
			.normalize( 'NFD' )
			.replace( /[̀-ͯ]/g, '' )
			.trim();
	}

	// Henkilöhaku.
	document.querySelectorAll( '[data-kp-henkilohaku]' ).forEach( function ( root ) {
		var input = root.querySelector( '[data-kp-haku]' );
		var select = root.querySelector( '[data-kp-osaaminen]' );
		var chips = root.querySelectorAll( '[data-kp-toimipiste]' );
		var rows = root.querySelectorAll( '.kp-henkilohaku__rivi' );
		var result = root.querySelector( '[data-kp-tulos]' );
		var empty = root.querySelector( '[data-kp-eitulosta]' );
		var office = '';

		function apply() {
			var words = normalize( input ? input.value : '' ).split( /\s+/ ).filter( Boolean );
			var skill = select ? select.value : '';
			var shown = 0;

			rows.forEach( function ( row ) {
				var hay = row.getAttribute( 'data-haku' ) || '';
				var ok = words.every( function ( w ) {
					return hay.indexOf( w ) !== -1;
				} );
				if ( ok && skill ) {
					ok = ( ' ' + row.getAttribute( 'data-osaaminen' ) + ' ' ).indexOf( ' ' + skill + ' ' ) !== -1;
				}
				if ( ok && office ) {
					ok = ( ' ' + row.getAttribute( 'data-toimipiste' ) + ' ' ).indexOf( ' ' + office + ' ' ) !== -1;
				}
				row.hidden = ! ok;
				if ( ok ) {
					shown++;
				}
			} );

			if ( result ) {
				result.textContent = shown === 1 ? '1 henkilö' : shown + ' henkilöä';
			}
			if ( empty ) {
				empty.hidden = shown !== 0;
			}
		}

		if ( input ) {
			input.addEventListener( 'input', apply );
		}
		if ( select ) {
			select.addEventListener( 'change', apply );
		}
		chips.forEach( function ( chip ) {
			chip.addEventListener( 'click', function () {
				office = chip.getAttribute( 'data-kp-toimipiste' );
				chips.forEach( function ( c ) {
					c.classList.toggle( 'is-active', c === chip );
					c.setAttribute( 'aria-pressed', c === chip ? 'true' : 'false' );
				} );
				apply();
			} );
		} );
		apply();
	} );

	// Referenssien suodatin.
	document.querySelectorAll( '[data-kp-suodatin]' ).forEach( function ( bar ) {
		var target = bar.nextElementSibling;
		if ( ! target ) {
			return;
		}
		var chips = bar.querySelectorAll( '.kp-chip' );
		chips.forEach( function ( chip ) {
			chip.addEventListener( 'click', function () {
				var val = chip.getAttribute( 'data-arvo' );
				chips.forEach( function ( c ) {
					c.classList.toggle( 'is-active', c === chip );
					c.setAttribute( 'aria-pressed', c === chip ? 'true' : 'false' );
				} );
				target.querySelectorAll( '[data-arvot]' ).forEach( function ( card ) {
					card.hidden = val && ( ' ' + card.getAttribute( 'data-arvot' ) + ' ' ).indexOf( ' ' + val + ' ' ) === -1;
				} );
			} );
		} );
	} );
} )();
