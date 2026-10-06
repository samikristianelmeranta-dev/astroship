# Aluesivustojen kirjoitusohje

Rakennetaan kolme erillistä WordPress-sivustoa (omat alidomainit), jotka käyttävät samaa
Nordic-teemaa kuin pääsivusto ikkunakauppias.fi. Jokaisella sivustolla on täsmälleen
9 sivua. Kirjoitat yhden alueen 8 sivua (energialaskuri tehdään erikseen).

## Tiedostot, jotka tuotat

Kansio: `/home/user/astroship/wordpress/alueet/<alue>/` (alue = `uusimaa`, `meri-lappi` tai `satakunta`)

| Tiedosto | Slug | Valikossa |
|---|---|---|
| `etusivu.html` | `etusivu` | Etusivu |
| `ikkunat.html` | `ikkunat` | Ikkunat |
| `ulko-ovet.html` | `ulko-ovet` | Ulko-ovet |
| `ikkunaremontit.html` | `ikkunaremontit` | Ikkunaremontit |
| `oviremontit.html` | `oviremontit` | Oviremontit |
| (tehdään erikseen) | `energiansaastolaskuri` | Energialaskuri |
| `ikkuna-asennus.html` | `ikkuna-asennus` | Ikkuna-asennus |
| `uudet-ikkunat-vai-huolto.html` | `uudet-ikkunat-vai-huolto` | Uudet ikkunat vai huolto |
| `yhteystiedot.html` | `yhteystiedot` | Yhteystiedot |

Lisäksi `sivut.json`:
```json
[
  {"slug": "etusivu", "title": "…", "description": "…"},
  …8 riviä, sama järjestys kuin taulukossa (ilman energiansaastolaskuria)
]
```
- `title` = WordPressin sivun otsikko ja selaimen välilehden otsikko (brändi lisätään automaattisesti
  perään). Hakusana + alue, enintään ~55 merkkiä. Esim. "Ikkunat Helsinkiin, Espooseen ja Vantaalle".
- `description` = hakukoneen kuvaus, 130–155 merkkiä, sisältää alueen ja houkuttelee klikkaamaan.

## Sivuston sisäiset linkit

Linkitä vain näihin yhdeksään sivuun suhteellisilla osoitteilla: `/`, `/ikkunat/`, `/ulko-ovet/`,
`/ikkunaremontit/`, `/oviremontit/`, `/energiansaastolaskuri/`, `/ikkuna-asennus/`,
`/uudet-ikkunat-vai-huolto/`, `/yhteystiedot/`. Ei linkkejä muille sivuille tai pääsivustolle.
Jokaisella sivulla 3–6 luontevaa tekstin sisäistä linkkiä muille sivuille.

## Sivun rakenne (HTML)

Sivu on yksi HTML-pätkä, joka alkaa `<main>` ja päättyy `</main>`. Ei `<html>`, `<head>`, `<style>`
tai `<script>`. Käytä VAIN alla olevia teeman komponentteja ja luokkia (mallit kansiossa
`alueet/_malli/` – katso esim. `ikkunaremontti.html`, `ikkunat.html`, `uudet-ikkunat.html`,
`yhteystiedot.html`, `etusivu-sisalto.html`). Älä keksi uusia luokkia äläkä käytä inline-tyylejä
(paitsi mallien `style="margin-top:24px"` tms. pienet välit).

