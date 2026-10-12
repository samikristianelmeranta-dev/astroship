<?php
/**
 * Kevyt chat: vastaa kävijän kysymyksiin sivuston omasta sisällöstä ja siirtää
 * keskustelun tarvittaessa WhatsAppiin (kysymys valmiina viestissä).
 *
 * - Ei ulkoisia palveluita eikä tekoälyrajapintoja: haku tehdään omalla palvelimella
 *   sivujen, artikkelien, UKK-osioiden ja hinnaston tekstistä.
 * - Kysymyksiä ei tallenneta. Ei evästeitä.
 * - Hakemisto rakennetaan välimuistiin ja päivittyy, kun sisältöä muokataan.
 *
 * Pois päältä: add_filter( 'tilihub_chat_enabled', '__return_false' );
 *
 * @package TiliHub
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

const TILIHUB_CHAT_INDEX = 'tilihub_chat_index_v1';

/**
 * Tekstin normalisointi hakua varten.
 *
 * @param string $text Teksti.
 * @return string
 */
function tilihub_chat_norm( $text ) {
	$text = html_entity_decode( wp_strip_all_tags( (string) $text ), ENT_QUOTES, 'UTF-8' );
	$text = mb_strtolower( $text, 'UTF-8' );
	$text = preg_replace( '/[^\p{L}\p{N}€]+/u', ' ', $text );
	return trim( preg_replace( '/\s+/u', ' ', $text ) );
}

/**
 * Kysymyksen hakusanat karkeasti perusmuotoon (suomen yleisimmät päätteet pois).
 *
 * @param string $q Kysymys.
 * @return string[]
 */
function tilihub_chat_terms( $q ) {
	static $stop = null;
	if ( null === $stop ) {
		$stop = array_flip(
			explode(
				' ',
				'ja tai on ovat oli ole olla olen olet onko ei en et se ne he me te mä sä minä sinä hän mikä mitä mitkä miten miksi milloin missä mistä mihin kuka ketkä kuinka paljonko paljon voi voiko voinko voitteko voitko teidän teillä teille teiltä meidän meillä minun minulla mulla mun kun jos että kuin myös vain sekä niin nyt jo vielä tämä tuo nämä noi tässä siinä sitä sen siitä hei moi moikko terve kiitos kiitti please the a an is of to for and in what how does do i you your'
			)
		);
	}
	$out = array();
	foreach ( explode( ' ', tilihub_chat_norm( $q ) ) as $w ) {
		if ( '' === $w || isset( $stop[ $w ] ) || mb_strlen( $w ) < 3 ) {
			continue;
		}
		// Kysymysmuotoiset verbit (osaatteko, teettekö, hoidatteko) eivät ole hakusanoja.
		if ( mb_strlen( $w ) >= 6 && preg_match( '/(ko|kö)$/u', $w ) ) {
			continue;
		}
		// Taivutuspäätteet pois, jos sana jää riittävän pitkäksi.
		foreach ( array( 'ineen', 'iden', 'issa', 'issä', 'ista', 'istä', 'illa', 'illä', 'ilta', 'iltä', 'ille', 'ssa', 'ssä', 'sta', 'stä', 'lla', 'llä', 'lta', 'ltä', 'lle', 'ksi', 'ien', 'jen', 'iin', 'aan', 'ään', 'een', 'tta', 'ttä', 'ita', 'itä', 'ja', 'jä', 'na', 'nä', 'ni', 'si', 'mme', 'nne', 'n', 't', 'a', 'ä', 'i', 'e' ) as $suffix ) {
			$len = mb_strlen( $suffix );
			if ( mb_strlen( $w ) - $len >= 4 && mb_substr( $w, -$len ) === $suffix ) {
				$w = mb_substr( $w, 0, -$len );
				break;
			}
		}
		$out[] = $w;
	}
	return array_values( array_unique( $out ) );
}

/**
 * Jakaa HTML-sisällön otsikoittain osiin.
 *
 * @param string $html  Sisältö.
 * @param string $title Sivun otsikko.
 * @param string $url   Osoite.
 * @return array<int, array<string, string>>
 */
