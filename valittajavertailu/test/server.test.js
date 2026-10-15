import { test, before, after } from 'node:test';
import assert from 'node:assert/strict';
import { mkdtemp, rm } from 'node:fs/promises';
import { tmpdir } from 'node:os';
import path from 'node:path';
import { createServer } from '../server.js';
import { validRequest, agent, bid, TINY_JPEG, YTJ_SAMPLE, COORDS } from './fixtures.js';

const ADMIN = 'salainen-testitunnus';
const DAY = 864e5;
let server, base, dir;
let clock = Date.parse('2026-10-14T12:00:00Z');

before(async () => {
  dir = await mkdtemp(path.join(tmpdir(), 'valittaja-'));
  server = createServer({
    dataDir: dir,
    adminToken: ADMIN,
    sessionSecret: 'testi',
    messenger: null,
    businessLookup: async () => YTJ_SAMPLE,
    postalCoordinates: COORDS,
    config: { pilotRegions: ['Varsinais-Suomi'], successFeeEur: 149 },
    now: () => clock,
  });
  await new Promise((r) => server.listen(0, r));
  base = `http://127.0.0.1:${server.address().port}`;
});

after(async () => {
  server.close();
  await rm(dir, { recursive: true, force: true });
});

// Pieni asiakas, joka muistaa istuntoevästeen kuten selain.
function client() {
  let cookie = '';
  const call = async (method, p, body) => {
    const res = await fetch(base + p, {
      method,
      headers: { 'Content-Type': 'application/json', Cookie: cookie },
      body: body === undefined ? undefined : JSON.stringify(body),
    });
    const set = res.headers.get('set-cookie');
    if (set) cookie = set.split(';')[0];
    const text = await res.text();
    let json = null;
    try {
      json = JSON.parse(text);
    } catch {}
    return { status: res.status, body: json, text, headers: res.headers };
  };
  return {
    cookie: () => cookie,
    get: (p) => call('GET', p),
    post: (p, b = {}) => call('POST', p, b),
    put: (p, b = {}) => call('PUT', p, b),
    del: (p) => call('DELETE', p, {}),
  };
}

const admin = client();
const outbox = async () => (await admin.get('/api/admin/outbox')).body.messages;
const sentTo = async (to, kind) => (await outbox()).filter((m) => m.to === to && m.kind === kind);
const tokenFrom = (link) => link.split('#')[1];

async function uploadPhotos(u, n = 3) {
  const ids = [];
  for (let i = 0; i < n; i++) ids.push((await u.post('/api/photos', { dataUrl: TINY_JPEG })).body.id);
  return ids;
}

// Myyjä lataa kolme kuvaa ja jättää kohteen. Palauttaa vastauksen ja tunnuksen.
async function createListing(u, extra = {}, { verify = true } = {}) {
  const photoIds = await uploadPhotos(u);
  const res = await u.post('/api/requests', { ...validRequest, photoIds, ...extra });
  const token = res.body?.devLink ? tokenFrom(res.body.devLink) : null;
  if (token && verify) await u.post(`/api/projects/${token}/verify`);
  return { res, token, photoIds };
}

const login = async (n) => {
  const u = client();
  await u.post('/api/login', { email: `valittaja${n}@example.fi`, password: 'salasana-123' });
  return u;
};

test('sivut tarjoillaan ja polkujen ohitus estetään', async () => {
  for (const p of ['/', '/kilpailuta', '/kohde', '/omat', '/valittajille', '/valittaja', '/tietosuoja', '/kayttoehdot', '/valittajaehdot', '/admin', '/styles.css', '/api/health']) {
    const res = await fetch(base + p);
    assert.equal(res.status, 200, p);
  }
  assert.equal((await fetch(base + '/..%2Fserver.js')).status, 404);
});