Tyypillinen järjestys:
1. **Hero** (ainoa H1):
```html
<header class="v4hero">
  <div class="wrap v4hero-wrap">
    <div class="v4hero-text">
      <h1>…</h1>
      <p>…</p>
      <div class="cta-row">
        <a class="btn btn-primary" href="#tarjous">Pyyd&auml;&nbsp;ilmainen&nbsp;tarjous</a>
      </div>
    </div>
    <div class="v4hero-media">
      <img src="/wp-content/themes/nordic-theme/assets/img/…" alt="…">
    </div>
  </div>
</header>
```
2. **Johdanto**: `<section class="intro"><div class="wrap"><p>…</p><p>…</p></div></section>`
3. **Osioita** (3–6 kpl), esim.
   - Otsikko: `<div class="section-head"><div class="eyebrow-line"></div><h2>…</h2><p>…</p></div>`
   - Korttiruudukko: `<div class="types-grid"><div class="type-card"><h3>…</h3><p>…</p><a class="card-link" href="/ikkunat/">Lue lisää &rarr;</a></div>…</div>`
     (card-link vain jos linkki; ilman linkkiä pelkkä h3+p)
   - Prosessi: `<div class="process-list"><div class="process-item"><div><h3>…</h3><p>…</p></div></div>…</div>`
   - Leipäteksti: `<section class="article-body"><div class="wrap"><h2>…</h2><p>…</p>…</div></section>`
   - Luettelo: `<ul class="bullet"><li><strong>…:</strong> …</li></ul>`
   - Taulukko: `<table class="price-table"><thead><tr><th>…</th><th>…</th></tr></thead><tbody>…</tbody></table>`
   - Nosto: `<div class="callout"><p>Otsikko</p><p>Teksti…</p></div>`
   - Taustan vaihtelu: lisää osioon `class="panel-alt"` (vaalea) tai `class="panel-dark"` (tumma) –
     vaihtele, ettei kaksi samanväristä osiota ole peräkkäin.
4. **Toiminta-alue**: `<section><div class="wrap"><div class="section-head"><div class="eyebrow-line"></div><h2>…</h2><p>…</p></div><ul class="area-list"><li>Kunta</li>…</ul></div></section>`
5. **UKK** (4–6 kysymystä, teema tekee niistä Googlen UKK-merkinnän):
```html
<section class="panel-alt">
  <div class="wrap">
    <div class="section-head"><div class="eyebrow-line"></div><h2>Usein kysytyt kysymykset</h2></div>
    <div class="faq-list">
      <div class="faq-item"><h3>Kysymys?</h3><p>Vastaus yhdessä kappaleessa.</p></div>
    </div>
  </div>
</section>
```
6. **Lopetus-CTA** (aina viimeisenä):
```html
<section class="final-cta" id="tarjous">
  <div class="wrap">
    <h2>…</h2>
    <p>…</p>
    <div class="cta-row"><a class="btn btn-primary" href="#tarjous" data-quote>Pyyd&auml;&nbsp;ilmainen&nbsp;tarjous</a></div>
  </div>
</section>
```

**Yhteystiedot-sivu**: ei final-cta-osiota. Lomake lisätään lyhytkoodilla omaan osioonsa:
`<div id="tarjous-lomake">[nordic_tarjouslomake]</div>` (teema tulostaa siihen tarjouslomakkeen).
Hero-painike yhteystiedoissa: `href="#tarjous-lomake"`.

### Kuvat (vain nämä, polku `/wp-content/themes/nordic-theme/assets/img/`)
- `hero-guide.webp` – valoisa olohuone, isot ikkunat
- `kuvat/olohuone-maisemaikkuna.webp` – olohuone, lattiasta kattoon ikkunat järvinäkymällä
- `kuvat/olohuone-puuikkunat.webp` – moderni olohuone, puupintaiset ikkunat, metsänäkymä
- `kuvat/puuikkuna-kaihtimet.webp` – puupintainen ikkuna, sälekaihtimet lasien välissä, talvi
- `kuvat/ikkuna-verhot.webp` – valkoinen ikkuna, verhot, kukkia ikkunalaudalla
- `kuvat/ulko-ovi-terassi.webp` – tumma ulko-ovi lasiaukolla, terassi, koira
- `kuvat/painike-musta.webp`, `kuvat/painike-valkoinen.webp` – ikkunan painike lähikuvassa
- `seo-ovi.jpg`, `seo-ikkuna.jpg`, `product-portrait.png` (harmaa ulko-ovi, pystykuva → käytä
  `<div class="v4hero-media portrait">`)
