<?php
/** Nordicin CRM-sovellus: kevyt HTML-kuori, sisältö piirretään assets/crm.js:llä REST-rajapinnasta. */
defined( 'ABSPATH' ) || exit;
$nap_cfg = array(
	'root'   => esc_url_raw( rest_url( 'nap/v1/' ) ),
	'nonce'  => wp_create_nonce( 'wp_rest' ),
	'logout' => wp_logout_url( home_url( '/crm/' ) ),
	'admin'  => current_user_can( 'nap_users' ) ? admin_url( 'options-general.php?page=nordic-asiakasportaali' ) : '',
	'users'  => current_user_can( 'create_users' ) ? admin_url( 'user-new.php' ) : '',
);
?><!doctype html>
<html lang="fi">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<meta name="robots" content="noindex, nofollow">
<title>Nordic CRM</title>
<link rel="manifest" href="<?php echo esc_url( home_url( '/crm/manifest.webmanifest' ) ); ?>">
<meta name="theme-color" content="#16303B">
<link rel="apple-touch-icon" href="<?php echo esc_url( NAP_URL . 'assets/icon-192.png' ); ?>">
<link rel="stylesheet" href="<?php echo esc_url( NAP_URL . 'assets/crm.css?v=' . NAP_VERSION ); ?>">
</head>
<body>
<div class="app">
  <aside class="side">
    <div class="brand"><svg viewBox="0 0 24 24" aria-hidden="true"><rect x="12" y="2" width="10" height="10" fill="#FFD866"/><rect x="2.6" y="2.6" width="18.8" height="18.8" fill="none" stroke="#fff" stroke-width="1.8"/><line x1="12" y1="2" x2="12" y2="22" stroke="#fff" stroke-width="1.6"/><line x1="2" y1="12" x2="22" y2="12" stroke="#fff" stroke-width="1.6"/></svg><span>Nordic<b> CRM</b></span></div>
    <nav class="nav" id="nav" aria-label="Päävalikko"></nav>
    <div class="who" id="who"></div>
  </aside>
  <main id="main" aria-live="polite"><p class="muted">Ladataan…</p></main>
</div>
<div class="toast" id="toast" role="status"></div>
<script>window.NAP = <?php echo wp_json_encode( $nap_cfg ); ?>;</script>
<script src="<?php echo esc_url( NAP_URL . 'assets/crm.js?v=' . NAP_VERSION ); ?>"></script>
<script>if ('serviceWorker' in navigator) navigator.serviceWorker.register(<?php echo wp_json_encode( home_url( '/crm/sw.js' ) ); ?>, { scope: <?php echo wp_json_encode( wp_parse_url( home_url( '/crm/' ), PHP_URL_PATH ) ); ?> }).catch(function () {});</script>
</body>
</html>
