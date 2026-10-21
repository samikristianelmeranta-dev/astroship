<?php
/**
 * Sivujen sisältö (arvot, luottamustoimet, työura, suosittelut, UKK).
 * Muokkaa tekstejä tässä tiedostossa – teeman sivupohjat lukevat ne täältä.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

return array(
	'values' => array(
		array(
			'icon' => 'lucide:briefcase',
			'title' => 'Työpaikat ja toimeentulo',
			'text' => 'Työ luo hyvinvointia ja turvaa arjen. Jokaisella on oltava mahdollisuus rakentaa omaa tulevaisuuttaan työn kautta.',
		),
		array(
			'icon' => 'lucide:rocket',
			'title' => 'Yrittäjyyden edistäminen ja sujuvoittaminen',
			'text' => 'Yrittäminen on elinvoimaisen yhteiskunnan perusta. Kun yrittäminen on sujuvaa, syntyy työtä, kasvua ja hyvinvointia.',
		),
		array(
			'icon' => 'lucide:map',
			'title' => 'Varsinais-Suomi elinvoimaisena ja toimivana',
			'text' => 'Tehdään yhdessä Varsinais-Suomesta paikka, jossa on hyvä asua, elää ja yrittää. Alueemme menestys on yhteinen etu.',
		),
		array(
			'icon' => 'lucide:baby',
			'title' => 'Lapset ja nuoret',
			'text' => 'Tulevaisuus rakentuu heidän ympärilleen, ja he ansaitsevat parhaat mahdolliset eväät elämään. Panostus lapsiin on sijoitus tulevaisuuteen.',
		),
		array(
			'icon' => 'lucide:heart-handshake',
			'title' => 'Aktiivinen ja kannustava eläkeikä',
			'text' => 'Jokaisella on oikeus merkitykselliseen ja osallistavaan elämään myös työuran jälkeen. Kokemus ja viisaus ovat voimavaroja, ei menneisyyttä.',
		),
		array(
			'icon' => 'lucide:globe',
			'title' => 'Maahanmuutto',
			'text' => 'Maahanmuuton tulee olla nettohyödyllistä – sen on tuettava työllisyyttä, turvallisuutta ja yhteiskunnan kestävää kehitystä. Oikein toteutettuna maahanmuutto on mahdollisuus. En hyväksy rasismia missään muodossa, mutta maahanmuutosta tulee voida keskustella tarvittaessa myös kriittisesti.',
		),
		array(
			'icon' => 'lucide:school',
			'title' => 'Koulumaailma vaatii ”kurinpalautuksen”',
			'text' => 'Oppimisen edellytyksenä on turvallinen ja hallittu oppimisympäristö, jossa jokainen tietää pelisäännöt ja vastuut. Nyt on aika palauttaa kouluun kurin ja rakenteen merkitys – se ei ole vanhanaikaista autoritarismia, vaan toimivan ja tasa-arvoisen oppimisen perusta.',
		),
		array(
			'icon' => 'lucide:landmark',
			'title' => 'Perinteet kunniaan',
			'text' => 'Perinteet ja arvot ovat yhteisön kantavia voimia. Niiden vaaliminen ei tarkoita muutoksen pelkoa, vaan sitä, että uudistumme viisaasti ja säilytämme sen, mikä toimii ja on hyväksi ihmisille.',
		),
	),
	'changes' => array(
		array(
			'title' => 'Valtioriippuvuus on muodostumassa vakavaksi sairaudeksi',
			'text' => 'Liian moni asia yhteiskunnassa pyörii valtion tukien, avustusten ja jatkuvan rahoituksen varassa. Tarvitsemme yhteiskunnan, jossa ihmisillä ja yrityksillä on mahdollisuus pärjätä omalla työllään ja osaamisellaan. Vahva Suomi syntyy vapaudesta, vastuusta ja luottamuksesta ihmisten omaan tekemiseen.',
		),
		array(
			'title' => 'Poliitikkojen hillotolpat',
			'text' => 'Poliitikon tehtävä on palvella ihmisiä, ei itseään. Hallinnon ja johtotehtävien on oltava läpinäkyviä ja vastuullisia. Haluan olla poliitikko, joka näkee työnsä velvollisuutena, ei etuoikeutena.',
		),
		array(
			'title' => 'Vastuullinen julkinen talous – vähemmän tuhlausta, enemmän järkeä',
			'text' => 'Julkisen sektorin tehtävä on palvella ihmisiä – ei paisuttaa hallintoa. On aika tarkastella kriittisesti, mihin rahamme todella menevät.',
			'items' => array(
				array(
					'Ylisuuriksi paisuneet hallintorakenteet',
					'Tarvitsemme kevyempää, tehokkaampaa hallintoa – ei uusia portaita päätöksenteon väliin.',
				),
				array(
					'Turhat selvitykset ja konsulttihankkeet',
					'Usein asiat voisi hoitaa virkatyönä ja käytännön järjellä.',
				),
				array(
					'IT-hankkeiden kustannusylitykset',
					'Digihankkeiden on palveltava käyttäjiä – ei byrokratiaa.',
				),
				array(
					'Rakentamisen ja remonttien ylitykset',
					'Verorahoilla pitää rakentaa kestävää, ei kallista.',
				),
				array(
					'Tehottomat tukimuodot ja näennäisprojektit',
					'Tuen pitää johtaa tuloksiin, ei raportteihin.',
				),
				array(
					'Kustannustehottomat hankinnat',
					'Tarvitsemme ketteryyttä ja vastuullisuutta julkisiin hankintoihin.',
				),
				array(
					'Ylisuuret palkkiot ja edut',
					'Johtamisen on oltava esimerkki vastuullisuudesta – ei etuoikeuksista.',
				),
			),
		),
	),
	'roles' => array(
		array(
			'title' => 'Varsinais-Suomen maakuntavaltuusto',
			'text' => 'Osallistun maakunnalliseen päätöksentekoon ja aluekehityksen suuntaamiseen Varsinais-Suomen parhaaksi.',
			'active' => true,
		),
		array(
			'title' => 'Kaarinan kaupunginhallitus',
			'text' => 'Osallistun kaupungin strategiseen päätöksentekoon ja palveluiden kehittämiseen.',
			'active' => true,
		),
		array(
			'title' => 'Kaarinan kaupunginvaltuusto',
			'text' => 'Valtuustotyössä vaikutan laajasti kunnan linjauksiin ja budjettivalintoihin.',
			'active' => true,
		),
		array(
			'title' => 'Saaristotoimikunnan jäsen',
			'text' => 'Tuin saaristoalueen elinvoimaa, liikkumista ja palveluiden toimivuutta.',
			'active' => false,
		),
		array(
			'title' => 'Varhan hyvinvointijaoston jäsen',
			'text' => 'Osallistuin hyvinvointiin liittyvien palveluiden kehittämiseen ja valvontaan.',
			'active' => false,
		),
		array(
			'title' => 'Varsinais-Suomen käräjäoikeuden lautamies',
			'text' => 'Toimin lautamiehenä, tukien oikeusprosessien toimivuutta ja oikeudenmukaisuutta.',
			'active' => false,
		),
		array(
			'title' => 'Teknisen lautakunnan varajäsen',
			'text' => 'Kävin läpi teknisen toimialan hankkeita ja toin paikallista näkökulmaa teknisiin ratkaisuihin.',
			'active' => false,
		),
		array(
			'title' => 'Kaarinan nuorisovaltuuston kummivaltuutettu 2022–2023',
			'text' => 'Toimin yhteyshenkilönä nuorisovaltuustolle, jotta nuorten ääni kuuluu päätöksenteossa.',
			'active' => false,
		),
	),
	'testimonials' => array(
		array(
			'quote' => 'Uskaltaa kysyä, kyseenalaistaa ja vastata. Vieläpä rakentavasti. Sellainen on meidän Sami.',
			'name' => 'Veli-Matti Sandberg',
			'role' => 'Turku',
		),
		array(
			'quote' => 'Pitää kiinni päätöksestään, jonka yleensä perustelee asiallisesti! On periaatteen mies, eikä turhia hötkyile! Kuuntelee myös vasemman laidan kulkijoita, vaikka välillä ollaan asioista eri mieltä. Onhan meillä yhteinen päämäärä!',
			'name' => 'Pentti Kallio',
			'role' => 'Kaarinan vasemmistoliitto',
		),
		array(
			'quote' => 'Sami Elmerantaa on helppo suositella, käytännössä mihin tahansa. Yrittäjä ja esimies, mutta ennen kaikkea yksi aktiivisimmista kaupunkilaisista yhteisen hyvän ja elinvoiman saralla.',
			'name' => 'Joonas Wikman',
			'role' => 'Myynnin ammattilainen',
		),
		array(
			'quote' => 'Sami on avoin, rohkea ja erittäin toimelias tekijä. Ottaa asioista selvää, uskaltaa tarvittaessa kyseenalaistaa ja osaa perustella näkökulmansa aina selkeästi. Yhteistyökykyinen ja luotettava tolkun mies.',
			'name' => 'Petteri Skovjberg',
			'role' => 'Kaarina',
		),
	),
	'workTestimonials' => array(
		array(
			'quote' => 'Samilla on vahva ymmärrys myynnistä, markkinoinnista ja esimiestyöstä. Sami on erittäin ”maanläheinen” kaveri ja valmis aina laittamaan itsensä peliin. Kollegana luotettava, avulias ja hyvällä huumorilla varustettu kaveri!',
			'name' => 'Mikko Lahtinen',
			'role' => 'Aluemyyntijohtaja',
		),
		array(
			'quote' => 'Mukava, luotettava ja tukea antava kollega, joka pitää omien puolia.',
			'name' => 'Patrik Lomski',
			'role' => 'Myyntiryhmän päällikkö',
		),
	),
	'career' => array(
		array(
			'title' => 'Aluemyyntijohtaja',
			'org' => 'Sanoma Media Finland Oy',
			'period' => '10/2022 – nykyinen',
			'text' => 'Vastuulla alueellinen myynti, myyntitiimien johtaminen sekä myyntistrategioiden toteuttaminen.',
		),
		array(
			'title' => 'Freelancer – myynnin ja markkinoinnin konsultointi',
			'org' => 'Oma toiminta',
			'period' => '1/2016 – nykyinen',
			'text' => 'Konsultointia myyntiin, markkinointiin ja strategiakehitykseen eri kokoisille yrityksille.',
		),
		array(
			'title' => 'Perustaja / toimitusjohtaja',
			'org' => 'Rakennusliike Sepp Oy',
			'period' => '1/2021 – 11/2022',
			'text' => 'Perustin ja johdin rakennusalan yritystä, vastaten kokonaisvaltaisesti liiketoiminnasta.',
		),
		array(
			'title' => 'Myyntiryhmän päällikkö',
			'org' => 'Markkinointi Routa Oy',
			'period' => '5/2017 – 9/2022',
			'text' => 'Johdin myyntiryhmää, kehitin myyntiprosesseja ja koulutin henkilöstöä.',
		),
		array(
			'title' => 'Toimitusjohtaja / osakas',
			'org' => 'VS-Ravintolapalvelut Oy',
			'period' => '1/2016 – 5/2017',
			'text' => 'Vastasin ravintolatoiminnan operatiivisesta johtamisesta ja kehittämisestä. Johdin kahta ravintolayksikköä.',
		),
		array(
			'title' => 'Tuoteryhmäpäällikkö',
			'org' => 'Fonecta Media',
			'period' => '1/2012 – 12/2015',
			'text' => 'Johdin tuoteryhmää, avainasiakkuustiimiä, tuoteportfoliota ja kaupallisia projekteja.',
		),
		array(
			'title' => 'Tiiminvetäjä',
			'org' => 'Hakukoneyhtiö Vihtori Oy',
			'period' => '8/2009 – 12/2011',
			'text' => 'Vastasin tiimin vetämisestä ja myynnin kehittämisestä hakupalveluiden parissa.',
		),
		array(
			'title' => 'Myyntineuvottelija',
			'org' => 'Hakukoneyhtiö Vihtori Oy',
			'period' => '8/2007 – 8/2009',
			'text' => 'Asiakassopimusten neuvottelu ja myyntitavoitteiden saavuttaminen.',
		),
		array(
			'title' => 'Baarityöntekijä',
			'org' => 'Hissomatum Oy',
			'period' => '1/2002 – 1/2006',
			'text' => 'Asiakaspalvelutyötä ravintola-alalla eri tehtävissä.',
		),
		array(
			'title' => 'Kokoonpanotyöntekijä',
			'org' => 'Trafotec Oy',
			'period' => '2/2003 – 5/2004',
			'text' => 'Työskentely muuntajatehtaan kokoonpanolinjalla.',
		),
	),
	'faq' => array(
		array(
			'q' => 'Kuka on Sami Elmeranta?',
			'a' => 'Sami Elmeranta on kaarinalainen poliitikko, myynnin johtaja ja yrittäjä. Hän on kolmen lapsen isä ja asuu Kaarinan Piikkiössä. Hän edustaa Liike Nytiä.',
		),
		array(
			'q' => 'Mitä luottamustoimia Sami Elmerannalla on?',
			'a' => 'Sami Elmeranta on Kaarinan kaupunginvaltuuston ja kaupunginhallituksen jäsen sekä Varsinais-Suomen maakuntavaltuuston jäsen. Hän on ollut kaupunginvaltuustossa vuodesta 2021.',
		),
		array(
			'q' => 'Mitkä asiat ovat Sami Elmerannalle tärkeimpiä?',
			'a' => 'Työpaikat ja toimeentulo, yrittäjyyden sujuvoittaminen, elinvoimainen Varsinais-Suomi, lapset ja nuoret, vastuullinen julkinen talous sekä turvallinen ja selkeä koulu.',
		),
		array(
			'q' => 'Miten Sami Elmerantaan saa yhteyden?',
			'a' => 'Puhelimitse numerosta 050 346 0444, WhatsAppilla, sähköpostilla tai sivuston yhteydenottosivun kautta. Kaikki yhteydenotot ovat tervetulleita.',
		),
	),
);
