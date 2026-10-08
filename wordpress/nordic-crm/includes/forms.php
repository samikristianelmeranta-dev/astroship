<?php
/**
 * Omat kevyet lomakkeet:
 *   [nordic_esite_lomake]    esitteen tilaus
 *   [nordic_laskuri_lomake]  energiansäästölaskurin tulos sähköpostiin
 *   [nordic_oppaat], [nordic_opas]  ladattavat oppaat (includes/guides.php)
 *
 * Lomakkeet toimivat ilman JavaScriptiä (tavallinen POST + uudelleenohjaus).
 * Roskapostisuoja: piilokenttä, allekirjoitettu aikaleima ja IP-kohtainen raja.
 * Erillistä nonce-tarkistusta ei käytetä, koska se rikkoutuisi välimuistitetuilla sivuilla.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

add_shortcode( 'nordic_esite_lomake', function () {
	return nordic_crm_form_html( 'esite' );
} );

add_shortcode( 'nordic_laskuri_lomake', function () {
	return nordic_crm_form_html( 'laskuri' );
} );

add_action( 'admin_post_nopriv_nordic_crm_form', 'nordic_crm_handle_form' );
add_action( 'admin_post_nordic_crm_form', 'nordic_crm_handle_form' );

function nordic_crm_form_token( $time ) {
	return hash_hmac( 'sha256', 'nordic-crm|' . $time, wp_salt( 'nonce' ) );
}

function nordic_crm_form_html( $type ) {
	static $assets = false;
	$s       = nordic_crm_settings();
	$privacy = $s['privacy_url'] ? $s['privacy_url'] : get_privacy_policy_url();
	$id      = 'nordic-crm-' . $type;
	$done    = isset( $_GET['nc'] ) && sanitize_key( wp_unslash( $_GET['nc'] ) ) === $type; // phpcs:ignore
	$error   = isset( $_GET['nc_err'] ) && sanitize_key( wp_unslash( $_GET['nc_err'] ) ) === $type; // phpcs:ignore
	$time    = time();

	$copy = array(
		'esite'   => array(
			'title'  => 'Tilaa esite sähköpostiisi',
			'text'   => 'Lähetämme Skaalan ikkuna- ja ovimalliston esitteen heti sähköpostiisi.',
			'button' => 'Lähetä esite',
			'done'   => 'Kiitos. Esite on matkalla sähköpostiisi. Jos sitä ei näy muutamassa minuutissa, katso myös roskapostikansio.',
		),
		'laskuri' => array(
			'title'  => 'Lähetä laskelma sähköpostiisi',
			'text'   => 'Saat yllä olevan tuloksen talteen sähköpostiisi, niin voit palata siihen myöhemmin.',
			'button' => 'Lähetä laskelma',
			'done'   => 'Kiitos. Laskelma on matkalla sähköpostiisi.',
		),
	);
	$c = $copy[ $type ];

	$out = '';
	if ( ! $assets ) {
		$assets = true;
		$out   .= '<style>' . nordic_crm_form_css() . '</style>';
	}

	$out .= '<div class="nordic-crm-form" id="' . esc_attr( $id ) . '">';
	if ( $done ) {
		$out .= '<p class="ncf-done" role="status">' . esc_html( $c['done'] ) . '</p></div>';
		return $out;
	}
	$out .= '<p class="ncf-title">' . esc_html( $c['title'] ) . '</p>';
	$out .= '<p class="ncf-text">' . esc_html( $c['text'] ) . '</p>';
	if ( $error ) {
		$out .= '<p class="ncf-error" role="alert">Tarkista sähköpostiosoite ja yritä uudelleen.</p>';
	}
	$out .= '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '"' . ( 'laskuri' === $type ? ' data-nordic-laskuri' : '' ) . '>';
	$out .= '<input type="hidden" name="action" value="nordic_crm_form">';
	$out .= '<input type="hidden" name="nc_type" value="' . esc_attr( $type ) . '">';
	$out .= '<input type="hidden" name="nc_t" value="' . esc_attr( $time ) . '">';
	$out .= '<input type="hidden" name="nc_h" value="' . esc_attr( nordic_crm_form_token( $time ) ) . '">';
	$out .= '<input type="hidden" name="nc_back" value="' . esc_attr( get_permalink() ) . '">';
	if ( 'laskuri' === $type ) {
		foreach ( array( 'ikkunat', 'ika', 'lammitys', 'saasto', 'kwh' ) as $f ) {
			$out .= '<input type="hidden" name="nc_' . $f . '" value="">';
		}
	}
	// Piilokenttä roboteille: ihminen ei näe eikä täytä sitä.
	$out .= '<div class="ncf-hp" aria-hidden="true"><label>Verkkosivu <input type="text" name="nc_website" tabindex="-1" autocomplete="off"></label></div>';
	$out .= '<div class="ncf-row">';
	$out .= '<label class="ncf-field"><span>Etunimi</span><input type="text" name="nc_first_name" autocomplete="given-name"></label>';
	$out .= '<label class="ncf-field"><span>Sähköposti</span><input type="email" name="nc_email" required autocomplete="email"></label>';
	$out .= '</div>';
	$out .= '<label class="ncf-consent"><input type="checkbox" name="nc_consent" value="1"> <span>Saa lähettää minulle myös muutaman vinkin ikkuna- ja oviremontista. Voin perua tilauksen milloin tahansa.</span></label>';
	$out .= '<button type="submit" class="btn btn-primary ncf-submit">' . esc_html( $c['button'] ) . '</button>';
	if ( $privacy ) {
		$out .= '<p class="ncf-privacy">Käytämme tietoja vain tähän tarkoitukseen. <a href="' . esc_url( $privacy ) . '">Tietosuojaseloste</a></p>';
	}
	$out .= '</form>';
	if ( 'laskuri' === $type ) {
		$out .= '<script>' . nordic_crm_laskuri_js() . '</script>';
	}
	$out .= '</div>';
	return $out;
}

function nordic_crm_handle_form() {
	$type = isset( $_POST['nc_type'] ) ? sanitize_key( wp_unslash( $_POST['nc_type'] ) ) : ''; // phpcs:ignore
	$back = isset( $_POST['nc_back'] ) ? esc_url_raw( wp_unslash( $_POST['nc_back'] ) ) : home_url( '/' ); // phpcs:ignore
	$back = wp_validate_redirect( $back, home_url( '/' ) );
	if ( ! in_array( $type, array( 'esite', 'laskuri', 'opas' ), true ) ) {
		wp_safe_redirect( $back );
		exit;
	}
	$guide = null;
	$extra = array();
	if ( 'opas' === $type ) {
		$guide = nordic_crm_guide_from_post();
		if ( ! $guide ) {
			wp_safe_redirect( $back );
			exit;
		}
		$extra = array( 'nc_opas' => $guide['id'] );
	}
	$back   = remove_query_arg( array( 'nc', 'nc_err', 'nc_opas', 'nc_k' ), $back );
	$anchor = $guide ? '#nordic-opas-' . $guide['id'] : '#nordic-crm-' . $type;
	$fail   = function () use ( $back, $type, $anchor, $extra ) {
		wp_safe_redirect( add_query_arg( array_merge( array( 'nc_err' => $type ), $extra ), $back ) . $anchor );
		exit;
	};
	$ok     = function () use ( $back, $type, $anchor, $extra, $guide ) {
		if ( $guide ) {
			$extra['nc_k'] = nordic_crm_guide_key( $guide['id'] );
		}
		wp_safe_redirect( add_query_arg( array_merge( array( 'nc' => $type ), $extra ), $back ) . $anchor );
		exit;
	};

	// Roskapostisuoja. Robotille näytetään "onnistui", jotta se ei yritä uudelleen.
	$t = isset( $_POST['nc_t'] ) ? (int) $_POST['nc_t'] : 0; // phpcs:ignore
	$h = isset( $_POST['nc_h'] ) ? sanitize_text_field( wp_unslash( $_POST['nc_h'] ) ) : ''; // phpcs:ignore
	if ( ! empty( $_POST['nc_website'] ) || ! hash_equals( nordic_crm_form_token( $t ), $h ) || time() - $t < 3 || time() - $t > DAY_IN_SECONDS ) { // phpcs:ignore
		$ok();
	}
	$ip_key = 'nordic_crm_rl_' . md5( isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '' );
	$count  = (int) get_transient( $ip_key );
	if ( $count >= 8 ) {
		$ok();
	}
	set_transient( $ip_key, $count + 1, HOUR_IN_SECONDS );

	$email = isset( $_POST['nc_email'] ) ? sanitize_email( wp_unslash( $_POST['nc_email'] ) ) : ''; // phpcs:ignore
	if ( ! is_email( $email ) ) {
		$fail();
	}

	$lead = array(
		'email'      => $email,
		'first_name' => isset( $_POST['nc_first_name'] ) ? sanitize_text_field( wp_unslash( $_POST['nc_first_name'] ) ) : '', // phpcs:ignore
		'type'       => $type,
		'consent'    => ! empty( $_POST['nc_consent'] ), // phpcs:ignore
		'source_url' => $back,
		'custom'     => array(),
	);

	if ( 'laskuri' === $type ) {
		$num = function ( $key, $max ) {
			$v = isset( $_POST[ $key ] ) ? (int) preg_replace( '/[^0-9]/', '', wp_unslash( $_POST[ $key ] ) ) : 0; // phpcs:ignore
			return ( $v > 0 && $v <= $max ) ? $v : 0;
		};
		$txt = function ( $key ) {
			return isset( $_POST[ $key ] ) ? mb_substr( sanitize_text_field( wp_unslash( $_POST[ $key ] ) ), 0, 60 ) : ''; // phpcs:ignore
		};
		$lead['custom'] = array_filter( array(
			'laskuri_ikkunat'  => $num( 'nc_ikkunat', 200 ),
			'laskuri_saasto'   => $num( 'nc_saasto', 100000 ),
			'laskuri_kwh'      => $num( 'nc_kwh', 1000000 ),
			'laskuri_ika'      => $txt( 'nc_ika' ),
			'laskuri_lammitys' => $txt( 'nc_lammitys' ),
		) );
	}

	if ( $guide ) {
		$lead['guide_id']           = $guide['id'];
		$lead['custom']['opas_viimeisin'] = $guide['title'];
	}

	nordic_crm_process_lead( $lead );
	$ok();
}

/**
 * Laskurisivun JS: kopioi laskurin nykyiset arvot lomakkeen piilokenttiin ennen lähetystä.
 */
