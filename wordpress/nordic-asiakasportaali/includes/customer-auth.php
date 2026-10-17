<?php
/**
 * Asiakkaan kirjautuminen ilman salasanaa.
 *
 * - Kirjautumislinkki: satunnainen 32 tavun avain, tietokantaan tallennetaan vain sen SHA-256-tiiviste.
 *   Linkki on voimassa 7 päivää ja toimii kerran.
 * - Istunto: allekirjoitettu eväste (asiakas-ID, voimassaolo, istuntoversio, HMAC). Istuntoversion
 *   kasvatus kirjaa asiakkaan ulos kaikilta laitteilta.
 */

defined( 'ABSPATH' ) || exit;

const NAP_COOKIE      = 'nap_session';
const NAP_LINK_DAYS   = 7;
const NAP_SESSION_DAYS = 30;

/** Luo uuden kirjautumislinkin asiakkaalle (vanha linkki lakkaa toimimasta). */
function nap_create_login_link( $customer_id ) {
	global $wpdb;
	$token = bin2hex( random_bytes( 32 ) );
	$wpdb->update( nap_table( 'customers' ), array(
		'token_hash'    => hash( 'sha256', $token ),
		'token_expires' => gmdate( 'Y-m-d H:i:s', time() + NAP_LINK_DAYS * DAY_IN_SECONDS ),
	), array( 'id' => (int) $customer_id ) );
	return add_query_arg( 'k', $token, home_url( '/oma-kohde/' ) );
}

/** Tarkistaa linkin avaimen ja palauttaa asiakkaan tai null. Avain mitätöidään käytössä. */
function nap_consume_token( $token ) {
	global $wpdb;
	if ( ! is_string( $token ) || ! preg_match( '/^[a-f0-9]{64}$/', $token ) ) {
		return null;
	}
	$row = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . nap_table( 'customers' ) . ' WHERE token_hash = %s LIMIT 1', hash( 'sha256', $token ) ), ARRAY_A );
	if ( ! $row || ! $row['token_expires'] || strtotime( $row['token_expires'] . ' UTC' ) < time() ) {
		return null;
	}
	$wpdb->update( nap_table( 'customers' ), array( 'token_hash' => '', 'token_expires' => null ), array( 'id' => (int) $row['id'] ) );
	return $row;
}

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
