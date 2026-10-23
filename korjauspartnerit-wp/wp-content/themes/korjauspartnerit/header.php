<?php
/**
 * Ylätunniste.
 */
defined( 'ABSPATH' ) || exit;
$landing = is_page_template( 'templates/sivupohja-laskeutumissivu.php' );
?><!doctype html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<a class="kp-ohita" href="#sisalto">Siirry sisältöön</a>

<header class="kp-ylatunniste" data-kp-ylatunniste>
	<div class="kp-ylatunniste__sisus kp-kehys kp-kehys--levea">
		<a class="kp-ylatunniste__logo" href="<?php echo esc_url( home_url( '/' ) ); ?>" rel="home">
			<?php echo kp_logo( 'vaaka' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
			<span class="screen-reader-text"><?php bloginfo( 'name' ); ?> – etusivu</span>
		</a>

		<?php if ( $landing ) : ?>
			<a class="kp-ylatunniste__puhelin" href="<?php echo esc_attr( function_exists( 'kp_tel_href' ) ? kp_tel_href( kp_t_setting( 'puhelin' ) ) : '' ); ?>">
				<?php echo kp_icon( 'puhelin' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
				<span><?php echo esc_html( kp_t_setting( 'puhelin' ) ); ?></span>
			</a>
		<?php else : ?>
			<nav class="kp-paavalikko" id="kp-paavalikko" aria-label="Päävalikko" data-kp-valikko>
				<?php
				wp_nav_menu( array(
					'theme_location' => 'paavalikko',
					'container'      => false,
					'menu_class'     => 'kp-paavalikko__lista',
					'depth'          => 2,
					'fallback_cb'    => function () {
						echo '<ul class="kp-paavalikko__lista">';
						wp_list_pages( array( 'title_li' => '', 'depth' => 1 ) );
						echo '</ul>';
					},
				) );
				?>
				<form class="kp-paavalikko__haku" role="search" method="get" action="<?php echo esc_url( home_url( '/' ) ); ?>">
					<label class="screen-reader-text" for="kp-mobiilihaku">Hae sivustolta</label>
					<input id="kp-mobiilihaku" type="search" name="s" placeholder="Hae sivustolta…">
					<button type="submit" aria-label="Hae"><?php echo kp_icon( 'haku' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></button>
				</form>
			</nav>

			<div class="kp-ylatunniste__toiminnot">
				<button class="kp-ikonipainike kp-hakupainike" type="button" aria-expanded="false" aria-controls="kp-haku" data-kp-hakupaneeli-avaa>
					<?php echo kp_icon( 'haku' ); // phpcs:ignore WordPress.Security.EscapeOutput ?><span class="screen-reader-text">Avaa haku</span>
				</button>
				<button class="kp-ikonipainike kp-valikkopainike" type="button" aria-expanded="false" aria-controls="kp-paavalikko" data-kp-valikko-avaa>
					<span class="kp-valikkopainike__auki"><?php echo kp_icon( 'valikko' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span>
					<span class="kp-valikkopainike__kiinni"><?php echo kp_icon( 'sulje' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span>
					<span class="screen-reader-text">Valikko</span>
				</button>
			</div>
		<?php endif; ?>
	</div>

	<?php if ( ! $landing ) : ?>
		<div class="kp-hakupaneeli" id="kp-haku" hidden data-kp-hakupaneeli>
			<form class="kp-kehys kp-hakupaneeli__lomake" role="search" method="get" action="<?php echo esc_url( home_url( '/' ) ); ?>">
				<label class="screen-reader-text" for="kp-hakukentta">Hae sivustolta</label>
				<input id="kp-hakukentta" type="search" name="s" value="<?php echo esc_attr( get_search_query() ); ?>" placeholder="Hae palvelua, henkilöä tai aihetta – esim. kuntotutkimus, TDD, valvonta">
				<button type="submit" class="kp-button">Hae</button>
			</form>
		</div>
	<?php endif; ?>
</header>

<main id="sisalto" class="kp-paa" tabindex="-1">
