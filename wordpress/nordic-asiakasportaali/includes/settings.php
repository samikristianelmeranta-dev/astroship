<?php
/**
 * Asetukset: Asetukset → Asiakasportaali.
 */

defined( 'ABSPATH' ) || exit;

function nap_settings_defaults() {
	return array(
		'form_ids'       => '',
		'default_owner'  => 0,
		'notify_stage'   => 1,
		'from_name'      => 'Nordic Ikkunat & Ovet Oy',
		'reply_to'       => 'info@ikkunakauppias.fi',
		'staff_notify'   => '',
		'twofa'          => 0,
		'auto_tasks'     => 1,
		'auto_watch'     => 1,
		'auto_digest'    => 1,
		'offer_reminders' => 1,
		'offer_days'     => 14,
		'measure_terms'  => 'Asiakkaan omat mitat: Kun asiakas mittaa itse tai toimittaa mitat meille, tuotteet valmistetaan asiakkaan ilmoittamien mittojen mukaan, ja vastuu mittojen oikeellisuudesta on aina asiakkaalla. Vääristä mitoista johtuvat muutokset ja uudet tuotteet ovat asiakkaan kustannuksella.',
		'cancel_terms'   => 'Ei lakisääteistä peruutusoikeutta: Suurin osa Skaalan toimittamista ikkunoista ja ovista valmistetaan mittatilaustyönä asiakkaan antamien mittojen ja toiveiden mukaan. Kuluttajansuojalain mukaan mittatilaustuotteilla ei ole 14 päivän maksutonta peruutusoikeutta, vaikka kauppa olisi tehty etämyyntinä tai kotimyyntinä (esimerkiksi edustajan käynnillä). Nordic Ikkunat & Ovet Oy toimii Skaalan jälleenmyyjänä.',
	);
}

/** Osakkaat: nimi ja sähköposti. Asetussivun painike luo puuttuvat tunnukset. */
function nap_partners() {
	return apply_filters( 'nap_partners', array(
		array( 'Sami Elmeranta', 'sami@ikkunakauppias.fi' ),
		array( 'Matias Sirkiä', 'matias@ikkunakauppias.fi' ),
		array( 'Jussi Henriksson', 'jussi@ikkunakauppias.fi' ),
		array( 'Anton Hörkkö', 'anton@ikkunakauppias.fi' ),
	) );
}

/**
 * Luo puuttuvat osakastunnukset ja lähettää jokaiselle WordPressin oman
 * "aseta salasana" -linkin. Olemassa olevalle käyttäjälle lisätään vain osakkaan rooli.
 *
 * @return array{created:string[],updated:string[],skipped:string[]}
 */
function nap_create_partner_accounts() {
	$r = array( 'created' => array(), 'updated' => array(), 'skipped' => array() );
	foreach ( nap_partners() as $p ) {
		list( $name, $email ) = $p;
		$user = get_user_by( 'email', $email );
		if ( $user ) {
			if ( user_can( $user, 'nap_all' ) ) {
				$r['skipped'][] = $email;
			} else {
				$user->add_role( 'nordic_osakas' );
				$r['updated'][] = $email;
			}
			continue;
		}
		$login = sanitize_user( strtok( $email, '@' ), true );
		if ( username_exists( $login ) ) {
			$login .= wp_rand( 10, 99 );
		}
		$parts = preg_split( '/\s+/', $name, 2 );
		$id    = wp_insert_user( array(
			'user_login'   => $login,
			'user_email'   => $email,
			'user_pass'    => wp_generate_password( 32, true, true ),
			'display_name' => $name,
			'first_name'   => $parts[0],
			'last_name'    => $parts[1] ?? '',
			'role'         => 'nordic_osakas',
		) );
		if ( is_wp_error( $id ) ) {
			$r['skipped'][] = $email . ' (' . $id->get_error_message() . ')';
			continue;
		}
		wp_new_user_notification( $id, null, 'user' );
		$r['created'][] = $email;
	}
	return $r;
}

