<?php
/**
 * Etusivu.
 *
 * @package sami-elmeranta
 */

get_header();

$ribbon = array( 'Työ ja toimeentulo', 'Yrittäjyys', 'Elinvoimainen Varsinais-Suomi', 'Lapset ja nuoret', 'Vastuullinen talous', 'Turvallinen koulu', 'Vähemmän hallintoa, enemmän tuloksia' );
$stats  = array(
	array( 2021, '', 'Kaarinan valtuustossa ja hallituksessa vuodesta', false ),
	array( 3, '', 'aktiivista luottamustointa', true ),
	array( 17, '+', 'vuotta myynnin johtotehtävissä', true ),
	array( 3, '', 'lasta – tärkein tehtäväni', true ),
);
?>

<!-- HERO -->
<section class="relative -mt-20 overflow-hidden pt-20" aria-labelledby="hero-title">
	<div class="pointer-events-none absolute inset-0 -z-10" aria-hidden="true">
		<div class="absolute -top-40 right-[-15%] size-[40rem] rounded-full bg-brand/20 blur-3xl animate-blob"></div>
		<div class="absolute top-1/3 left-[-15%] size-[30rem] rounded-full bg-ink-soft/10 blur-3xl animate-blob blob-delay"></div>
		<div class="absolute bottom-0 right-1/3 size-[22rem] rounded-full bg-brand-light/20 blur-3xl animate-blob blob-delay-2"></div>
		<div class="absolute inset-0 bg-dots"></div>
	</div>

	<div class="container-site grid items-center gap-14 pb-20 pt-10 lg:grid-cols-[1.2fr_1fr] lg:pt-16">
		<div>
			<p class="hero-word inline-flex items-center gap-2 rounded-full bg-white/80 px-4 py-1.5 text-sm font-semibold ring-1 ring-line backdrop-blur">
				<span class="relative flex size-2.5">
					<span class="absolute inline-flex size-full animate-ping rounded-full bg-brand opacity-60"></span>
					<span class="relative inline-flex size-2.5 rounded-full bg-brand"></span>
				</span>
				<?php echo esc_html( se_opt( 'party_name' ) . ' · ' . se_opt( 'locality' ) ); ?>
			</p>
			<h1 id="hero-title" class="mt-6 text-5xl font-extrabold leading-[1.02] sm:text-6xl xl:text-7xl">
				<span class="hero-word block"><?php echo esc_html( se_opt( 'hero_line1' ) ); ?></span>
				<span class="hero-word block"><?php echo esc_html( se_opt( 'hero_line2' ) ); ?></span>
				<span class="hero-word block text-gradient"><?php echo esc_html( se_opt( 'hero_line3' ) ); ?></span>
			</h1>
			<p class="hero-word mt-7 max-w-xl text-xl leading-relaxed text-muted"><?php echo esc_html( se_opt( 'hero_lead' ) ); ?></p>
			<div class="hero-word mt-9 flex flex-wrap gap-3">
				<a href="<?php echo esc_url( se_page_url( 'politiikkaa' ) ); ?>" class="btn-primary">Politiikka <?php se_the_icon( 'lucide:arrow-right' ); ?></a>
				<a href="<?php echo esc_url( se_page_url( 'tyoelamayrittajyys' ) ); ?>" class="btn-light">Työelämä</a>
				<a href="#tutustu" class="btn-light">Tutustutaan</a>
			</div>
		</div>

		<div class="relative mx-auto w-full max-w-md" data-reveal="zoom">
			<div class="absolute -inset-4 rotate-3 rounded-[2.5rem] bg-gradient-to-br from-brand to-ink-soft opacity-90" aria-hidden="true"></div>
			<img src="<?php echo esc_url( se_image_url( 'portrait', 'sami-portrait.webp' ) ); ?>" alt="<?php echo esc_attr( se_opt( 'person_name' ) ); ?>" width="1000" height="1500" fetchpriority="high" decoding="async" class="relative aspect-[4/5] w-full rounded-[2rem] object-cover object-top shadow-2xl">
			<?php
			// Kuvan päällä kelluvat teemamerkit.
			$se_badges = array(
				array( 'lucide:scale', 'Tolkun poliitikko', '-right-2 top-8 sm:-right-4', 'bg-ink text-white', 'bg-white/10 text-brand-light', '' ),
				array( 'lucide:flag', 'Suomalaisen asialla', '-left-2 top-1/2 -translate-y-1/2 sm:-left-6', 'bg-white/90 ring-1 ring-line backdrop-blur', 'bg-brand-soft text-brand-dark', 'blob-delay' ),
				array( 'lucide:briefcase', 'Yrittäjyys & työpaikat', '-right-2 bottom-10 sm:-right-4', 'bg-brand text-white', 'bg-white/15 text-white', 'blob-delay-2' ),
			);
			foreach ( $se_badges as $b ) :
				?>
				<div class="absolute <?php echo esc_attr( $b[2] ); ?>">
					<div class="flex items-center gap-3 rounded-2xl px-4 py-3 shadow-xl animate-float <?php echo esc_attr( $b[3] . ' ' . $b[5] ); ?>">
						<span class="grid size-9 place-items-center rounded-xl <?php echo esc_attr( $b[4] ); ?>"><?php se_the_icon( $b[0] ); ?></span>
						<span class="font-display font-bold"><?php echo esc_html( $b[1] ); ?></span>
					</div>
				</div>
			<?php endforeach; ?>
		</div>
	</div>
