# Nordic Asiakasportaali (vaihe 1)

WordPress-lisäosa ikkunakauppias.fi:hin: oma myynti- ja projektinhallinta (Nordic CRM, osoitteessa `/crm/`)
ja asiakkaan Oma kohde -sivu (`/oma-kohde/`). FluentCRM jää markkinointiin, tämä ei korvaa sitä.

## Mitä vaihe 1 sisältää
- Omat tietokantataulut: asiakkaat, kohteet, tehtävät ja tapahtumahistoria.
- Roolit **Nordic: osakas** (näkee kaiken, asetukset, käyttäjät) ja **Nordic: asentaja** (näkee vain kohteet,
  joihin hänet on merkitty asentajaksi, ja omat tehtävänsä).
- Tarjouspyyntölomake (Fluent Forms) luo automaattisesti asiakkaan ja kohteen vaiheeseen *Tarjouspyyntö*,
  tehtävän "Soita ja sovi mittaus" seuraavalle arkipäivälle vastuumyyjälle, kiitosviestin asiakkaalle
  Oma kohde -linkillä ja ilmoituksen henkilökunnalle.
- Nordic CRM (`/crm/`): Oma päivä, Myyntiputki, Asiakkaat ja asiakaskortti, Tehtävät, Uusi tarjouspyyntö.
  Toimii myös puhelimella.
- **Uusi tarjouspyyntö käsin** (puhelu, sähköposti, messut, paikan päällä): asiakas, kohde, tuotteet, asiakkaan tarve,
  lähde ja suosittelija, vastuumyyjä. Seuraava askel valitaan: soittotehtävä, jo sovittu mittauskäynti (kohde menee suoraan
  vaiheeseen Mittaus) tai ei tehtävää. Kiitosviestin ja Oma kohde -linkin voi lähettää, jos sähköposti on tiedossa.
  Jos sähköpostilla on jo asiakas, kohde lisätään hänelle.
- **Liitä asiakkaan viesti** (WhatsApp, tekstiviesti, Messenger, sähköposti): lomake poimii viestistä nimen, puhelimen,
  sähköpostin, osoitteen, paikkakunnan ja tuotteet valmiiksi kenttiin ja kertoo, jos numero tai sähköposti on jo asiakkaalla.
- **Androidin Jaa-valikko:** kun CRM on lisätty kotinäytölle (Chrome → ⋮ → Lisää aloitusnäyttöön / Asenna sovellus),
  WhatsApp-viestin voi jakaa suoraan Nordic CRM:ään, ja Uusi tarjouspyyntö aukeaa täytettynä. iPhonella kopioi ja liitä. Asiakaskortin **+ Uusi kohde** lisää kohteen suoraan olemassa olevalle asiakkaalle.
- Vaiheen siirto kirjautuu historiaan ja lähettää asiakkaalle ilmoituksen (voi kytkeä pois).
- Asiakkaan kirjautuminen ilman salasanaa: linkki sähköpostiin (voimassa 7 vrk).
  Linkkiin voi palata voimassaolon aikana. Uuden linkin voi pyytää Oma kohde -sivulta. Pyynnöt on rajoitettu, eikä sivu paljasta, onko osoite asiakkaana.
- Suosittelulinkki `ikkunakauppias.fi/s/KOODI`: uusi tarjouspyyntö merkitään suositelluksi ja näkyy
  suosittelijan asiakaskortilla.

## Automaatiot (asetuksissa, oletuksena päällä)
- **Vaiheen vaihto luo tehtävät:** Tarjous lähetetty → "Seuraa tarjousta: soita asiakkaalle" (3 arkipv), Hyväksytty → "Tilaa tuotteet
  Skaalalta", Tilaus → "Sovi asennuspäivä asiakkaan kanssa" (asentajalle), Asennus → "Luovutus", Valmis → "Laskuta työ"
  ja "Kiitoslahja ja suosittelupyyntö". Kun mittaus on sovittu, avoin "Soita ja sovi mittaus" kuittautuu itsestään.
- **Liidivahti:** jos tarjouspyyntöön ei ole reagoitu 24 tunnissa (ei soittoa, muistiinpanoa tai kuitattua tehtävää),
  vastuumyyjä saa muistutuksen; 48 tunnin jälkeen muut osakkaat saavat ilmoituksen.
- **Aamukooste** arkiaamuisin klo 7: omat tehtävät (myös myöhässä olevat), osakkaille uudet tarjouspyynnöt
  ja maanantaisin yli 2 viikkoa samassa vaiheessa olleet kohteet.
