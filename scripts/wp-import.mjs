#!/usr/bin/env node
/**
 * WordPress -> Astro -tuonti.
 *
 * Käyttö:  node scripts/wp-import.mjs <WordPress-vienti.xml>
 *
 * Luo:
 *   src/content/posts/<slug>.md   julkaistut artikkelit
 *   src/content/pages/<id>.md     julkaistut sivut (polku frontmatterissa)
 *   src/data/wp-urls.json         kaikki vanhan sivuston julkiset osoitteet
 *   src/data/wp-images.json       sisällön kuvat (ladataan scripts/wp-fetch-images.mjs:llä)
 *
 * Sisältö siistitään: WordPressin lohkokommentit, inline-tyylit, skriptit,
 * lomakkeet ja koristeelliset osiot poistetaan, ja sisäiset linkit muutetaan
 * suhteellisiksi. Raportti ongelmista tulostetaan lopuksi.
 */
import fs from "node:fs";
import path from "node:path";
import { XMLParser } from "fast-xml-parser";
import * as cheerio from "cheerio";
import TurndownService from "turndown";
import { gfm } from "turndown-plugin-gfm";

const SITE = "https://www.tilihub.fi";
const SITE_NAME = "TiliHub Oy";
const ROOT = path.resolve(path.dirname(new URL(import.meta.url).pathname), "..");

// Sivut, joiden sisältö on käsin tehty Astro-sivu (ei generoida markdownia).
const BESPOKE_PAGES = new Set(["/", "/kirjanpidon-hinnat/", "/yhteystiedot/"]);

// Vanhan sivuston rikkinäiset sisäiset linkit -> oikea kohde.
const LINK_FIXES = {
  "/hinnasto/": "/kirjanpidon-hinnat/",
  "/hinnat/": "/kirjanpidon-hinnat/",
  "/kirjanpito/": "/kirjanpitopalvelut/",
  "/yhteystiedot/yhteystiedot/": "/yhteystiedot/",
  "/yhteystiedot.html": "/yhteystiedot/",
  "/ota-yhteytta/": "/yhteystiedot/",
  "/kirjanpito.html": "/kirjanpitopalvelut/",
  "/hinnasto.html": "/kirjanpidon-hinnat/",
  "/palkanlaskenta.html": "/palkanlaskenta/",
  "/tilinpaatos.html": "/tilinpaatos/",
  "/yrityksen-perustaminen.html": "/yrityksen-perustaminen/",
  "/toiminimen-kirjanpito/": "/kirjanpidon-erot-toiminimi-vs-osakeyhtio/",
  "/osakeyhtion-kirjanpito/": "/kirjanpito-pienelle-osakeyhtiolle/",
  "/osakeyhtion-kirjanpito.html": "/kirjanpito-pienelle-osakeyhtiolle/",
  "/kirjanpito-ohjelmat-vertailu-2026/": "/kirjanpito-ohjelmat-vertailu/",
  "/kilometrikorvaus-matkakorvaus-paivaraha-2026/": "/kilometrikorvaus-matkakorvaus-ja-paivaraha-vuonna-2026/",
  "/luontoisedut-2026/": "/kilometrikorvaus-matkakorvaus-ja-paivaraha-vuonna-2026/",
  "/tilitoimiston-vaihto-edessa/": "/tilitoimistoa-vaihtamassa/",
  "/kirjanpidon-perusteet-liiketoiminnassa/": "/mita-kirjanpitoon-kuuluu/",
  "/yleisimmat-kirjanpidon-virheet/": "/yleiset-virheet-kirjanpidossa-lue-lisaa/",
  "/juokseva-kirjanpito/": "/juokseva-kirjanpito-mita-tarkoittaa/",
  "/artikkeli-alv-ilmoitus-myohastyy.html": "/alv-ilmoitus-myohassa-miten-toimin/",
  "/artikkeli-kassavirtaennuste.html": "/kassavirtaennuste-tarkeampaa-kuin-tulos/",
  "/artikkeli-unohdetut-verovahennykset.html": "/verovahennykset-jotka-helposti-unohtuu/",
  "/kirjanpito-teollisuuteen/": "/teollisuuden-kirjanpito/",
  "/tilitoimisto-pori/": "/tilitoimisto-porissa-digitaalisesti-ympari-suomea/",
};

