<?php
/**
 * Nordic Ikkunat & Ovet -teeman toiminnot (versio 2)
 *
 * Versio 2 uudistaa ulkoasun ja tekniikan, mutta sivujen sisältö (tekstit ja
 * HTML-rakenne) säilyy ennallaan. Kaikki sivut tyylitellään nyt yhdestä
 * design-järjestelmästä (style.css). Sivukohtaisia _nordic_page_css-tyylejä ei
 * enää tulosteta, joten teeman voi ottaa käyttöön ilman sivujen uudelleentuontia.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

define( 'NORDIC_VERSION', '2.1.1' );

/**
 * Vanhat sivukohtaiset tyylit (_nordic_page_css) saa tarvittaessa takaisin päälle
 * lisäämällä wp-config.php:hen: define( 'NORDIC_LEGACY_PAGE_CSS', true );
 */
if ( ! defined( 'NORDIC_LEGACY_PAGE_CSS' ) ) {
	define( 'NORDIC_LEGACY_PAGE_CSS', false );
}

/**
 * Tarjouslomakkeen Fluent Forms -lomakkeen ID (sama lomake kuin sivuilla).
 * Aluesivustoilla ID valitaan Mukauta → Sivuston alue -kohdasta.
 */
if ( ! defined( 'NORDIC_QUOTE_FORM_ID' ) ) {
	define( 'NORDIC_QUOTE_FORM_ID', 3 );
}

function nordic_quote_form_id() {
	$id = (int) get_theme_mod( 'nordic_quote_form_id', 0 );
	return $id > 0 ? $id : (int) NORDIC_QUOTE_FORM_ID;
}

/* =========================================================================
 * Alueet: sama teema pääsivustolle ja aluesivustoille (alidomainit).
 * Alue valitaan Ulkoasu → Mukauta → Sivuston alue. Oletus on pääsivusto.
 * ========================================================================= */

function nordic_regions() {
	$region_menu = array(
		'/'                          => 'Etusivu',
		'/ikkunat/'                  => 'Ikkunat',
		'/ulko-ovet/'                => 'Ulko-ovet',
		'/ikkunaremontit/'           => 'Ikkunaremontit',
		'/oviremontit/'              => 'Oviremontit',
		'/energiansaastolaskuri/'    => 'Energialaskuri',
		'/ikkuna-asennus/'           => 'Ikkuna-asennus',
		'/uudet-ikkunat-vai-huolto/' => 'Uudet ikkunat vai huolto',
		'/yhteystiedot/'             => 'Yhteystiedot',
	);
	return apply_filters( 'nordic_regions', array(
		'varsinais-suomi' => array(
			'label'     => 'Varsinais-Suomi (pääsivusto)',
			'mode'      => 'full',
			'region'    => 'Varsinais-Suomi',
			'area_text' => 'Turku ja Varsinais-Suomi',
			'in_text'   => 'Turussa ja Varsinais-Suomessa',
			'locality'  => 'Turku',
			'cities'    => array_values( nordic_cities() ),
		),
		'uusimaa'         => array(
			'label'     => 'Uusimaa: Helsinki, Espoo ja Vantaa',
			'mode'      => 'region',
			'region'    => 'Uusimaa',
			'area_text' => 'Helsinki, Espoo, Vantaa ja Kauniainen',
			'in_text'   => 'Helsingissä, Espoossa ja Vantaalla',
			'locality'  => 'Helsinki',
			'cities'    => array( 'Helsinki', 'Espoo', 'Vantaa', 'Kauniainen' ),
			'menu'      => $region_menu,
		),
		'meri-lappi'      => array(
			'label'     => 'Meri-Lappi',
			'mode'      => 'region',
			'region'    => 'Lappi',
			'area_text' => 'Kemi, Tornio ja Meri-Lappi',
			'in_text'   => 'Kemissä, Torniossa ja koko Meri-Lapissa',
			'locality'  => 'Kemi',
			'cities'    => array( 'Kemi', 'Tornio', 'Keminmaa', 'Simo', 'Tervola' ),
			'menu'      => $region_menu,
		),
		'satakunta'       => array(
			'label'     => 'Satakunta',
			'mode'      => 'region',
			'region'    => 'Satakunta',
			'area_text' => 'Pori, Rauma ja Satakunta',
			'in_text'   => 'Porissa, Raumalla ja koko Satakunnassa',
			'locality'  => 'Pori',
			'cities'    => array( 'Pori', 'Rauma', 'Ulvila', 'Eurajoki', 'Eura', 'Harjavalta', 'Kokemäki', 'Huittinen', 'Kankaanpää', 'Nakkila' ),
			'menu'      => $region_menu,
		),
	) );
}

function nordic_region() {
	$regions = nordic_regions();
	$key     = (string) get_theme_mod( 'nordic_region', '' );
	if ( '' === $key ) {
		// Ei valittu Mukauta-valikosta: päätellään osoitteesta (esim. uusimaa.ikkunakauppias.fi).
		$host = strtolower( (string) wp_parse_url( home_url(), PHP_URL_HOST ) );
		foreach ( array( 'uusimaa' => 'uusimaa', 'merilappi' => 'meri-lappi', 'meri-lappi' => 'meri-lappi', 'satakunta' => 'satakunta' ) as $needle => $region ) {
			if ( false !== strpos( $host, $needle ) ) {
				$key = $region;
				break;
			}
		}
	}
	if ( ! isset( $regions[ $key ] ) ) {
		$key = 'varsinais-suomi';
	}
	return array_merge( array( 'key' => $key, 'menu' => array() ), $regions[ $key ] );
}

function nordic_is_regional() {
	$r = nordic_region();
	return 'region' === $r['mode'];
}

function nordic_customize_region( $wp_customize ) {
	$wp_customize->add_section( 'nordic_site', array(
		'title'       => 'Sivuston alue',
		'priority'    => 30,
		'description' => 'Valitse, minkä alueen sivusto tämä on. Valinta muuttaa päävalikon, alatunnisteen ja hakukoneille näkyvän toiminta-alueen.',
	) );
	$choices = array();
	foreach ( nordic_regions() as $key => $r ) {
		$choices[ $key ] = $r['label'];
	}
	$wp_customize->add_setting( 'nordic_region', array( 'default' => nordic_region()['key'], 'sanitize_callback' => function ( $v ) {
		return array_key_exists( $v, nordic_regions() ) ? $v : 'varsinais-suomi';
	} ) );
	$wp_customize->add_control( 'nordic_region', array( 'section' => 'nordic_site', 'label' => 'Alue', 'type' => 'select', 'choices' => $choices ) );
	$wp_customize->add_setting( 'nordic_quote_form_id', array( 'default' => '', 'sanitize_callback' => 'absint' ) );
	$wp_customize->add_control( 'nordic_quote_form_id', array(
		'section'     => 'nordic_site',
		'label'       => 'Tarjouslomakkeen ID (Fluent Forms)',
		'description' => 'Lomakkeen numero lyhytkoodista [fluentform id="X"]. Tyhjä = ' . (int) NORDIC_QUOTE_FORM_ID . '.',
		'type'        => 'number',
	) );
}
add_action( 'customize_register', 'nordic_customize_region' );

