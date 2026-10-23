<?php
/**
 * Etusivu.
 *
 * Yläosan otsikko ja teksti: Etusivu-sivun kentät "Yläosan otsikko" ja "Ingressi"
 * (tai Mukauta → Etusivu). Kuva: Etusivu-sivun artikkelikuva, oletuksena assets/img/etusivu-hero.webp.
 * Sivun editorisisältö näytetään kohderyhmäkorttien jälkeen.
 */
defined( 'ABSPATH' ) || exit;
get_header();

$front    = get_post( get_option( 'page_on_front' ) );
$title    = $front ? get_post_meta( $front->ID, 'kp_hero_otsikko', true ) : '';
$lead     = $front ? get_post_meta( $front->ID, 'kp_ingressi', true ) : '';
$image_id = $front ? kp_t_thumb( $front ) : 0;

$page_link = function ( $slug, $fallback = '' ) {
	$p = get_page_by_path( $slug );
	return $p ? get_permalink( $p ) : ( $fallback ? $fallback : home_url( '/' ) );
};
?>

<section class="kp-etusivu-hero">
	<div class="kp-etusivu-hero__kuva">
		<?php
		if ( $image_id ) {
			echo wp_get_attachment_image( $image_id, 'kp-hero', false, array( 'loading' => 'eager', 'fetchpriority' => 'high', 'alt' => '', 'sizes' => '(min-width: 960px) 62vw, 100vw' ) );
		} else {
			// Oletuskuva teemasta. Vaihdetaan asettamalla Etusivu-sivulle artikkelikuva.
			$img = get_template_directory_uri() . '/assets/img/';
			printf(
				'<img src="%1$setusivu-hero.webp" srcset="%1$setusivu-hero-800.webp 800w, %1$setusivu-hero.webp 1536w" sizes="(min-width: 960px) 62vw, 100vw" width="1536" height="1024" alt="Korjauspartnereiden asiantuntijat kohteella" fetchpriority="high">',
				esc_url( $img )
			);
		}
		?>
	</div>
	<div class="kp-etusivu-hero__sisus kp-kehys kp-kehys--levea">
		<div class="kp-etusivu-hero__tekstit">
			<h1 class="kp-etusivu-hero__otsikko"><?php echo esc_html( $title ? $title : kp_mod( 'kp_hero_otsikko' ) ); ?></h1>
			<p class="kp-etusivu-hero__teksti"><?php echo esc_html( $lead ? $lead : kp_mod( 'kp_hero_teksti' ) ); ?></p>
			<div class="kp-hero__painikkeet">
				<a class="kp-button kp-button--valkoinen" href="<?php echo esc_url( get_post_type_archive_link( 'palvelu' ) ? get_post_type_archive_link( 'palvelu' ) : home_url( '/' ) ); ?>">Tutustu palveluihin</a>
				<a class="kp-button kp-button--reuna-valkoinen" href="<?php echo esc_url( home_url( '/yhteystiedot/#viesti' ) ); ?>">Pyydä tarjous</a>
			</div>
		</div>
	</div>
</section>

<section class="kp-pikahaku" aria-labelledby="kp-pikahaku-otsikko">
	<div class="kp-kehys kp-kehys--levea kp-pikahaku__sisus">
		<div>
			<h2 id="kp-pikahaku-otsikko" class="kp-pikahaku__otsikko">Tiedätkö jo mitä etsit?</h2>
			<p>Hae palvelun tai henkilön nimellä – esim. asbestikartoitus, kuntotutkimus, sisäilmatutkimus, TDD tai valvonta.</p>
		</div>
		<form role="search" method="get" action="<?php echo esc_url( home_url( '/' ) ); ?>" class="kp-pikahaku__lomake">
			<label class="screen-reader-text" for="kp-etusivuhaku">Hae sivustolta</label>
			<input id="kp-etusivuhaku" type="search" name="s" placeholder="Mitä etsit?">
			<button class="kp-button" type="submit">Hae</button>
		</form>
	</div>
</section>

<section class="kp-osio kp-osio--kohderyhmat">
	<div class="kp-kehys kp-kehys--levea">
		<header class="kp-osio__yla">
			<p class="kp-ylaotsikko">Palvelut</p>
			<h2 class="kp-osio__otsikko">Asiantuntijapalvelut kiinteistöjen koko elinkaarelle</h2>
		</header>
		<div class="kp-kohderyhmat">
			<?php
			$groups = array(
				array( 'Taloyhtiöille', 'Kuntotutkimukset, PTS, linjasaneeraukset, julkisivu- ja parvekekorjaukset sekä energiaremontit.', $page_link( 'kiinteistojen-palvelut-taloyhtioille' ) ),
				array( 'Liike- ja toimitiloille', 'Teknisten riskien arvioinnit (TDD), vuokralaismuutokset ja peruskorjaukset ilman katkoja.', $page_link( 'kiinteistojen-palvelut-liike-ja-toimitiloille' ) ),
				array( 'Teollisuudelle', 'Teollisuuskiinteistöjen ja tuotannon ylläpito, koneturvallisuus ja automaatiosuunnittelu.', $page_link( 'optimoi-teollisuuskiinteiston-ja-tuotannon-yllapito' ) ),
				array( 'Ylläpitopartneri', 'Kiinteistön ennakoiva ylläpito ja vuositarkastukset yhdeltä kumppanilta.', ( function_exists( 'get_page_by_path' ) && get_page_by_path( 'yllapitopartneri', OBJECT, 'palvelu' ) ) ? get_permalink( get_page_by_path( 'yllapitopartneri', OBJECT, 'palvelu' ) ) : home_url( '/palvelut/' ) ),
			);
			foreach ( $groups as $i => $g ) :
				?>
				<a class="kp-kohderyhma" href="<?php echo esc_url( $g[2] ); ?>">
					<span class="kp-kohderyhma__numero" aria-hidden="true"><?php echo esc_html( str_pad( (string) ( $i + 1 ), 2, '0', STR_PAD_LEFT ) ); ?></span>
					<span class="kp-kohderyhma__otsikko"><?php echo esc_html( $g[0] ); ?></span>
					<span class="kp-kohderyhma__teksti"><?php echo esc_html( $g[1] ); ?></span>
					<span class="kp-nuolilinkki">Katso palvelut</span>
				</a>
			<?php endforeach; ?>
		</div>
	</div>
