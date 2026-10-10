<?php
/**
 * Vanhan sisällön siistiminen näytettäessä.
 *
 * Monet artikkelit on liitetty "Oma HTML" -lohkoina kokonaisina HTML-sivuina, joissa on
 * omat <style>-määrittelyt. Ne muuttavat koko sivun ulkoasua (esim. piilottavat teeman
 * otsikot ja vaihtavat fontit). Tämä suodatin poistaa ne vain näytettäessä – tietokannan
 * sisältöön ei kosketa – jolloin artikkelit saavat teeman yhtenäisen ilmeen.
 *
 * Suodattimen voi poistaa käytöstä: add_filter( 'tilihub_clean_legacy_content', '__return_false' );
 *
 * @package TiliHub
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Siistii yhden HTML-palan.
 *
 * @param string $html Lohkon HTML.
 * @return string
 */
function tilihub_clean_legacy_html( $html ) {
	if ( '' === trim( $html ) ) {
		return $html;
	}

	// Kokonaisen HTML-dokumentin kuoret ja <head>-osio pois.
	$html = preg_replace( '#<!DOCTYPE[^>]*>#i', '', $html );
	$html = preg_replace( '#<head\b[^>]*>.*?</head>#is', '', $html );
	$html = preg_replace( '#</?(html|body)\b[^>]*>#i', '', $html );

	// Upotetut tyylit, metatiedot ja skriptit (JSON-LD-rakennetiedot säilytetään).
	$html = preg_replace( '#<style\b[^>]*>.*?</style>#is', '', $html );
	$html = preg_replace( '#<(link|meta)\b[^>]*>#i', '', $html );
	$html = preg_replace( '#<title\b[^>]*>.*?</title>#is', '', $html );
	$html = preg_replace_callback(
		'#<script\b([^>]*)>.*?</script>#is',
		static function ( $m ) {
			return false !== stripos( $m[1], 'application/ld+json' ) ? $m[0] : '';
		},
		$html
	);

	// Artikkelin oma "hero"-otsake toistaa otsikon: säilytetään vain ingressi.
	$html = preg_replace_callback(
		'#<header\b[^>]*class="[^"]*\bhero\b[^"]*"[^>]*>(.*?)</header>#is',
		static function ( $m ) {
			preg_match_all( '#<p\b[^>]*class="[^"]*\b(lede|lead|intro)\b[^"]*"[^>]*>.*?</p>#is', $m[1], $p );
			return $p[0] ? '<div class="legacy-lede">' . implode( '', $p[0] ) . '</div>' : '';
		},
		$html
	);

	// Sivulla on vain yksi h1 (artikkelin otsikko).
	$html = preg_replace( '#<h1(\s|>)#i', '<h2$1', $html );
	$html = preg_replace( '#</h1>#i', '</h2>', $html );

	// Inline-tyylit pois, jotta teeman tyylit pätevät.
	$html = preg_replace( '#\sstyle=("[^"]*"|\'[^\']*\')#i', '', $html );

	return $html;
}

/**
 * Kohdistetaan vain "Oma HTML"- ja klassisen editorin lohkoihin etusivulla.
 *
 * @param string $content Lohkon renderöity sisältö.
 * @param array  $block   Lohkon tiedot.
 * @return string
 */
function tilihub_filter_legacy_blocks( $content, $block ) {
	if ( is_admin() || wp_is_json_request() ) {
		return $content;
	}
	// Vain artikkelien ja sivujen omassa sisällössä, ei teeman sivupohjissa.
	if ( empty( $GLOBALS['tilihub_in_content'] ) ) {
		return $content;
	}
	if ( ! apply_filters( 'tilihub_clean_legacy_content', true ) ) {
		return $content;
	}
	$name = $block['blockName'] ?? null;
	if ( 'core/html' !== $name && null !== $name ) {
		return $content;
	}
	return tilihub_clean_legacy_html( $content );
}
add_filter( 'render_block', 'tilihub_filter_legacy_blocks', 10, 2 );

/**
 * Artikkelilistojen tiivistelmä: jos käsin kirjoitettua otetta ei ole, käytetään
 * SEO-kuvausta tai siistittyä tekstiä (Oma HTML -lohkoista ei muuten synny otetta).
 *
 * @param string  $excerpt Ote.
 * @param WP_Post $post    Artikkeli.
 * @return string
 */
function tilihub_excerpt_fallback( $excerpt, $post = null ) {
	$post = get_post( $post );
	// Käsin kirjoitettu ote kelpaa sellaisenaan; automaattinen ote sisältäisi upotettua CSS:ää.
	if ( ! $post || has_excerpt( $post ) ) {
		return $excerpt;
	}
	$seo = get_post_meta( $post->ID, '_aioseo_description', true );
	if ( $seo && false === strpos( $seo, '#' ) ) {
		return wp_strip_all_tags( $seo );
	}
	$text = tilihub_clean_legacy_html( strip_shortcodes( $post->post_content ) );
	// Otteeseen ei kuulu otsakkeiden kategoria- ja lukuaikarivejä eikä sisällysluetteloa.
	$text = preg_replace( '#<(span|div|p|nav|aside)\b[^>]*class="[^"]*\b(meta|eyebrow|crumbs?|breadcrumbs?|toc|tag-row|badge)\b[^"]*"[^>]*>.*?</\1>#is', ' ', $text );
	$text = preg_replace( '#<(h[1-6])\b[^>]*>.*?</\1>#is', ' ', $text );
	$text = trim( preg_replace( '/\s+/u', ' ', wp_strip_all_tags( $text ) ) );
	return wp_trim_words( $text, 28, '…' );
}
add_filter( 'get_the_excerpt', 'tilihub_excerpt_fallback', 20, 2 );

// Merkitään, milloin ollaan the_content-suodattimen sisällä (artikkelin oma sisältö).
add_filter(
	'the_content',
	static function ( $content ) {
		$GLOBALS['tilihub_in_content'] = ( $GLOBALS['tilihub_in_content'] ?? 0 ) + 1;
		return $content;
	},
	1
);
add_filter(
	'the_content',
	static function ( $content ) {
		$GLOBALS['tilihub_in_content'] = max( 0, ( $GLOBALS['tilihub_in_content'] ?? 1 ) - 1 );
		return $content;
	},
	PHP_INT_MAX
);