/** [nordic_tarjouslomake] – tarjouslomake sivun sisältöön (yhteystiedot). */
add_shortcode( 'nordic_tarjouslomake', function () {
	return nordic_quote_form_html();
} );

/* =========================================================================
 * Perusasetukset
 * ========================================================================= */

function nordic_setup() {
	add_theme_support( 'title-tag' );
	add_theme_support( 'post-thumbnails' );
	add_theme_support( 'html5', array( 'search-form', 'comment-form', 'comment-list', 'gallery', 'caption', 'style', 'script' ) );
	add_theme_support( 'automatic-feed-links' );
	add_post_type_support( 'page', 'excerpt' ); // Ote = hakukoneiden kuvaus (meta description)

	register_nav_menus( array(
		'primary'         => __( 'Päävalikko', 'nordic' ),
		'footer_palvelut' => __( 'Alatunniste: Palvelut', 'nordic' ),
		'footer_alueet'   => __( 'Alatunniste: Alueet', 'nordic' ),
	) );
}
add_action( 'after_setup_theme', 'nordic_setup' );

/**
 * Kevyempi <head>: emoji-skriptit, generator-tagi ym. pois (nopeus + siisteys).
 */
function nordic_cleanup_head() {
	remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
	remove_action( 'wp_print_styles', 'print_emoji_styles' );
	remove_action( 'wp_head', 'wp_generator' );
	remove_action( 'wp_head', 'wlwmanifest_link' );
	remove_action( 'wp_head', 'rsd_link' );
	remove_action( 'wp_head', 'wp_shortlink_wp_head' );
	// WordPressin oma canonical korvataan teeman canonicalilla (nordic_head_meta).
	remove_action( 'wp_head', 'rel_canonical' );
}
add_action( 'init', 'nordic_cleanup_head' );

/**
 * Sivuston sisältö on valmiiksi rakennettua HTML:ää, joten wpautop pois
 * (muuten WordPress lisää ylimääräisiä <p>-tageja divien sisään).
 */
remove_filter( 'the_content', 'wpautop' );
remove_filter( 'the_excerpt', 'wpautop' );

/**
 * Otsikon erotin: "Sivu – Nordic Ikkunat & Ovet"
 */
add_filter( 'document_title_separator', function () {
	return '–';
} );

/* =========================================================================
 * Tyylit, fontit ja skriptit
 * ========================================================================= */

function nordic_asset_uri( $path ) {
	return get_template_directory_uri() . '/assets/' . ltrim( $path, '/' );
}

function nordic_scripts() {
	wp_enqueue_style( 'nordic-style', get_stylesheet_uri(), array(), NORDIC_VERSION );
	wp_enqueue_script( 'nordic', nordic_asset_uri( 'js/nordic.js' ), array(), NORDIC_VERSION, array(
		'strategy'  => 'defer',
		'in_footer' => true,
	) );
}
add_action( 'wp_enqueue_scripts', 'nordic_scripts' );

/**
 * Fontit ladataan teeman omista tiedostoista (ei Google Fonts -kutsua):
 * nopeampi ensimmäinen piirto ja GDPR-ystävällisempi.
 * Lisäksi html-elementille "js"-luokka ennen piirtoa, jotta animaatiot
 * eivät välähdä.
 */
function nordic_head_early() {
	echo '<script>document.documentElement.classList.add("js");</script>' . "\n";
	echo '<link rel="preload" href="' . esc_url( nordic_asset_uri( 'fonts/inter.woff2' ) ) . '" as="font" type="font/woff2" crossorigin>' . "\n";
	echo '<link rel="preload" href="' . esc_url( nordic_asset_uri( 'fonts/space-grotesk.woff2' ) ) . '" as="font" type="font/woff2" crossorigin>' . "\n";
	echo '<meta name="theme-color" content="#16303B">' . "\n";

	// Hero-kuvan esilataus (LCP-kuva) — nopeuttaa näkyvää latausta.
	if ( is_singular( 'page' ) ) {
		$hero = nordic_page_hero_image( get_the_ID() );
		if ( $hero ) {
			$attrs = nordic_image_variant_attrs( $hero );
			echo '<link rel="preload" as="image" href="' . esc_url( $attrs['src'] ) . '"';
			if ( ! empty( $attrs['srcset'] ) ) {
				echo ' imagesrcset="' . esc_attr( $attrs['srcset'] ) . '" imagesizes="' . esc_attr( $attrs['sizes'] ) . '"';
			}
			echo ' fetchpriority="high">' . "\n";
		}
	}
}
add_action( 'wp_head', 'nordic_head_early', 1 );

/**
 * Favicon / sivuston kuvake, jos sivustokuvaketta ei ole asetettu WordPressissä.
 */
function nordic_favicon() {
	if ( function_exists( 'has_site_icon' ) && has_site_icon() ) {
		return;
	}
	echo '<link rel="icon" href="' . esc_url( nordic_asset_uri( 'img/site-icon-512.svg' ) ) . '" type="image/svg+xml">' . "\n";
	echo '<link rel="apple-touch-icon" href="' . esc_url( nordic_asset_uri( 'img/site-icon-512.png' ) ) . '">' . "\n";
}
add_action( 'wp_head', 'nordic_favicon', 2 );

/**
 * Body-luokat: sivun slug ja sivutyyppi tyylittelyä varten.
 */
function nordic_body_class( $classes ) {
	if ( is_singular( 'page' ) ) {
		$slug      = get_post_field( 'post_name', get_the_ID() );
		$classes[] = 'page-slug-' . sanitize_html_class( $slug );
		$classes[] = 'page-type-' . nordic_page_type( $slug );
	}
	if ( nordic_is_regional() ) {
		$classes[] = 'nordic-regional';
	}
	return $classes;
}
add_filter( 'body_class', 'nordic_body_class' );

/* =========================================================================
 * Yritystiedot ja sivujen luokittelu
 * ========================================================================= */

/**
 * Yrityksen perustiedot skeemoja varten.
 * Täytä puhelin ja osoite, kun ne ovat käytettävissä — ne tulostuvat
 * automaattisesti skeemaan, llms.txt-tiedostoon ja alatunnisteeseen.
 */
function nordic_business_info() {
	return array(
		'name'            => 'Nordic Ikkunat & Ovet Oy',
		'brand'           => 'Ikkunakauppias.fi',
		'description'     => 'Skaala-ikkunoiden ja -ovien asennus, myynti ja huolto ' . nordic_region()['in_text'] . '.',
		'areaServed'      => array_merge( array( nordic_region()['region'] ), nordic_region()['cities'] ),
		'email'           => 'info@ikkunakauppias.fi',
		'telephone'       => '', // esim. '+358401234567'
		'streetAddress'   => '',
		'postalCode'      => '',
		'addressLocality' => nordic_region()['locality'],
		'openingHours'    => 'Mo-Fr 08:00-17:00',
	);
}

