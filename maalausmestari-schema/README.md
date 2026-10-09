# Maalausmestari – schema-paketti (JSON-LD)

Valmiit upotettavat `<script type="application/ld+json">`-koodit jokaiselle sitemapin 81 sivulle
(RSS/Atom-syötteet eivät tarvitse schemaa). Hakemisto sivu → tiedosto: `koodit/HAKEMISTO.txt`.

| Kansio | Sivut | Schemat |
|---|---|---|
| `koodit/00-koko-sivusto.html` | kaikki sivut | `HousePainter` (yritys) + Turun ja Helsingin toimipisteet + `WebSite` |
| `koodit/1-paasivut/` (13) | etusivu, palvelut, galleria, uutiset, toimipisteet, tarjouspyyntö, laskuri, tietosuoja | `WebPage`/`CollectionPage`/`ContactPage`/`ImageGallery` + `BreadcrumbList` + palvelusivuilla `Service` (takuu `Offer.warranty`) |
| `koodit/2-alueet/` (7) | maakunta- ja kaupunkisivut | `WebPage` + `BreadcrumbList` + `Service` (areaServed = maakunta) |
| `koodit/3-paikkakunnat/` (33) | `/talonmaalaus/<paikkakunta>` | `WebPage` + `BreadcrumbList` + `Service` (areaServed = kunta → maakunta), 3 v takuu |
| `koodit/4-artikkelit/` (28) | blogi/uutisartikkelit | `WebPage` + `BreadcrumbList` + `BlogPosting` |

## Asennus Dudaan

1. **Tarkista ensin, mitä Duda jo tuottaa.** Avaa julkaistu sivu → *Näytä sivun lähdekoodi* → hae `ld+json`.
   - Jos Duda tuottaa jo `LocalBusiness`-scheman (Business Info / Structured data -asetus), poista se
     käytöstä **tai** jätä `00-koko-sivusto.html` asentamatta – kahta eri yritys-schemaa ei kannata olla.
   - Duda-blogi voi tuottaa artikkeleille oman `BlogPosting`-scheman. Jos tuottaa, asenna artikkeleille
     vain `WebPage`- ja `BreadcrumbList`-osat (poista `BlogPosting`-lohko).
2. **Koko sivusto:** `00-koko-sivusto.html` → *Site Settings → Head HTML* (kerran, näkyy kaikilla sivuilla).
   Sivukohtaiset koodit viittaavat siihen `@id`:llä, joten tämä on asennettava ensimmäisenä.
3. **Sivukohtaiset:** kunkin sivun tiedoston sisältö → sivun *Page Settings → SEO / Advanced → Head HTML*.
   Jos blogipostauksessa ei ole Head HTML -kenttää, lisää koodi postauksen loppuun HTML-widgettiin
   (JSON-LD toimii myös bodyssa).
4. **Testaa** muutama sivu: <https://search.google.com/test/rich-results> ja <https://validator.schema.org/>.

Etusivun FAQ-osiolle **ei** ole erillistä koodia: Dudan accordion-widgetissä on `schema: true`, joten
`FAQPage` syntyy jo automaattisesti. Tuplana se aiheuttaisi virheen Search Consolessa.

## Tarkistettavat tiedot ennen julkaisua

Schemat perustuvat sitemapiin ja etusivun HTML:ään. Muiden sivujen sisältöä en nähnyt, joten:

- **Artikkelien otsikot** on muodostettu URL:sta. `headline` ja sivun `name` kannattaa tarkistaa
  vastaamaan sivun H1:tä (ääkköset, välimerkit).
- **Artikkelien päivämäärät:** `dateModified` on sitemapin lastmod. Lisää `datePublished` ja
  `image` (artikkelin pääkuva), jos ne ovat tiedossa – Google suosittelee molempia.
- **Puuttuvat yritystiedot** – lisää `generate.py`:n `ORG`-osioon ja aja `python3 generate.py`:
  - `logo` (logon kuva-URL) – tärkein puute, Google käyttää sitä hakutuloksissa
  - `legalName` + `vatID`/`taxID` (Y-tunnus). Avainlippu-linkin perusteella virallinen nimi voi olla
    *Suomen Maalausmestarit Oy* – vahvista ennen lisäämistä
  - toimipisteiden katuosoitteet + postinumerot (`/varsinais-suomi`- ja `/uusimaab84c772c`-sivuilta)
  - `openingHoursSpecification`, `foundingDate`, Facebook/LinkedIn `sameAs`-listaan
- **Arvostelut:** Trustmary-widget voi tuottaa oman review-scheman. `AggregateRating`ia ei lisätty
  käsin, koska lukuja ei ole sivun HTML:ssä.

## Muut löydetyt optimoinnit (etusivun HTML)

