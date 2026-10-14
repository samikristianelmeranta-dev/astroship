// Logomainos: 300 x 300 mm PDF (vektori, 3 mm leikkuuvara) + PNG-kuvat: node build-logomainos.mjs [kohdekansio]
import { chromium } from 'playwright';
import { fileURLToPath } from 'url';
import path from 'path';
import fs from 'fs';
const dir = path.dirname(fileURLToPath(import.meta.url));
const out = process.argv[2] || path.join(dir, '..', 'dist', 'logomainos');
fs.mkdirSync(out, { recursive: true });
const b = await chromium.launch();
const p = await b.newPage({ viewport: { width: 1400, height: 1600 } });
await p.goto('file://' + path.join(dir, 'logomainos.html'), { waitUntil: 'networkidle' });
await p.evaluate(() => document.fonts.ready);
for (const id of ['logomainos-300x300mm', 'logomainos-1080x1080']) {
  const info = await p.evaluate(id => {
    document.body.classList.add('print');
    document.querySelectorAll('section.ad').forEach(s => { s.style.display = s.id === id ? '' : 'none'; });
    const el = document.getElementById(id), box = el.getBoundingClientRect();
    const bad = [...el.querySelectorAll('.mark,.name,.band,.tel,.small')].filter(n => {
      const r = n.getBoundingClientRect(); return r.right > box.right - 8 || r.bottom > box.bottom - 4 || r.left < box.left + 8;
    }).map(n => n.className || n.tagName);
    return { w: el.style.width, h: el.style.height, bad };
  }, id);
  if (id === 'logomainos-300x300mm') await p.pdf({ path: path.join(out, id + '.pdf'), width: info.w, height: info.h, printBackground: true, pageRanges: '1' });
  await p.locator('#' + id).screenshot({ path: path.join(out, id + '.png') });
  console.log(id, info.w, info.h, info.bad.length ? 'YLITYS: ' + info.bad.join(', ') : 'ok');
}
await b.close();
