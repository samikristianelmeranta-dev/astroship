// Hakukoneille tarkoitetut sivut: kiinteistönvälittäjä kaupungeittain, koontisivu
// ja oppaat. Sivut renderöidään palvelimella valmiiksi HTML:ksi.

export const SEO_SLUG = 'kiinteistonvalittaja';

// Suurimmat kaupungit: nimi, osoitteen osa, sisäpaikallissija, keskustan postinumero.
export const SEO_CITIES = [
  ['Helsinki', 'helsinki', 'Helsingissä', '00100'],
  ['Espoo', 'espoo', 'Espoossa', '02100'],
  ['Tampere', 'tampere', 'Tampereella', '33100'],
  ['Vantaa', 'vantaa', 'Vantaalla', '01300'],
  ['Oulu', 'oulu', 'Oulussa', '90100'],
  ['Turku', 'turku', 'Turussa', '20100'],
  ['Jyväskylä', 'jyvaskyla', 'Jyväskylässä', '40100'],
  ['Kuopio', 'kuopio', 'Kuopiossa', '70100'],
  ['Lahti', 'lahti', 'Lahdessa', '15100'],
  ['Pori', 'pori', 'Porissa', '28100'],
  ['Kouvola', 'kouvola', 'Kouvolassa', '45100'],
  ['Joensuu', 'joensuu', 'Joensuussa', '80100'],
  ['Lappeenranta', 'lappeenranta', 'Lappeenrannassa', '53100'],
  ['Hämeenlinna', 'hameenlinna', 'Hämeenlinnassa', '13100'],
  ['Vaasa', 'vaasa', 'Vaasassa', '65100'],
  ['Seinäjoki', 'seinajoki', 'Seinäjoella', '60100'],
  ['Rovaniemi', 'rovaniemi', 'Rovaniemellä', '96100'],
  ['Mikkeli', 'mikkeli', 'Mikkelissä', '50100'],
  ['Kotka', 'kotka', 'Kotkassa', '48100'],
  ['Salo', 'salo', 'Salossa', '24100'],
  ['Porvoo', 'porvoo', 'Porvoossa', '06100'],
  ['Kerava', 'kerava', 'Keravalla', '04200'],
  ['Järvenpää', 'jarvenpaa', 'Järvenpäässä', '04400'],
  ['Lohja', 'lohja', 'Lohjalla', '08100'],
  ['Kaarina', 'kaarina', 'Kaarinassa', '20780'],
  ['Nokia', 'nokia', 'Nokialla', '37100'],
].map(([name, slug, locative, postalCode]) => ({ name, slug, locative, postalCode }));

const TIPS = [
  'Vertaa välityspalkkiota samalla kauppahinnalla. Korkein hinta-arvio ei tarkoita korkeinta kauppahintaa.',
  'Kysy, sisältyvätkö valokuvaus, ilmoituskulut, stailaus ja esittelyt palkkioon vai laskutetaanko ne erikseen.',
  'Tarkista toimeksiannon kesto ja irtisanomisehdot ennen kuin allekirjoitat sopimuksen.',
  'Pyydä välittäjää perustelemaan arvio alueen toteutuneilla kaupoilla.',
];

const FAQ = [
  ['Paljonko kiinteistönvälittäjä maksaa?', 'Välityspalkkio on tyypillisesti prosenttiosuus kauppahinnasta, usein vähimmäispalkkiolla, tai kiinteä summa. Kilpailutuksessa näet kaikkien palkkiot rinnakkain samalla kauppahinnalla laskettuna.'],
  ['Sitooko arvion pyytäminen minua?', 'Ei. Arviot ovat maksuttomia ja sitoumuksettomia. Toimeksiantosopimus tehdään vasta valitsemasi välittäjän kanssa.'],
  ['Miksi voin valita kaksi välittäjää?', 'Moni haluaa tavata kaksi välittäjää arviokäynnillä ennen päätöstä. Valitse kaksi, niin molemmat saavat yhteystietosi ja sovitte arviokäynnit.'],
  ['Näkevätkö välittäjät yhteystietoni?', 'Eivät. Välittäjät näkevät asunnon tiedot ja kuvat ilman nimeäsi, katuosoitettasi, sähköpostiasi ja puhelinnumeroasi. Yhteystiedot saavat vain valitsemasi välittäjät.'],
];

