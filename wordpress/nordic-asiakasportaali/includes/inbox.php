<?php
/**
 * Saapuneet: myynti@ ja info@ ohjataan cPanelissa kopiona keräyslaatikkoon (esim. crm@ikkunakauppias.fi).
 * CRM hakee laatikon viestit IMAP:lla muutaman minuutin välein, tunnistaa viestin tyypin säännöillä,
 * liittää asiakkaan viestit asiakkaan historiaan ja poistaa haetut viestit keräyslaatikosta.
 * Alkuperäiset viestit pysyvät myynti@- ja info@-laatikoissa.
 *
 * Ei vaadi PHP:n IMAP-laajennusta: oma kevyt IMAP-asiakas ja MIME-purku.
 */

defined( 'ABSPATH' ) || exit;

function nap_inbox_categories() {
	return array(
		'tarjouspyynto' => 'Tarjouspyyntö',
		'asiakas'       => 'Asiakkaan viesti',
		'reklamaatio'   => 'Reklamaatio / takuu',
		'skaala'        => 'Skaala',
		'lasku'         => 'Lasku / kuitti',
		'toimittaja'    => 'Toimittajat ja yhteistyö',
		'muu'           => 'Muut',
		'uutiskirje'    => 'Uutiskirjeet ja mainokset',
		'jarjestelma'   => 'Sivuston ilmoitukset',
		'roskaposti'    => 'Roskaposti',
	);
}

/** Luokat, jotka eivät näy oletusnäkymässä. */
function nap_inbox_hidden_categories() {
	return array( 'uutiskirje', 'jarjestelma', 'roskaposti' );
}

/* =====================================================================
 * IMAP-asiakas
 * ===================================================================== */

class Nap_Imap {
	private $fp;
	private $n = 0;
	public $error = '';

	public function connect( $host, $port, $ssl ) {
		$ctx = stream_context_create( array( 'ssl' => array(
			// Sama palvelin (localhost): sertifikaatin nimi ei täsmää, liikenne ei lähde koneelta.
			'verify_peer'      => ! in_array( $host, array( 'localhost', '127.0.0.1' ), true ),
			'verify_peer_name' => ! in_array( $host, array( 'localhost', '127.0.0.1' ), true ),
		) ) );
		$this->fp = @stream_socket_client( ( $ssl ? 'ssl://' : 'tcp://' ) . $host . ':' . (int) $port, $errno, $errstr, 15, STREAM_CLIENT_CONNECT, $ctx ); // phpcs:ignore
		if ( ! $this->fp ) {
			$this->error = 'Yhteys postipalvelimeen epäonnistui: ' . $errstr;
			return false;
		}
		stream_set_timeout( $this->fp, 30 );
		$greet = fgets( $this->fp );
		return 0 === strpos( (string) $greet, '* OK' );
	}

	private function q( $s ) {
		return '"' . str_replace( array( '\\', '"' ), array( '\\\\', '\\"' ), $s ) . '"';
	}

	/** Lähettää komennon. Palauttaa array( ok, rivit, literaalit ). */
	public function cmd( $command ) {
		$tag = 'N' . ( ++$this->n );
		fwrite( $this->fp, $tag . ' ' . $command . "\r\n" );
		$lines    = array();
		$literals = array();
		while ( true ) {
			$line = fgets( $this->fp );
			if ( false === $line ) {
				$this->error = 'Postipalvelin katkaisi yhteyden.';
				return array( false, $lines, $literals );
			}
			if ( preg_match( '/\{(\d+)\}\r\n$/', $line, $m ) ) { // literaali, esim. viestin sisältö
				$data = '';
				$left = (int) $m[1];
				while ( $left > 0 && ! feof( $this->fp ) ) {
					$chunk = fread( $this->fp, min( 65536, $left ) );
					if ( false === $chunk || '' === $chunk ) {
						break;
					}
					$data .= $chunk;
					$left -= strlen( $chunk );
				}
				$literals[] = $data;
				$lines[]    = $line;
				continue;
			}
			if ( 0 === strpos( $line, $tag . ' ' ) ) {
				$ok = 0 === strpos( substr( $line, strlen( $tag ) + 1 ), 'OK' );
				if ( ! $ok ) {
					$this->error = trim( substr( $line, strlen( $tag ) + 1 ) );
				}
				return array( $ok, $lines, $literals );
			}
			$lines[] = $line;
		}
	}

	public function login( $user, $pass ) {
		list( $ok ) = $this->cmd( 'LOGIN ' . $this->q( $user ) . ' ' . $this->q( $pass ) );
		if ( ! $ok ) {
			$this->error = 'Kirjautuminen postilaatikkoon epäonnistui. Tarkista käyttäjätunnus ja salasana.';
		}
		return $ok;
	}