test('hakukonesivut, sivukartta ja robots', async () => {
  const city = await fetch(base + '/kiinteistonvalittaja/turku');
  assert.equal(city.status, 200);
  const html = await city.text();
  assert.ok(html.includes('<h1>Kiinteistönvälittäjä Turussa</h1>'));
  assert.ok(html.includes('application/ld+json'));
  assert.equal((await fetch(base + '/kiinteistonvalittaja')).status, 200);
  assert.equal((await fetch(base + '/oppaat/valityspalkkio')).status, 200);
  assert.equal((await fetch(base + '/kiinteistonvalittaja/olematon')).status, 404);
  const sitemap = await (await fetch(base + '/sitemap.xml')).text();
  assert.ok(sitemap.includes('/kiinteistonvalittaja/jyvaskyla'));
  const robots = await (await fetch(base + '/robots.txt')).text();
  assert.ok(robots.includes('Disallow: /valittaja$') && robots.includes('Sitemap:'));
});

test('koko kilpailutus: vahvistus, kysymykset ja kuvapyynnöt, suljetut arviot, vertailu, valinta, maksu ja arvio', async () => {
  const [a, b, c] = [client(), client(), client()];
  const consumer = client();
  assert.equal((await admin.post('/api/admin/login', { token: ADMIN })).status, 200);

  // Välittäjät: a ja b Varsinais-Suomessa, c Lapissa. YTJ-tiedot haetaan rekisteröinnissä.
  assert.equal((await a.post('/api/agents', agent(0))).status, 201);
  assert.equal((await b.post('/api/agents', agent(1))).status, 201);
  assert.equal((await c.post('/api/agents', agent(2, { regions: ['Lappi'] }))).status, 201);
  assert.equal((await client().post('/api/agents', { ...agent(0), email: 'toinen@example.fi', licensed: false })).status, 422);
  assert.equal((await client().post('/api/agents', agent(0))).status, 409, 'sama sähköposti');
  const me = (await a.get('/api/agent')).body.agent;
  assert.equal(me.prh.active, true);
  assert.equal(me.prh.name, 'Esimerkki Kodit Oy LKV');
  assert.equal(me.passwordHash, undefined);

  // Hyväksyntä vaatii välitysliikerekisterin tarkistuksen.
  const ids = (await admin.get('/api/admin/overview')).body.agents.map((x) => x.id);
  assert.equal((await admin.post(`/api/admin/agents/${ids[0]}/approve`, {})).status, 422);
  for (const id of ids) assert.equal((await admin.post(`/api/admin/agents/${id}/approve`, { registryChecked: true })).status, 200);
  assert.equal((await sentTo('valittaja0@example.fi', 'valittaja_hyvaksytty')).length, 1);

  // Vähintään kolme kuvaa vaaditaan.
  const two = await uploadPhotos(consumer, 2);
  const tooFew = await consumer.post('/api/requests', { ...validRequest, photoIds: two });
  assert.equal(tooFew.status, 422);
  assert.ok(tooFew.body.errors.photos);

  // Myyjä jättää kohteen. Kohde odottaa sähköpostivahvistusta.
  const { res: created, token } = await createListing(consumer, {}, { verify: false });
  assert.equal(created.status, 201);
  assert.equal(created.body.token, undefined, 'tunnus tulee vain sähköpostiin');
  const verifyMail = await sentTo('matti@example.fi', 'vahvistus');
  assert.equal(verifyMail.length, 1);
  assert.ok(verifyMail[0].text.includes(token));
  assert.ok(verifyMail[0].text.includes('Kaksio 54,5 m², Turku'));
  assert.equal((await a.get('/api/agent')).body.open.length, 0, 'vahvistamaton ei näy');

  // Vahvistus julkaisee kohteen ja ilmoittaa alueen välittäjille.
  assert.equal((await consumer.post(`/api/projects/${token}/verify`)).body.verified, true);
  assert.equal((await consumer.post(`/api/projects/${token}/verify`)).body.verified, false);
  assert.equal((await sentTo('valittaja0@example.fi', 'uusi_kohde')).length, 1);
  assert.equal((await sentTo('valittaja1@example.fi', 'uusi_kohde')).length, 1);
  assert.equal((await sentTo('valittaja2@example.fi', 'uusi_kohde')).length, 0);

  const project = (await a.get('/api/agent')).body.open[0];
  assert.equal(project.streetAddress, undefined);
  assert.equal(project.name, undefined);
  assert.equal(project.email, undefined);
  assert.equal(project.priceExpectation, 210000);
  assert.equal(project.area, 54.5);
  assert.equal(project.photoIds.length, 3);
  assert.equal((await c.get('/api/agent')).body.open.length, 0);

  // Kysymys ja vastaus näkyvät kaikille kohteen välittäjille; yhteystietoja ei saa jakaa.
  assert.equal((await a.post(`/api/agent/questions/${project.id}`, { text: 'Soita minulle 040 765 4321' })).status, 422);
  assert.equal((await a.post(`/api/agent/questions/${project.id}`, { text: 'Milloin putkiremontti on tehty?' })).status, 201);
  // Kuvapyyntö: myyjä vastaa lisäämällä kuvia.
  assert.equal((await b.post(`/api/agent/questions/${project.id}`, { text: 'Voisitko lisätä kuvan saunasta?', wantsPhotos: true })).status, 201);
  assert.equal((await sentTo('matti@example.fi', 'kysymys')).length, 2);
  const qView = (await consumer.get(`/api/projects/${token}`)).body.questions;
  assert.deepEqual(qView.map((q) => q.wantsPhotos), [false, true]);
  assert.equal((await consumer.post(`/api/projects/${token}/questions/${qView[0].id}/answer`, { answer: 'Vuonna 2015.' })).status, 200);
  assert.equal((await consumer.post(`/api/projects/${token}/questions/${qView[1].id}/answer`, { answer: '' })).status, 422, 'tyhjä vastaus ilman kuvia');
  const saunaPhotos = await uploadPhotos(consumer, 2);
  assert.equal((await consumer.post(`/api/projects/${token}/questions/${qView[1].id}/answer`, { photoIds: saunaPhotos })).status, 200);
  assert.equal((await consumer.post(`/api/projects/${token}/questions/${qView[1].id}/answer`, { photoIds: saunaPhotos })).status, 422, 'samaa kuvaa ei liitetä kahdesti');

  const bOpen = (await b.get('/api/agent')).body.open[0];
  assert.deepEqual(bOpen.questions.map((q) => [q.text, q.answer, q.mine]), [
    ['Milloin putkiremontti on tehty?', 'Vuonna 2015.', false],
    ['Voisitko lisätä kuvan saunasta?', 'Lisäsin kuvia.', true],
  ]);
  assert.equal(bOpen.photoIds.length, 5, 'vastauksen kuvat lisätään kohteeseen');
  assert.deepEqual(bOpen.questions[1].answerPhotoIds, saunaPhotos);
  assert.equal((await fetch(`${base}/api/photos/${saunaPhotos[0]}`, { headers: { Cookie: a.cookie() } })).status, 200, 'kaikki välittäjät näkevät');
  assert.equal((await sentTo('valittaja0@example.fi', 'vastaus')).length, 2);

  // Myyjä voi lisätä kuvia myös ilman pyyntöä.
  const extra = await uploadPhotos(consumer, 1);
  assert.equal((await consumer.post(`/api/projects/${token}/photos`, { photoIds: extra })).status, 201);
  assert.equal((await a.get('/api/agent')).body.open[0].photoIds.length, 6);
  assert.equal((await sentTo('valittaja1@example.fi', 'lisakuvia')).length, 1);

  // Vakiomuotoiset arviot. a: prosenttipalkkio, b: kiinteä palkkio.
  assert.equal((await a.put(`/api/agent/bids/${project.id}`, bid(200000, 400000))).status, 422, 'liian leveä haarukka');
  assert.equal((await a.put(`/api/agent/bids/${project.id}`, bid(200000, 212000, { description: 'lyhyt' }))).status, 422);
  assert.equal((await a.put(`/api/agent/bids/${project.id}`, bid(195000, 205000))).status, 201);
  assert.equal((await a.put(`/api/agent/bids/${project.id}`, bid(200000, 212000))).status, 200);
  assert.equal((await b.put(`/api/agent/bids/${project.id}`, bid(205000, 220000, { commissionType: 'fixed', commissionFixedEur: 4900 }))).status, 201);
  assert.equal((await c.put(`/api/agent/bids/${project.id}`, bid(1000, 1200))).status, 404);

  const aView = JSON.stringify((await a.get('/api/agent')).body);
  const bView = JSON.stringify((await b.get('/api/agent')).body);
  assert.ok(aView.includes('212000') && !aView.includes('220000') && !aView.includes('Välittäjä 1 Oy'));
  assert.ok(bView.includes('220000') && !bView.includes('212000') && !bView.includes('Välittäjä 0 Oy'));

  let p = await consumer.get(`/api/projects/${token}`);
  assert.equal(p.body.bidCount, 2);
  assert.deepEqual(p.body.offers, [], 'arviot suljettu määräaikaan asti');
  assert.equal((await consumer.post(`/api/projects/${token}/accept`, { bidIds: ['x'] })).status, 409);

  // Määräaika umpeutuu: ajastettu työ ilmoittaa myyjälle ja arvion jättäneille.
  clock += 7 * DAY + 1;
  assert.equal((await a.put(`/api/agent/bids/${project.id}`, bid(1000, 1100))).status, 409);
  assert.equal((await a.post(`/api/agent/questions/${project.id}`, { text: 'Vielä yksi kysymys?' })).status, 409);
  assert.equal((await consumer.post(`/api/projects/${token}/photos`, { photoIds: await uploadPhotos(consumer, 1) })).status, 409);
  assert.equal((await server.api.runJobs()).closed, 1);
  assert.equal((await server.api.runJobs()).closed, 0, 'ilmoitus vain kerran');
  assert.equal((await sentTo('matti@example.fi', 'tarjoukset_auki')).length, 1);
  assert.equal((await sentTo('valittaja1@example.fi', 'kilpailu_paattyi')).length, 1);

  // Vertailu: kulut samalla viitehinnalla (arvioiden keskikohtien mediaani tuhansiin pyöristettynä).
  p = await consumer.get(`/api/projects/${token}`);
  assert.equal(p.body.referencePrice, 209000); // (206 000 + 212 500) / 2 = 209 250
  const [cheap, dear] = p.body.offers;
  assert.deepEqual([cheap.company, cheap.costAtReference, dear.costAtReference], ['Välittäjä 1 Oy LKV', 4900, 7315]);
  assert.equal(dear.commissionAtOwn, 7210); // 206 000 × 3,5 %
  assert.equal(cheap.netAtReference, 209000 - 4900);
  assert.equal(cheap.verified, true);
  assert.equal(cheap.agentId, undefined);
  assert.equal(cheap.website, undefined, 'verkkosivu vasta valinnan jälkeen');

  // Valinta: palvelumaksu, viestit valitulle, muille ja myyjälle.
  assert.equal((await consumer.post(`/api/projects/${token}/accept`, { bidIds: [cheap.id] })).status, 200);
  assert.equal((await consumer.post(`/api/projects/${token}/accept`, { bidIds: [dear.id] })).status, 409);
  const won = await sentTo('valittaja1@example.fi', 'valittu');
  assert.equal(won.length, 1);
  assert.ok(won[0].text.includes('Esimerkkikatu 5 B 12, 20100 Turku'));
  assert.equal((await sentTo('valittaja0@example.fi', 'ei_valittu')).length, 1);
  assert.equal((await sentTo('matti@example.fi', 'valinta_vahvistus')).length, 1);
  const fees = (await admin.get('/api/admin/fees')).body;
  assert.deepEqual(fees.fees.map((f) => [f.company, f.amountEur]), [['Välittäjä 1 Oy LKV', 149]]);
  const csv = await admin.get('/api/admin/fees.csv');
  assert.ok(csv.headers.get('content-disposition').includes('palvelumaksut.csv'));
  assert.ok(csv.text.includes('Välittäjä 1 Oy LKV') && csv.text.includes('149'));

  // Valitsematon ei näe valittua, palkkiota eikä osoitetta.
  const lost = (await a.get('/api/agent')).body;
  assert.equal(lost.history[0].outcome, 'ei_valittu');
  assert.ok(!JSON.stringify(lost).includes('4900') && !JSON.stringify(lost).includes('Esimerkkikatu'));

  // Arvio: vain kerran, näkyy välittäjälle.
  assert.equal((await consumer.post(`/api/projects/${token}/review`, { rating: 7 })).status, 422);
  assert.equal((await consumer.post(`/api/projects/${token}/review`, { rating: 5, text: 'Asiantunteva ja nopea.' })).status, 201);
  assert.equal((await consumer.post(`/api/projects/${token}/review`, { rating: 1 })).status, 409);
  const bNow = (await b.get('/api/agent')).body;
  assert.deepEqual(bNow.rating, { avg: 5, count: 1 });
  assert.equal(bNow.history[0].review.rating, 5);
  assert.equal((await consumer.get(`/api/projects/${token}`)).body.project.status, 'valmis');

  // Ylläpito voi piilottaa arvion.
  const reviewId = (await admin.get('/api/admin/reviews')).body.reviews[0].id;
  await admin.post(`/api/admin/reviews/${reviewId}/hide`);
  assert.equal((await b.get('/api/agent')).body.rating, null);

  // Mittarit kirjautuivat.
  const funnel = Object.fromEntries((await admin.get('/api/admin/funnel')).body.steps.map((s) => [s.name, s.count]));
  assert.deepEqual([funnel.lahetetty, funnel.vahvistettu, funnel.tarjous, funnel.valittu], [1, 1, 2, 1]);
});

