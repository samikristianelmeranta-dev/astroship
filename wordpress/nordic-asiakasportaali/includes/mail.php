<?php
/**
 * Sähköpostit asiakkaalle ja henkilökunnalle. Asiakas saa viestin vain näissä kohdissa:
 * kiitos tarjouspyynnöstä → mittauskäynnin vahvistus → tarjous → hyväksynnän vahvistus →
 * asennus- tai toimituspäivän vahvistus sopimusehtoineen (+ muistutus edellisenä päivänä).
 */

defined( 'ABSPATH' ) || exit;

function nap_mail( $to, $subject, $heading, $paragraphs, $button = null, $reply_to = null ) {
	$s    = nap_settings();
	$body = '<div style="background:#F3F6F7;padding:24px 12px;font-family:Arial,Helvetica,sans-serif;color:#16303B">'
		. '<div style="max-width:560px;margin:0 auto;background:#ffffff;border-radius:12px;overflow:hidden">'
		. '<div style="background:#16303B;color:#ffffff;padding:18px 24px;font-weight:bold;font-size:18px">Ikkunakauppias<span style="color:#FFD866">.fi</span></div>'
		. '<div style="padding:24px">'
		. ( '' !== $heading ? '<h1 style="font-size:20px;margin:0 0 12px">' . esc_html( $heading ) . '</h1>' : '' );
	foreach ( (array) $paragraphs as $p ) {
		$body .= '<p style="font-size:15px;line-height:1.55;margin:0 0 12px">' . nl2br( esc_html( $p ) ) . '</p>';
	}
	if ( $button ) {
		$body .= '<p style="margin:20px 0"><a href="' . esc_url( $button[1] ) . '" style="display:inline-block;background:#FFD866;color:#16303B;text-decoration:none;font-weight:bold;padding:12px 20px;border-radius:999px">' . esc_html( $button[0] ) . '</a></p>'
			. '<p style="font-size:12.5px;color:#647A84;margin:0">Jos painike ei toimi, kopioi tämä osoite selaimeen:<br>' . esc_html( $button[1] ) . '</p>';
	}
	$body .= '</div><div style="padding:16px 24px;border-top:1px solid #E3EAEE;font-size:12.5px;color:#647A84">Nordic Ikkunat &amp; Ovet Oy · Myynti 045 7832 5070 · Asennus 045 7830 4746 · ikkunakauppias.fi</div></div></div>';

	$headers = array( 'Content-Type: text/html; charset=UTF-8' );
	if ( $reply_to ) {
		$headers[] = 'Reply-To: ' . $reply_to;
	} elseif ( $s['reply_to'] ) {
		$headers[] = 'Reply-To: ' . $s['from_name'] . ' <' . $s['reply_to'] . '>';
	}
	$from = function () use ( $s ) {
		return $s['from_name'];
	};
	// Lähettäjäosoite oikeaan, olemassa olevaan laatikkoon (esim. info@) eikä WordPressin oletukseen
	// wordpress@…: vastaukset ja palautukset menevät perille, ja SPF/DMARC täsmäävät verkkotunnukseen.
	$from_email = function ( $email ) use ( $s ) {
		$host = strtolower( preg_replace( '/^www\./', '', (string) wp_parse_url( home_url(), PHP_URL_HOST ) ) );
		$own  = strtolower( (string) $s['reply_to'] );
		return $own && is_email( $own ) && substr( $own, -strlen( '@' . $host ) ) === '@' . $host ? $own : $email;
	};
	// Tekstiversio HTML:n rinnalle: viesti ilman tekstiosaa saa roskapostisuodattimissa huonommat pisteet.
	$text = function ( $phpmailer ) use ( $heading, $paragraphs, $button ) {
		$t = ( '' !== $heading ? $heading . "\n\n" : '' ) . implode( "\n\n", (array) $paragraphs );
		if ( $button ) {
			$t .= "\n\n" . $button[0] . ': ' . $button[1];
		}
		$phpmailer->AltBody = $t . "\n\n--\nNordic Ikkunat & Ovet Oy · Myynti 045 7832 5070 · Asennus 045 7830 4746 · ikkunakauppias.fi";
	};
	add_filter( 'wp_mail_from_name', $from );
	add_filter( 'wp_mail_from', $from_email );
	add_action( 'phpmailer_init', $text );
	$ok = wp_mail( $to, $subject, $body, $headers );
	remove_filter( 'wp_mail_from_name', $from );
	remove_filter( 'wp_mail_from', $from_email );
	remove_action( 'phpmailer_init', $text );
	return $ok;
}

