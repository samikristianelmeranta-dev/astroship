<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
<meta charset="<?php bloginfo( 'charset' ); ?>">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<?php wp_head(); ?>
</head>
<?php
$nordic_slug    = is_singular( 'page' ) ? get_post_field( 'post_name', get_the_ID() ) : '';
$nordic_landing = in_array( $nordic_slug, nordic_noindex_slugs(), true ); // mainoslaskeutumissivu: ei päävalikkoa
?>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<a class="skip-link" href="#main-content"><?php esc_html_e( 'Siirry sisältöön', 'nordic' ); ?></a>

<header class="site-header" id="site-header">
  <div class="site-header-inner">

    <a class="site-logo" href="<?php echo esc_url( home_url( '/' ) ); ?>" aria-label="<?php esc_attr_e( 'Ikkunakauppias.fi – etusivu', 'nordic' ); ?>">
      <svg class="site-logo-mark" viewBox="0 0 24 24" aria-hidden="true" focusable="false">
        <rect x="12" y="2" width="10" height="10" fill="#FFD866"/>
        <rect x="2.6" y="2.6" width="18.8" height="18.8" fill="none" stroke="currentColor" stroke-width="1.8"/>
        <line x1="12" y1="2" x2="12" y2="22" stroke="currentColor" stroke-width="1.6"/>
        <line x1="2" y1="12" x2="22" y2="12" stroke="currentColor" stroke-width="1.6"/>
      </svg>
      <span class="site-logo-text">Ikkunakauppias<b>.fi</b></span>
    </a>

    <?php if ( ! $nordic_landing ) : ?>
    <button class="nav-toggle" type="button" aria-expanded="false" aria-controls="site-nav">
      <span class="nav-toggle-bars" aria-hidden="true"><span></span><span></span><span></span></span>
      <span class="screen-reader-text"><?php esc_html_e( 'Valikko', 'nordic' ); ?></span>
    </button>

    <nav class="site-nav" id="site-nav" aria-label="<?php esc_attr_e( 'Päävalikko', 'nordic' ); ?>">
      <?php
      if ( has_nav_menu( 'primary' ) ) {
        wp_nav_menu( array(
          'theme_location' => 'primary',
          'container'      => false,
          'menu_class'     => 'menu',
          'fallback_cb'    => false,
        ) );
      } else {
        $nordic_menu = array(
          '/'                       => 'Etusivu',
          '/ikkunat/'               => 'Ikkunat',
          '/ovet/'                  => 'Ovet',
          '/ikkunaremontti/'        => 'Ikkunaremontti',
          '/oviremontti/'           => 'Oviremontti',
          '/huolto/'                => 'Huolto',
          '/energiansaastolaskuri/' => 'Laskuri',
          '/yhteystiedot/'          => 'Yhteystiedot',
        );
        $nordic_current = trailingslashit( wp_parse_url( (string) get_permalink(), PHP_URL_PATH ) );
        echo '<ul class="menu">';
        foreach ( $nordic_menu as $nordic_path => $nordic_label ) {
          $nordic_is_current = ( $nordic_path === $nordic_current ) || ( '/' === $nordic_path && is_front_page() );
          echo '<li' . ( $nordic_is_current ? ' class="current-menu-item"' : '' ) . '><a href="' . esc_url( home_url( $nordic_path ) ) . '"' . ( $nordic_is_current ? ' aria-current="page"' : '' ) . '>' . esc_html( $nordic_label ) . '</a></li>';
        }
        echo '</ul>';
      }
      ?>
      <a class="btn btn-primary nav-cta-mobile" href="#tarjous" data-quote><?php esc_html_e( 'Pyydä ilmainen tarjous', 'nordic' ); ?></a>
    </nav>
    <?php endif; ?>

    <a class="btn btn-primary header-cta" href="#tarjous" data-quote>
      <span><?php esc_html_e( 'Pyydä ilmainen tarjous', 'nordic' ); ?></span>
      <?php echo nordic_icon( 'arrow' ); // phpcs:ignore ?>
    </a>

  </div>
  <div class="scroll-progress" aria-hidden="true"><span></span></div>
</header>

<?php if ( is_singular( 'page' ) && ! is_front_page() && ! $nordic_landing ) : ?>
<nav class="breadcrumbs" aria-label="<?php esc_attr_e( 'Murupolku', 'nordic' ); ?>">
  <div class="wrap">
    <ol>
      <?php
      $nordic_trail = nordic_breadcrumb_trail( get_the_ID() );
      $nordic_last  = count( $nordic_trail ) - 1;
      foreach ( $nordic_trail as $nordic_i => $nordic_crumb ) {
        if ( $nordic_i === $nordic_last ) {
          echo '<li><span aria-current="page">' . esc_html( $nordic_crumb['name'] ) . '</span></li>';
        } else {
          echo '<li><a href="' . esc_url( $nordic_crumb['url'] ) . '">' . esc_html( $nordic_crumb['name'] ) . '</a></li>';
        }
      }
      ?>
    </ol>
  </div>
</nav>
<?php endif; ?>
