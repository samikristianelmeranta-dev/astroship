<?php
/**
 * Henkilön oma sivu.
 */
defined( 'ABSPATH' ) || exit;
get_header();
while ( have_posts() ) :
	the_post();
	$h = function_exists( 'kp_person' ) ? kp_person( get_post() ) : null;
	if ( ! $h ) {
		continue;
	}
	?>
	<section class="kp-hero kp-hero--hiekka kp-hero--henkilo">
		<div class="kp-hero__sisus kp-kehys kp-kehys--levea">
			<?php kp_breadcrumbs(); ?>
			<div class="kp-profiili">
				<div class="kp-profiili__kuva">
					<?php
					if ( $h['kuva_id'] ) {
						echo wp_get_attachment_image( $h['kuva_id'], 'kp-henkilo', false, array( 'alt' => esc_attr( $h['nimi'] ), 'loading' => 'eager' ) );
					} else {
						echo '<span class="kp-henkilo__paikkamerkki">' . kp_logo( 'liikemerkki' ) . '</span>'; // phpcs:ignore
					}
					?>
				</div>
				<div class="kp-profiili__tiedot">
					<h1 class="kp-hero__otsikko"><?php echo esc_html( $h['nimi'] ); ?></h1>
					<?php if ( $h['titteli'] ) : ?><p class="kp-profiili__titteli"><?php echo esc_html( $h['titteli'] ); ?></p><?php endif; ?>
					<?php if ( $h['toimipisteet'] ) : ?><p class="kp-profiili__toimipiste"><?php echo kp_icon( 'sijainti' ); // phpcs:ignore ?><?php echo esc_html( implode( ', ', wp_list_pluck( $h['toimipisteet'], 'name' ) ) ); ?></p><?php endif; ?>
					<div class="kp-hero__painikkeet">
						<?php if ( $h['puhelin'] ) : ?><a class="kp-button" href="<?php echo esc_attr( kp_tel_href( $h['puhelin'] ) ); ?>"><?php echo esc_html( $h['puhelin'] ); ?></a><?php endif; ?>
						<?php if ( $h['sahkoposti'] ) : ?><a class="kp-button kp-button--reuna" href="mailto:<?php echo esc_attr( antispambot( $h['sahkoposti'] ) ); ?>">Lähetä sähköpostia</a><?php endif; ?>
						<?php if ( $h['linkedin'] ) : ?><a class="kp-button kp-button--reuna" href="<?php echo esc_url( $h['linkedin'] ); ?>" target="_blank" rel="noopener">LinkedIn</a><?php endif; ?>
					</div>
				</div>
			</div>
		</div>
	</section>
	<div class="kp-osio kp-osio--sisalto">
		<div class="kp-kehys kp-sisalto">
			<?php the_content(); ?>
			<?php if ( $h['hakusanat'] ) : ?>
				<h2>Osaaminen</h2>
				<p class="kp-avainsanat">
					<?php foreach ( $h['hakusanat'] as $t ) : ?>
						<a class="kp-chip" href="<?php echo esc_url( add_query_arg( 'haku', rawurlencode( $t->name ), get_permalink( get_page_by_path( 'henkilokunta' ) ) ) . '#haku' ); ?>"><?php echo esc_html( $t->name ); ?></a>
					<?php endforeach; ?>
				</p>
			<?php endif; ?>
			<?php $hk = get_page_by_path( 'henkilokunta' ); ?>
			<?php if ( $hk ) : ?>
				<p><a class="kp-nuolilinkki" href="<?php echo esc_url( get_permalink( $hk ) ); ?>">Kaikki henkilöt</a></p>
			<?php endif; ?>
		</div>
	</div>
	<?php
endwhile;
get_footer();