test('pilottialueen ulkopuolella liitytään jonoon', async () => {
  const { res } = await createListing(client(), { postalCode: '33100', city: 'Tampere', region: '' }, { verify: false });
  assert.equal(res.status, 422);
  assert.equal(res.body.pilot, false);
  const cov = (await client().get('/api/coverage?postalCode=33100')).body;
  assert.deepEqual([cov.region, cov.inPilot], ['Pirkanmaa', false]);
  assert.equal((await client().get('/api/coverage?postalCode=20100')).body.agents, 2);
  assert.equal((await client().post('/api/waitlist', { email: 'liisa@example.fi', postalCode: '33100' })).status, 201);
  assert.equal((await admin.get('/api/admin/waitlist')).body.byRegion.Pirkanmaa, 1);
  assert.equal((await admin.post('/api/admin/waitlist/notify', { region: 'Pirkanmaa' })).body.sent, 1);
  assert.equal((await sentTo('liisa@example.fi', 'jono_avattu')).length, 1);
});

test('myyjä kirjautuu sähköpostilinkillä ja näkee omat kohteensa', async () => {
  const u = client();
  assert.equal((await u.post('/api/consumer/login', { email: 'tuntematon@example.fi' })).body.devLink, undefined);
  const { devLink } = (await u.post('/api/consumer/login', { email: 'MATTI@example.fi' })).body;
  assert.ok(devLink);
  assert.equal((await u.get('/api/consumer/requests')).status, 401);
  assert.equal((await u.post('/api/consumer/session', { token: tokenFrom(devLink) })).status, 200);
  assert.equal((await client().post('/api/consumer/session', { token: tokenFrom(devLink) })).status, 401, 'kertakäyttöinen');
  const mine = (await u.get('/api/consumer/requests')).body;
  assert.equal(mine.requests.length, 1);
  assert.equal(mine.requests[0].bidCount, 2);
  assert.equal(mine.requests[0].title, 'Kaksio 54,5 m², Turku');
});