function tilihub_chat_sections( $html, $title, $url ) {
	$chunks = array();
	$parts  = preg_split( '#(<h[2-4]\b[^>]*>.*?</h[2-4]>)#is', $html, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY );
	$head   = $title;
	foreach ( $parts as $part ) {
		if ( preg_match( '#^<h[2-4]#i', $part ) ) {
			$head = trim( preg_replace( '/^\d+[\s.)]*/u', '', html_entity_decode( wp_strip_all_tags( $part ), ENT_QUOTES, 'UTF-8' ) ) );
			continue;
		}
		$text = trim( preg_replace( '/\s+/u', ' ', html_entity_decode( wp_strip_all_tags( preg_replace( '#</(p|li|td|th|div)>#i', '$0 ', $part ) ), ENT_QUOTES, 'UTF-8' ) ) );
		if ( mb_strlen( $text ) < 40 || preg_match( '/^(sisällys|sisällysluettelo|table of contents|contents)\b/iu', $head ) ) {
			continue;
		}
		$chunks[] = array(
			'type'  => 'section',
			'title' => $title,
			'url'   => $url,
			'head'  => $head,
			'text'  => mb_substr( $text, 0, 900 ),
		);
	}
	return $chunks;
}

/**
 * Rakentaa (tai hakee välimuistista) chatin hakemiston.
 *
 * @return array<int, array<string, string>>
 */
