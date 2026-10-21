<?php
/**
 * Toistuvat osiot.
 *
 * @package sami-elmeranta
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Osion otsikko.
 *
 * @param array $a eyebrow, title, lead, id, dark, center.
 */
function se_section_head( $a ) {
	$a = wp_parse_args( $a, array( 'eyebrow' => '', 'title' => '', 'lead' => '', 'id' => '', 'dark' => false, 'center' => false ) );
	?>
	<div class="max-w-3xl<?php echo $a['center'] ? ' mx-auto text-center' : ''; ?>" data-reveal>
		<?php if ( $a['eyebrow'] ) : ?>
			<p class="eyebrow<?php echo $a['dark'] ? ' !text-brand-light' : ''; ?>"><?php echo esc_html( $a['eyebrow'] ); ?></p>
		<?php endif; ?>
		<h2<?php echo $a['id'] ? ' id="' . esc_attr( $a['id'] ) . '"' : ''; ?> class="mt-3 text-4xl font-bold sm:text-5xl<?php echo $a['dark'] ? ' text-white' : ''; ?>"><?php echo esc_html( $a['title'] ); ?></h2>
		<?php if ( $a['lead'] ) : ?>
			<p class="mt-5 text-lg <?php echo $a['dark'] ? 'text-white/70' : 'text-muted'; ?>"><?php echo esc_html( $a['lead'] ); ?></p>
		<?php endif; ?>
	</div>
	<?php
}

/**
 * Alasivun hero.
 *
 * @param string $eyebrow Yläotsikko.
 * @param string $title   Otsikko.
 * @param string $lead    Johdanto.
 */
function se_page_hero( $eyebrow, $title, $lead = '' ) {
	?>
	<section class="relative -mt-20 overflow-hidden pb-16 pt-32 sm:pt-40">
		<div class="pointer-events-none absolute inset-0 -z-10" aria-hidden="true">
			<div class="absolute -top-32 right-[-10%] size-[32rem] rounded-full bg-brand/15 blur-3xl animate-blob"></div>
			<div class="absolute top-20 left-[-10%] size-[26rem] rounded-full bg-ink-soft/10 blur-3xl animate-blob blob-delay"></div>
		</div>
		<div class="container-site">
			<?php if ( $eyebrow ) : ?>
				<p class="eyebrow hero-word"><?php echo esc_html( $eyebrow ); ?></p>
			<?php endif; ?>
			<h1 class="hero-word mt-4 max-w-4xl text-5xl font-extrabold leading-[1.05] sm:text-6xl lg:text-7xl"><?php echo esc_html( $title ); ?></h1>
			<?php if ( $lead ) : ?>
				<p class="hero-word mt-6 max-w-2xl text-xl text-muted"><?php echo esc_html( $lead ); ?></p>
			<?php endif; ?>
		</div>
	</section>
	<?php
}

/**
 * Suosittelukortit.
 *
 * @param array $items quote, name, role.
 */
function se_testimonials( $items ) {
	echo '<div class="grid gap-6 md:grid-cols-2">';
	foreach ( $items as $i => $t ) {
		?>
		<figure class="card flex flex-col" data-reveal data-delay="<?php echo esc_attr( ( $i % 2 ) + 1 ); ?>">
			<?php se_the_icon( 'lucide:quote', 'size-9 text-brand' ); ?>
			<blockquote class="mt-4 flex-1 text-lg leading-relaxed"><p><?php echo esc_html( $t['quote'] ); ?></p></blockquote>
			<figcaption class="mt-6 flex items-center gap-3">
				<span class="grid size-11 place-items-center rounded-full bg-brand-soft font-display font-bold text-brand-dark" aria-hidden="true"><?php echo esc_html( se_initials( $t['name'] ) ); ?></span>
				<span>
					<span class="block font-semibold"><?php echo esc_html( $t['name'] ); ?></span>
					<span class="block text-sm text-muted"><?php echo esc_html( $t['role'] ); ?></span>
				</span>
			</figcaption>
		</figure>
		<?php
	}
	echo '</div>';
}

/**
 * Artikkelikortti (loopin sisällä).
 *
 * @param int $delay Animaation viive.
 */
