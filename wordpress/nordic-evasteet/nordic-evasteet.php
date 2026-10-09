<?php
/**
 * Plugin Name:       Nordic Evästeet
 * Description:       Kevyt evästesuostumus: banneri, evästeasetukset, Google Consent Mode v2 ja WP Consent API -tuki (Site Kit).
 * Version:           1.0.0
 * Author:            Nordic Ikkunat & Ovet Oy
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * License:           GPL-2.0-or-later
 * Text Domain:       nordic-evasteet
 */

defined( 'ABSPATH' ) || exit;

define( 'NORDIC_EVASTEET_VERSION', '1.0.0' );
/* Nosta, kun evästeiden käyttö muuttuu olennaisesti: kaikilta kysytään suostumus uudelleen. */
define( 'NORDIC_EVASTEET_POLICY', 1 );
define( 'NORDIC_EVASTEET_COOKIE', 'nordic_consent' );

/**
 * Näytetäänkö Markkinointi-kategoria. Ota käyttöön vasta, kun sivustolla on
 * mainosseurantaa (esim. Google Ads tai Meta Pixel):
 * add_filter( 'nordic_evasteet_markkinointi', '__return_true' );
 */
function nordic_evasteet_has_marketing() {
	return (bool) apply_filters( 'nordic_evasteet_markkinointi', false );
}

/**
 * Lukee käyttäjän valinnan evästeestä. Palauttaa null, jos valintaa ei ole tehty.
 *
 * @return array|null array( 'stats' => bool, 'mkt' => bool )
 */
function nordic_evasteet_choice() {
	if ( empty( $_COOKIE[ NORDIC_EVASTEET_COOKIE ] ) ) {
		return null;
	}
	$c = json_decode( wp_unslash( $_COOKIE[ NORDIC_EVASTEET_COOKIE ] ), true );
	if ( ! is_array( $c ) || (int) ( $c['v'] ?? 0 ) !== NORDIC_EVASTEET_POLICY ) {
		return null;
	}
	return array( 'stats' => ! empty( $c['s'] ), 'mkt' => ! empty( $c['m'] ) );
}

/**
 * Apufunktio muille lisäosille: nordic_has_consent( 'statistics' ) tai 'marketing'.
 */
function nordic_has_consent( $category ) {
	$c = nordic_evasteet_choice();
	if ( ! $c ) {
		return false;
	}
	return 'marketing' === $category ? $c['mkt'] : ( 'statistics' === $category ? $c['stats'] : true );
}

/* WP Consent API: ilmoitetaan, että tämä lisäosa hoitaa suostumuksen (Site Kit lukee tämän). */
add_filter( 'wp_consent_api_registered_' . plugin_basename( __FILE__ ), '__return_true' );
add_filter( 'wp_get_consent_type', function () { return 'optin'; } );

function nordic_evasteet_is_frontend() {
	return ! is_admin() && ! wp_doing_ajax() && ! ( defined( 'REST_REQUEST' ) && REST_REQUEST ) && ! is_customize_preview();
}

/* 1) Google Consent Mode v2: oletuksena kaikki kielletty, ennen kuin Google-tagi latautuu. */
add_action( 'wp_head', function () {
	if ( ! nordic_evasteet_is_frontend() ) {
		return;
	}
	$cookie = NORDIC_EVASTEET_COOKIE;
	$policy = (int) NORDIC_EVASTEET_POLICY;
	?>
<script id="nordic-consent-default">
window.dataLayer=window.dataLayer||[];function gtag(){dataLayer.push(arguments);}
gtag('consent','default',{ad_storage:'denied',ad_user_data:'denied',ad_personalization:'denied',analytics_storage:'denied',functionality_storage:'granted',security_storage:'granted',wait_for_update:500});
gtag('set','ads_data_redaction',true);
(function(){var m=document.cookie.match(/(?:^|; )<?php echo esc_js( $cookie ); ?>=([^;]*)/);if(!m)return;var c;try{c=JSON.parse(decodeURIComponent(m[1]));}catch(e){return;}if(!c||c.v!==<?php echo $policy; ?>)return;var s=c.s?'granted':'denied',k=c.m?'granted':'denied';gtag('consent','update',{analytics_storage:s,ad_storage:k,ad_user_data:k,ad_personalization:k});})();
</script>
	<?php
}, -1000 );