/**
 * Artikkeli-tyyppisten opassivujen slugit — näille tulostetaan Article-skeema
 * ja "Lue myös" -nostot.
 */
function nordic_article_slugs() {
	return array(
		'kannattaako-ikkunoiden-vaihto',
		'kannattaako-ulko-oven-vaihto',
		'merkit-etta-ikkunat-pitaa-uusia',
		'milloin-ikkunaremontti-kannattaa-aloittaa',
		'kolmilasinen-vai-nelilasinen-ikkuna',
		'ikkunaremontin-vaikutus-asunnon-arvoon',
		'nain-valmistaudut-ikkuna-asennukseen',
		'energiatodistus-ja-ikkunat',
		'taloyhtion-ikkunaremontti-kustannusten-jako',
		'ulko-oven-turvallisuus',
		'ikkunaremontti-vai-kunnostus',
		'kosteusvaurio-ikkunan-ymparilla',
		'ikkunoiden-aanieristys',
		'passiivitalon-ikkunat',
		'ikkunoiden-huolto-ja-hoito',
		'ikkunaremontin-aikataulu',
		'ikkunaremontti-ja-vakuutus',
		'miksi-ikkuna-huurtuu-sisapuolelta',
		'ikkunaremontti-ja-sisailma',
		'ulko-oven-vedontiiviys',
		'ikkunoiden-uv-suoja',
		'vanhojen-ikkunoiden-kierratys',
		'1970-luvun-talon-ikkunaremontti',
		'hirsitalon-ikkunat',
		'rintamamiestalon-ikkunaremontti',
		'miten-valita-oikeat-ikkunat',
		'miten-valita-paras-ulko-ovi',
		'ikkunoiden-ja-ikkunaremontin-hinta',
		'ulko-ovien-ja-oviremontin-hinta',
		'ulko-oven-vaihto-itse-vai-ammattilainen',
		'kotitalousvahennys-ikkuna-ovi',
		'rakennuslupa-ikkuna-ovi-remontti',
		'ikkunaremontin-rahoitus',
		'kannattaako-ikkunoiden-vaihto',
		'ikkunoiden-ja-ovien-vaihto-samalla-kertaa',
		'ikkuna-ja-ovitarjousten-vertailu',
		'ikkunan-ja-ulko-oven-u-arvo',
	);
}

/**
 * Paikkakuntasivujen slug-pääte → paikkakunnan nimi.
 */
function nordic_cities() {
	return array(
		'turku'        => 'Turku',
		'salo'         => 'Salo',
		'naantali'     => 'Naantali',
		'kaarina'      => 'Kaarina',
		'lieto'        => 'Lieto',
		'parainen'     => 'Parainen',
		'masku'        => 'Masku',
		'raisio'       => 'Raisio',
		'paimio'       => 'Paimio',
		'aura-poytya'  => 'Aura ja Pöytyä',
		'halikko'      => 'Halikko',
		'uusikaupunki' => 'Uusikaupunki',
		'laitila'      => 'Laitila',
		'nousiainen'   => 'Nousiainen',
	);
}

/**
 * Palauttaa paikkakunnan nimen paikkakuntasivun slugista, muuten ''.
 */
function nordic_city_from_slug( $slug ) {
	foreach ( array( 'ikkunat-ikkunaremontti-', 'ulko-ovet-oviremontti-' ) as $prefix ) {
		if ( strpos( $slug, $prefix ) === 0 ) {
			$key    = substr( $slug, strlen( $prefix ) );
			$cities = nordic_cities();
			return isset( $cities[ $key ] ) ? $cities[ $key ] : '';
		}
	}
	return '';
}

/**
 * Sivutyyppi: front | article | city | product | service | utility
 */
function nordic_page_type( $slug ) {
	if ( in_array( $slug, array( 'etusivu-sisalto', 'etusivu-ilman-navigaatiota' ), true ) ) {
		return 'front';
	}
	if ( in_array( $slug, nordic_article_slugs(), true ) ) {
		return 'article';
	}
	if ( nordic_city_from_slug( $slug ) ) {
		return 'city';
	}
	if ( preg_match( '/^(ikkunat-|ovet-|skaala-)/', $slug ) && ! in_array( $slug, array( 'ikkunat-ja-ovet-omakotitaloon', 'ikkunat-ja-ovet-rakennusliikkeille', 'ikkunat-rivitaloon', 'ovet-rivitaloon', 'ovet-ja-ikkunat-mokille' ), true ) ) {
		return 'product';
	}
	if ( in_array( $slug, array( 'sivukartta', 'yhteystiedot', 'energiansaastolaskuri' ), true ) ) {
		return 'utility';
	}
	return 'service';
}

/**
 * Murupolun yläsivu sivun slugin perusteella (sivustolla ei ole WordPressin
 * sivuhierarkiaa, joten hierarkia päätellään osoitteista).
 *
 * @return array|null array( 'slug' => ..., 'name' => ... )
 */
function nordic_parent_for_slug( $slug ) {
	if ( strpos( $slug, 'ikkunat-ikkunaremontti-' ) === 0 ) {
		return array( 'slug' => 'ikkunaremontti', 'name' => 'Ikkunaremontti' );
	}
	if ( strpos( $slug, 'ulko-ovet-oviremontti-' ) === 0 ) {
		return array( 'slug' => 'oviremontti', 'name' => 'Oviremontti' );
	}
	if ( nordic_page_type( $slug ) === 'product' ) {
		if ( strpos( $slug, 'ikkunat-' ) === 0 ) {
			return array( 'slug' => 'ikkunat', 'name' => 'Ikkunat' );
		}
		return array( 'slug' => 'ovet', 'name' => 'Ovet' );
	}
	if ( in_array( $slug, array( 'ikkunaremontti-taloyhtioon' ), true ) ) {
		return array( 'slug' => 'ikkunaremontti', 'name' => 'Ikkunaremontti' );
	}
	if ( in_array( $slug, array( 'oviremontti-taloyhtioon' ), true ) ) {
		return array( 'slug' => 'oviremontti', 'name' => 'Oviremontti' );
	}
	return null;
}

/**
 * Murupolku: array of array( 'name' => ..., 'url' => ... ).
 */
function nordic_breadcrumb_trail( $post_id ) {
	$slug  = get_post_field( 'post_name', $post_id );
	$trail = array(
		array( 'name' => 'Etusivu', 'url' => home_url( '/' ) ),
	);
	$parent = nordic_parent_for_slug( $slug );
	if ( $parent ) {
		$parent_page = get_page_by_path( $parent['slug'] );
		if ( $parent_page && 'publish' === $parent_page->post_status ) {
			$trail[] = array( 'name' => $parent['name'], 'url' => get_permalink( $parent_page ) );
		}
	}
	$trail[] = array( 'name' => nordic_short_title( $post_id ), 'url' => get_permalink( $post_id ) );
	return $trail;
}

