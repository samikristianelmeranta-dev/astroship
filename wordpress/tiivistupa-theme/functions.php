<?php
/**
 * TiivisTupa-teema: ikkunoiden ja ovien huoltoremontit.
 *
 * Sivujen sisältö tulee tiedostosta content/pages.json (tuotetaan generaattorilla
 * tiivistupa/build.py wp). Teema luo sivut WordPressiin aktivoinnin yhteydessä.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'TT_VERSION', '1.0.0' );

/* =========================================================================
 * Perustiedot
 * ========================================================================= */

function tt_services() {
	return array(
		'ikkunahuolto'            => 'Ikkunahuolto',
		'ovihuolto'               => 'Ovihuolto',
		'ikkunoiden-tiivistys'    => 'Ikkunoiden tiivistys',
		'ovien-tiivistys'         => 'Ovien tiivistys',
		'ikkunoiden-korjaus'      => 'Ikkunoiden korjaus',
		'ikkunapuitteiden-huolto' => 'Ikkunapuitteiden huolto',
	);
}

function tt_cities() {
	return array(
		'salo'     => 'Salo',
		'kaarina'  => 'Kaarina',
		'naantali' => 'Naantali',
		'raisio'   => 'Raisio',
		'parainen' => 'Parainen',
		'paimio'   => 'Paimio',
		'lieto'    => 'Lieto',
		'masku'    => 'Masku',
	);
}

/** Oletusarvot, joita voi muuttaa kohdasta Ulkoasu → Mukauta → TiivisTupa: yhteystiedot. */
function tt_defaults() {
	return array(
		'tt_phone'    => '045 7832 5070',
		'tt_email'    => 'info@ikkunakauppias.fi',
		'tt_legal'    => 'Nordic Ikkunat & Ovet Oy',
		'tt_ytunnus'  => '3653098-6',
		'tt_locality' => 'Masku',
		'tt_notify'   => '',
	);
}

function tt_opt( $key ) {
	$d = tt_defaults();
	$v = get_theme_mod( $key, isset( $d[ $key ] ) ? $d[ $key ] : '' );
	return is_string( $v ) ? trim( $v ) : '';
}

function tt_phone_intl() {
	$digits = preg_replace( '/\D+/', '', tt_opt( 'tt_phone' ) );
	if ( '' === $digits ) {
		return '';
	}
	return '+358' . ltrim( $digits, '0' );
}

/**
 * Sivun tyyppi slugista: array( kind, service, city ).
 * kind: front | service | city | hub | other
 */
function tt_page_info( $post = null ) {
	$post = get_post( $post );
	$info = array( 'kind' => 'other', 'service' => '', 'city' => '' );
	if ( ! $post || 'page' !== $post->post_type ) {
		return $info;
	}
	if ( (int) get_option( 'page_on_front' ) === (int) $post->ID ) {
		$info['kind'] = 'front';
		return $info;
	}
	$slug     = $post->post_name;
	$services = tt_services();
	if ( 'palvelualue' === $slug ) {
		$info['kind'] = 'hub';
		return $info;
	}
	if ( isset( $services[ $slug ] ) ) {
		$info['kind']    = 'service';
		$info['service'] = $slug;
		return $info;
	}
	foreach ( $services as $s => $name ) {
		foreach ( tt_cities() as $c => $city ) {
			if ( $slug === $s . '-' . $c ) {
				return array( 'kind' => 'city', 'service' => $s, 'city' => $c );
			}
		}
	}
	return $info;
}

/* =========================================================================
 * Teeman asetukset
 * ========================================================================= */

add_action( 'after_setup_theme', function () {
	add_theme_support( 'title-tag' );
	add_theme_support( 'html5', array( 'search-form', 'gallery', 'caption', 'style', 'script' ) );
	add_theme_support( 'responsive-embeds' );
} );

add_action( 'wp_enqueue_scripts', function () {
	wp_enqueue_style( 'tiivistupa', get_stylesheet_uri(), array(), TT_VERSION );
	// Sivut eivät käytä lohkoeditorin tyylejä.
	wp_dequeue_style( 'wp-block-library' );
	wp_dequeue_style( 'global-styles' );
	wp_dequeue_style( 'classic-theme-styles' );
}, 20 );

