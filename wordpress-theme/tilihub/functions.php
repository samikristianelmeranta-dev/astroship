<?php
/**
 * TiliHub-teeman toiminnot.
 *
 * @package TiliHub
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'TILIHUB_VERSION', '1.0.0' );

/**
 * Teeman perusasetukset.
 */
function tilihub_setup() {
	add_theme_support( 'wp-block-styles' );
	add_theme_support( 'editor-styles' );
	add_theme_support( 'responsive-embeds' );
	add_theme_support( 'post-thumbnails' );
	add_editor_style( 'assets/css/theme.css' );
}
add_action( 'after_setup_theme', 'tilihub_setup' );

/**
 * Tyylit.
 */
function tilihub_enqueue() {
	$path = get_theme_file_path( 'assets/css/theme.css' );
	wp_enqueue_style(
		'tilihub',
		get_theme_file_uri( 'assets/css/theme.css' ),
		array(),
		file_exists( $path ) ? (string) filemtime( $path ) : TILIHUB_VERSION
	);
}
add_action( 'wp_enqueue_scripts', 'tilihub_enqueue' );

/**
 * Oma lohkomallien kategoria.
 */
function tilihub_pattern_categories() {
	register_block_pattern_category( 'tilihub', array( 'label' => __( 'TiliHub', 'tilihub' ) ) );
}
add_action( 'init', 'tilihub_pattern_categories' );

require_once get_theme_file_path( 'inc/legacy-content.php' );
require_once get_theme_file_path( 'inc/shortcodes.php' );
