<?php
/**
 * Blogi, arkistot ja haku.
 *
 * @package sami-elmeranta
 */

get_header();

if ( is_search() ) {
	/* translators: %s: hakusana */
	se_page_hero( 'Haku', sprintf( 'Tulokset: %s', get_search_query() ) );
} elseif ( is_archive() ) {
	se_page_hero( 'Arkisto', wp_strip_all_tags( get_the_archive_title() ), wp_strip_all_tags( get_the_archive_description() ) );
} else {
	se_page_hero( 'Blogi', 'Mielipiteet & ajatukset', 'Kirjoituksia politiikasta ja yhteiskunnallisista asioista – selkokielellä ja ilman jargonia.' );
}
?>
<section class="py-8">
	<div class="container-site">
		<?php if ( have_posts() ) : ?>
			<div class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
				<?php
				$i = 0;
				while ( have_posts() ) {
					the_post();
					se_post_card( ( $i++ % 3 ) + 1 );
				}
				?>
			</div>
			<nav class="mt-14 flex justify-center" aria-label="Sivutus">
				<?php
				echo wp_kses_post( paginate_links( array(
					'type'      => 'list',
					'prev_text' => '← Uudemmat',
					'next_text' => 'Vanhemmat →',
				) ) );
				?>
			</nav>
		<?php else : ?>
			<div class="card max-w-xl">
				<p class="text-lg">Ei tuloksia. Kokeile toista hakusanaa.</p>
				<div class="mt-6"><?php get_search_form(); ?></div>
			</div>
		<?php endif; ?>
	</div>
</section>
<?php
se_newsletter();
get_footer();
