<?php
/**
 * Apufunktiot.
 *
 * @package sami-elmeranta
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Teeman asetusten oletusarvot (muokattavissa: Ulkoasu → Mukauta).
 */
function se_defaults() {
	return array(
		'person_name'    => 'Sami Elmeranta',
		'tagline'        => 'Tolkun poliitikko',
		'description'    => 'Sami Elmeranta – Liike Nytin kaarinalainen kaupunginvaltuutettu, kaupunginhallituksen ja Varsinais-Suomen maakuntavaltuuston jäsen. Myynnin johtaja, yrittäjä ja kolmen lapsen isä Piikkiöstä.',
		'job_title'      => 'Kaupunginvaltuutettu, Kaarina',
		'party_name'     => 'Liike Nyt',
		'party_url'      => 'https://liikenyt.fi',
		'locality'       => 'Kaarina',
		'district'       => 'Piikkiö',
		'region'         => 'Varsinais-Suomi',
		'phone'          => '+358503460444',
		'phone_display'  => '050 346 0444',
		'email'          => '',
		'form_recipient' => '',
		'newsletter_url' => 'http://eepurl.com/jrWeaI',
		'whatsapp_url'   => 'https://wa.me/358503460444?text=Hei%20Sami!',
		'facebook'       => 'https://www.facebook.com/Elmeranta/',
		'instagram'      => 'https://www.instagram.com/s.elmeranta/',
		'tiktok'         => 'https://www.tiktok.com/@samielmeranta',
		'linkedin'       => 'https://www.linkedin.com/in/sami-kristian-elmeranta-05aa27170/',
		'uusisuomi'      => 'https://puheenvuoro.uusisuomi.fi/author/sami-elmeranta/',
		'portrait'       => 0,
		'portrait_alt'   => 0,
		'hero_line1'     => 'Hei, olen',
		'hero_line2'     => 'Sami Elmeranta.',
		'hero_line3'     => 'Tolkun poliitikko.',
		'hero_lead'      => 'Isä, myynnin ja markkinoinnin kokenut johtaja, yrittäjä, poliitikko ja seutuvaikuttaja. Haluan rakentaa yhteiskuntaa, jossa tekeminen palkitsee, yhteisö tukee ja jokainen voi vaikuttaa.',
	);
}

/**
 * Hae teeman asetus.
 *
 * @param string $key Asetuksen nimi.
 * @return mixed
 */
function se_opt( $key ) {
	$defaults = se_defaults();
	return get_theme_mod( 'se_' . $key, isset( $defaults[ $key ] ) ? $defaults[ $key ] : '' );
}

/**
 * Sivun sisältödata (inc/content.php).
 *
 * @param string $key Osion nimi.
 * @return array
 */
function se_content( $key ) {
	static $data = null;
	if ( null === $data ) {
		$data = require SE_DIR . '/inc/content.php';
	}
	return isset( $data[ $key ] ) ? $data[ $key ] : array();
}

/**
 * Inline-SVG-ikoni. Ikonit ovat staattisia, teeman omia merkkijonoja.
 *
 * @param string $name  Ikonin nimi, esim. "lucide:arrow-right".
 * @param string $class CSS-luokat.
 * @return string
 */
function se_icon( $name, $class = 'size-5' ) {
	static $icons = null;
	if ( null === $icons ) {
		$icons = require SE_DIR . '/inc/icons.php';
	}
	if ( ! isset( $icons[ $name ] ) ) {
		return '';
	}
	list( $w, $h, $body ) = $icons[ $name ];
	return sprintf(
		'<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 %1$d %2$d" class="%3$s" aria-hidden="true" focusable="false">%4$s</svg>',
		(int) $w,
		(int) $h,
		esc_attr( $class ),
		$body // Staattinen, luotettu SVG-sisältö.
	);
}

/**
 * Tulosta ikoni.
 *
 * @param string $name  Ikonin nimi.
 * @param string $class CSS-luokat.
 */
function se_the_icon( $name, $class = 'size-5' ) {
	echo se_icon( $name, $class ); // phpcs:ignore WordPress.Security.EscapeOutput -- staattinen SVG.
}

/**
 * Teeman kuvan URL.
 *
 * @param string $file Tiedostonimi kansiossa assets/img.
 * @return string
 */
function se_asset_img( $file ) {
	return get_theme_file_uri( 'assets/img/' . $file );
}

