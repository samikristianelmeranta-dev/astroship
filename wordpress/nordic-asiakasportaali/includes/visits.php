<?php
/**
 * Käynnit ja kalenteri: mittaus-, asennus-, toimitus- ja huoltokäynnit asentajittain.
 *  - Asiakas saa vahvistuksen käynnin varaamisesta (ja muutoksesta) sekä muistutuksen edellisenä päivänä.
 *  - Asentajan puhelinnäkymä: kuvat ennen ja jälkeen, mittauspöytäkirja kuvineen ja luovutuskuittaus,
 *    jossa asiakas kuittaa työn sormella näytölle.
 * Kuvat ja kuittaukset tallennetaan suojattuun kansioon, eikä niihin pääse ilman kirjautumista.
 */

defined( 'ABSPATH' ) || exit;

function nap_visit_types() {
	return array(
		'mittaus' => array( 'Mittauskäynti', 60 ),
		'asennus' => array( 'Asennus', 480 ),
		'toimitus' => array( 'Toimitus', 60 ),
		'huolto'  => array( 'Huoltokäynti', 60 ),
		'muu'     => array( 'Käynti', 60 ),
	);
}

function nap_file_kinds() {
	return array( 'ennen' => 'Ennen', 'jalkeen' => 'Jälkeen', 'mittaus' => 'Mittaus', 'muu' => 'Muut', 'kuittaus' => 'Kuittaus' );
}

function nap_get_visit( $id ) {
	global $wpdb;
	return $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . nap_table( 'visits' ) . ' WHERE id = %d', (int) $id ), ARRAY_A );
}

/** Asentaja näkee käynnit, joihin hänet on merkitty, ja omien kohteidensa käynnit. */
function nap_can_see_visit( $v, $user_id = 0 ) {
	$user_id = $user_id ?: get_current_user_id();
	if ( ! $v || ! user_can( $user_id, 'nap_access' ) ) {
		return false;
	}
	return user_can( $user_id, 'nap_all' ) || (int) $v['installer_id'] === $user_id || nap_can_see_project( nap_get_project( (int) $v['project_id'] ), $user_id );
}

function nap_visit_label( $v ) {
	$t = nap_visit_types();
	return $t[ $v['type'] ][0] ?? 'Käynti';
}

/** "ti 21.10. klo 10.00–11.00" */
function nap_visit_when( $v, $year = false ) {
	$days  = array( 'su', 'ma', 'ti', 'ke', 'to', 'pe', 'la' );
	$start = strtotime( $v['start_at'] );
	$end   = strtotime( $v['end_at'] );
	$out   = $days[ (int) gmdate( 'w', $start ) ] . ' ' . gmdate( $year ? 'j.n.Y' : 'j.n.', $start );
	if ( (int) $v['all_day'] ) {
		return $out;
	}
	return $out . ' klo ' . gmdate( 'G.i', $start ) . '–' . gmdate( 'G.i', $end );
}

