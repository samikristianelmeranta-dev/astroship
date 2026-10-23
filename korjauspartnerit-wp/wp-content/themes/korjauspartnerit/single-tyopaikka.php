<?php
/**
 * Yksittäinen työpaikkailmoitus.
 */
defined( 'ABSPATH' ) || exit;
get_header();
while ( have_posts() ) :
	the_post();
	$id      = get_the_ID();
	$link    = get_post_meta( $id, 'kp_hakulinkki', true );
	$email   = get_post_meta( $id, 'kp_hakuemail', true );
	$ends    = get_post_meta( $id, 'kp_haku_paattyy', true );
	$closed  = $ends && $ends < current_time( 'Y-m-d' );
	$apply   = $link ? $link : ( $email ? 'mailto:' . antispambot( $email ) . '?subject=' . rawurlencode( 'Hakemus: ' . get_the_title() ) : '' );
	$facts   = array_filter( array(
		'Sijainti'     => get_post_meta( $id, 'kp_sijainti', true ),
		'Työsuhde'     => get_post_meta( $id, 'kp_tyosuhde', true ),
		'Haku päättyy' => $ends ? date_i18n( 'j.n.Y', strtotime( $ends ) ) : 'Jatkuva haku',
	) );
	kp_singular_hero( array(
		'kicker'  => 'Työpaikka',
		'buttons' => ( $apply && ! $closed ) ? array( array( 'Hae paikkaa', $apply, '' ) ) : array(),
	) );
	?>
	<div class="kp-osio kp-osio--sisalto">
		<div class="kp-kehys kp-kehys--levea kp-sivupalkillinen">
			<article <?php post_class( 'kp-sisalto' ); ?>>
				<?php if ( $closed ) : ?>
					<div class="kp-laatikko"><p><strong>Tämän tehtävän haku on päättynyt.</strong> Katso muut <a href="<?php echo esc_url( get_post_type_archive_link( 'tyopaikka' ) ); ?>">avoimet työpaikat</a>.</p></div>
				<?php endif; ?>
				<?php the_content(); ?>
			</article>
			<aside class="kp-sivupalkki" aria-label="Tehtävän tiedot">
				<div class="kp-sivupalkki__laatikko">
					<h2>Tehtävä</h2>
					<dl class="kp-faktat">
						<?php foreach ( $facts as $k => $v ) : ?>
							<dt><?php echo esc_html( $k ); ?></dt><dd><?php echo esc_html( $v ); ?></dd>
						<?php endforeach; ?>
					</dl>
					<?php if ( $apply && ! $closed ) : ?>
						<p><a class="kp-button" href="<?php echo esc_url( $apply ); ?>">Hae paikkaa</a></p>
					<?php endif; ?>
				</div>
			</aside>
		</div>
	</div>
	<?php
endwhile;
get_footer();
