<?php
/**
 * Omat muuttujat viesteihin: {{nordic.tervehdys}}, {{nordic.allekirjoitus}},
 * {{nordic.esite_url}}, {{nordic.laskelma}}, {{nordic.opas_nimi}}, {{nordic.opas_url}}.
 *
 * Muuttujat haetaan lähetyshetkellä asetuksista, joten esimerkiksi allekirjoituksen
 * tai esitteen linkin voi vaihtaa ilman, että viestejä tarvitsee muokata.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

function nordic_crm_register_smartcodes() {
	if ( ! function_exists( 'FluentCrmApi' ) ) {
		return;
	}
	FluentCrmApi( 'extender' )->addSmartCode(
		'nordic',
		'Nordic',
		array(
			'tervehdys'     => 'Tervehdys ("Hei Matti,")',
			'allekirjoitus' => 'Allekirjoitus',
			'esite_url'     => 'Esitteen osoite',
			'laskelma'      => 'Laskurin tulos',
			'opas_nimi'     => 'Ladatun oppaan nimi',
			'opas_url'      => 'Ladatun oppaan osoite (PDF)',
		),
		'nordic_crm_smartcode_value'
	);
}

function nordic_crm_smartcode_value( $code, $key, $default, $subscriber ) {
	$s = nordic_crm_settings();
	switch ( $key ) {
		case 'tervehdys':
			$name = $subscriber && ! empty( $subscriber->first_name ) ? trim( $subscriber->first_name ) : '';
			return $name ? 'Hei ' . esc_html( $name ) . ',' : 'Hei,';

		case 'allekirjoitus':
			return nordic_crm_signature_html( $s );

		case 'esite_url':
			return esc_url( $s['esite_url'] ? $s['esite_url'] : home_url( '/ikkunat/' ) );

		case 'laskelma':
			return nordic_crm_calc_html( $subscriber );

		case 'opas_nimi':
		case 'opas_url':
			$guide = ( $subscriber && function_exists( 'nordic_crm_guide' ) )
				? nordic_crm_guide( (int) fluentcrm_get_subscriber_meta( $subscriber->id, '_nordic_opas_id', 0 ) )
				: null;
			if ( 'opas_nimi' === $key ) {
				return $guide ? esc_html( $guide['title'] ) : 'opas';
			}
			return esc_url( $guide ? $guide['pdf'] : home_url( '/' ) );
	}
	return $default !== '' ? $default : $code;
}

function nordic_crm_signature_html( $s ) {
	$lines = array( 'Terveisin' );
	if ( $s['sender_name'] ) {
		$lines[] = '<strong style="color:#16303B;">' . esc_html( $s['sender_name'] ) . '</strong>';
		$lines[] = esc_html( trim( ( $s['sender_title'] ? $s['sender_title'] . ', ' : '' ) . $s['company'] ) );
	} else {
		$lines[] = '<strong style="color:#16303B;">' . esc_html( $s['company'] ) . '</strong>';
	}
	$contact = array();
	if ( $s['phone'] ) {
		$tel       = preg_replace( '/[^0-9+]/', '', $s['phone'] );
		$contact[] = '<a href="tel:' . esc_attr( $tel ) . '" style="color:#1D5E79;text-decoration:none;">' . esc_html( $s['phone'] ) . '</a>';
	}
	if ( $s['email'] ) {
		$contact[] = '<a href="mailto:' . esc_attr( $s['email'] ) . '" style="color:#1D5E79;text-decoration:none;">' . esc_html( $s['email'] ) . '</a>';
	}
	if ( $contact ) {
		$lines[] = implode( ' &middot; ', $contact );
	}
	return implode( '<br>', $lines );
}

/**
 * Laskurin tulos taulukkona (lukee kontaktin lisäkentät).
 */
function nordic_crm_calc_html( $subscriber ) {
	if ( ! $subscriber || ! method_exists( $subscriber, 'custom_fields' ) ) {
		return '';
	}
	$f      = (array) $subscriber->custom_fields();
	$saasto = isset( $f['laskuri_saasto'] ) ? (int) $f['laskuri_saasto'] : 0;
	if ( ! $saasto ) {
		return '';
	}
	$fmt  = function ( $n ) {
		return number_format( (float) $n, 0, ',', '&nbsp;' );
	};
	$rows = array();
	if ( ! empty( $f['laskuri_ikkunat'] ) ) {
		$rows[] = array( 'Vaihdettavia ikkunoita ja ovia', esc_html( $f['laskuri_ikkunat'] ) . ' kpl' );
	}
	if ( ! empty( $f['laskuri_ika'] ) ) {
		$rows[] = array( 'Nykyisten ikkunoiden ikä', esc_html( $f['laskuri_ika'] ) );
	}
	if ( ! empty( $f['laskuri_lammitys'] ) ) {
		$rows[] = array( 'Lämmitysmuoto', esc_html( $f['laskuri_lammitys'] ) );
	}
	if ( ! empty( $f['laskuri_kwh'] ) ) {
		$rows[] = array( 'Säästö lämmitysenergiassa', $fmt( $f['laskuri_kwh'] ) . '&nbsp;kWh vuodessa' );
	}

	$font = "-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Helvetica,Arial,sans-serif";
	$html = '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin:4px 0 20px;border:1px solid #E1E8EB;border-radius:8px;">';
	$html .= '<tr><td style="padding:18px 20px 14px;font-family:' . $font . ';">'
		. '<div style="font-size:13px;color:#647A84;margin:0 0 4px;">Arvioitu säästö</div>'
		. '<div style="font-size:28px;font-weight:700;color:#16303B;line-height:1.2;">noin ' . $fmt( $saasto ) . '&nbsp;€ vuodessa</div>'
		. '</td></tr>';
	foreach ( $rows as $row ) {
		$html .= '<tr><td style="padding:10px 20px;border-top:1px solid #E1E8EB;font-family:' . $font . ';font-size:14.5px;color:#22343c;">'
			. '<span style="color:#647A84;">' . $row[0] . ':</span> ' . $row[1] . '</td></tr>';
	}
	$html .= '</table>';
	return $html;
}
