<?php
/**
 * Henkilöhaku. Suodatus tapahtuu selaimessa (assets/tyokalut.js), joten haku on välitön.
 *
 * @var array  $people   kp_person()-taulukot.
 * @var array  $offices  WP_Term[] toimipisteet.
 * @var array  $keywords WP_Term[] hakusanat.
 * @var string $initial  Alkuarvo hakukenttään (?haku=).
 */
defined( 'ABSPATH' ) || exit;
$uid = wp_unique_id( 'kp-haku-' );
?>
<div class="kp-henkilohaku" data-kp-henkilohaku>
	<form class="kp-henkilohaku__ohjaimet" role="search" onsubmit="return false">
		<div class="kp-henkilohaku__kentta">
			<label for="<?php echo esc_attr( $uid ); ?>">Hae nimellä, tehtävällä tai osaamisella</label>
			<input id="<?php echo esc_attr( $uid ); ?>" type="search" data-kp-haku value="<?php echo esc_attr( $initial ); ?>" placeholder="Esim. LVI-suunnittelu, valvonta tai nimi" autocomplete="off">
		</div>
		<?php if ( $keywords ) : ?>
			<div class="kp-henkilohaku__kentta">
				<label for="<?php echo esc_attr( $uid ); ?>-osaaminen">Osaamisalue</label>
				<select id="<?php echo esc_attr( $uid ); ?>-osaaminen" data-kp-osaaminen>
					<option value="">Kaikki osaamisalueet</option>
					<?php foreach ( $keywords as $t ) : ?>
						<option value="<?php echo esc_attr( $t->slug ); ?>"><?php echo esc_html( $t->name ); ?></option>
					<?php endforeach; ?>
				</select>
			</div>
		<?php endif; ?>
		<?php if ( $offices ) : ?>
			<div class="kp-henkilohaku__chipit" role="group" aria-label="Toimipiste">
				<button type="button" class="kp-chip is-active" data-kp-toimipiste="">Kaikki toimipisteet</button>
				<?php foreach ( $offices as $t ) : ?>
					<button type="button" class="kp-chip" data-kp-toimipiste="<?php echo esc_attr( $t->slug ); ?>"><?php echo esc_html( $t->name ); ?></button>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>
	</form>
	<p class="kp-henkilohaku__tulos" aria-live="polite" data-kp-tulos><?php echo esc_html( sprintf( '%d henkilöä', count( $people ) ) ); ?></p>
	<div class="kp-henkilot">
		<?php
		foreach ( $people as $h ) :
			$search = implode( ' ', array_merge(
				array( $h['nimi'], $h['titteli'], $h['sahkoposti'] ),
				wp_list_pluck( $h['hakusanat'], 'name' ),
				wp_list_pluck( $h['toimipisteet'], 'name' )
			) );
			?>
			<div class="kp-henkilohaku__rivi"
				data-haku="<?php echo esc_attr( remove_accents( mb_strtolower( $search ) ) ); ?>"
				data-osaaminen="<?php echo esc_attr( implode( ' ', wp_list_pluck( $h['hakusanat'], 'slug' ) ) ); ?>"
				data-toimipiste="<?php echo esc_attr( implode( ' ', wp_list_pluck( $h['toimipisteet'], 'slug' ) ) ); ?>">
				<?php echo kp_get_template( 'henkilo-kortti', array( 'henkilo' => $h ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
			</div>
		<?php endforeach; ?>
	</div>
	<p class="kp-henkilohaku__eitulosta" data-kp-eitulosta hidden>Hakuehdoilla ei löytynyt henkilöitä. Soita asiakaspalveluumme <a href="<?php echo esc_attr( kp_tel_href( kp_setting( 'puhelin' ) ) ); ?>"><?php echo esc_html( kp_setting( 'puhelin' ) ); ?></a>, niin ohjaamme sinut oikealle asiantuntijalle.</p>
</div>
