/**
 * Renderöi pipomerkit: PNG 300 dpi läpinäkyvällä taustalla + PDF painoon.
 * Käyttö: node mainokset/render-pipo.cjs <playwright-polku> <tulostuskansio>
 */
const path = require("path");
const fs = require("fs");
const { chromium } = require(process.argv[2] || "playwright");

const out = path.resolve(process.argv[3] || "downloads/mainokset/pipo");
const page = "file://" + path.resolve(__dirname, "pipo.html");
const MM = 96 / 25.4; // CSS-pikseliä millimetrissä
const DPI_SCALE = 300 / 96;

const badges = [
  { file: "pipomerkki-vaaka-100x50mm", w: 100, h: 50, layout: "vaaka" },
  { file: "pipomerkki-pyorea-70mm", w: 70, h: 70, layout: "pyorea" },
];

(async () => {
  fs.mkdirSync(out, { recursive: true });
  const browser = await chromium.launch();
  for (const f of badges) {
    const w = Math.round(f.w * MM), h = Math.round(f.h * MM);
    const p = await browser.newPage({ viewport: { width: w, height: h }, deviceScaleFactor: DPI_SCALE });
    await p.goto(`${page}?w=${w}&h=${h}&layout=${f.layout}`, { waitUntil: "networkidle" });
    await p.evaluate(() => document.fonts.ready);
    await p.screenshot({ path: path.join(out, f.file + ".png"), omitBackground: true });
    await p.pdf({ path: path.join(out, f.file + ".pdf"), width: f.w + "mm", height: f.h + "mm", printBackground: true, pageRanges: "1" });
    await p.close();
    console.log(f.file);
  }
  await browser.close();
})();
