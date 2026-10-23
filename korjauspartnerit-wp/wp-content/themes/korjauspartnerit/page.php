<?php
/**
 * Oletussivupohja.
 */
defined( 'ABSPATH' ) || exit;
get_header();
while ( have_posts() ) :
	the_post();
	kp_singular_hero();
	?>
	<div class="kp-osio kp-osio--sisalto">
		<article <?php post_class( 'kp-kehys kp-sisalto' ); ?>>
			<?php the_content(); ?>
			<?php wp_link_pages(); ?>
		</article>
	</div>
	<?php
	kp_after_content();
endwhile;
get_footer();
