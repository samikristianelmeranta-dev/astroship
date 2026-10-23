<?php
/**
 * Sisältöpohjat.
 *
 * Valmiit rungot artikkeleille, sivuille, palveluille, referensseille ja työpaikoille.
 * Pohjat ovat tavallisia muokattavia sisältöjä (Korjauspartnerit → Sisältöpohjat),
 * joten pääkäyttäjät voivat muokata niitä ja lisätä uusia ilman koodia.
 *
 * Käyttö:
 *  - Korjauspartnerit → Luo uutta pohjasta, tai
 *  - "Lisää uusi" -näkymän oikean reunan laatikko "Aloita sisältöpohjasta".
 */

defined( 'ABSPATH' ) || exit;

/**
 * Sisältötyypit, joihin pohjia voi liittää.
 */
function kp_template_post_types() {
	return array(
		'post'         => 'Artikkeli',
		'page'         => 'Sivu',
		'palvelu'      => 'Palvelu',
		'referenssi'   => 'Referenssi',
		'tyopaikka'    => 'Työpaikka',
		'henkilokunta' => 'Henkilö',
	);
}

/**
 * Oletuspohjat. Luodaan aktivoinnissa ja asennusapurissa, jos samannimistä pohjaa ei ole.
 */
function kp_default_content_templates() {
	return array(
		array(
			'title'    => 'Artikkeli: Uutinen tai tiedote',
			'excerpt'  => 'Lyhyt ajankohtainen uutinen: mitä tapahtui, kenelle se on tärkeää ja mistä saa lisätietoa.',
			'types'    => array( 'post' ),
			'category' => 'ajankohtaista',
			'content'  => <<<HTML
<p class="kp-ingressi">Kirjoita tähän 1–2 lauseen tiivistelmä uutisesta: mitä tapahtui ja miksi se on lukijalle tärkeää.</p>
<p>Kerro uutisen tausta. Vastaa kysymyksiin kuka, mitä, missä, milloin ja miksi. Pidä kappaleet lyhyinä (2–4 virkettä).</p>
<h2>Väliotsikko, joka kertoo olennaisen</h2>
<p>Jatka tarinaa. Väliotsikot helpottavat lukemista ja hakukoneoptimointia.</p>
<blockquote class="kp-nosto"><p>”Sitaatti asiantuntijalta tai asiakkaalta tuo uutiseen ihmisen äänen.”</p><cite>Etunimi Sukunimi, titteli, Korjauspartnerit</cite></blockquote>
<h2>Lisätietoja</h2>
<p>[kp_henkilot nimet="Etunimi Sukunimi"]</p>
HTML,
		),
		array(
			'title'    => 'Artikkeli: Asiantuntija-artikkeli',
			'excerpt'  => 'Opastava artikkeli taloyhtiöille tai kiinteistönomistajille. Sisältää hyödyt, vaiheet, usein kysytyt kysymykset ja yhteydenoton.',
			'types'    => array( 'post' ),
			'category' => 'ajankohtaista',
			'content'  => <<<HTML
<p class="kp-ingressi">Aloita lukijan ongelmasta tai kysymyksestä. Esim. "Parvekkeiden kunto on taloyhtiön vastuulla – näin selvität tilanteen."</p>
<p>Johdantokappale: miksi aihe on ajankohtainen ja mitä lukija oppii artikkelista.</p>
<h2>Mistä on kyse?</h2>
<p>Selitä aihe selkeästi ilman turhaa ammattikieltä.</p>
<h2>Miksi asiaan kannattaa tarttua nyt?</h2>
<ul class="kp-lista-check"><li>Ensimmäinen syy tai hyöty</li><li>Toinen syy tai hyöty</li><li>Kolmas syy tai hyöty</li></ul>
<h2>Näin eteneminen tapahtuu</h2>
<ol><li><strong>Kartoitus</strong> – lyhyt kuvaus vaiheesta.</li><li><strong>Suunnittelu</strong> – lyhyt kuvaus vaiheesta.</li><li><strong>Toteutus ja valvonta</strong> – lyhyt kuvaus vaiheesta.</li></ol>
<div class="kp-laatikko"><h3>Hyvä tietää</h3><p>Tärkeä huomio, esim. lakisääteinen velvoite tai avustusmahdollisuus.</p></div>
<h2>Usein kysytyt kysymykset</h2>
<div class="kp-ukk"><details><summary>Kysymys 1?</summary><p>Vastaus.</p></details><details><summary>Kysymys 2?</summary><p>Vastaus.</p></details></div>
<div class="kp-cta"><h2>Kysy lisää asiantuntijaltamme</h2><p>Autamme mielellämme – yhteistyö kanssamme on helppoa ja nopeaa.</p><p><a class="kp-button kp-button--valkoinen" href="/yhteystiedot/#viesti">Ota yhteyttä</a></p></div>
HTML,
		),
		array(
			'title'    => 'Artikkeli: Henkilöesittely',
			'excerpt'  => 'Tutustu korjauspartneriin -juttu: kuka olet, mitä teet ja mikä työssä innostaa.',
			'types'    => array( 'post' ),
			'category' => 'hlokuntaesittely',
			'content'  => <<<HTML
<p class="kp-ingressi">Esittele henkilö yhdellä lauseella. Esim. "Aleksi tekee rakennesuunnittelua sekä töissä että vapaa-ajalla."</p>
<h2>Kuka olet ja mitä teet Korjauspartnereilla?</h2>
<p>Vastaus omin sanoin.</p>
<h2>Millainen on tyypillinen työpäiväsi?</h2>
<p>Vastaus.</p>
<blockquote class="kp-nosto"><p>”Paras asia työssäni on…”</p><cite>Etunimi Sukunimi, titteli</cite></blockquote>
<h2>Mitä teet vapaa-ajallasi?</h2>
<p>Vastaus.</p>
<p>[kp_henkilot nimet="Etunimi Sukunimi"]</p>
HTML,
		),
		array(
			'title'    => 'Artikkeli: Asiakastarina',
			'excerpt'  => 'Onnistunut hanke asiakkaan näkökulmasta: lähtötilanne, ratkaisu ja tulokset.',
			'types'    => array( 'post' ),
			'category' => 'ajankohtaista',
			'content'  => <<<HTML
<p class="kp-ingressi">Tiivistä tarina: kuka asiakas on, mikä haaste ratkaistiin ja mikä oli lopputulos.</p>
<div class="kp-laatikko kp-laatikko--hiekka"><h3>Hanke lyhyesti</h3><ul class="kp-lista-nuoli"><li>Asiakas: </li><li>Kohde: </li><li>Palvelut: </li><li>Valmistui: </li></ul></div>
<h2>Lähtötilanne</h2>
<p>Mikä oli asiakkaan tarve tai ongelma?</p>
<h2>Ratkaisu</h2>
<p>Miten Korjauspartnerit auttoi? Mitä tehtiin ja miksi?</p>
<h2>Tulokset</h2>
<ul class="kp-lista-check"><li>Mitattava tulos, esim. energiankulutus laski 30 %</li><li>Aikataulussa ja budjetissa</li></ul>
<blockquote class="kp-nosto"><p>”Asiakkaan kommentti yhteistyöstä.”</p><cite>Etunimi Sukunimi, hallituksen puheenjohtaja</cite></blockquote>
HTML,
		),
		array(
			'title'     => 'Sivu: Palvelusivu',
			'excerpt'   => 'Yksittäisen palvelun esittely: kenelle, mitä sisältyy, eteneminen, hyödyt ja yhteyshenkilöt.',
			'types'     => array( 'page', 'palvelu' ),
			'template'  => 'templates/sivupohja-palvelu.php',
			'content'   => <<<HTML
<p class="kp-ingressi">Kerro yhdellä–kahdella lauseella, mitä palvelu on ja mitä hyötyä siitä on asiakkaalle.</p>
<h2>Kenelle palvelu sopii?</h2>
<p>Taloyhtiöille, liike- ja toimitiloille, teollisuuteen… Kuvaa tyypillinen tilanne, jossa palvelua tarvitaan.</p>
<h2>Mitä palvelu sisältää?</h2>
<ul class="kp-lista-check"><li>Sisältö 1</li><li>Sisältö 2</li><li>Sisältö 3</li></ul>
<h2>Näin palvelu etenee</h2>
<div class="kp-kortit"><div class="kp-kortti"><h3>1. Kartoitus</h3><p>Lyhyt kuvaus.</p></div><div class="kp-kortti"><h3>2. Raportti</h3><p>Lyhyt kuvaus.</p></div><div class="kp-kortti"><h3>3. Jatkotoimet</h3><p>Lyhyt kuvaus.</p></div></div>
<h2>Miksi Korjauspartnerit?</h2>
<p>Meillä yksikään asiakas ei ole liian pieni, mikään projekti liian vaativa eikä kukaan ihminen merkityksetön.</p>
<h2>Referenssejä</h2>
<p>[kp_referenssit maara="3"]</p>
HTML,
		),
		array(
			'title'    => 'Sivu: Kampanja- tai laskeutumissivu',
			'excerpt'  => 'Mainoskampanjan tai webinaarin laskeutumissivu: vahva otsikko, hyödyt ja yhteydenottolomake.',
			'types'    => array( 'page' ),
			'template' => 'templates/sivupohja-laskeutumissivu.php',
			'content'  => <<<HTML
<p class="kp-ingressi">Lupaus lukijalle yhdellä lauseella – mitä hän saa, kun ottaa yhteyttä tai ilmoittautuu.</p>
<div class="kp-kortit"><div class="kp-kortti"><h3>Hyöty 1</h3><p>Lyhyt kuvaus.</p></div><div class="kp-kortti"><h3>Hyöty 2</h3><p>Lyhyt kuvaus.</p></div><div class="kp-kortti"><h3>Hyöty 3</h3><p>Lyhyt kuvaus.</p></div></div>
<h2>Miten se toimii?</h2>
<p>Kerro lyhyesti, mitä seuraavaksi tapahtuu.</p>
<blockquote class="kp-nosto"><p>”Asiakaskokemus tai sitaatti.”</p><cite>Asiakas</cite></blockquote>
<p>[kp_yhteydenotto otsikko="Pyydä tarjous"]</p>
HTML,
		),
		array(
			'title'    => 'Sivu: Yleinen sisältösivu',
			'excerpt'  => 'Perussivu: ingressi, väliotsikot ja yhteydenottonosto.',
			'types'    => array( 'page' ),
			'content'  => <<<HTML
<p class="kp-ingressi">Sivun tärkein viesti lyhyesti.</p>
<h2>Väliotsikko</h2>
<p>Leipätekstiä.</p>
<div class="kp-palstat"><div class="kp-palsta"><h3>Vasen palsta</h3><p>Tekstiä…</p></div><div class="kp-palsta"><h3>Oikea palsta</h3><p>Tekstiä…</p></div></div>
<div class="kp-cta"><h2>Ota yhteyttä</h2><p>Kerro tarpeestasi, niin ohjaamme sinut oikealle asiantuntijalle.</p><p><a class="kp-button kp-button--valkoinen" href="/yhteystiedot/#viesti">Ota yhteyttä</a></p></div>
HTML,
		),
		array(
			'title'   => 'Referenssi: Referenssikohde',
			'excerpt' => 'Toteutettu kohde: palvelut, kohteen tiedot ja projektikuvaus.',
			'types'   => array( 'referenssi' ),
			'content' => <<<HTML
<h2>Toteutetut palvelut</h2>
<ul class="kp-lista-check"><li>Hankesuunnittelu</li><li>Rakennuttaminen</li><li>Valvonta</li></ul>
<div class="kp-laatikko kp-laatikko--hiekka"><h3>Kohteen tiedot</h3><ul class="kp-lista-nuoli"><li>Rakennusvuosi: </li><li>Rakennustyyppi: </li><li>Kerroksia: </li><li>Huoneistoja: </li><li>Valmistunut: </li></ul></div>
<h2>Projektikuvaus</h2>
<p>Kuvaa lyhyesti, mitä kohteessa tehtiin ja mikä oli lopputulos.</p>
HTML,
		),
		array(
			'title'   => 'Työpaikka: Työpaikkailmoitus',
			'excerpt' => 'Avoin tehtävä: tehtävän kuvaus, odotukset, mitä tarjoamme ja miten haetaan.',
			'types'   => array( 'tyopaikka' ),
			'content' => <<<HTML
<p class="kp-ingressi">Etsimme joukkoomme [tehtävänimike] [paikkakunta]. Kerro lyhyesti, miksi tehtävä on kiinnostava.</p>
<h2>Tehtävässä pääset</h2>
<ul class="kp-lista-check"><li>Tehtävä 1</li><li>Tehtävä 2</li><li>Tehtävä 3</li></ul>
<h2>Toivomme sinulta</h2>
<ul class="kp-lista-nuoli"><li>Soveltuva koulutus</li><li>Kokemusta …</li><li>Hyvät vuorovaikutustaidot</li></ul>
<h2>Me tarjoamme</h2>
<ul class="kp-lista-check"><li>Mielenkiintoiset ja monipuoliset tehtävät</li><li>Joustavat työajat ja mahdollisuus etätyöhön</li><li>Kokeneet kollegat, joilta voi aina kysyä</li><li>Kattava työterveyshuolto ja liikunta- ja kulttuurietu</li></ul>
<h2>Kiinnostuitko?</h2>
<p>Lähetä hakemuksesi ja CV:si viimeistään [päivämäärä]. Lisätietoja tehtävästä antaa:</p>
<p>[kp_henkilot nimet="Etunimi Sukunimi"]</p>
HTML,
		),
		array(
			'title'   => 'Henkilö: Esittelyteksti',
			'excerpt' => 'Lyhyt esittely henkilön omalle sivulle.',
			'types'   => array( 'henkilokunta' ),
			'content' => <<<HTML
<p>Kerro 2–3 lauseella, missä tehtävissä henkilö toimii ja millaisissa asioissa hänen puoleensa voi kääntyä.</p>
<h3>Osaaminen</h3>
<ul class="kp-lista-check"><li>Osaamisalue 1</li><li>Osaamisalue 2</li></ul>
HTML,
		),
	);
}

