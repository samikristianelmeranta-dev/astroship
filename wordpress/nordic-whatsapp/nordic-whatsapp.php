<?php
/**
 * Plugin Name:       Nordic WhatsApp – myynti ja asennus
 * Description:       WhatsApp-painike sivuston kulmaan. Kävijä valitsee Myynnin tai Asennuksen, ja keskustelu aukeaa oikeaan numeroon valmiin aloitusviestin kanssa. Lisäksi lyhytkoodi [nordic_whatsapp] sivujen sisältöön.
 * Version:           1.0.0
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * Author:            Nordic Ikkunat & Ovet Oy
 * License:           GPLv2 or later
 * Text Domain:       nordic-whatsapp
 */

if ( ! defined( 'ABSPATH' ) ) exit;

define( 'NORDIC_WA_VERSION', '1.0.0' );
define( 'NORDIC_WA_FILE', __FILE__ );

/* -------------------------------------------------------------------------
 * Asetukset
 * ---------------------------------------------------------------------- */

function nordic_wa_defaults() {
	return array(
		'enabled'       => 1,
		'sales_number'  => '05084578325070',
		'sales_label'   => 'Myynti',
		'sales_desc'    => 'Tarjoukset, tuotteet ja mittauskäynnit',
		'sales_text'    => 'Hei! Haluaisin kysyä ikkunoista tai ovista.',
		'inst_number'   => '05084578304746',
		'inst_label'    => 'Asennus',
		'inst_desc'     => 'Asennusaikataulut ja asennuspäivän asiat',
		'inst_text'     => 'Hei! Minulla on kysymys asennuksesta.',
		'hours'         => 'Vastaamme arkisin klo 8–17.',
		'button_text'   => 'WhatsApp',
	);
}

function nordic_wa_settings() {
	return wp_parse_args( (array) get_option( 'nordic_wa_settings', array() ), nordic_wa_defaults() );
}

/**
 * Suomalainen numero → WhatsAppin muoto (vain numerot, maatunnus edessä).
 * 050 123 4567 → 358501234567, +358 50… → 35850…
 */
function nordic_wa_number( $raw ) {
	$n = preg_replace( '/[^0-9+]/', '', (string) $raw );
	if ( 0 === strpos( $n, '+' ) ) {
		return substr( $n, 1 );
	}
	if ( 0 === strpos( $n, '00' ) ) {
		return substr( $n, 2 );
	}
	if ( 0 === strpos( $n, '0' ) ) {
		return '358' . substr( $n, 1 );
	}
	return $n;
}

function nordic_wa_link( $raw, $text ) {
	$num = nordic_wa_number( $raw );
	if ( '' === $num ) {
		return '';
	}
	return 'https://wa.me/' . $num . ( '' !== trim( $text ) ? '?text=' . rawurlencode( $text ) : '' );
}

/** Yhteystiedot JS:lle ja lyhytkoodille. */
function nordic_wa_contacts() {
	$s   = nordic_wa_settings();
	$out = array();
	foreach ( array( 'sales', 'inst' ) as $k ) {
		$num = nordic_wa_number( $s[ $k . '_number' ] );
		if ( '' === $num ) {
			continue;
		}
		$out[] = array(
			'key'   => $k,
			'label' => $s[ $k . '_label' ],
			'desc'  => $s[ $k . '_desc' ],
			'num'   => $num,
			'text'  => $s[ $k . '_text' ],
		);
	}
	return $out;
}

add_action( 'admin_menu', function () {
	add_options_page( 'WhatsApp', 'WhatsApp', 'manage_options', 'nordic-whatsapp', 'nordic_wa_settings_page' );
} );

add_action( 'admin_init', function () {
	register_setting( 'nordic_wa', 'nordic_wa_settings', array(
		'type'              => 'array',
		'sanitize_callback' => function ( $in ) {
			$d   = nordic_wa_defaults();
			$out = array();
			foreach ( $d as $k => $v ) {
				if ( 'enabled' === $k ) {
					$out[ $k ] = empty( $in[ $k ] ) ? 0 : 1;
				} elseif ( substr( $k, -5 ) === '_text' ) {
					$out[ $k ] = sanitize_textarea_field( $in[ $k ] ?? '' );
				} else {
					$out[ $k ] = sanitize_text_field( $in[ $k ] ?? '' );
				}
			}
			return $out;
		},
	) );
} );

