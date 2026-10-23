<?php
/**
 * Sivupohjissa käytettävät apufunktiot.
 */

defined( 'ABSPATH' ) || exit;

/**
 * Asetus Työkalut-lisäosasta, varmistettuna jos lisäosa ei ole käytössä.
 */
function kp_t_setting( $key, $fallback = '' ) {
	return function_exists( 'kp_setting' ) ? kp_setting( $key ) : $fallback;
}

/**
 * Logo inline-SVG:nä, jotta väri seuraa ympäristöä (turkoosi, valkoinen tai musta).
 * Ohjeistuksen mukaan logoa ei muokata – väri vaihdetaan vain sallittuihin versioihin.
 *
 * @param string $variant vaaka|pino|liikemerkki
 */
function kp_logo( $variant = 'vaaka', $class = '' ) {
	$file = get_template_directory() . '/assets/img/logo-' . $variant . '.svg';
	if ( 'liikemerkki' === $variant ) {
		$file = get_template_directory() . '/assets/img/liikemerkki.svg';
	}
	if ( ! file_exists( $file ) ) {
		return '';
	}
	$svg = file_get_contents( $file ); // phpcs:ignore WordPress.WP.AlternativeFunctions
	return str_replace( '<svg ', '<svg class="kp-logo kp-logo--' . esc_attr( $variant ) . ' ' . esc_attr( $class ) . '" focusable="false" ', trim( $svg ) );
}

function kp_hero_style( $post = null ) {
	$post = get_post( $post );
	if ( ! $post ) {
		return 'turkoosi';
	}
	$style = get_post_meta( $post->ID, 'kp_hero_tyyli', true );
	if ( '' === $style ) {
		$style = kp_t_thumb( $post ) && in_array( $post->post_type, array( 'page', 'palvelu', 'referenssi', 'tyopaikka' ), true ) ? 'kuva' : 'turkoosi';
	}
	if ( 'kuva' === $style && ! kp_t_thumb( $post ) ) {
		$style = 'turkoosi';
	}
	return $style;
}

/**
 * Onko sivulla kuvallinen yläosa (ylätunniste läpinäkyvä kuvan päällä).
 */
function kp_has_image_hero() {
	if ( is_front_page() ) {
		return true;
	}
	if ( is_singular( array( 'page', 'palvelu', 'referenssi', 'tyopaikka' ) ) ) {
		return 'kuva' === kp_hero_style( get_queried_object_id() );
	}
	return false;
}

/**
 * Murupolku.
 */
function kp_breadcrumbs() {
	if ( is_front_page() ) {
		return;
	}
	$items = array( array( home_url( '/' ), 'Etusivu' ) );

	if ( is_singular( 'post' ) ) {
		$blog = get_option( 'page_for_posts' );
		if ( $blog ) {
			$items[] = array( get_permalink( $blog ), get_the_title( $blog ) );
		}
	} elseif ( is_singular( array( 'palvelu', 'referenssi', 'tyopaikka' ) ) ) {
		$type = get_post_type_object( get_post_type() );
		if ( 'tyopaikka' === $type->name && function_exists( 'kp_page_id' ) && kp_page_id( 'korjauspartnerit-tyopaikkana' ) ) {
			$id      = kp_page_id( 'korjauspartnerit-tyopaikkana' );
			$items[] = array( get_permalink( $id ), get_the_title( $id ) );
		} else {
			$items[] = array( get_post_type_archive_link( $type->name ), $type->labels->name );
		}
	} elseif ( is_singular( 'henkilokunta' ) ) {
		$page = get_page_by_path( 'henkilokunta' );
		if ( $page ) {
			$items[] = array( get_permalink( $page ), get_the_title( $page ) );
		}
	} elseif ( is_page() ) {
		foreach ( array_reverse( get_post_ancestors( get_queried_object_id() ) ) as $anc ) {
			$items[] = array( get_permalink( $anc ), get_the_title( $anc ) );
		}
	} elseif ( is_tax() || is_category() || is_tag() ) {
		$term = get_queried_object();
		$tax  = get_taxonomy( $term->taxonomy );
		if ( ! empty( $tax->object_type[0] ) && get_post_type_archive_link( $tax->object_type[0] ) ) {
			$pto     = get_post_type_object( $tax->object_type[0] );
			$items[] = array( get_post_type_archive_link( $pto->name ), $pto->labels->name );
		} elseif ( get_option( 'page_for_posts' ) ) {
			$items[] = array( get_permalink( get_option( 'page_for_posts' ) ), get_the_title( get_option( 'page_for_posts' ) ) );
		}
	}

	echo '<nav class="kp-murupolku" aria-label="Murupolku"><ol>';
	foreach ( $items as $item ) {
		printf( '<li><a href="%s">%s</a></li>', esc_url( $item[0] ), esc_html( $item[1] ) );
	}
	echo '<li aria-current="page">' . esc_html( wp_strip_all_tags( kp_current_title() ) ) . '</li>';
	echo '</ol></nav>';
}