Vaihtele kuvia sivujen välillä. Alt-teksti kuvaa kuvan, ei hakusanoja.

## Yritystiedot (samat kaikilla sivustoilla)
- Nordic Ikkunat & Ovet Oy, Y-tunnus 3653098-6, kotipaikka Masku
- Sähköposti info@ikkunakauppias.fi, palvelemme arkisin klo 8–17
- **Puhelinnumeroa EI saa käyttää missään.**
- Valtuutettu Skaala-jälleenmyyjä. Skaala valmistaa ikkunat ja ovet mittatilaustyönä Ylihärmässä.
- Palvelut: ikkunoiden ja ulko-ovien myynti, mittaus, asennus avaimet käteen (purku, asennus,
  tiivistys, viimeistely, vanhojen ikkunoiden kierrätys), ikkunoiden ja ovien huolto, myös
  pelkät tuotteet ilman asennusta. Ilmainen mittauskäynti ja kirjallinen, sitoumukseton tarjous.
- Asiakkaat: omakotitalot, rivitalot, mökit, taloyhtiöt, rakennusliikkeet.

## Sallitut tuotefaktat (älä keksi muita lukuja)
- Ikkunat: **Aukea** (sisäänaukeava, kolmilasinen, Suomen yleisin malli), **Aarre+** (sisäänaukeava,
  nelilasinen 2+2-rakenne, matalaenergia), **Aasa** (ulosaukeava, vapaa ikkunalauta, suosittu
  muissa Pohjoismaissa), **Arvo / Arvo+** (piilosaranat, Arvo+ energiatehokkain), **Aava** (kiinteä,
  maisemaikkunat). Arvo+ ja Aarre+ -ikkunoilla voi parhaimmillaan saavuttaa jopa 70 %:n säästön
  energiankulutuksessa normitasoon verrattuna. Sälekaihtimet lasien väliin mahdollisia. Värit
  vapaasti (RAL/NCS).
- Ulko-ovet: mallistot **Kaiku** (umpiovet), **Sävel** (pystylasit), **Melodia** (neliölasit),
  **Sointu** (vaaka-, pyörö- ja puolipyörölasit), **Klassinen** (perinteinen ristiuritus,
  rintamamiestalot), **Aika** (uutuus, alumiinipinta, kestää tummatkin sävyt, 20 v pinnoitetakuu).
  10 v suorana pysymisen takuu Skaala-oville. U-arvo 0,61–1,0 W/m²K rakenteesta riippuen.
  Äänieristetty ovi jopa 38 dB (Rw+Ctr). Myös parveke-, terassi- ja lasiliukuovet.
- Hinnat (asennettuna, tyypilliset): ikkuna 400–1 500 €; omakotitalo 7–15 ikkunaa
  6 000–20 000 €; tyypillinen OKT 10 ikkunaa + 2 ulko-ovea 13 000–16 000 €; taloyhtiö 300–700 €/ikkuna;
  pelkkä asennustyö 400–550 €/ikkuna; ulko-ovi asennettuna 800–4 000 € (perusovi 1 500–2 500 €,
  laadukas puuovi 3 000–4 000 €); lukon vaihto 100–300 €, karmin korjaus 500–1 000 €.
  Sano aina, että tarkka hinta selviää ilmaisella mittauskäynnillä.
- Ikkunan käyttöikä puuikkuna 40–60 v; ulko-ovi 30–40 v. Yksi ikkuna vaihdetaan yleensä
  muutamassa tunnissa; ulko-ovi yleensä päivässä; omakotitalon ikkunat tyypillisesti 1–3 päivässä.
  Vaihto onnistuu myös talvella (aukko auki vain hetken).
- Kotitalousvähennys: asennustyön osuudesta saa kotitalousvähennystä. **Älä mainitse prosentteja,
  omavastuuta tai euromääriä** (ne muuttuvat) – ohjaa tarkistamaan Verohallinnosta.