export const GUIDES = {
  valityspalkkio: {
    title: 'Välityspalkkio: mitä se sisältää ja miten vertailla?',
    description: 'Prosenttipalkkio, vähimmäispalkkio vai kiinteä hinta? Näin vertailet kiinteistönvälittäjien palkkioita oikein.',
    body: [
      ['Mistä palkkio muodostuu', 'Välityspalkkio on yleensä prosenttiosuus toteutuneesta kauppahinnasta. Usein siihen liittyy vähimmäispalkkio, joka nousee merkitseväksi edullisissa asunnoissa. Osa välittäjistä tarjoaa kiinteää palkkiota, joka ei riipu kauppahinnasta.'],
      ['Ilmoita ja vertaa hinnat arvonlisäveroineen', 'Kuluttajalle ilmoitettavien hintojen on sisällettävä arvonlisävero. Varmista, että vertaat prosentteja ja euroja, joissa vero on mukana.'],
      ['Lisäkulut', 'Palkkion lisäksi voidaan laskuttaa esimerkiksi ilmoituskuluja, valokuvausta, stailausta tai asiakirjojen hankintaa. Välittäjävertailussa välittäjä kertoo lisäkulut erikseen, ja ne lasketaan mukaan kokonaiskuluun.'],
      ['Vertaa samalla hinnalla', 'Jos yksi välittäjä arvioi asunnon 10 000 euroa muita korkeammalle, myös hänen prosenttipalkkionsa näyttää suuremmalta tai pienemmältä kuin todellisuudessa. Siksi Välittäjävertailu laskee kaikkien palkkiot samalla viitehinnalla, joka on arvioiden mediaani.'],
      ['Ei kauppaa, ei palkkiota', 'Kysy, maksatko mitään, jos asunto ei mene kaupaksi toimeksiannon aikana. Moni välittäjä tarjoaa ehdon, jossa palkkiota tai kuluja ei peritä ilman kauppaa.'],
    ],
  },
  'asunnon-myynti-vaiheet': {
    title: 'Asunnon myynti vaihe vaiheelta',
    description: 'Arviokäynnistä kaupantekoon: mitä asunnon myynnissä tapahtuu ja mitä välittäjä tekee puolestasi.',
    body: [
      ['1. Välittäjän valinta ja arviokäynti', 'Kilpailuta välittäjät ja tapaa yksi tai kaksi arviokäynnillä. Välittäjä tarkentaa hinta-arvion nähtyään asunnon.'],
      ['2. Toimeksiantosopimus', 'Toimeksiantosopimus tehdään kirjallisena. Siinä sovitaan palkkio, kulut, toimeksiannon kesto ja pyyntöhinta.'],
      ['3. Asiakirjat ja markkinointi', 'Välittäjä hankkii esimerkiksi isännöitsijäntodistuksen tai lainhuutotodistuksen, teettää kuvat ja julkaisee myynti-ilmoituksen.'],
      ['4. Esittelyt ja tarjoukset', 'Välittäjä järjestää esittelyt, vastaanottaa ostotarjoukset ja neuvottelee niistä kanssasi.'],
      ['5. Kauppa', 'Kun tarjous hyväksytään, välittäjä valmistelee kauppakirjan ja huolehtii kaupanteosta.'],
    ],
  },
  'nain-valitset-valittajan': {
    title: 'Näin valitset kiinteistönvälittäjän',
    description: 'Hinta-arvio, palkkio, markkinointi ja kokemus: mihin kannattaa kiinnittää huomiota välittäjää valitessa.',
    body: [
      ['Älä valitse pelkän arvion perusteella', 'Korkea hinta-arvio houkuttelee, mutta kauppahinnan ratkaisevat ostajat. Pyydä perustelut ja vertaa arvioita keskenään.'],
      ['Tarkista rekisteröinti', 'Kiinteistönvälitysliikkeen on oltava rekisteröity aluehallintoviraston välitysliikerekisteriin. Välittäjävertailussa ylläpito tarkistaa rekisteröinnin ennen kuin välittäjä pääsee näkemään kohteita.'],
      ['Vertaa palvelua', 'Katso, mitä palkkioon sisältyy: valokuvaus, pohjapiirros, video- tai 3D-esittely, somemainonta ja yksityisesittelyt.'],
      ['Tapaa kaksi', 'Arviokäynnillä näet, miten välittäjä tuntee alueen ja miten hän aikoo markkinoida asuntoasi. Kahden tapaaminen antaa vertailukohdan.'],
    ],
  },
};

const esc = (s) => String(s).replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[c]);

