<?php
/**
 * Plugin Name: TiliHub – sisältöpäivitys
 * Description: Kertakäyttöinen päivitys: siirtää palvelusivujen sisällön sivupohjista tavallisiksi sivuiksi ja korjaa artikkelien hinnat (Premium-paketti pois). Näyttää ensin esikatselun. Jokaisesta muutoksesta jää versio, joten muutokset voi perua.
 * Version: 1.0.0
 * Requires at least: 6.6
 * Requires PHP: 7.4
 * Author: TiliHub Oy
 * Text Domain: tilihub-sisaltopaivitys
 *
 * @package TiliHubSisaltopaivitys
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

const TILIHUB_SP_SLUG = 'tilihub-sisaltopaivitys';

/**
 * Päivitysdata (data/updates.json).
 *
 * @return array{pages: array, posts: array}
 */
function tilihub_sp_data() {
	static $data = null;
	if ( null === $data ) {
		$json = file_get_contents( __DIR__ . '/data/updates.json' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		$data = json_decode( (string) $json, true );
		if ( ! is_array( $data ) ) {
			$data = array( 'pages' => array(), 'posts' => array(), 'trash' => array() );
		}
	}
	return $data;
}

/**
 * Selvittää yhden kohteen tilan.
 *
 * @param array $item  Päivitettävä kohde.
 * @param bool  $page  Onko kyseessä sivu.
 * @return array{status: string, label: string, post: WP_Post|null}
 */
function tilihub_sp_status( $item, $page ) {
	$post = get_post( (int) $item['id'] );
	$type = $page ? 'page' : 'post';
	if ( ! $post || $post->post_type !== $type ) {
		// Varalla haetaan osoitteen perusteella.
		$post = get_page_by_path( $item['slug'], OBJECT, $type );
	}
	if ( ! $post ) {
		return array( 'status' => 'missing', 'label' => 'Ei löydy – ohitetaan', 'post' => null );
	}
	$current = (string) $post->post_content;
	if ( $current === $item['content'] ) {
		$tpl_ok = ! $page || get_page_template_slug( $post ) === $item['template'];
		if ( $tpl_ok ) {
			return array( 'status' => 'done', 'label' => 'Jo päivitetty', 'post' => $post );
		}
	}
	if ( $page ) {
		return array( 'status' => 'update', 'label' => 'Sisältö siirretään sivulle', 'post' => $post );
	}
	// XML-vienti muuntaa rivinvaihdot muotoon \n, joten verrataan samassa muodossa.
	if ( md5( str_replace( "\r\n", "\n", $current ) ) === $item['old_md5'] ) {
		return array( 'status' => 'update', 'label' => 'Hinnat korjataan', 'post' => $post );
	}
	return array( 'status' => 'changed', 'label' => 'Muokattu viennin jälkeen – ohitetaan, ellei pakoteta', 'post' => $post );
}

add_action(
	'admin_menu',
	static function () {
		add_management_page(
			'TiliHub-sisältöpäivitys',
			'TiliHub-sisältöpäivitys',
			'manage_options',
			TILIHUB_SP_SLUG,
			'tilihub_sp_render_page'
		);
	}
);

/**
 * Tekee päivitykset.
 *
 * @param bool $force Päivitetäänkö myös viennin jälkeen muokatut artikkelit.
 * @return array<int, string> Tulosrivit.
 */
function tilihub_sp_run( $force ) {
	global $wpdb;
	$data   = tilihub_sp_data();
	$result = array();

	kses_remove_filters();

	foreach ( $data['pages'] as $item ) {
		$s = tilihub_sp_status( $item, true );
		if ( 'update' !== $s['status'] ) {
			$result[] = sprintf( '%s: %s', $item['slug'], $s['label'] );
			continue;
		}
		// Vanhan teeman sivupohja ei ole enää olemassa, joten sivupohja vaihdetaan samalla.
		$res = wp_update_post(
			wp_slash(
				array(
					'ID'            => $s['post']->ID,
					'post_content'  => $item['content'],
					'page_template' => $item['template'],
				)
			),
			true
		);
		if ( is_wp_error( $res ) ) {
			$result[] = sprintf( '%s: VIRHE %s', $item['slug'], $res->get_error_message() );
			continue;
		}
		$result[] = sprintf( '%s: sisältö siirretty sivulle, sivupohjaksi "Laskeutumissivu"', get_the_title( $s['post'] ) );
	}

	$aioseo_table = $wpdb->prefix . 'aioseo_posts';
	$has_aioseo   = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $aioseo_table ) ) === $aioseo_table; // phpcs:ignore WordPress.DB.DirectDatabaseQuery

	foreach ( $data['posts'] as $item ) {
		$s = tilihub_sp_status( $item, false );
		if ( 'update' !== $s['status'] && ! ( $force && 'changed' === $s['status'] ) ) {
			$result[] = sprintf( '%s: %s', $item['slug'], $s['label'] );
			continue;
		}
		$res = wp_update_post(
			wp_slash(
				array(
					'ID'           => $s['post']->ID,
					'post_content' => $item['content'],
				)
			),
			true
		);
		if ( is_wp_error( $res ) ) {
			$result[] = sprintf( '%s: VIRHE %s', $item['slug'], $res->get_error_message() );
			continue;
		}
		if ( ! empty( $item['seo_description'] ) ) {
			update_post_meta( $s['post']->ID, '_aioseo_description', $item['seo_description'] );
			if ( $has_aioseo ) {
				$wpdb->update( $aioseo_table, array( 'description' => $item['seo_description'] ), array( 'post_id' => $s['post']->ID ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			}
		}
		$result[] = sprintf( '%s: hinnat korjattu', get_the_title( $s['post'] ) );
	}

	foreach ( $data['trash'] ?? array() as $item ) {
		$page = get_post( (int) $item['id'] );
		if ( ! $page || 'page' !== $page->post_type || $page->post_name !== $item['slug'] ) {
			$page = get_page_by_path( $item['slug'], OBJECT, 'page' );
		}
		if ( ! $page || 'trash' === $page->post_status ) {
			$result[] = sprintf( '%s: jo poistettu', $item['slug'] );
			continue;
		}
		wp_trash_post( $page->ID );
		$result[] = sprintf( '%s: siirretty roskakoriin, osoite ohjautuu sivulle %s', $item['title'], $item['redirect'] );
	}

	kses_init_filters();
	return $result;
}

/**
 * Hallintasivu: esikatselu ja suoritus.
 */
function tilihub_sp_render_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	$data    = tilihub_sp_data();
	$results = null;

	if ( isset( $_POST['tilihub_sp_run'] ) ) {
		check_admin_referer( 'tilihub_sp_run' );
		$results = tilihub_sp_run( ! empty( $_POST['tilihub_sp_force'] ) );
	}

	$theme_ok = 'tilihub' === get_stylesheet() || 'tilihub' === get_template();
	?>
	<div class="wrap">
		<h1>TiliHub-sisältöpäivitys</h1>

		<?php if ( null !== $results ) : ?>
			<div class="notice notice-success"><p><strong>Päivitys tehty.</strong> Jokaisesta muutoksesta on tallennettu versio (sivun muokkausnäkymä → Versiot), josta aiemman sisällön voi palauttaa.</p></div>
			<ul style="list-style:disc;padding-left:1.5em">
				<?php foreach ( $results as $line ) : ?>
					<li><?php echo esc_html( $line ); ?></li>
				<?php endforeach; ?>
			</ul>
			<p>Kun kaikki näyttää hyvältä, tämän lisäosan voi poistaa käytöstä ja poistaa.</p>
			<hr>
		<?php endif; ?>

		<?php if ( ! $theme_ok ) : ?>
			<div class="notice notice-warning"><p>TiliHub-teema ei ole käytössä. Ota teema käyttöön ennen päivitystä, sillä sivut käyttävät teeman "Laskeutumissivu"-sivupohjaa.</p></div>
		<?php endif; ?>

		<p>Päivitys tekee kaksi asiaa:</p>
		<ol>
			<li><strong>Palvelusivut tavallisiksi sivuiksi:</strong> etusivun, kirjanpidon, palkanlaskennan, tilinpäätöksen, hinnaston, Tietoa meistä -sivun ja yhteystietojen sisältö siirretään sivujen omaksi sisällöksi. Sen jälkeen niitä muokataan tavallisessa sivueditorissa.</li>
			<li><strong>Hoiva-alan sivu poistetaan:</strong> sivu siirretään roskakoriin, ja sen osoite ohjataan pysyvästi kirjanpitosivulle.</li>
			<li><strong>Artikkelien hinnat:</strong> Premium-paketti poistetaan ja hinnat korjataan nykyisen hinnaston mukaisiksi (kirjanpito alkaen 55 € + alv / kk, 55 €/h, asiantuntijatyö 79 €/h, verosuunnittelu 119 €/h). Tarkat muutokset ovat lisäosan tiedostossa <code>MUUTOKSET.md</code>.</li>
		</ol>

		<h2>Sivut</h2>
		<?php tilihub_sp_table( $data['pages'], true ); ?>

		<?php if ( ! empty( $data['trash'] ) ) : ?>
			<h2>Poistettavat sivut</h2>
			<table class="widefat striped" style="max-width:60rem">
				<thead><tr><th>Sivu</th><th>Tila</th></tr></thead>
				<tbody>
				<?php foreach ( $data['trash'] as $item ) : ?>
					<?php $tp = get_page_by_path( $item['slug'], OBJECT, 'page' ); ?>
					<tr>
						<td><?php echo esc_html( $item['title'] ); ?> (<code>/<?php echo esc_html( $item['slug'] ); ?>/</code>)</td>
						<td><?php echo esc_html( $tp ? 'Siirretään roskakoriin, osoite ohjautuu sivulle ' . $item['redirect'] : 'Jo poistettu' ); ?></td>
					</tr>
				<?php endforeach; ?>
				</tbody>
			</table>
		<?php endif; ?>

		<h2>Artikkelit</h2>
		<?php tilihub_sp_table( $data['posts'], false ); ?>

		<form method="post" style="margin-top:2em">
			<?php wp_nonce_field( 'tilihub_sp_run' ); ?>
			<p><label><input type="checkbox" name="tilihub_sp_force" value="1"> Päivitä myös artikkelit, joita on muokattu viennin (10.10.2026) jälkeen. Muokkaukset korvautuvat, mutta ne jäävät versioihin.</label></p>
			<p><button type="submit" name="tilihub_sp_run" value="1" class="button button-primary button-hero">Suorita päivitys</button></p>
		</form>
	</div>
	<?php
}

/**
 * Esikatselutaulukko.
 *
 * @param array $items Kohteet.
 * @param bool  $page  Sivut vai artikkelit.
 */
function tilihub_sp_table( $items, $page ) {
	?>
	<table class="widefat striped" style="max-width:60rem">
		<thead><tr><th>Otsikko</th><th>Osoite</th><th>Tila</th></tr></thead>
		<tbody>
		<?php foreach ( $items as $item ) : ?>
			<?php $s = tilihub_sp_status( $item, $page ); ?>
			<tr>
				<td><?php echo esc_html( $s['post'] ? get_the_title( $s['post'] ) : ( $item['title'] ?? $item['slug'] ) ); ?></td>
				<td><?php if ( $s['post'] ) : ?><a href="<?php echo esc_url( get_permalink( $s['post'] ) ); ?>" target="_blank" rel="noopener"><?php echo esc_html( wp_make_link_relative( get_permalink( $s['post'] ) ) ); ?></a><?php endif; ?></td>
				<td><?php echo esc_html( $s['label'] ); ?></td>
			</tr>
		<?php endforeach; ?>
		</tbody>
	</table>
	<?php
}
