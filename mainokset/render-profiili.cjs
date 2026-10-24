/**
 * Renderöi profiilikuvat PNG:nä.
 * Käyttö: node mainokset/render-profiili.cjs <playwright-polku> <tulostuskansio>
 */
const path = require("path");
const fs = require("fs");
const { chromium } = require(process.argv[2] || "playwright");

const out = path.resolve(process.argv[3] || "downloads/mainokset/profiilikuva");
const page = "file://" + path.resolve(__dirname, "profiili.html");

const variants = [
  { file: "profiilikuva-1080x1080", v: 1 },
  { file: "profiilikuva-liike-nyt-1080x1080", v: 2 },
];

(async () => {
  fs.mkdirSync(out, { recursive: true });
  const browser = await chromium.launch();
  for (const f of variants) {
    const p = await browser.newPage({ viewport: { width: 1080, height: 1080 } });
    await p.goto(`${page}?v=${f.v}`, { waitUntil: "networkidle" });
    await p.evaluate(() => document.fonts.ready);
    await p.screenshot({ path: path.join(out, f.file + ".png") });
    await p.close();
    console.log(f.file);
  }
  await browser.close();
})();
