// Renderöi some.html:n jokaisen <section class="ab"> PNG-kuvaksi: node build.mjs [kohdekansio]
import { chromium } from 'playwright';
import { fileURLToPath } from 'url';
import path from 'path';
import fs from 'fs';
const dir = path.dirname(fileURLToPath(import.meta.url));
const out = process.argv[2] || path.join(dir, '..', 'dist', 'some');
fs.mkdirSync(out, { recursive: true });
const b = await chromium.launch();
const p = await b.newPage({ viewport: { width: 1800, height: 1200 } });
await p.goto('file://' + path.join(dir, 'some.html'), { waitUntil: 'networkidle' });
await p.evaluate(() => document.fonts.ready);
const only = process.argv[3] ? process.argv[3].split(',') : null;
for (const [id, clear] of await p.$$eval('section.ab', s => s.map(x => [x.id, x.classList.contains('lapinakyva')]))) {
  if (only && !only.some(o => id.startsWith(o))) continue;
  await p.evaluate(c => { document.body.style.background = c ? 'transparent' : ''; }, clear);
  await (await p.$('#' + id)).screenshot({ path: path.join(out, id + '.png'), omitBackground: clear });
  console.log(id);
}
await b.close();