function kp_current_title() {
	if ( is_home() ) {
		$blog = get_option( 'page_for_posts' );
		return $blog ? get_the_title( $blog ) : 'Ajankohtaista';
	}
	if ( is_search() ) {
		return 'Haku';
	}
	if ( is_404() ) {
		return 'Sivua ei löytynyt';
	}
	if ( is_post_type_archive() ) {
		return post_type_archive_title( '', false );
	}
	if ( is_archive() ) {
		return single_term_title( '', false ) ?: get_the_archive_title();
	}
	return get_the_title();
}

/**
 * Sivun yläosa (hero).
 *
 * @param array $args title, kicker, lead, image_id, style (kuva|turkoosi|hiekka|ei), buttons [[teksti, url, luokka]], extra (html)
 */
function kp_page_hero( $args = array() ) {
	$args = wp_parse_args( $args, array(
		'title'    => kp_current_title(),
		'kicker'   => '',
		'lead'     => '',
		'image_id' => 0,
		'style'    => 'turkoosi',
		'buttons'  => array(),
		'extra'    => '',
		'crumbs'   => true,
	) );
	get_template_part( 'template-parts/hero', null, $args );
}

/**
 * Hero yksittäiselle sivulle/palvelulle sen omista kentistä.
 */
function kp_singular_hero( $extra = array() ) {
	$post  = get_post();
	$title = get_post_meta( $post->ID, 'kp_hero_otsikko', true );
	$btn_t = get_post_meta( $post->ID, 'kp_painike_teksti', true );
	$btn_u = get_post_meta( $post->ID, 'kp_painike_linkki', true );
	$args  = array_merge( array(
		'title'    => $title ? $title : get_the_title( $post ),
		'lead'     => get_post_meta( $post->ID, 'kp_ingressi', true ),
		'image_id' => kp_t_thumb( $post ),
		'style'    => kp_hero_style( $post ),
		'buttons'  => $btn_t ? array( array( $btn_t, $btn_u ? $btn_u : '#viesti', '' ) ) : array(),
	), $extra );
	kp_page_hero( $args );
}

/**
 * Sivun loppuun valitut yhteyshenkilöt ja/tai yhteydenottolomake.
 */
function kp_after_content( $post = null ) {
	$post = get_post( $post );
	if ( ! $post || ! function_exists( 'kp_render_people' ) ) {
		return;
	}
	$ids = array_filter( array_map( 'absint', (array) get_post_meta( $post->ID, 'kp_yhteyshenkilot', true ) ) );
	if ( $ids ) {
		$people = kp_query_people( array( 'ids' => $ids ) );
		if ( $people ) {
			echo '<section class="kp-osio kp-osio--yhteyshenkilot"><div class="kp-kehys"><h2 class="kp-osio__otsikko">Yhteyshenkilöt</h2>';
			echo kp_render_people( $people ); // phpcs:ignore WordPress.Security.EscapeOutput
			echo '</div></section>';
		}
	}
	if ( get_post_meta( $post->ID, 'kp_nayta_lomake', true ) && false === strpos( $post->post_content, '[kp_yhteydenotto' ) ) {
		echo '<section class="kp-osio kp-osio--hiekka kp-osio--lomake"><div class="kp-kehys kp-kehys--kapea"><h2 class="kp-osio__otsikko">Ota yhteyttä</h2>';
		echo do_shortcode( '[kp_yhteydenotto]' );
		echo '</div></section>';
	}
}