/* 2) Tyylit ja skripti. */
add_action( 'wp_enqueue_scripts', function () {
	$url = plugin_dir_url( __FILE__ ) . 'assets/';
	wp_enqueue_style( 'nordic-evasteet', $url . 'evasteet.css', array(), NORDIC_EVASTEET_VERSION );
	wp_enqueue_script( 'nordic-evasteet', $url . 'evasteet.js', array(), NORDIC_EVASTEET_VERSION, array( 'in_footer' => true, 'strategy' => 'defer' ) );
	wp_localize_script( 'nordic-evasteet', 'nordicEvasteet', array(
		'cookie' => NORDIC_EVASTEET_COOKIE,
		'policy' => (int) NORDIC_EVASTEET_POLICY,
		'days'   => 365,
		'secure' => is_ssl(),
	) );
} );

/* 3) Banneri ja asetuspaneeli (piilossa, skripti näyttää tarvittaessa – toimii myös välimuistin kanssa). */
add_action( 'wp_footer', function () {
	if ( ! nordic_evasteet_is_frontend() ) {
		return;
	}
	$privacy = get_privacy_policy_url();
	if ( ! $privacy ) {
		$privacy = home_url( '/tietosuojaseloste/' );
	}
	$mkt = nordic_evasteet_has_marketing();
	?>
<div class="nev" id="nordic-evasteet" role="dialog" aria-modal="false" aria-labelledby="nev-title" aria-describedby="nev-text" hidden>
  <div class="nev-box">
    <h2 class="nev-title" id="nev-title">Evästeet</h2>
    <p class="nev-text" id="nev-text">Käytämme välttämättömiä evästeitä, jotta sivusto toimii. <?php echo $mkt ? 'Tilasto- ja markkinointievästeet' : 'Tilastoevästeet (Google Analytics)'; ?> otamme käyttöön vain suostumuksellasi. Niiden avulla näemme, miten sivustoa käytetään, ja voimme parantaa sitä. Voit muuttaa valintaasi milloin tahansa sivun alareunan Evästeasetukset-linkistä. <a href="<?php echo esc_url( $privacy ); ?>">Lue lisää tietosuojaselosteesta</a>.</p>

    <div class="nev-prefs" id="nev-prefs" hidden>
      <label class="nev-row is-locked">
        <input type="checkbox" checked disabled>
        <span><strong>Välttämättömät</strong><small>Sivuston perustoiminnot, tietoturva ja lomakkeet. Aina käytössä.</small></span>
      </label>
      <label class="nev-row">
        <input type="checkbox" id="nev-stats">
        <span><strong>Tilastot</strong><small>Google Analytics: kävijämäärät ja sivujen käyttö. Tiedot käsittelee Google Ireland Limited.</small></span>
      </label>
      <?php if ( $mkt ) : ?>
      <label class="nev-row">
        <input type="checkbox" id="nev-mkt">
        <span><strong>Markkinointi</strong><small>Mainonnan kohdentaminen ja mainosten tehokkuuden mittaus.</small></span>
      </label>
      <?php endif; ?>
    </div>

    <div class="nev-actions">
      <button type="button" class="nev-btn" data-nev="all">Hyväksy kaikki</button>
      <button type="button" class="nev-btn" data-nev="none">Vain välttämättömät</button>
      <button type="button" class="nev-btn nev-btn-ghost" data-nev="prefs" aria-controls="nev-prefs" aria-expanded="false">Asetukset</button>
      <button type="button" class="nev-btn" data-nev="save" hidden>Tallenna valinnat</button>
    </div>
  </div>
</div>
	<?php
}, 50 );

/* 4) Linkki evästeasetuksiin: [nordic_evasteasetukset] tai mikä tahansa linkki osoitteeseen #evasteasetukset. */
function nordic_evasteet_link( $label = 'Evästeasetukset' ) {
	return '<a href="#evasteasetukset" class="nev-open">' . esc_html( $label ) . '</a>';
}
add_shortcode( 'nordic_evasteasetukset', function ( $atts ) {
	$atts = shortcode_atts( array( 'teksti' => 'Evästeasetukset' ), $atts );
	return nordic_evasteet_link( $atts['teksti'] );
} );