function page({ title, description, path, body, jsonLd, base = '' }) {
  return `<!doctype html>
<html lang="fi">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
  <title>${esc(title)} | Välittäjävertailu</title>
  <meta name="description" content="${esc(description)}">
  <link rel="canonical" href="${esc(base + path)}">
  <meta property="og:title" content="${esc(title)}">
  <meta property="og:description" content="${esc(description)}">
  <meta property="og:type" content="website">
  <link rel="icon" href="/favicon.svg" type="image/svg+xml">
  <link rel="stylesheet" href="/styles.css">
  ${jsonLd ? `<script type="application/ld+json">${JSON.stringify(jsonLd).replace(/</g, '\\u003c')}</script>` : ''}
</head>
<body>
  <header class="site-header">
    <div class="container">
      <a class="logo" href="/">Välittäjä<span>vertailu</span></a>
      <nav class="nav">
        <a href="/oppaat">Oppaat</a>
        <a href="/omat">Omat pyynnöt</a>
        <a class="btn" href="/kilpailuta">Kilpailuta välittäjät</a>
      </nav>
    </div>
  </header>
  <main>
${body}
  </main>
  <footer class="site-footer">
    <div class="container">
      <div>© Välittäjävertailu – kiinteistönvälittäjien kilpailutus</div>
      <div><a href="/valittajille">Välittäjille</a> · <a href="/oppaat">Oppaat</a> · <a href="/kayttoehdot">Käyttöehdot</a> · <a href="/tietosuoja">Tietosuoja</a></div>
    </div>
  </footer>
  <script type="module" src="/seo.js"></script>
</body>
</html>
`;
}

const ctaButton = (postalCode, label) =>
  `<a class="btn btn-lg" href="/kilpailuta" data-quickstart-postal="${postalCode ?? ''}">${esc(label)} <span class="arrow" aria-hidden="true">→</span></a>`;

const steps = `
      <ol class="timeline seo-steps">
        <li data-step="1"><div><h3>Kerro asunnostasi</h3><p>Perustiedot, osoite ja vähintään kolme kuvaa. Maksuton ja sitoumukseton.</p></div></li>
        <li data-step="2"><div><h3>Välittäjät arvioivat suljetusti</h3><p>Alueen tarkistetut välittäjät jättävät hinta-arvion ja palkkiotarjouksen. Kukaan ei näe toisten tarjouksia.</p></div></li>
        <li data-step="3"><div><h3>Vertaa ja valitse 1–2</h3><p>Näet arviot ja palkkiot rinnakkain. Valitut saavat yhteystietosi ja sopivat arviokäynnin.</p></div></li>
      </ol>`;

function cityPage(city, base) {
  const title = `Kiinteistönvälittäjä ${city.locative} – kilpailuta välityspalkkio`;
  const description = `Kilpailuta kiinteistönvälittäjät ${city.locative}. Saat hinta-arviot ja palkkiotarjoukset rinnakkain ja valitset yhden tai kaksi välittäjää. Maksuton.`;
  const others = SEO_CITIES.filter((c) => c.slug !== city.slug);
  const body = `
    <section class="seo-hero">
      <div class="container">
        <p class="eyebrow"><a href="/${SEO_SLUG}">Kiinteistönvälittäjä</a> · ${esc(city.name)}</p>
        <h1>Kiinteistönvälittäjä ${esc(city.locative)}</h1>
        <p class="lead">Myytkö asuntoa ${esc(city.locative)}? Kerro asunnostasi kerran, niin alueen välittäjät jättävät hinta-arvion ja palkkiotarjouksen. Sinä vertaat ja valitset.</p>
        <div class="seo-cta">${ctaButton(city.postalCode, 'Kilpailuta välittäjät')}<span class="hint">Maksuton · sitoumukseton</span></div>
      </div>
    </section>
    <section>
      <div class="container seo-grid">
        <div class="article">
          <h2>Näin kilpailutus toimii ${esc(city.locative)}</h2>
          ${steps}
          <h2>Mitä huomioida</h2>
          <ul>${TIPS.map((t) => `<li>${esc(t)}</li>`).join('')}</ul>
          <h2>Usein kysyttyä</h2>
          ${FAQ.map(([q, a]) => `<details><summary>${esc(q)}</summary><p>${esc(a)}</p></details>`).join('\n')}
        </div>
        <aside class="card seo-aside">
          <p class="eyebrow">Välittäjävertailu</p>
          <p class="seo-price">0 €</p>
          <p class="hint">Myyjälle aina maksuton. Saat hinta-arviot ja välityspalkkiot samassa muodossa.</p>
          ${ctaButton(city.postalCode, 'Aloita')}
          <p class="hint">Yhteystietosi vain valitsemillesi välittäjille</p>
        </aside>
      </div>
    </section>
    <section class="band">
      <div class="container">
        <h2>Kiinteistönvälittäjä muualla Suomessa</h2>
        <ul class="city-links">${others.map((c) => `<li><a href="/${SEO_SLUG}/${c.slug}">${esc(c.name)}</a></li>`).join('')}</ul>
      </div>
    </section>`;
  const jsonLd = [
    {
      '@context': 'https://schema.org',
      '@type': 'Service',
      name: title,
      serviceType: 'Kiinteistönvälittäjien kilpailutus',
      areaServed: { '@type': 'City', name: city.name },
      provider: { '@type': 'Organization', name: 'Välittäjävertailu', url: `${base}/` },
    },
    {
      '@context': 'https://schema.org',
      '@type': 'FAQPage',
      mainEntity: FAQ.map(([q, a]) => ({ '@type': 'Question', name: q, acceptedAnswer: { '@type': 'Answer', text: a } })),
    },
  ];
  return page({ title, description, path: `/${SEO_SLUG}/${city.slug}`, body, jsonLd, base });
}

