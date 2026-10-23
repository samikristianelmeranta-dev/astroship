<?php
/**
 * Asennusapuri: perusasetukset, valikot ja sivupohjat sisällön tuonnin jälkeen.
 * Ajetaan hallinnasta (Korjauspartnerit → Asennusapuri) tai komennolla `wp kp asenna`.
 */

defined( 'ABSPATH' ) || exit;

function kp_page_id( $slug ) {
	$p = get_page_by_path( $slug, OBJECT, 'page' );
	return $p ? (int) $p->ID : 0;
}

function kp_ensure_term( $name, $tax, $slug = '', $parent = 0 ) {
	$slug = $slug ? $slug : sanitize_title( $name );
	$t    = get_term_by( 'slug', $slug, $tax );
	if ( $t ) {
		return (int) $t->term_id;
	}
	$r = wp_insert_term( $name, $tax, array( 'slug' => $slug, 'parent' => $parent ) );
	return is_wp_error( $r ) ? 0 : (int) $r['term_id'];
}

function kp_run_setup() {
	$log = array();

	// Perusasetukset.
	update_option( 'timezone_string', 'Europe/Helsinki' );
	update_option( 'date_format', 'j.n.Y' );
	update_option( 'time_format', 'H:i' );
	update_option( 'start_of_week', 1 );
	update_option( 'default_comment_status', 'closed' );
	update_option( 'default_ping_status', 'closed' );
	update_option( 'posts_per_page', 12 );
	if ( ! get_option( 'permalink_structure' ) ) {
		update_option( 'permalink_structure', '/%postname%/' );
	}
	$log[] = 'Perusasetukset (aikavyöhyke, päivämäärä, kestolinkit, kommentit pois) asetettu.';

	// Taksonomiat.
	foreach ( array( 'Uusimaa' => 'uusimaa', 'Varsinais-Suomi' => 'varsinais-suomi' ) as $n => $s ) {
		kp_ensure_term( $n, 'toimipiste', $s );
	}
	foreach ( array( 'Taloyhtiöt' => 'taloyhtiot', 'Liike- ja toimitilat' => 'liike-ja-toimitilat', 'Teollisuus' => 'teollisuus' ) as $n => $s ) {
		kp_ensure_term( $n, 'kohderyhma', $s );
	}
	$log[] = 'Toimipisteet ja asiakasryhmät tarkistettu.';

	$n     = kp_seed_content_templates();
	$log[] = sprintf( 'Sisältöpohjat: %d uutta luotu.', $n );

	// Etusivu ja artikkelisivu.
	$front = kp_page_id( 'etusivu' );
	if ( ! $front ) {
		$front = kp_page_id( 'sample-page' );
		if ( $front ) {
			wp_update_post( array( 'ID' => $front, 'post_name' => 'etusivu' ) );
		}
	}
	if ( ! $front ) {
		$front = wp_insert_post( array( 'post_type' => 'page', 'post_status' => 'publish', 'post_title' => 'Etusivu', 'post_name' => 'etusivu' ) );
	}
	$blog = kp_page_id( 'ajankohtaista' );
	if ( ! $blog ) {
		$blog = wp_insert_post( array( 'post_type' => 'page', 'post_status' => 'publish', 'post_title' => 'Ajankohtaista', 'post_name' => 'ajankohtaista' ) );
	}
	update_option( 'show_on_front', 'page' );
	update_option( 'page_on_front', $front );
	update_option( 'page_for_posts', $blog );
	$log[] = sprintf( 'Etusivu (#%d) ja Ajankohtaista (#%d) asetettu.', $front, $blog );

	$privacy = kp_page_id( 'tietosuojaseloste' );
	if ( $privacy ) {
		update_option( 'wp_page_for_privacy_policy', $privacy );
	}

	// Sivupohjat.
	$templates = array(
		'henkilokunta'  => 'templates/sivupohja-henkilokunta.php',
		'yhteystiedot'  => 'templates/sivupohja-yhteystiedot.php',
		'kiinteistojen-palvelut-taloyhtioille'        => 'templates/sivupohja-palvelu.php',
		'kiinteistojen-palvelut-liike-ja-toimitiloille' => 'templates/sivupohja-palvelu.php',
		'vuoden-putkiremontti-2024'   => 'templates/sivupohja-laskeutumissivu.php',
		'taloyhtioiden-energiaratkaisut' => 'templates/sivupohja-laskeutumissivu.php',
	);
	foreach ( $templates as $slug => $tpl ) {
		$id = kp_page_id( $slug );
		if ( $id && ! get_post_meta( $id, '_wp_page_template', true ) ) {
			update_post_meta( $id, '_wp_page_template', $tpl );
			$log[] = "Sivupohja {$tpl} → /{$slug}/";
		}
	}

	// Valikot.
	$log = array_merge( $log, kp_setup_menus() );

	flush_rewrite_rules();
	$log[] = 'Kestolinkit päivitetty. Valmis!';
	return $log;
}

