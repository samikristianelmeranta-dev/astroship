<?php
/**
 * Template Name: Yhteystiedot
 *
 * Yhteystiedot, toimipisteet ja laskutustiedot tulevat asetuksista
 * (Korjauspartnerit → Asetukset), lomake lisätään automaattisesti.
 * Sivun oma sisältö näytetään yhteystietojen alla.
 */
defined( 'ABSPATH' ) || exit;
get_header();
while ( have_posts() ) :
	the_post();
	kp_singular_hero();
	$phone = kp_t_setting( 'puhelin' );
	$email = kp_t_setting( 'sahkoposti' );
	?>
	<div class="kp-osio">
		<div class="kp-kehys kp-kehys--levea kp-yhteystiedot">
			<div class="kp-yhteystiedot__tiedot">
				<h2 class="kp-osio__otsikko"><?php echo esc_html( kp_t_setting( 'yritys', 'Korjauspartnerit Oy' ) ); ?></h2>
				<ul class="kp-yhteystiedot__lista">
					<?php if ( $phone ) : ?>
						<li><?php echo kp_icon( 'puhelin' ); // phpcs:ignore ?><span><small>Asiakaspalvelu</small><a href="<?php echo esc_attr( kp_tel_href( $phone ) ); ?>"><?php echo esc_html( $phone ); ?></a></span></li>
					<?php endif; ?>
					<?php if ( $email ) : ?>
						<li><?php echo kp_icon( 'email' ); // phpcs:ignore ?><span><small>Sähköposti</small><a href="mailto:<?php echo esc_attr( antispambot( $email ) ); ?>"><?php echo esc_html( antispambot( $email ) ); ?></a></span></li>
					<?php endif; ?>
				</ul>
				<?php if ( trim( get_post()->post_content ) ) : ?>
					<div class="kp-sisalto"><?php the_content(); ?></div>
				<?php endif; ?>

				<div class="kp-laskutus" id="laskutustiedot">
					<h2 class="kp-osio__otsikko kp-osio__otsikko--pieni">Laskutustiedot</h2>
					<dl>
						<?php if ( kp_t_setting( 'ytunnus' ) ) : ?><dt>Y-tunnus</dt><dd><?php echo esc_html( kp_t_setting( 'ytunnus' ) ); ?></dd><?php endif; ?>
						<?php if ( kp_t_setting( 'verkkolaskuosoite' ) ) : ?><dt>Verkkolaskuosoite</dt><dd><?php echo esc_html( kp_t_setting( 'verkkolaskuosoite' ) ); ?></dd><?php endif; ?>
						<?php if ( kp_t_setting( 'operaattori' ) ) : ?><dt>Operaattoritunnus</dt><dd><?php echo esc_html( kp_t_setting( 'operaattori' ) ); ?></dd><?php endif; ?>
						<?php if ( kp_t_setting( 'laskut_sahkoposti' ) ) : ?><dt>Laskut sähköpostitse</dt><dd><?php echo esc_html( kp_t_setting( 'laskut_sahkoposti' ) ); ?></dd><?php endif; ?>
					</dl>
				</div>
			</div>
			<div class="kp-yhteystiedot__lomake">
				<?php echo do_shortcode( '[kp_yhteydenotto otsikko="Jätä viesti tai tarjouspyyntö"]' ); ?>
			</div>
		</div>
	</div>

	<section class="kp-osio kp-osio--hiekka" id="toimipisteet">
		<div class="kp-kehys kp-kehys--levea">
			<h2 class="kp-osio__otsikko">Toimipisteet</h2>
			<?php echo do_shortcode( '[kp_toimipisteet]' ); ?>
			<?php $hk = get_page_by_path( 'henkilokunta' ); ?>
			<?php if ( $hk ) : ?>
				<p class="kp-osio__lisa"><a class="kp-nuolilinkki" href="<?php echo esc_url( get_permalink( $hk ) ); ?>">Katso henkilökunnan yhteystiedot</a></p>
			<?php endif; ?>
		</div>
	</section>
	<?php
endwhile;
get_footer();
