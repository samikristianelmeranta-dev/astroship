<?php
/**
 * Hakukone- ja tekoälyoptimointi: metatiedot, Open Graph, JSON-LD,
 * llms.txt ja robots.txt.
 *
 * Jos Yoast, Rank Math, SEOPress tai AIOSEO on käytössä, teema jättää
 * meta- ja OG-tagit niille ja tuottaa vain oman Person/FAQ-rakenteensa.
 *
 * @package sami-elmeranta
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Onko SEO-lisäosa käytössä.
 */
function se_seo_plugin_active() {
	return defined( 'WPSEO_VERSION' ) || defined( 'RANK_MATH_VERSION' ) || defined( 'SEOPRESS_VERSION' ) || defined( 'AIOSEO_VERSION' );
}

/**
 * Nykyisen sivun kuvaus.
 */
function se_meta_description() {
	if ( is_singular() ) {
		$post = get_queried_object();
		if ( has_excerpt( $post ) ) {
			return wp_strip_all_tags( get_the_excerpt( $post ) );
		}
		$text = wp_strip_all_tags( strip_shortcodes( $post->post_content ) );
		$text = trim( preg_replace( '/\s+/', ' ', $text ) );
		if ( $text && ! is_front_page() ) {
			return wp_trim_words( $text, 30, '…' );
		}
	}
	if ( is_category() || is_tag() ) {
		$desc = wp_strip_all_tags( term_description() );
		if ( $desc ) {
			return $desc;
		}
	}
	return se_opt( 'description' );
}

/**
 * Nykyisen sivun kuva.
 */
function se_meta_image() {
	if ( is_singular() && has_post_thumbnail() ) {
		return get_the_post_thumbnail_url( null, 'post-thumbnail' );
	}
	return se_asset_img( 'og.png' );
}

/**
 * Kanoninen URL.
 */
function se_canonical() {
	if ( is_singular() ) {
		return wp_get_canonical_url();
	}
	if ( is_front_page() ) {
		return home_url( '/' );
	}
	if ( is_home() ) {
		return se_blog_url();
	}
	if ( is_category() || is_tag() ) {
		return get_term_link( get_queried_object() );
	}
	return '';
}

// Meta- ja Open Graph -tagit.
add_action( 'wp_head', function () {
	if ( se_seo_plugin_active() ) {
		return;
	}
	$title = wp_get_document_title();
	$desc  = se_meta_description();
	$img   = se_meta_image();
	$url   = se_canonical();
	$type  = is_singular( 'post' ) ? 'article' : 'website';

	echo '<meta name="description" content="' . esc_attr( $desc ) . '">' . "\n";
	if ( is_search() || is_404() ) {
		echo '<meta name="robots" content="noindex, follow">' . "\n";
	} else {
		echo '<meta name="robots" content="index, follow, max-image-preview:large, max-snippet:-1">' . "\n";
	}
	if ( $url && ! is_singular() ) {
		echo '<link rel="canonical" href="' . esc_url( $url ) . '">' . "\n"; // Singular: WP core tulostaa.
	}
	$tags = array(
		'og:type'        => $type,
		'og:site_name'   => get_bloginfo( 'name' ),
		'og:locale'      => str_replace( '-', '_', get_bloginfo( 'language' ) ),
		'og:title'       => $title,
		'og:description' => $desc,
		'og:url'         => $url,
		'og:image'       => $img,
	);
	foreach ( $tags as $prop => $content ) {
		if ( $content ) {
			echo '<meta property="' . esc_attr( $prop ) . '" content="' . esc_attr( $content ) . '">' . "\n";
		}
	}
	if ( is_singular( 'post' ) ) {
		echo '<meta property="article:published_time" content="' . esc_attr( get_the_date( 'c' ) ) . '">' . "\n";
		echo '<meta property="article:modified_time" content="' . esc_attr( get_the_modified_date( 'c' ) ) . '">' . "\n";
	}
	echo '<meta name="twitter:card" content="summary_large_image">' . "\n";
	echo '<meta name="twitter:title" content="' . esc_attr( $title ) . '">' . "\n";
	echo '<meta name="twitter:description" content="' . esc_attr( $desc ) . '">' . "\n";
	echo '<meta name="twitter:image" content="' . esc_url( $img ) . '">' . "\n";
}, 3 );

