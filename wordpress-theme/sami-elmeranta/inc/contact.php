<?php
/**
 * Oma yhteydenottolomake ilman lisäosia.
 *
 * Suojaukset: nonce, roskapostiansa (honeypot), aikaraja (botit lähettävät
 * heti), IP-kohtainen rajoitus, kenttien validointi ja otsakeinjektion esto.
 *
 * @package sami-elmeranta
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Tulosta lomake.
 */
function se_contact_form() {
	$status = isset( $_GET['viesti'] ) ? sanitize_key( wp_unslash( $_GET['viesti'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
	$input  = 'mt-2 block w-full rounded-2xl border-0 bg-mist px-4 py-3.5 ring-1 ring-line transition placeholder:text-muted/60 focus:bg-white focus:outline-none focus:ring-2 focus:ring-brand';

	if ( 'ok' === $status ) {
		echo '<p class="mt-6 rounded-2xl bg-brand-soft p-5 font-semibold text-brand-dark" role="status">Kiitos viestistäsi! Palaan asiaan mahdollisimman pian.</p>';
	} elseif ( $status ) {
		echo '<p class="mt-6 rounded-2xl bg-red-50 p-5 font-semibold text-red-700" role="alert">Oho, viesti ei mennyt perille. Tarkista kentät ja yritä uudelleen – tai soita ' . esc_html( se_opt( 'phone_display' ) ) . '.</p>';
	}
	?>
	<form action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post" class="mt-8 grid gap-5 sm:grid-cols-2">
		<input type="hidden" name="action" value="se_contact">
		<input type="hidden" name="se_ts" value="<?php echo esc_attr( time() ); ?>">
		<input type="hidden" name="se_back" value="<?php echo esc_url( get_permalink() ); ?>">
		<?php wp_nonce_field( 'se_contact', 'se_nonce' ); ?>
		<label class="font-medium">Etunimi
			<input name="etunimi" required maxlength="80" autocomplete="given-name" class="<?php echo esc_attr( $input ); ?>">
		</label>
		<label class="font-medium">Sukunimi
			<input name="sukunimi" required maxlength="80" autocomplete="family-name" class="<?php echo esc_attr( $input ); ?>">
		</label>
		<label class="font-medium sm:col-span-2">Sähköposti
			<input type="email" name="email" required maxlength="160" autocomplete="email" class="<?php echo esc_attr( $input ); ?>">
		</label>
		<label class="font-medium sm:col-span-2">Viestisi
			<textarea name="viesti" required rows="6" minlength="10" maxlength="5000" class="<?php echo esc_attr( $input ); ?>"></textarea>
		</label>
		<div class="hidden" aria-hidden="true">
			<label>Älä täytä tätä <input type="text" name="se_website" tabindex="-1" autocomplete="off"></label>
		</div>
		<p class="text-sm text-muted sm:col-span-2">
			Lähettämällä viestin hyväksyt, että tietojasi käsitellään yhteydenottoon vastaamiseksi.
			<?php
			$privacy = get_privacy_policy_url();
			if ( $privacy ) {
				echo '<a href="' . esc_url( $privacy ) . '" class="font-semibold text-brand-dark underline">Tietosuojaseloste</a>';
			}
			?>
		</p>
		<button type="submit" class="btn-primary sm:col-span-2 sm:justify-self-start">Lähetä <?php se_the_icon( 'lucide:send', 'size-4' ); ?></button>
	</form>
	<?php
}

/**
 * Käsittele lomake.
 */
function se_handle_contact() {
	$back = isset( $_POST['se_back'] ) ? esc_url_raw( wp_unslash( $_POST['se_back'] ) ) : home_url( '/' );
	$back = wp_validate_redirect( $back, home_url( '/' ) );
	$fail = function ( $code ) use ( $back ) {
		wp_safe_redirect( add_query_arg( 'viesti', $code, $back ) . '#lomake' );
		exit;
	};

	if ( ! isset( $_POST['se_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['se_nonce'] ) ), 'se_contact' ) ) {
		$fail( 'virhe' );
	}

	// Honeypot ja liian nopea lähetys → hiljainen "onnistui" boteille.
	$ts = isset( $_POST['se_ts'] ) ? (int) $_POST['se_ts'] : 0;
	if ( ! empty( $_POST['se_website'] ) || ( time() - $ts ) < 3 ) {
		wp_safe_redirect( add_query_arg( 'viesti', 'ok', $back ) . '#lomake' );
		exit;
	}

	// IP-kohtainen rajoitus: enintään 3 viestiä / 10 min.
	$ip    = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
	$key   = 'se_contact_' . md5( $ip );
	$count = (int) get_transient( $key );
	if ( $count >= 3 ) {
		$fail( 'raja' );
	}

	$first   = isset( $_POST['etunimi'] ) ? sanitize_text_field( wp_unslash( $_POST['etunimi'] ) ) : '';
	$last    = isset( $_POST['sukunimi'] ) ? sanitize_text_field( wp_unslash( $_POST['sukunimi'] ) ) : '';
	$email   = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';
	$message = isset( $_POST['viesti'] ) ? sanitize_textarea_field( wp_unslash( $_POST['viesti'] ) ) : '';

	if ( ! $first || ! $last || ! is_email( $email ) || mb_strlen( $message ) < 10 || mb_strlen( $message ) > 5000 ) {
		$fail( 'virhe' );
	}

	// Otsakeinjektion esto.
	$name = preg_replace( '/[\r\n<>"]+/', ' ', mb_substr( $first . ' ' . $last, 0, 160 ) );

	// Vastaanottaja: lomakkeen vastaanottaja → julkinen sähköposti → ylläpitäjän osoite (ei näy sivulla).
	$to = se_opt( 'form_recipient' );
	if ( ! is_email( $to ) ) {
		$to = is_email( se_opt( 'email' ) ) ? se_opt( 'email' ) : get_option( 'admin_email' );
	}
	$subject = 'Yhteydenotto sivustolta: ' . $name;
	$body    = "Nimi: {$name}\nSähköposti: {$email}\n\n{$message}\n\n--\nLähetetty: " . home_url( '/' );
	$headers = array( 'Content-Type: text/plain; charset=UTF-8', 'Reply-To: ' . $name . ' <' . $email . '>' );

	set_transient( $key, $count + 1, 10 * MINUTE_IN_SECONDS );

	if ( ! wp_mail( $to, $subject, $body, $headers ) ) {
		$fail( 'virhe' );
	}
	wp_safe_redirect( add_query_arg( 'viesti', 'ok', $back ) . '#lomake' );
	exit;
}
add_action( 'admin_post_nopriv_se_contact', 'se_handle_contact' );
add_action( 'admin_post_se_contact', 'se_handle_contact' );
