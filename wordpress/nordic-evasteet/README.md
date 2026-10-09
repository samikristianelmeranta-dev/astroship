# Nordic Evästeet 1.0.0

Kevyt evästesuostumus ikkunakauppias.fi:lle. Ei ulkoisia palveluita, ei tietokantatauluja, ei asetussivua.

## Mitä se tekee
- Näyttää bannerin ensimmäisellä käynnillä: **Hyväksy kaikki**, **Vain välttämättömät** ja **Asetukset** (yhtä näkyvät painikkeet, kuten Traficom edellyttää).
- **Google Consent Mode v2:** ennen Google-tagia sivulle tulostetaan oletus "kaikki kielletty". Google Analytics ei tallenna evästeitä ennen kuin kävijä hyväksyy tilastot.
- **WP Consent API:** ilmoittaa valinnan Site Kitille ja muille lisäosille, jotka tukevat WP Consent API:a.
- Muistaa valinnan 12 kuukautta (eväste `nordic_consent`). Kun suostumus perutaan, `_ga`-evästeet poistetaan ja sivu ladataan uudelleen.
- Evästeasetukset voi avata uudelleen Nordic-teeman (2.5.1+) alatunnisteen linkistä, lyhytkoodilla `[nordic_evasteasetukset]` tai millä tahansa linkillä osoitteeseen `#evasteasetukset`.

## Asennus
1. Poista ensin muut evästebannerit käytöstä (esim. Complianz, Cookie Notice), ettei bannereita näy kahta.
2. Lisäosat → Lisää uusi → Lataa lisäosa → `nordic-evasteet-1.0.0.zip` → Asenna → Ota käyttöön.
3. Päivitä teema versioon 2.5.1, niin alatunnisteeseen tulee Evästeasetukset-linkki.
4. Site Kit:
   - Asenna ja ota käyttöön lisäosa **WP Consent API** (WordPress.org, maksuton). Site Kit käyttää sitä suostumuksen lukemiseen.
   - Site Kit → Asetukset → **Hallinta-asetukset** (Admin Settings) → **Consent Mode** → **Ota käyttöön**.
     Site Kitin pitäisi näyttää, että WP Consent API on käytössä.
5. Tyhjennä välimuisti (jos käytössä on esim. LiteSpeed Cache) ja testaa yksityisessä ikkunassa.

## Testaus
- Banneri näkyy ensimmäisellä käynnillä.
- Kehittäjätyökalut (F12) → Application → Cookies: ennen hyväksyntää ei `_ga`-evästeitä. Hyväksynnän jälkeen ne ilmestyvät.
- Google Analytics → Raportit → Reaaliaikainen: käynti näkyy hyväksynnän jälkeen.
- Google Tag Assistant (tagassistant.google.com): Consent-välilehdellä default = denied, ja update = granted hyväksynnän jälkeen.

## Muokkaus
- Markkinointi-kategoria (Google Ads, Meta Pixel), kun sellaisia otetaan käyttöön: lisää WPCode-snippetiksi
  `add_filter( 'nordic_evasteet_markkinointi', '__return_true' );`
- Kysy kaikilta uudelleen: nosta `NORDIC_EVASTEET_POLICY` tiedostossa `nordic-evasteet.php`.
- Muissa lisäosissa: PHP `nordic_has_consent( 'statistics' )`, JS `window.nordicConsent.get()` ja tapahtuma `nordic:consent`.