test('säde toimiston postinumerosta, profiili, logo ja palkkiopohjat', async () => {
  const c = await login(2);
  // Lapin välittäjä laajentaa alueen 100 km säteelle Salosta (24100), joka kattaa Turun.
  const prof = await c.put('/api/agent/profile', {
    regions: ['Lappi'], homePostalCode: '24100', radiusKm: 100, notifyEmail: true, website: 'https://esimerkki.fi', chain: 'Esimerkkiketju',
  });
  assert.equal(prof.status, 200);
  await createListing(client());
  assert.equal((await c.get('/api/agent')).body.open.length, 1);
  assert.equal((await c.put('/api/agent/profile', { regions: [], homePostalCode: '', radiusKm: '' })).status, 422);

  const logo = await c.post('/api/agent/logo', { dataUrl: TINY_JPEG });
  assert.equal(logo.status, 201);
  assert.equal((await fetch(`${base}/api/photos/${logo.body.id}`)).status, 200, 'logo on julkinen');

  assert.equal((await c.post('/api/agent/templates', { name: 'Kerrostalo perus', ...bid(100000, 110000, { commissionPercent: '2,9' }) })).status, 201);
  let tpl = (await c.get('/api/agent')).body.templates;
  assert.equal(tpl[0].fields.commissionPercent, 2.9);
  assert.equal(tpl[0].fields.estimateLow, undefined, 'hinta-arviota ei tallenneta pohjaan');
  assert.equal((await c.del(`/api/agent/templates/${tpl[0].id}`)).status, 200);
  assert.equal((await c.get('/api/agent')).body.templates.length, 0);
});