/**
 * Saako lomakkeelta tulleelle osoitteelle lähettää automaattisen kiitosviestin? Estää sen, että
 * roskapostibotit käyttävät lomaketta viestien lähettämiseen vieraisiin osoitteisiin:
 *  - sama osoite enintään kerran vuorokaudessa
 *  - samasta IP-osoitteesta enintään 5 tunnissa
 *  - kaikkiaan enintään 40 vuorokaudessa
 *  - ei, jos viestissä on linkkejä tai sähköpostiosoite näyttää virheelliseltä
 * Tarjouspyyntö tallentuu CRM:ään joka tapauksessa; vain automaattinen viesti jää lähtemättä.
 *
 * @return string '' = saa lähettää, muuten syy
 */
function nap_ack_block_reason( $email, $message = '' ) {
	$email = strtolower( trim( (string) $email ) );
	if ( ! is_email( $email ) ) {
		return 'virheellinen sähköpostiosoite';
	}
	if ( preg_match_all( '#https?://|www\.#i', (string) $message ) >= 1 ) {
		return 'viestissä on linkkejä (tyypillistä roskapostille)';
	}
	$ip   = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
	$keys = array(
		'nap_ack_e_' . md5( $email )             => array( 1, DAY_IN_SECONDS, 'samalle osoitteelle lähetetty jo tänään' ),
		'nap_ack_i_' . md5( $ip )                => array( 5, HOUR_IN_SECONDS, 'liikaa tarjouspyyntöjä samasta osoitteesta' ),
		'nap_ack_d_' . current_time( 'Ymd' )     => array( 40, DAY_IN_SECONDS, 'päivän enimmäismäärä täynnä' ),
	);
	foreach ( $keys as $key => $lim ) {
		if ( (int) get_transient( $key ) >= $lim[0] ) {
			return $lim[2];
		}
	}
	foreach ( $keys as $key => $lim ) {
		set_transient( $key, (int) get_transient( $key ) + 1, $lim[1] );
	}
	return '';
}

/**
 * Kiitosviesti tarjouspyynnöstä. Korvaa myös Nordic CRM:n (FluentCRM) vahvistusviestin, joten sisältö on sama
 * ja lisäksi Oma kohde -linkki. Vastaukset menevät asetusten vastausosoitteeseen.
 */