// Vaihtoehtoiset koneluettavat lähteet.
add_action( 'wp_head', function () {
	echo '<link rel="alternate" type="text/plain" title="llms.txt" href="' . esc_url( home_url( '/llms.txt' ) ) . '">' . "\n";
}, 4 );

/**
 * Person-rakenne.
 */
function se_person_schema() {
	$home    = home_url( '/' );
	$same_as = array_values( array_filter( array_merge(
		wp_list_pluck( array_map( function ( $s ) {
			return array( 'url' => $s[2] );
		}, se_socials() ), 'url' ),
		array( se_opt( 'uusisuomi' ) )
	) ) );
	$roles = array_filter( se_content( 'roles' ), function ( $r ) {
		return ! empty( $r['active'] );
	} );

	return array_filter( array(
		'@type'        => 'Person',
		'@id'          => $home . '#person',
		'name'         => se_opt( 'person_name' ),
		'description'  => se_opt( 'description' ),
		'url'          => $home,
		'image'        => se_image_url( 'portrait', 'sami-portrait.webp' ),
		'jobTitle'     => se_opt( 'job_title' ),
		'telephone'    => se_opt( 'phone' ),
		'email'        => se_opt( 'email' ) ? 'mailto:' . se_opt( 'email' ) : null,
		'affiliation'  => array(
			'@type' => 'PoliticalParty',
			'name'  => se_opt( 'party_name' ),
			'url'   => se_opt( 'party_url' ),
		),
		'memberOf'     => array_values( array_map( function ( $r ) {
			return array( '@type' => 'GovernmentOrganization', 'name' => $r['title'] );
		}, $roles ) ),
		'homeLocation' => array(
			'@type'   => 'Place',
			'address' => array(
				'@type'           => 'PostalAddress',
				'addressLocality' => se_opt( 'locality' ),
				'addressRegion'   => se_opt( 'region' ),
				'addressCountry'  => 'FI',
			),
		),
		'knowsAbout'   => array( 'Kuntapolitiikka', 'Julkinen talous', 'Yrittäjyys', 'Myynti ja markkinointi', se_opt( 'locality' ), se_opt( 'region' ) ),
		'sameAs'       => $same_as,
	), function ( $v ) {
		return null !== $v;
	} );
}

/**
 * FAQ-rakenne artikkelista: H2 "Usein kysytyt kysymykset" ja sen alla
 * H3-kysymykset vastauksineen.
 *
 * @param string $html Artikkelin sisältö.
 * @return array Kysymys–vastaus-parit.
 */
function se_extract_faq( $html ) {
	if ( ! preg_match( '/<h2[^>]*>\s*(?:Usein kysyt[^<]*|UKK[^<]*)<\/h2>(.*?)(?=<h2|$)/isu', $html, $m ) ) {
		return array();
	}
	preg_match_all( '/<h3[^>]*>(.*?)<\/h3>(.*?)(?=<h3|$)/isu', $m[1], $pairs, PREG_SET_ORDER );
	$faq = array();
	foreach ( $pairs as $pair ) {
		$q = trim( wp_strip_all_tags( $pair[1] ) );
		$a = trim( preg_replace( '/\s+/u', ' ', wp_strip_all_tags( $pair[2] ) ) );
		if ( $q && $a ) {
			$faq[] = array( 'q' => $q, 'a' => $a );
		}
	}
	return $faq;
}

