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
- Asiakkaan kirjautuminen ilman salasanaa: kertakäyttöinen linkki sähköpostiin (voimassa 7 vrk).
  Linkin voi pyytää uudelleen Oma kohde -sivulta. Pyynnöt on rajoitettu, eikä sivu paljasta, onko osoite asiakkaana.
- Suosittelulinkki `ikkunakauppias.fi/s/KOODI`: uusi tarjouspyyntö merkitään suositelluksi ja näkyy
  suosittelijan asiakaskortilla.

Tulossa seuraavissa vaiheissa: tarjous linkkiin ja hyväksyntä, maksutapa (Holvi / Skaala-rahoitus OP), dokumentit
ja viestit, kalenteri, kiitoslahja ja suosittelulahjat, yleinen muistutus meistä, raportit. Katso
`../asiakasportaali-prototyyppi/VAATIMUKSET.md`.

## Asennus
1. WordPress → Lisäosat → Lisää uusi → Lataa lisäosa → `nordic-asiakasportaali-0.3.0.zip` → Ota käyttöön.
2. Asetukset → Kestolinkit → **Tallenna muutokset** (varmistaa, että `/crm/` ja `/oma-kohde/` toimivat).
3. Asetukset → **Asiakasportaali**:
   - *Tarjouslomakkeet*: Fluent Formsin lomakkeen ID (näkyy Fluent Forms → Lomakkeet, sarake ID). Jos kenttä on tyhjä,
     käytetään Nordic CRM -lisäosan asetusten lomakkeita.
   - *Uuden tarjouspyynnön vastuumyyjä*: kenelle uudet tarjouspyynnöt ja soittotehtävät menevät.
   - Paina **Luo puuttuvat osakastunnukset**: luo tunnukset anton@, sami@, matias@ ja jussi@ikkunakauppias.fi.
     Kukin saa sähköpostiin linkin oman salasanan asettamiseen.
4. Asentajat: Käyttäjät → Lisää uusi → rooli **Nordic: asentaja**.
5. Kaksivaiheinen kirjautuminen on valmiina päällä, eikä mitään tarvitse asentaa: osakkaat, asentajat ja ylläpitäjät
   saavat salasanan jälkeen sähköpostiin 6-numeroisen koodin. "Muista tämä laite 30 päivää" ohittaa koodin samalla
   selaimella, ja salasanan vaihto nollaa muistetut laitteet. Jos jollakulla on jo Two Factor- tai Wordfence-2FA
   käytössä, koodia ei kysytä kahteen kertaan. Hätätilassa (esim. sähköposti rikki) koodin saa pois lisäämällä
   wp-config.php:hen rivin `define( 'NAP_2FA_DISABLE', true );`.
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
