<?php
/**
 * Palvelusivun sivupalkki: yhteyslaatikko ja muut palvelut samalta palvelualueelta.
 */
defined( 'ABSPATH' ) || exit;
$post   = get_post();
$ids    = array_filter( array_map( 'absint', (array) get_post_meta( $post->ID, 'kp_yhteyshenkilot', true ) ) );
$person = ( $ids && function_exists( 'kp_person' ) ) ? kp_person( $ids[0] ) : null;
$phone  = $person && $person['puhelin'] ? $person['puhelin'] : kp_t_setting( 'puhelin' );
$email  = $person && $person['sahkoposti'] ? $person['sahkoposti'] : kp_t_setting( 'sahkoposti' );

$related = array();
if ( 'palvelu' === $post->post_type ) {
	$terms = get_the_terms( $post, 'palvelualue' );
	if ( $terms && ! is_wp_error( $terms ) ) {
		$related = get_posts( array(
			'post_type'    => 'palvelu',
			'numberposts'  => 8,
			'post__not_in' => array( $post->ID ),
			'orderby'      => 'title',
			'order'        => 'ASC',
			'tax_query'    => array( array( 'taxonomy' => 'palvelualue', 'terms' => wp_list_pluck( $terms, 'term_id' ) ) ), // phpcs:ignore
		) );
	}
} else {
	$related = get_pages( array( 'child_of' => $post->post_parent ? $post->post_parent : $post->ID, 'exclude' => $post->ID, 'number' => 8 ) );
}
?>
<aside class="kp-sivupalkki" aria-label="Lisätietoja">
	<div class="kp-sivupalkki__laatikko kp-sivupalkki__laatikko--turkoosi">
		<h2>Kysy lisää</h2>
		<?php if ( $person ) : ?>
			<div class="kp-sivupalkki__henkilo">
				<?php if ( $person['kuva_id'] ) { echo wp_get_attachment_image( $person['kuva_id'], 'thumbnail', false, array( 'alt' => '' ) ); } ?>
				<p><strong><?php echo esc_html( $person['nimi'] ); ?></strong><br><?php echo esc_html( $person['titteli'] ); ?></p>
			</div>
		<?php else : ?>
			<p>Asiantuntijamme vastaavat mielellään kysymyksiisi.</p>
		<?php endif; ?>
		<ul class="kp-sivupalkki__yhteys">
			<?php if ( $phone ) : ?><li><a href="<?php echo esc_attr( kp_tel_href( $phone ) ); ?>"><?php echo kp_icon( 'puhelin' ); // phpcs:ignore ?><?php echo esc_html( $phone ); ?></a></li><?php endif; ?>
			<?php if ( $email ) : ?><li><a href="mailto:<?php echo esc_attr( antispambot( $email ) ); ?>"><?php echo kp_icon( 'email' ); // phpcs:ignore ?><?php echo esc_html( antispambot( $email ) ); ?></a></li><?php endif; ?>
		</ul>
		<p><a class="kp-button kp-button--valkoinen" href="#viesti">Pyydä tarjous</a></p>
	</div>
	<?php if ( $related ) : ?>
		<nav class="kp-sivupalkki__laatikko" aria-label="Liittyvät palvelut">
			<h2>Katso myös</h2>
			<ul class="kp-sivupalkki__linkit">
				<?php foreach ( $related as $r ) : ?>
					<li><a href="<?php echo esc_url( get_permalink( $r ) ); ?>"><?php echo esc_html( get_the_title( $r ) ); ?></a></li>
				<?php endforeach; ?>
			</ul>
		</nav>
	<?php endif; ?>
</aside>