- Ajastus käyttää WordPressin cronia. Täsmällisyyttä varten cPanel → Cron Jobs → kerran tunnissa:
  `wget -q -O /dev/null https://ikkunakauppias.fi/wp-cron.php?doing_wp_cron`

## Toimitustapa
Jokaisella kohteella on toimitustapa: **Asennettuna** (oletus), **Tuotteet ilman asennusta (mittaamme)** tai
**Vain tuotteet, asiakas mittaa itse**. Valitaan Uusi tarjouspyyntö -lomakkeella (tai poimitaan liitetystä viestistä),
verkkosivun lomakkeelta tai myöhemmin kohteen kortilta.
- *Asiakas mittaa itse:* ensimmäinen tehtävä on "Soita ja pyydä mitat", kiitosviesti kertoo, että tarjous tehdään asiakkaan
  mitoilla, ja **mittavastuuehto** näkyy kiitosviestissä, kohteen kortilla, hyväksyntälomakkeella ja vahvistuksessa.
  Ehdon teksti muokataan asetuksissa.
- *Ilman asennusta:* asennustehtävien tilalle tulevat "Sovi toimitus tai nouto" ja "Toimita tuotteet asiakkaalle".
  Asiakkaan sivulla ja viesteissä vaihe *Asennus* näkyy nimellä *Toimitus*.
- Nordic CRM (FluentCRM) ei lähetä näille mittauskäyntiä lupaavia viestejä (vaatii nordic-crm 1.2.4).

### Verkkosivun tarjouslomakkeeseen (Fluent Forms, lomake 3)
1. Fluent Forms → lomake 3 → lisää kenttä **Radio** ja anna sille *Name Attribute* `toimitustapa`.
   Otsikko: "Toimitustapa". Vaihtoehdot (Label ja Value samat):
   - `Asennettuna` (oletusvalinta)
   - `Tuotteet ilman asennusta`
   - `Vain tuotteet, mittaan itse`
2. Lisää kenttä **Checkbox**, *Name Attribute* `mittavastuu`, vaihtoehto
   "Vastaan itse ilmoittamieni mittojen oikeellisuudesta." Merkitse pakolliseksi ja laita
   *Conditional Logic*: näytä, kun `toimitustapa` = `Vain tuotteet, mittaan itse`.
3. Tallenna ja lähetä testi: CRM:ssä kohteen kortilla näkyy toimitustapa.

## Tarjous asiakkaalle
Kohteen kortilla **Lähetä tarjous asiakkaalle** (myös kun vaiheeksi valitaan *Tarjous lähetetty* tai myyntiputken nuoli):
- **Valmis PDF-tarjous** (oletus): liitetään vain Skaalan tarjoustyökalun PDF. Erittely, hinta ja voimassaolo ovat PDF:ssä,
  eikä muita kenttiä käytetä. Hinnan voi halutessaan kirjata myyntiputkeen; se ei näy asiakkaalle.
- **Täytä tiedot**: hinta (sis. alv), työn osuus, asiakkaalle näkyvä sisältö ja lisätiedot; PDF on valinnainen.
- Tarjous on voimassa **14 päivää** lähetyspäivästä (asetuksissa muutettavissa).
- Asiakas saa sähköpostiin **suoran linkin tarjoukseen** (`/tarjous/…`): ei kirjautumista, ja samaan linkkiin voi palata
  90 päivän ajan millä tahansa laitteella. Tarjoussivulla avataan PDF, valitaan maksutapa, vahvistetaan ehdot ja
  hyväksytään, tai kysytään / kerrotaan, ettei tarjous ole ajankohtainen. Vastaukset sähköpostiin menevät lähettäjälle.
- Asiakas näkee Oma kohde -sivulla hinnan, sisällön, PDF:n, peruutusehdot (ja omat mitat -ehdon) ja voi
  **hyväksyä** (valitsee maksutavaksi laskun tai Skaala-rahoituksen ja vahvistaa ehdot), **kysyä tarkennusta** tai kertoa,
  ettei ole kiinnostunut. Myyjä saa ilmoituksen ja tarvittaessa tehtävän; CRM näyttää, milloin asiakas avasi tarjouksen.
- Hyväksyntä toimii kuten henkilökunnan kirjaama: vaihe Hyväksytty, tehtävät ja vahvistusviesti.
- Muistutukset asiakkaalle 3 ja 7 päivän kohdalla ja 2 päivää ennen voimassaolon päättymistä (yksi kerrallaan).
- Päivitetyn tarjouksen voi lähettää, kunnes tarjous on hyväksytty.
- PDF:t tallentuvat kansioon `wp-content/uploads/nap-private/`, joka on suljettu suoralta lataukselta;
  niitä näytetään vain kirjautuneelle asiakkaalle ja henkilökunnalle.

