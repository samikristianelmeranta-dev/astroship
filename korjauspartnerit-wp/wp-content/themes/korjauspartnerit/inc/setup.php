<?php
/**
 * Teeman perusasetukset ja tiedostojen lataus.
 */

defined( 'ABSPATH' ) || exit;

add_action( 'after_setup_theme', function () {
	load_theme_textdomain( 'korjauspartnerit', get_template_directory() . '/languages' );

	add_theme_support( 'title-tag' );
	add_theme_support( 'post-thumbnails' );
	add_theme_support( 'automatic-feed-links' );
	add_theme_support( 'html5', array( 'search-form', 'gallery', 'caption', 'style', 'script', 'navigation-widgets' ) );
	add_theme_support( 'responsive-embeds' );
	add_theme_support( 'custom-logo', array( 'height' => 60, 'width' => 320, 'flex-width' => true, 'flex-height' => true ) );

	add_theme_support( 'editor-styles' );
	add_editor_style( array( 'assets/css/perus.css', 'assets/css/sisalto.css', 'assets/css/editori.css' ) );

	register_nav_menus( array(
		'paavalikko' => 'Päävalikko',
		'alavalikko' => 'Alatunnisteen valikko',
	) );

	add_image_size( 'kp-hero', 2000, 1000, true );
	add_image_size( 'kp-kortti', 800, 560, true );
	add_image_size( 'kp-henkilo', 600, 600, true );

	$GLOBALS['content_width'] = 760;
} );

add_filter( 'image_size_names_choose', function ( $sizes ) {
	return array_merge( $sizes, array( 'kp-kortti' => 'Korttikuva (3:2)' ) );
} );

add_action( 'wp_enqueue_scripts', function () {
	$uri = get_template_directory_uri();
	wp_enqueue_style( 'kp-perus', $uri . '/assets/css/perus.css', array(), KP_TEEMA_VERSION );
	wp_enqueue_style( 'kp-sisalto', $uri . '/assets/css/sisalto.css', array( 'kp-perus' ), KP_TEEMA_VERSION );
	wp_enqueue_style( 'kp-main', $uri . '/assets/css/main.css', array( 'kp-sisalto' ), KP_TEEMA_VERSION );
	wp_enqueue_script( 'kp-main', $uri . '/assets/js/main.js', array(), KP_TEEMA_VERSION, array( 'strategy' => 'defer', 'in_footer' => true ) );

	// Lohkoeditorin tyylejä ei tarvita, koska sivusto käyttää klassista editoria.
	wp_dequeue_style( 'wp-block-library' );
	wp_dequeue_style( 'wp-block-library-theme' );
	wp_dequeue_style( 'classic-theme-styles' );
	wp_dequeue_style( 'global-styles' );
}, 20 );

add_action( 'wp_head', function () {
	printf( '<link rel="preload" href="%s" as="font" type="font/woff2" crossorigin>' . "\n", esc_url( get_template_directory_uri() . '/assets/fonts/montserrat-latin.woff2' ) );
	echo '<meta name="theme-color" content="#004b59">' . "\n";
}, 1 );

remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
remove_action( 'wp_print_styles', 'print_emoji_styles' );
remove_action( 'wp_head', 'wp_generator' );

/**
 * Favicon liikemerkistä, jos sivustokuvaketta ei ole asetettu.
 */
add_action( 'wp_head', function () {
	if ( ! has_site_icon() ) {
		printf( '<link rel="icon" href="%s" type="image/svg+xml">' . "\n", esc_url( get_template_directory_uri() . '/assets/img/favicon.svg' ) );
	}
} );

/**
 * Meta description, jos SEO-lisäosaa (Yoast, Rank Math, SEOPress) ei ole käytössä.
 */
add_action( 'wp_head', function () {
	if ( defined( 'WPSEO_VERSION' ) || defined( 'RANK_MATH_VERSION' ) || defined( 'SEOPRESS_VERSION' ) ) {
		return;
	}
	$desc = '';
	if ( is_singular() ) {
		$desc = function_exists( 'kp_excerpt' ) ? kp_excerpt( get_queried_object(), 30 ) : get_the_excerpt();
	} elseif ( is_front_page() || is_home() ) {
		$desc = get_bloginfo( 'description' );
	}
	if ( $desc ) {
		printf( '<meta name="description" content="%s">' . "\n", esc_attr( wp_strip_all_tags( $desc ) ) );
	}
}, 2 );

add_filter( 'body_class', function ( $classes ) {
	if ( kp_has_image_hero() ) {
		$classes[] = 'kp-has-image-hero';
	}
	if ( is_page_template( 'templates/sivupohja-laskeutumissivu.php' ) ) {
		$classes[] = 'kp-laskeutumissivu';
	}
	return $classes;
} );

add_filter( 'excerpt_length', function () {
	return 24;
} );

add_filter( 'excerpt_more', function () {
	return '…';
} );

/**
 * Hakuun mukaan kaikki julkiset sisältötyypit.
 */
add_action( 'pre_get_posts', function ( $q ) {
	if ( ! is_admin() && $q->is_main_query() && $q->is_search() ) {
		$q->set( 'post_type', array( 'page', 'post', 'palvelu', 'referenssi', 'henkilokunta', 'tyopaikka' ) );
		$q->set( 'posts_per_page', 20 );
	}
	if ( ! is_admin() && $q->is_main_query() && ( $q->is_post_type_archive( 'referenssi' ) || $q->is_tax( 'referenssityyppi' ) || $q->is_post_type_archive( 'palvelu' ) || $q->is_tax( 'palvelualue' ) || $q->is_tax( 'kohderyhma' ) ) ) {
		$q->set( 'posts_per_page', -1 );
		$q->set( 'orderby', array( 'menu_order' => 'ASC', 'title' => 'ASC' ) );
	}
} );

/**
 * YouTube- ym. upotukset responsiivisiksi.
 */
add_filter( 'embed_oembed_html', function ( $html, $url ) {
	if ( preg_match( '#youtube|youtu\.be|vimeo#', $url ) ) {
		return '<div class="kp-upotus">' . $html . '</div>';
	}
	return $html;
}, 10, 2 );

/**
 * Teema tarvitsee Työkalut-lisäosan. Ilmoitus hallintaan, jos se puuttuu.
 */
add_action( 'admin_notices', function () {
	if ( ! function_exists( 'kp_person' ) && current_user_can( 'activate_plugins' ) ) {
		echo '<div class="notice notice-warning"><p><strong>Korjauspartnerit-teema:</strong> ota käyttöön lisäosa <em>Korjauspartnerit – Työkalut</em>, jotta henkilökunta, palvelut, referenssit ja lomakkeet toimivat.</p></div>';
	}
} );
