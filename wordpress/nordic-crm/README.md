# Nordic CRM – sähköpostiautomaatiot

WordPress-lisäosa, joka rakentaa ikkunakauppias.fi:n sähköpostiautomaatiot
FluentCRM:ään. Se toimii **FluentCRM:n ilmaisversiolla**, eikä Pro-versiota tarvita.

## Mitä lisäosa tekee

Kun asiakas jättää yhteydenoton, hänelle lähtee heti palveluviesti. Jos hän
antoi markkinointiluvan, perään lähtee lyhyt jatkosarja.

| Yhteydenotto | Heti (kaikille) | Jatkosarja (vain luvan antaneille) |
|---|---|---|
| Tarjouspyyntö | Tarjouspyyntösi on perillä | 2 pv: mittauskäynti · +4 pv: kolmi- vai nelilasinen · +5 pv: kotitalousvähennys · +7 pv: jäikö kysyttävää |
| Tarjouspyyntö taloyhtiösivulta | Tarjouspyyntönne on perillä | 2 pv: PTS ja kuntotarkastus · +5 pv: rahoitus ja kustannusten jako · +7 pv: hinta-arvio yhtiökokoukseen |
| Esitteen tilaus | Pyytämäsi esite | 3 pv: mistä hinta muodostuu · +7 pv: tarkka hinta mittauskäynnillä |
| Laskurin tulos sähköpostiin | Säästölaskelmasi (asiakkaan omat luvut) | 3 pv: U-arvo · +7 pv: laskurista tarkkaan arvioon |
| Oppaan lataus | Pyytämäsi opas: *oppaan nimi* | 7 pv: Vieläkö mietit ikkuna- ja oviratkaisua? (yhteystiedot) |

Lisäksi lisäosa:
- luo tagit (Lähde: …, Markkinointilupa, Asiakas, Ei kiinnostunut) ja
  laskurin lisäkentät
- kääntää tuplavahvistusviestin suomeksi
- tuo kaksi lomaketta: `[nordic_esite_lomake]` ja `[nordic_laskuri_lomake]`.
  Nordic-teema lisää laskurilomakkeen laskurisivulle automaattisesti ja
  esitelomakkeen Ikkunat- ja Ovet-sivuille, kun esitteen osoite on asetettu.
- **pysäyttää jatkosarjan**, kun kontaktille lisätään tagi *Asiakas* tai
  *Ei kiinnostunut*

## Ladattavat oppaat

Lisäosa tuo WordPressiin valikon **Oppaat**. Jokainen opas on oma kohteensa:

| Kenttä | Mihin |
|---|---|
| Otsikko | Oppaan nimi kortissa, sähköpostissa ja tagissa |
| Ote | Lyhyt kuvaus kortissa |
| Kansikuva | Kortin yläosan kuva (esim. PDF:n ensimmäinen sivu) |
| Oppaan tiedosto (PDF) | Valitse mediakirjastosta |
| Järjestys | Sivun määritteet → Järjestys |

Lisäosan mukana tulee viisi valmista opasta (Skaalan tuote-esitteet): Aukea-,
Aasa- ja Aava-ikkuna, terassi- ja parvekeovet sekä palo-ovet. Asennus kopioi PDF:t
ja kansikuvat mediakirjastoon ja luo oppaat kerran. Poistettua opasta ei palauteta
päivityksessä.

Sivulle oppaat lisätään lyhytkoodilla:

- `[nordic_oppaat]` kaikki julkaistut oppaat korttiruudukkona (esim. sivu *Oppaat*)
- `[nordic_oppaat ids="12,15" otsikko="Ilmaiset oppaat"]` valitut oppaat ja otsikko
- `[nordic_opas id="12"]` yksi opas leveänä nostona, esim. artikkelin loppuun

