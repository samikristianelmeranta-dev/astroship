<?php
/**
 * Asetukset: Asetukset → Nordic CRM.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

function nordic_crm_defaults() {
	return array(
		'quote_form_ids'     => '3',
		'taloyhtio_match'    => 'taloyhtio',
		'consent_field'      => 'markkinointilupa',
		'double_optin'       => 1,
		'esite_url'          => plugins_url( 'assets/nordic-esite-2026.pdf', NORDIC_CRM_FILE ),
		'sender_name'        => 'Sami Elmeranta',
		'sender_title'       => '',
		'company'            => 'Nordic Ikkunat & Ovet Oy',
		'phone'              => '',
		'email'              => 'info@ikkunakauppias.fi',
		'logo_url'           => plugins_url( 'assets/logo.png', NORDIC_CRM_FILE ),
		'privacy_url'        => '',
		'stop_tags'          => 'Asiakas, Ei kiinnostunut',
	);
}

function nordic_crm_settings() {
	return wp_parse_args( get_option( 'nordic_crm_settings', array() ), nordic_crm_defaults() );
}

add_action( 'admin_menu', function () {
	add_options_page( 'Nordic CRM', 'Nordic CRM', 'manage_options', 'nordic-crm', 'nordic_crm_settings_page' );
} );

add_filter( 'plugin_action_links_' . plugin_basename( NORDIC_CRM_FILE ), function ( $links ) {
	array_unshift( $links, '<a href="' . esc_url( admin_url( 'options-general.php?page=nordic-crm' ) ) . '">Asetukset</a>' );
	return $links;
} );

add_action( 'admin_init', function () {
	register_setting( 'nordic_crm', 'nordic_crm_settings', array(
		'type'              => 'array',
		'sanitize_callback' => 'nordic_crm_sanitize_settings',
	) );
} );

function nordic_crm_sanitize_settings( $in ) {
	$d   = nordic_crm_defaults();
	$out = array();
	foreach ( $d as $key => $default ) {
		$val = isset( $in[ $key ] ) ? $in[ $key ] : '';
		switch ( $key ) {
			case 'double_optin':
				$out[ $key ] = empty( $in[ $key ] ) ? 0 : 1;
				break;
			case 'esite_url':
			case 'logo_url':
			case 'privacy_url':
				$out[ $key ] = esc_url_raw( trim( $val ) );
				break;
			case 'email':
				$out[ $key ] = sanitize_email( $val );
				break;
			case 'quote_form_ids':
				$out[ $key ] = implode( ',', array_filter( array_map( 'absint', explode( ',', $val ) ) ) );
				break;
			default:
				$out[ $key ] = sanitize_text_field( $val );
		}
	}
	foreach ( array( 'logo_url', 'esite_url' ) as $k ) {
		if ( ! $out[ $k ] ) {
			$out[ $k ] = $d[ $k ];
		}
	}
	return $out;
}

/**
 * Asetussivu + "Asenna / päivitä automaatiot" -painike.
 */
