// Tulostaa esite.html:n A4-PDF:ksi: node build.mjs
import { chromium } from 'playwright';
import { fileURLToPath } from 'url';
import path from 'path';
const dir = path.dirname(fileURLToPath(import.meta.url));
const b = await chromium.launch();
const p = await b.newPage();
await p.goto('file://' + path.join(dir, 'esite.html'), { waitUntil: 'networkidle' });
await p.evaluate(() => document.fonts.ready);
await p.pdf({ path: path.join(dir, 'nordic-esite-2026.pdf'), format: 'A4', printBackground: true, preferCSSPageSize: true });
await b.close();
console.log('ok');
