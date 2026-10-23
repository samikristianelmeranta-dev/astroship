<?php
/**
 * Sisältötyypit ja taksonomiat.
 *
 * Henkilökunnan tunniste (henkilokunta), taksonomia (hakusanat) ja kenttien
 * meta-avaimet (tehtavanimike, puhelinnumero, sahkoposti) ovat samat kuin vanhalla
 * sivustolla, joten vanha sisältö ja URL-osoitteet toimivat suoraan.
 */

defined( 'ABSPATH' ) || exit;

add_action( 'init', 'kp_register_post_types', 5 );
add_action( 'init', 'kp_register_taxonomies', 5 );

function kp_cpt_labels( $singular, $plural, $extra = array() ) {
	return array_merge(
		array(
			'name'               => $plural,
			'singular_name'      => $singular,
			'menu_name'          => $plural,
			'all_items'          => 'Kaikki',
			'add_new'            => 'Lisää uusi',
			'add_new_item'       => 'Lisää uusi',
			'edit_item'          => 'Muokkaa',
			'new_item'           => 'Uusi',
			'view_item'          => 'Näytä',
			'view_items'         => 'Näytä',
			'search_items'       => 'Hae',
			'not_found'          => 'Ei löytynyt',
			'not_found_in_trash' => 'Roskakorissa ei ole mitään',
			'featured_image'     => 'Kuva',
			'set_featured_image' => 'Aseta kuva',
			'remove_featured_image' => 'Poista kuva',
			'use_featured_image' => 'Käytä kuvana',
		),
		$extra
	);
}

function kp_register_post_types() {
	register_post_type(
		'henkilokunta',
		array(
			'labels'        => kp_cpt_labels( 'Henkilö', 'Henkilökunta', array(
				'all_items'          => 'Kaikki henkilöt',
				'add_new'            => 'Lisää uusi henkilö',
				'add_new_item'       => 'Lisää uusi henkilö',
				'edit_item'          => 'Muokkaa henkilöä',
				'search_items'       => 'Etsi henkilöä',
				'featured_image'     => 'Henkilökuva',
				'set_featured_image' => 'Aseta henkilökuva',
				'remove_featured_image' => 'Poista henkilökuva',
			) ),
			'public'        => true,
			'show_in_rest'  => true,
			'has_archive'   => false,
			'menu_icon'     => 'dashicons-groups',
			'menu_position' => 21,
			'supports'      => array( 'title', 'editor', 'thumbnail', 'excerpt', 'page-attributes', 'custom-fields', 'revisions' ),
			'rewrite'       => array( 'slug' => 'henkilokunta', 'with_front' => false ),
		)
	);

	register_post_type(
		'palvelu',
		array(
			'labels'        => kp_cpt_labels( 'Palvelu', 'Palvelut', array(
				'all_items'    => 'Kaikki palvelut',
				'add_new'      => 'Lisää uusi palvelu',
				'add_new_item' => 'Lisää uusi palvelu',
				'edit_item'    => 'Muokkaa palvelua',
			) ),
			'public'        => true,
			'show_in_rest'  => true,
			'has_archive'   => 'palvelut',
			'menu_icon'     => 'dashicons-hammer',
			'menu_position' => 22,
			'supports'      => array( 'title', 'editor', 'thumbnail', 'excerpt', 'page-attributes', 'revisions', 'custom-fields' ),
			'rewrite'       => array( 'slug' => 'palvelut', 'with_front' => false ),
		)
	);

	register_post_type(
		'referenssi',
		array(
			'labels'        => kp_cpt_labels( 'Referenssi', 'Referenssit', array(
				'all_items'    => 'Kaikki referenssit',
				'add_new'      => 'Lisää uusi referenssi',
				'add_new_item' => 'Lisää uusi referenssi',
				'edit_item'    => 'Muokkaa referenssiä',
			) ),
			'public'        => true,
			'show_in_rest'  => true,
			'has_archive'   => 'referenssit',
			'menu_icon'     => 'dashicons-building',
			'menu_position' => 23,
			'supports'      => array( 'title', 'editor', 'thumbnail', 'excerpt', 'revisions', 'custom-fields' ),
			'rewrite'       => array( 'slug' => 'referenssit', 'with_front' => false ),
		)
	);

	register_post_type(
		'tyopaikka',
		array(
			'labels'        => kp_cpt_labels( 'Työpaikka', 'Työpaikat', array(
				'all_items'    => 'Kaikki työpaikat',
				'add_new'      => 'Lisää uusi työpaikka',
				'add_new_item' => 'Lisää uusi työpaikka',
				'edit_item'    => 'Muokkaa työpaikkailmoitusta',
			) ),
			'public'        => true,
			'show_in_rest'  => true,
			'has_archive'   => 'tyopaikat',
			'menu_icon'     => 'dashicons-id-alt',
			'menu_position' => 24,
			'supports'      => array( 'title', 'editor', 'thumbnail', 'excerpt', 'revisions' ),
			'rewrite'       => array( 'slug' => 'tyopaikat', 'with_front' => false ),
		)
	);

	// Lomakkeella lähetetyt yhteydenotot tallennetaan myös tietokantaan, jotta mikään ei katoa sähköpostiongelmissa.
	register_post_type(
		'kp_yhteydenotto',
		array(
			'labels'          => kp_cpt_labels( 'Yhteydenotto', 'Yhteydenotot' ),
			'public'          => false,
			'show_ui'         => true,
			'show_in_menu'    => true,
			'menu_icon'       => 'dashicons-email-alt',
			'menu_position'   => 26,
			'supports'        => array( 'title', 'editor' ),
			'capability_type' => 'post',
			'capabilities'    => array( 'create_posts' => 'do_not_allow' ),
			'map_meta_cap'    => true,
		)
	);

	register_post_type(
		'kp_pohja',
		array(
			'labels'       => kp_cpt_labels( 'Sisältöpohja', 'Sisältöpohjat', array(
				'all_items'    => 'Sisältöpohjat',
				'add_new'      => 'Lisää uusi pohja',
				'add_new_item' => 'Lisää uusi sisältöpohja',
				'edit_item'    => 'Muokkaa sisältöpohjaa',
			) ),
			'public'       => false,
			'show_ui'      => true,
			'show_in_menu' => 'kp-tyokalut',
			'supports'     => array( 'title', 'editor', 'excerpt', 'revisions' ),
		)
	);
}

