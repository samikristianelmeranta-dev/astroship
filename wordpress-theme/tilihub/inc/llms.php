<?php
/**
 * /llms.txt – tiivis kuvaus sivustosta tekoälyhauille (ChatGPT, Claude, Perplexity, Gemini).
 * Muodostetaan aina ajantasaisesti: yrityksen tiedot, hinnat, palvelusivut ja kaikki artikkelit.
 * Ks. https://llmstxt.org/
 *
 * Pois päältä: add_filter( 'tilihub_llms_txt', '__return_false' );
 *
 * @package TiliHub
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * llms.txt:n sisältö (Markdown).
 *
 * @return string
 */
function tilihub_llms_txt() {
	$b     = tilihub_business();
	$lines = array();

	$lines[] = '# ' . $b['name'];
	$lines[] = '';
	$lines[] = '> ' . $b['description'];
	$lines[] = '';
	$lines[] = sprintf( '- Sijainti: %s, %s. Palvelemme Turun seutua ja koko Suomea digitaalisesti.', $b['locality'], $b['region'] );
	$lines[] = sprintf( '- Perustettu: %s. Perustaja ja pääkirjanpitäjä: %s.', $b['founded'], $b['founder'] );
	$lines[] = sprintf( '- Jäsenyys: %s.', $b['member_of']['name'] );
	$lines[] = sprintf( '- Ohjelmistot: %s.', implode( ', ', $b['software'] ) );
	$lines[] = sprintf( '- Yhteystiedot: puhelin %s, sähköposti %s, tarjouspyyntö %s', $b['phone_human'], $b['email'], home_url( '/yhteystiedot/' ) );
	$lines[] = '- Palvelukielet: suomi ja englanti.';
	$lines[] = '';
	$lines[] = '## Hinnat (1.7.2026 alkaen, alv 0 %, arvonlisävero 25,5 % lisätään)';
	$lines[] = '';
	$lines[] = sprintf( '- Kirjanpito alkaen %d € + alv kuukaudessa.', $b['min_monthly'] );
	foreach ( $b['prices'] as $p ) {
		$lines[] = sprintf( '- %s: %d €/h. %s', $p['name'], $p['price'], $p['note'] );
	}
	$lines[] = '- Ohjelmistomaksut eivät sisälly palveluhintoihin. Kiinteähintaisia paketteja ei ole.';
	$lines[] = '';
	$lines[] = '## Palvelut';
	$lines[] = '';
	$pages = array(
		'/kirjanpitopalvelut/' => 'Kirjanpito: juokseva kirjanpito, ALV-ilmoitukset, raportointi ja neuvonta',
		'/palkanlaskenta/'     => 'Palkanlaskenta: palkat, tulorekisteri, TES-osaaminen',
		'/tilinpaatos/'        => 'Tilinpäätös ja veroilmoitus eri yhtiömuodoille',
		'/kirjanpidon-hinnat/' => 'Hinnasto',
		'/tietoa-meista/'      => 'Tietoa meistä: tausta, tiimi ja arvot',
		'/yhteystiedot/'       => 'Yhteystiedot ja tarjouspyyntö',
	);
	foreach ( $pages as $path => $label ) {
		$lines[] = sprintf( '- [%s](%s)', $label, home_url( $path ) );
	}
	$lines[] = '';
	$lines[] = '## Artikkelit';
	$lines[] = '';
	$posts = get_posts(
		array(
			'post_type'      => 'post',
			'post_status'    => 'publish',
			'posts_per_page' => 300,
			'orderby'        => 'date',
			'order'          => 'DESC',
		)
	);
	foreach ( $posts as $post ) {
		$excerpt = wp_strip_all_tags( get_the_excerpt( $post ) );
		$excerpt = trim( preg_replace( '/\s+/u', ' ', $excerpt ) );
		$lines[] = sprintf(
			'- [%s](%s)%s',
			wp_strip_all_tags( html_entity_decode( get_the_title( $post ), ENT_QUOTES, 'UTF-8' ) ),
			get_permalink( $post ),
			$excerpt ? ': ' . wp_trim_words( html_entity_decode( $excerpt, ENT_QUOTES, 'UTF-8' ), 30, '…' ) : ''
		);
	}
	$lines[] = '';
	$lines[] = '## Lisätiedot';
	$lines[] = '';
	$lines[] = sprintf( '- [Sivukartta](%s)', home_url( '/sitemap.xml' ) );

	return implode( "\n", $lines ) . "\n";
}

add_action(
	'init',
	static function () {
		$path = (string) wp_parse_url( wp_unslash( $_SERVER['REQUEST_URI'] ?? '' ), PHP_URL_PATH ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
		if ( '/llms.txt' !== $path || false === apply_filters( 'tilihub_llms_txt', true ) ) {
			return;
		}
		header( 'Content-Type: text/plain; charset=utf-8' );
		header( 'X-Robots-Tag: noindex' );
		echo tilihub_llms_txt(); // phpcs:ignore WordPress.Security.EscapeOutput
		exit;
	},
	1
);