function kp_seed_content_templates() {
	if ( ! post_type_exists( 'kp_pohja' ) ) {
		kp_register_post_types();
	}
	$created = 0;
	foreach ( kp_default_content_templates() as $i => $t ) {
		$exists = get_posts( array( 'post_type' => 'kp_pohja', 'title' => $t['title'], 'post_status' => 'any', 'numberposts' => 1, 'fields' => 'ids' ) );
		if ( $exists ) {
			continue;
		}
		$id = wp_insert_post( array(
			'post_type'    => 'kp_pohja',
			'post_status'  => 'publish',
			'post_title'   => $t['title'],
			'post_excerpt' => $t['excerpt'],
			'post_content' => $t['content'],
			'menu_order'   => $i,
		) );
		if ( $id && ! is_wp_error( $id ) ) {
			update_post_meta( $id, 'kp_pohja_tyypit', $t['types'] );
			if ( ! empty( $t['template'] ) ) {
				update_post_meta( $id, 'kp_pohja_sivupohja', $t['template'] );
			}
			if ( ! empty( $t['category'] ) ) {
				update_post_meta( $id, 'kp_pohja_kategoria', $t['category'] );
			}
			$created++;
		}
	}
	return $created;
}

/**
 * Pohjat, jotka koskevat annettua sisältötyyppiä.
 */
