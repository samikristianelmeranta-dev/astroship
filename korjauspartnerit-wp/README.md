# Korjauspartnerit – uusi WordPress-sivusto

Uusi WordPress-sivusto Korjauspartnereille, tehty graafisen ohjeistuksen 2024 (v1.0) mukaan. Se korvaa vanhan Avada / Fusion Builder -toteutuksen. Sisältöä muokataan klassisella editorilla (Classic Editor).

| Kansio | Sisältö |
| --- | --- |
| `wp-content/themes/korjauspartnerit/` | Teema: ulkoasu, sivupohjat, logo, fontit |
| `wp-content/plugins/korjauspartnerit-tyokalut/` | Lisäosa: henkilökunta ja henkilöhaku, palvelut, referenssit, työpaikat, yhteydenottolomake, sisältöpohjat, Classic Editor -asetukset, uudelleenohjaukset ja asennusapuri |
| `sisalto/korjauspartnerit-sisalto.xml` | Vanhan sivuston sisältö muunnettuna (WordPress-tuontitiedosto) |
| `tyokalut/muunna_sisalto.py` | Skripti, jolla muunnos tehtiin. Sen voi ajaa uudelleen tuoreesta viennistä. |
| `OHJE-SISALLONTUOTTAJILLE.md` | Käyttöohje henkilökunnalle |

Toiminnallisuus on lisäosassa eikä teemassa. Näin henkilöt, referenssit ja muu sisältö säilyvät, vaikka teema joskus vaihtuisi.

## Asennus

Vaatimukset: WordPress 6.2 tai uudempi ja PHP 7.4 tai uudempi (suositus 8.2+).

1. Asenna puhdas WordPress ja vaihda kieleksi suomi (Asetukset → Yleiset → Sivuston kieli).
2. Kopioi kansiot `wp-content/themes/korjauspartnerit` ja `wp-content/plugins/korjauspartnerit-tyokalut` palvelimelle samoihin polkuihin.
3. Asenna ja ota käyttöön lisäosat:
   - **Korjauspartnerit – Työkalut** (tämä lisäosa). Sen on oltava käytössä ennen tuontia.
   - **Classic Editor** (wordpress.org). Lohkoeditori on jo poistettu käytöstä Työkalut-lisäosassa, mutta virallinen lisäosa kannattaa silti asentaa.
   - **WordPress Importer** (Työkalut → Tuo → WordPress → Asenna nyt).
   - Suositus: **Yoast SEO**. Vanhan sivuston SEO-otsikot ja -kuvaukset siirtyvät mukana.
   - Suositus: SMTP-lisäosa (esim. WP Mail SMTP), jotta lomakkeen sähköpostit menevät varmasti perille.
4. Ota teema käyttöön: Ulkoasu → Teemat → Korjauspartnerit.
5. Tuo sisältö: Työkalut → Tuo → WordPress → `sisalto/korjauspartnerit-sisalto.xml`.
   - Valitse kirjoittajille olemassa oleva käyttäjä tai luo uudet.
   - Rastita **"Lataa ja tuo liitetiedostot"**. Kuvat (349 kpl) haetaan osoitteesta `korjauspartnerit.fi/wp-content/uploads/`, joten vanhan sivuston pitää olla vielä verkossa tuonnin aikana.
   - Jos kuvien lataus aikakatkaistaan (iso tiedosto), aja tuonti uudelleen: jo tuodut kohteet ohitetaan. Vaihtoehtoisesti kopioi vanhan palvelimen `wp-content/uploads` sellaisenaan ja tuo ilman liitteitä.
6. Aja asennusapuri: **Korjauspartnerit → Asennusapuri → Aja**, tai komentoriviltä `wp kp asenna`. Apuri
   - asettaa etusivun ja Ajankohtaista-sivun
   - luo päävalikon ja alatunnisteen valikon
   - liittää sivupohjat (henkilökunta, yhteystiedot, palvelusivut, kampanjasivut)
   - luo sisältöpohjat, toimipisteet ja asiakasryhmät
   - asettaa kestolinkit (`/%postname%/`), aikavyöhykkeen ja päivämäärämuodon sekä sulkee kommentit.
7. Tarkista Korjauspartnerit → Asetukset: puhelin, sähköpostit, lomakkeen vastaanottaja, toimipisteet ja some-linkit.
8. Lisää etusivun yläosan kuva: Sivut → Etusivu → Artikkelikuva. Yläosan otsikon ja tekstin voi vaihtaa Etusivu-sivun kentistä tai kohdasta Ulkoasu → Mukauta → Etusivu.

Kun sivusto julkaistaan samaan osoitteeseen, vanhat osoitteet ohjautuvat automaattisesti (301), ks. *Uudelleenohjaukset*.

## Ulkoasu ja brändi

