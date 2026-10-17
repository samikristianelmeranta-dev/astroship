<?php
/**
 * Nordicin henkilökunnan rajapinta (/wp-json/nap/v1/...), jota CRM-sovellus käyttää.
 * Kirjautuminen: WordPressin istunto + wp_rest-nonce. Jokainen pyyntö tarkistaa oikeuden
 * kohteen/asiakkaan tasolla (asentaja näkee vain omat kohteensa).
 */

defined( 'ABSPATH' ) || exit;

add_action( 'rest_api_init', function () {
	$ns   = 'nap/v1';
	$perm = function () {
		return current_user_can( 'nap_access' );
	};
	$r = function ( $route, $method, $cb ) use ( $ns, $perm ) {
		register_rest_route( $ns, $route, array( 'methods' => $method, 'callback' => $cb, 'permission_callback' => $perm ) );
	};
	$r( '/bootstrap', 'GET', 'nap_api_bootstrap' );
	$r( '/board', 'GET', 'nap_api_board' );
	$r( '/projects/(?P<id>\d+)', 'POST', 'nap_api_update_project' );
	$r( '/customers', 'GET', 'nap_api_customers' );
	$r( '/customers', 'POST', 'nap_api_create_customer' );
	$r( '/customers/(?P<id>\d+)', 'GET', 'nap_api_customer' );
	$r( '/customers/(?P<id>\d+)', 'POST', 'nap_api_update_customer' );
	$r( '/customers/(?P<id>\d+)/note', 'POST', 'nap_api_note' );
	$r( '/customers/(?P<id>\d+)/invite', 'POST', 'nap_api_invite' );
	$r( '/tasks', 'GET', 'nap_api_tasks' );
	$r( '/tasks', 'POST', 'nap_api_create_task' );
	$r( '/tasks/(?P<id>\d+)', 'POST', 'nap_api_update_task' );
} );

function nap_err( $msg, $code = 403 ) {
	return new WP_Error( 'nap_error', $msg, array( 'status' => $code ) );
}

function nap_user_name( $id ) {
	if ( ! $id ) {
		return '';
	}
	$u = get_userdata( (int) $id );
	return $u ? $u->display_name : '';
}

function nap_project_out( $p, $customer = null ) {
	return array(
		'id'           => (int) $p['id'],
		'customer_id'  => (int) $p['customer_id'],
		'customer'     => $customer ? $customer['name'] : '',
		'title'        => $p['title'],
		'address'      => $p['address'],
		'stage'        => (int) $p['stage'],
		'value'        => (float) $p['value'],
		'owner_id'     => $p['owner_id'] ? (int) $p['owner_id'] : null,
		'installer_id' => $p['installer_id'] ? (int) $p['installer_id'] : null,
		'details'      => (string) $p['details'],
		'updated_at'   => $p['updated_at'],
	);
}

function nap_api_bootstrap() {
	$me = wp_get_current_user();
	return array(
		'me'     => array( 'id' => (int) $me->ID, 'name' => $me->display_name, 'role' => current_user_can( 'nap_all' ) ? 'osakas' : 'asentaja', 'twofa' => nap_user_has_2fa( $me->ID ) ),
		'users'  => nap_staff_users(),
		'stages' => NAP_STAGES,
		'today'  => current_time( 'Y-m-d' ),
	);
}

function nap_api_board() {
	global $wpdb;
	$rows = $wpdb->get_results( 'SELECT p.*, c.name AS cname FROM ' . nap_table( 'projects' ) . ' p JOIN ' . nap_table( 'customers' ) . ' c ON c.id = p.customer_id ORDER BY p.updated_at DESC LIMIT 500', ARRAY_A );
	$out  = array();
	foreach ( $rows as $p ) {
		if ( nap_can_see_project( $p ) ) {
			$out[] = nap_project_out( $p, array( 'name' => $p['cname'] ) );
		}
	}
	return $out;
}

