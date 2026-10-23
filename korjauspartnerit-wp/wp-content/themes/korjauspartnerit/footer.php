<?php
/**
 * Alatunniste.
 */
defined( 'ABSPATH' ) || exit;
$phone = kp_t_setting( 'puhelin' );
$email = kp_t_setting( 'sahkoposti' );
$tel   = function_exists( 'kp_tel_href' ) ? kp_tel_href( $phone ) : '';
?>
</main>

<footer class="kp-alatunniste">
	<div class="kp-kehys kp-kehys--levea">
		<div class="kp-alatunniste__yla">
			<div class="kp-alatunniste__brandi">
				<a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="kp-alatunniste__logo"><?php echo kp_logo( 'vaaka' ); // phpcs:ignore WordPress.Security.EscapeOutput ?><span class="screen-reader-text"><?php bloginfo( 'name' ); ?></span></a>
				<p class="kp-alatunniste__lupaus">Insinööritoimisto, jolla on kova osaamistaso ja pehmeä arvomaailma.</p>
				<?php echo kp_social_links(); // phpcs:ignore WordPress.Security.EscapeOutput ?>
			</div>

			<div class="kp-alatunniste__sarake">
				<h2 class="kp-alatunniste__otsikko">Asiakaspalvelu</h2>
				<ul class="kp-alatunniste__yhteys">
					<?php if ( $phone ) : ?><li><a href="<?php echo esc_attr( $tel ); ?>"><?php echo kp_icon( 'puhelin' ); // phpcs:ignore ?><?php echo esc_html( $phone ); ?></a></li><?php endif; ?>
					<?php if ( $email ) : ?><li><a href="mailto:<?php echo esc_attr( antispambot( $email ) ); ?>"><?php echo kp_icon( 'email' ); // phpcs:ignore ?><?php echo esc_html( antispambot( $email ) ); ?></a></li><?php endif; ?>
				</ul>
				<p><a class="kp-button kp-button--reuna-valkoinen" href="<?php echo esc_url( home_url( '/yhteystiedot/#viesti' ) ); ?>">Pyydä tarjous</a></p>
			</div>

			<div class="kp-alatunniste__sarake">
				<h2 class="kp-alatunniste__otsikko">Toimipisteet</h2>
				<?php if ( function_exists( 'kp_offices' ) ) : ?>
					<ul class="kp-alatunniste__toimipisteet">
						<?php foreach ( kp_offices() as $o ) : ?>
							<li><strong><?php echo esc_html( $o['nimi'] ); ?></strong><br><?php echo esc_html( $o['osoite'] ); ?></li>
						<?php endforeach; ?>
					</ul>
				<?php endif; ?>
			</div>

			<div class="kp-alatunniste__sarake">
				<h2 class="kp-alatunniste__otsikko">Korjauspartnerit</h2>
				<?php
				wp_nav_menu( array(
					'theme_location' => 'alavalikko',
					'container'      => false,
					'menu_class'     => 'kp-alavalikko',
					'depth'          => 1,
					'fallback_cb'    => 'kp_footer_fallback_menu',
				) );
				?>
			</div>
		</div>

		<div class="kp-alatunniste__ala">
			<p>© <?php echo esc_html( gmdate( 'Y' ) ); ?> <?php echo esc_html( kp_t_setting( 'yritys', get_bloginfo( 'name' ) ) ); ?><?php if ( kp_t_setting( 'ytunnus' ) ) : ?> · Y-tunnus <?php echo esc_html( kp_t_setting( 'ytunnus' ) ); ?><?php endif; ?></p>
			<?php if ( get_privacy_policy_url() ) : ?>
				<p><a href="<?php echo esc_url( get_privacy_policy_url() ); ?>">Tietosuojaseloste</a></p>
			<?php endif; ?>
		</div>
	</div>
</footer>

<?php wp_footer(); ?>
</body>
</html>
