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
      <?php $nordic_about_url = nordic_about_url(); if ( $nordic_about_url ) : ?>
      <a class="footer-about" href="<?php echo esc_url( $nordic_about_url ); ?>"><?php echo nordic_team_faces( 'team-faces--sm' ); // phpcs:ignore ?><span><b>Tutustu meihin</b><small>Neljä yrittäjää, yksi tiimi</small></span><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6"/></svg></a>
      <?php endif; ?>
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
        $nordic_fin = get_page_by_path( 'skaala-rahoitus' );
        if ( $nordic_fin && 'publish' === $nordic_fin->post_status ) {
          echo '<li><a href="' . esc_url( get_permalink( $nordic_fin ) ) . '">Rahoitus</a></li>';
        }
        $nordic_about = nordic_about_page();
        if ( $nordic_about ) {
          echo '<li><a href="' . esc_url( get_permalink( $nordic_about ) ) . '">Meistä</a></li>';
        }
        $nordic_hub = get_page_by_path( 'oppaat-ja-artikkelit' );
        if ( $nordic_hub && 'publish' === $nordic_hub->post_status ) {
          echo '<li><a href="' . esc_url( get_permalink( $nordic_hub ) ) . '">Oppaat ja artikkelit</a></li>';
        }
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
        <?php foreach ( $nordic_business['phones'] as $nordic_phone ) : ?>
        <li><?php echo nordic_icon( 'phone' ); // phpcs:ignore ?><span><?php echo esc_html( $nordic_phone['label'] ); ?> <a href="tel:<?php echo esc_attr( $nordic_phone['tel'] ); ?>" data-phone="<?php echo esc_attr( $nordic_phone['type'] ); ?>"><?php echo esc_html( $nordic_phone['display'] ); ?></a></span></li>
        <?php endforeach; ?>
        <li><?php echo nordic_icon( 'mail' ); // phpcs:ignore ?><a href="mailto:<?php echo esc_attr( $nordic_business['email'] ); ?>"><?php echo esc_html( $nordic_business['email'] ); ?></a></li>
        <li><a class="footer-link-strong" href="<?php echo esc_url( home_url( '/yhteystiedot/' ) ); ?>">Ota yhteyttä &rarr;</a></li>
      </ul>
      <?php $nordic_social = nordic_social_profiles(); if ( $nordic_social ) : ?>
      <ul class="footer-social">
        <?php foreach ( $nordic_social as $nordic_key => $nordic_sp ) : ?>
        <li><a href="<?php echo esc_url( $nordic_sp['url'] ); ?>" target="_blank" rel="noopener me" aria-label="<?php echo esc_attr( 'Nordic Ikkunat & Ovet: ' . $nordic_sp['label'] ); ?>"><?php echo nordic_social_icon( $nordic_key ); // phpcs:ignore ?></a></li>
        <?php endforeach; ?>
      </ul>
      <?php endif; ?>
    </div>

  </div>

  <div class="footer-bottom">
    <div class="wrap">
      <span>&copy; <?php echo esc_html( date( 'Y' ) ); ?> Nordic Ikkunat &amp; Ovet Oy</span>
      <?php if ( nordic_is_regional() ) : ?>
      <span>Y-tunnus 3653098-6 · Kotipaikka Masku · <a href="https://ikkunakauppias.fi/tietosuojaseloste/">Tietosuojaseloste</a><?php if ( function_exists( 'nordic_evasteet_link' ) ) echo ' · ' . nordic_evasteet_link(); ?></span>
      <?php else : ?>
      <span>Y-tunnus 3653098-6 · Kotipaikka Masku · <a href="<?php echo esc_url( nordic_privacy_url() ); ?>">Tietosuojaseloste</a> · <a href="<?php echo esc_url( home_url( '/sivukartta/' ) ); ?>">Sivukartta</a><?php if ( function_exists( 'nordic_evasteet_link' ) ) echo ' · ' . nordic_evasteet_link(); ?></span>
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

<?php if ( nordic_exit_popup_enabled() ) : ?>
<?php /* Poistumisikkuna: aukeaa kerran, kun kävijä on lähdössä sivulta (exit-intent.js) */ ?>
<dialog class="exit-modal" id="exit-modal" aria-labelledby="exit-modal-title" aria-describedby="exit-modal-text">
  <div class="exit-modal-inner">
    <button class="quote-modal-close exit-modal-close" type="button" data-exit-close aria-label="<?php esc_attr_e( 'Sulje', 'nordic' ); ?>"><?php echo nordic_icon( 'close' ); // phpcs:ignore ?></button>
    <div class="exit-modal-photos" aria-hidden="true">
      <?php foreach ( nordic_team() as $nordic_m ) : ?>
      <img src="<?php echo esc_url( nordic_asset_uri( 'img/tiimi/' . $nordic_m['key'] . '-300.webp' ) ); ?>" alt="" width="300" height="400" loading="lazy" decoding="async">
      <?php endforeach; ?>
    </div>
    <div class="exit-modal-body">
      <p class="exit-modal-kicker">Ennen kuin lähdet</p>
      <h2 id="exit-modal-title">Haluatko varmasti poistua sivuilta?</h2>
      <p id="exit-modal-text">Voimme myös tulla luoksesi: ammattilaisemme katsoo yhdessä kanssasi ikkunoiden ja ovien kunnon ja kertoo suoraan, riittääkö huolto vai kannattaako vaihtaa. Käynti on maksuton eikä sido mihinkään.</p>
      <div class="exit-modal-actions">
        <a class="btn btn-primary" href="#tarjous" data-quote data-exit-yes autofocus>Kyllä, haluan maksuttoman käynnin</a>
        <button class="exit-modal-no" type="button" data-exit-close>Ei kiitos, poistun sivulta</button>
      </div>
      <p class="exit-modal-team"><?php echo esc_html( implode( ', ', array_map( function ( $m ) { return strtok( $m['name'], ' ' ); }, array_slice( nordic_team(), 0, -1 ) ) ) . ' ja ' . strtok( nordic_team()[ count( nordic_team() ) - 1 ]['name'], ' ' ) ); ?> · vastaamme viimeistään seuraavana arkipäivänä</p>
    </div>
  </div>
</dialog>
<?php endif; ?>

<?php wp_footer(); ?>
</body>
</html>
