<?php
/**
 * Tarjouspyyntö → asiakas + kohde.
 *
 * Kun asetuksissa valittu Fluent Forms -lomake lähetetään:
 *  1. asiakas haetaan sähköpostin perusteella tai luodaan uusi
 *  2. luodaan kohde vaiheeseen "Tarjouspyyntö"
 *  3. vastuumyyjälle tehtävä "Soita ja sovi mittaus" (seuraava arkipäivä)
 *  4. asiakkaalle kiitosviesti ja linkki Oma kohde -sivulle
 * Suosittelukoodi (eväste nap_ref) liittää asiakkaan suosittelijaan.
 */

defined( 'ABSPATH' ) || exit;

add_action( 'fluentform/submission_inserted', 'nap_on_fluentform', 30, 3 );

function nap_on_fluentform( $insert_id, $form_data, $form ) {
	$ids = array_filter( array_map( 'absint', explode( ',', (string) nap_settings()['form_ids'] ) ) );
	if ( ! $form || ! in_array( (int) $form->id, $ids, true ) ) {
		return;
	}
	$lead = nap_extract_lead( (array) $form_data );
	if ( ! $lead['email'] ) {
		return;
	}
	$lead['page'] = isset( $form_data['_wp_http_referer'] ) ? home_url( (string) $form_data['_wp_http_referer'] ) : '';
	nap_intake( $lead );
}

/** Poimii lomakkeelta yhteystiedot, osoitteen ja viestin, vaikka kenttien nimet vaihtelisivat. */
/** Lomakkeen valinnan teksti tai arvo → toimitustapa. */
function nap_delivery_from_text( $t ) {
	$t = mb_strtolower( $t );
	if ( preg_match( '/omat[_ ]mitat|mittaa[a-zä]* itse|itse mita|asiakas mittaa|vain tuotteet|vain toimitus/u', $t ) ) {
		return 'omat_mitat';
	}
	if ( preg_match( '/ilman[_ ]asennus/u', $t ) ) {
		return 'ilman_asennusta';
	}
	return 'asennus';
}

function nap_extract_lead( $data ) {
	$lead = array( 'email' => '', 'name' => '', 'phone' => '', 'address' => '', 'city' => '', 'products' => '', 'message' => '', 'source' => '', 'delivery' => 'asennus' );
	$first = $last = '';
	foreach ( $data as $key => $value ) {
		$k = strtolower( (string) $key );
		if ( 0 === strpos( $k, '_' ) ) {
			continue;
		}
		if ( preg_match( '/toimitus|asennus|delivery/', $k ) ) { // Fluent Formsin "Toimitustapa"-valinta
			$lead['delivery'] = nap_delivery_from_text( is_array( $value ) ? implode( ' ', array_filter( $value, 'is_scalar' ) ) : (string) $value );
			continue;
		}
		if ( preg_match( '/mittavastuu|omat_mitat|hyvaksyn_mitat/', $k ) ) { // vahvistus mittavastuusta
			continue;
		}
		if ( is_array( $value ) ) {
			if ( isset( $value['first_name'] ) || isset( $value['last_name'] ) ) {
				$first = $first ?: trim( (string) ( $value['first_name'] ?? '' ) );
				$last  = $last ?: trim( (string) ( $value['last_name'] ?? '' ) );
			} elseif ( isset( $value['address_line_1'] ) || isset( $value['city'] ) ) {
				$lead['address'] = $lead['address'] ?: trim( (string) ( $value['address_line_1'] ?? '' ) );
				$lead['city']    = $lead['city'] ?: trim( (string) ( $value['city'] ?? '' ) );
			} elseif ( preg_match( '/tuote|product|valinta|palvelu/', $k ) ) {
				$lead['products'] = implode( ', ', array_map( 'sanitize_text_field', array_filter( $value, 'is_scalar' ) ) );
			}
			continue;
		}
		$v = trim( (string) $value );
		if ( '' === $v ) {
			continue;
		}
		if ( ! $lead['email'] && is_email( $v ) ) {
			$lead['email'] = $v;
		} elseif ( ! $lead['phone'] && preg_match( '/phone|puhelin|puh|tel/', $k ) ) {
			$lead['phone'] = $v;
		} elseif ( preg_match( '/^(first_name|etunimi)$/', $k ) ) {
			$first = $first ?: $v;
		} elseif ( preg_match( '/^(last_name|sukunimi)$/', $k ) ) {
			$last = $last ?: $v;
		} elseif ( ! $first && preg_match( '/^(name|nimi|full_name|koko_nimi)$/', $k ) ) {
			$first = $v;
		} elseif ( ! $lead['city'] && preg_match( '/city|paikkakunta|kunta|kaupunki/', $k ) ) {
			$lead['city'] = $v;
		} elseif ( ! $lead['address'] && preg_match( '/osoite|address|katu/', $k ) ) {
			$lead['address'] = $v;
		} elseif ( ! $lead['products'] && preg_match( '/tuote|product|valinta|palvelu/', $k ) ) {
			$lead['products'] = $v;
		} elseif ( ! $lead['message'] && preg_match( '/viesti|message|lisatie|lisätie|kuvaus|description/', $k ) ) {
			$lead['message'] = $v;
		} elseif ( ! $lead['source'] && preg_match( '/^(lahde|lähde|source)$/', $k ) ) {
			$lead['source'] = $v;
		}
	}
	$lead['name'] = trim( $first . ' ' . $last );
	return $lead;
}

