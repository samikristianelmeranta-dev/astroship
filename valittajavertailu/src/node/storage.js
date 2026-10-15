// Tallennus: JSON-tiedostot (kehitys) tai PostgreSQL (DATABASE_URL).
import { readFile, writeFile, mkdir, unlink } from 'node:fs/promises';
import path from 'node:path';
import { JsonStore } from '../store.js';

export const COLLECTIONS = [
  'requests', 'agents', 'bids', 'photos', 'questions', 'reviews', 'templates',
  'logins', 'outbox', 'fees', 'events', 'waitlist',
];

export function jsonStorage(dataDir) {
  const stores = Object.fromEntries(COLLECTIONS.map((c) => [c, new JsonStore(path.join(dataDir, `${c}.json`))]));
  // Kuvatiedostot levylle; id tulee aina varastosta (randomUUID), ei käyttäjältä.
  const file = (id) => path.join(dataDir, 'photos', `${id}.jpg`);
  const photoFiles = {
    async write(id, base64) {
      await mkdir(path.join(dataDir, 'photos'), { recursive: true });
      await writeFile(file(id), Buffer.from(base64, 'base64'));
    },
    async read(id) {
      const data = await readFile(file(id)).catch(() => null);
      return data?.toString('base64') ?? null;
    },
    async remove(id) {
      await unlink(file(id)).catch(() => {});
    },
  };
  return { kind: 'json', stores, photoFiles, close: async () => {} };
}

export async function storageFromEnv({ dataDir, databaseUrl = process.env.DATABASE_URL } = {}) {
  if (!databaseUrl) return jsonStorage(dataDir);
  const { pgStorage } = await import('./pg-store.js');
  return pgStorage(databaseUrl);
}