function tilihub_chat_index() {
	$cached = get_option( TILIHUB_CHAT_INDEX );
	if ( is_array( $cached ) && ! empty( $cached['chunks'] ) ) {
		return $cached['chunks'];
	}

	$b      = tilihub_business();
	$chunks = array();

	// Yrityksen perustiedot ja hinnat (painotettu: vastaavat yleisimpiin kysymyksiin).
	$rates = array();
	foreach ( $b['prices'] as $p ) {
		$rates[] = sprintf( '%s %d €/h', mb_strtolower( $p['name'], 'UTF-8' ), $p['price'] );
	}
	$chunks[] = array(
		'type'  => 'fact',
		'title' => 'Hinnasto',
		'url'   => home_url( '/kirjanpidon-hinnat/' ),
		'head'  => 'Mitä kirjanpito maksaa? Paljonko maksaa? Hinta, hinnat, hinnasto, kustannus, kustannukset, tuntihinta, kuukausihinta, euroa',
		'text'  => sprintf( 'Kirjanpito alkaen %d € + alv kuukaudessa. Tuntihinnat: %s (tilinpäätös ja veroilmoitus ovat asiantuntijatyötä). Hintoihin lisätään alv 25,5 %%, ohjelmistomaksut laskutetaan erikseen.', $b['min_monthly'], implode( ', ', $rates ) ),
	);
	// Palvelukohtaiset hinnat omina vastauksinaan.
	$keys = array(
		'Juokseva kirjanpito' => 'kirjanpito kirjanpidon juokseva kuukausi tositteet alv-ilmoitus',
		'Palkanlaskenta'      => 'palkanlaskenta palkanlaskennan palkat palkka palkka-ajo tulorekisteri',
		'Asiantuntijatyö'     => 'tilinpäätös tilinpäätöksen veroilmoitus asiantuntijatyö osingonjako korjaukset',
		'Verosuunnittelu'     => 'verosuunnittelu verosuunnittelun veroneuvonta osinko holding yritysjärjestely',
	);
	foreach ( $b['prices'] as $p ) {
		$chunks[] = array(
			'type'  => 'fact',
			'title' => 'Hinnasto',
			'url'   => home_url( '/kirjanpidon-hinnat/' ),
			'head'  => $p['name'] . ' hinta maksaa paljonko kustannus ' . ( $keys[ $p['name'] ] ?? '' ),
			'text'  => sprintf( '%s maksaa %d €/h + alv. %s', $p['name'], $p['price'], $p['note'] ),
		);
	}
	$chunks[] = array(
		'type'  => 'fact',
		'title' => 'Tietoa meistä',
		'url'   => home_url( '/tietoa-meista/' ),
		'head'  => 'Kieli, kielet, englanti, englanniksi, english, speak, ulkomainen, foreign, ruotsi',
		'text'  => 'Palvelemme suomeksi ja englanniksi – myös ulkomaalaistaustaisia yrittäjiä ja ulkomaisessa omistuksessa olevia yrityksiä. We also serve clients in English.',
	);
	$chunks[] = array(
		'type'  => 'fact',
		'title' => 'Yhteystiedot',
		'url'   => home_url( '/yhteystiedot/' ),
		'head'  => 'Yhteystiedot, puhelin, puhelinnumero, numero, soittaa, sähköposti, email, WhatsApp, tavoittaa, ota yhteyttä, yhteydenotto, sijainti, osoite, toimisto, missä',
		'text'  => sprintf( 'Meidät tavoittaa puhelimella %s, sähköpostilla %s ja WhatsAppissa. Toimimme %s, Varsinais-Suomi, ja palvelemme digitaalisesti koko Suomea. Tarjouspyynnön voi jättää yhteystietosivulla.', $b['phone_human'], $b['email'], $b['locality'] ),
	);
	$chunks[] = array(
		'type'  => 'fact',
		'title' => 'Tietoa meistä',
		'url'   => home_url( '/tietoa-meista/' ),
		'head'  => 'Kuka, keitä olette, yritys, perustaja, kirjanpitäjä, ohjelmistot, ohjelmat, kieli, englanti, englanniksi, english, ruotsi',
		'text'  => sprintf( '%s Perustettu %s, perustaja ja pääkirjanpitäjä %s. Taloushallintoliiton jäsen. Käytämme ohjelmistoja %s. Palvelemme suomeksi ja englanniksi (service in English).', $b['description'], $b['founded'], $b['founder'], implode( ', ', $b['software'] ) ),
	);

	$posts = get_posts(
		array(
			'post_type'      => array( 'page', 'post' ),
			'post_status'    => 'publish',
			'posts_per_page' => 400,
			'orderby'        => 'menu_order date',
		)
	);
	foreach ( $posts as $post ) {
		$url     = get_permalink( $post );
		$title   = html_entity_decode( get_the_title( $post ), ENT_QUOTES, 'UTF-8' );
		$content = $post->post_content;
		if ( function_exists( 'tilihub_clean_legacy_html' ) ) {
			$content = tilihub_clean_legacy_html( $content );
		}
		$content = strip_shortcodes( preg_replace( '#<!--.*?-->#s', '', $content ) );

		// UKK-parit: sivujen UKK-osiot ja artikkelien FAQPage-rakennetieto.
		$pairs = function_exists( 'tilihub_schema_faq_pairs' ) ? tilihub_schema_faq_pairs( $content ) : array();
		if ( preg_match_all( '#<script\b[^>]*ld\+json[^>]*>(.*?)</script>#is', $post->post_content, $ld ) ) {
			foreach ( $ld[1] as $json ) {
				$data  = json_decode( $json, true );
				$nodes = isset( $data['@graph'] ) ? $data['@graph'] : array( $data );
				foreach ( (array) $nodes as $node ) {
					if ( is_array( $node ) && 'FAQPage' === ( $node['@type'] ?? '' ) ) {
						foreach ( (array) ( $node['mainEntity'] ?? array() ) as $q ) {
							if ( ! empty( $q['name'] ) && ! empty( $q['acceptedAnswer']['text'] ) ) {
								$pairs[] = array( 'q' => $q['name'], 'a' => wp_strip_all_tags( $q['acceptedAnswer']['text'] ) );
							}
						}
					}
				}
			}
		}
		foreach ( $pairs as $p ) {
			$chunks[] = array(
				'type'  => 'page' === $post->post_type ? 'faq_page' : 'faq',
				'title' => $title,
				'url'   => $url,
				'head'  => $p['q'],
				'text'  => mb_substr( $p['a'], 0, 900 ),
			);
		}

		$content = preg_replace( '#<script\b.*?</script>#is', '', $content );
		foreach ( tilihub_chat_sections( $content, $title, $url ) as $c ) {
			$c['type'] = 'page' === $post->post_type ? 'page' : 'post';
			$chunks[]  = $c;
		}
	}

	foreach ( $chunks as &$c ) {
		$c['norm_head'] = tilihub_chat_norm( $c['title'] . ' ' . $c['head'] );
		$c['norm_text'] = tilihub_chat_norm( $c['text'] );
	}
	unset( $c );

	update_option( TILIHUB_CHAT_INDEX, array( 'built' => time(), 'chunks' => $chunks ), false );
	return $chunks;
}

/**
 * Hakemisto rakennetaan uudelleen, kun sisältö muuttuu.
 */
function tilihub_chat_flush() {
	delete_option( TILIHUB_CHAT_INDEX );
}
add_action( 'save_post', 'tilihub_chat_flush' );
add_action( 'deleted_post', 'tilihub_chat_flush' );
add_action( 'trashed_post', 'tilihub_chat_flush' );
add_action( 'after_switch_theme', 'tilihub_chat_flush' );

/**
 * Lyhentää tekstin muutamaan lauseeseen.
 *
 * @param string $text Teksti.
 * @param int    $max  Merkkien enimmäismäärä.
 * @return string
 */
