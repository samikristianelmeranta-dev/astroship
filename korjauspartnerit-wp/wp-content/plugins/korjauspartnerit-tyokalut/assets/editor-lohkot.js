/* global tinymce */
/**
 * "Lisää lohko" -valikko klassiseen editoriin.
 * Lohkot määritellään PHP:ssä (kp_editor_blocks) ja välitetään editorin asetuksissa.
 */
( function () {
	tinymce.PluginManager.add( 'kp_lohkot', function ( editor ) {
		// WordPress tulostaa [-alkuiset asetukset valmiiksi taulukoksi, muuten JSON-merkkijono.
		var blocks = editor.getParam( 'kp_blocks' ) || [];
		if ( typeof blocks === 'string' ) {
			try {
				blocks = JSON.parse( blocks );
			} catch ( e ) {
				blocks = [];
			}
		}

		var groups = {};
		var order = [];
		blocks.forEach( function ( b ) {
			if ( ! groups[ b.ryhma ] ) {
				groups[ b.ryhma ] = [];
				order.push( b.ryhma );
			}
			groups[ b.ryhma ].push( {
				text: b.nimi,
				onclick: function () {
					editor.insertContent( b.html );
				}
			} );
		} );

		var menu = order.map( function ( g ) {
			return { text: g, menu: groups[ g ] };
		} );

		editor.addButton( 'kp_lohkot', {
			type: 'menubutton',
			text: 'Lisää lohko',
			icon: 'template',
			tooltip: 'Lisää valmis sisältölohko',
			menu: menu
		} );
	} );
} )();
