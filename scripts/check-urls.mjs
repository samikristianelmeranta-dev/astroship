#!/usr/bin/env node
/**
 * Tarkistaa, että jokainen vanhan WordPress-sivuston osoite löytyy uudesta buildista.
 * Aja `pnpm build` ensin.  Käyttö: node scripts/check-urls.mjs
 */
import fs from "node:fs";
import path from "node:path";

const root = path.resolve(path.dirname(new URL(import.meta.url).pathname), "..");
const urls = JSON.parse(fs.readFileSync(path.join(root, "src/data/wp-urls.json"), "utf8"));
const dist = path.join(root, "dist");

let missing = 0;
for (const u of urls) {
  const file = path.join(dist, u.path, "index.html");
  if (!fs.existsSync(file)) {
    missing++;
    console.log(`PUUTTUU  ${u.path}  (${u.type}: ${u.title})`);
  }
}
console.log(`\n${urls.length - missing}/${urls.length} vanhaa osoitetta löytyy uudesta sivustosta.`);
process.exit(missing ? 1 : 0);