/** Ensimmäinen tehtävä uudesta tarjouspyynnöstä toimitustavan mukaan. */
function nap_first_task_title( $delivery ) {
	return 'omat_mitat' === $delivery ? 'Soita ja pyydä mitat' : 'Soita ja sovi mittaus';
}

function nap_next_workday( $from = null ) {
	$t = $from ? strtotime( $from ) : current_time( 'timestamp' );
	do {
		$t += DAY_IN_SECONDS;
	} while ( (int) gmdate( 'N', $t ) >= 6 );
	return gmdate( 'Y-m-d', $t );
}

/**
 * Luo asiakkaan, kohteen, tehtävän ja tapahtumat. Palauttaa array( customer_id, project_id ).
 */
function nap_intake( $lead ) {
	$s        = nap_settings();
	$owner    = (int) $s['default_owner'] ?: null;
	$referrer = null;
	if ( ! empty( $_COOKIE['nap_ref'] ) ) {
		$referrer = nap_find_customer_by_ref( sanitize_text_field( wp_unslash( $_COOKIE['nap_ref'] ) ) );
	}
	$source = $lead['source'] ? $lead['source'] : ( $referrer ? 'Suosittelu' : 'Verkkosivu' );

	$customer = nap_find_customer_by_email( $lead['email'] );
	if ( $customer ) {
		$cid = (int) $customer['id'];
		nap_update_customer( $cid, array_filter( array(
			'name'    => $customer['name'] ? null : $lead['name'],
			'phone'   => $customer['phone'] ? null : $lead['phone'],
			'address' => $customer['address'] ? null : $lead['address'],
			'city'    => $customer['city'] ? null : $lead['city'],
		) ) );
	} else {
		$cid = nap_create_customer( array(
			'name'        => $lead['name'] ?: $lead['email'],
			'email'       => $lead['email'],
			'phone'       => $lead['phone'],
			'address'     => $lead['address'],
			'city'        => $lead['city'],
			'source'      => $source,
			'referred_by' => ( $referrer && strtolower( $referrer['email'] ) !== strtolower( $lead['email'] ) ) ? (int) $referrer['id'] : null,
			'owner_id'    => $owner,
		) );
	}
	$customer = nap_get_customer( $cid );
	$address  = trim( $lead['address'] . ( $lead['city'] ? ', ' . $lead['city'] : '' ), ', ' );
	$details  = trim( ( $lead['products'] ? 'Tuotteet: ' . $lead['products'] . "\n" : '' ) . ( $lead['message'] ? 'Viesti: ' . $lead['message'] . "\n" : '' ) . ( $lead['page'] ? 'Lomake sivulla: ' . $lead['page'] : '' ) );

	$pid = nap_create_project( array(
		'customer_id' => $cid,
		'title'       => $lead['products'] ? $lead['products'] : 'Ikkuna- ja oviremontti',
		'address'     => $address ?: trim( $customer['address'] . ', ' . $customer['city'], ', ' ),
		'stage'       => 0,
		'owner_id'    => $customer['owner_id'] ?: $owner,
		'details'     => $details,
		'delivery'    => $lead['delivery'] ?? 'asennus',
	) );

	nap_log( $cid, $pid, 'request', 'Tarjouspyyntö vastaanotettu (' . $source . ')' . ( $referrer ? ', suositteli ' . $referrer['name'] : '' ), true );
	if ( $lead['message'] ) {
		nap_log( $cid, $pid, 'note', 'Asiakkaan viesti: ' . $lead['message'] );
	}
	nap_create_task( array(
		'project_id'  => $pid,
		'customer_id' => $cid,
		'title'       => nap_first_task_title( $lead['delivery'] ?? 'asennus' ) . ': ' . ( $customer['name'] ?: $customer['email'] ),
		'assignee_id' => $customer['owner_id'] ?: $owner,
		'due_date'    => nap_next_workday(),
	) );

	nap_mail_welcome( $customer, nap_create_login_link( $cid ), '', $lead['delivery'] ?? 'asennus' );
	nap_log( $cid, $pid, 'mail', 'Kiitosviesti ja Oma kohde -linkki lähetetty asiakkaalle' );

	if ( $s['staff_notify'] ) {
		nap_mail(
			array_filter( array_map( 'trim', explode( ',', $s['staff_notify'] ) ) ),
			'Uusi tarjouspyyntö: ' . ( $customer['name'] ?: $customer['email'] ),
			'Uusi tarjouspyyntö',
			array_filter( array( $customer['name'] . ' · ' . $customer['email'] . ' · ' . $customer['phone'], $address, 'Toimitustapa: ' . nap_deliveries()[ nap_delivery( $lead ) ], $details, 'Lähde: ' . $source ) ),
			array( 'Avaa CRM', home_url( '/crm/#asiakas-' . $cid ) )
		);
	}
	return array( $cid, $pid );
}
