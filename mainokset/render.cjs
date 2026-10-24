/**
 * Renderöi mainokset: some-kuvat PNG:nä ja painotuotteet PDF:nä.
 * Käyttö: node mainokset/render.cjs <playwright-polku> <tulostuskansio>
 */
const path = require("path");
const fs = require("fs");
const { chromium } = require(process.argv[2] || "playwright");

const out = path.resolve(process.argv[3] || "downloads/mainokset");
const page = "file://" + path.resolve(__dirname, "mainos.html");
const MM = 96 / 25.4; // CSS-pikseliä millimetrissä
const BLEED = 3; // leikkuuvara mm

const social = [
  { file: "some-instagram-neliö-1080x1080", w: 1080, h: 1080, layout: "portrait", extra: "square" },
  { file: "some-instagram-facebook-pysty-1080x1350", w: 1080, h: 1350, layout: "portrait" },
  { file: "some-story-reels-1080x1920", w: 1080, h: 1920, layout: "tall" },
  { file: "some-facebook-linkedin-jako-1200x630", w: 1200, h: 630, layout: "wide", short: 1 },
  { file: "some-facebook-kansikuva-1640x624", w: 1640, h: 624, layout: "wide", extra: "fbcover", short: 1 },
  { file: "some-x-twitter-header-1500x500", w: 1500, h: 500, layout: "wide", extra: "slim", short: 1 },
  { file: "some-linkedin-banneri-1584x396", w: 1584, h: 396, layout: "wide", extra: "slim", short: 1 },
];

const print = [
  { file: "paino-tienvarsikyltti-1200x800mm", w: 1200, h: 800, layout: "wide", extra: "sign", short: 1 },
  { file: "paino-juliste-A1-594x841mm", w: 594, h: 841, layout: "portrait", extra: "poster" },
  { file: "paino-rollup-850x2000mm", w: 850, h: 2000, layout: "tall" },
  { file: "paino-flyer-A5-148x210mm", w: 148, h: 210, layout: "portrait", extra: "poster" },
];

const url = (f, w, h, payer) => {
  const q = new URLSearchParams({ w: Math.round(w), h: Math.round(h), layout: f.layout });
  if (f.extra) q.set("extra", f.extra);
  if (f.short) q.set("short", "1");
  if (payer) q.set("payer", "1");
  return page + "?" + q;
};

(async () => {
  fs.mkdirSync(path.join(out, "some"), { recursive: true });
  fs.mkdirSync(path.join(out, "paino"), { recursive: true });
  const browser = await chromium.launch();

  for (const f of social) {
    const p = await browser.newPage({ viewport: { width: f.w, height: f.h } });
    await p.goto(url(f, f.w, f.h, false), { waitUntil: "networkidle" });
    await p.evaluate(() => document.fonts.ready);
    await p.screenshot({ path: path.join(out, "some", f.file + ".png") });
    await p.close();
    console.log("some", f.file);
  }

  for (const f of print) {
    // Koko leikkuuvaroineen
    const wmm = f.w + 2 * BLEED, hmm = f.h + 2 * BLEED;
    const wpx = wmm * MM, hpx = hmm * MM;
    const p = await browser.newPage({ viewport: { width: Math.round(wpx), height: Math.round(hpx) } });
    await p.goto(url(f, wpx, hpx, true), { waitUntil: "networkidle" });
    await p.evaluate(() => document.fonts.ready);
    await p.pdf({ path: path.join(out, "paino", f.file + ".pdf"), width: wmm + "mm", height: hmm + "mm", printBackground: true, pageRanges: "1" });
    // Esikatselukuva
    const scale = 1400 / Math.max(wpx, hpx);
    await p.setViewportSize({ width: Math.round(wpx), height: Math.round(hpx) });
    await p.screenshot({ path: path.join(out, "paino", f.file + "-esikatselu.png"), scale: "css", clip: { x: 0, y: 0, width: wpx, height: hpx } });
    await p.close();
    console.log("paino", f.file, scale.toFixed(2));
  }
  await browser.close();
})();