function nap_visit_out( $v, $full = false ) {
	$p    = nap_get_project( (int) $v['project_id'] );
	$c    = $p ? nap_get_customer( (int) $p['customer_id'] ) : null;
	$out  = array(
		'id'           => (int) $v['id'],
		'project_id'   => (int) $v['project_id'],
		'customer_id'  => $c ? (int) $c['id'] : 0,
		'customer'     => $c ? $c['name'] : '',
		'phone'        => $c ? $c['phone'] : '',
		'address'      => $p ? ( $p['address'] ?: trim( $c['address'] . ', ' . $c['city'], ', ' ) ) : '',
		'title'        => $p ? $p['title'] : '',
		'type'         => $v['type'],
		'label'        => nap_visit_label( $v ),
		'start_at'     => $v['start_at'],
		'end_at'       => $v['end_at'],
		'all_day'      => (bool) (int) $v['all_day'],
		'installer_id' => $v['installer_id'] ? (int) $v['installer_id'] : null,
		'notes'        => (string) $v['notes'],
		'status'       => $v['status'],
		'confirmed'    => (bool) $v['confirmed_at'],
		'reminded'     => (bool) $v['reminded_at'],
		'has_protocol' => ! empty( $v['protocol'] ),
		'handover'     => ! empty( $v['handover'] ) ? json_decode( $v['handover'], true ) : null,
	);
	if ( $full ) {
		global $wpdb;
		$out['email']    = $c ? $c['email'] : '';
		$out['protocol'] = ! empty( $v['protocol'] ) ? json_decode( $v['protocol'], true ) : null;
		$out['files']    = array_map( function ( $f ) {
			return array( 'id' => (int) $f['id'], 'kind' => $f['kind'], 'name' => $f['name'], 'caption' => $f['caption'], 'at' => $f['created_at'], 'by' => nap_user_name( $f['uploaded_by'] ) );
		}, $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM ' . nap_table( 'files' ) . ' WHERE visit_id = %d OR (project_id = %d AND visit_id IS NULL) ORDER BY created_at ASC', $v['id'], $v['project_id'] ), ARRAY_A ) );
		$out['delivery'] = $p ? nap_delivery( $p ) : 'asennus';
	}
	return $out;
}

/* ---------- Tallennus ---------- */

/** Lukee lomakkeen ajan: päivä, kellonaika ja kesto (min) tai koko päivä. */
function nap_visit_times( $req, $type ) {
	$date = sanitize_text_field( (string) $req->get_param( 'date' ) );
	if ( ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $date ) ) {
		return new WP_Error( 'nap', 'Valitse käynnin päivä.' );
	}
	$all  = (bool) $req->get_param( 'all_day' );
	$time = $all ? '07:00' : sanitize_text_field( (string) $req->get_param( 'time' ) );
	if ( ! preg_match( '/^\d{1,2}:\d{2}$/', $time ) ) {
		return new WP_Error( 'nap', 'Valitse kellonaika.' );
	}
	$dur   = $all ? 600 : max( 15, min( 720, (int) ( $req->get_param( 'duration' ) ?: nap_visit_types()[ $type ][1] ) ) );
	$start = strtotime( $date . ' ' . $time . ':00 UTC' );
	return array( gmdate( 'Y-m-d H:i:s', $start ), gmdate( 'Y-m-d H:i:s', $start + $dur * 60 ), $all ? 1 : 0 );
}

function nap_api_create_visit( WP_REST_Request $req ) {
	global $wpdb;
	$p = nap_get_project( (int) $req->get_param( 'project_id' ) );
	if ( ! nap_can_see_project( $p ) ) {
		return nap_err( 'Valitse kohde, johon sinulla on oikeus.' );
	}
	$type = sanitize_key( (string) $req->get_param( 'type' ) );
	if ( ! isset( nap_visit_types()[ $type ] ) ) {
		return nap_err( 'Valitse käynnin tyyppi.', 400 );
	}
	$times = nap_visit_times( $req, $type );
	if ( is_wp_error( $times ) ) {
		return nap_err( $times->get_error_message(), 400 );
	}
	$inst = (int) $req->get_param( 'installer_id' );
	if ( $inst && ! user_can( $inst, 'nap_access' ) ) {
		return nap_err( 'Valittu henkilö ei ole Nordicin käyttäjä.', 400 );
	}
	if ( ! current_user_can( 'nap_all' ) ) {
		$inst = get_current_user_id(); // asentaja varaa käyntejä itselleen
	}
	$now = nap_now();
	$wpdb->insert( nap_table( 'visits' ), array(
		'project_id'   => (int) $p['id'],
		'type'         => $type,
		'start_at'     => $times[0],
		'end_at'       => $times[1],
		'all_day'      => $times[2],
		'installer_id' => $inst ?: null,
		'notes'        => sanitize_textarea_field( (string) $req->get_param( 'notes' ) ),
		'status'       => 'planned',
		'created_by'   => get_current_user_id(),
		'created_at'   => $now,
		'updated_at'   => $now,
	) );
	$v = nap_get_visit( (int) $wpdb->insert_id );
	if ( $inst && ! $p['installer_id'] ) {
		nap_update_project( (int) $p['id'], array( 'installer_id' => $inst ) );
	}
	nap_log( (int) $p['customer_id'], (int) $p['id'], 'visit', nap_visit_label( $v ) . ' ' . nap_visit_when( $v ) . ( $inst ? ' (' . nap_user_name( $inst ) . ')' : '' ), true );
	nap_visit_advance_stage( $p, $type );
	if ( $req->get_param( 'notify' ) ) {
		nap_visit_notify( $v, 'confirm' );
	}
	return nap_visit_out( nap_get_visit( (int) $v['id'] ), true );
}

