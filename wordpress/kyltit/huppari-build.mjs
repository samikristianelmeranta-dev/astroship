// Hupparipainatukset: PDF (vektori, läpinäkyvä) + PNG 300 dpi: node huppari-build.mjs [kohdekansio]
import { chromium } from 'playwright';
import { fileURLToPath } from 'url';
import path from 'path';
import fs from 'fs';
const dir = path.dirname(fileURLToPath(import.meta.url));
const out = process.argv[2] || path.join(dir, '..', 'dist', 'huppari');
fs.mkdirSync(out, { recursive: true });
const MM = 96 / 25.4;
const b = await chromium.launch();
const url = 'file://' + path.join(dir, 'huppari.html');
const p = await b.newPage({ viewport: { width: 2600, height: 2000 } });
await p.goto(url, { waitUntil: 'networkidle' });
await p.evaluate(() => document.fonts.ready);
await p.waitForTimeout(300);
await (await p.$('#huppari-mallikuva')).screenshot({ path: path.join(out, 'huppari-mallikuva.png') });
const ids = await p.$$eval('body > .print-item', s => s.map(x => x.id));
// PNG 300 dpi
const hi = await b.newPage({ viewport: { width: 2600, height: 2000 }, deviceScaleFactor: 300 / 96 });
await hi.goto(url, { waitUntil: 'networkidle' });
await hi.evaluate(() => { document.fonts.ready; document.body.style.background = 'transparent'; });
for (const id of ids) {
  await (await hi.$('#' + id)).screenshot({ path: path.join(out, id + '-300dpi.png'), omitBackground: true });
}
// PDF: yksi painatus per sivu, sivukoko = painatuksen koko
for (const id of ids) {
  const size = await p.evaluate(id => {
    document.body.classList.add('print');
    document.querySelectorAll('body > .print-item, .mock').forEach(s => { s.style.display = s.id === id ? '' : 'none'; });
    const r = document.getElementById(id).getBoundingClientRect();
    return { w: r.width, h: r.height };
  }, id);
  const w = Math.ceil(size.w / MM * 10) / 10, h = Math.ceil(size.h / MM * 10) / 10;
  await p.pdf({ path: path.join(out, id + '.pdf'), width: w + 'mm', height: h + 'mm', printBackground: true, omitBackground: true, pageRanges: '1' });
  console.log(id, w + ' x ' + h + ' mm');
}
await b.close();
