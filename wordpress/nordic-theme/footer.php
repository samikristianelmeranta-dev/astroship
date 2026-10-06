<?php $nordic_business = nordic_business_info(); $nordic_region = nordic_region(); ?>
<footer class="site-footer">
  <div class="wrap footer-grid">

    <div class="footer-brand">
      <a class="site-logo site-logo--light" href="<?php echo esc_url( home_url( '/' ) ); ?>">
        <svg class="site-logo-mark" viewBox="0 0 24 24" aria-hidden="true" focusable="false">
          <rect x="12" y="2" width="10" height="10" fill="#FFD866"/>
          <rect x="2.6" y="2.6" width="18.8" height="18.8" fill="none" stroke="currentColor" stroke-width="1.8"/>
          <line x1="12" y1="2" x2="12" y2="22" stroke="currentColor" stroke-width="1.6"/>
          <line x1="2" y1="12" x2="22" y2="12" stroke="currentColor" stroke-width="1.6"/>
        </svg>
        <span class="site-logo-text">Ikkunakauppias<b>.fi</b></span>
      </a>
      <p>Valtuutettu Skaala-jälleenmyyjä. Myymme ja asennamme energiatehokkaita ikkunoita ja ulko-ovia <?php echo esc_html( $nordic_region['in_text'] ); ?>.</p>
    </div>

    <div class="footer-col">
      <h2 class="footer-title"><?php esc_html_e( 'Palvelut', 'nordic' ); ?></h2>
      <?php
      if ( has_nav_menu( 'footer_palvelut' ) ) {
        wp_nav_menu( array(
          'theme_location' => 'footer_palvelut',
          'container'      => false,
          'menu_class'     => '',
          'fallback_cb'    => false,
        ) );
      } elseif ( nordic_is_regional() ) {
        echo '<ul>';
        foreach ( $nordic_region['menu'] as $nordic_path => $nordic_label ) {
          if ( in_array( $nordic_path, array( '/', '/yhteystiedot/' ), true ) ) {
            continue;
          }
          echo '<li><a href="' . esc_url( home_url( $nordic_path ) ) . '">' . esc_html( $nordic_label ) . '</a></li>';
        }
        echo '</ul>';
      } else {
        echo '<ul>';
        echo '<li><a href="' . esc_url( home_url( '/ikkunat/' ) ) . '">Ikkunat</a></li>';
        echo '<li><a href="' . esc_url( home_url( '/ovet/' ) ) . '">Ovet</a></li>';
        echo '<li><a href="' . esc_url( home_url( '/ikkunaremontti/' ) ) . '">Ikkunaremontti</a></li>';
        echo '<li><a href="' . esc_url( home_url( '/oviremontti/' ) ) . '">Oviremontti</a></li>';
        echo '<li><a href="' . esc_url( home_url( '/huolto/' ) ) . '">Ikkuna- ja ovihuolto</a></li>';
        echo '<li><a href="' . esc_url( home_url( '/energiansaastolaskuri/' ) ) . '">Energiansäästölaskuri</a></li>';
        echo '<li><a href="' . esc_url( home_url( '/sivukartta/' ) ) . '">Sivukartta</a></li>';
        echo '</ul>';
      }
      ?>
    </div>

    <div class="footer-col">
      <h2 class="footer-title"><?php esc_html_e( 'Alueet', 'nordic' ); ?></h2>
      <?php
      if ( has_nav_menu( 'footer_alueet' ) ) {
        wp_nav_menu( array(
          'theme_location' => 'footer_alueet',
          'container'      => false,
          'menu_class'     => '',
          'fallback_cb'    => false,
        ) );
      } elseif ( nordic_is_regional() ) {
        echo '<ul class="footer-cities">';
        foreach ( $nordic_region['cities'] as $nordic_city_name ) {
          echo '<li><span>' . esc_html( $nordic_city_name ) . '</span></li>';
        }
        echo '</ul>';
      } else {
        echo '<ul class="footer-cities">';
        foreach ( array( 'turku', 'kaarina', 'raisio', 'naantali', 'lieto', 'salo', 'paimio', 'parainen' ) as $nordic_city ) {
          $nordic_cities = nordic_cities();
          echo '<li><a href="' . esc_url( home_url( '/ikkunat-ikkunaremontti-' . $nordic_city . '/' ) ) . '">' . esc_html( $nordic_cities[ $nordic_city ] ) . '</a></li>';
        }
        echo '</ul>';
      }
      ?>
    </div>

    <div class="footer-col">
      <h2 class="footer-title"><?php esc_html_e( 'Yhteystiedot', 'nordic' ); ?></h2>
      <ul class="footer-contact">
        <li><?php echo nordic_icon( 'pin' ); // phpcs:ignore ?><span><?php echo esc_html( $nordic_region['area_text'] ); ?></span></li>
        <li><?php echo nordic_icon( 'clock' ); // phpcs:ignore ?><span>Arkisin klo 8–17</span></li>
        <?php if ( $nordic_business['telephone'] ) : ?>
        <li><?php echo nordic_icon( 'phone' ); // phpcs:ignore ?><a href="tel:<?php echo esc_attr( $nordic_business['telephone'] ); ?>"><?php echo esc_html( $nordic_business['telephone'] ); ?></a></li>
        <?php endif; ?>
        <li><?php echo nordic_icon( 'mail' ); // phpcs:ignore ?><a href="mailto:<?php echo esc_attr( $nordic_business['email'] ); ?>"><?php echo esc_html( $nordic_business['email'] ); ?></a></li>
        <li><a class="footer-link-strong" href="<?php echo esc_url( home_url( '/yhteystiedot/' ) ); ?>">Ota yhteyttä &rarr;</a></li>
      </ul>
    </div>

  </div>

  <div class="footer-bottom">
    <div class="wrap">
      <span>&copy; <?php echo esc_html( date( 'Y' ) ); ?> Nordic Ikkunat &amp; Ovet Oy</span>
      <?php if ( nordic_is_regional() ) : ?>
      <span>Y-tunnus 3653098-6 · <a href="https://ikkunakauppias.fi/tietosuojaseloste/">Tietosuojaseloste</a></span>
      <?php else : ?>
      <span><a href="<?php echo esc_url( home_url( '/sivukartta/' ) ); ?>">Sivukartta</a></span>
      <?php endif; ?>
    </div>
  </div>
