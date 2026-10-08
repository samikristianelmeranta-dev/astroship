<?php
/**
 * Liidien käsittely: lomakkeelta tullut yhteydenotto → FluentCRM-kontakti →
 * automaatioiden käynnistys.
 *
 * Markkinointilupa (sähköisen suoramarkkinoinnin ennakkosuostumus):
 * - Palveluviesti (vahvistus, esite, laskelma) lähtee aina, koska asiakas pyysi sitä.
 * - Jatkoviestit lähtevät vain, jos asiakas valitsi luparuudun. Tuplavahvistuksen
 *   ollessa päällä jatkoviestit odottavat, kunnes asiakas on vahvistanut luvan.
 * - Aiemmin tilauksen perunutta kontaktia ei palauteta tilaajaksi ilman uutta lupaa.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Tagien nimet. Asennus luo nämä FluentCRM:ään.
 */
function nordic_crm_tag_names() {
	return array(
		'tarjous'   => 'Lähde: Tarjouspyyntö',
		'taloyhtio' => 'Lähde: Taloyhtiö',
		'esite'     => 'Lähde: Esite',
		'laskuri'   => 'Lähde: Laskuri',
		'opas'      => 'Lähde: Opas',
		'consent'   => 'Markkinointilupa',
	);
}

/**
 * Kontaktin lisäkentät (laskurin tulos ym.).
 */
function nordic_crm_custom_fields() {
	return array(
		array( 'slug' => 'laskuri_ikkunat', 'label' => 'Laskuri: ikkunoita/ovia', 'type' => 'number' ),
		array( 'slug' => 'laskuri_ika', 'label' => 'Laskuri: ikkunoiden ikä', 'type' => 'text' ),
		array( 'slug' => 'laskuri_lammitys', 'label' => 'Laskuri: lämmitysmuoto', 'type' => 'text' ),
		array( 'slug' => 'laskuri_saasto', 'label' => 'Laskuri: arvioitu säästö €/v', 'type' => 'number' ),
		array( 'slug' => 'laskuri_kwh', 'label' => 'Laskuri: säästö kWh/v', 'type' => 'number' ),
		array( 'slug' => 'liidin_sivu', 'label' => 'Yhteydenoton sivu', 'type' => 'text' ),
		array( 'slug' => 'opas_viimeisin', 'label' => 'Viimeksi ladattu opas', 'type' => 'text' ),
	);
}

/**
 * Hakee tagin ID:n nimen perusteella (luo puuttuvan).
 */
function nordic_crm_tag_id( $key ) {
	$names = nordic_crm_tag_names();
	if ( ! isset( $names[ $key ] ) || ! class_exists( '\FluentCrm\App\Models\Tag' ) ) {
		return 0;
	}
	$tag = \FluentCrm\App\Models\Tag::where( 'title', $names[ $key ] )->first();
	if ( ! $tag ) {
		$tag = \FluentCrm\App\Models\Tag::create( array(
			'title' => $names[ $key ],
			'slug'  => sanitize_title( $names[ $key ] ),
		) );
	}
	return (int) $tag->id;
}

/**
 * Pääfunktio: käsittelee yhden yhteydenoton.
 *
 * @param array $lead {
 *   email, first_name, last_name, phone, type (tarjous|taloyhtio|esite|laskuri|opas),
 *   consent (bool), source_url, custom (array lisäkenttiä), guide_id (opas)
 * }
 * @return \FluentCrm\App\Models\Subscriber|false
 */
