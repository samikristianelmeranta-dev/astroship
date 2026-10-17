<?php
/**
 * Tietokantataulut ja tietojen käsittely.
 *
 * Taulut: asiakkaat, kohteet, tehtävät ja tapahtumat (historia). Kaikki haut
 * tehdään $wpdb->prepare-kyselyillä; oikeudet tarkistetaan rest.php:ssä.
 */

defined( 'ABSPATH' ) || exit;

const NAP_STAGES = array( 'Tarjouspyyntö', 'Mittaus', 'Tarjous lähetetty', 'Hyväksytty', 'Tilaus', 'Asennus', 'Valmis' );

function nap_table( $name ) {
	global $wpdb;
	return $wpdb->prefix . 'nap_' . $name;
}

function nap_install_db() {
	global $wpdb;
	require_once ABSPATH . 'wp-admin/includes/upgrade.php';
	$c = $wpdb->get_charset_collate();

	dbDelta( "CREATE TABLE " . nap_table( 'customers' ) . " (
		id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
		name varchar(190) NOT NULL DEFAULT '',
		email varchar(190) NOT NULL DEFAULT '',
		phone varchar(50) NOT NULL DEFAULT '',
		address varchar(190) NOT NULL DEFAULT '',
		city varchar(100) NOT NULL DEFAULT '',
		source varchar(100) NOT NULL DEFAULT '',
		referred_by bigint(20) unsigned DEFAULT NULL,
		ref_code varchar(20) NOT NULL DEFAULT '',
		owner_id bigint(20) unsigned DEFAULT NULL,
		token_hash char(64) NOT NULL DEFAULT '',
		token_expires datetime DEFAULT NULL,
		session_version int(10) unsigned NOT NULL DEFAULT 1,
		created_at datetime NOT NULL,
		updated_at datetime NOT NULL,
		PRIMARY KEY  (id),
		KEY email (email),
		KEY ref_code (ref_code),
		KEY owner_id (owner_id)
	) $c;" );

	dbDelta( "CREATE TABLE " . nap_table( 'projects' ) . " (
		id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
		customer_id bigint(20) unsigned NOT NULL,
		title varchar(190) NOT NULL DEFAULT '',
		address varchar(190) NOT NULL DEFAULT '',
		stage tinyint(3) unsigned NOT NULL DEFAULT 0,
		value decimal(12,2) NOT NULL DEFAULT 0,
		owner_id bigint(20) unsigned DEFAULT NULL,
		installer_id bigint(20) unsigned DEFAULT NULL,
		details text NULL,
		approval text NULL,
		created_at datetime NOT NULL,
		updated_at datetime NOT NULL,
		PRIMARY KEY  (id),
		KEY customer_id (customer_id),
		KEY stage (stage),
		KEY owner_id (owner_id),
		KEY installer_id (installer_id)
	) $c;" );

	dbDelta( "CREATE TABLE " . nap_table( 'tasks' ) . " (
		id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
		project_id bigint(20) unsigned DEFAULT NULL,
		customer_id bigint(20) unsigned DEFAULT NULL,
		title varchar(255) NOT NULL DEFAULT '',
		assignee_id bigint(20) unsigned DEFAULT NULL,
		due_date date DEFAULT NULL,
		done_at datetime DEFAULT NULL,
		created_by bigint(20) unsigned DEFAULT NULL,
		created_at datetime NOT NULL,
		PRIMARY KEY  (id),
		KEY assignee_id (assignee_id),
		KEY project_id (project_id),
		KEY customer_id (customer_id)
	) $c;" );

	dbDelta( "CREATE TABLE " . nap_table( 'events' ) . " (
		id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
		customer_id bigint(20) unsigned DEFAULT NULL,
		project_id bigint(20) unsigned DEFAULT NULL,
		type varchar(40) NOT NULL DEFAULT '',
		text text NULL,
		user_id bigint(20) unsigned DEFAULT NULL,
		for_customer tinyint(1) NOT NULL DEFAULT 0,
		created_at datetime NOT NULL,
		PRIMARY KEY  (id),
		KEY customer_id (customer_id),
		KEY project_id (project_id)
	) $c;" );

	update_option( 'nap_db_version', NAP_DB_VERSION, false );
}

