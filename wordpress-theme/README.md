# TiliHub – WordPress-teema

Lohkoteema (Full Site Editing) osoitteeseen www.tilihub.fi. Sisältö, osoitteet, AIOSEO-asetukset ja
WPForms-lomakkeet pysyvät WordPressissä ennallaan. Teema vaihtaa vain ulkoasun.

## Asennus

1. **Ota varmuuskopio** sivustosta (esim. UpdraftPlus tai palveluntarjoajan varmuuskopio).
2. Valitse *Ulkoasu → Teemat → Lisää uusi teema → Lataa teema* ja valitse `tilihub.zip`.
3. Valitse ensin **Esikatselu (Live Preview)**, ja kun kaikki näyttää hyvältä, valitse **Ota käyttöön**.
4. Asenna ja aja sisältöpäivitys (ks. alla).
5. Luo uusi sivu nimeltä **Artikkelit**, jonka osoite (slug) on `blogi`. Jätä sivu tyhjäksi.
   Teema näyttää siinä kaikki artikkelit (osoite `/blogi/`), ja valikon "Artikkelit"-linkki toimii.
6. Tyhjennä välimuisti, jos käytössä on välimuistilisäosa tai palvelimen välimuisti.

Vanhan Gutentools-teeman muokatut sivupohjat jäävät tallennetuiksi tietokantaan. Teeman voi siis
vaihtaa takaisin milloin tahansa.

## Sisältöpäivitys: palvelusivut sivuiksi ja hintakorjaukset

Asenna teeman jälkeen lisäosa `tilihub-sisaltopaivitys.zip` (*Lisäosat → Lisää uusi → Lataa lisäosa*)
ja valitse *Työkalut → TiliHub-sisältöpäivitys*. Sivu näyttää ensin, mitä muuttuu. Muutokset tehdään
vasta, kun painat *Suorita päivitys*:

- Hoiva-alan sivu ja 8 päällekkäistä artikkelia siirretään roskakoriin, ja niiden osoitteet ohjataan pysyvästi (301)
  korvaavalle sivulle (lista: `tools/redirects.json`).
- Artikkelien rikkinäiset sisäiset linkit (43 kpl) korjataan.
- Etusivun, kirjanpidon, palkanlaskennan, tilinpäätöksen, hinnaston, Tietoa meistä -sivun ja
  yhteystietojen sisältö siirretään sivujen omaksi sisällöksi. Niiden sivupohjaksi tulee
  *Laskeutumissivu*. Sen jälkeen sivuja muokataan tavallisessa sivueditorissa.
- 27 artikkelista poistetaan Premium-paketti ja hinnat korjataan nykyisen hinnaston mukaisiksi.
  Myös rakenteinen data ja AIOSEO-kuvaukset korjataan. Kaikki muutokset on lueteltu tiedostossa
  `wordpress-plugin/tilihub-sisaltopaivitys/MUUTOKSET.md`.

Jokaisesta muutoksesta jää versio, joten vanhan sisällön voi palauttaa. Artikkelit, joita on
muokattu viennin (10.10.2026) jälkeen, ohitetaan, ellet erikseen valitse niiden päivittämistä.
Kun päivitys on tehty, lisäosan voi poistaa.

| Osoite | Sisältö |
| --- | --- |
| Palvelusivut, etusivu | Sivun oma sisältö, sivupohja *Laskeutumissivu* |
| `/blogi/` | Sivupohja `page-blogi.html` (artikkelilista) |
| Muut sivut | `page.html`: tumma otsake ja sivun sisältö |
| Artikkelit | `single.html` |
| `/category/…/`, `/tag/…/` | `archive.html` |

Teemassa on varalla sivupohjat `page-<osoite>.html`. Niiden ansiosta palvelusivut näkyvät oikein
myös ennen kuin päivitys on tehty. Päivityksen jälkeen niitä ei käytetä.

## Hakukone- ja tekoälyoptimointi

Teema hoitaa nämä automaattisesti (`inc/`):

| Mitä | Missä |
| --- | --- |
| 301-uudelleenohjaukset poistetuille sivuille, yhdistetyille artikkeleille ja vanhoille rikkinäisille osoitteille | `redirects.php`, lista `redirects-map.php` |
| Rakenteinen data: tilitoimisto (AccountingService) yhteystietoineen, palvelut ja hinnat, palvelusivuille Service ja usein kysyttyjen kysymysten rakenne (FAQPage), joka luetaan sivun omasta Usein kysyttyä- tai Q&A-osiosta | `schema.php`, tiedot `business.php` |
| `/llms.txt`: tiivis kuvaus tekoälyhauille, jossa ovat yritys, hinnat, palvelut ja kaikki artikkelit (päivittyy itsestään) | `llms.php` |
| robots.txt: tekoälyhakujen indeksoijat sallitaan (GPTBot, ClaudeBot, PerplexityBot ym.), ja llms.txt mainitaan | `seo.php` |
| Tagisivut, Uncategorized-kategoria ja haku piilotetaan hakukoneilta (noindex, follow) | `seo.php` |
| Englanninkielisille artikkeleille merkitään kieli `lang="en"` | `seo.php` |

