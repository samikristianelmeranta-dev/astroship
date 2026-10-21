<?php
/**
 * Template Name: Politiikka
 *
 * Sivun omaa sisältöä (editori) näytetään "Ajankohtaista"-osiossa, jos sitä on.
 *
 * @package sami-elmeranta
 */

get_header();
the_post();

$roles  = se_content( 'roles' );
$active = array_values( array_filter( $roles, function ( $r ) {
	return ! empty( $r['active'] );
} ) );
$past   = array_values( array_filter( $roles, function ( $r ) {
	return empty( $r['active'] );
} ) );

se_page_hero( 'Sami & politiikka', 'Politiikkaa kunta- ja aluetasolla', 'Poliittista vaikuttamista Kaarinassa ja Varsinais-Suomessa – vastuullisesti, avoimesti ja yhteistyöhakuisesti.' );
?>

<section class="py-12" aria-labelledby="tausta-title">
	<div class="container-site grid gap-12 lg:grid-cols-[1.3fr_1fr]">
		<div class="space-y-5 text-lg leading-relaxed text-muted" data-reveal="left">
			<h2 id="tausta-title" class="text-3xl font-bold text-ink">Vahvemmalla mandaatilla eteenpäin</h2>
			<p>Olen ollut mukana paikallispolitiikassa vuodesta 2021. Ensimmäisissä kuntavaaleissani tulin valituksi Kaarinan kaupunginvaltuustoon ja kaupunginhallitukseen. Vuoden 2025 vaaleissa luottamukseni vahvistui entisestään – äänimääräni kasvoi merkittävästi, ja jatkan nyt työtäni valtuustossa sekä kaupunginhallituksessa entistä vahvemmalla mandaatilla.</p>
			<p>Paikallisen päätöksenteon lisäksi olen ollut aktiivisesti mukana myös aluepolitiikassa jaostotasolla. Toimin tällä hetkellä Varsinais-Suomen maakuntavaltuuston jäsenenä, jossa pääsen vaikuttamaan laajemmin koko alueemme kehitykseen.</p>
		</div>
		<aside class="card self-start bg-mist" data-reveal="right">
			<p class="eyebrow">Puolue</p>
			<p class="mt-3 font-display text-3xl font-bold"><?php echo esc_html( se_opt( 'party_name' ) ); ?></p>
			<p class="mt-3 text-muted">Edustan <?php echo esc_html( se_opt( 'party_name' ) ); ?>iä Kaarinan ja Varsinais-Suomen päätöksenteossa.</p>
			<?php if ( se_opt( 'party_url' ) ) : ?>
				<a href="<?php echo esc_url( se_opt( 'party_url' ) ); ?>" target="_blank" rel="noopener noreferrer" class="btn-light mt-6"><?php echo esc_html( wp_parse_url( se_opt( 'party_url' ), PHP_URL_HOST ) ); ?> <?php se_the_icon( 'lucide:external-link', 'size-4' ); ?></a>
			<?php endif; ?>
		</aside>
	</div>
</section>

