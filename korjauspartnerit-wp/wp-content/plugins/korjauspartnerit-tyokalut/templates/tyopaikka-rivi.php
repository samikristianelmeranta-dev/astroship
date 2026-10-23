<?php
/**
 * Työpaikkailmoitus listauksessa.
 *
 * @var WP_Post $post
 */
defined( 'ABSPATH' ) || exit;
$sijainti = get_post_meta( $post->ID, 'kp_sijainti', true );
$tyosuhde = get_post_meta( $post->ID, 'kp_tyosuhde', true );
$paattyy  = get_post_meta( $post->ID, 'kp_haku_paattyy', true );
?>
<a class="kp-tyopaikka" href="<?php echo esc_url( get_permalink( $post ) ); ?>">
	<span class="kp-tyopaikka__otsikko"><?php echo esc_html( get_the_title( $post ) ); ?></span>
	<span class="kp-tyopaikka__tiedot">
		<?php if ( $sijainti ) : ?><span><?php echo esc_html( $sijainti ); ?></span><?php endif; ?>
		<?php if ( $tyosuhde ) : ?><span><?php echo esc_html( $tyosuhde ); ?></span><?php endif; ?>
		<span><?php echo $paattyy ? esc_html( 'Haku päättyy ' . date_i18n( 'j.n.Y', strtotime( $paattyy ) ) ) : 'Jatkuva haku'; ?></span>
	</span>
	<span class="kp-nuolilinkki" aria-hidden="true">Katso ilmoitus</span>
</a>