</section>

<?php if ( $front && trim( $front->post_content ) ) : ?>
	<section class="kp-osio kp-osio--sisalto">
		<div class="kp-kehys kp-sisalto">
			<?php echo apply_filters( 'the_content', $front->post_content ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
		</div>
	</section>
<?php endif; ?>

<section class="kp-teesi">
	<div class="kp-kehys kp-kehys--levea kp-teesi__sisus">
		<div class="kp-teesi__teksti">
			<p class="kp-ylaotsikko kp-ylaotsikko--vaalea">Korjauspartnerit</p>
			<h2 class="kp-teesi__otsikko"><?php echo esc_html( kp_mod( 'kp_teesi_otsikko' ) ); ?></h2>
			<p><?php echo esc_html( kp_mod( 'kp_teesi_teksti' ) ); ?></p>
			<p><a class="kp-button kp-button--valkoinen" href="<?php echo esc_url( $page_link( 'korjauspartnerit' ) ); ?>">Tutustu meihin</a></p>
		</div>
		<dl class="kp-luvut">
			<?php for ( $i = 1; $i <= 3; $i++ ) : ?>
				<?php if ( kp_mod( 'kp_luku_' . $i ) ) : ?>
					<div class="kp-luku">
						<dt><?php echo esc_html( kp_mod( 'kp_luku_' . $i . '_selite' ) ); ?></dt>
						<dd><?php echo esc_html( kp_mod( 'kp_luku_' . $i ) ); ?></dd>
					</div>
				<?php endif; ?>
			<?php endfor; ?>
		</dl>
	</div>
</section>

<?php if ( kp_mod( 'kp_nayta_referenssit' ) && function_exists( 'kp_sc_referenssit' ) && wp_count_posts( 'referenssi' )->publish ) : ?>
	<section class="kp-osio">
		<div class="kp-kehys kp-kehys--levea">
			<header class="kp-osio__yla kp-osio__yla--rivi">
				<div>
					<p class="kp-ylaotsikko">Referenssit</p>
					<h2 class="kp-osio__otsikko">Toteutettuja hankkeita</h2>
				</div>
				<a class="kp-nuolilinkki" href="<?php echo esc_url( get_post_type_archive_link( 'referenssi' ) ); ?>">Kaikki referenssit</a>
			</header>
			<?php echo kp_sc_referenssit( array( 'maara' => 6 ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
		</div>
	</section>
<?php endif; ?>

<?php if ( kp_mod( 'kp_nayta_ajankohtaiset' ) && function_exists( 'kp_sc_ajankohtaiset' ) ) : ?>
	<section class="kp-osio kp-osio--hiekka">
		<div class="kp-kehys kp-kehys--levea">
			<header class="kp-osio__yla kp-osio__yla--rivi">
				<div>
					<p class="kp-ylaotsikko">Ajankohtaista</p>
					<h2 class="kp-osio__otsikko">Korjauspartnereiden ja alan kuulumisia</h2>
				</div>
				<?php if ( get_option( 'page_for_posts' ) ) : ?>
					<a class="kp-nuolilinkki" href="<?php echo esc_url( get_permalink( get_option( 'page_for_posts' ) ) ); ?>">Kaikki artikkelit</a>
				<?php endif; ?>
			</header>
			<?php
			// Etusivulla näytetään kategorian "Ajankohtaista footer" artikkelit, kuten vanhan sivuston alatunnisteessa.
			// Jos kategoriassa ei ole artikkeleita, näytetään uusimmat.
			$news = kp_sc_ajankohtaiset( array( 'maara' => absint( kp_mod( 'kp_ajankohtaiset_maara' ) ), 'kategoria' => kp_mod( 'kp_ajankohtaiset_kategoria' ) ) );
			if ( ! $news ) {
				$news = kp_sc_ajankohtaiset( array( 'maara' => absint( kp_mod( 'kp_ajankohtaiset_maara' ) ) ) );
			}
			echo $news; // phpcs:ignore WordPress.Security.EscapeOutput
			?>
		</div>
	</section>
<?php endif; ?>

<?php get_template_part( 'template-parts/cta' ); ?>

<?php
get_footer();