function nordic_crm_laskuri_js() {
	return <<<'JS'
(function(){
  var form = document.querySelector('form[data-nordic-laskuri]');
  if (!form) return;
  function labelFor(name){
    var el = document.querySelector('input[name="' + name + '"]:checked');
    if (!el) return '';
    var l = document.querySelector('label[for="' + el.id + '"]');
    return l ? l.textContent.trim() : el.value;
  }
  function digits(sel){
    var el = document.querySelector(sel);
    return el ? (el.value || el.textContent || '').replace(/[^0-9]/g, '') : '';
  }
  form.addEventListener('submit', function(){
    form.nc_ikkunat.value = digits('#windowCount');
    form.nc_saasto.value = digits('#savingsAmount');
    var sub = document.getElementById('savingsSub');
    var m = sub ? sub.textContent.replace(/[\u00a0\u202f]/g, ' ').match(/noin ([0-9 ]+) kWh/) : null;
    form.nc_kwh.value = m ? m[1].replace(/\s/g, '') : '';
    form.nc_ika.value = labelFor('age');
    form.nc_lammitys.value = labelFor('heat');
  });
})();
JS;
}

function nordic_crm_form_css() {
	return '.nordic-crm-form{margin:24px 0 0;padding:clamp(20px,3vw,32px);background:#fff;border:1px solid rgba(22,48,59,.1);border-radius:20px;box-shadow:0 1px 2px rgba(16,37,48,.05),0 4px 12px rgba(16,37,48,.05);color:#3D5561}'
		. '.nordic-crm-form .ncf-title{margin:0 0 4px;font-family:"Space Grotesk",Inter,system-ui,sans-serif;font-weight:700;font-size:1.25rem;color:#16303B}'
		. '.nordic-crm-form .ncf-text{margin:0 0 18px}'
		. '.nordic-crm-form .ncf-row{display:grid;grid-template-columns:1fr 1.4fr;gap:12px}'
		. '@media(max-width:600px){.nordic-crm-form .ncf-row{grid-template-columns:1fr}}'
		. '.nordic-crm-form .ncf-field{display:flex;flex-direction:column;gap:6px;font-weight:600;font-size:14.5px;color:#16303B}'
		. '.nordic-crm-form input[type=text],.nordic-crm-form input[type=email]{min-height:50px;padding:12px 14px;font:inherit;font-size:16px;font-weight:400;color:#16303B;background:#F6F8F9;border:1.5px solid transparent;border-radius:12px;box-shadow:inset 0 0 0 1px rgba(22,48,59,.1)}'
		. '.nordic-crm-form input:focus{outline:none;background:#fff;border-color:#1D5E79;box-shadow:0 0 0 4px rgba(29,94,121,.15)}'
		. '.nordic-crm-form .ncf-consent{display:flex;gap:10px;align-items:flex-start;margin:16px 0;font-size:14.5px;line-height:1.5;cursor:pointer}'
		. '.nordic-crm-form .ncf-consent input{margin-top:3px;width:18px;height:18px;flex:none;accent-color:#1D5E79}'
		. '.nordic-crm-form .ncf-submit{width:100%;border:0;cursor:pointer;min-height:52px;border-radius:999px;background:#FFD866;color:#16303B;font:inherit;font-weight:700;font-size:16px}'
		. '.nordic-crm-form .ncf-privacy{margin:12px 0 0;font-size:13px;color:#647A84}'
		. '.nordic-crm-form .ncf-privacy a{color:#1D5E79}'
		. '.nordic-crm-form .ncf-done{margin:0;padding:14px 16px;border-radius:12px;background:rgba(47,125,91,.1);color:#16303B;font-weight:500}'
		. '.nordic-crm-form .ncf-error{margin:0 0 12px;color:#B3361F;font-weight:500}'
		. '.nordic-crm-form .ncf-hp{position:absolute!important;left:-9999px;width:1px;height:1px;overflow:hidden}';
}