function kp_register_taxonomies() {
	$common = array(
		'hierarchical'      => true,
		'public'            => true,
		'show_in_rest'      => true,
		'show_admin_column' => true,
	);

	register_taxonomy(
		'hakusanat',
		array( 'henkilokunta' ),
		array_merge( $common, array(
			'labels'  => array(
				'name'          => 'Osaaminen ja hakusanat',
				'singular_name' => 'Hakusana',
				'menu_name'     => 'Hakusanat',
				'all_items'     => 'Kaikki hakusanat',
				'add_new_item'  => 'Lisää uusi hakusana',
				'edit_item'     => 'Muokkaa hakusanaa',
				'search_items'  => 'Etsi hakusana',
				'not_found'     => 'Ei hakusanoja',
			),
			'rewrite' => array( 'slug' => 'osaaminen', 'with_front' => false ),
		) )
	);

	register_taxonomy(
		'toimipiste',
		array( 'henkilokunta', 'tyopaikka' ),
		array_merge( $common, array(
			'labels'  => array(
				'name'          => 'Toimipisteet ja alueet',
				'singular_name' => 'Toimipiste',
				'menu_name'     => 'Toimipisteet',
				'all_items'     => 'Kaikki toimipisteet',
				'add_new_item'  => 'Lisää uusi toimipiste',
				'edit_item'     => 'Muokkaa toimipistettä',
			),
			'rewrite' => array( 'slug' => 'toimipiste', 'with_front' => false ),
		) )
	);

	register_taxonomy(
		'palvelualue',
		array( 'palvelu' ),
		array_merge( $common, array(
			'labels'  => array(
				'name'          => 'Palvelualueet',
				'singular_name' => 'Palvelualue',
				'all_items'     => 'Kaikki palvelualueet',
				'add_new_item'  => 'Lisää uusi palvelualue',
				'edit_item'     => 'Muokkaa palvelualuetta',
			),
			'rewrite' => array( 'slug' => 'palvelualue', 'with_front' => false ),
		) )
	);

	register_taxonomy(
		'kohderyhma',
		array( 'palvelu', 'referenssi' ),
		array_merge( $common, array(
			'labels'  => array(
				'name'          => 'Asiakasryhmät',
				'singular_name' => 'Asiakasryhmä',
				'all_items'     => 'Kaikki asiakasryhmät',
				'add_new_item'  => 'Lisää uusi asiakasryhmä',
				'edit_item'     => 'Muokkaa asiakasryhmää',
			),
			'rewrite' => array( 'slug' => 'asiakasryhma', 'with_front' => false ),
		) )
	);

	register_taxonomy(
		'referenssityyppi',
		array( 'referenssi' ),
		array_merge( $common, array(
			'labels'  => array(
				'name'          => 'Hanketyypit',
				'singular_name' => 'Hanketyyppi',
				'all_items'     => 'Kaikki hanketyypit',
				'add_new_item'  => 'Lisää uusi hanketyyppi',
				'edit_item'     => 'Muokkaa hanketyyppiä',
			),
			'rewrite' => array( 'slug' => 'hanketyyppi', 'with_front' => false ),
		) )
	);
}