**Otsikot ja rakenne**
1. Etusivulla on **kaksi H1-elementtiä** ("Maalausliike" + "Turku & Helsinki") → yhdistä yhdeksi:
   `<h1>Maalausliike Turku & Helsinki</h1>`.
2. **Tabletilla ei ole H1:tä lainkaan** – hero on tehty kahtena eri rivinä (`hide-for-medium` /
   `hide-for-small hide-for-large`), ja tablettiversiossa otsikko on `<p>`. Sama koskee palvelukortteja:
   sisältö on DOMissa kahteen kertaan. Tee yksi responsiivinen osio.
3. Osioiden otsikot ovat `<p>`-tageja: "Maalausliikkeen palvelut", "Katso mitä asiakkaat kertovat
   meistä", "Laske tästä hinta-arvio…", "Tutustu meihin…", "Uutiset", "Instagram" sekä palvelukorttien
   "Talon maalaus / Tiilikaton pinnoitus / Peltikaton maalaus" → muuta H2/H3:ksi.
4. H2 "Maalausliike Turussa & **h**elsingissä" → iso H (näkyy versaalina, mutta Google lukee tekstin).

**Kuvat**
5. Tyhjä `alt=""`: `rappauksen+maalaus.jpeg`, kaksi `Näyttökuva 2024-11-17 …png` -kuvaa ja
   `talon+maalauksen+ammattilainen.webp` (Juhanan kuva). Lisää kuvaavat alt-tekstit.
6. "Näyttökuva 2024-11-17 kello 2.59.19.png" -tiedostot: nimeä kuvaavasti (esim. `tiilikaton-pinnoitus.webp`)
   ja muunna WebP:ksi – PNG-kuvakaappaukset ovat raskaita.
7. Peltikaton maalaus -kortin (tablettinäkymä) kuva linkittää **etusivulle `/`** → pitäisi olla `/peltikatonmaalaus`.

**Linkit**
8. Puhelinlinkki `tel:+35850 366 7703` sisältää välilyöntejä → `tel:+358503667703`.
9. Piilotettu rivi (`hide-for-small hide-for-medium hide-for-large`) linkittää sivuille `/shop` ja `/kauppa`,
   joita ei ole sitemapissa → poista koko rivi.
10. "Tutustu meihin" -accordionissa linkki `/ajankohtaista`, jota ei ole sitemapissa → vaihda `/uutiset`.
11. Sisäiset ankkurit "Turussa" → `/varsinais-suomi` ja "Helsingissä" → `/uusimaab84c772c`: kohdesivut
    `/turku` ja `/helsinki` ovat olemassa – päätä kumpi on pääsivu, ettei kaksi sivua kilpaile samasta hakusanasta.

**URL-rakenne (sitemapista)**
12. `/uusimaab84c772c` → nimeä `/uusimaa` + 301-uudelleenohjaus.
13. `/talonmaalaus/copy-of-tiilikaton-pinnoitus-uusimaa` on kopiosivu väärän polun alla → siirrä
    esim. `/tiilikaton-pinnoitus/uusimaa` + 301, tai poista ja ohjaa `/tiilikaton-pinnoitusjamaalaus`iin.
14. Päällekkäiset aiheet: `/tiilikaton-pinnoitusjamaalaus` vs. `/tiilikaton-pinnoitus-ja-maalaus` ja
    `/ulkomaalaus` vs. `/talonulkomaalaus` → yhdistä tai aseta canonical pääsivuun.
15. `/hyvaa-alkanutta-vuotta-2020` ja `/tiedote` ovat ohutta sisältöä → harkitse `noindex` tai poistoa.

**Lomake ja tekstit**
16. Lomakkeen `locale="ENGLISH"`, piilotettu otsikko "Contact Us" ja virheviesti "Oops, there was an error…"
    → vaihda suomeksi. Kenttien labelit ovat piilossa (vain placeholder) → saavutettavuus.
17. Kirjoitusvirheet: "takavaat" → "takaavat", FAQ:ssa "kotistalousvähennyksen" → "kotitalousvähennyksen".

**Suorituskyky**
18. Herossa taustavideo kahdesti (molemmat hero-versiot) + Trustmary-, Leadoo- ja Instagram-skriptit.
    Lataa kolmannen osapuolen skriptit viiveellä ja käytä videolle poster-kuvaa (LCP).

**Head-osio (ei näkynyt liitetyssä HTML:ssä)** – tarkista sivukohtaisesti: uniikki `<title>` (50–60 merkkiä),
meta description (140–160), canonical, Open Graph -kuva ja `kiitos-otayhteyttä`-sivulle `noindex`.

## Päivittäminen

Kaikki data on `generate.py`:ssä. Muokkaa ja aja `python3 generate.py` → `koodit/` luodaan uudelleen.
