<?php
/**
 * Lisäkentät (meta boxit) ilman ulkoisia lisäosia.
 *
 * Kentät määritellään taulukkona ja piirretään/tallennetaan yhdellä koodilla.
 * Meta-avaimet ovat yhteensopivia vanhan ACF-toteutuksen kanssa.
 */

defined( 'ABSPATH' ) || exit;

function kp_meta_box_definitions() {
	$page_settings = array(
		'kp_ingressi'      => array( 'type' => 'textarea', 'label' => 'Ingressi', 'help' => 'Lyhyt johdanto, joka näytetään otsikon alla isommalla fontilla. Näytetään myös listauksissa.' ),
		'kp_hero_otsikko'  => array( 'type' => 'text', 'label' => 'Yläosan otsikko (valinnainen)', 'help' => 'Jos tyhjä, käytetään sivun otsikkoa.' ),
		'kp_hero_tyyli'    => array(
			'type'    => 'select',
			'label'   => 'Yläosan tyyli',
			'options' => array(
				''        => 'Automaattinen (kuva jos asetettu, muuten turkoosi)',
				'kuva'    => 'Iso kuva taustalla',
				'turkoosi' => 'Turkoosi tausta',
				'hiekka'  => 'Hiekka tausta',
				'ei'      => 'Ei yläosaa (pelkkä otsikko)',
			),
		),
		'kp_painike_teksti' => array( 'type' => 'text', 'label' => 'Yläosan painikkeen teksti', 'help' => 'Esim. "Pyydä tarjous". Jätä tyhjäksi, jos painiketta ei tarvita.' ),
		'kp_painike_linkki' => array( 'type' => 'url', 'label' => 'Yläosan painikkeen linkki' ),
		'kp_yhteyshenkilot' => array( 'type' => 'people', 'label' => 'Yhteyshenkilöt', 'help' => 'Valitut henkilöt näytetään sivun lopussa yhteystietokortteina. Pidä Ctrl/Cmd pohjassa valitaksesi useita.' ),
		'kp_nayta_lomake'   => array( 'type' => 'checkbox', 'label' => 'Näytä yhteydenottolomake sivun lopussa' ),
	);

	return array(
		'kp_henkilo' => array(
			'title'  => 'Henkilön tiedot',
			'screen' => array( 'henkilokunta' ),
			'context' => 'normal',
			'fields' => array(
				'tehtavanimike' => array( 'type' => 'text', 'label' => 'Tehtävänimike', 'help' => 'Esim. "Projektipäällikkö, LVI-suunnittelu".' ),
				'puhelinnumero' => array( 'type' => 'text', 'label' => 'Puhelinnumero', 'help' => 'Muodossa 040 123 4567 tai +358 40 123 4567.' ),
				'sahkoposti'    => array( 'type' => 'email', 'label' => 'Sähköposti' ),
				'kp_linkedin'   => array( 'type' => 'url', 'label' => 'LinkedIn-profiili (valinnainen)' ),
				'kp_johto'      => array( 'type' => 'checkbox', 'label' => 'Kuuluu johtoon (näytetään henkilökuntasivulla ensimmäisenä)' ),
			),
		),
		'kp_referenssi' => array(
			'title'  => 'Kohteen tiedot',
			'screen' => array( 'referenssi' ),
			'context' => 'normal',
			'fields' => array(
				'kohde'          => array( 'type' => 'text', 'label' => 'Toteutettu palvelu / hanke', 'help' => 'Näytetään referenssikortissa, esim. "Julkisivusaneeraus, valvonta".' ),
				'kp_tilaaja'     => array( 'type' => 'text', 'label' => 'Tilaaja' ),
				'kp_sijainti'    => array( 'type' => 'text', 'label' => 'Paikkakunta' ),
				'kp_valmistunut' => array( 'type' => 'text', 'label' => 'Valmistunut', 'help' => 'Esim. 08/2024.' ),
			),
		),
		'kp_tyopaikka' => array(
			'title'  => 'Työpaikan tiedot',
			'screen' => array( 'tyopaikka' ),
			'context' => 'normal',
			'fields' => array(
				'kp_sijainti'     => array( 'type' => 'text', 'label' => 'Sijainti', 'help' => 'Esim. Helsinki tai Turku / etätyö.' ),
				'kp_tyosuhde'     => array( 'type' => 'text', 'label' => 'Työsuhde', 'help' => 'Esim. Vakituinen, kokoaikainen.' ),
				'kp_haku_paattyy' => array( 'type' => 'date', 'label' => 'Haku päättyy', 'help' => 'Ilmoitus piilotetaan listauksesta automaattisesti päättymispäivän jälkeen. Jätä tyhjäksi, jos haku on jatkuva.' ),
				'kp_hakulinkki'   => array( 'type' => 'url', 'label' => 'Hakulomakkeen linkki', 'help' => 'Jos tyhjä, Hae-painike avaa sähköpostin osoitteeseen alla.' ),
				'kp_hakuemail'    => array( 'type' => 'email', 'label' => 'Hakemukset sähköpostiin' ),
			),
		),
		'kp_sivu' => array(
			'title'   => 'Sivun asetukset',
			'screen'  => array( 'page', 'post', 'palvelu', 'referenssi' ),
			'context' => 'normal',
			'fields'  => $page_settings,
		),
	);
}

