# Nordic Kysy meiltä – UKK-chat (ilmaisversio)

Kevyt chat-ikkuna ikkunakauppias.fi-sivustolle. Se vastaa yleisimpiin
kysymyksiin sivuston omista **usein kysytyistä kysymyksistä** ja näyttää
linkin sivulle, jolta vastaus löytyy. Jos valmista vastausta ei löydy,
asiakas voi lähettää kysymyksen suoraan osoitteeseen
**info@ikkunakauppias.fi**.

- **Ilmainen:** ei tekoälyä, ei API-avaimia, ei ulkoisia palveluita eikä
  kuukausikuluja.
- **Kevyt:** noin 30 kt koodia. Kysymysaineisto (noin 130 kt, pakattuna
  murto-osa) ladataan vasta, kun kävijä avaa ikkunan.
- **Päivittyy itsestään:** vastaukset tulevat sivujen UKK-osioista (sivun
  FAQ-skeema tai `.faq-item`-lohkot). Kun muokkaat tai lisäät UKK-kysymyksen
  sivulle, chat käyttää uutta tekstiä seuraavasta tallennuksesta alkaen.

> Tämä on vaihtoehto tekoälyä käyttävälle **Nordic Chat** -lisäosalle. Pidä
> käytössä vain toinen niistä, ettei sivulle tule kahta chat-painiketta.

## Miten se toimii

1. Kävijä klikkaa pikakysymystä tai kirjoittaa oman kysymyksen.
2. Selain etsii parhaiten sopivan UKK-kysymyksen. Haku ymmärtää taivutusmuodot
   karkeasti ("ikkunoita", "ikkunoiden") ja tavallisimmat synonyymit (hinta ~
   maksaa ~ kustannus, talvi ~ pakkanen, asennus ~ asentaa jne.) sekä
   paikkakunnat.
3. Vastauksessa näkyvät kysymys, vastaus, linkki lähdesivulle,
   *Pyydä ilmainen tarjous* -painike ja enintään kolme liittyvää kysymystä.
4. Jos varmaa osumaa ei ole, ikkuna ehdottaa lähimpiä kysymyksiä ja sivuja ja
   tarjoaa lomakkeen (kysymys, nimi, sähköposti). Viesti tulee
   info@ikkunakauppias.fi-osoitteeseen, ja *Vastaa* vastaa suoraan kysyjälle.

Keskustelu säilyy, kun kävijä siirtyy sivulta toiselle (selaimen välilehden
ajan). Mitään ei tallenneta palvelimelle. Ainoastaan lähetetty kysymys
lähtee sähköpostina.

## Käyttöönotto

1. Lisäosat → Lisää uusi → Lataa lisäosa → `nordic-faq-1.0.0.zip` → Aktivoi.
2. Jos Nordic Chat on käytössä, poista se käytöstä.
3. Avaa sivusto ja kokeile esim. "Mitä ikkunaremontti maksaa?" ja jotain,
   mihin ei ole vastausta, ja lähetä se omalla sähköpostiosoitteellasi.
   Viestin pitäisi tulla info@-osoitteeseen.
4. Tietosuojaseloste: lisää lyhyt osio (ks. alla).

Sähköpostit lähtevät WordPressin kautta. Jos sivuston muutkin lomakeviestit
tulevat perille (esim. WP Mail SMTP / FluentSMTP), nämäkin tulevat.

Teeman painikkeet: linkki `href="#chat"` tai attribuutti `data-open-chat`
avaa ikkunan. *Pyydä tarjous* -painike avaa teeman tarjouslomakkeen.

## Muokkaus

Asetukset tehdään suodattimilla, esim. teeman `functions.php`:hen tai
Code Snippets -lisäosalla:

```php
// Pikakysymykset (näkyvät, kun ikkuna avataan). Kirjoita ne samoin kuin sivujen UKK:ssa.
add_filter( 'nordic_faq_quick', function () {
	return array(
		'Mitä ikkunaremontti maksaa?',
		'Kuinka kauan ulko-oven vaihto kestää?',
		'Saako remontista kotitalousvähennystä?',
	);
} );

// Tervehdys
add_filter( 'nordic_faq_greeting', function () {
	return 'Hei! Kysy ikkunoista tai ovista.';
} );

// Vastaanottaja
add_filter( 'nordic_faq_email', function () {
	return 'info@ikkunakauppias.fi';
} );
```

Paras tapa parantaa vastauksia on lisätä sivuille UKK-kysymyksiä niillä
sanoilla, joilla asiakkaat kysyvät. Jos sama kysymys on sekä yleisellä
sivulla että paikkakuntasivulla, chat käyttää yleisen sivun versiota.

## Roskapostisuojaus

- piilokenttä, jonka vain robotit täyttävät
- enintään 5 kysymystä tunnissa samasta IP-osoitteesta
- lähetys hyväksytään vain omalta sivustolta

## Tietosuojaselosteeseen

> **Kysy meiltä -ikkuna.** Sivuston kysymysikkuna hakee vastaukset
> sivustomme omista sisällöistä selaimessasi, eikä kirjoittamiasi
> kysymyksiä tallenneta. Jos lähetät kysymyksen meille, käsittelemme
> antamasi nimen, sähköpostiosoitteen, kysymyksen ja sivun osoitteen
> vastataksemme sinulle (oikeutettu etu / pyynnöstäsi tehtävät
> toimenpiteet). Viesti säilytetään kuten muutkin yhteydenotot.

## Tiedostot

| Tiedosto | Sisältö |
|---|---|
| `nordic-faq.php` | Aineiston kokoaminen sivuilta (välimuisti 12 h, tyhjenee sivun tallennuksessa), REST-rajapinnat `GET /wp-json/nordic-faq/v1/aineisto` ja `POST /wp-json/nordic-faq/v1/kysymys` |
| `assets/faq-match.js` | Haku: taivutus, synonyymit, paikkakunnat, pisteytys |
| `assets/faq.js`, `assets/faq.css` | Chat-ikkuna |