/**
 * Kuvan URL: Customizerissa valittu mediakuva tai teeman oletuskuva.
 *
 * @param string $key     Asetus (portrait / portrait_alt).
 * @param string $default Oletustiedosto.
 * @param string $size    Kuvakoko.
 * @return string
 */
function se_image_url( $key, $default, $size = 'large' ) {
	$id = absint( se_opt( $key ) );
	if ( $id ) {
		$src = wp_get_attachment_image_url( $id, $size );
		if ( $src ) {
			return $src;
		}
	}
	return se_asset_img( $default );
}

/**
 * Sosiaalisen median linkit.
 *
 * @return array
 */
function se_socials() {
	$list = array(
		array( 'Facebook', 'simple-icons:facebook', se_opt( 'facebook' ) ),
		array( 'Instagram', 'simple-icons:instagram', se_opt( 'instagram' ) ),
		array( 'TikTok', 'simple-icons:tiktok', se_opt( 'tiktok' ) ),
		array( 'LinkedIn', 'simple-icons:linkedin', se_opt( 'linkedin' ) ),
	);
	return array_values( array_filter( $list, function ( $s ) {
		return ! empty( $s[2] );
	} ) );
}

/**
 * Mielipidekirjoitukset muualla.
 *
 * @return array
 */
function se_writing() {
	return array_filter( array(
		array( 'Uusi Suomi -blogit', se_opt( 'uusisuomi' ) ),
		array( 'Turun Sanomat', 'https://www.ts.fi/lukijoilta/6432868' ),
		array( 'Helsingin Sanomat', 'https://www.hs.fi/mielipide/art-2000011564975.html' ),
	), function ( $w ) {
		return ! empty( $w[1] );
	} );
}

/**
 * Tulosta somekuvakkeet.
 *
 * @param bool   $dark  Tumma tausta.
 * @param string $class Lisäluokat.
 */
function se_the_socials( $dark = false, $class = '' ) {
	echo '<ul class="flex flex-wrap gap-3 ' . esc_attr( $class ) . '">';
	foreach ( se_socials() as $s ) {
		printf(
			'<li><a href="%1$s" target="_blank" rel="noopener noreferrer me" class="grid size-11 place-items-center rounded-full transition duration-300 hover:-translate-y-1 hover:bg-brand hover:text-white %2$s">%3$s<span class="sr-only">%4$s (avautuu uuteen välilehteen)</span></a></li>',
			esc_url( $s[2] ),
			$dark ? 'bg-white/10 text-white' : 'bg-mist text-ink ring-1 ring-line',
			se_icon( $s[1], 'size-5' ), // phpcs:ignore
			esc_html( $s[0] )
		);
	}
	echo '</ul>';
}

/**
 * Sivun URL slugin perusteella (fallback-valikkoa varten).
 *
 * @param string $slug     Sivun slug.
 * @param string $fallback Varaosoite.
 * @return string
 */
function se_page_url( $slug, $fallback = '' ) {
	$page = get_page_by_path( $slug );
	if ( $page ) {
		return get_permalink( $page );
	}
	return $fallback ? $fallback : home_url( '/' . $slug . '/' );
}

/**
 * Blogin URL.
 *
 * @return string
 */
function se_blog_url() {
	$id = (int) get_option( 'page_for_posts' );
	return $id ? get_permalink( $id ) : home_url( '/' );
}

/**
 * Yhteyssivun URL.
 *
 * @return string
 */
function se_contact_url() {
	$pages = get_pages( array(
		'meta_key'   => '_wp_page_template', // phpcs:ignore WordPress.DB.SlowDBQuery
		'meta_value' => 'page-templates/yhteys.php', // phpcs:ignore WordPress.DB.SlowDBQuery
		'number'     => 1,
	) );
	if ( $pages ) {
		return get_permalink( $pages[0] );
	}
	return se_page_url( 'contact' );
}

/**
 * Arvioitu lukuaika minuutteina.
 *
 * @param int|null $post_id Artikkeli.
 * @return int
 */
function se_reading_time( $post_id = null ) {
	$words = str_word_count( wp_strip_all_tags( get_post_field( 'post_content', $post_id ) ) );
	return max( 1, (int) round( $words / 180 ) );
}

/**
 * Nimikirjaimet.
 *
 * @param string $name Nimi.
 * @return string
 */
function se_initials( $name ) {
	$parts = preg_split( '/\s+/', trim( $name ) );
	$out   = '';
	foreach ( array_slice( $parts, 0, 2 ) as $p ) {
		$out .= mb_substr( $p, 0, 1 );
	}
	return $out;
}