function nap_mail_welcome( $customer, $link, $measure_date = '', $delivery = 'asennus', $taloyhtio = false ) {
	$hi    = 'Hei ' . nap_first_name( $customer['name'] ) . ',';
	$terms = trim( (string) nap_settings()['measure_terms'] );
	if ( $taloyhtio ) {
		$subject = 'Tarjouspyyntönne on perillä';
		$lines   = array(
			$hi,
			'kiitos taloyhtiönne tarjouspyynnöstä. Otamme yhteyttä viimeistään seuraavana arkipäivänä.',
			'Taloyhtiön remontti etenee yleensä eri tahtiin kuin omakotitalon, koska päätökset tehdään hallituksessa ja yhtiökokouksessa. Kertokaa siis mielellään, missä vaiheessa hanke on: onko kyseessä alustava hinta-arvio esimerkiksi PTS:ää varten vai jo varsinainen tarjous.',
			'Oma kohde -sivulta näette hankkeen vaiheet ja myöhemmin myös tarjouksen, sopimuksen ja dokumentit. Kirjautumiseen ei tarvita salasanaa.',
			'Voitte vastata suoraan tähän viestiin.',
		);
	} elseif ( 'omat_mitat' === $delivery ) {
		$subject = 'Tarjouspyyntösi on perillä';
		$lines   = array(
			$hi,
			'kiitos tarjouspyynnöstä. Se tuli perille, ja otamme sinuun yhteyttä viimeistään seuraavana arkipäivänä.',
			'Toivoit tuotteet ilman asennusta ja mittaat ikkunat ja ovet itse. Sovimme, miten toimitat mitat meille, ja teemme tarjouksen niiden perusteella.',
		);
		if ( $terms ) {
			$lines[] = $terms;
		}
		$lines[] = 'Oma kohde -sivulta näet tilauksesi vaiheet ja myöhemmin myös tarjouksen, sopimuksen ja dokumentit. Kirjautumiseen ei tarvita salasanaa.';
		$lines[] = 'Jos mitat ovat jo valmiina, voit lähettää ne vastaamalla suoraan tähän viestiin. Voit lähettää mitat ja muut tiedot myös erikseen osoitteeseen ' . ( nap_settings()['reply_to'] ?: 'info@ikkunakauppias.fi' ) . '.';
	} else {
		$subject = 'Tarjouspyyntösi on perillä';
		$lines   = array(
			$hi,
			'kiitos tarjouspyynnöstä! Se tuli perille.',
			$measure_date
				? 'Maksuton mittauskäynti on sovittu ' . date_i18n( 'j.n.Y', strtotime( $measure_date ) ) . '. Käymme paikan päällä, mittaamme ikkunat ja ovet ja käymme vaihtoehdot läpi kanssasi. Sen jälkeen saat kirjallisen tarjouksen.'
				: 'Otamme sinuun yhteyttä viimeistään seuraavana arkipäivänä ja sovimme maksuttoman mittauskäynnin. Vahvistamme sovitun päivän vielä sähköpostilla. Käynnillä mittaamme ikkunat ja ovet ja käymme vaihtoehdot läpi kanssasi, ja sen jälkeen saat kirjallisen tarjouksen.',
		);
		if ( 'ilman_asennusta' === $delivery ) {
			$lines[] = 'Toivoit tuotteet ilman asennusta: mittaamme kohteen, ja toimitamme tuotteet asennettaviksi itse.';
		}
		$lines[] = 'Oma kohde -sivulta näet ' . ( 'asennus' === $delivery ? 'remonttisi' : 'tilauksesi' ) . ' vaiheet ja myöhemmin myös tarjouksen, sopimuksen ja dokumentit. Kirjautumiseen ei tarvita salasanaa.';
		$lines[] = 'Jos haluat kertoa jo nyt jotain lisää, kuten montako ikkunaa tai ovea on kyseessä tai mikä aikataulu sinulla on mielessä, vastaa suoraan tähän viestiin.';
	}
	return nap_mail( $customer['email'], $subject, 'Kiitos tarjouspyynnöstä' . ( $taloyhtio ? 'nne' : 'si' ), $lines, array( 'Avaa Oma kohde', $link ) );
}

/** Vahvistus, kun henkilökunta on kirjannut asiakkaan hyväksynnän (puhelimessa, paikan päällä tms.). */
function nap_mail_approval( $customer, $project, $a, $link ) {
	$me    = get_userdata( (int) ( $a['by'] ?: $project['owner_id'] ) ); // asiakkaan vastaus menee kirjaajalle tai vastuumyyjälle
	$lines = array(
		'Hei ' . nap_first_name( $customer['name'] ) . ',',
		'kiitos! Vahvistamme, että hyväksyit tarjouksemme ' . $a['how_label'] . ' ' . date_i18n( 'j.n.Y', strtotime( $a['at'] ) ) . '.',
		'Kohde: ' . $project['title'] . ( $project['address'] ? ', ' . $project['address'] : '' ) . "\n"
			. ( ! empty( $a['price_in_pdf'] ) || $a['value'] <= 0 ? 'Hinta ja erittely: tarjouksen (PDF) mukaan' : 'Hinta: ' . number_format( (float) $a['value'], 0, ',', ' ' ) . ' € (sis. alv)' ),
	);
	$delivery = nap_delivery( $project );
	$lines[]  = 'Toimitustapa: ' . nap_deliveries()[ $delivery ] . '.';
	$day      = 'asennus' === $delivery ? 'asennuspäivän' : 'toimituspäivän';
	$lines[]  = 'Seuraavaksi tilaamme tuotteet ja sovimme kanssasi ' . $day . '. Kun päivä on varmistunut, saat siitä vahvistuksen ja sopimusehdot sähköpostilla.';
	$lines[]  = 'Jos tiedoissa on virhe, vastaa tähän viestiin tai soita: myynti 045 7832 5070.';
	return nap_mail( $customer['email'], 'Vahvistus: tarjous hyväksytty', 'Kiitos tilauksestasi', $lines,
		array( 'Avaa Oma kohde', $link ), $me ? $me->display_name . ' <' . $me->user_email . '>' : null );
}