/**
 * Lyhyt sivun nimi murupolkuun: H1-otsikko sisällöstä, jos sellainen on,
 * muuten sivun otsikko ilman " — ..."-loppua.
 */
function nordic_short_title( $post_id ) {
	$content = get_post_field( 'post_content', $post_id );
	if ( preg_match( '#<h1[^>]*>(.*?)</h1>#si', $content, $m ) ) {
		$h1 = trim( wp_strip_all_tags( $m[1] ) );
		if ( $h1 ) {
			return html_entity_decode( $h1, ENT_QUOTES, 'UTF-8' );
		}
	}
	$title = get_the_title( $post_id );
	$title = preg_split( '/\s+[—–|]\s+/u', $title );
	return html_entity_decode( $title[0], ENT_QUOTES, 'UTF-8' );
}

/* =========================================================================
 * Kuvat: koot, WebP-versiot ja srcset
 * ========================================================================= */

/**
 * Teeman kuvien mitat ja kevyemmät versiot. Mitat estävät asettelun
 * hyppimisen (CLS) ja WebP/srcset pienentää siirrettävää dataa.
 */
function nordic_image_map() {
	return array(
		'hero-guide.webp'       => array( 'w' => 1400, 'h' => 933, 'webp' => 'hero-guide.webp', 'srcset' => array( 800 => 'hero-guide-800.webp', 1400 => 'hero-guide.webp' ) ),
		'hero-compact.webp'     => array( 'w' => 800, 'h' => 533, 'webp' => 'hero-compact.webp' ),
		'hero.webp'             => array( 'w' => 2048, 'h' => 1152, 'webp' => 'hero.webp', 'srcset' => array( 1024 => 'hero-1024.webp', 2048 => 'hero.webp' ) ),
		'seo-ikkuna.jpg'        => array( 'w' => 1200, 'h' => 800, 'webp' => 'seo-ikkuna.webp', 'srcset' => array( 640 => 'seo-ikkuna-640.webp', 1200 => 'seo-ikkuna.webp' ) ),
		'seo-ovi.jpg'           => array( 'w' => 1200, 'h' => 873, 'webp' => 'seo-ovi.webp', 'srcset' => array( 640 => 'seo-ovi-640.webp', 1200 => 'seo-ovi.webp' ) ),
		'product-portrait.png'  => array( 'w' => 238, 'h' => 504, 'webp' => 'product-portrait.webp' ),
	);
}

/**
 * Palauttaa src/srcset/sizes/width/height teeman kuvalle (URL tai tiedostonimi).
 */
function nordic_image_variant_attrs( $url ) {
	$file = basename( wp_parse_url( $url, PHP_URL_PATH ) );
	$map  = nordic_image_map();
	$base = get_template_directory_uri() . '/assets/img/';
	if ( ! isset( $map[ $file ] ) ) {
		return array( 'src' => $url );
	}
	$info  = $map[ $file ];
	$attrs = array(
		'src'    => $base . $info['webp'],
		'width'  => $info['w'],
		'height' => $info['h'],
	);
	if ( ! empty( $info['srcset'] ) ) {
		$set = array();
		foreach ( $info['srcset'] as $w => $f ) {
			$set[] = $base . $f . ' ' . $w . 'w';
		}
		$attrs['srcset'] = implode( ', ', $set );
		$attrs['sizes']  = '(max-width: 900px) 100vw, 50vw';
	}
	return $attrs;
}

/**
 * Sivun pääkuva (ensimmäinen kuva sisällössä) — käytetään hero-esilatauksessa,
 * og:image-tagissa ja skeemoissa.
 */
function nordic_page_hero_image( $post_id ) {
	$content = get_post_field( 'post_content', $post_id );
	if ( strpos( $content, 'hero-photo' ) !== false ) {
		return get_template_directory_uri() . '/assets/img/hero.webp';
	}
	if ( preg_match( '#<img[^>]+src="([^"]+)"#i', $content, $m ) ) {
		return $m[1];
	}
	return '';
}

/**
 * og:image -kuva (aina täysikokoinen versio).
 */
function nordic_share_image( $post_id ) {
	$img = nordic_page_hero_image( $post_id );
	if ( ! $img || strpos( $img, 'product-portrait' ) !== false ) {
		$img = get_template_directory_uri() . '/assets/img/hero-guide.webp';
	}
	$attrs = nordic_image_variant_attrs( $img );
	return $attrs;
}

/* =========================================================================
 * Sisällön muokkaukset tulostusvaiheessa (tekstit pysyvät ennallaan)
 * ========================================================================= */

function nordic_filter_content( $html ) {
	if ( ! is_singular( 'page' ) || ! in_the_loop() || ! is_main_query() ) {
		return $html;
	}
	$post_id = get_the_ID();
	$slug    = get_post_field( 'post_name', $post_id );

	// 1) Sisällön oma <main> → <div>: page.php tarjoaa jo <main>-elementin,
	//    eikä sivulla saa olla kahta main-elementtiä (saavutettavuus + SEO).
	$html = preg_replace( '#^\s*<main(\s[^>]*)?>#i', '<div class="page-content">', $html, 1, $count );
	if ( $count ) {
		$pos = strripos( $html, '</main>' );
		if ( false !== $pos ) {
			$html = substr_replace( $html, '</div>', $pos, strlen( '</main>' ) );
		}
	} else {
		$html = '<div class="page-content">' . $html . '</div>';
	}

	// 2) Kuvat: WebP, srcset, mitat, laiska lataus. Ensimmäinen kuva on hero → ladataan heti.
	$index = 0;
	$html  = preg_replace_callback( '#<img\b([^>]*)>#i', function ( $m ) use ( &$index ) {
		$attrs = $m[1];
		$index++;
		if ( preg_match( '#\ssrc="([^"]+)"#i', $attrs, $src ) ) {
			$v = nordic_image_variant_attrs( $src[1] );
			$attrs = str_replace( $src[0], ' src="' . esc_url( $v['src'] ) . '"', $attrs );
			if ( ! empty( $v['srcset'] ) && stripos( $attrs, 'srcset=' ) === false ) {
				$attrs .= ' srcset="' . esc_attr( $v['srcset'] ) . '" sizes="' . esc_attr( $v['sizes'] ) . '"';
			}
			if ( ! empty( $v['width'] ) && stripos( $attrs, 'width=' ) === false ) {
				$attrs .= ' width="' . (int) $v['width'] . '" height="' . (int) $v['height'] . '"';
			}
		}
		$attrs = preg_replace( '#\sloading="[^"]*"#i', '', $attrs );
		if ( 1 === $index ) {
			$attrs .= ' fetchpriority="high" decoding="async"';
		} else {
			$attrs .= ' loading="lazy" decoding="async"';
		}
		return '<img' . $attrs . '>';
	}, $html );

	// 3) Tyhjät CTA-linkit (href="#") avaavat tarjouslomakkeen.
	$html = preg_replace( '#<a class="btn btn-primary" href="\#">#', '<a class="btn btn-primary" href="#tarjous" data-quote>', $html );

	// 4) Luottamuspalkki heron alle (konversio).
	if ( strpos( $html, 'class="v4hero"' ) !== false ) {
		$html = preg_replace( '#(<header class="v4hero">.*?</header>)#s', '$1' . nordic_trust_strip(), $html, 1 );
	}

	// 5) Opassivuille "Lue myös" -nostot sisäisen linkityksen tueksi.
	if ( nordic_page_type( $slug ) === 'article' ) {
		$related = nordic_related_guides_html( $slug );
		if ( $related ) {
			$pos = strpos( $html, '<section class="final-cta"' );
			if ( false !== $pos ) {
				$html = substr_replace( $html, $related, $pos, 0 );
			} else {
				$html .= $related;
			}
		}
	}

	// 6) Nordic CRM -lisäosan lomakkeet (vain jos lisäosa on käytössä).
	if ( 'energiansaastolaskuri' === $slug && shortcode_exists( 'nordic_laskuri_lomake' ) && strpos( $html, 'nordic-crm-laskuri' ) === false ) {
		$html = preg_replace( '#(<div class="cta-band".*?</a>\s*</div>)#s', '$1' . do_shortcode( '[nordic_laskuri_lomake]' ), $html, 1 );
	}
	if ( in_array( $slug, array( 'ikkunat', 'ovet' ), true ) && shortcode_exists( 'nordic_esite_lomake' ) && function_exists( 'nordic_crm_settings' ) ) {
		$crm = nordic_crm_settings();
		if ( ! empty( $crm['esite_url'] ) && strpos( $html, 'nordic-crm-esite' ) === false ) {
			$block = '<section class="esite-cta"><div class="wrap">' . do_shortcode( '[nordic_esite_lomake]' ) . '</div></section>';
			$pos   = strpos( $html, '<section class="seo-summary' );
			$html  = false !== $pos ? substr_replace( $html, $block, $pos, 0 ) : $html . $block;
		}
	}

	return $html;
}
add_filter( 'the_content', 'nordic_filter_content', 20 );

