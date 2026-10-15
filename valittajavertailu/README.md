# Välittäjävertailu – kiinteistönvälittäjien kilpailutuspalvelu

Asunnon myyjä kilpailuttaa kiinteistönvälittäjät suljetulla kilpailulla: välittäjät jättävät **hinta-arvion ja palkkiotarjouksen**, ja myyjä valitsee **enintään kaksi** välittäjää arviokäynnille. Palvelu toimii oletuksena koko Suomessa; `PILOT_REGIONS` rajaa alueet, ja muualla myyjä voi liittyä jonoon.

Rakenne on sama kuin Pumppuvertailussa (`samikristianelmeranta-dev/l-mp-pumppu`): Node.js ilman kehyksiä, JSON-tiedostot tai PostgreSQL.

Julkaisun tehtävälista: [docs/JULKAISU.md](docs/JULKAISU.md).

## Miten kilpailutus toimii

1. **Myyjä** täyttää lomakkeen kolmessa vaiheessa: asunto (tyyppi, omistusmuoto, pinta-ala, huoneet, rakennusvuosi, kunto, varustelu), osoite ja **vähintään 3 kuvaa** (enintään 15), yhteystiedot. Myyjä valitsee arvioiden määräajan (3 päivää – 2 viikkoa).
2. Kohde **julkaistaan vasta, kun myyjä avaa sähköpostiin lähetetyn linkin**. Sama linkki (`/kohde#tunnus`) on myyjän näkymä; omat kohteet löytyvät myös `/omat`-sivulta kertakäyttöisellä kirjautumislinkillä.
3. **Välittäjät**, jotka ylläpito on hyväksynyt (Y-tunnus YTJ:stä, välitysliike AVI:n välitysliikerekisteristä) ja joiden alueeseen kohde osuu, saavat ilmoituksen. He näkevät tiedot ja kuvat ilman nimeä, katuosoitetta, sähköpostia ja puhelinta.
4. Välittäjät voivat **kysyä lisätietoja ja pyytää lisää kuvia**. Myyjä vastaa tekstillä, kuvilla tai molemmilla, ja voi lisätä kuvia myös oma-aloitteisesti. Vastaukset ja kuvat näkyvät kaikille kohteen välittäjille. Yhteystietojen jakaminen estetään.
5. Välittäjä jättää **vakiomuotoisen arvion**: hinta-arvio (haarukka, velaton), pyyntöhintaehdotus, välityspalkkio (prosentti + vähimmäispalkkio tai kiinteä), lisäkulut, mitä palkkioon sisältyy, toimeksiannon kesto, "ei kauppaa, ei kuluja", myyntiaika-arvio ja perustelut. **Kukaan ei näe muiden arvioita**, eikä myyjä näe niitä ennen määräaikaa.
6. Määräajan päätyttyä myyjä näkee arviot **vertailutaulukossa**. Kulut lasketaan kaikille **samalla viitehinnalla** (arvioiden keskikohtien mediaani), jotta palkkiot ovat vertailukelpoisia, vaikka arviot eroavat. Korkein arvio ei tarkoita korkeinta kauppahintaa.
7. Myyjä valitsee **yhden tai kaksi välittäjää**. Yhteystiedot vaihdetaan valittujen kanssa, valitut näkevät, jos myös toinen valittiin (ei kuka), muut saavat tiedon ratkaisusta, ja kummallekin valitulle kirjataan **palvelumaksu** (oletus 149 €, alv 0 %).
8. 60 päivän päästä myyjältä kysytään, miten myynti sujui, ja pyydetään **arvio** (1–5 tähteä) välittäjälle, joka hoiti myynnin.

Säännöt ovat tiedostossa `src/tender.js` ja vertailuluvut `src/compare.js`. Testit (`test/server.test.js`) varmistavat, ettei mikään rajapinta vuoda muiden arvioita, yhteystietoja tai osoitetta.

