<?php
/**
 * Classic Editor -asetukset.
 *
 * Lohkoeditori (Gutenberg) poistetaan käytöstä kaikilta sisältötyypeiltä ja
 * vimpaimilta. Toimii yhdessä virallisen Classic Editor -lisäosan kanssa tai ilman sitä.
 * Klassiseen editoriin lisätään brändin mukaiset tyylit ja "Lisää lohko" -valikko,
 * josta henkilökunta voi lisätä valmiita sisältölohkoja.
 */

defined( 'ABSPATH' ) || exit;

add_filter( 'use_block_editor_for_post', '__return_false', 100 );
add_filter( 'use_block_editor_for_post_type', '__return_false', 100 );
add_filter( 'use_widgets_block_editor', '__return_false', 100 );
add_filter( 'gutenberg_use_widgets_block_editor', '__return_false', 100 );

/**
 * Valmiit sisältölohkot "Lisää lohko" -valikkoon.
 */
function kp_editor_blocks() {
	$blocks = array(
		array(
			'ryhma' => 'Rakenne',
			'nimi'  => 'Ingressi',
			'html'  => '<p class="kp-ingressi">Kirjoita tähän lyhyt, houkutteleva johdanto aiheeseen.</p>',
		),
		array(
			'ryhma' => 'Rakenne',
			'nimi'  => 'Kaksi palstaa',
			'html'  => '<div class="kp-palstat"><div class="kp-palsta"><h3>Vasen palsta</h3><p>Tekstiä…</p></div><div class="kp-palsta"><h3>Oikea palsta</h3><p>Tekstiä…</p></div></div><p></p>',
		),
		array(
			'ryhma' => 'Rakenne',
			'nimi'  => 'Kolme nostoa (kortit)',
			'html'  => '<div class="kp-kortit"><div class="kp-kortti"><h3>Nosto 1</h3><p>Lyhyt kuvaus.</p></div><div class="kp-kortti"><h3>Nosto 2</h3><p>Lyhyt kuvaus.</p></div><div class="kp-kortti"><h3>Nosto 3</h3><p>Lyhyt kuvaus.</p></div></div><p></p>',
		),
		array(
			'ryhma' => 'Rakenne',
			'nimi'  => 'Huomiolaatikko',
			'html'  => '<div class="kp-laatikko"><h3>Hyvä tietää</h3><p>Tärkeä huomio tai lisätieto lukijalle.</p></div><p></p>',
		),
		array(
			'ryhma' => 'Rakenne',
			'nimi'  => 'Kohteen tiedot (referenssi)',
			'html'  => '<div class="kp-laatikko kp-laatikko--hiekka"><h3>Kohteen tiedot</h3><ul class="kp-lista-nuoli"><li>Rakennusvuosi: </li><li>Rakennustyyppi: </li><li>Kerroksia: </li><li>Huoneistoja: </li><li>Valmistunut: </li></ul></div><p></p>',
		),
		array(
			'ryhma' => 'Rakenne',
			'nimi'  => 'Nostolainaus',
			'html'  => '<blockquote class="kp-nosto"><p>”Lainaus tai keskeinen viesti tähän.”</p><cite>Etunimi Sukunimi, titteli</cite></blockquote><p></p>',
		),
		array(
			'ryhma' => 'Rakenne',
			'nimi'  => 'Tarkistuslista',
			'html'  => '<ul class="kp-lista-check"><li>Ensimmäinen hyöty</li><li>Toinen hyöty</li><li>Kolmas hyöty</li></ul>',
		),
		array(
			'ryhma' => 'Rakenne',
			'nimi'  => 'Usein kysytyt kysymykset',
			'html'  => '<div class="kp-ukk"><details><summary>Kysymys 1?</summary><p>Vastaus kysymykseen.</p></details><details><summary>Kysymys 2?</summary><p>Vastaus kysymykseen.</p></details></div><p></p>',
		),
		array(
			'ryhma' => 'Toiminnot',
			'nimi'  => 'Yhteydenottonosto (turkoosi)',
			'html'  => '<div class="kp-cta"><h2>Kysy lisää asiantuntijaltamme</h2><p>Autamme mielellämme – yhteistyö kanssamme on helppoa ja nopeaa.</p><p><a class="kp-button kp-button--valkoinen" href="/yhteystiedot/#viesti">Ota yhteyttä</a></p></div><p></p>',
		),
		array(
			'ryhma' => 'Toiminnot',
			'nimi'  => 'Painike',
			'html'  => '<p><a class="kp-button" href="/yhteystiedot/">Painikkeen teksti</a></p>',
		),
		array(
			'ryhma' => 'Dynaamiset',
			'nimi'  => 'Yhteyshenkilöt (valitse nimet)',
			'html'  => '<p>[kp_henkilot nimet="Etunimi Sukunimi, Toinen Henkilö"]</p>',
		),
		array(
			'ryhma' => 'Dynaamiset',
			'nimi'  => 'Henkilöt osaamisalueen mukaan',
			'html'  => '<p>[kp_henkilot hakusana="lvi-suunnittelu"]</p>',
		),
		array(
			'ryhma' => 'Dynaamiset',
			'nimi'  => 'Henkilöhaku',
			'html'  => '<p>[kp_henkilohaku]</p>',
		),
		array(
			'ryhma' => 'Dynaamiset',
			'nimi'  => 'Palvelulista',
			'html'  => '<p>[kp_palvelut asiakasryhma="taloyhtiot"]</p>',
		),
		array(
			'ryhma' => 'Dynaamiset',
			'nimi'  => 'Referenssit',
			'html'  => '<p>[kp_referenssit maara="6"]</p>',
		),
		array(
			'ryhma' => 'Dynaamiset',
			'nimi'  => 'Uusimmat artikkelit',
			'html'  => '<p>[kp_ajankohtaiset maara="3"]</p>',
		),
		array(
			'ryhma' => 'Dynaamiset',
			'nimi'  => 'Avoimet työpaikat',
			'html'  => '<p>[kp_tyopaikat]</p>',
		),
		array(
			'ryhma' => 'Dynaamiset',
			'nimi'  => 'Yhteydenottolomake',
			'html'  => '<p>[kp_yhteydenotto]</p>',
		),
		array(
			'ryhma' => 'Dynaamiset',
			'nimi'  => 'Toimipisteet',
			'html'  => '<p>[kp_toimipisteet]</p>',
		),
	);
	return apply_filters( 'kp_editor_blocks', $blocks );
}