	public function select( $box = 'INBOX' ) {
		list( $ok ) = $this->cmd( 'SELECT ' . $this->q( $box ) );
		return $ok;
	}

	/** Kaikkien viestien UID:t. */
	public function uids() {
		list( $ok, $lines ) = $this->cmd( 'UID SEARCH ALL' );
		$uids = array();
		foreach ( $lines as $l ) {
			if ( 0 === strpos( $l, '* SEARCH' ) ) {
				$uids = array_merge( $uids, array_map( 'intval', preg_split( '/\s+/', trim( substr( $l, 8 ) ), -1, PREG_SPLIT_NO_EMPTY ) ) );
			}
		}
		return $ok ? $uids : array();
	}

	public function fetch_raw( $uid ) {
		list( $ok, , $lit ) = $this->cmd( 'UID FETCH ' . (int) $uid . ' (BODY.PEEK[])' );
		return $ok && $lit ? $lit[0] : null;
	}

	public function delete( $uids ) {
		if ( $uids ) {
			$this->cmd( 'UID STORE ' . implode( ',', array_map( 'intval', $uids ) ) . ' +FLAGS.SILENT (\\Deleted)' );
			$this->cmd( 'EXPUNGE' );
		}
	}

	public function logout() {
		if ( $this->fp ) {
			$this->cmd( 'LOGOUT' );
			fclose( $this->fp );
			$this->fp = null;
		}
	}
}

/* =====================================================================
 * MIME-purku
 * ===================================================================== */

function nap_mime_split( $raw ) {
	$raw = str_replace( "\r\n", "\n", (string) $raw );
	$pos = strpos( $raw, "\n\n" );
	if ( false === $pos ) {
		return array( $raw, '' );
	}
	return array( substr( $raw, 0, $pos ), substr( $raw, $pos + 2 ) );
}

/** Otsakkeet: nimi (pienillä) => arvo tai arvojen taulukko. */
function nap_mime_headers( $head ) {
	$head = preg_replace( "/\n[ \t]+/", ' ', $head ); // jatkorivit
	$out  = array();
	foreach ( explode( "\n", $head ) as $line ) {
		$p = strpos( $line, ':' );
		if ( false === $p ) {
			continue;
		}
		$k = strtolower( trim( substr( $line, 0, $p ) ) );
		$v = trim( substr( $line, $p + 1 ) );
		if ( isset( $out[ $k ] ) ) {
			$out[ $k ] = array_merge( (array) $out[ $k ], array( $v ) );
		} else {
			$out[ $k ] = $v;
		}
	}
	return $out;
}

function nap_mime_h( $h, $k ) {
	return isset( $h[ $k ] ) ? ( is_array( $h[ $k ] ) ? $h[ $k ][0] : $h[ $k ] ) : '';
}

/** =?UTF-8?B?...?= -koodatut otsakkeet. */
function nap_mime_decode_header( $s ) {
	if ( false === strpos( $s, '=?' ) ) {
		return $s;
	}
	$d = function_exists( 'iconv_mime_decode' ) ? @iconv_mime_decode( $s, ICONV_MIME_DECODE_CONTINUE_ON_ERROR, 'UTF-8' ) : false; // phpcs:ignore
	return false !== $d ? $d : mb_decode_mimeheader( $s );
}

function nap_mime_param( $value, $name ) {
	if ( preg_match( '/' . $name . '\*?=\s*(?:"([^"]*)"|([^;\s]+))/i', $value, $m ) ) {
		$v = '' !== $m[1] ? $m[1] : $m[2];
		if ( preg_match( "/^([\w-]+)''(.*)$/", $v, $mm ) ) { // RFC 2231: utf-8''nimi%C3%A4.pdf
			return mb_convert_encoding( rawurldecode( $mm[2] ), 'UTF-8', $mm[1] );
		}
		return nap_mime_decode_header( $v );
	}
	return '';
}

function nap_mime_body( $body, $encoding, $charset ) {
	$encoding = strtolower( trim( $encoding ) );
	if ( 'base64' === $encoding ) {
		$body = base64_decode( preg_replace( '/\s+/', '', $body ) );
	} elseif ( 'quoted-printable' === $encoding ) {
		$body = quoted_printable_decode( $body );
	}
	if ( null !== $charset ) {
		$charset = strtoupper( $charset ?: 'UTF-8' );
		if ( 'UTF-8' !== $charset && 'US-ASCII' !== $charset ) {
			$conv = @mb_convert_encoding( $body, 'UTF-8', 'WINDOWS-1252' === $charset || 'ISO-8859-1' === $charset || 'ISO-8859-15' === $charset ? $charset : $charset ); // phpcs:ignore
			$body = false !== $conv ? $conv : $body;
		} elseif ( ! mb_check_encoding( $body, 'UTF-8' ) ) {
			$body = mb_convert_encoding( $body, 'UTF-8', 'WINDOWS-1252' );
		}
	}
	return $body;
}