/**
 * Käynnin varaus vie kohdetta eteenpäin: mittaus → vaihe Mittaus, asennus hyväksytylle kohteelle → vaihe Asennus.
 * Asiakas saa käynnistä oman vahvistuksen, joten vaiheen yleistä ilmoitusta ei lähetetä.
 */
function nap_visit_advance_stage( $p, $type ) {
	$old = (int) $p['stage'];
	$new = $old;
	if ( 'mittaus' === $type && $old < 1 ) {
		$new = 1;
	} elseif ( in_array( $type, array( 'asennus', 'toimitus' ), true ) && $old >= 3 && $old < 5 ) {
		$new = 5;
	}
	if ( $new !== $old ) {
		nap_update_project( (int) $p['id'], array( 'stage' => $new ) );
		$p = nap_get_project( (int) $p['id'] );
		nap_log( (int) $p['customer_id'], (int) $p['id'], 'stage', 'Vaihe: ' . nap_stage_names( nap_delivery( $p ) )[ $new ], true );
		nap_on_stage_change( $p, $old );
	}
}

function nap_api_update_visit( WP_REST_Request $req ) {
	global $wpdb;
	$v = nap_get_visit( (int) $req['id'] );
	if ( ! nap_can_see_visit( $v ) ) {
		return nap_err( 'Ei oikeutta tähän käyntiin.' );
	}
	$data = array();
	$type = $req->get_param( 'type' ) ? sanitize_key( (string) $req->get_param( 'type' ) ) : $v['type'];
	if ( ! isset( nap_visit_types()[ $type ] ) ) {
		return nap_err( 'Tuntematon käynnin tyyppi.', 400 );
	}
	$data['type'] = $type;
	if ( $req->get_param( 'date' ) ) {
		$times = nap_visit_times( $req, $type );
		if ( is_wp_error( $times ) ) {
			return nap_err( $times->get_error_message(), 400 );
		}
		list( $data['start_at'], $data['end_at'], $data['all_day'] ) = $times;
	}
	if ( null !== $req->get_param( 'installer_id' ) && current_user_can( 'nap_all' ) ) {
		$data['installer_id'] = (int) $req->get_param( 'installer_id' ) ?: null;
	}
	if ( null !== $req->get_param( 'notes' ) ) {
		$data['notes'] = sanitize_textarea_field( (string) $req->get_param( 'notes' ) );
	}
	if ( in_array( $req->get_param( 'status' ), array( 'planned', 'done', 'cancelled' ), true ) ) {
		$data['status'] = $req->get_param( 'status' );
	}
	if ( null !== $req->get_param( 'protocol' ) ) {
		$data['protocol'] = wp_json_encode( nap_clean_protocol( (array) $req->get_param( 'protocol' ) ), JSON_UNESCAPED_UNICODE );
	}
	$moved = isset( $data['start_at'] ) && ( $data['start_at'] !== $v['start_at'] || $data['end_at'] !== $v['end_at'] );
	if ( $moved ) {
		$data['reminded_at'] = null; // uusi aika → uusi muistutus
	}
	$data['updated_at'] = nap_now();
	$wpdb->update( nap_table( 'visits' ), $data, array( 'id' => (int) $v['id'] ) );
	$nv = nap_get_visit( (int) $v['id'] );
	$p  = nap_get_project( (int) $v['project_id'] );
	if ( $moved ) {
		nap_log( (int) $p['customer_id'], (int) $p['id'], 'visit', nap_visit_label( $nv ) . ' siirretty: ' . nap_visit_when( $nv ), true );
	}
	if ( isset( $data['status'] ) && $data['status'] !== $v['status'] ) {
		nap_log( (int) $p['customer_id'], (int) $p['id'], 'visit', nap_visit_label( $nv ) . ( 'cancelled' === $data['status'] ? ' peruttu' : ( 'done' === $data['status'] ? ' tehty' : ' palautettu suunnitelluksi' ) ) . ' (' . nap_visit_when( $nv ) . ')', 'cancelled' === $data['status'] );
	}
	if ( isset( $data['protocol'] ) ) {
		$rows = count( (array) json_decode( $data['protocol'], true )['rows'] );
		nap_log( (int) $p['customer_id'], (int) $p['id'], 'protocol', 'Mittauspöytäkirja tallennettu (' . $rows . ' riviä)' );
	}
	if ( $req->get_param( 'notify' ) && ( $moved || ( isset( $data['status'] ) && 'cancelled' === $data['status'] && 'cancelled' !== $v['status'] ) ) ) {
		nap_visit_notify( $nv, 'cancelled' === $nv['status'] ? 'cancel' : 'update' );
	}
	return nap_visit_out( $nv, true );
}

