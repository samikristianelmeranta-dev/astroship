<?php
/**
 * Template Name: Henkilökunta (henkilöhaku)
 *
 * Sivun oma sisältö näytetään ensin, sen jälkeen johto ja haettava henkilöhakemisto.
 * Hakemisto päivittyy automaattisesti, kun henkilöitä lisätään kohdassa Henkilökunta.
 */
defined( 'ABSPATH' ) || exit;
get_header();
while ( have_posts() ) :
	the_post();
	kp_singular_hero();
	$content = get_post()->post_content;
	?>
	<?php if ( trim( $content ) ) : ?>
		<div class="kp-osio kp-osio--sisalto kp-osio--tiivis">
			<article <?php post_class( 'kp-kehys kp-sisalto' ); ?>>
				<?php the_content(); ?>
			</article>
		</div>
	<?php endif; ?>
	<?php if ( function_exists( 'kp_query_people' ) && false === strpos( $content, '[kp_henkilohaku' ) ) : ?>
		<?php $johto = kp_query_people( array( 'johto' => 1 ) ); ?>
		<?php if ( $johto ) : ?>
			<section class="kp-osio kp-osio--tiivis">
				<div class="kp-kehys kp-kehys--levea">
					<h2 class="kp-osio__otsikko">Johto</h2>
					<?php echo kp_render_people( $johto ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
				</div>
			</section>
		<?php endif; ?>
		<section class="kp-osio kp-osio--hiekka" id="haku">
			<div class="kp-kehys kp-kehys--levea">
				<h2 class="kp-osio__otsikko">Löydä oma Korjauspartnerisi</h2>
				<p class="kp-osio__johdanto">Hae nimellä, tehtävänimikkeellä tai osaamisalueella. Sähköpostimme ovat muotoa etunimi.sukunimi@korjauspartnerit.fi.</p>
				<?php echo do_shortcode( '[kp_henkilohaku]' ); ?>
			</div>
		</section>
	<?php endif; ?>
	<?php
endwhile;
get_template_part( 'template-parts/cta' );
get_footer();
