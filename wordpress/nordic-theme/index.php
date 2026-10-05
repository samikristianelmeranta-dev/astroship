<?php
/**
 * Varapohja (404, haku ym.). Sivusto koostuu kokonaan sivuista (Pages).
 */

get_header();
?>

<main id="main-content" tabindex="-1">
  <section class="fallback">
    <div class="wrap">
      <?php if ( have_posts() ) : ?>
        <?php while ( have_posts() ) : the_post(); ?>
          <article class="prose">
            <h1><?php the_title(); ?></h1>
            <?php the_content(); ?>
          </article>
        <?php endwhile; ?>
      <?php else : ?>
        <div class="fallback-404">
          <p class="eyebrow">404</p>
          <h1><?php esc_html_e( 'Sivua ei löytynyt', 'nordic' ); ?></h1>
          <p><?php esc_html_e( 'Etsimääsi sisältöä ei valitettavasti löytynyt.', 'nordic' ); ?></p>
          <div class="cta-row">
            <a class="btn btn-primary" href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'Palaa etusivulle', 'nordic' ); ?></a>
            <a class="btn btn-ghost" href="<?php echo esc_url( home_url( '/sivukartta/' ) ); ?>"><?php esc_html_e( 'Sivukartta', 'nordic' ); ?></a>
          </div>
        </div>
      <?php endif; ?>
    </div>
  </section>
</main>

<?php
get_footer();
