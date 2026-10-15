import http from 'node:http';
import { readFile } from 'node:fs/promises';
import { readFileSync } from 'node:fs';
import { createHmac, randomBytes, scrypt, timingSafeEqual } from 'node:crypto';
import { promisify } from 'node:util';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

import { createApi } from './src/api/index.js';
import { createGeo } from './src/geo.js';
import { renderSeoPage, sitemapXml } from './src/seo.js';
import { jsonStorage, storageFromEnv } from './src/node/storage.js';
import { messengerFromEnv, ytjLookup } from './src/node/messengers.js';

const ROOT = path.dirname(fileURLToPath(import.meta.url));
const PUBLIC_DIR = path.join(ROOT, 'public');
const MAX_BODY = 50_000;
const MAX_PHOTO_BODY = 4_500_000;
const SESSION_COOKIE = 'vv_session';
const SESSION_DAYS = 7;
const JOB_INTERVAL_MS = 60_000;
const scryptAsync = promisify(scrypt);

const MIME = {
  '.html': 'text/html; charset=utf-8',
  '.css': 'text/css; charset=utf-8',
  '.js': 'text/javascript; charset=utf-8',
  '.svg': 'image/svg+xml',
  '.woff2': 'font/woff2',
  '.png': 'image/png',
  '.ico': 'image/x-icon',
  '.txt': 'text/plain; charset=utf-8',
  '.xml': 'application/xml',
};

// Siistit osoitteet ilman .html-päätettä.
const PAGES = {
  '/': 'index.html',
  '/kilpailuta': 'kilpailuta.html',
  '/kohde': 'kohde.html',
  '/omat': 'omat.html',
  '/valittajille': 'valittajille.html',
  '/valittaja': 'valittaja.html',
  '/tietosuoja': 'tietosuoja.html',
  '/kayttoehdot': 'kayttoehdot.html',
  '/valittajaehdot': 'valittajaehdot.html',
  '/admin': 'admin.html',
};

const SECURITY_HEADERS = {
  'X-Content-Type-Options': 'nosniff',
  'X-Frame-Options': 'DENY',
  'Referrer-Policy': 'no-referrer',
  'Content-Security-Policy':
    "default-src 'self'; img-src 'self' data: blob:; style-src 'self'; script-src 'self'; form-action 'self'; frame-ancestors 'none'",
};

// IP-kohtaiset rajat tunnissa roskapostin ja arvailun takia: [metodi, polun alku, enimmäismäärä].
const LIMITS = [
  ['POST', '/api/requests', 20],
  ['POST', '/api/agents', 10],
  ['POST', '/api/login', 30],
  ['POST', '/api/admin/login', 10],
  ['POST', '/api/consumer/login', 10],
  ['POST', '/api/waitlist', 10],
  ['POST', '/api/photos', 60],
  ['POST', '/api/agent/questions', 60],
  ['POST', '/api/events', 600],
];

export const passwords = {
  async hash(password) {
    const salt = randomBytes(16).toString('hex');
    const key = await scryptAsync(password, salt, 64);
    return `scrypt$${salt}$${key.toString('hex')}`;
  },
  async verify(password, stored) {
    const [scheme, salt, hex] = String(stored ?? '').split('$');
    if (scheme !== 'scrypt' || !salt || !hex) return false;
    const key = await scryptAsync(password, salt, 64);
    const expected = Buffer.from(hex, 'hex');
    return expected.length === key.length && timingSafeEqual(expected, key);
  },
};

// Allekirjoitettu evästeistunto: base64url(json).hmac
function sessionCodec(secret) {
  const sign = (data) => createHmac('sha256', secret).update(data).digest('base64url');
  return {
    encode(session) {
      const data = Buffer.from(JSON.stringify({ ...session, exp: Date.now() + SESSION_DAYS * 864e5 })).toString('base64url');
      return `${data}.${sign(data)}`;
    },
    decode(value) {
      const [data, mac] = String(value ?? '').split('.');
      if (!data || !mac) return null;
      const expected = Buffer.from(sign(data));
      const given = Buffer.from(mac);
      if (expected.length !== given.length || !timingSafeEqual(expected, given)) return null;
      try {
        const session = JSON.parse(Buffer.from(data, 'base64url').toString('utf8'));
        return session.exp > Date.now() ? session : null;
      } catch {
        return null;
      }
    },
  };
}

