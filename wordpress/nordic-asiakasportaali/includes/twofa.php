<?php
/**
 * Kaksivaiheinen kirjautuminen sähköpostikoodilla henkilökunnalle (osakkaat, asentajat, ylläpitäjät).
 * Salasanan jälkeen sähköpostiin lähtee 6-numeroinen koodi (voimassa 10 min, 5 yritystä).
 * "Muista tämä laite 30 päivää" ohittaa koodin samalla selaimella.
 * Jos käyttäjällä on jo Two Factor- tai Wordfence-2FA käytössä, tätä ei kysytä.
 * Hätäkytkin: define( 'NAP_2FA_DISABLE', true ); wp-config.php:hen.
 */

defined( 'ABSPATH' ) || exit;

const NAP_2FA_TRUST_DAYS = 30;

function nap_2fa_required( WP_User $user ) {
	if ( defined( 'NAP_2FA_DISABLE' ) && NAP_2FA_DISABLE ) {
		return false;
	}
	if ( ! nap_settings()['twofa'] ) {
		return false;
	}
	if ( ! user_can( $user, 'nap_access' ) && ! user_can( $user, 'manage_options' ) ) {
		return false;
	}
	return ! nap_user_has_2fa( $user->ID ); // toinen 2FA-lisäosa hoitaa jo
}

/** Laitteen muistamisen eväste: käyttäjä|vanhenee|allekirjoitus. Versio-meta mitätöi kaikki laitteet. */
function nap_2fa_trust_sig( $uid, $exp ) {
	return hash_hmac( 'sha256', $uid . '|' . $exp . '|' . (int) get_user_meta( $uid, 'nap_2fa_ver', true ), wp_salt( 'auth' ) );
}

function nap_2fa_trusted( $uid ) {
	$c = isset( $_COOKIE['nap_2fa_trust'] ) ? sanitize_text_field( wp_unslash( $_COOKIE['nap_2fa_trust'] ) ) : '';
	foreach ( array_filter( explode( ',', $c ) ) as $item ) {
		$p = explode( '|', $item );
		if ( 3 === count( $p ) && (int) $p[0] === (int) $uid && (int) $p[1] > time() && hash_equals( nap_2fa_trust_sig( $uid, (int) $p[1] ), $p[2] ) ) {
			return true;
		}
	}
	return false;
}

function nap_2fa_trust_device( $uid ) {
	$exp   = time() + NAP_2FA_TRUST_DAYS * DAY_IN_SECONDS;
	$items = array();
	$c     = isset( $_COOKIE['nap_2fa_trust'] ) ? sanitize_text_field( wp_unslash( $_COOKIE['nap_2fa_trust'] ) ) : '';
	foreach ( array_filter( explode( ',', $c ) ) as $item ) { // samalla koneella voi olla useampi käyttäjä
		$p = explode( '|', $item );
		if ( 3 === count( $p ) && (int) $p[0] !== (int) $uid && (int) $p[1] > time() ) {
			$items[] = $item;
		}
	}
	$items[] = $uid . '|' . $exp . '|' . nap_2fa_trust_sig( $uid, $exp );
	setcookie( 'nap_2fa_trust', implode( ',', array_slice( $items, -4 ) ), array(
		'expires'  => $exp,
		'path'     => COOKIEPATH ?: '/',
		'domain'   => COOKIE_DOMAIN,
		'secure'   => is_ssl(),
		'httponly' => true,
		'samesite' => 'Lax',
	) );
}

/** Luo odottavan kirjautumisen ja lähettää koodin. Palauttaa tunnisteen tai false. */
function nap_2fa_start( WP_User $user, $remember, $redirect ) {
	$token = bin2hex( random_bytes( 20 ) );
	$code  = (string) random_int( 100000, 999999 );
	set_transient( 'nap_2fa_' . hash( 'sha256', $token ), array(
		'uid'      => $user->ID,
		'code'     => wp_hash_password( $code ),
		'tries'    => 0,
		'remember' => (bool) $remember,
		'redirect' => $redirect,
		'sent'     => time(),
		'exp'      => time() + 10 * MINUTE_IN_SECONDS,
	), 15 * MINUTE_IN_SECONDS );
	$ok = nap_2fa_send( $user, $code );
	return $ok ? $token : false;
}

