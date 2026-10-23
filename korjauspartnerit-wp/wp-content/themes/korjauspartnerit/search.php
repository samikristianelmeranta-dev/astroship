<?php
/**
 * Hakutulokset.
 */
defined( 'ABSPATH' ) || exit;
get_header();
global $wp_query;
$form = '<form role="search" method="get" class="kp-pikahaku__lomake kp-pikahaku__lomake--hero" action="' . esc_url( home_url( '/' ) ) . '"><label class="screen-reader-text" for="kp-hakusivu">Hae</label><input id="kp-hakusivu" type="search" name="s" value="' . esc_attr( get_search_query() ) . '"><button class="kp-button kp-button--valkoinen" type="submit">Hae</button></form>';
kp_page_hero( array(
	'title' => get_search_query() ? sprintf( 'Haku: %s', esc_html( get_search_query() ) ) : 'Haku',
	'lead'  => sprintf( '%d tulosta', (int) $wp_query->found_posts ),
	'extra' => $form,
) );
?>
<div class="kp-osio">
	<div class="kp-kehys">
		<?php if ( have_posts() ) : ?>
			<ol class="kp-hakutulokset">
				<?php
				while ( have_posts() ) :
					the_post();
					$is_person = 'henkilokunta' === get_post_type();
					?>
					<li class="kp-hakutulos">
						<a href="<?php the_permalink(); ?>">
							<span class="kp-hakutulos__tyyppi"><?php echo esc_html( kp_post_type_label() ); ?></span>
							<span class="kp-hakutulos__otsikko"><?php the_title(); ?></span>
							<span class="kp-hakutulos__teksti">
								<?php
								if ( $is_person ) {
									echo esc_html( trim( get_post_meta( get_the_ID(), 'tehtavanimike', true ) . ' · ' . get_post_meta( get_the_ID(), 'sahkoposti', true ), ' ·' ) );
								} else {
									echo esc_html( function_exists( 'kp_excerpt' ) ? kp_excerpt( get_post(), 30 ) : get_the_excerpt() );
								}
								?>
							</span>
						</a>
					</li>
				<?php endwhile; ?>
			</ol>
			<?php kp_pagination(); ?>
		<?php else : ?>
			<div class="kp-sisalto">
				<p>Hakusanalla ei löytynyt tuloksia. Kokeile toista hakusanaa tai selaa <a href="<?php echo esc_url( get_post_type_archive_link( 'palvelu' ) ); ?>">palveluitamme</a>.</p>
				<p>Voit myös soittaa asiakaspalveluumme <a href="<?php echo esc_attr( kp_tel_href( kp_t_setting( 'puhelin' ) ) ); ?>"><?php echo esc_html( kp_t_setting( 'puhelin' ) ); ?></a> – asiasi lähtee heti eteenpäin oikealle asiantuntijalle.</p>
			</div>
		<?php endif; ?>
	</div>
</div>
<?php
get_footer();
