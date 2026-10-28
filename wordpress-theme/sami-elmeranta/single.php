<?php
/**
 * Blogiartikkeli.
 *
 * @package sami-elmeranta
 */

get_header();
the_post();

$url   = get_permalink();
// Pystykuva näytetään otsikon vieressä, vaakakuva leveänä otsikon alla.
$thumb_meta = has_post_thumbnail() ? wp_get_attachment_metadata( get_post_thumbnail_id() ) : array();
$portrait   = ! empty( $thumb_meta['width'] ) && ! empty( $thumb_meta['height'] ) && $thumb_meta['height'] > $thumb_meta['width'];
$title = get_the_title();
$share = array(
	array( 'Facebook', 'simple-icons:facebook', 'https://www.facebook.com/sharer/sharer.php?u=' . rawurlencode( $url ) ),
	array( 'LinkedIn', 'simple-icons:linkedin', 'https://www.linkedin.com/sharing/share-offsite/?url=' . rawurlencode( $url ) ),
	array( 'X', 'simple-icons:x', 'https://x.com/intent/post?url=' . rawurlencode( $url ) . '&text=' . rawurlencode( $title ) ),
	array( 'WhatsApp', 'simple-icons:whatsapp', 'https://wa.me/?text=' . rawurlencode( $title . ' ' . $url ) ),
);
?>
<div class="fixed inset-x-0 top-0 z-[60] h-1" aria-hidden="true">
	<div data-progress class="progress-bar h-full bg-gradient-to-r from-brand to-brand-light"></div>
</div>