/** Purkaa viestin: otsakkeet, teksti, html ja liitteet. */
function nap_mime_parse( $raw, $depth = 0 ) {
	list( $head, $body ) = nap_mime_split( $raw );
	$h     = nap_mime_headers( $head );
	$ctype = nap_mime_h( $h, 'content-type' ) ?: 'text/plain';
	$type  = strtolower( trim( strtok( $ctype, ';' ) ) );
	$out   = array( 'headers' => $h, 'text' => '', 'html' => '', 'attachments' => array() );
	if ( 0 === strpos( $type, 'multipart/' ) && $depth < 8 ) {
		$b = nap_mime_param( $ctype, 'boundary' );
		if ( $b ) {
			$parts = explode( "\n--" . $b, "\n" . $body );
			array_shift( $parts ); // johdanto
			foreach ( $parts as $part ) {
				if ( 0 === strpos( $part, '--' ) ) {
					break; // loppuraja
				}
				$sub = nap_mime_parse( ltrim( preg_replace( '/^[ \t]*\n/', '', $part ), "\n" ), $depth + 1 );
				if ( ! $out['text'] && $sub['text'] ) {
					$out['text'] = $sub['text'];
				}
				if ( ! $out['html'] && $sub['html'] ) {
					$out['html'] = $sub['html'];
				}
				$out['attachments'] = array_merge( $out['attachments'], $sub['attachments'] );
			}
		}
		return $out;
	}
	$disp  = nap_mime_h( $h, 'content-disposition' );
	$fname = nap_mime_param( $disp, 'filename' ) ?: nap_mime_param( $ctype, 'name' );
	$enc   = nap_mime_h( $h, 'content-transfer-encoding' );
	if ( $fname || 0 === stripos( $disp, 'attachment' ) || ! preg_match( '#^(text/plain|text/html|message/)#', $type ) ) {
		if ( 'message/rfc822' === $type ) {
			$inner = nap_mime_parse( nap_mime_body( $body, $enc, null ), $depth + 1 );
			$out['attachments'] = $inner['attachments'];
			$out['text']        = $inner['text'];
			return $out;
		}
		$out['attachments'][] = array(
			'name'   => $fname ?: 'liite',
			'type'   => $type,
			'inline' => 0 === stripos( $disp, 'inline' ) || ( ! $disp && nap_mime_h( $h, 'content-id' ) ),
			'data'   => nap_mime_body( $body, $enc, null ),
		);
		return $out;
	}
	$text = nap_mime_body( $body, $enc, nap_mime_param( $ctype, 'charset' ) );
	if ( 'text/html' === $type ) {
		$out['html'] = $text;
	} else {
		$out['text'] = $text;
	}
	return $out;
}

/** HTML → luettava teksti. */
function nap_html_to_text( $html ) {
	$html = preg_replace( '#<(script|style|head)[^>]*>.*?</\1>#is', '', $html );
	$html = preg_replace( '#<br\s*/?>|</(p|div|tr|li|h\d)>#i', "\n", $html );
	$text = html_entity_decode( wp_strip_all_tags( $html ), ENT_QUOTES | ENT_HTML5, 'UTF-8' );
	return trim( preg_replace( "/\n{3,}/", "\n\n", preg_replace( "/[ \t]+/", ' ', $text ) ) );
}

/** Osoitteet otsakkeesta: array( array( nimi, email ) ). */
function nap_mime_addresses( $value ) {
	$out = array();
	foreach ( (array) $value as $v ) {
		// Pilkut lainausmerkkien sisällä (esim. "Malli, Tiina") eivät erota osoitteita.
		foreach ( preg_split( '/,(?=(?:[^"]*"[^"]*")*[^"]*$)/', nap_mime_decode_header( $v ) ) as $part ) {
			$part = trim( $part );
			if ( preg_match( '/^(.*)<\s*([^<>\s]+@[^<>\s]+)\s*>/u', $part, $m ) ) {
				$out[] = array( trim( $m[1], " \t\"'" ), strtolower( $m[2] ) );
			} elseif ( preg_match( '/[^\s<>"]+@[^\s<>"]+/u', $part, $m ) ) {
				$out[] = array( '', strtolower( $m[0] ) );
			}
		}
	}
	return $out;
}

/** Omat verkkotunnukset: sivuston ja keräyslaatikon. */
function nap_own_domains() {
	$d = array( preg_replace( '/^www\./', '', strtolower( (string) wp_parse_url( home_url(), PHP_URL_HOST ) ) ) );
	$u = (string) nap_settings()['inbox_user'];
	if ( false !== strpos( $u, '@' ) ) {
		$d[] = strtolower( substr( strrchr( $u, '@' ), 1 ) );
	}
	return array_values( array_unique( array_filter( $d ) ) );
}