function nap_offer_mail_lines( $project, $offer ) {
	return 'Kohde: ' . $project['title'] . ( $project['address'] ? ', ' . $project['address'] : '' ) . "\n"
		. ( 'pdf' === ( $offer['mode'] ?? '' ) ? 'Tarjous erittelyineen on PDF-tiedostona linkin takana.' : 'Hinta: ' . number_format( (float) $offer['sum'], 0, ',', ' ' ) . ' € (sis. alv)' )
		. ( ! empty( $offer['valid_until'] ) ? "\nVoimassa: " . date_i18n( 'j.n.Y', strtotime( $offer['valid_until'] ) ) . ' asti' : '' );
}

/** Tarjous valmis: viestissä vain yhteenveto ja linkki, itse tarjous Oma kohde -sivulla. */
function nap_mail_offer( $customer, $project, $offer, $link ) {
	$me = get_userdata( (int) $offer['sent_by'] );
	return nap_mail( $customer['email'], ( $offer['version'] > 1 ? 'Päivitetty tarjous' : 'Tarjouksesi on valmis' ) . ' – Nordic Ikkunat & Ovet', ( $offer['version'] > 1 ? 'Päivitetty tarjous' : 'Tarjouksesi on valmis' ),
		array(
			'Hei ' . nap_first_name( $customer['name'] ) . ',',
			'kiitos odottamisesta! ' . ( $offer['version'] > 1 ? 'Päivitetty tarjous' : 'Tarjouksesi' ) . ' on valmis.',
			nap_offer_mail_lines( $project, $offer ),
			'Painike avaa tarjouksen suoraan, eikä kirjautumista tarvita. Voit hyväksyä tarjouksen ja valita maksutavan tai kysyä tarkennusta, ja palata samaan linkkiin myöhemmin.',
			'Jos haluat käydä tarjouksen läpi puhelimessa, vastaa tähän viestiin tai soita: myynti 045 7832 5070.',
		),
		array( 'Avaa tarjous', $link ), $me ? $me->display_name . ' <' . $me->user_email . '>' : null );
}

function nap_mail_offer_reminder( $customer, $project, $offer, $kind, $link ) {
	$me    = get_userdata( (int) $offer['sent_by'] );
	$first = 'exp' === $kind
		? 'tarjouksesi on voimassa ' . date_i18n( 'j.n.Y', strtotime( $offer['valid_until'] ) ) . ' asti. Jos haluat, että tarjouksen hinnat pysyvät, hyväksy tarjous sitä ennen.'
		: 'muistutamme, että tarjouksesi odottaa. Painikkeesta pääset suoraan tarjoukseen: voit hyväksyä sen tai kysyä tarkennusta, jos jokin askarruttaa.';
	return nap_mail( $customer['email'], 'exp' === $kind ? 'Tarjouksesi voimassaolo päättyy pian' : 'Muistutus: tarjouksesi odottaa', 'exp' === $kind ? 'Tarjous päättyy pian' : 'Tarjouksesi odottaa',
		array( 'Hei ' . nap_first_name( $customer['name'] ) . ',', $first, nap_offer_mail_lines( $project, $offer ), 'Jos et ole enää kiinnostunut, voit kertoa sen samalla sivulla, niin emme muistuta enempää.' ),
		array( 'Avaa tarjous', $link ), $me ? $me->display_name . ' <' . $me->user_email . '>' : null );
}

