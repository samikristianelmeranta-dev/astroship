<?php
/**
 * Plugin Name:       Nordic Kysy meiltä – UKK-chat
 * Description:       Kevyt ja ilmainen chat-ikkuna, joka vastaa yleisimpiin kysymyksiin sivuston omista UKK-vastauksista ja näyttää linkin lähdesivulle. Jos vastausta ei löydy, kysymyksen voi lähettää sähköpostiin. Ei ulkoisia palveluita eikä käyttökuluja.
 * Version:           1.1.0
 * Requires at least: 6.5
 * Requires PHP:      7.4
 * Author:            Nordic Ikkunat & Ovet Oy
 * Text Domain:       nordic-faq
 * License:           GPLv2 or later
 */

if ( ! defined( 'ABSPATH' ) ) exit;

define( 'NORDIC_FAQ_VERSION', '1.1.0' );
define( 'NORDIC_FAQ_FILE', __FILE__ );

/**
 * Asetukset (suodattimilla, ei erillistä asetussivua):
 *   nordic_faq_email          vastaanottaja (oletus info@ikkunakauppias.fi)
 *   nordic_faq_quick          aloituskysymykset
 *   nordic_faq_greeting       tervehdys
 */
function nordic_faq_email() {
	return apply_filters( 'nordic_faq_email', 'info@ikkunakauppias.fi' );
}

/* -------------------------------------------------------------------------
 * Aineisto: sivujen UKK-kysymykset ja sivuhakemisto
 * ---------------------------------------------------------------------- */

/**
 * Kokoaa kysymykset ja vastaukset julkaistuilta sivuilta.
 * Lähteet: sivun FAQPage-skeema (_nordic_schema_json) ja varalla sivun
 * sisällön .faq-item-lohkot. Välimuistissa, tyhjennetään sivun tallennuksessa.
 */
function nordic_faq_data() {
	$data = get_transient( 'nordic_faq_data' );
	if ( is_array( $data ) ) {
		return $data;
	}

	$skip  = function_exists( 'nordic_noindex_slugs' ) ? nordic_noindex_slugs() : array( 'etusivu-ilman-navigaatiota' );
	$faq   = array();
	$pages = array();
	$seen  = array();

	foreach ( get_pages( array( 'post_status' => 'publish', 'sort_column' => 'menu_order,post_title' ) ) as $page ) {
		if ( in_array( $page->post_name, $skip, true ) || post_password_required( $page ) ) {
			continue;
		}
		$url   = get_permalink( $page );
		$title = function_exists( 'nordic_short_title' ) ? nordic_short_title( $page->ID ) : get_the_title( $page );
		$desc  = (string) get_post_meta( $page->ID, '_nordic_meta_description', true );

		$pages[] = array( 't' => $title, 'u' => $url, 'd' => $desc );

		// Paikkakuntasivujen yleiset kysymykset toistuvat; niissä yleinen sivu on parempi lähde.
		$is_city = (bool) preg_match( '/^(ikkunat-ikkunaremontti-|ulko-ovet-oviremontti-)/', $page->post_name );

		foreach ( nordic_faq_pairs_for_page( $page ) as $pair ) {
			$key = mb_strtolower( trim( $pair['q'] ) );
			if ( isset( $seen[ $key ] ) ) {
				$prev = $seen[ $key ];
				if ( $faq[ $prev ]['city'] && ! $is_city ) {
					$faq[ $prev ] = array( 'q' => $pair['q'], 'a' => $pair['a'], 'u' => $url, 't' => $title, 'city' => false );
				}
				continue;
			}
			$seen[ $key ] = count( $faq );
			$faq[]        = array( 'q' => $pair['q'], 'a' => $pair['a'], 'u' => $url, 't' => $title, 'city' => $is_city );
		}
	}

	foreach ( $faq as &$item ) {
		unset( $item['city'] );
	}
	unset( $item );

	$data = array( 'faq' => $faq, 'pages' => $pages, 'v' => md5( wp_json_encode( $faq ) ) );
	set_transient( 'nordic_faq_data', $data, 12 * HOUR_IN_SECONDS );
	return $data;
}

add_action( 'save_post_page', function () {
	delete_transient( 'nordic_faq_data' );
} );

