<?php
/**
 * Teeman perusasetukset, valikot, tyylit ja skriptit.
 *
 * @package sami-elmeranta
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'after_setup_theme', function () {
	load_theme_textdomain( 'sami-elmeranta', SE_DIR . '/languages' );

	add_theme_support( 'title-tag' );
	add_theme_support( 'automatic-feed-links' );
	add_theme_support( 'post-thumbnails' );
	add_theme_support( 'responsive-embeds' );
	add_theme_support( 'custom-logo', array( 'height' => 80, 'width' => 80, 'flex-width' => true, 'flex-height' => true ) );
	add_theme_support( 'html5', array( 'search-form', 'comment-form', 'comment-list', 'gallery', 'caption', 'style', 'script', 'navigation-widgets' ) );

	// Classic Editorin tyylit vastaamaan sivustoa.
	add_theme_support( 'editor-styles' );
	add_editor_style( array( 'assets/css/editor.css' ) );

	register_nav_menus( array(
		'primary' => 'Päävalikko',
		'footer'  => 'Alavalikko',
	) );

	set_post_thumbnail_size( 1200, 630, true );
} );

add_action( 'wp_enqueue_scripts', function () {
	$css = SE_DIR . '/assets/css/main.css';
	$js  = SE_DIR . '/assets/js/motion.js';
	wp_enqueue_style( 'se-main', get_theme_file_uri( 'assets/css/main.css' ), array(), file_exists( $css ) ? (string) filemtime( $css ) : SE_VERSION );
	wp_enqueue_script( 'se-motion', get_theme_file_uri( 'assets/js/motion.js' ), array(), file_exists( $js ) ? (string) filemtime( $js ) : SE_VERSION, array( 'strategy' => 'defer', 'in_footer' => true ) );

	if ( is_singular() && comments_open() && get_option( 'thread_comments' ) ) {
		wp_enqueue_script( 'comment-reply' );
	}

	// Lohkotyylit jätetään: vanhat artikkelit sisältävät lohkoeditorin lohkoja.
}, 20 );

// Fonttien esilataus.
add_action( 'wp_head', function () {
	foreach ( array( 'inter-latin-wght-normal.woff2', 'bricolage-grotesque-latin-wght-normal.woff2' ) as $font ) {
		printf( '<link rel="preload" href="%s" as="font" type="font/woff2" crossorigin>' . "\n", esc_url( get_theme_file_uri( 'assets/fonts/' . $font ) ) );
	}
	echo '<meta name="theme-color" content="#1d1934">' . "\n";
}, 2 );

// Ote-pituus ja -pääte.
add_filter( 'excerpt_length', function () {
	return 32;
} );
add_filter( 'excerpt_more', function () {
	return '…';
} );

// Ulkoisille linkeille sisällössä rel="noopener noreferrer".
add_filter( 'the_content', function ( $content ) {
	if ( false === stripos( $content, 'target="_blank"' ) ) {
		return $content;
	}
	return preg_replace_callback( '/<a\s[^>]*target="_blank"[^>]*>/i', function ( $m ) {
		if ( preg_match( '/\srel=/i', $m[0] ) ) {
			return $m[0];
		}
		return str_replace( '<a ', '<a rel="noopener noreferrer" ', $m[0] );
	}, $content );
}, 20 );

/**
 * Valikon fallback, jos valikkoa ei ole vielä luotu.
 *
 * @return array Linkit [otsikko, url].
 */
function se_default_nav() {
	return array(
		array( 'Politiikka', se_page_url( 'politiikkaa' ) ),
		array( 'Työelämä', se_page_url( 'tyoelamayrittajyys' ) ),
		array( 'Arki', se_page_url( 'arkivapaa-aika' ) ),
		array( 'Blogi', se_blog_url() ),
	);
}

/**
 * Tulosta valikko: WP-valikko jos asetettu, muuten oletuslinkit.
 *
 * @param string $location Valikon sijainti.
 * @param string $variant  desktop | mobile | footer.
 */
function se_nav( $location, $variant ) {
	$classes = array(
		'desktop' => 'relative rounded-full px-4 py-2 font-medium text-ink/80 transition hover:text-ink aria-[current=page]:text-brand-dark after:absolute after:inset-x-4 after:-bottom-0.5 after:h-0.5 after:origin-left after:scale-x-0 after:rounded-full after:bg-brand after:transition-transform after:duration-300 hover:after:scale-x-100 aria-[current=page]:after:scale-x-100',
		'mobile'  => 'flex items-center justify-between border-b border-line py-4 font-display text-2xl font-bold aria-[current=page]:text-brand-dark',
		'footer'  => 'text-white/80 transition hover:text-brand-light',
	);
	$items = array();
	$locs  = get_nav_menu_locations();
	if ( ! empty( $locs[ $location ] ) ) {
		$menu = wp_get_nav_menu_items( $locs[ $location ] );
		foreach ( (array) $menu as $item ) {
			if ( 0 === (int) $item->menu_item_parent ) {
				$items[] = array( $item->title, $item->url );
			}
		}
	}
	if ( ! $items ) {
		$items = se_default_nav();
	}
	if ( 'desktop' !== $variant ) {
		$items[] = array( 'Ota yhteyttä', se_contact_url() );
	}
	$current = '';
	if ( is_home() ) {
		$current = se_blog_url();
	} elseif ( is_singular() ) {
		$current = (string) get_permalink();
	}
	foreach ( $items as $item ) {
		$is_current = untrailingslashit( $item[1] ) === untrailingslashit( $current );
		printf(
			'<li><a href="%1$s" class="%2$s"%3$s>%4$s%5$s</a></li>',
			esc_url( $item[1] ),
			esc_attr( $classes[ $variant ] ),
			$is_current ? ' aria-current="page"' : '',
			esc_html( $item[0] ),
			'mobile' === $variant ? se_icon( 'lucide:arrow-right', 'size-5 text-brand' ) : '' // phpcs:ignore
		);
	}
}