## Käynnistys

```bash
npm install
ADMIN_TOKEN=vaihda SESSION_SECRET=vaihda npm start      # http://localhost:3000
npm test
```

Ilman `DATABASE_URL`-muuttujaa tiedot tallennetaan JSON-tiedostoihin `data/`-kansioon. Ilman `POSTMARK_TOKEN`-muuttujaa sähköposteja ei lähetetä: viestit näkyvät ylläpidon **Viestit**-välilehdellä, ja vahvistus- ja kirjautumislinkit näytetään suoraan selaimessa (kehitystila).

Tuotantoa vastaava ympäristö (palvelu + PostgreSQL):

```bash
cp .env.example .env    # täytä arvot
docker compose up --build
```

## Sivut

| Polku | Sisältö |
|---|---|
| `/` | Etusivu: pikalomake, esimerkki suljetuista arvioista, palkkiolaskuri, vertailu, UKK |
| `/kilpailuta` | Myyntikohteen lomake |
| `/kohde#<tunnus>` | **Myyjän näkymä**: kysymykset ja kuvapyynnöt, lisäkuvat, vertailu, valinta, arvio |
| `/omat` | Myyjän omat kohteet (kirjautuminen sähköpostilinkillä) |
| `/valittajille`, `/valittaja` | Välittäjien rekisteröityminen ja **välittäjän näkymä** (avoimet, päättyneet, profiili, palkkiopohjat) |
| `/admin` | **Ylläpito**: kohteet, välittäjät (YTJ, välitysliikerekisteri), viestit, laskutus, arviot, mittarit, jono |
| `/kiinteistonvalittaja/helsinki` jne. | Hakukonesivut 26 kaupungille ja koontisivu |
| `/oppaat` | Oppaat (välityspalkkio, myynnin vaiheet, välittäjän valinta) |
| `/kayttoehdot`, `/valittajaehdot`, `/tietosuoja` | Ehdot ja tietosuoja (**luonnoksia**, tarkistutettava) |

## Rakenne

- `server.js` – HTTP-palvelin: evästeistunnot (HMAC), scrypt-salasanat, nopeusrajoitukset, tiedostot, ajastetut työt minuutin välein
- `src/api/` – rajapinta ilman HTTP-riippuvuuksia (`public`, `consumer`, `photos`, `agent`, `admin`)
- `src/tender.js` – kilpailun säännöt (kuka näkee mitä ja milloin)
- `src/compare.js` – palkkiot euroina, viitehinta ja kokonaiskulut
- `src/jobs.js` – ajastetut työt: arviot auki -ilmoitus, arviopyyntö, siivous
- `src/messages.js` – sähköposti- ja tekstiviestipohjat
- `src/validate.js`, `src/catalog.js` – validointi ja luettelot
- `src/prh.js`, `src/geo.js` – YTJ-vastauksen tulkinta, etäisyydet
- `src/seo.js` – kaupunkisivut, oppaat, sivukartta
- `src/node/` – tallennus (JSON tai PostgreSQL), Postmark, 46elks, YTJ-haku
- `public/` – sivut, tyylit ja selainskriptit (ei kehyksiä eikä koontivaihetta)

## Tietosuoja ja turvallisuus

- Välittäjät näkevät kohteen vain hyväksyttyinä ja vain omalta alueeltaan, ilman yhteystietoja ja katuosoitetta. Vahvistamattoman kohteen kuvat eivät näy välittäjille.
- Kuvat pienennetään selaimessa ja niistä poistetaan EXIF-tiedot (myös GPS).
- Vahvistamattomat kohteet ja irralliset kuvat poistetaan automaattisesti.
- Tilastoina kirjataan vain päiväkohtaiset määrät, ei evästeitä eikä henkilötietoja.
- CSP, HttpOnly/SameSite-evästeet, CSRF-suoja JSON-vaatimuksella, IP-kohtaiset nopeusrajat.
