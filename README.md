# KasvuKumppani

KasvuKumppanin markkinointisivusto: SEO-optimointi, AI-optimointi, kotisivujen toteutus ja digitaalinen markkinointi. Rakennettu Astrolla ja Tailwind CSS:llä.

## Kehitys

### 1. Asenna riippuvuudet

```bash
pnpm install
```

### 2. Käynnistä kehityspalvelin

```bash
pnpm dev
```

### 3. Julkaisuversion esikatselu ja build

```bash
pnpm build
pnpm preview
```

## Sisällönmuokkaus ilman koodia (Keystatic)

Blogiartikkelit, hinnoittelupaketit ja yhteystiedot voi muokata selaimessa ilman koodia [Keystatic](https://keystatic.com/)-editorilla:

```bash
pnpm dev
```

Avaa sitten osoite `http://localhost:4321/keystatic`. Editori kirjoittaa muutokset suoraan projektin tiedostoihin (`src/content/blog/*.md`, `src/data/*.json`) — muista committaa ja pushata muutokset, jotta ne päätyvät julkaistulle sivulle.

Editori on tarkoituksella käytössä vain paikallisessa kehityspalvelimessa (`pnpm dev`), ei julkaistulla sivulla (`pnpm build`), koska sivusto on rakennettu täysin staattiseksi. Jos haluat muokata sisältöä selaimesta ilman repon kloonaamista (esim. puhelimella, kirjautumalla GitHubilla), se on mahdollista laajentaa myöhemmin GitHub-pohjaiseksi tallennukseksi, mutta vaatii oman OAuth-sovelluksen ja palvelinpuolen deployn (esim. Vercel/Netlify).

## Yhteydenottolomake

Yhteydenottolomake (`src/components/contactform.astro`) käyttää [Web3Forms](https://web3forms.com/)-palvelua. Luo ilmainen access key ja korvaa sillä `YOUR_ACCESS_KEY_HERE`-arvo, jotta lomakevastaukset saapuvat sähköpostiin.

## Projektin rakenne

```
/
├── public/                 # staattiset tiedostot (favicon, robots.txt, opengraph.jpg)
├── src/
│   ├── components/         # uudelleenkäytettävät komponentit
│   ├── content/blog/       # blogiartikkelit (Astro content collection)
│   ├── layouts/            # sivupohjat
│   └── pages/               # reitit (etusivu, palvelut, hinnoittelu, blogi, meistä, yhteystiedot)
└── package.json
```

## 👀 Lisätietoa

Ks. [Astro-dokumentaatio](https://docs.astro.build).

[![Built with Astro](https://astro.badg.es/v1/built-with-astro.svg)](https://astro.build)
