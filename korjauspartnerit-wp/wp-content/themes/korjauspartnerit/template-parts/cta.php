<?php
/**
 * Yhteydenottonosto sivun lopussa.
 */
defined( 'ABSPATH' ) || exit;
$phone = kp_t_setting( 'puhelin' );
$email = kp_t_setting( 'sahkoposti' );
?>
<section class="kp-yhteysnosto">
	<div class="kp-kehys kp-kehys--levea kp-yhteysnosto__sisus">
		<div>
			<h2 class="kp-yhteysnosto__otsikko"><?php echo esc_html( kp_mod( 'kp_cta_otsikko' ) ); ?></h2>
			<p><?php echo esc_html( kp_mod( 'kp_cta_teksti' ) ); ?></p>
		</div>
		<div class="kp-yhteysnosto__toiminnot">
			<?php if ( $phone ) : ?>
				<a class="kp-yhteysnosto__linkki" href="<?php echo esc_attr( function_exists( 'kp_tel_href' ) ? kp_tel_href( $phone ) : '' ); ?>"><?php echo kp_icon( 'puhelin' ); // phpcs:ignore ?><span><small>Asiakaspalvelu</small><?php echo esc_html( $phone ); ?></span></a>
			<?php endif; ?>
			<?php if ( $email ) : ?>
				<a class="kp-yhteysnosto__linkki" href="mailto:<?php echo esc_attr( antispambot( $email ) ); ?>"><?php echo kp_icon( 'email' ); // phpcs:ignore ?><span><small>Sähköposti</small><?php echo esc_html( antispambot( $email ) ); ?></span></a>
			<?php endif; ?>
			<a class="kp-button" href="<?php echo esc_url( home_url( '/yhteystiedot/#viesti' ) ); ?>">Jätä yhteydenottopyyntö</a>
		</div>
	</div>
</section>
