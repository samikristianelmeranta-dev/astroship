<?php
/**
 * Osoitteet:
 *  /crm/          Nordicin henkilökunnan sovellus (vaatii kirjautumisen ja roolin)
 *  /oma-kohde/    asiakkaan portaali (kirjautuminen sähköpostilinkillä)
 *  /s/KOODI/      suosittelulinkki: tallentaa suosittelijan ja ohjaa etusivulle
 * Sivut eivät näy hakukoneille eikä niitä tallenneta välimuistiin.
 */

defined( 'ABSPATH' ) || exit;

function nap_add_rewrites() {
	add_rewrite_rule( '^crm/?$', 'index.php?nap_route=crm', 'top' );
	add_rewrite_rule( '^oma-kohde/?$', 'index.php?nap_route=oma', 'top' );
	add_rewrite_rule( '^s/([A-Za-z0-9]{4,12})/?$', 'index.php?nap_route=ref&nap_ref=$matches[1]', 'top' );
}
add_action( 'init', 'nap_add_rewrites' );

add_filter( 'query_vars', function ( $v ) {
	$v[] = 'nap_route';
	$v[] = 'nap_ref';
	return $v;
} );

add_action( 'template_redirect', function () {
	$route = get_query_var( 'nap_route' );
	if ( ! $route ) {
		return;
	}
	nocache_headers();
	header( 'X-Robots-Tag: noindex, nofollow', true );
	if ( ! defined( 'DONOTCACHEPAGE' ) ) {
		define( 'DONOTCACHEPAGE', true ); // WP-Optimize ym. välimuistit
	}

	if ( 'ref' === $route ) {
		$code = strtoupper( sanitize_text_field( get_query_var( 'nap_ref' ) ) );
		if ( nap_find_customer_by_ref( $code ) ) {
			setcookie( 'nap_ref', $code, array( 'expires' => time() + 90 * DAY_IN_SECONDS, 'path' => '/', 'secure' => is_ssl(), 'httponly' => true, 'samesite' => 'Lax' ) );
		}
		wp_safe_redirect( add_query_arg( 'suositus', '1', home_url( '/' ) ) );
		exit;
	}

	if ( 'crm' === $route ) {
		if ( ! is_user_logged_in() ) {
			auth_redirect();
		}
		if ( ! current_user_can( 'nap_access' ) ) {
			wp_die( 'Sinulla ei ole oikeutta Nordicin CRM:ään. Pyydä osakasta lisäämään sinulle rooli.', 'Ei oikeutta', array( 'response' => 403 ) );
		}
		include NAP_DIR . '/templates/crm.php';
		exit;
	}

	if ( 'oma' === $route ) {
		nap_handle_customer_route();
	}
} );

function nap_handle_customer_route() {
	// Kirjautumislinkki
	if ( isset( $_GET['k'] ) ) { // phpcs:ignore
		$c = nap_consume_token( sanitize_text_field( wp_unslash( $_GET['k'] ) ) ); // phpcs:ignore
		if ( $c ) {
			nap_set_session( $c );
			nap_log( $c['id'], null, 'login', 'Asiakas kirjautui Oma kohde -sivulle' );
			wp_safe_redirect( home_url( '/oma-kohde/' ) );
			exit;
		}
		wp_safe_redirect( add_query_arg( 'virhe', 'linkki', home_url( '/oma-kohde/' ) ) );
		exit;
	}
	if ( 'POST' === $_SERVER['REQUEST_METHOD'] ) { // phpcs:ignore
		$action = sanitize_key( $_POST['nap_action'] ?? '' ); // phpcs:ignore
		if ( 'link' === $action && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_wpnonce'] ?? '' ) ), 'nap_link' ) ) {
			if ( empty( $_POST['nap_hp'] ) ) { // piilokenttä botteja vastaan
				nap_request_link( sanitize_email( wp_unslash( $_POST['email'] ?? '' ) ) );
			}
			wp_safe_redirect( add_query_arg( 'lahetetty', '1', home_url( '/oma-kohde/' ) ) );
			exit;
		}
		if ( 'logout' === $action && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_wpnonce'] ?? '' ) ), 'nap_logout' ) ) {
			nap_logout_customer();
			wp_safe_redirect( home_url( '/oma-kohde/' ) );
			exit;
		}
	}
	include NAP_DIR . '/templates/oma-kohde.php';
	exit;
}
