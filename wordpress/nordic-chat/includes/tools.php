<?php
/**
 * Avustajan työkalut:
 *   hae_sivustolta             – hakee sivuston sivujen sisältöä
 *   laheta_viesti_yritykselle  – välittää asiakkaan yhteydenoton sähköpostiin
 *
 * Työkalut on määritelty strict-tilassa, joten syötteet vastaavat aina skeemaa.
 * Silti kaikki tarkistetaan palvelimella ennen käyttöä.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

function nordic_chat_tools() {
	return array(
		array(
			'name'         => 'hae_sivustolta',
			'description'  => 'Hakee ikkunakauppias.fi-sivuston sivujen sisällön. Käytä aina, kun tarvitset tietoa tuotteista, malleista, teknisistä arvoista, hinnoista, oppaista, paikkakunnista tai palveluista. Anna joko hakusanat tai hakemistossa näkyvä sivun osoite (tai molemmat). Palauttaa enintään kolme sivua.',
			'strict'       => true,
			'inputSchema'  => array(
				'type'                 => 'object',
				'properties'           => array(
					'haku'          => array(
						'type'        => 'string',
						'description' => 'Hakusanat suomeksi, esim. "nelilasinen ikkuna energiansäästö" tai "ulko-oven hinta".',
					),
					'sivun_osoite'  => array(
						'type'        => array( 'string', 'null' ),
						'description' => 'Hakemistossa näkyvä sivun osoite, jos tiedät oikean sivun. Muuten null.',
					),
				),
				'required'             => array( 'haku', 'sivun_osoite' ),
				'additionalProperties' => false,
			),
		),
		array(
			'name'         => 'laheta_viesti_yritykselle',
			'description'  => 'Lähettää asiakkaan yhteydenoton Nordic Ikkunat & Ovien sähköpostiin. Käytä vain, kun asiakas on antanut nimensä ja sähköpostiosoitteensa ja vahvistanut yhteenvedon lähettämisen. Älä keksi tietoja: käytä vain sitä, mitä asiakas on kertonut.',
			'strict'       => true,
			'inputSchema'  => array(
				'type'                 => 'object',
				'properties'           => array(
					'nimi'        => array( 'type' => 'string', 'description' => 'Asiakkaan nimi.' ),
					'sahkoposti'  => array( 'type' => 'string', 'description' => 'Asiakkaan sähköpostiosoite.' ),
					'puhelin'     => array( 'type' => array( 'string', 'null' ), 'description' => 'Puhelinnumero, jos asiakas antoi sen. Muuten null.' ),
					'paikkakunta' => array( 'type' => array( 'string', 'null' ), 'description' => 'Kohteen paikkakunta, jos tiedossa. Muuten null.' ),
					'aihe'        => array(
						'type'        => 'string',
						'enum'        => array( 'tarjouspyynto', 'mittauskaynti', 'kysymys', 'huolto', 'muu' ),
						'description' => 'Yhteydenoton aihe.',
					),
					'viesti'      => array( 'type' => 'string', 'description' => 'Asiakkaan asia tiivistettynä yritykselle: mitä hän haluaa ja kaikki kertomansa kohteen tiedot (määrät, talotyyppi, aikataulu, toiveet).' ),
				),
				'required'             => array( 'nimi', 'sahkoposti', 'puhelin', 'paikkakunta', 'aihe', 'viesti' ),
				'additionalProperties' => false,
			),
		),
	);
}

/**
 * Suorittaa työkalun. Palauttaa [ string $content, bool $is_error ].
 *
 * @param array $conv Keskustelun tila (viittauksena; yhteydenottolaskuri päivittyy).
 */
function nordic_chat_run_tool( $name, $input, array &$conv ) {
	$input = is_array( $input ) ? $input : array();

	if ( 'hae_sivustolta' === $name ) {
		$results = nordic_chat_search_pages( (string) ( $input['haku'] ?? '' ), (string) ( $input['sivun_osoite'] ?? '' ) );
		if ( ! $results ) {
			return array( 'Hakusanoilla ei löytynyt sivuja. Kokeile toisia sanoja tai valitse sivu hakemistosta.', false );
		}
		return array( wp_json_encode( $results, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ), false );
	}

	if ( 'laheta_viesti_yritykselle' === $name ) {
		return nordic_chat_send_contact( $input, $conv );
	}

	return array( 'Tuntematon työkalu.', true );
}

