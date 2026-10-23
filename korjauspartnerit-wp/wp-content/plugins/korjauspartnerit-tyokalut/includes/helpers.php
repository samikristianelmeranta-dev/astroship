<?php
/**
 * Yleiset apufunktiot.
 */

defined( 'ABSPATH' ) || exit;

/**
 * Sivuston yleiset asetukset (Asetukset → Korjauspartnerit).
 */
function kp_settings_defaults() {
	return array(
		'yritys'            => 'Korjauspartnerit Oy',
		'ytunnus'           => '2869492-1',
		'puhelin'           => '029 309 9000',
		'sahkoposti'        => 'toimisto@korjauspartnerit.fi',
		'lomake_vastaanottaja' => 'toimisto@korjauspartnerit.fi',
		'verkkolaskuosoite' => '003728694921',
		'operaattori'       => '003721291126 (Maventa)',
		'laskut_sahkoposti' => '28694921@scan.netvisor.fi',
		'linkedin'          => 'https://www.linkedin.com/company/korjauspartnerit-oy/',
		'facebook'          => 'https://www.facebook.com/korjauspartnerit/',
		'instagram'         => 'https://www.instagram.com/korjauspartnerit/',
		'youtube'           => 'https://www.youtube.com/channel/UCGrVI7_xAsOBgGCbRi1cfvw/videos',
		'toimipisteet'      => "Helsingin toimisto|Elimäenkatu 5, 00510 Helsinki|toimisto@korjauspartnerit.fi|029 309 9000|Aluejohtaja Samuel Enkvist\nTurun toimisto|Lemminkäisenkatu 50, 20520 Turku|turku@korjauspartnerit.fi|029 309 9000|Aluejohtaja Taru Säteri",
	);
}

function kp_setting( $key ) {
	$opts     = get_option( 'kp_asetukset', array() );
	$defaults = kp_settings_defaults();
	if ( isset( $opts[ $key ] ) && '' !== $opts[ $key ] ) {
		return $opts[ $key ];
	}
	return isset( $defaults[ $key ] ) ? $defaults[ $key ] : '';
}

/**
 * Toimipisteet listana: [ [nimi, osoite, email, puhelin, lisatieto], ... ].
 */
function kp_offices() {
	$rows = array_filter( array_map( 'trim', explode( "\n", (string) kp_setting( 'toimipisteet' ) ) ) );
	$out  = array();
	foreach ( $rows as $row ) {
		$p     = array_map( 'trim', explode( '|', $row ) );
		$out[] = array(
			'nimi'     => $p[0] ?? '',
			'osoite'   => $p[1] ?? '',
			'email'    => $p[2] ?? '',
			'puhelin'  => $p[3] ?? '',
			'lisatieto' => $p[4] ?? '',
		);
	}
	return $out;
}

/**
 * Puhelinnumero tel:-linkkiä varten (+358-muoto).
 */
function kp_tel_href( $phone ) {
	$n = preg_replace( '/[^0-9+]/', '', (string) $phone );
	if ( '' === $n ) {
		return '';
	}
	if ( 0 === strpos( $n, '0' ) ) {
		$n = '+358' . substr( $n, 1 );
	}
	return 'tel:' . $n;
}

/**
 * Henkilön tiedot yhdessä taulukossa.
 */
function kp_person( $post ) {
	$post = get_post( $post );
	if ( ! $post ) {
		return null;
	}
	$terms = function ( $tax ) use ( $post ) {
		$t = get_the_terms( $post, $tax );
		return ( $t && ! is_wp_error( $t ) ) ? $t : array();
	};
	return array(
		'id'          => $post->ID,
		'nimi'        => get_the_title( $post ),
		'url'         => get_permalink( $post ),
		'titteli'     => (string) get_post_meta( $post->ID, 'tehtavanimike', true ),
		'puhelin'     => (string) get_post_meta( $post->ID, 'puhelinnumero', true ),
		'sahkoposti'  => (string) get_post_meta( $post->ID, 'sahkoposti', true ),
		'linkedin'    => (string) get_post_meta( $post->ID, 'kp_linkedin', true ),
		'johto'       => (bool) get_post_meta( $post->ID, 'kp_johto', true ),
		'hakusanat'   => $terms( 'hakusanat' ),
		'toimipisteet' => $terms( 'toimipiste' ),
		'kuva_id'     => kp_thumb_id( $post ),
	);
}

/**
 * Lataa template-osan. Teema voi ohittaa tiedoston kansiossa
 * /korjauspartnerit/{nimi}.php, muuten käytetään lisäosan omaa.
 */
function kp_get_template( $name, $args = array() ) {
	$file = locate_template( 'korjauspartnerit/' . $name . '.php' );
	if ( ! $file ) {
		$file = KP_TYOKALUT_DIR . 'templates/' . $name . '.php';
	}
	if ( ! file_exists( $file ) ) {
		return '';
	}
	ob_start();
	// phpcs:ignore WordPress.PHP.DontExtract.extract_extract
	extract( $args, EXTR_SKIP );
	include $file;
	return ob_get_clean();
}

/**
 * Liikemerkki (KP-kolmio) SVG:nä – käytetään kuvattomien henkilöiden ja korttien paikkamerkkinä.
 */
function kp_mark_svg( $class = '' ) {
	$file = KP_TYOKALUT_DIR . 'assets/liikemerkki.svg';
	if ( ! file_exists( $file ) ) {
		return '';
	}
	$svg = file_get_contents( $file ); // phpcs:ignore WordPress.WP.AlternativeFunctions
	return str_replace( '<svg ', '<svg class="' . esc_attr( $class ) . '" aria-hidden="true" ', $svg );
}

/**
 * Artikkelikuvan ID vain, jos kuvatiedosto on oikeasti olemassa
 * (tuonnissa epäonnistuneet kuvat eivät jätä tyhjiä kuvapaikkoja).
 */
function kp_thumb_id( $post = null ) {
	$id = get_post_thumbnail_id( $post );
	if ( ! $id || 'attachment' !== get_post_type( $id ) || ! wp_get_attachment_image_src( $id ) ) {
		return 0;
	}
	return (int) $id;
}

/**
 * Sähköpostiosoite, joka rivittyy siististi @-merkin kohdalta.
 */
function kp_email_html( $email ) {
	return str_replace( '@', '<wbr>@', esc_html( antispambot( $email ) ) );
}
