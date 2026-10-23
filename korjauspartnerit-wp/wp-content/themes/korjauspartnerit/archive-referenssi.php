<?php
/**
 * Referenssit-listaus suodattimella. Toimii myös hanketyypin arkistona.
 */
defined( 'ABSPATH' ) || exit;
get_header();
$is_type = is_tax( 'referenssityyppi' );
kp_page_hero( array(
	'title' => $is_type ? single_term_title( '', false ) : 'Referenssit',
	'lead'  => $is_type && term_description() ? wp_strip_all_tags( term_description() ) : 'Valikoima toteutettuja hankkeita taloyhtiöille, liike- ja toimitiloille sekä teollisuudelle.',
) );
?>
<div class="kp-osio">
	<div class="kp-kehys kp-kehys--levea">
		<?php
		if ( function_exists( 'kp_sc_referenssit' ) ) {
			$html = kp_sc_referenssit( array( 'suodatin' => '1', 'tyyppi' => $is_type ? get_queried_object()->slug : '' ) );
			echo $html ? $html : '<p class="kp-tyhja">Ei referenssejä.</p>'; // phpcs:ignore
		}
		?>
	</div>
</div>
<?php
get_template_part( 'template-parts/cta' );
get_footer();
