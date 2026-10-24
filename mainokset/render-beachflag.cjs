/**
 * Renderöi beach flagin 600x2000 mm: PDF painoon (3 mm leikkuuvarat) + esikatselukuva.
 * Käyttö: node mainokset/render-beachflag.cjs <playwright-polku> <tulostuskansio>
 */
const path = require("path");
const fs = require("fs");
const { chromium } = require(process.argv[2] || "playwright");

const out = path.resolve(process.argv[3] || "downloads/mainokset/paino");
const page = "file://" + path.resolve(__dirname, "beachflag.html");
const MM = 96 / 25.4; // CSS-pikseliä millimetrissä
const BLEED = 3; // leikkuuvara mm
const file = "paino-beachflag-600x2000mm";

(async () => {
  fs.mkdirSync(out, { recursive: true });
  const wmm = 600 + 2 * BLEED, hmm = 2000 + 2 * BLEED;
  const w = Math.round(wmm * MM), h = Math.round(hmm * MM);
  const browser = await chromium.launch();
  const p = await browser.newPage({ viewport: { width: w, height: h } });
  await p.goto(`${page}?w=${w}&h=${h}`, { waitUntil: "networkidle" });
  await p.evaluate(() => document.fonts.ready);
  await p.pdf({ path: path.join(out, file + ".pdf"), width: wmm + "mm", height: hmm + "mm", printBackground: true, pageRanges: "1" });
  await p.screenshot({ path: path.join(out, file + "-esikatselu.png") });
  await browser.close();
  console.log(file);
})();
