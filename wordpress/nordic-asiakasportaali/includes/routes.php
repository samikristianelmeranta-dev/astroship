<?php
/**
 * Osoitteet:
 *  /crm/          Nordicin henkilökunnan sovellus (vaatii kirjautumisen ja roolin)
 *  /crm/manifest.webmanifest ja /crm/sw.js  kotinäytölle asennus ja Androidin Jaa-valikko
 *  /oma-kohde/    asiakkaan portaali (kirjautuminen sähköpostilinkillä)
 *  /tarjous/AVAIN/ tarjous suoraan linkistä ilman kirjautumista (linkkiin voi palata)
 *  /s/KOODI/      suosittelulinkki: tallentaa suosittelijan ja ohjaa etusivulle
 * Sivut eivät näy hakukoneille eikä niitä tallenneta välimuistiin.
 */

defined( 'ABSPATH' ) || exit;

function nap_add_rewrites() {
	add_rewrite_rule( '^crm/?$', 'index.php?nap_route=crm', 'top' );
	add_rewrite_rule( '^crm/manifest\\.webmanifest$', 'index.php?nap_route=manifest', 'top' );
	add_rewrite_rule( '^crm/sw\\.js$', 'index.php?nap_route=sw', 'top' );
	add_rewrite_rule( '^oma-kohde/?$', 'index.php?nap_route=oma', 'top' );
	add_rewrite_rule( '^tarjous/([a-f0-9]{64})/?$', 'index.php?nap_route=offer&nap_tok=$matches[1]', 'top' );
	add_rewrite_rule( '^s/([A-Za-z0-9]{4,12})/?$', 'index.php?nap_route=ref&nap_ref=$matches[1]', 'top' );
}
add_action( 'init', 'nap_add_rewrites' );

add_filter( 'query_vars', function ( $v ) {
	$v[] = 'nap_route';
	$v[] = 'nap_ref';
	$v[] = 'nap_tok';
	return $v;
} );

