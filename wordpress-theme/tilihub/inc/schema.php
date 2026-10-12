<?php
/**
 * Rakenteinen data (schema.org JSON-LD) hakukoneille ja tekoälyhauille.
 *
 * - Etusivu, hinnasto, yhteystiedot ja Tietoa meistä: tilitoimiston tiedot (AccountingService),
 *   palvelut ja hinnat (OfferCatalog), perustaja, toimialue ja some.
 * - Palvelusivut: Service + hinta.
 * - Sivut, joilla on kysymys–vastaus-osio: FAQPage, joka luetaan sivun omasta sisällöstä,
 *   joten se pysyy ajan tasalla, kun sivua muokataan.
 *
 * AIOSEO tuottaa edelleen omat perustietonsa (WebPage, BreadcrumbList, Article); nämä
 * täydentävät niitä. Pois päältä: add_filter( 'tilihub_schema', '__return_empty_array' );
 *
 * @package TiliHub
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Tilitoimiston tiedot.
 *
 * @return array<string, mixed>
 */
function tilihub_schema_business() {
	$b    = tilihub_business();
	$home = home_url( '/' );

	$offers = array();
	foreach ( $b['prices'] as $p ) {
		$offers[] = array(
			'@type'              => 'Offer',
			'name'               => $p['name'],
			'description'        => $p['note'],
			'url'                => home_url( $p['url'] ),
			'priceCurrency'      => 'EUR',
			'priceSpecification' => array(
				'@type'                 => 'UnitPriceSpecification',
				'price'                 => $p['price'],
				'priceCurrency'         => 'EUR',
				'unitCode'              => $p['unit'],
				'unitText'              => 'tunti',
				'valueAddedTaxIncluded' => false,
			),
			'itemOffered'        => array(
				'@type' => 'Service',
				'name'  => $p['name'],
			),
		);
	}

	return array(
		'@type'             => array( 'AccountingService', 'LocalBusiness' ),
		'@id'               => $home . '#tilitoimisto',
		'name'              => $b['name'],
		'legalName'         => $b['legal_name'],
		'description'       => $b['description'],
		'url'               => $home,
		'logo'              => get_theme_file_uri( 'assets/logo.svg' ),
		'image'             => get_theme_file_uri( 'assets/logo.svg' ),
		'telephone'         => $b['phone'],
		'email'             => $b['email'],
		'foundingDate'      => $b['founded'],
		'founder'           => array(
			'@type'    => 'Person',
			'name'     => $b['founder'],
			'jobTitle' => 'Perustaja ja pääkirjanpitäjä',
		),
		'address'           => array(
			'@type'           => 'PostalAddress',
			'addressLocality' => $b['locality'],
			'addressRegion'   => $b['region'],
			'addressCountry'  => $b['country'],
		),
		'areaServed'        => array_map(
			static function ( $a ) {
				return array( '@type' => 'Place', 'name' => $a );
			},
			$b['areas']
		),
		'knowsLanguage'     => array( 'fi', 'en' ),
		'priceRange'        => sprintf( 'alkaen %d € / kk + alv', $b['min_monthly'] ),
		'currenciesAccepted' => 'EUR',
		'memberOf'          => array(
			'@type' => 'Organization',
			'name'  => $b['member_of']['name'],
			'url'   => $b['member_of']['url'],
		),
		'sameAs'            => $b['same_as'],
		'contactPoint'      => array(
			'@type'             => 'ContactPoint',
			'contactType'       => 'customer service',
			'telephone'         => $b['phone'],
			'email'             => $b['email'],
			'availableLanguage' => array( 'Finnish', 'English' ),
			'areaServed'        => 'FI',
		),
		'hasOfferCatalog'   => array(
			'@type'           => 'OfferCatalog',
			'name'            => 'TiliHubin palvelut ja hinnat (alv 0 %)',
			'itemListElement' => $offers,
		),
	);
}

/**
 * Lukee sisällöstä kysymys–vastaus-parit: otsikko, joka päättyy kysymysmerkkiin, ja
 * sitä seuraavat kappaleet seuraavaan otsikkoon asti. Myös <details>-rakenteet.
 *
 * @param string $html Sivun sisältö.
 * @return array<int, array{q: string, a: string}>
 */