function readCookie(req, name) {
  for (const part of (req.headers.cookie ?? '').split(';')) {
    const [k, ...v] = part.trim().split('=');
    if (k === name) return v.join('=');
  }
  return null;
}

function send(res, status, body, headers = {}) {
  const isJson = body !== null && typeof body !== 'string' && !Buffer.isBuffer(body);
  res.writeHead(status, {
    ...SECURITY_HEADERS,
    'Content-Type': isJson ? 'application/json; charset=utf-8' : 'text/plain; charset=utf-8',
    ...headers,
  });
  res.end(isJson ? JSON.stringify(body) : (body ?? ''));
}

async function readJson(req, limit = MAX_BODY) {
  let size = 0;
  const chunks = [];
  for await (const chunk of req) {
    size += chunk.length;
    if (size > limit) throw Object.assign(new Error('Liian suuri pyyntö'), { status: 413 });
    chunks.push(chunk);
  }
  try {
    return JSON.parse(Buffer.concat(chunks).toString('utf8') || '{}');
  } catch {
    throw Object.assign(new Error('Virheellinen JSON'), { status: 400 });
  }
}

function rateLimiter(windowMs = 60 * 60 * 1000) {
  const hits = new Map();
  return (key, max) => {
    const now = Date.now();
    const recent = (hits.get(key) ?? []).filter((t) => now - t < windowMs);
    recent.push(now);
    hits.set(key, recent);
    return recent.length <= max;
  };
}

function loadPostalCoordinates(dataDir) {
  try {
    return JSON.parse(readFileSync(path.join(dataDir, 'postal-coordinates.json'), 'utf8'));
  } catch {
    return null;
  }
}

const envList = (v) => (v === undefined ? undefined : v === '*' ? null : v.split(',').map((s) => s.trim()).filter(Boolean));