function nap_clean_protocol( $in ) {
	$rows = array();
	foreach ( array_slice( (array) ( $in['rows'] ?? array() ), 0, 100 ) as $r ) {
		$row = array(
			'tila'   => sanitize_text_field( (string) ( $r['tila'] ?? '' ) ),
			'tyyppi' => sanitize_text_field( (string) ( $r['tyyppi'] ?? '' ) ),
			'leveys' => absint( $r['leveys'] ?? 0 ),
			'korkeus' => absint( $r['korkeus'] ?? 0 ),
			'kpl'    => max( 1, absint( $r['kpl'] ?? 1 ) ),
			'huomio' => sanitize_text_field( (string) ( $r['huomio'] ?? '' ) ),
		);
		if ( $row['tila'] || $row['leveys'] || $row['korkeus'] || $row['huomio'] ) {
			$rows[] = $row;
		}
	}
	return array(
		'rows'     => $rows,
		'seina'    => sanitize_text_field( (string) ( $in['seina'] ?? '' ) ),
		'poisto'   => sanitize_text_field( (string) ( $in['poisto'] ?? '' ) ),
		'lisatiedot' => sanitize_textarea_field( (string) ( $in['lisatiedot'] ?? '' ) ),
		'paivitetty' => nap_now(),
		'tekija'   => wp_get_current_user()->display_name,
	);
}

function nap_api_visits( WP_REST_Request $req ) {
	global $wpdb;
	$from = sanitize_text_field( (string) $req->get_param( 'from' ) );
	$to   = sanitize_text_field( (string) $req->get_param( 'to' ) );
	if ( ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $from ) || ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $to ) ) {
		$from = current_time( 'Y-m-d' );
		$to   = gmdate( 'Y-m-d', strtotime( $from . ' +7 days' ) );
	}
	$where = 'start_at >= %s AND start_at < %s';
	$args  = array( $from . ' 00:00:00', gmdate( 'Y-m-d', strtotime( $to . ' +1 day' ) ) . ' 00:00:00' );
	if ( $req->get_param( 'project_id' ) ) {
		$where = 'project_id = %d';
		$args  = array( (int) $req->get_param( 'project_id' ) );
	}
	$rows = $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM ' . nap_table( 'visits' ) . " WHERE $where ORDER BY start_at ASC LIMIT 500", $args ), ARRAY_A ); // phpcs:ignore
	$out  = array();
	foreach ( $rows as $v ) {
		if ( nap_can_see_visit( $v ) ) {
			$out[] = nap_visit_out( $v );
		}
	}
	return $out;
}

function nap_api_visit( WP_REST_Request $req ) {
	$v = nap_get_visit( (int) $req['id'] );
	return nap_can_see_visit( $v ) ? nap_visit_out( $v, true ) : nap_err( 'Ei oikeutta tähän käyntiin.' );
}

/* ---------- Kuvat ---------- */

