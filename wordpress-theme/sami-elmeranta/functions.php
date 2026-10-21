<?php
/**
 * Sami Elmeranta – Liike Nyt -teeman toiminnot.
 *
 * @package sami-elmeranta
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'SE_VERSION', '1.3.1' );
define( 'SE_DIR', get_template_directory() );

require SE_DIR . '/inc/helpers.php';
require SE_DIR . '/inc/template-tags.php';
require SE_DIR . '/inc/setup.php';
require SE_DIR . '/inc/customizer.php';
require SE_DIR . '/inc/security.php';
require SE_DIR . '/inc/seo.php';
require SE_DIR . '/inc/contact.php';
