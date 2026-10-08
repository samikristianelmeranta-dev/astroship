// Tekee kyltit.html:n jokaisesta kyltistä painovalmiin PDF:n (1:1, vektori): node build.mjs [kohdekansio]
import { chromium } from 'playwright';
import { fileURLToPath } from 'url';
import path from 'path';
import fs from 'fs';
const dir = path.dirname(fileURLToPath(import.meta.url));
const out = process.argv[2] || path.join(dir, '..', 'dist', 'kyltit');
fs.mkdirSync(out, { recursive: true });
const b = await chromium.launch();
const p = await b.newPage();
await p.goto('file://' + path.join(dir, 'kyltit.html'), { waitUntil: 'networkidle' });
await p.evaluate(() => document.fonts.ready);
const ids = await p.$$eval('section.sign', s => s.map(x => x.id));
for (const id of ids) {
  const info = await p.evaluate(id => {
    document.body.classList.add('print');
    document.querySelectorAll('section.sign').forEach(s => { s.style.display = s.id === id ? '' : 'none'; });
    const el = document.getElementById(id);
    const box = el.getBoundingClientRect();
    // Tarkistus: mikään teksti ei saa ylittää kyltin reunaa (5 mm turvamarginaali)
    const mm = box.width / parseFloat(el.style.width);
    const bad = [...el.querySelectorAll('.name,.band,.phone,.small,.kicker,.list li,.mark')].filter(n => {
      const r = n.getBoundingClientRect();
      const full = n.classList.contains('band') && r.width >= box.width - 1;
      return !full && (r.left < box.left + 5 * mm || r.right > box.right - 5 * mm || r.top < box.top || r.bottom > box.bottom);
    }).map(n => n.className + ':' + n.textContent.trim().slice(0, 20));
    return { w: el.style.width, h: el.style.height, bad };
  }, id);
  await p.pdf({ path: path.join(out, id + '.pdf'), width: info.w, height: info.h, printBackground: true, pageRanges: '1' });
  console.log(id, info.w, info.h, info.bad.length ? 'YLITYS: ' + info.bad.join(', ') : 'ok');
}
await b.close();
