<?php
/**
 * Poistettujen sivujen pysyvät uudelleenohjaukset (301), jotta vanhat linkit ja
 * hakukonetulokset eivät johda virhesivulle.
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
		$map  = apply_filters(
			'tilihub_redirects',
			array(
				'/kirjanpito-hoiva-alalle/' => '/kirjanpitopalvelut/',
			)
		);
		$path = trailingslashit( (string) wp_parse_url( $_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH ) ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
		if ( isset( $map[ $path ] ) ) {
			wp_safe_redirect( home_url( $map[ $path ] ), 301 );
			exit;
		}
	}
);
