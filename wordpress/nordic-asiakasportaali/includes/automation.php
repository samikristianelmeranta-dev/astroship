<?php
/**
 * Sääntöpohjaiset automaatiot (ei tekoälyä):
 *  - vaiheen vaihto luo seuraavat tehtävät
 *  - aamukooste arkiaamuisin henkilökunnalle
 *  - liidivahti: tarjouspyyntö ilman reaktiota 24 h → muistutus vastuumyyjälle, 48 h → muille osakkaille
 *  - jumissa-vahti: yli 14 päivää samassa vaiheessa → maanantain koosteeseen
 * Ajastus: WordPressin cron (tunneittain). Täsmällisyyttä varten cPanelin cron-ajo, ks. README.
 */

defined( 'ABSPATH' ) || exit;

/** Vaihe → automaattiset tehtävät: otsikko, arkipäiviä eräpäivään, kenelle (owner / installer). */
function nap_stage_tasks() {
	return apply_filters( 'nap_stage_tasks', array(
		2 => array( array( 'Seuraa tarjousta: soita asiakkaalle', 3, 'owner' ) ),
		3 => array( array( 'Tilaa tuotteet Skaalalta', 1, 'owner' ) ),
		4 => array( array( 'Sovi asennuspäivä asiakkaan kanssa', 2, 'installer' ) ),
		5 => array( array( 'Luovutus: käy työ läpi asiakkaan kanssa', 1, 'installer' ) ),
		6 => array(
			array( 'Laskuta työ (Holvi) tai tarkista rahoitus', 1, 'owner' ),
			array( 'Kiitoslahja ja suosittelupyyntö asiakkaalle', 1, 'owner' ),
		),
	) );
}

function nap_add_workdays( $n, $from = null ) {
	$d = $from ? $from : current_time( 'Y-m-d' );
	for ( $i = 0; $i < max( 1, (int) $n ); $i++ ) {
		$d = nap_next_workday( $d );
	}
	return $d;
}

/** Kutsutaan, kun kohteen vaihe vaihtuu. */
function nap_on_stage_change( $p, $old_stage ) {
	global $wpdb;
	$new = (int) $p['stage'];
	$t   = nap_table( 'tasks' );
	// Mittaus sovittu tai pidemmällä → avoin "Soita ja sovi mittaus" on hoidettu.
	if ( $new > 0 ) {
		$wpdb->query( $wpdb->prepare( "UPDATE $t SET done_at = %s WHERE project_id = %d AND done_at IS NULL AND title LIKE %s", nap_now(), $p['id'], 'Soita ja sovi mittaus%' ) ); // phpcs:ignore
	}
	if ( $new <= (int) $old_stage || ! nap_settings()['auto_tasks'] ) {
		return;
	}
	$map = nap_stage_tasks();
	if ( empty( $map[ $new ] ) ) {
		return;
	}
	foreach ( $map[ $new ] as $def ) {
		$exists = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM $t WHERE project_id = %d AND title = %s AND done_at IS NULL", $p['id'], $def[0] ) ); // phpcs:ignore
		if ( $exists ) {
			continue;
		}
		$who = 'installer' === $def[2] && $p['installer_id'] ? $p['installer_id'] : $p['owner_id'];
		nap_create_task( array(
			'project_id'  => $p['id'],
			'customer_id' => $p['customer_id'],
			'title'       => $def[0],
			'assignee_id' => $who ?: (int) nap_settings()['default_owner'],
			'due_date'    => nap_add_workdays( $def[1] ),
		) );
	}
	nap_log( $p['customer_id'], $p['id'], 'auto', 'Automaattiset tehtävät: ' . implode( ', ', array_column( $map[ $new ], 0 ) ) );
}

/* ---------- Ajastus ---------- */

add_action( 'init', function () {
	if ( ! wp_next_scheduled( 'nap_hourly' ) ) {
		wp_schedule_event( time() + 60, 'hourly', 'nap_hourly' );
	}
} );

add_action( 'nap_hourly', 'nap_run_automations' );