add_action( 'add_meta_boxes', function () {
	foreach ( kp_meta_box_definitions() as $id => $box ) {
		add_meta_box( $id, $box['title'], 'kp_render_meta_box', $box['screen'], $box['context'], 'high', array( 'id' => $id ) );
	}
} );

function kp_render_meta_box( $post, $args ) {
	$defs = kp_meta_box_definitions();
	$box  = $defs[ $args['args']['id'] ];
	wp_nonce_field( 'kp_meta_' . $args['args']['id'], 'kp_meta_nonce_' . $args['args']['id'] );
	echo '<div class="kp-fields">';
	foreach ( $box['fields'] as $key => $f ) {
		$val  = get_post_meta( $post->ID, $key, true );
		$name = 'kp_meta[' . esc_attr( $key ) . ']';
		echo '<div class="kp-field kp-field--' . esc_attr( $f['type'] ) . '">';
		if ( 'checkbox' === $f['type'] ) {
			printf(
				'<label><input type="hidden" name="%1$s" value="0"><input type="checkbox" name="%1$s" value="1" %2$s> %3$s</label>',
				$name,
				checked( (bool) $val, true, false ),
				esc_html( $f['label'] )
			);
		} else {
			printf( '<label for="kp-%1$s"><strong>%2$s</strong></label>', esc_attr( $key ), esc_html( $f['label'] ) );
			switch ( $f['type'] ) {
				case 'textarea':
					printf( '<textarea id="kp-%1$s" name="%2$s" rows="3" class="widefat">%3$s</textarea>', esc_attr( $key ), $name, esc_textarea( $val ) );
					break;
				case 'select':
					printf( '<select id="kp-%1$s" name="%2$s">', esc_attr( $key ), $name );
					foreach ( $f['options'] as $ov => $ol ) {
						printf( '<option value="%s" %s>%s</option>', esc_attr( $ov ), selected( $val, $ov, false ), esc_html( $ol ) );
					}
					echo '</select>';
					break;
				case 'people':
					$people = get_posts( array( 'post_type' => 'henkilokunta', 'numberposts' => -1, 'orderby' => 'title', 'order' => 'ASC', 'post_status' => 'publish' ) );
					$sel    = array_map( 'intval', (array) $val );
					printf( '<input type="hidden" name="%s[]" value="">', $name );
					printf( '<select id="kp-%1$s" name="%2$s[]" multiple size="8" class="widefat">', esc_attr( $key ), $name );
					foreach ( $people as $p ) {
						$t = get_post_meta( $p->ID, 'tehtavanimike', true );
						printf( '<option value="%d" %s>%s%s</option>', $p->ID, selected( in_array( $p->ID, $sel, true ), true, false ), esc_html( $p->post_title ), $t ? ' – ' . esc_html( $t ) : '' );
					}
					echo '</select>';
					break;
				default:
					printf( '<input id="kp-%1$s" type="%2$s" name="%3$s" value="%4$s" class="widefat">', esc_attr( $key ), esc_attr( $f['type'] ), $name, esc_attr( $val ) );
			}
		}
		if ( ! empty( $f['help'] ) ) {
			echo '<p class="description">' . esc_html( $f['help'] ) . '</p>';
		}
		echo '</div>';
	}
	echo '</div>';
}

