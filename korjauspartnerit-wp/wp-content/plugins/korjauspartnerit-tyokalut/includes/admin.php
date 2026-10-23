<?php
/**
 * Hallintapaneelin näkymät: Korjauspartnerit-valikko, asetukset, pikaohjeet.
 */

defined( 'ABSPATH' ) || exit;

add_action( 'admin_menu', function () {
	add_menu_page( 'Korjauspartnerit', 'Korjauspartnerit', 'edit_posts', 'kp-tyokalut', 'kp_admin_page_new_from_template', 'dashicons-controls-play', 3 );
	add_submenu_page( 'kp-tyokalut', 'Luo uutta pohjasta', 'Luo uutta pohjasta', 'edit_posts', 'kp-tyokalut', 'kp_admin_page_new_from_template' );
	add_submenu_page( 'kp-tyokalut', 'Asetukset', 'Asetukset', 'manage_options', 'kp-asetukset', 'kp_admin_page_settings' );
	add_submenu_page( 'kp-tyokalut', 'Asennusapuri', 'Asennusapuri', 'manage_options', 'kp-asennus', 'kp_admin_page_setup' );
} );

function kp_admin_page_new_from_template() {
	$groups = array();
	foreach ( kp_get_content_templates() as $t ) {
		foreach ( $t['tyypit'] as $type ) {
			$groups[ $type ][] = $t;
		}
	}
	$labels = kp_template_post_types();
	?>
	<div class="wrap kp-admin">
		<h1>Luo uutta sisältöä valmiista pohjasta</h1>
		<p class="kp-admin__intro">Valitse pohja, niin uusi sisältö avautuu valmiilla rungolla: otsikoinnit, ingressi ja tyypilliset lohkot ovat valmiina. Korvaa esimerkkitekstit omillasi ja julkaise. Editorin <strong>Lisää lohko</strong> -valikosta voit lisätä lisää valmiita osioita, ja <strong>Muotoilut</strong>-valikosta löydät brändin mukaiset tyylit (ingressi, painike, huomiolaatikko…).</p>
		<?php foreach ( $labels as $type => $label ) : ?>
			<?php if ( empty( $groups[ $type ] ) || ! post_type_exists( $type ) ) { continue; } ?>
			<h2><?php echo esc_html( get_post_type_object( $type )->labels->name ); ?></h2>
			<div class="kp-admin__kortit">
				<?php foreach ( $groups[ $type ] as $t ) : ?>
					<a class="kp-admin__kortti" href="<?php echo esc_url( admin_url( 'post-new.php?post_type=' . $type . '&kp_pohja=' . $t['id'] ) ); ?>">
						<strong><?php echo esc_html( preg_replace( '/^[^:]+:\s*/', '', $t['nimi'] ) ); ?></strong>
						<span><?php echo esc_html( $t['kuvaus'] ); ?></span>
						<em>Luo uusi →</em>
					</a>
				<?php endforeach; ?>
				<a class="kp-admin__kortti kp-admin__kortti--tyhja" href="<?php echo esc_url( admin_url( 'post-new.php?post_type=' . $type ) ); ?>">
					<strong>Tyhjä <?php echo esc_html( mb_strtolower( $label ) ); ?></strong>
					<span>Aloita tyhjästä editorista.</span>
					<em>Luo uusi →</em>
				</a>
			</div>
		<?php endforeach; ?>
		<?php if ( current_user_can( 'edit_others_posts' ) ) : ?>
			<p><a class="button" href="<?php echo esc_url( admin_url( 'edit.php?post_type=kp_pohja' ) ); ?>">Muokkaa sisältöpohjia</a> <a class="button" href="<?php echo esc_url( admin_url( 'post-new.php?post_type=kp_pohja' ) ); ?>">Lisää uusi pohja</a></p>
		<?php endif; ?>
	</div>
	<?php
}