## Tarjouksen hyväksyntä asiakkaan puolesta
Kun asiakas hyväksyy tarjouksen puhelimessa, paikan päällä, sähköpostilla, viestillä tai allekirjoittamalla paperin,
myyjä tai asentaja kirjaa sen kohteen kortilta: **✓ Hyväksy tarjous asiakkaan puolesta**.
- Kirjataan hyväksytty hinta, miten asiakas hyväksyi, maksutapa (Holvi-lasku / Skaala-rahoitus OP / ei vielä sovittu)
  ja lisätiedot. Kirjaajan nimi ja aika tallentuvat.
- Kohde siirtyy vaiheeseen Hyväksytty, ja tehtävä "Tilaa tuotteet Skaalalta" syntyy (rahoituksessa myös
  "Hoida Skaala-rahoituksen hakemus (OP)").
- Asiakas saa halutessa sähköpostivahvistuksen (hinta, maksutapa, peruutusehdot, Oma kohde -linkki).
  Peruutusehtojen teksti muokataan asetuksissa. Vastaukset menevät kirjaajalle.
- Asentaja voi hyväksyä vain omia kohteitaan. Myyntiputken nuoli ja vaihevalikko avaavat hyväksyntälomakkeen,
  jotta jokaisesta hyväksytystä kaupasta jää merkintä.

## Saapuneet (myynti@ ja info@)
CRM:n näkymä **Saapuneet** (vain osakkaat) kerää myynti@- ja info@-laatikoiden viestit ja tunnistaa ne säännöillä:
Tarjouspyyntö, Asiakkaan viesti, Reklamaatio / takuu, Skaala, Lasku / kuitti, Toimittajat ja yhteistyö, Muut sekä
piilotetut Uutiskirjeet, Sivuston ilmoitukset ja Roskaposti. Jokaisessa viestissä näkyy tunnistuksen syy, ja luokan voi vaihtaa.
- Asiakkaan viesti ja reklamaatio liitetään asiakkaan historiaan ja vastuumyyjälle tulee tehtävä (reklamaatio samalle päivälle).
- Skaalan viestit liitetään asiakkaaseen, jos asiakkaan nimi tai osoite mainitaan.
- Tarjouspyynnöstä **Luo tarjouspyyntö** avaa lomakkeen täytettynä (nimi, sähköposti, puhelin, osoite, tuotteet).
- Sama viesti molempiin laatikoihin tallentuu kerran. Liitteet tallentuvat suojattuun kansioon.
- Viestit säilytetään 12 kk (asetus); roskaposti, uutiskirjeet ja sivuston ilmoitukset 30 päivää.

**Käyttöönotto (cPanel, Polar55):**
1. cPanel → **Email Accounts** → luo keräyslaatikko, esim. `crm@ikkunakauppias.fi` (vahva salasana).
2. cPanel → **Forwarders** → *Add Forwarder*: osoite `myynti` → *Forward to Email Address* `crm@ikkunakauppias.fi`.
   Toista osoitteelle `info`. Alkuperäiset viestit jäävät edelleen myynti@- ja info@-laatikoihin.
3. WordPress → Asetukset → Asiakasportaali → **Saapuneet**: ruksi päälle, keräyslaatikko ja salasana
   (palvelin `localhost`, portti 993, SSL). Tallenna ja paina CRM:n Saapuneet-näkymässä **Hae uudet**.
4. CRM hakee viestit 5 minuutin välein ja poistaa haetut viestit keräyslaatikosta.
   Täsmällistä ajoitusta varten cPanel → Cron Jobs → 5 minuutin välein:
   `wget -q -O /dev/null https://ikkunakauppias.fi/wp-cron.php?doing_wp_cron`
   Salasanan voi asetussivun sijaan antaa wp-config.php:ssä: `define( 'NAP_MAIL_PASS', '...' );`

Tulossa seuraavissa vaiheissa: dokumentit
ja viestit, kalenteri, kiitoslahja ja suosittelulahjat, yleinen muistutus meistä, raportit. Katso
`../asiakasportaali-prototyyppi/VAATIMUKSET.md`.

