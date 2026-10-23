<?php
/**
 * Avoimet työpaikat.
 */
defined( 'ABSPATH' ) || exit;
get_header();
kp_page_hero( array(
	'title' => 'Avoimet työpaikat',
	'lead'  => 'Korjauspartnereilla työntekijät ovat kaiken toimintamme ytimessä. Tule mukaan ammattitaitoiseen ja tiiviiseen yhteisöömme!',
) );
?>
<div class="kp-osio">
	<div class="kp-kehys">
		<?php echo function_exists( 'kp_sc_tyopaikat' ) ? kp_sc_tyopaikat() : ''; // phpcs:ignore ?>
	</div>
</div>
<?php
get_footer();
