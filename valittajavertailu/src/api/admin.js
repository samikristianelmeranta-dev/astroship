// Ylläpidon reitit: kirjautuminen, yleiskatsaus, välittäjien tarkistus ja hyväksyntä,
// tarjousajan päättäminen, lähetetyt viestit, palvelumaksut, arviot, mittarit ja jono.
import { FUNNEL_EVENTS } from '../catalog.js';
import { isOpen, publicAgent, acceptedIds } from '../tender.js';
import { checkBusiness } from './agent.js';
import { reply, notFound, safeEqual } from './util.js';

const csvCell = (v) => `"${String(v ?? '').replace(/"/g, '""')}"`;

export function adminRoutes(ctx) {
  const { requests, agents, bids, questions, reviews, outbox, fees, events, waitlist } = ctx.stores;

  async function feeRows() {
    const byId = Object.fromEntries((await agents.all()).map((a) => [a.id, a]));
    const reqById = Object.fromEntries((await requests.all()).map((r) => [r.id, r]));
    return (await fees.all())
      .map((f) => ({
        ...f,
        month: f.createdAt.slice(0, 7),
        company: byId[f.agentId]?.company ?? f.agentId,
        businessId: byId[f.agentId]?.businessId ?? '',
        listing: reqById[f.requestId] ? ctx.listingTitle(reqById[f.requestId]) : '',
      }))
      .reverse();
  }

  return [
    ['POST', /^\/api\/admin\/login$/, async ({ body }) => {
      if (!ctx.adminToken) return reply(403, { error: 'Ylläpito ei ole käytössä (ADMIN_TOKEN puuttuu).' });
      if (!safeEqual(String(body.token ?? ''), ctx.adminToken)) return reply(401, { error: 'Väärä tunnus.' });
      return reply(200, { ok: true }, { session: { role: 'admin' } });
    }],

    ['GET', /^\/api\/admin\/overview$/, async () => {
      const [reqs, allAgents, allBids, allQuestions, allReviews] = await Promise.all([
        requests.all(), agents.all(), bids.all(), questions.all(), reviews.all(),
      ]);
      const byId = Object.fromEntries(allAgents.map((a) => [a.id, a.company]));
      const ratings = ctx.ratingsByAgent(allReviews);
      const t = ctx.now();
      return reply(200, {
        requests: reqs
          .map((r) => ({
            ...r,
            open: isOpen(r, t),
            acceptedBidIds: acceptedIds(r),
            bids: allBids
              .filter((b) => b.requestId === r.id)
              .map((b) => ({ ...b, company: byId[b.agentId] ?? b.agentId }))
              .sort((a, b) => b.estimateHigh - a.estimateHigh),
            questions: allQuestions.filter((q) => q.requestId === r.id).map((q) => ({ ...q, company: byId[q.agentId] })),
          }))
          .reverse(),
        agents: allAgents
          .map((a) => ({ ...publicAgent(a), rating: ratings[a.id] ?? null }))
          .reverse(),
      });
    }, 'admin'],

    // Hyväksyntä vaatii, että ylläpito on tarkistanut välitysliikkeen aluehallintoviraston
    // välitysliikerekisteristä (laki kiinteistönvälitysliikkeistä 1075/2000).
    ['POST', /^\/api\/admin\/agents\/([\w-]+)\/approve$/, async ({ params: [id], body }) => {
      if (body.registryChecked !== true)
        return reply(422, { error: 'Tarkista välitysliike AVI:n välitysliikerekisteristä ennen hyväksyntää.' });
      const updated = await agents.update(id, { status: 'hyvaksytty', registryCheckedAt: ctx.iso() });
      if (!updated) return notFound();
      await ctx.notify({ email: updated.email }, 'valittaja_hyvaksytty', { company: updated.company, contactName: updated.contactName });
      return reply(200, { ok: true });
    }, 'admin'],

    ['POST', /^\/api\/admin\/agents\/([\w-]+)\/suspend$/, async ({ params: [id] }) => {
      const updated = await agents.update(id, { status: 'jaadytetty' });
      return updated ? reply(200, { ok: true }) : notFound();
    }, 'admin'],

    ['POST', /^\/api\/admin\/agents\/([\w-]+)\/prh$/, async ({ params: [id] }) => {
      const agent = (await agents.all()).find((a) => a.id === id);
      if (!agent) return notFound();
      const prh = await checkBusiness(ctx, agent.businessId);
      await agents.update(id, { prh });
      return reply(200, { prh });
    }, 'admin'],

    // Päättää arvioajan heti (esim. asiakkaan pyynnöstä tai testauksessa).
    ['POST', /^\/api\/admin\/requests\/([\w-]+)\/close$/, async ({ params: [id] }) => {
      const t = ctx.now();
      const updated = await requests.update(id, (r) => (isOpen(r, t) ? { deadline: new Date(t).toISOString() } : null));
      return updated ? reply(200, { ok: true }) : reply(409, { error: 'Arvioaika on jo päättynyt.' });
    }, 'admin'],

    ['GET', /^\/api\/admin\/outbox$/, async () => reply(200, { messages: (await outbox.all()).slice(-300).reverse() }), 'admin'],

    ['GET', /^\/api\/admin\/fees$/, async () => {
      const rows = await feeRows();
      const summary = {};
      for (const f of rows) {
        const key = `${f.month}|${f.agentId}`;
        const s = (summary[key] ??= { month: f.month, company: f.company, businessId: f.businessId, count: 0, totalEur: 0, uninvoicedEur: 0 });
        s.count += 1;
        s.totalEur += f.amountEur;
        if (!f.invoiced) s.uninvoicedEur += f.amountEur;
      }
      return reply(200, { fees: rows, summary: Object.values(summary), feeEur: ctx.config.successFeeEur, startDate: ctx.config.feeStartDate });
    }, 'admin'],

    ['GET', /^\/api\/admin\/fees\.csv$/, async () => {
      const header = ['Päivä', 'Yritys', 'Y-tunnus', 'Kohde', 'Summa (€, alv 0 %)', 'Laskutettu'];
      const lines = (await feeRows()).map((f) =>
        [f.createdAt.slice(0, 10), f.company, f.businessId, f.listing, f.amountEur, f.invoiced ? 'kyllä' : 'ei'].map(csvCell).join(';'),
      );
      return reply(200, null, { file: { type: 'text/csv; charset=utf-8', text: '﻿' + [header.map(csvCell).join(';'), ...lines].join('\r\n'), filename: 'palvelumaksut.csv' } });
    }, 'admin'],

    ['POST', /^\/api\/admin\/fees\/([\w-]+)\/invoiced$/, async ({ params: [id] }) => {
      const updated = await fees.update(id, { invoiced: true, invoicedAt: ctx.iso() });
      return updated ? reply(200, { ok: true }) : notFound();
    }, 'admin'],

    ['GET', /^\/api\/admin\/reviews$/, async () => {
      const byId = Object.fromEntries((await agents.all()).map((a) => [a.id, a.company]));
      return reply(200, { reviews: (await reviews.all()).map((r) => ({ ...r, company: byId[r.agentId] })).reverse() });
    }, 'admin'],

    ['POST', /^\/api\/admin\/reviews\/([\w-]+)\/(hide|show)$/, async ({ params: [id, action] }) => {
      const updated = await reviews.update(id, { hidden: action === 'hide' });
      return updated ? reply(200, { ok: true }) : notFound();
    }, 'admin'],

    // Suppilo: tapahtumien määrät valitulta ajalta.
    ['GET', /^\/api\/admin\/funnel$/, async ({ query }) => {
      const days = Math.min(365, Math.max(1, Number(query.get('days')) || 30));
      const since = new Date(ctx.now() - days * 864e5).toISOString().slice(0, 10);
      const totals = {};
      for (const e of await events.all()) if (e.day >= since) totals[e.name] = (totals[e.name] ?? 0) + e.count;
      return reply(200, { days, steps: Object.entries(FUNNEL_EVENTS).map(([name, label]) => ({ name, label, count: totals[name] ?? 0 })) });
    }, 'admin'],

    ['GET', /^\/api\/admin\/waitlist$/, async () => {
      const rows = await waitlist.all();
      const byRegion = {};
      for (const w of rows) if (!w.notifiedAt) byRegion[w.region] = (byRegion[w.region] ?? 0) + 1;
      return reply(200, { waitlist: rows.reverse(), byRegion });
    }, 'admin'],

    // Kun palvelu avataan uudelle alueelle, jonossa olevat saavat viestin.
    ['POST', /^\/api\/admin\/waitlist\/notify$/, async ({ body }) => {
      const region = String(body.region ?? '');
      let sent = 0;
      for (const w of await waitlist.all()) {
        if (w.region !== region || w.notifiedAt) continue;
        await ctx.notify({ email: w.email }, 'jono_avattu', { region });
        await waitlist.update(w.id, { notifiedAt: ctx.iso() });
        sent += 1;
      }
      return reply(200, { sent });
    }, 'admin'],
  ];
}
