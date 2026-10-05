<?php
/**
 * REST-rajapinta: POST /wp-json/nordic-chat/v1/viesti
 *
 * Pyyntö:  { "istunto": "<id tai tyhjä>", "viesti": "...", "sivu": "<nykyinen sivu>" }
 * Vastaus: { "istunto": "<id>", "vastaus": "...", "lahetetty": bool }
 */

if ( ! defined( 'ABSPATH' ) ) exit;

const NORDIC_CHAT_MAX_INPUT = 1500; // merkkiä per viesti

add_action( 'rest_api_init', function () {
	register_rest_route( 'nordic-chat/v1', '/viesti', array(
		'methods'             => 'POST',
		'callback'            => 'nordic_chat_rest_message',
		'permission_callback' => '__return_true',
		'args'                => array(
			'viesti'  => array( 'type' => 'string', 'required' => true ),
			'istunto' => array( 'type' => 'string', 'required' => false ),
			'sivu'    => array( 'type' => 'string', 'required' => false ),
		),
	) );
} );

function nordic_chat_error( $message, $status ) {
	return new WP_REST_Response( array( 'virhe' => $message ), $status );
}

function nordic_chat_client_ip() {
	return isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
}

function nordic_chat_rest_message( WP_REST_Request $request ) {
	$s     = nordic_chat_settings();
	$email = $s['contact_email'];

	if ( ! nordic_chat_is_ready() ) {
		return nordic_chat_error( 'Chat ei ole juuri nyt käytettävissä. Lähetä viesti osoitteeseen ' . $email . '.', 503 );
	}

	// Vain omalta sivustolta (selaimen Origin-otsake, jos se on mukana).
	$origin = $request->get_header( 'origin' );
	if ( $origin && wp_parse_url( $origin, PHP_URL_HOST ) !== wp_parse_url( home_url(), PHP_URL_HOST ) ) {
		return nordic_chat_error( 'Pyyntö estetty.', 403 );
	}

	$text = trim( sanitize_textarea_field( (string) $request->get_param( 'viesti' ) ) );
	if ( '' === $text ) {
		return nordic_chat_error( 'Kirjoita viesti.', 400 );
	}
	if ( mb_strlen( $text ) > NORDIC_CHAT_MAX_INPUT ) {
		return nordic_chat_error( 'Viesti on liian pitkä. Lyhennä se alle ' . NORDIC_CHAT_MAX_INPUT . ' merkkiin, tai lähetä pidempi viesti osoitteeseen ' . $email . '.', 400 );
	}

	// Rajat: koko sivusto / vuorokausi ja IP / tunti.
	$day_key = 'nordic_chat_day_' . gmdate( 'Ymd' );
	if ( (int) get_transient( $day_key ) >= (int) $s['daily_limit'] ) {
		return nordic_chat_error( 'Chat on tältä päivältä tauolla. Lähetä viesti osoitteeseen ' . $email . ', niin vastaamme viimeistään seuraavana arkipäivänä.', 429 );
	}
	$ip_key = 'nordic_chat_ip_' . md5( nordic_chat_client_ip() . wp_salt( 'nonce' ) );
	if ( (int) get_transient( $ip_key ) >= (int) $s['ip_hour_limit'] ) {
		return nordic_chat_error( 'Viestejä on lähetetty lyhyessä ajassa paljon. Odota hetki, tai lähetä viesti osoitteeseen ' . $email . '.', 429 );
	}

	// Keskustelu: jatketaan olemassa olevaa tai aloitetaan uusi.
	$conv = nordic_chat_load_session( (string) $request->get_param( 'istunto' ) );
	if ( ! $conv ) {
		$page = esc_url_raw( (string) $request->get_param( 'sivu' ) );
		if ( $page && wp_parse_url( $page, PHP_URL_HOST ) !== wp_parse_url( home_url(), PHP_URL_HOST ) ) {
			$page = '';
		}
		$conv = nordic_chat_new_session( $page );
	}
	if ( $conv['user_count'] >= (int) $s['session_limit'] ) {
		return new WP_REST_Response( array(
			'istunto'   => $conv['id'],
			'vastaus'   => 'Tämä keskustelu on jo aika pitkä, joten jatketaan mieluummin sähköpostilla: ' . $email . '. Vastaamme viimeistään seuraavana arkipäivänä.',
			'lahetetty' => false,
		), 200 );
	}
	if ( ! nordic_chat_lock( $conv['id'] ) ) {
		return nordic_chat_error( 'Edellinen viesti on vielä käsittelyssä. Odota hetki.', 409 );
	}

	set_transient( $day_key, (int) get_transient( $day_key ) + 1, DAY_IN_SECONDS );
	set_transient( $ip_key, (int) get_transient( $ip_key ) + 1, HOUR_IN_SECONDS );
	if ( function_exists( 'set_time_limit' ) ) {
		@set_time_limit( 180 ); // phpcs:ignore
	}

	try {
		$result = nordic_chat_respond( $conv, $text );
		nordic_chat_save_session( $conv );
		nordic_chat_unlock( $conv['id'] );
		return new WP_REST_Response( array(
			'istunto'   => $conv['id'],
			'vastaus'   => $result['reply'],
			'lahetetty' => (bool) $result['contact_sent'],
		), 200 );
	} catch ( \Throwable $e ) {
		// Keskeneräinen vuoro pois historiasta; yhteydenottolaskuri säilyy, jotta
		// ehditty yhteydenotto ei lähde uudelleen.
		nordic_chat_rollback_turn( $conv );
		nordic_chat_save_session( $conv );
		nordic_chat_unlock( $conv['id'] );
		nordic_chat_log_error( $e );
		$busy = $e instanceof \Anthropic\Core\Exceptions\RateLimitException
			|| ( class_exists( '\Anthropic\Core\Exceptions\InternalServerException' ) && $e instanceof \Anthropic\Core\Exceptions\InternalServerException );
		return nordic_chat_error(
			$busy
				? 'Avustaja on juuri nyt ruuhkautunut. Yritä hetken päästä uudelleen, tai lähetä viesti osoitteeseen ' . $email . '.'
				: 'Avustaja ei vastannut. Yritä uudelleen, tai lähetä viesti osoitteeseen ' . $email . '.',
			$busy ? 503 : 502
		);
	}
}

/**
 * Virheet PHP:n lokiin (ei koskaan selaimelle).
 */
function nordic_chat_log_error( \Throwable $e ) {
	$status = method_exists( $e, 'getCode' ) ? $e->getCode() : 0;
	error_log( '[Nordic Chat] ' . get_class( $e ) . ' (' . $status . '): ' . $e->getMessage() ); // phpcs:ignore
}