add_action( 'admin_post_nap_create_partners', function () {
	if ( ! current_user_can( 'create_users' ) ) {
		wp_die( 'Ei oikeutta.' );
	}
	check_admin_referer( 'nap_create_partners' );
	$r = nap_create_partner_accounts();
	set_transient( 'nap_partner_result_' . get_current_user_id(), $r, 300 );
	wp_safe_redirect( admin_url( 'options-general.php?page=nordic-asiakasportaali&partners=1' ) );
	exit;
} );

function nap_settings() {
	$s = wp_parse_args( (array) get_option( 'nap_settings', array() ), nap_settings_defaults() );
	// Tarjouslomakkeiden tunnukset: oletuksena samat kuin Nordic CRM -lisäosassa.
	if ( '' === trim( (string) $s['form_ids'] ) && function_exists( 'nordic_crm_settings' ) ) {
		$crm            = nordic_crm_settings();
		$s['form_ids']  = isset( $crm['quote_form_ids'] ) ? (string) $crm['quote_form_ids'] : '';
	}
	return $s;
}

add_action( 'admin_menu', function () {
	add_options_page( 'Asiakasportaali', 'Asiakasportaali', 'nap_users', 'nordic-asiakasportaali', 'nap_settings_page' );
} );

add_filter( 'plugin_action_links_' . plugin_basename( NAP_FILE ), function ( $links ) {
	array_unshift( $links, '<a href="' . esc_url( admin_url( 'options-general.php?page=nordic-asiakasportaali' ) ) . '">Asetukset</a>' );
	array_unshift( $links, '<a href="' . esc_url( home_url( '/crm/' ) ) . '">Avaa CRM</a>' );
	return $links;
} );

add_action( 'admin_init', function () {
	register_setting( 'nap_settings', 'nap_settings', array(
		'type'              => 'array',
		'sanitize_callback' => function ( $in ) {
			$d = nap_settings_defaults();
			return array(
				'form_ids'      => preg_replace( '/[^0-9,]/', '', (string) ( $in['form_ids'] ?? '' ) ),
				'default_owner' => absint( $in['default_owner'] ?? 0 ),
				'notify_stage'  => empty( $in['notify_stage'] ) ? 0 : 1,
				'from_name'     => sanitize_text_field( $in['from_name'] ?? $d['from_name'] ),
				'reply_to'      => sanitize_email( $in['reply_to'] ?? $d['reply_to'] ),
				'staff_notify'  => sanitize_text_field( $in['staff_notify'] ?? '' ),
				'twofa'         => empty( $in['twofa'] ) ? 0 : 1,
				'auto_tasks'    => empty( $in['auto_tasks'] ) ? 0 : 1,
				'auto_watch'    => empty( $in['auto_watch'] ) ? 0 : 1,
				'auto_digest'   => empty( $in['auto_digest'] ) ? 0 : 1,
				'offer_reminders' => empty( $in['offer_reminders'] ) ? 0 : 1,
				'offer_days'    => max( 1, min( 365, absint( $in['offer_days'] ?? 14 ) ) ),
				'cancel_terms'  => sanitize_textarea_field( $in['cancel_terms'] ?? $d['cancel_terms'] ),
				'measure_terms' => sanitize_textarea_field( $in['measure_terms'] ?? $d['measure_terms'] ),
			);
		},
	) );
} );