/* =====================================================================
 * Tunnistus
 * ===================================================================== */

/**
 * Tunnistaa viestin tyypin säännöillä. $m: from_email, from_name, subject, text, headers, attachments.
 * Palauttaa array( luokka, syy, asiakas|null ).
 */
function nap_inbox_classify( $m ) {
	global $wpdb;
	$h       = $m['headers'];
	$from    = $m['from_email'];
	$fdom    = strtolower( substr( strrchr( $from, '@' ), 1 ) );
	$subj    = mb_strtolower( $m['subject'] );
	$all     = $subj . "\n" . mb_strtolower( mb_substr( $m['text'], 0, 6000 ) );
	$has_pdf = (bool) array_filter( $m['attachments'], function ( $a ) {
		return 'application/pdf' === $a['type'] || preg_match( '/\.pdf$/i', $a['name'] );
	} );
	$has     = function ( $re, $text ) {
		return (bool) preg_match( '/' . $re . '/u', $text );
	};

	$spam = strtolower( nap_mime_h( $h, 'x-spam-flag' ) . ' ' . nap_mime_h( $h, 'x-spam-status' ) );
	if ( preg_match( '/^\s*yes|\byes\b/', $spam ) || 0 === strpos( $subj, '***spam***' ) ) {
		return array( 'roskaposti', 'Palvelimen roskapostisuodatin merkitsi roskapostiksi', null );
	}
	if ( in_array( $fdom, nap_own_domains(), true ) && ( preg_match( '/^(wordpress|noreply|no-reply)@/', $from ) || $has( '^(uusi tarjouspyyntö:|tarjouspyyntö odottaa|liidi odottanut|päivän kooste|kysymys tarjouksesta|tarjous hyväksytty|tarjous hylätty)', $subj ) ) ) {
		return array( 'jarjestelma', 'Sivuston automaattinen ilmoitus', null );
	}
	$customer = $from ? nap_find_customer_by_email( $from ) : null;
	if ( false !== strpos( $fdom, 'skaala' ) ) {
		return array( 'skaala', 'Lähettäjä on Skaala (' . $fdom . ')', nap_inbox_match_customer_in_text( $m ) );
	}
	$complaint = 'reklamaatio|takuu|vika |viallinen|rikki|vuotaa|vuoto|huurtu|kosteu|ei sulkeudu|ei aukea|jumittaa|naarmu|valitus|korjauskäyn';
	if ( $customer ) {
		if ( $has( $complaint, $all ) ) {
			return array( 'reklamaatio', 'Asiakas ' . $customer['name'] . ' – viestissä reklamaatioon viittaavia sanoja', $customer );
		}
		return array( 'asiakas', 'Lähettäjä on asiakas ' . $customer['name'], $customer );
	}
	if ( $has( '\b(lasku|laskunne|invoice|kuitti|receipt|maksumuistutus|e-lasku|verkkolasku)\b', $subj ) || ( $has_pdf && $has( 'eräpäivä|viitenumero|iban|due date', $all ) ) ) {
		return array( 'lasku', 'Laskuun tai kuittiin viittaava viesti', null );
	}
	$suppliers = array_filter( array_map( 'trim', explode( ',', strtolower( (string) nap_settings()['inbox_suppliers'] ) ) ) );
	foreach ( $suppliers as $s ) {
		if ( $s && ( $fdom === $s || substr( $fdom, -strlen( '.' . $s ) ) === '.' . $s ) ) {
			return array( 'toimittaja', 'Lähettäjä on toimittajalistalla (' . $s . ')', null );
		}
	}
	if ( nap_mime_h( $h, 'list-unsubscribe' ) || preg_match( '/^(bulk|list|junk)$/i', nap_mime_h( $h, 'precedence' ) ) || $has( '^(newsletter|uutiskirje|marketing|news|noreply|no-reply|kampanja)', $from ) ) {
		return array( 'uutiskirje', 'Uutiskirje tai joukkoviesti', null );
	}
	if ( $has( $complaint, $all ) ) {
		return array( 'reklamaatio', 'Viestissä reklamaatioon viittaavia sanoja', null );
	}
	if ( $has( 'tarjous|hinta-arvio|hinta-arvion|paljonko|mitä maksaa|mittauskäyn|ikkun|ulko-ov|parvekeov|terassiov|\bovi\b|\bovet\b|ikkunaremont', $all ) ) {
		return array( 'tarjouspyynto', 'Viestissä kysytään tarjousta tai ikkunoista ja ovista', null );
	}
	return array( 'muu', 'Ei tunnistettu – tarkista', null );
}

