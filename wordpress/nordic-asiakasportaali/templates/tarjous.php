<?php
/**
 * Tarjoussivu asiakkaalle: avautuu suoraan sähköpostin linkistä ilman kirjautumista (tai Oma kohde -sivulta).
 * Muuttujat: $c (asiakas), $p (kohde), $base (sivun osoite).
 */
defined( 'ABSPATH' ) || exit;

$offer    = nap_get_offer( $p );
$ap       = ! empty( $p['approval'] ) ? json_decode( $p['approval'], true ) : null;
$is_pdf   = 'pdf' === ( $offer['mode'] ?? '' );
$delivery = nap_delivery( $p );
$expired  = ! $ap && nap_offer_expired( $offer );
$open     = ! $ap && nap_offer_open( $offer );
$n        = nap_customer_nonce( $c, 'offer' . $p['id'] );
$pdf_url  = add_query_arg( 'pdf', 1, $base );
$mterms   = 'omat_mitat' === $delivery ? trim( (string) nap_settings()['measure_terms'] ) : '';
$cterms   = trim( (string) nap_settings()['cancel_terms'] );
$days     = $offer['valid_until'] ? (int) floor( ( strtotime( $offer['valid_until'] ) - strtotime( current_time( 'Y-m-d' ) ) ) / DAY_IN_SECONDS ) : null;
$fmt      = function ( $d ) {
	return $d ? date_i18n( 'j.n.Y', strtotime( $d ) ) : '';
};
$eur      = function ( $v ) {
	return number_format( (float) $v, 0, ',', ' ' ) . ' €';
};
$steps    = 'asennus' === $delivery
	? array( 'Tilaamme tuotteet Skaalan tehtaalta.', 'Sovimme kanssasi asennuspäivän.', 'Asennamme ja käymme työn läpi kanssasi.' )
	: array( 'Tilaamme tuotteet Skaalan tehtaalta.', 'Sovimme kanssasi toimituksen tai noudon.', 'Toimitamme tuotteet.' );