add_filter( 'tiny_mce_before_init', function ( $init ) {
	$init['block_formats'] = 'Kappale=p;Otsikko 2=h2;Otsikko 3=h3;Otsikko 4=h4;Esimuotoiltu=pre';

	$formats = array(
		array( 'title' => 'Ingressi', 'block' => 'p', 'classes' => 'kp-ingressi' ),
		array( 'title' => 'Pieni teksti', 'inline' => 'span', 'classes' => 'kp-pieni' ),
		array( 'title' => 'Painike', 'selector' => 'a', 'classes' => 'kp-button' ),
		array( 'title' => 'Painike (reunaviiva)', 'selector' => 'a', 'classes' => 'kp-button kp-button--reuna' ),
		array( 'title' => 'Nuolilinkki', 'selector' => 'a', 'classes' => 'kp-nuolilinkki' ),
		array( 'title' => 'Tarkistuslista', 'selector' => 'ul', 'classes' => 'kp-lista-check' ),
		array( 'title' => 'Nuolilista', 'selector' => 'ul', 'classes' => 'kp-lista-nuoli' ),
		array( 'title' => 'Huomiolaatikko', 'block' => 'div', 'classes' => 'kp-laatikko', 'wrapper' => true ),
		array( 'title' => 'Hiekkalaatikko', 'block' => 'div', 'classes' => 'kp-laatikko kp-laatikko--hiekka', 'wrapper' => true ),
		array( 'title' => 'Nostolainaus', 'block' => 'blockquote', 'classes' => 'kp-nosto', 'wrapper' => true ),
	);
	$init['style_formats']       = wp_json_encode( $formats );
	$init['style_formats_merge'] = false;

	$ext = 'details[class|open],summary[class],div[class|id],span[class],a[class|href|target|rel|title|id]';
	$init['extended_valid_elements'] = empty( $init['extended_valid_elements'] ) ? $ext : $init['extended_valid_elements'] . ',' . $ext;
	$init['valid_children']          = '+div[details],+details[summary|p|ul|ol|h3|h4],+summary[strong|em]';

	$init['kp_blocks'] = wp_json_encode( kp_editor_blocks() );
	return $init;
} );

add_filter( 'mce_buttons', function ( $buttons ) {
	array_unshift( $buttons, 'kp_lohkot' );
	return $buttons;
} );

add_filter( 'mce_buttons_2', function ( $buttons ) {
	if ( ! in_array( 'styleselect', $buttons, true ) ) {
		array_unshift( $buttons, 'styleselect' );
	}
	return $buttons;
} );

add_filter( 'mce_external_plugins', function ( $plugins ) {
	$plugins['kp_lohkot'] = KP_TYOKALUT_URL . 'assets/editor-lohkot.js?ver=' . KP_TYOKALUT_VERSION;
	return $plugins;
} );

// Näytetään oletuksena myös toinen työkalurivi (tyylivalikko).
add_filter( 'tiny_mce_before_init', function ( $init ) {
	$init['wordpress_adv_hidden'] = false;
	return $init;
}, 20 );