export function createServer({
  dataDir = path.join(ROOT, 'data'),
  storage = jsonStorage(dataDir),
  adminToken = process.env.ADMIN_TOKEN,
  sessionSecret = process.env.SESSION_SECRET ?? randomBytes(32).toString('hex'),
  secureCookies = process.env.NODE_ENV === 'production',
  publicUrl = process.env.PUBLIC_URL ?? '',
  messenger = messengerFromEnv(),
  businessLookup = process.env.YTJ_LOOKUP === 'off' ? null : ytjLookup,
  postalCoordinates = loadPostalCoordinates(dataDir),
  config = {
    pilotRegions: envList(process.env.PILOT_REGIONS),
    successFeeEur: process.env.SUCCESS_FEE_EUR === undefined ? undefined : Number(process.env.SUCCESS_FEE_EUR),
    feeStartDate: process.env.FEE_START_DATE,
    adminEmail: process.env.ADMIN_EMAIL,
  },
  now,
} = {}) {
  const api = createApi({
    stores: storage.stores,
    photoFiles: storage.photoFiles,
    passwords,
    newToken: () => randomBytes(24).toString('hex'),
    adminToken,
    messenger,
    links: {
      project: (t) => `${publicUrl}/kohde#${t}`,
      agent: () => `${publicUrl}/valittaja`,
      consumerLogin: (t) => `${publicUrl}/omat#${t}`,
      home: () => `${publicUrl}/`,
    },
    geo: createGeo(postalCoordinates),
    businessLookup,
    config,
    now,
  });
  const sessions = sessionCodec(sessionSecret);
  const allow = rateLimiter();

  async function serveStatic(res, pathname) {
    if (pathname === '/sitemap.xml') return send(res, 200, sitemapXml(publicUrl), { 'Content-Type': MIME['.xml'] });
    if (pathname === '/robots.txt') {
      const rules = ['/admin', '/api/', '/kohde', '/omat', '/valittaja$'].map((p) => `Disallow: ${p}`).join('\n');
      return send(res, 200, `User-agent: *\n${rules}\nSitemap: ${publicUrl}/sitemap.xml\n`);
    }
    const seo = renderSeoPage(pathname, publicUrl);
    if (seo) return send(res, 200, seo, { 'Content-Type': MIME['.html'], 'Cache-Control': 'public, max-age=600' });
    const file = PAGES[pathname] ?? pathname.slice(1);
    const full = path.normalize(path.join(PUBLIC_DIR, file));
    if (!full.startsWith(PUBLIC_DIR + path.sep)) return send(res, 404, 'Sivua ei löytynyt');
    try {
      const body = await readFile(full);
      const ext = path.extname(full);
      send(res, 200, body, {
        'Content-Type': MIME[ext] ?? 'application/octet-stream',
        'Cache-Control': ext === '.html' ? 'no-cache' : ext === '.woff2' ? 'public, max-age=31536000, immutable' : 'public, max-age=3600',
      });
    } catch {
      const notFound = await readFile(path.join(PUBLIC_DIR, '404.html')).catch(() => 'Sivua ei löytynyt');
      send(res, 404, notFound, { 'Content-Type': MIME['.html'] });
    }
  }

  async function handleApi(req, res, url) {
    if (req.method !== 'GET') {
      // CSRF-suoja: lomakkeet eivät voi lähettää JSONia toiselta sivustolta ilman CORSia.
      if (!String(req.headers['content-type'] ?? '').startsWith('application/json'))
        return send(res, 415, { error: 'Käytä Content-Type: application/json' });
      const limit = LIMITS.find(([m, prefix]) => m === req.method && url.pathname.startsWith(prefix));
      if (limit && !allow(`${limit[1]} ${req.socket.remoteAddress}`, limit[2]))
        return send(res, 429, { error: 'Liian monta yritystä. Yritä myöhemmin uudelleen.' });
    }
    const result = await api({
      method: req.method,
      path: url.pathname,
      query: url.searchParams,
      body: req.method === 'GET' ? {} : await readJson(req, url.pathname === '/api/photos' || url.pathname === '/api/agent/logo' ? MAX_PHOTO_BODY : MAX_BODY),
      session: sessions.decode(readCookie(req, SESSION_COOKIE)),
    });
    const headers = { 'Cache-Control': 'no-store' };
    if (result.session !== undefined) {
      const flags = `Path=/; HttpOnly; SameSite=Lax${secureCookies ? '; Secure' : ''}`;
      headers['Set-Cookie'] = result.session
        ? `${SESSION_COOKIE}=${sessions.encode(result.session)}; ${flags}; Max-Age=${SESSION_DAYS * 86400}`
        : `${SESSION_COOKIE}=; ${flags}; Max-Age=0`;
    }
    if (result.file) {
      headers['Content-Type'] = result.file.type;
      if (result.file.filename) headers['Content-Disposition'] = `attachment; filename="${result.file.filename}"`;
      else headers['Cache-Control'] = 'private, max-age=3600';
      const data = result.file.text !== undefined ? result.file.text : Buffer.from(result.file.base64, 'base64');
      return send(res, result.status, data, headers);
    }
    send(res, result.status, result.body, headers);
  }

  const server = http.createServer(async (req, res) => {
    const url = new URL(req.url, 'http://localhost');
    try {
      if (url.pathname.startsWith('/api/')) return await handleApi(req, res, url);
      if (req.method !== 'GET' && req.method !== 'HEAD') return send(res, 405, 'Method Not Allowed');
      return await serveStatic(res, url.pathname);
    } catch (err) {
      if (!err.status) console.error(err);
      if (!res.headersSent) send(res, err.status ?? 500, { error: err.status ? err.message : 'Palvelinvirhe' });
    }
  });
  server.api = api;
  server.storageKind = storage.kind;
  return server;
}

if (process.argv[1] === fileURLToPath(import.meta.url)) {
  const port = Number(process.env.PORT ?? 3000);
  const storage = await storageFromEnv({ dataDir: path.join(ROOT, 'data') });
  const server = createServer({ storage });
  server.listen(port, () => {
    console.log(`Palvelu käynnissä: http://localhost:${port} (tallennus: ${storage.kind})`);
    if (!process.env.ADMIN_TOKEN) console.log('Huom: aseta ADMIN_TOKEN, jotta /admin toimii.');
    if (!process.env.SESSION_SECRET) console.log('Huom: aseta SESSION_SECRET, muuten kirjautumiset nollautuvat uudelleenkäynnistyksessä.');
    if (!process.env.POSTMARK_TOKEN) console.log('Huom: POSTMARK_TOKEN puuttuu, viestit näkyvät vain ylläpidon Viestit-välilehdellä.');
  });
  const tick = () => server.api.runJobs().catch((err) => console.error('Ajastettu työ epäonnistui:', err));
  setInterval(tick, JOB_INTERVAL_MS).unref();
  tick();
  const shutdown = () => server.close(() => storage.close().finally(() => process.exit(0)));
  process.on('SIGTERM', shutdown);
  process.on('SIGINT', shutdown);
}
