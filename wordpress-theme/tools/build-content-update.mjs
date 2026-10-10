#!/usr/bin/env node
/**
 * Rakentaa TiliHub-sisältöpäivityslisäosan datan WordPress-viennistä:
 *  1) artikkelien hintakorjaukset (Premium-paketti pois, hinnat nykyisen hinnaston mukaisiksi)
 *  2) palvelusivujen sisältö sivupohjista tavallisiksi sivuiksi
 *
 * Käyttö: node wordpress-theme/tools/build-content-update.mjs <WordPress-vienti.xml>
 * Tulos:  wordpress-plugin/tilihub-sisaltopaivitys/data/updates.json ja MUUTOKSET.md
 */
import fs from "node:fs";
import path from "node:path";
import crypto from "node:crypto";
import { XMLParser } from "fast-xml-parser";

const ROOT = path.resolve(path.dirname(new URL(import.meta.url).pathname), "../..");
const THEME = path.join(ROOT, "wordpress-theme/tilihub");
const OUT = path.join(ROOT, "wordpress-plugin/tilihub-sisaltopaivitys");

const xmlPath = process.argv[2];
if (!xmlPath) {
  console.error("Käyttö: node wordpress-theme/tools/build-content-update.mjs <vienti.xml>");
  process.exit(1);
}

const parser = new XMLParser({
  ignoreAttributes: false,
  attributeNamePrefix: "",
  isArray: (n) => ["item", "category", "wp:postmeta"].includes(n),
});
const items = parser.parse(fs.readFileSync(xmlPath, "utf8")).rss.channel.item;
const text = (v) => (v == null ? "" : typeof v === "object" ? String(v["#text"] ?? "") : String(v));
const md5 = (s) => crypto.createHash("md5").update(s, "utf8").digest("hex");
const meta = (it) => Object.fromEntries((it["wp:postmeta"] || []).map((m) => [text(m["wp:meta_key"]), text(m["wp:meta_value"])]));

// ---------------------------------------------------------------------------
// 1) Hintakorjaukset
// ---------------------------------------------------------------------------

// Lauseenosat, joissa Premium-paketti mainitaan muun tekstin keskellä.
const PHRASES = [
  [/,?\s*tai kiinteänä Premium-pakettina alkaen 167\s*€\s*\/\s*kk/gi, ""],
  [/,?\s*tai kiinteä Premium-paketti alkaen 167\s*€\s*\/\s*kk(\s+sisältäen kirjanpidon, palkanlaskennan ja tilinpäätöksen)?/gi, ""],
  [/,\s*Premium-paketti alkaen 167\s*€\s*\/\s*kk/gi, ""],
  [/\s*tai Premium-paketti 167\s*€\s*\/\s*kk/gi, ""],
  [/,?\s*or a fixed Premium package from €167\s*\/\s*month/gi, ""],
  [/ Tai vaihtoehtoisesti voit valita premium-palvelun, jossa kaikki kirjanpidon asiat kuuluu hintaan\./gi, ""],
  [/Premium-paketti skaalautuu automaattisesti kasvun myötä/gi, "Palvelumme skaalautuu kasvun myötä"],
  [/Ohjelmiston hinta sisältyy palvelupakettiin/g, "Ohjelmistomaksu laskutetaan valitun järjestelmän mukaan"],
  [/läpinäkyvän kiinteän tai tuntipohjaisen hinnoittelun/g, "läpinäkyvän tuntipohjaisen hinnoittelun"],
  [/Saat meiltä kiinteän, läpinäkyvän hinnoittelun/g, "Saat meiltä selkeän, läpinäkyvän hinnoittelun"],
  [
    /TiliHubin Premium-paketti sisältää kirjanpidon lisäksi palkanlaskennan\. Myös tuntiveloituksella palkanlaskenta on saatavilla erillisenä palveluna\./gi,
    "TiliHubilla palkanlaskenta on saatavilla kirjanpidon rinnalla erillisenä palveluna (55 €/h, alk. 15 € / palkka-ajo).",
  ],
  [/Tuntihinnoittelu alkaen 50 €\/h, jos kiinteä paketti ei sovi\./g, "Kirjanpito alkaen 55 € + alv kuukaudessa, tuntihinta 55 €/h."],
  [
    /Kirjanpidon hinta alkaen 55 €\/h tai kiinteä peruspalvelut 55 €\/h, tilinpäätös 79 €\/h\./g,
    "Kirjanpito alkaen 55 € + alv kuukaudessa: peruspalvelut 55 €/h, tilinpäätös 79 €/h.",
  ],
  [
    /Kirjanpito alkaen 55 €\/h tai kiinteä peruspalvelut 55 €\/h, tilinpäätös 79 €\/h\./g,
    "Kirjanpito alkaen 55 € + alv kuukaudessa: peruspalvelut 55 €/h, tilinpäätös 79 €/h.",
  ],
];

