<?php
/**
 * Huoltolomake: [nap_huoltolomake]
 *
 * Huoltosivujen oma yhteydenottolomake. Kysyy kohteen perustiedot ja ostovalmiuden, luo CRM:ään asiakkaan,
 * kohteen ja tehtävän (nap_intake) ja tallentaa vastaukset kohteen tietoihin.
 *
 * Lomake toimii ilman JavaScriptiä (POST + uudelleenohjaus). Roskapostisuoja: piilokenttä, allekirjoitettu
 * aikaleima sekä osoite- ja sähköpostikohtaiset määrärajat. Noncea ei käytetä, koska sivut ovat välimuistissa.
 */

defined( 'ABSPATH' ) || exit;

/** Lomakkeen valinnat: avain → asiakkaalle näkyvä teksti. Samat tekstit tallentuvat CRM:ään. */
function nap_huolto_options() {
	return array(
		'kohde'    => array(
			'omakotitalo' => 'Omakotitalo',
			'rivitalo'    => 'Rivitalo',
			'taloyhtio'   => 'Taloyhtiö',
		),
		'ika'      => array(
			''      => 'En tiedä',
			'0-10'  => 'Alle 10 vuotta',
			'10-20' => '10–20 vuotta',
			'20-30' => '20–30 vuotta',
			'30-40' => '30–40 vuotta',
			'40+'   => 'Yli 40 vuotta',
		),
		'ongelmat' => array(
			'veto'      => 'Vetoa ikkunoista tai ovista',
			'huurre'    => 'Huurretta tai kosteutta lasien välissä',
			'tiivisteet' => 'Tiivisteet kuluneet tai irti',
			'kaynti'    => 'Ikkuna tai ovi ei sulkeudu kunnolla',
			'maali'     => 'Maali hilseilee',
			'laho'      => 'Lahoa tai pehmeää puuta',
			'lasi'      => 'Rikkoutunut tai samea lasi',
			'helat'     => 'Helat, painikkeet tai lukitus eivät toimi',
			'melu'      => 'Melu kuuluu sisälle',
			'muu'       => 'Jokin muu',
		),
		'ajankohta' => array(
			'lahiaikoina' => 'Lähiaikoina',
			'myohemmin'   => 'Vasta myöhemmin',
			'selvitan'    => 'Selvitän vasta vaihtoehtoja',
		),
		'paattaja' => array(
			'itse'    => 'Kyllä, päätän itse',
			'yhdessa' => 'Päätän yhdessä toisen kanssa',
			'muu'     => 'En, päätöksen tekee joku muu (esim. hallitus)',
		),
		'valmius'  => array(
			'heti'      => 'Heti, kun sopiva aika löytyy',
			'1-3kk'     => '1–3 kuukauden sisällä',
			'3-6kk'     => '3–6 kuukauden sisällä',
			'myohemmin' => 'Myöhemmin kuin puolen vuoden päästä',
			'en-tieda'  => 'En osaa vielä sanoa',
		),
	);
}

function nap_huolto_token( $time ) {
	return substr( hash_hmac( 'sha256', 'nap-huolto|' . (int) $time, wp_salt( 'nonce' ) ), 0, 24 );
}

add_shortcode( 'nap_huoltolomake', 'nap_huolto_form_html' );