function tilihub_chat_excerpt( $text, $max = 340 ) {
	if ( mb_strlen( $text ) <= $max ) {
		return $text;
	}
	$cut = mb_substr( $text, 0, $max );
	$dot = max( mb_strrpos( $cut, '. ' ) ?: 0, mb_strrpos( $cut, '? ' ) ?: 0, mb_strrpos( $cut, '! ' ) ?: 0 );
	return $dot > 120 ? mb_substr( $cut, 0, $dot + 1 ) : rtrim( mb_substr( $cut, 0, mb_strrpos( $cut, ' ' ) ?: $max ) ) . '…';
}

/**
 * Vastaa kysymykseen.
 *
 * @param string $q Kysymys.
 * @return array<string, mixed>
 */
function tilihub_chat_answer( $q ) {
	$b      = tilihub_business();
	$norm   = tilihub_chat_norm( $q );
	$wa     = sprintf( 'Hei! Kysyin sivuillanne: "%s"', mb_substr( trim( $q ), 0, 300 ) );
	$result = array(
		'answer'  => '',
		'source'  => null,
		'related' => array(),
		'wa_text' => $wa,
	);

	if ( preg_match( '/^(hei|moi|moikka|terve|huomenta|päivää|hello|hi)\b[\s!.]*$/u', $norm ) ) {
		$result['answer'] = 'Hei! Kysy vaikka hinnoista, kirjanpidosta, palkanlaskennasta, tilinpäätöksestä tai tilitoimiston vaihdosta. Voit myös jatkaa suoraan WhatsAppissa.';
		$result['wa_text'] = 'Hei! Tarvitsisin lisätietoja.';
		return $result;
	}

	$terms = tilihub_chat_terms( $q );
	if ( ! $terms ) {
		$result['answer'] = 'Kirjoita kysymyksesi hieman tarkemmin, niin etsin vastauksen sivuiltamme. Voit myös kysyä suoraan WhatsAppissa.';
		return $result;
	}

	$chunks = tilihub_chat_index();
	$n      = count( $chunks );

	// Harvinaisemmat sanat painavat enemmän (IDF).
	$idf = array();
	foreach ( $terms as $t ) {
		$df = 0;
		foreach ( $chunks as $c ) {
			if ( false !== mb_strpos( ' ' . $c['norm_head'] . ' ' . $c['norm_text'], ' ' . $t ) ) {
				$df++;
			}
		}
		$idf[ $t ] = log( 1 + ( $n - $df + 0.5 ) / ( $df + 0.5 ) );
	}

	// Hintakysymyksissä TiliHubin omat hinnat ennen artikkelien markkinahintoja.
	$price_q = (bool) preg_match( '/hin(ta|na|no)|maksa|kustann|paljonko|€|euro/u', $norm );
	$weights = array( 'fact' => 1.6, 'faq_page' => 1.8, 'faq' => 1.3, 'page' => 1.2, 'post' => 1.0 );
	$scored  = array();
	foreach ( $chunks as $i => $c ) {
		$score   = 0.0;
		$matched = 0;
		$head    = ' ' . $c['norm_head'];
		$text    = ' ' . $c['norm_text'];
		foreach ( $terms as $t ) {
			// Osuma vain sanan alusta (ei esim. "kissa" sanassa "pankissa").
			$in_head = mb_substr_count( $head, ' ' . $t );
			$in_text = mb_substr_count( $text, ' ' . $t );
			if ( $in_head || $in_text ) {
				$matched++;
				$score += $idf[ $t ] * ( 2.2 * min( $in_head, 2 ) + min( $in_text, 4 ) / ( 1 + 0.002 * mb_strlen( $c['norm_text'] ) ) );
			}
		}
		if ( ! $matched ) {
			continue;
		}
		$coverage = $matched / count( $terms );
		$s        = $score * ( 0.4 + $coverage ) * $weights[ $c['type'] ];
		if ( $price_q && ( 'fact' === $c['type'] || ( in_array( $c['type'], array( 'faq_page', 'page' ), true ) && false !== mb_strpos( $c['text'], '€/h' ) ) ) ) {
			$s *= 'fact' === $c['type'] ? 2.2 : 1.8;
		}
		// Lyhyet mainoslaatikot ("Pyydä tarjous", "Ota yhteyttä") eivät ole vastauksia.
		if ( mb_strlen( $c['text'] ) < 220 && preg_match( '/pyydä tarjous|ota yhteyttä|varaa|maksuton kartoitus/iu', $c['text'] ) ) {
			$s *= 0.35;
		}
		// Yli puolet hakusanoista pitää löytyä, jos sanoja on useita.
		if ( count( $terms ) >= 2 && $coverage < 0.6 ) {
			continue;
		}
		$scored[ $i ] = $s;
	}
	arsort( $scored );

	if ( ! $scored || reset( $scored ) < 1.5 ) {
		$result['answer'] = 'En löytänyt tähän suoraa vastausta sivuiltamme. Kysy meiltä WhatsAppissa, niin kirjanpitäjä vastaa sinulle henkilökohtaisesti.';
		return $result;
	}

	$best_i            = array_key_first( $scored );
	$best              = $chunks[ $best_i ];
	$result['answer']  = tilihub_chat_excerpt( $best['text'] );
	$result['source']  = array(
		'title' => $best['title'],
		'url'   => $best['url'],
	);
	// Otsikko näytetään vastauksen yläpuolella, jos se kertoo jotain lisää (ei hinnaston hakusanoja).
	$result['heading'] = ( 'fact' !== $best['type'] && $best['head'] !== $best['title'] ) ? $best['head'] : '';

	$seen = array( $best['url'] => true );
	foreach ( array_keys( $scored ) as $i ) {
		$c = $chunks[ $i ];
		if ( isset( $seen[ $c['url'] ] ) ) {
			continue;
		}
		$seen[ $c['url'] ]   = true;
		$result['related'][] = array( 'title' => $c['title'], 'url' => $c['url'] );
		if ( count( $result['related'] ) >= 2 ) {
			break;
		}
	}
	return $result;
}

