<?php
/**
 * Plugin Name:       Nordic Asiakasportaali
 * Description:       Asiakasportaali "Oma kohde" ja Nordicin myynnin ja projektien hallinta (CRM): asiakkaat, kohteet, myyntiputki, tehtävät ja käyttöoikeudet. Vaihe 1.
 * Version:           0.6.1
 * Author:            Nordic Ikkunat & Ovet Oy
 * Requires at least: 6.2
 * Requires PHP:      7.4
 * License:           GPL-2.0-or-later
 * Text Domain:       nordic-asiakasportaali
 */

defined( 'ABSPATH' ) || exit;

define( 'NAP_VERSION', '0.5.1' );
define( 'NAP_DB_VERSION', '4' );
define( 'NAP_FILE', __FILE__ );
define( 'NAP_DIR', __DIR__ );
define( 'NAP_URL', plugin_dir_url( __FILE__ ) );

require_once NAP_DIR . '/includes/db.php';
require_once NAP_DIR . '/includes/roles.php';
require_once NAP_DIR . '/includes/settings.php';
require_once NAP_DIR . '/includes/mail.php';
require_once NAP_DIR . '/includes/customer-auth.php';
require_once NAP_DIR . '/includes/twofa.php';
require_once NAP_DIR . '/includes/intake.php';
require_once NAP_DIR . '/includes/automation.php';
require_once NAP_DIR . '/includes/rest.php';
require_once NAP_DIR . '/includes/routes.php';

register_activation_hook( __FILE__, function () {
	nap_install_db();
	nap_install_roles();
	nap_add_rewrites();
	flush_rewrite_rules();
} );

register_deactivation_hook( __FILE__, function () {
	wp_clear_scheduled_hook( 'nap_hourly' );
	flush_rewrite_rules();
} );

/* Tietokanta päivitetään automaattisesti, kun lisäosan versio vaihtuu. */
add_action( 'plugins_loaded', function () {
	if ( get_option( 'nap_db_version' ) !== NAP_DB_VERSION ) {
		nap_install_db();
		nap_install_roles();
		update_option( 'nap_flush_rewrites', 1, false );
	}
	if ( get_option( 'nap_rw_version' ) !== NAP_VERSION ) { // uudet osoitteet (esim. /crm/sw.js)
		update_option( 'nap_rw_version', NAP_VERSION, false );
		update_option( 'nap_flush_rewrites', 1, false );
	}
} );
add_action( 'init', function () {
	if ( get_option( 'nap_flush_rewrites' ) ) {
		delete_option( 'nap_flush_rewrites' );
		flush_rewrite_rules();
	}
}, 99 );
