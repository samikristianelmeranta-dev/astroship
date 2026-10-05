<?php
/**
 * Asetukset: Asetukset → Nordic Chat.
 *
 * API-avain kannattaa määrittää wp-config.php:ssä:
 *   define( 'NORDIC_CHAT_ANTHROPIC_KEY', 'sk-ant-...' );
 * Silloin se ei ole tietokannassa eikä näy hallintapaneelissa.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

function nordic_chat_defaults() {
	return array(
		'enabled'        => 1,
		'api_key'        => '',
		'contact_email'  => 'info@ikkunakauppias.fi',
		'daily_limit'    => 400,   // viestiä vuorokaudessa koko sivustolla (kustannusraja)
		'ip_hour_limit'  => 30,    // viestiä tunnissa per IP
		'session_limit'  => 40,    // viestiä per keskustelu
		'greeting'       => 'Hei! Olen Nordic Ikkunat & Ovien tekoälyavustaja. Kysy vaikka ikkunamalleista, ulko-ovista, remontin kulusta tai hinnoista. Voin myös välittää viestisi suoraan meille.',
	);
}

function nordic_chat_settings() {
	return wp_parse_args( get_option( 'nordic_chat_settings', array() ), nordic_chat_defaults() );
}

/**
 * API-avain: wp-config.php:n vakio ensisijaisesti, muuten asetus.
 */
function nordic_chat_api_key() {
	if ( defined( 'NORDIC_CHAT_ANTHROPIC_KEY' ) && NORDIC_CHAT_ANTHROPIC_KEY ) {
		return (string) NORDIC_CHAT_ANTHROPIC_KEY;
	}
	$s = nordic_chat_settings();
	return (string) $s['api_key'];
}

function nordic_chat_is_ready() {
	$s = nordic_chat_settings();
	return ! empty( $s['enabled'] ) && '' !== nordic_chat_api_key() && file_exists( NORDIC_CHAT_DIR . '/vendor/autoload.php' );
}

add_action( 'admin_menu', function () {
	add_options_page( 'Nordic Chat', 'Nordic Chat', 'manage_options', 'nordic-chat', 'nordic_chat_settings_page' );
} );

add_filter( 'plugin_action_links_' . plugin_basename( NORDIC_CHAT_FILE ), function ( $links ) {
	array_unshift( $links, '<a href="' . esc_url( admin_url( 'options-general.php?page=nordic-chat' ) ) . '">Asetukset</a>' );
	return $links;
} );

add_action( 'admin_init', function () {
	register_setting( 'nordic_chat', 'nordic_chat_settings', array(
		'type'              => 'array',
		'sanitize_callback' => function ( $in ) {
			$d   = nordic_chat_defaults();
			$old = nordic_chat_settings();
			return array(
				'enabled'       => empty( $in['enabled'] ) ? 0 : 1,
				// Tyhjä kenttä säilyttää aiemman avaimen (avainta ei näytetä lomakkeella).
				'api_key'       => ! empty( $in['api_key'] ) ? sanitize_text_field( $in['api_key'] ) : $old['api_key'],
				'contact_email' => is_email( $in['contact_email'] ?? '' ) ? sanitize_email( $in['contact_email'] ) : $d['contact_email'],
				'daily_limit'   => max( 1, absint( $in['daily_limit'] ?? $d['daily_limit'] ) ),
				'ip_hour_limit' => max( 1, absint( $in['ip_hour_limit'] ?? $d['ip_hour_limit'] ) ),
				'session_limit' => max( 1, absint( $in['session_limit'] ?? $d['session_limit'] ) ),
				'greeting'      => sanitize_textarea_field( $in['greeting'] ?? $d['greeting'] ),
			);
		},
	) );
} );