function kp_setup_menus() {
	$log   = array();
	$menus = array(
		'paavalikko' => array(
			'name'  => 'Päävalikko',
			'items' => array(
				array( 'title' => 'Palvelut', 'archive' => 'palvelu', 'children' => array(
					array( 'title' => 'Taloyhtiöille', 'page' => 'kiinteistojen-palvelut-taloyhtioille' ),
					array( 'title' => 'Liike- ja toimitiloille', 'page' => 'kiinteistojen-palvelut-liike-ja-toimitiloille' ),
					array( 'title' => 'Teollisuudelle', 'page' => 'optimoi-teollisuuskiinteiston-ja-tuotannon-yllapito' ),
					array( 'title' => 'Ylläpitopartneri', 'post' => array( 'palvelu', 'yllapitopartneri' ) ),
					array( 'title' => 'Kaikki palvelut', 'archive' => 'palvelu' ),
				) ),
				array( 'title' => 'Referenssit', 'archive' => 'referenssi' ),
				array( 'title' => 'Ajankohtaista', 'page' => 'ajankohtaista' ),
				array( 'title' => 'Korjauspartnerit', 'page' => 'korjauspartnerit', 'children' => array(
					array( 'title' => 'Yritysesittely', 'page' => 'korjauspartnerit' ),
					array( 'title' => 'Henkilökunta', 'page' => 'henkilokunta' ),
					array( 'title' => 'Työpaikat', 'page' => 'korjauspartnerit-tyopaikkana' ),
					array( 'title' => 'Mediapankki', 'page' => 'mediapankki' ),
					array( 'title' => 'Sponsorointi ja yhteistyö', 'page' => 'sponsorointi-lahjoitukset-ja-yhteistyo' ),
				) ),
				array( 'title' => 'Yhteystiedot', 'page' => 'yhteystiedot' ),
			),
		),
		'alavalikko' => array(
			'name'  => 'Alatunnisteen valikko',
			'items' => array(
				array( 'title' => 'Toimipisteet', 'url' => home_url( '/yhteystiedot/#toimipisteet' ) ),
				array( 'title' => 'Henkilökunta', 'page' => 'henkilokunta' ),
				array( 'title' => 'Laskutustiedot', 'url' => home_url( '/yhteystiedot/#laskutustiedot' ) ),
				array( 'title' => 'Ura', 'page' => 'korjauspartnerit-tyopaikkana' ),
				array( 'title' => 'Mediapankki', 'page' => 'mediapankki' ),
				array( 'title' => 'Tietosuojaseloste', 'page' => 'tietosuojaseloste' ),
				array( 'title' => 'Jätä yhteydenottopyyntö', 'url' => home_url( '/yhteystiedot/#viesti' ) ),
			),
		),
	);

	$locations = get_theme_mod( 'nav_menu_locations', array() );
	foreach ( $menus as $location => $menu ) {
		$existing = wp_get_nav_menu_object( $menu['name'] );
		if ( $existing ) {
			$log[] = "Valikko \"{$menu['name']}\" on jo olemassa – ei muutettu.";
			$locations[ $location ] = $existing->term_id;
			continue;
		}
		$menu_id = wp_create_nav_menu( $menu['name'] );
		if ( is_wp_error( $menu_id ) ) {
			continue;
		}
		kp_add_menu_items( $menu_id, $menu['items'], 0 );
		$locations[ $location ] = $menu_id;
		$log[] = "Valikko \"{$menu['name']}\" luotu.";
	}
	set_theme_mod( 'nav_menu_locations', $locations );
	return $log;
}

function kp_add_menu_items( $menu_id, $items, $parent ) {
	foreach ( $items as $item ) {
		$args = array(
			'menu-item-title'     => $item['title'],
			'menu-item-status'    => 'publish',
			'menu-item-parent-id' => $parent,
		);
		if ( ! empty( $item['page'] ) ) {
			$id = kp_page_id( $item['page'] );
			if ( ! $id ) {
				continue;
			}
			$args += array( 'menu-item-type' => 'post_type', 'menu-item-object' => 'page', 'menu-item-object-id' => $id );
		} elseif ( ! empty( $item['post'] ) ) {
			$p = get_page_by_path( $item['post'][1], OBJECT, $item['post'][0] );
			if ( ! $p ) {
				continue;
			}
			$args += array( 'menu-item-type' => 'post_type', 'menu-item-object' => $item['post'][0], 'menu-item-object-id' => $p->ID );
		} elseif ( ! empty( $item['archive'] ) ) {
			$args += array( 'menu-item-type' => 'post_type_archive', 'menu-item-object' => $item['archive'] );
		} else {
			$args += array( 'menu-item-type' => 'custom', 'menu-item-url' => $item['url'] );
		}
		$id = wp_update_nav_menu_item( $menu_id, 0, $args );
		if ( ! is_wp_error( $id ) && ! empty( $item['children'] ) ) {
			kp_add_menu_items( $menu_id, $item['children'], $id );
		}
	}
}