function nap_settings_page() {
	$s     = nap_settings();
	$staff = nap_staff_users();
	?>
	<div class="wrap">
		<h1>Asiakasportaali</h1>
		<p>Nordicin CRM: <a href="<?php echo esc_url( home_url( '/crm/' ) ); ?>"><?php echo esc_html( home_url( '/crm/' ) ); ?></a> · Asiakkaan portaali: <code><?php echo esc_html( home_url( '/oma-kohde/' ) ); ?></code></p>
		<form method="post" action="options.php">
			<?php settings_fields( 'nap_settings' ); ?>
			<table class="form-table" role="presentation">
				<tr><th scope="row"><label for="nap_form_ids">Tarjouslomakkeet</label></th>
					<td><input id="nap_form_ids" name="nap_settings[form_ids]" value="<?php echo esc_attr( $s['form_ids'] ); ?>" class="regular-text">
					<p class="description">Fluent Forms -lomakkeiden ID:t pilkulla eroteltuna (Fluent Forms → Forms → ID-sarake). Jokaisesta lähetyksestä tulee asiakas ja kohde. Tyhjänä käytetään Nordic CRM:n asetusta.</p></td></tr>
				<tr><th scope="row"><label for="nap_owner">Uuden tarjouspyynnön vastuumyyjä</label></th>
					<td><select id="nap_owner" name="nap_settings[default_owner]"><option value="0">Ei valittu</option>
					<?php foreach ( $staff as $u ) : if ( 'osakas' !== $u['role'] ) continue; ?>
						<option value="<?php echo (int) $u['id']; ?>" <?php selected( (int) $s['default_owner'], $u['id'] ); ?>><?php echo esc_html( $u['name'] ); ?></option>
					<?php endforeach; ?></select>
					<p class="description">Saa tehtävän "Soita ja sovi mittaus" jokaisesta uudesta tarjouspyynnöstä.</p></td></tr>
				<tr><th scope="row">Ilmoitukset asiakkaalle</th>
					<td><label><input type="checkbox" name="nap_settings[notify_stage]" value="1" <?php checked( $s['notify_stage'] ); ?>> Lähetä asiakkaalle sähköposti, kun kohde siirtyy seuraavaan vaiheeseen</label></td></tr>
				<tr><th scope="row">Kaksivaiheinen kirjautuminen</th>
					<td><label><input type="checkbox" name="nap_settings[twofa]" value="1" <?php checked( $s['twofa'] ); ?>> Lisäturva: henkilökunta ja ylläpitäjät vahvistavat kirjautumisen sähköpostiin tulevalla koodilla</label>
					<p class="description">Ei asennettavaa eikä erillistä sovellusta. "Muista tämä laite" ohittaa koodin samalla selaimella <?php echo (int) NAP_2FA_TRUST_DAYS; ?> päivän ajan. Jos sähköposti ei jostain syystä lähde, kirjautuminen onnistuu ilman koodia, jotta ketään ei lukita ulos.</p></td></tr>
				<tr><th scope="row">Automaatiot</th>
					<td><label><input type="checkbox" name="nap_settings[auto_tasks]" value="1" <?php checked( $s['auto_tasks'] ); ?>> Vaiheen vaihto luo seuraavat tehtävät (esim. Hyväksytty → "Tilaa tuotteet Skaalalta")</label><br>
					<label><input type="checkbox" name="nap_settings[auto_watch]" value="1" <?php checked( $s['auto_watch'] ); ?>> Liidivahti: muistutus, jos tarjouspyyntöön ei ole reagoitu 24 tunnissa, ja 48 tunnin jälkeen muille osakkaille</label><br>
					<label><input type="checkbox" name="nap_settings[auto_digest]" value="1" <?php checked( $s['auto_digest'] ); ?>> Aamukooste arkiaamuisin klo 7 (omat tehtävät, uudet tarjouspyynnöt, maanantaisin jumissa olevat kohteet)</label>
					<p class="description">Tarkka ajoitus: cPanel → Cron Jobs → kerran tunnissa <code>wget -q -O /dev/null <?php echo esc_html( site_url( 'wp-cron.php?doing_wp_cron' ) ); ?></code></p></td></tr>
				<tr><th scope="row">Tarjoukset</th>
					<td><label>Voimassa oletuksena <input type="number" min="1" max="365" name="nap_settings[offer_days]" value="<?php echo (int) $s['offer_days']; ?>" style="width:70px"> päivää</label><br>
					<label><input type="checkbox" name="nap_settings[offer_reminders]" value="1" <?php checked( $s['offer_reminders'] ); ?>> Muistuta asiakasta avoimesta tarjouksesta 3 ja 7 päivän kohdalla sekä 2 päivää ennen voimassaolon päättymistä</label></td></tr>
				<tr><th scope="row"><label for="nap_terms">Peruutusehdot</label></th>
					<td><textarea id="nap_terms" name="nap_settings[cancel_terms]" rows="4" class="large-text"><?php echo esc_textarea( $s['cancel_terms'] ); ?></textarea>
					<p class="description">Näkyy asiakkaalle lähtevässä vahvistuksessa, kun tarjous merkitään hyväksytyksi. Tyhjänä ei näytetä.</p></td></tr>
				<tr><th scope="row"><label for="nap_mterms">Ehto: asiakkaan omat mitat</label></th>
					<td><textarea id="nap_mterms" name="nap_settings[measure_terms]" rows="3" class="large-text"><?php echo esc_textarea( $s['measure_terms'] ); ?></textarea>
					<p class="description">Näkyy kiitosviestissä ja hyväksynnän vahvistuksessa, kun toimitustapa on "Vain tuotteet, asiakas mittaa itse".</p></td></tr>
				<tr><th scope="row"><label for="nap_from">Lähettäjän nimi</label></th>
					<td><input id="nap_from" name="nap_settings[from_name]" value="<?php echo esc_attr( $s['from_name'] ); ?>" class="regular-text"></td></tr>
				<tr><th scope="row"><label for="nap_reply">Vastausosoite</label></th>
					<td><input id="nap_reply" type="email" name="nap_settings[reply_to]" value="<?php echo esc_attr( $s['reply_to'] ); ?>" class="regular-text"></td></tr>
				<tr><th scope="row"><label for="nap_staff">Ilmoitus uusista tarjouspyynnöistä</label></th>
					<td><input id="nap_staff" name="nap_settings[staff_notify]" value="<?php echo esc_attr( $s['staff_notify'] ); ?>" class="regular-text" placeholder="esim. info@ikkunakauppias.fi">
					<p class="description">Valinnainen. Sähköpostiosoitteet pilkulla eroteltuna.</p></td></tr>
			</table>
			<?php submit_button(); ?>
		</form>
		<h2>Käyttäjät</h2>
		<?php
		$res = isset( $_GET['partners'] ) ? get_transient( 'nap_partner_result_' . get_current_user_id() ) : false; // phpcs:ignore
		if ( $res ) :
			delete_transient( 'nap_partner_result_' . get_current_user_id() ); ?>
			<div class="notice notice-success"><p>
				<?php if ( $res['created'] ) echo 'Luotu ja salasanalinkki lähetetty: ' . esc_html( implode( ', ', $res['created'] ) ) . '<br>'; ?>
				<?php if ( $res['updated'] ) echo 'Osakkaan rooli lisätty: ' . esc_html( implode( ', ', $res['updated'] ) ) . '<br>'; ?>
				<?php if ( $res['skipped'] ) echo 'Ei muutoksia: ' . esc_html( implode( ', ', $res['skipped'] ) ); ?>
			</p></div>
		<?php endif; ?>
		<?php if ( current_user_can( 'create_users' ) ) : ?>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="margin:0 0 16px">
			<input type="hidden" name="action" value="nap_create_partners">
			<?php wp_nonce_field( 'nap_create_partners' ); ?>
			<p>Osakkaat: <?php echo esc_html( implode( ', ', array_map( function ( $p ) { return $p[0] . ' (' . $p[1] . ')'; }, nap_partners() ) ) ); ?></p>
			<?php submit_button( 'Luo puuttuvat osakastunnukset', 'secondary', 'submit', false ); ?>
			<p class="description">Jokainen saa sähköpostiin linkin oman salasanan asettamiseen. Olemassa olevaan tunnukseen lisätään vain osakkaan rooli.</p>
		</form>
		<?php endif; ?>
		<p>Lisää osakkaat ja asentajat kohdassa Käyttäjät → Lisää uusi ja valitse rooli <b>Nordic: osakas</b> tai <b>Nordic: asentaja</b>.</p>
		<table class="widefat striped" style="max-width:720px"><thead><tr><th>Nimi</th><th>Rooli</th><th>Kaksivaiheinen</th></tr></thead><tbody>
		<?php foreach ( $staff as $u ) : ?>
			<tr><td><?php echo esc_html( $u['name'] ); ?></td><td><?php echo esc_html( 'osakas' === $u['role'] ? 'Osakas' : 'Asentaja' ); ?></td>
			<td><?php echo $u['twofa'] ? 'Käytössä (2FA-lisäosa)' : ( $s['twofa'] ? 'Käytössä (sähköpostikoodi)' : 'Ei käytössä (käyttäjänimi + salasana)' ); // phpcs:ignore ?></td></tr>
		<?php endforeach; ?>
		</tbody></table>
	</div>
	<?php
}
