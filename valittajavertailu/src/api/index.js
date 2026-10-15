// Palvelun rajapinta ilman HTTP-yksityiskohtia. Node-palvelin (server.js) ja
// selainesikatselu käyttävät samaa koodia; ne antavat tallennuksen, viestien
// lähetyksen, salasanojen tiivisteet, satunnaistunnukset ja istunnon.
//
// handle({ method, path, query, body, session }) palauttaa
// { status, body, session?, file? }, jossa session: undefined = ennallaan, null = ulos,
// ja file = { type, base64 } tai { type, text, filename } tiedostovastauksille.
// handle.runJobs() ajaa ajastetut työt (määräajat, muistutukset, siivous).

import { PROPERTY_TYPES, ROOMS, DEFAULT_PILOT_REGIONS, DEFAULT_SUCCESS_FEE_EUR, REVIEW_DELAY_DAYS } from '../catalog.js';
import { buildMessage } from '../messages.js';
import { createGeo } from '../geo.js';
import { publicRoutes } from './public.js';
import { consumerRoutes } from './consumer.js';
import { photoRoutes } from './photos.js';
import { agentRoutes } from './agent.js';
import { adminRoutes } from './admin.js';
import { runJobs } from '../jobs.js';

import { reply, unauthorized, safeEqual } from './util.js';

export { reply, notFound, unauthorized, safeEqual } from './util.js';

const STORE_NAMES = [
  'requests', 'agents', 'bids', 'photos', 'questions', 'reviews', 'templates',
  'logins', 'outbox', 'fees', 'events', 'waitlist',
];

const DEFAULT_LINKS = {
  project: (t) => `/kohde#${t}`,
  agent: () => '/valittaja',
  consumerLogin: (t) => `/omat#${t}`,
  home: () => '/',
};

export function createApi({
  stores,
  photoFiles,
  passwords,
  newToken,
  adminToken,
  messenger = null,
  links = DEFAULT_LINKS,
  geo = createGeo(null),
  businessLookup = null,
  config = {},
  now = () => Date.now(),
}) {
  for (const name of STORE_NAMES) if (!stores[name]) throw new Error(`Varasto puuttuu: ${name}`);

  const cfg = {
    pilotRegions: config.pilotRegions === undefined ? DEFAULT_PILOT_REGIONS : config.pilotRegions,
    successFeeEur: config.successFeeEur ?? DEFAULT_SUCCESS_FEE_EUR,
    feeStartDate: config.feeStartDate ?? null,
    reviewDelayDays: config.reviewDelayDays ?? REVIEW_DELAY_DAYS,
    adminEmail: config.adminEmail ?? null,
    // Kehityksessä (ei oikeaa viestipalvelua) vahvistuslinkit palautetaan suoraan.
    devLinks: config.devLinks ?? !messenger?.live,
  };

  const ctx = {
    stores,
    photoFiles,
    passwords,
    newToken,
    adminToken,
    links,
    geo,
    businessLookup,
    config: cfg,
    now,
    iso: () => new Date(now()).toISOString(),
    // Lyhyt kuvaus viesteihin, esim. "Kaksio 54 m², Turku". Ei osoitetta.
    listingTitle: (r) => `${r.propertyType === 'kerrostalo' ? ROOMS[r.rooms] ?? PROPERTY_TYPES[r.propertyType] : PROPERTY_TYPES[r.propertyType] ?? 'Asunto'} ${String(r.area).replace('.', ',')} m², ${r.city || r.postalCode}`,
    inPilot: (region) => !cfg.pilotRegions || cfg.pilotRegions.includes(region),

    async currentAgent(session) {
      if (session?.role !== 'agent') return null;
      return (await stores.agents.all()).find((a) => a.id === session.id) ?? null;
    },

    async requestByToken(token) {
      return (await stores.requests.all()).find((r) => safeEqual(r.consumerToken ?? '', token)) ?? null;
    },

    // Lähettää viestin ja kirjaa sen lähetettyjen listaan (ylläpito näkee kaikki).
    async notify({ email, phone, sms = false }, kind, vars) {
      const msg = buildMessage(kind, vars, links);
      const deliveries = [];
      if (email) deliveries.push({ channel: 'email', to: email, subject: msg.subject, text: msg.text });
      if (sms && phone && msg.sms) deliveries.push({ channel: 'sms', to: phone, subject: '', text: msg.sms });
      for (const d of deliveries) {
        let status = 'kehitys';
        let error = null;
        if (messenger?.live) {
          try {
            await (d.channel === 'email' ? messenger.email(d) : messenger.sms(d));
            status = 'lähetetty';
          } catch (err) {
            status = 'virhe';
            error = String(err?.message ?? err).slice(0, 300);
          }
        }
        await stores.outbox.insert({ ...d, kind, status, error });
      }
      return msg;
    },

    // Päiväkohtainen laskuri. Ei henkilötietoja, ei evästeitä.
    async track(name) {
      const day = new Date(now()).toISOString().slice(0, 10);
      const existing = (await stores.events.all()).find((e) => e.day === day && e.name === name);
      if (existing) await stores.events.update(existing.id, (e) => ({ count: e.count + 1 }));
      else await stores.events.insert({ day, name, count: 1 });
    },

    ratingsByAgent(reviews) {
      const out = {};
      for (const r of reviews) {
        if (r.hidden) continue;
        const o = (out[r.agentId] ??= { sum: 0, count: 0 });
        o.sum += r.rating;
        o.count += 1;
      }
      for (const o of Object.values(out)) o.avg = Math.round((o.sum / o.count) * 10) / 10;
      return out;
    },
  };

  const routes = [
    ...publicRoutes(ctx),
    ...consumerRoutes(ctx),
    ...photoRoutes(ctx),
    ...agentRoutes(ctx),
    ...adminRoutes(ctx),
  ];

  async function handle({ method, path, query = new URLSearchParams(), body = {}, session = null }) {
    for (const [m, pattern, fn, role] of routes) {
      const match = pattern.exec(path);
      if (!match || m !== method) continue;
      if (role === 'admin' && session?.role !== 'admin') return unauthorized();
      // Hunajapurkki: ihminen ei näe kenttää, botit täyttävät sen.
      if (method === 'POST' && body?.website) return reply(201, { ok: true });
      return fn({ params: match.slice(1), query, body: body ?? {}, session });
    }
    return reply(404, { error: 'Tuntematon rajapinta' });
  }

  handle.runJobs = () => runJobs(ctx);
  return handle;
}