Kun kävijä lataa oppaan:
1. Hän antaa etunimen ja sähköpostin. Markkinointilupa on erillinen, vapaaehtoinen ruutu.
2. Kontakti tallentuu FluentCRM:ään tageilla *Lähde: Opas* ja *Opas: oppaan nimi*,
   ja kenttään *Viimeksi ladattu opas*.
3. Latauslinkki näkyy heti sivulla, ja opas lähtee myös sähköpostiin.
4. Luvan antaneet saavat viikon päästä viestin *Vieläkö mietit ikkuna- ja oviratkaisua?*, jossa on yhteystiedot.

Analytics saa tapahtumat `guide_download` (lomake lähetetty) ja `guide_open` (PDF avattu).

Kaikki automaatiot ja viestit näkyvät FluentCRM:ssä (*Automations*), ja niitä voi
muokata siellä kuten mitä tahansa FluentCRM-automaatiota.

## Käyttöönotto

### 1. Sähköpostin lähetys kuntoon (tärkein vaihe)
WordPressin oma sähköposti päätyy helposti roskapostiin. Siksi:
1. Asenna **FluentSMTP** (ilmainen) ja kytke se lähetyspalveluun, esimerkiksi
   Brevo (ilmainen 300 viestiin/pv asti) tai Amazon SES.
2. Lisää verkkotunnuksen DNS-asetuksiin lähetyspalvelun antamat **SPF-, DKIM-
   ja DMARC**-tietueet. Ilman niitä Gmail ja Outlook hylkäävät osan viesteistä.
3. Lähettäjäosoitteeksi kannattaa valita osoite, jota oikeasti luetaan, esim.
   `info@ikkunakauppias.fi`. Viestit kehottavat vastaamaan suoraan.

### 2. FluentCRM
1. Asenna ja aktivoi **FluentCRM** (ilmainen, wordpress.org).
2. FluentCRM → Settings → Email Settings: *From Name* on allekirjoittajan nimi
   (esim. "Matti / Ikkunakauppias.fi"), ja *Reply-to* on luettava osoite.
3. FluentCRM → Settings → Business Settings: yrityksen nimi ja osoite
   (näkyvät peruutussivulla).
4. Varmista, että WordPressin ajastus toimii. Paras tapa on palvelimen cron,
   joka kutsuu `wp-cron.php`:tä minuutin välein (webhotellin hallintapaneelissa).
   Ilman sitä jatkoviestit lähtevät vasta, kun joku käy sivustolla.

### 3. Tarjouslomakkeeseen luparuutu
Fluent Forms → lomake 3 (tarjouspyyntö) → lisää kenttä **Checkbox**:
- Name Attribute: `markkinointilupa`
- Vaihtoehto: *Saa lähettää minulle myös muutaman vinkin ikkuna- ja
  oviremontista. Voin perua tilauksen milloin tahansa.*
- **Ei pakollinen, eikä oletuksena valittu** (lupa pitää antaa itse).

Jos lomakkeessa on jo Fluent Formsin oma *FluentCRM-integraatio*, poista se
käytöstä. Nordic CRM hoitaa kontaktin luonnin ja luvan käsittelyn.

### 4. Lisäosa
1. Lisäosat → Lisää uusi → Lataa lisäosa → `nordic-crm-v1.2.1.zip` → Aktivoi.
   Päivitettäessä valitse *Korvaa nykyinen*. Uudet automaatiot asennetaan
   automaattisesti, kun avaat seuraavan kerran hallintapaneelin.
2. Avaa jokin hallintasivu. Lisäosa luo tagit, kentät ja 8 automaatiota, ja
   yläreunaan tulee ilmoitus.
3. **Asetukset → Nordic CRM**: allekirjoittajana on valmiiksi Sami Elmeranta,
   ja esitteenä lisäosan mukana tuleva 8-sivuinen PDF. Täydennä puhelinnumero
   ja tietosuojaselosteen osoite.