function nap_api_update_project( WP_REST_Request $req ) {
	$p = nap_get_project( (int) $req['id'] );
	if ( ! nap_can_see_project( $p ) ) {
		return nap_err( 'Ei oikeutta tähän kohteeseen.' );
	}
	$data = array();
	foreach ( array( 'title', 'address', 'details', 'value', 'stage', 'installer_id', 'owner_id' ) as $k ) {
		if ( null !== $req->get_param( $k ) ) {
			$data[ $k ] = $req->get_param( $k );
		}
	}
	if ( isset( $data['owner_id'] ) && ! current_user_can( 'nap_all' ) ) {
		unset( $data['owner_id'] ); // vain osakas vaihtaa vastuumyyjää
	}
	foreach ( array( 'owner_id', 'installer_id' ) as $k ) {
		if ( ! empty( $data[ $k ] ) && ! user_can( (int) $data[ $k ], 'nap_access' ) ) {
			return nap_err( 'Valittu henkilö ei ole Nordicin käyttäjä.', 400 );
		}
	}
	$old_stage = (int) $p['stage'];
	nap_update_project( (int) $p['id'], $data );
	$p = nap_get_project( (int) $p['id'] );

	if ( isset( $data['stage'] ) && (int) $p['stage'] !== $old_stage ) {
		$c = nap_get_customer( (int) $p['customer_id'] );
		nap_log( $c['id'], $p['id'], 'stage', 'Vaihe: ' . NAP_STAGES[ (int) $p['stage'] ], true );
		if ( nap_settings()['notify_stage'] && (int) $p['stage'] > $old_stage && $c['email'] ) {
			if ( nap_mail_stage( $c, $p, home_url( '/oma-kohde/' ) ) ) {
				nap_log( $c['id'], $p['id'], 'mail', 'Asiakkaalle ilmoitettu vaiheesta: ' . NAP_STAGES[ (int) $p['stage'] ] );
			}
		}
	}
	if ( isset( $data['value'] ) ) {
		nap_log( $p['customer_id'], $p['id'], 'edit', 'Kohteen arvo päivitetty: ' . number_format( (float) $p['value'], 0, ',', ' ' ) . ' €' );
	}
	if ( array_key_exists( 'installer_id', $data ) ) {
		nap_log( $p['customer_id'], $p['id'], 'edit', 'Asentaja: ' . ( nap_user_name( $p['installer_id'] ) ?: 'ei valittu' ) );
	}
	return nap_project_out( $p );
}

function nap_api_customers( WP_REST_Request $req ) {
	global $wpdb;
	$q    = trim( (string) $req->get_param( 'q' ) );
	$sql  = 'SELECT * FROM ' . nap_table( 'customers' );
	$args = array();
	if ( '' !== $q ) {
		$like = '%' . $wpdb->esc_like( $q ) . '%';
		$sql .= ' WHERE name LIKE %s OR email LIKE %s OR address LIKE %s OR city LIKE %s OR phone LIKE %s';
		$args = array( $like, $like, $like, $like, $like );
	}
	$sql .= ' ORDER BY updated_at DESC LIMIT 300';
	$rows = $args ? $wpdb->get_results( $wpdb->prepare( $sql, $args ), ARRAY_A ) : $wpdb->get_results( $sql, ARRAY_A ); // phpcs:ignore
	$out  = array();
	foreach ( $rows as $c ) {
		if ( ! nap_can_see_customer( $c ) ) {
			continue;
		}
		$projects = nap_customer_projects( (int) $c['id'] );
		$stage    = $projects ? max( array_map( function ( $p ) { return (int) $p['stage']; }, $projects ) ) : 0;
		$out[]    = array( 'id' => (int) $c['id'], 'name' => $c['name'], 'address' => trim( $c['address'] . ', ' . $c['city'], ', ' ), 'source' => $c['source'], 'stage' => $stage );
	}
	return $out;
}

function nap_api_customer( WP_REST_Request $req ) {
	global $wpdb;
	$c = nap_get_customer( (int) $req['id'] );
	if ( ! nap_can_see_customer( $c ) ) {
		return nap_err( 'Ei oikeutta tähän asiakkaaseen.' );
	}
	$projects = array();
	foreach ( nap_customer_projects( (int) $c['id'] ) as $p ) {
		if ( nap_can_see_project( $p ) ) {
			$projects[] = nap_project_out( $p );
		}
	}
	$tasks = $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM ' . nap_table( 'tasks' ) . ' WHERE customer_id = %d AND done_at IS NULL ORDER BY due_date IS NULL, due_date ASC', $c['id'] ), ARRAY_A );
	$refs  = $wpdb->get_results( $wpdb->prepare( 'SELECT id, name FROM ' . nap_table( 'customers' ) . ' WHERE referred_by = %d', $c['id'] ), ARRAY_A );
	$by    = $c['referred_by'] ? nap_get_customer( (int) $c['referred_by'] ) : null;
	$events = array_map( function ( $e ) {
		return array( 'type' => $e['type'], 'text' => $e['text'], 'at' => $e['created_at'], 'by' => nap_user_name( $e['user_id'] ), 'for_customer' => (bool) $e['for_customer'] );
	}, nap_customer_events( (int) $c['id'] ) );
	foreach ( $refs as &$r ) {
		$rp       = nap_customer_projects( (int) $r['id'] );
		$r['stage'] = $rp ? max( array_map( function ( $p ) { return (int) $p['stage']; }, $rp ) ) : 0;
	}
	return array(
		'id'          => (int) $c['id'],
		'name'        => $c['name'],
		'email'       => $c['email'],
		'phone'       => $c['phone'],
		'address'     => $c['address'],
		'city'        => $c['city'],
		'source'      => $c['source'],
		'owner_id'    => $c['owner_id'] ? (int) $c['owner_id'] : null,
		'ref_code'    => $c['ref_code'],
		'referred_by' => $by ? array( 'id' => (int) $by['id'], 'name' => $by['name'] ) : null,
		'referrals'   => $refs,
		'projects'    => $projects,
		'tasks'       => array_map( 'nap_task_out', $tasks ),
		'events'      => $events,
		'created_at'  => $c['created_at'],
	);
}

