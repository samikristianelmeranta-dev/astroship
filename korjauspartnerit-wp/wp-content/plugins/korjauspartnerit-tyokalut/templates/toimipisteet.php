<?php
/**
 * Toimipisteiden yhteystiedot (Asetukset → Korjauspartnerit).
 *
 * @var array $offices
 */
defined( 'ABSPATH' ) || exit;
if ( ! $offices ) {
	return;
}
?>
<div class="kp-toimipisteet">
	<?php foreach ( $offices as $o ) : ?>
		<div class="kp-toimipiste">
			<h3><?php echo esc_html( $o['nimi'] ); ?></h3>
			<?php if ( $o['osoite'] ) : ?>
				<p class="kp-toimipiste__osoite"><a href="<?php echo esc_url( 'https://www.google.com/maps/search/?api=1&query=' . rawurlencode( $o['osoite'] ) ); ?>" target="_blank" rel="noopener"><?php echo esc_html( $o['osoite'] ); ?></a></p>
			<?php endif; ?>
			<?php if ( $o['lisatieto'] ) : ?>
				<p><?php echo esc_html( $o['lisatieto'] ); ?></p>
			<?php endif; ?>
			<ul class="kp-henkilo__yhteys">
				<?php if ( $o['puhelin'] ) : ?>
					<li><a href="<?php echo esc_attr( kp_tel_href( $o['puhelin'] ) ); ?>"><span class="kp-ikoni kp-ikoni--puhelin" aria-hidden="true"></span><?php echo esc_html( $o['puhelin'] ); ?></a></li>
				<?php endif; ?>
				<?php if ( $o['email'] ) : ?>
					<li><a href="mailto:<?php echo esc_attr( antispambot( $o['email'] ) ); ?>"><span class="kp-ikoni kp-ikoni--email" aria-hidden="true"></span><?php echo esc_html( antispambot( $o['email'] ) ); ?></a></li>
				<?php endif; ?>
			</ul>
		</div>
	<?php endforeach; ?>
</div>