function kp_get_content_templates( $post_type = '' ) {
	$all = get_posts( array( 'post_type' => 'kp_pohja', 'post_status' => 'publish', 'numberposts' => -1, 'orderby' => array( 'menu_order' => 'ASC', 'title' => 'ASC' ) ) );
	$out = array();
	foreach ( $all as $p ) {
		$types = (array) get_post_meta( $p->ID, 'kp_pohja_tyypit', true );
		if ( $post_type && ! in_array( $post_type, $types, true ) ) {
			continue;
		}
		$cat_id = 0;
		$cat    = get_post_meta( $p->ID, 'kp_pohja_kategoria', true );
		if ( $cat ) {
			$term   = get_term_by( 'slug', $cat, 'category' );
			$cat_id = $term ? (int) $term->term_id : 0;
		}
		$out[ $p->ID ] = array(
			'id'        => $p->ID,
			'nimi'      => $p->post_title,
			'kuvaus'    => $p->post_excerpt,
			'tyypit'    => array_values( array_filter( $types ) ),
			'sisalto'   => $p->post_content,
			'sivupohja' => (string) get_post_meta( $p->ID, 'kp_pohja_sivupohja', true ),
			'kategoria' => $cat_id,
		);
	}
	return $out;
}

/* ---------- Pohjan omat asetukset (mille sisältötyypeille pohja näkyy) ---------- */