function nap_huolto_form_html() {
	static $css = false;
	$o      = nap_huolto_options();
	$time   = time();
	$status = isset( $_GET['huolto'] ) ? sanitize_key( wp_unslash( $_GET['huolto'] ) ) : ''; // phpcs:ignore
	$msgs   = array(
		'puuttuu' => 'Täytä tähdellä merkityt kentät ja tarkista sähköpostiosoite ja postinumero.',
		'linkki'  => 'Poista viestistä verkko-osoitteet ja lähetä uudelleen.',
		'virhe'   => 'Lähetys ei onnistunut. Yritä hetken kuluttua uudelleen tai soita meille.',
	);
	$out = '';
	if ( ! $css ) {
		$css  = true;
		$out .= '<style>' . nap_huolto_form_css() . '</style>';
	}
	$out .= '<div class="nap-hf" id="huoltolomake">';
	if ( 'ok' === $status ) {
		return $out . '<p class="nap-hf-done" role="status"><strong>Kiitos! Huoltopyyntösi on vastaanotettu.</strong> Otamme yhteyttä viimeistään seuraavana arkipäivänä ja sovimme maksuttoman kartoituskäynnin.</p></div>';
	}
	if ( isset( $msgs[ $status ] ) ) {
		$out .= '<p class="nap-hf-error" role="alert">' . esc_html( $msgs[ $status ] ) . '</p>';
	}
	$radio = function ( $name, $legend, $required = true ) use ( $o ) {
		$h = '<fieldset class="nap-hf-set"><legend>' . esc_html( $legend ) . ( $required ? ' <span aria-hidden="true">*</span>' : '' ) . '</legend><div class="nap-hf-opts">';
		foreach ( $o[ $name ] as $k => $label ) {
			$h .= '<label><input type="radio" name="hf_' . esc_attr( $name ) . '" value="' . esc_attr( $k ) . '"' . ( $required ? ' required' : '' ) . '> <span>' . esc_html( $label ) . '</span></label>';
		}
		return $h . '</div></fieldset>';
	};
	$text = function ( $name, $label, $type = 'text', $attrs = '', $required = true ) {
		return '<label class="nap-hf-field"><span>' . esc_html( $label ) . ( $required ? ' <span aria-hidden="true">*</span>' : '' ) . '</span><input type="' . esc_attr( $type ) . '" name="hf_' . esc_attr( $name ) . '"' . ( $required ? ' required' : '' ) . ' ' . $attrs . '></label>';
	};

	$out .= '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
	$out .= '<input type="hidden" name="action" value="nap_huolto">';
	$out .= '<input type="hidden" name="hf_t" value="' . (int) $time . '"><input type="hidden" name="hf_k" value="' . esc_attr( nap_huolto_token( $time ) ) . '">';
	$out .= '<input type="hidden" name="hf_back" value="' . esc_url( get_permalink() ) . '">';
	$out .= '<div class="nap-hf-hp" aria-hidden="true"><label>Jätä tämä kenttä tyhjäksi <input type="text" name="hf_verkkosivu" tabindex="-1" autocomplete="off"></label></div>';

	$out .= '<div class="nap-hf-grid">';
	$out .= $text( 'nimi', 'Nimi', 'text', 'autocomplete="name" maxlength="100"' );
	$out .= $text( 'puhelin', 'Puhelin', 'tel', 'autocomplete="tel" maxlength="30"' );
	$out .= $text( 'email', 'Sähköposti', 'email', 'autocomplete="email" maxlength="120"' );
	$out .= $text( 'paikkakunta', 'Paikkakunta', 'text', 'autocomplete="address-level2" maxlength="60"' );
	$out .= $text( 'postinumero', 'Postinumero', 'text', 'inputmode="numeric" pattern="[0-9]{5}" maxlength="5" autocomplete="postal-code"' );
	$out .= '</div>';

	$out .= $radio( 'kohde', 'Onko kyseessä omakotitalo, rivitalo vai taloyhtiö?' );

	$out .= '<div class="nap-hf-grid">';
	$out .= $text( 'ikkunat', 'Ikkunoiden määrä (arvio riittää)', 'number', 'min="0" max="2000" inputmode="numeric"', false );
	$out .= $text( 'ovet', 'Ovien määrä (arvio riittää)', 'number', 'min="0" max="2000" inputmode="numeric"', false );
	$out .= '<label class="nap-hf-field"><span>Ikkunoiden ikä, jos tiedossa</span><select name="hf_ika">';
	foreach ( $o['ika'] as $k => $label ) {
		$out .= '<option value="' . esc_attr( $k ) . '">' . esc_html( $label ) . '</option>';
	}
	$out .= '</select></label></div>';

	$out .= '<fieldset class="nap-hf-set"><legend>Mitä ongelmia ikkunoissa tai ovissa on?</legend><div class="nap-hf-opts nap-hf-cols">';
	foreach ( $o['ongelmat'] as $k => $label ) {
		$out .= '<label><input type="checkbox" name="hf_ongelmat[]" value="' . esc_attr( $k ) . '"> <span>' . esc_html( $label ) . '</span></label>';
	}
	$out .= '</div></fieldset>';
	$out .= '<label class="nap-hf-field"><span>Kerro halutessasi lisää</span><textarea name="hf_viesti" rows="3" maxlength="3000" placeholder="Esim. makuuhuoneiden ikkunoista vetää ja ulko-ovi laahaa kynnykseen."></textarea></label>';

	$out .= $radio( 'ajankohta', 'Onko tarkoitus tehdä huolto lähiaikoina vai vasta myöhemmin?' );
	$out .= $radio( 'paattaja', 'Päätätkö itse työn tilaamisesta?' );
	$out .= '<div class="nap-hf-key">' . $radio( 'valmius', 'Milloin olisit valmis toteuttamaan huollon, jos saat sopivan tarjouksen?' ) . '</div>';

	$out .= '<button type="submit" class="btn btn-primary nap-hf-submit">Lähetä huoltopyyntö</button>';
	$privacy = get_privacy_policy_url();
	$out    .= '<p class="nap-hf-note">Kartoituskäynti ja tarjous ovat maksuttomia eivätkä sido sinua mihinkään. Käytämme tietojasi vain yhteydenottoon ja tarjouksen tekemiseen.' . ( $privacy ? ' <a href="' . esc_url( $privacy ) . '">Tietosuojaseloste</a>' : '' ) . '</p>';
	$out    .= '</form></div>';
	return $out;
}

