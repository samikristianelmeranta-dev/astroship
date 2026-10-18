<?php
/** Asiakkaan Oma kohde -sivu: tarjous ja sen hyväksyntä, kohteen vaiheet, historia ja yhteystiedot. */
defined( 'ABSPATH' ) || exit;
$c        = nap_current_customer();
$projects = $c ? nap_customer_projects( (int) $c['id'] ) : array();
$events   = $c ? nap_customer_events( (int) $c['id'], true ) : array();
$sent     = isset( $_GET['lahetetty'] ); // phpcs:ignore
$bad      = isset( $_GET['virhe'] ); // phpcs:ignore
$omsg     = sanitize_key( $_GET['tarjous'] ?? '' ); // phpcs:ignore
$omsgs    = array(
	'hyvaksytty' => array( 'ok', 'Kiitos! Tarjous on hyväksytty. Lähetimme vahvistuksen sähköpostiisi, ja otamme sinuun yhteyttä tilauksen ja toimituksen aikataulusta.' ),
	'kysytty'    => array( 'ok', 'Kiitos kysymyksestä! Vastaamme sinulle mahdollisimman pian, yleensä saman tai seuraavan arkipäivän aikana.' ),
	'kiitos'     => array( 'ok', 'Kiitos, että kerroit. Jos mielesi muuttuu tai haluat uuden tarjouksen, ota meihin yhteyttä.' ),
	'ehdot'      => array( 'warn', 'Valitse maksutapa ja vahvista, että olet lukenut tarjouksen ehdot.' ),
	'tyhja'      => array( 'warn', 'Kirjoita kysymyksesi ennen lähettämistä.' ),
	'suljettu'   => array( 'warn', 'Tähän tarjoukseen on jo vastattu tai sen voimassaolo on päättynyt. Ota yhteyttä, niin autamme.' ),
	'virhe'      => array( 'warn', 'Toiminto ei onnistunut. Päivitä sivu ja yritä uudelleen, tai ota meihin yhteyttä.' ),
);
function nap_eur( $n ) {
	return number_format( (float) $n, 0, ',', ' ' ) . ' €';
}
function nap_fi_date( $dt ) {
	return $dt ? date_i18n( 'j.n.Y', strtotime( $dt ) ) : '';
}
?><!doctype html>
<html lang="fi">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<meta name="robots" content="noindex, nofollow">
<title>Oma kohde · Ikkunakauppias.fi</title>
<style>
:root{--bg:#F3F6F7;--surface:#fff;--ink:#16303B;--ink2:#41575F;--muted:#64787F;--line:#DCE4E8;--pine:#1D5E79;--pinesoft:#E2EEF3;--gold:#FFD866;--goldsoft:#FDF3D3;--top:#16303B;color-scheme:light}
@media (prefers-color-scheme:dark){:root{--bg:#0D191F;--surface:#14242C;--ink:#E5EEF1;--ink2:#B7C7CE;--muted:#8FA3AC;--line:#253A44;--pine:#62B3D2;--pinesoft:#183746;--goldsoft:#3A311B;--top:#0A141A;color-scheme:dark}}
*{box-sizing:border-box}body{margin:0;background:var(--bg);color:var(--ink);font-family:Inter,system-ui,-apple-system,"Segoe UI",Roboto,sans-serif;font-size:15px;line-height:1.55}
.top{background:var(--top);color:#fff;padding:18px max(18px,env(safe-area-inset-right,0px)) 18px max(18px,env(safe-area-inset-left,0px))}
.top .in,.wrap{max-width:640px;margin:0 auto}
.logo{display:flex;align-items:center;gap:8px;font-weight:700;font-size:16px}.logo b{color:var(--gold)}.logo svg{width:22px;height:22px}
.top h1{font-size:22px;margin:14px 0 2px;letter-spacing:-.01em}.top p{margin:0;color:rgba(255,255,255,.75);font-size:14px}
.wrap{padding:16px max(16px,env(safe-area-inset-right,0px)) 40px max(16px,env(safe-area-inset-left,0px))}
.card{background:var(--surface);border:1px solid var(--line);border-radius:14px;padding:16px;margin-bottom:12px}
h2{font-size:17px;margin:0 0 10px}h3{font-size:15px;margin:0}
.muted{color:var(--muted)}.small{font-size:13px}
.tl{list-style:none;margin:0;padding:0}
.tl li{display:grid;grid-template-columns:26px 1fr;gap:10px;padding-bottom:12px;position:relative}
.tl li::before{content:"";position:absolute;left:12px;top:24px;bottom:0;width:2px;background:var(--line)}.tl li:last-child::before{display:none}
.dot{width:26px;height:26px;border-radius:50%;display:grid;place-items:center;font-size:12.5px;font-weight:700;background:var(--pinesoft);color:var(--pine)}
.done .dot{background:var(--pine);color:var(--surface)}.now .dot{background:var(--gold);color:#16303B}
.now h3::after{content:" · nyt";font-weight:600;color:var(--muted);font-size:12.5px}
.row{display:flex;justify-content:space-between;gap:12px;padding:8px 0;border-bottom:1px solid var(--line)}.row:last-child{border:0}
.hist{list-style:none;margin:0;padding:0}.hist li{padding:8px 0;border-bottom:1px solid var(--line);font-size:14px}.hist li:last-child{border:0}.hist .d{font-size:12px;color:var(--muted)}
label{display:block;font-weight:600;font-size:14px;margin-bottom:6px}
input[type=email],textarea{width:100%;border:1px solid var(--line);border-radius:10px;padding:12px;font-size:16px;background:var(--surface);color:var(--ink)}
.btn{display:block;width:100%;border:0;border-radius:12px;padding:13px;font-weight:700;font-size:15px;cursor:pointer;background:var(--gold);color:#16303B;margin-top:10px}
.btn.o{background:transparent;border:1px solid var(--line);color:var(--ink)}
.ok{background:var(--pinesoft);color:var(--pine);border-radius:10px;padding:10px 12px;font-size:14px;margin-bottom:12px}
.warn{background:var(--goldsoft);border-radius:10px;padding:10px 12px;font-size:14px;margin-bottom:12px}
.hp{position:absolute;left:-9999px}
.offer{border:2px solid var(--gold)}.price{font-size:28px;font-weight:800;letter-spacing:-.01em;margin:4px 0 2px}
.terms{background:var(--goldsoft);border-radius:10px;padding:10px 12px;font-size:13.5px;margin:10px 0}
.pre{white-space:pre-line}.opt{display:flex;gap:10px;align-items:flex-start;padding:10px 12px;border:1px solid var(--line);border-radius:10px;margin-bottom:8px;font-weight:600;cursor:pointer}
.opt input{margin-top:4px}.opt small{display:block;font-weight:400;color:var(--muted)}
.chk{display:flex;gap:10px;align-items:flex-start;font-weight:500;font-size:14px;margin:12px 0 0}.chk input{margin-top:4px}
details{margin-top:10px;border-top:1px solid var(--line);padding-top:10px}summary{cursor:pointer;font-weight:600;color:var(--pine)}
a.btn{text-align:center;text-decoration:none}textarea{font-family:inherit;margin-top:6px}
</style>
</head>
<body>
<header class="top"><div class="in">
  <div class="logo"><svg viewBox="0 0 24 24" aria-hidden="true"><rect x="12" y="2" width="10" height="10" fill="#FFD866"/><rect x="2.6" y="2.6" width="18.8" height="18.8" fill="none" stroke="#fff" stroke-width="1.8"/><line x1="12" y1="2" x2="12" y2="22" stroke="#fff" stroke-width="1.6"/><line x1="2" y1="12" x2="22" y2="12" stroke="#fff" stroke-width="1.6"/></svg><span>Ikkunakauppias<b>.fi</b> · Oma kohde</span></div>
  <?php if ( $c ) : ?>
    <h1>Hei <?php echo esc_html( nap_first_name( $c['name'] ) ); ?></h1>
    <p><?php echo esc_html( trim( $c['address'] . ', ' . $c['city'], ', ' ) ); ?></p>
  <?php else : ?>
    <h1>Oma kohde</h1><p>Seuraa ikkuna- ja oviremonttisi vaiheita.</p>
  <?php endif; ?>
</div></header>
<main class="wrap">
<?php if ( ! $c ) : ?>
  <div class="card">
    <?php if ( $sent ) : ?><div class="ok">Jos osoitteella on kohde meillä, lähetimme sinulle kirjautumislinkin. Tarkista myös roskapostikansio.</div><?php endif; ?>
    <?php if ( $bad ) : ?><div class="warn">Kirjautumislinkki on vanhentunut tai jo käytetty. Pyydä uusi linkki alta.</div><?php endif; ?>
    <h2>Kirjaudu sähköpostilla</h2>
    <p class="small muted" style="margin-top:0">Salasanaa ei tarvita. Lähetämme sähköpostiisi linkin, jolla pääset kohteeseesi.</p>
    <form method="post">
      <?php wp_nonce_field( 'nap_link' ); ?>
      <input type="hidden" name="nap_action" value="link">
      <input class="hp" type="text" name="nap_hp" tabindex="-1" autocomplete="off" aria-hidden="true">
      <label for="email">Sähköpostiosoite</label>
      <input id="email" type="email" name="email" required autocomplete="email">
      <button class="btn" type="submit">Lähetä kirjautumislinkki</button>
    </form>
  </div>
<?php else : ?>
  <?php if ( isset( $omsgs[ $omsg ] ) ) : ?><div class="<?php echo esc_attr( $omsgs[ $omsg ][0] ); ?>" id="tarjous"><?php echo esc_html( $omsgs[ $omsg ][1] ); ?></div><?php endif; ?>
  <?php foreach ( $projects as $p ) : $st = (int) $p['stage']; $offer = nap_get_offer( $p ); $ap = ! empty( $p['approval'] ) ? json_decode( $p['approval'], true ) : null; ?>
  <?php if ( $offer ) : $is_pdf = 'pdf' === ( $offer['mode'] ?? '' ); $open = ! $ap && nap_offer_open( $offer ); ?>
  <div class="card offer" id="tarjous-<?php echo (int) $p['id']; ?>">
    <h2><?php echo $offer['version'] > 1 ? 'Päivitetty tarjous' : 'Tarjous'; ?></h2>
    <p class="small muted" style="margin:-6px 0 8px"><?php echo esc_html( $p['title'] ); ?><?php echo $is_pdf ? '' : ' · ' . esc_html( nap_eur( $offer['sum'] ) ); ?></p>
    <?php if ( $ap ) : ?><div class="ok" style="margin:0">✓ Hyväksytty <?php echo esc_html( nap_fi_date( $ap['at'] ) ); ?> · <?php echo esc_html( $ap['pay_label'] ); ?></div>
    <?php elseif ( 'declined' === $offer['status'] ) : ?><div class="warn" style="margin:0">Kerroit, ettei tarjous ole ajankohtainen.</div>
    <?php elseif ( nap_offer_expired( $offer ) ) : ?><div class="warn" style="margin:0">Voimassaolo päättyi <?php echo esc_html( nap_fi_date( $offer['valid_until'] ) ); ?>.</div>
    <?php else : ?><p class="small" style="margin:0">Voimassa <?php echo esc_html( nap_fi_date( $offer['valid_until'] ) ); ?> asti.</p><?php endif; ?>
    <a class="btn" href="<?php echo esc_url( add_query_arg( 'nayta', (int) $p['id'], home_url( '/oma-kohde/' ) ) ); ?>"><?php echo $open ? 'Avaa ja hyväksy tarjous' : 'Avaa tarjous'; ?></a>
  </div>
  <?php endif; ?>
  <div class="card">
    <h2><?php echo esc_html( $p['title'] ); ?></h2>
    <p class="small muted" style="margin-top:-6px"><?php echo esc_html( $p['address'] ); ?><?php if ( 'asennus' !== nap_delivery( $p ) ) echo ' · ' . esc_html( nap_deliveries()[ nap_delivery( $p ) ] ); ?></p>
    <?php
    global $wpdb;
    $upcoming = $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM ' . nap_table( 'visits' ) . " WHERE project_id = %d AND status = 'planned' AND end_at >= %s ORDER BY start_at ASC LIMIT 5", $p['id'], current_time( 'mysql' ) ), ARRAY_A ); // phpcs:ignore
    if ( $upcoming ) : ?>
    <div class="ok" style="margin:6px 0 14px">
      <?php foreach ( $upcoming as $uv ) : ?><div><b><?php echo esc_html( nap_visit_label( $uv ) ); ?>:</b> <?php echo esc_html( nap_visit_when( $uv, true ) ); ?><?php echo $uv['installer_id'] ? ' · ' . esc_html( nap_user_name( $uv['installer_id'] ) ) : ''; ?></div><?php endforeach; ?>
    </div>
    <?php endif; ?>
    <ul class="tl">
      <?php foreach ( nap_stage_names( nap_delivery( $p ) ) as $i => $name ) : ?>
        <li class="<?php echo $i < $st || 6 === $st ? 'done' : ( $i === $st ? 'now' : '' ); ?>"><span class="dot"><?php echo $i < $st || 6 === $st ? '✓' : (int) ( $i + 1 ); ?></span><div><h3><?php echo esc_html( $name ); ?></h3></div></li>
      <?php endforeach; ?>
    </ul>
  </div>
  <?php endforeach; ?>
  <?php if ( ! $projects ) : ?><div class="card"><p class="muted" style="margin:0">Kohteesi tiedot tulevat tähän, kun olemme käsitelleet tarjouspyyntösi.</p></div><?php endif; ?>
  <div class="card">
    <h2>Tapahtumat</h2>
    <?php if ( $events ) : ?><ul class="hist"><?php foreach ( $events as $e ) : ?><li><div class="d"><?php echo esc_html( nap_fi_date( $e['created_at'] ) ); ?></div><?php echo esc_html( $e['text'] ); ?></li><?php endforeach; ?></ul>
    <?php else : ?><p class="muted" style="margin:0">Ei vielä tapahtumia.</p><?php endif; ?>
  </div>
  <div class="card">
    <h2>Yhteystiedot</h2>
    <div class="row"><span>Myynti</span><b>045 7832 5070</b></div>
    <div class="row"><span>Asennus</span><b>045 7830 4746</b></div>
    <div class="row"><span>Sähköposti</span><b>info@ikkunakauppias.fi</b></div>
  </div>
  <form method="post"><?php wp_nonce_field( 'nap_logout' ); ?><input type="hidden" name="nap_action" value="logout"><button class="btn o" type="submit">Kirjaudu ulos</button></form>
<?php endif; ?>
</main>
</body>
</html>
