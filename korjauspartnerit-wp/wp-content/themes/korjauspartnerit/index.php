<?php
/**
 * Artikkelilistaus (Ajankohtaista), kategoriat, avainsanat ja muut arkistot.
 */
defined( 'ABSPATH' ) || exit;
get_header();

$lead = '';
if ( is_home() && get_option( 'page_for_posts' ) ) {
	$lead = get_post_meta( get_option( 'page_for_posts' ), 'kp_ingressi', true );
	if ( ! $lead ) {
		$lead = 'Korjauspartnereiden ja alan kuulumisia, asiantuntija-artikkeleita ja asiakastarinoita.';
	}
} elseif ( is_archive() ) {
	$lead = wp_strip_all_tags( get_the_archive_description() );
}
kp_page_hero( array( 'lead' => $lead, 'style' => 'turkoosi' ) );

$cats = get_categories( array( 'hide_empty' => true, 'exclude' => array( get_option( 'default_category' ) ) ) );
$cats = array_filter( $cats, function ( $c ) {
	return ! in_array( $c->slug, array( 'ajankohtaista-footer' ), true );
} );
?>
<div class="kp-osio">
	<div class="kp-kehys kp-kehys--levea">
		<?php if ( ( is_home() || is_category() ) && count( $cats ) > 1 ) : ?>
			<nav class="kp-suodatin" aria-label="Kategoriat">
				<?php $blog_url = get_option( 'page_for_posts' ) ? get_permalink( get_option( 'page_for_posts' ) ) : home_url( '/' ); ?>
				<a class="kp-chip<?php echo is_home() ? ' is-active' : ''; ?>" href="<?php echo esc_url( $blog_url ); ?>">Kaikki</a>
				<?php foreach ( $cats as $c ) : ?>
					<a class="kp-chip<?php echo is_category( $c->term_id ) ? ' is-active' : ''; ?>" href="<?php echo esc_url( get_category_link( $c ) ); ?>"><?php echo esc_html( $c->name ); ?></a>
				<?php endforeach; ?>
			</nav>
		<?php endif; ?>

		<?php if ( have_posts() ) : ?>
			<div class="kp-kortisto kp-kortisto--artikkelit">
				<?php
				while ( have_posts() ) :
					the_post();
					if ( function_exists( 'kp_get_template' ) ) {
						echo kp_get_template( 'artikkeli-kortti', array( 'post' => get_post() ) ); // phpcs:ignore
					} else {
						printf( '<a class="kp-kortti-linkki" href="%s"><span class="kp-kortti-linkki__sisalto"><span class="kp-kortti-linkki__otsikko">%s</span></span></a>', esc_url( get_permalink() ), esc_html( get_the_title() ) );
					}
				endwhile;
				?>
			</div>
			<?php kp_pagination(); ?>
		<?php else : ?>
			<p class="kp-tyhja">Täällä ei ole vielä sisältöä.</p>
		<?php endif; ?>
	</div>
</div>
<?php
get_template_part( 'template-parts/cta' );
get_footer();
