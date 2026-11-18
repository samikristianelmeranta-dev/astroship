<?php
/**
 * Alaosa: tarjouslomake, alapalkki.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
$tt_phone = tt_opt( 'tt_phone' );
$tt_email = tt_opt( 'tt_email' );
$tt_phone2 = tt_opt( 'tt_phone2' );
?>
<div class="seal" aria-hidden="true"></div>
<section class="quote" id="tarjous"><div class="wrap quote-grid">
<div class="quote-text">
<h2>Pyydä maksuton kartoitus ja tarjous</h2>
<p>Kerro lyhyesti, mitä ikkunoissasi tai ovissasi on vialla. Vastaamme viimeistään seuraavana arkipäivänä ja sovimme kartoituskäynnin. Tarjous on kirjallinen eikä sido sinua mihinkään.</p>
<ul class="contact-list">
<?php if ( $tt_phone ) : ?><li><span>Myynti</span><a href="tel:<?php echo esc_attr( tt_phone_intl() ); ?>"><b><?php echo esc_html( $tt_phone ); ?></b></a></li><?php endif; ?>
<?php if ( $tt_phone2 ) : ?><li><span>Asennus</span><a href="tel:<?php echo esc_attr( preg_replace( '/[^0-9+]/', '', $tt_phone2 ) ); ?>"><b><?php echo esc_html( $tt_phone2 ); ?></b></a></li><?php endif; ?>
<?php if ( $tt_email ) : ?><li><span>Sähköposti</span><a href="mailto:<?php echo esc_attr( $tt_email ); ?>"><b><?php echo esc_html( $tt_email ); ?></b></a></li><?php endif; ?>
<li><span>Tukikohta</span><?php echo esc_html( tt_opt( 'tt_locality' ) ); ?>, Varsinais-Suomi</li>
</ul>
</div>
<?php echo tt_quote_form_html(); // phpcs:ignore ?>
</div></section>
</main>
<footer class="site-foot">
<div class="wrap foot-grid">
<div><h2>TiivisTupa</h2><p>Ikkunoiden ja ovien huoltoremontit Turussa ja Varsinais-Suomessa: huolto, tiivistys, korjaus ja puitteiden maalaus.</p></div>
<div><h2>Palvelut</h2><ul>
<?php foreach ( tt_services() as $tt_slug => $tt_name ) : ?>
<li><a href="<?php echo esc_url( home_url( '/' . $tt_slug . '/' ) ); ?>"><?php echo esc_html( $tt_name ); ?></a></li>
<?php endforeach; ?>
</ul></div>
<div><h2>Paikkakunnat</h2><ul>
<?php foreach ( tt_cities() as $tt_slug => $tt_name ) : ?>
<li><a href="<?php echo esc_url( home_url( '/ikkunahuolto-' . $tt_slug . '/' ) ); ?>"><?php echo esc_html( $tt_name ); ?></a></li>
<?php endforeach; ?>
<li><a href="<?php echo esc_url( home_url( '/palvelualue/' ) ); ?>">Kaikki aluesivut</a></li>
</ul></div>
<div><h2>Yhteystiedot</h2><ul>
<?php if ( $tt_phone ) : ?><li>Myynti <?php echo esc_html( $tt_phone ); ?></li><?php endif; ?>
<?php if ( $tt_phone2 ) : ?><li>Asennus <?php echo esc_html( $tt_phone2 ); ?></li><?php endif; ?>
<?php if ( $tt_email ) : ?><li><?php echo esc_html( $tt_email ); ?></li><?php endif; ?>
<li><?php echo esc_html( tt_opt( 'tt_locality' ) ); ?>, Varsinais-Suomi</li>
<li><a href="<?php echo esc_url( home_url( '/yhteystiedot/' ) ); ?>">Yhteystiedot ja tiimi</a></li>
<li><a href="<?php echo esc_url( TT_PARENT_URL ); ?>">Uudet ikkunat ja ovet: www.ikkunakauppias.fi</a></li>
<?php if ( get_privacy_policy_url() ) : ?><li><a href="<?php echo esc_url( get_privacy_policy_url() ); ?>">Tietosuojaseloste</a></li><?php endif; ?>
</ul></div>
</div>
<div class="wrap foot-bottom">
<span><?php echo esc_html( TT_LEGAL_NOTE ); ?><?php if ( tt_opt( 'tt_ytunnus' ) ) : ?> Y-tunnus <?php echo esc_html( tt_opt( 'tt_ytunnus' ) ); ?>.<?php endif; ?></span>
<span>© <?php echo esc_html( gmdate( 'Y' ) ); ?> TiivisTupa</span>
</div>
</footer>
<script>
(function(){var f=document.getElementById('quote-form');if(!f)return;var s=document.getElementById('q-status');
f.addEventListener('submit',function(ev){var n=document.getElementById('q-nimi'),p=document.getElementById('q-puhelin');
if(!n.value.trim()||!p.value.trim()){ev.preventDefault();s.hidden=false;s.classList.remove('is-ok');s.textContent='Täytä vähintään nimi ja puhelinnumero.';(n.value.trim()?p:n).focus();}});})();
</script>
<?php wp_footer(); ?>
</body>
</html>
