<?php
/**
 * Sivun yläosa. Ks. kp_page_hero().
 *
 * @var array $args
 */
defined( 'ABSPATH' ) || exit;
$style = $args['style'];
if ( 'kuva' === $style && ! $args['image_id'] ) {
	$style = 'turkoosi';
}
?>
<section class="kp-hero kp-hero--<?php echo esc_attr( $style ); ?>">
	<?php if ( 'kuva' === $style ) : ?>
		<div class="kp-hero__kuva">
			<?php echo wp_get_attachment_image( $args['image_id'], 'kp-hero', false, array( 'loading' => 'eager', 'fetchpriority' => 'high', 'alt' => '', 'sizes' => '100vw' ) ); ?>
		</div>
	<?php endif; ?>
	<div class="kp-hero__sisus kp-kehys">
		<?php if ( $args['crumbs'] ) { kp_breadcrumbs(); } ?>
		<?php if ( $args['kicker'] ) : ?>
			<p class="kp-hero__ylaotsikko"><?php echo wp_kses_post( $args['kicker'] ); ?></p>
		<?php endif; ?>
		<h1 class="kp-hero__otsikko"><?php echo wp_kses_post( $args['title'] ); ?></h1>
		<?php if ( $args['lead'] ) : ?>
			<p class="kp-hero__ingressi"><?php echo wp_kses_post( $args['lead'] ); ?></p>
		<?php endif; ?>
		<?php if ( $args['buttons'] ) : ?>
			<div class="kp-hero__painikkeet">
				<?php foreach ( $args['buttons'] as $i => $b ) : ?>
					<?php
					$cls = $b[2] ?? '';
					if ( ! $cls ) {
						$cls = ( 'hiekka' === $style || 'ei' === $style ) ? ( $i ? 'kp-button--reuna' : '' ) : ( $i ? 'kp-button--reuna-valkoinen' : 'kp-button--valkoinen' );
					}
					?>
					<a class="kp-button <?php echo esc_attr( $cls ); ?>" href="<?php echo esc_url( $b[1] ); ?>"><?php echo esc_html( $b[0] ); ?></a>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>
		<?php echo $args['extra']; // phpcs:ignore WordPress.Security.EscapeOutput ?>
	</div>
</section>