</section>

<!-- RIBBON -->
<div class="relative -rotate-1 overflow-hidden bg-ink py-5 text-white" aria-hidden="true">
	<div class="flex w-max animate-marquee gap-10 whitespace-nowrap font-display text-2xl font-bold hover:[animation-play-state:paused]">
		<?php foreach ( array_merge( $ribbon, $ribbon ) as $r ) : ?>
			<span class="flex items-center gap-10"><?php echo esc_html( $r ); ?><span class="text-brand">✦</span></span>
		<?php endforeach; ?>
	</div>
</div>

<!-- STATS -->
<section aria-label="Lukuina" class="py-20">
	<div class="container-site">
		<dl class="grid grid-cols-2 gap-6 lg:grid-cols-4">
			<?php foreach ( $stats as $i => $s ) : ?>
				<div class="flex flex-col rounded-3xl bg-mist p-6 ring-1 ring-line" data-reveal data-delay="<?php echo esc_attr( $i + 1 ); ?>">
					<dt class="text-sm text-muted"><?php echo esc_html( $s[2] ); ?></dt>
					<dd class="order-first font-display text-5xl font-extrabold text-brand-dark">
						<?php if ( $s[3] ) : ?>
							<span data-count="<?php echo esc_attr( $s[0] ); ?>"><?php echo esc_html( $s[0] ); ?></span><?php echo esc_html( $s[1] ); ?>
						<?php else : ?>
							<?php echo esc_html( $s[0] ); ?>
						<?php endif; ?>
					</dd>
				</div>
			<?php endforeach; ?>
		</dl>
	</div>
</section>