function nap_api_visit_file( WP_REST_Request $req ) {
	global $wpdb;
	$v = nap_get_visit( (int) $req['id'] );
	if ( ! nap_can_see_visit( $v ) ) {
		return nap_err( 'Ei oikeutta tähän käyntiin.' );
	}
	$kind = sanitize_key( (string) $req->get_param( 'kind' ) );
	if ( ! isset( nap_file_kinds()[ $kind ] ) || 'kuittaus' === $kind ) {
		$kind = 'muu';
	}
	$f = $req->get_file_params()['file'] ?? null;
	if ( ! $f || UPLOAD_ERR_OK !== (int) $f['error'] || ! is_uploaded_file( $f['tmp_name'] ) ) {
		return nap_err( 'Kuvan lataus epäonnistui.', 400 );
	}
	if ( $f['size'] > 12 * MB_IN_BYTES ) {
		return nap_err( 'Kuva on liian suuri (enintään 12 Mt).', 400 );
	}
	$info = @getimagesize( $f['tmp_name'] ); // phpcs:ignore
	$ok   = array( IMAGETYPE_JPEG => 'image/jpeg', IMAGETYPE_PNG => 'image/png', IMAGETYPE_WEBP => 'image/webp' );
	if ( ! $info || ! isset( $ok[ $info[2] ] ) ) {
		return nap_err( 'Tiedoston pitää olla kuva (JPG, PNG tai WebP).', 400 );
	}
	$id = nap_store_file( $v, $kind, file_get_contents( $f['tmp_name'] ), $ok[ $info[2] ], sanitize_file_name( $f['name'] ), sanitize_text_field( (string) $req->get_param( 'caption' ) ) );
	$p  = nap_get_project( (int) $v['project_id'] );
	nap_log( (int) $p['customer_id'], (int) $p['id'], 'photo', 'Kuva lisätty (' . nap_file_kinds()[ $kind ] . ')' );
	return array( 'id' => $id );
}

function nap_store_file( $v, $kind, $data, $type, $name, $caption = '' ) {
	global $wpdb;
	$dir = nap_private_dir() . '/files';
	if ( ! is_dir( $dir ) ) {
		wp_mkdir_p( $dir );
	}
	$stored = bin2hex( random_bytes( 16 ) );
	file_put_contents( $dir . '/' . $stored, $data );
	$p = nap_get_project( (int) $v['project_id'] );
	$wpdb->insert( nap_table( 'files' ), array(
		'project_id'  => (int) $v['project_id'],
		'customer_id' => (int) $p['customer_id'],
		'visit_id'    => (int) $v['id'],
		'kind'        => $kind,
		'file'        => $stored,
		'name'        => mb_substr( $name ?: 'kuva', 0, 190 ),
		'type'        => $type,
		'size'        => strlen( $data ),
		'caption'     => mb_substr( (string) $caption, 0, 250 ),
		'uploaded_by' => get_current_user_id() ?: null,
		'created_at'  => nap_now(),
	) );
	return (int) $wpdb->insert_id;
}

function nap_api_delete_file( WP_REST_Request $req ) {
	global $wpdb;
	$f = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . nap_table( 'files' ) . ' WHERE id = %d', (int) $req['id'] ), ARRAY_A );
	if ( ! $f || 'kuittaus' === $f['kind'] || ! nap_can_see_project( nap_get_project( (int) $f['project_id'] ) ) && ! nap_can_see_visit( nap_get_visit( (int) $f['visit_id'] ) ) ) {
		return nap_err( 'Ei oikeutta.' );
	}
	@unlink( nap_private_dir() . '/files/' . basename( $f['file'] ) ); // phpcs:ignore
	$wpdb->delete( nap_table( 'files' ), array( 'id' => (int) $f['id'] ) );
	return array( 'ok' => true );
}

/** /crm/?tiedosto=ID (henkilökunta, oikeus tarkistetaan). */
function nap_send_file( $id ) {
	global $wpdb;
	$f = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . nap_table( 'files' ) . ' WHERE id = %d', (int) $id ), ARRAY_A );
	$ok = $f && ( nap_can_see_project( nap_get_project( (int) $f['project_id'] ) ) || nap_can_see_visit( nap_get_visit( (int) $f['visit_id'] ) ) );
	$path = $ok ? nap_private_dir() . '/files/' . basename( $f['file'] ) : '';
	if ( ! $ok || ! is_file( $path ) ) {
		status_header( 404 );
		exit( 'Tiedostoa ei löytynyt.' );
	}
	header( 'Content-Type: ' . $f['type'] );
	header( 'Content-Disposition: inline; filename="' . sanitize_file_name( $f['name'] ) . '"' );
	header( 'Cache-Control: private, max-age=86400' );
	header( 'X-Content-Type-Options: nosniff' );
	header( 'Content-Length: ' . filesize( $path ) );
	readfile( $path ); // phpcs:ignore
	exit;
}

/* ---------- Luovutuskuittaus ---------- */