// Uuden sivuston omat osoitteet, joihin sisällöstä saa linkittää.
const NEW_PATHS = ["/blogi/"];

const xmlPath = process.argv[2];
if (!xmlPath) {
  console.error("Käyttö: node scripts/wp-import.mjs <WordPress-vienti.xml>");
  process.exit(1);
}

const parser = new XMLParser({
  ignoreAttributes: false,
  attributeNamePrefix: "",
  cdataPropName: false,
  isArray: (name) => ["item", "category", "wp:postmeta", "wp:category", "wp:tag"].includes(name),
});
const doc = parser.parse(fs.readFileSync(xmlPath, "utf8"));
const channel = doc.rss.channel;
const items = channel.item;

const text = (v) => (v == null ? "" : typeof v === "object" ? String(v["#text"] ?? "") : String(v));
const meta = (it) =>
  Object.fromEntries((it["wp:postmeta"] || []).map((m) => [text(m["wp:meta_key"]), text(m["wp:meta_value"])]));
const decode = (s) => cheerio.load(`<p>${s}</p>`)("p").text();

const report = { brokenLinks: new Map(), images: new Set(), notes: [] };

// ---------- apurit ----------

function resolveSeoTitle(tpl, title) {
  if (!tpl) return "";
  return decode(
    tpl
      .replace(/#post_title/g, title)
      .replace(/#separator_sa/g, "-")
      .replace(/#site_title/g, SITE_NAME)
      .replace(/#current_year/g, String(new Date().getFullYear()))
      .replace(/#\w+/g, "")
      .replace(/\s+/g, " ")
      .trim()
  );
}

function linkPath(href) {
  try {
    const u = new URL(href, SITE);
    if (!/^(www\.)?tilihub\.fi$/.test(u.hostname)) return null;
    return u;
  } catch {
    return null;
  }
}

const EMOJI_ONLY = /^[\s\p{Extended_Pictographic}‍️★☆✓✔]+$/u;

/** Siistii WordPressin HTML:n ja palauttaa cheerio-dokumentin body-sisällön. */
function cleanHtml(html, { template = false } = {}) {
  // Osa artikkeleista on liitetty kokonaisina HTML-dokumentteina wp:html-lohkoon.
  html = html.replace(/<!--[\s\S]*?-->/g, "");
  html = html.replace(/<!DOCTYPE[^>]*>/gi, "");
  html = html.replace(/<\/?(html|head|body)[^>]*>/gi, "");
  html = html.replace(/\[(wpforms|contact-form-7)[^\]]*\]/g, "");
  const $ = cheerio.load(`<div id="root">${html}</div>`, null, false);
  const root = $("#root");

  root.find("style, script, meta, link, title, noscript, form, iframe, input, button, svg").remove();
  root.find('[class*="themeisle-blocks/form"], .wp-block-themeisle-blocks-form, .wp-block-themeisle-blocks-popup').remove();
  root.find(".wp-block-post-comments-form, .wp-block-spacer, .gutentools-agency-scroll-top").remove();

  if (template) {
    // Sivupohjien koristeelliset ja toistuvat osiot korvataan uuden teeman omilla.
    root.find(".th-partners, .th-cta-band, .th-trust, .th-stars, .th-eyebrow, .th-label").remove();
    root.find(".th-service-icon, .th-why-dot, .th-contact-icon").remove();
    // Vaiheiden numerot ovat pelkkää koristetta.
    root.find("div, span, p").each((_, el) => {
      if (/^\d{1,2}$/.test($(el).text().trim()) && !$(el).children().length) $(el).remove();
    });
  }

  // Kuvat: absoluuttiset https-osoitteet, talteen latauslistaan.
  root.find("img").each((_, el) => {
    const $el = $(el);
    let src = $el.attr("src") || "";
    if (src.startsWith("//")) src = "https:" + src;
    src = src.replace(/^http:\/\//, "https://");
    if (src.startsWith("/")) src = SITE + src;
    $el.attr("src", src);
    for (const a of Object.keys(el.attribs)) if (!["src", "alt", "width", "height"].includes(a)) $el.removeAttr(a);
    if (/tilihub\.fi\/wp-content\/uploads\//.test(src)) report.images.add(src);
  });

  // Linkit: sisäiset suhteellisiksi, korjaa tunnetut rikkinäiset.
  root.find("a").each((_, el) => {
    const $el = $(el);
    const href = $el.attr("href") || "";
    const u = linkPath(href);
    if (u && !u.pathname.startsWith("/wp-content/")) {
      let p = u.pathname;
      if (!p.endsWith("/") && !path.extname(p)) p += "/";
      p = LINK_FIXES[p] || p;
      $el.attr("href", p + u.search + u.hash);
    } else if (u) {
      $el.attr("href", SITE + u.pathname);
    }
    for (const a of Object.keys(el.attribs)) if (!["href", "title"].includes(a)) $el.removeAttr(a);
    if (/^https?:/.test($el.attr("href") || "")) $el.attr("rel", "noopener");
  });

  // Poista tyylit ja luokat kaikesta.
  root.find("*").each((_, el) => {
    if (el.name === "img" || el.name === "a") return;
    for (const a of Object.keys(el.attribs || {})) {
      if (["colspan", "rowspan", "open"].includes(a)) continue;
      $(el).removeAttr(a);
    }
  });

  // Pelkkiä emojeja tai tähtiä sisältävät rivit pois.
  root.find("p, div, span, h1, h2, h3, h4, li").each((_, el) => {
    const $el = $(el);
    if ($el.find("img").length) return;
    const t = $el.text().trim();
    if (t === "" || EMOJI_ONLY.test(t)) $el.remove();
  });
  // Emojit otsikoiden ja rivien alusta.
  root.find("h1, h2, h3, h4, h5, li, p, summary").each((_, el) => {
    const first = el.children?.[0];
    if (first && first.type === "text") first.data = first.data.replace(/^[\s\p{Extended_Pictographic}️]+/u, "");
  });

  return { $, root };
}

const td = new TurndownService({
  headingStyle: "atx",
  bulletListMarker: "-",
  codeBlockStyle: "fenced",
  emDelimiter: "*",
});
td.use(gfm);
td.keep(["details", "summary"]);
// Taulukot, joissa ei ole otsikkoriviä, jäisivät muuten HTML:ksi: tehdään ensimmäisestä rivistä otsikko.
td.addRule("tableNoHead", {
  filter: (node) => node.nodeName === "TABLE" && !node.querySelector("th"),
  replacement: (_c, node) => {
    const rows = [...node.querySelectorAll("tr")].map((tr) =>
      [...tr.children].map((c) => c.textContent.replace(/\s+/g, " ").replace(/\|/g, "\\|").trim())
    );
    if (!rows.length) return "";
    const w = Math.max(...rows.map((r) => r.length));
    const line = (r) => "| " + Array.from({ length: w }, (_, i) => r[i] ?? "").join(" | ") + " |";
    return "\n\n" + [line(rows[0]), "|" + " --- |".repeat(w), ...rows.slice(1).map(line)].join("\n") + "\n\n";
  },
});

function toMarkdown(html, title, opts) {
  const { $, root } = cleanHtml(html, opts);
  // Sivun otsikko näytetään pohjassa h1:nä; sisällön ensimmäinen h1 poistetaan ja muut h1:t -> h2.
  const h1s = root.find("h1");
  h1s.each((i, el) => {
    if (i === 0) {
      if (opts?.keepLeadH1) return;
      $(el).remove();
    } else el.tagName = "h2";
  });
  // details: summary + sisältö markdowniksi details-tagin sisään
  root.find("details").each((_, el) => {
    const $el = $(el);
    const summary = $el.find("summary").first().text().trim();
    $el.find("summary").remove();
    const inner = td.turndown($el.html() || "").trim();
    $el.replaceWith(`<details><summary>${summary}</summary>\n\n${inner}\n\n</details>`);
  });
  let md = td.turndown(root.html() || "");
  md = md
    .replace(/ /g, " ")
    .replace(/\n{3,}/g, "\n\n")
    .replace(/^\s*\\?-\s*$/gm, "")
    .trim();
  return md;
}

function yamlStr(s) {
  return JSON.stringify(s ?? "");
}

function excerptFrom(md, n = 180) {
  const plain = md
    .replace(/<[^>]+>/g, " ")
    .replace(/!\[[^\]]*\]\([^)]*\)/g, "")
    .replace(/\[([^\]]*)\]\([^)]*\)/g, "$1")
    .replace(/[#>*_`|-]/g, " ")
    .replace(/\s+/g, " ")
    .trim();
  return plain.length > n ? plain.slice(0, plain.lastIndexOf(" ", n)) + "…" : plain;
}

// ---------- kerää data ----------

const byType = (t, s = "publish") =>
  items.filter((i) => text(i["wp:post_type"]) === t && (s == null || text(i["wp:status"]) === s));

const templates = {};
for (const t of byType("wp_template")) {
  const theme = (t.category || []).map((c) => text(c)).find((c) => c === "gutentools");
  if (!theme) continue;
  const slug = text(t["wp:post_name"]);
  const prev = templates[slug];
  if (!prev || text(t["wp:post_modified"]) > text(prev["wp:post_modified"])) templates[slug] = t;
}

const attachments = Object.fromEntries(
  byType("attachment", null).map((a) => [text(a["wp:post_id"]), text(a["wp:attachment_url"]).replace(/^http:/, "https:")])
);

const urls = [];
const outPosts = path.join(ROOT, "src/content/posts");
const outPages = path.join(ROOT, "src/content/pages");
for (const d of [outPosts, outPages]) {
  fs.rmSync(d, { recursive: true, force: true });
  fs.mkdirSync(d, { recursive: true });
}

// ---------- artikkelit ----------
const catNames = {};
const tagNames = {};
const catUse = new Set();
const tagUse = new Set();

for (const it of byType("post")) {
  const m = meta(it);
  const title = decode(text(it.title));
  const slug = decodeURIComponent(text(it["wp:post_name"]));
  const link = new URL(text(it.link)).pathname;
  const cats = [];
  const tags = [];
  for (const c of it.category || []) {
    const nice = c.nicename;
    if (c.domain === "category") {
      cats.push(nice);
      catNames[nice] = decode(text(c));
      catUse.add(nice);
    } else if (c.domain === "post_tag") {
      tags.push(nice);
      tagNames[nice] = decode(text(c));
      tagUse.add(nice);
    }
  }
  const body = toMarkdown(text(it["content:encoded"]), title, {});
  const seoTitle = resolveSeoTitle(m._aioseo_title, title);
  const description = decode(m._aioseo_description || "") || excerptFrom(body);
  const image = m._thumbnail_id ? attachments[m._thumbnail_id] : undefined;
  const fm = [
    "---",
    `title: ${yamlStr(title)}`,
    `slug: ${yamlStr(slug)}`,
    `path: ${yamlStr(link)}`,
    `date: ${text(it["wp:post_date"]).replace(" ", "T")}`,
    `updated: ${text(it["wp:post_modified"]).replace(" ", "T")}`,
    seoTitle ? `seoTitle: ${yamlStr(seoTitle)}` : null,
    `description: ${yamlStr(description)}`,
    `categories: ${JSON.stringify(cats)}`,
    `tags: ${JSON.stringify(tags)}`,
    image ? `image: ${yamlStr(image)}` : null,
    `wpId: ${text(it["wp:post_id"])}`,
    "---",
    "",
  ]
    .filter((l) => l !== null)
    .join("\n");
  fs.writeFileSync(path.join(outPosts, `${slug}.md`), fm + body + "\n");
  urls.push({ path: link, type: "post", title });
  if (!body) report.notes.push(`Tyhjä artikkeli: ${link}`);
}

// ---------- sivut ----------
for (const it of byType("page")) {
  const m = meta(it);
  const title = decode(text(it.title));
  const link = new URL(text(it.link)).pathname;
  urls.push({ path: link, type: "page", title });
  if (BESPOKE_PAGES.has(link)) continue;

  let html = text(it["content:encoded"]);
  let fromTemplate = false;
  const tplName = m._wp_page_template;
  const tplSlug = tplName === "default" || !tplName ? "page" : tplName;
  const tpl = templates[tplSlug];
  if (tpl && !/blank|full-width/.test(tplSlug)) {
    const tplHtml = text(tpl["content:encoded"]);
    if (tplHtml.includes("wp:post-content") === false && tplHtml.replace(/<!--[\s\S]*?-->/g, "").trim().length > 500) {
      html = tplHtml;
      fromTemplate = true;
    }
  }
  if (link === "/yhteystiedot/") {
    // Yhteystiedot-sivu on rakennettu uuteen teemaan omana sivunaan; tallennetaan silti teksti.
  }
  const body = toMarkdown(html, title, { template: fromTemplate });
  const firstH1 = cheerio.load(html)("h1").first().text().trim();
  const seoTitle = resolveSeoTitle(m._aioseo_title, title);
  const description = decode(m._aioseo_description || "") || excerptFrom(body);
  const fm = [
    "---",
    `title: ${yamlStr(title)}`,
    firstH1 ? `heading: ${yamlStr(firstH1.replace(/\s+/g, " "))}` : null,
    `path: ${yamlStr(link)}`,
    `updated: ${text(it["wp:post_modified"]).replace(" ", "T")}`,
    seoTitle ? `seoTitle: ${yamlStr(seoTitle)}` : null,
    `description: ${yamlStr(description)}`,
    `wpId: ${text(it["wp:post_id"])}`,
    "---",
    "",
  ]
    .filter((l) => l !== null)
    .join("\n");
  const file = link.replace(/^\/|\/$/g, "").replace(/\//g, "__") || "index";
  fs.writeFileSync(path.join(outPages, `${file}.md`), fm + body + "\n");
  if (body.length < 200) report.notes.push(`Sivulla lähes tyhjä sisältö vanhalla sivustolla: ${link}`);
}

// ---------- arkistot ----------
const catsOut = Object.entries(catNames)
  .filter(([k]) => catUse.has(k))
  .map(([slug, name]) => ({ slug, name }));
const tagsOut = Object.entries(tagNames)
  .filter(([k]) => tagUse.has(k))
  .map(([slug, name]) => ({ slug, name }));
for (const c of catsOut) urls.push({ path: `/category/${c.slug}/`, type: "category", title: c.name });
for (const t of tagsOut) urls.push({ path: `/tag/${t.slug}/`, type: "tag", title: t.name });

// ---------- tarkista sisäiset linkit ----------
const known = new Set([...urls.map((u) => u.path), ...NEW_PATHS]);
for (const dir of [outPosts, outPages]) {
  for (const f of fs.readdirSync(dir)) {
    const md = fs.readFileSync(path.join(dir, f), "utf8");
    for (const [, href] of md.matchAll(/\]\((\/[^)\s#?]*)[^)]*\)|href="(\/[^"#?]*)/g)) {
      if (href && !known.has(href) && !href.startsWith("/wp-content/")) {
        if (!report.brokenLinks.has(href)) report.brokenLinks.set(href, []);
        report.brokenLinks.get(href).push(f);
      }
    }
  }
}

fs.mkdirSync(path.join(ROOT, "src/data"), { recursive: true });
fs.writeFileSync(
  path.join(ROOT, "src/data/wp-urls.json"),
  JSON.stringify(urls.sort((a, b) => a.path.localeCompare(b.path, "fi")), null, 2) + "\n"
);
fs.writeFileSync(
  path.join(ROOT, "src/data/taxonomies.json"),
  JSON.stringify({ categories: catsOut, tags: tagsOut }, null, 2) + "\n"
);
fs.writeFileSync(path.join(ROOT, "src/data/wp-images.json"), JSON.stringify([...report.images].sort(), null, 2) + "\n");

console.log(`Artikkeleita: ${byType("post").length}, sivuja: ${byType("page").length}`);
console.log(`Kategorioita: ${catsOut.length}, tageja: ${tagsOut.length}, osoitteita yhteensä: ${urls.length}`);
console.log(`Kuvia: ${report.images.size}`);
for (const n of report.notes) console.log("HUOM: " + n);
for (const [href, files] of report.brokenLinks) console.log(`Rikkinäinen sisäinen linkki ${href}  (${[...new Set(files)].join(", ")})`);
