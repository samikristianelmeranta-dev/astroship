# Julkaisusuunnitelma

Koodi on valmis; alla olevat kohdat vaativat tilejä, sopimuksia tai ihmisten työtä.

## 1. Tekniset valmistelut

| Tehtävä | Miten | Valmis |
|---|---|---|
| Palvelin EU:ssa | Docker-kuva (`Dockerfile`), esim. UpCloud, Hetzner tai Fly.io | ☐ |
| Tietokanta | Hallinnoitu PostgreSQL 16, `DATABASE_URL`. Kuvat tallentuvat samaan kantaan | ☐ |
| Varmuuskopiot | Päivittäiset varmuuskopiot + viikoittainen `pg_dump` erilliseen sijaintiin. Testaa palautus | ☐ |
| Verkkotunnus ja HTTPS | `PUBLIC_URL`, `NODE_ENV=production` | ☐ |
| Sähköposti | Postmark, `POSTMARK_TOKEN`, `MAIL_FROM`, SPF/DKIM/DMARC | ☐ |
| Salaisuudet | `ADMIN_TOKEN`, `SESSION_SECRET` (`openssl rand -hex 32`) | ☐ |
| Postinumeroiden sijainnit | `node scripts/fetch-postal-coordinates.mjs` (Tilastokeskus Paavo, CC BY 4.0) | ☐ |
| Valvonta | `GET /api/health`, virhelokit, käytettävyysvalvonta | ☐ |

## 2. Juridiikka

- Täydennä yhtiön tiedot sivuille `/kayttoehdot`, `/valittajaehdot` ja `/tietosuoja` ja tarkistuta juristilla.
- Tarkista erityisesti: kuluttajansuoja (palvelu ei ole toimeksiannon osapuoli), välittäjien ehtojen P2B-vaatimukset (järjestysperuste, ilmoitusajat), DSA-velvoitteet, käsittelijäsopimukset.
- Varmista, että lomakkeen ja arvioiden kentät vastaavat välitysliikelain ja hintojen ilmoittamista koskevien säännösten vaatimuksia (palkkiot ja kulut arvonlisäveroineen).

## 3. Välittäjien rekrytointi (ennen kuluttajamarkkinointia)

Tavoite: vähintään 6–8 hyväksyttyä välittäjää ensimmäiselle alueelle.

1. Valitse aloitusalue (esim. yksi kaupunkiseutu) ja aseta `PILOT_REGIONS`.
2. Listaa alueen välitysliikkeet, myös ketjujen yksittäiset toimistot.
3. Kerro lyhyesti: myyjät, jotka ovat oikeasti myymässä; kuvat ja tiedot valmiina; maksu vain valinnasta.
4. Tarjoa pilottijaksolle maksuton käyttö (`FEE_START_DATE`).
5. Hyväksy rekisteröityneet ylläpidossa: tarkista YTJ ja AVI:n välitysliikerekisteri.

## 4. Mittarit (ensimmäiset 3 kuukautta)

| Mittari | Tavoite |
|---|---|
| Vahvistettuja kohteita | 60+ |
| Arvioita per kohde | keskimäärin 3+ |
| Kohteet ilman arvioita | alle 10 % |
| Valittuja välittäjiä | 50 %+ kohteista |
| Lomakkeen läpäisy (vaihe 1 → lähetetty) | 35 %+ |

Ylläpidon Mittarit-välilehti näyttää suppilon (etusivu → lomakkeen vaiheet → lähetetty → vahvistettu → arvio → valittu).