// Hinnat nykyisen hinnaston mukaisiksi (1.7.2026): 55 €/h, minimi 55 €/kk, asiantuntijatyö 79 €/h,
// verosuunnittelu 119 €/h.
const PRICES = [
  [/(\b|>)50(\s?)€(\s?)\/(\s?)h\b/g, "$155$2€$3/$4h"],
  [/(\b|>)50(\s?)€(\s?)\/(\s?)kk\b/g, "$155$2€$3/$4kk"],
  [/(alk\.|alkaen|alkaa|vain|veloitus on)(\s+)50(\s?)€/g, "$1$255$3€"],
  [/alkaen: 50€/g, "alkaen: 55€"],
  [/>(\s*)50(\s?)€(\s*)</g, ">$155$2€$3<"],
  [/(tyypillisesti\s*(?:<strong>)?\s*)50 € – 300 €/g, "$155 € – 300 €"],
  [/Tilinpäätös \(Vuosittainen\)(<\/span>\s*<span class="price-tag">)alk\. 300 €/g, "Tilinpäätös (asiantuntijatyö)$179 € / h"],
  [/maksaa vain 50 €\?/g, "maksaa vain 55 €?"],
  [/\b54(\s?)€/g, "55$1€"],
  [/tuntihinta on 54 €/g, "tuntihinta on 55 €"],
  [/\b89(\s?)€(\s?)\/(\s?)h\b/g, "79$1€$2/$3h"],
  [/\b109(\s?)€(\s?)\/(\s?)h\b/g, "119$1€$2/$3h"],
  [/€89\s*\/\s*hour/g, "€79/hour"],
  [/€109\s*\/\s*hour/g, "€119/hour"],
  [/tyypillisesti 50–150 €\/kk/g, "tyypillisesti 55–150 €/kk"],
  [/kuukausikustannus voi olla 50–150 €/g, "kuukausikustannus voi olla 55–150 €"],
  [/€50[–-]150\/month/g, "€55–150/month"],
];

// Hintataulukoiden palvelukuvaukset vastaamaan hinnastoa.
const DESCRIPTIONS = [
  [/Kirjanpito, tilinpäätös, veroilmoitukset, palkanlaskenta/g, "Juokseva kirjanpito, palkanlaskenta, viranomaisilmoitukset"],
  [/Kirjanpito, palkanlaskenta, tilinpäätös, veroilmoitukset/g, "Juokseva kirjanpito, palkanlaskenta, viranomaisilmoitukset"],
  [/(Perus-taloushallinto[\s\S]{0,200}?)Kirjanpito, tilinpäätös, veroilmoitukset(?!,)/g, "$1Juokseva kirjanpito, ALV- ja viranomaisilmoitukset"],
  [/Projektiluonteiset työt, analyysit, juridiset asiakirjat/g, "Tilinpäätös, veroilmoitus, kirjanpidon korjaukset, sopimusasiakirjat"],
  [/Projektiluonteiset työt, juridiset asiakirjat, analyysit/g, "Tilinpäätös, veroilmoitus, kirjanpidon korjaukset, sopimusasiakirjat"],
  [/Bookkeeping, financial statements, tax filings, payroll/g, "Bookkeeping, payroll, VAT and other statutory filings"],
  [/Project-based work, analyses, (legal )?documents/g, "Financial statements, tax returns, corrections, contracts"],
];

/** Etsii pienimmän annettua kohtaa ympäröivän elementin (tr, li, p, …) ja palauttaa [alku, loppu]. */
function enclosing(html, index, tags) {
  for (const tag of tags) {
    const openRe = new RegExp(`<${tag}\\b`, "gi");
    let start = -1;
    let m;
    while ((m = openRe.exec(html)) && m.index < index) start = m.index;
    while (start !== -1) {
      // etsitään vastaava sulkeva tagi tasapainoisesti
      const re = new RegExp(`<(/?)${tag}\\b[^>]*>`, "gi");
      re.lastIndex = start;
      let depth = 0;
      let end = -1;
      let t;
      while ((t = re.exec(html))) {
        depth += t[1] ? -1 : 1;
        if (depth === 0) {
          end = t.index + t[0].length;
          break;
        }
      }
      if (end > index) return [start, end];
      // tämä elementti päättyi ennen kohtaa: kokeillaan edellistä avausta
      const prev = html.lastIndexOf(`<${tag}`, start - 1);
      start = prev;
    }
  }
  return null;
}

