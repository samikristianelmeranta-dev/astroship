<?php
/** Nordic CRM:n kirjautumissivu (/crm/). Muuttujat: $error, $login. */
defined( 'ABSPATH' ) || exit;
nocache_headers();
?><!doctype html>
<html lang="fi">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<meta name="robots" content="noindex, nofollow">
<meta name="theme-color" content="#16303B">
<link rel="manifest" href="<?php echo esc_url( home_url( '/crm/manifest.webmanifest' ) ); ?>">
<link rel="apple-touch-icon" href="<?php echo esc_url( NAP_URL . 'assets/icon-192.png' ); ?>">
<title>Kirjaudu · Nordic CRM</title>
<style>
:root{--bg:#16303B;--surface:#fff;--ink:#16303B;--muted:#667A82;--line:#D5DFE3;--pine:#1D5E79;--gold:#FFD866;--err:#B23B2E;--errsoft:#F8E3E0;color-scheme:light}
@media (prefers-color-scheme:dark){:root{--bg:#0A141A;--surface:#13232B;--ink:#E6EEF1;--muted:#8DA1A9;--line:#2A404A;--pine:#6BB8D6;--err:#F08070;--errsoft:#3D1F1B;color-scheme:dark}}
*{box-sizing:border-box}
html,body{min-height:100%}
body{margin:0;background:var(--bg);color:var(--ink);font-family:Inter,system-ui,-apple-system,"Segoe UI",Roboto,sans-serif;font-size:16px;line-height:1.5;-webkit-font-smoothing:antialiased;display:flex;flex-direction:column;align-items:center;justify-content:center;padding:max(24px,env(safe-area-inset-top,0px)) 16px max(24px,env(safe-area-inset-bottom,0px))}
.brand{display:flex;align-items:center;gap:10px;color:#fff;font-weight:700;font-size:20px;margin-bottom:22px;letter-spacing:-.01em}
.brand svg{width:30px;height:30px}.brand b{color:var(--gold)}
.card{width:100%;max-width:380px;background:var(--surface);border-radius:20px;padding:26px 22px 22px;box-shadow:0 10px 40px rgba(0,0,0,.25)}
h1{font-size:21px;margin:0 0 4px;letter-spacing:-.01em}
.sub{color:var(--muted);font-size:14.5px;margin:0 0 18px}
label{display:block;font-weight:600;font-size:14px;margin:0 0 6px}
input[type=text],input[type=password]{width:100%;border:1.5px solid var(--line);border-radius:12px;padding:13px 14px;font:inherit;font-size:16px;background:var(--surface);color:var(--ink);margin-bottom:14px}
input:focus{outline:none;border-color:var(--pine);box-shadow:0 0 0 3px rgba(29,94,121,.18)}
.pw{position:relative}.pw input{padding-right:64px}
.pw button{position:absolute;right:8px;top:8px;border:0;background:none;color:var(--pine);font:inherit;font-size:14px;font-weight:600;padding:6px 8px;cursor:pointer}
.chk{display:flex;align-items:center;gap:10px;font-weight:500;font-size:15px;margin:2px 0 18px;cursor:pointer}.chk input{width:19px;height:19px;accent-color:var(--pine);margin:0}
.btn{width:100%;border:0;border-radius:14px;padding:15px;font:inherit;font-weight:700;font-size:16.5px;background:var(--gold);color:#16303B;cursor:pointer}
.btn:active{transform:scale(.99)}
.err{background:var(--errsoft);color:var(--err);border-radius:12px;padding:11px 13px;font-size:14.5px;margin-bottom:16px}
.links{text-align:center;margin-top:16px;font-size:14px}.links a{color:var(--muted)}
</style>
</head>
<body>
<div class="brand"><svg viewBox="0 0 24 24" aria-hidden="true"><rect x="12" y="2" width="10" height="10" fill="#FFD866"/><rect x="2.6" y="2.6" width="18.8" height="18.8" fill="none" stroke="#fff" stroke-width="1.8"/><line x1="12" y1="2" x2="12" y2="22" stroke="#fff" stroke-width="1.6"/><line x1="2" y1="12" x2="22" y2="12" stroke="#fff" stroke-width="1.6"/></svg><span>Nordic <b>CRM</b></span></div>
<main class="card">
  <h1>Kirjaudu sisään</h1>
  <p class="sub">Nordic Ikkunat &amp; Ovet Oy · henkilökunta</p>
  <?php if ( $error ) : ?><div class="err" role="alert"><?php echo esc_html( $error ); ?></div><?php endif; ?>
  <form method="post" action="<?php echo esc_url( home_url( '/crm/' ) ); ?>">
    <?php wp_nonce_field( 'nap_crm_login' ); ?>
    <label for="log">Käyttäjänimi tai sähköposti</label>
    <input id="log" name="log" type="text" value="<?php echo esc_attr( $login ); ?>" autocomplete="username" autocapitalize="none" autocorrect="off" spellcheck="false" required <?php echo $login ? '' : 'autofocus'; ?>>
    <label for="pwd">Salasana</label>
    <div class="pw">
      <input id="pwd" name="pwd" type="password" autocomplete="current-password" required <?php echo $login ? 'autofocus' : ''; ?>>
      <button type="button" aria-controls="pwd" onclick="var i=document.getElementById('pwd');i.type=i.type==='password'?'text':'password';this.textContent=i.type==='password'?'Näytä':'Piilota'">Näytä</button>
    </div>
    <label class="chk"><input type="checkbox" name="rememberme" value="forever" checked> Pysy kirjautuneena (<?php echo (int) NAP_REMEMBER_DAYS; ?> päivää)</label>
    <button class="btn" type="submit">Kirjaudu</button>
  </form>
  <p class="links"><a href="<?php echo esc_url( wp_lostpassword_url( home_url( '/crm/' ) ) ); ?>">Unohtuiko salasana?</a></p>
</main>
</body>
</html>
