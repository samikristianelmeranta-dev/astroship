<?php
/**
 * Hakukonenäkyvyys.
 *
 * - Rakenteinen data (JSON-LD): sisällön kenttä kp_jsonld (Article, FAQPage, BreadcrumbList)
 *   tulostetaan headiin. Osoitteet korjataan sisällön todelliseen osoitteeseen.
 *   Jos kenttää ei ole, FAQPage muodostetaan automaattisesti sisällön UKK-osiosta
 *   (Lisää lohko → Usein kysytyt kysymykset, tai kp-article__faq-item -rakenne).
 * - Jos SEO-lisäosaa (Yoast, Rank Math, SEOPress) ei ole: title, meta description,
 *   Open Graph ja Twitter-kortti. Yoast-kentät (_yoast_wpseo_title, _yoast_wpseo_metadesc)
 *   huomioidaan, joten tiedot säilyvät, otetaanpa Yoast käyttöön tai ei.
 */

defined( 'ABSPATH' ) || exit;

function kp_seo_plugin_active() {
	return defined( 'WPSEO_VERSION' ) || defined( 'RANK_MATH_VERSION' ) || defined( 'SEOPRESS_VERSION' );
}

/**
 * Korvaa Yoastin muuttujat (%%title%%, %%sep%%, %%sitename%% …), kun Yoast ei ole käytössä.
 */
function kp_seo_replace_vars( $text, $post = null ) {
	$post = get_post( $post );
	$map  = array(
		'%%title%%'    => $post ? get_the_title( $post ) : '',
		'%%sitename%%' => get_bloginfo( 'name' ),
		'%%sep%%'      => '|',
		'%%page%%'     => '',
		'%%primary_category%%' => '',
		'%%excerpt%%'  => $post && function_exists( 'kp_excerpt' ) ? kp_excerpt( $post, 25 ) : '',
	);
	$text = strtr( (string) $text, $map );
	$text = preg_replace( '/%%[a-z_]+%%/', '', $text );
	return trim( preg_replace( '/\s{2,}/', ' ', $text ), " \t|–-" );
}

function kp_seo_description( $post = null ) {
	$post = get_post( $post );
	if ( ! $post ) {
		return get_bloginfo( 'description' );
	}
	$desc = get_post_meta( $post->ID, '_yoast_wpseo_metadesc', true );
	if ( ! $desc ) {
		$desc = function_exists( 'kp_excerpt' ) ? kp_excerpt( $post, 28 ) : get_the_excerpt( $post );
	}
	return wp_strip_all_tags( kp_seo_replace_vars( $desc, $post ) );
}

/* ---------- Title, description, Open Graph (vain ilman SEO-lisäosaa) ---------- */

add_filter( 'pre_get_document_title', function ( $title ) {
	if ( kp_seo_plugin_active() || ! is_singular() ) {
		return $title;
	}
	$custom = get_post_meta( get_queried_object_id(), '_yoast_wpseo_title', true );
	if ( $custom ) {
		$custom = kp_seo_replace_vars( $custom, get_queried_object_id() );
		// Brändi loppuun, jos sitä ei jo ole.
		if ( false === stripos( $custom, get_bloginfo( 'name' ) ) ) {
			$custom .= ' | ' . get_bloginfo( 'name' );
		}
		return $custom;
	}
	return $title;
}, 20 );

add_action( 'wp_head', function () {
	if ( kp_seo_plugin_active() ) {
		return;
	}
	$post  = is_singular() ? get_queried_object() : null;
	$desc  = ( is_front_page() || ! $post ) ? kp_seo_description( is_front_page() ? get_option( 'page_on_front' ) : null ) : kp_seo_description( $post );
	$title = wp_get_document_title();
	$url   = $post ? get_permalink( $post ) : home_url( add_query_arg( array() ) );
	$image = '';
	if ( $post && function_exists( 'kp_thumb_id' ) && kp_thumb_id( $post ) ) {
		$image = wp_get_attachment_image_url( kp_thumb_id( $post ), 'large' );
	}
	if ( ! $image ) {
		$image = get_template_directory_uri() . '/assets/img/etusivu-hero.webp';
	}

	if ( $desc ) {
		printf( '<meta name="description" content="%s">' . "\n", esc_attr( $desc ) );
	}
	$og = array(
		'og:type'        => ( $post && 'post' === $post->post_type ) ? 'article' : 'website',
		'og:locale'      => 'fi_FI',
		'og:site_name'   => get_bloginfo( 'name' ),
		'og:title'       => $title,
		'og:description' => $desc,
		'og:url'         => $url,
		'og:image'       => $image,
	);
	foreach ( $og as $k => $v ) {
		if ( $v ) {
			printf( '<meta property="%s" content="%s">' . "\n", esc_attr( $k ), esc_attr( $v ) );
		}
	}
	if ( $post && 'post' === $post->post_type ) {
		printf( '<meta property="article:published_time" content="%s">' . "\n", esc_attr( get_the_date( 'c', $post ) ) );
		printf( '<meta property="article:modified_time" content="%s">' . "\n", esc_attr( get_the_modified_date( 'c', $post ) ) );
	}
	echo '<meta name="twitter:card" content="summary_large_image">' . "\n";
}, 2 );

/* ---------- Rakenteinen data ---------- */

/**
 * FAQ-kysymykset sisällöstä: <details><summary> tai .kp-article__faq-item h3 + p.
 */