test('myyjä valitsee kaksi välittäjää', async () => {
  // Kolme välittäjää näkee kohteen (c säteen ansiosta edellisestä testistä).
  const [a, b, c] = [await login(0), await login(1), await login(2)];
  const consumer = client();
  const { token } = await createListing(consumer, { email: 'kaksi@example.fi' });
  const id = (await a.get('/api/agent')).body.open.sort((x, y) => y.createdAt.localeCompare(x.createdAt))[0].id;
  await a.put(`/api/agent/bids/${id}`, bid(200000, 210000));
  await b.put(`/api/agent/bids/${id}`, bid(205000, 215000, { commissionPercent: '2,8' }));
  await c.put(`/api/agent/bids/${id}`, bid(220000, 235000, { commissionType: 'fixed', commissionFixedEur: 5900, extraCostsEur: 490 }));
  clock += 7 * DAY + 1;
  await server.api.runJobs();

  const offers = (await consumer.get(`/api/projects/${token}`)).body.offers;
  assert.equal(offers.length, 3);
  const [o1, o2, o3] = offers;
  assert.equal((await consumer.post(`/api/projects/${token}/accept`, { bidIds: [o1.id, o2.id, o3.id] })).status, 422, 'enintään kaksi');
  assert.equal((await consumer.post(`/api/projects/${token}/accept`, { bidIds: [] })).status, 422);
  const feesBefore = (await admin.get('/api/admin/fees')).body.fees.length;
  assert.equal((await consumer.post(`/api/projects/${token}/accept`, { bidIds: [o1.id, o2.id] })).status, 200);
  assert.equal((await consumer.post(`/api/projects/${token}/accept`, { bidIds: [o3.id] })).status, 409, 'valinta tehdään kerran');

  const view = (await consumer.get(`/api/projects/${token}`)).body;
  assert.deepEqual(view.project.acceptedBidIds, [o1.id, o2.id]);
  assert.deepEqual(view.winners.map((w) => w.company).sort(), [o1.company, o2.company].sort());
  assert.ok(view.winners.every((w) => w.email && w.phone));
  assert.equal((await admin.get('/api/admin/fees')).body.fees.length, feesBefore + 2, 'maksu kummallekin');

  // Molemmat valitut saavat yhteystiedot ja tiedon, että valittuja on kaksi; kolmas ei.
  const histories = await Promise.all([a, b, c].map(async (u) => (await u.get('/api/agent')).body.history.find((h) => h.request.id === id)));
  const chosen = histories.filter((h) => h.outcome === 'valittu');
  assert.equal(chosen.length, 2);
  assert.ok(chosen.every((h) => h.sharedSelection && h.contact.email === 'kaksi@example.fi'));
  const lost = histories.find((h) => h.outcome === 'ei_valittu');
  assert.equal(lost.contact, null);
  const confirm = (await sentTo('kaksi@example.fi', 'valinta_vahvistus'))[0];
  assert.ok(confirm.text.includes(o1.company) && confirm.text.includes(o2.company));

  // Arvio: kerrotaan kumpi hoiti myynnin.
  assert.equal((await consumer.post(`/api/projects/${token}/review`, { rating: 4 })).status, 422);
  assert.equal((await consumer.post(`/api/projects/${token}/review`, { rating: 4, bidId: o3.id })).status, 422, 'vain valitulle');
  assert.equal((await consumer.post(`/api/projects/${token}/review`, { rating: 4, bidId: o2.id })).status, 201);
  const i = histories.findIndex((h) => h.myBid.id === o2.id);
  assert.equal((await [a, b, c][i].get('/api/agent')).body.history.find((h) => h.request.id === id).review.rating, 4);
});