function nap_now() {
	return current_time( 'mysql' );
}

/* ---------- Asiakkaat ---------- */

function nap_get_customer( $id ) {
	global $wpdb;
	return $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . nap_table( 'customers' ) . ' WHERE id = %d', $id ), ARRAY_A );
}

function nap_find_customer_by_email( $email ) {
	global $wpdb;
	if ( '' === trim( (string) $email ) ) {
		return null;
	}
	return $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . nap_table( 'customers' ) . ' WHERE email = %s ORDER BY id ASC LIMIT 1', strtolower( $email ) ), ARRAY_A );
}

function nap_find_customer_by_ref( $code ) {
	global $wpdb;
	if ( '' === $code ) {
		return null;
	}
	return $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . nap_table( 'customers' ) . ' WHERE ref_code = %s LIMIT 1', strtoupper( $code ) ), ARRAY_A );
}

function nap_new_ref_code() {
	$alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
	do {
		$code = '';
		for ( $i = 0; $i < 6; $i++ ) {
			$code .= $alphabet[ random_int( 0, strlen( $alphabet ) - 1 ) ];
		}
	} while ( nap_find_customer_by_ref( $code ) );
	return $code;
}

function nap_create_customer( $data ) {
	global $wpdb;
	$now = nap_now();
	$row = array(
		'name'        => sanitize_text_field( $data['name'] ?? '' ),
		'email'       => strtolower( sanitize_email( $data['email'] ?? '' ) ),
		'phone'       => sanitize_text_field( $data['phone'] ?? '' ),
		'address'     => sanitize_text_field( $data['address'] ?? '' ),
		'city'        => sanitize_text_field( $data['city'] ?? '' ),
		'source'      => sanitize_text_field( $data['source'] ?? 'Verkkosivu' ),
		'referred_by' => ! empty( $data['referred_by'] ) ? (int) $data['referred_by'] : null,
		'ref_code'    => nap_new_ref_code(),
		'owner_id'    => ! empty( $data['owner_id'] ) ? (int) $data['owner_id'] : null,
		'created_at'  => $now,
		'updated_at'  => $now,
	);
	$wpdb->insert( nap_table( 'customers' ), $row );
	return (int) $wpdb->insert_id;
}

function nap_update_customer( $id, $data ) {
	global $wpdb;
	$allowed = array( 'name', 'email', 'phone', 'address', 'city', 'source', 'owner_id' );
	$row     = array();
	foreach ( $allowed as $k ) {
		if ( array_key_exists( $k, $data ) ) {
			$row[ $k ] = 'email' === $k ? strtolower( sanitize_email( $data[ $k ] ) ) : ( 'owner_id' === $k ? ( $data[ $k ] ? (int) $data[ $k ] : null ) : sanitize_text_field( $data[ $k ] ) );
		}
	}
	if ( ! $row ) {
		return false;
	}
	$row['updated_at'] = nap_now();
	return false !== $wpdb->update( nap_table( 'customers' ), $row, array( 'id' => (int) $id ) );
}

/* ---------- Kohteet ---------- */

function nap_get_project( $id ) {
	global $wpdb;
	return $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . nap_table( 'projects' ) . ' WHERE id = %d', $id ), ARRAY_A );
}

function nap_customer_projects( $customer_id ) {
	global $wpdb;
	return $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM ' . nap_table( 'projects' ) . ' WHERE customer_id = %d ORDER BY id DESC', $customer_id ), ARRAY_A );
}

