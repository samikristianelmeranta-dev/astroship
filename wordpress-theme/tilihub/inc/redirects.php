<?php
/**
 * Pysyvät uudelleenohjaukset (301) poistetuille sivuille, yhdistetyille artikkeleille ja
 * vanhoille rikkinäisille osoitteille, jotta vanhat linkit ja hakutulokset eivät johda
 * virhesivulle. Lista: inc/redirects-map.php (generoitu tiedostosta tools/redirects.json).
 *
 * @package TiliHub
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action(
	'template_redirect',
	static function () {
		if ( ! is_404() ) {
			return;
		}
		$map  = apply_filters( 'tilihub_redirects', require __DIR__ . '/redirects-map.php' );
		$path = (string) wp_parse_url( wp_unslash( $_SERVER['REQUEST_URI'] ?? '' ), PHP_URL_PATH ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
		$path = rawurldecode( $path );
		$keys = array( $path, trailingslashit( $path ), untrailingslashit( $path ) );
		foreach ( $keys as $key ) {
			if ( isset( $map[ $key ] ) ) {
				wp_safe_redirect( home_url( $map[ $key ] ), 301, 'TiliHub' );
				exit;
			}
		}
	},
	1 // ennen WordPressin omaa osoitearvausta
);
