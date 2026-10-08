# Aluesivustojen käyttöönotto

Kolme erillistä sivustoa, kukin omassa alidomainissaan. Pääsivustoon ikkunakauppias.fi
ei kosketa.

| Sivusto | Ehdotettu osoite | Tuontitiedosto | Mukauta → Sivuston alue |
|---|---|---|---|
| Uusimaa (Helsinki, Espoo, Vantaa) | uusimaa.ikkunakauppias.fi | `nordic-uusimaa-sivut.xml` | Uusimaa: Helsinki, Espoo ja Vantaa |
| Meri-Lappi | merilappi.ikkunakauppias.fi | `nordic-meri-lappi-sivut.xml` | Meri-Lappi |
| Satakunta | satakunta.ikkunakauppias.fi | `nordic-satakunta-sivut.xml` | Satakunta |

Jokaisella sivustolla on samat 9 sivua: Etusivu, Ikkunat, Ulko-ovet, Ikkunaremontit,
Oviremontit, Energialaskuri, Ikkuna-asennus, Uudet ikkunat vai huolto ja Yhteystiedot.

## Tee jokaiselle sivustolle

1. **Alidomain:** cPanel → Verkkotunnukset → *Luo uusi verkkotunnus*, esim.
   `uusimaa.ikkunakauppias.fi`. Asenna SSL-varmenne (cPanel → SSL/TLS Status → *Run AutoSSL*).
2. **WordPress:** asenna alidomainiin cPanelin *WordPress Management* / *1-Click* -työkalulla.
3. **Asetukset → Yleiset:** sivuston otsikko esim. `Ikkunakauppias.fi Uusimaa`, kieli suomi,
   aikavyöhyke Helsinki.
4. **Asetukset → Kestolinkit:** valitse *Artikkelin nimi*.
5. **Teema:** Ulkoasu → Teemat → Lisää uusi → Lataa teema → `nordic-theme-v2.2.0.zip` →
   Ota käyttöön.
6. **Alue:** Ulkoasu → Mukauta → **Sivuston alue** → valitse alue → Julkaise.
7. **Sivut:** Työkalut → Tuonti → WordPress → *Asenna nyt* → *Suorita tuonti* → valitse
   alueen XML-tiedosto. Kun kysytään kirjoittajaa, valitse oma käyttäjäsi.
8. **Etusivu:** Asetukset → Lukeminen → *Staattinen sivu* → Etusivu: **Etusivu** → Tallenna.
9. **Tarjouslomake:** asenna Fluent Forms ja tee tarjouspyyntölomake (tai vie pääsivuston
   lomake: Fluent Forms → Tools → Export → Import). Katso lomakkeen numero lyhytkoodista
   `[fluentform id="X"]` ja kirjoita se kohtaan **Mukauta → Sivuston alue → Tarjouslomakkeen ID**.
   Ilmoitusviestin vastaanottajaksi info@ikkunakauppias.fi.
10. **Sähköposti:** asenna FluentSMTP ja käytä samaa Brevo-API-avainta kuin pääsivustolla.
    Domain on jo todennettu, joten info@ikkunakauppias.fi toimii lähettäjänä.
11. **Seuranta:** lisää sivusto Google Search Consoleen (oma ominaisuus alidomainille) ja
    Analyticsiin.

Valinnaiset: Nordic CRM (sama sähköpostiautomaatio) ja Kysy meiltä -chat toimivat
aluesivustoilla samalla tavalla kuin pääsivustolla.

## Hyvä tietää

- Alatunnisteen tietosuojalinkki osoittaa pääsivuston tietosuojaselosteeseen
  (sama yritys ja sama rekisteri). Muita sivuja ei tarvita.
- Yhteystiedot-sivulla ja alatunnisteessa ovat myynnin (045 7832 5070) ja asennuksen (045 7830 4746) numerot.
- Sisällöt ovat aluekohtaisia ja keskenään erilaisia, joten Google ei tulkitse niitä kopioiksi.
- Energialaskuri on täsmälleen sama kuin pääsivustolla.