function tilihub_schema_faq_pairs( $html ) {
	$pairs = array();
	// Vain UKK-osiot: h2-otsikosta, jossa on "Q&A", "Usein kysyt…", "kysymyks…" tai "FAQ", seuraavaan h2:een.
	$sections = '';
	if ( preg_match_all( '#<h2\b[^>]*>(.*?)</h2>(.*?)(?=<h2\b|$)#is', $html, $h2s, PREG_SET_ORDER ) ) {
		foreach ( $h2s as $sec ) {
			if ( preg_match( '/q\s*&(amp;)?\s*a|usein kysyt|kysymyks|faq/iu', wp_strip_all_tags( $sec[1] ) ) ) {
				$sections .= $sec[2];
			}
		}
	}
	if ( '' !== $sections && preg_match_all( '#<h([3-4])\b[^>]*>(.*?)</h\1>(.*?)(?=<h[1-4]\b|$)#is', $sections, $m, PREG_SET_ORDER ) ) {
		foreach ( $m as $row ) {
			$q = trim( wp_strip_all_tags( $row[2] ) );
			if ( '?' !== substr( $q, -1 ) ) {
				continue;
			}
			preg_match_all( '#<p\b[^>]*>(.*?)</p>#is', $row[3], $ps );
			$a = trim( preg_replace( '/\s+/u', ' ', wp_strip_all_tags( implode( ' ', $ps[1] ) ) ) );
			if ( '' !== $a ) {
				$pairs[] = array( 'q' => $q, 'a' => $a );
			}
		}
	}
	if ( preg_match_all( '#<details\b[^>]*>\s*<summary\b[^>]*>(.*?)</summary>(.*?)</details>#is', $html, $d, PREG_SET_ORDER ) ) {
		foreach ( $d as $row ) {
			$q = trim( wp_strip_all_tags( $row[1] ) );
			$a = trim( preg_replace( '/\s+/u', ' ', wp_strip_all_tags( $row[2] ) ) );
			if ( '' !== $q && '' !== $a ) {
				$pairs[] = array( 'q' => $q, 'a' => $a );
			}
		}
	}
	return $pairs;
}

/**
 * Kokoaa sivukohtaisen rakenteisen datan.
 *
 * @return array<int, array<string, mixed>>
 */
function tilihub_schema_graph() {
	$graph = array();
	if ( ! is_singular() && ! is_front_page() ) {
		return $graph;
	}
	$post = get_queried_object();
	$slug = $post instanceof WP_Post ? $post->post_name : '';

	$business_pages = array( 'kirjanpidon-hinnat', 'yhteystiedot', 'tietoa-meista' );
	if ( is_front_page() || in_array( $slug, $business_pages, true ) ) {
		$graph[] = tilihub_schema_business();
	}

	$services = array(
		'kirjanpitopalvelut' => array( 'Kirjanpito', 'Juokseva kirjanpito, ALV-ilmoitukset, täsmäytykset ja kuukausiraportit pk-yrityksille.', 0 ),
		'palkanlaskenta'     => array( 'Palkanlaskenta', 'Palkanlaskenta, palkkalaskelmat, tulorekisteri-ilmoitukset ja työnantajan erillisilmoitukset.', 1 ),
		'tilinpaatos'        => array( 'Tilinpäätös', 'Tilinpäätös ja yrityksen veroilmoitus eri yhtiömuodoille.', 2 ),
	);
	if ( isset( $services[ $slug ] ) ) {
		$b       = tilihub_business();
		$price   = $b['prices'][ $services[ $slug ][2] ];
		$graph[] = array(
			'@type'       => 'Service',
			'@id'         => get_permalink( $post ) . '#palvelu',
			'name'        => $services[ $slug ][0],
			'serviceType' => $services[ $slug ][0],
			'description' => $services[ $slug ][1],
			'url'         => get_permalink( $post ),
			'provider'    => array( '@id' => home_url( '/' ) . '#tilitoimisto' ),
			'areaServed'  => $b['areas'],
			'offers'      => array(
				'@type'              => 'Offer',
				'priceCurrency'      => 'EUR',
				'priceSpecification' => array(
					'@type'                 => 'UnitPriceSpecification',
					'price'                 => $price['price'],
					'priceCurrency'         => 'EUR',
					'unitCode'              => 'HUR',
					'valueAddedTaxIncluded' => false,
				),
			),
		);
		// Palvelusivulla viitataan tilitoimistoon, joten liitetään sen tiedot mukaan.
		$graph[] = tilihub_schema_business();
	}

	// FAQPage sivuille (artikkeleissa on omansa), jos sisällössä on vähintään kaksi kysymystä.
	if ( $post instanceof WP_Post && 'page' === $post->post_type && false === strpos( $post->post_content, 'FAQPage' ) ) {
		$pairs = tilihub_schema_faq_pairs( $post->post_content );
		if ( count( $pairs ) >= 2 ) {
			$graph[] = array(
				'@type'      => 'FAQPage',
				'@id'        => get_permalink( $post ) . '#ukk',
				'mainEntity' => array_map(
					static function ( $p ) {
						return array(
							'@type'          => 'Question',
							'name'           => $p['q'],
							'acceptedAnswer' => array( '@type' => 'Answer', 'text' => $p['a'] ),
						);
					},
					$pairs
				),
			);
		}
	}

	return apply_filters( 'tilihub_schema', $graph );
}

add_action(
	'wp_head',
	static function () {
		$graph = tilihub_schema_graph();
		if ( ! $graph ) {
			return;
		}
		// Sama tilitoimisto voi tulla mukaan kahdesti; poistetaan toistot @id:n perusteella.
		$seen  = array();
		$graph = array_values(
			array_filter(
				$graph,
				static function ( $n ) use ( &$seen ) {
					$id = $n['@id'] ?? null;
					if ( $id && isset( $seen[ $id ] ) ) {
						return false;
					}
					$seen[ $id ] = true;
					return true;
				}
			)
		);
		printf(
			"<script type=\"application/ld+json\" class=\"tilihub-schema\">%s</script>\n",
			wp_json_encode( array( '@context' => 'https://schema.org', '@graph' => $graph ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES )
		);
	},
	20
);
