// Välittäjän reitit: rekisteröinti, kirjautuminen, kohteet, arviot, kysymykset ja
// kuvapyynnöt, profiili, logo ja tarjouspohjat.
import { validateAgent, validateBid, validateMessageText, validateProfile } from '../validate.js';
import { isOpen, canSee, requestForAgent, contactOf, publicAgent, bidOutcome, acceptedIds } from '../tender.js';
import { parseYtjCompany } from '../prh.js';
import { savePhoto } from './photos.js';
import { reply, notFound, unauthorized } from './util.js';

// Hakee yrityksen tiedot YTJ:stä. Virhe ei estä rekisteröintiä; ylläpito näkee tuloksen.
export async function checkBusiness(ctx, businessId) {
  if (!ctx.businessLookup) return { checkedAt: ctx.iso(), error: 'YTJ-tarkistus ei ole käytössä' };
  try {
    return { checkedAt: ctx.iso(), ...parseYtjCompany(await ctx.businessLookup(businessId)) };
  } catch (err) {
    return { checkedAt: ctx.iso(), error: String(err?.message ?? err).slice(0, 200) };
  }
}

export function agentRoutes(ctx) {
  const { agents, requests, bids, questions, reviews, templates, photos } = ctx.stores;

  // Kohde, jonka kirjautunut ja hyväksytty välittäjä saa nähdä. Muuten null.
  async function visibleRequest(agent, requestId) {
    const request = (await requests.all()).find((r) => r.id === requestId);
    return request && canSee(agent, request, ctx.geo) ? request : null;
  }

  return [
    ['POST', /^\/api\/agents$/, async ({ body }) => {
      const { ok, errors, data, password } = validateAgent(body);
      if (!ok) return reply(422, { errors });
      // Samalla Y-tunnuksella voi olla useita toimistoja tai välittäjiä, sähköposti on yksilöllinen.
      if ((await agents.all()).some((a) => a.email === data.email))
        return reply(409, { errors: { email: 'Tällä sähköpostilla on jo tunnus. Kirjaudu sisään.' } });
      const { termsAccepted, ...fields } = data;
      const saved = await agents.insert({
        ...fields,
        passwordHash: await ctx.passwords.hash(password),
        status: 'odottaa_hyvaksyntaa',
        termsAcceptedAt: ctx.iso(),
        prh: await checkBusiness(ctx, data.businessId),
        registryCheckedAt: null,
        logoPhotoId: null,
      });
      if (ctx.config.adminEmail) {
        await ctx.notify({ email: ctx.config.adminEmail }, 'uusi_valittaja', { company: saved.company, businessId: saved.businessId });
      }
      return reply(201, { ok: true }, { session: { role: 'agent', id: saved.id } });
    }],

    ['POST', /^\/api\/login$/, async ({ body }) => {
      const email = String(body.email ?? '').trim().toLowerCase();
      const agent = (await agents.all()).find((a) => a.email === email);
      const valid = agent && (await ctx.passwords.verify(String(body.password ?? ''), agent.passwordHash));
      if (!valid) return reply(401, { error: 'Väärä sähköposti tai salasana.' });
      return reply(200, { ok: true }, { session: { role: 'agent', id: agent.id } });
    }],

    ['POST', /^\/api\/logout$/, async () => reply(200, { ok: true }, { session: null })],

    ['GET', /^\/api\/agent$/, async ({ session }) => {
      const agent = await ctx.currentAgent(session);
      if (!agent) return unauthorized();
      const t = ctx.now();
      const allRequests = await requests.all();
      const allQuestions = await questions.all();
      const mine = (await bids.all()).filter((b) => b.agentId === agent.id);
      const myBidFor = Object.fromEntries(mine.map((b) => [b.requestId, b]));
      // Kysymykset näytetään ilman kysyjän tietoja; oma kysymys merkitään.
      const qsFor = (requestId) =>
        allQuestions
          .filter((q) => q.requestId === requestId)
          .map((q) => ({
            id: q.id, text: q.text, wantsPhotos: Boolean(q.wantsPhotos), answer: q.answer ?? null,
            answerPhotoIds: q.answerPhotoIds ?? [], mine: q.agentId === agent.id, createdAt: q.createdAt,
          }));

      const open = allRequests
        .filter((r) => isOpen(r, t) && canSee(agent, r, ctx.geo))
        .sort((a, b) => a.deadline.localeCompare(b.deadline))
        .map((r) => ({ ...requestForAgent(r), myBid: myBidFor[r.id] ?? null, questions: qsFor(r.id) }));

      const myReviews = (await reviews.all()).filter((r) => r.agentId === agent.id && !r.hidden);
      const history = mine
        .map((b) => {
          const r = allRequests.find((x) => x.id === b.requestId);
          if (!r || isOpen(r, t)) return null;
          const outcome = bidOutcome(r, b, t);
          const review = myReviews.find((x) => x.requestId === r.id);
          return {
            request: requestForAgent(r),
            myBid: b,
            outcome,
            contact: outcome === 'valittu' ? contactOf(r) : null,
            // Kerrotaan vain, että myös toinen valittiin; ei kuka.
            sharedSelection: outcome === 'valittu' && acceptedIds(r).length > 1,
            review: review ? { rating: review.rating, text: review.text } : null,
          };
        })
        .filter(Boolean)
        .sort((a, b) => b.request.deadline.localeCompare(a.request.deadline));

      const rating = ctx.ratingsByAgent(myReviews)[agent.id] ?? null;
      return reply(200, {
        agent: publicAgent(agent),
        open,
        history,
        templates: (await templates.all()).filter((x) => x.agentId === agent.id),
        rating: rating && { avg: rating.avg, count: rating.count },
        reviews: myReviews.map(({ rating: r, text, createdAt }) => ({ rating: r, text, createdAt })).reverse(),
        fee: { eur: ctx.config.successFeeEur, startDate: ctx.config.feeStartDate },
      });
    }],

    ['PUT', /^\/api\/agent\/bids\/([\w-]+)$/, async ({ params: [requestId], body, session }) => {
      const agent = await ctx.currentAgent(session);
      if (!agent) return unauthorized();
      if (agent.status !== 'hyvaksytty') return reply(403, { error: 'Tilisi odottaa ylläpidon hyväksyntää.' });
      const request = await visibleRequest(agent, requestId);
      if (!request) return notFound();
      if (!isOpen(request, ctx.now())) return reply(409, { error: 'Arvioaika on päättynyt.' });
      const { ok, errors, data } = validateBid(body);
      if (!ok) return reply(422, { errors });
      const { row, created } = await bids.upsert(
        (b) => b.requestId === requestId && b.agentId === agent.id,
        { ...data, requestId, agentId: agent.id },
      );
      if (created) await ctx.track('tarjous');
      return reply(created ? 201 : 200, { bid: row });
    }],

    // Kysymys myyjälle. wantsPhotos = välittäjä pyytää lisäkuvia (esim. kylpyhuoneesta).
    ['POST', /^\/api\/agent\/questions\/([\w-]+)$/, async ({ params: [requestId], body, session }) => {
      const agent = await ctx.currentAgent(session);
      if (!agent) return unauthorized();
      if (agent.status !== 'hyvaksytty') return reply(403, { error: 'Tilisi odottaa ylläpidon hyväksyntää.' });
      const request = await visibleRequest(agent, requestId);
      if (!request) return notFound();
      if (!isOpen(request, ctx.now())) return reply(409, { error: 'Arvioaika on päättynyt.' });
      const { ok, errors, text } = validateMessageText(body.text, { min: 5, max: 500 });
      if (!ok) return reply(422, { errors });
      const wantsPhotos = body.wantsPhotos === true;
      const q = await questions.insert({ requestId, agentId: agent.id, text, wantsPhotos, answer: null, answerPhotoIds: [] });
      await ctx.notify({ email: request.email }, 'kysymys', { question: text, wantsPhotos, token: request.consumerToken });
      return reply(201, { id: q.id });
    }],

    ['PUT', /^\/api\/agent\/profile$/, async ({ body, session }) => {
      const agent = await ctx.currentAgent(session);
      if (!agent) return unauthorized();
      const { ok, errors, data } = validateProfile(body);
      if (!ok) return reply(422, { errors });
      const updated = await agents.update(agent.id, data);
      return reply(200, { agent: publicAgent(updated) });
    }],

    ['POST', /^\/api\/agent\/logo$/, async ({ body, session }) => {
      const agent = await ctx.currentAgent(session);
      if (!agent) return unauthorized();
      const { error, photo } = await savePhoto(ctx, body.dataUrl, { kind: 'logo', agentId: agent.id });
      if (error) return error;
      if (agent.logoPhotoId) {
        await photos.remove(agent.logoPhotoId);
        await ctx.photoFiles.remove?.(agent.logoPhotoId);
      }
      await agents.update(agent.id, { logoPhotoId: photo.id });
      return reply(201, { id: photo.id });
    }],

    // Tarjouspohjat: palkkio ja palvelusisältö tallennetaan ilman pakollisuustarkistusta.
    // Hinta-arviota ei tallenneta, koska se on aina kohdekohtainen.
    ['POST', /^\/api\/agent\/templates$/, async ({ body, session }) => {
      const agent = await ctx.currentAgent(session);
      if (!agent) return unauthorized();
      const name = String(body.name ?? '').trim().slice(0, 60);
      if (name.length < 2) return reply(422, { errors: { templateName: 'Anna pohjalle nimi.' } });
      const { data } = validateBid(body, { requireAll: false });
      const { estimateLow, estimateHigh, askingPrice, ...rest } = data;
      const fields = Object.fromEntries(Object.entries(rest).filter(([, v]) => v !== null && v !== '' && !Number.isNaN(v)));
      const saved = await templates.upsert((x) => x.agentId === agent.id && x.name === name, { agentId: agent.id, name, fields });
      return reply(201, { template: saved.row });
    }],

    ['DELETE', /^\/api\/agent\/templates\/([\w-]+)$/, async ({ params: [id], session }) => {
      const agent = await ctx.currentAgent(session);
      if (!agent) return unauthorized();
      const tpl = (await templates.all()).find((x) => x.id === id && x.agentId === agent.id);
      if (!tpl) return notFound();
      await templates.remove(id);
      return reply(200, { ok: true });
    }],
  ];
}
