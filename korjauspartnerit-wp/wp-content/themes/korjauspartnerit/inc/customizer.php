<?php
/**
 * Mukauttimen asetukset (Ulkoasu → Mukauta → Etusivu).
 * Etusivun yläosan otsikko, teksti ja kuva muokataan Etusivu-sivun omista kentistä.
 */

defined( 'ABSPATH' ) || exit;

function kp_front_defaults() {
	return array(
		'kp_hero_otsikko'   => 'Korjausrakentamisen kokenein kumppani',
		'kp_hero_teksti'    => 'Insinööritoimisto korjausrakentamisen, kiinteistöjen ja teollisuuden asiantuntijapalveluihin. Meillä yksikään asiakas ei ole liian pieni eikä mikään projekti liian vaativa.',
		'kp_teesi_otsikko'  => 'Insinööritoimisto, jolla on kova osaamistaso ja pehmeä arvomaailma.',
		'kp_teesi_teksti'   => 'Toimimme alan tiennäyttäjänä ja tartumme rohkeasti haasteisiin. Nopeaa laskun lähettämistä tärkeämpää on ongelmien ratkaisu ja ehkäisy. Sanaamme voi luottaa ja kannamme vastuumme projektin päättymisen jälkeenkin.',
		'kp_luku_1'         => '2017',
		'kp_luku_1_selite'  => 'perustettu',
		'kp_luku_2'         => '40+',
		'kp_luku_2_selite'  => 'asiantuntijaa',
		'kp_luku_3'         => 'AAA',
		'kp_luku_3_selite'  => 'luottoluokitus',
		'kp_cta_otsikko'    => 'Ota yhteyttä tai pyydä tarjous!',
		'kp_cta_teksti'     => 'Kerro tarpeestasi, niin ohjaamme sinut oikealle asiantuntijalle. Palvelulupauksemme on, että yhteistyö kanssamme on helppoa ja nopeaa.',
		'kp_nayta_referenssit'   => true,
		'kp_nayta_ajankohtaiset' => true,
	);
}

function kp_mod( $key ) {
	$d = kp_front_defaults();
	return get_theme_mod( $key, isset( $d[ $key ] ) ? $d[ $key ] : '' );
}

add_action( 'customize_register', function ( $wp_customize ) {
	$wp_customize->add_section( 'kp_etusivu', array(
		'title'       => 'Etusivu',
		'priority'    => 30,
		'description' => 'Etusivun osioiden tekstit. Yläosan kuva on Etusivu-sivun artikkelikuva.',
	) );

	$fields = array(
		'kp_hero_otsikko'  => array( 'Yläosan otsikko', 'text' ),
		'kp_hero_teksti'   => array( 'Yläosan teksti', 'textarea' ),
		'kp_teesi_otsikko' => array( 'Arvolupaus – otsikko', 'textarea' ),
		'kp_teesi_teksti'  => array( 'Arvolupaus – teksti', 'textarea' ),
		'kp_luku_1'        => array( 'Luku 1', 'text' ),
		'kp_luku_1_selite' => array( 'Luku 1 – selite', 'text' ),
		'kp_luku_2'        => array( 'Luku 2', 'text' ),
		'kp_luku_2_selite' => array( 'Luku 2 – selite', 'text' ),
		'kp_luku_3'        => array( 'Luku 3', 'text' ),
		'kp_luku_3_selite' => array( 'Luku 3 – selite', 'text' ),
		'kp_cta_otsikko'   => array( 'Yhteydenotto – otsikko', 'text' ),
		'kp_cta_teksti'    => array( 'Yhteydenotto – teksti', 'textarea' ),
		'kp_nayta_referenssit'   => array( 'Näytä referenssit', 'checkbox' ),
		'kp_nayta_ajankohtaiset' => array( 'Näytä ajankohtaiset', 'checkbox' ),
	);
	$defaults = kp_front_defaults();
	foreach ( $fields as $key => $f ) {
		$wp_customize->add_setting( $key, array(
			'default'           => $defaults[ $key ],
			'sanitize_callback' => 'checkbox' === $f[1] ? 'rest_sanitize_boolean' : ( 'textarea' === $f[1] ? 'sanitize_textarea_field' : 'sanitize_text_field' ),
		) );
		$wp_customize->add_control( $key, array( 'label' => $f[0], 'section' => 'kp_etusivu', 'type' => $f[1] ) );
	}
} );
