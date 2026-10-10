<?php
/**
 * Pienet apulyhytkoodit sivupohjiin.
 *
 * [tilihub_year]          – kuluva vuosi (alatunnisteen tekijänoikeusrivi)
 * [tilihub_reading_time]  – artikkelin arvioitu lukuaika
 *
 * @package TiliHub
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_shortcode(
	'tilihub_year',
	static function () {
		return esc_html( wp_date( 'Y' ) );
	}
);

add_shortcode(
	'tilihub_reading_time',
	static function () {
		$post = get_post();
		if ( ! $post ) {
			return '';
		}
		$words   = str_word_count( wp_strip_all_tags( strip_shortcodes( $post->post_content ) ), 0, 'åäöÅÄÖéÉüÜ' );
		$minutes = max( 1, (int) round( $words / 180 ) );
		/* translators: %d: minutes */
		return esc_html( sprintf( __( 'Lukuaika noin %d min', 'tilihub' ), $minutes ) );
	}
);

/**
 * Sivupohjien kappaleissa olevat [tilihub_…]-koodit suoritetaan.
 */
add_filter(
	'render_block_core/paragraph',
	static function ( $content ) {
		return false !== strpos( $content, '[tilihub_' ) ? do_shortcode( $content ) : $content;
	}
);
