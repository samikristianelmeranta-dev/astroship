// Tekee mainokset.html:n jokaisesta mainoksesta painovalmiin vektori-PDF:n (1:1, 3 mm leikkuuvara)
// ja esikatselukuvan: node build.mjs [kohdekansio]
import { chromium } from 'playwright';
import { fileURLToPath } from 'url';
import path from 'path';
import fs from 'fs';
const dir = path.dirname(fileURLToPath(import.meta.url));
const out = process.argv[2] || path.join(dir, '..', 'dist', 'mainokset');
fs.mkdirSync(out, { recursive: true });
const b = await chromium.launch();
const p = await b.newPage({ viewport: { width: 4000, height: 8000 } });
await p.goto('file://' + path.join(dir, 'mainokset.html'), { waitUntil: 'networkidle' });
await p.evaluate(() => document.fonts.ready);
const ids = await p.$$eval('section.ad', s => s.map(x => x.id));
for (const id of ids) {
  const info = await p.evaluate(id => {
    document.body.classList.add('print');
    document.querySelectorAll('section.ad').forEach(s => { s.style.display = s.id === id ? '' : 'none'; });
    const el = document.getElementById(id);
    const box = el.getBoundingClientRect();
    // Tarkistus: teksti ei saa mennä 3 mm leikkuuvaran + 10 mm turva-alueen yli eikä toisen elementin päälle
    const mm = box.width / parseFloat(el.style.width);
    const safe = 13 * mm;
    const nodes = [...el.querySelectorAll('.logo,.kicker,h1,.lead,.list li,.phone,.web,.qr,.scene,.band')];
    const bad = nodes.filter(n => {
      const r = n.getBoundingClientRect();
      if (n.classList.contains('band')) return r.bottom > box.bottom - safe && r.top < box.bottom - safe ? false : false;
      return r.left < box.left + safe || r.right > box.right - safe || r.top < box.top + safe || r.bottom > box.bottom - safe;
    }).map(n => (n.className.baseVal ?? n.className) + ':' + n.textContent.trim().slice(0, 24));
    const text = [...el.querySelectorAll('h1,.lead,.list li,.phone,.web')];
    for (let i = 0; i < text.length; i++) for (let j = i + 1; j < text.length; j++) {
      const a = text[i].getBoundingClientRect(), c = text[j].getBoundingClientRect();
      if (text[i].contains(text[j]) || text[j].contains(text[i])) continue;
      if (a.left < c.right && c.left < a.right && a.top < c.bottom && c.top < a.bottom) bad.push('PÄÄLLEKKÄIN: ' + text[i].textContent.trim().slice(0, 15) + ' / ' + text[j].textContent.trim().slice(0, 15));
    }
    return { w: el.style.width, h: el.style.height, bad };
  }, id);
  await p.pdf({ path: path.join(out, id + '.pdf'), width: info.w, height: info.h, printBackground: true, pageRanges: '1' });
  await p.locator('#' + id).screenshot({ path: path.join(out, id + '-esikatselu.png') });
  console.log(id, info.w, info.h, info.bad.length ? info.bad.join(' | ') : 'ok');
}
await b.close();