function nordic_crm_settings_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	$s = nordic_crm_settings();

	if ( isset( $_POST['nordic_crm_reinstall'] ) && check_admin_referer( 'nordic_crm_reinstall' ) ) {
		$force  = ! empty( $_POST['nordic_crm_force'] );
		$result = nordic_crm_install( $force );
		echo '<div class="notice notice-success"><p>' . wp_kses_post( nordic_crm_install_summary( $result ) ) . '</p></div>';
	}

	$notice = get_transient( 'nordic_crm_install_notice' );
	if ( $notice ) {
		delete_transient( 'nordic_crm_install_notice' );
		echo '<div class="notice notice-success"><p>' . wp_kses_post( nordic_crm_install_summary( $notice ) ) . '</p></div>';
	}

	$field = function ( $key, $label, $help = '', $type = 'text' ) use ( $s ) {
		echo '<tr><th scope="row"><label for="nc-' . esc_attr( $key ) . '">' . esc_html( $label ) . '</label></th><td>';
		if ( 'checkbox' === $type ) {
			echo '<label><input type="checkbox" id="nc-' . esc_attr( $key ) . '" name="nordic_crm_settings[' . esc_attr( $key ) . ']" value="1" ' . checked( ! empty( $s[ $key ] ), true, false ) . '> ' . esc_html( $help ) . '</label>';
		} else {
			echo '<input type="' . esc_attr( $type ) . '" class="regular-text" id="nc-' . esc_attr( $key ) . '" name="nordic_crm_settings[' . esc_attr( $key ) . ']" value="' . esc_attr( $s[ $key ] ) . '">';
			if ( $help ) {
				echo '<p class="description">' . wp_kses_post( $help ) . '</p>';
			}
		}
		echo '</td></tr>';
	};
	?>
	<div class="wrap">
		<h1>Nordic CRM – sähköpostiautomaatiot</h1>
		<p>Automaatiot näkyvät ja ovat muokattavissa FluentCRM:ssä kohdassa <a href="<?php echo esc_url( admin_url( 'admin.php?page=fluentcrm-admin#/funnels' ) ); ?>">Automations</a>.</p>

		<form method="post" action="options.php">
			<?php settings_fields( 'nordic_crm' ); ?>
			<h2>Lomakkeet</h2>
			<table class="form-table" role="presentation">
				<?php
				$field( 'quote_form_ids', 'Tarjouspyyntölomakkeet (Fluent Forms ID)', 'Pilkulla eroteltuna, esim. <code>3</code>. Lomakkeessa pitää olla sähköpostikenttä.' );
				$field( 'taloyhtio_match', 'Taloyhtiön tunniste', 'Jos lomake lähetetään sivulta, jonka osoitteessa on tämä teksti, asiakas saa taloyhtiöille kirjoitetut viestit.' );
				$field( 'consent_field', 'Markkinointiluvan kenttä', 'Fluent Forms -lomakkeen valintaruudun nimi (Name Attribute). Jatkoviestit lähtevät vain, jos ruutu on valittu.' );
				$field( 'double_optin', 'Tuplavahvistus', 'Pyydä markkinointiluvan vahvistus sähköpostilla ennen jatkoviestejä (suositus).', 'checkbox' );
				$field( 'privacy_url', 'Tietosuojaselosteen osoite', 'Näytetään esite- ja laskurilomakkeiden alla. Tyhjänä käytetään WordPressin tietosuojasivua.' );
				?>
			</table>
			<h2>Viestien tiedot</h2>
			<table class="form-table" role="presentation">
				<?php
				$field( 'sender_name', 'Allekirjoittajan nimi', 'Viestit allekirjoitetaan tällä nimellä, esim. <code>Matti Virtanen</code>. Oikea ihminen allekirjoituksena saa vastauksia selvästi useammin kuin pelkkä yrityksen nimi.' );
				$field( 'sender_title', 'Allekirjoittajan titteli', 'Esim. <code>yrittäjä</code> tai <code>myynti ja mittaukset</code>.' );
				$field( 'company', 'Yrityksen nimi' );
				$field( 'phone', 'Puhelinnumero', 'Näkyy allekirjoituksessa, jos täytetty.' );
				$field( 'email', 'Sähköposti', 'Näkyy allekirjoituksessa. Vastaukset tulevat FluentCRM:n lähetysasetusten Reply-to-osoitteeseen.', 'email' );
				$field( 'esite_url', 'Esitteen osoite (PDF)', 'Linkki, joka lähetetään esitteen tilaajalle. Oletuksena lisäosan mukana tuleva esite (8 s.). Jos teet uuden version, lataa PDF Mediakirjastoon ja liitä sen osoite tähän.' );
				$field( 'logo_url', 'Logo sähköposteihin (PNG)', 'Pieni neliömäinen kuva. Oletuksena lisäosan mukana tuleva logo.' );
				$field( 'stop_tags', 'Pysäytystagit', 'Kun kontaktille lisätään jokin näistä tageista, jatkoviestit loppuvat. Pilkulla eroteltuna.' );
				?>
			</table>
			<?php submit_button( 'Tallenna asetukset' ); ?>
		</form>

		<hr>
		<h2>Automaatiot</h2>
		<p>Luo puuttuvat tagit, kentät ja viestisarjat. Olemassa oleviin automaatioihin ei kosketa, jos niitä on muokattu FluentCRM:ssä, ellei alla olevaa ruutua valita.</p>
		<form method="post">
			<?php wp_nonce_field( 'nordic_crm_reinstall' ); ?>
			<p><label><input type="checkbox" name="nordic_crm_force" value="1"> Korvaa myös käsin muokatut viestit lisäosan versioilla</label></p>
			<?php submit_button( 'Asenna / päivitä automaatiot', 'secondary', 'nordic_crm_reinstall', false ); ?>
		</form>

		<h2>Lomakkeet sivuille</h2>
		<p>Esitteen tilauslomake: <code>[nordic_esite_lomake]</code><br>
		Laskurin tulos sähköpostiin: <code>[nordic_laskuri_lomake]</code> (Nordic-teema lisää tämän laskurisivulle automaattisesti)</p>
	</div>
	<?php
}