/** Käynnin vahvistus, muutos, peruutus tai muistutus. */
function nap_mail_visit( $customer, $project, $v, $kind, $link ) {
	$label = nap_visit_label( $v );
	$when  = nap_visit_when( $v, true );
	$inst  = $v['installer_id'] ? nap_user_name( $v['installer_id'] ) : '';
	$addr  = $project['address'] ?: trim( $customer['address'] . ', ' . $customer['city'], ', ' );
	$head  = array(
		'confirm'  => in_array( $v['type'], array( 'asennus', 'toimitus' ), true )
			? array( ( 'asennus' === $v['type'] ? 'Asennuspäivä' : 'Toimituspäivä' ) . ' vahvistettu: ' . $when, ( 'asennus' === $v['type'] ? 'Asennuspäivä' : 'Toimituspäivä' ) . ' on vahvistettu' )
			: array( $label . ' vahvistettu: ' . $when, $label . ' on sovittu' ),
		'update'   => array( $label . ': uusi aika ' . $when, $label . ' – aika muuttui' ),
		'cancel'   => array( $label . ' peruttu', $label . ' on peruttu' ),
		'reminder' => array( 'Muistutus: ' . mb_strtolower( $label ) . ' huomenna', 'Huomenna: ' . mb_strtolower( $label ) ),
	);
	$lines = array( 'Hei ' . nap_first_name( $customer['name'] ) . ',' );
	if ( 'cancel' === $kind ) {
		$lines[] = $label . ' ' . $when . ' on peruttu. Otamme yhteyttä ja sovimme uuden ajan.';
	} else {
		$install = in_array( $v['type'], array( 'asennus', 'toimitus' ), true );
		$lines[] = ( 'reminder' === $kind ? 'muistutamme huomisesta käynnistä.' : ( 'update' === $kind ? 'käynnin aika on muuttunut. Uusi aika:' : ( $install ? 'vahvistamme ' . ( 'asennus' === $v['type'] ? 'asennuspäivän' : 'toimituspäivän' ) . '. Alla ovat myös sopimusehdot.' : 'vahvistamme sovitun käynnin.' ) ) );
		$lines[] = $label . "\n" . ucfirst( $when ) . "\n" . $addr . ( $inst ? "\n" . 'Tulossa: ' . $inst : '' );
		$tips    = array(
			'mittaus'  => 'Käynti kestää yleensä noin tunnin. Mittaamme ikkunat ja ovet ja käymme vaihtoehdot läpi kanssasi.',
			'asennus'  => 'Valmistaudu asennukseen: tyhjennä ikkunalaudat ja ovien edustat, ota verhot ja kaihtimet pois ja varmista, että pääsemme kohteeseen sovittuun aikaan.',
			'toimitus' => 'Varmista, että toimitukselle on esteetön pääsy ja tuotteille tilaa.',
			'huolto'   => 'Huoltokäynti kestää yleensä noin tunnin.',
		);
		if ( isset( $tips[ $v['type'] ] ) ) {
			$lines[] = $tips[ $v['type'] ];
		}
		$lines[] = 'Jos aika ei sovi, soita tai vastaa tähän viestiin: myynti 045 7832 5070, asennus 045 7830 4746.';
		if ( 'confirm' === $kind && in_array( $v['type'], array( 'asennus', 'toimitus' ), true ) ) {
			$lines = array_merge( $lines, nap_contract_terms_lines( $project ) );
		}
	}
	$owner = $project['owner_id'] ? get_userdata( (int) $project['owner_id'] ) : null;
	return nap_mail( $customer['email'], $head[ $kind ][0], $head[ $kind ][1], $lines, array( 'Avaa Oma kohde', $link ), $owner ? $owner->display_name . ' <' . $owner->user_email . '>' : null );
}

/** Sopimusehdot asennus- tai toimituspäivän vahvistukseen (asetuksista). */
function nap_contract_terms_lines( $project ) {
	$s   = nap_settings();
	$out = array();
	if ( trim( (string) $s['contract_terms'] ) ) {
		$out[] = "Sopimusehdot\n" . trim( (string) $s['contract_terms'] );
	}
	if ( $s['terms_url'] ) {
		$out[] = 'Sopimusehdot kokonaisuudessaan: ' . $s['terms_url'];
	}
	if ( 'omat_mitat' === nap_delivery( $project ) && trim( (string) $s['measure_terms'] ) ) {
		$out[] = trim( (string) $s['measure_terms'] );
	}
	if ( trim( (string) $s['cancel_terms'] ) ) {
		$out[] = trim( (string) $s['cancel_terms'] );
	}
	return $out;
}