test('arviopyyntö lähetetään valinnan jälkeen', async () => {
  const consumer = client();
  const a = await login(0);
  const { token } = await createListing(consumer, { email: 'arvio@example.fi' });
  const id = (await a.get('/api/agent')).body.open.find((r) => r.status === 'avoin' && r.photoIds).id;
  await a.put(`/api/agent/bids/${id}`, bid(200000, 210000));
  clock += 7 * DAY + 1;
  await server.api.runJobs();
  const [offer] = (await consumer.get(`/api/projects/${token}`)).body.offers;
  await consumer.post(`/api/projects/${token}/accept`, { bidIds: [offer.id] });
  clock += 61 * DAY;
  assert.ok((await server.api.runJobs()).reviewRequests >= 1);
  assert.equal((await sentTo('arvio@example.fi', 'arvio_pyynto')).length, 1);
});

test('ajastetut työt siivoavat vahvistamattomat kohteet ja irralliset kuvat', async () => {
  const u = client();
  const orphan = await u.post('/api/photos', { dataUrl: TINY_JPEG });
  await createListing(u, { email: 'hylatty@example.fi' }, { verify: false });
  clock += 4 * DAY;
  const done = await server.api.runJobs();
  assert.ok(done.unverifiedRemoved >= 1);
  assert.ok(done.photosRemoved >= 1);
  const all = (await admin.get('/api/admin/overview')).body.requests;
  assert.ok(!all.some((r) => r.email === 'hylatty@example.fi'));
  assert.ok(orphan.body.id);
});

