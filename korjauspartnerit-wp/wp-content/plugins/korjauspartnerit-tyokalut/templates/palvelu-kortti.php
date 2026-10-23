<?php
/**
 * Palvelukortti.
 *
 * @var WP_Post $post
 */
defined( 'ABSPATH' ) || exit;
?>
<a class="kp-kortti-linkki kp-kortti-linkki--palvelu" href="<?php echo esc_url( get_permalink( $post ) ); ?>">
	<?php if ( kp_thumb_id( $post ) ) : ?>
		<span class="kp-kortti-linkki__kuva"><?php echo wp_get_attachment_image( kp_thumb_id( $post ), 'medium_large', false, array( 'loading' => 'lazy', 'alt' => '' ) ); ?></span>
	<?php endif; ?>
	<span class="kp-kortti-linkki__sisalto">
		<span class="kp-kortti-linkki__otsikko"><?php echo esc_html( get_the_title( $post ) ); ?></span>
		<span class="kp-kortti-linkki__teksti"><?php echo esc_html( kp_excerpt( $post, 18 ) ); ?></span>
		<span class="kp-nuolilinkki" aria-hidden="true">Lue lisää</span>
	</span>
</a>