function hub(base) {
  const body = `
    <section class="seo-hero">
      <div class="container">
        <h1>Kiinteistönvälittäjä – kilpailuta koko Suomessa</h1>
        <p class="lead">Saat hinta-arviot ja palkkiotarjoukset alueesi välittäjiltä yhdellä pyynnöllä.</p>
        <div class="seo-cta">${ctaButton('', 'Kilpailuta välittäjät')}</div>
      </div>
    </section>
    <section>
      <div class="container">
        <h2>Valitse kaupunki</h2>
        <ul class="city-links">${SEO_CITIES.map((c) => `<li><a href="/${SEO_SLUG}/${c.slug}">${esc(c.name)}</a></li>`).join('')}</ul>
      </div>
    </section>`;
  return page({ title: 'Kiinteistönvälittäjä – kilpailuta välittäjät', description: 'Valitse kaupunki ja kilpailuta paikalliset kiinteistönvälittäjät.', path: `/${SEO_SLUG}`, body, base });
}

function guidePage(slug, base) {
  const g = GUIDES[slug];
  const body = `
    <section class="seo-hero">
      <div class="container narrow">
        <p class="eyebrow"><a href="/oppaat">Oppaat</a></p>
        <h1>${esc(g.title)}</h1>
        <p class="lead">${esc(g.description)}</p>
      </div>
    </section>
    <section>
      <div class="narrow article">
        ${g.body.map(([h, p]) => `<h2>${esc(h)}</h2>\n<p>${esc(p)}</p>`).join('\n')}
        <div class="cta">
          <h2>Kilpailuta välittäjät</h2>
          <p>Alueen välittäjät jättävät hinta-arvion ja palkkiotarjouksen. Maksutonta ja sitoumuksetonta.</p>
          <a class="btn btn-lg" href="/kilpailuta">Aloita <span class="arrow" aria-hidden="true">→</span></a>
        </div>
      </div>
    </section>`;
  return page({
    title: g.title, description: g.description, path: `/oppaat/${slug}`, body, base,
    jsonLd: { '@context': 'https://schema.org', '@type': 'Article', headline: g.title, description: g.description, publisher: { '@type': 'Organization', name: 'Välittäjävertailu' } },
  });
}

function guidesIndex(base) {
  const body = `
    <section class="seo-hero">
      <div class="container narrow">
        <h1>Oppaat asunnon myyjälle</h1>
        <p class="lead">Välityspalkkiot, myynnin vaiheet ja välittäjän valinta selkeästi.</p>
      </div>
    </section>
    <section>
      <div class="container grid">
        ${Object.entries(GUIDES).map(([slug, g]) => `<a class="card service-card" href="/oppaat/${slug}"><h3>${esc(g.title)}</h3><p>${esc(g.description)}</p><span class="more">Lue opas →</span></a>`).join('\n')}
      </div>
    </section>`;
  return page({ title: 'Oppaat', description: 'Oppaat asunnon myyntiin ja kiinteistönvälittäjän valintaan.', path: '/oppaat', body, base });
}

// Palauttaa sivun HTML:n tai null, jos polku ei ole hakukonesivu.
export function renderSeoPage(pathname, base = '') {
  const parts = pathname.replace(/\/+$/, '').split('/').filter(Boolean);
  if (parts[0] === 'oppaat') {
    if (parts.length === 1) return guidesIndex(base);
    if (parts.length === 2 && GUIDES[parts[1]]) return guidePage(parts[1], base);
    return null;
  }
  if (parts[0] !== SEO_SLUG) return null;
  if (parts.length === 1) return hub(base);
  const city = parts.length === 2 && SEO_CITIES.find((c) => c.slug === parts[1]);
  return city ? cityPage(city, base) : null;
}

export function seoPaths() {
  return [
    '/oppaat',
    ...Object.keys(GUIDES).map((g) => `/oppaat/${g}`),
    `/${SEO_SLUG}`,
    ...SEO_CITIES.map((c) => `/${SEO_SLUG}/${c.slug}`),
  ];
}

export function sitemapXml(base = '') {
  const paths = ['/', '/kilpailuta', '/valittajille', ...seoPaths()];
  return `<?xml version="1.0" encoding="UTF-8"?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
${paths.map((p) => `  <url><loc>${esc(base + p)}</loc></url>`).join('\n')}
</urlset>
`;
}
