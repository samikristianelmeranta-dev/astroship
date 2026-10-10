#!/usr/bin/env node
/**
 * Rakentaa palvelusivujen sivupohjat (templates/page-<slug>.html) WordPressistä tuodusta
 * sisällöstä (src/content/pages/*.md, ks. scripts/wp-import.mjs).
 *
 * Sivupohja = ylätunniste + tumma otsake (h1, ingressi, painikkeet) + sisältö
 * WordPressin omina lohkoina + sivupalkin yhteydenottokortti + yhteydenottokaista.
 *
 * Käyttö: node wordpress-theme/tools/build-page-templates.mjs
 */
import fs from "node:fs";
import path from "node:path";
import { marked } from "marked";
import * as cheerio from "cheerio";

const ROOT = path.resolve(path.dirname(new URL(import.meta.url).pathname), "../..");
const THEME = path.join(ROOT, "wordpress-theme/tilihub");

const PAGES = [
  { slug: "kirjanpitopalvelut", eyebrow: "Kirjanpitopalvelut · Turku & Varsinais-Suomi" },
  { slug: "palkanlaskenta", eyebrow: "Palkanlaskentapalvelut · Turku & Varsinais-Suomi" },
  { slug: "tilinpaatos", eyebrow: "Tilinpäätöspalvelut · Turku & Varsinais-Suomi" },
  { slug: "tietoa-meista", eyebrow: "Tietoa meistä · Perustettu 2025, Kaarina" },
];

// Sisältökorjaukset, jotka asiakas on vahvistanut.
const FIXES = [
  [
    /Kirjanpidon hinta on alkaen 55 €\/kk sisältäen ohjelmiston perusmaksun\. Hintaan lisätään voimassa oleva arvonlisävero 25,5 %\./,
    "Kirjanpidon hinta on alkaen 55 € + alv kuukaudessa. Tarkemmat hinnat löydät [hinnastosta](/kirjanpidon-hinnat/).",
  ],
];

const AREAS = /^(Turku|Kaarina|Raisio|Naantali|Lieto|Varsinais-Suomi|Koko Suomi \(digitaalisesti\)|\s)+$/;

