<?php
/**
 * Yksittäinen referenssi.
 */
defined( 'ABSPATH' ) || exit;
get_header();
while ( have_posts() ) :
	the_post();
	$id    = get_the_ID();
	$types = get_the_terms( $id, 'referenssityyppi' );
	$types = ( $types && ! is_wp_error( $types ) ) ? $types : array();
	$facts = array_filter( array(
		'Toteutettu'  => get_post_meta( $id, 'kohde', true ),
		'Tilaaja'     => get_post_meta( $id, 'kp_tilaaja', true ),
		'Paikkakunta' => get_post_meta( $id, 'kp_sijainti', true ),
		'Valmistunut' => get_post_meta( $id, 'kp_valmistunut', true ),
	) );
	kp_singular_hero( array(
		'kicker' => $types ? esc_html( implode( ', ', wp_list_pluck( array_filter( $types, function ( $t ) { return $t->parent; } ) ?: $types, 'name' ) ) ) : 'Referenssi',
	) );
	?>
	<div class="kp-osio kp-osio--sisalto">
		<div class="kp-kehys kp-kehys--levea kp-sivupalkillinen">
			<article <?php post_class( 'kp-sisalto' ); ?>>
				<?php the_content(); ?>
			</article>
			<aside class="kp-sivupalkki" aria-label="Kohteen tiedot">
				<?php if ( $facts || $types ) : ?>
					<div class="kp-sivupalkki__laatikko">
						<h2>Kohde</h2>
						<dl class="kp-faktat">
							<?php foreach ( $facts as $k => $v ) : ?>
								<dt><?php echo esc_html( $k ); ?></dt><dd><?php echo esc_html( $v ); ?></dd>
							<?php endforeach; ?>
							<?php if ( $types ) : ?>
								<dt>Hanketyyppi</dt>
								<dd><?php echo wp_kses_post( implode( ', ', array_map( function ( $t ) { return '<a href="' . esc_url( get_term_link( $t ) ) . '">' . esc_html( $t->name ) . '</a>'; }, $types ) ) ); ?></dd>
							<?php endif; ?>
						</dl>
					</div>
				<?php endif; ?>
				<div class="kp-sivupalkki__laatikko kp-sivupalkki__laatikko--turkoosi">
					<h2>Suunnitteilla vastaava hanke?</h2>
					<p>Kerro tarpeestasi, niin kerromme miten voimme auttaa.</p>
					<p><a class="kp-button kp-button--valkoinen" href="<?php echo esc_url( home_url( '/yhteystiedot/#viesti' ) ); ?>">Ota yhteyttä</a></p>
				</div>
			</aside>
		</div>
	</div>
	<?php
	kp_after_content();
	$related = $types ? get_posts( array(
		'post_type'    => 'referenssi',
		'numberposts'  => 3,
		'post__not_in' => array( $id ),
		'tax_query'    => array( array( 'taxonomy' => 'referenssityyppi', 'terms' => wp_list_pluck( $types, 'term_id' ) ) ), // phpcs:ignore
	) ) : array();
	if ( $related && function_exists( 'kp_get_template' ) ) :
		?>
		<section class="kp-osio kp-osio--hiekka">
			<div class="kp-kehys kp-kehys--levea">
				<header class="kp-osio__yla kp-osio__yla--rivi">
					<h2 class="kp-osio__otsikko">Muita vastaavia kohteita</h2>
					<a class="kp-nuolilinkki" href="<?php echo esc_url( get_post_type_archive_link( 'referenssi' ) ); ?>">Kaikki referenssit</a>
				</header>
				<div class="kp-kortisto kp-kortisto--referenssit">
					<?php
					foreach ( $related as $r ) {
						echo kp_get_template( 'referenssi-kortti', array( 'post' => $r ) ); // phpcs:ignore
					}
					?>
				</div>
			</div>
		</section>
		<?php
	endif;
endwhile;
get_footer();
