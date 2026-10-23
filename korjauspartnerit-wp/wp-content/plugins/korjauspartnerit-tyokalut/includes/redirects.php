<?php
/**
 * Vanhan sivuston osoitteiden uudelleenohjaukset (301), jotta hakukonenäkyvyys
 * ja vanhat linkit säilyvät.
 *
 * Vanhalla sivustolla sekä palvelut että referenssit olivat osoitteessa /palvelut/…
 * Uudessa referenssit ovat osoitteessa /referenssit/…
 * Omia ohjauksia voi lisätä kohdassa Asetukset → Korjauspartnerit.
 */

defined( 'ABSPATH' ) || exit;

function kp_builtin_redirects() {
	return array(
		'palvelut/yllapitopartnerit'   => '/palvelut/yllapitopartneri/',
		'teollisuuden-referenssit'     => '/hanketyyppi/teollisuus/',
		'henkilohaku'                  => '/henkilokunta/',
		'contact-us'                   => '/yhteystiedot/',
		'ota-yhteytta'                 => '/yhteystiedot/#viesti',
		'portfolio-items'              => '/referenssit/',
		'portfolio_category'           => '/referenssit/',
	);
}

function kp_custom_redirects() {
	$out = array();
	foreach ( preg_split( '/\r?\n/', (string) kp_setting( 'uudelleenohjaukset' ) ) as $line ) {
		if ( false === strpos( $line, '=>' ) ) {
			continue;
		}
		list( $from, $to ) = array_map( 'trim', explode( '=>', $line, 2 ) );
		$from = trim( wp_parse_url( $from, PHP_URL_PATH ) ?: $from, '/' );
		if ( $from && $to ) {
			$out[ $from ] = $to;
		}
	}
	return $out;
}

add_action( 'template_redirect', function () {
	if ( ! is_404() ) {
		return;
	}
	$path = trim( (string) wp_parse_url( isset( $_SERVER['REQUEST_URI'] ) ? wp_unslash( $_SERVER['REQUEST_URI'] ) : '', PHP_URL_PATH ), '/' ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
	$home = trim( (string) wp_parse_url( home_url(), PHP_URL_PATH ), '/' );
	if ( $home && 0 === strpos( $path, $home ) ) {
		$path = trim( substr( $path, strlen( $home ) ), '/' );
	}
	$path = rawurldecode( $path );

	$map = array_merge( kp_builtin_redirects(), kp_custom_redirects() );
	foreach ( $map as $from => $to ) {
		if ( $path === $from || 0 === strpos( $path, $from . '/' ) ) {
			wp_safe_redirect( home_url( $to ), 301 );
			exit;
		}
	}

	// /palvelut/{slug}/ → referenssi samalla nimellä.
	if ( preg_match( '#^palvelut/([^/]+)$#', $path, $m ) ) {
		$ref = get_page_by_path( sanitize_title( $m[1] ), OBJECT, 'referenssi' );
		if ( $ref && 'publish' === $ref->post_status ) {
			wp_safe_redirect( get_permalink( $ref ), 301 );
			exit;
		}
	}

	// Viimeinen yritys: sama polun loppuosa missä tahansa julkisessa sisältötyypissä.
	$slug = sanitize_title( basename( $path ) );
	if ( $slug ) {
		$found = get_posts( array(
			'name'        => $slug,
			'post_type'   => array( 'page', 'post', 'palvelu', 'referenssi', 'henkilokunta', 'tyopaikka' ),
			'post_status' => 'publish',
			'numberposts' => 1,
		) );
		if ( $found ) {
			wp_safe_redirect( get_permalink( $found[0] ), 301 );
			exit;
		}
	}
}, 5 );
