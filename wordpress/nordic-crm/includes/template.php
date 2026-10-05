<?php
/**
 * Sähköpostipohja.
 *
 * Tarkoituksella kirjemäinen: valkoinen pohja, pieni logo, normaalikokoinen
 * teksti ja allekirjoitus. Ei isoja bannereita, jotka saavat viestin näyttämään
 * massapostitukselta (ja ohjaavat sen Gmailissa Tarjoukset-välilehdelle).
 *
 * Tyylit kirjoitetaan inline-muodossa, koska Outlook ja osa sähköpostiohjelmista
 * ohittavat <style>-lohkon.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Lisää inline-tyylit viestin sisällön elementteihin.
 */
function nordic_crm_inline_styles( $html ) {
	$font = "font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Helvetica,Arial,sans-serif;";
	$map  = array(
		'<p>'      => '<p style="margin:0 0 16px;' . $font . 'font-size:16px;line-height:1.6;color:#22343c;">',
		'<ul>'     => '<ul style="margin:0 0 16px;padding:0 0 0 20px;' . $font . 'font-size:16px;line-height:1.6;color:#22343c;">',
		'<li>'     => '<li style="margin:0 0 8px;">',
		'<strong>' => '<strong style="color:#16303B;">',
	);
	// Kappale, jossa on pelkkä lihavoitu linkki, on viestin pääpainike (esite, vahvistus).
	$html = preg_replace_callback(
		'#<p>\s*<a href="([^"]+)">\s*<strong>([^<]+)</strong>\s*</a>\s*</p>#u',
		function ( $m ) use ( $font ) {
			return '<table role="presentation" cellpadding="0" cellspacing="0" border="0" style="margin:4px 0 22px;"><tr>'
				. '<td style="border-radius:999px;background:#FFD866;">'
				. '<a style="display:inline-block;padding:13px 26px;' . $font . 'font-size:16px;font-weight:700;color:#16303B;text-decoration:none;border-radius:999px;" href="' . $m[1] . '">' . $m[2] . '</a>'
				. '</td></tr></table>';
		},
		$html
	);
	$html = strtr( $html, $map );
	// Linkit: perinteinen alleviivattu linkki brändin värillä.
	$html = preg_replace( '#<a href=#', '<a style="color:#1D5E79;text-decoration:underline;" href=', $html );
	return $html;
}

/**
 * Koko viesti HTML:nä.
 *
 * @param string $body      Viestin sisältö (kappaleet).
 * @param string $preheader Esikatseluteksti, joka näkyy postilaatikossa otsikon perässä.
 * @param string $reason    Rivi alatunnisteeseen: miksi vastaanottaja saa viestin.
 * @param bool   $marketing Onko markkinointiviesti (näytetään peruutuslinkki).
 */
function nordic_crm_render_email( $body, $preheader = '', $reason = '', $marketing = true ) {
	$s        = nordic_crm_settings();
	$site     = untrailingslashit( home_url() );
	$logo     = esc_url( $s['logo_url'] );
	$body     = nordic_crm_inline_styles( $body );
	$font     = "-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Helvetica,Arial,sans-serif";
	$reason   = $reason ? $reason : 'Saat tämän viestin, koska jätit yhteystietosi sivustollamme ' . wp_parse_url( $site, PHP_URL_HOST ) . '.';
	$pre      = esc_html( $preheader );
	// Täytemerkit estävät postiohjelmaa näyttämästä esikatselussa viestin alkua preheaderin perässä.
	$spacer   = str_repeat( '&#847;&zwnj;&nbsp;', 40 );

	$footer_links = $marketing
		? '<a href="##crm.unsubscribe_url##" style="color:#647A84;text-decoration:underline;">Peru viestien tilaus</a>'
		: '';

	return '<!DOCTYPE html>
<html lang="fi" xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="x-apple-disable-message-reformatting">
<meta name="color-scheme" content="light">
<meta name="supported-color-schemes" content="light">
<title>' . $pre . '</title>
<style>
  @media (max-width: 620px) {
    .nc-card { padding: 28px 22px !important; }
    .nc-outer { padding: 12px 0 !important; }
    .nc-foot { padding: 18px 22px 0 !important; }
  }
  a { color: #1D5E79; }
</style>
</head>
<body style="margin:0;padding:0;background:#F1F4F5;">
<div style="display:none;max-height:0;overflow:hidden;opacity:0;mso-hide:all;">' . $pre . $spacer . '</div>
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background:#F1F4F5;">
  <tr>
    <td align="center" class="nc-outer" style="padding:32px 12px;">
      <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="max-width:600px;">
        <tr>
          <td style="height:4px;background:#FFD866;font-size:0;line-height:0;">&nbsp;</td>
        </tr>
        <tr>
          <td class="nc-card" style="background:#FFFFFF;padding:36px 44px 32px;">
            <table role="presentation" cellpadding="0" cellspacing="0" border="0" style="margin:0 0 28px;">
              <tr>
                <td style="vertical-align:middle;padding-right:10px;"><a href="' . esc_url( $site . '/' ) . '"><img src="' . $logo . '" width="28" height="28" alt="" style="display:block;border:0;"></a></td>
                <td style="vertical-align:middle;font-family:' . $font . ';font-size:16px;font-weight:700;color:#16303B;letter-spacing:-0.2px;"><a href="' . esc_url( $site . '/' ) . '" style="color:#16303B;text-decoration:none;">Ikkunakauppias<span style="color:#1D5E79;">.fi</span></a></td>
              </tr>
            </table>
            ' . $body . '
          </td>
        </tr>
        <tr>
          <td class="nc-foot" style="padding:20px 44px 0;font-family:' . $font . ';font-size:12.5px;line-height:1.6;color:#647A84;">
            <p style="margin:0 0 6px;">' . esc_html( $s['company'] ) . ' &middot; Valtuutettu Skaala-jälleenmyyjä &middot; Turku ja Varsinais-Suomi</p>
            <p style="margin:0 0 6px;">' . esc_html( $reason ) . '</p>
            ' . ( $footer_links ? '<p style="margin:0;">' . $footer_links . '</p>' : '' ) . '
          </td>
        </tr>
      </table>
    </td>
  </tr>
</table>
</body>
</html>';
}

/**
 * Alatunnisteen syyrivi viestisarjan tyypin mukaan.
 */
function nordic_crm_reason_for( $lead_type, $audience ) {
	$host = wp_parse_url( home_url(), PHP_URL_HOST );
	if ( 'consent' === $audience ) {
		return 'Saat tämän viestin, koska annoit luvan lähettää sinulle sähköpostia sivustollamme ' . $host . '.';
	}
	switch ( $lead_type ) {
		case 'tarjous':
		case 'taloyhtio':
			return 'Saat tämän viestin, koska lähetit tarjouspyynnön sivustollamme ' . $host . '.';
		case 'esite':
			return 'Saat tämän viestin, koska tilasit esitteen sivustollamme ' . $host . '.';
		case 'laskuri':
			return 'Saat tämän viestin, koska pyysit laskurin tuloksen sähköpostiisi sivustollamme ' . $host . '.';
	}
	return '';
}
