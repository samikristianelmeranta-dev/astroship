<?php
/**
 * 404.
 *
 * @package sami-elmeranta
 */

get_header();
?>
<div class="container-site grid min-h-[60vh] place-items-center py-24 text-center">
	<div>
		<p class="hero-word font-display text-[8rem] font-extrabold leading-none text-gradient sm:text-[12rem]">404</p>
		<h1 class="hero-word mt-4 text-4xl font-bold">Sivua ei löytynyt</h1>
		<p class="hero-word mt-4 text-lg text-muted">Etsimääsi sivua ei ole – se on ehkä siirtynyt uuteen osoitteeseen.</p>
		<div class="hero-word mx-auto mt-8 max-w-md"><?php get_search_form(); ?></div>
		<div class="hero-word mt-8 flex flex-wrap justify-center gap-3">
			<a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="btn-primary">Etusivulle</a>
			<a href="<?php echo esc_url( se_blog_url() ); ?>" class="btn-light">Blogiin</a>
		</div>
	</div>
</div>
<?php
get_footer();