add_action( 'add_meta_boxes_kp_pohja', function () {
	add_meta_box( 'kp_pohja_asetukset', 'Pohjan asetukset', function ( $post ) {
		wp_nonce_field( 'kp_pohja', 'kp_pohja_nonce' );
		$types = (array) get_post_meta( $post->ID, 'kp_pohja_tyypit', true );
		$tpl   = get_post_meta( $post->ID, 'kp_pohja_sivupohja', true );
		$cat   = get_post_meta( $post->ID, 'kp_pohja_kategoria', true );
		echo '<p><strong>Näytä pohja näille sisältötyypeille:</strong></p>';
		foreach ( kp_template_post_types() as $k => $label ) {
			printf( '<label style="display:block"><input type="checkbox" name="kp_pohja_tyypit[]" value="%s" %s> %s</label>', esc_attr( $k ), checked( in_array( $k, $types, true ), true, false ), esc_html( $label ) );
		}
		echo '<p><label><strong>Sivupohja (vain sivuille)</strong><br><select name="kp_pohja_sivupohja"><option value="">Oletus</option>';
		foreach ( wp_get_theme()->get_page_templates( null, 'page' ) as $file => $name ) {
			printf( '<option value="%s" %s>%s</option>', esc_attr( $file ), selected( $tpl, $file, false ), esc_html( $name ) );
		}
		echo '</select></label></p>';
		echo '<p><label><strong>Kategoria (vain artikkeleille)</strong><br><select name="kp_pohja_kategoria"><option value="">Ei oletusta</option>';
		foreach ( get_categories( array( 'hide_empty' => false ) ) as $c ) {
			printf( '<option value="%s" %s>%s</option>', esc_attr( $c->slug ), selected( $cat, $c->slug, false ), esc_html( $c->name ) );
		}
		echo '</select></label></p>';
		echo '<p class="description">Ote-kenttään kirjoitettu kuvaus näytetään pohjan valinnassa.</p>';
	}, 'kp_pohja', 'side' );
} );

