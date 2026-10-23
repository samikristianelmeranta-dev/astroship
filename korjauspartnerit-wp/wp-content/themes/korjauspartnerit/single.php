<?php
/**
 * Yksittäinen artikkeli.
 */
defined( 'ABSPATH' ) || exit;
get_header();
while ( have_posts() ) :
	the_post();
	$cats = get_the_category();
	$cats = array_filter( $cats, function ( $c ) {
		return 'ajankohtaista-footer' !== $c->slug;
	} );
	$kicker = '<time datetime="' . esc_attr( get_the_date( 'c' ) ) . '">' . esc_html( get_the_date() ) . '</time>';
	if ( $cats ) {
		$kicker .= ' · ' . esc_html( implode( ', ', wp_list_pluck( $cats, 'name' ) ) );
	}
	kp_page_hero( array(
		'title'  => get_the_title(),
		'kicker' => $kicker,
		'lead'   => get_post_meta( get_the_ID(), 'kp_ingressi', true ),
		'style'  => 'hiekka',
	) );
	?>
	<div class="kp-osio kp-osio--sisalto kp-osio--artikkeli">
		<article <?php post_class( 'kp-kehys kp-sisalto' ); ?>>
			<?php if ( kp_t_thumb() ) : ?>
				<figure class="kp-artikkelikuva"><?php the_post_thumbnail( 'large', array( 'loading' => 'eager' ) ); ?></figure>
			<?php endif; ?>
			<?php the_content(); ?>
			<?php wp_link_pages(); ?>

			<?php $tags = get_the_tags(); ?>
			<?php if ( $tags ) : ?>
				<p class="kp-avainsanat">
					<?php foreach ( $tags as $t ) : ?>
						<a class="kp-chip" href="<?php echo esc_url( get_tag_link( $t ) ); ?>">#<?php echo esc_html( $t->name ); ?></a>
					<?php endforeach; ?>
				</p>
			<?php endif; ?>

			<div class="kp-jaa">
				<span>Jaa artikkeli:</span>
				<a href="<?php echo esc_url( 'https://www.linkedin.com/sharing/share-offsite/?url=' . rawurlencode( get_permalink() ) ); ?>" target="_blank" rel="noopener"><?php echo kp_icon( 'linkedin' ); // phpcs:ignore ?><span class="screen-reader-text">LinkedIn</span></a>
				<a href="<?php echo esc_url( 'https://www.facebook.com/sharer/sharer.php?u=' . rawurlencode( get_permalink() ) ); ?>" target="_blank" rel="noopener"><?php echo kp_icon( 'facebook' ); // phpcs:ignore ?><span class="screen-reader-text">Facebook</span></a>
				<a href="<?php echo esc_url( 'mailto:?subject=' . rawurlencode( get_the_title() ) . '&body=' . rawurlencode( get_permalink() ) ); ?>"><?php echo kp_icon( 'email' ); // phpcs:ignore ?><span class="screen-reader-text">Sähköposti</span></a>
			</div>
		</article>
	</div>
	<?php
	kp_after_content();

	$related = get_posts( array(
		'numberposts'  => 3,
		'post__not_in' => array( get_the_ID() ),
		'category__in' => wp_list_pluck( $cats, 'term_id' ),
	) );
	if ( $related && function_exists( 'kp_get_template' ) ) :
		?>
		<section class="kp-osio kp-osio--hiekka">
			<div class="kp-kehys kp-kehys--levea">
				<h2 class="kp-osio__otsikko">Lue myös</h2>
				<div class="kp-kortisto kp-kortisto--artikkelit">
					<?php
					foreach ( $related as $r ) {
						echo kp_get_template( 'artikkeli-kortti', array( 'post' => $r ) ); // phpcs:ignore
					}
					?>
				</div>
			</div>
		</section>
		<?php
	endif;
endwhile;
get_template_part( 'template-parts/cta' );
get_footer();