<section class="py-20" aria-labelledby="toimet-title">
	<div class="container-site">
		<?php se_section_head( array( 'id' => 'toimet-title', 'eyebrow' => 'Luottamustoimet', 'title' => 'Poliittiset luottamustoimeni', 'lead' => 'Olen mukana paikallisessa ja alueellisessa päätöksenteossa – työtä paremman ja toimivamman Varsinais-Suomen puolesta.' ) ); ?>
		<div class="mt-12 grid gap-6 md:grid-cols-3">
			<?php foreach ( $active as $i => $r ) : ?>
				<article class="card overflow-hidden" data-reveal data-delay="<?php echo esc_attr( $i + 1 ); ?>">
					<div class="absolute inset-x-0 top-0 h-1.5 bg-gradient-to-r from-brand to-brand-light" aria-hidden="true"></div>
					<p class="inline-flex items-center gap-2 text-sm font-semibold text-brand-dark"><span class="size-2 rounded-full bg-brand animate-pulse" aria-hidden="true"></span> Nykyinen</p>
					<h3 class="mt-4 text-2xl font-bold leading-snug"><?php echo esc_html( $r['title'] ); ?></h3>
					<p class="mt-3 text-muted"><?php echo esc_html( $r['text'] ); ?></p>
				</article>
			<?php endforeach; ?>
		</div>

		<?php if ( $past ) : ?>
			<h3 class="mt-20 text-2xl font-bold" data-reveal>Aiemmat luottamustoimet</h3>
			<ol class="relative mt-8 space-y-6 border-l-2 border-line pl-8">
				<?php foreach ( $past as $i => $r ) : ?>
					<li class="relative" data-reveal data-delay="<?php echo esc_attr( ( $i % 5 ) + 1 ); ?>">
						<span class="absolute -left-[2.55rem] top-1.5 size-4 rounded-full border-4 border-white bg-brand ring-2 ring-brand/30" aria-hidden="true"></span>
						<p class="font-display text-lg font-bold"><?php echo esc_html( $r['title'] ); ?></p>
						<p class="text-muted"><?php echo esc_html( $r['text'] ); ?></p>
					</li>
				<?php endforeach; ?>
			</ol>
		<?php endif; ?>
		<p class="mt-14 max-w-2xl text-lg text-muted" data-reveal>Luottamustoimissa toimin arvojeni mukaisesti – vastuullisesti, avoimesti ja yhteistyöhakuisesti.</p>
	</div>
</section>

<?php get_template_part( 'template-parts/page-extra' ); ?>

<section class="py-12" aria-labelledby="keskustele-title">
	<div class="container-site grid gap-6 lg:grid-cols-2">
		<div class="card bg-ink text-white ring-0" data-reveal="left">
			<h2 id="keskustele-title" class="text-3xl font-bold">Haluatko keskustella politiikasta?</h2>
			<p class="mt-4 text-white/75">Olen täällä sinua varten – ilman turhaa byrokratiaa ja muodollisuuksia. Jos jokin asia mietityttää, haluat jakaa ajatuksia tai kysyä näkemyksiäni, ota rohkeasti yhteyttä. Matalalla kynnyksellä, avoimesti ja rakentavasti.</p>
			<div class="mt-6 flex flex-wrap gap-3">
				<a href="<?php echo esc_url( se_contact_url() ); ?>" class="btn-primary">Laita viestiä</a>
				<a href="tel:<?php echo esc_attr( se_opt( 'phone' ) ); ?>" class="btn-ghost">Soita <?php echo esc_html( se_opt( 'phone_display' ) ); ?></a>
			</div>
		</div>
		<div class="card" data-reveal="right">
			<h2 class="text-3xl font-bold">Lue ajatuksiani</h2>
			<p class="mt-4 text-muted">Kirjoitan politiikasta omaan blogiini ja kirjoitan mielipidekirjoituksia eri lehtiin.</p>
			<ul class="mt-6 space-y-2">
				<li><a href="<?php echo esc_url( se_blog_url() ); ?>" class="inline-flex items-center gap-2 font-semibold text-brand-dark hover:underline"><?php se_the_icon( 'lucide:arrow-right', 'size-4' ); ?> Oma blogi</a></li>
				<?php foreach ( se_writing() as $w ) : ?>
					<li><a href="<?php echo esc_url( $w[1] ); ?>" target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-2 font-semibold text-brand-dark hover:underline"><?php se_the_icon( 'lucide:external-link', 'size-4' ); ?> <?php echo esc_html( $w[0] ); ?></a></li>
				<?php endforeach; ?>
			</ul>
			<?php se_the_socials( false, 'mt-6' ); ?>
		</div>
	</div>
</section>

<?php
se_newsletter();
get_footer();
