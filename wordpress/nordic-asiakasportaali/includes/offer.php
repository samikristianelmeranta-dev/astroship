<?php
/**
 * Tarjous: henkilökunta liittää tarjouksen (Skaalan tarjoustyökalun PDF, hinta, sisältö, voimassaolo) CRM:ssä,
 * asiakas näkee sen Oma kohde -sivulla ja voi hyväksyä (maksutavan valinta), kysyä tarkennusta tai kieltäytyä.
 * Muistutukset asiakkaalle 3 ja 7 päivän kohdalla sekä 2 päivää ennen voimassaolon päättymistä.
 * PDF:t tallennetaan suojattuun kansioon, eikä niihin pääse ilman kirjautumista.
 */

defined( 'ABSPATH' ) || exit;

function nap_get_offer( $p ) {
	return ! empty( $p['offer'] ) ? json_decode( $p['offer'], true ) : null;
}

function nap_offer_open( $offer ) {
	return $offer && 'sent' === $offer['status'] && ! nap_offer_expired( $offer );
}

function nap_offer_expired( $offer ) {
	return ! empty( $offer['valid_until'] ) && $offer['valid_until'] < current_time( 'Y-m-d' );
}

/* ---------- PDF-tiedostot suojattuun kansioon ---------- */

function nap_private_dir() {
	$up  = wp_upload_dir();
	$dir = trailingslashit( $up['basedir'] ) . 'nap-private';
	if ( ! is_dir( $dir ) ) {
		wp_mkdir_p( $dir );
	}
	if ( ! file_exists( $dir . '/.htaccess' ) ) {
		file_put_contents( $dir . '/.htaccess', "Require all denied\nDeny from all\n" );
		file_put_contents( $dir . '/index.php', "<?php // Hiljaisuus on kultaa.\n" );
	}
	return $dir;
}

/** Tallentaa ladatun PDF:n. Palauttaa tiedostonimen tai WP_Errorin. */
function nap_store_offer_pdf( $file ) {
	if ( empty( $file['tmp_name'] ) || UPLOAD_ERR_OK !== (int) $file['error'] || ! is_uploaded_file( $file['tmp_name'] ) ) {
		return new WP_Error( 'nap', 'Tiedoston lataus epäonnistui.' );
	}
	if ( $file['size'] > 15 * MB_IN_BYTES ) {
		return new WP_Error( 'nap', 'PDF on liian suuri (enintään 15 Mt).' );
	}
	$head = file_get_contents( $file['tmp_name'], false, null, 0, 5 );
	if ( '%PDF-' !== $head ) {
		return new WP_Error( 'nap', 'Liitteen pitää olla PDF-tiedosto.' );
	}
	$name = 'tarjous-' . bin2hex( random_bytes( 16 ) ) . '.pdf';
	if ( ! move_uploaded_file( $file['tmp_name'], nap_private_dir() . '/' . $name ) ) {
		return new WP_Error( 'nap', 'Tiedoston tallennus epäonnistui.' );
	}
	return $name;
}

/** Lähettää tarjouksen PDF:n selaimeen (oikeus tarkistettu kutsujassa). */
function nap_send_offer_pdf( $offer, $download_name = 'tarjous.pdf' ) {
	$path = nap_private_dir() . '/' . basename( (string) ( $offer['file'] ?? '' ) );
	if ( empty( $offer['file'] ) || ! is_file( $path ) ) {
		status_header( 404 );
		exit( 'Tiedostoa ei löytynyt.' );
	}
	nocache_headers();
	header( 'Content-Type: application/pdf' );
	header( 'Content-Disposition: inline; filename="' . sanitize_file_name( $download_name ) . '"' );
	header( 'Content-Length: ' . filesize( $path ) );
	header( 'X-Content-Type-Options: nosniff' );
	readfile( $path ); // phpcs:ignore
	exit;
}

/* ---------- Tarjouksen lähetys (CRM) ---------- */

