<?php
/**
 * Yläosa: logo, puhelin, tarjouspainike ja palveluvalikko.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?><!doctype html>
<html <?php language_attributes(); ?>>
<head>
<meta charset="<?php bloginfo( 'charset' ); ?>">
<meta name="viewport" content="width=device-width, initial-scale=1">
<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<a class="skip-link" href="#sisalto-alku">Siirry sisältöön</a>
<header class="site-head">
<div class="wrap head-row">
<a class="logo" href="<?php echo esc_url( home_url( '/' ) ); ?>" aria-label="TiivisTupa, etusivu"><?php echo tt_logo_svg(); // phpcs:ignore ?><span>Tiivis<b>Tupa</b></span></a>
<div class="head-actions">
<?php if ( tt_opt( 'tt_phone' ) ) : ?>
<a class="head-phone" href="tel:<?php echo esc_attr( tt_phone_intl() ); ?>"><?php echo esc_html( tt_opt( 'tt_phone' ) ); ?></a>
<?php endif; ?>
<a class="btn btn-primary btn-small" href="#tarjous">Pyydä tarjous</a>
</div>
</div>
<nav class="nav" aria-label="Palvelut"><div class="wrap"><ul><?php echo tt_nav_html(); // phpcs:ignore ?></ul></div></nav>
</header>
<main id="sisalto-alku">