add_action( 'save_post', function ( $post_id, $post ) {
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}
	if ( ! isset( $_POST['kp_meta'] ) || ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}
	$input = wp_unslash( $_POST['kp_meta'] ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
	foreach ( kp_meta_box_definitions() as $id => $box ) {
		if ( ! in_array( $post->post_type, $box['screen'], true ) ) {
			continue;
		}
		$nonce = 'kp_meta_nonce_' . $id;
		if ( empty( $_POST[ $nonce ] ) || ! wp_verify_nonce( sanitize_key( $_POST[ $nonce ] ), 'kp_meta_' . $id ) ) {
			continue;
		}
		foreach ( $box['fields'] as $key => $f ) {
			if ( ! array_key_exists( $key, $input ) ) {
				continue;
			}
			$raw = $input[ $key ];
			switch ( $f['type'] ) {
				case 'textarea':
					$v = sanitize_textarea_field( $raw );
					break;
				case 'email':
					$v = sanitize_email( $raw );
					break;
				case 'url':
					$v = esc_url_raw( trim( $raw ) );
					break;
				case 'checkbox':
					$v = $raw ? '1' : '';
					break;
				case 'people':
					$v = array_values( array_filter( array_map( 'absint', (array) $raw ) ) );
					break;
				case 'select':
					$v = array_key_exists( $raw, $f['options'] ) ? $raw : '';
					break;
				default:
					$v = sanitize_text_field( $raw );
			}
			if ( '' === $v || array() === $v ) {
				delete_post_meta( $post_id, $key );
			} else {
				update_post_meta( $post_id, $key, $v );
			}
		}
	}
}, 10, 2 );

add_action( 'admin_head', function () {
	echo '<style>.kp-fields{display:grid;gap:14px}.kp-field label strong{display:block;margin-bottom:4px}.kp-field .description{margin:4px 0 0}.kp-field select:not([multiple]){min-width:280px}</style>';
} );

/**
 * Henkilökunnan listaukseen lisäsarakkeet.
 */
add_filter( 'manage_henkilokunta_posts_columns', function ( $cols ) {
	$new = array();
	foreach ( $cols as $k => $v ) {
		$new[ $k ] = $v;
		if ( 'title' === $k ) {
			$new['kp_kuva']    = 'Kuva';
			$new['kp_titteli'] = 'Tehtävänimike';
			$new['kp_yhteys']  = 'Yhteystiedot';
		}
	}
	return $new;
} );

add_action( 'manage_henkilokunta_posts_custom_column', function ( $col, $id ) {
	if ( 'kp_kuva' === $col ) {
		echo get_the_post_thumbnail( $id, array( 48, 48 ), array( 'style' => 'border-radius:50%;width:48px;height:48px;object-fit:cover' ) );
	} elseif ( 'kp_titteli' === $col ) {
		echo esc_html( get_post_meta( $id, 'tehtavanimike', true ) );
	} elseif ( 'kp_yhteys' === $col ) {
		echo esc_html( get_post_meta( $id, 'sahkoposti', true ) ) . '<br>' . esc_html( get_post_meta( $id, 'puhelinnumero', true ) );
	}
}, 10, 2 );
