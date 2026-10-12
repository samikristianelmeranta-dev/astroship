<?php
/**
 * Yrityksen perustiedot yhdessä paikassa: rakenteinen data (schema.org) ja llms.txt
 * käyttävät näitä. Muuta tiedot tarvittaessa suodattimella 'tilihub_business'.
 *
 * @package TiliHub
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Yrityksen tiedot.
 *
 * @return array<string, mixed>
 */
function tilihub_business() {
	return apply_filters(
		'tilihub_business',
		array(
			'name'        => 'TiliHub Oy',
			'legal_name'  => 'TiliHub Oy',
			'description' => 'TiliHub Oy on moderni digitaalinen tilitoimisto Kaarinasta. Kirjanpito, palkanlaskenta, tilinpäätökset ja veroneuvonta pk-yrityksille Turussa, Varsinais-Suomessa ja digitaalisesti koko Suomessa.',
			'phone'       => '+358440803344',
			'phone_human' => '044 080 3344',
			'email'       => 'info@tilihub.fi',
			'locality'    => 'Kaarina',
			'region'      => 'Varsinais-Suomi',
			'country'     => 'FI',
			'founded'     => '2025',
			'founder'     => 'Jackson Shuudifonya',
			'areas'       => array( 'Turku', 'Kaarina', 'Raisio', 'Naantali', 'Lieto', 'Varsinais-Suomi', 'Suomi' ),
			'software'    => array( 'Fennoa', 'Procountor', 'Netvisor', 'NocFo' ),
			'same_as'     => array(
				'https://www.facebook.com/profile.php?id=61579294262007',
				'https://www.instagram.com/tilihub_oy',
			),
			'member_of'   => array(
				'name' => 'Taloushallintoliitto',
				'url'  => 'https://taloushallintoliitto.fi/',
			),
			// Hinnasto 1.7.2026 alkaen, alv 0 %.
			'prices'      => array(
				array( 'name' => 'Juokseva kirjanpito', 'price' => 55, 'unit' => 'HUR', 'note' => 'Minimiveloitus 55 € / kirjanpitokuukausi. Tositteet, osto- ja myyntireskontra, ALV-ilmoitukset, täsmäytykset ja kuukausiraportit.', 'url' => '/kirjanpitopalvelut/' ),
				array( 'name' => 'Palkanlaskenta', 'price' => 55, 'unit' => 'HUR', 'note' => 'Alkaen 15 € / palkka-ajo. Palkkalaskelmat, tulorekisteri-ilmoitukset ja työnantajan erillisilmoitukset.', 'url' => '/palkanlaskenta/' ),
				array( 'name' => 'Asiantuntijatyö', 'price' => 79, 'unit' => 'HUR', 'note' => 'Tilinpäätös ja veroilmoitus, osingonjakolaskelmat, kirjanpidon korjaukset, sopimus- ja yhtiöasiakirjat.', 'url' => '/tilinpaatos/' ),
				array( 'name' => 'Verosuunnittelu', 'price' => 119, 'unit' => 'HUR', 'note' => 'Palkka-osinko-optimointi, holding-yhtiöt, yritysjärjestelyt ja ennakkoverojen suunnittelu.', 'url' => '/kirjanpidon-hinnat/' ),
			),
			'min_monthly' => 55,
		)
	);
}