</footer>

<?php /* Kiinteä tarjous-CTA mobiilissa */ ?>
<div class="sticky-cta" id="sticky-cta" aria-hidden="true">
  <a class="btn btn-primary" href="#tarjous" data-quote tabindex="-1">
    <span><?php esc_html_e( 'Pyydä ilmainen tarjous', 'nordic' ); ?></span>
    <?php echo nordic_icon( 'arrow' ); // phpcs:ignore ?>
  </a>
</div>

<?php /* Tarjouslomake modaalina sivuilla, joilla ei ole omaa lomaketta */ ?>
<dialog class="quote-modal" id="quote-modal" aria-labelledby="quote-modal-title" data-has-inline-form="<?php echo nordic_page_has_form() ? '1' : '0'; ?>">
  <div class="quote-modal-inner">
    <button class="quote-modal-close" type="button" data-close aria-label="<?php esc_attr_e( 'Sulje', 'nordic' ); ?>"><?php echo nordic_icon( 'close' ); // phpcs:ignore ?></button>
    <div class="quote-modal-head">
      <p class="quote-modal-kicker"><?php esc_html_e( 'Ilmainen mittauskäynti', 'nordic' ); ?></p>
      <h2 id="quote-modal-title"><?php esc_html_e( 'Pyydä ilmainen tarjous', 'nordic' ); ?></h2>
      <p><?php esc_html_e( 'Ota yhteyttä, niin sovimme mittauskäynnin ja laadimme sitoumuksettoman tarjouksen.', 'nordic' ); ?></p>
    </div>
    <div class="quote-modal-form contact-form-col">
      <?php if ( ! nordic_page_has_form() ) { echo nordic_quote_form_html(); } // phpcs:ignore ?>
    </div>
  </div>
</dialog>

<?php wp_footer(); ?>
</body>
</html>