function nordic_crm_process_lead( $lead ) {
	if ( ! function_exists( 'FluentCrmApi' ) ) {
		return false;
	}
	$lead = wp_parse_args( $lead, array(
		'email'      => '',
		'first_name' => '',
		'last_name'  => '',
		'phone'      => '',
		'type'       => 'tarjous',
		'consent'    => false,
		'source_url' => '',
		'custom'     => array(),
		'guide_id'   => 0,
	) );
	$lead['email'] = sanitize_email( $lead['email'] );
	if ( ! is_email( $lead['email'] ) ) {
		return false;
	}

	$s        = nordic_crm_settings();
	$existing = \FluentCrm\App\Models\Subscriber::where( 'email', $lead['email'] )->first();
	$status   = nordic_crm_target_status( $existing, (bool) $lead['consent'], ! empty( $s['double_optin'] ) );

	$data = array(
		'email' => $lead['email'],
	);
	foreach ( array( 'first_name', 'last_name', 'phone' ) as $k ) {
		if ( $lead[ $k ] !== '' ) {
			$data[ $k ] = sanitize_text_field( $lead[ $k ] );
		}
	}
	if ( $status ) {
		$data['status'] = $status;
	}
	$custom = (array) $lead['custom'];
	if ( $lead['source_url'] ) {
		$custom['liidin_sivu'] = esc_url_raw( $lead['source_url'] );
	}
	if ( $custom ) {
		$data['custom_values'] = array_map( 'sanitize_text_field', $custom );
	}
	$data['source'] = 'nordic-crm:' . $lead['type'];

	$subscriber = FluentCrmApi( 'contacts' )->createOrUpdate( $data );
	if ( ! $subscriber ) {
		return false;
	}

	$tags = array_filter( array( nordic_crm_tag_id( $lead['type'] ) ) );
	if ( $lead['consent'] ) {
		$tags[] = nordic_crm_tag_id( 'consent' );
		fluentcrm_update_subscriber_meta( $subscriber->id, '_nordic_consent_at', current_time( 'mysql' ) );
		fluentcrm_update_subscriber_meta( $subscriber->id, '_nordic_consent_source', esc_url_raw( $lead['source_url'] ) );
	}
	if ( $lead['guide_id'] && function_exists( 'nordic_crm_guide' ) ) {
		$guide = nordic_crm_guide( $lead['guide_id'] );
		if ( $guide ) {
			$tags[] = nordic_crm_guide_tag_id( $guide['title'] );
			// Oppaan viestin muuttujat ({{nordic.opas_nimi}}, {{nordic.opas_url}}) lukevat tämän.
			fluentcrm_update_subscriber_meta( $subscriber->id, '_nordic_opas_id', (int) $guide['id'] );
		}
	}
	if ( $tags ) {
		$subscriber->attachTags( array_filter( $tags ) );
	}

	$subscriber = $subscriber->fresh();

	// Tuplavahvistus: lähetetään kerran, vaikka useampi automaatio käynnistyy.
	if ( $lead['consent'] && 'pending' === $subscriber->status ) {
		$subscriber->sendDoubleOptinEmail();
	}

	/**
	 * Käynnistää Nordic CRM -automaatiot (FluentCRM:n oma käynnistin kuuntelee tätä).
	 *
	 * @param array      $lead
	 * @param Subscriber $subscriber
	 */
	do_action( NORDIC_CRM_TRIGGER, $lead, $subscriber );

	return $subscriber;
}

/**
 * Mikä status kontaktille asetetaan. Palauttaa '' kun statusta ei muuteta.
 */
function nordic_crm_target_status( $existing, $consent, $double_optin ) {
	$opted_in = $double_optin ? 'pending' : 'subscribed';

	if ( ! $existing ) {
		return $consent ? $opted_in : 'transactional';
	}
	if ( 'subscribed' === $existing->status ) {
		return '';
	}
	if ( $consent ) {
		// Uusi, nimenomainen lupa: myös aiemmin perunut voi tilata uudelleen (vahvistettuna).
		if ( in_array( $existing->status, array( 'bounced', 'complained', 'spammed' ), true ) ) {
			return '';
		}
		return $opted_in;
	}
	return '';
}

/* -------------------------------------------------------------------------
 * Fluent Forms: tarjouspyyntölomakkeet
 * ---------------------------------------------------------------------- */

add_action( 'fluentform/submission_inserted', 'nordic_crm_on_fluentform', 20, 3 );

function nordic_crm_on_fluentform( $insert_id, $form_data, $form ) {
	$s   = nordic_crm_settings();
	$ids = array_filter( array_map( 'absint', explode( ',', (string) $s['quote_form_ids'] ) ) );
	if ( ! $form || ! in_array( (int) $form->id, $ids, true ) ) {
		return;
	}

	$lead = nordic_crm_extract_from_form_data( $form_data );
	if ( ! $lead['email'] ) {
		return;
	}

	$source            = isset( $form_data['_wp_http_referer'] ) ? home_url( $form_data['_wp_http_referer'] ) : '';
	$lead['source_url'] = $source;
	$match             = trim( (string) $s['taloyhtio_match'] );
	$lead['type']      = ( $match && $source && stripos( $source, $match ) !== false ) ? 'taloyhtio' : 'tarjous';
	$lead['consent']   = nordic_crm_truthy( isset( $form_data[ $s['consent_field'] ] ) ? $form_data[ $s['consent_field'] ] : '' );

	nordic_crm_process_lead( $lead );
}

