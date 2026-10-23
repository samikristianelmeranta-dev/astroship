<?php
/**
 * Yhteydenottolomake [kp_yhteydenotto] – korvaa vanhan Gravity Forms -lomakkeen.
 *
 * Viesti lähetetään sähköpostilla (Asetukset → Korjauspartnerit → Lomakkeen vastaanottaja)
 * ja tallennetaan lisäksi hallintaan kohtaan "Yhteydenotot".
 * Roskapostisuojana hunajapurkki-kenttä ja aikaraja.
 */

defined( 'ABSPATH' ) || exit;

add_action( 'init', function () {
	add_shortcode( 'kp_yhteydenotto', 'kp_sc_yhteydenotto' );
} );

function kp_form_topics() {
	return apply_filters( 'kp_form_topics', array(
		'Tarjouspyyntö',
		'Kuntotutkimukset ja kartoitukset',
		'Suunnittelu',
		'Rakennuttaminen ja valvonta',
		'Ylläpitopartneri',
		'Teollisuuden palvelut',
		'Muu yhteydenotto',
	) );
}

function kp_sc_yhteydenotto( $atts ) {
	$atts   = shortcode_atts( array( 'otsikko' => '' ), $atts, 'kp_yhteydenotto' );
	$status = isset( $_GET['kp_lomake'] ) ? sanitize_key( $_GET['kp_lomake'] ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
	$uid    = wp_unique_id( 'kp-lomake-' );
	$privacy = get_privacy_policy_url();

	ob_start();
	?>
	<div class="kp-lomake" id="viesti">
		<?php if ( $atts['otsikko'] ) : ?>
			<h2><?php echo esc_html( $atts['otsikko'] ); ?></h2>
		<?php endif; ?>

		<?php if ( 'ok' === $status ) : ?>
			<div class="kp-lomake__ilmoitus kp-lomake__ilmoitus--ok" role="status">
				<strong>Kiitos yhteydenotostasi!</strong> Olemme sinuun yhteydessä mahdollisimman pian, yleensä saman tai seuraavan arkipäivän aikana.
			</div>
		<?php elseif ( 'virhe' === $status ) : ?>
			<div class="kp-lomake__ilmoitus kp-lomake__ilmoitus--virhe" role="alert">
				Viestin lähetys epäonnistui. Tarkista pakolliset kentät ja yritä uudelleen, tai soita meille <a href="<?php echo esc_attr( kp_tel_href( kp_setting( 'puhelin' ) ) ); ?>"><?php echo esc_html( kp_setting( 'puhelin' ) ); ?></a>.
			</div>
		<?php endif; ?>

		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="kp-lomake__lomake">
			<input type="hidden" name="action" value="kp_yhteydenotto">
			<input type="hidden" name="kp_paluu" value="<?php echo esc_url( get_permalink() ); ?>">
			<input type="hidden" name="kp_aika" value="<?php echo esc_attr( time() ); ?>">
			<?php wp_nonce_field( 'kp_yhteydenotto', 'kp_nonce' ); ?>
			<div class="kp-lomake__hp" aria-hidden="true">
				<label>Jätä tämä kenttä tyhjäksi <input type="text" name="kp_kotisivu" tabindex="-1" autocomplete="off"></label>
			</div>

			<div class="kp-lomake__rivi kp-lomake__rivi--2">
				<p>
					<label for="<?php echo esc_attr( $uid ); ?>-nimi">Nimi <span aria-hidden="true">*</span></label>
					<input id="<?php echo esc_attr( $uid ); ?>-nimi" type="text" name="kp_nimi" required autocomplete="name">
				</p>
				<p>
					<label for="<?php echo esc_attr( $uid ); ?>-yritys">Yritys tai taloyhtiö</label>
					<input id="<?php echo esc_attr( $uid ); ?>-yritys" type="text" name="kp_yritys" autocomplete="organization">
				</p>
			</div>
			<div class="kp-lomake__rivi kp-lomake__rivi--2">
				<p>
					<label for="<?php echo esc_attr( $uid ); ?>-email">Sähköposti <span aria-hidden="true">*</span></label>
					<input id="<?php echo esc_attr( $uid ); ?>-email" type="email" name="kp_email" required autocomplete="email">
				</p>
				<p>
					<label for="<?php echo esc_attr( $uid ); ?>-puh">Puhelin</label>
					<input id="<?php echo esc_attr( $uid ); ?>-puh" type="tel" name="kp_puhelin" autocomplete="tel">
				</p>
			</div>
			<p>
				<label for="<?php echo esc_attr( $uid ); ?>-aihe">Aihe</label>
				<select id="<?php echo esc_attr( $uid ); ?>-aihe" name="kp_aihe">
					<?php foreach ( kp_form_topics() as $topic ) : ?>
						<option><?php echo esc_html( $topic ); ?></option>
					<?php endforeach; ?>
				</select>
			</p>
			<p>
				<label for="<?php echo esc_attr( $uid ); ?>-viesti">Viesti <span aria-hidden="true">*</span></label>
				<textarea id="<?php echo esc_attr( $uid ); ?>-viesti" name="kp_viesti" rows="6" required></textarea>
			</p>
			<p class="kp-lomake__suostumus">
				<label>
					<input type="checkbox" name="kp_suostumus" value="1" required>
					<span>Hyväksyn, että tietojani käsitellään yhteydenottoon vastaamiseksi<?php
					if ( $privacy ) {
						echo ' <a href="' . esc_url( $privacy ) . '">tietosuojaselosteen</a> mukaisesti';
					}
					?>. <span aria-hidden="true">*</span></span>
				</label>
			</p>
			<p><button type="submit" class="kp-button">Lähetä viesti</button></p>
		</form>
	</div>
	<?php
	return ob_get_clean();
}

add_action( 'admin_post_nopriv_kp_yhteydenotto', 'kp_handle_contact_form' );
add_action( 'admin_post_kp_yhteydenotto', 'kp_handle_contact_form' );

function kp_handle_contact_form() {
	$back = isset( $_POST['kp_paluu'] ) ? esc_url_raw( wp_unslash( $_POST['kp_paluu'] ) ) : home_url( '/' );
	$back = wp_validate_redirect( $back, home_url( '/' ) );

	$fail = function () use ( $back ) {
		wp_safe_redirect( add_query_arg( 'kp_lomake', 'virhe', $back ) . '#viesti' );
		exit;
	};

	if ( empty( $_POST['kp_nonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['kp_nonce'] ), 'kp_yhteydenotto' ) ) {
		$fail();
	}

	// Roskapostisuoja: täytetty piilokenttä tai liian nopea lähetys → hiljainen "onnistuminen".
	$elapsed = time() - ( isset( $_POST['kp_aika'] ) ? absint( $_POST['kp_aika'] ) : 0 );
	if ( ! empty( $_POST['kp_kotisivu'] ) || $elapsed < 3 ) {
		wp_safe_redirect( add_query_arg( 'kp_lomake', 'ok', $back ) . '#viesti' );
		exit;
	}

	$data = array(
		'Nimi'      => sanitize_text_field( wp_unslash( $_POST['kp_nimi'] ?? '' ) ),
		'Yritys'    => sanitize_text_field( wp_unslash( $_POST['kp_yritys'] ?? '' ) ),
		'Sähköposti' => sanitize_email( wp_unslash( $_POST['kp_email'] ?? '' ) ),
		'Puhelin'   => sanitize_text_field( wp_unslash( $_POST['kp_puhelin'] ?? '' ) ),
		'Aihe'      => sanitize_text_field( wp_unslash( $_POST['kp_aihe'] ?? '' ) ),
		'Viesti'    => sanitize_textarea_field( wp_unslash( $_POST['kp_viesti'] ?? '' ) ),
		'Sivu'      => $back,
	);

	if ( ! $data['Nimi'] || ! is_email( $data['Sähköposti'] ) || ! $data['Viesti'] || empty( $_POST['kp_suostumus'] ) ) {
		$fail();
	}

	$body = '';
	foreach ( $data as $k => $v ) {
		if ( '' !== $v ) {
			$body .= $k . ': ' . ( 'Viesti' === $k ? "\n" : '' ) . $v . "\n";
		}
	}

	wp_insert_post( array(
		'post_type'    => 'kp_yhteydenotto',
		'post_status'  => 'private',
		'post_title'   => $data['Aihe'] . ' – ' . $data['Nimi'],
		'post_content' => $body,
	) );

	$to      = kp_setting( 'lomake_vastaanottaja' );
	$subject = '[Verkkosivut] ' . $data['Aihe'] . ': ' . $data['Nimi'];
	$headers = array( 'Reply-To: ' . $data['Nimi'] . ' <' . $data['Sähköposti'] . '>' );
	wp_mail( $to, $subject, $body, $headers );

	wp_safe_redirect( add_query_arg( 'kp_lomake', 'ok', $back ) . '#viesti' );
	exit;
}