function nap_api_create_customer( WP_REST_Request $req ) {
	$email = sanitize_email( (string) $req->get_param( 'email' ) );
	$name  = sanitize_text_field( (string) $req->get_param( 'name' ) );
	if ( ! $name ) {
		return nap_err( 'Anna asiakkaan nimi.', 400 );
	}
	if ( $email && nap_find_customer_by_email( $email ) ) {
		return nap_err( 'Tällä sähköpostilla on jo asiakas.', 409 );
	}
	$me    = get_current_user_id();
	$owner = current_user_can( 'nap_all' ) ? ( (int) $req->get_param( 'owner_id' ) ?: $me ) : $me;
	$cid   = nap_create_customer( array(
		'name'     => $name,
		'email'    => $email,
		'phone'    => $req->get_param( 'phone' ),
		'address'  => $req->get_param( 'address' ),
		'city'     => $req->get_param( 'city' ),
		'source'   => $req->get_param( 'source' ) ?: 'Muu',
		'owner_id' => $owner,
	) );
	$c   = nap_get_customer( $cid );
	$pid = nap_create_project( array(
		'customer_id'  => $cid,
		'title'        => $req->get_param( 'title' ) ?: 'Ikkuna- ja oviremontti',
		'address'      => trim( $c['address'] . ', ' . $c['city'], ', ' ),
		'owner_id'     => $owner,
		'installer_id' => current_user_can( 'nap_all' ) ? null : $me,
	) );
	nap_log( $cid, $pid, 'request', 'Asiakas lisätty käsin (' . $c['source'] . ')', true );
	return array( 'id' => $cid, 'project_id' => $pid );
}

function nap_api_update_customer( WP_REST_Request $req ) {
	$c = nap_get_customer( (int) $req['id'] );
	if ( ! nap_can_see_customer( $c ) ) {
		return nap_err( 'Ei oikeutta tähän asiakkaaseen.' );
	}
	$data = array();
	foreach ( array( 'name', 'email', 'phone', 'address', 'city', 'source' ) as $k ) {
		if ( null !== $req->get_param( $k ) ) {
			$data[ $k ] = $req->get_param( $k );
		}
	}
	if ( null !== $req->get_param( 'owner_id' ) && current_user_can( 'nap_all' ) ) {
		$data['owner_id'] = (int) $req->get_param( 'owner_id' );
	}
	if ( isset( $data['email'] ) && $data['email'] && ! is_email( $data['email'] ) ) {
		return nap_err( 'Sähköpostiosoite ei kelpaa.', 400 );
	}
	nap_update_customer( (int) $c['id'], $data );
	nap_log( $c['id'], null, 'edit', 'Asiakastiedot päivitetty' );
	return nap_api_customer_by_id( (int) $c['id'] );
}

function nap_api_customer_by_id( $id ) {
	$r = new WP_REST_Request( 'GET' );
	$r->set_url_params( array( 'id' => $id ) );
	return nap_api_customer( $r );
}

function nap_api_note( WP_REST_Request $req ) {
	$c    = nap_get_customer( (int) $req['id'] );
	$text = sanitize_textarea_field( (string) $req->get_param( 'text' ) );
	if ( ! nap_can_see_customer( $c ) ) {
		return nap_err( 'Ei oikeutta tähän asiakkaaseen.' );
	}
	if ( '' === $text ) {
		return nap_err( 'Kirjoita muistiinpano.', 400 );
	}
	nap_log( $c['id'], (int) $req->get_param( 'project_id' ) ?: null, 'note', $text );
	return array( 'ok' => true );
}