// JSON-LD-graafi.
add_action( 'wp_head', function () {
	$home  = home_url( '/' );
	$graph = array( se_person_schema() );

	if ( ! se_seo_plugin_active() ) {
		$graph[] = array(
			'@type'       => 'WebSite',
			'@id'         => $home . '#website',
			'url'         => $home,
			'name'        => get_bloginfo( 'name' ),
			'description' => se_opt( 'description' ),
			'inLanguage'  => get_bloginfo( 'language' ),
			'publisher'   => array( '@id' => $home . '#person' ),
		);
	}

	if ( is_front_page() ) {
		$graph[] = array(
			'@type'      => 'ProfilePage',
			'url'        => $home,
			'mainEntity' => array( '@id' => $home . '#person' ),
		);
		$graph[] = array(
			'@type'      => 'FAQPage',
			'mainEntity' => array_map( function ( $f ) {
				return array(
					'@type'          => 'Question',
					'name'           => $f['q'],
					'acceptedAnswer' => array( '@type' => 'Answer', 'text' => $f['a'] ),
				);
			}, se_content( 'faq' ) ),
		);
	}

	if ( is_singular( 'post' ) ) {
		$faq = se_extract_faq( (string) get_post_field( 'post_content' ) );
		if ( $faq ) {
			$graph[] = array(
				'@type'      => 'FAQPage',
				'mainEntity' => array_map( function ( $f ) {
					return array(
						'@type'          => 'Question',
						'name'           => $f['q'],
						'acceptedAnswer' => array( '@type' => 'Answer', 'text' => $f['a'] ),
					);
				}, $faq ),
			);
		}
	}

	if ( is_singular( 'post' ) && ! se_seo_plugin_active() ) {
		$url     = get_permalink();
		$graph[] = array(
			'@type'            => 'BlogPosting',
			'@id'              => $url . '#article',
			'headline'         => get_the_title(),
			'description'      => se_meta_description(),
			'url'              => $url,
			'mainEntityOfPage' => $url,
			'datePublished'    => get_the_date( 'c' ),
			'dateModified'     => get_the_modified_date( 'c' ),
			'inLanguage'       => get_bloginfo( 'language' ),
			'wordCount'        => str_word_count( wp_strip_all_tags( get_post_field( 'post_content' ) ) ),
			'keywords'         => implode( ', ', wp_list_pluck( (array) get_the_tags(), 'name' ) ),
			'image'            => se_meta_image(),
			'author'           => array( '@id' => $home . '#person' ),
			'publisher'        => array( '@id' => $home . '#person' ),
		);
		$graph[] = array(
			'@type'           => 'BreadcrumbList',
			'itemListElement' => array(
				array( '@type' => 'ListItem', 'position' => 1, 'name' => 'Etusivu', 'item' => $home ),
				array( '@type' => 'ListItem', 'position' => 2, 'name' => 'Blogi', 'item' => se_blog_url() ),
				array( '@type' => 'ListItem', 'position' => 3, 'name' => get_the_title(), 'item' => $url ),
			),
		);
	}

	$json = wp_json_encode(
		array( '@context' => 'https://schema.org', '@graph' => $graph ),
		JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP
	);
	echo '<script type="application/ld+json">' . $json . '</script>' . "\n"; // phpcs:ignore -- JSON_HEX_TAG estää </script>-katkaisun.
}, 5 );

// robots.txt: tekoälyhaut sallitaan, hallinta estetään, sivukartta mukaan.
add_filter( 'robots_txt', function ( $output, $public ) {
	if ( ! $public ) {
		return $output;
	}
	$bots = array( 'OAI-SearchBot', 'ChatGPT-User', 'GPTBot', 'ClaudeBot', 'Claude-User', 'Claude-SearchBot', 'PerplexityBot', 'Perplexity-User', 'Google-Extended', 'Applebot-Extended', 'CCBot' );
	$out  = "# Hakukoneet ja tekoälyhaut ovat tervetulleita.\n";
	$out .= "User-agent: *\nDisallow: /wp-admin/\nAllow: /wp-admin/admin-ajax.php\nDisallow: /?s=\nDisallow: /search/\n\n";
	foreach ( $bots as $bot ) {
		$out .= 'User-agent: ' . $bot . "\n";
	}
	$out .= "Allow: /\nDisallow: /wp-admin/\n\n";
	$out .= 'Sitemap: ' . home_url( '/wp-sitemap.xml' ) . "\n";
	return $out;
}, 10, 2 );

