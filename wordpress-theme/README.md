# TiliHub – WordPress-teema

Lohkoteema (Full Site Editing) osoitteeseen www.tilihub.fi. Sisältö, osoitteet, AIOSEO-asetukset ja
WPForms-lomakkeet pysyvät WordPressissä ennallaan. Teema vaihtaa vain ulkoasun.

## Asennus

1. **Ota varmuuskopio** sivustosta (esim. UpdraftPlus tai palveluntarjoajan varmuuskopio).
2. Valitse *Ulkoasu → Teemat → Lisää uusi teema → Lataa teema* ja valitse `tilihub.zip`.
3. Valitse ensin **Esikatselu (Live Preview)**, ja kun kaikki näyttää hyvältä, valitse **Ota käyttöön**.
4. Luo uusi sivu nimeltä **Artikkelit**, jonka osoite (slug) on `blogi`. Jätä sivu tyhjäksi.
   Teema näyttää siinä kaikki artikkelit (osoite `/blogi/`), ja valikon "Artikkelit"-linkki toimii.
5. Tyhjennä välimuisti, jos käytössä on välimuistilisäosa tai palvelimen välimuisti.

Vanhan Gutentools-teeman muokatut sivupohjat jäävät tallennetuiksi tietokantaan. Teeman voi siis
vaihtaa takaisin milloin tahansa.

## Mistä sivujen sisältö tulee

| Osoite | Sivupohja | Muokkaus |
| --- | --- | --- |
| `/` | `front-page.html` | Ulkoasu → Editori → Sivupohjat → Etusivu |
| `/kirjanpitopalvelut/` | `page-kirjanpitopalvelut.html` | Ulkoasu → Editori → Sivupohjat |
| `/palkanlaskenta/` | `page-palkanlaskenta.html` | 〃 |
| `/tilinpaatos/` | `page-tilinpaatos.html` | 〃 |
| `/kirjanpidon-hinnat/` | `page-kirjanpidon-hinnat.html` | 〃 |
| `/tietoa-meista/` | `page-tietoa-meista.html` | 〃 |
| `/yhteystiedot/` | `page-yhteystiedot.html` (WPForms-lomake 1283) | 〃 |
| `/blogi/` | `page-blogi.html` | Artikkelilista |
| Muut sivut | `page.html` | Sivun oma sisältö sivueditorissa |
| Artikkelit | `single.html` | Artikkelin oma sisältö |
| `/category/…/`, `/tag/…/` | `archive.html` | – |

Palvelusivujen sisältö on siis sivupohjassa, kuten vanhassa teemassa. Sivupohjaan tehdyt muutokset
tallentuvat tietokantaan. Alkuperäisen version saa takaisin valitsemalla sivupohjan kohdalla
*Palauta*.

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
- Palvelusivujen sivupohjat luodaan WordPress-viennistä:
  `node scripts/wp-import.mjs vienti.xml && node wordpress-theme/tools/build-page-templates.mjs`
- Zip-paketti: `cd wordpress-theme && zip -r tilihub.zip tilihub`