function nap_huolto_form_css() {
	return '.nap-hf{background:#fff;color:#16303b;border-radius:16px;padding:28px;text-align:left;max-width:860px;margin:0 auto;box-shadow:0 18px 50px rgba(0,0,0,.18)}'
		. '.nap-hf form{display:flex;flex-direction:column;gap:20px}'
		. '.nap-hf-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(min(100%,220px),1fr));gap:14px}'
		. '.nap-hf-field{display:flex;flex-direction:column;gap:6px;font-weight:600;font-size:15px;min-width:0}'
		. '.nap-hf-field input,.nap-hf-field select,.nap-hf-field textarea{font:inherit;font-weight:400;font-size:16px;color:#16303b;background:#fff;border:1px solid #b8c7cd;border-radius:10px;padding:11px 12px;width:100%;min-width:0}'
		. '.nap-hf-field textarea{resize:vertical;min-height:90px}'
		. '.nap-hf input:focus-visible,.nap-hf select:focus-visible,.nap-hf textarea:focus-visible{outline:3px solid #ffd866;outline-offset:1px}'
		. '.nap-hf-set{border:0;margin:0;padding:0;min-width:0}'
		. '.nap-hf-set legend{font-weight:700;font-size:16px;padding:0;margin-bottom:10px;color:#16303b}'
		. '.nap-hf-opts{display:flex;flex-direction:column;gap:8px}'
		. '.nap-hf-cols{display:grid;grid-template-columns:repeat(auto-fit,minmax(min(100%,260px),1fr));gap:8px 18px}'
		. '.nap-hf-opts label{display:flex;gap:10px;align-items:flex-start;font-size:15.5px;line-height:1.4;cursor:pointer}'
		. '.nap-hf-opts input{width:20px;height:20px;margin:1px 0 0;flex:none;accent-color:#1d5e79}'
		. '.nap-hf-key{background:#fff4cf;border-left:5px solid #f4c64a;border-radius:10px;padding:16px 18px}'
		. '.nap-hf-submit{align-self:flex-start}'
		. '.nap-hf p.nap-hf-note{margin:0;font-size:14px;color:#4d6369;max-width:none;text-align:left}'
		. '.nap-hf p.nap-hf-done,.nap-hf p.nap-hf-error{color:#16303b;max-width:none;text-align:left}'
		. '.nap-hf-note a{color:#1d5e79}'
		. '.nap-hf-hp{position:absolute;left:-9999px;width:1px;height:1px;overflow:hidden}'
		. '.nap-hf-error{margin:0 0 16px;padding:12px 14px;background:#f8e3e0;border-left:4px solid #b23b2e;border-radius:8px;font-weight:600}'
		. '.nap-hf-done{margin:0;padding:16px 18px;background:#e3f2ea;border-left:4px solid #2c7a4e;border-radius:8px;font-size:17px;line-height:1.5}'
		. '@media (max-width:600px){.nap-hf{padding:18px;border-radius:12px}.nap-hf-submit{align-self:stretch;text-align:center}}';
}