/** Skaalan viestit: etsii asiakkaan nimen tai osoitteen otsikosta tai tekstistä. */
function nap_inbox_match_customer_in_text( $m ) {
	global $wpdb;
	$text = mb_strtolower( $m['subject'] . "\n" . mb_substr( $m['text'], 0, 8000 ) );
	$rows = $wpdb->get_results( 'SELECT * FROM ' . nap_table( 'customers' ) . ' ORDER BY updated_at DESC LIMIT 500', ARRAY_A ); // phpcs:ignore
	foreach ( $rows as $c ) {
		$name = mb_strtolower( trim( $c['name'] ) );
		$addr = mb_strtolower( trim( $c['address'] ) );
		if ( ( $name && false !== strpos( $name, ' ' ) && false !== mb_strpos( $text, $name ) ) || ( mb_strlen( $addr ) > 6 && false !== mb_strpos( $text, $addr ) ) ) {
			return $c;
		}
	}
	return null;
}

/* =====================================================================
 * Haku ja tallennus
 * ===================================================================== */

function nap_inbox_password() {
	if ( defined( 'NAP_MAIL_PASS' ) && NAP_MAIL_PASS ) {
		return (string) NAP_MAIL_PASS;
	}
	return (string) nap_settings()['inbox_pass'];
}

function nap_inbox_ready() {
	return ! nap_inbox_missing();
}

/** Mitä asetuksista puuttuu (tyhjä = valmis). */
function nap_inbox_missing() {
	$s    = nap_settings();
	$miss = array();
	if ( empty( $s['inbox_enabled'] ) ) {
		$miss[] = 'ruksi kohdassa "Hae keräyslaatikon viestit"';
	}
	if ( ! $s['inbox_user'] ) {
		$miss[] = 'keräyslaatikon osoite (esim. crm@ikkunakauppias.fi)';
	}
	if ( '' === nap_inbox_password() ) {
		$miss[] = 'keräyslaatikon salasana';
	}
	return $miss;
}

/** Hakee uudet viestit. Palauttaa array( 'haettu' => n ) tai WP_Error. */
function nap_inbox_fetch() {
	if ( ! nap_inbox_ready() ) {
		return new WP_Error( 'nap', 'Saapuneet ei ole käytössä. Asetuksista (Asetukset → Asiakasportaali) puuttuu: ' . implode( ', ', nap_inbox_missing() ) . '. Täytä ja paina Tallenna muutokset.' );
	}
	if ( get_transient( 'nap_inbox_lock' ) ) {
		return array( 'haettu' => 0, 'kesken' => true );
	}
	set_transient( 'nap_inbox_lock', 1, 5 * MINUTE_IN_SECONDS );
	$s    = nap_settings();
	$imap = new Nap_Imap();
	$done = array();
	$n    = 0;
	try {
		if ( ! $imap->connect( $s['inbox_host'], (int) $s['inbox_port'], ! empty( $s['inbox_ssl'] ) ) || ! $imap->login( $s['inbox_user'], nap_inbox_password() ) || ! $imap->select() ) {
			$err = $imap->error ?: 'Postilaatikon avaus epäonnistui.';
			update_option( 'nap_inbox_status', array( 'at' => nap_now(), 'ok' => false, 'msg' => $err ), false );
			return new WP_Error( 'nap', $err );
		}
		foreach ( array_slice( $imap->uids(), 0, 40 ) as $uid ) { // enintään 40 viestiä kerralla
			$raw = $imap->fetch_raw( $uid );
			if ( null === $raw ) {
				continue;
			}
			if ( nap_inbox_store( $raw ) ) {
				$n++;
			}
			$done[] = $uid; // tallennettu tai kaksoiskappale: voidaan poistaa keräyslaatikosta
		}
		$imap->delete( $done );
		$imap->logout();
	} finally {
		delete_transient( 'nap_inbox_lock' );
	}
	update_option( 'nap_inbox_status', array( 'at' => nap_now(), 'ok' => true, 'msg' => 'Haettu ' . $n . ' uutta viestiä' ), false );
	return array( 'haettu' => $n );
}

