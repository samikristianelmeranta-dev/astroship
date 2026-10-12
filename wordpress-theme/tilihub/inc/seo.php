<?php
/**
 * Hakukone- ja tekoälyasetukset, jotka kuuluvat teemaan:
 * - ohuet arkistot (tagit, "Uncategorized") pois hakutuloksista (noindex, follow)
 * - englanninkielisille artikkeleille oikea kielimerkintä (lang="en")
 * - robots.txt: tekoälyhakujen indeksoijat sallitaan ja llms.txt + sivukartta mainitaan
 *
 * @package TiliHub
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Onko nykyinen sivu ohut arkisto, jota ei kannata näyttää hakutuloksissa.
 *
 * @return bool
 */
function tilihub_is_thin_archive() {
	return is_tag() || is_category( 'uncategorized' ) || is_search();
}

// WordPressin oma robots-meta.
add_filter(
	'wp_robots',
	static function ( $robots ) {
		if ( tilihub_is_thin_archive() ) {
			$robots['noindex'] = true;
			$robots['follow']  = true;
			unset( $robots['index'] );
		}
		return $robots;
	}
);

// AIOSEO korvaa WordPressin robots-metan omallaan, joten sama sääntö myös sille.
add_filter(
	'aioseo_robots_meta',
	static function ( $attributes ) {
		if ( tilihub_is_thin_archive() && is_array( $attributes ) ) {
			$attributes['noindex'] = 'noindex';
			$attributes['follow']  = 'follow';
			unset( $attributes['index'] );
		}
		return $attributes;
	}
);

/**
 * Onko artikkeli englanninkielinen (kategoria "Accounting Finland" tai englanninkielinen otsikko).
 *
 * @return bool
 */
function tilihub_is_english() {
	if ( ! is_singular( 'post' ) ) {
		return false;
	}
	$post = get_queried_object();
	return has_category( 'accounting-finland', $post ) || (bool) preg_match( '/^(Accounting|Bookkeeping)\b/i', $post->post_title );
}

add_filter(
	'language_attributes',
	static function ( $output ) {
		return tilihub_is_english() ? preg_replace( '/lang="[^"]*"/', 'lang="en"', $output ) : $output;
	}
);

// robots.txt (WordPressin virtuaalinen; AIOSEO:n omat säännöt säilyvät).
add_filter(
	'robots_txt',
	static function ( $output, $public ) {
		if ( ! $public ) {
			return $output;
		}
		$bots = array( 'GPTBot', 'OAI-SearchBot', 'ChatGPT-User', 'ClaudeBot', 'Claude-SearchBot', 'PerplexityBot', 'Google-Extended', 'Applebot-Extended', 'CCBot' );
		$add  = "\n# Tekoälyhaut saavat lukea sivuston (TiliHub-teema)\n";
		foreach ( $bots as $bot ) {
			if ( false === stripos( $output, 'User-agent: ' . $bot ) ) {
				$add .= "User-agent: {$bot}\nAllow: /\nDisallow: /wp-admin/\n\n";
			}
		}
		if ( false === stripos( $output, 'Sitemap:' ) ) {
			$add .= 'Sitemap: ' . home_url( '/sitemap.xml' ) . "\n";
		}
		$add .= '# Tiivis kuvaus tekoälyille: ' . home_url( '/llms.txt' ) . "\n";
		return rtrim( $output ) . "\n" . $add;
	},
	999,
	2
);
