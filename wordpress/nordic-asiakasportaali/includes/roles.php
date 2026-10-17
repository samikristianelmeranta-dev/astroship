<?php
/**
 * Käyttäjäroolit ja oikeudet.
 *
 *  nordic_osakas   – näkee ja muokkaa kaikkea, raportit ja käyttäjät
 *  nordic_asentaja – näkee vain kohteet, joissa on vastuumyyjä tai asentaja
 * WordPressin ylläpitäjät saavat osakkaan oikeudet.
 */

defined( 'ABSPATH' ) || exit;

function nap_install_roles() {
	add_role( 'nordic_osakas', 'Nordic: osakas', array( 'read' => true, 'nap_access' => true, 'nap_all' => true, 'nap_reports' => true, 'nap_users' => true ) );
	add_role( 'nordic_asentaja', 'Nordic: asentaja', array( 'read' => true, 'nap_access' => true ) );
	$admin = get_role( 'administrator' );
	if ( $admin ) {
		foreach ( array( 'nap_access', 'nap_all', 'nap_reports', 'nap_users' ) as $cap ) {
			$admin->add_cap( $cap );
		}
	}
}

/** Nordicin henkilökunta (osakkaat, asentajat, ylläpitäjät). */
function nap_staff_users() {
	$users = get_users( array( 'capability' => 'nap_access', 'orderby' => 'display_name', 'fields' => array( 'ID', 'display_name' ) ) );
	$out   = array();
	foreach ( $users as $u ) {
		$out[] = array(
			'id'    => (int) $u->ID,
			'name'  => $u->display_name,
			'role'  => user_can( $u->ID, 'nap_all' ) ? 'osakas' : 'asentaja',
			'ini'   => nap_initials( $u->display_name ),
			'twofa' => nap_user_has_2fa( $u->ID ),
		);
	}
	return $out;
}

function nap_initials( $name ) {
	$parts = preg_split( '/\s+/', trim( $name ) );
	$ini   = '';
	foreach ( array_slice( $parts, 0, 2 ) as $p ) {
		$ini .= mb_strtoupper( mb_substr( $p, 0, 1 ) );
	}
	return $ini ?: '?';
}

/** Kaksivaiheinen tunnistautuminen: tunnistaa Two Factor -lisäosan ja Wordfence Login Securityn. */
function nap_user_has_2fa( $user_id ) {
	if ( class_exists( 'Two_Factor_Core' ) && method_exists( 'Two_Factor_Core', 'is_user_using_two_factor' ) ) {
		return (bool) Two_Factor_Core::is_user_using_two_factor( $user_id );
	}
	if ( class_exists( '\WordfenceLS\Controller_Users' ) ) {
		try {
			return (bool) \WordfenceLS\Controller_Users::shared()->has_2fa_active( new WP_User( $user_id ) );
		} catch ( Throwable $e ) {
			return null;
		}
	}
	return null; // ei tiedossa: lisäosaa ei ole asennettu
}

/** Saako käyttäjä nähdä kohteen? */
function nap_can_see_project( $project, $user_id = 0 ) {
	$user_id = $user_id ?: get_current_user_id();
	if ( ! $project || ! user_can( $user_id, 'nap_access' ) ) {
		return false;
	}
	if ( user_can( $user_id, 'nap_all' ) ) {
		return true;
	}
	return (int) $project['owner_id'] === $user_id || (int) $project['installer_id'] === $user_id;
}

/** Saako käyttäjä nähdä asiakkaan? Asentaja näkee asiakkaan, jos jokin asiakkaan kohteista on hänen. */
function nap_can_see_customer( $customer, $user_id = 0 ) {
	$user_id = $user_id ?: get_current_user_id();
	if ( ! $customer || ! user_can( $user_id, 'nap_access' ) ) {
		return false;
	}
	if ( user_can( $user_id, 'nap_all' ) || (int) $customer['owner_id'] === $user_id ) {
		return true;
	}
	foreach ( nap_customer_projects( (int) $customer['id'] ) as $p ) {
		if ( nap_can_see_project( $p, $user_id ) ) {
			return true;
		}
	}
	return false;
}
