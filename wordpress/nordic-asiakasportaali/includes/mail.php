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

function nap_mail_welcome( $customer, $link, $measure_date = '' ) {
	return nap_mail(
		$customer['email'],
		'Kiitos tarjouspyynnöstä – seuraa kohdettasi',
		'Kiitos tarjouspyynnöstäsi',
		array(
			'Hei ' . nap_first_name( $customer['name'] ) . ',',
			$measure_date
				? 'Olemme vastaanottaneet tarjouspyyntösi. Maksuton mittauskäynti on sovittu ' . date_i18n( 'j.n.Y', strtotime( $measure_date ) ) . '.'
				: 'Olemme vastaanottaneet tarjouspyyntösi. Otamme sinuun yhteyttä ja sovimme maksuttoman mittauskäynnin.',
			'Oma kohde -sivulta näet remonttisi vaiheet ja myöhemmin myös tarjouksen, sopimuksen ja dokumentit. Kirjautumiseen ei tarvita salasanaa.',
		),
		array( 'Avaa Oma kohde', $link )
	);
}

/** Vahvistus, kun henkilökunta on kirjannut asiakkaan hyväksynnän (puhelimessa, paikan päällä tms.). */
function nap_mail_approval( $customer, $project, $a, $link ) {
	$me    = get_userdata( (int) $a['by'] );
	$lines = array(
		'Hei ' . nap_first_name( $customer['name'] ) . ',',
		'kiitos! Vahvistamme, että hyväksyit tarjouksemme ' . $a['how_label'] . ' ' . date_i18n( 'j.n.Y', strtotime( $a['at'] ) ) . '.',
		'Kohde: ' . $project['title'] . ( $project['address'] ? ', ' . $project['address'] : '' ) . "\n"
			. 'Hinta: ' . number_format( (float) $a['value'], 0, ',', ' ' ) . ' € (sis. alv)'
			. ( 'auki' !== $a['payment'] ? "\nMaksutapa: " . $a['pay_label'] : '' ),
	);
	$lines[] = 'Seuraavaksi tilaamme tuotteet Skaalalta ja sovimme kanssasi asennuspäivän. Voit seurata kohdetta Oma kohde -sivulla.';
	$lines[] = 'Jos tiedoissa on virhe, vastaa tähän viestiin tai soita: myynti 045 7832 5070.';
	$terms   = trim( (string) nap_settings()['cancel_terms'] );
	if ( $terms ) {
		$lines[] = $terms;
	}
	return nap_mail( $customer['email'], 'Vahvistus: tarjous hyväksytty', 'Kiitos tilauksestasi', $lines,
		array( 'Avaa Oma kohde', $link ), $me ? $me->display_name . ' <' . $me->user_email . '>' : null );
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
	$stage = (int) $project['stage'];
	if ( empty( $texts[ $stage ] ) ) {
		return false;
	}
	return nap_mail(
		$customer['email'],
		'Kohteesi eteni: ' . NAP_STAGES[ $stage ],
		NAP_STAGES[ $stage ],
		array( 'Hei ' . nap_first_name( $customer['name'] ) . ',', $texts[ $stage ] ),
		array( 'Avaa Oma kohde', $link )
	);
}

function nap_first_name( $name ) {
	$p = preg_split( '/\s+/', trim( (string) $name ) );
	return $p[0] ? $p[0] : 'hei';
}