add_action( 'wp_head', function () {
	$base = get_template_directory_uri() . '/assets/';
	echo '<link rel="preload" href="' . esc_url( $base . 'fonts/source-sans-3.woff2' ) . '" as="font" type="font/woff2" crossorigin>' . "\n";
	echo '<link rel="preload" href="' . esc_url( $base . 'fonts/bricolage-grotesque.woff2' ) . '" as="font" type="font/woff2" crossorigin>' . "\n";
	if ( ! has_site_icon() ) {
		echo '<link rel="icon" href="' . esc_url( $base . 'favicon.svg' ) . '" type="image/svg+xml">' . "\n";
	}
}, 2 );

// Kevennetään <head>: emojit ja turhat linkit pois.
remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
remove_action( 'wp_print_styles', 'print_emoji_styles' );
remove_action( 'wp_head', 'wp_generator' );
remove_action( 'wp_head', 'rsd_link' );
remove_action( 'wp_head', 'wlwmanifest_link' );
remove_action( 'wp_head', 'wp_shortlink_wp_head' );

add_action( 'customize_register', function ( $wp_customize ) {
	$wp_customize->add_section( 'tt_contact', array(
		'title'       => 'TiivisTupa: yhteystiedot',
		'description' => 'Nämä tiedot näkyvät ylä- ja alapalkissa, tarjouslomakkeen vieressä ja hakukoneiden rakenteisessa tiedossa.',
		'priority'    => 30,
	) );
	$fields = array(
		'tt_phone'    => array( 'Puhelinnumero', 'text' ),
		'tt_email'    => array( 'Sähköposti (näkyy sivuilla)', 'email' ),
		'tt_notify'   => array( 'Tarjouspyyntöjen vastaanottaja (tyhjä = sama kuin yllä)', 'email' ),
		'tt_legal'    => array( 'Yrityksen virallinen nimi', 'text' ),
		'tt_ytunnus'  => array( 'Y-tunnus', 'text' ),
		'tt_locality' => array( 'Paikkakunta', 'text' ),
	);
	$d = tt_defaults();
	foreach ( $fields as $key => $f ) {
		$wp_customize->add_setting( $key, array(
			'default'           => $d[ $key ],
			'sanitize_callback' => 'email' === $f[1] ? 'sanitize_email' : 'sanitize_text_field',
		) );
		$wp_customize->add_control( $key, array( 'label' => $f[0], 'section' => 'tt_contact', 'type' => $f[1] ) );
	}
} );

/* =========================================================================
 * Sivujen luonti teeman sisällöstä
 * ========================================================================= */

function tt_content_pages() {
	$file = get_template_directory() . '/content/pages.json';
	if ( ! is_readable( $file ) ) {
		return array();
	}
	$pages = json_decode( file_get_contents( $file ), true ); // phpcs:ignore
	return is_array( $pages ) ? $pages : array();
}

/**
 * Luo sivut. $overwrite = false: vain puuttuvat sivut luodaan, olemassa oleviin ei kosketa.
 * $overwrite = true: myös olemassa olevien sivujen sisältö palautetaan teeman versioon.
 *
 * @return array( created, updated, skipped )
 */
function tt_import_pages( $overwrite = false ) {
	$res = array( 'created' => 0, 'updated' => 0, 'skipped' => 0 );
	// Sisältö on teeman omaa HTML:ää; estetään suodatus, joka poistaisi luokkia ja SVG-kuvan.
	kses_remove_filters();
	$front_id = 0;
	foreach ( tt_content_pages() as $p ) {
		if ( empty( $p['slug'] ) || empty( $p['content'] ) ) {
			continue;
		}
		$existing = get_page_by_path( $p['slug'], OBJECT, 'page' );
		$data     = array(
			'post_type'    => 'page',
			'post_status'  => 'publish',
			'post_name'    => $p['slug'],
			'post_title'   => $p['h1'],
			'post_excerpt' => $p['meta'],
			'post_content' => "<!-- wp:html -->\n" . $p['content'] . "\n<!-- /wp:html -->",
		);
		if ( $existing ) {
			if ( ! $overwrite ) {
				$res['skipped']++;
				$id = $existing->ID;
			} else {
				$data['ID'] = $existing->ID;
				$id         = wp_update_post( wp_slash( $data ) );
				$res['updated']++;
				update_post_meta( $id, '_tt_seo_title', $p['seo_title'] );
			}
		} else {
			$id = wp_insert_post( wp_slash( $data ) );
			if ( $id && ! is_wp_error( $id ) ) {
				$res['created']++;
				update_post_meta( $id, '_tt_seo_title', $p['seo_title'] );
			}
		}
		if ( ! empty( $p['front'] ) && $id && ! is_wp_error( $id ) ) {
			$front_id = (int) $id;
		}
	}
	kses_init_filters();

	// Uudessa asennuksessa etusivuksi asetetaan teeman etusivu ja osoitteet muotoon /sivun-nimi/.
	if ( $front_id && 'page' !== get_option( 'show_on_front' ) ) {
		update_option( 'show_on_front', 'page' );
		update_option( 'page_on_front', $front_id );
	}
	if ( '' === (string) get_option( 'permalink_structure' ) ) {
		global $wp_rewrite;
		$wp_rewrite->set_permalink_structure( '/%postname%/' );
		flush_rewrite_rules();
	}
	update_option( 'tt_content_version', TT_VERSION );
	return $res;
}