- **Logo:** vektorilogo on irrotettu suoraan graafisesta ohjeistuksesta (`assets/img/logo-vaaka.svg`, `logo-pino.svg`, `liikemerkki.svg`). Logo tulostetaan sivulle inline-SVG:nä, joten käytössä ovat vain ohjeistuksen sallimat värit: turkoosi valkoisella pohjalla ja valkoinen tummalla pohjalla tai kuvan päällä. Logon korkeus on 34–38 px (minimikoko 30 px).
- **Typografia:** Montserrat (Regular–Bold ja kursiivit) on ladattu palvelimelle eikä Google Fontsista, joten kävijän tietoja ei välity Googlelle.
- **Värit** (`assets/css/perus.css`): musta `#000000`, valkoinen `#FFFFFF` ja turkoosi `#004B59` ovat päävärit. Hiekkaa `#E5E4E3` käytetään taustoissa. Tiiltä `#FF6C42` käytetään vain pieninä aksentteina (nuolet, alleviivaukset, kolmiot), ei koskaan tekstissä, koska sen kontrasti ei riitä.
- **Kolmio-aihe:** liikemerkin kolmio toistuu painikkeiden nuolissa, listamerkeissä ja taustakuvioissa.
- **Ohjeistuksen verkkosivuesimerkki:** etusivulla on kokoruudun kuva, valkoinen logo ja valikko kuvan päällä sekä otsikko "Korjausrakentamisen kokenein kumppani".
- **Saavutettavuus:** sivuilla on "Siirry sisältöön" -linkki, näkyvä näppäimistöfokus ja aria-merkinnät valikoissa, lomakkeissa ja hauissa. Liikeanimaatiot vähenevät, jos käyttäjä on pyytänyt sitä laitteensa asetuksissa. Värikontrastit ovat WCAG AA -tasolla.
- Sivusto on mobiilioptimoitu (testattu 390 px – 1440 px), eikä se lataa lohkoeditorin tyylitiedostoja.

## Sisältötyypit ja työkalut

| Sisältötyyppi | Osoite | Kentät |
| --- | --- | --- |
| Henkilökunta (`henkilokunta`) | `/henkilokunta/nimi/` | titteli, puhelin, sähköposti, LinkedIn, johto, kuva. Taksonomiat: hakusanat (osaaminen) ja toimipiste |
| Palvelut (`palvelu`) | `/palvelut/…` | palvelualue, asiakasryhmä, ingressi, yhteyshenkilöt |
| Referenssit (`referenssi`) | `/referenssit/…` | toteutettu palvelu, tilaaja, paikkakunta, valmistunut, hanketyyppi, asiakasryhmä |
| Työpaikat (`tyopaikka`) | `/tyopaikat/…` | sijainti, työsuhde, haun päättymispäivä (ilmoitus poistuu listalta automaattisesti), hakulinkki tai -sähköposti |
| Yhteydenotot | vain hallinnassa | lomakkeella lähetetyt viestit |
| Sisältöpohjat | vain hallinnassa | muokattavat rungot uudelle sisällölle |

Henkilökunnan tunnisteet ja kenttien nimet (`tehtavanimike`, `puhelinnumero`, `sahkoposti`, `hakusanat`) ovat samat kuin vanhassa ACF-toteutuksessa. ACF-lisäosaa ei enää tarvita.

**Lyhytkoodit** lisätään editorin *Lisää lohko* -valikosta:

| Lyhytkoodi | Mitä tekee |
| --- | --- |
| `[kp_henkilohaku]` | Henkilöhaku: haku nimellä, tittelillä tai osaamisella sekä suodatus osaamisalueen ja toimipisteen mukaan. Toimii ääkkösistä riippumatta. |
| `[kp_henkilot nimet="Etunimi Sukunimi, …"]` | Valitut henkilöt korttina. Valinnat: `hakusana=""`, `toimipiste=""`, `johto="1"` |
| `[kp_palvelut asiakasryhma="taloyhtiot" ryhmittele="1"]` | Palvelulista, ryhmiteltynä palvelualueittain |
| `[kp_referenssit maara="6" suodatin="1"]` | Referenssikortit ja hanketyyppisuodatin |
| `[kp_ajankohtaiset maara="3"]` | Uusimmat artikkelit |
| `[kp_tyopaikat]` | Voimassa olevat työpaikat |
| `[kp_yhteydenotto otsikko=""]` | Yhteydenottolomake: roskapostisuojaus, tietosuojasuostumus ja tallennus hallintaan |
| `[kp_toimipisteet]` | Toimipisteiden yhteystiedot |

**Sivupohjat** (Sivun attribuutit → Sivupohja):

