<?php
/**
 * Henkilökortti. Teema voi ohittaa: /korjauspartnerit/henkilo-kortti.php
 *
 * @var array $henkilo kp_person()-taulukko.
 */
defined( 'ABSPATH' ) || exit;
$h = $henkilo;
?>
<article class="kp-henkilo">
	<div class="kp-henkilo__kuva">
		<?php
		if ( $h['kuva_id'] ) {
			echo wp_get_attachment_image( $h['kuva_id'], 'medium', false, array( 'alt' => esc_attr( $h['nimi'] ), 'loading' => 'lazy' ) );
		} else {
			echo '<span class="kp-henkilo__paikkamerkki">' . kp_mark_svg() . '</span>'; // phpcs:ignore WordPress.Security.EscapeOutput
		}
		?>
	</div>
	<div class="kp-henkilo__tiedot">
		<h3 class="kp-henkilo__nimi"><?php echo esc_html( $h['nimi'] ); ?></h3>
		<?php if ( $h['titteli'] ) : ?>
			<p class="kp-henkilo__titteli"><?php echo esc_html( $h['titteli'] ); ?></p>
		<?php endif; ?>
		<?php if ( $h['toimipisteet'] ) : ?>
			<p class="kp-henkilo__toimipiste"><?php echo esc_html( implode( ', ', wp_list_pluck( $h['toimipisteet'], 'name' ) ) ); ?></p>
		<?php endif; ?>
		<ul class="kp-henkilo__yhteys">
			<?php if ( $h['puhelin'] ) : ?>
				<li><a href="<?php echo esc_attr( kp_tel_href( $h['puhelin'] ) ); ?>"><span class="kp-ikoni kp-ikoni--puhelin" aria-hidden="true"></span><?php echo esc_html( $h['puhelin'] ); ?></a></li>
			<?php endif; ?>
			<?php if ( $h['sahkoposti'] ) : ?>
				<li><a href="mailto:<?php echo esc_attr( antispambot( $h['sahkoposti'] ) ); ?>"><span class="kp-ikoni kp-ikoni--email" aria-hidden="true"></span><?php echo kp_email_html( $h['sahkoposti'] ); // phpcs:ignore ?></a></li>
			<?php endif; ?>
			<?php if ( $h['linkedin'] ) : ?>
				<li><a href="<?php echo esc_url( $h['linkedin'] ); ?>" target="_blank" rel="noopener"><span class="kp-ikoni kp-ikoni--linkedin" aria-hidden="true"></span>LinkedIn</a></li>
			<?php endif; ?>
		</ul>
	</div>
</article>