// Ensimmäinen aktivointi: luodaan puuttuvat sivut. Olemassa olevia ei muuteta.
add_action( 'after_switch_theme', function () {
	if ( current_user_can( 'edit_pages' ) ) {
		tt_import_pages( false );
	}
} );

add_action( 'admin_menu', function () {
	add_theme_page( 'TiivisTupa-sisältö', 'TiivisTupa-sisältö', 'manage_options', 'tt-content', 'tt_content_admin_page' );
} );

function tt_content_admin_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	$msg = '';
	if ( isset( $_POST['tt_action'] ) && check_admin_referer( 'tt_content' ) ) {
		$overwrite = 'overwrite' === $_POST['tt_action'] && ! empty( $_POST['tt_confirm'] );
		if ( 'overwrite' === $_POST['tt_action'] && ! $overwrite ) {
			$msg = 'Sivuja ei muutettu: rastita vahvistus, jos haluat korvata nykyisen sisällön.';
		} else {
			$r   = tt_import_pages( $overwrite );
			$msg = sprintf( 'Valmis. Luotu %d, päivitetty %d, ennallaan %d sivua.', $r['created'], $r['updated'], $r['skipped'] );
		}
	}
	$pages   = tt_content_pages();
	$missing = 0;
	foreach ( $pages as $p ) {
		if ( ! get_page_by_path( $p['slug'], OBJECT, 'page' ) ) {
			$missing++;
		}
	}
	echo '<div class="wrap"><h1>TiivisTupa-sisältö</h1>';
	if ( $msg ) {
		echo '<div class="notice notice-success"><p>' . esc_html( $msg ) . '</p></div>';
	}
	echo '<p>Teemassa on ' . count( $pages ) . ' sivua. WordPressistä puuttuu niistä ' . (int) $missing . '.</p>';
	echo '<form method="post">';
	wp_nonce_field( 'tt_content' );
	echo '<h2>Luo puuttuvat sivut</h2><p>Luo ne sivut, joita ei vielä ole. Olemassa oleviin sivuihin ei kosketa.</p>';
	echo '<p><button class="button button-primary" name="tt_action" value="create">Luo puuttuvat sivut</button></p>';
	echo '<h2>Palauta kaikki sivut teeman sisältöön</h2><p>Korvaa kaikkien teeman sivujen sisällön, otsikon ja otteen teeman versiolla. <strong>WordPressissä tehdyt muokkaukset näihin sivuihin katoavat.</strong></p>';
	echo '<p><label><input type="checkbox" name="tt_confirm" value="1"> Ymmärrän, että muokkaukseni korvataan</label></p>';
	echo '<p><button class="button" name="tt_action" value="overwrite">Palauta teeman sisältö</button></p>';
	echo '</form></div>';
}

/* =========================================================================
 * SEO: otsikko, kuvaus, Open Graph, JSON-LD
 * ========================================================================= */

function tt_seo_title( $post = null ) {
	$post = get_post( $post );
	if ( ! $post ) {
		return '';
	}
	$t = (string) get_post_meta( $post->ID, '_tt_seo_title', true );
	return '' !== $t ? $t : get_the_title( $post );
}

function tt_meta_description( $post = null ) {
	$post = get_post( $post );
	if ( ! $post ) {
		return '';
	}
	$d = trim( wp_strip_all_tags( $post->post_excerpt ) );
	if ( '' === $d ) {
		$d = wp_trim_words( wp_strip_all_tags( strip_shortcodes( $post->post_content ) ), 24, '…' );
	}
	return $d;
}

add_filter( 'pre_get_document_title', function ( $title ) {
	if ( is_singular() ) {
		return tt_seo_title() . ' | TiivisTupa';
	}
	return $title;
} );