/**
 * Luottamuspalkki: lyhyet faktat, jotka toistuvat sivujen sisällössä.
 */
function nordic_trust_strip() {
	$items = array(
		array( 'shield', 'Valtuutettu Skaala-jälleenmyyjä' ),
		array( 'ruler', 'Ilmainen mittauskäynti' ),
		array( 'doc', 'Kirjallinen, sitoumukseton tarjous' ),
		array( 'flag', 'Valmistettu Suomessa' ),
	);
	$out = '<div class="trust-strip" aria-label="Miksi Nordic Ikkunat &amp; Ovet"><div class="wrap"><ul>';
	foreach ( $items as $item ) {
		$out .= '<li>' . nordic_icon( $item[0] ) . '<span>' . esc_html( $item[1] ) . '</span></li>';
	}
	$out .= '</ul></div></div>';
	return $out;
}

/**
 * Pienet viivaikonit (inline SVG, ei ulkoisia pyyntöjä).
 */
function nordic_icon( $name ) {
	$paths = array(
		'shield' => '<path d="M12 3l7 3v5c0 4.5-3 8.3-7 10-4-1.7-7-5.5-7-10V6l7-3z"/><path d="M9 12l2 2 4-4"/>',
		'ruler'  => '<path d="M3 17L17 3l4 4L7 21z"/><path d="M7 13l2 2M10 10l2 2M13 7l2 2"/>',
		'doc'    => '<path d="M7 3h7l5 5v13H7z"/><path d="M14 3v5h5M10 13h6M10 17h6"/>',
		'flag'   => '<path d="M5 21V4M5 4h11l-2 4 2 4H5"/>',
		'arrow'  => '<path d="M5 12h14M13 6l6 6-6 6"/>',
		'phone'  => '<path d="M5 4h4l2 5-2.5 1.5a11 11 0 005 5L15 13l5 2v4a2 2 0 01-2 2A16 16 0 013 6a2 2 0 012-2z"/>',
		'mail'   => '<path d="M3 6h18v12H3z"/><path d="M3 7l9 6 9-6"/>',
		'pin'    => '<path d="M12 21s-7-6.2-7-11a7 7 0 0114 0c0 4.8-7 11-7 11z"/><circle cx="12" cy="10" r="2.5"/>',
		'clock'  => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
		'close'  => '<path d="M6 6l12 12M18 6L6 18"/>',
	);
	if ( ! isset( $paths[ $name ] ) ) {
		return '';
	}
	return '<svg class="i i-' . esc_attr( $name ) . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">' . $paths[ $name ] . '</svg>';
}

/**
 * "Lue myös": kolme seuraavaa opasta listasta (kiertävästi).
 */
function nordic_related_guides_html( $slug ) {
	$slugs = nordic_article_slugs();
	$pos   = array_search( $slug, $slugs, true );
	if ( false === $pos ) {
		return '';
	}
	$cards = '';
	$found = 0;
	for ( $i = 1; $i < count( $slugs ) && $found < 3; $i++ ) {
		$page = get_page_by_path( $slugs[ ( $pos + $i ) % count( $slugs ) ] );
		if ( ! $page || 'publish' !== $page->post_status ) {
			continue;
		}
		$found++;
		$desc   = get_post_meta( $page->ID, '_nordic_meta_description', true );
		$cards .= '<article class="type-card related-card"><span class="related-kicker">Opas</span><h3><a href="' . esc_url( get_permalink( $page ) ) . '">' . esc_html( nordic_short_title( $page->ID ) ) . '</a></h3>';
		if ( $desc ) {
			$cards .= '<p>' . esc_html( wp_trim_words( $desc, 18, '…' ) ) . '</p>';
		}
		$cards .= '<span class="card-link" aria-hidden="true">Lue opas &rarr;</span></article>';
	}
	if ( ! $cards ) {
		return '';
	}
	return '<section class="related-guides"><div class="wrap"><div class="section-head"><div class="eyebrow-line"></div><h2>Lue myös</h2></div><div class="types-grid">' . $cards . '</div></div></section>';
}

/* =========================================================================
 * SEO: meta-tagit, canonical, Open Graph ja JSON-LD
 * ========================================================================= */

/**
 * Päällekkäiset sivut: sisällössä on kaksi samanlaista etusivua
 * (etusivu-sisalto ja etusivu-ilman-navigaatiota). Se, jota EI ole asetettu
 * WordPressissä etusivuksi (Asetukset → Lukeminen), on kopio: sen canonical
 * osoittaa etusivulle ja se jätetään pois XML-sivukartasta ja llms.txt:stä.
 */