add_action( 'save_post_kp_pohja', function ( $post_id ) {
	if ( empty( $_POST['kp_pohja_nonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['kp_pohja_nonce'] ), 'kp_pohja' ) || ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}
	$types = array_intersect( array_map( 'sanitize_key', (array) ( $_POST['kp_pohja_tyypit'] ?? array() ) ), array_keys( kp_template_post_types() ) );
	update_post_meta( $post_id, 'kp_pohja_tyypit', array_values( $types ) );
	update_post_meta( $post_id, 'kp_pohja_sivupohja', sanitize_text_field( wp_unslash( $_POST['kp_pohja_sivupohja'] ?? '' ) ) );
	update_post_meta( $post_id, 'kp_pohja_kategoria', sanitize_title( wp_unslash( $_POST['kp_pohja_kategoria'] ?? '' ) ) );
} );

/* ---------- Pohjan valinta uuden sisällön luonnissa ---------- */

add_filter( 'default_content', function ( $content, $post ) {
	$id = isset( $_GET['kp_pohja'] ) ? absint( $_GET['kp_pohja'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification
	if ( $id && current_user_can( 'edit_posts' ) ) {
		$tpl = get_post( $id );
		if ( $tpl && 'kp_pohja' === $tpl->post_type ) {
			return $tpl->post_content;
		}
	}
	return $content;
}, 10, 2 );

add_action( 'add_meta_boxes', function ( $post_type, $post ) {
	if ( ! array_key_exists( $post_type, kp_template_post_types() ) ) {
		return;
	}
	if ( ! $post || 'auto-draft' !== $post->post_status ) {
		return; // Näytetään vain uutta sisältöä luotaessa.
	}
	if ( ! kp_get_content_templates( $post_type ) ) {
		return;
	}
	add_meta_box( 'kp_pohjavalinta', 'Aloita sisältöpohjasta', 'kp_render_template_picker', $post_type, 'side', 'high' );
}, 10, 2 );

function kp_render_template_picker( $post ) {
	$templates = kp_get_content_templates( $post->post_type );
	echo '<p>Valitse valmis runko, jota voit muokata vapaasti. Pohja korvaa editorin nykyisen sisällön.</p>';
	echo '<select id="kp-pohja-valinta" class="widefat"><option value="">– Valitse pohja –</option>';
	foreach ( $templates as $t ) {
		printf( '<option value="%d">%s</option>', (int) $t['id'], esc_html( $t['nimi'] ) );
	}
	echo '</select><p id="kp-pohja-kuvaus" class="description"></p>';
	echo '<p><button type="button" class="button button-primary" id="kp-pohja-kayta">Käytä pohjaa</button></p>';
	if ( current_user_can( 'edit_others_posts' ) ) {
		echo '<p><a href="' . esc_url( admin_url( 'edit.php?post_type=kp_pohja' ) ) . '">Muokkaa sisältöpohjia</a></p>';
	}
}

add_action( 'admin_enqueue_scripts', function ( $hook ) {
	if ( 'post-new.php' !== $hook ) {
		return;
	}
	$screen = get_current_screen();
	if ( ! $screen || ! array_key_exists( $screen->post_type, kp_template_post_types() ) ) {
		return;
	}
	wp_enqueue_script( 'kp-pohjat', KP_TYOKALUT_URL . 'assets/admin-pohjat.js', array( 'jquery' ), KP_TYOKALUT_VERSION, true );
	wp_localize_script( 'kp-pohjat', 'kpPohjat', array(
		'pohjat'    => array_values( kp_get_content_templates( $screen->post_type ) ),
		'valittu'   => isset( $_GET['kp_pohja'] ) ? absint( $_GET['kp_pohja'] ) : 0, // phpcs:ignore WordPress.Security.NonceVerification
		'vahvistus' => 'Pohja korvaa editorissa olevan tekstin. Jatketaanko?',
	) );
} );
