import * as cheerio from "cheerio";

export interface CheckResult {
  id: string;
  label: string;
  passed: boolean;
  detail: string;
  recommendation: string;
  weight: number;
}

export interface CategoryResult {
  score: number;
  checks: CheckResult[];
}

export interface VisibilityReport {
  url: string;
  fetchedOk: boolean;
  fetchError?: string;
  seo: CategoryResult;
  ai: CategoryResult;
  overallScore: number;
}

const FETCH_TIMEOUT_MS = 8000;
const AI_BOTS = [
  "GPTBot",
  "ChatGPT-User",
  "ClaudeBot",
  "anthropic-ai",
  "PerplexityBot",
  "Google-Extended",
  "CCBot",
];

async function fetchText(
  url: string,
  timeoutMs = FETCH_TIMEOUT_MS,
): Promise<{ ok: boolean; status?: number; body?: string }> {
  const controller = new AbortController();
  const timeout = setTimeout(() => controller.abort(), timeoutMs);
  try {
    const res = await fetch(url, {
      signal: controller.signal,
      redirect: "follow",
      headers: {
        "User-Agent":
          "Mozilla/5.0 (compatible; NakyvyysTestiBot/1.0; +https://kasvukumppani.fi)",
      },
    });
    const body = await res.text();
    return { ok: res.ok, status: res.status, body };
  } catch {
    return { ok: false };
  } finally {
    clearTimeout(timeout);
  }
}

function normalizeUrl(input: string): string {
  let value = input.trim();
  if (!/^https?:\/\//i.test(value)) {
    value = `https://${value}`;
  }
  return new URL(value).toString();
}

function weightedScore(checks: CheckResult[]): number {
  const totalWeight = checks.reduce((sum, c) => sum + c.weight, 0);
  if (totalWeight === 0) return 0;
  const earned = checks.reduce((sum, c) => sum + (c.passed ? c.weight : 0), 0);
  return Math.round((earned / totalWeight) * 100);
}

