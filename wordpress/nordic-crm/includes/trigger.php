<?php
/**
 * FluentCRM-automaation käynnistin "Nordic: uusi yhteydenotto".
 *
 * Näkyy FluentCRM:n automaatioeditorissa kuten muutkin käynnistimet. Asetuksissa
 * valitaan yhteydenoton tyyppi ja se, lähetetäänkö viestit kaikille vai vain
 * markkinointiluvan antaneille.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

use FluentCrm\App\Services\Funnel\BaseTrigger;
use FluentCrm\App\Services\Funnel\FunnelHelper;
use FluentCrm\App\Services\Funnel\FunnelProcessor;
use FluentCrm\App\Models\FunnelSubscriber;

class Nordic_CRM_Lead_Trigger extends BaseTrigger {

	public function __construct() {
		$this->triggerName  = NORDIC_CRM_TRIGGER;
		$this->actionArgNum = 2;
		$this->priority     = 20;
		parent::__construct();
	}

	public static function lead_types() {
		return array(
			array( 'id' => 'tarjous', 'title' => 'Tarjouspyyntö (koti)' ),
			array( 'id' => 'taloyhtio', 'title' => 'Tarjouspyyntö (taloyhtiö)' ),
			array( 'id' => 'esite', 'title' => 'Esitteen tilaus' ),
			array( 'id' => 'laskuri', 'title' => 'Laskurin tulos sähköpostiin' ),
			array( 'id' => 'opas', 'title' => 'Oppaan lataus' ),
		);
	}

	public function getTrigger() {
		return array(
			'category'    => 'Nordic',
			'label'       => 'Nordic: uusi yhteydenotto',
			'description' => 'Käynnistyy, kun asiakas lähettää tarjouspyynnön, tilaa esitteen, lataa oppaan tai pyytää laskurin tuloksen sähköpostiin.',
			'icon'        => 'fc-icon-fluentforms',
		);
	}

	public function getFunnelSettingsDefaults() {
		return array(
			'lead_type' => 'tarjous',
			'audience'  => 'all',
		);
	}

	public function getSettingsFields( $funnel ) {
		return array(
			'title'     => 'Nordic: uusi yhteydenotto',
			'sub_title' => 'Valitse, minkä yhteydenoton jälkeen viestit lähtevät.',
			'fields'    => array(
				'lead_type' => array(
					'type'    => 'select',
					'label'   => 'Yhteydenoton tyyppi',
					'options' => self::lead_types(),
				),
				'audience'  => array(
					'type'    => 'radio',
					'label'   => 'Kenelle',
					'options' => array(
						array( 'id' => 'all', 'title' => 'Kaikille (palveluviesti: vahvistus, esite, opas, laskelma)' ),
						array( 'id' => 'consent', 'title' => 'Vain markkinointiluvan antaneille (jatkoviestit)' ),
					),
				),
			),
		);
	}

	public function getFunnelConditionDefaults( $funnel ) {
		return array( 'run_only_one' => 'yes' );
	}

	public function getConditionFields( $funnel ) {
		return array(
			'run_only_one' => array(
				'type'        => 'yes_no_check',
				'label'       => '',
				'check_label' => 'Aja vain kerran per kontakti (uusi yhteydenotto ei aloita sarjaa alusta)',
				'options'     => FunnelHelper::getUpdateOptions(),
			),
		);
	}

	/**
	 * @param \FluentCrm\App\Models\Funnel $funnel
	 * @param array                        $args [ $lead, $subscriber ]
	 */
	public function handle( $funnel, $args ) {
		$lead       = isset( $args[0] ) ? (array) $args[0] : array();
		$subscriber = isset( $args[1] ) ? $args[1] : null;
		if ( ! $subscriber || empty( $lead['type'] ) ) {
			return;
		}

		$settings = (array) $funnel->settings;
		if ( ( $settings['lead_type'] ?? '' ) !== $lead['type'] ) {
			return;
		}

		$audience = $settings['audience'] ?? 'all';
		if ( 'consent' === $audience && empty( $lead['consent'] ) ) {
			// Ei uutta lupaa tällä kertaa: jatkoviestit vain, jos kontakti on jo tilaaja.
			if ( 'subscribed' !== $subscriber->status ) {
				return;
			}
		}

		// Jo sarjassa?
		if ( FunnelHelper::ifAlreadyInFunnel( $funnel->id, $subscriber->id ) ) {
			$run_multiple = ( ( (array) $funnel->conditions )['run_only_one'] ?? 'yes' ) === 'no';
			if ( ! $run_multiple ) {
				return;
			}
			FunnelHelper::removeSubscribersFromFunnel( $funnel->id, array( $subscriber->id ) );
		}

		// FluentCRM lähettää viestejä vain statuksille subscribed ja transactional.
		// Palveluviesti (asiakkaan itse pyytämä vahvistus, esite tai laskelma) kuuluu
		// lähettää myös, kun kontakti odottaa tuplavahvistusta tai on aiemmin perunut
		// markkinointiviestit. Lähetetään se silloin suoraan automaation viestistä.
		if ( 'all' === $audience && ! in_array( $subscriber->status, array( 'subscribed', 'transactional' ), true ) ) {
			if ( in_array( $subscriber->status, array( 'pending', 'unsubscribed' ), true ) ) {
				self::send_service_emails_directly( $funnel, $subscriber );
			}
			return;
		}

		// Markkinointisarja odottaa tuplavahvistusta (FluentCRM jatkaa sitä vahvistuksen jälkeen).
		$status = 'draft';
		if ( 'consent' === $audience && in_array( $subscriber->status, array( 'pending', 'unsubscribed' ), true ) ) {
			$status = 'pending';
		} elseif ( 'all' === $audience ) {
			$status = 'active';
		}

		( new FunnelProcessor() )->startSequences( $subscriber, $funnel, array(
			'status'              => $status,
			'source_trigger_name' => $this->triggerName,
			'source_ref_id'       => 0,
		) );
	}

	/**
	 * Lähettää automaation alussa olevat viestit (ennen ensimmäistä odotusta) suoraan.
	 * Sisältö luetaan FluentCRM:stä, joten siellä tehdyt muokkaukset ovat mukana.
	 */
	public static function send_service_emails_directly( $funnel, $subscriber ) {
		$sequences = \FluentCrm\App\Models\FunnelSequence::where( 'funnel_id', $funnel->id )
			->orderBy( 'sequence', 'ASC' )
			->get();
		foreach ( $sequences as $sequence ) {
			if ( 'fluentcrm_wait_times' === $sequence->action_name ) {
				break;
			}
			if ( 'send_custom_email' !== $sequence->action_name ) {
				continue;
			}
			$campaign_id = (int) ( $sequence->settings['reference_campaign'] ?? 0 );
			$campaign    = $campaign_id ? \FluentCrm\App\Models\FunnelCampaign::find( $campaign_id ) : null;
			if ( ! $campaign ) {
				continue;
			}
			$body    = apply_filters( 'fluent_crm/parse_campaign_email_text', $campaign->email_body, $subscriber );
			$subject = apply_filters( 'fluent_crm/parse_campaign_email_text', $campaign->email_subject, $subscriber );
			$headers = \FluentCrm\App\Services\Helper::getMailHeader();
			$sent    = \FluentCrm\App\Services\Libs\Mailer\Mailer::send( array(
				'to'      => array(
					'email' => $subscriber->email,
					'name'  => trim( $subscriber->first_name . ' ' . $subscriber->last_name ),
				),
				'subject' => $subject,
				'body'    => $body,
				'headers' => $headers,
			), $subscriber );
			if ( $sent ) {
				\FluentCrm\App\Models\SubscriberNote::create( array(
					'subscriber_id' => $subscriber->id,
					'type'          => 'system_log',
					'title'         => 'Palveluviesti lähetetty: ' . $subject,
					'description'   => 'Nordic CRM lähetti viestin suoraan, koska kontaktin status on ' . $subscriber->status . '.',
				) );
			}
		}
	}
}
