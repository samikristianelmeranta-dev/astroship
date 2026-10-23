<?php
/**
 * Template Name: Leveä sisältö
 * Template Post Type: page, palvelu
 *
 * Sisältö koko sisältöalueen levyisenä (esim. paljon kortteja, taulukoita tai kuvia).
 */
defined( 'ABSPATH' ) || exit;
get_header();
while ( have_posts() ) :
	the_post();
	kp_singular_hero();
	?>
	<div class="kp-osio kp-osio--sisalto">
		<article <?php post_class( 'kp-kehys kp-kehys--levea kp-sisalto kp-sisalto--levea' ); ?>>
			<?php the_content(); ?>
		</article>
	</div>
	<?php
	kp_after_content();
endwhile;
get_footer();