export async function analyzeVisibility(
  rawUrl: string,
): Promise<VisibilityReport> {
  const url = normalizeUrl(rawUrl);
  const origin = new URL(url).origin;

  const [pageRes, robotsRes, sitemapRes, llmsRes] = await Promise.all([
    fetchText(url),
    fetchText(`${origin}/robots.txt`),
    fetchText(`${origin}/sitemap.xml`),
    fetchText(`${origin}/llms.txt`),
  ]);

  if (!pageRes.ok || !pageRes.body) {
    const emptyChecks: CheckResult[] = [];
    return {
      url,
      fetchedOk: false,
      fetchError:
        "Sivustoa ei saatu haettua. Tarkista, että osoite on oikein ja sivusto on julkisesti saatavilla.",
      seo: { score: 0, checks: emptyChecks },
      ai: { score: 0, checks: emptyChecks },
      overallScore: 0,
    };
  }

  const html = pageRes.body;
  const $ = cheerio.load(html);
  const robotsTxt = robotsRes.ok ? (robotsRes.body ?? "") : "";
  const llmsTxt = llmsRes.ok ? (llmsRes.body ?? "") : "";

  const title = $("title").first().text().trim();
  const metaDescription =
    $('meta[name="description"]').attr("content")?.trim() ?? "";
  const h1s = $("h1");
  const canonical = $('link[rel="canonical"]').attr("href");
  const viewport = $('meta[name="viewport"]').attr("content");
  const lang = $("html").attr("lang");
  const ogTitle = $('meta[property="og:title"]').attr("content");
  const ogDescription = $('meta[property="og:description"]').attr("content");
  const ogImage = $('meta[property="og:image"]').attr("content");
  const jsonLdBlocks = $('script[type="application/ld+json"]');
  const images = $("img");
  const imagesWithAlt = images.filter((_, el) =>
    Boolean($(el).attr("alt")?.trim()),
  );
  const isHttps = url.startsWith("https://");
  const favicon =
    $('link[rel="icon"]').length > 0 ||
    $('link[rel="shortcut icon"]').length > 0;

  const disallowsEverything =
    /Disallow:\s*\/\s*$/im.test(robotsTxt) && !/Allow:/i.test(robotsTxt);
  const sitemapReferencedInRobots = /Sitemap:/i.test(robotsTxt);
  const hasSitemap = sitemapRes.ok || sitemapReferencedInRobots;

  const blockedAiBots = AI_BOTS.filter((bot) => {
    const regex = new RegExp(
      `User-agent:\\s*${bot}[\\s\\S]*?Disallow:\\s*/\\s*$`,
      "im",
    );
    return regex.test(robotsTxt);
  });

  const bodyText = $("body").text().replace(/\s+/g, " ").trim();
  const hasSubstantialContent = bodyText.length > 250;

  let jsonLdTypes: string[] = [];
  jsonLdBlocks.each((_, el) => {
    try {
      const parsed = JSON.parse($(el).text());
      const items = Array.isArray(parsed) ? parsed : [parsed];
      for (const item of items) {
        const type = item?.["@type"];
        if (type)
          jsonLdTypes.push(Array.isArray(type) ? type.join(",") : String(type));
      }
    } catch {
      // ignore malformed JSON-LD
    }
  });
  const hasOrgOrPersonSchema = jsonLdTypes.some((t) =>
    /organization|person|localbusiness/i.test(t),
  );
  const hasFaqOrHowToSchema = jsonLdTypes.some((t) => /faqpage|howto/i.test(t));

  const seoChecks: CheckResult[] = [
    {
      id: "https",
      label: "Sivusto käyttää HTTPS-salausta",
      passed: isHttps,
      detail: isHttps
        ? "Sivusto ladataan salatulla yhteydellä."
        : "Sivustoa ei ladattu HTTPS-osoitteesta.",
      recommendation:
        "Ota HTTPS käyttöön kaikilla sivuilla ja ohjaa HTTP-liikenne automaattisesti HTTPS:ään.",
      weight: 3,
    },
    {
      id: "title",
      label: "Title-tagi on olemassa ja sopivan pituinen",
      passed: title.length >= 10 && title.length <= 65,
      detail: title
        ? `Title: "${title}" (${title.length} merkkiä)`
        : "Title-tagi puuttuu.",
      recommendation:
        "Kirjoita jokaiselle sivulle uniikki, 10-65 merkin title, joka sisältää tärkeimmän avainsanan.",
      weight: 3,
    },
    {
      id: "meta_description",
      label: "Meta-kuvaus on olemassa ja sopivan pituinen",
      passed: metaDescription.length >= 50 && metaDescription.length <= 165,
      detail: metaDescription
        ? `Kuvaus: "${metaDescription}" (${metaDescription.length} merkkiä)`
        : "Meta-kuvaus puuttuu.",
      recommendation:
        "Lisää 50-165 merkin meta-kuvaus, joka kertoo selkeästi mitä sivu tarjoaa.",
      weight: 3,
    },
    {
      id: "h1",
      label: "Sivulla on täsmälleen yksi H1-otsikko",
      passed: h1s.length === 1,
      detail: `Sivulta löytyi ${h1s.length} kpl H1-otsikoita.`,
      recommendation:
        "Käytä jokaisella sivulla vain yhtä H1-otsikkoa, joka kuvaa sivun pääsisällön.",
      weight: 2,
    },
    {
      id: "canonical",
      label: "Canonical-tagi on määritelty",
      passed: Boolean(canonical),
      detail: canonical
        ? `Canonical: ${canonical}`
        : "Canonical-tagia ei löytynyt.",
      recommendation:
        'Lisää <link rel="canonical"> jokaiselle sivulle duplikaattisisällön välttämiseksi.',
      weight: 1,
    },
    {
      id: "viewport",
      label: "Sivusto on mobiilioptimoitu (viewport)",
      passed: Boolean(viewport),
      detail: viewport
        ? `Viewport: ${viewport}`
        : "Viewport-metatagia ei löytynyt.",
      recommendation:
        "Lisää responsiivinen viewport-metatagi, jotta sivusto skaalautuu mobiilissa oikein.",
      weight: 2,
    },
    {
      id: "robots_sitemap",
      label: "robots.txt sallii indeksoinnin eikä estä koko sivustoa",
      passed: robotsRes.ok && !disallowsEverything,
      detail: robotsRes.ok
        ? disallowsEverything
          ? "robots.txt estää hakukoneita indeksoimasta koko sivustoa."
          : "robots.txt löytyy eikä estä koko sivuston indeksointia."
        : "robots.txt-tiedostoa ei löytynyt.",
      recommendation:
        'Varmista, että /robots.txt on olemassa eikä sisällä "Disallow: /" ilman poikkeuksia.',
      weight: 2,
    },
    {
      id: "sitemap",
      label: "XML-sitemap on saatavilla",
      passed: hasSitemap,
      detail: hasSitemap
        ? "Sitemap löytyi (/sitemap.xml tai robots.txt:ssä)."
        : "Sitemapia ei löytynyt.",
      recommendation:
        "Julkaise XML-sitemap osoitteessa /sitemap.xml ja viittaa siihen robots.txt:ssä.",
      weight: 2,
    },
    {
      id: "og_tags",
      label: "Open Graph -tagit somejakoja varten",
      passed: Boolean(ogTitle && ogDescription && ogImage),
      detail: `og:title=${Boolean(ogTitle)}, og:description=${Boolean(ogDescription)}, og:image=${Boolean(ogImage)}`,
      recommendation:
        "Lisää og:title, og:description ja og:image, jotta linkit näyttävät hyvältä somessa.",
      weight: 1,
    },
    {
      id: "structured_data",
      label: "Sivulla on jäsenneltyä dataa (schema.org)",
      passed: jsonLdBlocks.length > 0,
      detail:
        jsonLdBlocks.length > 0
          ? `Löytyi ${jsonLdBlocks.length} JSON-LD-lohko(a): ${jsonLdTypes.join(", ") || "tuntematon tyyppi"}`
          : "JSON-LD-jäsenneltyä dataa ei löytynyt.",
      recommendation:
        "Lisää schema.org-merkintä (esim. Organization, LocalBusiness) JSON-LD-muodossa.",
      weight: 2,
    },
    {
      id: "image_alt",
      label: "Kuvilla on alt-tekstit",
      passed:
        images.length === 0 || imagesWithAlt.length / images.length >= 0.8,
      detail: `${imagesWithAlt.length}/${images.length} kuvasta löytyi alt-teksti.`,
      recommendation:
        "Lisää kuvaava alt-teksti vähintään 80 %:iin sivun kuvista.",
      weight: 1,
    },
    {
      id: "lang",
      label: "HTML-elementillä on lang-attribuutti",
      passed: Boolean(lang),
      detail: lang
        ? `lang="${lang}"`
        : "lang-attribuuttia ei löytynyt <html>-elementistä.",
      recommendation:
        'Lisää esim. lang="fi" <html>-tagiin, jotta hakukoneet ja ruudunlukijat tunnistavat kielen.',
      weight: 1,
    },
    {
      id: "favicon",
      label: "Sivustolla on favicon",
      passed: favicon,
      detail: favicon ? "Favicon löytyi." : "Faviconia ei löytynyt.",
      recommendation:
        'Lisää favicon.ico tai SVG-favicon <link rel="icon">-tagilla.',
      weight: 1,
    },
  ];

  const aiChecks: CheckResult[] = [
    {
      id: "llms_txt",
      label: "llms.txt löytyy (AI-botteja varten)",
      passed: llmsRes.ok && llmsTxt.length > 0,
      detail: llmsRes.ok
        ? "llms.txt löytyi."
        : "llms.txt-tiedostoa ei löytynyt.",
      recommendation:
        "Julkaise /llms.txt, joka kuvaa lyhyesti yrityksesi ja tärkeimmät sivut tekoälypohjaisille hakupalveluille.",
      weight: 2,
    },
    {
      id: "ai_bots_allowed",
      label: "robots.txt sallii tekoälybottien pääsyn",
      passed: robotsRes.ok && blockedAiBots.length === 0,
      detail:
        blockedAiBots.length > 0
          ? `robots.txt estää seuraavat AI-botit: ${blockedAiBots.join(", ")}`
          : "robots.txt ei estä yleisimpiä AI-botteja (GPTBot, ClaudeBot, PerplexityBot ym.).",
      recommendation:
        "Jos haluat näkyä ChatGPT:n, Claudelin ja Perplexityn vastauksissa, älä estä GPTBot-, ClaudeBot- tai PerplexityBot-crawlereita robots.txt:ssä.",
      weight: 3,
    },
    {
      id: "content_without_js",
      label: "Sisältö on luettavissa ilman JavaScriptiä",
      passed: hasSubstantialContent,
      detail: hasSubstantialContent
        ? `Sivun HTML sisälsi ${bodyText.length} merkkiä tekstiä ilman JS-ajoa.`
        : "Sivun raaka HTML sisälsi hyvin vähän tekstiä - sisältö saattaa latautua vasta JavaScriptillä.",
      recommendation:
        "Varmista palvelinpuolen renderöinti (SSR) tai staattinen HTML, sillä monet AI-crawlerit eivät aja JavaScriptiä.",
      weight: 3,
    },
    {
      id: "structured_data_ai",
      label: "Jäsennelty data auttaa tekoälyä ymmärtämään sisällön",
      passed: jsonLdBlocks.length > 0,
      detail:
        jsonLdBlocks.length > 0
          ? "Sivulta löytyi schema.org-merkintää, joka auttaa AI-malleja tulkitsemaan sisältöä."
          : "Jäsenneltyä dataa ei löytynyt.",
      recommendation:
        "Lisää schema.org-merkintä - se helpottaa sekä hakukoneita että tekoälyavustajia ymmärtämään, mistä sivustossa on kyse.",
      weight: 2,
    },
    {
      id: "org_schema",
      label: "Organisaatio-/yritystiedot on merkitty koneluettavasti",
      passed: hasOrgOrPersonSchema,
      detail: hasOrgOrPersonSchema
        ? "Organization/LocalBusiness/Person-skeema löytyi."
        : "Yritys- tai henkilötietoja ei löytynyt jäsenneltynä datana.",
      recommendation:
        "Lisää Organization- tai LocalBusiness-skeema nimellä, osoitteella ja yhteystiedoilla - tämä vahvistaa AI-vastausten luotettavuutta.",
      weight: 2,
    },
    {
      id: "faq_schema",
      label: "FAQ- tai HowTo-sisältöä merkitty skeemalla",
      passed: hasFaqOrHowToSchema,
      detail: hasFaqOrHowToSchema
        ? "FAQPage/HowTo-skeema löytyi."
        : "FAQ- tai HowTo-skeemaa ei löytynyt.",
      recommendation:
        "Jos sivustolla on UKK-osio tai ohjeita, merkitse ne FAQPage/HowTo-skeemalla - tekoälyt suosivat tätä muotoa vastauksissaan.",
      weight: 1,
    },
    {
      id: "meta_description_ai",
      label: "Meta-kuvaus tiivistää sisällön selkeästi",
      passed: metaDescription.length >= 50,
      detail: metaDescription
        ? "Meta-kuvaus on riittävän kuvaava."
        : "Meta-kuvaus puuttuu tai on liian lyhyt.",
      recommendation:
        "Kirjoita meta-kuvaus, joka tiivistää selkeästi mitä yritys tekee - AI-hakupalvelut käyttävät tätä usein tiivistelmän pohjana.",
      weight: 1,
    },
    {
      id: "sitemap_ai",
      label: "Sitemap auttaa AI-crawlereita löytämään sisällön",
      passed: hasSitemap,
      detail: hasSitemap ? "Sitemap löytyi." : "Sitemapia ei löytynyt.",
      recommendation:
        "Julkaise ja pidä ajan tasalla XML-sitemap, jotta myös AI-crawlerit löytävät kaiken sisältösi.",
      weight: 1,
    },
  ];

  const seoScore = weightedScore(seoChecks);
  const aiScore = weightedScore(aiChecks);

  return {
    url,
    fetchedOk: true,
    seo: { score: seoScore, checks: seoChecks },
    ai: { score: aiScore, checks: aiChecks },
    overallScore: Math.round((seoScore + aiScore) / 2),
  };
}
