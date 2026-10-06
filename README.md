# TiivisTupa – verkkosivusto

**Nordic Ikkunat & Ovet Oy** · markkinointinimi **TiivisTupa** · *Tiiviimmän kodin puolesta*

Ikkunoiden ja ovien tiivistys, huolto, myynti ja asennus Turussa ja Varsinais-Suomessa.
Sivusto on rakennettu [Astrolla](https://astro.build) ja käyttää Nordic-teeman (v2.0.2) ulkoasua.

## Kehitys

```bash
pnpm install
pnpm dev       # kehityspalvelin http://localhost:4321
pnpm build     # staattinen sivusto kansioon dist/
pnpm preview
```

## Rakenne

| Polku | Sisältö |
|---|---|
| `src/data/site.ts` | Yritystiedot (sähköposti, puhelin, lomakkeen osoite), valikot, paikkakunnat |
| `src/data/faq.ts` | Usein kysytyt kysymykset |
| `src/pages/` | Sivut: etusivu, ikkunat, ovet, ikkunaremontti, tiivistys, huolto, alueet, yhteystiedot, UKK, sivukartta |
| `src/pages/alueet/[city].astro` | Paikkakuntasivut (generoidaan `cities`-listasta) |
| `src/components/` | Header, Footer, tarjouslomake, hero, UKK, CTA-osiot |
| `src/styles/nordic.css` | Nordic-teeman tyylit sellaisenaan |
| `src/styles/site.css` | Astro-version täydennykset |
| `public/js/nordic.js` | Teeman animaatiot, mobiilivalikko, UKK-haitarit, tarjousmodaali |
| `public/js/form.js` | Tarjouslomakkeen lähetys |

## Ennen julkaisua

- Päivitä `src/data/site.ts`: sähköposti, puhelinnumero ja `formEndpoint`
  (esim. Formspree tai oma API). Ilman endpointia lomake avaa sähköpostiohjelman.
- Päivitä verkkotunnus `astro.config.mjs`:n `site`-kenttään.