add_action( 'admin_post_nopriv_nap_huolto', 'nap_huolto_handle' );
add_action( 'admin_post_nap_huolto', 'nap_huolto_handle' );

function nap_huolto_handle() {
	// phpcs:disable WordPress.Security.NonceVerification.Missing -- julkinen lomake, suojaus aikaleimalla ja piilokentällä.
	$back = isset( $_POST['hf_back'] ) ? wp_validate_redirect( esc_url_raw( wp_unslash( $_POST['hf_back'] ) ), home_url( '/huolto/' ) ) : home_url( '/huolto/' );
	$back = remove_query_arg( 'huolto', $back );
	$go   = function ( $status ) use ( $back ) {
		wp_safe_redirect( add_query_arg( 'huolto', $status, $back ) . '#huoltolomake' );
		exit;
	};
	$time = isset( $_POST['hf_t'] ) ? (int) $_POST['hf_t'] : 0;
	$key  = isset( $_POST['hf_k'] ) ? sanitize_text_field( wp_unslash( $_POST['hf_k'] ) ) : '';
	$age  = time() - $time;
	// Robotille näytetään onnistuminen, mitään ei tallenneta.
	if ( ! empty( $_POST['hf_verkkosivu'] ) || ! hash_equals( nap_huolto_token( $time ), $key ) || $age < 4 || $age > WEEK_IN_SECONDS ) {
		$go( 'ok' );
	}

	$get = function ( $name, $max = 120 ) {
		return isset( $_POST[ 'hf_' . $name ] ) && is_scalar( $_POST[ 'hf_' . $name ] ) ? mb_substr( sanitize_text_field( wp_unslash( $_POST[ 'hf_' . $name ] ) ), 0, $max ) : '';
	};
	$o       = nap_huolto_options();
	$pick    = function ( $name ) use ( $o, $get ) {
		$v = $get( $name, 30 );
		return isset( $o[ $name ][ $v ] ) ? $v : null;
	};
	$name    = $get( 'nimi', 100 );
	$phone   = $get( 'puhelin', 30 );
	$email   = isset( $_POST['hf_email'] ) ? sanitize_email( wp_unslash( $_POST['hf_email'] ) ) : '';
	$city    = $get( 'paikkakunta', 60 );
	$zip     = preg_replace( '/\D/', '', $get( 'postinumero', 10 ) );
	$kohde   = $pick( 'kohde' );
	$ajank   = $pick( 'ajankohta' );
	$paat    = $pick( 'paattaja' );
	$valmius = $pick( 'valmius' );
	$ika     = $pick( 'ika' );
	$windows = min( 2000, max( 0, (int) $get( 'ikkunat', 6 ) ) );
	$doors   = min( 2000, max( 0, (int) $get( 'ovet', 6 ) ) );
	$message = isset( $_POST['hf_viesti'] ) ? mb_substr( sanitize_textarea_field( wp_unslash( $_POST['hf_viesti'] ) ), 0, 3000 ) : '';
	$issues  = array();
	if ( isset( $_POST['hf_ongelmat'] ) && is_array( $_POST['hf_ongelmat'] ) ) {
		foreach ( wp_unslash( $_POST['hf_ongelmat'] ) as $k ) {
			$k = is_scalar( $k ) ? sanitize_key( $k ) : '';
			if ( isset( $o['ongelmat'][ $k ] ) ) {
				$issues[ $k ] = $o['ongelmat'][ $k ];
			}
		}
	}
	// phpcs:enable

	if ( '' === $name || ! is_email( $email ) || ! preg_match( '/\d{5,}/', preg_replace( '/[\s\-()+]/', '', $phone ) ) || '' === $city || 5 !== strlen( $zip ) || ! $kohde || ! $ajank || ! $paat || ! $valmius ) {
		$go( 'puuttuu' );
	}
	if ( preg_match( '#https?://|www\.#i', $message . ' ' . $name . ' ' . $city ) ) {
		$go( 'linkki' );
	}

	// Määrärajat: 5 pyyntöä tunnissa samasta osoitteesta, 2 vuorokaudessa samalla sähköpostilla, 60 vuorokaudessa koko sivustolta.
	$ip    = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
	$k_ip  = 'nap_hf_ip_' . md5( $ip );
	$k_em  = 'nap_hf_em_' . md5( strtolower( $email ) );
	$k_day = 'nap_hf_day_' . gmdate( 'Ymd' );
	if ( (int) get_transient( $k_ip ) >= 5 || (int) get_transient( $k_em ) >= 2 || (int) get_transient( $k_day ) >= 60 ) {
		$go( 'virhe' );
	}
	set_transient( $k_ip, (int) get_transient( $k_ip ) + 1, HOUR_IN_SECONDS );
	set_transient( $k_em, (int) get_transient( $k_em ) + 1, DAY_IN_SECONDS );
	set_transient( $k_day, (int) get_transient( $k_day ) + 1, DAY_IN_SECONDS );

	// Ostovalmius: valmis heti tai 1–3 kk sisällä, aikoo tehdä lähiaikoina ja on mukana päätöksessä.
	$hot   = in_array( $valmius, array( 'heti', '1-3kk' ), true ) && 'lahiaikoina' === $ajank && 'muu' !== $paat;
	$lines = array_filter( array(
		'Valmis toteuttamaan: ' . $o['valmius'][ $valmius ],
		'Ajankohta: ' . $o['ajankohta'][ $ajank ],
		'Päättää itse: ' . $o['paattaja'][ $paat ],
		'Kohde: ' . $o['kohde'][ $kohde ] . ', ' . $zip . ' ' . $city,
		$windows ? 'Ikkunoita: noin ' . $windows : '',
		$doors ? 'Ovia: noin ' . $doors : '',
		null !== $ika && '' !== $ika ? 'Ikkunoiden ikä: ' . $o['ika'][ $ika ] : '',
		$issues ? 'Ongelmat: ' . implode( ', ', $issues ) : '',
		'' !== $message ? 'Lisätiedot: ' . $message : '',
	) );

	$result = nap_intake( array(
		'email'    => $email,
		'name'     => function_exists( 'nordic_crm_clean_name' ) ? nordic_crm_clean_name( $name ) : $name,
		'phone'    => $phone,
		'address'  => '',
		'city'     => trim( $zip . ' ' . $city ),
		'products' => 'Ikkuna- ja ovihuolto' . ( 'taloyhtio' === $kohde ? ' (taloyhtiö)' : '' ),
		'message'  => implode( "\n", $lines ),
		'source'   => 'Huoltolomake',
		'delivery' => 'asennus',
		'page'     => $back,
		'kind'     => 'huolto',
		'hot'      => $hot,
	) );

	/**
	 * Uusi huoltopyyntö. $answers sisältää valintojen avaimet jatkokäsittelyä varten.
	 */
	do_action( 'nap_huolto_lead', $result, compact( 'email', 'name', 'phone', 'city', 'zip', 'kohde', 'windows', 'doors', 'ika', 'ajank', 'paat', 'valmius', 'hot' ) + array( 'issues' => array_keys( $issues ) ) );

	$go( $result ? 'ok' : 'virhe' );
}

