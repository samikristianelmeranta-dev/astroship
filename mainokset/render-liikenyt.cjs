/**
 * Renderöi Liike Nyt -mainokset: some-kuvat PNG:nä ja A4-juliste PDF:nä.
 * Käyttö: node mainokset/render-liikenyt.cjs <playwright-polku> <tulostuskansio>
 */
const path = require("path");
const fs = require("fs");
const { chromium } = require(process.argv[2] || "playwright");

const out = path.resolve(process.argv[3] || "downloads/mainokset/liike-nyt");
const page = "file://" + path.resolve(__dirname, "liikenyt.html");
const MM = 96 / 25.4; // CSS-pikseliä millimetrissä
const BLEED = 3; // leikkuuvara mm

const social = [
  { file: "liikenyt-neliö-1080x1080", w: 1080, h: 1080, layout: "square" },
  { file: "liikenyt-pysty-1080x1350", w: 1080, h: 1350, layout: "portrait" },
  { file: "liikenyt-story-1080x1920", w: 1080, h: 1920, layout: "tall" },
  { file: "liikenyt-jako-1200x630", w: 1200, h: 630, layout: "wide" },
];

(async () => {
  fs.mkdirSync(out, { recursive: true });
  const browser = await chromium.launch();
  for (const f of social) {
    const p = await browser.newPage({ viewport: { width: f.w, height: f.h } });
    await p.goto(`${page}?w=${f.w}&h=${f.h}&layout=${f.layout}`, { waitUntil: "networkidle" });
    await p.evaluate(() => document.fonts.ready);
    await p.screenshot({ path: path.join(out, f.file + ".png") });
    await p.close();
    console.log(f.file);
  }
  // A4-juliste leikkuuvaroineen
  const wmm = 210 + 2 * BLEED, hmm = 297 + 2 * BLEED;
  const w = Math.round(wmm * MM), h = Math.round(hmm * MM);
  const p = await browser.newPage({ viewport: { width: w, height: h }, deviceScaleFactor: 1400 / h });
  await p.goto(`${page}?w=${w}&h=${h}&layout=portrait&payer=1`, { waitUntil: "networkidle" });
  await p.evaluate(() => document.fonts.ready);
  await p.pdf({ path: path.join(out, "liikenyt-juliste-A4-210x297mm.pdf"), width: wmm + "mm", height: hmm + "mm", printBackground: true, pageRanges: "1" });
  await p.screenshot({ path: path.join(out, "liikenyt-juliste-A4-210x297mm-esikatselu.png") });
  console.log("liikenyt-juliste-A4");
  await browser.close();
})();