function nap_handover_items( $delivery = 'asennus' ) {
	return 'asennus' === $delivery
		? array( 'Ikkunat ja ovet asennettu ja säädetty', 'Toiminta käyty läpi asiakkaan kanssa', 'Työmaa siivottu', 'Vanhat ikkunat ja ovet viety', 'Käyttö- ja huolto-ohjeet luovutettu' )
		: array( 'Tuotteet toimitettu sovitusti', 'Tuotteet tarkastettu vaurioiden varalta', 'Käyttö- ja huolto-ohjeet luovutettu' );
}

function nap_api_handover( WP_REST_Request $req ) {
	global $wpdb;
	$v = nap_get_visit( (int) $req['id'] );
	if ( ! nap_can_see_visit( $v ) ) {
		return nap_err( 'Ei oikeutta tähän käyntiin.' );
	}
	if ( ! empty( $v['handover'] ) ) {
		return nap_err( 'Työ on jo kuitattu.', 409 );
	}
	$name = sanitize_text_field( (string) $req->get_param( 'name' ) );
	$sig  = (string) $req->get_param( 'signature' );
	if ( ! $name ) {
		return nap_err( 'Kirjoita kuittaajan nimi.', 400 );
	}
	if ( ! preg_match( '#^data:image/png;base64,([A-Za-z0-9+/=]+)$#', $sig, $m ) || strlen( $m[1] ) < 800 ) {
		return nap_err( 'Allekirjoitus puuttuu. Pyydä asiakasta kuittaamaan sormella ruutuun.', 400 );
	}
	$png = base64_decode( $m[1] );
	if ( ! $png || strlen( $png ) > 2 * MB_IN_BYTES || "\x89PNG" !== substr( $png, 0, 4 ) ) {
		return nap_err( 'Allekirjoitusta ei voitu tallentaa.', 400 );
	}
	$p       = nap_get_project( (int) $v['project_id'] );
	$items   = nap_handover_items( nap_delivery( $p ) );
	$checked = array_values( array_intersect( $items, array_map( 'sanitize_text_field', (array) $req->get_param( 'checklist' ) ) ) );
	$file    = nap_store_file( $v, 'kuittaus', $png, 'image/png', 'kuittaus-' . $v['id'] . '.png' );
	$h       = array(
		'at'        => nap_now(),
		'name'      => $name,
		'checklist' => $checked,
		'items'     => $items,
		'remarks'   => sanitize_textarea_field( (string) $req->get_param( 'remarks' ) ),
		'by'        => wp_get_current_user()->display_name,
		'file'      => $file,
	);
	$wpdb->update( nap_table( 'visits' ), array( 'handover' => wp_json_encode( $h, JSON_UNESCAPED_UNICODE ), 'status' => 'done', 'updated_at' => nap_now() ), array( 'id' => (int) $v['id'] ) );
	$c = nap_get_customer( (int) $p['customer_id'] );
	nap_log( (int) $c['id'], (int) $p['id'], 'handover', 'Työ luovutettu ja kuitattu (' . $name . ')' . ( $h['remarks'] ? '. Huomautukset: ' . $h['remarks'] : '' ), true );
	if ( $h['remarks'] ) {
		nap_create_task( array(
			'project_id'  => (int) $p['id'],
			'customer_id' => (int) $c['id'],
			'title'       => 'Luovutuksen huomautukset: ' . mb_substr( $h['remarks'], 0, 120 ),
			'assignee_id' => $p['owner_id'] ?: get_current_user_id(),
			'due_date'    => nap_next_workday(),
		) );
	}
	// Kuittaus hoitaa luovutus- ja toimitustehtävät.
	$wpdb->query( $wpdb->prepare( 'UPDATE ' . nap_table( 'tasks' ) . " SET done_at = %s WHERE project_id = %d AND done_at IS NULL AND (title LIKE %s OR title LIKE %s)", nap_now(), $p['id'], 'Luovutus:%', 'Toimita tuotteet%' ) ); // phpcs:ignore
	// Kuitattu luovutus → kohde valmis (laskutus- ja kiitoslahjatehtävät syntyvät vaiheesta).
	$old = (int) $p['stage'];
	if ( $old < 6 ) {
		nap_update_project( (int) $p['id'], array( 'stage' => 6 ) );
		$p = nap_get_project( (int) $p['id'] );
		nap_log( (int) $c['id'], (int) $p['id'], 'stage', 'Vaihe: ' . nap_stage_names( nap_delivery( $p ) )[6], true );
		nap_on_stage_change( $p, $old );
	}
	if ( $c['email'] && nap_mail_handover( $c, $p, $v, $h, nap_create_login_link( (int) $c['id'] ) ) ) {
		nap_log( (int) $c['id'], (int) $p['id'], 'mail', 'Luovutuskuittaus lähetetty asiakkaalle' );
	}
	return nap_visit_out( nap_get_visit( (int) $v['id'] ), true );
}