function nordic_noindex_slugs() {
	$front_id   = (int) get_option( 'page_on_front' );
	$front_slug = $front_id ? get_post_field( 'post_name', $front_id ) : '';
	$copies     = array();
	foreach ( array( 'etusivu-sisalto', 'etusivu-ilman-navigaatiota' ) as $slug ) {
		if ( $slug !== $front_slug ) {
			$copies[] = $slug;
		}
	}
	// Jos etusivua ei ole asetettu kumpaankaan, etusivu-sisalto on pääversio.
	if ( count( $copies ) === 2 ) {
		$copies = array( 'etusivu-ilman-navigaatiota' );
	}
	return $copies;
}

function nordic_meta_description( $post_id ) {
	$desc = get_post_meta( $post_id, '_nordic_meta_description', true );
	if ( ! $desc ) {
		// Uusilla sivuilla kuvaus kirjoitetaan sivun Ote-kenttään.
		$desc = get_post_field( 'post_excerpt', $post_id );
	}
	if ( ! $desc ) {
		// Varakuvaus: ensimmäinen kappale sisällöstä.
		$content = get_post_field( 'post_content', $post_id );
		if ( preg_match( '#<p[^>]*>(.*?)</p>#si', $content, $m ) ) {
			$desc = wp_trim_words( wp_strip_all_tags( $m[1] ), 26, '…' );
		}
	}
	return trim( html_entity_decode( (string) $desc, ENT_QUOTES, 'UTF-8' ) );
}

/**
 * Sivut, jotka eivät kuulu hakutuloksiin (noindex) eivätkä sivukarttaan,
 * esim. lomakkeen kiitossivu.
 */
function nordic_hidden_slugs() {
	return apply_filters( 'nordic_hidden_slugs', array( 'kiitos' ) );
}

/**
 * Robots-ohjeet: isot esikatselukuvat ja täydet tekstiotteet sallittu
 * (Google Discover, AI-yhteenvedot). Kopio-etusivu ohjataan canonicalilla
 * etusivulle (ei noindexiä, jotta signaalit eivät ole ristiriidassa).
 */
function nordic_robots( $robots ) {
	if ( is_singular( 'page' ) && in_array( get_post_field( 'post_name', get_queried_object_id() ), nordic_hidden_slugs(), true ) ) {
		$robots['noindex'] = true;
		$robots['follow']  = true;
		return $robots;
	}
	$robots['max-image-preview'] = 'large';
	$robots['max-snippet']       = '-1';
	$robots['max-video-preview'] = '-1';
	return $robots;
}
add_filter( 'wp_robots', 'nordic_robots' );