// /crm/sw.js ja /crm/manifest.webmanifest eivät saa saada kauttaviivaa perään (service worker ei salli uudelleenohjausta).
add_filter( 'redirect_canonical', function ( $url ) {
	return get_query_var( 'nap_route' ) ? false : $url;
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

	if ( 'manifest' === $route ) {
		nap_send_manifest();
	}
	if ( 'sw' === $route ) {
		header( 'Content-Type: application/javascript; charset=utf-8' );
		header( 'Service-Worker-Allowed: /crm/' );
		// Ei välimuistia: sovellus hakee aina tuoreet tiedot. Fetch-käsittelijä tarvitaan asennettavuuteen.
		echo "self.addEventListener('install',function(){self.skipWaiting();});\n"
			. "self.addEventListener('activate',function(e){e.waitUntil(self.clients.claim());});\n"
			. "self.addEventListener('fetch',function(){});\n";
		exit;
	}

	if ( 'crm' === $route ) {
		if ( ! is_user_logged_in() ) {
			auth_redirect();
		}
		if ( ! current_user_can( 'nap_access' ) ) {
			wp_die( 'Sinulla ei ole oikeutta Nordicin CRM:ään. Pyydä osakasta lisäämään sinulle rooli.', 'Ei oikeutta', array( 'response' => 403 ) );
		}
		if ( isset( $_GET['tarjous_pdf'] ) ) { // phpcs:ignore
			$p = nap_get_project( absint( $_GET['tarjous_pdf'] ) ); // phpcs:ignore
			if ( ! nap_can_see_project( $p ) ) {
				wp_die( 'Ei oikeutta.', '', array( 'response' => 403 ) );
			}
			nap_send_offer_pdf( nap_get_offer( $p ), 'tarjous-' . $p['id'] . '.pdf' );
		}
		include NAP_DIR . '/templates/crm.php';
		exit;
	}

	if ( 'oma' === $route ) {
		nap_handle_customer_route();
	}
	if ( 'offer' === $route ) {
		$tok = (string) get_query_var( 'nap_tok' );
		$r   = nap_check_token( $tok, 'offer' );
		$p   = $r ? nap_get_project( (int) $r[1]['project_id'] ) : null;
		if ( ! $p || (int) $p['customer_id'] !== (int) $r[0]['id'] || ! nap_get_offer( $p ) ) {
			wp_safe_redirect( add_query_arg( 'virhe', 'linkki', home_url( '/oma-kohde/' ) ) );
			exit;
		}
		if ( ! nap_current_customer() ) {
			nap_set_session( $r[0] ); // samalla pääsee Oma kohde -sivulle ilman uutta linkkiä
		}
		nap_offer_page( $r[0], $p, home_url( '/tarjous/' . $tok . '/' ) );
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
		if ( in_array( $action, array( 'offer_accept', 'offer_question', 'offer_decline' ), true ) ) {
			$c = nap_current_customer();
			if ( ! $c ) {
				wp_safe_redirect( home_url( '/oma-kohde/' ) );
				exit;
			}
			$msg = nap_customer_offer_action( $c, $action );
			wp_safe_redirect( add_query_arg( 'tarjous', $msg, home_url( '/oma-kohde/' ) ) . '#tarjous' );
			exit;
		}
		if ( 'logout' === $action && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_wpnonce'] ?? '' ) ), 'nap_logout' ) ) {
			nap_logout_customer();
			wp_safe_redirect( home_url( '/oma-kohde/' ) );
			exit;
		}
	}
	if ( isset( $_GET['nayta'] ) ) { // phpcs:ignore
		$c = nap_current_customer();
		$p = $c ? nap_get_project( absint( $_GET['nayta'] ) ) : null; // phpcs:ignore
		if ( $p && (int) $p['customer_id'] === (int) $c['id'] && nap_get_offer( $p ) ) {
			nap_offer_page( $c, $p, add_query_arg( 'nayta', (int) $p['id'], home_url( '/oma-kohde/' ) ) );
		}
	}
	if ( isset( $_GET['tarjous_pdf'] ) ) { // phpcs:ignore
		$c = nap_current_customer();
		$p = $c ? nap_get_project( absint( $_GET['tarjous_pdf'] ) ) : null; // phpcs:ignore
		if ( ! $p || (int) $p['customer_id'] !== (int) $c['id'] ) {
			wp_safe_redirect( home_url( '/oma-kohde/' ) );
			exit;
		}
		nap_send_offer_pdf( nap_get_offer( $p ), 'tarjous-nordic-ikkunat.pdf' );
	}
	include NAP_DIR . '/templates/oma-kohde.php';
	exit;
}

/** Kotinäytölle asennettava sovellus. share_target tuo sovelluksen Androidin Jaa-valikkoon. */
function nap_send_manifest() {
	header( 'Content-Type: application/manifest+json; charset=utf-8' );
	echo wp_json_encode( array(
		'name'             => 'Nordic CRM',
		'short_name'       => 'Nordic CRM',
		'start_url'        => home_url( '/crm/' ),
		'scope'            => home_url( '/crm/' ),
		'display'          => 'standalone',
		'background_color' => '#16303B',
		'theme_color'      => '#16303B',
		'lang'             => 'fi',
		'icons'            => array(
			array( 'src' => NAP_URL . 'assets/icon-192.png', 'sizes' => '192x192', 'type' => 'image/png', 'purpose' => 'any maskable' ),
			array( 'src' => NAP_URL . 'assets/icon-512.png', 'sizes' => '512x512', 'type' => 'image/png', 'purpose' => 'any maskable' ),
		),
		'share_target'     => array(
			'action' => home_url( '/crm/' ),
			'method' => 'GET',
			'params' => array( 'title' => 'jaa_otsikko', 'text' => 'jaa_teksti', 'url' => 'jaa_linkki' ),
		),
	), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
	exit;
}

/** Tarjoussivu: PDF, asiakkaan toiminnot ja näkymä. $base = sivun oma osoite (tarjouslinkki tai Oma kohde). */
function nap_offer_page( $c, $p, $base ) {
	if ( isset( $_GET['pdf'] ) ) { // phpcs:ignore
		nap_send_offer_pdf( nap_get_offer( $p ), 'tarjous-nordic-ikkunat.pdf' );
	}
	if ( 'POST' === $_SERVER['REQUEST_METHOD'] ) { // phpcs:ignore
		$action = sanitize_key( $_POST['nap_action'] ?? '' ); // phpcs:ignore
		$msg    = in_array( $action, array( 'offer_accept', 'offer_question', 'offer_decline' ), true ) ? nap_customer_offer_action( $c, $action ) : 'virhe';
		wp_safe_redirect( add_query_arg( 'tila', $msg, $base ) );
		exit;
	}
	nap_mark_offer_viewed( $p );
	$p = nap_get_project( (int) $p['id'] );
	include NAP_DIR . '/templates/tarjous.php';
	exit;
}