/** Tallentaa yhden raakaviestin. Sama viesti kahteen laatikkoon tallentuu kerran. */
function nap_inbox_store( $raw ) {
	global $wpdb;
	$p    = nap_mime_parse( $raw );
	$h    = $p['headers'];
	$from = nap_mime_addresses( nap_mime_h( $h, 'from' ) );
	$from = $from ? $from[0] : array( '', '' );
	$own  = nap_own_domains();
	$to   = array();
	foreach ( array_merge( nap_mime_addresses( $h['to'] ?? '' ), nap_mime_addresses( $h['cc'] ?? '' ), nap_mime_addresses( $h['delivered-to'] ?? '' ) ) as $a ) {
		if ( in_array( substr( strrchr( $a[1], '@' ), 1 ), $own, true ) && 0 !== strpos( $a[1], strtok( (string) nap_settings()['inbox_user'], '@' ) . '@' ) ) {
			$to[ $a[1] ] = true;
		}
	}
	$msgid = trim( nap_mime_h( $h, 'message-id' ), '<> ' ) ?: md5( $raw );
	$t     = $wpdb->prefix . 'nap_inbox';
	$exist = $wpdb->get_row( $wpdb->prepare( "SELECT id, mailbox FROM $t WHERE msg_id = %s", $msgid ), ARRAY_A ); // phpcs:ignore
	if ( $exist ) {
		$boxes = array_unique( array_merge( array_filter( explode( ', ', $exist['mailbox'] ) ), array_keys( $to ) ) );
		$wpdb->update( $t, array( 'mailbox' => implode( ', ', $boxes ) ), array( 'id' => (int) $exist['id'] ) );
		return false;
	}
	$text = $p['text'] ? $p['text'] : nap_html_to_text( $p['html'] );
	$text = trim( mb_substr( str_replace( "\r", '', $text ), 0, 30000 ) );
	$date = strtotime( nap_mime_h( $h, 'date' ) );
	$m    = array(
		'from_email'  => $from[1],
		'from_name'   => nap_mime_decode_header( $from[0] ),
		'subject'     => mb_substr( trim( nap_mime_decode_header( nap_mime_h( $h, 'subject' ) ) ), 0, 250 ) ?: '(ei aihetta)',
		'text'        => $text,
		'headers'     => $h,
		'attachments' => array_values( array_filter( $p['attachments'], function ( $a ) {
			return ! ( $a['inline'] && 0 === strpos( $a['type'], 'image/' ) ); // allekirjoitusten kuvat pois
		} ) ),
	);
	list( $cat, $why, $cust ) = nap_inbox_classify( $m );

	$files = array();
	foreach ( array_slice( $m['attachments'], 0, 10 ) as $a ) {
		if ( strlen( $a['data'] ) > 15 * MB_IN_BYTES || in_array( $cat, nap_inbox_hidden_categories(), true ) ) {
			continue;
		}
		$dir = nap_private_dir() . '/mail';
		if ( ! is_dir( $dir ) ) {
			wp_mkdir_p( $dir );
		}
		$stored = bin2hex( random_bytes( 16 ) );
		file_put_contents( $dir . '/' . $stored, $a['data'] );
		$files[] = array( 'name' => sanitize_file_name( $a['name'] ), 'type' => $a['type'], 'size' => strlen( $a['data'] ), 'file' => $stored );
	}
	$project = $cust ? nap_customer_projects( (int) $cust['id'] ) : array();
	$project = $project ? $project[0] : null;
	$wpdb->insert( $t, array(
		'msg_id'      => mb_substr( $msgid, 0, 250 ),
		'mailbox'     => implode( ', ', array_keys( $to ) ),
		'from_name'   => mb_substr( $m['from_name'], 0, 190 ),
		'from_email'  => mb_substr( $m['from_email'], 0, 190 ),
		'subject'     => $m['subject'],
		'body'        => $text,
		'attachments' => $files ? wp_json_encode( $files, JSON_UNESCAPED_UNICODE ) : null,
		'received_at' => $date ? wp_date( 'Y-m-d H:i:s', $date ) : nap_now(),
		'category'    => $cat,
		'reason'      => mb_substr( $why, 0, 250 ),
		'customer_id' => $cust ? (int) $cust['id'] : null,
		'project_id'  => $project ? (int) $project['id'] : null,
		'status'      => in_array( $cat, nap_inbox_hidden_categories(), true ) ? 'done' : 'new',
		'created_at'  => nap_now(),
	) );
	$id = (int) $wpdb->insert_id;
	if ( $cust && in_array( $cat, array( 'asiakas', 'reklamaatio', 'skaala' ), true ) ) {
		nap_log( (int) $cust['id'], $project ? (int) $project['id'] : null, 'email', ( 'skaala' === $cat ? 'Skaalan viesti: ' : 'Sähköposti asiakkaalta: ' ) . $m['subject'] );
		if ( 'skaala' !== $cat ) {
			nap_create_task( array(
				'project_id'  => $project ? (int) $project['id'] : null,
				'customer_id' => (int) $cust['id'],
				'title'       => ( 'reklamaatio' === $cat ? 'Reklamaatio: ' : 'Vastaa sähköpostiin: ' ) . mb_substr( $m['subject'], 0, 120 ),
				'assignee_id' => ( $project && $project['owner_id'] ) ? (int) $project['owner_id'] : ( (int) $cust['owner_id'] ?: (int) nap_settings()['default_owner'] ),
				'due_date'    => current_time( 'Y-m-d' ),
			) );
		}
	}
	return $id;
}

/* ---------- Ajastus ja säilytys ---------- */