test('kuvien näkyvyys', async () => {
  const consumer = client();
  const photoIds = await uploadPhotos(consumer);
  assert.equal((await fetch(`${base}/api/photos/${photoIds[0]}`)).status, 404, 'liittämätön');
  const { devLink } = (await consumer.post('/api/requests', { ...validRequest, photoIds })).body;
  const token = tokenFrom(devLink);
  assert.equal((await client().post('/api/requests', { ...validRequest, photoIds })).status, 422, 'kuva kuuluu jo toiseen kohteeseen');
  const a = await login(0);
  assert.equal((await fetch(`${base}/api/photos/${photoIds[0]}`, { headers: { Cookie: a.cookie() } })).status, 404, 'vahvistamaton ei näy välittäjälle');
  await consumer.post(`/api/projects/${token}/verify`);
  assert.equal((await fetch(`${base}/api/photos/${photoIds[0]}`, { headers: { Cookie: a.cookie() } })).status, 200);
  assert.equal((await fetch(`${base}/api/photos/${photoIds[0]}`)).status, 404);
  assert.equal((await fetch(`${base}/api/photos/${photoIds[0]}?t=${token}`)).status, 200);
});

test('peruutus ennen valintaa', async () => {
  const { token } = await createListing(client());
  const consumer = client();
  assert.equal((await consumer.post(`/api/projects/${token}/cancel`)).status, 200);
  assert.equal((await consumer.post(`/api/projects/${token}/cancel`)).status, 409);
  const a = await login(0);
  assert.ok(!(await a.get('/api/agent')).body.open.some((r) => r.status === 'peruttu'));
});

test('kirjautuminen, väärennetty eväste ja virheellinen syöte', async () => {
  const u = client();
  assert.equal((await u.post('/api/login', { email: 'valittaja0@example.fi', password: 'väärä-salasana' })).status, 401);
  const fake = Buffer.from(JSON.stringify({ role: 'admin', exp: Date.now() + 1e9 })).toString('base64url');
  assert.equal((await fetch(base + '/api/admin/overview', { headers: { Cookie: `vv_session=${fake}.abc` } })).status, 401);
  const res = await u.post('/api/requests', { ...validRequest, phone: 'abc', deadlineDays: 4 });
  assert.equal(res.status, 422);
  assert.ok(res.body.errors.phone && res.body.errors.deadlineDays);
  const form = await fetch(base + '/api/requests', { method: 'POST', body: 'a=1', headers: { 'Content-Type': 'application/x-www-form-urlencoded' } });
  assert.equal(form.status, 415);
});
