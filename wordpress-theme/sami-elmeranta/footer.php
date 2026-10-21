<?php
/**
 * Alaosa.
 *
 * @package sami-elmeranta
 */

?>
</main>

<footer class="relative mt-24 overflow-hidden bg-ink text-white">
	<div class="pointer-events-none absolute -top-40 right-0 size-[28rem] rounded-full bg-brand/30 blur-3xl animate-blob" aria-hidden="true"></div>
	<div class="container-site relative grid gap-12 py-16 md:grid-cols-[1.4fr_1fr_1fr]">
		<div>
			<p class="font-display text-3xl font-bold"><?php echo esc_html( se_opt( 'person_name' ) ); ?></p>
			<p class="mt-1 text-sm font-semibold uppercase tracking-[0.2em] text-brand-light"><?php echo esc_html( se_opt( 'tagline' ) . ' · ' . se_opt( 'party_name' ) ); ?></p>
			<p class="mt-5 max-w-md text-white/70">Kaupunginvaltuutettu ja kaupunginhallituksen jäsen <?php echo esc_html( se_opt( 'locality' ) ); ?>sta. Teen töitä sen eteen, että tekeminen palkitsee, yhteisö tukee ja jokainen voi vaikuttaa.</p>
			<?php se_the_socials( true, 'mt-6' ); ?>
		</div>

		<nav aria-label="Alavalikko">
			<p class="text-sm font-semibold uppercase tracking-[0.18em] text-white/50">Sivusto</p>
			<ul class="mt-4 space-y-2"><?php se_nav( has_nav_menu( 'footer' ) ? 'footer' : 'primary', 'footer' ); ?></ul>
		</nav>

		<div>
			<p class="text-sm font-semibold uppercase tracking-[0.18em] text-white/50">Yhteys</p>
			<ul class="mt-4 space-y-3 text-white/80">
				<li><a href="tel:<?php echo esc_attr( se_opt( 'phone' ) ); ?>" class="inline-flex items-center gap-2 hover:text-brand-light"><?php se_the_icon( 'lucide:phone', 'size-4' ); ?> <?php echo esc_html( se_opt( 'phone_display' ) ); ?></a></li>
				<?php if ( se_opt( 'email' ) ) : ?>
				<li><a href="mailto:<?php echo esc_attr( antispambot( se_opt( 'email' ) ) ); ?>" class="inline-flex items-center gap-2 break-all hover:text-brand-light"><?php se_the_icon( 'lucide:mail', 'size-4 shrink-0' ); ?> <?php echo esc_html( antispambot( se_opt( 'email' ) ) ); ?></a></li>
				<?php endif; ?>
				<li class="inline-flex items-center gap-2"><?php se_the_icon( 'lucide:map-pin', 'size-4' ); ?> <?php echo esc_html( se_opt( 'district' ) . ', ' . se_opt( 'locality' ) ); ?></li>
			</ul>
		</div>
	</div>
	<div class="container-site relative flex flex-col gap-3 border-t border-white/10 py-6 text-sm text-white/50 sm:flex-row sm:items-center sm:justify-between">
		<p>© <?php echo esc_html( gmdate( 'Y' ) . ' ' . se_opt( 'person_name' ) ); ?></p>
		<ul class="flex flex-wrap gap-x-5 gap-y-2">
			<?php if ( get_privacy_policy_url() ) : ?>
				<li><a href="<?php echo esc_url( get_privacy_policy_url() ); ?>" class="hover:text-white">Tietosuoja</a></li>
			<?php endif; ?>
			<li><a href="<?php echo esc_url( get_feed_link() ); ?>" class="hover:text-white">RSS</a></li>
			<li><a href="<?php echo esc_url( home_url( '/llms.txt' ) ); ?>" class="hover:text-white">llms.txt</a></li>
		</ul>
	</div>
</footer>

<?php wp_footer(); ?>
</body>
</html>
