<?php
/**
 * Chat-ikkuna sivuston alareunaan.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

add_action( 'wp_enqueue_scripts', function () {
	if ( ! nordic_chat_is_ready() || is_admin() ) {
		return;
	}
	$s = nordic_chat_settings();
	wp_enqueue_style( 'nordic-chat', plugins_url( 'assets/chat.css', NORDIC_CHAT_FILE ), array(), NORDIC_CHAT_VERSION );
	wp_enqueue_script( 'nordic-chat', plugins_url( 'assets/chat.js', NORDIC_CHAT_FILE ), array(), NORDIC_CHAT_VERSION, array(
		'strategy'  => 'defer',
		'in_footer' => true,
	) );
	wp_localize_script( 'nordic-chat', 'NordicChat', array(
		'endpoint' => esc_url_raw( rest_url( 'nordic-chat/v1/viesti' ) ),
		'greeting' => $s['greeting'],
		'email'    => $s['contact_email'],
		'host'     => wp_parse_url( home_url(), PHP_URL_HOST ),
		'privacy'  => get_privacy_policy_url(),
		'chips'    => apply_filters( 'nordic_chat_quick_questions', array(
			'Paljonko ikkunaremontti maksaa?',
			'Kolmi- vai nelilasinen ikkuna?',
			'Mitä ulko-ovimalleja teillä on?',
			'Haluan tarjouksen',
		) ),
	) );
} );
