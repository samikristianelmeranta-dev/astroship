<?php
/**
 * Tietosuojaselosteen tekstiehdotus: näkyy kohdassa Asetukset → Tietosuoja →
 * "Tietosuojaselosteen ohjeet", josta sen voi kopioida sivuston selosteeseen.
 * Sama teksti on myös tiedostossa TIETOSUOJA.md.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

add_action( 'admin_init', function () {
	if ( ! function_exists( 'wp_add_privacy_policy_content' ) ) {
		return;
	}
	$file = NORDIC_CRM_DIR . '/TIETOSUOJA.md';
	if ( ! is_readable( $file ) ) {
		return;
	}
	$md   = (string) file_get_contents( $file );
	$html = nordic_crm_simple_markdown( $md );
	wp_add_privacy_policy_content( 'Nordic CRM (yhteydenotot ja markkinointi)', wp_kses_post( $html ) );
} );

/**
 * Riittävä Markdown → HTML tähän tiedostoon (otsikot, listat, lihavointi, kappaleet).
 */
function nordic_crm_simple_markdown( $md ) {
	$out   = array();
	$list  = false;
	$para  = array();
	$flush = function () use ( &$para, &$out ) {
		if ( $para ) {
			$out[] = '<p>' . implode( ' ', $para ) . '</p>';
			$para  = array();
		}
	};
	foreach ( preg_split( '/\r?\n/', $md ) as $line ) {
		$line = rtrim( $line );
		$text = preg_replace( '/\*\*(.+?)\*\*/', '<strong>$1</strong>', esc_html( ltrim( $line, '#-> ' ) ) );
		if ( preg_match( '/^(#{1,4})\s/', $line, $m ) ) {
			$flush();
			if ( $list ) { $out[] = '</ul>'; $list = false; }
			$level = min( 4, strlen( $m[1] ) + 1 );
			$out[] = "<h{$level}>" . $text . "</h{$level}>";
		} elseif ( preg_match( '/^\s*-\s/', $line ) ) {
			$flush();
			if ( ! $list ) { $out[] = '<ul>'; $list = true; }
			$out[] = '<li>' . $text . '</li>';
		} elseif ( '' === trim( $line ) ) {
			$flush();
			if ( $list ) { $out[] = '</ul>'; $list = false; }
		} else {
			$para[] = $text;
		}
	}
	$flush();
	if ( $list ) {
		$out[] = '</ul>';
	}
	return implode( "\n", $out );
}
