<?php
/**
 * Plugin Name:       Nordic CRM – sähköpostiautomaatiot
 * Description:       Valmiit FluentCRM-automaatiot Nordic Ikkunat & Ovet -sivustolle: tarjouspyyntö, taloyhtiö, esitteen tilaus, ladattavat oppaat ja energiansäästölaskuri. Luo tagit, viestipohjat ja viestisarjat, ja hoitaa markkinointiluvan.
 * Version:           1.2.1
 * Requires at least: 6.5
 * Requires PHP:      7.4
 * Requires Plugins:  fluent-crm
 * Author:            Nordic Ikkunat & Ovet Oy
 * Text Domain:       nordic-crm
 * License:           GPLv2 or later
 */

if ( ! defined( 'ABSPATH' ) ) exit;

define( 'NORDIC_CRM_VERSION', '1.2.1' );
define( 'NORDIC_CRM_FILE', __FILE__ );
define( 'NORDIC_CRM_DIR', __DIR__ );
define( 'NORDIC_CRM_TRIGGER', 'nordic_crm_lead' );

require_once __DIR__ . '/includes/settings.php';
require_once __DIR__ . '/includes/emails.php';
require_once __DIR__ . '/includes/template.php';
require_once __DIR__ . '/includes/leads.php';
require_once __DIR__ . '/includes/forms.php';
require_once __DIR__ . '/includes/guides.php';
require_once __DIR__ . '/includes/installer.php';
require_once __DIR__ . '/includes/privacy.php';

/**
 * FluentCRM-riippuvaiset osat ladataan vasta, kun FluentCRM on käynnissä.
 */
add_action( 'fluentcrm_loaded', function () {
	require_once __DIR__ . '/includes/trigger.php';
	require_once __DIR__ . '/includes/smartcodes.php';
	new Nordic_CRM_Lead_Trigger();
	nordic_crm_register_smartcodes();
}, 5 );

/**
 * Aktivointi: luodaan tagit, kentät ja automaatiot.
 * Ajetaan vasta seuraavalla latauksella, jolloin FluentCRM on varmasti valmis.
 */
register_activation_hook( __FILE__, function () {
	update_option( 'nordic_crm_needs_install', 1, false );
} );

add_action( 'admin_init', function () {
	// Lisäosan päivitys (zip korvattu): uudet automaatiot ja kentät asennetaan automaattisesti.
	if ( get_option( 'nordic_crm_installed_version' ) && get_option( 'nordic_crm_installed_version' ) !== NORDIC_CRM_VERSION ) {
		update_option( 'nordic_crm_needs_install', 1, false );
	}
	if ( ! get_option( 'nordic_crm_needs_install' ) || ! defined( 'FLUENTCRM' ) ) {
		return;
	}
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	delete_option( 'nordic_crm_needs_install' );
	$result = nordic_crm_install();
	set_transient( 'nordic_crm_install_notice', $result, 300 );
} );

/**
 * Ilmoitus, jos FluentCRM puuttuu.
 */
add_action( 'admin_notices', function () {
	if ( defined( 'FLUENTCRM' ) ) {
		return;
	}
	echo '<div class="notice notice-error"><p><strong>Nordic CRM:</strong> FluentCRM-lisäosa ei ole käytössä. Asenna ja aktivoi FluentCRM ensin.</p></div>';
} );
