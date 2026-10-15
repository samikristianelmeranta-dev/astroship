// PostgreSQL-tallennuksen testit. Ajetaan vain, kun PG_TEST_URL on asetettu, esim.
// PG_TEST_URL=postgres://postgres@localhost:5432/valittaja_test npm test
import { test, before, after } from 'node:test';
import assert from 'node:assert/strict';
import { createServer } from '../server.js';
import { validRequest, agent, TINY_JPEG } from './fixtures.js';

const url = process.env.PG_TEST_URL;
let storage;

before(async () => {
  if (!url) return;
  const { default: pg } = await import('pg');
  const pool = new pg.Pool({ connectionString: url });
  await pool.query('DROP TABLE IF EXISTS docs, photo_files');
  await pool.end();
  const { pgStorage } = await import('../src/node/pg-store.js');
  storage = await pgStorage(url);
});

after(async () => storage?.close());

test('PostgreSQL: lisäys, päivitys, upsert, poisto ja järjestys', { skip: !url }, async () => {
  const s = storage.stores.templates;
  const a = await s.insert({ name: 'a' });
  const b = await s.insert({ name: 'b' });
  assert.deepEqual((await s.all()).map((r) => r.name), ['a', 'b']);
  assert.equal((await s.update(a.id, (r) => (r.name === 'a' ? { name: 'A' } : null))).name, 'A');
  assert.equal(await s.update(a.id, (r) => (r.name === 'a' ? { name: 'x' } : null)), null, 'ehto ei täyty');
  assert.equal((await s.upsert((r) => r.name === 'b', { name: 'b', extra: 1 })).created, false);
  assert.equal((await s.upsert((r) => r.name === 'c', { name: 'c' })).created, true);
  assert.equal(await s.remove(b.id), true);
  assert.deepEqual((await s.all()).map((r) => r.name), ['A', 'c']);
});

test('PostgreSQL: samanaikaiset ehdolliset päivitykset eivät hukkaa muutoksia', { skip: !url }, async () => {
  const s = storage.stores.events;
  const row = await s.insert({ count: 0 });
  await Promise.all(Array.from({ length: 25 }, () => s.update(row.id, (r) => ({ count: r.count + 1 }))));
  assert.equal((await s.all()).find((r) => r.id === row.id).count, 25);
  // Vain yksi voi "lunastaa" saman rivin.
  const claims = await Promise.all(Array.from({ length: 10 }, () => s.update(row.id, (r) => (r.claimed ? null : { claimed: true }))));
  assert.equal(claims.filter(Boolean).length, 1);
});

test('PostgreSQL: kuvat ja koko palvelin', { skip: !url }, async () => {
  await storage.photoFiles.write('00000000-0000-4000-8000-000000000001', Buffer.from('kuva').toString('base64'));
  assert.equal(Buffer.from(await storage.photoFiles.read('00000000-0000-4000-8000-000000000001'), 'base64').toString(), 'kuva');
  const server = createServer({ storage, adminToken: 'x', messenger: null, businessLookup: null, config: { pilotRegions: null } });
  await new Promise((r) => server.listen(0, r));
  const base = `http://127.0.0.1:${server.address().port}`;
  const post = (p, b) => fetch(base + p, { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(b) });
  assert.equal((await post('/api/agents', agent(0))).status, 201);
  const photoIds = [];
  for (let i = 0; i < 3; i++) photoIds.push((await (await post('/api/photos', { dataUrl: TINY_JPEG })).json()).id);
  const res = await (await post('/api/requests', { ...validRequest, photoIds })).json();
  assert.ok(res.devLink);
  const verify = await (await post(`/api/projects/${res.devLink.split('#')[1]}/verify`, {})).json();
  assert.equal(verify.verified, true);
  server.close();
});