function nordic_chat_settings_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	$s          = nordic_chat_settings();
	$from_const = defined( 'NORDIC_CHAT_ANTHROPIC_KEY' ) && NORDIC_CHAT_ANTHROPIC_KEY;
	$has_vendor = file_exists( NORDIC_CHAT_DIR . '/vendor/autoload.php' );
	$today      = (int) get_transient( 'nordic_chat_day_' . gmdate( 'Ymd' ) );
	?>
	<div class="wrap">
		<h1>Nordic Chat – tekoälyavustaja</h1>
		<?php if ( ! $has_vendor ) : ?>
			<div class="notice notice-error"><p>Lisäosan kirjastot puuttuvat (vendor-kansio). Asenna lisäosa valmiista zip-paketista.</p></div>
		<?php endif; ?>
		<?php if ( '' === nordic_chat_api_key() ) : ?>
			<div class="notice notice-warning"><p>Chat ei ole vielä käytössä: lisää Anthropic API -avain.</p></div>
		<?php endif; ?>
		<p>Viestejä tänään: <strong><?php echo (int) $today; ?></strong> / <?php echo (int) $s['daily_limit']; ?></p>

		<form method="post" action="options.php">
			<?php settings_fields( 'nordic_chat' ); ?>
			<table class="form-table" role="presentation">
				<tr><th scope="row">Chat käytössä</th><td><label><input type="checkbox" name="nordic_chat_settings[enabled]" value="1" <?php checked( ! empty( $s['enabled'] ) ); ?>> Näytä chat sivustolla</label></td></tr>
				<tr><th scope="row"><label for="nc-key">Anthropic API -avain</label></th><td>
					<?php if ( $from_const ) : ?>
						<p>Avain on määritetty wp-config.php:ssä (<code>NORDIC_CHAT_ANTHROPIC_KEY</code>).</p>
					<?php else : ?>
						<input type="password" class="regular-text" id="nc-key" name="nordic_chat_settings[api_key]" value="" placeholder="<?php echo $s['api_key'] ? '•••••••• (tallennettu)' : 'sk-ant-…'; ?>" autocomplete="off">
						<p class="description">Suositus: lisää avain mieluummin wp-config.php:hen: <code>define( 'NORDIC_CHAT_ANTHROPIC_KEY', 'sk-ant-…' );</code> Avaimen saa osoitteesta console.anthropic.com. Aseta konsolissa avaimelle myös kuukausittainen kulukatto.</p>
					<?php endif; ?>
				</td></tr>
				<tr><th scope="row"><label for="nc-email">Yhteydenotot osoitteeseen</label></th><td><input type="email" class="regular-text" id="nc-email" name="nordic_chat_settings[contact_email]" value="<?php echo esc_attr( $s['contact_email'] ); ?>"></td></tr>
				<tr><th scope="row"><label for="nc-greet">Tervehdys</label></th><td><textarea class="large-text" rows="3" id="nc-greet" name="nordic_chat_settings[greeting]"><?php echo esc_textarea( $s['greeting'] ); ?></textarea></td></tr>
				<tr><th scope="row">Rajat</th><td>
					<p><label><input type="number" min="1" name="nordic_chat_settings[daily_limit]" value="<?php echo (int) $s['daily_limit']; ?>" style="width:90px"> viestiä vuorokaudessa koko sivustolla</label></p>
					<p><label><input type="number" min="1" name="nordic_chat_settings[ip_hour_limit]" value="<?php echo (int) $s['ip_hour_limit']; ?>" style="width:90px"> viestiä tunnissa samasta osoitteesta</label></p>
					<p><label><input type="number" min="1" name="nordic_chat_settings[session_limit]" value="<?php echo (int) $s['session_limit']; ?>" style="width:90px"> viestiä yhdessä keskustelussa</label></p>
					<p class="description">Rajat suojaavat kuluilta ja väärinkäytöltä. Kun raja täyttyy, chat ohjaa ottamaan yhteyttä sähköpostilla.</p>
				</td></tr>
			</table>
			<?php submit_button( 'Tallenna' ); ?>
		</form>
	</div>
	<?php
}