/** Poistaa Premium-paketin rivit, listakohdat ja kappaleet. */
function removePremiumElements(html, log) {
  const re = /premium|Kuukausiveloitus \(Ennakoitava\)/gi;
  let guard = 0;
  for (;;) {
    re.lastIndex = 0;
    let m;
    let target = null;
    while ((m = re.exec(html))) {
      // ohitetaan JSON-LD ja HTML-kommentit (käsitellään lauseina)
      const before = html.slice(0, m.index);
      const inScript = before.lastIndexOf("<script") > before.lastIndexOf("</script");
      const inComment = before.lastIndexOf("<!--") > before.lastIndexOf("-->");
      if (inScript || inComment) continue;
      target = m.index;
      break;
    }
    if (target === null || ++guard > 50) break;
    const range = enclosing(html, target, ["tr", "li", "p", "h3", "h4", "div"]);
    if (!range) break;
    const removed = html.slice(range[0], range[1]);
    log.push("POISTETTU: " + removed.replace(/<[^>]+>/g, " ").replace(/\s+/g, " ").trim().slice(0, 200));
    html = html.slice(0, range[0]) + html.slice(range[1]);
  }
  return html;
}

/** JSON-LD:n FAQ-vastauksissa Premium-lauseet poistetaan lauseittain. */
function cleanScripts(html) {
  const PRICE_MAP = { 50: "55", 54: "55", 89: "79", 109: "119" };
  // Poistaa taulukoista Premium-tarjoukset ja korjaa hinnat rakenteisesta datasta.
  const walk = (v) => {
    if (Array.isArray(v)) return v.filter((x) => !(x && typeof x === "object" && /premium/i.test(JSON.stringify(x)))).map(walk);
    if (v && typeof v === "object") {
      const o = {};
      for (const [k, x] of Object.entries(v)) {
        if (k === "price" && PRICE_MAP[String(x)]) o[k] = PRICE_MAP[String(x)];
        else if (typeof x === "string") o[k] = x.replace(/[^."]*\b[Pp]remium[^."]*\.\s?/g, "").trim() || x;
        else o[k] = walk(x);
      }
      return o;
    }
    return v;
  };
  return html.replace(/(<script\b[^>]*>)([\s\S]*?)(<\/script>)/gi, (all, a, body, c) => {
    if (/ld\+json/i.test(a)) {
      try {
        const data = JSON.parse(body);
        if (!/premium|"price":\s*"(50|54|89|109)"/i.test(body)) return all;
        return a + "\n" + JSON.stringify(walk(data), null, 2) + "\n" + c;
      } catch {
        /* ei kelvollista JSONia: käsitellään tekstinä */
      }
    }
    const fixed = body.replace(/[^."]*\b[Pp]remium[^."]*\.\s?/g, "");
    return a + fixed + c;
  });
}

function textOf(s) {
  return s.replace(/<[^>]+>/g, " ").replace(/&nbsp;/g, " ").replace(/\s+/g, " ").trim();
}

function fixContent(content, log) {
  let html = content;
  for (const [re, to] of PHRASES) html = html.replace(re, to);
  html = removePremiumElements(html, log);
  html = cleanScripts(html);
  for (const [re, to] of [...PRICES, ...DESCRIPTIONS]) html = html.replace(re, to);
  return html;
}

/** Lyhyt muutosraportti: muuttuneet tekstilohkot ennen/jälkeen. */
function diffSnippets(a, b) {
  const split = (s) =>
    s
      .split(/(?=<(?:p|li|tr|h[1-6]|td|div)\b)/i)
      .map(textOf)
      .filter((t) => /€|premium/i.test(t));
  const A = split(a);
  const B = new Set(split(b));
  const Bl = split(b);
  const As = new Set(A);
  const out = [];
  for (const t of A) if (!B.has(t)) out.push(["-", t]);
  for (const t of Bl) if (!As.has(t)) out.push(["+", t]);
  return out;
}

