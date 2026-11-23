/**
 * Renderöi kangasbannerin 305 x 180 cm: PDF painoon (3 mm leikkuuvarat) + esikatselukuva.
 * Käyttö: node mainokset/render-banneri.cjs <playwright-polku> <tulostuskansio>
 */
const path = require("path");
const fs = require("fs");
const { chromium } = require(process.argv[2] || "playwright");

const out = path.resolve(process.argv[3] || "downloads/mainokset/banneri");
const page = "file://" + path.resolve(__dirname, "banneri.html");
const MM = 96 / 25.4; // CSS-pikseliä millimetrissä
const BLEED = 3; // leikkuuvara mm
const file = "kangasbanneri-305x180cm";

(async () => {
  fs.mkdirSync(out, { recursive: true });
  const wmm = 3050 + 2 * BLEED, hmm = 1800 + 2 * BLEED;
  const w = Math.round(wmm * MM), h = Math.round(hmm * MM);
  const browser = await chromium.launch();
  const p = await browser.newPage({ viewport: { width: w, height: h } });
  await p.goto(`${page}?w=${w}&h=${h}`, { waitUntil: "networkidle" });
  await p.evaluate(() => document.fonts.ready);
  await p.pdf({ path: path.join(out, file + ".pdf"), width: wmm + "mm", height: hmm + "mm", printBackground: true, pageRanges: "1" });
  // Esikatselu pienemmästä näkymästä: täysikokoinen ylittää selaimen kuvakaappausrajan.
  const pw = 2400, ph = Math.round(pw * hmm / wmm);
  const pv = await browser.newPage({ viewport: { width: pw, height: ph } });
  await pv.goto(`${page}?w=${pw}&h=${ph}`, { waitUntil: "networkidle" });
  await pv.evaluate(() => document.fonts.ready);
  await pv.screenshot({ path: path.join(out, file + "-esikatselu.png") });
  await browser.close();
  console.log(file);
})();
