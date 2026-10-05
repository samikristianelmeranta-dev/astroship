<?php
/**
 * Keskustelun kulku Clauden kanssa.
 *
 * Keskustelu tallennetaan palvelimelle (transient) täsmälleen siinä muodossa
 * kuin API sen palautti, ja historiaan vain lisätään. Tämä on tarpeen, koska
 * Claude Opus 5.5:n ajattelulohkot on sidottu keskusteluun: aiemman vuoron
 * muokkaaminen tai poistaminen mitätöisi ne. Selain lähettää vain uuden
 * viestin, joten asiakas ei voi muokata historiaa.
 *
 * Järjestelmäkehote ja työkalut jäädytetään keskustelun alussa samasta syystä
 * (ja jotta välimuisti pysyy lämpimänä).
 */

if ( ! defined( 'ABSPATH' ) ) exit;

const NORDIC_CHAT_MODEL       = 'claude-opus-5-5';
const NORDIC_CHAT_SESSION_TTL = 6 * HOUR_IN_SECONDS;
const NORDIC_CHAT_MAX_ROUNDS  = 4; // työkalukierroksia per asiakkaan viesti

/**
 * Anthropic-asiakas. Kirjastot ladataan vasta tarvittaessa, jotta ne eivät
 * kuormita sivulatauksia eivätkä törmää muiden lisäosien kirjastoihin.
 */
function nordic_chat_client() {
	require_once NORDIC_CHAT_DIR . '/vendor/autoload.php';

	$options = array( 'maxRetries' => 2 );
	if ( class_exists( '\Http\Client\Curl\Client' ) ) {
		// Aikaraja asetetaan HTTP-kirjastoon; SDK ei itse valvo sitä.
		$options['transporter'] = new \Http\Client\Curl\Client( null, null, array(
			CURLOPT_CONNECTTIMEOUT => 10,
			CURLOPT_TIMEOUT        => 70,
		) );
	}
	/**
	 * Mahdollistaa testauksen ja välityspalvelimen (esim. oma transporter).
	 */
	$options = apply_filters( 'nordic_chat_request_options', $options );

	return new \Anthropic\Client( apiKey: nordic_chat_api_key(), requestOptions: $options );
}

/* -------------------------------------------------------------------------
 * Keskustelun tallennus
 * ---------------------------------------------------------------------- */

function nordic_chat_new_session( $page = '' ) {
	$id   = strtolower( wp_generate_password( 32, false, false ) );
	$conv = array(
		'id'            => $id,
		'created'       => time(),
		'system'        => nordic_chat_system_prompt(),
		'messages'      => array(),
		'user_count'    => 0,
		'contact_sends' => 0,
		'page'          => esc_url_raw( $page ),
	);
	return $conv;
}

function nordic_chat_load_session( $id ) {
	if ( ! preg_match( '/^[a-z0-9]{32}$/', (string) $id ) ) {
		return null;
	}
	$conv = get_transient( 'nordic_chat_s_' . $id );
	return is_array( $conv ) ? $conv : null;
}

function nordic_chat_save_session( array $conv ) {
	set_transient( 'nordic_chat_s_' . $conv['id'], $conv, NORDIC_CHAT_SESSION_TTL );
}

/**
 * Estää saman keskustelun kaksi samanaikaista pyyntöä (historia pysyy lineaarisena).
 */
function nordic_chat_lock( $id ) {
	$key = 'nordic_chat_lock_' . $id;
	if ( get_transient( $key ) ) {
		return false;
	}
	set_transient( $key, 1, 90 );
	return true;
}

function nordic_chat_unlock( $id ) {
	delete_transient( 'nordic_chat_lock_' . $id );
}

/* -------------------------------------------------------------------------
 * Viestin käsittely
 * ---------------------------------------------------------------------- */

/**
 * Lähettää asiakkaan viestin ja palauttaa avustajan vastauksen.
 *
 * @return array{reply:string, contact_sent:bool}
 * @throws \Throwable API- ja verkkovirheet (käsitellään rest.php:ssä).
 */