function nap_api_offer( WP_REST_Request $req ) {
	$p = nap_get_project( (int) $req['id'] );
	if ( ! nap_can_see_project( $p ) ) {
		return nap_err( 'Ei oikeutta tähän kohteeseen.' );
	}
	if ( ! empty( $p['approval'] ) ) {
		return nap_err( 'Tarjous on jo hyväksytty.', 409 );
	}
	$sum = (float) str_replace( array( ' ', ',' ), array( '', '.' ), (string) $req->get_param( 'sum' ) );
	if ( $sum <= 0 ) {
		return nap_err( 'Anna tarjouksen hinta.', 400 );
	}
	$old   = nap_get_offer( $p );
	$file  = $old['file'] ?? '';
	$fname = $old['file_name'] ?? '';
	$files = $req->get_file_params();
	if ( ! empty( $files['pdf']['name'] ) ) {
		$stored = nap_store_offer_pdf( $files['pdf'] );
		if ( is_wp_error( $stored ) ) {
			return nap_err( $stored->get_error_message(), 400 );
		}
		$file  = $stored;
		$fname = sanitize_file_name( $files['pdf']['name'] );
	}
	$valid = sanitize_text_field( (string) $req->get_param( 'valid_until' ) );
	$me    = wp_get_current_user();
	$offer = array(
		'version'     => (int) ( $old['version'] ?? 0 ) + 1,
		'status'      => 'sent',
		'sum'         => $sum,
		'labor'       => max( 0, (float) str_replace( array( ' ', ',' ), array( '', '.' ), (string) $req->get_param( 'labor' ) ) ),
		'valid_until' => preg_match( '/^\d{4}-\d{2}-\d{2}$/', $valid ) ? $valid : '',
		'content'     => sanitize_textarea_field( (string) $req->get_param( 'content' ) ),
		'notes'       => sanitize_textarea_field( (string) $req->get_param( 'notes' ) ),
		'file'        => $file,
		'file_name'   => $fname,
		'sent_at'     => nap_now(),
		'sent_by'     => (int) $me->ID,
		'sent_name'   => $me->display_name,
		'viewed_at'   => '',
		'reminders'   => array(),
	);
	$prev = (int) $p['stage'];
	nap_update_project( (int) $p['id'], array( 'offer' => $offer, 'value' => $sum, 'stage' => max( 2, $prev ) ) );
	$p   = nap_get_project( (int) $p['id'] );
	$c   = nap_get_customer( (int) $p['customer_id'] );
	$eur = number_format( $sum, 0, ',', ' ' ) . ' €';
	nap_log( $c['id'], $p['id'], 'offer', ( $offer['version'] > 1 ? 'Päivitetty tarjous' : 'Tarjous' ) . ' lähetetty (' . $eur . ')', true );
	if ( $prev < 2 ) {
		nap_log( $c['id'], $p['id'], 'stage', 'Vaihe: ' . nap_stage_names( nap_delivery( $p ) )[2], true );
		nap_on_stage_change( $p, $prev );
	}
	if ( $req->get_param( 'notify' ) && 'false' !== $req->get_param( 'notify' ) && $c['email'] ) {
		if ( nap_mail_offer( $c, $p, $offer, nap_create_login_link( (int) $c['id'] ) ) ) {
			nap_log( $c['id'], $p['id'], 'mail', 'Tarjous lähetetty asiakkaalle sähköpostilla (Oma kohde -linkki)' );
		}
	}
	return nap_project_out( $p );
}

add_action( 'rest_api_init', function () {
	register_rest_route( 'nap/v1', '/projects/(?P<id>\d+)/offer', array(
		'methods'             => 'POST',
		'callback'            => 'nap_api_offer',
		'permission_callback' => function () {
			return current_user_can( 'nap_access' );
		},
	) );
} );

/* ---------- Asiakkaan toiminnot Oma kohde -sivulla ---------- */

/** Asiakaskohtainen lomakeavain (WordPressin nonce ei erottele kirjautumattomia). */
function nap_customer_nonce( $c, $action ) {
	return substr( hash_hmac( 'sha256', $c['id'] . '|' . $c['session_version'] . '|' . $action . '|' . wp_date( 'Y-m-d' ), wp_salt( 'nonce' ) ), 0, 20 );
}