add_action( 'admin_head', function () {
	?>
	<style>
		.kp-admin__intro{max-width:780px;font-size:14px}
		.kp-admin__kortit{display:grid;grid-template-columns:repeat(auto-fill,minmax(260px,1fr));gap:16px;margin:12px 0 28px}
		.kp-admin__kortti{display:flex;flex-direction:column;gap:8px;padding:18px 20px;background:#fff;border:1px solid #dcdcde;border-left:4px solid #004B59;border-radius:4px;text-decoration:none;color:#1d2327}
		.kp-admin__kortti:hover,.kp-admin__kortti:focus{border-color:#004B59;box-shadow:0 2px 8px rgba(0,75,89,.15)}
		.kp-admin__kortti strong{font-size:15px;color:#004B59}
		.kp-admin__kortti em{margin-top:auto;font-style:normal;color:#FF6C42;font-weight:600}
		.kp-admin__kortti--tyhja{border-left-color:#E5E4E3}
		#toplevel_page_kp-tyokalut .wp-menu-image:before{color:#FF6C42}
	</style>
	<?php
} );

/* ---------- Asetukset ---------- */

add_action( 'admin_init', function () {
	register_setting( 'kp_asetukset', 'kp_asetukset', array(
		'type'              => 'array',
		'sanitize_callback' => function ( $in ) {
			$out = array();
			foreach ( (array) $in as $k => $v ) {
				$k         = sanitize_key( $k );
				$out[ $k ] = in_array( $k, array( 'toimipisteet', 'uudelleenohjaukset' ), true ) ? sanitize_textarea_field( $v ) : sanitize_text_field( $v );
			}
			return $out;
		},
	) );
} );

function kp_admin_page_settings() {
	$fields = array(
		'yritys'               => 'Yrityksen nimi',
		'ytunnus'              => 'Y-tunnus',
		'puhelin'              => 'Asiakaspalvelun puhelin',
		'sahkoposti'           => 'Yleinen sähköposti',
		'lomake_vastaanottaja' => 'Yhteydenottolomakkeen vastaanottaja(t), pilkulla eroteltuna',
		'verkkolaskuosoite'    => 'Verkkolaskuosoite',
		'operaattori'          => 'Verkkolaskuoperaattori',
		'laskut_sahkoposti'    => 'Laskut sähköpostitse',
		'linkedin'             => 'LinkedIn',
		'facebook'             => 'Facebook',
		'instagram'            => 'Instagram',
		'youtube'              => 'YouTube',
	);
	$opts     = get_option( 'kp_asetukset', array() );
	$defaults = kp_settings_defaults();
	?>
	<div class="wrap">
		<h1>Korjauspartnerit – asetukset</h1>
		<p>Näitä tietoja käytetään sivuston alatunnisteessa, yhteystiedoissa, lomakkeessa ja lyhytkoodeissa. Tyhjä kenttä käyttää oletusarvoa.</p>
		<form method="post" action="options.php">
			<?php settings_fields( 'kp_asetukset' ); ?>
			<table class="form-table" role="presentation">
				<?php foreach ( $fields as $k => $label ) : ?>
					<tr>
						<th scope="row"><label for="kp-<?php echo esc_attr( $k ); ?>"><?php echo esc_html( $label ); ?></label></th>
						<td><input class="regular-text" id="kp-<?php echo esc_attr( $k ); ?>" name="kp_asetukset[<?php echo esc_attr( $k ); ?>]" value="<?php echo esc_attr( $opts[ $k ] ?? '' ); ?>" placeholder="<?php echo esc_attr( $defaults[ $k ] ?? '' ); ?>"></td>
					</tr>
				<?php endforeach; ?>
				<tr>
					<th scope="row"><label for="kp-toimipisteet">Toimipisteet</label></th>
					<td>
						<textarea class="large-text code" rows="5" id="kp-toimipisteet" name="kp_asetukset[toimipisteet]" placeholder="<?php echo esc_attr( $defaults['toimipisteet'] ); ?>"><?php echo esc_textarea( $opts['toimipisteet'] ?? '' ); ?></textarea>
						<p class="description">Yksi toimipiste per rivi: <code>Nimi | Osoite | Sähköposti | Puhelin | Lisätieto</code></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="kp-ohjaukset">Uudelleenohjaukset</label></th>
					<td>
						<textarea class="large-text code" rows="5" id="kp-ohjaukset" name="kp_asetukset[uudelleenohjaukset]" placeholder="/vanha-sivu/ => /uusi-sivu/"><?php echo esc_textarea( $opts['uudelleenohjaukset'] ?? '' ); ?></textarea>
						<p class="description">Yksi ohjaus per rivi: <code>/vanha-osoite/ =&gt; /uusi-osoite/</code>. Ohjaus tehdään vain, jos vanhaa osoitetta ei enää ole.</p>
					</td>
				</tr>
			</table>
			<?php submit_button(); ?>
		</form>
	</div>
	<?php
}

/* ---------- Asennusapuri ---------- */

function kp_admin_page_setup() {
	$log = null;
	if ( isset( $_POST['kp_asenna'] ) && check_admin_referer( 'kp_asenna' ) ) {
		$log = kp_run_setup();
	}
	?>
	<div class="wrap">
		<h1>Asennusapuri</h1>
		<p>Aja apuri sisällön tuonnin (Työkalut → Tuo → WordPress) jälkeen. Apuri on turvallista ajaa useita kertoja.</p>
		<ul style="list-style:disc;padding-left:20px">
			<li>asettaa etusivun ja Ajankohtaista-sivun</li>
			<li>luo päävalikon ja alatunnisteen valikon</li>
			<li>liittää sivupohjat (henkilökunta, yhteystiedot, palvelusivut)</li>
			<li>luo oletussisältöpohjat, toimipisteet ja asiakasryhmät</li>
			<li>asettaa kestolinkit, aikavyöhykkeen ja päivämäärämuodon</li>
		</ul>
		<form method="post"><?php wp_nonce_field( 'kp_asenna' ); ?><p><button class="button button-primary" name="kp_asenna" value="1">Aja asennusapuri</button></p></form>
		<?php if ( $log ) : ?>
			<h2>Tulos</h2>
			<pre style="background:#fff;padding:12px;border:1px solid #ddd;max-width:900px;white-space:pre-wrap"><?php echo esc_html( implode( "\n", $log ) ); ?></pre>
		<?php endif; ?>
	</div>
	<?php
}

/* ---------- Ohjauspaneelin pikaohje ---------- */

add_action( 'wp_dashboard_setup', function () {
	wp_add_dashboard_widget( 'kp_pikaohje', 'Korjauspartnerit – pikaohje', function () {
		$links = array(
			admin_url( 'admin.php?page=kp-tyokalut' )          => 'Luo uusi artikkeli tai sivu valmiista pohjasta',
			admin_url( 'post-new.php?post_type=henkilokunta' ) => 'Lisää uusi henkilö',
			admin_url( 'post-new.php?post_type=tyopaikka' )    => 'Lisää työpaikkailmoitus',
			admin_url( 'post-new.php?post_type=referenssi' )   => 'Lisää referenssi',
			admin_url( 'edit.php?post_type=kp_yhteydenotto' )  => 'Lue lomakkeella tulleet yhteydenotot',
		);
		echo '<ul>';
		foreach ( $links as $url => $label ) {
			echo '<li><a href="' . esc_url( $url ) . '">' . esc_html( $label ) . '</a></li>';
		}
		echo '</ul><p><strong>Vinkit:</strong> Käytä otsikkotasoja järjestyksessä (Otsikko 2 → Otsikko 3). Lisää kuviin aina vaihtoehtoinen teksti. Henkilön puhelinnumero, sähköposti ja titteli päivittyvät automaattisesti kaikkialle, missä henkilö näkyy.</p>';
	} );
} );
