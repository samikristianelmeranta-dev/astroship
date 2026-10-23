<?php
/**
 * Lyhytkoodit, joilla henkilökunta voi lisätä dynaamisia listauksia mille tahansa sivulle.
 *
 * [kp_henkilohaku]                         Haettava ja suodatettava henkilöhakemisto
 * [kp_henkilot nimet="" hakusana="" toimipiste="" johto="1" maara=""]
 * [kp_palvelut asiakasryhma="" palvelualue="" ryhmittele="1" maara=""]
 * [kp_referenssit tyyppi="" asiakasryhma="" maara="" suodatin="1"]
 * [kp_ajankohtaiset maara="3" kategoria=""]
 * [kp_tyopaikat]
 * [kp_yhteydenotto otsikko=""]
 * [kp_toimipisteet]
 * [kp_painike linkki="" tyyli=""]Teksti[/kp_painike]
 */

defined( 'ABSPATH' ) || exit;

add_action( 'init', function () {
	add_shortcode( 'kp_henkilohaku', 'kp_sc_henkilohaku' );
	add_shortcode( 'kp_henkilot', 'kp_sc_henkilot' );
	add_shortcode( 'kp_palvelut', 'kp_sc_palvelut' );
	add_shortcode( 'kp_referenssit', 'kp_sc_referenssit' );
	add_shortcode( 'kp_ajankohtaiset', 'kp_sc_ajankohtaiset' );
	add_shortcode( 'kp_tyopaikat', 'kp_sc_tyopaikat' );
	add_shortcode( 'kp_toimipisteet', 'kp_sc_toimipisteet' );
	add_shortcode( 'kp_painike', 'kp_sc_painike' );
} );

add_action( 'wp_enqueue_scripts', function () {
	wp_register_script( 'kp-tyokalut', KP_TYOKALUT_URL . 'assets/tyokalut.js', array(), KP_TYOKALUT_VERSION, true );
} );

/**
 * Lyhyt kuvaus: ingressi → ote → sisällön alku.
 */
function kp_excerpt( $post, $words = 24 ) {
	$post = get_post( $post );
	$txt  = get_post_meta( $post->ID, 'kp_ingressi', true );
	if ( ! $txt ) {
		$txt = $post->post_excerpt;
	}
	if ( ! $txt ) {
		$txt = strip_shortcodes( $post->post_content );
	}
	return wp_trim_words( wp_strip_all_tags( $txt ), $words, '…' );
}

/**
 * Henkilöiden haku. Johto ensin, sitten järjestysnumero ja sukunimi.
 */
function kp_query_people( $args = array() ) {
	$q = array(
		'post_type'      => 'henkilokunta',
		'post_status'    => 'publish',
		'posts_per_page' => -1,
		'no_found_rows'  => true,
	);
	$tax = array();
	if ( ! empty( $args['hakusana'] ) ) {
		$tax[] = array( 'taxonomy' => 'hakusanat', 'field' => 'slug', 'terms' => array_map( 'trim', explode( ',', $args['hakusana'] ) ) );
	}
	if ( ! empty( $args['toimipiste'] ) ) {
		$tax[] = array( 'taxonomy' => 'toimipiste', 'field' => 'slug', 'terms' => array_map( 'trim', explode( ',', $args['toimipiste'] ) ) );
	}
	if ( $tax ) {
		$q['tax_query'] = $tax; // phpcs:ignore WordPress.DB.SlowDBQuery
	}
	if ( ! empty( $args['johto'] ) ) {
		$q['meta_key']   = 'kp_johto'; // phpcs:ignore WordPress.DB.SlowDBQuery
		$q['meta_value'] = '1'; // phpcs:ignore WordPress.DB.SlowDBQuery
	}
	if ( ! empty( $args['ids'] ) ) {
		$q['post__in'] = array_map( 'absint', (array) $args['ids'] );
		$q['orderby']  = 'post__in';
	}
	$posts = get_posts( $q );

	if ( empty( $args['ids'] ) ) {
		usort( $posts, function ( $a, $b ) {
			$ja = (int) get_post_meta( $a->ID, 'kp_johto', true );
			$jb = (int) get_post_meta( $b->ID, 'kp_johto', true );
			if ( $ja !== $jb ) {
				return $jb - $ja;
			}
			if ( $a->menu_order !== $b->menu_order ) {
				return $a->menu_order - $b->menu_order;
			}
			$sa = kp_sort_name( $a->post_title );
			$sb = kp_sort_name( $b->post_title );
			return strcoll( $sa, $sb );
		} );
	}
	if ( ! empty( $args['maara'] ) ) {
		$posts = array_slice( $posts, 0, absint( $args['maara'] ) );
	}
	return $posts;
}

function kp_sort_name( $name ) {
	$parts = preg_split( '/\s+/', trim( $name ) );
	$last  = array_pop( $parts );
	return remove_accents( $last . ' ' . implode( ' ', $parts ) );
}

