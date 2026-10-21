<?php
/**
 * Tavallinen sivu (esim. tietosuojaseloste).
 *
 * @package sami-elmeranta
 */

get_header();
the_post();

se_page_hero( '', get_the_title(), has_excerpt() ? get_the_excerpt() : '' );
?>
<div class="container-site max-w-3xl">
	<?php if ( has_post_thumbnail() ) : ?>
		<?php the_post_thumbnail( 'large', array( 'class' => 'mb-10 w-full rounded-[2rem] object-cover shadow-xl' ) ); ?>
	<?php endif; ?>
	<div class="entry-content prose prose-lg max-w-none">
		<?php
		the_content();
		wp_link_pages();
		?>
	</div>
</div>
<?php
get_footer();