function nordic_chat_respond( array &$conv, $user_text ) {
	$client = nordic_chat_client();
	$tools  = nordic_chat_tools();

	$conv['base']       = count( $conv['messages'] ); // vuoron alku: tähän palataan virheen tai kieltäytymisen jälkeen
	$conv['messages'][] = array( 'role' => 'user', 'content' => $user_text );
	$conv['user_count']++;
	$sends_before = (int) $conv['contact_sends'];

	for ( $round = 0; $round < NORDIC_CHAT_MAX_ROUNDS; $round++ ) {
		$response = $client->beta->messages->create(
			model: apply_filters( 'nordic_chat_model', NORDIC_CHAT_MODEL ),
			maxTokens: 16000,
			system: array(
				array( 'type' => 'text', 'text' => $conv['system'] ),
			),
			tools: $tools,
			messages: $conv['messages'],
			// Asiakaspalvelukeskusteluun riittää matala ajattelutaso: nopeampi ja edullisempi.
			outputConfig: array( 'effort' => 'low' ),
			// Välimuisti: järjestelmäkehote, työkalut ja aiempi keskustelu luetaan halvemmalla.
			cacheControl: array( 'type' => 'ephemeral' ),
			// Jos turvallisuusluokittelija hylkää pyynnön, API ajaa sen varamallilla.
			fallbacks: 'default',
			betas: array( 'server-side-fallback-2026-07-01' ),
		);

		$content = json_decode( wp_json_encode( $response->content ), true );

		if ( 'refusal' === $response->stopReason ) {
			// Kieltäytymisen vastaus voi olla tyhjä, eikä tyhjää viestiä voi lähettää
			// takaisin API:lle. Poistetaan koko vuoro historian lopusta (alkuosa ei
			// muutu, joten aiemmat ajattelulohkot pysyvät voimassa).
			nordic_chat_rollback_turn( $conv );
			return array(
				'reply'        => 'En valitettavasti pysty vastaamaan tähän. Autan mielelläni ikkunoihin, oviin ja remonttiin liittyvissä kysymyksissä, tai voit lähettää viestin suoraan osoitteeseen ' . nordic_chat_settings()['contact_email'] . '.',
				'contact_sent' => $conv['contact_sends'] > $sends_before,
			);
		}

		// Vastaus tallennetaan sellaisenaan (ajattelu- ja fallback-lohkot mukaan lukien).
		$conv['messages'][] = array( 'role' => 'assistant', 'content' => $content );

		if ( 'tool_use' !== $response->stopReason ) {
			$reply = nordic_chat_text_of( $content );
			if ( '' === $reply ) {
				$reply = 'Pahoittelut, en saanut muotoiltua vastausta. Voisitko kysyä uudelleen hieman eri sanoin?';
			}
			return array(
				'reply'        => $reply,
				'contact_sent' => $conv['contact_sends'] > $sends_before,
			);
		}

		// Työkalukutsut: kaikki tulokset samassa käyttäjäviestissä.
		$results = array();
		foreach ( $content as $block ) {
			if ( ( $block['type'] ?? '' ) !== 'tool_use' ) {
				continue;
			}
			list( $out, $is_error ) = nordic_chat_run_tool( $block['name'] ?? '', $block['input'] ?? array(), $conv );
			$result = array(
				'type'      => 'tool_result',
				'toolUseID' => $block['id'],
				'content'   => $out,
			);
			if ( $is_error ) {
				$result['isError'] = true;
			}
			$results[] = $result;
		}
		$conv['messages'][] = array( 'role' => 'user', 'content' => $results );
	}

	return array(
		'reply'        => 'Tämä vaati enemmän selvittelyä kuin ehdin tehdä. Kokeile kysyä tarkemmin, tai lähetä viesti osoitteeseen ' . nordic_chat_settings()['contact_email'] . '.',
		'contact_sent' => $conv['contact_sends'] > $sends_before,
	);
}

/**
 * Poistaa keskeneräisen vuoron historian lopusta. Vain loppupäätä poistetaan,
 * joten jäljelle jäävä historia on täsmälleen sama kuin aiemmissa pyynnöissä.
 * Yhteydenottolaskuri säilyy, jotta jo lähetettyä viestiä ei lähetetä uudelleen.
 */
function nordic_chat_rollback_turn( array &$conv ) {
	if ( isset( $conv['base'] ) && count( $conv['messages'] ) > $conv['base'] ) {
		array_splice( $conv['messages'], (int) $conv['base'] );
		$conv['user_count'] = max( 0, $conv['user_count'] - 1 );
	}
}

/**
 * Tekstilohkojen sisältö yhtenä merkkijonona.
 */
function nordic_chat_text_of( array $content ) {
	$parts = array();
	foreach ( $content as $block ) {
		if ( ( $block['type'] ?? '' ) === 'text' && isset( $block['text'] ) ) {
			$parts[] = $block['text'];
		}
	}
	return trim( implode( "\n\n", $parts ) );
}