<article <?php post_class(); ?>>
	<header class="relative -mt-20 overflow-hidden pb-10 pt-30 sm:pt-36">
		<div class="pointer-events-none absolute -top-40 right-[-10%] -z-10 size-[30rem] rounded-full bg-brand/15 blur-3xl animate-blob" aria-hidden="true"></div>
		<div class="container-site <?php echo $portrait ? 'grid max-w-6xl items-center gap-12 lg:grid-cols-[1.4fr_1fr]' : 'max-w-4xl'; ?>">
			<div>
			<nav aria-label="Murupolku" class="hero-word text-sm text-muted">
				<ol class="flex flex-wrap items-center gap-2">
					<li><a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="hover:text-brand-dark">Etusivu</a></li>
					<li aria-hidden="true">/</li>
					<li><a href="<?php echo esc_url( se_blog_url() ); ?>" class="hover:text-brand-dark">Blogi</a></li>
				</ol>
			</nav>
			<h1 class="hero-word mt-5 text-4xl font-extrabold leading-tight sm:text-5xl lg:text-6xl"><?php the_title(); ?></h1>
			<p class="hero-word mt-6 flex flex-wrap items-center gap-x-4 gap-y-2 text-muted">
				<span class="font-semibold text-ink"><?php echo esc_html( se_opt( 'person_name' ) ); ?></span>
				<span aria-hidden="true">•</span>
				<time datetime="<?php echo esc_attr( get_the_date( 'c' ) ); ?>"><?php echo esc_html( get_the_date() ); ?></time>
				<span aria-hidden="true">•</span>
				<span><?php echo esc_html( se_reading_time() ); ?> min lukuaika</span>
			</p>
			<?php
			$tags = get_the_tags();
			if ( $tags ) :
				?>
				<ul class="hero-word mt-5 flex flex-wrap gap-2">
					<?php foreach ( $tags as $t ) : ?>
						<li><a href="<?php echo esc_url( get_tag_link( $t ) ); ?>" class="rounded-full bg-brand-soft px-3 py-1 text-sm font-medium text-brand-dark hover:bg-brand hover:text-white">#<?php echo esc_html( $t->name ); ?></a></li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>
			</div>
			<?php if ( $portrait ) : ?>
				<figure class="relative mx-auto w-full max-w-sm" data-reveal="zoom">
					<div class="absolute -inset-3 rotate-3 rounded-[2.5rem] bg-gradient-to-br from-brand to-ink-soft opacity-90" aria-hidden="true"></div>
					<?php the_post_thumbnail( 'large', array( 'class' => 'relative aspect-[4/5] w-full rounded-[2rem] bg-white object-cover object-top shadow-2xl', 'fetchpriority' => 'high' ) ); ?>
				</figure>
			<?php endif; ?>
		</div>
	</header>

	<?php if ( has_post_thumbnail() && ! $portrait ) : ?>
		<div class="container-site mb-12 max-w-5xl">
			<?php the_post_thumbnail( 'large', array( 'class' => 'w-full rounded-[2rem] object-cover shadow-xl', 'fetchpriority' => 'high' ) ); ?>
		</div>
	<?php endif; ?>

	<div class="container-site grid max-w-6xl gap-12 lg:grid-cols-[1fr_16rem]">
		<div class="entry-content prose prose-lg max-w-none min-w-0">
			<?php
			the_content();
			wp_link_pages();
			?>
		</div>
		<aside class="space-y-8 lg:sticky lg:top-24 lg:self-start">
			<div>
				<p class="text-sm font-semibold uppercase tracking-[0.15em] text-muted">Jaa artikkeli</p>
				<ul class="mt-3 flex gap-2">
					<?php foreach ( $share as $s ) : ?>
						<li><a href="<?php echo esc_url( $s[2] ); ?>" target="_blank" rel="noopener noreferrer" class="grid size-11 place-items-center rounded-full bg-mist ring-1 ring-line transition hover:-translate-y-1 hover:bg-brand hover:text-white"><?php se_the_icon( $s[1] ); ?><span class="sr-only">Jaa: <?php echo esc_html( $s[0] ); ?></span></a></li>
					<?php endforeach; ?>
				</ul>
			</div>
		</aside>
	</div>

	<div class="container-site mt-16 max-w-4xl">
		<div class="card flex flex-col gap-6 bg-ink text-white ring-0 sm:flex-row sm:items-center" data-reveal>
			<img src="<?php echo esc_url( se_image_url( 'portrait', 'sami-portrait-600.webp', 'thumbnail' ) ); ?>" alt="" width="96" height="96" loading="lazy" class="size-24 rounded-2xl object-cover object-top">
			<div class="flex-1">
				<p class="font-display text-2xl font-bold"><?php echo esc_html( se_opt( 'person_name' ) ); ?></p>
				<p class="mt-1 text-white/70"><?php echo esc_html( se_opt( 'tagline' ) . ' · ' . se_opt( 'party_name' ) ); ?>. Kaupunginvaltuutettu ja -hallituksen jäsen.</p>
			</div>
			<a href="<?php echo esc_url( se_contact_url() ); ?>" class="btn-primary shrink-0">Ota yhteyttä <?php se_the_icon( 'lucide:arrow-right', 'size-4' ); ?></a>
		</div>
	</div>

	<?php if ( comments_open() || get_comments_number() ) : ?>
		<div class="container-site mt-16 max-w-3xl"><?php comments_template(); ?></div>
	<?php endif; ?>
</article>

<?php
$related = new WP_Query( array(
	'posts_per_page'      => 3,
	'post__not_in'        => array( get_the_ID() ),
	'ignore_sticky_posts' => true,
	'no_found_rows'       => true,
) );
if ( $related->have_posts() ) :
	?>
	<section class="py-24" aria-labelledby="lisaa-title">
		<div class="container-site">
			<h2 id="lisaa-title" class="text-3xl font-bold" data-reveal>Lue myös</h2>
			<div class="mt-10 grid gap-6 md:grid-cols-3">
				<?php
				$i = 0;
				while ( $related->have_posts() ) {
					$related->the_post();
					se_post_card( ++$i );
				}
				wp_reset_postdata();
				?>
			</div>
		</div>
	</section>
	<?php
endif;

get_footer();