const posts = [];
const report = [];
for (const it of items) {
  const type = text(it["wp:post_type"]);
  if (type !== "post" || text(it["wp:status"]) !== "publish") continue;
  const content = text(it["content:encoded"]);
  if (!/premium|\b50\s?€|\b54\s?€|\b89\s?€|\b109\s?€|€89|€109|50€/i.test(content)) continue;
  const log = [];
  const fixed = fixContent(content, log);
  const m = meta(it);
  let desc = m._aioseo_description || "";
  let newDesc = desc;
  for (const [re, to] of [...PHRASES, ...PRICES]) newDesc = newDesc.replace(re, to);
  if (fixed === content && newDesc === desc) continue;
  const id = Number(text(it["wp:post_id"]));
  const slug = decodeURIComponent(text(it["wp:post_name"]));
  posts.push({
    id,
    slug,
    title: text(it.title),
    old_md5: md5(content),
    content: fixed,
    ...(newDesc !== desc ? { seo_description: newDesc } : {}),
  });
  const snippets = diffSnippets(content, fixed);
  report.push(
    `### ${text(it.title)}\n\`/${slug}/\` (ID ${id})\n\n` +
      snippets.map(([s, t]) => `${s === "-" ? "- ~~" + t.slice(0, 220) + "~~" : "- **" + t.slice(0, 220) + "**"}`).join("\n") +
      (newDesc !== desc ? `\n- SEO-kuvaus: ~~${desc}~~ → **${newDesc}**` : "") +
      "\n"
  );
}

// Tarkistus: jääkö Premium-mainintoja tai vanhoja hintoja?
const leftovers = posts.flatMap((p) => {
  const t = textOf(p.content.replace(/<script[\s\S]*?<\/script>/gi, ""));
  const hits = t.match(/[^.]{0,60}(premium|\b(50|54|89|109) ?€ ?\/ ?h|€(89|109))[^.]{0,40}/gi) || [];
  return hits.map((h) => `${p.slug}: ${h.trim()}`);
});

// ---------------------------------------------------------------------------
// 2) Palvelusivut sivupohjista sivuiksi
// ---------------------------------------------------------------------------

const PAGE_SOURCES = [
  { id: 946, slug: "bcd31-home", template: "page-bcd31-home.html" },
  { id: 9, slug: "kirjanpitopalvelut", template: "page-kirjanpitopalvelut.html" },
  { id: 1175, slug: "palkanlaskenta", template: "page-palkanlaskenta.html" },
  { id: 1451, slug: "tilinpaatos", template: "page-tilinpaatos.html" },
  { id: 1886, slug: "kirjanpidon-hinnat", template: "page-kirjanpidon-hinnat.html" },
  { id: 2098, slug: "tietoa-meista", template: "page-tietoa-meista.html" },
  { id: 1238, slug: "yhteystiedot", template: "page-yhteystiedot.html" },
];
const pages = PAGE_SOURCES.map((p) => {
  const tpl = fs.readFileSync(path.join(THEME, "templates", p.template), "utf8");
  const body = tpl
    .replace(/<!-- wp:template-part \{"slug":"header"[^>]*\/-->\s*/, "")
    .replace(/\s*<!-- wp:template-part \{"slug":"footer"[^>]*\/-->\s*$/, "")
    .trim();
  return { id: p.id, slug: p.slug, template: "page-landing", content: body + "\n" };
});

fs.mkdirSync(path.join(OUT, "data"), { recursive: true });
// 3) Poistettavat sivut (siirretään roskakoriin; teema ohjaa vanhan osoitteen 301:llä)
const trash = [{ id: 2106, slug: "kirjanpito-hoiva-alalle", title: "Kirjanpito hoiva-alalle", redirect: "/kirjanpitopalvelut/" }];

fs.writeFileSync(path.join(OUT, "data/updates.json"), JSON.stringify({ generated: new Date().toISOString(), pages, posts, trash }, null, 1));
fs.writeFileSync(
  path.join(OUT, "MUUTOKSET.md"),
  `# Sisältöpäivityksen muutokset\n\nArtikkeleita: ${posts.length}. Yliviivattu = poistuu, lihavoitu = uusi teksti.\n\n` + report.join("\n")
);
console.log(`Sivuja ${pages.length}, artikkeleita ${posts.length}`);
if (leftovers.length) {
  console.log("\nTARKISTA (jäi tekstiin):");
  for (const l of leftovers) console.log("  " + l);
}