add_action( 'wp_head', function () {
	if ( ! is_singular() ) {
		return;
	}
	$post  = get_queried_object();
	$desc  = tt_meta_description( $post );
	$url   = get_permalink( $post );
	$info  = tt_page_info( $post );
	$cities = tt_cities();
	$place = $info['city'] ? $cities[ $info['city'] ] : 'Turku';
	echo '<meta name="description" content="' . esc_attr( $desc ) . '">' . "\n";
	echo '<meta property="og:type" content="website">' . "\n";
	echo '<meta property="og:site_name" content="TiivisTupa">' . "\n";
	echo '<meta property="og:locale" content="fi_FI">' . "\n";
	echo '<meta property="og:title" content="' . esc_attr( tt_seo_title( $post ) ) . '">' . "\n";
	echo '<meta property="og:description" content="' . esc_attr( $desc ) . '">' . "\n";
	echo '<meta property="og:url" content="' . esc_url( $url ) . '">' . "\n";
	echo '<meta name="twitter:card" content="summary">' . "\n";
	echo '<meta name="geo.region" content="FI-19">' . "\n";
	echo '<meta name="geo.placename" content="' . esc_attr( $place ) . '">' . "\n";
	echo '<script type="application/ld+json">' . wp_json_encode( tt_jsonld( $post ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) . '</script>' . "\n";
}, 5 );

// Hakukoneet saavat näyttää pitkät katkelmat.
add_filter( 'wp_robots', function ( $robots ) {
	if ( empty( $robots['noindex'] ) ) {
		$robots['max-snippet'] = '-1';
	}
	return $robots;
} );

/** Usein kysytyt kysymykset sivun sisällöstä (faq-item-lohkot). */
function tt_faq_from_content( $content ) {
	$out = array();
	if ( preg_match_all( '#<div class="faq-item"><h3>(.*?)</h3><p>(.*?)</p></div>#s', $content, $m, PREG_SET_ORDER ) ) {
		foreach ( $m as $row ) {
			$out[] = array(
				'q' => html_entity_decode( wp_strip_all_tags( $row[1] ), ENT_QUOTES, 'UTF-8' ),
				'a' => html_entity_decode( wp_strip_all_tags( $row[2] ), ENT_QUOTES, 'UTF-8' ),
			);
		}
	}
	return $out;
}

function tt_jsonld( $post ) {
	$home     = home_url( '/' );
	$url      = get_permalink( $post );
	$info     = tt_page_info( $post );
	$services = tt_services();
	$cities   = tt_cities();

	$area = array( array( '@type' => 'City', 'name' => 'Turku' ) );
	foreach ( $cities as $name ) {
		$area[] = array( '@type' => 'City', 'name' => $name );
	}
	$area[] = array( '@type' => 'AdministrativeArea', 'name' => 'Varsinais-Suomi' );

	$org = array(
		'@type'      => 'HomeAndConstructionBusiness',
		'@id'        => $home . '#yritys',
		'name'       => 'TiivisTupa',
		'legalName'  => tt_opt( 'tt_legal' ),
		'url'        => $home,
		'telephone'  => tt_phone_intl(),
		'email'      => tt_opt( 'tt_email' ),
		'taxID'      => tt_opt( 'tt_ytunnus' ),
		'address'    => array(
			'@type'           => 'PostalAddress',
			'addressLocality' => tt_opt( 'tt_locality' ),
			'addressRegion'   => 'Varsinais-Suomi',
			'addressCountry'  => 'FI',
		),
		'areaServed' => $area,
		'knowsAbout' => array_merge( array_values( $services ), array( 'Ikkunoiden ja ovien huoltoremontti' ) ),
	);
	$org = array_filter( $org );

	$crumbs = array( array( 'Etusivu', $home ) );
	if ( $info['service'] ) {
		$crumbs[] = array( $services[ $info['service'] ], home_url( '/' . $info['service'] . '/' ) );
	}
	if ( 'city' === $info['kind'] ) {
		$crumbs[] = array( $cities[ $info['city'] ], $url );
	} elseif ( in_array( $info['kind'], array( 'hub', 'other' ), true ) ) {
		$crumbs[] = array( get_the_title( $post ), $url );
	}
	$items = array();
	foreach ( $crumbs as $i => $c ) {
		$items[] = array( '@type' => 'ListItem', 'position' => $i + 1, 'name' => $c[0], 'item' => $c[1] );
	}

	$graph = array(
		$org,
		array(
			'@type'        => 'WebPage',
			'@id'          => $url . '#sivu',
			'url'          => $url,
			'name'         => tt_seo_title( $post ),
			'description'  => tt_meta_description( $post ),
			'inLanguage'   => 'fi',
			'isPartOf'     => array( '@id' => $home . '#sivusto' ),
			'about'        => array( '@id' => $home . '#yritys' ),
			'dateModified' => get_post_modified_time( 'c', true, $post ),
		),
		array( '@type' => 'BreadcrumbList', 'itemListElement' => $items ),
		array(
			'@type'      => 'WebSite',
			'@id'        => $home . '#sivusto',
			'url'        => $home,
			'name'       => 'TiivisTupa',
			'inLanguage' => 'fi',
			'publisher'  => array( '@id' => $home . '#yritys' ),
		),
	);

	if ( in_array( $info['kind'], array( 'front', 'service', 'city' ), true ) ) {
		$served = $info['city']
			? array( array( '@type' => 'City', 'name' => $cities[ $info['city'] ] ) )
			: array( array( '@type' => 'City', 'name' => 'Turku' ), array( '@type' => 'AdministrativeArea', 'name' => 'Varsinais-Suomi' ) );
		$graph[] = array(
			'@type'       => 'Service',
			'@id'         => $url . '#palvelu',
			'name'        => get_the_title( $post ),
			'serviceType' => $info['service'] ? $services[ $info['service'] ] : 'Ikkunoiden ja ovien huoltoremontti',
			'description' => tt_meta_description( $post ),
			'provider'    => array( '@id' => $home . '#yritys' ),
			'areaServed'  => $served,
			'url'         => $url,
		);
	}

	$faq = tt_faq_from_content( $post->post_content );
	if ( $faq ) {
		$entities = array();
		foreach ( $faq as $f ) {
			$entities[] = array(
				'@type'          => 'Question',
				'name'           => $f['q'],
				'acceptedAnswer' => array( '@type' => 'Answer', 'text' => $f['a'] ),
			);
		}
		$graph[] = array( '@type' => 'FAQPage', '@id' => $url . '#ukk', 'mainEntity' => $entities );
	}

	return array( '@context' => 'https://schema.org', '@graph' => $graph );
}

// XML-sivukartta: vain sivut. Käyttäjä- ja luokkasivuja ei ole.
add_filter( 'wp_sitemaps_add_provider', function ( $provider, $name ) {
	return in_array( $name, array( 'users', 'taxonomies' ), true ) ? false : $provider;
}, 10, 2 );

add_filter( 'wp_sitemaps_post_types', function ( $types ) {
	unset( $types['post'], $types['tt_lead'] );
	return $types;
} );

/** llms.txt tekoälyhakuja varten: /llms.txt */
add_action( 'init', function () {
	$path = isset( $_SERVER['REQUEST_URI'] ) ? wp_parse_url( wp_unslash( $_SERVER['REQUEST_URI'] ), PHP_URL_PATH ) : ''; // phpcs:ignore
	$base = wp_parse_url( home_url( '/' ), PHP_URL_PATH );
	if ( rtrim( (string) $base, '/' ) . '/llms.txt' !== $path ) {
		return;
	}
	$lines = array(
		'# TiivisTupa',
		'',
		'> TiivisTupa tekee ikkunoiden ja ovien huoltoremontteja Turussa ja Varsinais-Suomessa: ikkunahuolto, ovihuolto, ikkunoiden ja ovien tiivistys, ikkunoiden korjaus sekä ikkunapuitteiden maalaus ja korjaus. Tukikohta on ' . tt_opt( 'tt_locality' ) . '. Kartoituskäynti on maksuton ja tarjous kirjallinen.',
		'',
	);
	$groups = array( 'Palvelut' => array(), 'Paikkakuntasivut' => array(), 'Muut sivut' => array() );
	$pages  = get_pages( array( 'sort_column' => 'post_title' ) );
	foreach ( $pages as $p ) {
		$info = tt_page_info( $p );
		$line = '- [' . get_the_title( $p ) . '](' . get_permalink( $p ) . '): ' . tt_meta_description( $p );
		if ( in_array( $info['kind'], array( 'front', 'service' ), true ) ) {
			$groups['Palvelut'][] = $line;
		} elseif ( 'city' === $info['kind'] ) {
			$groups['Paikkakuntasivut'][] = $line;
		} else {
			$groups['Muut sivut'][] = $line;
		}
	}
	foreach ( $groups as $title => $rows ) {
		if ( $rows ) {
			$lines[] = '## ' . $title;
			$lines[] = '';
			$lines   = array_merge( $lines, $rows );
			$lines[] = '';
		}
	}
	$lines[] = '## Yhteystiedot';
	$lines[] = '';
	$lines[] = '- Puhelin: ' . tt_opt( 'tt_phone' );
	$lines[] = '- Sähköposti: ' . tt_opt( 'tt_email' );
	$lines[] = '- Yritys: ' . tt_opt( 'tt_legal' ) . ', Y-tunnus ' . tt_opt( 'tt_ytunnus' ) . ', ' . tt_opt( 'tt_locality' );
	status_header( 200 );
	header( 'Content-Type: text/plain; charset=utf-8' );
	echo implode( "\n", $lines ) . "\n"; // phpcs:ignore
	exit;
} );

/* =========================================================================
 * Tarjouslomake
 * ========================================================================= */

add_action( 'init', function () {
	register_post_type( 'tt_lead', array(
		'labels'              => array(
			'name'          => 'Tarjouspyynnöt',
			'singular_name' => 'Tarjouspyyntö',
			'menu_name'     => 'Tarjouspyynnöt',
			'edit_item'     => 'Tarjouspyyntö',
			'search_items'  => 'Etsi tarjouspyyntöjä',
			'not_found'     => 'Ei tarjouspyyntöjä',
		),
		'public'              => false,
		'show_ui'             => true,
		'show_in_menu'        => true,
		'menu_icon'           => 'dashicons-email-alt',
		'menu_position'       => 25,
		'supports'            => array( 'title', 'editor' ),
		'capability_type'     => 'page',
		'capabilities'        => array( 'create_posts' => 'do_not_allow' ),
		'map_meta_cap'        => true,
		'exclude_from_search' => true,
	) );
} );

function tt_form_token( $time ) {
	return substr( hash_hmac( 'sha256', 'tt-quote|' . (int) $time, wp_salt( 'nonce' ) ), 0, 20 );
}

function tt_client_ip() {
	return isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
}

function tt_quote_form_html() {
	$info   = tt_page_info();
	$time   = time();
	$status = isset( $_GET['tarjous'] ) ? sanitize_key( wp_unslash( $_GET['tarjous'] ) ) : ''; // phpcs:ignore
	$msgs   = array(
		'ok'     => 'Kiitos! Tarjouspyyntösi on vastaanotettu. Otamme yhteyttä viimeistään seuraavana arkipäivänä.',
		'puuttuu' => 'Täytä vähintään nimi ja puhelinnumero.',
		'virhe'  => 'Lähetys ei onnistunut. Yritä hetken kuluttua uudelleen tai soita meille.',
		'linkki' => 'Poista viestistä verkko-osoitteet ja lähetä uudelleen.',
	);
	$opts = '<option value="">Valitse</option><option>Turku</option>';
	foreach ( tt_cities() as $c => $name ) {
		$opts .= '<option' . selected( $c, $info['city'], false ) . '>' . esc_html( $name ) . '</option>';
	}
	$opts  .= '<option>Muu Varsinais-Suomi</option>';
	$checks = '';
	foreach ( tt_services() as $s => $name ) {
		$checks .= '<label><input type="checkbox" name="palvelu[]" value="' . esc_attr( $name ) . '"' . checked( $s, $info['service'], false ) . '> ' . esc_html( $name ) . '</label>';
	}
	$privacy = get_privacy_policy_url();
	ob_start();
	?>
<form class="quote-form" id="quote-form" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post" novalidate>
<input type="hidden" name="action" value="tt_quote">
<input type="hidden" name="tt_t" value="<?php echo (int) $time; ?>">
<input type="hidden" name="tt_k" value="<?php echo esc_attr( tt_form_token( $time ) ); ?>">
<input type="hidden" name="tt_sivu" value="<?php echo esc_url( get_permalink() ); ?>">
<p class="tt-hp" aria-hidden="true"><label>Jätä tämä kenttä tyhjäksi <input type="text" name="verkkosivu" tabindex="-1" autocomplete="off"></label></p>
<?php if ( $status && isset( $msgs[ $status ] ) ) : ?>
<p class="form-status<?php echo 'ok' === $status ? ' is-ok' : ''; ?>" id="q-status" role="status"><?php echo esc_html( $msgs[ $status ] ); ?></p>
<?php else : ?>
<p class="form-status" id="q-status" role="status" hidden></p>
<?php endif; ?>
<div class="field"><label for="q-nimi">Nimi</label><input id="q-nimi" name="nimi" type="text" autocomplete="name" required maxlength="100"></div>
<div class="field"><label for="q-puhelin">Puhelin</label><input id="q-puhelin" name="puhelin" type="tel" autocomplete="tel" required maxlength="30"></div>
<div class="field"><label for="q-email">Sähköposti</label><input id="q-email" name="email" type="email" autocomplete="email" maxlength="120"></div>
<div class="field"><label for="q-kunta">Paikkakunta</label><select id="q-kunta" name="paikkakunta"><?php echo $opts; // phpcs:ignore ?></select></div>
<fieldset class="field full"><legend>Mitä palvelua tarvitset?</legend><div class="checks"><?php echo $checks; // phpcs:ignore ?></div></fieldset>
<div class="field full"><label for="q-viesti">Kuvaile tilanne</label><textarea id="q-viesti" name="viesti" maxlength="3000" placeholder="Esim. omakotitalo 1982, 14 ikkunaa, makuuhuoneissa vetää ja ulkopuitteiden maali hilseilee."></textarea></div>
<div class="field full"><button class="btn btn-primary" type="submit" id="q-laheta">Lähetä tarjouspyyntö</button></div>
<p class="form-note">Käytämme tietojasi vain tarjouspyyntösi käsittelyyn.<?php if ( $privacy ) : ?> <a href="<?php echo esc_url( $privacy ); ?>">Tietosuojaseloste</a><?php endif; ?></p>
</form>
	<?php
	return ob_get_clean();
}

add_action( 'admin_post_nopriv_tt_quote', 'tt_handle_quote' );
add_action( 'admin_post_tt_quote', 'tt_handle_quote' );

function tt_quote_redirect( $status ) {
	$back = isset( $_POST['tt_sivu'] ) ? wp_validate_redirect( esc_url_raw( wp_unslash( $_POST['tt_sivu'] ) ), home_url( '/' ) ) : home_url( '/' ); // phpcs:ignore
	wp_safe_redirect( add_query_arg( 'tarjous', $status, $back ) . '#tarjous' );
	exit;
}

function tt_handle_quote() {
	// phpcs:disable WordPress.Security.NonceVerification.Missing -- julkinen lomake, suojaus aikaleimalla ja hunajapurkilla.
	$time  = isset( $_POST['tt_t'] ) ? (int) $_POST['tt_t'] : 0;
	$token = isset( $_POST['tt_k'] ) ? sanitize_text_field( wp_unslash( $_POST['tt_k'] ) ) : '';
	$age   = time() - $time;
	// Robotit: hunajapurkki täytetty, tunniste väärä, lähetetty alle 3 sekunnissa tai yli viikon vanhalta sivulta.
	if ( ! empty( $_POST['verkkosivu'] ) || ! hash_equals( tt_form_token( $time ), $token ) || $age < 3 || $age > WEEK_IN_SECONDS ) {
		tt_quote_redirect( 'ok' ); // Robotille näytetään onnistuminen, mitään ei tallenneta.
	}

	$name    = isset( $_POST['nimi'] ) ? sanitize_text_field( wp_unslash( $_POST['nimi'] ) ) : '';
	$phone   = isset( $_POST['puhelin'] ) ? sanitize_text_field( wp_unslash( $_POST['puhelin'] ) ) : '';
	$email   = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';
	$city    = isset( $_POST['paikkakunta'] ) ? sanitize_text_field( wp_unslash( $_POST['paikkakunta'] ) ) : '';
	$message = isset( $_POST['viesti'] ) ? sanitize_textarea_field( wp_unslash( $_POST['viesti'] ) ) : '';
	$page    = isset( $_POST['tt_sivu'] ) ? esc_url_raw( wp_unslash( $_POST['tt_sivu'] ) ) : '';
	$wanted  = array();
	if ( isset( $_POST['palvelu'] ) && is_array( $_POST['palvelu'] ) ) {
		$allowed = array_values( tt_services() );
		foreach ( wp_unslash( $_POST['palvelu'] ) as $s ) {
			$s = sanitize_text_field( $s );
			if ( in_array( $s, $allowed, true ) ) {
				$wanted[] = $s;
			}
		}
	}
	// phpcs:enable

	$name    = mb_substr( $name, 0, 100 );
	$phone   = mb_substr( $phone, 0, 30 );
	$city    = mb_substr( $city, 0, 60 );
	$message = mb_substr( $message, 0, 3000 );

	if ( '' === $name || '' === $phone || ! preg_match( '/\d{5,}/', preg_replace( '/[\s\-()+]/', '', $phone ) ) ) {
		tt_quote_redirect( 'puuttuu' );
	}
	if ( preg_match( '#https?://|www\.#i', $message . ' ' . $name ) ) {
		tt_quote_redirect( 'linkki' );
	}

	// Määrärajat: 5 pyyntöä tunnissa samasta osoitteesta, 80 vuorokaudessa koko sivustolta.
	$ip_key  = 'tt_q_' . md5( tt_client_ip() );
	$ip_n    = (int) get_transient( $ip_key );
	$day_key = 'tt_q_day_' . gmdate( 'Ymd' );
	$day_n   = (int) get_transient( $day_key );
	if ( $ip_n >= 5 || $day_n >= 80 ) {
		tt_quote_redirect( 'virhe' );
	}
	set_transient( $ip_key, $ip_n + 1, HOUR_IN_SECONDS );
	set_transient( $day_key, $day_n + 1, DAY_IN_SECONDS );

	$rows = array(
		'Nimi'        => $name,
		'Puhelin'     => $phone,
		'Sähköposti'  => $email,
		'Paikkakunta' => $city,
		'Palvelut'    => implode( ', ', $wanted ),
		'Sivu'        => $page,
	);
	$body = '';
	foreach ( $rows as $k => $v ) {
		if ( '' !== $v ) {
			$body .= $k . ': ' . $v . "\n";
		}
	}
	$body .= "\nViesti:\n" . ( '' !== $message ? $message : '(ei viestiä)' ) . "\n";

	$lead_id = wp_insert_post( array(
		'post_type'    => 'tt_lead',
		'post_status'  => 'private',
		'post_title'   => $name . ( $city ? ', ' . $city : '' ),
		'post_content' => $body,
	) );

	$to      = tt_opt( 'tt_notify' );
	$to      = is_email( $to ) ? $to : ( is_email( tt_opt( 'tt_email' ) ) ? tt_opt( 'tt_email' ) : get_option( 'admin_email' ) );
	$headers = array( 'Content-Type: text/plain; charset=UTF-8' );
	if ( is_email( $email ) ) {
		$headers[] = 'Reply-To: ' . $email;
	}
	$sent = wp_mail( $to, 'Tarjouspyyntö: ' . $name . ( $city ? ', ' . $city : '' ), $body, $headers );

	$lead = compact( 'name', 'phone', 'email', 'city', 'message', 'wanted', 'page' );
	/**
	 * Uusi tarjouspyyntö. Tähän voi kytkeä CRM:n tai muun jatkokäsittelyn.
	 *
	 * @param array $lead    name, phone, email, city, message, wanted (palvelut), page
	 * @param int   $lead_id Tallennetun tarjouspyynnön ID.
	 */
	do_action( 'tiivistupa_lead', $lead, (int) $lead_id );

	// Pyyntö on tallessa hallinnassa, vaikka sähköposti ei lähtisi.
	tt_quote_redirect( ( $lead_id && ! is_wp_error( $lead_id ) ) || $sent ? 'ok' : 'virhe' );
}

/* =========================================================================
 * Ulkoasun apurit
 * ========================================================================= */

function tt_logo_svg() {
	return '<svg viewBox="0 0 34 34" aria-hidden="true"><rect x="1" y="1" width="32" height="32" rx="5" fill="#f5ad1b"/><path d="M7 17 17 8l10 9v10H7z" fill="#0a3540"/><rect x="14" y="18" width="6" height="9" fill="#f5ad1b"/></svg>';
}

function tt_nav_html() {
	$info = tt_page_info();
	$out  = '';
	foreach ( tt_services() as $slug => $name ) {
		$cur  = $slug === $info['service'] ? ' aria-current="page"' : '';
		$out .= '<li><a href="' . esc_url( home_url( '/' . $slug . '/' ) ) . '"' . $cur . '>' . esc_html( $name ) . '</a></li>';
	}
	$cur  = 'hub' === $info['kind'] ? ' aria-current="page"' : '';
	$out .= '<li><a href="' . esc_url( home_url( '/palvelualue/' ) ) . '"' . $cur . '>Palvelualue</a></li>';
	return $out;
}

/** Sivu on teeman HTML-sivu, jos sisällössä on teeman hero. Muut sivut saavat tavallisen otsikon ja palstan. */
function tt_is_theme_page( $post = null ) {
	$post = get_post( $post );
	return $post && false !== strpos( $post->post_content, 'class="hero' );
}
