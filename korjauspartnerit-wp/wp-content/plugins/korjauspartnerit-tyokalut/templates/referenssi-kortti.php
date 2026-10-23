<?php
/**
 * Referenssikortti.
 *
 * @var WP_Post $post
 */
defined( 'ABSPATH' ) || exit;
$types = get_the_terms( $post, 'referenssityyppi' );
$slugs = ( $types && ! is_wp_error( $types ) ) ? wp_list_pluck( $types, 'slug' ) : array();
$names = ( $types && ! is_wp_error( $types ) ) ? wp_list_pluck( array_filter( $types, function ( $t ) { return $t->parent; } ), 'name' ) : array();
$kohde = get_post_meta( $post->ID, 'kohde', true );
?>
<a class="kp-kortti-linkki kp-kortti-linkki--referenssi" data-arvot="<?php echo esc_attr( implode( ' ', $slugs ) ); ?>" href="<?php echo esc_url( get_permalink( $post ) ); ?>">
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
		<?php if ( $names || $kohde ) : ?>
			<span class="kp-kortti-linkki__ylaotsikko"><?php echo esc_html( $kohde ? $kohde : implode( ', ', $names ) ); ?></span>
		<?php endif; ?>
		<span class="kp-kortti-linkki__otsikko"><?php echo esc_html( get_the_title( $post ) ); ?></span>
	</span>
</a>