/**
 * Poimii nimen, sähköpostin ja puhelimen Fluent Forms -datasta, vaikka kenttien
 * nimet vaihtelisivat (names/first_name, email/sahkoposti, phone/puhelin ...).
 */
function nordic_crm_extract_from_form_data( $data ) {
	$lead = array( 'email' => '', 'first_name' => '', 'last_name' => '', 'phone' => '' );

	foreach ( $data as $key => $value ) {
		if ( strpos( (string) $key, '_' ) === 0 ) {
			continue;
		}
		$k = strtolower( (string) $key );
		if ( is_array( $value ) ) {
			// Fluent Formsin nimikenttä: names[first_name], names[last_name]
			if ( isset( $value['first_name'] ) || isset( $value['last_name'] ) ) {
				$lead['first_name'] = $lead['first_name'] ?: trim( (string) ( $value['first_name'] ?? '' ) );
				$lead['last_name']  = $lead['last_name'] ?: trim( (string) ( $value['last_name'] ?? '' ) );
			}
			continue;
		}
		$value = trim( (string) $value );
		if ( '' === $value ) {
			continue;
		}
		if ( ! $lead['email'] && ( is_email( $value ) && ( strpos( $k, 'mail' ) !== false || strpos( $k, 'posti' ) !== false || 'email' === $k ) ) ) {
			$lead['email'] = $value;
		} elseif ( ! $lead['phone'] && preg_match( '/phone|puhelin|puh|tel/', $k ) ) {
			$lead['phone'] = $value;
		} elseif ( ! $lead['first_name'] && preg_match( '/^(first_name|etunimi)$/', $k ) ) {
			$lead['first_name'] = $value;
		} elseif ( ! $lead['last_name'] && preg_match( '/^(last_name|sukunimi)$/', $k ) ) {
			$lead['last_name'] = $value;
		} elseif ( ! $lead['first_name'] && preg_match( '/^(name|nimi|full_name|koko_nimi)$/', $k ) ) {
			$parts              = preg_split( '/\s+/', $value, 2 );
			$lead['first_name'] = $parts[0];
			$lead['last_name']  = isset( $parts[1] ) ? $parts[1] : '';
		}
	}

	// Varmuuden vuoksi: mikä tahansa kenttä, jonka arvo on sähköpostiosoite.
	if ( ! $lead['email'] ) {
		foreach ( $data as $key => $value ) {
			if ( is_string( $value ) && is_email( trim( $value ) ) && strpos( (string) $key, '_' ) !== 0 ) {
				$lead['email'] = trim( $value );
				break;
			}
		}
	}
	return $lead;
}

function nordic_crm_truthy( $value ) {
	if ( is_array( $value ) ) {
		return count( array_filter( $value ) ) > 0;
	}
	$value = strtolower( trim( (string) $value ) );
	return '' !== $value && ! in_array( $value, array( '0', 'no', 'ei', 'false' ), true );
}

/* -------------------------------------------------------------------------
 * Jatkoviestien pysäytys: "Asiakas", "Ei kiinnostunut" ym.
 * ---------------------------------------------------------------------- */

add_action( 'fluentcrm_contact_added_to_tags', 'nordic_crm_maybe_stop_sequences', 10, 2 );

function nordic_crm_maybe_stop_sequences( $tag_ids, $subscriber ) {
	$s     = nordic_crm_settings();
	$names = array_filter( array_map( 'trim', explode( ',', (string) $s['stop_tags'] ) ) );
	if ( ! $names || ! class_exists( '\FluentCrm\App\Models\Tag' ) ) {
		return;
	}
	$stop_ids = \FluentCrm\App\Models\Tag::whereIn( 'title', $names )->pluck( 'id' )->toArray();
	if ( ! array_intersect( array_map( 'intval', (array) $tag_ids ), array_map( 'intval', $stop_ids ) ) ) {
		return;
	}
	foreach ( nordic_crm_installed_funnels() as $info ) {
		if ( 'consent' === $info['audience'] && ! empty( $info['funnel_id'] ) ) {
			\FluentCrm\App\Services\Funnel\FunnelHelper::removeSubscribersFromFunnel( (int) $info['funnel_id'], array( $subscriber->id ) );
		}
	}
}