function se_post_card( $delay = 1 ) {
	$cats = get_the_category();
	?>
	<article class="card group flex flex-col" data-reveal data-delay="<?php echo esc_attr( $delay ); ?>">
		<?php if ( has_post_thumbnail() ) : ?>
			<div class="-mx-7 -mt-7 mb-6 overflow-hidden rounded-t-3xl">
				<?php the_post_thumbnail( 'medium_large', array( 'class' => 'aspect-[16/9] w-full object-cover transition duration-700 group-hover:scale-105', 'loading' => 'lazy' ) ); ?>
			</div>
		<?php endif; ?>
		<div class="flex items-center gap-3 text-sm text-muted">
			<?php if ( $cats ) : ?>
				<span class="rounded-full bg-brand-soft px-3 py-1 font-semibold text-brand-dark"><?php echo esc_html( $cats[0]->name ); ?></span>
			<?php endif; ?>
			<time datetime="<?php echo esc_attr( get_the_date( 'c' ) ); ?>"><?php echo esc_html( get_the_date() ); ?></time>
		</div>
		<h3 class="mt-5 text-2xl font-bold leading-snug">
			<a href="<?php the_permalink(); ?>" class="after:absolute after:inset-0 after:rounded-3xl group-hover:text-brand-dark"><?php the_title(); ?></a>
		</h3>
		<p class="mt-3 line-clamp-3 flex-1 text-muted"><?php echo esc_html( wp_strip_all_tags( get_the_excerpt() ) ); ?></p>
		<p class="mt-6 flex items-center justify-between text-sm font-semibold">
			<span class="text-muted"><?php echo esc_html( se_reading_time() ); ?> min lukuaika</span>
			<span class="inline-flex items-center gap-1 text-brand-dark">Lue <?php se_the_icon( 'lucide:arrow-right', 'size-4 transition-transform duration-300 group-hover:translate-x-1' ); ?></span>
		</p>
	</article>
	<?php
}

/**
 * Uutiskirje-CTA.
 */
function se_newsletter() {
	?>
	<section id="uutiskirje" aria-labelledby="uutiskirje-title" class="py-24">
		<div class="container-site">
			<div class="relative overflow-hidden rounded-[2.5rem] bg-gradient-to-br from-brand via-liila to-ink-soft px-8 py-16 text-white sm:px-16" data-reveal="zoom">
				<div class="pointer-events-none absolute -right-16 -top-16 size-72 rounded-full bg-white/10 blur-2xl animate-blob" aria-hidden="true"></div>
				<div class="pointer-events-none absolute -bottom-24 left-1/3 size-80 rounded-full bg-brand-light/30 blur-3xl animate-blob blob-delay" aria-hidden="true"></div>
				<div class="relative grid items-center gap-10 lg:grid-cols-[1.5fr_1fr]">
					<div>
						<p class="text-sm font-semibold uppercase tracking-[0.2em] text-white/70">Sisäpiiri</p>
						<h2 id="uutiskirje-title" class="mt-3 text-4xl font-bold sm:text-5xl">Liity sisäpiiriin</h2>
						<p class="mt-4 max-w-xl text-lg text-white/80">Saat minulta 1–2 sähköpostia kuukaudessa yhteiskunnallisista asioista – selkeästi ja ilman poliittista jargonia. En roskapostita, enkä luovuta tietojasi eteenpäin.</p>
					</div>
					<div class="flex flex-col gap-3 sm:flex-row lg:flex-col lg:items-stretch">
						<?php if ( se_opt( 'newsletter_url' ) ) : ?>
							<a href="<?php echo esc_url( se_opt( 'newsletter_url' ) ); ?>" target="_blank" rel="noopener noreferrer" class="btn bg-white text-ink hover:-translate-y-0.5 hover:shadow-2xl"><?php se_the_icon( 'lucide:mail-plus' ); ?> Tilaa uutiskirje</a>
						<?php endif; ?>
						<?php if ( se_opt( 'whatsapp_url' ) ) : ?>
							<a href="<?php echo esc_url( se_opt( 'whatsapp_url' ) ); ?>" target="_blank" rel="noopener noreferrer" class="btn-ghost"><?php se_the_icon( 'simple-icons:whatsapp' ); ?> Laita viestiä WhatsAppissa</a>
						<?php endif; ?>
					</div>
				</div>
			</div>
		</div>
	</section>
	<?php
}
