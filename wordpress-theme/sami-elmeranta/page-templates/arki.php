<?php
/**
 * Template Name: Arki & vapaa-aika
 *
 * Galleria: lisää kuvat sivulle Classic Editorin "Lisää media → Luo galleria"
 * -toiminnolla. Ensimmäisen gallerian kuvat näytetään tyylitellysti.
 *
 * @package sami-elmeranta
 */

get_header();
the_post();

// Kuvat sivun ensimmäisestä galleriasta tai sivulle liitetyistä kuvista.
$gallery_ids = array();
$gallery     = get_post_gallery( get_the_ID(), false );
if ( ! empty( $gallery['ids'] ) ) {
	$gallery_ids = array_map( 'absint', explode( ',', $gallery['ids'] ) );
} else {
	$gallery_ids = get_posts( array(
		'post_parent'    => get_the_ID(),
		'post_type'      => 'attachment',
		'post_mime_type' => 'image',
		'posts_per_page' => 12,
		'fields'         => 'ids',
		'orderby'        => 'menu_order ID',
		'order'          => 'ASC',
	) );
}

se_page_hero( 'Arki & vapaa-aika', 'Elämä lasten ehdoilla', 'Urheilua, yhdistystoimintaa ja yhteisöllisyyttä.' );
?>

<section class="py-12">
	<div class="container-site grid items-start gap-6 lg:grid-cols-[1fr_1fr_0.8fr]">
		<article class="card" data-reveal="left">
			<span class="grid size-12 place-items-center rounded-2xl bg-brand-soft text-brand-dark"><?php se_the_icon( 'lucide:trophy', 'size-6' ); ?></span>
			<h2 class="mt-5 text-3xl font-bold">Vapaa-aika</h2>
			<div class="mt-4 space-y-4 text-muted">
				<p>Arkeni on aivan tavallista perhe-elämää lasten ja läheisten kanssa, ja vapaa-aikani kuluu pitkälti lasten ehdoilla. Urheilu on ollut minulle aina suuri intohimo – minut löytääkin usein futiskentältä, padelin parista tai golfkentältä.</p>
				<p>Olen ollut aktiivisesti mukana useissa seuroissa niin perustajajäsenenä kuin hallitustasolla. Valmennan tällä hetkellä 2014-syntyneiden poikien jalkapallojoukkuetta.</p>
			</div>
		</article>
		<article class="card" data-reveal>
			<span class="grid size-12 place-items-center rounded-2xl bg-brand-soft text-brand-dark"><?php se_the_icon( 'lucide:hand-heart', 'size-6' ); ?></span>
			<h2 class="mt-5 text-3xl font-bold">Yhdistys- ja vapaaehtoistyö</h2>
			<div class="mt-4 space-y-4 text-muted">
				<p>Kotiseutu on minulle tärkeä, joten olen pitkään osallistunut Piikkiö Päivän järjestelyihin. Olen toiminut Mannerheimin Lastensuojeluliiton paikallistehtävissä ja olen mukana Lions Clubin sekä Piikkiön nuorison tuki ry:n toiminnassa.</p>
				<p>Joulu on minulle tärkeä auttamisen ajankohta. Olen vuosia järjestänyt ruokalahjoituskeräyksiä ja vuonna 2022 järjestin isomman tapahtuman yksinäisille ja vähävaraisille Kaarinassa. Olen pitänyt vapaaehtoisena pienten lasten liikuntakerhoa 3–7-vuotiaille.</p>
			</div>
		</article>
		<figure class="relative hidden lg:block" data-reveal="right">
			<div class="absolute -inset-3 rotate-3 rounded-[2rem] bg-brand-soft" aria-hidden="true"></div>
			<img src="<?php echo esc_url( se_image_url( 'portrait_alt', 'sami-arki-600.webp', 'medium_large' ) ); ?>" alt="<?php echo esc_attr( se_opt( 'person_name' ) ); ?>" width="600" height="900" loading="lazy" decoding="async" class="relative aspect-[3/4] w-full rounded-[1.75rem] object-cover object-top shadow-xl">
		</figure>
	</div>
</section>

<?php if ( $gallery_ids ) : ?>
	<section class="py-20" aria-labelledby="kuvat-title">
		<div class="container-site">
			<?php se_section_head( array( 'id' => 'kuvat-title', 'eyebrow' => 'Galleria', 'title' => 'Muutamia kuvia arjestani' ) ); ?>
			<ul class="mt-12 columns-1 gap-5 sm:columns-2 lg:columns-4">
				<?php foreach ( $gallery_ids as $i => $id ) : ?>
					<li class="mb-5 break-inside-avoid" data-reveal="zoom" data-delay="<?php echo esc_attr( ( $i % 4 ) + 1 ); ?>">
						<figure class="group overflow-hidden rounded-3xl bg-mist ring-1 ring-line">
							<?php echo wp_get_attachment_image( $id, 'medium_large', false, array( 'class' => 'w-full object-cover transition duration-700 group-hover:scale-105', 'loading' => 'lazy' ) ); ?>
							<?php $caption = wp_get_attachment_caption( $id ); ?>
							<?php if ( $caption ) : ?>
								<figcaption class="p-4 text-sm text-muted"><?php echo esc_html( $caption ); ?></figcaption>
							<?php endif; ?>
						</figure>
					</li>
				<?php endforeach; ?>
			</ul>
		</div>
	</section>
<?php endif; ?>

<?php get_template_part( 'template-parts/page-extra' ); ?>

<section class="py-12" aria-labelledby="kirjoitan-title">
	<div class="container-site">
		<div class="card grid gap-8 bg-mist lg:grid-cols-[1.4fr_1fr] lg:items-center" data-reveal>
			<div>
				<h2 id="kirjoitan-title" class="text-3xl font-bold">Vaikutan myös kirjoittamalla</h2>
				<p class="mt-4 text-muted">Olet mahdollisesti törmännyt minuun sosiaalisessa mediassa? Kirjoitan paljon mielipidekirjoituksia ja otan kantaa yhteiskunnallisiin asioihin.</p>
				<ul class="mt-5 flex flex-wrap gap-3">
					<?php foreach ( se_writing() as $w ) : ?>
						<li><a href="<?php echo esc_url( $w[1] ); ?>" target="_blank" rel="noopener noreferrer" class="btn-light !py-2"><?php echo esc_html( $w[0] ); ?> <?php se_the_icon( 'lucide:external-link', 'size-4' ); ?></a></li>
					<?php endforeach; ?>
				</ul>
			</div>
			<div>
				<p class="font-semibold">Seuraa minua somessa</p>
				<?php se_the_socials( false, 'mt-3' ); ?>
			</div>
		</div>
	</div>
</section>

<?php
get_footer();