function nap_run_automations() {
	$s = nap_settings();
	if ( $s['auto_watch'] ) {
		nap_lead_watch();
	}
	if ( $s['auto_digest'] ) {
		nap_maybe_send_digest();
	}
}

function nap_osakkaat() {
	return get_users( array( 'capability' => 'nap_all', 'fields' => array( 'ID', 'display_name', 'user_email' ) ) );
}

/** Liidivahti: tarjouspyyntö, johon kukaan henkilökunnasta ei ole reagoinut. */
function nap_lead_watch() {
	global $wpdb;
	$p_t = nap_table( 'projects' );
	$e_t = nap_table( 'events' );
	$k_t = nap_table( 'tasks' );
	$limit = gmdate( 'Y-m-d H:i:s', current_time( 'timestamp' ) - DAY_IN_SECONDS );
	$rows  = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $p_t WHERE stage = 0 AND created_at < %s ORDER BY created_at ASC LIMIT 50", $limit ), ARRAY_A ); // phpcs:ignore
	foreach ( $rows as $p ) {
		$reacted = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM $e_t WHERE project_id = %d AND user_id IS NOT NULL", $p['id'] ) ) // phpcs:ignore
			+ (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM $k_t WHERE project_id = %d AND done_at IS NOT NULL", $p['id'] ) ); // phpcs:ignore
		if ( $reacted ) {
			continue;
		}
		$sent  = $wpdb->get_results( $wpdb->prepare( "SELECT type, created_at FROM $e_t WHERE project_id = %d AND type IN ('watch1','watch2')", $p['id'] ), OBJECT_K ); // phpcs:ignore
		$c     = nap_get_customer( (int) $p['customer_id'] );
		$hours = (int) floor( ( current_time( 'timestamp' ) - strtotime( $p['created_at'] ) ) / HOUR_IN_SECONDS );
		$line  = ( $c['name'] ?: $c['email'] ) . ' · ' . $p['title'] . ( $p['address'] ? ' · ' . $p['address'] : '' );
		$link  = array( 'Avaa asiakas', home_url( '/crm/#asiakas-' . $c['id'] ) );
		if ( ! isset( $sent['watch1'] ) ) {
			$owner = $p['owner_id'] ? get_userdata( (int) $p['owner_id'] ) : null;
			$to    = $owner ? $owner->user_email : wp_list_pluck( nap_osakkaat(), 'user_email' );
			nap_mail( $to, 'Tarjouspyyntö odottaa: ' . ( $c['name'] ?: $c['email'] ), 'Tarjouspyyntö odottaa yhteydenottoa',
				array( 'Tähän tarjouspyyntöön ei ole vielä reagoitu (' . $hours . ' tuntia).', $line, 'Kun soitat tai kirjaat muistiinpanon, muistutukset loppuvat.' ), $link );
			nap_log( $c['id'], $p['id'], 'watch1', 'Liidivahti: muistutus vastuumyyjälle' );
		} elseif ( ! isset( $sent['watch2'] ) && $hours >= 48 && current_time( 'timestamp' ) - strtotime( $sent['watch1']->created_at ) >= 12 * HOUR_IN_SECONDS ) {
			$others = array_filter( nap_osakkaat(), function ( $u ) use ( $p ) {
				return (int) $u->ID !== (int) $p['owner_id'];
			} );
			nap_mail( wp_list_pluck( $others, 'user_email' ) ?: wp_list_pluck( nap_osakkaat(), 'user_email' ), 'Liidi odottanut 2 päivää: ' . ( $c['name'] ?: $c['email'] ),
				'Tarjouspyyntö on odottanut kaksi päivää',
				array( 'Vastuumyyjä: ' . ( nap_user_name( $p['owner_id'] ) ?: 'ei valittu' ) . '. Voisiko joku muu soittaa asiakkaalle?', $line ), $link );
			nap_log( $c['id'], $p['id'], 'watch2', 'Liidivahti: ilmoitus muille osakkaille' );
		}
	}
}

/** Aamukooste arkiaamuisin klo 7 jälkeen, kerran päivässä. */
function nap_maybe_send_digest( $force = false ) {
	$today = current_time( 'Y-m-d' );
	if ( ! $force ) {
		if ( (int) current_time( 'N' ) >= 6 || (int) current_time( 'G' ) < 7 || get_option( 'nap_digest_last' ) === $today ) {
			return;
		}
	}
	update_option( 'nap_digest_last', $today, false );
	foreach ( get_users( array( 'capability' => 'nap_access' ) ) as $u ) {
		$body = nap_digest_for( $u );
		if ( $body ) {
			nap_mail( $u->user_email, 'Päivän kooste ' . current_time( 'j.n.' ), 'Hyvää huomenta, ' . nap_first_name( $u->display_name ), $body, array( 'Avaa CRM', home_url( '/crm/' ) ) );
		}
	}
}

function nap_digest_for( WP_User $u ) {
	global $wpdb;
	$today = current_time( 'Y-m-d' );
	$k_t   = nap_table( 'tasks' );
	$p_t   = nap_table( 'projects' );
	$c_t   = nap_table( 'customers' );
	$out   = array();

	$tasks = $wpdb->get_results( $wpdb->prepare( "SELECT k.title, k.due_date, c.name FROM $k_t k LEFT JOIN $c_t c ON c.id = k.customer_id WHERE k.assignee_id = %d AND k.done_at IS NULL AND k.due_date <= %s ORDER BY k.due_date ASC LIMIT 25", $u->ID, $today ), ARRAY_A ); // phpcs:ignore
	if ( $tasks ) {
		$out[] = 'Tehtävät tänään (' . count( $tasks ) . '):';
		foreach ( $tasks as $t ) {
			$late  = $t['due_date'] < $today ? ' – myöhässä ' . date_i18n( 'j.n.', strtotime( $t['due_date'] ) ) : '';
			$out[] = '• ' . $t['title'] . ( $t['name'] && false === strpos( $t['title'], $t['name'] ) ? ' (' . $t['name'] . ')' : '' ) . $late;
		}
	}
	if ( user_can( $u, 'nap_all' ) ) {
		$since = gmdate( 'Y-m-d H:i:s', current_time( 'timestamp' ) - ( 1 === (int) current_time( 'N' ) ? 3 : 1 ) * DAY_IN_SECONDS );
		$new   = $wpdb->get_results( $wpdb->prepare( "SELECT p.title, p.address, c.name FROM $p_t p JOIN $c_t c ON c.id = p.customer_id WHERE p.created_at >= %s ORDER BY p.created_at DESC LIMIT 20", $since ), ARRAY_A ); // phpcs:ignore
		if ( $new ) {
			$out[] = 'Uudet tarjouspyynnöt (' . count( $new ) . '):';
			foreach ( $new as $n ) {
				$out[] = '• ' . $n['name'] . ' – ' . $n['title'] . ( $n['address'] ? ', ' . $n['address'] : '' );
			}
		}
		if ( 1 === (int) current_time( 'N' ) ) {
			$old   = gmdate( 'Y-m-d H:i:s', current_time( 'timestamp' ) - 14 * DAY_IN_SECONDS );
			$stuck = $wpdb->get_results( $wpdb->prepare( "SELECT p.title, p.stage, p.updated_at, c.name FROM $p_t p JOIN $c_t c ON c.id = p.customer_id WHERE p.stage BETWEEN 1 AND 5 AND p.updated_at < %s ORDER BY p.updated_at ASC LIMIT 20", $old ), ARRAY_A ); // phpcs:ignore
			if ( $stuck ) {
				$out[] = 'Jumissa yli 2 viikkoa (' . count( $stuck ) . '):';
				foreach ( $stuck as $s ) {
					$out[] = '• ' . $s['name'] . ' – ' . NAP_STAGES[ (int) $s['stage'] ] . ' ' . date_i18n( 'j.n.', strtotime( $s['updated_at'] ) ) . ' alkaen';
				}
			}
		}
	}
	return $out;
}