const esc = (s) => s.replace(/&(?![a-z#0-9]+;)/gi, "&amp;");
const attrs = (o) => (o && Object.keys(o).length ? " " + JSON.stringify(o) : "");
const block = (name, inner, a) => `<!-- wp:${name}${attrs(a)} -->\n${inner}\n<!-- /wp:${name} -->`;

function paragraph(html, a) {
  return block("paragraph", `<p${a?.className ? ` class="${a.className}"` : ""}>${html}</p>`, a);
}

function buttons(links, outline = false) {
  const inner = links
    .map(({ href, text }, i) => {
      const o = outline || i > 0;
      return block(
        "button",
        `<div class="wp-block-button${o ? " is-style-outline" : ""}"><a class="wp-block-button__link wp-element-button" href="${href}">${text}</a></div>`,
        o ? { className: "is-style-outline" } : undefined
      );
    })
    .join("\n\n");
  return block("buttons", `<div class="wp-block-buttons">${inner}</div>`);
}

function list($, el, ordered, className) {
  const items = $(el)
    .children("li")
    .map((_, li) => block("list-item", `<li>${$(li).html().trim()}</li>`))
    .get()
    .join("\n\n");
  const tag = ordered ? "ol" : "ul";
  const cls = ["wp-block-list", className].filter(Boolean).join(" ");
  const a = {};
  if (ordered) a.ordered = true;
  if (className) a.className = className;
  return block("list", `<${tag} class="${cls}">${items}</${tag}>`, a);
}

/** Muuntaa kappaleen, jossa on pelkkiä linkkejä, painikkeiksi. */
function linkOnly($, el) {
  const $el = $(el);
  const links = $el.find("a");
  if (!links.length) return null;
  const rest = $el.clone();
  rest.find("a").remove();
  if (rest.text().replace(/[·|\s]/g, "") !== "") return null;
  return links.map((_, a) => ({ href: $(a).attr("href"), text: $(a).text().trim() })).get();
}

function convert(html) {
  const $ = cheerio.load(`<div id="r">${html}</div>`, null, false);
  const out = [];
  const nodes = $("#r").children().toArray();
  for (let i = 0; i < nodes.length; i++) {
    const el = nodes[i];
    const tag = el.tagName.toLowerCase();
    const $el = $(el);
    if (/^h[2-6]$/.test(tag)) {
      const level = Number(tag[1]);
      out.push(block("heading", `<h${level} class="wp-block-heading">${$el.html()}</h${level}>`, level === 2 ? undefined : { level }));
    } else if (tag === "p") {
      const text = $el.text().trim();
      if (AREAS.test(text)) {
        const items = text.match(/Koko Suomi \(digitaalisesti\)|[A-ZÅÄÖ][\wåäö-]+(?:-[A-ZÅÄÖ][\wåäö]+)?/g);
        out.push(list($, cheerio.load(`<ul>${items.map((t) => `<li>${t}</li>`).join("")}</ul>`)("ul")[0], false, "th-chips"));
        continue;
      }
      const links = linkOnly($, el);
      if (links) {
        out.push(buttons(links));
        continue;
      }
      if ($el.children("img").length === 1 && text === "") {
        const img = $el.children("img");
        out.push(block("image", `<figure class="wp-block-image"><img src="${img.attr("src")}" alt="${img.attr("alt") || ""}"/></figure>`));
        continue;
      }
      out.push(paragraph(esc($el.html().trim())));
    } else if (tag === "ul" || tag === "ol") {
      out.push(list($, el, tag === "ol"));
    } else if (tag === "blockquote") {
      let cite = "";
      const next = nodes[i + 1];
      if (next && next.tagName === "p" && $(next).children().first().is("strong")) {
        cite = `<cite>${$(next).html()}</cite>`;
        i++;
      }
      const inner = $el
        .children()
        .map((_, c) => paragraph($(c).html()))
        .get()
        .join("\n\n");
      out.push(block("quote", `<blockquote class="wp-block-quote">${inner}${cite}</blockquote>`));
    } else if (tag === "table") {
      out.push(block("table", `<figure class="wp-block-table"><table>${$el.html()}</table></figure>`, { hasFixedLayout: false }));
    } else if (tag === "details") {
      const summary = $el.children("summary").first().html();
      $el.children("summary").remove();
      const body = convert($el.html());
      out.push(block("details", `<details class="wp-block-details"><summary>${summary}</summary>${body}</details>`));
    } else if (tag === "hr") {
      out.push(block("separator", `<hr class="wp-block-separator has-alpha-channel-opacity"/>`));
    } else {
      out.push(block("html", $.html(el)));
    }
  }
  return out.join("\n\n");
}

function prepare(md) {
  for (const [re, to] of FIXES) md = md.replace(re, to);
  // Sivun lopun puhelin-/WhatsApp-laatikko ja vanha CTA korvautuvat teeman omilla.
  const lines = md.split("\n");
  const phoneAt = lines.findIndex((l) => /^Puhelin \[/.test(l));
  if (phoneAt > 0) {
    let cut = phoneAt;
    for (let j = phoneAt - 1; j >= 0; j--) {
      if (/^#{2,4} /.test(lines[j])) {
        cut = j;
        break;
      }
    }
    md = lines.slice(0, cut).join("\n");
  }
  return md.trim();
}

const HEADER = '<!-- wp:template-part {"slug":"header","area":"header","tagName":"header"} /-->';
const FOOTER = '<!-- wp:template-part {"slug":"footer","area":"footer","tagName":"div"} /-->';

for (const page of PAGES) {
  const file = fs.readFileSync(path.join(ROOT, "src/content/pages", `${page.slug}.md`), "utf8");
  const [, fm, body] = file.match(/^---\n([\s\S]*?)\n---\n([\s\S]*)$/);
  const heading = (fm.match(/^heading: (.*)$/m) || fm.match(/^title: (.*)$/m))[1];
  const h1 = JSON.parse(heading);

  const html = marked.parse(prepare(body));
  const $ = cheerio.load(`<div id="r">${html}</div>`, null, false);
  const kids = $("#r").children().toArray();

  // Ensimmäinen kappale on ingressi, sitä seuraava pelkkä linkkirivi otsakkeen painikkeet.
  let lead = "";
  let heroButtons = [];
  if (kids[0]?.tagName === "p" && !linkOnly($, kids[0])) {
    lead = $(kids[0]).html();
    $(kids[0]).remove();
    const second = $("#r").children().first()[0];
    const links = second?.tagName === "p" ? linkOnly($, second) : null;
    if (links) {
      heroButtons = links;
      $(second).remove();
    }
  }
  const content = convert($("#r").html());

  const hero = `<!-- wp:group {"tagName":"header","align":"full","className":"th-page-hero th-dark","style":{"spacing":{"padding":{"top":"var:preset|spacing|60","bottom":"var:preset|spacing|60"}}},"backgroundColor":"hero","textColor":"hero-ink","layout":{"type":"constrained"}} -->
<header class="wp-block-group alignfull th-page-hero th-dark has-hero-ink-color has-hero-background-color has-text-color has-background" style="padding-top:var(--wp--preset--spacing--60);padding-bottom:var(--wp--preset--spacing--60)"><!-- wp:group {"align":"wide","layout":{"type":"default"}} -->
<div class="wp-block-group alignwide">${paragraph(esc(page.eyebrow), { className: "th-eyebrow" })}

${block("heading", `<h1 class="wp-block-heading">${esc(h1)}</h1>`, { level: 1 })}
${lead ? "\n" + paragraph(esc(lead), { className: "th-lead" }) : ""}
${heroButtons.length ? "\n" + buttons(heroButtons) : ""}</div>
<!-- /wp:group --></header>
<!-- /wp:group -->`;

  const main = `<!-- wp:group {"tagName":"main","align":"full","style":{"spacing":{"padding":{"top":"var:preset|spacing|60","bottom":"var:preset|spacing|60"}}},"layout":{"type":"constrained"}} -->
<main class="wp-block-group alignfull" style="padding-top:var(--wp--preset--spacing--60);padding-bottom:var(--wp--preset--spacing--60)"><!-- wp:columns {"align":"wide","style":{"spacing":{"blockGap":{"left":"clamp(2rem, 5vw, 4.5rem)"}}}} -->
<div class="wp-block-columns alignwide"><!-- wp:column {"width":"70%"} -->
<div class="wp-block-column" style="flex-basis:70%"><!-- wp:group {"className":"th-prose","layout":{"type":"default"}} -->
<div class="wp-block-group th-prose">${content}</div>
<!-- /wp:group --></div>
<!-- /wp:column -->

<!-- wp:column {"width":"30%","className":"th-aside-col"} -->
<div class="wp-block-column th-aside-col" style="flex-basis:30%"><!-- wp:pattern {"slug":"tilihub/aside-contact"} /--></div>
<!-- /wp:column --></div>
<!-- /wp:columns --></main>
<!-- /wp:group -->`;

  const tpl = [HEADER, hero, main, '<!-- wp:pattern {"slug":"tilihub/cta-band"} /-->', FOOTER].join("\n\n") + "\n";
  fs.writeFileSync(path.join(THEME, "templates", `page-${page.slug}.html`), tpl);
  console.log(`templates/page-${page.slug}.html`);
}