function nordic_chat_send_contact( array $in, array &$conv ) {
	$s     = nordic_chat_settings();
	$email = sanitize_email( (string) ( $in['sahkoposti'] ?? '' ) );
	$name  = sanitize_text_field( (string) ( $in['nimi'] ?? '' ) );
	if ( ! is_email( $email ) ) {
		return array( 'Sähköpostiosoite ei ole kelvollinen. Pyydä asiakasta tarkistamaan osoite.', true );
	}
	if ( '' === $name ) {
		return array( 'Nimi puuttuu. Kysy asiakkaan nimi.', true );
	}
	if ( ( $conv['contact_sends'] ?? 0 ) >= 2 ) {
		return array( 'Tästä keskustelusta on jo lähetetty viesti. Uutta viestiä ei lähetetty. Kerro asiakkaalle, että hän voi lähettää lisätietoja suoraan osoitteeseen ' . $s['contact_email'] . '.', true );
	}

	$topics = array(
		'tarjouspyynto' => 'Tarjouspyyntö',
		'mittauskaynti' => 'Mittauskäyntipyyntö',
		'kysymys'       => 'Kysymys',
		'huolto'        => 'Huolto',
		'muu'           => 'Yhteydenotto',
	);
	$topic   = $topics[ $in['aihe'] ?? 'muu' ] ?? 'Yhteydenotto';
	$phone   = sanitize_text_field( (string) ( $in['puhelin'] ?? '' ) );
	$city    = sanitize_text_field( (string) ( $in['paikkakunta'] ?? '' ) );
	$message = sanitize_textarea_field( (string) ( $in['viesti'] ?? '' ) );

	// Asiakkaan omat viestit mukaan sellaisenaan, jotta mitään ei jää pelkän tiivistelmän varaan.
	$user_lines = array();
	foreach ( $conv['messages'] as $m ) {
		if ( 'user' === $m['role'] && is_string( $m['content'] ) ) {
			$user_lines[] = '> ' . str_replace( "\n", "\n> ", $m['content'] );
		}
	}

	$body  = "{$topic} sivuston chatista\n\n";
	$body .= "Nimi: {$name}\nSähköposti: {$email}\n";
	$body .= $phone ? "Puhelin: {$phone}\n" : '';
	$body .= $city ? "Paikkakunta: {$city}\n" : '';
	$body .= ! empty( $conv['page'] ) ? 'Sivu: ' . $conv['page'] . "\n" : '';
	$body .= "\nAsia (tekoälyn tiivistelmä):\n{$message}\n";
	$body .= "\nAsiakkaan viestit chatissa:\n" . implode( "\n\n", $user_lines ) . "\n";
	$body .= "\n—\nVastaa tähän viestiin, niin vastaus menee suoraan asiakkaalle.\n";

	$headers = array(
		'Content-Type: text/plain; charset=UTF-8',
		'Reply-To: ' . str_replace( array( "\r", "\n", '<', '>' ), '', $name ) . ' <' . $email . '>',
	);
	$subject = "[Chat] {$topic}: {$name}" . ( $city ? ", {$city}" : '' );
	$sent    = wp_mail( $s['contact_email'], $subject, $body, $headers );

	if ( ! $sent ) {
		return array( 'Lähetys epäonnistui teknisen virheen vuoksi. Pyydä asiakasta lähettämään sähköpostia osoitteeseen ' . $s['contact_email'] . '.', true );
	}
	$conv['contact_sends'] = ( $conv['contact_sends'] ?? 0 ) + 1;

	// Nordic CRM: tarjous- ja mittauspyynnöt asiakasrekisteriin ja vahvistusviesti asiakkaalle.
	if ( in_array( $in['aihe'] ?? '', array( 'tarjouspyynto', 'mittauskaynti' ), true ) && function_exists( 'nordic_crm_process_lead' ) ) {
		$parts = preg_split( '/\s+/u', $name, 2 );
		nordic_crm_process_lead( array(
			'email'      => $email,
			'first_name' => $parts[0] ?? '',
			'last_name'  => $parts[1] ?? '',
			'phone'      => $phone,
			'type'       => ( false !== stripos( $message . ' ' . ( $conv['page'] ?? '' ), 'taloyhti' ) ) ? 'taloyhtio' : 'tarjous',
			'consent'    => false, // chatissa ei kerätä markkinointilupaa
			'source_url' => $conv['page'] ?? '',
		) );
	}

	do_action( 'nordic_chat_contact_sent', $in, $conv );

	return array( 'Viesti lähetetty yritykselle osoitteeseen ' . $s['contact_email'] . '.' . ( function_exists( 'nordic_crm_process_lead' ) && in_array( $in['aihe'] ?? '', array( 'tarjouspyynto', 'mittauskaynti' ), true ) ? ' Asiakas saa lisäksi vahvistuksen sähköpostiinsa.' : '' ), false );
}