/* =========================================================================
 * Yhteydenottolomake: [nap_yhteydenotto]
 *
 * Yleinen viestilomake (ei tarjouspyyntö). Viesti lähtee henkilökunnalle sähköpostilla, ja vastaus
 * menee suoraan lähettäjälle. Jos lähettäjä on jo CRM:n asiakas, viesti kirjataan asiakkaan historiaan.
 * ========================================================================= */

function nap_contact_topics() {
	return array(
		'uudet'   => 'Uudet ikkunat tai ovet',
		'huolto'  => 'Huolto tai korjaus',
		'tarjous' => 'Tarjous tai tilaus',
		'asennus' => 'Asennus tai aikataulu',
		'lasku'   => 'Lasku tai maksaminen',
		'muu'     => 'Muu asia',
	);
}

add_shortcode( 'nap_yhteydenotto', 'nap_contact_form_html' );

function nap_contact_form_html() {
	static $css = false;
	$time   = time();
	$status = isset( $_GET['viesti'] ) ? sanitize_key( wp_unslash( $_GET['viesti'] ) ) : ''; // phpcs:ignore
	$msgs   = array(
		'puuttuu' => 'Täytä nimi, sähköposti ja viesti ja tarkista sähköpostiosoite.',
		'linkki'  => 'Poista viestistä verkko-osoitteet ja lähetä uudelleen.',
		'virhe'   => 'Lähetys ei onnistunut. Yritä hetken kuluttua uudelleen tai soita meille.',
	);
	$out = '';
	if ( ! $css ) {
		$css  = true;
		$out .= '<style>' . nap_huolto_form_css() . '</style>';
	}
	$out .= '<div class="nap-hf nap-cf" id="yhteydenotto" data-no-quote>';
	if ( 'ok' === $status ) {
		return $out . '<p class="nap-hf-done" role="status"><strong>Kiitos viestistäsi!</strong> Vastaamme viimeistään seuraavana arkipäivänä.</p></div>';
	}
	if ( isset( $msgs[ $status ] ) ) {
		$out .= '<p class="nap-hf-error" role="alert">' . esc_html( $msgs[ $status ] ) . '</p>';
	}
	$out .= '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
	$out .= '<input type="hidden" name="action" value="nap_yhteydenotto">';
	$out .= '<input type="hidden" name="cf_t" value="' . (int) $time . '"><input type="hidden" name="cf_k" value="' . esc_attr( nap_huolto_token( $time ) ) . '">';
	$out .= '<input type="hidden" name="cf_back" value="' . esc_url( get_permalink() ) . '">';
	$out .= '<div class="nap-hf-hp" aria-hidden="true"><label>Jätä tämä kenttä tyhjäksi <input type="text" name="cf_verkkosivu" tabindex="-1" autocomplete="off"></label></div>';
	$out .= '<div class="nap-hf-grid">';
	$out .= '<label class="nap-hf-field"><span>Nimi <span aria-hidden="true">*</span></span><input type="text" name="cf_nimi" required autocomplete="name" maxlength="100"></label>';
	$out .= '<label class="nap-hf-field"><span>Sähköposti <span aria-hidden="true">*</span></span><input type="email" name="cf_email" required autocomplete="email" maxlength="120"></label>';
	$out .= '<label class="nap-hf-field"><span>Puhelin</span><input type="tel" name="cf_puhelin" autocomplete="tel" maxlength="30"></label>';
	$out .= '<label class="nap-hf-field"><span>Aihe</span><select name="cf_aihe">';
	foreach ( nap_contact_topics() as $k => $label ) {
		$out .= '<option value="' . esc_attr( $k ) . '">' . esc_html( $label ) . '</option>';
	}
	$out .= '</select></label></div>';
	$out .= '<label class="nap-hf-field"><span>Viesti <span aria-hidden="true">*</span></span><textarea name="cf_viesti" rows="5" required maxlength="4000"></textarea></label>';
	$out .= '<button type="submit" class="btn btn-primary nap-hf-submit">Lähetä viesti</button>';
	$privacy = get_privacy_policy_url();
	$out    .= '<p class="nap-hf-note">Vastaamme viimeistään seuraavana arkipäivänä. Käytämme tietojasi vain viestiisi vastaamiseen.' . ( $privacy ? ' <a href="' . esc_url( $privacy ) . '">Tietosuojaseloste</a>' : '' ) . '</p>';
	return $out . '</form></div>';
}