function nap_2fa_send( WP_User $user, $code ) {
	return nap_mail(
		$user->user_email,
		'Kirjautumiskoodi: ' . $code,
		'Kirjautumiskoodisi',
		array(
			'Hei ' . nap_first_name( $user->display_name ) . ',',
			'Koodi: ' . $code,
			'Koodi on voimassa 10 minuuttia. Jos et ollut kirjautumassa, joku tietää salasanasi: vaihda se heti ja kerro muille osakkaille.',
		)
	);
}

/** Salasana oikein → koodivaihe ennen kuin kirjautumisevästettä annetaan. */
add_filter( 'authenticate', function ( $user ) {
	if ( ! ( $user instanceof WP_User ) || 'POST' !== ( $_SERVER['REQUEST_METHOD'] ?? '' ) || ! isset( $_POST['log'], $_POST['pwd'] ) ) { // phpcs:ignore
		return $user;
	}
	if ( defined( 'XMLRPC_REQUEST' ) || defined( 'REST_REQUEST' ) || ! nap_2fa_required( $user ) || nap_2fa_trusted( $user->ID ) ) {
		return $user;
	}
	$redirect = isset( $_REQUEST['redirect_to'] ) ? wp_unslash( $_REQUEST['redirect_to'] ) : ''; // phpcs:ignore
	$token    = nap_2fa_start( $user, ! empty( $_POST['rememberme'] ), $redirect ); // phpcs:ignore
	if ( ! $token ) {
		// Sähköposti ei lähtenyt: ei lukita ketään ulos, mutta kirjataan ylös.
		error_log( 'Nordic asiakasportaali: kirjautumiskoodin lähetys epäonnistui käyttäjälle ' . $user->user_login );
		return $user;
	}
	wp_safe_redirect( add_query_arg( array( 'action' => 'nap_2fa', 't' => $token ), wp_login_url() ) );
	exit;
}, 99 );

