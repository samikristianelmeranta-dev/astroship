<?php
/**
 * Hakulomake.
 *
 * @package sami-elmeranta
 */

$se_id = wp_unique_id( 'haku-' );
?>
<form role="search" method="get" action="<?php echo esc_url( home_url( '/' ) ); ?>" class="flex gap-2">
	<label for="<?php echo esc_attr( $se_id ); ?>" class="sr-only">Hae sivustolta</label>
	<input id="<?php echo esc_attr( $se_id ); ?>" type="search" name="s" value="<?php echo esc_attr( get_search_query() ); ?>" placeholder="Hae…" maxlength="100" class="block w-full rounded-full border-0 bg-mist px-5 py-3 ring-1 ring-line focus:outline-none focus:ring-2 focus:ring-brand">
	<button type="submit" class="btn-primary !px-4"><?php se_the_icon( 'lucide:search' ); ?><span class="sr-only">Hae</span></button>
</form>