Yrityksen tiedot (hinnat, yhteystiedot, toimialue) muutetaan yhdestä paikasta: `inc/business.php`.

### Tarkista AIOSEO:sta käyttöönoton jälkeen

1. *Hakunäkyvyys → Taksonomiat → Avainsanat (Tags)*: **Näytä hakutuloksissa: ei**.
2. *Hakunäkyvyys → Sisältötyypit*: sivuilla ja artikkeleilla rakenteisen datan tyyppi WebPage / Article. Teema lisää tilitoimiston tiedot erikseen.
3. *Työkalut → Robots.txt*: varmista, ettei tekoälyhakuja estetä (esim. `Disallow` GPTBotille).
4. *Sivukartat*: luo sivukartta uudelleen, jotta poistetut artikkelit poistuvat siitä.
5. Jos AIOSEO:n oma llms.txt-toiminto on päällä, jätä se pois päältä tai poista teeman versio suodattimella `tilihub_llms_txt`.
6. Google Search Console: lähetä sivukartta uudelleen ja tarkista *Rich results test* -työkalulla etusivu ja kirjanpitosivu.

## Chat ja WhatsApp

Sivuston oikeassa alakulmassa on kevyt chat (`inc/chat.php`, `assets/js/chat.js`, `assets/css/chat.css`):

- Chat vastaa sivuston omasta sisällöstä: hinnastosta, yhteystiedoista, palvelusivujen usein kysytyistä kysymyksistä sekä sivujen ja artikkelien teksteistä. Vastauksen alla on linkki lähdesivulle ja kaksi muuta aiheeseen liittyvää sivua.
- Jokaisen vastauksen alla on painike **Kysy tarkemmin WhatsAppissa**. Se avaa WhatsAppin numeroon 044 080 3344, ja kävijän kysymys on valmiina viestissä ("Hei! Kysyin sivuillanne: …").
- Chat ei käytä ulkopuolisia palveluita, tekoälyrajapintoja eikä evästeitä, eikä kysymyksiä tallenneta. Väärinkäytön estämiseksi yhdestä IP-osoitteesta voi lähettää enintään 40 kysymystä minuutissa.
- Hakemisto päivittyy itsestään, kun sivuja tai artikkeleita muokataan.
- Valmiit kysymykset, tervehdys ja tilateksti muutetaan tiedostossa `inc/chat.php` (kohta `wp_localize_script`).
- Mikä tahansa linkki, jonka osoite on `#chat`, avaa chatin, esimerkiksi "Kysy chatissa".
- Chatin saa pois päältä: `add_filter( 'tilihub_chat_enabled', '__return_false' );`

## Vanhojen artikkelien ulkoasu

Monessa artikkelissa on Oma HTML -lohkona kokonainen HTML-sivu omine tyyleineen. Ne piilottivat
teeman otsikoita ja vaihtoivat fontteja. Teema siistii tällaisen sisällön **vain näytettäessä**
(`inc/legacy-content.php`): se poistaa upotetut `<style>`-tyylit ja inline-tyylit sekä artikkelin
oman otsakkeen, joka toistaisi otsikon. Teema antaa vanhoille luokille, kuten `.cta-box`,
`.quick-answer`, `.toc` ja `.checklist`, uuden ilmeen. Tietokannan sisältöön ei kosketa.

Siistimisen voi kytkeä pois päältä:

```php
add_filter( 'tilihub_clean_legacy_content', '__return_false' );
```

## Kehitys

- Värit, fontit ja välistykset: `theme.json`. Fontit ladataan teeman omista tiedostoista, ei Googlelta.
- Komponenttien tyylit: `assets/css/theme.css`
- Palvelusivujen sisältö ja hintakorjaukset luodaan WordPress-viennistä:
  `node scripts/wp-import.mjs vienti.xml && node wordpress-theme/tools/build-page-templates.mjs && node wordpress-theme/tools/build-content-update.mjs vienti.xml`
- Zip-paketit: `cd wordpress-theme && zip -r tilihub.zip tilihub` ja `cd wordpress-plugin && zip -r tilihub-sisaltopaivitys.zip tilihub-sisaltopaivitys`
