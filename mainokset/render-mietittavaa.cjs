/**
 * Renderöi "Tässä vähän mietittävää?" -somemainokset PNG:nä.
 * Käyttö: node mainokset/render-mietittavaa.cjs <playwright-polku> <tulostuskansio>
 */
const path = require("path");
const fs = require("fs");
const { chromium } = require(process.argv[2] || "playwright");

const out = path.resolve(process.argv[3] || "downloads/mainokset/mietittavaa");
const page = "file://" + path.resolve(__dirname, "mietittavaa.html");
const ads = [
  { file: "mietittavaa-facebook-pysty-1080x1350", w: 1080, h: 1350, layout: "portrait" },
  { file: "mietittavaa-facebook-neliö-1080x1080", w: 1080, h: 1080, layout: "square" },
  { file: "mietittavaa-facebook-linkki-1200x630", w: 1200, h: 630, layout: "wide" },
];

(async () => {
  fs.mkdirSync(out, { recursive: true });
  const browser = await chromium.launch();
  for (const f of ads) {
    const p = await browser.newPage({ viewport: { width: f.w, height: f.h } });
    await p.goto(`${page}?w=${f.w}&h=${f.h}&layout=${f.layout}`, { waitUntil: "networkidle" });
    await p.evaluate(() => document.fonts.ready);
    await p.screenshot({ path: path.join(out, f.file + ".png") });
    await p.close();
    console.log(f.file);
  }
  await browser.close();
})();