$tila     = sanitize_key( $_GET['tila'] ?? '' ); // phpcs:ignore
$notes    = array(
	'kysytty' => array( 'ok', 'Kiitos kysymyksestä! Vastaamme mahdollisimman pian, yleensä saman tai seuraavan arkipäivän aikana.' ),
	'ehdot'   => array( 'warn', 'Valitse maksutapa ja vahvista, että olet lukenut tarjouksen ehdot.' ),
	'tyhja'   => array( 'warn', 'Kirjoita kysymyksesi ennen lähettämistä.' ),
	'suljettu' => array( 'warn', 'Tähän tarjoukseen on jo vastattu tai sen voimassaolo on päättynyt.' ),
	'virhe'   => array( 'warn', 'Toiminto ei onnistunut. Päivitä sivu ja yritä uudelleen, tai soita meille.' ),
);
$hidden   = '<input type="hidden" name="project_id" value="' . (int) $p['id'] . '"><input type="hidden" name="nap_n" value="' . esc_attr( $n ) . '">';
?><!doctype html>
<html lang="fi">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<meta name="robots" content="noindex, nofollow">
<meta name="theme-color" content="#16303B">
<title>Tarjous · Nordic Ikkunat &amp; Ovet</title>
<style>
:root{--bg:#F1F5F6;--surface:#fff;--ink:#16303B;--ink2:#3F5660;--muted:#667A82;--line:#DDE5E8;--pine:#1D5E79;--pinesoft:#E3EEF3;--gold:#FFD866;--goldink:#16303B;--goldsoft:#FDF4D6;--good:#2C7A4E;--goodsoft:#E4F2EA;--top:#16303B;--shadow:0 1px 2px rgba(22,48,59,.06),0 6px 24px rgba(22,48,59,.06);color-scheme:light}
@media (prefers-color-scheme:dark){:root{--bg:#0C171D;--surface:#13232B;--ink:#E6EEF1;--ink2:#BFCDD3;--muted:#8DA1A9;--line:#253A44;--pine:#6BB8D6;--pinesoft:#173745;--goldsoft:#352D18;--good:#6BCB94;--goodsoft:#163326;--top:#0A141A;--shadow:none;color-scheme:dark}}
*{box-sizing:border-box}
body{margin:0;background:var(--bg);color:var(--ink);font-family:Inter,system-ui,-apple-system,"Segoe UI",Roboto,sans-serif;font-size:16px;line-height:1.55;-webkit-font-smoothing:antialiased}
a{color:var(--pine)}
.top{background:var(--top);color:#fff;padding:14px max(18px,env(safe-area-inset-right,0px)) 64px max(18px,env(safe-area-inset-left,0px))}
.in{max-width:560px;margin:0 auto}
.bar{display:flex;justify-content:space-between;align-items:center;gap:12px}
.logo{display:flex;align-items:center;gap:8px;font-weight:700;font-size:15.5px;color:#fff;text-decoration:none}.logo b{color:var(--gold)}.logo svg{width:22px;height:22px}
.bar a.lnk{color:rgba(255,255,255,.8);font-size:14px;text-decoration:none;border:1px solid rgba(255,255,255,.25);border-radius:999px;padding:5px 12px}
.eyebrow{margin:26px 0 4px;font-size:12.5px;font-weight:700;letter-spacing:.12em;text-transform:uppercase;color:var(--gold)}
h1{font-size:27px;line-height:1.2;margin:0;letter-spacing:-.02em;text-wrap:balance}
.sub{margin:6px 0 0;color:rgba(255,255,255,.75);font-size:15px}
main{padding:0 max(14px,env(safe-area-inset-right,0px)) 40px max(14px,env(safe-area-inset-left,0px));margin-top:-44px}
.card{background:var(--surface);border:1px solid var(--line);border-radius:18px;padding:20px;margin-bottom:14px;box-shadow:var(--shadow)}
.card h2{font-size:18px;margin:0 0 4px;letter-spacing:-.01em}
.muted{color:var(--muted)}.small{font-size:14px}
.chip{display:inline-flex;align-items:center;gap:6px;border-radius:999px;padding:4px 11px;font-size:13.5px;font-weight:700;background:var(--goldsoft);color:var(--ink)}
.chip.good{background:var(--goodsoft);color:var(--good)}.chip.off{background:var(--pinesoft);color:var(--ink2)}
.chip i{width:7px;height:7px;border-radius:50%;background:currentColor;display:inline-block}
.doc{display:flex;gap:14px;align-items:center;margin:16px 0 14px}
.doc .ic{flex:none;width:52px;height:62px;border-radius:9px;background:var(--pinesoft);color:var(--pine);display:grid;place-items:center;position:relative}
.doc .ic svg{width:26px;height:26px}.doc .ic span{position:absolute;bottom:7px;font-size:10px;font-weight:800;letter-spacing:.06em}
.doc b{display:block;font-size:16.5px}
.price{font-size:38px;font-weight:800;letter-spacing:-.03em;line-height:1.1;margin:14px 0 2px}
.btn{display:flex;align-items:center;justify-content:center;gap:8px;width:100%;border:0;border-radius:14px;padding:15px 18px;font:inherit;font-weight:700;font-size:16.5px;cursor:pointer;text-decoration:none;background:var(--gold);color:var(--goldink);transition:transform .08s ease,filter .15s ease}
.btn:hover{filter:brightness(.97)}.btn:active{transform:scale(.99)}
.btn.o{background:transparent;color:var(--ink);border:1.5px solid var(--line)}
.btn svg{width:19px;height:19px}
.facts{margin:16px 0 0;padding:0;list-style:none;border-top:1px solid var(--line)}
.facts li{display:flex;justify-content:space-between;gap:14px;padding:11px 0;border-bottom:1px solid var(--line);font-size:15px}.facts li:last-child{border:0;padding-bottom:0}
.facts span{color:var(--muted)}.facts b{text-align:right}
.pre{white-space:pre-line}
.step{font-size:13px;font-weight:700;letter-spacing:.04em;text-transform:uppercase;color:var(--muted);margin:18px 0 8px}
.opt{display:flex;gap:12px;align-items:flex-start;padding:14px;border:1.5px solid var(--line);border-radius:14px;margin-bottom:10px;cursor:pointer;transition:border-color .15s ease,background .15s ease}
.opt:has(input:checked){border-color:var(--pine);background:var(--pinesoft)}
.opt input{margin:3px 0 0;width:19px;height:19px;accent-color:var(--pine);flex:none}
.opt b{display:block}.opt small{display:block;color:var(--muted);font-size:14px}
.chk{display:flex;gap:12px;align-items:flex-start;font-size:15px;cursor:pointer}.chk input{margin:3px 0 0;width:19px;height:19px;accent-color:var(--pine);flex:none}
details.terms{margin:10px 0 16px;border-radius:12px;background:var(--goldsoft);padding:11px 14px;font-size:14px}
details.terms summary{cursor:pointer;font-weight:700}details.terms p{margin:8px 0 0}
.steps{list-style:none;margin:10px 0 0;padding:0;counter-reset:s}
.steps li{counter-increment:s;display:grid;grid-template-columns:28px 1fr;gap:10px;align-items:start;padding:5px 0;font-size:15px}
.steps li::before{content:counter(s);width:28px;height:28px;border-radius:50%;background:var(--pinesoft);color:var(--pine);display:grid;place-items:center;font-weight:800;font-size:13px}
.note{border-radius:14px;padding:13px 16px;margin-bottom:14px;font-size:15px}
.note.ok{background:var(--goodsoft);color:var(--good)}.note.warn{background:var(--goldsoft)}
.done{text-align:center;padding:28px 20px}
.done .ck{width:64px;height:64px;border-radius:50%;background:var(--goodsoft);color:var(--good);display:grid;place-items:center;margin:0 auto 12px}.done .ck svg{width:32px;height:32px}
.done .steps{text-align:left;max-width:360px;margin:16px auto 0}
.row2{display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-top:14px}.row2 .btn{font-size:15.5px;padding:13px 8px;white-space:nowrap}
details.ask summary{list-style:none}details.ask summary::-webkit-details-marker{display:none}
textarea{width:100%;border:1.5px solid var(--line);border-radius:12px;padding:12px;font:inherit;font-size:16px;background:var(--surface);color:var(--ink);margin:12px 0 10px;resize:vertical}
.foot{text-align:center;color:var(--muted);font-size:13px;margin-top:22px}
.decline{text-align:center;margin-top:4px}.decline summary{cursor:pointer;color:var(--muted);font-size:14px;text-decoration:underline}
:focus-visible{outline:3px solid var(--pine);outline-offset:2px}
@media (max-width:380px){h1{font-size:24px}.row2{grid-template-columns:1fr}.price{font-size:34px}}
</style>
</head>
<body>
<header class="top"><div class="in">
  <div class="bar">
    <a class="logo" href="<?php echo esc_url( home_url( '/' ) ); ?>"><svg viewBox="0 0 24 24" aria-hidden="true"><rect x="12" y="2" width="10" height="10" fill="#FFD866"/><rect x="2.6" y="2.6" width="18.8" height="18.8" fill="none" stroke="#fff" stroke-width="1.8"/><line x1="12" y1="2" x2="12" y2="22" stroke="#fff" stroke-width="1.6"/><line x1="2" y1="12" x2="22" y2="12" stroke="#fff" stroke-width="1.6"/></svg><span>Ikkunakauppias<b>.fi</b></span></a>
    <a class="lnk" href="<?php echo esc_url( home_url( '/oma-kohde/' ) ); ?>">Oma kohde</a>
  </div>
  <p class="eyebrow"><?php echo $offer['version'] > 1 ? 'Päivitetty tarjous' : 'Tarjous'; ?></p>
  <h1>Hei <?php echo esc_html( nap_first_name( $c['name'] ) ); ?>, tässä tarjouksesi</h1>
  <p class="sub"><?php echo esc_html( $p['address'] ?: $p['title'] ); ?></p>
</div></header>

<main><div class="in">
  <?php if ( isset( $notes[ $tila ] ) ) : ?><div class="note <?php echo esc_attr( $notes[ $tila ][0] ); ?>" role="status"><?php echo esc_html( $notes[ $tila ][1] ); ?></div><?php endif; ?>

  <?php if ( $ap ) : ?>
  <section class="card done" aria-labelledby="ok-h">
    <div class="ck"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12.5l4.5 4.5L19 7.5"/></svg></div>
    <h2 id="ok-h">Kiitos, tarjous on hyväksytty</h2>
    <p class="muted small" style="margin:4px 0 0">Hyväksytty <?php echo esc_html( $fmt( $ap['at'] ) ); ?> · <?php echo esc_html( $ap['pay_label'] ); ?><?php echo empty( $ap['price_in_pdf'] ) && $ap['value'] > 0 ? ' · ' . esc_html( $eur( $ap['value'] ) ) : ''; ?></p>
    <p class="small" style="margin:12px 0 0">Lähetimme vahvistuksen sähköpostiisi. Seuraavaksi:</p>
    <ol class="steps"><?php foreach ( $steps as $s ) : ?><li><span><?php echo esc_html( $s ); ?></span></li><?php endforeach; ?></ol>
  </section>
  <?php endif; ?>

  <section class="card" aria-labelledby="t-h">
    <div style="display:flex;justify-content:space-between;align-items:center;gap:10px;flex-wrap:wrap">
      <h2 id="t-h"><?php echo esc_html( $p['title'] ); ?></h2>
      <?php if ( $ap ) : ?><span class="chip good"><i></i>Hyväksytty</span>
      <?php elseif ( 'declined' === $offer['status'] ) : ?><span class="chip off">Ei ajankohtainen</span>
      <?php elseif ( $expired ) : ?><span class="chip off">Voimassaolo päättynyt</span>
      <?php elseif ( null !== $days ) : ?><span class="chip"><i></i><?php echo 0 === $days ? 'Voimassa tänään' : ( 1 === $days ? 'Voimassa vielä huomenna' : 'Voimassa vielä ' . (int) $days . ' päivää' ); ?></span><?php endif; ?>
    </div>

    <?php if ( $is_pdf ) : ?>
      <div class="doc">
        <div class="ic" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M14 3H7a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V8z"/><path d="M14 3v5h5M9 13h6M9 17h4"/></svg><span>PDF</span></div>
        <div><b>Tarjous erittelyineen</b><span class="muted small">Tuotteet, hinta ja ehdot</span></div>
      </div>
      <a class="btn" href="<?php echo esc_url( $pdf_url ); ?>" target="_blank" rel="noopener"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12z"/><circle cx="12" cy="12" r="3"/></svg>Avaa tarjous</a>
    <?php else : ?>
      <div class="price"><?php echo esc_html( $eur( $offer['sum'] ) ); ?></div>
      <div class="muted small">sis. alv<?php echo $offer['labor'] > 0 ? ' · josta työn osuus ' . esc_html( $eur( $offer['labor'] ) ) . ', kotitalousvähennyskelpoinen' : ''; ?></div>
      <?php if ( $offer['content'] ) : ?><p class="pre" style="margin:14px 0 0"><?php echo esc_html( $offer['content'] ); ?></p><?php endif; ?>
      <?php if ( $offer['notes'] ) : ?><p class="pre small muted" style="margin:8px 0 0"><?php echo esc_html( $offer['notes'] ); ?></p><?php endif; ?>
      <?php if ( $offer['file'] ) : ?><a class="btn o" style="margin-top:16px" href="<?php echo esc_url( $pdf_url ); ?>" target="_blank" rel="noopener">Avaa tarjous (PDF)</a><?php endif; ?>
    <?php endif; ?>

    <ul class="facts">
      <?php if ( $offer['valid_until'] && ! $ap ) : ?><li><span>Voimassa</span><b><?php echo esc_html( $fmt( $offer['valid_until'] ) ); ?> asti</b></li><?php endif; ?>
      <li><span>Toimitustapa</span><b><?php echo esc_html( nap_deliveries()[ $delivery ] ); ?></b></li>
      <li><span>Tarjouksen tekijä</span><b><?php echo esc_html( $offer['sent_name'] ); ?></b></li>
      <li><span>Päivätty</span><b><?php echo esc_html( $fmt( $offer['sent_at'] ) ); ?></b></li>
    </ul>
  </section>

  <?php
  // Arvio OP-rahoituksen kuukausierästä (teeman laskuri; vain kun hinta näkyy sivulla).
  $op_month = ! $is_pdf && function_exists( 'nordic_op_monthly' ) ? nordic_op_monthly( (float) $offer['sum'] ) : null;
  $op_page  = get_page_by_path( 'skaala-rahoitus' );
  $op_url   = $op_page && 'publish' === $op_page->post_status ? get_permalink( $op_page ) . '#rahoituslaskuri' : '';
  ?>
  <?php if ( $open ) : ?>
  <section class="card" aria-labelledby="h-h" id="hyvaksy">
    <h2 id="h-h">Hyväksy tarjous</h2>
    <p class="muted small" style="margin:0">Kaksi valintaa, ja tilaus lähtee eteenpäin.</p>
    <form method="post" action="<?php echo esc_url( $base ); ?>">
      <input type="hidden" name="nap_action" value="offer_accept"><?php echo $hidden; // phpcs:ignore ?>
      <p class="step">1. Maksutapa</p>
      <label class="opt"><input type="radio" name="payment" value="holvi" required><span><b>Lasku</b><small>Maksat laskulla.</small></span></label>
      <label class="opt"><input type="radio" name="payment" value="rahoitus"><span><b>Skaala-rahoitus (OP)</b><small>Maksat erissä<?php echo $op_month ? ' – esim. noin ' . esc_html( number_format( $op_month, 0, ',', ' ' ) ) . ' €/kk 5 vuoden maksuajalla' : ''; ?>. Hoidamme rahoitushakemuksen kanssasi.<?php if ( $op_url ) : ?> <a href="<?php echo esc_url( $op_url ); ?>" target="_blank" rel="noopener">Rahoituslaskuri ja ehdot</a><?php endif; ?></small></span></label>
      <p class="step">2. Vahvistus</p>
      <?php if ( $cterms || $mterms ) : ?>
      <details class="terms"><summary>Lue tarjouksen ehdot</summary><?php if ( $mterms ) : ?><p><?php echo esc_html( $mterms ); ?></p><?php endif; ?><?php if ( $cterms ) : ?><p><?php echo esc_html( $cterms ); ?></p><?php endif; ?></details>
      <?php endif; ?>
      <label class="chk"><input type="checkbox" name="terms" value="1" required><span>Olen lukenut tarjouksen ja sen ehdot<?php echo $mterms ? ', ja vastaan itse ilmoittamieni mittojen oikeellisuudesta' : ''; ?>.</span></label>
      <button class="btn" type="submit" style="margin-top:18px">Hyväksy tarjous</button>
      <p class="muted small" style="text-align:center;margin:10px 0 0">Saat vahvistuksen sähköpostiisi.</p>
    </form>
  </section>

  <section class="card" aria-labelledby="n-h">
    <h2 id="n-h">Mitä hyväksymisen jälkeen?</h2>
    <ol class="steps"><?php foreach ( $steps as $s ) : ?><li><span><?php echo esc_html( $s ); ?></span></li><?php endforeach; ?></ol>
  </section>
  <?php endif; ?>

  <?php if ( ! $ap ) : ?>
  <section class="card" aria-labelledby="k-h">
    <h2 id="k-h">Kysyttävää?</h2>
    <p class="muted small" style="margin:0">Käydään tarjous läpi yhdessä. Vastaamme yleensä saman päivän aikana.</p>
    <div class="row2">
      <a class="btn o" href="tel:+3584578325070"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M22 16.9v3a2 2 0 0 1-2.2 2 19.8 19.8 0 0 1-8.6-3.1 19.5 19.5 0 0 1-6-6A19.8 19.8 0 0 1 2.1 4.2 2 2 0 0 1 4.1 2h3a2 2 0 0 1 2 1.7c.1.9.4 1.8.7 2.7a2 2 0 0 1-.5 2.1L8 9.8a16 16 0 0 0 6 6l1.3-1.3a2 2 0 0 1 2.1-.4c.9.3 1.8.6 2.7.7a2 2 0 0 1 1.7 2z"/></svg>Soita</a>
      <?php if ( $open ) : ?><a class="btn o" href="#kysy" onclick="document.getElementById('kysy').open=true">Kirjoita</a><?php else : ?><a class="btn o" href="mailto:info@ikkunakauppias.fi">Sähköposti</a><?php endif; ?>
    </div>
    <p class="muted small" style="margin:10px 0 0;text-align:center">Myynti 045 7832 5070</p>
    <?php if ( $open ) : ?>
    <details class="ask" id="kysy">
      <summary></summary>
      <form method="post" action="<?php echo esc_url( $base ); ?>">
        <input type="hidden" name="nap_action" value="offer_question"><?php echo $hidden; // phpcs:ignore ?>
        <label for="q" class="small" style="font-weight:700;display:block;margin-top:14px">Kysymyksesi</label>
        <textarea id="q" name="question" rows="4" maxlength="2000" required placeholder="Esim. voiko ulko-oven värin vaihtaa?"></textarea>
        <button class="btn" type="submit">Lähetä kysymys</button>
      </form>
    </details>
    <?php endif; ?>
  </section>
  <?php endif; ?>

  <?php if ( $open ) : ?>
  <details class="decline">
    <summary>En ole kiinnostunut</summary>
    <form method="post" action="<?php echo esc_url( $base ); ?>" class="card" style="margin-top:12px;text-align:left">
      <input type="hidden" name="nap_action" value="offer_decline"><?php echo $hidden; // phpcs:ignore ?>
      <p class="small" style="margin:0">Kiitos, että kerrot. Emme muistuta tästä tarjouksesta enää.</p>
      <textarea name="reason" rows="3" maxlength="1000" placeholder="Syy (vapaaehtoinen)"></textarea>
      <button class="btn o" type="submit">Lähetä</button>
    </form>
  </details>
  <?php elseif ( $expired ) : ?>
  <div class="note warn">Tarjouksen voimassaolo päättyi <?php echo esc_html( $fmt( $offer['valid_until'] ) ); ?>. Soita tai lähetä viesti, niin päivitämme tarjouksen.</div>
  <?php endif; ?>

  <p class="foot">Nordic Ikkunat &amp; Ovet Oy · Skaalan jälleenmyyjä<br>Myynti 045 7832 5070 · info@ikkunakauppias.fi</p>
</div></main>
</body>
</html>