add_action( 'login_form_nap_2fa', function () {
	nocache_headers();
	$token = isset( $_REQUEST['t'] ) ? preg_replace( '/[^a-f0-9]/', '', (string) $_REQUEST['t'] ) : ''; // phpcs:ignore
	$key   = 'nap_2fa_' . hash( 'sha256', $token );
	$st    = $token ? get_transient( $key ) : false;
	$user  = $st ? get_userdata( $st['uid'] ) : false;
	if ( ! $st || ! $user || $st['exp'] < time() ) {
		delete_transient( $key );
		wp_safe_redirect( add_query_arg( 'nap2fa', 'expired', wp_login_url() ) );
		exit;
	}
	$error = '';
	$info  = '';
	if ( 'POST' === $_SERVER['REQUEST_METHOD'] && isset( $_POST['nap_code'] ) && check_admin_referer( 'nap_2fa_' . $token ) ) {
		$code = preg_replace( '/\D/', '', (string) wp_unslash( $_POST['nap_code'] ) );
		if ( $code && wp_check_password( $code, $st['code'] ) ) {
			delete_transient( $key );
			if ( ! empty( $_POST['nap_trust'] ) ) {
				nap_2fa_trust_device( $user->ID );
			}
			wp_set_auth_cookie( $user->ID, $st['remember'] );
			do_action( 'wp_login', $user->user_login, $user );
			$to = $st['redirect'] ?: ( user_can( $user, 'manage_options' ) ? admin_url() : home_url( '/crm/' ) );
			wp_safe_redirect( apply_filters( 'login_redirect', $to, $st['redirect'], $user ) );
			exit;
		}
		$st['tries']++;
		if ( $st['tries'] >= 5 ) {
			delete_transient( $key );
			wp_safe_redirect( add_query_arg( 'nap2fa', 'locked', wp_login_url() ) );
			exit;
		}
		set_transient( $key, $st, max( 60, $st['exp'] - time() + 300 ) );
		$error = 'Koodi ei täsmää. Yrityksiä jäljellä: ' . ( 5 - $st['tries'] ) . '.';
	} elseif ( isset( $_GET['resend'] ) && check_admin_referer( 'nap_2fa_resend_' . $token ) ) {
		if ( time() - $st['sent'] < 60 ) {
			$error = 'Odota hetki ennen kuin pyydät uutta koodia.';
		} else {
			$code       = (string) random_int( 100000, 999999 );
			$st['code'] = wp_hash_password( $code );
			$st['sent'] = time();
			$st['exp']  = time() + 10 * MINUTE_IN_SECONDS;
			$st['tries'] = 0;
			set_transient( $key, $st, 15 * MINUTE_IN_SECONDS );
			nap_2fa_send( $user, $code );
			$info = 'Uusi koodi lähetetty.';
		}
	}

	$email = $user->user_email;
	$at    = strpos( $email, '@' );
	$mask  = substr( $email, 0, 1 ) . str_repeat( '•', max( 1, $at - 1 ) ) . substr( $email, $at );
	login_header( 'Kirjautumiskoodi', '', $error ? new WP_Error( 'nap_2fa', esc_html( $error ) ) : null );
	if ( $info ) {
		echo '<p class="message">' . esc_html( $info ) . '</p>';
	}
	?>
	<form name="nap2fa" id="loginform" action="<?php echo esc_url( add_query_arg( array( 'action' => 'nap_2fa', 't' => $token ), wp_login_url() ) ); ?>" method="post" autocomplete="off">
		<p>Lähetimme 6-numeroisen koodin osoitteeseen <b><?php echo esc_html( $mask ); ?></b>.</p>
		<p style="margin-top:14px">
			<label for="nap_code">Koodi</label>
			<input type="text" name="nap_code" id="nap_code" class="input" inputmode="numeric" pattern="[0-9 ]*" autocomplete="one-time-code" maxlength="7" required autofocus style="font-size:24px;letter-spacing:.2em">
		</p>
		<p class="forgetmenot"><label><input name="nap_trust" type="checkbox" value="1" checked> Muista tämä laite <?php echo (int) NAP_2FA_TRUST_DAYS; ?> päivää</label></p>
		<?php wp_nonce_field( 'nap_2fa_' . $token ); ?>
		<p class="submit"><input type="submit" class="button button-primary button-large" value="Kirjaudu"></p>
	</form>
	<p id="nav"><a href="<?php echo esc_url( wp_nonce_url( add_query_arg( array( 'action' => 'nap_2fa', 't' => $token, 'resend' => 1 ), wp_login_url() ), 'nap_2fa_resend_' . $token ) ); ?>">Lähetä uusi koodi</a></p>
	<?php
	login_footer( 'nap_code' );
	exit;
} );

add_filter( 'wp_login_errors', function ( $errors ) {
	$m = isset( $_GET['nap2fa'] ) ? sanitize_key( $_GET['nap2fa'] ) : ''; // phpcs:ignore
	if ( 'expired' === $m ) {
		$errors->add( 'nap_2fa', 'Kirjautumiskoodi vanheni. Kirjaudu uudelleen.' );
	} elseif ( 'locked' === $m ) {
		$errors->add( 'nap_2fa', 'Liian monta väärää koodia. Kirjaudu uudelleen, niin saat uuden koodin.' );
	}
	return $errors;
} );

/** Salasanan vaihto mitätöi muistetut laitteet. */
add_action( 'after_password_reset', function ( $user ) {
	update_user_meta( $user->ID, 'nap_2fa_ver', (int) get_user_meta( $user->ID, 'nap_2fa_ver', true ) + 1 );
} );
add_action( 'profile_update', function ( $uid, $old ) {
	$new = get_userdata( $uid );
	if ( $new && $old && $new->user_pass !== $old->user_pass ) {
		update_user_meta( $uid, 'nap_2fa_ver', (int) get_user_meta( $uid, 'nap_2fa_ver', true ) + 1 );
	}
}, 10, 2 );
