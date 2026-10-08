<?php
/**
 * Asennus: tagit, lisäkentät, tuplavahvistusviesti ja automaatiot.
 *
 * Voidaan ajaa uudelleen turvallisesti (Asetukset → Nordic CRM → Asenna / päivitä):
 * - puuttuvat automaatiot luodaan,
 * - lisäosan viestit päivitetään uusimpaan versioon vain, jos niitä ei ole
 *   muokattu FluentCRM:ssä (tai jos päivitys pakotetaan),
 * - automaatioiden rakennetta (odotusajat, lisätyt vaiheet) ei muuteta, jos
 *   automaatio on jo olemassa.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

use FluentCrm\App\Models\Funnel;
use FluentCrm\App\Models\FunnelCampaign;
use FluentCrm\App\Models\FunnelSequence;
use FluentCrm\App\Services\Funnel\FunnelHelper;

function nordic_crm_installed_funnels() {
	return (array) get_option( 'nordic_crm_funnels', array() );
}

/**
 * @param bool $force Korvaa myös käsin muokatut viestit.
 * @return array Yhteenveto.
 */
function nordic_crm_install( $force = false ) {
	$result = array( 'created' => array(), 'guides' => array(), 'updated' => array(), 'kept' => array(), 'errors' => array() );
	if ( ! defined( 'FLUENTCRM' ) || ! class_exists( '\FluentCrm\App\Models\Funnel' ) ) {
		$result['errors'][] = 'FluentCRM ei ole käytössä.';
		return $result;
	}

	// 1) Tagit
	foreach ( array_keys( nordic_crm_tag_names() ) as $key ) {
		nordic_crm_tag_id( $key );
	}
	$s = nordic_crm_settings();
	foreach ( array_filter( array_map( 'trim', explode( ',', (string) $s['stop_tags'] ) ) ) as $name ) {
		if ( ! \FluentCrm\App\Models\Tag::where( 'title', $name )->first() ) {
			\FluentCrm\App\Models\Tag::create( array( 'title' => $name, 'slug' => sanitize_title( $name ) ) );
		}
	}

	// 2) Lisäkentät
	$fields   = (array) fluentcrm_get_option( 'contact_custom_fields', array() );
	$existing = wp_list_pluck( $fields, 'slug' );
	foreach ( nordic_crm_custom_fields() as $field ) {
		if ( ! in_array( $field['slug'], $existing, true ) ) {
			$fields[] = array(
				'label' => $field['label'],
				'slug'  => $field['slug'],
				'type'  => $field['type'],
				'group' => 'Nordic',
			);
		}
	}
	fluentcrm_update_option( 'contact_custom_fields', array_values( $fields ) );

	// 3) Tuplavahvistusviesti suomeksi
	$doi_hash = (string) get_option( 'nordic_crm_doi_hash', '' );
	$current  = (array) fluentcrm_get_option( 'double_optin_settings', array() );
	$doi      = nordic_crm_double_optin_email();
	$doi_body = nordic_crm_render_email( $doi['body'], 'Yksi klikkaus, niin tilaus on voimassa.', 'Saat tämän viestin, koska tilauslomakkeelle syötettiin tämä osoite sivustollamme ' . wp_parse_url( home_url(), PHP_URL_HOST ) . '.', false );
	if ( $force || ! $current || md5( (string) ( $current['email_body'] ?? '' ) ) === $doi_hash ) {
		fluentcrm_update_option( 'double_optin_settings', array(
			'email_subject'           => $doi['subject'],
			'email_pre_header'        => 'Yksi klikkaus, niin tilaus on voimassa.',
			'design_template'         => 'raw_html',
			'email_body'              => $doi_body,
			'after_confirmation_type' => 'message',
			'after_confirm_message'   => '<h2>Kiitos, tilaus on vahvistettu</h2><p>Lähetämme sinulle muutaman viestin ikkuna- ja oviremontista. Jokaisen viestin lopussa on linkki, josta voit perua tilauksen.</p><p><a href="' . esc_url( home_url( '/' ) ) . '">Takaisin sivustolle</a></p>',
			'after_conf_redirect_url' => '',
		) );
		update_option( 'nordic_crm_doi_hash', md5( $doi_body ), false );
	}

	// 4) Valmiit oppaat (Skaalan tuote-esitteet) mediakirjastoon
	$result['guides'] = nordic_crm_install_default_guides();

	// 5) Automaatiot
	$installed = nordic_crm_installed_funnels();
	foreach ( nordic_crm_flows() as $flow ) {
		try {
			$info = isset( $installed[ $flow['key'] ] ) ? $installed[ $flow['key'] ] : null;
			if ( $info && ! empty( $info['funnel_id'] ) && Funnel::find( (int) $info['funnel_id'] ) ) {
				$installed[ $flow['key'] ] = nordic_crm_update_flow( $flow, $info, $force, $result );
			} else {
				$installed[ $flow['key'] ] = nordic_crm_create_flow( $flow );
				$result['created'][]       = $flow['title'];
			}
		} catch ( \Throwable $e ) {
			$result['errors'][] = $flow['title'] . ': ' . $e->getMessage();
		}
	}
	// Poistuneet automaatiot (korvattu uudemmilla): asetetaan luonnokseksi, jotta ne eivät enää käynnisty.
	foreach ( array( 'opas-jatko' ) as $retired ) {
		if ( ! empty( $installed[ $retired ]['funnel_id'] ) ) {
			Funnel::where( 'id', (int) $installed[ $retired ]['funnel_id'] )->update( array( 'status' => 'draft' ) );
			unset( $installed[ $retired ] );
			$result['updated'][] = 'vanha opassarja pois käytöstä';
		}
	}
	update_option( 'nordic_crm_funnels', $installed, false );
	update_option( 'nordic_crm_installed_version', NORDIC_CRM_VERSION, false );

	return $result;
}

