<?php
/**
 * Sivupohjien valinnainen lisäsisältö: näytetään vain, jos sivun
 * mukautettu kenttä "nayta_sisalto" on 1. Näin vanhan WordPress-sivun
 * sisältö (vanhat koodit, popupit) ei näy uudessa ulkoasussa vahingossa.
 *
 * @package sami-elmeranta
 */

if ( '1' !== get_post_meta( get_the_ID(), 'nayta_sisalto', true ) || '' === trim( get_the_content() ) ) {
	return;
}
?>
<section class="py-12">
	<div class="container-site max-w-3xl">
		<div class="entry-content prose prose-lg max-w-none" data-reveal>
			<?php the_content(); ?>
		</div>
	</div>
</section>
