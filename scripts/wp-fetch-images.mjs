#!/usr/bin/env node
/**
 * Lataa sisällön kuvat vanhalta WordPress-sivustolta kansioon public/wp-content/uploads/,
 * jolloin myös kuvien osoitteet säilyvät ennallaan, ja vaihtaa sisällön kuvalinkit
 * suhteellisiksi (/wp-content/uploads/...).
 *
 * Aja kun vanha sivusto on vielä pystyssä:  node scripts/wp-fetch-images.mjs
 */
import fs from "node:fs";
import path from "node:path";

const root = path.resolve(path.dirname(new URL(import.meta.url).pathname), "..");
const images = JSON.parse(fs.readFileSync(path.join(root, "src/data/wp-images.json"), "utf8"));
const extra = [
  "https://www.tilihub.fi/wp-content/uploads/2026/06/jakke.jpg",
  "https://www.tilihub.fi/wp-content/uploads/2026/06/sami.webp",
  "https://www.tilihub.fi/wp-content/uploads/2025/08/tietosuojaseloste_tilihub.pdf",
];

let ok = 0;
for (const url of [...new Set([...images, ...extra])]) {
  const rel = new URL(url).pathname;
  const out = path.join(root, "public", rel);
  if (fs.existsSync(out)) {
    ok++;
    continue;
  }
  const res = await fetch(url);
  if (!res.ok) {
    console.log(`EPÄONNISTUI ${res.status} ${url}`);
    continue;
  }
  fs.mkdirSync(path.dirname(out), { recursive: true });
  fs.writeFileSync(out, Buffer.from(await res.arrayBuffer()));
  ok++;
}
console.log(`Ladattu ${ok} tiedostoa.`);

// Vaihda absoluuttiset kuvaosoitteet suhteellisiksi sisällössä ja komponenteissa.
const re = /https?:\/\/(www\.)?tilihub\.fi\/wp-content\/uploads\//g;
const walk = (d) =>
  fs.readdirSync(d, { withFileTypes: true }).flatMap((e) => (e.isDirectory() ? walk(path.join(d, e.name)) : [path.join(d, e.name)]));
for (const f of walk(path.join(root, "src")).filter((f) => /\.(md|astro|ts)$/.test(f))) {
  const s = fs.readFileSync(f, "utf8");
  if (s.search(re) !== -1) fs.writeFileSync(f, s.replace(re, "/wp-content/uploads/"));
}
console.log("Kuvalinkit päivitetty suhteellisiksi.");