4. **Tietosuojaseloste**: Asetukset → Tietosuoja → Ohjeet → *Nordic CRM*.
   Kopioi teksti selosteeseen ja täydennä hakasulkeissa olevat kohdat
   (Y-tunnus, osoite, palveluntarjoajat). Sama teksti on tiedostossa
   `TIETOSUOJA.md`.

### 5. Testaa itse
Lähetä tarjouspyyntö omalla osoitteellasi luparuutu valittuna. Sinulle pitäisi
tulla heti vahvistus ja tuplavahvistuspyyntö. FluentCRM → Contacts näyttää
kontaktin, tagit ja automaation tilan.

## Päivittäinen käyttö

- **Kauppa syntyi tai asiakas kieltäytyi:** lisää kontaktille tagi *Asiakas*
  tai *Ei kiinnostunut*. Jatkoviestit loppuvat heti.
- **Viestin muokkaus:** FluentCRM → Automations → automaatio → viesti. Kun
  lisäosaa myöhemmin päivitetään, se ei kirjoita käsin muokattujen viestien
  päälle.
- **Vastaukset:** viestit pyytävät vastaamaan suoraan, joten vastaukset
  tulevat Reply-to-osoitteeseen.

## Esite

`assets/nordic-esite-2026.pdf` on 8-sivuinen esite: yritys, ikkunamallit,
lasit ja energia, ulko-ovet, ovityypit ja värit, hinnat ja kotitalousvähennys
sekä yhteystiedot. Lähde on tiedostossa `wordpress/esite/esite.html`, ja PDF
tehdään komennolla `node build.mjs` (vaatii Playwrightin).

## Markkinointilupa ja lainsäädäntö

- Palveluviesti (vahvistus, esite, laskelma) on asiakkaan itse pyytämä, joten
  se lähtee aina. Lisäosa lähettää sen myös asiakkaalle, joka on aiemmin
  perunut markkinointiviestit.
- Jatkoviestit ovat sähköistä suoramarkkinointia ja vaativat luvan. Ne lähtevät
  vain luparuudun valinneille, ja tuplavahvistuksen ollessa päällä vasta
  vahvistuksen jälkeen.
- Luvan ajankohta ja sivu tallentuvat kontaktin tietoihin.
- Jokaisessa markkinointiviestissä on peruutuslinkki.
- Lisää tietosuojaselosteeseen markkinointirekisterin kuvaus (mitä tietoja,
  mihin tarkoitukseen, säilytysaika).

## Viestien tyyli

Viestit on kirjoitettu kirjeen tapaan: lyhyet kappaleet, ei huutomerkkejä, ei
mainoskliseitä, yksi asia per viesti ja oikean ihmisen allekirjoitus. Faktat
(hinnat, kotitalousvähennys, U-arvot) on poimittu sivuston omista oppaista.
Jos lisäät viestejä, katso kirjoitusohje tiedoston `includes/emails.php` alusta.

## Tekninen rakenne

| Tiedosto | Sisältö |
|---|---|
| `includes/emails.php` | Viestien tekstit ja sarjat |
| `includes/template.php` | Sähköpostipohja (Outlook- ja mobiiliyhteensopiva) |
| `includes/trigger.php` | FluentCRM-käynnistin *Nordic: uusi yhteydenotto* |
| `includes/leads.php` | Lomakkeiden käsittely, luvan logiikka, pysäytystagit |
| `includes/forms.php` | Esite- ja laskurilomakkeet |
| `includes/guides.php` | Oppaat: hallinta, lyhytkoodit ja latauslomake |
| `includes/smartcodes.php` | Muuttujat `{{nordic.tervehdys}}`, `{{nordic.allekirjoitus}}`, `{{nordic.esite_url}}`, `{{nordic.laskelma}}`, `{{nordic.opas_nimi}}`, `{{nordic.opas_url}}` |
| `includes/installer.php` | Automaatioiden asennus ja päivitys |

Testattu: WordPress 7.1.2, FluentCRM 2.9.84, PHP 8.3.