add_action( 'admin_post_nopriv_nap_yhteydenotto', 'nap_contact_handle' );
add_action( 'admin_post_nap_yhteydenotto', 'nap_contact_handle' );

function nap_contact_handle() {
	// phpcs:disable WordPress.Security.NonceVerification.Missing -- julkinen lomake, suojaus aikaleimalla ja piilokentällä.
	$back = isset( $_POST['cf_back'] ) ? wp_validate_redirect( esc_url_raw( wp_unslash( $_POST['cf_back'] ) ), home_url( '/yhteystiedot/' ) ) : home_url( '/yhteystiedot/' );
	$back = remove_query_arg( 'viesti', $back );
	$go   = function ( $status ) use ( $back ) {
		wp_safe_redirect( add_query_arg( 'viesti', $status, $back ) . '#yhteydenotto' );
		exit;
	};
	$time = isset( $_POST['cf_t'] ) ? (int) $_POST['cf_t'] : 0;
	$key  = isset( $_POST['cf_k'] ) ? sanitize_text_field( wp_unslash( $_POST['cf_k'] ) ) : '';
	$age  = time() - $time;
	if ( ! empty( $_POST['cf_verkkosivu'] ) || ! hash_equals( nap_huolto_token( $time ), $key ) || $age < 4 || $age > WEEK_IN_SECONDS ) {
		$go( 'ok' ); // robotille näytetään onnistuminen
	}
	$name    = isset( $_POST['cf_nimi'] ) ? mb_substr( sanitize_text_field( wp_unslash( $_POST['cf_nimi'] ) ), 0, 100 ) : '';
	$email   = isset( $_POST['cf_email'] ) ? sanitize_email( wp_unslash( $_POST['cf_email'] ) ) : '';
	$phone   = isset( $_POST['cf_puhelin'] ) ? mb_substr( sanitize_text_field( wp_unslash( $_POST['cf_puhelin'] ) ), 0, 30 ) : '';
	$topic   = isset( $_POST['cf_aihe'] ) ? sanitize_key( wp_unslash( $_POST['cf_aihe'] ) ) : 'muu';
	$message = isset( $_POST['cf_viesti'] ) ? mb_substr( sanitize_textarea_field( wp_unslash( $_POST['cf_viesti'] ) ), 0, 4000 ) : '';
	// phpcs:enable
	$topics = nap_contact_topics();
	$topic  = isset( $topics[ $topic ] ) ? $topic : 'muu';
	if ( '' === $name || ! is_email( $email ) || mb_strlen( $message ) < 5 ) {
		$go( 'puuttuu' );
	}
	if ( preg_match( '#https?://|www\.#i', $message . ' ' . $name ) ) {
		$go( 'linkki' );
	}
	$ip    = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
	$k_ip  = 'nap_cf_ip_' . md5( $ip );
	$k_em  = 'nap_cf_em_' . md5( strtolower( $email ) );
	$k_day = 'nap_cf_day_' . gmdate( 'Ymd' );
	if ( (int) get_transient( $k_ip ) >= 5 || (int) get_transient( $k_em ) >= 3 || (int) get_transient( $k_day ) >= 80 ) {
		$go( 'virhe' );
	}
	set_transient( $k_ip, (int) get_transient( $k_ip ) + 1, HOUR_IN_SECONDS );
	set_transient( $k_em, (int) get_transient( $k_em ) + 1, DAY_IN_SECONDS );
	set_transient( $k_day, (int) get_transient( $k_day ) + 1, DAY_IN_SECONDS );

	$s  = nap_settings();
	$to = array_filter( array_map( 'trim', explode( ',', (string) $s['staff_notify'] ) ), 'is_email' );
	if ( ! $to ) {
		$to = is_email( $s['reply_to'] ) ? array( $s['reply_to'] ) : wp_list_pluck( nap_osakkaat(), 'user_email' );
	}
	$customer = nap_find_customer_by_email( $email );
	$lines    = array_filter( array(
		$name . ' · ' . $email . ( $phone ? ' · ' . $phone : '' ),
		'Aihe: ' . $topics[ $topic ],
		$message,
		$customer ? 'Lähettäjä on jo CRM:n asiakas.' : 'Lähettäjä ei ole vielä CRM:n asiakas.',
		'Lomake sivulla: ' . $back,
	) );
	$sent = nap_mail( $to, 'Yhteydenotto: ' . $topics[ $topic ] . ' – ' . $name, 'Uusi yhteydenotto verkkosivulta', $lines, $customer ? array( 'Avaa asiakas', home_url( '/crm/#asiakas-' . (int) $customer['id'] ) ) : null, $email );
	if ( $customer ) {
		nap_log( (int) $customer['id'], null, 'note', 'Yhteydenotto verkkosivulta (' . $topics[ $topic ] . '): ' . $message );
	}
	do_action( 'nap_contact_message', compact( 'name', 'email', 'phone', 'topic', 'message' ), $customer );
	$go( $sent ? 'ok' : 'virhe' );
}
