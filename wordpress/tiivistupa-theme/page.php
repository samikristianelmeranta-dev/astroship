<?php
/**
 * Sivupohja. Teeman omat sivut tulostetaan sellaisenaan (hero on sisällössä),
 * muut sivut (esim. tietosuojaseloste) saavat otsikon ja lukupalstan.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
get_header();
while ( have_posts() ) :
	the_post();
	if ( tt_is_theme_page() ) :
		the_content();
	else :
		?>
<section class="hero is-city"><div class="wrap hero-grid"><div class="hero-text"><h1><?php the_title(); ?></h1></div></div></section>
<div class="seal" aria-hidden="true"></div>
<section class="band is-paper"><div class="wrap"><div class="prose page-plain"><?php the_content(); ?></div></div></section>
		<?php
	endif;
endwhile;
get_footer();