function nap_create_project( $data ) {
	global $wpdb;
	$now = nap_now();
	$wpdb->insert( nap_table( 'projects' ), array(
		'customer_id'  => (int) $data['customer_id'],
		'title'        => sanitize_text_field( $data['title'] ?? 'Ikkuna- ja oviremontti' ),
		'address'      => sanitize_text_field( $data['address'] ?? '' ),
		'stage'        => max( 0, min( 6, (int) ( $data['stage'] ?? 0 ) ) ),
		'value'        => (float) ( $data['value'] ?? 0 ),
		'owner_id'     => ! empty( $data['owner_id'] ) ? (int) $data['owner_id'] : null,
		'installer_id' => ! empty( $data['installer_id'] ) ? (int) $data['installer_id'] : null,
		'details'      => sanitize_textarea_field( $data['details'] ?? '' ),
		'created_at'   => $now,
		'updated_at'   => $now,
	) );
	return (int) $wpdb->insert_id;
}

function nap_update_project( $id, $data ) {
	global $wpdb;
	$row = array();
	foreach ( array( 'title', 'address', 'details' ) as $k ) {
		if ( array_key_exists( $k, $data ) ) {
			$row[ $k ] = 'details' === $k ? sanitize_textarea_field( $data[ $k ] ) : sanitize_text_field( $data[ $k ] );
		}
	}
	if ( array_key_exists( 'value', $data ) ) {
		$row['value'] = max( 0, (float) $data['value'] );
	}
	if ( array_key_exists( 'stage', $data ) ) {
		$row['stage'] = max( 0, min( 6, (int) $data['stage'] ) );
	}
	foreach ( array( 'owner_id', 'installer_id' ) as $k ) {
		if ( array_key_exists( $k, $data ) ) {
			$row[ $k ] = $data[ $k ] ? (int) $data[ $k ] : null;
		}
	}
	if ( array_key_exists( 'approval', $data ) ) {
		$row['approval'] = $data['approval'] ? wp_json_encode( $data['approval'], JSON_UNESCAPED_UNICODE ) : null;
	}
	if ( ! $row ) {
		return false;
	}
	$row['updated_at'] = nap_now();
	return false !== $wpdb->update( nap_table( 'projects' ), $row, array( 'id' => (int) $id ) );
}

/* ---------- Tehtävät ---------- */

function nap_create_task( $data ) {
	global $wpdb;
	$wpdb->insert( nap_table( 'tasks' ), array(
		'project_id'  => ! empty( $data['project_id'] ) ? (int) $data['project_id'] : null,
		'customer_id' => ! empty( $data['customer_id'] ) ? (int) $data['customer_id'] : null,
		'title'       => sanitize_text_field( $data['title'] ),
		'assignee_id' => ! empty( $data['assignee_id'] ) ? (int) $data['assignee_id'] : null,
		'due_date'    => ! empty( $data['due_date'] ) && preg_match( '/^\d{4}-\d{2}-\d{2}$/', $data['due_date'] ) ? $data['due_date'] : null,
		'created_by'  => get_current_user_id() ?: null,
		'created_at'  => nap_now(),
	) );
	return (int) $wpdb->insert_id;
}

function nap_get_task( $id ) {
	global $wpdb;
	return $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . nap_table( 'tasks' ) . ' WHERE id = %d', $id ), ARRAY_A );
}

/* ---------- Tapahtumat (historia) ---------- */

function nap_log( $customer_id, $project_id, $type, $text, $for_customer = false ) {
	global $wpdb;
	$wpdb->insert( nap_table( 'events' ), array(
		'customer_id'  => $customer_id ? (int) $customer_id : null,
		'project_id'   => $project_id ? (int) $project_id : null,
		'type'         => sanitize_key( $type ),
		'text'         => wp_strip_all_tags( $text ),
		'user_id'      => get_current_user_id() ?: null,
		'for_customer' => $for_customer ? 1 : 0,
		'created_at'   => nap_now(),
	) );
}

function nap_customer_events( $customer_id, $only_customer_visible = false ) {
	global $wpdb;
	$sql = 'SELECT * FROM ' . nap_table( 'events' ) . ' WHERE customer_id = %d' . ( $only_customer_visible ? ' AND for_customer = 1' : '' ) . ' ORDER BY created_at DESC, id DESC LIMIT 200';
	return $wpdb->get_results( $wpdb->prepare( $sql, $customer_id ), ARRAY_A );
}
