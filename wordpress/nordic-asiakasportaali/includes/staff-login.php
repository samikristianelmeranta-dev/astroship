<?php
/**
 * Henkilökunnan kirjautuminen osoitteessa /crm/: käyttäjänimi (tai sähköposti) + salasana.
 *  - "Pysy kirjautuneena" pitää kirjautumisen voimassa 90 päivää samalla laitteella.
 *  - Arvausesto: 5 väärää salasanaa samalle tunnukselle tai 10 samasta osoitteesta → 15 minuutin tauko.
 *    Koskee myös WordPressin omaa kirjautumissivua.
 *  - Jos sähköpostikoodi on asetuksissa päällä, se kysytään salasanan jälkeen kuten ennenkin.
 */

defined( 'ABSPATH' ) || exit;

const NAP_REMEMBER_DAYS = 90;
const NAP_LOCK_MINUTES  = 15;

function nap_client_ip() {
	return isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
}

function nap_fail_keys( $username ) {
	return array(
		'u' => 'nap_lf_u_' . md5( strtolower( trim( (string) $username ) ) ),
		'i' => 'nap_lf_i_' . md5( nap_client_ip() ),
	);
}

function nap_login_locked( $username ) {
	$k = nap_fail_keys( $username );
	return (int) get_transient( $k['u'] ) >= 5 || (int) get_transient( $k['i'] ) >= 10;
}

add_action( 'wp_login_failed', function ( $username ) {
	foreach ( nap_fail_keys( $username ) as $key ) {
		set_transient( $key, (int) get_transient( $key ) + 1, NAP_LOCK_MINUTES * MINUTE_IN_SECONDS );
	}
} );

add_action( 'wp_login', function ( $login ) {
	delete_transient( nap_fail_keys( $login )['u'] );
} );

/** Lukittu tunnus ei pääse sisään oikeallakaan salasanalla ennen tauon päättymistä. */
add_filter( 'authenticate', function ( $user, $username ) {
	if ( $username && nap_login_locked( $username ) ) {
		return new WP_Error( 'nap_locked', 'Liian monta väärää yritystä. Odota ' . NAP_LOCK_MINUTES . ' minuuttia ja yritä uudelleen.' );
	}
	return $user;
}, 98, 2 );

/** "Pysy kirjautuneena": henkilökunnalle 90 päivää (WordPressin oletus on 14). */
add_filter( 'auth_cookie_expiration', function ( $length, $user_id, $remember ) {
	return $remember && user_can( $user_id, 'nap_access' ) ? NAP_REMEMBER_DAYS * DAY_IN_SECONDS : $length;
}, 10, 3 );

/** /crm/ ilman kirjautumista: kirjautumislomake tai sen käsittely. */
function nap_crm_login_page() {
	$error = '';
	$login = '';
	if ( 'POST' === ( $_SERVER['REQUEST_METHOD'] ?? '' ) && isset( $_POST['log'], $_POST['pwd'] ) ) { // phpcs:ignore
		$login = sanitize_user( wp_unslash( $_POST['log'] ) ); // phpcs:ignore
		if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_wpnonce'] ?? '' ) ), 'nap_crm_login' ) ) { // phpcs:ignore
			$error = 'Lomake vanheni. Yritä uudelleen.';
		} else {
			// redirect_to on mukana, jotta mahdollinen sähköpostikoodi palaa CRM:ään.
			$_REQUEST['redirect_to'] = home_url( '/crm/' );
			$user = wp_signon( array(
				'user_login'    => $login,
				'user_password' => (string) wp_unslash( $_POST['pwd'] ), // phpcs:ignore
				'remember'      => ! empty( $_POST['rememberme'] ), // phpcs:ignore
			), is_ssl() );
			if ( ! is_wp_error( $user ) ) {
				wp_safe_redirect( home_url( '/crm/' ) );
				exit;
			}
			$error = 'nap_locked' === $user->get_error_code() ? $user->get_error_message() : 'Väärä käyttäjänimi tai salasana.';
		}
	}
	include NAP_DIR . '/templates/crm-login.php';
	exit;
}