function nordic_wa_settings_page() {
	$s = nordic_wa_settings();
	$f = function ( $key, $label, $help = '', $type = 'text' ) use ( $s ) {
		$name = 'nordic_wa_settings[' . $key . ']';
		echo '<tr><th scope="row"><label for="nwa-' . esc_attr( $key ) . '">' . esc_html( $label ) . '</label></th><td>';
		if ( 'checkbox' === $type ) {
			echo '<label><input type="checkbox" id="nwa-' . esc_attr( $key ) . '" name="' . esc_attr( $name ) . '" value="1"' . checked( ! empty( $s[ $key ] ), true, false ) . '> ' . esc_html( $help ) . '</label>';
		} elseif ( 'textarea' === $type ) {
			echo '<textarea id="nwa-' . esc_attr( $key ) . '" name="' . esc_attr( $name ) . '" rows="2" class="large-text">' . esc_textarea( $s[ $key ] ) . '</textarea>';
		} else {
			echo '<input type="text" id="nwa-' . esc_attr( $key ) . '" name="' . esc_attr( $name ) . '" value="' . esc_attr( $s[ $key ] ) . '" class="regular-text">';
		}
		if ( $help && 'checkbox' !== $type ) {
			echo '<p class="description">' . wp_kses_post( $help ) . '</p>';
		}
		echo '</td></tr>';
	};
	?>
	<div class="wrap">
		<h1>WhatsApp</h1>
		<p>Painike näkyy sivuston vasemmassa alakulmassa. Sivuille voi lisätä painikkeet myös lyhytkoodilla <code>[nordic_whatsapp]</code>.</p>
		<form method="post" action="options.php">
			<?php settings_fields( 'nordic_wa' ); ?>
			<table class="form-table" role="presentation">
				<?php $f( 'enabled', 'Näytä painike', 'Näytä WhatsApp-painike kaikilla sivuilla', 'checkbox' ); ?>
				<?php $f( 'button_text', 'Painikkeen teksti' ); ?>
				<?php $f( 'hours', 'Vastausaika', 'Näkyy valikon alareunassa.' ); ?>
			</table>
			<h2>Myynti</h2>
			<table class="form-table" role="presentation">
				<?php $f( 'sales_number', 'Numero', 'Suomalainen numero, esim. <code>050 123 4567</code> tai <code>+358 50 123 4567</code>.' ); ?>
				<?php $f( 'sales_label', 'Otsikko' ); ?>
				<?php $f( 'sales_desc', 'Kuvaus' ); ?>
				<?php $f( 'sales_text', 'Aloitusviesti', 'Valmiiksi kirjoitettu viesti, jota kävijä voi muokata ennen lähettämistä.', 'textarea' ); ?>
			</table>
			<h2>Asennus</h2>
			<table class="form-table" role="presentation">
				<?php $f( 'inst_number', 'Numero', 'Jätä tyhjäksi, jos haluat näyttää vain myynnin.' ); ?>
				<?php $f( 'inst_label', 'Otsikko' ); ?>
				<?php $f( 'inst_desc', 'Kuvaus' ); ?>
				<?php $f( 'inst_text', 'Aloitusviesti', '', 'textarea' ); ?>
			</table>
			<?php submit_button(); ?>
		</form>
		<h2>Testaa</h2>
		<p>
		<?php foreach ( nordic_wa_contacts() as $c ) : ?>
			<a class="button" target="_blank" rel="noopener" href="<?php echo esc_url( nordic_wa_link( $c['num'], $c['text'] ) ); ?>"><?php echo esc_html( $c['label'] . ' (+' . $c['num'] . ')' ); ?></a>
		<?php endforeach; ?>
		</p>
	</div>
	<?php
}

/* -------------------------------------------------------------------------
 * Painike sivustolle
 * ---------------------------------------------------------------------- */

add_action( 'wp_enqueue_scripts', function () {
	$s = nordic_wa_settings();
	if ( is_admin() || empty( $s['enabled'] ) || ! nordic_wa_contacts() ) {
		return;
	}
	wp_enqueue_style( 'nordic-whatsapp', plugins_url( 'assets/wa.css', NORDIC_WA_FILE ), array(), NORDIC_WA_VERSION );
	wp_enqueue_script( 'nordic-whatsapp', plugins_url( 'assets/wa.js', NORDIC_WA_FILE ), array(), NORDIC_WA_VERSION, array( 'strategy' => 'defer', 'in_footer' => true ) );
	wp_localize_script( 'nordic-whatsapp', 'NordicWA', array(
		'contacts' => nordic_wa_contacts(),
		'hours'    => $s['hours'],
		'button'   => $s['button_text'],
	) );
} );

/** [nordic_whatsapp] – painikkeet sivun sisältöön (esim. Yhteystiedot). */
add_shortcode( 'nordic_whatsapp', function () {
	$contacts = nordic_wa_contacts();
	if ( ! $contacts ) {
		return '';
	}
	wp_enqueue_style( 'nordic-whatsapp', plugins_url( 'assets/wa.css', NORDIC_WA_FILE ), array(), NORDIC_WA_VERSION );
	$out = '<div class="nwa-inline">';
	foreach ( $contacts as $c ) {
		$out .= '<a class="nwa-inline-btn" target="_blank" rel="noopener" data-nwa="' . esc_attr( $c['key'] ) . '" href="' . esc_url( nordic_wa_link( $c['num'], $c['text'] ) ) . '">'
			. nordic_wa_icon() . '<span><strong>' . esc_html( $c['label'] ) . '</strong><small>' . esc_html( $c['desc'] ) . '</small></span></a>';
	}
	return $out . '</div>';
} );

function nordic_wa_icon() {
	return '<svg class="nwa-ico" viewBox="0 0 32 32" aria-hidden="true"><path fill="currentColor" d="M16 3C8.8 3 3 8.7 3 15.8c0 2.5.7 4.9 2 6.9L3 29l6.5-2c1.9 1 4.1 1.6 6.5 1.6 7.2 0 13-5.7 13-12.8S23.2 3 16 3zm0 23.3c-2.1 0-4.1-.6-5.8-1.7l-.4-.2-3.9 1.2 1.2-3.7-.3-.4a10.4 10.4 0 0 1-1.7-5.7C5.1 10 10 5.3 16 5.3S26.9 10 26.9 15.8 22 26.3 16 26.3zm6-7.8c-.3-.2-1.9-.9-2.2-1s-.5-.2-.7.2-.8 1-1 1.2-.4.2-.7.1a8.6 8.6 0 0 1-4.3-3.7c-.3-.6.3-.5 1-1.7.1-.2 0-.4 0-.5l-1-2.4c-.3-.6-.5-.5-.7-.5h-.6c-.2 0-.5.1-.8.4-.3.3-1 1-1 2.5s1.1 2.9 1.2 3.1c.1.2 2.1 3.2 5.1 4.5 1.9.8 2.6.9 3.6.7.6-.1 1.9-.8 2.1-1.5.3-.7.3-1.4.2-1.5-.1-.1-.3-.2-.6-.3z"/></svg>';
}