// llms.txt (https://llmstxt.org) – tiivis koneluettava kuvaus sivustosta.
add_action( 'init', function () {
	add_rewrite_rule( '^llms\.txt$', 'index.php?se_llms=1', 'top' );
} );
add_filter( 'query_vars', function ( $vars ) {
	$vars[] = 'se_llms';
	return $vars;
} );
add_action( 'after_switch_theme', function () {
	add_rewrite_rule( '^llms\.txt$', 'index.php?se_llms=1', 'top' );
	flush_rewrite_rules();
} );
add_filter( 'redirect_canonical', function ( $redirect, $requested ) {
	return get_query_var( 'se_llms' ) ? false : $redirect;
}, 10, 2 );

add_action( 'template_redirect', function () {
	if ( ! get_query_var( 'se_llms' ) ) {
		return;
	}
	$nl    = "\n";
	$roles = array_filter( se_content( 'roles' ), function ( $r ) {
		return ! empty( $r['active'] );
	} );
	$out   = '# ' . se_opt( 'person_name' ) . $nl . $nl . '> ' . se_opt( 'description' ) . $nl . $nl;
	$out  .= '- Puolue: ' . se_opt( 'party_name' ) . $nl;
	$out  .= '- Kotipaikka: ' . se_opt( 'district' ) . ', ' . se_opt( 'locality' ) . ' (' . se_opt( 'region' ) . ')' . $nl;
	$out  .= '- Nykyiset luottamustoimet: ' . implode( '; ', wp_list_pluck( $roles, 'title' ) ) . $nl;
	$out  .= '- Yhteys: puhelin ' . se_opt( 'phone_display' ) . ( se_opt( 'email' ) ? ', sähköposti ' . se_opt( 'email' ) : '' ) . $nl . $nl;

	$out .= '## Sivut' . $nl . $nl;
	foreach ( get_pages( array( 'sort_column' => 'menu_order' ) ) as $page ) {
		$out .= '- [' . get_the_title( $page ) . '](' . get_permalink( $page ) . ')' . $nl;
	}
	$out .= $nl . '## Keskeiset teemat' . $nl . $nl;
	foreach ( se_content( 'values' ) as $v ) {
		$out .= '- ' . $v['title'] . ': ' . $v['text'] . $nl;
	}
	$out .= $nl . '## Usein kysyttyä' . $nl . $nl;
	foreach ( se_content( 'faq' ) as $f ) {
		$out .= '### ' . $f['q'] . $nl . $f['a'] . $nl . $nl;
	}
	$out .= '## Blogikirjoitukset' . $nl . $nl;
	foreach ( get_posts( array( 'numberposts' => 50 ) ) as $p ) {
		$out .= '- [' . get_the_title( $p ) . '](' . get_permalink( $p ) . '): ' . wp_strip_all_tags( get_the_excerpt( $p ) ) . $nl;
	}
	$out .= $nl . '## Optional' . $nl . $nl;
	$out .= '- [RSS](' . get_feed_link() . ')' . $nl;
	$out .= '- [Sivukartta](' . home_url( '/wp-sitemap.xml' ) . ')' . $nl;
	foreach ( se_socials() as $s ) {
		$out .= '- [' . $s[0] . '](' . $s[2] . ')' . $nl;
	}

	status_header( 200 );
	header( 'Content-Type: text/plain; charset=utf-8' );
	header( 'X-Robots-Tag: noindex' );
	echo html_entity_decode( $out, ENT_QUOTES, 'UTF-8' ); // phpcs:ignore -- text/plain.
	exit;
}, 0 );
