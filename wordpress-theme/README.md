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