/**
 * Valmis HTML yhdelle sarjan viestille.
 */
function nordic_crm_step_html( $flow, $step ) {
	$body = str_replace( '%site%', untrailingslashit( home_url() ), $step['body'] );
	return nordic_crm_render_email(
		$body,
		$step['preheader'],
		nordic_crm_reason_for( $flow['lead_type'], $flow['audience'] ),
		'consent' === $flow['audience']
	);
}

function nordic_crm_campaign_data( $flow, $step ) {
	$mock                     = FunnelCampaign::getMock();
	$mock['email_subject']    = $step['subject'];
	$mock['email_pre_header'] = $step['preheader'];
	$mock['email_body']       = nordic_crm_step_html( $flow, $step );
	$mock['design_template']  = 'raw_html';
	$mock['settings']         = array(
		'template_config' => \FluentCrm\App\Services\Helper::getTemplateConfig( 'raw_html' ),
		'mailer_settings' => array(
			'from_name'      => '',
			'from_email'     => '',
			'reply_to_name'  => '',
			'reply_to_email' => '',
			'is_custom'      => 'no',
		),
	);
	return $mock;
}

function nordic_crm_wait_label( $days ) {
	return 1 === (int) $days ? 'Odota 1 päivä' : 'Odota ' . (int) $days . ' päivää';
}

function nordic_crm_create_flow( $flow ) {
	$funnel_settings = array(
		'lead_type'           => $flow['lead_type'],
		'audience'            => $flow['audience'],
		'__force_run_actions' => 'all' === $flow['audience'] ? 'yes' : 'no',
	);
	$conditions = array( 'run_only_one' => 'all' === $flow['audience'] ? 'no' : 'yes' );

	$funnel = Funnel::create( array(
		'title'        => 'Nordic: ' . $flow['title'],
		'trigger_name' => NORDIC_CRM_TRIGGER,
		'status'       => 'draft',
		'settings'     => $funnel_settings,
		'conditions'   => $conditions,
		'created_by'   => get_current_user_id(),
	) );

	$sequences = array();
	foreach ( $flow['steps'] as $step ) {
		if ( ! empty( $step['wait'] ) ) {
			$sequences[] = array(
				'type'        => 'action',
				'action_name' => 'fluentcrm_wait_times',
				'title'       => nordic_crm_wait_label( $step['wait'] ),
				'description' => '',
				'settings'    => array(
					'wait_type'         => 'unit_wait',
					'wait_time_amount'  => (int) $step['wait'],
					'wait_time_unit'    => 'days',
					'is_timestamp_wait' => '',
					'wait_date_time'    => '',
					'to_day'            => array(),
					'to_day_time'       => '',
				),
			);
		}
		$sequences[] = array(
			'type'        => 'action',
			'action_name' => 'send_custom_email',
			'title'       => $step['subject'],
			'description' => '',
			'settings'    => array(
				'reference_campaign' => '',
				'send_email_to_type' => 'contact',
				'send_email_custom'  => '',
				'campaign'           => nordic_crm_campaign_data( $flow, $step ),
				'is_scheduled'       => 'no',
				'scheduled_at'       => '',
				'skip_if_overdue'    => 'no',
				'mailer_settings'    => array(
					'from_name'      => '',
					'from_email'     => '',
					'reply_to_name'  => '',
					'reply_to_email' => '',
					'is_custom'      => 'no',
				),
			),
		);
	}

	FunnelHelper::saveFunnelSequence( $funnel->id, array(
		'funnel_settings' => wp_json_encode( $funnel_settings ),
		'conditions'      => wp_json_encode( $conditions ),
		'status'          => 'published',
		'funnel_title'    => 'Nordic: ' . $flow['title'],
		'sequences'       => wp_json_encode( $sequences ),
	) );

	// Talletetaan viestien ID:t ja tarkisteet päivityksiä varten.
	$email_sequences = FunnelSequence::where( 'funnel_id', $funnel->id )
		->where( 'action_name', 'send_custom_email' )
		->orderBy( 'sequence', 'ASC' )
		->get();
	$steps = array();
	$i     = 0;
	foreach ( $flow['steps'] as $step ) {
		$seq          = isset( $email_sequences[ $i ] ) ? $email_sequences[ $i ] : null;
		$campaign_id  = $seq ? (int) ( $seq->settings['reference_campaign'] ?? 0 ) : 0;
		$campaign     = $campaign_id ? FunnelCampaign::find( $campaign_id ) : null;
		$steps[ $step['key'] ] = array(
			'campaign_id' => $campaign_id,
			'hash'        => $campaign ? nordic_crm_campaign_hash( $campaign ) : '',
		);
		$i++;
	}

	return array(
		'funnel_id' => (int) $funnel->id,
		'lead_type' => $flow['lead_type'],
		'audience'  => $flow['audience'],
		'steps'     => $steps,
	);
}

