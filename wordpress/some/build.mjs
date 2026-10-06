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
for (const id of await p.$$eval('section.ab', s => s.map(x => x.id))) {
  await (await p.$('#' + id)).screenshot({ path: path.join(out, id + '.png') });
  console.log(id);
}
await b.close();