function kp_sc_henkilot( $atts ) {
	$atts = shortcode_atts( array( 'nimet' => '', 'ids' => '', 'hakusana' => '', 'toimipiste' => '', 'johto' => '', 'maara' => '', 'otsikko' => '' ), $atts, 'kp_henkilot' );

	$args = $atts;
	if ( $atts['ids'] ) {
		$args['ids'] = array_filter( array_map( 'absint', explode( ',', $atts['ids'] ) ) );
	} elseif ( $atts['nimet'] ) {
		$ids = array();
		foreach ( array_map( 'trim', explode( ',', $atts['nimet'] ) ) as $name ) {
			$found = get_posts( array( 'post_type' => 'henkilokunta', 'title' => $name, 'numberposts' => 1, 'fields' => 'ids' ) );
			if ( $found ) {
				$ids[] = $found[0];
			}
		}
		if ( ! $ids ) {
			return '';
		}
		$args['ids'] = $ids;
	}
	$people = kp_query_people( $args );
	if ( ! $people ) {
		return '';
	}
	return kp_render_people( $people, $atts['otsikko'] );
}

function kp_render_people( $people, $title = '' ) {
	$out = $title ? '<h2 class="kp-henkilot__otsikko">' . esc_html( $title ) . '</h2>' : '';
	$out .= '<div class="kp-henkilot">';
	foreach ( $people as $p ) {
		$out .= kp_get_template( 'henkilo-kortti', array( 'henkilo' => kp_person( $p ) ) );
	}
	return $out . '</div>';
}

function kp_sc_henkilohaku( $atts ) {
	$atts   = shortcode_atts( array( 'toimipiste' => '' ), $atts, 'kp_henkilohaku' );
	$people = kp_query_people( array( 'toimipiste' => $atts['toimipiste'] ) );
	wp_enqueue_script( 'kp-tyokalut' );

	$offices  = get_terms( array( 'taxonomy' => 'toimipiste', 'hide_empty' => true ) );
	$keywords = get_terms( array( 'taxonomy' => 'hakusanat', 'hide_empty' => true ) );

	return kp_get_template(
		'henkilohaku',
		array(
			'people'   => array_map( 'kp_person', $people ),
			'offices'  => is_wp_error( $offices ) ? array() : $offices,
			'keywords' => is_wp_error( $keywords ) ? array() : $keywords,
			'initial'  => isset( $_GET['haku'] ) ? sanitize_text_field( wp_unslash( $_GET['haku'] ) ) : '', // phpcs:ignore WordPress.Security.NonceVerification
		)
	);
}

function kp_sc_palvelut( $atts ) {
	$atts = shortcode_atts( array( 'asiakasryhma' => '', 'palvelualue' => '', 'ryhmittele' => '', 'maara' => -1 ), $atts, 'kp_palvelut' );
	$q    = array(
		'post_type'      => 'palvelu',
		'post_status'    => 'publish',
		'posts_per_page' => (int) $atts['maara'],
		'orderby'        => array( 'menu_order' => 'ASC', 'title' => 'ASC' ),
	);
	$tax = array();
	if ( $atts['asiakasryhma'] ) {
		$tax[] = array( 'taxonomy' => 'kohderyhma', 'field' => 'slug', 'terms' => explode( ',', $atts['asiakasryhma'] ) );
	}
	if ( $atts['palvelualue'] ) {
		$tax[] = array( 'taxonomy' => 'palvelualue', 'field' => 'slug', 'terms' => explode( ',', $atts['palvelualue'] ) );
	}
	if ( $tax ) {
		$q['tax_query'] = $tax; // phpcs:ignore WordPress.DB.SlowDBQuery
	}
	$posts = get_posts( $q );
	if ( ! $posts ) {
		return '';
	}

	if ( ! $atts['ryhmittele'] ) {
		$out = '<div class="kp-kortisto">';
		foreach ( $posts as $p ) {
			$out .= kp_get_template( 'palvelu-kortti', array( 'post' => $p ) );
		}
		return $out . '</div>';
	}

	$groups = array();
	foreach ( $posts as $p ) {
		$terms = get_the_terms( $p, 'palvelualue' );
		$name  = ( $terms && ! is_wp_error( $terms ) ) ? $terms[0]->name : 'Muut palvelut';
		$groups[ $name ][] = $p;
	}
	ksort( $groups );
	$out = '';
	foreach ( $groups as $name => $items ) {
		$out .= '<section class="kp-palveluryhma"><h3 class="kp-palveluryhma__otsikko">' . esc_html( $name ) . '</h3><div class="kp-kortisto">';
		foreach ( $items as $p ) {
			$out .= kp_get_template( 'palvelu-kortti', array( 'post' => $p ) );
		}
		$out .= '</div></section>';
	}
	return $out;
}