function nordic_faq_pairs_for_page( $page ) {
	$pairs  = array();
	$schema = (string) get_post_meta( $page->ID, '_nordic_schema_json', true );
	if ( $schema && preg_match_all( '#<script[^>]*>(.*?)</script>#is', $schema, $m ) ) {
		foreach ( $m[1] as $json ) {
			$d = json_decode( $json, true );
			if ( ! is_array( $d ) || empty( $d['mainEntity'] ) ) {
				continue;
			}
			foreach ( (array) $d['mainEntity'] as $e ) {
				$q = trim( (string) ( $e['name'] ?? '' ) );
				$a = trim( (string) ( $e['acceptedAnswer']['text'] ?? '' ) );
				if ( $q && $a ) {
					$pairs[] = array( 'q' => wp_strip_all_tags( $q ), 'a' => wp_strip_all_tags( $a ) );
				}
			}
		}
	}
	if ( $pairs ) {
		return $pairs;
	}
	// Varalla: sivun omat UKK-lohkot (<div class="faq-item"><h3>…</h3><p>…</p></div>).
	if ( preg_match_all( '#<div class="faq-item"[^>]*>.*?<h3>(.*?)</h3>.*?<p>(.*?)</p>#is', (string) $page->post_content, $m, PREG_SET_ORDER ) ) {
		foreach ( $m as $row ) {
			$q = trim( html_entity_decode( wp_strip_all_tags( $row[1] ), ENT_QUOTES, 'UTF-8' ) );
			$a = trim( html_entity_decode( wp_strip_all_tags( $row[2] ), ENT_QUOTES, 'UTF-8' ) );
			if ( $q && $a ) {
				$pairs[] = array( 'q' => $q, 'a' => $a );
			}
		}
	}
	return $pairs;
}

/* -------------------------------------------------------------------------
 * REST: aineisto ja kysymyksen lähetys
 * ---------------------------------------------------------------------- */

add_action( 'rest_api_init', function () {
	register_rest_route( 'nordic-faq/v1', '/aineisto', array(
		'methods'             => 'GET',
		'permission_callback' => '__return_true',
		'callback'            => function () {
			$res = new WP_REST_Response( nordic_faq_data(), 200 );
			$res->header( 'Cache-Control', 'public, max-age=3600' );
			return $res;
		},
	) );
	register_rest_route( 'nordic-faq/v1', '/kysymys', array(
		'methods'             => 'POST',
		'permission_callback' => '__return_true',
		'callback'            => 'nordic_faq_send_question',
	) );
} );

function nordic_faq_send_question( WP_REST_Request $r ) {
	$fail = function ( $msg, $code = 400 ) {
		return new WP_REST_Response( array( 'ok' => false, 'virhe' => $msg ), $code );
	};

	// Vain omalta sivustolta, piilokenttä roboteille ja IP-kohtainen raja.
	$origin = $r->get_header( 'origin' );
	if ( $origin && wp_parse_url( $origin, PHP_URL_HOST ) !== wp_parse_url( home_url(), PHP_URL_HOST ) ) {
		return $fail( 'Pyyntö estetty.', 403 );
	}
	if ( '' !== (string) $r->get_param( 'verkkosivu' ) ) {
		return new WP_REST_Response( array( 'ok' => true ), 200 );
	}
	$ip  = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
	$key = 'nordic_faq_rl_' . md5( $ip . wp_salt( 'nonce' ) );
	if ( (int) get_transient( $key ) >= 5 ) {
		return $fail( 'Olet lähettänyt jo useamman kysymyksen. Voit lähettää lisää sähköpostilla: ' . nordic_faq_email(), 429 );
	}

	$name  = sanitize_text_field( (string) $r->get_param( 'nimi' ) );
	$email = sanitize_email( (string) $r->get_param( 'sahkoposti' ) );
	$text  = sanitize_textarea_field( (string) $r->get_param( 'kysymys' ) );
	$page  = esc_url_raw( (string) $r->get_param( 'sivu' ) );
	if ( ! is_email( $email ) ) {
		return $fail( 'Tarkista sähköpostiosoite.' );
	}
	if ( mb_strlen( $text ) < 3 || mb_strlen( $text ) > 3000 ) {
		return $fail( 'Kirjoita kysymys (enintään 3000 merkkiä).' );
	}

	$body  = "Kysymys sivuston Kysy meiltä -ikkunasta\n\n";
	$body .= ( $name ? "Nimi: {$name}\n" : '' ) . "Sähköposti: {$email}\n";
	$body .= $page ? "Sivu: {$page}\n" : '';
	$body .= "\n{$text}\n\n—\nVastaa tähän viestiin, niin vastaus menee suoraan kysyjälle.\n";
	$headers = array(
		'Content-Type: text/plain; charset=UTF-8',
		'Reply-To: ' . ( $name ? str_replace( array( "\r", "\n", '<', '>' ), '', $name ) . ' ' : '' ) . '<' . $email . '>',
	);
	$subject = '[Kysy meiltä] ' . ( $name ? $name . ': ' : '' ) . wp_trim_words( $text, 8, '…' );

	if ( ! wp_mail( nordic_faq_email(), $subject, $body, $headers ) ) {
		return $fail( 'Lähetys epäonnistui. Lähetä kysymys sähköpostilla: ' . nordic_faq_email(), 500 );
	}
	set_transient( $key, (int) get_transient( $key ) + 1, HOUR_IN_SECONDS );
	return new WP_REST_Response( array( 'ok' => true ), 200 );
}