## Kalenteri ja käynnit
- **Kalenteri** (CRM-valikko): viikkonäkymä asentajittain, puhelimessa päivälista. Klikkaa tyhjää kohtaa tai "+ Uusi käynti". Käynnin voi varata myös asiakaskortilta.
- Käyntityypit: mittaus (1 h), asennus (8 h), toimitus, huolto, muu. Kesto ja asentaja valitaan lomakkeella.
- **Asiakkaalle**: vahvistus heti varattaessa, uusi viesti siirrosta tai perumisesta ja muistutus edellisenä päivänä (lähtee klo 12 jälkeen). Tulevat käynnit näkyvät myös Oma kohde -sivulla.
- **Vaiheet etenevät itse**: mittaus varattu → Mittaus, asennus/toimitus varattu → Asennus, luovutus kuitattu → Valmis.
- **Asentajan puhelinnäkymä**: Oma päivä näyttää päivän käynnit. Käynnillä: Navigoi (Google Maps / karttasovellus), Soita, tilanapit (Matkalla, Aloitettu, Tehty).
- **Kuvat**: Ennen, Jälkeen, Mittaus ja Muut. "Ota kuva" avaa kameran. Kuvat pienennetään puhelimessa (1600 px) ja tallennetaan suojattuun kansioon `uploads/nap-private/files`.
- **Mittauspöytäkirja**: rivit (paikka, tuote, leveys × korkeus, kpl, huomio) + yleiset huomiot ja mittauskuvat; Tulosta / PDF.
- **Luovutuskuittaus**: tarkistuslista, huomautukset, asiakkaan nimi ja allekirjoitus sormella näytölle. Kuittaus merkitsee kohteen valmiiksi, lähettää asiakkaalle kiitosviestin ja luo laskutus- ja kiitoslahjatehtävät (huomautuksista oma tehtävä).

## Asennus
1. WordPress → Lisäosat → Lisää uusi → Lataa lisäosa → `nordic-asiakasportaali-0.11.1.zip` → Ota käyttöön.
2. Asetukset → Kestolinkit → **Tallenna muutokset** (varmistaa, että `/crm/` ja `/oma-kohde/` toimivat).
3. Asetukset → **Asiakasportaali**:
   - *Tarjouslomakkeet*: Fluent Formsin lomakkeen ID (näkyy Fluent Forms → Lomakkeet, sarake ID). Jos kenttä on tyhjä,
     käytetään Nordic CRM -lisäosan asetusten lomakkeita.
   - *Uuden tarjouspyynnön vastuumyyjä*: kenelle uudet tarjouspyynnöt ja soittotehtävät menevät.
   - Paina **Luo puuttuvat osakastunnukset**: luo tunnukset anton@, sami@, matias@ ja jussi@ikkunakauppias.fi.
     Kukin saa sähköpostiin linkin oman salasanan asettamiseen.
4. Asentajat: Käyttäjät → Lisää uusi → rooli **Nordic: asentaja**.
5. **Kirjautuminen:** henkilökunta kirjautuu osoitteessa `ikkunakauppias.fi/crm/` käyttäjänimellä (tai sähköpostilla)
   ja salasanalla. "Pysy kirjautuneena" pitää kirjautumisen voimassa 90 päivää samalla laitteella. Arvausesto:
   5 väärää salasanaa samalle tunnukselle tai 10 samasta osoitteesta → 15 minuutin tauko (koskee myös wp-login.php:tä).
   Lisäturvaksi voi asetuksista ottaa käyttöön sähköpostiin tulevan kirjautumiskoodin (oletuksena pois). Hätätilassa
   koodin saa pois wp-config.php:n rivillä `define( 'NAP_2FA_DISABLE', true );`.
6. Jos käytössä on välimuisti (WP-Optimize, LiteSpeed tms.), lisää poikkeuksiin `/crm/`, `/oma-kohde/` ja `/s/`.
   Lisäosa merkitsee sivut myös itse ei-välimuistettaviksi.

## Käyttö
- Henkilökunta: kirjaudu WordPressiin ja avaa `ikkunakauppias.fi/crm/`. Kannattaa lisätä puhelimen kotinäytölle.
- Asiakas: saa linkin automaattisesti tarjouspyynnön jälkeen. Asiakaskortin painike **Lähetä Oma kohde -linkki**
  lähettää uuden linkin (esim. käsin lisätylle asiakkaalle).

## Tietoturva
- REST-rajapinta `nap/v1` vaatii kirjautumisen ja tarkistaa oikeuden jokaiseen asiakkaaseen ja kohteeseen erikseen.
- Kirjautumislinkistä tallennetaan vain tiiviste. Linkki on kertakäyttöinen, ja asiakkaan istuntoeväste on allekirjoitettu.
- Sivut ovat `noindex` eivätkä mene välimuistiin. Sähköposteissa on vain linkkejä, ei henkilö- tai hintatietoja.
