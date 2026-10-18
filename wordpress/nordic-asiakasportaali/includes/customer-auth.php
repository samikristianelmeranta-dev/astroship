<?php
/**
 * Asiakkaan kirjautuminen ilman salasanaa.
 *
 * - Kirjautumislinkki: satunnainen 32 tavun avain, tietokantaan tallennetaan vain sen SHA-256-tiiviste.
 *   Kirjautumislinkki on voimassa 7 päivää ja tarjouslinkki 90 päivää; molempiin voi palata uudelleen.
 * - Istunto: allekirjoitettu eväste (asiakas-ID, voimassaolo, istuntoversio, HMAC). Istuntoversion
 *   kasvatus kirjaa asiakkaan ulos kaikilta laitteilta.
 */

defined( 'ABSPATH' ) || exit;

const NAP_COOKIE      = 'nap_session';
const NAP_LINK_DAYS   = 7;
const NAP_SESSION_DAYS = 30;

/**
 * Luo avaimen asiakkaalle. Avaimesta tallennetaan vain tiiviste, ja sama linkki toimii koko voimassaolonsa ajan
 * (asiakas voi palata siihen). kind: login (Oma kohde, 7 pv) tai offer (yksi tarjous, 90 pv).
 */
function nap_create_token( $customer_id, $kind = 'login', $project_id = null, $days = NAP_LINK_DAYS ) {
	global $wpdb;
	$token = bin2hex( random_bytes( 32 ) );
	$wpdb->insert( nap_table( 'tokens' ), array(
		'customer_id' => (int) $customer_id,
		'project_id'  => $project_id ? (int) $project_id : null,
		'kind'        => $kind,
		'token_hash'  => hash( 'sha256', $token ),
		'expires_at'  => gmdate( 'Y-m-d H:i:s', time() + $days * DAY_IN_SECONDS ),
		'created_at'  => gmdate( 'Y-m-d H:i:s' ),
	) );
	return $token;
}

/** Kirjautumislinkki Oma kohde -sivulle. Aiemmat linkit toimivat edelleen omat 7 päiväänsä. */
function nap_create_login_link( $customer_id ) {
	return add_query_arg( 'k', nap_create_token( $customer_id ), home_url( '/oma-kohde/' ) );
}

/** Suora linkki tarjoukseen: ei kirjautumista, toimii 90 päivää. */
function nap_create_offer_link( $customer_id, $project_id ) {
	return home_url( '/tarjous/' . nap_create_token( $customer_id, 'offer', $project_id, 90 ) . '/' );
}

/** Voimassa oleva avain → array( asiakas, avainrivi ) tai null. */
function nap_check_token( $token, $kind = null ) {
	global $wpdb;
	if ( ! is_string( $token ) || ! preg_match( '/^[a-f0-9]{64}$/', $token ) ) {
		return null;
	}
	$row = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . nap_table( 'tokens' ) . ' WHERE token_hash = %s LIMIT 1', hash( 'sha256', $token ) ), ARRAY_A );
	if ( ! $row || strtotime( $row['expires_at'] . ' UTC' ) < time() || ( $kind && $row['kind'] !== $kind ) ) {
		return null;
	}
	$c = nap_get_customer( (int) $row['customer_id'] );
	return $c ? array( $c, $row ) : null;
}

/** Oma kohde -linkki (myös tarjouslinkki kelpaa kirjautumiseen). */
function nap_consume_token( $token ) {
	$r = nap_check_token( $token );
	return $r ? $r[0] : null;
}

/** Vanhentuneiden avainten siivous. */
add_action( 'nap_hourly', function () {
	global $wpdb;
	$wpdb->query( $wpdb->prepare( 'DELETE FROM ' . nap_table( 'tokens' ) . ' WHERE expires_at < %s', gmdate( 'Y-m-d H:i:s', time() - DAY_IN_SECONDS ) ) ); // phpcs:ignore
}, 30 );

function nap_session_sign( $payload ) {
	return hash_hmac( 'sha256', $payload, wp_salt( 'auth' ) . 'nap' );
}

function nap_set_session( $customer ) {
	$exp     = time() + NAP_SESSION_DAYS * DAY_IN_SECONDS;
	$payload = (int) $customer['id'] . '|' . $exp . '|' . (int) $customer['session_version'];
	$value   = $payload . '|' . nap_session_sign( $payload );
	setcookie( NAP_COOKIE, $value, array(
		'expires'  => $exp,
		'path'     => '/',
		'secure'   => is_ssl(),
		'httponly' => true,
		'samesite' => 'Lax',
	) );
	$_COOKIE[ NAP_COOKIE ] = $value;
}

/** Kirjautunut asiakas tai null. */
function nap_current_customer() {
	static $cached = false;
	if ( false !== $cached ) {
		return $cached;
	}
	$cached = null;
	if ( empty( $_COOKIE[ NAP_COOKIE ] ) ) {
		return null;
	}
	$parts = explode( '|', wp_unslash( $_COOKIE[ NAP_COOKIE ] ) ); // phpcs:ignore
	if ( 4 !== count( $parts ) ) {
		return null;
	}
	list( $id, $exp, $ver, $sig ) = $parts;
	if ( ! hash_equals( nap_session_sign( "$id|$exp|$ver" ), $sig ) || (int) $exp < time() ) {
		return null;
	}
	$c = nap_get_customer( (int) $id );
	if ( ! $c || (int) $c['session_version'] !== (int) $ver ) {
		return null;
	}
	$cached = $c;
	return $c;
}

function nap_logout_customer() {
	setcookie( NAP_COOKIE, '', array( 'expires' => time() - 3600, 'path' => '/', 'secure' => is_ssl(), 'httponly' => true, 'samesite' => 'Lax' ) );
	unset( $_COOKIE[ NAP_COOKIE ] );
}

/**
 * Uuden linkin pyyntö sähköpostilla. Vastaus on aina sama, ettei osoitteiden olemassaoloa voi
 * päätellä. Rajoitus: 3 pyyntöä / osoite / tunti ja 10 / IP / tunti.
 */
function nap_request_link( $email ) {
	$email = strtolower( sanitize_email( $email ) );
	$ip    = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
	foreach ( array( 'e' . md5( $email ) => 3, 'i' . md5( $ip ) => 10 ) as $key => $max ) {
		$n = (int) get_transient( 'nap_rl_' . $key );
		if ( $n >= $max ) {
			return;
		}
		set_transient( 'nap_rl_' . $key, $n + 1, HOUR_IN_SECONDS );
	}
	if ( ! is_email( $email ) ) {
		return;
	}
	$c = nap_find_customer_by_email( $email );
	if ( $c ) {
		nap_mail_login_link( $c, nap_create_login_link( (int) $c['id'] ) );
	}
}