function nap_api_invite( WP_REST_Request $req ) {
	$c = nap_get_customer( (int) $req['id'] );
	if ( ! nap_can_see_customer( $c ) ) {
		return nap_err( 'Ei oikeutta tähän asiakkaaseen.' );
	}
	if ( ! is_email( $c['email'] ) ) {
		return nap_err( 'Asiakkaalla ei ole sähköpostiosoitetta.', 400 );
	}
	$ok = nap_mail_login_link( $c, nap_create_login_link( (int) $c['id'] ) );
	nap_log( $c['id'], null, 'mail', 'Oma kohde -kirjautumislinkki lähetetty asiakkaalle' );
	return array( 'ok' => (bool) $ok );
}

/* ---------- Tehtävät ---------- */

function nap_task_out( $t ) {
	$c = $t['customer_id'] ? nap_get_customer( (int) $t['customer_id'] ) : null;
	return array(
		'id'          => (int) $t['id'],
		'title'       => $t['title'],
		'customer_id' => $t['customer_id'] ? (int) $t['customer_id'] : null,
		'customer'    => $c ? $c['name'] : '',
		'address'     => $c ? trim( $c['address'] . ', ' . $c['city'], ', ' ) : '',
		'project_id'  => $t['project_id'] ? (int) $t['project_id'] : null,
		'assignee_id' => $t['assignee_id'] ? (int) $t['assignee_id'] : null,
		'due_date'    => $t['due_date'],
		'done'        => (bool) $t['done_at'],
	);
}

function nap_can_see_task( $t ) {
	if ( ! $t ) {
		return false;
	}
	if ( current_user_can( 'nap_all' ) || (int) $t['assignee_id'] === get_current_user_id() || (int) $t['created_by'] === get_current_user_id() ) {
		return true;
	}
	return $t['project_id'] && nap_can_see_project( nap_get_project( (int) $t['project_id'] ) );
}

function nap_api_tasks( WP_REST_Request $req ) {
	global $wpdb;
	$rows = $wpdb->get_results( 'SELECT * FROM ' . nap_table( 'tasks' ) . ' WHERE done_at IS NULL OR done_at > DATE_SUB(NOW(), INTERVAL 14 DAY) ORDER BY done_at IS NOT NULL, due_date IS NULL, due_date ASC LIMIT 500', ARRAY_A );
	$out  = array();
	foreach ( $rows as $t ) {
		if ( nap_can_see_task( $t ) ) {
			$out[] = nap_task_out( $t );
		}
	}
	return $out;
}

function nap_api_create_task( WP_REST_Request $req ) {
	$title = sanitize_text_field( (string) $req->get_param( 'title' ) );
	if ( '' === $title ) {
		return nap_err( 'Kirjoita tehtävä.', 400 );
	}
	$cid = (int) $req->get_param( 'customer_id' );
	if ( $cid && ! nap_can_see_customer( nap_get_customer( $cid ) ) ) {
		return nap_err( 'Ei oikeutta tähän asiakkaaseen.' );
	}
	$assignee = (int) $req->get_param( 'assignee_id' ) ?: get_current_user_id();
	if ( ! current_user_can( 'nap_all' ) ) {
		$assignee = get_current_user_id(); // asentaja tekee tehtäviä itselleen
	}
	if ( ! user_can( $assignee, 'nap_access' ) ) {
		return nap_err( 'Valittu henkilö ei ole Nordicin käyttäjä.', 400 );
	}
	$id = nap_create_task( array(
		'title'       => $title,
		'customer_id' => $cid ?: null,
		'project_id'  => (int) $req->get_param( 'project_id' ) ?: null,
		'assignee_id' => $assignee,
		'due_date'    => (string) $req->get_param( 'due_date' ),
	) );
	if ( $cid ) {
		nap_log( $cid, null, 'task', 'Tehtävä: ' . $title . ' (' . nap_user_name( $assignee ) . ')' );
	}
	return nap_task_out( nap_get_task( $id ) );
}

function nap_api_update_task( WP_REST_Request $req ) {
	global $wpdb;
	$t = nap_get_task( (int) $req['id'] );
	if ( ! nap_can_see_task( $t ) ) {
		return nap_err( 'Ei oikeutta tähän tehtävään.' );
	}
	if ( null !== $req->get_param( 'done' ) ) {
		$done = rest_sanitize_boolean( $req->get_param( 'done' ) );
		$wpdb->update( nap_table( 'tasks' ), array( 'done_at' => $done ? nap_now() : null ), array( 'id' => (int) $t['id'] ) );
		if ( $done && $t['customer_id'] ) {
			nap_log( $t['customer_id'], $t['project_id'], 'task', 'Tehty: ' . $t['title'] );
		}
	}
	return nap_task_out( nap_get_task( (int) $t['id'] ) );
}
