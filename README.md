# samielmeranta.fi – Liike Nyt -teemainen Astro-sivusto

Moderni, nopea ja turvallinen staattinen sivusto, jonka sisältö on siirretty
WordPress-sivustolta. Rakennettu Astro 5:llä ja Tailwind CSS 4:llä.

## Käyttö

```bash
pnpm install
pnpm dev       # kehityspalvelin http://localhost:4321
pnpm build     # tuotantoversio kansioon dist/
pnpm preview   # esikatsele tuotantoversiota
```

## Mitä muokata

| Mitä | Missä |
| --- | --- |
| Nimi, puhelin, sähköposti, somet, puolue, lomakepalvelu | `src/config/site.ts` |
| **Brändivärit ja fontit** (yksi paikka) | `src/styles/global.css` → `@theme` |
| Arvot, muutostavoitteet, luottamustoimet, suosittelut, työura, UKK | `src/data/content.ts` |
| Blogikirjoitukset (Markdown) | `src/content/blog/*.md` |
| Potretti | `public/images/` + `site.images.portrait` |
| Galleriakuvat | `src/pages/arki.astro` → `gallery` |
| Some-jakokuva (1200×630) | `public/og.png` |

Uusi blogikirjoitus = uusi `.md`-tiedosto kansioon `src/content/blog/`:

```md
---
title: "Otsikko"
snippet: "Lyhyt kuvaus hakukoneille ja someen."
publishDate: "2026-10-21"
tags: ["kaarina"]
---

Teksti tähän…
```

## Tekoäly- ja hakukoneoptimointi

- JSON-LD-rakenteet: `Person` (puolue, luottamustoimet, somet), `WebSite`,
  `ProfilePage`, `BlogPosting`, `Blog`, `FAQPage`, `BreadcrumbList`, `ContactPage`
- `/llms.txt` – tiivis koneluettava kuvaus (generoidaan automaattisesti)
- `robots.txt` sallii hakukoneet ja tekoälyhaut (ChatGPT, Claude, Perplexity…)
- Sivukartta, RSS-syöte, kanoninen URL, Open Graph ja Twitter-kortit
- Semanttinen HTML, selkeä otsikkohierarkia ja UKK-osio suoria vastauksia varten
- Vanhat WordPress-osoitteet ohjataan uusiin (`astro.config.mjs` + `public/_redirects`)

## Tietoturva

- Staattinen sivusto: ei tietokantaa, hallintapaneelia eikä lisäosia hyökkäyspinnaksi
- Tiukka **Content-Security-Policy** (ei inline-skriptejä tai -tyylejä, ei ulkoisia
  resursseja) + HSTS, X-Frame-Options, nosniff, Referrer- ja Permissions-Policy,
  COOP/CORP → `public/_headers` (Netlify, Cloudflare Pages) ja `vercel.json` (Vercel)
- Ei evästeitä, analytiikkaa tai kolmannen osapuolen upotuksia; fontit itse isännöity
- Ulkoiset linkit `rel="noopener noreferrer"`, JSON-LD escapoitu
- Lomake: roskapostiansa (honeypot), kenttärajoitukset, tietosuojaseloste
- `/.well-known/security.txt`

Jos otat yhteydenottolomakkeen käyttöön (esim. Formspree), aseta
`formEndpoint` tiedostoon `src/config/site.ts` **ja** lisää palvelun domain
`form-action`-sääntöön tiedostoissa `public/_headers` ja `vercel.json`.

## Animaatiot

Kevyet, riippuvuudettomat animaatiot (`src/scripts/motion.ts` + `global.css`):
vieritykseen reagoivat ilmestymiset, numerolaskurit, liikkuvat taustagradientit,
teemanauha, kutistuva yläpalkki ja lukemisen edistymispalkki. Kaikki kunnioittavat
käyttäjän `prefers-reduced-motion`-asetusta, ja sisältö näkyy myös ilman JavaScriptiä.