/* -------------------------------------------------------------------------
 * Chat-ikkuna
 * ---------------------------------------------------------------------- */

add_action( 'wp_enqueue_scripts', function () {
	if ( is_admin() ) {
		return;
	}
	wp_enqueue_style( 'nordic-faq', plugins_url( 'assets/faq.css', NORDIC_FAQ_FILE ), array(), NORDIC_FAQ_VERSION );
	wp_register_script( 'nordic-faq-match', plugins_url( 'assets/faq-match.js', NORDIC_FAQ_FILE ), array(), NORDIC_FAQ_VERSION, array(
		'strategy'  => 'defer',
		'in_footer' => true,
	) );
	wp_enqueue_script( 'nordic-faq', plugins_url( 'assets/faq.js', NORDIC_FAQ_FILE ), array( 'nordic-faq-match' ), NORDIC_FAQ_VERSION, array(
		'strategy'  => 'defer',
		'in_footer' => true,
	) );
	$data = get_transient( 'nordic_faq_data' );
	wp_localize_script( 'nordic-faq', 'NordicFaq', array(
		'data'     => esc_url_raw( add_query_arg( 'v', is_array( $data ) ? $data['v'] : NORDIC_FAQ_VERSION, rest_url( 'nordic-faq/v1/aineisto' ) ) ),
		'send'     => esc_url_raw( rest_url( 'nordic-faq/v1/kysymys' ) ),
		'email'    => nordic_faq_email(),
		'contact'  => home_url( '/yhteystiedot/' ),
		'guides'   => nordic_faq_guides(),
		'greeting' => apply_filters( 'nordic_faq_greeting', 'Hei! Kysy meiltä ikkunoista, ovista tai remontista. Haen vastauksen usein kysytyistä kysymyksistä, ja jos en löydä sitä, voit lähettää kysymyksen meille.' ),
		'quick'    => apply_filters( 'nordic_faq_quick', array(
			'Mitä ikkunaremontti maksaa?',
			'Kuinka kauan ikkunaremontti kestää?',
			'Saako remontista kotitalousvähennystä?',
			'Voiko ikkunat vaihtaa talvella?',
			'Voiko ikkunat tilata ilman asennusta?',
			'Kolmi- vai nelilasinen ikkuna?',
		) ),
	) );
} );

/**
 * Ladattavat esitteet chattiin (Nordic CRM:n Oppaat).
 * Sivun osoitteen voi vaihtaa suodattimella nordic_faq_guides_url.
 */
function nordic_faq_guides() {
	$url = apply_filters( 'nordic_faq_guides_url', home_url( '/ladattavat-esitteet/' ) );
	if ( ! $url ) {
		return null;
	}
	$items = array();
	if ( post_type_exists( 'nordic_opas' ) ) {
		$posts = get_posts( array(
			'post_type'      => 'nordic_opas',
			'post_status'    => 'publish',
			'posts_per_page' => 12,
			'orderby'        => array( 'menu_order' => 'ASC', 'date' => 'DESC' ),
		) );
		foreach ( $posts as $p ) {
			if ( ! get_post_meta( $p->ID, '_nordic_opas_pdf', true ) ) {
				continue;
			}
			$items[] = array(
				't' => get_the_title( $p ),
				'u' => $url . '#nordic-opas-' . $p->ID,
			);
		}
	}
	return array( 'url' => $url, 'items' => $items );
}