add_action(
	'rest_api_init',
	static function () {
		register_rest_route(
			'tilihub/v1',
			'/chat',
			array(
				'methods'             => 'GET',
				'permission_callback' => '__return_true',
				'args'                => array(
					'q' => array(
						'required'          => true,
						'type'              => 'string',
						'sanitize_callback' => static function ( $v ) {
							return mb_substr( sanitize_text_field( (string) $v ), 0, 300 );
						},
					),
				),
				'callback'            => static function ( WP_REST_Request $req ) {
					// Kevyt rajoitus: 40 kysymystä minuutissa / IP (IP:tä ei tallenneta, vain tiiviste).
					$key   = 'tilihub_chat_' . md5( (string) ( $_SERVER['REMOTE_ADDR'] ?? '' ) . wp_salt() ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
					$count = (int) get_transient( $key );
					if ( $count > 40 ) {
						return new WP_REST_Response( array( 'error' => 'Liikaa kysymyksiä. Odota hetki.' ), 429 );
					}
					set_transient( $key, $count + 1, MINUTE_IN_SECONDS );

					$res = rest_ensure_response( tilihub_chat_answer( (string) $req->get_param( 'q' ) ) );
					$res->header( 'Cache-Control', 'no-store' );
					return $res;
				},
			)
		);
	}
);

add_action(
	'wp_enqueue_scripts',
	static function () {
		if ( ! apply_filters( 'tilihub_chat_enabled', true ) || is_admin() ) {
			return;
		}
		$b    = tilihub_business();
		$path = get_theme_file_path( 'assets/js/chat.js' );
		wp_enqueue_style( 'tilihub-chat', get_theme_file_uri( 'assets/css/chat.css' ), array(), (string) filemtime( get_theme_file_path( 'assets/css/chat.css' ) ) );
		wp_enqueue_script( 'tilihub-chat', get_theme_file_uri( 'assets/js/chat.js' ), array(), (string) filemtime( $path ), array( 'strategy' => 'defer', 'in_footer' => true ) );
		wp_localize_script(
			'tilihub-chat',
			'TILIHUB_CHAT',
			array(
				'endpoint' => esc_url_raw( rest_url( 'tilihub/v1/chat' ) ),
				'whatsapp' => preg_replace( '/\D/', '', $b['phone'] ),
				'phone'    => $b['phone_human'],
				'avatar'   => esc_url_raw( content_url( '/uploads/2026/06/jakke.jpg' ) ),
				'name'     => 'TiliHub Oy',
				'status'   => 'Vastaamme yleensä saman päivän aikana',
				'greeting' => 'Hei 👋 Olen TiliHubin chat. Etsin vastauksen sivuiltamme, ja voit jatkaa keskustelua kirjanpitäjän kanssa WhatsAppissa.',
				'quick'    => array(
					'Mitä kirjanpito maksaa?',
					'Miten tilitoimiston vaihto toimii?',
					'Mitä palkanlaskenta sisältää?',
					'Milloin tilinpäätös pitää tehdä?',
				),
			)
		);
	}
);
