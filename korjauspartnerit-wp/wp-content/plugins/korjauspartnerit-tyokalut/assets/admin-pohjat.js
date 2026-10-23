/* global kpPohjat, tinymce, jQuery */
/**
 * Sisältöpohjan valinta "Lisää uusi" -näkymässä.
 */
jQuery( function ( $ ) {
	var data = window.kpPohjat || { pohjat: [] };

	function find( id ) {
		id = parseInt( id, 10 );
		for ( var i = 0; i < data.pohjat.length; i++ ) {
			if ( data.pohjat[ i ].id === id ) {
				return data.pohjat[ i ];
			}
		}
		return null;
	}

	function currentContent() {
		var ed = window.tinymce && tinymce.get( 'content' );
		if ( ed && ! ed.isHidden() ) {
			return ed.getContent( { format: 'text' } ).trim();
		}
		return ( $( '#content' ).val() || '' ).trim();
	}

	function setContent( html ) {
		var ed = window.tinymce && tinymce.get( 'content' );
		if ( ed && ! ed.isHidden() ) {
			ed.setContent( html );
			ed.undoManager.add();
		}
		$( '#content' ).val( html );
	}

	// Sivupohja ja kategoria asetetaan myös, jos pohja tuli URL-parametrilla.
	function applyMeta( tpl ) {
		if ( tpl.sivupohja && $( '#page_template' ).length ) {
			$( '#page_template' ).val( tpl.sivupohja ).trigger( 'change' );
		}
		if ( tpl.kategoria ) {
			$( '#in-category-' + tpl.kategoria ).prop( 'checked', true );
		}
	}

	function apply( tpl ) {
		setContent( tpl.sisalto );
		applyMeta( tpl );
	}

	$( '#kp-pohja-valinta' ).on( 'change', function () {
		var tpl = find( this.value );
		$( '#kp-pohja-kuvaus' ).text( tpl ? tpl.kuvaus : '' );
	} );

	$( '#kp-pohja-kayta' ).on( 'click', function () {
		var tpl = find( $( '#kp-pohja-valinta' ).val() );
		if ( ! tpl ) {
			return;
		}
		if ( currentContent() && ! window.confirm( data.vahvistus ) ) {
			return;
		}
		apply( tpl );
	} );

	if ( data.valittu ) {
		var preset = find( data.valittu );
		if ( preset ) {
			applyMeta( preset );
			$( '#kp-pohja-valinta' ).val( preset.id ).trigger( 'change' );
		}
	}
} );