function kp_seo_faq_from_content( $content ) {
	$out = array();
	if ( preg_match_all( '#<details[^>]*>\s*<summary[^>]*>(.*?)</summary>(.*?)</details>#s', $content, $m, PREG_SET_ORDER ) ) {
		foreach ( $m as $x ) {
			$out[] = array( $x[1], $x[2] );
		}
	}
	if ( preg_match_all( '#<div class="kp-article__faq-item">\s*<h3[^>]*>(.*?)</h3>(.*?)</div>#s', $content, $m, PREG_SET_ORDER ) ) {
		foreach ( $m as $x ) {
			$out[] = array( $x[1], $x[2] );
		}
	}
	$faq = array();
	foreach ( $out as $qa ) {
		$q = trim( wp_strip_all_tags( $qa[0] ) );
		$a = trim( preg_replace( '/\s+/', ' ', wp_strip_all_tags( $qa[1] ) ) );
		if ( $q && $a ) {
			$faq[] = array(
				'@type'          => 'Question',
				'name'           => $q,
				'acceptedAnswer' => array( '@type' => 'Answer', 'text' => $a ),
			);
		}
	}
	return $faq;
}

function kp_seo_graph( $post ) {
	$url   = get_permalink( $post );
	$raw   = get_post_meta( $post->ID, 'kp_jsonld', true );
	$data  = $raw ? json_decode( $raw, true ) : null;
	$graph = array();
	if ( is_array( $data ) ) {
		$graph = isset( $data['@graph'] ) ? $data['@graph'] : array( $data );
	}

	$has_faq = false;
	foreach ( $graph as &$node ) {
		$type = isset( $node['@type'] ) ? $node['@type'] : '';
		if ( in_array( $type, array( 'Article', 'BlogPosting', 'WebPage', 'Service' ), true ) ) {
			// Osoite, päivämäärät ja kuva ajan tasalle tästä sisällöstä.
			$node['mainEntityOfPage'] = array( '@type' => 'WebPage', '@id' => $url );
			if ( 'post' === $post->post_type ) {
				$node['datePublished'] = get_the_date( 'c', $post );
				$node['dateModified']  = get_the_modified_date( 'c', $post );
			}
			if ( function_exists( 'kp_thumb_id' ) && kp_thumb_id( $post ) ) {
				$node['image'] = array( '@type' => 'ImageObject', 'url' => wp_get_attachment_image_url( kp_thumb_id( $post ), 'large' ) );
			}
		} elseif ( 'BreadcrumbList' === $type && ! empty( $node['itemListElement'] ) ) {
			$last = count( $node['itemListElement'] ) - 1;
			$node['itemListElement'][ $last ]['item'] = $url;
		} elseif ( 'FAQPage' === $type ) {
			$has_faq = true;
		}
	}
	unset( $node );

	if ( ! $has_faq ) {
		$faq = kp_seo_faq_from_content( $post->post_content );
		if ( count( $faq ) >= 2 ) {
			$graph[] = array( '@type' => 'FAQPage', 'mainEntity' => $faq );
		}
	}

	// SEO-lisäosa tuottaa jo Article- ja murupolkutiedot; meiltä vain niiden täydennykset.
	if ( kp_seo_plugin_active() ) {
		$graph = array_values( array_filter( $graph, function ( $n ) {
			return isset( $n['@type'] ) && ! in_array( $n['@type'], array( 'Article', 'BlogPosting', 'WebPage', 'BreadcrumbList' ), true );
		} ) );
	}
	return $graph;
}

add_action( 'wp_head', function () {
	if ( ! is_singular() ) {
		return;
	}
	$graph = kp_seo_graph( get_queried_object() );
	if ( ! $graph ) {
		return;
	}
	$json = wp_json_encode( array( '@context' => 'https://schema.org', '@graph' => $graph ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );
	echo '<script type="application/ld+json">' . str_replace( '</', '<\/', $json ) . "</script>\n"; // phpcs:ignore WordPress.Security.EscapeOutput
}, 20 );

/**
 * Yhteisön tiedot etusivulle (Organization / ProfessionalService).
 */
add_action( 'wp_head', function () {
	if ( ! is_front_page() || kp_seo_plugin_active() ) {
		return;
	}
	$offices = array();
	foreach ( kp_offices() as $o ) {
		$parts     = array_map( 'trim', explode( ',', $o['osoite'] ) );
		$city      = isset( $parts[1] ) ? preg_replace( '/^\d{5}\s*/', '', $parts[1] ) : '';
		$zip       = isset( $parts[1] ) && preg_match( '/\d{5}/', $parts[1], $z ) ? $z[0] : '';
		$offices[] = array(
			'@type'         => 'PostalAddress',
			'streetAddress' => $parts[0],
			'postalCode'    => $zip,
			'addressLocality' => $city,
			'addressCountry' => 'FI',
		);
	}
	$org = array(
		'@context'  => 'https://schema.org',
		'@type'     => 'ProfessionalService',
		'name'      => kp_setting( 'yritys' ),
		'url'       => home_url( '/' ),
		'telephone' => kp_setting( 'puhelin' ),
		'email'     => kp_setting( 'sahkoposti' ),
		'vatID'     => kp_setting( 'ytunnus' ),
		'address'   => $offices,
		'areaServed' => 'FI',
		'sameAs'    => array_values( array_filter( array( kp_setting( 'linkedin' ), kp_setting( 'facebook' ), kp_setting( 'instagram' ), kp_setting( 'youtube' ) ) ) ),
	);
	echo '<script type="application/ld+json">' . wp_json_encode( $org, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) . "</script>\n"; // phpcs:ignore WordPress.Security.EscapeOutput
}, 20 );

/**
 * Kenttä kp_jsonld näkyviin hallintaan (Mukautetut kentät), mutta suojattu REST:ltä.
 */
add_action( 'init', function () {
	foreach ( array( 'post', 'page', 'palvelu' ) as $type ) {
		register_post_meta( $type, 'kp_jsonld', array( 'type' => 'string', 'single' => true, 'show_in_rest' => false ) );
	}
} );
