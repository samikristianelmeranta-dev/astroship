// PostgreSQL-tallennus. Sama rajapinta kuin JsonStore: all, insert, update, upsert, remove.
// Rivit tallennetaan JSONB-dokumentteina; kirjoitukset lukitaan kokoelmakohtaisesti
// (pg_advisory_xact_lock), joten ehdolliset päivitykset ovat atomisia myös usealla palvelimella.
import { randomUUID } from 'node:crypto';
import { COLLECTIONS } from './storage.js';

const SCHEMA = `
CREATE TABLE IF NOT EXISTS docs (
  collection text NOT NULL,
  id uuid NOT NULL,
  data jsonb NOT NULL,
  created_at timestamptz NOT NULL DEFAULT now(),
  PRIMARY KEY (collection, id)
);
CREATE INDEX IF NOT EXISTS docs_collection_created ON docs (collection, created_at, id);
CREATE TABLE IF NOT EXISTS photo_files (
  id uuid PRIMARY KEY,
  data bytea NOT NULL
);`;

class PgStore {
  constructor(pool, collection) {
    this.pool = pool;
    this.collection = collection;
  }

  async all(client = this.pool) {
    const { rows } = await client.query('SELECT data FROM docs WHERE collection = $1 ORDER BY created_at, id', [this.collection]);
    return rows.map((r) => r.data);
  }

  insert(record) {
    return this.#write((rows) => {
      const row = { id: randomUUID(), createdAt: new Date().toISOString(), ...record };
      rows.push(row);
      return row;
    });
  }

  update(id, patch) {
    return this.#write((rows) => {
      const row = rows.find((r) => r.id === id);
      const changes = row && (typeof patch === 'function' ? patch(row) : patch);
      if (!changes) return null;
      return Object.assign(row, changes, { updatedAt: new Date().toISOString() });
    });
  }

  upsert(predicate, record) {
    return this.#write((rows) => {
      const row = rows.find(predicate);
      if (row) return { row: Object.assign(row, record, { updatedAt: new Date().toISOString() }), created: false };
      const created = { id: randomUUID(), createdAt: new Date().toISOString(), ...record };
      rows.push(created);
      return { row: created, created: true };
    });
  }

  remove(id) {
    return this.#write((rows) => {
      const i = rows.findIndex((r) => r.id === id);
      if (i < 0) return false;
      rows.splice(i, 1);
      return true;
    });
  }

  // Lukee kokoelman lukon alla, ajaa muutoksen ja kirjoittaa vain muuttuneet rivit.
  async #write(mutate) {
    const client = await this.pool.connect();
    try {
      await client.query('BEGIN');
      await client.query('SELECT pg_advisory_xact_lock(hashtext($1))', [this.collection]);
      const rows = await this.all(client);
      const before = new Map(rows.map((r) => [r.id, JSON.stringify(r)]));
      const result = mutate(rows);
      const after = new Set(rows.map((r) => r.id));
      for (const r of rows) {
        const json = JSON.stringify(r);
        if (before.get(r.id) === json) continue;
        await client.query(
          `INSERT INTO docs (collection, id, data, created_at) VALUES ($1, $2, $3, $4)
           ON CONFLICT (collection, id) DO UPDATE SET data = EXCLUDED.data`,
          [this.collection, r.id, json, r.createdAt ?? new Date().toISOString()],
        );
      }
      for (const id of before.keys()) {
        if (!after.has(id)) await client.query('DELETE FROM docs WHERE collection = $1 AND id = $2', [this.collection, id]);
      }
      await client.query('COMMIT');
      return result;
    } catch (err) {
      await client.query('ROLLBACK').catch(() => {});
      throw err;
    } finally {
      client.release();
    }
  }
}

export async function pgStorage(databaseUrl) {
  const { default: pg } = await import('pg');
  const pool = new pg.Pool({ connectionString: databaseUrl, max: 10 });
  await pool.query(SCHEMA);
  const stores = Object.fromEntries(COLLECTIONS.map((c) => [c, new PgStore(pool, c)]));
  // Kuvat samaan tietokantaan, jolloin ne ovat mukana varmuuskopioissa.
  const photoFiles = {
    async write(id, base64) {
      await pool.query('INSERT INTO photo_files (id, data) VALUES ($1, $2) ON CONFLICT (id) DO UPDATE SET data = EXCLUDED.data', [id, Buffer.from(base64, 'base64')]);
    },
    async read(id) {
      const { rows } = await pool.query('SELECT data FROM photo_files WHERE id = $1', [id]);
      return rows[0]?.data.toString('base64') ?? null;
    },
    async remove(id) {
      await pool.query('DELETE FROM photo_files WHERE id = $1', [id]);
    },
  };
  return { kind: 'postgres', stores, photoFiles, close: () => pool.end() };
}
