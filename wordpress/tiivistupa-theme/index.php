<?php
/**
 * Oletuspohja (arkistot, haku, 404).
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
get_header();
?>
<section class="hero is-city"><div class="wrap hero-grid"><div class="hero-text">
<h1><?php echo is_404() ? 'Sivua ei löytynyt' : esc_html( wp_get_document_title() ); ?></h1>
<?php if ( is_404() ) : ?><p class="lead">Etsimääsi sivua ei ole tai se on siirretty. Löydät palvelumme alta.</p><?php endif; ?>
</div></div></section>
<div class="seal" aria-hidden="true"></div>
<section class="band is-paper"><div class="wrap"><div class="prose">
<?php if ( have_posts() && ! is_404() ) : ?>
<ul>
	<?php
	while ( have_posts() ) :
		the_post();
		?>
<li><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></li>
	<?php endwhile; ?>
</ul>
<?php else : ?>
<ul>
<li><a href="<?php echo esc_url( home_url( '/' ) ); ?>">Etusivu: ikkunoiden ja ovien huoltoremontti</a></li>
	<?php foreach ( tt_services() as $tt_slug => $tt_name ) : ?>
<li><a href="<?php echo esc_url( home_url( '/' . $tt_slug . '/' ) ); ?>"><?php echo esc_html( $tt_name ); ?></a></li>
	<?php endforeach; ?>
<li><a href="<?php echo esc_url( home_url( '/palvelualue/' ) ); ?>">Palvelualue</a></li>
</ul>
<?php endif; ?>
</div></div></section>
<?php
get_footer();
