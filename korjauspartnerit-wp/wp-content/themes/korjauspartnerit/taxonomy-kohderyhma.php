<?php
/**
 * Palvelut-listaus, ryhmiteltynä palvelualueittain. Suodatus asiakasryhmän mukaan.
 */
defined( 'ABSPATH' ) || exit;
get_header();

$current = isset( $_GET['asiakasryhma'] ) ? sanitize_title( wp_unslash( $_GET['asiakasryhma'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
if ( is_tax( 'kohderyhma' ) ) {
	$current = get_queried_object()->slug;
}
$groups = get_terms( array( 'taxonomy' => 'kohderyhma', 'hide_empty' => true ) );

kp_page_hero( array(
	'title' => is_tax() ? single_term_title( '', false ) : 'Palvelut',
	'lead'  => is_tax() && term_description() ? wp_strip_all_tags( term_description() ) : 'Korjausrakentamisen, kiinteistöjen ja teollisuuden asiantuntijapalvelut kuntotutkimuksista suunnitteluun, rakennuttamiseen ja valvontaan.',
) );
?>
<div class="kp-osio">
	<div class="kp-kehys kp-kehys--levea">
		<?php if ( $groups && ! is_wp_error( $groups ) && ! is_tax( 'palvelualue' ) ) : ?>
			<nav class="kp-suodatin" aria-label="Asiakasryhmä">
				<a class="kp-chip<?php echo $current ? '' : ' is-active'; ?>" href="<?php echo esc_url( get_post_type_archive_link( 'palvelu' ) ); ?>">Kaikki palvelut</a>
				<?php foreach ( $groups as $g ) : ?>
					<a class="kp-chip<?php echo $current === $g->slug ? ' is-active' : ''; ?>" href="<?php echo esc_url( add_query_arg( 'asiakasryhma', $g->slug, get_post_type_archive_link( 'palvelu' ) ) ); ?>"><?php echo esc_html( $g->name ); ?></a>
				<?php endforeach; ?>
			</nav>
		<?php endif; ?>
		<?php
		if ( function_exists( 'kp_sc_palvelut' ) ) {
			$atts = array( 'ryhmittele' => '1', 'asiakasryhma' => $current );
			if ( is_tax( 'palvelualue' ) ) {
				$atts = array( 'palvelualue' => get_queried_object()->slug );
			}
			$html = kp_sc_palvelut( $atts );
			echo $html ? $html : '<p class="kp-tyhja">Ei palveluita valituilla ehdoilla.</p>'; // phpcs:ignore
		}
		?>
	</div>
</div>
<?php
get_template_part( 'template-parts/cta' );
get_footer();
