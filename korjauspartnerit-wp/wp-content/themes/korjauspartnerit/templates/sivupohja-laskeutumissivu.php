<?php
/**
 * Template Name: Kampanja- / laskeutumissivu
 *
 * Kevennetty ylätunniste (logo + puhelin), iso yläosa ja yhteydenottolomake.
 * Sopii mainoskampanjoihin ja webinaareihin.
 */
defined( 'ABSPATH' ) || exit;
get_header();
while ( have_posts() ) :
	the_post();
	kp_singular_hero( array(
		'crumbs'  => false,
		'buttons' => array( array( get_post_meta( get_the_ID(), 'kp_painike_teksti', true ) ?: 'Ota yhteyttä', get_post_meta( get_the_ID(), 'kp_painike_linkki', true ) ?: '#viesti', '' ) ),
	) );
	?>
	<div class="kp-osio kp-osio--sisalto">
		<article <?php post_class( 'kp-kehys kp-sisalto' ); ?>>
			<?php the_content(); ?>
		</article>
	</div>
	<?php
	kp_after_content();
	if ( false === strpos( get_post()->post_content, '[kp_yhteydenotto' ) && ! get_post_meta( get_the_ID(), 'kp_nayta_lomake', true ) ) {
		echo '<section class="kp-osio kp-osio--hiekka kp-osio--lomake"><div class="kp-kehys kp-kehys--kapea"><h2 class="kp-osio__otsikko">Ota yhteyttä</h2>';
		echo do_shortcode( '[kp_yhteydenotto]' );
		echo '</div></section>';
	}
endwhile;
get_footer();