function kp_social_links( $class = '' ) {
	$out = '';
	foreach ( array( 'linkedin' => 'LinkedIn', 'facebook' => 'Facebook', 'instagram' => 'Instagram', 'youtube' => 'YouTube' ) as $key => $label ) {
		$url = kp_t_setting( $key );
		if ( $url ) {
			$out .= sprintf( '<li><a href="%s" target="_blank" rel="noopener"><span class="screen-reader-text">%s</span>%s</a></li>', esc_url( $url ), esc_html( $label ), kp_icon( $key ) );
		}
	}
	return $out ? '<ul class="kp-some ' . esc_attr( $class ) . '">' . $out . '</ul>' : '';
}

/**
 * Alavalikon puutteessa tulostettavat oletuslinkit.
 */
function kp_footer_fallback_menu() {
	echo '<ul class="kp-alavalikko">';
	foreach ( array( 'yhteystiedot' => 'Yhteystiedot', 'henkilokunta' => 'Henkilökunta', 'tietosuojaseloste' => 'Tietosuojaseloste' ) as $slug => $label ) {
		$page = get_page_by_path( $slug );
		if ( $page ) {
			printf( '<li><a href="%s">%s</a></li>', esc_url( get_permalink( $page ) ), esc_html( $label ) );
		}
	}
	echo '</ul>';
}

/**
 * Lisää alavalikon avauspainikkeen valikkokohtiin, joilla on alakohtia.
 */
add_filter( 'walker_nav_menu_start_el', function ( $output, $item, $depth, $args ) {
	if ( isset( $args->theme_location ) && 'paavalikko' === $args->theme_location && in_array( 'menu-item-has-children', (array) $item->classes, true ) && 0 === $depth ) {
		$output .= '<button class="kp-alavalikko-avaa" aria-expanded="false" aria-label="' . esc_attr( 'Avaa alavalikko: ' . $item->title ) . '">' . kp_icon( 'alas' ) . '</button>';
	}
	return $output;
}, 10, 4 );

/**
 * Sivunumerointi.
 */
function kp_pagination() {
	the_posts_pagination( array(
		'mid_size'           => 1,
		'prev_text'          => '‹ <span class="screen-reader-text">Edellinen sivu</span>',
		'next_text'          => '<span class="screen-reader-text">Seuraava sivu</span> ›',
		'before_page_number' => '<span class="screen-reader-text">Sivu </span>',
	) );
}

/**
 * Sisältötyypin nimi hakutuloksiin.
 */
function kp_post_type_label( $post = null ) {
	$type = get_post_type( $post );
	$map  = array( 'post' => 'Artikkeli', 'page' => 'Sivu', 'palvelu' => 'Palvelu', 'referenssi' => 'Referenssi', 'henkilokunta' => 'Henkilö', 'tyopaikka' => 'Työpaikka' );
	return isset( $map[ $type ] ) ? $map[ $type ] : '';
}

/*
 * Varafunktiot, jos Työkalut-lisäosa ei ole käytössä (estää virheet).
 */
if ( ! function_exists( 'kp_tel_href' ) ) {
	function kp_tel_href( $phone ) {
		return 'tel:' . preg_replace( '/[^0-9+]/', '', (string) $phone );
	}
}

/**
 * Olemassa olevan artikkelikuvan ID (tai 0).
 */
function kp_t_thumb( $post = null ) {
	return function_exists( 'kp_thumb_id' ) ? kp_thumb_id( $post ) : (int) get_post_thumbnail_id( $post );
}
