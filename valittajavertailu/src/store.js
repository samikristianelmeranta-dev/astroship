import { readFile, writeFile, rename, mkdir } from 'node:fs/promises';
import { randomUUID } from 'node:crypto';
import path from 'node:path';

// Yksinkertainen JSON-tiedostovarasto. Riittää MVP-vaiheeseen; vaihdetaan
// tietokantaan (esim. PostgreSQL) kun liikennettä alkaa tulla.
export class JsonStore {
  constructor(file) {
    this.file = file;
    this.queue = Promise.resolve();
  }

  async all() {
    try {
      return JSON.parse(await readFile(this.file, 'utf8'));
    } catch (err) {
      if (err.code === 'ENOENT') return [];
      throw err;
    }
  }

  insert(record) {
    return this.#write((rows) => {
      const row = { id: randomUUID(), createdAt: new Date().toISOString(), ...record };
      rows.push(row);
      return row;
    });
  }

  // patch voi olla olio tai funktio rivi => olio | null. Funktio ajetaan
  // kirjoituslukon sisällä, joten ehdollinen päivitys on atominen.
  // Palauttaa päivitetyn rivin tai null, jos riviä ei löydy tai ehto ei täyty.
  update(id, patch) {
    return this.#write((rows) => {
      const row = rows.find((r) => r.id === id);
      const changes = row && (typeof patch === 'function' ? patch(row) : patch);
      if (!changes) return null;
      return Object.assign(row, changes, { updatedAt: new Date().toISOString() });
    });
  }

  // Päivittää ehdon täyttävän rivin tai lisää uuden. Palauttaa { row, created }.
  upsert(predicate, record) {
    return this.#write((rows) => {
      const row = rows.find(predicate);
      if (row) return { row: Object.assign(row, record, { updatedAt: new Date().toISOString() }), created: false };
      const created = { id: randomUUID(), createdAt: new Date().toISOString(), ...record };
      rows.push(created);
      return { row: created, created: true };
    });
  }

  // Poistaa rivin. Palauttaa true, jos rivi löytyi.
  remove(id) {
    return this.#write((rows) => {
      const i = rows.findIndex((r) => r.id === id);
      if (i < 0) return false;
      rows.splice(i, 1);
      return true;
    });
  }

  // Kirjoitukset sarjallistetaan, jotta samanaikaiset pyynnöt eivät hukkaa rivejä.
  #write(mutate) {
    const run = async () => {
      const rows = await this.all();
      const result = mutate(rows);
      await mkdir(path.dirname(this.file), { recursive: true });
      const tmp = `${this.file}.${process.pid}.tmp`;
      await writeFile(tmp, JSON.stringify(rows, null, 2));
      await rename(tmp, this.file);
      return result;
    };
    const result = this.queue.then(run);
    this.queue = result.catch(() => {});
    return result;
  }
}