| Sivupohja | Käyttö |
| --- | --- |
| Oletus | Kapea luettava sisältö |
| Leveä sisältö | Paljon kortteja tai taulukoita |
| Palvelusivu (sivupalkilla) | Sivupalkissa "Kysy lisää" -laatikko ja liittyvät palvelut, lopussa tarjouspyyntölomake |
| Henkilökunta | Sivun teksti, johto ja henkilöhaku |
| Yhteystiedot | Asiakaspalvelu, laskutustiedot (`#laskutustiedot`), lomake (`#viesti`) ja toimipisteet (`#toimipisteet`) |
| Kampanja- / laskeutumissivu | Kevyt ylätunniste (logo ja puhelin) ja lomake |

## Sisällön muunnos

`tyokalut/muunna_sisalto.py` tekee vanhasta WordPress-viennistä uuden tuontitiedoston:

- Fusion Builderin lyhytkoodit (n. 4 500 kpl) poistetaan. Tilalle jää puhdas HTML, jota classic editorilla on helppo muokata. Otsikot, listat, kuvat ja painikkeet säilyvät, piilotetut (luonnos-) osiot jätetään pois.
- Avadan portfoliot jaetaan 27 palveluun ja 61 referenssiin. Referenssit saavat hanketyypit (Kiinteistöt / Teollisuus ja alatyypit) ja asiakasryhmät. Palvelujen asiakasryhmät päätellään sivujen *Taloyhtiöille*, *Liike- ja toimitiloille* ja *Teollisuudelle* linkeistä.
- Henkilökunta (32 henkilöä) siirtyy kenttineen. Puuttuvat tiedot (titteli, puhelin, kuva, toimipiste) täydennetään henkilökuntasivulta.
- Työpaikat: RSS-syötteen 3 ilmoitusta ja työpaikkasivulla mainitut 2 avointa tehtävää.
- Gravity Formsin lomake vaihtuu omaan `[kp_yhteydenotto]`-lomakkeeseen.
- Pois jätetään Avadan rakenneosat, ACF-määrittelyt, vanhat valikot (asennusapuri luo uudet) ja lokit.

Uudelleenajo tuoreesta viennistä (Työkalut → Vie → Kaikki sisältö):

```
python3 tyokalut/muunna_sisalto.py vanha-vienti.xml sisalto/korjauspartnerit-sisalto.xml
```

## Uudelleenohjaukset

Lisäosa ohjaa vanhat osoitteet uusiin (301):

- `/palvelut/{referenssi}/` → `/referenssit/{referenssi}/`. Vanhalla sivustolla referenssit olivat palveluiden alla.
- `/palvelut/yllapitopartnerit/` → `/palvelut/yllapitopartneri/`, `/teollisuuden-referenssit/` → `/hanketyyppi/teollisuus/`
- `/contact-us/` ja `/ota-yhteytta/` → `/yhteystiedot/`
- Jos osoite muuten on 404, sisältö haetaan samalla polun loppuosalla.
- Omia ohjauksia voi lisätä kohdassa Korjauspartnerit → Asetukset → Uudelleenohjaukset.

## Tarkistettavaa ennen julkaisua

- **Kuvat:** ohjeistuksen kuvat on merkitty "vain sisäiseen käyttöön". Sivustolla käytetään vanhan sivuston omia kuvia. Lisää etusivulle uusi yläosan kuva, kun kuvat on otettu.
- **Henkilökunta:** 9 henkilöllä on toimipiste (vanhan henkilökuntasivun mukaan). Muille toimipiste on lisättävä, jotta toimipistesuodatus kattaa kaikki. Vanhan sivun "Johto"-osio oli piilotettu, joten johto-merkintää ei asetettu kenellekään. Rasti "Kuuluu johtoon" näyttää henkilön henkilökuntasivulla ensimmäisenä. Osalta henkilöistä puuttuu kuva.
- **Työpaikat:** tarkista, ovatko tuodut ilmoitukset yhä ajankohtaisia, ja lisää niille haun päättymispäivä.
- **Koneturvallisuus**-palvelun sivulla on jo vanhalla sivustolla täytetekstiä (lorem ipsum).
- **Etusivun luvut** (2017 / 40+ / AAA) ovat vanhan sivuston tekstien pohjalta. Muokattavissa kohdassa Ulkoasu → Mukauta → Etusivu.
- Lomakkeen vastaanottaja on oletuksena `toimisto@korjauspartnerit.fi`.
- Tarkista tietosuojaseloste uuden lomakkeen osalta: lomakeviestit tallennetaan myös hallintaan.

## Kehitys

Rakenne on tavallinen klassinen WordPress-teema, jossa ei ole build-vaihetta. CSS ja JS ovat sellaisenaan kansiossa `assets/`. Lisäosan template-tiedostot (`templates/*.php`, esim. henkilökortti) voi ohittaa kopioimalla ne teeman kansioon `korjauspartnerit/`.
