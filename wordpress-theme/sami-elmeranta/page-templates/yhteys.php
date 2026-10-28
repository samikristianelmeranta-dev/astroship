<?php
/**
 * Template Name: Ota yhteyttä
 *
 * @package sami-elmeranta
 */

get_header();
the_post();

$email    = antispambot( se_opt( 'email' ) );
$channels = array(
	array( 'lucide:phone', 'Soita', se_opt( 'phone_display' ), 'tel:' . se_opt( 'phone' ), false ),
	array( 'simple-icons:whatsapp', 'WhatsApp', 'Laita viestiä', se_opt( 'whatsapp_url' ), true ),
	array( 'lucide:mail', 'Sähköposti', $email, $email ? 'mailto:' . $email : '', false ),
);

se_page_hero( 'Ota yhteyttä', 'Jotain mielessä?', 'Älä epäröi olla yhteydessä – kuulen mielelläni ajatuksiasi ja palautettasi. Politiikka on yhteistyötä, ja jokainen näkökulma on tärkeä.' );
?>

<section class="py-8">
	<div class="container-site grid gap-10 lg:grid-cols-[1fr_1.3fr]">
		<div class="space-y-4">
			<?php foreach ( $channels as $i => $c ) : ?>
				<?php
				if ( empty( $c[2] ) ) {
					continue;
				}
				?>
				<a href="<?php echo esc_url( $c[3], array( 'tel', 'mailto', 'https', 'http' ) ); ?>"<?php echo $c[4] ? ' target="_blank" rel="noopener noreferrer"' : ''; ?> class="card group flex items-center gap-5" data-reveal="left" data-delay="<?php echo esc_attr( $i + 1 ); ?>">
					<span class="grid size-14 shrink-0 place-items-center rounded-2xl bg-brand text-white transition duration-500 group-hover:rotate-6 group-hover:scale-110"><?php se_the_icon( $c[0], 'size-6' ); ?></span>
					<span class="min-w-0">
						<span class="block text-sm font-semibold uppercase tracking-wider text-muted"><?php echo esc_html( $c[1] ); ?></span>
						<span class="block break-all font-display text-xl font-bold"><?php echo esc_html( $c[2] ); ?></span>
					</span>
					<?php se_the_icon( 'lucide:arrow-up-right', 'ml-auto size-5 shrink-0 text-brand opacity-0 transition group-hover:opacity-100' ); ?>
				</a>
			<?php endforeach; ?>
			<div class="card" data-reveal="left" data-delay="4">
				<p class="font-semibold">Seuraa minua somessa</p>
				<?php se_the_socials( false, 'mt-3' ); ?>
			</div>
		</div>

		<div id="lomake" class="card scroll-mt-28 !p-8 sm:!p-10" data-reveal="right">
			<h2 class="text-3xl font-bold">Lähetä viesti</h2>
			<?php se_contact_form(); ?>
		</div>
	</div>
</section>

<?php
get_template_part( 'template-parts/page-extra' );
get_footer();
