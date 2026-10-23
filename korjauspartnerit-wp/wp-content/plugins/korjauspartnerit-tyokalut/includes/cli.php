<?php
/**
 * WP-CLI: `wp kp asenna` ajaa asennusapurin.
 */

defined( 'ABSPATH' ) || exit;

WP_CLI::add_command( 'kp asenna', function () {
	foreach ( kp_run_setup() as $line ) {
		WP_CLI::log( $line );
	}
	WP_CLI::success( 'Asennusapuri ajettu.' );
} );
