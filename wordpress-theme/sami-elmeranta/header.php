<?php
/**
 * Yläosa.
 *
 * @package sami-elmeranta
 */

?><!doctype html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<?php wp_head(); ?>
</head>
<body <?php body_class( 'min-h-dvh flex flex-col' ); ?>>
<?php wp_body_open(); ?>
<a href="#main" class="sr-only focus:not-sr-only focus:fixed focus:left-4 focus:top-4 focus:z-[100] focus:rounded-full focus:bg-ink focus:px-5 focus:py-3 focus:text-white">Siirry sisältöön</a>

<header data-header class="group/header sticky top-0 z-50 transition-all duration-500 [&.is-scrolled]:bg-white/80 [&.is-scrolled]:shadow-sm [&.is-scrolled]:backdrop-blur-xl">
	<div class="container-site flex h-20 items-center justify-between gap-6 transition-all duration-500 group-[.is-scrolled]/header:h-16">
		<a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="group flex items-center gap-3" aria-label="<?php echo esc_attr( se_opt( 'person_name' ) ); ?> – etusivu">
			<?php
			$logo_id = get_theme_mod( 'custom_logo' );
			if ( $logo_id ) {
				echo wp_get_attachment_image( $logo_id, 'thumbnail', false, array( 'class' => 'size-10 rounded-2xl object-contain', 'alt' => '' ) );
			} else {
				echo '<span class="grid size-10 place-items-center rounded-2xl bg-brand font-display text-lg font-extrabold text-white shadow-lg shadow-brand/30 transition duration-500 group-hover:rotate-[-8deg] group-hover:scale-105" aria-hidden="true">' . esc_html( se_initials( se_opt( 'person_name' ) ) ) . '</span>';
			}
			?>
			<span class="leading-tight">
				<span class="block font-display text-lg font-bold"><?php echo esc_html( se_opt( 'person_name' ) ); ?></span>
				<span class="block text-xs font-medium uppercase tracking-[0.2em] text-muted"><?php echo esc_html( se_opt( 'party_name' ) ); ?></span>
			</span>
		</a>

		<nav aria-label="Päävalikko" class="hidden lg:block">
			<ul class="flex items-center gap-1"><?php se_nav( 'primary', 'desktop' ); ?></ul>
		</nav>

		<div class="flex items-center gap-2">
			<a href="<?php echo esc_url( se_contact_url() ); ?>" class="btn-primary hidden !px-5 !py-2.5 sm:inline-flex">Ota yhteyttä <?php se_the_icon( 'lucide:arrow-up-right', 'size-4' ); ?></a>
			<button type="button" data-menu-toggle aria-expanded="false" aria-controls="mobile-menu" class="group grid size-11 place-items-center rounded-full ring-1 ring-line lg:hidden">
				<span class="sr-only">Avaa valikko</span>
				<?php se_the_icon( 'lucide:menu', 'size-5 group-aria-expanded:hidden' ); ?>
				<?php se_the_icon( 'lucide:x', 'hidden size-5 group-aria-expanded:block' ); ?>
			</button>
		</div>
	</div>

	<div id="mobile-menu" hidden class="fixed inset-x-0 top-20 bottom-0 bg-white/95 backdrop-blur-xl lg:hidden">
		<nav aria-label="Mobiilivalikko" class="px-6 py-8">
			<ul class="space-y-1"><?php se_nav( 'primary', 'mobile' ); ?></ul>
		</nav>
	</div>
</header>

<main id="main" class="flex-1">
