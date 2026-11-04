/**
 * Renderöi flyerit: PDF painoon (3 mm leikkuuvarat) + esikatselukuva.
 * Käyttö: node mainokset/render-flyer.cjs <playwright-polku> <tulostuskansio>
 */
const path = require("path");
const fs = require("fs");
const { chromium } = require(process.argv[2] || "playwright");

const out = path.resolve(process.argv[3] || "downloads/mainokset/flyerit");
const page = "file://" + path.resolve(__dirname, "flyer.html");
const icons = JSON.parse(fs.readFileSync(path.join(__dirname, "assets/icons.json"), "utf8"));
const MM = 96 / 25.4; // CSS-pikseliä millimetrissä
const BLEED = 3; // leikkuuvara mm

const flyers = [
  { file: "flyer-A4-210x297mm", w: 210, h: 297 },
  { file: "flyer-A5-148x210mm", w: 148, h: 210 },
  { file: "flyer-148x200mm", w: 148, h: 200, layout: "short" },
  { file: "flyer-A6-105x148mm", w: 105, h: 148, layout: "compact" },
  { file: "flyer-DL-99x210mm", w: 99, h: 210, layout: "dl" },
];

(async () => {
  fs.mkdirSync(out, { recursive: true });
  const browser = await chromium.launch();
  for (const f of flyers) {
    const wmm = f.w + 2 * BLEED, hmm = f.h + 2 * BLEED;
    // Esikatselu ja PDF samasta näkymästä; skaalaus pitää pienetkin koot tarkkoina.
    const scale = Math.max(1, 1400 / (hmm * MM));
    const w = Math.round(wmm * MM), h = Math.round(hmm * MM);
    const p = await browser.newPage({ viewport: { width: w, height: h }, deviceScaleFactor: scale });
    await p.addInitScript((i) => { window.__icons = i; }, icons);
    await p.goto(`${page}?w=${w}&h=${h}${f.layout ? "&layout=" + f.layout : ""}`, { waitUntil: "networkidle" });
    await p.evaluate(() => document.fonts.ready);
    await p.pdf({ path: path.join(out, f.file + ".pdf"), width: wmm + "mm", height: hmm + "mm", printBackground: true, pageRanges: "1" });
    await p.screenshot({ path: path.join(out, f.file + "-esikatselu.png") });
    await p.close();
    console.log(f.file);
  }
  await browser.close();
})();
