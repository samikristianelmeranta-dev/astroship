<?php
/**
 * Sähköpostit asiakkaalle ja henkilökunnalle. Viesteissä on vain linkki portaaliin,
 * ei sopimuksia tai muita liitteitä.
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
	add_filter( 'wp_mail_from_name', $from );
	$ok = wp_mail( $to, $subject, $body, $headers );
	remove_filter( 'wp_mail_from_name', $from );
	return $ok;
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
			'kiitos tarjouspyynnöstä. Se tuli perille, ja otamme sinuun yhteyttä viimeistään seuraavana arkipäivänä.',
			$measure_date
				? 'Maksuton mittauskäynti on sovittu ' . date_i18n( 'j.n.Y', strtotime( $measure_date ) ) . '. Käymme paikan päällä, mittaamme karmit ja katsomme, mitkä mallit sopivat taloosi. Sen jälkeen saat kirjallisen tarjouksen.'
				: 'Ensimmäiseksi sovimme mittauskäynnin. Se on maksuton, eikä se sido sinua mihinkään. Käymme paikan päällä, mittaamme karmit ja katsomme, mitkä mallit sopivat taloosi. Sen jälkeen saat kirjallisen tarjouksen.',
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
			. 'Hinta: ' . number_format( (float) $a['value'], 0, ',', ' ' ) . ' € (sis. alv)'
			. ( 'auki' !== $a['payment'] ? "\nMaksutapa: " . $a['pay_label'] : '' ),
	);
	$delivery = nap_delivery( $project );
	$lines[]  = 'Toimitustapa: ' . nap_deliveries()[ $delivery ] . '.';
	$lines[]  = 'Seuraavaksi tilaamme tuotteet Skaalalta ja sovimme kanssasi ' . ( 'asennus' === $delivery ? 'asennuspäivän' : 'toimituksen' ) . '. Voit seurata kohdetta Oma kohde -sivulla.';
	if ( 'omat_mitat' === $delivery && trim( (string) nap_settings()['measure_terms'] ) ) {
		$lines[] = trim( (string) nap_settings()['measure_terms'] );
	}
	$lines[] = 'Jos tiedoissa on virhe, vastaa tähän viestiin tai soita: myynti 045 7832 5070.';
	$terms   = trim( (string) nap_settings()['cancel_terms'] );
	if ( $terms ) {
		$lines[] = $terms;
	}
	return nap_mail( $customer['email'], 'Vahvistus: tarjous hyväksytty', 'Kiitos tilauksestasi', $lines,
		array( 'Avaa Oma kohde', $link ), $me ? $me->display_name . ' <' . $me->user_email . '>' : null );
}

function nap_offer_mail_lines( $project, $offer ) {
	return 'Kohde: ' . $project['title'] . ( $project['address'] ? ', ' . $project['address'] : '' ) . "\n"
		. 'Hinta: ' . number_format( (float) $offer['sum'], 0, ',', ' ' ) . ' € (sis. alv)'
		. ( ! empty( $offer['valid_until'] ) ? "\nVoimassa: " . date_i18n( 'j.n.Y', strtotime( $offer['valid_until'] ) ) . ' asti' : '' );
}

/** Tarjous valmis: viestissä vain yhteenveto ja linkki, itse tarjous Oma kohde -sivulla. */
function nap_mail_offer( $customer, $project, $offer, $link ) {
	$me = get_userdata( (int) $offer['sent_by'] );
	return nap_mail( $customer['email'], ( $offer['version'] > 1 ? 'Päivitetty tarjous' : 'Tarjouksesi on valmis' ) . ' – Nordic Ikkunat & Ovet', ( $offer['version'] > 1 ? 'Päivitetty tarjous' : 'Tarjouksesi on valmis' ),
		array(
			'Hei ' . nap_first_name( $customer['name'] ) . ',',
			'kiitos odottamisesta! ' . ( $offer['version'] > 1 ? 'Päivitetty tarjous' : 'Tarjouksesi' ) . ' on nyt luettavissa Oma kohde -sivulla.',
			nap_offer_mail_lines( $project, $offer ),
			'Sivulla voit avata tarjouksen, hyväksyä sen ja valita maksutavan tai kysyä tarkennusta. Kirjautumiseen ei tarvita salasanaa.',
			'Jos haluat käydä tarjouksen läpi puhelimessa, vastaa tähän viestiin tai soita: myynti 045 7832 5070.',
		),
		array( 'Avaa tarjous', $link ), $me ? $me->display_name . ' <' . $me->user_email . '>' : null );
}

function nap_mail_offer_reminder( $customer, $project, $offer, $kind, $link ) {
	$me    = get_userdata( (int) $offer['sent_by'] );
	$first = 'exp' === $kind
		? 'tarjouksesi on voimassa ' . date_i18n( 'j.n.Y', strtotime( $offer['valid_until'] ) ) . ' asti. Jos haluat, että tarjouksen hinnat pysyvät, hyväksy tarjous sitä ennen Oma kohde -sivulla.'
		: 'muistutamme, että tarjouksesi odottaa Oma kohde -sivulla. Siellä voit hyväksyä sen tai kysyä tarkennusta, jos jokin askarruttaa.';
	return nap_mail( $customer['email'], 'exp' === $kind ? 'Tarjouksesi voimassaolo päättyy pian' : 'Muistutus: tarjouksesi odottaa', 'exp' === $kind ? 'Tarjous päättyy pian' : 'Tarjouksesi odottaa',
		array( 'Hei ' . nap_first_name( $customer['name'] ) . ',', $first, nap_offer_mail_lines( $project, $offer ), 'Jos et ole enää kiinnostunut, voit kertoa sen samalla sivulla, niin emme muistuta enempää.' ),
		array( 'Avaa tarjous', $link ), $me ? $me->display_name . ' <' . $me->user_email . '>' : null );
}

function nap_mail_login_link( $customer, $link ) {
	return nap_mail(
		$customer['email'],
		'Kirjautumislinkki: Oma kohde',
		'Kirjaudu Oma kohde -sivulle',
		array( 'Pyysit kirjautumislinkkiä. Linkki on voimassa 7 päivää ja toimii kerran.', 'Jos et pyytänyt linkkiä, voit jättää tämän viestin huomiotta.' ),
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