<!-- ABOUT -->
<section id="tutustu" aria-labelledby="tutustu-title" class="py-16">
	<div class="container-site grid items-center gap-16 lg:grid-cols-2">
		<div data-reveal="left">
			<p class="eyebrow">Mikä mies kyseessä?</p>
			<h2 id="tutustu-title" class="mt-3 text-4xl font-bold sm:text-5xl">Isä, johtaja, yrittäjä – ja kaarinalainen</h2>
			<div class="mt-6 space-y-4 text-lg leading-relaxed text-muted">
				<p>Olen 41-vuotias kolmen lapsen isä Kaarinan Piikkiöstä. Työskentelen myynnin johdollisissa tehtävissä media-alalla ja lisäksi toimin sivutoimisena yrittäjänä. Poliittisesti olen hyvin aktiivinen, sekä toimin useassa alueellisessa yhdistyksessä vapaaehtoisena. Vapaa-aikana minuun voi törmätä jalkapallo- ja golfkentillä.</p>
				<p><strong class="text-ink">Puolueeni on <?php echo esc_html( se_opt( 'party_name' ) ); ?>.</strong> Minun tärkein tehtäväni on olla isä kolmelle ihanalle lapselle, toinen tärkeä tehtäväni on rakentaa parempaa huomista meille kaikille.</p>
			</div>
			<div class="mt-8 flex flex-wrap items-center gap-6">
				<a href="<?php echo esc_url( se_page_url( 'arkivapaa-aika' ) ); ?>" class="btn-primary">Tutustutaan lisää</a>
				<?php se_the_socials(); ?>
			</div>
		</div>
		<div class="relative mx-auto w-full max-w-md" data-reveal="right">
			<div class="absolute -inset-3 -rotate-3 rounded-[2.5rem] bg-brand-soft" aria-hidden="true"></div>
			<img src="<?php echo esc_url( se_image_url( 'portrait_alt', 'sami-arki.webp' ) ); ?>" alt="<?php echo esc_attr( se_opt( 'person_name' ) ); ?> rennosti pehmolelun kanssa" width="1000" height="1500" loading="lazy" decoding="async" class="relative aspect-[4/5] w-full rounded-[2rem] object-cover object-top shadow-2xl">
			<div class="absolute -right-5 bottom-16 flex items-center gap-3 rounded-2xl bg-white px-4 py-3 shadow-xl ring-1 ring-line animate-float">
				<span class="grid size-10 place-items-center rounded-xl bg-brand-soft text-brand-dark"><?php se_the_icon( 'lucide:trophy' ); ?></span>
				<span><span class="block text-xs font-semibold uppercase tracking-wider text-muted">Valmentaja</span><span class="block font-display font-bold">2014-syntyneet pojat</span></span>
			</div>
			<div class="absolute -left-5 top-10 flex items-center gap-3 rounded-2xl bg-ink px-4 py-3 text-white shadow-xl animate-float blob-delay">
				<span class="grid size-10 place-items-center rounded-xl bg-white/10"><?php se_the_icon( 'lucide:trending-up' ); ?></span>
				<span><span class="block text-xs font-semibold uppercase tracking-wider text-white/60">Työ</span><span class="block font-display font-bold">Aluemyyntijohtaja</span></span>
			</div>
		</div>
	</div>
</section>

<!-- VALUES -->
<section aria-labelledby="arvot-title" class="py-24">
	<div class="container-site">
		<?php
		se_section_head( array(
			'id'      => 'arvot-title',
			'eyebrow' => 'Arvot & ajatukset',
			'title'   => 'Uskon tekemisen voimaan, vastuuseen ja yhteisöllisyyteen',
			'lead'    => 'Nämä arvot ohjaavat kaikkea sitä, mitä teen – niin työssä, politiikassa kuin arjessakin. Alla muutama minulle tärkeä asia, joita edistän.',
		) );
		?>
		<div class="mt-14 grid gap-6 md:grid-cols-2 lg:grid-cols-4">
			<?php foreach ( se_content( 'values' ) as $i => $v ) : ?>
				<article class="card group" data-reveal data-delay="<?php echo esc_attr( ( $i % 4 ) + 1 ); ?>">
					<span class="grid size-12 place-items-center rounded-2xl bg-brand-soft text-brand-dark transition duration-500 group-hover:rotate-6 group-hover:bg-brand group-hover:text-white"><?php se_the_icon( $v['icon'], 'size-6' ); ?></span>
					<h3 class="mt-5 text-xl font-bold leading-snug"><?php echo esc_html( $v['title'] ); ?></h3>
					<p class="mt-3 text-muted"><?php echo esc_html( $v['text'] ); ?></p>
				</article>
			<?php endforeach; ?>
		</div>
	</div>
</section>