add_filter( 'cron_schedules', function ( $s ) {
	$s['nap_5min'] = array( 'interval' => 5 * MINUTE_IN_SECONDS, 'display' => 'Viiden minuutin välein' );
	return $s;
} );
add_action( 'init', function () {
	if ( ! wp_next_scheduled( 'nap_inbox_cron' ) ) {
		wp_schedule_event( time() + 120, 'nap_5min', 'nap_inbox_cron' );
	}
} );
add_action( 'nap_inbox_cron', function () {
	if ( nap_inbox_ready() ) {
		nap_inbox_fetch();
	}
} );

/** Vanhat viestit pois: piilotetut 30 päivän, muut asetuksen mukaan (oletus 12 kk) jälkeen. */
add_action( 'nap_hourly', function () {
	global $wpdb;
	if ( (int) get_option( 'nap_inbox_cleanup' ) > time() - DAY_IN_SECONDS ) {
		return;
	}
	update_option( 'nap_inbox_cleanup', time(), false );
	$t      = $wpdb->prefix . 'nap_inbox';
	$hidden = "'" . implode( "','", nap_inbox_hidden_categories() ) . "'";
	$rows   = $wpdb->get_results( $wpdb->prepare( "SELECT id, attachments FROM $t WHERE (category IN ($hidden) AND received_at < %s) OR received_at < %s LIMIT 500", gmdate( 'Y-m-d', strtotime( '-30 days' ) ), gmdate( 'Y-m-d', strtotime( '-' . max( 1, (int) nap_settings()['inbox_months'] ) . ' months' ) ) ), ARRAY_A ); // phpcs:ignore
	foreach ( $rows as $r ) {
		foreach ( (array) json_decode( (string) $r['attachments'], true ) as $f ) {
			@unlink( nap_private_dir() . '/mail/' . basename( $f['file'] ) ); // phpcs:ignore
		}
		$wpdb->delete( $t, array( 'id' => (int) $r['id'] ) );
	}
}, 40 );

/* =====================================================================
 * REST (vain osakkaat)
 * ===================================================================== */

add_action( 'rest_api_init', function () {
	$perm = function () {
		return current_user_can( 'nap_all' );
	};
	register_rest_route( 'nap/v1', '/inbox', array( 'methods' => 'GET', 'callback' => 'nap_api_inbox', 'permission_callback' => $perm ) );
	register_rest_route( 'nap/v1', '/inbox/fetch', array( 'methods' => 'POST', 'callback' => 'nap_api_inbox_fetch', 'permission_callback' => $perm ) );
	register_rest_route( 'nap/v1', '/inbox/(?P<id>\d+)', array(
		array( 'methods' => 'GET', 'callback' => 'nap_api_inbox_item', 'permission_callback' => $perm ),
		array( 'methods' => 'POST', 'callback' => 'nap_api_inbox_update', 'permission_callback' => $perm ),
	) );
} );

function nap_inbox_out( $r, $full = false ) {
	$c   = $r['customer_id'] ? nap_get_customer( (int) $r['customer_id'] ) : null;
	$out = array(
		'id'          => (int) $r['id'],
		'mailbox'     => $r['mailbox'],
		'from_name'   => $r['from_name'],
		'from_email'  => $r['from_email'],
		'subject'     => $r['subject'],
		'snippet'     => mb_substr( preg_replace( '/\s+/', ' ', $r['body'] ), 0, 140 ),
		'received_at' => $r['received_at'],
		'category'    => $r['category'],
		'reason'      => $r['reason'],
		'customer_id' => $r['customer_id'] ? (int) $r['customer_id'] : null,
		'customer'    => $c ? $c['name'] : '',
		'status'      => $r['status'],
		'files'       => count( (array) json_decode( (string) $r['attachments'], true ) ),
	);
	if ( $full ) {
		$out['body']        = $r['body'];
		$out['attachments'] = array_map( function ( $f ) {
			return array( 'name' => $f['name'], 'size' => $f['size'] );
		}, (array) json_decode( (string) $r['attachments'], true ) );
	}
	return $out;
}

