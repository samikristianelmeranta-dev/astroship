<?php
/**
 * Plugin Name:       Korjauspartnerit – Työkalut
 * Description:       Korjauspartnereiden sivuston toiminnallisuus: henkilökunta ja henkilöhaku, palvelut, referenssit, työpaikat, yhteydenottolomake, sisältöpohjat ja Classic Editor -asetukset. Pidetään erillään teemasta, jotta sisältö säilyy teeman vaihtuessa.
 * Version:           1.0.0
 * Requires at least: 6.2
 * Requires PHP:      7.4
 * Author:            Korjauspartnerit
 * Text Domain:       korjauspartnerit
 * License:           GPL-2.0-or-later
 */

defined( 'ABSPATH' ) || exit;

define( 'KP_TYOKALUT_VERSION', '1.0.0' );
define( 'KP_TYOKALUT_FILE', __FILE__ );
define( 'KP_TYOKALUT_DIR', plugin_dir_path( __FILE__ ) );
define( 'KP_TYOKALUT_URL', plugin_dir_url( __FILE__ ) );

require_once KP_TYOKALUT_DIR . 'includes/helpers.php';
require_once KP_TYOKALUT_DIR . 'includes/post-types.php';
require_once KP_TYOKALUT_DIR . 'includes/meta-boxes.php';
require_once KP_TYOKALUT_DIR . 'includes/classic-editor.php';
require_once KP_TYOKALUT_DIR . 'includes/shortcodes.php';
require_once KP_TYOKALUT_DIR . 'includes/contact-form.php';
require_once KP_TYOKALUT_DIR . 'includes/content-templates.php';
require_once KP_TYOKALUT_DIR . 'includes/redirects.php';
require_once KP_TYOKALUT_DIR . 'includes/admin.php';
require_once KP_TYOKALUT_DIR . 'includes/setup.php';

if ( defined( 'WP_CLI' ) && WP_CLI ) {
	require_once KP_TYOKALUT_DIR . 'includes/cli.php';
}

register_activation_hook( __FILE__, 'kp_activate' );
function kp_activate() {
	kp_register_post_types();
	kp_register_taxonomies();
	kp_seed_content_templates();
	flush_rewrite_rules();
}

register_deactivation_hook( __FILE__, 'flush_rewrite_rules' );

add_action( 'init', function () {
	load_plugin_textdomain( 'korjauspartnerit', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
}, 1 );
