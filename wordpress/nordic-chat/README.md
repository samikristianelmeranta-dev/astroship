# Nordic Chat – tekoälyavustaja

Kevyt chat ikkunakauppias.fi-sivustolle. Asiakas voi kysyä palveluista,
tuotteista, remontin kulusta ja hinnoista, ja avustaja vastaa sivuston
sisällön pohjalta. Kun asiakas haluaa tarjouksen tai yhteydenoton, avustaja
kerää tiedot, näyttää yhteenvedon ja lähettää sen asiakkaan luvalla osoitteeseen
**info@ikkunakauppias.fi**.

Malli on Anthropicin **Claude Opus 5.5**.

## Miten se toimii

- **Tietopohja:** avustaja saa yrityksen perustiedot ja hakemiston sivuston
  kaikista sivuista. Tarkemmat tiedot (mallit, tekniset arvot, hinnat, oppaat)
  se hakee työkalulla suoraan sivuilta ennen vastaamista. Kun muokkaat sivuja,
  chat käyttää automaattisesti uusia tietoja. Erillistä opettamista ei tarvita.
- **Rajat:** avustaja kertoo vain sivuston tietoja. Se ei arvaa hintoja tai
  teknisiä arvoja, ei lupaa aikoja eikä vastaa aiheen ulkopuolisiin kysymyksiin.
- **Yhteydenotto:** viesti tulee sähköpostiin. Siinä on asiakkaan tiedot,
  tekoälyn tiivistelmä ja asiakkaan omat chat-viestit sanasta sanaan, ja
  *Vastaa*-painike vastaa suoraan asiakkaalle. Jos Nordic CRM on käytössä,
  tarjous- ja mittauspyynnöt menevät myös asiakasrekisteriin, ja asiakas saa
  vahvistusviestin. Markkinointilupaa chatissa ei kerätä.
- **Keskustelu säilyy**, kun asiakas siirtyy sivulta toiselle.
- **Teeman painikkeet:** linkki `href="#chat"` tai attribuutti
  `data-open-chat` avaa chatin.

## Käyttöönotto

1. **API-avain:** luo avain osoitteessa console.anthropic.com. Aseta samalla
   *Limits*-kohdasta kuukausittainen kulukatto, esim. 30 $.
2. **Lisäosa:** Lisäosat → Lisää uusi → Lataa lisäosa →
   `nordic-chat-1.0.0.zip` → Aktivoi.
3. **Avain WordPressiin**, mieluiten `wp-config.php`:hen:
   ```php
   define( 'NORDIC_CHAT_ANTHROPIC_KEY', 'sk-ant-...' );
   ```
   Vaihtoehtoisesti Asetukset → Nordic Chat → API-avain.
4. **Testaa:** avaa sivusto, kysy esim. "Paljonko ikkunaremontti maksaa?" ja
   pyydä sen jälkeen tarjous omalla sähköpostiosoitteellasi.
5. **Tietosuojaseloste:** lisää chat-osio (ks. `TIETOSUOJA-CHAT.md`).

Vaatimukset: PHP 8.1+, PHP:n cURL-laajennus (on lähes kaikissa
webhotelleissa) ja WordPress 6.5+.

## Kustannukset (arvio)

Keskustelu maksaa tyypillisesti noin **0,05–0,20 €**. Hinta riippuu siitä,
montako viestiä keskustelussa on ja kuinka monta sivua avustaja hakee. Toistuva
osa (ohjeet ja sivuhakemisto) on välimuistissa, mikä pudottaa jatkoviestien
hinnan murto-osaan.

| Keskusteluja kuukaudessa | Arvioitu kulu |
|---|---|
| 100 | 5–20 € |
| 500 | 25–100 € |

Kulut näkyvät Anthropicin konsolissa. Varmuuden vuoksi lisäosassa on rajat
(Asetukset → Nordic Chat):
- **400 viestiä vuorokaudessa** koko sivustolla
- **30 viestiä tunnissa** samasta IP-osoitteesta
- **40 viestiä** yhdessä keskustelussa

Kun raja täyttyy, chat ohjaa asiakkaan sähköpostiin.

## Turvallisuus

- API-avain on vain palvelimella eikä koskaan päädy selaimeen.
- Keskusteluhistoria säilytetään palvelimella (6 tuntia), ja selain lähettää
  vain uuden viestin. Asiakas ei siis voi muokata avustajan aiempia vastauksia.
- Rajapinta hyväksyy pyynnöt vain omalta sivustolta, ja viestin pituus on
  rajattu.
- Avustaja voi lähettää sähköpostia vain yrityksen omaan osoitteeseen, ja
  yhdestä keskustelusta enintään kaksi viestiä.
- Virheet kirjataan PHP:n virhelokiin, eikä niitä näytetä kävijälle.

## Tekninen rakenne

| Tiedosto | Sisältö |
|---|---|
| `includes/knowledge.php` | Järjestelmäkehote, sivuhakemisto ja sivuston haku |
| `includes/tools.php` | Työkalut `hae_sivustolta` ja `laheta_viesti_yritykselle` |
| `includes/conversation.php` | Keskustelu Clauden kanssa, työkalukierrokset ja tallennus |
| `includes/rest.php` | REST-rajapinta `POST /wp-json/nordic-chat/v1/viesti`, rajat ja virheet |
| `includes/widget.php`, `assets/` | Chat-ikkuna |
| `build.sh` | Asennuspaketin rakentaminen (`composer install` + siivous + zip) |

API-pyyntöjen asetukset (`conversation.php`):
- `claude-opus-5-5` ja `effort: low`, koska asiakaspalvelukeskustelu ei
  hyödy syvemmästä päättelystä ja matala taso on nopeampi ja edullisempi
- välimuisti päällä
- `fallbacks: "default"`: jos Clauden turvallisuusluokittelija hylkää
  pyynnön, API ajaa sen automaattisesti varamallilla
- keskusteluhistoriaan vain lisätään viestejä, mitä Claude Opus 5.5:n
  ajattelulohkot edellyttävät

Mallin voi vaihtaa suodattimella `nordic_chat_model`, ja pikakysymyksiä voi
muuttaa suodattimella `nordic_chat_quick_questions`.