function nap_check_customer_nonce( $c, $action, $value ) {
	$value = (string) $value;
	foreach ( array( 0, 1 ) as $days_ago ) {
		$exp = substr( hash_hmac( 'sha256', $c['id'] . '|' . $c['session_version'] . '|' . $action . '|' . wp_date( 'Y-m-d', time() - $days_ago * DAY_IN_SECONDS ), wp_salt( 'nonce' ) ), 0, 20 );
		if ( hash_equals( $exp, $value ) ) {
			return true;
		}
	}
	return false;
}

/** Käsittelee asiakkaan tarjoustoiminnon. Palauttaa viestikoodin uudelleenohjaukseen. */
function nap_customer_offer_action( $c, $action ) {
	$p = nap_get_project( (int) ( $_POST['project_id'] ?? 0 ) ); // phpcs:ignore
	if ( ! $p || (int) $p['customer_id'] !== (int) $c['id'] || ! nap_check_customer_nonce( $c, 'offer' . $p['id'], wp_unslash( $_POST['nap_n'] ?? '' ) ) ) { // phpcs:ignore
		return 'virhe';
	}
	$offer = nap_get_offer( $p );
	if ( ! nap_offer_open( $offer ) ) {
		return 'suljettu';
	}
	if ( 'offer_accept' === $action ) {
		$pay = sanitize_key( $_POST['payment'] ?? '' ); // phpcs:ignore
		if ( empty( $_POST['terms'] ) || ! in_array( $pay, array( 'holvi', 'rahoitus' ), true ) ) { // phpcs:ignore
			return 'ehdot';
		}
		$r = nap_record_approval( $p, array( 'value' => $offer['sum'], 'how' => 'portaali', 'payment' => $pay, 'note' => '', 'by' => 0, 'by_name' => $c['name'], 'confirm' => true ) );
		return is_wp_error( $r ) ? 'virhe' : 'hyvaksytty';
	}
	if ( 'offer_question' === $action ) {
		$q = trim( sanitize_textarea_field( wp_unslash( $_POST['question'] ?? '' ) ) ); // phpcs:ignore
		if ( '' === $q ) {
			return 'tyhja';
		}
		$q = mb_substr( $q, 0, 2000 );
		nap_log( $c['id'], $p['id'], 'question', 'Kysymys tarjouksesta: ' . $q, true );
		nap_create_task( array(
			'project_id'  => $p['id'],
			'customer_id' => $c['id'],
			'title'       => 'Vastaa asiakkaan kysymykseen tarjouksesta: ' . $c['name'],
			'assignee_id' => $p['owner_id'] ?: (int) nap_settings()['default_owner'],
			'due_date'    => current_time( 'Y-m-d' ),
		) );
		nap_notify_owner( $p, 'Kysymys tarjouksesta: ' . $c['name'], 'Asiakas kysyy tarjouksesta',
			array( $c['name'] . ' (' . $c['email'] . ( $c['phone'] ? ', ' . $c['phone'] : '' ) . ') kysyy:', $q, 'Voit vastata suoraan tähän viestiin.' ),
			$c['email'] ? $c['name'] . ' <' . $c['email'] . '>' : null );
		return 'kysytty';
	}
	if ( 'offer_decline' === $action ) {
		$why               = mb_substr( trim( sanitize_textarea_field( wp_unslash( $_POST['reason'] ?? '' ) ) ), 0, 1000 ); // phpcs:ignore
		$offer['status']      = 'declined';
		$offer['answered_at'] = nap_now();
		nap_update_project( (int) $p['id'], array( 'offer' => $offer ) );
		nap_log( $c['id'], $p['id'], 'offer', 'Asiakas ei ole kiinnostunut tarjouksesta' . ( $why ? ': ' . $why : '' ), true );
		nap_notify_owner( $p, 'Tarjous hylätty: ' . $c['name'], 'Asiakas ei ole kiinnostunut',
			array( $c['name'] . ' kertoi Oma kohde -sivulla, ettei ole kiinnostunut tarjouksesta.', $why ? 'Syy: ' . $why : 'Syytä ei kerrottu.' ) );
		return 'kiitos';
	}
	return 'virhe';
}

