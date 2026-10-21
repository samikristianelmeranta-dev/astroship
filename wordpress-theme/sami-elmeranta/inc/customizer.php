<?php
/**
 * Customizer: Ulkoasu → Mukauta → "Sami Elmeranta – asetukset".
 *
 * @package sami-elmeranta
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'customize_register', function ( WP_Customize_Manager $wp_customize ) {
	$wp_customize->add_panel( 'se_panel', array(
		'title'    => 'Sami Elmeranta – asetukset',
		'priority' => 30,
	) );

	$sections = array(
		'se_person'  => array( 'Henkilö & puolue', array(
			'person_name' => array( 'Nimi', 'text' ),
			'tagline'     => array( 'Iskulause', 'text' ),
			'job_title'   => array( 'Tehtävänimike (hakukoneille)', 'text' ),
			'description' => array( 'Kuvaus (hakukoneet, some, tekoäly)', 'textarea' ),
			'party_name'  => array( 'Puolue', 'text' ),
			'party_url'   => array( 'Puolueen verkkosivu', 'url' ),
			'district'    => array( 'Kaupunginosa', 'text' ),
			'locality'    => array( 'Kunta', 'text' ),
			'region'      => array( 'Maakunta', 'text' ),
		) ),
		'se_hero'    => array( 'Etusivun hero', array(
			'hero_line1' => array( 'Otsikko, rivi 1', 'text' ),
			'hero_line2' => array( 'Otsikko, rivi 2', 'text' ),
			'hero_line3' => array( 'Otsikko, rivi 3 (korostettu)', 'text' ),
			'hero_lead'  => array( 'Johdanto', 'textarea' ),
		) ),
		'se_contact' => array( 'Yhteystiedot & lomake', array(
			'phone'          => array( 'Puhelin (kansainvälinen, esim. +35850…)', 'text' ),
			'phone_display'  => array( 'Puhelin näytettävässä muodossa', 'text' ),
			'email'          => array( 'Julkinen sähköposti (tyhjä = ei näytetä sivulla)', 'email' ),
			'form_recipient' => array( 'Lomakeviestien vastaanottaja (tyhjä = julkinen tai ylläpitäjän sähköposti)', 'email' ),
			'newsletter_url' => array( 'Uutiskirjeen tilauslinkki (Mailchimp)', 'url' ),
			'whatsapp_url'   => array( 'WhatsApp-linkki', 'url' ),
		) ),
		'se_social'  => array( 'Sosiaalinen media', array(
			'facebook'  => array( 'Facebook', 'url' ),
			'instagram' => array( 'Instagram', 'url' ),
			'tiktok'    => array( 'TikTok', 'url' ),
			'linkedin'  => array( 'LinkedIn', 'url' ),
			'uusisuomi' => array( 'Uusi Suomi -blogi', 'url' ),
		) ),
	);

	$defaults = se_defaults();
	foreach ( $sections as $section_id => $section ) {
		$wp_customize->add_section( $section_id, array( 'title' => $section[0], 'panel' => 'se_panel' ) );
		foreach ( $section[1] as $key => $field ) {
			$type     = $field[1];
			$sanitize = 'sanitize_text_field';
			if ( 'url' === $type ) {
				$sanitize = 'esc_url_raw';
			} elseif ( 'email' === $type ) {
				$sanitize = 'sanitize_email';
			} elseif ( 'textarea' === $type ) {
				$sanitize = 'sanitize_textarea_field';
			}
			$wp_customize->add_setting( 'se_' . $key, array(
				'default'           => $defaults[ $key ],
				'sanitize_callback' => $sanitize,
				'capability'        => 'edit_theme_options',
			) );
			$wp_customize->add_control( 'se_' . $key, array(
				'label'   => $field[0],
				'section' => $section_id,
				'type'    => $type,
			) );
		}
	}

	$wp_customize->add_section( 'se_images', array( 'title' => 'Kuvat', 'panel' => 'se_panel' ) );
	foreach ( array(
		'portrait'     => 'Etusivun potretti (pysty, 2:3)',
		'portrait_alt' => 'Toinen kuva (esittely, arki)',
	) as $key => $label ) {
		$wp_customize->add_setting( 'se_' . $key, array(
			'default'           => 0,
			'sanitize_callback' => 'absint',
			'capability'        => 'edit_theme_options',
		) );
		$wp_customize->add_control( new WP_Customize_Media_Control( $wp_customize, 'se_' . $key, array(
			'label'     => $label,
			'section'   => 'se_images',
			'mime_type' => 'image',
		) ) );
	}
} );