function kp_sc_referenssit( $atts ) {
	$atts = shortcode_atts( array( 'tyyppi' => '', 'asiakasryhma' => '', 'maara' => -1, 'suodatin' => '' ), $atts, 'kp_referenssit' );
	$q    = array(
		'post_type'      => 'referenssi',
		'post_status'    => 'publish',
		'posts_per_page' => (int) $atts['maara'],
	);
	$tax = array();
	if ( $atts['tyyppi'] ) {
		$tax[] = array( 'taxonomy' => 'referenssityyppi', 'field' => 'slug', 'terms' => explode( ',', $atts['tyyppi'] ) );
	}
	if ( $atts['asiakasryhma'] ) {
		$tax[] = array( 'taxonomy' => 'kohderyhma', 'field' => 'slug', 'terms' => explode( ',', $atts['asiakasryhma'] ) );
	}
	if ( $tax ) {
		$q['tax_query'] = $tax; // phpcs:ignore WordPress.DB.SlowDBQuery
	}
	$posts = get_posts( $q );
	if ( ! $posts ) {
		return '';
	}
	$out = '';
	if ( $atts['suodatin'] ) {
		wp_enqueue_script( 'kp-tyokalut' );
		$used = array();
		foreach ( $posts as $p ) {
			foreach ( (array) get_the_terms( $p, 'referenssityyppi' ) as $t ) {
				if ( $t && ! is_wp_error( $t ) && $t->parent ) {
					$used[ $t->slug ] = $t->name;
				}
			}
		}
		asort( $used );
		$out .= '<div class="kp-suodatin" data-kp-suodatin=".kp-kortisto--referenssit" role="group" aria-label="Suodata hanketyypin mukaan">';
		$out .= '<button type="button" class="kp-chip is-active" data-arvo="">Kaikki</button>';
		foreach ( $used as $slug => $name ) {
			$out .= '<button type="button" class="kp-chip" data-arvo="' . esc_attr( $slug ) . '">' . esc_html( $name ) . '</button>';
		}
		$out .= '</div>';
	}
	$out .= '<div class="kp-kortisto kp-kortisto--referenssit">';
	foreach ( $posts as $p ) {
		$out .= kp_get_template( 'referenssi-kortti', array( 'post' => $p ) );
	}
	return $out . '</div>';
}

function kp_sc_ajankohtaiset( $atts ) {
	$atts  = shortcode_atts( array( 'maara' => 3, 'kategoria' => '' ), $atts, 'kp_ajankohtaiset' );
	$posts = get_posts( array(
		'post_type'      => 'post',
		'posts_per_page' => absint( $atts['maara'] ),
		'category_name'  => sanitize_title( $atts['kategoria'] ),
	) );
	if ( ! $posts ) {
		return '';
	}
	$out = '<div class="kp-kortisto kp-kortisto--artikkelit">';
	foreach ( $posts as $p ) {
		$out .= kp_get_template( 'artikkeli-kortti', array( 'post' => $p ) );
	}
	return $out . '</div>';
}

/**
 * Voimassa olevat työpaikat (haku ei ole päättynyt).
 */
function kp_active_jobs() {
	$today = current_time( 'Y-m-d' );
	return get_posts( array(
		'post_type'      => 'tyopaikka',
		'post_status'    => 'publish',
		'posts_per_page' => -1,
		'meta_query'     => array( // phpcs:ignore WordPress.DB.SlowDBQuery
			'relation' => 'OR',
			array( 'key' => 'kp_haku_paattyy', 'compare' => 'NOT EXISTS' ),
			array( 'key' => 'kp_haku_paattyy', 'value' => '', 'compare' => '=' ),
			array( 'key' => 'kp_haku_paattyy', 'value' => $today, 'compare' => '>=', 'type' => 'DATE' ),
		),
	) );
}

function kp_sc_tyopaikat() {
	$jobs = kp_active_jobs();
	if ( ! $jobs ) {
		return '<p class="kp-tyhja">Meillä ei ole juuri nyt avoimia työpaikkoja, mutta otamme mielellämme vastaan avoimia hakemuksia.</p>';
	}
	// Avoin hakemus näytetään aina viimeisenä.
	usort( $jobs, function ( $a, $b ) {
		$oa = false !== stripos( $a->post_title, 'avoin hakemus' );
		$ob = false !== stripos( $b->post_title, 'avoin hakemus' );
		if ( $oa !== $ob ) {
			return $oa ? 1 : -1;
		}
		return strcmp( $b->post_date, $a->post_date );
	} );
	$out = '<div class="kp-tyopaikat">';
	foreach ( $jobs as $p ) {
		$out .= kp_get_template( 'tyopaikka-rivi', array( 'post' => $p ) );
	}
	return $out . '</div>';
}

function kp_sc_toimipisteet() {
	return kp_get_template( 'toimipisteet', array( 'offices' => kp_offices() ) );
}

function kp_sc_painike( $atts, $content = '' ) {
	$atts  = shortcode_atts( array( 'linkki' => '#', 'tyyli' => '' ), $atts, 'kp_painike' );
	$class = 'kp-button' . ( $atts['tyyli'] ? ' kp-button--' . sanitize_html_class( $atts['tyyli'] ) : '' );
	return '<a class="' . esc_attr( $class ) . '" href="' . esc_url( $atts['linkki'] ) . '">' . esc_html( $content ? $content : 'Lue lisää' ) . '</a>';
}
