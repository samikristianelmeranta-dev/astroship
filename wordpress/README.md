# Nordic Ikkunat & Ovet – WordPress-teema v2

Uudistettu teema sivustolle ikkunakauppias.fi. Sivujen tekstit ja sisältö ovat
ennallaan: kaikkien 95 sivun tekstisisältö on tarkistettu koneellisesti
identtiseksi alkuperäisen viennin (`nordic-sivut_26.xml`) kanssa. Uudistus
tehdään kokonaan teemassa, joten **sivuja ei tarvitse tuoda uudelleen**.

- `nordic-theme/`: teeman lähdekoodi
- Ladattava paketti: `cd wordpress && zip -r nordic-theme-v2.0.0.zip nordic-theme`
  (zip-tiedostoa ei ole versionhallinnassa)

## Käyttöönotto

1. Ota varmuuskopio (tiedostot ja tietokanta).
2. WordPress → Ulkoasu → Teemat → Lisää uusi → Lataa teema → valitse
   `nordic-theme-v2.0.0.zip` → **Korvaa nykyinen ladatulla**. Kansion nimi on
   sama (`nordic-theme`), joten sisällön kuvapolut, valikot ja asetukset säilyvät.
3. Tyhjennä välimuistit (välimuistilisäosa, CDN, palvelin).
4. Tarkista nämä:
   - etusivu ja yksi alasivu mobiilissa ja tietokoneella
   - tarjouslomake: "Pyydä ilmainen tarjous" avaa lomakkeen modaalina sivuilla,
     joilla lomaketta ei ole sisällössä
   - `https://ikkunakauppias.fi/llms.txt` ja `https://ikkunakauppias.fi/robots.txt`
   - Googlen [Rich Results -testi](https://search.google.com/test/rich-results)
     yhdelle opas- ja yhdelle paikkakuntasivulle

Paluu vanhaan: aiempi teemaversio takaisin, tai vanhat sivukohtaiset tyylit
päälle lisäämällä `wp-config.php`:hen `define( 'NORDIC_LEGACY_PAGE_CSS', true );`.

## Mitä muuttui

### Ulkoasu ja konversio
- Yksi yhtenäinen design-järjestelmä (`style.css`) korvaa sivuihin tallennetut
  kahdeksan erillistä tyylitiedostoa (`_nordic_page_css`).
- Kultainen ensisijainen CTA kaikkialla. Otsikkopalkissa on aina näkyvä
  "Pyydä ilmainen tarjous" -painike, ja mobiilissa kiinteä CTA ilmestyy heron
  jälkeen.
- **Tarjouslomake modaalina** (Fluent Forms, lomake 3). Aiemmin ali-sivujen
  loppu-CTA (`href="#"`) ei vienyt mihinkään. Etusivulla ja yhteystiedoissa
  painike vierittää sivun omaan lomakkeeseen.
- Heron alla luottamuspalkki: valtuutettu Skaala-jälleenmyyjä, ilmainen
  mittauskäynti, sitoumukseton tarjous, valmistettu Suomessa.
- Opassivuille "Lue myös" -nostot (3 seuraavaa opasta), jotka parantavat
  sisäistä linkitystä.
- Näkyvä murupolku, uusi alatunniste (palvelut, alueet, yhteystiedot) ja
  mainoslaskeutumissivu ilman päävalikkoa (`etusivu-ilman-navigaatiota`).

### Animaatiot
- Heron sisääntulo (teksti, kuvan paljastus ja zoom) sekä kevyt parallax
  tukevissa selaimissa.
- Sisältö ilmestyy porrastetusti vierittäessä (IntersectionObserver).
- Numerolaskuri (10+), animoidut UKK-haitarit, korttien nostot, painikkeiden
  valoliuku, lukemisen edistymispalkki opassivuilla, laskurin tuloksen
  "pomppu" ja kultaisen CTA:n hehku.
- Kaikki animaatiot kunnioittavat käyttäjän *vähennä liikettä* -asetusta.
  Sisältö näkyy myös ilman JavaScriptiä.

### Tekninen SEO
- Yksi JSON-LD `@graph`: `HomeAndConstructionBusiness` (palveluluettelo,
  palvelualueen kaupungit, aukioloajat, Skaala-brändi), `WebSite`, `WebPage`,
  hierarkkinen `BreadcrumbList`, `Article` oppaille ja `Service` palvelu-,
  tuote- ja paikkakuntasivuille (`areaServed` = kaupunki). Sivujen omat
  FAQPage-skeemat säilyvät.
- Open Graph -kuva mittoineen, `twitter:card`, artikkelien päivämäärät.
- Robots `max-image-preview:large` ja `max-snippet:-1`.
- Kopio-etusivun canonical osoittaa etusivulle, ja sivu on poistettu
  XML-sivukartasta.
- Sivulla on enää yksi `<main>` (sisällön sisäkkäinen `<main>` muutetaan
  tulostettaessa) ja ohituslinkki "Siirry sisältöön".

### Nopeus (Core Web Vitals)
- Fontit (Inter, Space Grotesk) ladataan teemasta eikä Google Fontsista.
  Tämä on nopeampaa ja GDPR-ystävällisempää.
- Kuvat WebP-muodossa ja srcsetillä (esim. seo-ovi 174 kt → 31–97 kt),
  mitat merkitty (ei asettelun hyppimistä), laiska lataus ja hero-kuvan
  esilataus `fetchpriority="high"`.
- Emoji-skriptit ja muu turha pois `<head>`-osiosta. JavaScript on yksi
  riippuvuudeton, `defer`-ladattu tiedosto (~10 kt).

### AI-haku (ChatGPT, Claude, Perplexity, Googlen AI-yhteenvedot)
- `/llms.txt` generoidaan automaattisesti julkaistuista sivuista
  (llmstxt.org-muoto): yrityksen perustiedot, toiminta-alue sekä kaikki sivut
  ryhmiteltyinä kuvauksineen.
- `robots.txt`:ssä on sivukartta ja viittaus llms.txt:hen. Kaikki robotit,
  myös AI-haut, saavat lukea sivuston.
- Rakenteinen data kertoo koneille yksiselitteisesti, kuka palvelee, missä ja
  mitä.

## Täydennä, kun tiedot ovat saatavilla
Lisää `functions.php` → `nordic_business_info()` -kohtaan puhelinnumero ja
katuosoite. Ne tulostuvat automaattisesti skeemaan, alatunnisteeseen ja
llms.txt:hen. Paikallisen haun (Google Maps) kannalta tämä on tärkein
yksittäinen puuttuva tieto.
