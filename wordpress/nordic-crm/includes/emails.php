<?php
/**
 * Automaatioiden viestit.
 *
 * Kirjoitusohje (jos muokkaat tai lisäät viestejä):
 * - Kirjoita kuin kirjoittaisit yhdelle asiakkaalle. Lyhyitä kappaleita, ei huutomerkkejä.
 * - Yksi asia per viesti ja korkeintaan yksi linkki, jota pyydetään klikkaamaan.
 * - Vain asioita, jotka pitävät paikkansa ja löytyvät myös sivustolta.
 * - Älä lupaa aikatauluja tai hintoja, joita ei voi pitää.
 *
 * Muuttujat:
 *   {{nordic.tervehdys}}     "Hei Matti," tai "Hei," jos nimeä ei ole
 *   {{nordic.allekirjoitus}} allekirjoitus asetuksista
 *   {{nordic.esite_url}}     esitteen osoite asetuksista
 *   {{nordic.laskelma}}      laskurin tulos (laskurisarja)
 *   {{nordic.opas_nimi}}     ladatun oppaan nimi (opassarja)
 *   {{nordic.opas_url}}      ladatun oppaan PDF (opassarja)
 *   %site%                   sivuston osoite ilman loppukauttaviivaa (korvataan asennuksessa)
 *
 * Jokaisella viestillä on pysyvä avain (key). Asennus päivittää viestin, jos sen
 * teksti tässä tiedostossa muuttuu, mutta ei koske FluentCRM:ssä käsin muokattuihin
 * viesteihin, ellei päivitystä erikseen pakoteta.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

function nordic_crm_flows() {
	return array(

		/* -------------------------------------------------------------
		 * TARJOUSPYYNTÖ (koti)
		 * ------------------------------------------------------------- */
		array(
			'key'       => 'tarjous-palvelu',
			'title'     => 'Tarjouspyyntö: vahvistus (kaikille)',
			'lead_type' => 'tarjous',
			'audience'  => 'all',
			'steps'     => array(
				array(
					'key'      => 'tarjous-vahvistus',
					'wait'     => 0,
					'subject'  => 'Tarjouspyyntösi on perillä',
					'preheader'=> 'Otamme yhteyttä viimeistään seuraavana arkipäivänä.',
					'body'     => <<<'HTML'
<p>{{nordic.tervehdys}}</p>
<p>kiitos tarjouspyynnöstä. Se tuli perille, ja otamme sinuun yhteyttä viimeistään seuraavana arkipäivänä.</p>
<p>Ensimmäiseksi sovimme mittauskäynnin. Se on maksuton, eikä se sido sinua mihinkään. Käymme paikan päällä, mittaamme karmit ja katsomme, mitkä mallit sopivat taloosi. Sen jälkeen saat kirjallisen tarjouksen.</p>
<p>Jos haluat kertoa jo nyt jotain lisää, kuten montako ikkunaa tai ovea on kyseessä tai mikä aikataulu sinulla on mielessä, vastaa suoraan tähän viestiin.</p>
<p>{{nordic.allekirjoitus}}</p>
HTML
				),
			),
		),

		array(
			'key'       => 'tarjous-jatko',
			'title'     => 'Tarjouspyyntö: jatkoviestit (markkinointilupa)',
			'lead_type' => 'tarjous',
			'audience'  => 'consent',
			'steps'     => array(
				array(
					'key'      => 'tarjous-mittauskaynti',
					'wait'     => 2,
					'subject'  => 'Mitä mittauskäynnillä tapahtuu',
					'preheader'=> 'Lyhyesti, mitä käynnillä käydään läpi ja mitä kannattaa miettiä etukäteen.',
					'body'     => <<<'HTML'
<p>{{nordic.tervehdys}}</p>
<p>moni kysyy, mitä mittauskäynnillä oikeastaan tehdään, joten tässä lyhyesti.</p>
<p>Mittaamme jokaisen karmin paikan päällä. Skaala-ikkunat ja -ovet tehdään mittatilaustyönä Ylihärmässä, joten mitat otetaan aina kohteesta eikä vanhoista piirustuksista. Samalla katsomme, missä kunnossa nykyiset ikkunat ja karmit ovat.</p>
<p>Etukäteen kannattaa miettiä kolmea asiaa:</p>
<ul>
<li>Mitkä ikkunat tai ovet vaihdetaan nyt ja mitkä voisivat odottaa.</li>
<li>Haluatko avattavia vai kiinteitä ikkunoita, esimerkiksi isoihin maisemaikkunoihin.</li>
<li>Värit sisä- ja ulkopuolelle, jos ne eivät ole samat kuin nyt.</li>
</ul>
<p>Mitään ei tarvitse päättää valmiiksi. Käydään ne yhdessä läpi.</p>
<p>{{nordic.allekirjoitus}}</p>
HTML
				),
				array(
					'key'      => 'tarjous-lasit',
					'wait'     => 4,
					'subject'  => 'Kolmi- vai nelilasinen ikkuna?',
					'preheader'=> 'Ero näkyy lämmityslaskussa. Tässä mitä se tarkoittaa käytännössä.',
					'body'     => <<<'HTML'
<p>{{nordic.tervehdys}}</p>
<p>yksi asia, joka tulee lähes aina vastaan tarjousvaiheessa, on lasien määrä.</p>
<p>Skaalan Aukea on kolmilasinen ja Suomen yleisin ikkunamalli. Aarre+ on nelilasinen matalaenergiaikkuna, samoin piilosaranoitu Arvo+. Nelilasisilla malleilla energiankulutus voi pienentyä jopa 70 % normitasoon verrattuna.</p>
<p>Kaikissa nelilasisissa ja energiatehokkaimmissa kolmilasisissa ikkunoissa on lisäksi vakiona FrostFree, joka estää ikkunan huurtumista.</p>
<p>Kumpi kannattaa, riippuu talosta ja lämmitysmuodosta. Vaihtoehdot on käyty tarkemmin läpi täällä:<br>
<a href="%site%/kolmilasinen-vai-nelilasinen-ikkuna/">Kolmilasinen vai nelilasinen ikkuna?</a></p>
<p>{{nordic.allekirjoitus}}</p>
HTML
				),
				array(
					'key'      => 'tarjous-kotitalousvahennys',
					'wait'     => 5,
					'subject'  => 'Kotitalousvähennys: laskuesimerkki',
					'preheader'=> 'Paljonko asennustyöstä saa takaisin verotuksessa.',
					'body'     => <<<'HTML'
<p>{{nordic.tervehdys}}</p>
<p>ikkuna- ja oviremontin asennustyöstä saa kotitalousvähennystä, ja se kannattaa ottaa huomioon, kun vertaat tarjouksia.</p>
<p>Vähennys on 40 % työn osuudesta. Omavastuu on 100 euroa, ja vähennyksen enimmäismäärä on 2 250 euroa henkilöä kohden.</p>
<p>Esimerkki: jos laskun työn osuus on 3 000 euroa, vähennyskelpoinen osuus on 1 200 euroa. Kun siitä vähennetään 100 euron omavastuu, verotuksessa saat 1 100 euroa takaisin.</p>
<p>Vähennys haetaan jälkikäteen verotuksessa, joten säilytä lasku, josta työn osuus näkyy erikseen. Säännöt voivat muuttua vuosittain, joten ajantasaiset tiedot kannattaa tarkistaa vero.fi:stä.</p>
<p><a href="%site%/kotitalousvahennys-ikkuna-ovi/">Lue lisää kotitalousvähennyksestä</a></p>
<p>{{nordic.allekirjoitus}}</p>
HTML
				),
				array(
					'key'      => 'tarjous-kysyttavaa',
					'wait'     => 7,
					'subject'  => 'Jäikö jotain kysyttävää?',
					'preheader'=> 'Maksutavat, talviasennus, vanhojen ikkunoiden poisvienti.',
					'body'     => <<<'HTML'
<p>{{nordic.tervehdys}}</p>
<p>halusin vielä tarkistaa, jäikö tarjouspyyntöön liittyen jotain auki. Muutama asia, jota meiltä kysytään usein:</p>
<ul>
<li><strong>Voiko maksaa osissa?</strong> Kyllä. Maksun voi jakaa useampaan erään, ja käymme vaihtoehdot läpi tarjouksen yhteydessä.</li>
<li><strong>Voiko ikkunat vaihtaa talvella?</strong> Kyllä. Kuiva talvi-ilma sopii asennukseen hyvin, ja aikataulut ovat usein joustavampia.</li>
<li><strong>Mitä vanhoille ikkunoille tapahtuu?</strong> Purku ja purkujätteen vienti kuuluvat asennukseen.</li>
</ul>
<p>Jos jokin muu mietityttää, vastaa tähän viestiin.</p>
<p>{{nordic.allekirjoitus}}</p>
HTML
				),
			),
		),

		/* -------------------------------------------------------------
		 * TARJOUSPYYNTÖ (taloyhtiö) – lähetetty taloyhtiösivuilta
		 * ------------------------------------------------------------- */
		array(
			'key'       => 'taloyhtio-palvelu',
			'title'     => 'Taloyhtiö: vahvistus (kaikille)',
			'lead_type' => 'taloyhtio',
			'audience'  => 'all',
			'steps'     => array(
				array(
					'key'      => 'taloyhtio-vahvistus',
					'wait'     => 0,
					'subject'  => 'Tarjouspyyntönne on perillä',
					'preheader'=> 'Otamme yhteyttä viimeistään seuraavana arkipäivänä.',
					'body'     => <<<'HTML'
<p>{{nordic.tervehdys}}</p>
<p>kiitos taloyhtiönne tarjouspyynnöstä. Otamme yhteyttä viimeistään seuraavana arkipäivänä.</p>
<p>Taloyhtiön remontti etenee yleensä eri tahtiin kuin omakotitalon, koska päätökset tehdään hallituksessa ja yhtiökokouksessa. Kertokaa siis mielellään, missä vaiheessa hanke on: onko kyseessä alustava hinta-arvio esimerkiksi PTS:ää varten vai jo varsinainen tarjous.</p>
<p>Voitte vastata suoraan tähän viestiin.</p>
<p>{{nordic.allekirjoitus}}</p>
HTML
				),
			),
		),

		array(
			'key'       => 'taloyhtio-jatko',
			'title'     => 'Taloyhtiö: jatkoviestit (markkinointilupa)',
			'lead_type' => 'taloyhtio',
			'audience'  => 'consent',
			'steps'     => array(
				array(
					'key'      => 'taloyhtio-pts',
					'wait'     => 2,
					'subject'  => 'Ikkunaremontti ja PTS: mistä liikkeelle',
					'preheader'=> 'Kuntotarkastus ennen isoja päätöksiä säästää keskustelua myöhemmin.',
					'body'     => <<<'HTML'
<p>{{nordic.tervehdys}}</p>
<p>taloyhtiön ikkunaremontti näkyy yleensä pitkän tähtäimen suunnitelmassa (PTS) hyvissä ajoin. Se antaa aikaa miettiä rahoitus ja aikataulu rauhassa.</p>
<p>Ennen isoja päätöksiä suosittelemme teettämään ulkopuolisen kuntotarkastuksen. Silloin hallituksella ja osakkailla on puolueeton arvio siitä, missä kunnossa ikkunat oikeasti ovat. Se helpottaa yhtiökokousta, kun keskustelu ei jää mielipiteiden varaan.</p>
<p>Kokosimme hallitukselle ja isännöitsijälle oppaan, jossa nämä asiat ovat samassa paikassa:<br>
<a href="%site%/taloyhtion-ikkunaremontti/">Taloyhtiön ikkunaremontti: opas hallitukselle ja isännöitsijälle</a></p>
<p>{{nordic.allekirjoitus}}</p>
HTML
				),
				array(
					'key'      => 'taloyhtio-rahoitus',
					'wait'     => 5,
					'subject'  => 'Rahoitus ja kustannusten jako osakkaille',
					'preheader'=> 'Kolme tavallisinta tapaa rahoittaa remontti.',
					'body'     => <<<'HTML'
<p>{{nordic.tervehdys}}</p>
<p>yhtiökokouksessa kysytään lähes aina samaa: paljonko tämä maksaa minulle ja miten se maksetaan. Taloyhtiöissä käytetään yleensä jotain näistä:</p>
<ul>
<li><strong>Kertavastike.</strong> Osakkaat maksavat osuutensa kerralla.</li>
<li><strong>Rahoitusvastike.</strong> Taloyhtiö ottaa remonttilainan, ja osakkaat maksavat osuutensa kuukausittain.</li>
<li><strong>Osakaskohtainen lisälaina.</strong> Osakas maksaa oman osuutensa erikseen pankkilainalla, jos taloyhtiö tarjoaa siihen mahdollisuuden.</li>
</ul>
<p>Kustannukset jaetaan tavallisesti yhtiöjärjestyksen mukaan, useimmiten osakkeiden lukumäärän suhteessa. Jakoperuste kannattaa tarkistaa omasta yhtiöjärjestyksestä ja käydä läpi isännöitsijän kanssa ennen kokousta.</p>
<p><a href="%site%/taloyhtion-ikkunaremontti-kustannusten-jako/">Lue lisää kustannusten jakamisesta</a></p>
<p>{{nordic.allekirjoitus}}</p>
HTML
				),
				array(
					'key'      => 'taloyhtio-kokous',
					'wait'     => 7,
					'subject'  => 'Tarvitaanko hinta-arvio yhtiökokoukseen?',
					'preheader'=> 'Kertokaa aikataulu, niin sovitetaan tarjous siihen.',
					'body'     => <<<'HTML'
<p>{{nordic.tervehdys}}</p>
<p>jos ikkunaremontti on tulossa seuraavan yhtiökokouksen asialistalle, kertokaa kokouksen ajankohta. Sovitetaan mittauskäynti ja kirjallinen tarjous sen mukaan.</p>
<p>Se, riittääkö päätökseen yksinkertainen enemmistö, riippuu hankkeen laajuudesta ja yhtiöjärjestyksestä. Sen voi varmistaa isännöitsijältä.</p>
<p>Voitte vastata suoraan tähän viestiin.</p>
<p>{{nordic.allekirjoitus}}</p>
HTML
				),
			),
		),

		/* -------------------------------------------------------------
		 * ESITTEEN TILAUS
		 * ------------------------------------------------------------- */
		array(
			'key'       => 'esite-palvelu',
			'title'     => 'Esite: lähetys (kaikille)',
			'lead_type' => 'esite',
			'audience'  => 'all',
			'steps'     => array(
				array(
					'key'      => 'esite-lahetys',
					'wait'     => 0,
					'subject'  => 'Pyytämäsi esite',
					'preheader'=> 'Esite on linkin takana, ja voit tallentaa sen itsellesi.',
					'body'     => <<<'HTML'
<p>{{nordic.tervehdys}}</p>
<p>tässä pyytämäsi esite:</p>
<p><a href="{{nordic.esite_url}}"><strong>Avaa esite</strong></a></p>
<p>Esitteessä on Skaalan ikkuna- ja ovimallit. Kaikki mallit tehdään mittatilaustyönä, joten koot ja värit sovitaan aina kohteen mukaan.</p>
<p>Jos jokin malli kiinnostaa tai haluat tietää, mitä se maksaisi juuri sinun taloosi, vastaa tähän viestiin.</p>
<p>{{nordic.allekirjoitus}}</p>
HTML
				),
			),
		),

		array(
			'key'       => 'esite-jatko',
			'title'     => 'Esite: jatkoviestit (markkinointilupa)',
			'lead_type' => 'esite',
			'audience'  => 'consent',
			'steps'     => array(
				array(
					'key'      => 'esite-hinta',
					'wait'     => 3,
					'subject'  => 'Mistä ikkunaremontin hinta muodostuu',
					'preheader'=> 'Tuotteet, asennus ja lisätyöt. Suuntaa-antavat hinnat.',
					'body'     => <<<'HTML'
<p>{{nordic.tervehdys}}</p>
<p>esitteen jälkeen seuraava kysymys on yleensä hinta, joten tässä suuntaa.</p>
<p>Yksittäisen ikkunan vaihto asennettuna maksaa tyypillisesti noin 400–1 500 euroa. Omakotitalossa, jossa on 7–15 ikkunaa, koko remontti on yleensä 6 000–20 000 euroa. Ero tulee koosta, mallista, lasituksesta ja siitä, paljonko asennustyötä kohde vaatii.</p>
<p>Karkeasti hinta jakautuu näin:</p>
<ul>
<li>tuotteet noin 40–60 %</li>
<li>asennustyö noin 25–40 %</li>
<li>loput purku, jätehuolto ja viimeistely.</li>
</ul>
<p>Asennustyön osuudesta saa lisäksi kotitalousvähennystä.</p>
<p><a href="%site%/ikkunoiden-ja-ikkunaremontin-hinta/">Koko hintaopas</a></p>
<p>{{nordic.allekirjoitus}}</p>
HTML
				),
				array(
					'key'      => 'esite-mittaus',
					'wait'     => 7,
					'subject'  => 'Tarkka hinta omaan taloosi',
					'preheader'=> 'Mittauskäynti on maksuton eikä sido mihinkään.',
					'body'     => <<<'HTML'
<p>{{nordic.tervehdys}}</p>
<p>hintaoppaan luvut ovat haarukoita. Tarkan hinnan saa vasta, kun ikkunat on mitattu.</p>
<p>Mittauskäynti on maksuton, eikä se sido mihinkään. Käymme paikan päällä Turussa tai muualla Varsinais-Suomessa, mittaamme karmit ja käymme mallit läpi kanssasi. Sen jälkeen saat kirjallisen tarjouksen.</p>
<p><a href="%site%/yhteystiedot/">Pyydä mittauskäynti</a> tai vastaa tähän viestiin, niin otamme yhteyttä.</p>
<p>{{nordic.allekirjoitus}}</p>
HTML
				),
			),
		),

		/* -------------------------------------------------------------
		 * OPPAAN LATAUS
		 * ------------------------------------------------------------- */
		array(
			'key'       => 'opas-palvelu',
			'title'     => 'Opas: lähetys (kaikille)',
			'lead_type' => 'opas',
			'audience'  => 'all',
			'steps'     => array(
				array(
					'key'      => 'opas-lahetys',
					'wait'     => 0,
					'subject'  => 'Pyytämäsi opas: {{nordic.opas_nimi}}',
					'preheader'=> 'Opas on linkin takana, ja voit tallentaa sen itsellesi.',
					'body'     => <<<'HTML'
<p>{{nordic.tervehdys}}</p>
<p>kiitos, että latasit oppaan. Tässä se on vielä tallessa:</p>
<p><a href="{{nordic.opas_url}}"><strong>Avaa opas: {{nordic.opas_nimi}}</strong></a></p>
<p>Voit palata siihen rauhassa milloin tahansa tämän viestin kautta.</p>
<p>Jos jokin kohta herättää kysymyksiä omasta talostasi, vastaa suoraan tähän viestiin. Viesti tulee meille, ei automaatille.</p>
<p>{{nordic.allekirjoitus}}</p>
HTML
				),
			),
		),

		array(
			'key'       => 'opas-jatko',
			'title'     => 'Opas: jatkoviestit (markkinointilupa)',
			'lead_type' => 'opas',
			'audience'  => 'consent',
			'steps'     => array(
				array(
					'key'      => 'opas-kysyttavaa',
					'wait'     => 3,
					'subject'  => 'Jäikö oppaasta jotain mietityttämään',
					'preheader'=> 'Kolme asiaa, joita kysytään useimmin.',
					'body'     => <<<'HTML'
<p>{{nordic.tervehdys}}</p>
<p>latasit muutama päivä sitten sivuiltamme oppaan {{nordic.opas_nimi}}. Sen jälkeen meiltä kysytään yleensä näitä kolmea asiaa:</p>
<ul>
<li><strong>Mitä remontti maksaa?</strong> Yksittäisen ikkunan vaihto asennettuna on tyypillisesti noin 400–1 500 euroa. Tarkka hinta riippuu koosta, mallista ja asennuksesta.</li>
<li><strong>Saako kotitalousvähennystä?</strong> Saa, asennustyön osuudesta.</li>
<li><strong>Pitääkö kaikki vaihtaa kerralla?</strong> Ei tarvitse. Remontin voi tehdä osissa, esimerkiksi huonokuntoisimmat ensin.</li>
</ul>
<p>Jos mielessä on jotain muuta, vastaa tähän viestiin.</p>
<p>{{nordic.allekirjoitus}}</p>
HTML
				),
				array(
					'key'      => 'opas-mittaus',
					'wait'     => 7,
					'subject'  => 'Oppaasta omaan taloon',
					'preheader'=> 'Mittauskäynti on maksuton eikä sido mihinkään.',
					'body'     => <<<'HTML'
<p>{{nordic.tervehdys}}</p>
<p>opas antaa hyvän pohjan, mutta jokainen talo on vähän erilainen. Siksi tarkka suunnitelma ja hinta tehdään aina paikan päällä.</p>
<p>Mittauskäynti on maksuton, eikä se sido mihinkään. Mittaamme karmit, katsomme nykyisten ikkunoiden ja ovien kunnon ja käymme mallit läpi kanssasi. Sen jälkeen saat kirjallisen tarjouksen.</p>
<p><a href="%site%/yhteystiedot/">Pyydä mittauskäynti</a> tai vastaa tähän viestiin, niin otamme yhteyttä.</p>
<p>{{nordic.allekirjoitus}}</p>
HTML
				),
			),
		),

		/* -------------------------------------------------------------
		 * ENERGIANSÄÄSTÖLASKURI
		 * ------------------------------------------------------------- */
		array(
			'key'       => 'laskuri-palvelu',
			'title'     => 'Laskuri: tuloksen lähetys (kaikille)',
			'lead_type' => 'laskuri',
			'audience'  => 'all',
			'steps'     => array(
				array(
					'key'      => 'laskuri-tulos',
					'wait'     => 0,
					'subject'  => 'Säästölaskelmasi',
					'preheader'=> 'Laskurin tulos tallessa sähköpostissasi.',
					'body'     => <<<'HTML'
<p>{{nordic.tervehdys}}</p>
<p>tässä energiansäästölaskurin tulos, jonka pyysit itsellesi:</p>
{{nordic.laskelma}}
<p>Laskuri käyttää Suomen keskimääräisiä lämmitystarvelukuja ja energian keskihintoja, joten tulos on suuntaa-antava. Todellinen säästö riippuu talosta, ikkunoiden koosta ja säästä.</p>
<p>Tarkemman arvion saa, kun ikkunat käydään paikan päällä läpi. Mittauskäynti on maksuton. Jos haluat sen, vastaa tähän viestiin.</p>
<p>{{nordic.allekirjoitus}}</p>
HTML
				),
			),
		),

		array(
			'key'       => 'laskuri-jatko',
			'title'     => 'Laskuri: jatkoviestit (markkinointilupa)',
			'lead_type' => 'laskuri',
			'audience'  => 'consent',
			'steps'     => array(
				array(
					'key'      => 'laskuri-u-arvo',
					'wait'     => 3,
					'subject'  => 'Mistä säästö oikeastaan tulee',
					'preheader'=> 'U-arvo kertoo, paljonko lämpöä karkaa ikkunan läpi.',
					'body'     => <<<'HTML'
<p>{{nordic.tervehdys}}</p>
<p>laskurin tulos perustuu yhteen lukuun: ikkunan U-arvoon. Se kertoo, paljonko lämpöä karkaa ikkunan läpi. Mitä pienempi luku, sitä vähemmän lämpöä menee hukkaan.</p>
<p>Esimerkiksi 1970-luvun alkuperäisissä ikkunoissa U-arvo on voinut olla jopa 2,8. Nykyiset rakennusmääräykset edellyttävät enintään 1,0, ja Skaalan Arvo-ikkunoissa U-arvo on parhaimmillaan 0,57. Ero vanhan ja uuden välillä on siis moninkertainen.</p>
<p><a href="%site%/1970-luvun-talon-ikkunaremontti/">Esimerkki: 1970-luvun talon ikkunaremontti</a></p>
<p>{{nordic.allekirjoitus}}</p>
HTML
				),
				array(
					'key'      => 'laskuri-kotikaynti',
					'wait'     => 7,
					'subject'  => 'Laskurista tarkkaan arvioon',
					'preheader'=> 'Maksuton mittauskäynti ja kirjallinen tarjous.',
					'body'     => <<<'HTML'
<p>{{nordic.tervehdys}}</p>
<p>laskuri antaa suunnan, mutta oman talon tarkkaa säästöä se ei tiedä. Siihen vaikuttavat ikkunoiden koko, ilmansuunnat ja nykyisten ikkunoiden todellinen kunto.</p>
<p>Ne näkee parhaiten paikan päällä. Mittauskäynti on maksuton, eikä se sido mihinkään. Sen jälkeen saat kirjallisen tarjouksen.</p>
<p><a href="%site%/yhteystiedot/">Pyydä mittauskäynti</a> tai vastaa tähän viestiin.</p>
<p>{{nordic.allekirjoitus}}</p>
HTML
				),
			),
		),
	);
}

/**
 * Tuplavahvistusviesti (double opt-in), kun asiakas antaa markkinointiluvan.
 */
function nordic_crm_double_optin_email() {
	return array(
		'subject' => 'Vahvista vielä, että haluat viestejämme',
		'body'    => <<<'HTML'
<p>{{nordic.tervehdys}}</p>
<p>annoit luvan lähettää sinulle sähköpostia ikkuna- ja oviremontista. Vahvista se vielä alla olevasta linkistä, niin tiedämme, että osoite on oikea.</p>
<p><a href="#activate_link#"><strong>Vahvistan tilauksen</strong></a></p>
<p>Lähetämme muutaman viestin, emme enempää. Jokaisen viestin lopussa on linkki, josta voit perua tilauksen.</p>
<p>Jos et pyytänyt tätä, voit jättää viestin huomiotta.</p>
<p>{{nordic.allekirjoitus}}</p>
HTML
	);
}