function nordic_head_meta() {
	if ( ! is_singular( 'page' ) ) {
		return;
	}

	$post_id  = get_the_ID();
	$slug     = get_post_field( 'post_name', $post_id );
	$type     = nordic_page_type( $slug );
	$is_front = is_front_page() || 'front' === $type;
	$page_url = $is_front ? home_url( '/' ) : get_permalink( $post_id );
	$title    = wp_get_document_title();
	$business = nordic_business_info();
	$desc     = nordic_meta_description( $post_id );
	$image    = nordic_share_image( $post_id );
	$home     = home_url( '/' );

	if ( $desc ) {
		echo '<meta name="description" content="' . esc_attr( $desc ) . '">' . "\n";
	}
	echo '<link rel="canonical" href="' . esc_url( $page_url ) . '">' . "\n";

	// Open Graph + Twitter
	echo '<meta property="og:locale" content="fi_FI">' . "\n";
	echo '<meta property="og:type" content="' . ( 'article' === $type ? 'article' : 'website' ) . '">' . "\n";
	echo '<meta property="og:site_name" content="' . esc_attr( $business['brand'] ) . '">' . "\n";
	echo '<meta property="og:title" content="' . esc_attr( $title ) . '">' . "\n";
	if ( $desc ) {
		echo '<meta property="og:description" content="' . esc_attr( $desc ) . '">' . "\n";
	}
	echo '<meta property="og:url" content="' . esc_url( $page_url ) . '">' . "\n";
	echo '<meta property="og:image" content="' . esc_url( $image['src'] ) . '">' . "\n";
	if ( ! empty( $image['width'] ) ) {
		echo '<meta property="og:image:width" content="' . (int) $image['width'] . '">' . "\n";
		echo '<meta property="og:image:height" content="' . (int) $image['height'] . '">' . "\n";
	}
	if ( 'article' === $type ) {
		echo '<meta property="article:published_time" content="' . esc_attr( get_the_date( 'c', $post_id ) ) . '">' . "\n";
		echo '<meta property="article:modified_time" content="' . esc_attr( get_the_modified_date( 'c', $post_id ) ) . '">' . "\n";
	}
	echo '<meta name="twitter:card" content="summary_large_image">' . "\n";

	// ---------- JSON-LD @graph: kaikki entiteetit yhdessä, ristiviittauksin ----------
	$org_id  = $home . '#organization';
	$site_id = $home . '#website';
	$page_id = $page_url . '#webpage';

	$org = array(
		'@type'        => array( 'HomeAndConstructionBusiness', 'LocalBusiness' ),
		'@id'          => $org_id,
		'name'         => $business['name'],
		'alternateName'=> $business['brand'],
		'description'  => $business['description'],
		'url'          => $home,
		'email'        => $business['email'],
		'logo'         => array(
			'@type'  => 'ImageObject',
			'url'    => nordic_asset_uri( 'img/site-icon-512.png' ),
			'width'  => 512,
			'height' => 512,
		),
		'image'        => nordic_asset_uri( 'img/hero-guide.webp' ),
		'areaServed'   => array_merge(
			array( array( '@type' => 'AdministrativeArea', 'name' => nordic_region()['region'] ) ),
			array_map( function ( $city ) {
				return array( '@type' => 'City', 'name' => $city );
			}, nordic_region()['cities'] )
		),
		'address'      => array(
			'@type'           => 'PostalAddress',
			'addressLocality' => $business['addressLocality'],
			'addressRegion'   => nordic_region()['region'],
			'addressCountry'  => 'FI',
		),
		'openingHours' => $business['openingHours'],
		'brand'        => array( '@type' => 'Brand', 'name' => 'Skaala' ),
		'knowsAbout'   => array( 'Ikkunaremontti', 'Oviremontti', 'Ikkunoiden asennus', 'Ulko-ovien asennus', 'Ikkunoiden huolto', 'Skaala-ikkunat', 'Skaala-ovet', 'Kotitalousvähennys' ),
		'hasOfferCatalog' => array(
			'@type'           => 'OfferCatalog',
			'name'            => 'Palvelut',
			'itemListElement' => array_map( function ( $s ) {
				return array( '@type' => 'Offer', 'itemOffered' => array( '@type' => 'Service', 'name' => $s ) );
			}, array( 'Ikkunoiden myynti ja asennus', 'Ulko-ovien myynti ja asennus', 'Ikkunaremontti', 'Oviremontti', 'Ikkunoiden ja ovien huolto', 'Ilmainen mittauskäynti' ) ),
		),
	);
	if ( $business['telephone'] ) {
		$org['telephone'] = $business['telephone'];
	}
	if ( $business['streetAddress'] ) {
		$org['address']['streetAddress'] = $business['streetAddress'];
	}
	if ( $business['postalCode'] ) {
		$org['address']['postalCode'] = $business['postalCode'];
	}

	$website = array(
		'@type'      => 'WebSite',
		'@id'        => $site_id,
		'url'        => $home,
		'name'       => $business['brand'],
		'alternateName' => $business['name'],
		'inLanguage' => 'fi-FI',
		'publisher'  => array( '@id' => $org_id ),
	);

	$webpage = array(
		'@type'              => 'WebPage',
		'@id'                => $page_id,
		'url'                => $page_url,
		'name'               => $title,
		'isPartOf'           => array( '@id' => $site_id ),
		'about'              => array( '@id' => $org_id ),
		'inLanguage'         => 'fi-FI',
		'datePublished'      => get_the_date( 'c', $post_id ),
		'dateModified'       => get_the_modified_date( 'c', $post_id ),
		'primaryImageOfPage' => array( '@type' => 'ImageObject', 'url' => $image['src'] ),
	);
	if ( $desc ) {
		$webpage['description'] = $desc;
	}
	if ( 'utility' === $type && 'yhteystiedot' === $slug ) {
		$webpage['@type'] = array( 'WebPage', 'ContactPage' );
	}

	$graph = array( $org, $website );

	if ( ! $is_front ) {
		$trail = nordic_breadcrumb_trail( $post_id );
		$items = array();
		foreach ( $trail as $i => $crumb ) {
			$items[] = array(
				'@type'    => 'ListItem',
				'position' => $i + 1,
				'name'     => $crumb['name'],
				'item'     => $crumb['url'],
			);
		}
		$webpage['breadcrumb'] = array( '@id' => $page_url . '#breadcrumb' );
		$graph[]               = array(
			'@type'           => 'BreadcrumbList',
			'@id'             => $page_url . '#breadcrumb',
			'itemListElement' => $items,
		);
	}

	$graph[] = $webpage;

	if ( 'article' === $type ) {
		$graph[] = array(
			'@type'            => 'Article',
			'@id'              => $page_url . '#article',
			'headline'         => nordic_short_title( $post_id ),
			'description'      => $desc,
			'image'            => $image['src'],
			'inLanguage'       => 'fi-FI',
			'datePublished'    => get_the_date( 'c', $post_id ),
			'dateModified'     => get_the_modified_date( 'c', $post_id ),
			'author'           => array( '@id' => $org_id ),
			'publisher'        => array( '@id' => $org_id ),
			'isPartOf'         => array( '@id' => $page_id ),
			'mainEntityOfPage' => array( '@id' => $page_id ),
		);
	}

	if ( in_array( $type, array( 'city', 'service', 'product' ), true ) && ! in_array( $slug, array( 'sivukartta' ), true ) ) {
		$service = array(
			'@type'       => 'Service',
			'@id'         => $page_url . '#service',
			'name'        => nordic_short_title( $post_id ),
			'url'         => $page_url,
			'provider'    => array( '@id' => $org_id ),
			'brand'       => array( '@type' => 'Brand', 'name' => 'Skaala' ),
			'areaServed'  => array( '@type' => 'AdministrativeArea', 'name' => nordic_region()['region'] ),
		);
		if ( $desc ) {
			$service['description'] = $desc;
		}
		$city = nordic_city_from_slug( $slug );
		if ( $city ) {
			$service['areaServed'] = array( '@type' => 'City', 'name' => $city );
		}
		$graph[] = $service;
	}

	echo '<script type="application/ld+json">' . wp_json_encode( array( '@context' => 'https://schema.org', '@graph' => $graph ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) . '</script>' . "\n";

	// Sivukohtainen skeema (FAQPage), tallennettu tuonnissa valmiina <script>-tagina.
	$schema = get_post_meta( $post_id, '_nordic_schema_json', true );
	if ( ! $schema ) {
		$schema = nordic_faq_schema_from_content( $post_id );
	}
	if ( $schema ) {
		echo $schema . "\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}
}
add_action( 'wp_head', 'nordic_head_meta', 5 );

/**
 * FAQPage-skeema sivun omista UKK-lohkoista (<div class="faq-item"><h3>…</h3><p>…</p></div>).
 * Uusille sivuille, joilla ei ole tuonnissa tallennettua skeemaa.
 */
function nordic_faq_schema_from_content( $post_id ) {
	$content = (string) get_post_field( 'post_content', $post_id );
	if ( ! preg_match_all( '#<div class="faq-item"[^>]*>\s*<h3>(.*?)</h3>\s*<p>(.*?)</p>#is', $content, $m, PREG_SET_ORDER ) ) {
		return '';
	}
	$items = array();
	foreach ( $m as $row ) {
		$q = trim( html_entity_decode( wp_strip_all_tags( $row[1] ), ENT_QUOTES, 'UTF-8' ) );
		$a = trim( html_entity_decode( wp_strip_all_tags( $row[2] ), ENT_QUOTES, 'UTF-8' ) );
		if ( $q && $a ) {
			$items[] = array(
				'@type'          => 'Question',
				'name'           => $q,
				'acceptedAnswer' => array( '@type' => 'Answer', 'text' => $a ),
			);
		}
	}
	if ( ! $items ) {
		return '';
	}
	return '<script type="application/ld+json">' . wp_json_encode( array( '@context' => 'https://schema.org', '@type' => 'FAQPage', 'mainEntity' => $items ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) . '</script>';
}

/**
 * Vanhat sivukohtaiset tyylit — vain jos NORDIC_LEGACY_PAGE_CSS on päällä.
 */
function nordic_page_css() {
	if ( ! NORDIC_LEGACY_PAGE_CSS || ! is_singular( 'page' ) ) {
		return;
	}
	$css = get_post_meta( get_the_ID(), '_nordic_page_css', true );
	if ( $css ) {
		echo '<style>' . $css . '</style>' . "\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}
}
add_action( 'wp_head', 'nordic_page_css', 100 );

/**
 * Kopio-etusivu pois XML-sivukartasta.
 */
function nordic_sitemap_exclude( $args, $post_type ) {
	if ( 'page' !== $post_type ) {
		return $args;
	}
	$exclude = array();
	foreach ( array_merge( nordic_noindex_slugs(), nordic_hidden_slugs() ) as $slug ) {
		$page = get_page_by_path( $slug );
		if ( $page ) {
			$exclude[] = $page->ID;
		}
	}
	if ( $exclude ) {
		$args['post__not_in'] = isset( $args['post__not_in'] ) ? array_merge( (array) $args['post__not_in'], $exclude ) : $exclude;
	}
	return $args;
}
add_filter( 'wp_sitemaps_posts_query_args', 'nordic_sitemap_exclude', 10, 2 );

/* =========================================================================
 * AI-hakukoneet: robots.txt ja /llms.txt
 * ========================================================================= */

/**
 * robots.txt: sivukartta näkyviin ja AI-hakurobotit sallittu nimeltä.
 * (Toimii, kun palvelimen juuressa ei ole erillistä robots.txt-tiedostoa.)
 */
function nordic_robots_txt( $output, $public ) {
	if ( ! $public ) {
		return $output;
	}
	// Sivusto sallii kaikki robotit (myös AI-haut: GPTBot, ClaudeBot, PerplexityBot,
	// Google-Extended ym.), joten erillisiä User-agent-ryhmiä ei tarvita.
	if ( strpos( $output, 'Sitemap:' ) === false ) {
		$output .= "\nSitemap: " . home_url( '/wp-sitemap.xml' ) . "\n";
	}
	$output .= '# Tiivis kuvaus sivustosta kielimalleille (llms.txt): ' . home_url( '/llms.txt' ) . "\n";
	return $output;
}
add_filter( 'robots_txt', 'nordic_robots_txt', 20, 2 );

/**
 * /llms.txt — koneluettava yhteenveto sivustosta ja sen tärkeimmistä sivuista
 * (llmstxt.org-muoto). Auttaa ChatGPT:tä, Claudea, Perplexityä ym. löytämään
 * ja lainaamaan oikeat sivut. Generoidaan automaattisesti julkaistuista sivuista.
 */
function nordic_llms_txt() {
	$path = wp_parse_url( isset( $_SERVER['REQUEST_URI'] ) ? wp_unslash( $_SERVER['REQUEST_URI'] ) : '', PHP_URL_PATH ); // phpcs:ignore
	$home = wp_parse_url( home_url( '/' ), PHP_URL_PATH );
	if ( rtrim( (string) $home, '/' ) . '/llms.txt' !== $path ) {
		return;
	}

	$business = nordic_business_info();
	$pages    = get_pages( array( 'post_status' => 'publish', 'sort_column' => 'menu_order,post_title' ) );
	$groups   = array(
		'service' => array( 'title' => 'Palvelut ja kohderyhmät', 'items' => array() ),
		'product' => array( 'title' => 'Tuotteet (Skaala-ikkunat ja -ovet)', 'items' => array() ),
		'article' => array( 'title' => 'Oppaat ja hinnat', 'items' => array() ),
		'city'    => array( 'title' => 'Paikkakunnat', 'items' => array() ),
		'utility' => array( 'title' => 'Muut', 'items' => array() ),
	);
	foreach ( $pages as $page ) {
		if ( in_array( $page->post_name, array_merge( nordic_noindex_slugs(), nordic_hidden_slugs() ), true ) ) {
			continue;
		}
		$type = nordic_page_type( $page->post_name );
		if ( 'front' === $type ) {
			continue;
		}
		$desc = nordic_meta_description( $page->ID );
		$line = '- [' . nordic_short_title( $page->ID ) . '](' . get_permalink( $page ) . ')' . ( $desc ? ': ' . $desc : '' );
		$groups[ $type ]['items'][] = $line;
	}

	$out  = '# ' . $business['brand'] . ' – ' . $business['name'] . "\n\n";
	$out .= '> ' . $business['description'] . " Valtuutettu Skaala-jälleenmyyjä: Suomessa (Ylihärmässä) mittatilaustyönä valmistetut ikkunat ja ulko-ovet asennettuna tai ilman asennusta.\n\n";
	$out .= "Perustiedot:\n";
	$out .= '- Yritys: ' . $business['name'] . "\n";
	$out .= '- Toiminta-alue: ' . nordic_region()['area_text'] . ' (' . implode( ', ', nordic_region()['cities'] ) . ")\n";
	$out .= "- Palveluaika: arkisin klo 8–17\n";
	$out .= '- Sähköposti: ' . $business['email'] . "\n";
	if ( $business['telephone'] ) {
		$out .= '- Puhelin: ' . $business['telephone'] . "\n";
	}
	$out .= "- Tarjouspyyntö: ilmainen mittauskäynti ja kirjallinen, sitoumukseton tarjous — " . home_url( '/yhteystiedot/' ) . "\n\n";

	foreach ( $groups as $group ) {
		if ( ! $group['items'] ) {
			continue;
		}
		$out .= '## ' . $group['title'] . "\n\n" . implode( "\n", $group['items'] ) . "\n\n";
	}

	status_header( 200 );
	header( 'Content-Type: text/plain; charset=utf-8' );
	header( 'X-Robots-Tag: noindex' );
	echo $out; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	exit;
}
add_action( 'parse_request', 'nordic_llms_txt', 1 );

/* =========================================================================
 * Ylläpidon apurit (ennallaan versiosta 1)
 * ========================================================================= */

function nordic_maybe_set_front_page() {
	$needs_front = ( get_option( 'show_on_front' ) !== 'page' );
	if ( ! $needs_front ) {
		$current_id = (int) get_option( 'page_on_front' );
		if ( ! $current_id || get_post_status( $current_id ) === false ) {
			$needs_front = true;
		}
	}
	if ( $needs_front ) {
		$front = get_page_by_path( 'etusivu-sisalto' );
		if ( $front ) {
			update_option( 'show_on_front', 'page' );
			update_option( 'page_on_front', $front->ID );
		}
	}
}
add_action( 'admin_init', 'nordic_maybe_set_front_page' );

function nordic_maybe_assign_primary_menu() {
	$locations = get_theme_mod( 'nav_menu_locations' );
	if ( empty( $locations['primary'] ) ) {
		$menu = get_term_by( 'name', 'Päävalikko', 'nav_menu' );
		if ( $menu ) {
			$locations['primary'] = $menu->term_id;
			set_theme_mod( 'nav_menu_locations', $locations );
		}
	}
}
add_action( 'admin_init', 'nordic_maybe_assign_primary_menu' );

function nordic_widgets_init() {
	register_sidebar( array(
		'name'          => __( 'Footer', 'nordic' ),
		'id'            => 'footer-1',
		'before_widget' => '<div class="footer-col">',
		'after_widget'  => '</div>',
		'before_title'  => '<h4>',
		'after_title'   => '</h4>',
	) );
}
add_action( 'widgets_init', 'nordic_widgets_init' );

/* =========================================================================
 * Tarjouslomake (modaali) — käytetään sivuilla, joilla ei ole omaa lomaketta
 * ========================================================================= */

function nordic_quote_form_html() {
	$shortcode = '[fluentform id="' . nordic_quote_form_id() . '"]';
	if ( shortcode_exists( 'fluentform' ) ) {
		return do_shortcode( $shortcode );
	}
	return '<p>Lähetä tarjouspyyntö sähköpostilla: <a href="mailto:info@ikkunakauppias.fi">info@ikkunakauppias.fi</a></p>';
}

/**
 * Onko sivun sisällössä jo oma tarjouslomake (etusivu, yhteystiedot)?
 */
function nordic_page_has_form() {
	if ( ! is_singular( 'page' ) ) {
		return false;
	}
	$content = (string) get_post_field( 'post_content', get_the_ID() );
	return strpos( $content, '[fluentform' ) !== false || strpos( $content, '[nordic_tarjouslomake' ) !== false;
}