/* ---------- Viestit asiakkaalle ---------- */

function nap_visit_notify( $v, $kind ) {
	global $wpdb;
	$p = nap_get_project( (int) $v['project_id'] );
	$c = $p ? nap_get_customer( (int) $p['customer_id'] ) : null;
	if ( ! $c || ! $c['email'] ) {
		return false;
	}
	$ok = nap_mail_visit( $c, $p, $v, $kind, nap_create_login_link( (int) $c['id'] ) );
	if ( $ok ) {
		$col = 'reminder' === $kind ? 'reminded_at' : 'confirmed_at';
		$wpdb->update( nap_table( 'visits' ), array( $col => nap_now() ), array( 'id' => (int) $v['id'] ) );
		$what = array( 'confirm' => 'Vahvistus', 'update' => 'Ilmoitus ajan muutoksesta', 'cancel' => 'Ilmoitus peruutuksesta', 'reminder' => 'Muistutus' );
		nap_log( (int) $c['id'], (int) $p['id'], 'mail', $what[ $kind ] . ' lähetetty asiakkaalle: ' . nap_visit_label( $v ) . ' ' . nap_visit_when( $v ) );
	}
	return $ok;
}

/** Muistutus edellisenä päivänä klo 12 jälkeen. */
add_action( 'nap_hourly', function () {
	global $wpdb;
	if ( (int) current_time( 'G' ) < 12 ) {
		return;
	}
	$tomorrow = gmdate( 'Y-m-d', strtotime( current_time( 'Y-m-d' ) . ' +1 day' ) );
	$rows     = $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM ' . nap_table( 'visits' ) . " WHERE status = 'planned' AND reminded_at IS NULL AND start_at >= %s AND start_at < %s", $tomorrow . ' 00:00:00', $tomorrow . ' 23:59:59' ), ARRAY_A ); // phpcs:ignore
	foreach ( $rows as $v ) {
		// Juuri vahvistettu käynti ei tarvitse erillistä muistutusta.
		if ( $v['confirmed_at'] && strtotime( $v['confirmed_at'] ) > current_time( 'timestamp' ) - 6 * HOUR_IN_SECONDS ) {
			$wpdb->update( nap_table( 'visits' ), array( 'reminded_at' => nap_now() ), array( 'id' => (int) $v['id'] ) );
			continue;
		}
		if ( ! nap_visit_notify( $v, 'reminder' ) ) {
			$wpdb->update( nap_table( 'visits' ), array( 'reminded_at' => nap_now() ), array( 'id' => (int) $v['id'] ) );
		}
	}
}, 25 );

/* ---------- REST ---------- */

add_action( 'rest_api_init', function () {
	$perm = function () {
		return current_user_can( 'nap_access' );
	};
	$r = function ( $route, $method, $cb ) use ( $perm ) {
		register_rest_route( 'nap/v1', $route, array( 'methods' => $method, 'callback' => $cb, 'permission_callback' => $perm ) );
	};
	$r( '/visits', 'GET', 'nap_api_visits' );
	$r( '/visits', 'POST', 'nap_api_create_visit' );
	$r( '/visits/(?P<id>\d+)', 'GET', 'nap_api_visit' );
	$r( '/visits/(?P<id>\d+)', 'POST', 'nap_api_update_visit' );
	$r( '/visits/(?P<id>\d+)/files', 'POST', 'nap_api_visit_file' );
	$r( '/visits/(?P<id>\d+)/handover', 'POST', 'nap_api_handover' );
	$r( '/files/(?P<id>\d+)/delete', 'POST', 'nap_api_delete_file' );
} );