- Ilmasto- ja aluefaktat: käytä vain yleisesti tunnettuja asioita, ei tarkkoja tilastolukuja,
  joita et ole varma. Ei keksittyjä asiakastarinoita, referenssikohteita, arvosteluja, vuosilukuja
  yrityksen historiasta, toimipisteitä tai "paikallista toimistoa" alueella.

## Kirjoitustyyli
- Suomeksi, luontevaa ja konkreettista asiantuntijan puhetta, sinuttelu. Ei mainoskliseitä
  ("huippulaatuinen", "ainutlaatuinen", "laaja valikoima tarjoaa…"), ei tekoälymäisiä fraaseja
  ("Oletko koskaan miettinyt…", "Tässä artikkelissa…", "yhteenvetona"). Lyhyet kappaleet.
  Säästeliäästi ajatusviivoja.
- **Alueellinen ja uniikki sisältö on tärkeintä.** Jokaisella sivulla pitää olla aitoa
  aluekohtaista sisältöä: ilmasto (pakkaset, tuuli, merituuli ja suola, kosteus), rakennuskanta
  (esim. rintamamiestalot, 60–80-lukujen omakotitalot, kerrostalot ja taloyhtiöt, puutalokorttelit,
  mökit), melu (liikenne, lentokenttä), suojellut alueet ja lupa-asiat, paikalliset kunnat ja
  kaupunginosat. Mainitse alueen kunnat/kaupunginosat luontevasti, ei listana tekstissä.
- Älä kopioi pääsivuston (`_malli/`) lauseita. Mallit ovat rakenteen ja faktojen lähde.
  Kirjoita jokainen sivu alusta.
- Pituus: etusivu ja palvelusivut noin 700–1 100 sanaa näkyvää tekstiä, yhteystiedot 350–600.
- Jokaisella sivulla eri näkökulma: ei toisteta samaa kappaletta sivulta toiselle.
- Sivukohtaiset painotukset:
  - **etusivu**: kuka olemme alueella, mitä teemme (kortit kaikille palvelusivuille, myös
    energialaskurille), miksi Skaala, miten projekti etenee, alueen erityispiirteet, UKK.
  - **ikkunat**: mallit (Aukea, Aarre+, Aasa, Arvo/Arvo+, Aava), lasitus, värit, mikä malli
    sopii alueen taloihin ja ilmastoon.
  - **ulko-ovet**: mallistot, turvallisuus, lämmöneristys, äänieristys, valinta talotyypin mukaan.
  - **ikkunaremontit**: koko remontti alusta loppuun, hinnat, kotitalousvähennys, taloyhtiöt,
    ajoitus, alueen tyypilliset remonttikohteet.
  - **oviremontit**: ulko-, parveke- ja terassiovien vaihto, merkit vaihdon tarpeesta, hinnat,
    taloyhtiöiden porraskäytävä-/kerrostaso-ovet (äänieristetty ovi).
  - **ikkuna-asennus**: asennuksen vaiheet tarkasti, valmistautuminen, talviasennus,
    tiivistys ja liittymät, purkujätteet, myös pelkkä asennus/pelkät tuotteet.
  - **uudet-ikkunat-vai-huolto**: päätösopas: milloin huolto riittää (tiivisteet, säätö,
    maalaus), milloin vaihto kannattaa, vertailutaulukko, kuntotarkastuslista.
  - **yhteystiedot**: yritystiedot (ilman puhelinta), toiminta-alue, miten yhteydenotto
    etenee, lomake, mitä tietoja kannattaa kertoa tarjouspyynnössä, lyhyt UKK.

## Tarkistus ennen valmista
- 8 HTML-tiedostoa + sivut.json, kaikki validia HTML:ää (tagit suljettu).
- Jokaisessa yksi H1, final-cta (paitsi yhteystiedot), UKK 4–6 kysymystä, area-list.
- Ei puhelinnumeroa, ei linkkejä muualle kuin yllä listattuihin sivuihin
  (poikkeus: mailto:info@ikkunakauppias.fi).
- Ei prosentteja/euroja kotitalousvähennyksestä.
