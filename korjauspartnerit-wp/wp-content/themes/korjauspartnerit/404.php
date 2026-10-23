<?php
/**
 * Sivua ei löytynyt.
 */
defined( 'ABSPATH' ) || exit;
get_header();
kp_page_hero( array(
	'title'   => 'Sivua ei löytynyt',
	'lead'    => 'Etsimääsi sivua ei ole tai se on siirtynyt. Kokeile hakua tai siirry etusivulle.',
	'crumbs'  => false,
	'buttons' => array( array( 'Etusivulle', home_url( '/' ), '' ), array( 'Yhteystiedot', home_url( '/yhteystiedot/' ), '' ) ),
	'extra'   => '<form role="search" method="get" class="kp-pikahaku__lomake kp-pikahaku__lomake--hero" action="' . esc_url( home_url( '/' ) ) . '"><label class="screen-reader-text" for="kp-404-haku">Hae</label><input id="kp-404-haku" type="search" name="s" placeholder="Hae sivustolta…"><button class="kp-button kp-button--valkoinen" type="submit">Hae</button></form>',
) );
get_footer();
