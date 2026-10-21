<?php
/**
 * Template Name: Työelämä & yrittäjyys
 *
 * @package sami-elmeranta
 */

get_header();
the_post();

se_page_hero( 'Työelämä & yrittäjyys', 'Kassalta johtoryhmään', 'Työurani alkoi ruokavaraston kassalta ja jatkui rautakaupan myyjänä, kokoonpanolinjalla ja ravintola-alalla. Nykyään johdan myyntiä ja toimin osakkaana sekä freelancerina.' );
?>

<section class="py-12" aria-labelledby="ura-title">
	<div class="container-site max-w-4xl">
		<?php se_section_head( array( 'id' => 'ura-title', 'eyebrow' => 'Työura', 'title' => 'Kokemusta myynnistä, johtamisesta ja yrittämisestä' ) ); ?>
		<ol class="relative mt-14 space-y-10 before:absolute before:inset-y-2 before:left-[0.6rem] before:w-0.5 before:bg-gradient-to-b before:from-brand before:via-brand-light before:to-line sm:before:left-[9.6rem]">
			<?php foreach ( se_content( 'career' ) as $c ) : ?>
				<li class="relative grid gap-2 pl-10 sm:grid-cols-[8rem_1fr] sm:gap-10 sm:pl-0" data-reveal>
					<span class="absolute left-0 top-1.5 size-5 rounded-full border-4 border-white bg-brand shadow sm:left-[9rem]" aria-hidden="true"></span>
					<p class="text-sm font-semibold text-muted sm:pt-1 sm:text-right"><?php echo esc_html( $c['period'] ); ?></p>
					<div class="sm:pl-10">
						<h3 class="text-xl font-bold"><?php echo esc_html( $c['title'] ); ?></h3>
						<p class="font-semibold text-brand-dark"><?php echo esc_html( $c['org'] ); ?></p>
						<p class="mt-2 text-muted"><?php echo esc_html( $c['text'] ); ?></p>
					</div>
				</li>
			<?php endforeach; ?>
		</ol>
		<p class="mt-14 rounded-3xl bg-mist p-6 text-lg text-muted" data-reveal>Edellä mainittujen lisäksi minulla on ollut useita osakasrooleja ja asiantuntijapositioita eri yrityksissä. Nykyään toimin edelleen osakkaana yrityksessä ja teen freelancerina erilaisia asiantuntijatöitä.</p>
	</div>
</section>

<?php get_template_part( 'template-parts/page-extra' ); ?>

<section class="bg-mist py-24" aria-labelledby="kollegat-title">
	<div class="container-site">
		<?php se_section_head( array( 'id' => 'kollegat-title', 'eyebrow' => 'Suosittelut', 'title' => 'Kollegoiden sanoin' ) ); ?>
		<div class="mt-12"><?php se_testimonials( se_content( 'workTestimonials' ) ); ?></div>
	</div>
</section>

<?php
get_footer();