/** Kuitattu luovutus: yhteenveto asiakkaalle. */
function nap_mail_handover( $customer, $project, $v, $h, $link ) {
	$lines = array(
		'Hei ' . nap_first_name( $customer['name'] ) . ',',
		'kiitos! Kuittasit ' . ( 'asennus' === nap_delivery( $project ) ? 'työn' : 'toimituksen' ) . ' vastaanotetuksi ' . date_i18n( 'j.n.Y \k\l\o G.i', strtotime( $h['at'] ) ) . ' (' . $h['name'] . ').',
		'Kohde: ' . $project['title'] . ( $project['address'] ? ', ' . $project['address'] : '' ),
		"Tarkistettu:\n" . implode( "\n", array_map( function ( $i ) use ( $h ) {
			return ( in_array( $i, $h['checklist'], true ) ? '✓ ' : '– ' ) . $i;
		}, $h['items'] ) ),
	);
	if ( $h['remarks'] ) {
		$lines[] = "Huomautukset:\n" . $h['remarks'] . "\nHoidamme huomautukset ja olemme sinuun yhteydessä.";
	}
	$lines[] = 'Takuutodistuksen ja huolto-ohjeet saat erikseen. Jos jokin askarruttaa, soita: asennus 045 7830 4746.';
	return nap_mail( $customer['email'], 'Kiitos – työ on luovutettu', 'Työ on luovutettu', $lines, array( 'Avaa Oma kohde', $link ) );
}

function nap_mail_login_link( $customer, $link ) {
	return nap_mail(
		$customer['email'],
		'Kirjautumislinkki: Oma kohde',
		'Kirjaudu Oma kohde -sivulle',
		array( 'Pyysit kirjautumislinkkiä. Linkki on voimassa 7 päivää, ja voit käyttää sitä useamman kerran.', 'Jos et pyytänyt linkkiä, voit jättää tämän viestin huomiotta.' ),
		array( 'Kirjaudu', $link )
	);
}

function nap_mail_stage( $customer, $project, $link ) {
	$texts = array(
		1 => 'Sovimme kanssasi mittauskäynnin. Edustajamme mittaa ikkunat ja ovet ja käy vaihtoehdot läpi kanssasi.',
		2 => 'Tarjouksesi on valmis. Näet sen Oma kohde -sivulta.',
		3 => 'Kiitos! Tarjous on hyväksytty, ja kohteesi etenee tilaukseen.',
		4 => 'Ikkunat ja ovet on tilattu Skaalan tehtaalta. Ilmoitamme toimitus- ja asennusajasta.',
		5 => 'Asennus on aikataulutettu. Ohjeet valmistautumiseen löydät Oma kohde -sivulta.',
		6 => 'Remonttisi on valmis. Kiitos, että valitsit meidät! Takuutodistuksen ja huolto-ohjeet saat luovutuksen yhteydessä.',
	);
	$delivery = nap_delivery( $project );
	if ( 'asennus' !== $delivery ) {
		$texts[1] = 'omat_mitat' === $delivery ? 'Odotamme mittojasi. Kun saamme ne, teemme tarjouksen niiden perusteella.' : 'Sovimme kanssasi mittauskäynnin. Edustajamme mittaa ikkunat ja ovet ja käy vaihtoehdot läpi kanssasi.';
		$texts[4] = 'Ikkunat ja ovet on tilattu Skaalan tehtaalta. Ilmoitamme toimitusajasta.';
		$texts[5] = 'Toimitus on aikataulutettu. Sovimme kanssasi toimituksen tai noudon yksityiskohdat.';
		$texts[6] = 'Tilauksesi on toimitettu. Kiitos, että valitsit meidät!';
	}
	$stage = (int) $project['stage'];
	if ( empty( $texts[ $stage ] ) ) {
		return false;
	}
	$names = nap_stage_names( $delivery );
	return nap_mail(
		$customer['email'],
		'Kohteesi eteni: ' . $names[ $stage ],
		$names[ $stage ],
		array( 'Hei ' . nap_first_name( $customer['name'] ) . ',', $texts[ $stage ] ),
		array( 'Avaa Oma kohde', $link )
	);
}

function nap_first_name( $name ) {
	$p = preg_split( '/\s+/', trim( (string) $name ) );
	return $p[0] ? $p[0] : 'hei';
}