<!-- CHANGES -->
<section aria-labelledby="muutos-title" class="relative overflow-hidden bg-ink py-24 text-white">
	<div class="pointer-events-none absolute -left-32 top-10 size-[30rem] rounded-full bg-brand/25 blur-3xl animate-blob" aria-hidden="true"></div>
	<div class="container-site relative">
		<?php
		se_section_head( array(
			'id'      => 'muutos-title',
			'dark'    => true,
			'eyebrow' => 'Muutos',
			'title'   => 'Asiat, joista en pidä ja mihin haluan muutosta',
			'lead'    => 'Muutama konkreettinen aihe, joita kritisoin ja joissa haluan vaikuttaa – selkeästi, vastuullisesti ja tuloksellisesti.',
		) );
		?>
		<div class="mt-14 space-y-4">
			<?php foreach ( se_content( 'changes' ) as $i => $c ) : ?>
				<details class="group rounded-3xl bg-white/5 p-6 ring-1 ring-white/10 transition open:bg-white/10 sm:p-8" <?php echo 0 === $i ? 'open' : ''; ?> data-reveal data-delay="<?php echo esc_attr( $i + 1 ); ?>">
					<summary class="flex cursor-pointer list-none items-center justify-between gap-6 [&::-webkit-details-marker]:hidden">
						<h3 class="text-2xl font-bold"><span class="mr-3 font-display text-brand-light">0<?php echo esc_html( $i + 1 ); ?></span><?php echo esc_html( $c['title'] ); ?></h3>
						<span class="grid size-10 shrink-0 place-items-center rounded-full bg-white/10 transition duration-300 group-open:rotate-45 group-open:bg-brand"><?php se_the_icon( 'lucide:plus' ); ?></span>
					</summary>
					<p class="mt-5 max-w-3xl text-lg text-white/75"><?php echo esc_html( $c['text'] ); ?></p>
					<?php if ( ! empty( $c['items'] ) ) : ?>
						<ul class="mt-6 grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
							<?php foreach ( $c['items'] as $item ) : ?>
								<li class="rounded-2xl border-l-4 border-brand bg-white/5 p-4">
									<p class="font-semibold"><?php echo esc_html( $item[0] ); ?></p>
									<p class="mt-1 text-sm text-white/65"><?php echo esc_html( $item[1] ); ?></p>
								</li>
							<?php endforeach; ?>
						</ul>
					<?php endif; ?>
				</details>
			<?php endforeach; ?>
		</div>
		<p class="mt-12 font-display text-3xl font-bold sm:text-4xl" data-reveal>Vähemmän hallintoa, <span class="text-brand-light">enemmän tuloksia.</span></p>
	</div>
</section>

<!-- TESTIMONIALS -->
<section aria-labelledby="suosittelut-title" class="bg-mist py-24">
	<div class="container-site">
		<?php se_section_head( array( 'id' => 'suosittelut-title', 'eyebrow' => 'Mitä muut sanovat', 'title' => 'Yli puoluerajojen' ) ); ?>
		<div class="mt-14"><?php se_testimonials( se_content( 'testimonials' ) ); ?></div>
	</div>
</section>

<!-- BLOG -->
<?php
$latest = new WP_Query( array( 'posts_per_page' => 3, 'ignore_sticky_posts' => true, 'no_found_rows' => true ) );
if ( $latest->have_posts() ) :
	?>
	<section aria-labelledby="blogi-title" class="py-24">
		<div class="container-site">
			<div class="flex flex-wrap items-end justify-between gap-6">
				<?php se_section_head( array( 'id' => 'blogi-title', 'eyebrow' => 'Blogi', 'title' => 'Uusimmat kirjoitukset' ) ); ?>
				<a href="<?php echo esc_url( se_blog_url() ); ?>" class="btn-light" data-reveal>Kaikki kirjoitukset <?php se_the_icon( 'lucide:arrow-right' ); ?></a>
			</div>
			<div class="mt-12 grid gap-6 md:grid-cols-3">
				<?php
				$i = 0;
				while ( $latest->have_posts() ) {
					$latest->the_post();
					se_post_card( ++$i );
				}
				wp_reset_postdata();
				?>
			</div>
		</div>
	</section>
<?php endif; ?>

<!-- FAQ -->
<section aria-labelledby="ukk-title" class="py-16">
	<div class="container-site grid gap-12 lg:grid-cols-[1fr_1.4fr]">
		<?php se_section_head( array( 'id' => 'ukk-title', 'eyebrow' => 'Usein kysyttyä', 'title' => 'Lyhyesti minusta', 'lead' => 'Nopeat vastaukset yleisimpiin kysymyksiin.' ) ); ?>
		<div class="divide-y divide-line rounded-3xl ring-1 ring-line" data-reveal>
			<?php foreach ( se_content( 'faq' ) as $f ) : ?>
				<details class="group p-6">
					<summary class="flex cursor-pointer list-none items-center justify-between gap-4 font-display text-lg font-bold [&::-webkit-details-marker]:hidden">
						<h3><?php echo esc_html( $f['q'] ); ?></h3>
						<?php se_the_icon( 'lucide:chevron-down', 'size-5 shrink-0 text-brand transition duration-300 group-open:rotate-180' ); ?>
					</summary>
					<p class="mt-3 text-muted"><?php echo esc_html( $f['a'] ); ?></p>
				</details>
			<?php endforeach; ?>
		</div>
	</div>
</section>

<?php
get_footer();