/** Ilmoitus kohteen vastuumyyjälle (tai osakkaille, jos vastuumyyjää ei ole). */
function nap_notify_owner( $p, $subject, $heading, $lines, $reply_to = null ) {
	$owner = $p['owner_id'] ? get_userdata( (int) $p['owner_id'] ) : null;
	$to    = $owner ? $owner->user_email : wp_list_pluck( nap_osakkaat(), 'user_email' );
	return nap_mail( $to, $subject, $heading, $lines, array( 'Avaa asiakas', home_url( '/crm/#asiakas-' . (int) $p['customer_id'] ) ), $reply_to );
}

/** Asiakkaan ensimmäinen avaus kirjataan myyjälle. */
function nap_mark_offer_viewed( $p ) {
	$offer = nap_get_offer( $p );
	if ( $offer && 'sent' === $offer['status'] && empty( $offer['viewed_at'] ) ) {
		$offer['viewed_at'] = nap_now();
		nap_update_project( (int) $p['id'], array( 'offer' => $offer ) );
		nap_log( (int) $p['customer_id'], (int) $p['id'], 'offer', 'Asiakas avasi tarjouksen' );
	}
}

/* ---------- Muistutukset ---------- */

add_action( 'nap_hourly', function () {
	if ( nap_settings()['offer_reminders'] ) {
		nap_offer_reminders();
	}
}, 20 );

function nap_offer_reminders() {
	global $wpdb;
	$rows  = $wpdb->get_results( 'SELECT * FROM ' . nap_table( 'projects' ) . " WHERE offer IS NOT NULL AND offer LIKE '%\"status\":\"sent\"%' AND approval IS NULL LIMIT 100", ARRAY_A ); // phpcs:ignore
	$today = current_time( 'Y-m-d' );
	foreach ( $rows as $p ) {
		$offer = nap_get_offer( $p );
		if ( ! nap_offer_open( $offer ) ) {
			continue;
		}
		$c = nap_get_customer( (int) $p['customer_id'] );
		if ( ! $c || ! $c['email'] ) {
			continue;
		}
		$days = (int) floor( ( strtotime( $today ) - strtotime( substr( $offer['sent_at'], 0, 10 ) ) ) / DAY_IN_SECONDS );
		$left = $offer['valid_until'] ? (int) floor( ( strtotime( $offer['valid_until'] ) - strtotime( $today ) ) / DAY_IN_SECONDS ) : 999;
		$sent = (array) $offer['reminders'];
		$due  = '';
		if ( $left <= 2 && $left >= 0 && ! in_array( 'exp', $sent, true ) && $days >= 1 ) {
			$due = 'exp';
		} elseif ( $days >= 7 && ! in_array( 'd7', $sent, true ) ) {
			$due = 'd7';
		} elseif ( $days >= 3 && ! in_array( 'd3', $sent, true ) && ! in_array( 'd7', $sent, true ) ) {
			$due = 'd3';
		}
		if ( ! $due ) {
			continue;
		}
		// Vain yksi muistutus kerrallaan: aiemmat merkitään hoidetuiksi.
		$order = array( 'd3', 'd7', 'exp' );
		$sent  = array_values( array_unique( array_merge( $sent, array_slice( $order, 0, array_search( $due, $order, true ) + 1 ) ) ) );
		$offer['reminders'] = $sent;
		nap_update_project( (int) $p['id'], array( 'offer' => $offer ) );
		if ( nap_mail_offer_reminder( $c, $p, $offer, $due, nap_create_login_link( (int) $c['id'] ) ) ) {
			nap_log( $c['id'], $p['id'], 'mail', 'Muistutus tarjouksesta lähetetty asiakkaalle' );
		}
	}
}
