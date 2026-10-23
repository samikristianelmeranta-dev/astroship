<?php
/**
 * Artikkelikortti (Ajankohtaista).
 *
 * @var WP_Post $post
 */
defined( 'ABSPATH' ) || exit;
?>
<a class="kp-kortti-linkki kp-kortti-linkki--artikkeli" href="<?php echo esc_url( get_permalink( $post ) ); ?>">
	<span class="kp-kortti-linkki__kuva">
		<?php
		if ( kp_thumb_id( $post ) ) {
			echo wp_get_attachment_image( kp_thumb_id( $post ), 'medium_large', false, array( 'loading' => 'lazy', 'alt' => '' ) );
		} else {
			echo '<span class="kp-kortti-linkki__paikkamerkki">' . kp_mark_svg() . '</span>'; // phpcs:ignore WordPress.Security.EscapeOutput
		}
		?>
	</span>
	<span class="kp-kortti-linkki__sisalto">
		<time class="kp-kortti-linkki__ylaotsikko" datetime="<?php echo esc_attr( get_the_date( 'c', $post ) ); ?>"><?php echo esc_html( get_the_date( 'j.n.Y', $post ) ); ?></time>
		<span class="kp-kortti-linkki__otsikko"><?php echo esc_html( get_the_title( $post ) ); ?></span>
		<span class="kp-kortti-linkki__teksti"><?php echo esc_html( kp_excerpt( $post, 20 ) ); ?></span>
	</span>
</a>
