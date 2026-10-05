<?php
/**
 * Plugin Name:       Nordic Chat – tekoälyavustaja
 * Description:       Kevyt tekoälychat Nordic Ikkunat & Ovet -sivustolle. Vastaa palveluita ja tuotteita koskeviin kysymyksiin sivuston sisällön pohjalta ja välittää yhteydenotot sähköpostiin. Käyttää Anthropicin Claude-mallia.
 * Version:           1.0.0
 * Requires at least: 6.5
 * Requires PHP:      8.1
 * Author:            Nordic Ikkunat & Ovet Oy
 * Text Domain:       nordic-chat
 * License:           GPLv2 or later
 */

if ( ! defined( 'ABSPATH' ) ) exit;

define( 'NORDIC_CHAT_VERSION', '1.0.0' );
define( 'NORDIC_CHAT_FILE', __FILE__ );
define( 'NORDIC_CHAT_DIR', __DIR__ );

require_once __DIR__ . '/includes/settings.php';
require_once __DIR__ . '/includes/knowledge.php';
require_once __DIR__ . '/includes/tools.php';
require_once __DIR__ . '/includes/conversation.php';
require_once __DIR__ . '/includes/rest.php';
require_once __DIR__ . '/includes/widget.php';
