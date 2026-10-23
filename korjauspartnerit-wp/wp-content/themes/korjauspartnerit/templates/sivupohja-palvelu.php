<?php
/**
 * Template Name: Palvelusivu (sivupalkilla)
 * Template Post Type: page, palvelu
 *
 * Sisältö vasemmalla, oikealla "Kysy lisää" -yhteyslaatikko ja muut saman alueen palvelut.
 */
defined( 'ABSPATH' ) || exit;
get_header();
while ( have_posts() ) :
	the_post();
	kp_singular_hero( array( 'buttons' => array( array( 'Pyydä tarjous', '#viesti', '' ) ) ) );
	?>
	<div class="kp-osio kp-osio--sisalto">
		<div class="kp-kehys kp-kehys--levea kp-sivupalkillinen">
			<article <?php post_class( 'kp-sisalto' ); ?>>
				<?php the_content(); ?>
			</article>
			<?php get_template_part( 'template-parts/sivupalkki-palvelu' ); ?>
		</div>
	</div>
	<?php
	kp_after_content();
	if ( ! get_post_meta( get_the_ID(), 'kp_nayta_lomake', true ) && false === strpos( get_post()->post_content, '[kp_yhteydenotto' ) ) {
		echo '<section class="kp-osio kp-osio--hiekka kp-osio--lomake"><div class="kp-kehys kp-kehys--kapea"><h2 class="kp-osio__otsikko">Pyydä tarjous tai kysy lisää</h2>';
		echo do_shortcode( '[kp_yhteydenotto]' );
		echo '</div></section>';
	}
endwhile;
get_footer();