function nordic_crm_campaign_hash( $campaign ) {
	return md5( $campaign->email_subject . '|' . $campaign->email_pre_header . '|' . $campaign->email_body );
}

/**
 * Päivittää olemassa olevan automaation viestit, jos niitä ei ole muokattu käsin.
 */
function nordic_crm_update_flow( $flow, $info, $force, &$result ) {
	foreach ( $flow['steps'] as $step ) {
		$step_info = isset( $info['steps'][ $step['key'] ] ) ? $info['steps'][ $step['key'] ] : null;
		$campaign  = ( $step_info && $step_info['campaign_id'] ) ? FunnelCampaign::find( (int) $step_info['campaign_id'] ) : null;
		if ( ! $campaign ) {
			$result['kept'][] = $step['subject'] . ' (viesti poistettu FluentCRM:stä, ei palautettu)';
			continue;
		}
		$untouched = nordic_crm_campaign_hash( $campaign ) === $step_info['hash'];
		$new       = nordic_crm_campaign_data( $flow, $step );
		$changed   = $campaign->email_subject !== $new['email_subject']
			|| $campaign->email_pre_header !== $new['email_pre_header']
			|| $campaign->email_body !== $new['email_body'];
		if ( ! $changed ) {
			continue;
		}
		if ( $untouched || $force ) {
			FunnelCampaign::where( 'id', $campaign->id )->update( array(
				'email_subject'    => $new['email_subject'],
				'email_pre_header' => $new['email_pre_header'],
				'email_body'       => $new['email_body'],
				'design_template'  => 'raw_html',
			) );
			$campaign                                       = FunnelCampaign::find( $campaign->id );
			$info['steps'][ $step['key'] ]['hash'] = nordic_crm_campaign_hash( $campaign );
			$result['updated'][]                            = $step['subject'];
		} else {
			$result['kept'][] = $step['subject'] . ' (muokattu käsin, ei korvattu)';
		}
	}
	return $info;
}

function nordic_crm_install_summary( $r ) {
	$parts = array();
	if ( ! empty( $r['created'] ) ) {
		$parts[] = 'Luotiin ' . count( $r['created'] ) . ' automaatiota.';
	}
	if ( ! empty( $r['guides'] ) ) {
		$parts[] = 'Lisättiin ' . count( $r['guides'] ) . ' opasta kohtaan Oppaat.';
	}
	if ( ! empty( $r['updated'] ) ) {
		$parts[] = 'Päivitettiin ' . count( $r['updated'] ) . ' viestiä.';
	}
	if ( ! empty( $r['kept'] ) ) {
		$parts[] = 'Ennallaan: ' . esc_html( implode( '; ', $r['kept'] ) ) . '.';
	}
	if ( ! empty( $r['errors'] ) ) {
		$parts[] = '<strong>Virheet:</strong> ' . esc_html( implode( '; ', $r['errors'] ) );
	}
	if ( ! $parts ) {
		$parts[] = 'Kaikki oli jo ajan tasalla.';
	}
	return 'Nordic CRM: ' . implode( ' ', $parts );
}