function nap_api_inbox( WP_REST_Request $req ) {
	global $wpdb;
	$t      = $wpdb->prefix . 'nap_inbox';
	$where  = '1=1';
	$args   = array();
	$cat    = sanitize_key( (string) $req->get_param( 'cat' ) );
	$hidden = nap_inbox_hidden_categories();
	if ( $cat && isset( nap_inbox_categories()[ $cat ] ) ) {
		$where .= ' AND category = %s';
		$args[] = $cat;
	} else {
		$where .= " AND category NOT IN ('" . implode( "','", $hidden ) . "')";
	}
	if ( 'new' === $req->get_param( 'status' ) ) {
		$where .= " AND status = 'new'";
	}
	$q = trim( (string) $req->get_param( 'q' ) );
	if ( '' !== $q ) {
		$like   = '%' . $wpdb->esc_like( $q ) . '%';
		$where .= ' AND (subject LIKE %s OR from_email LIKE %s OR from_name LIKE %s OR body LIKE %s)';
		array_push( $args, $like, $like, $like, $like );
	}
	$sql  = "SELECT * FROM $t WHERE $where ORDER BY received_at DESC LIMIT 200";
	$rows = $args ? $wpdb->get_results( $wpdb->prepare( $sql, $args ), ARRAY_A ) : $wpdb->get_results( $sql, ARRAY_A ); // phpcs:ignore
	$cnt  = array();
	foreach ( $wpdb->get_results( "SELECT category, SUM(status = 'new') AS n, COUNT(*) AS total FROM $t GROUP BY category", ARRAY_A ) as $r ) { // phpcs:ignore
		$cnt[ $r['category'] ] = array( 'new' => (int) $r['n'], 'total' => (int) $r['total'] );
	}
	return array(
		'items'      => array_map( 'nap_inbox_out', $rows ),
		'counts'     => $cnt,
		'categories' => nap_inbox_categories(),
		'hidden'     => $hidden,
		'ready'      => nap_inbox_ready(),
		'missing'    => nap_inbox_missing(),
		'status'     => get_option( 'nap_inbox_status' ) ?: null,
	);
}

function nap_api_inbox_item( WP_REST_Request $req ) {
	global $wpdb;
	$r = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}nap_inbox WHERE id = %d", (int) $req['id'] ), ARRAY_A ); // phpcs:ignore
	return $r ? nap_inbox_out( $r, true ) : nap_err( 'Viestiä ei löytynyt.', 404 );
}

function nap_api_inbox_update( WP_REST_Request $req ) {
	global $wpdb;
	$t = $wpdb->prefix . 'nap_inbox';
	$r = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $t WHERE id = %d", (int) $req['id'] ), ARRAY_A ); // phpcs:ignore
	if ( ! $r ) {
		return nap_err( 'Viestiä ei löytynyt.', 404 );
	}
	$data = array();
	$cat  = sanitize_key( (string) $req->get_param( 'category' ) );
	if ( $cat && isset( nap_inbox_categories()[ $cat ] ) ) {
		$data['category'] = $cat;
		$data['reason']   = 'Luokitellut ' . wp_get_current_user()->display_name;
	}
	if ( in_array( $req->get_param( 'status' ), array( 'new', 'done' ), true ) ) {
		$data['status'] = $req->get_param( 'status' );
	}
	if ( null !== $req->get_param( 'customer_id' ) ) {
		$cid = (int) $req->get_param( 'customer_id' );
		$c   = $cid ? nap_get_customer( $cid ) : null;
		$data['customer_id'] = $c ? $cid : null;
		if ( $c && (int) $r['customer_id'] !== $cid ) {
			$pr                 = nap_customer_projects( $cid );
			$data['project_id'] = $pr ? (int) $pr[0]['id'] : null;
			nap_log( $cid, $data['project_id'], 'email', 'Sähköposti liitetty: ' . $r['subject'] );
		}
	}
	if ( $data ) {
		$wpdb->update( $t, $data, array( 'id' => (int) $r['id'] ) );
	}
	return nap_api_inbox_item( $req );
}

function nap_api_inbox_fetch() {
	$r = nap_inbox_fetch();
	return is_wp_error( $r ) ? nap_err( $r->get_error_message(), 502 ) : $r;
}

/** Liitteen lataus CRM:stä: /crm/?saapunut=ID&liite=N (vain osakkaat). */
function nap_inbox_send_attachment( $id, $n ) {
	global $wpdb;
	$r     = $wpdb->get_row( $wpdb->prepare( "SELECT attachments FROM {$wpdb->prefix}nap_inbox WHERE id = %d", (int) $id ), ARRAY_A ); // phpcs:ignore
	$files = $r ? (array) json_decode( (string) $r['attachments'], true ) : array();
	$f     = $files[ (int) $n ] ?? null;
	$path  = $f ? nap_private_dir() . '/mail/' . basename( $f['file'] ) : '';
	if ( ! $f || ! is_file( $path ) ) {
		status_header( 404 );
		exit( 'Liitettä ei löytynyt.' );
	}
	nocache_headers();
	$inline = in_array( $f['type'], array( 'application/pdf', 'image/jpeg', 'image/png' ), true );
	header( 'Content-Type: ' . ( $inline ? $f['type'] : 'application/octet-stream' ) );
	header( 'Content-Disposition: ' . ( $inline ? 'inline' : 'attachment' ) . '; filename="' . sanitize_file_name( $f['name'] ) . '"' );
	header( 'X-Content-Type-Options: nosniff' );
	header( 'Content-Length: ' . filesize( $path ) );
	readfile( $path ); // phpcs:ignore
	exit;
}
