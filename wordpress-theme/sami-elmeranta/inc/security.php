<?php
/**
 * Tietoturvakovennukset.
 *
 * Teema ei voi korvata päivityksiä, vahvoja salasanoja ja kaksivaiheista
 * tunnistautumista – ks. README.
 *
 * @package sami-elmeranta
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// WordPress-version piilotus.
remove_action( 'wp_head', 'wp_generator' );
add_filter( 'the_generator', '__return_empty_string' );

// Tarpeettomat head-linkit ja emojiskriptit.
remove_action( 'wp_head', 'rsd_link' );
remove_action( 'wp_head', 'wlwmanifest_link' );
remove_action( 'wp_head', 'wp_shortlink_wp_head' );
remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
remove_action( 'wp_print_styles', 'print_emoji_styles' );
remove_action( 'admin_print_scripts', 'print_emoji_detection_script' );
remove_action( 'admin_print_styles', 'print_emoji_styles' );
remove_filter( 'the_content_feed', 'wp_staticize_emoji' );
remove_filter( 'comment_text_rss', 'wp_staticize_emoji' );
remove_filter( 'wp_mail', 'wp_staticize_emoji_for_email' );

// XML-RPC pois (yleinen brute force -kohde).
add_filter( 'xmlrpc_enabled', '__return_false' );
add_filter( 'wp_headers', function ( $headers ) {
	unset( $headers['X-Pingback'] );
	return $headers;
} );
add_filter( 'pings_open', '__return_false' );

// Käyttäjätunnusten urkinnan esto: REST users -päätepiste vain kirjautuneille.
add_filter( 'rest_endpoints', function ( $endpoints ) {
	if ( is_user_logged_in() ) {
		return $endpoints;
	}
	foreach ( array_keys( $endpoints ) as $route ) {
		if ( 0 === strpos( $route, '/wp/v2/users' ) ) {
			unset( $endpoints[ $route ] );
		}
	}
	return $endpoints;
} );

// ?author=N -urkinta ja kirjoaja-arkistot ohjataan etusivulle.
add_action( 'template_redirect', function () {
	if ( is_author() || ( isset( $_GET['author'] ) && ! is_admin() ) ) { // phpcs:ignore WordPress.Security.NonceVerification
		wp_safe_redirect( home_url( '/' ), 301 );
		exit;
	}
} );
add_filter( 'author_link', function () {
	return home_url( '/' );
} );
// Käyttäjälista pois core-sivukartasta.
add_filter( 'wp_sitemaps_add_provider', function ( $provider, $name ) {
	return 'users' === $name ? false : $provider;
}, 10, 2 );

// Yleinen kirjautumisvirhe (ei paljasta, oliko tunnus oikein).
add_filter( 'login_errors', function () {
	return 'Kirjautuminen epäonnistui. Tarkista tiedot.';
} );

// Tietoturvaotsakkeet julkisille sivuille.
add_action( 'send_headers', function () {
	if ( is_admin() || headers_sent() ) {
		return;
	}
	header( 'X-Content-Type-Options: nosniff' );
	header( 'X-Frame-Options: SAMEORIGIN' );
	header( 'Referrer-Policy: strict-origin-when-cross-origin' );
	header( 'Permissions-Policy: camera=(), microphone=(), geolocation=(), payment=(), usb=(), browsing-topics=()' );
	header( 'Cross-Origin-Opener-Policy: same-origin' );
	header( "Content-Security-Policy: frame-ancestors 'self'; base-uri 'self'; object-src 'none'; form-action 'self' https://*.list-manage.com" );
	if ( is_ssl() ) {
		header( 'Strict-Transport-Security: max-age=31536000; includeSubDomains' );
	}
} );

// Ladattavat tiedostot: estetään SVG:n ja muiden skriptattavien tyyppien lataus.
add_filter( 'upload_mimes', function ( $mimes ) {
	unset( $mimes['svg'], $mimes['svgz'], $mimes['swf'], $mimes['exe'] );
	return $mimes;
} );
