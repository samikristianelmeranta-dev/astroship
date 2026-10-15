// Myyjän (kuluttajan) reitit: myyntikohde, vahvistus, arviot, kysymykset ja lisäkuvat,
// välittäjien valinta, arvio välittäjästä, kirjautuminen sähköpostilinkillä ja jono.
import { validateListing, validateMessageText, validatePhotoIds, validateReview, validateWaitlist } from '../validate.js';
import { deadlineFrom, isOpen, canSee, isDecided, contactOf, acceptedIds, MAX_CHOSEN } from '../tender.js';
import { offerFigures, referencePrice } from '../compare.js';
import { reply, notFound, unauthorized } from './util.js';

const LOGIN_TTL_MS = 30 * 60 * 1000;
const TOKEN_RE = '([a-f0-9]{32,})';
// Kohteessa voi olla lisäkuvien kanssa enintään näin monta kuvaa.
const MAX_TOTAL_PHOTOS = 30;
const eur = (n) => `${new Intl.NumberFormat('fi-FI').format(n)} €`;

export function consumerRoutes(ctx) {
  const { requests, bids, agents, photos, questions, reviews, logins, waitlist, fees } = ctx.stores;

  async function publish(request) {
    const t = ctx.now();
    const updated = await requests.update(request.id, (r) =>
      r.status === 'vahvistamaton'
        ? { status: 'avoin', verifiedAt: ctx.iso(), deadline: deadlineFrom(t, r.deadlineDays) }
        : null,
    );
    if (!updated) return null;
    await ctx.track('vahvistettu');
    for (const agent of await agents.all()) {
      if (!canSee(agent, updated, ctx.geo)) continue;
      await ctx.notify(
        { email: agent.notifyEmail === false ? null : agent.email, phone: agent.phone, sms: agent.notifySms },
        'uusi_kohde',
        { title: ctx.listingTitle(updated), deadline: updated.deadline },
      );
    }
    return updated;
  }

  // Liittää ladatut kuvat kohteeseen. Kuvan on oltava ladattu eikä jo toiseen kohteeseen kuuluva.
  async function attachPhotos(request, ids) {
    const all = await photos.all();
    if (!ids.every((id) => all.some((p) => p.id === id && !p.requestId && p.kind === 'kohde'))) return false;
    if ((request.photoIds?.length ?? 0) + ids.length > MAX_TOTAL_PHOTOS) return false;
    for (const id of ids) await photos.update(id, (p) => (p.requestId ? null : { requestId: request.id }));
    await requests.update(request.id, (r) => ({ photoIds: [...(r.photoIds ?? []), ...ids.filter((id) => !r.photoIds?.includes(id))] }));
    return true;
  }

  // Välittäjät, jotka ovat jo osoittaneet kiinnostusta kohteeseen (kysyneet tai jättäneet arvion).
  async function interestedAgents(request, extra = []) {
    const ids = new Set([
      ...extra,
      ...(await bids.all()).filter((b) => b.requestId === request.id).map((b) => b.agentId),
      ...(await questions.all()).filter((q) => q.requestId === request.id).map((q) => q.agentId),
    ]);
    return (await agents.all()).filter((a) => ids.has(a.id) && a.notifyEmail !== false);
  }

  async function projectView(request) {
    const open = isOpen(request, ctx.now());
    const own = (await bids.all()).filter((b) => b.requestId === request.id);
    const qs = (await questions.all())
      .filter((q) => q.requestId === request.id)
      .map(({ id, text, wantsPhotos, answer, answerPhotoIds, createdAt, answeredAt }) => ({
        id, text, wantsPhotos: Boolean(wantsPhotos), answer, answerPhotoIds: answerPhotoIds ?? [], createdAt, answeredAt,
      }));
    let offers = [];
    let winners = [];
    let reference = null;
    if (!open && request.status !== 'vahvistamaton') {
      const byId = Object.fromEntries((await agents.all()).map((a) => [a.id, a]));
      const ratings = ctx.ratingsByAgent(await reviews.all());
      reference = referencePrice(own);
      offers = own
        .map((b) => {
          const agent = byId[b.agentId] ?? {};
          const { agentId, requestId, ...fields } = b;
          return {
            ...fields,
            ...offerFigures(b, reference),
            company: agent.company,
            chain: agent.chain,
            companyDescription: agent.description,
            logoPhotoId: agent.logoPhotoId ?? null,
            rating: ratings[agent.id] ? { avg: ratings[agent.id].avg, count: ratings[agent.id].count } : null,
            verified: Boolean(agent.prh?.active && agent.registryCheckedAt),
          };
        })
        .sort((a, b) => a.costAtReference - b.costAtReference);
      winners = acceptedIds(request)
        .map((id) => own.find((b) => b.id === id))
        .filter(Boolean)
        .map((b) => {
          const agent = byId[b.agentId] ?? {};
          return { bidId: b.id, company: agent.company, contactName: agent.contactName, email: agent.email, phone: agent.phone, website: agent.website };
        });
    }
    const review = (await reviews.all()).find((r) => r.requestId === request.id);
    const { consumerToken, ...project } = request;
    return {
      project: { ...project, acceptedBidIds: acceptedIds(request) },
      open,
      bidCount: own.length,
      maxChosen: MAX_CHOSEN,
      referencePrice: reference,
      offers,
      winners,
      questions: qs,
      review: review ? { rating: review.rating, text: review.text, bidId: review.bidId ?? null } : null,
    };
  }

  return [
    ['POST', /^\/api\/requests$/, async ({ body }) => {
      const { ok, errors, data } = validateListing(body);
      if (!ok) return reply(422, { errors });
      if (!ctx.inPilot(data.region)) {
        return reply(422, {
          errors: { region: `Palvelu toimii toistaiseksi alueella ${ctx.config.pilotRegions.join(', ')}. Liity jonoon, niin kerromme kun laajennamme.` },
          pilot: false,
        });
      }
      const { consent, photoIds, ...fields } = data;
      const allPhotos = await photos.all();
      if (!photoIds.every((id) => allPhotos.some((p) => p.id === id && !p.requestId && p.kind === 'kohde')))
        return reply(422, { errors: { photos: 'Kuvan lataus epäonnistui. Poista kuva ja lisää se uudelleen.' } });
      const token = ctx.newToken();
      const saved = await requests.insert({
        ...fields,
        photoIds,
        deadline: null,
        status: 'vahvistamaton',
        consumerToken: token,
        acceptedBidIds: [],
      });
      for (const id of photoIds) await photos.update(id, (p) => (p.requestId ? null : { requestId: saved.id }));
      await ctx.notify({ email: saved.email }, 'vahvistus', { name: saved.name, title: ctx.listingTitle(saved), token });
      await ctx.track('lahetetty');
      return reply(201, {
        ok: true,
        email: saved.email,
        ...(ctx.config.devLinks ? { devLink: ctx.links.project(token) } : {}),
      });
    }],

    // Linkin avaaminen sähköpostista todistaa osoitteen ja julkaisee kohteen.
    ['POST', new RegExp(`^/api/projects/${TOKEN_RE}/verify$`), async ({ params: [token] }) => {
      const request = await ctx.requestByToken(token);
      if (!request) return notFound();
      const published = request.status === 'vahvistamaton' ? await publish(request) : null;
      return reply(200, { verified: Boolean(published) });
    }],

    ['GET', new RegExp(`^/api/projects/${TOKEN_RE}$`), async ({ params: [token] }) => {
      const request = await ctx.requestByToken(token);
      return request ? reply(200, await projectView(request)) : notFound();
    }],

    // Myyjä lisää kuvia kohteeseen arvioajan aikana, esimerkiksi välittäjän pyynnöstä.
    ['POST', new RegExp(`^/api/projects/${TOKEN_RE}/photos$`), async ({ params: [token], body }) => {
      const request = await ctx.requestByToken(token);
      if (!request) return notFound();
      if (!isOpen(request, ctx.now())) return reply(409, { error: 'Kuvia voi lisätä vain arvioajan aikana.' });
      const { ok, errors, photoIds } = validatePhotoIds(body.photoIds);
      if (!ok) return reply(422, { errors });
      if (!(await attachPhotos(request, photoIds)))
        return reply(422, { errors: { photos: `Kuvien lisäys epäonnistui. Kohteessa voi olla enintään ${MAX_TOTAL_PHOTOS} kuvaa.` } });
      for (const agent of await interestedAgents(request)) {
        await ctx.notify({ email: agent.email }, 'lisakuvia', { title: ctx.listingTitle(request), count: photoIds.length });
      }
      return reply(201, { ok: true });
    }],

    // Myyjä valitsee yhden tai kaksi välittäjää kerralla. Valitut saavat yhteystiedot,
    // muut tiedon ratkaisusta. Jokaisesta valitusta kirjataan palvelumaksu.
    ['POST', new RegExp(`^/api/projects/${TOKEN_RE}/accept$`), async ({ params: [token], body }) => {
      const request = await ctx.requestByToken(token);
      if (!request) return notFound();
      if (request.status === 'avoin' && isOpen(request, ctx.now()))
        return reply(409, { error: 'Arviot avautuvat valittaviksi määräajan päätyttyä.' });
      const ids = [...new Set(Array.isArray(body.bidIds) ? body.bidIds : [])].map(String);
      if (ids.length < 1 || ids.length > MAX_CHOSEN)
        return reply(422, { error: `Valitse yksi tai enintään ${MAX_CHOSEN} välittäjää.` });
      const own = (await bids.all()).filter((b) => b.requestId === request.id);
      const chosen = ids.map((id) => own.find((b) => b.id === id));
      if (chosen.some((b) => !b)) return reply(422, { error: 'Valitse saapuneista arvioista.' });
      const updated = await requests.update(request.id, (r) =>
        r.status === 'avoin' ? { status: 'valittu', acceptedBidIds: ids, acceptedAt: ctx.iso() } : null,
      );
      if (!updated) return reply(409, { error: 'Olet jo tehnyt valinnan tähän kohteeseen.' });
      await ctx.track('valittu');

      const byId = Object.fromEntries((await agents.all()).map((a) => [a.id, a]));
      const feeActive = ctx.config.successFeeEur > 0 && (!ctx.config.feeStartDate || ctx.iso() >= ctx.config.feeStartDate);
      const title = ctx.listingTitle(request);
      for (const bid of chosen) {
        const agent = byId[bid.agentId];
        if (feeActive) {
          await fees.insert({ agentId: bid.agentId, requestId: request.id, bidId: bid.id, amountEur: ctx.config.successFeeEur, invoiced: false });
        }
        await ctx.notify({ email: agent.email, phone: agent.phone, sms: agent.notifySms }, 'valittu', {
          title, contact: contactOf(updated), fee: feeActive ? ctx.config.successFeeEur : null, shared: chosen.length > 1,
        });
      }
      for (const other of own.filter((b) => !ids.includes(b.id))) {
        const agent = byId[other.agentId];
        if (agent) await ctx.notify({ email: agent.email }, 'ei_valittu', { title });
      }
      await ctx.notify({ email: request.email }, 'valinta_vahvistus', {
        name: request.name,
        token,
        chosen: chosen.map((b) => ({ company: byId[b.agentId].company, estimate: `${eur(b.estimateLow)}–${eur(b.estimateHigh)}`, agent: byId[b.agentId] })),
      });
      return reply(200, { ok: true });
    }],

    // Kohteen voi perua, kunnes välittäjä on valittu.
    ['POST', new RegExp(`^/api/projects/${TOKEN_RE}/cancel$`), async ({ params: [token] }) => {
      const request = await ctx.requestByToken(token);
      if (!request) return notFound();
      const updated = await requests.update(request.id, (r) =>
        ['vahvistamaton', 'avoin'].includes(r.status) ? { status: 'peruttu', cancelledAt: ctx.iso() } : null,
      );
      return updated ? reply(200, { ok: true }) : reply(409, { error: 'Pyyntöä ei voi enää perua.' });
    }],

    // Vastaus välittäjän kysymykseen: teksti, kuvat tai molemmat. Kuvat lisätään
    // kohteeseen, joten kaikki kohteen välittäjät näkevät ne.
    ['POST', new RegExp(`^/api/projects/${TOKEN_RE}/questions/([\\w-]+)/answer$`), async ({ params: [token, qid], body }) => {
      const request = await ctx.requestByToken(token);
      if (!request) return notFound();
      const question = (await questions.all()).find((q) => q.id === qid && q.requestId === request.id);
      if (!question) return notFound();
      if (!isOpen(request, ctx.now())) return reply(409, { error: 'Arvioaika on päättynyt.' });
      const photoIds = validatePhotoIds(body.photoIds ?? [], { min: 0 });
      if (!photoIds.ok) return reply(422, { errors: photoIds.errors });
      const hasPhotos = photoIds.photoIds.length > 0;
      const { ok, errors, text } = validateMessageText(body.answer, { min: hasPhotos ? 0 : 2, field: 'answer' });
      if (!ok) return reply(422, { errors });
      if (hasPhotos && !(await attachPhotos(request, photoIds.photoIds)))
        return reply(422, { errors: { photos: `Kuvien lisäys epäonnistui. Kohteessa voi olla enintään ${MAX_TOTAL_PHOTOS} kuvaa.` } });
      await questions.update(qid, (q) => ({
        answer: text || q.answer || (hasPhotos ? 'Lisäsin kuvia.' : ''),
        answerPhotoIds: [...(q.answerPhotoIds ?? []), ...photoIds.photoIds],
        answeredAt: ctx.iso(),
      }));
      for (const agent of await interestedAgents(request, [question.agentId])) {
        await ctx.notify({ email: agent.email }, 'vastaus', {
          title: ctx.listingTitle(request), question: question.text, answer: text, photoCount: photoIds.photoIds.length,
        });
      }
      return reply(200, { ok: true });
    }],

    ['POST', new RegExp(`^/api/projects/${TOKEN_RE}/complete$`), async ({ params: [token] }) => {
      const request = await ctx.requestByToken(token);
      if (!request) return notFound();
      const updated = await requests.update(request.id, (r) => (r.status === 'valittu' ? { status: 'valmis', completedAt: ctx.iso() } : null));
      return updated ? reply(200, { ok: true }) : reply(409, { error: 'Merkitse myynti valmiiksi vasta, kun olet valinnut välittäjän.' });
    }],

    // Arvio annetaan välittäjälle, joka hoiti myynnin. Jos valittuja oli kaksi, myyjä kertoo kumpi.
    ['POST', new RegExp(`^/api/projects/${TOKEN_RE}/review$`), async ({ params: [token], body }) => {
      const request = await ctx.requestByToken(token);
      if (!request) return notFound();
      if (!isDecided(request)) return reply(409, { error: 'Voit arvioida välittäjän, kun olet valinnut hänet.' });
      const { ok, errors, data } = validateReview(body);
      if (!ok) return reply(422, { errors });
      const chosenIds = acceptedIds(request);
      const bidId = chosenIds.length === 1 ? chosenIds[0] : String(body.bidId ?? '');
      if (!chosenIds.includes(bidId)) return reply(422, { errors: { bidId: 'Valitse, kumpi välittäjä hoiti myynnin.' } });
      const bid = (await bids.all()).find((b) => b.id === bidId);
      if ((await reviews.all()).some((r) => r.requestId === request.id)) return reply(409, { error: 'Olet jo arvioinut välittäjän.' });
      await reviews.insert({ requestId: request.id, agentId: bid.agentId, bidId, ...data, hidden: false });
      await requests.update(request.id, (r) => (r.status === 'valittu' ? { status: 'valmis', completedAt: ctx.iso(), completedBidId: bidId } : {}));
      return reply(201, { ok: true });
    }],

    // --- Kirjautuminen sähköpostilinkillä --------------------------------
    // Vastaus on aina sama, jotta sähköpostiosoitteiden olemassaoloa ei voi arvailla.
    ['POST', /^\/api\/consumer\/login$/, async ({ body }) => {
      const email = String(body.email ?? '').trim().toLowerCase();
      const has = (await requests.all()).some((r) => r.email === email);
      let devLink;
      if (has) {
        const token = ctx.newToken();
        await logins.insert({ email, token, expiresAt: new Date(ctx.now() + LOGIN_TTL_MS).toISOString(), usedAt: null });
        await ctx.notify({ email }, 'kirjautuminen', { token });
        if (ctx.config.devLinks) devLink = ctx.links.consumerLogin(token);
      }
      return reply(200, { ok: true, ...(devLink ? { devLink } : {}) });
    }],

    ['POST', /^\/api\/consumer\/session$/, async ({ body }) => {
      const token = String(body.token ?? '');
      const login = (await logins.all()).find((l) => l.token === token);
      if (!login || login.usedAt || Date.parse(login.expiresAt) < ctx.now())
        return reply(401, { error: 'Linkki on vanhentunut tai jo käytetty. Pyydä uusi linkki.' });
      await logins.update(login.id, { usedAt: ctx.iso() });
      return reply(200, { ok: true }, { session: { role: 'consumer', email: login.email } });
    }],

    ['GET', /^\/api\/consumer\/requests$/, async ({ session }) => {
      if (session?.role !== 'consumer') return unauthorized();
      const allBids = await bids.all();
      const list = (await requests.all())
        .filter((r) => r.email === session.email)
        .map((r) => ({
          token: r.consumerToken,
          title: ctx.listingTitle(r),
          streetAddress: r.streetAddress,
          status: r.status,
          open: isOpen(r, ctx.now()),
          deadline: r.deadline,
          createdAt: r.createdAt,
          bidCount: allBids.filter((b) => b.requestId === r.id).length,
        }))
        .reverse();
      return reply(200, { email: session.email, requests: list });
    }],

    ['POST', /^\/api\/waitlist$/, async ({ body }) => {
      const { ok, errors, data } = validateWaitlist(body);
      if (!ok) return reply(422, { errors });
      await waitlist.upsert((w) => w.email === data.email && w.region === data.region, { ...data, notifiedAt: null });
      return reply(201, { ok: true });
    }],
  ];
}
