import { test } from 'node:test';
import assert from 'node:assert/strict';
import { validateListing, validateAgent, validateBid, validatePhotoIds, isValidBusinessId, containsContactInfo } from '../src/validate.js';
import { regionForPostalCode } from '../src/catalog.js';
import { isOpen, canSee, bidOutcome, requestForAgent } from '../src/tender.js';
import { commissionAt, median, referencePrice, offerFigures } from '../src/compare.js';
import { parseYtjCompany } from '../src/prh.js';
import { createGeo } from '../src/geo.js';
import { validRequest, bid, YTJ_SAMPLE, COORDS } from './fixtures.js';

const photos = (n) => Array.from({ length: n }, (_, i) => `kuva-${i}-abcdef`);

test('postinumero → maakunta', () => {
  assert.equal(regionForPostalCode('00100'), 'Uusimaa');
  assert.equal(regionForPostalCode('20100'), 'Varsinais-Suomi');
  assert.equal(regionForPostalCode('22100'), 'Ahvenanmaa');
  assert.equal(regionForPostalCode('33100'), 'Pirkanmaa');
  assert.equal(regionForPostalCode('90100'), 'Pohjois-Pohjanmaa');
  assert.equal(regionForPostalCode('96100'), 'Lappi');
  assert.equal(regionForPostalCode('1234'), null);
  assert.equal(regionForPostalCode(undefined), null);
});

test('kelvollinen myyntikohde normalisoidaan', () => {
  const { ok, data, errors } = validateListing({ ...validRequest, photoIds: photos(3) });
  assert.ok(ok, JSON.stringify(errors));
  assert.equal(data.region, 'Varsinais-Suomi');
  assert.equal(data.email, 'matti@example.fi');
  assert.equal(data.phone, '0401234567');
  assert.equal(data.area, 54.5);
  assert.equal(data.buildYear, 1985);
  assert.equal(data.priceExpectation, 210000);
  assert.equal(data.maintenanceFee, 240);
  assert.equal(data.plotArea, null, 'kerrostalossa ei tonttia');
  assert.deepEqual(data.features, ['sauna', 'parveke', 'hissi']);
});

test('kuvia vähintään kolme ja enintään viisitoista', () => {
  assert.ok(validateListing({ ...validRequest, photoIds: photos(2) }).errors.photos);
  assert.ok(validateListing({ ...validRequest, photoIds: photos(16) }).errors.photos);
  assert.ok(validateListing({ ...validRequest, photoIds: [...photos(2), 'kuva-0-abcdef'] }).errors.photos, 'kaksoiskappaleet eivät kelpaa');
  assert.ok(validateListing({ ...validRequest, photoIds: photos(15) }).ok);
  assert.ok(validatePhotoIds(photos(1)).ok);
  assert.ok(!validatePhotoIds([]).ok);
  assert.ok(validatePhotoIds([], { min: 0 }).ok);
});

test('puuttuvat ja virheelliset kentät raportoidaan', () => {
  const { ok, errors } = validateListing({
    ...validRequest, photoIds: photos(3), propertyType: 'linna', email: 'x', consent: false, area: '5', buildYear: '', details: 'soita 040 123 4567',
  });
  assert.equal(ok, false);
  assert.deepEqual(Object.keys(errors).sort(), ['area', 'buildYear', 'consent', 'details', 'email', 'propertyType']);
  const house = validateListing({ ...validRequest, photoIds: photos(3), propertyType: 'omakotitalo', ownership: 'kiinteisto_oma', plotArea: '1 200' });
  assert.equal(house.data.plotArea, 1200);
});

test('Y-tunnuksen tarkistusmerkki', () => {
  assert.ok(isValidBusinessId('0112038-9'));
  assert.ok(!isValidBusinessId('0112038-8'));
  assert.ok(!isValidBusinessId('112038-9'));
});

test('välittäjän validointi vaatii alueen, rekisteröinnin ja ehdot', () => {
  const { ok, errors } = validateAgent({
    company: 'Koti LKV Oy', businessId: '0112038-9', contactName: 'Liisa', email: 'l@x.fi', phone: '0401234567', regions: ['Mordor'],
  });
  assert.equal(ok, false);
  assert.deepEqual(Object.keys(errors).sort(), ['licensed', 'password', 'regions', 'termsAccepted']);
});

test('arvion ja palkkiotarjouksen validointi', () => {
  const good = validateBid(bid('200 000', '212 000', { includes: ['photography', 'tuntematon'] }));
  assert.ok(good.ok, JSON.stringify(good.errors));
  assert.equal(good.data.estimateLow, 200000);
  assert.equal(good.data.commissionPercent, 3.5);
  assert.deepEqual(good.data.includes, ['photography']);
  assert.equal(good.data.commissionFixedEur, null);

  const fixed = validateBid(bid(200000, 210000, { commissionType: 'fixed', commissionFixedEur: '4 900', commissionPercent: '99' }));
  assert.ok(fixed.ok, JSON.stringify(fixed.errors));
  assert.equal(fixed.data.commissionPercent, null, 'kiinteässä prosenttia ei tallenneta');

  const bad = validateBid(bid(200000, 190000, { commissionPercent: '15', contractMonths: 24, saleTimeEstimate: '', description: 'soita 040 123 4567' }));
  assert.deepEqual(Object.keys(bad.errors).sort(), ['commissionPercent', 'contractMonths', 'description', 'estimateHigh', 'saleTimeEstimate']);
  assert.ok(validateBid({ commissionType: 'percent', commissionPercent: 3 }, { requireAll: false }).errors.estimateLow, 'pohjassakin luvut tarkistetaan');
});

test('palkkiot ja vertailu samalla viitehinnalla', () => {
  const pct = { commissionType: 'percent', commissionPercent: 3, commissionMinEur: 4000, extraCostsEur: 0 };
  assert.equal(commissionAt(pct, 200000), 6000);
  assert.equal(commissionAt(pct, 100000), 4000, 'vähimmäispalkkio');
  assert.equal(commissionAt({ commissionType: 'fixed', commissionFixedEur: 4900 }, 900000), 4900);
  assert.equal(median([3, 1, 2]), 2);
  assert.equal(median([1, 2, 3, 4]), 3);
  assert.equal(median([]), null);
  const bids = [
    { ...pct, estimateLow: 190000, estimateHigh: 200000 },
    { ...pct, estimateLow: 220000, estimateHigh: 240000, extraCostsEur: 500 },
  ];
  assert.equal(referencePrice(bids), 213000); // (195 000 + 230 000) / 2 = 212 500
  const f = offerFigures(bids[1], 213000);
  assert.deepEqual(f, { estimateMid: 230000, commissionAtOwn: 6900, netAtOwn: 230000 - 7400, costAtReference: 6390 + 500, netAtReference: 213000 - 6890 });
});

test('yhteystietojen tunnistus', () => {
  assert.ok(containsContactInfo('soita 040 123 4567'));
  assert.ok(containsContactInfo('www.yritys.fi'));
  assert.ok(!containsContactInfo('Vertailukaupat 3 800–4 100 €/m², kaksio 54 m²'));
});

test('YTJ-vastauksen tulkinta ja etäisyys', () => {
  const r = parseYtjCompany(YTJ_SAMPLE);
  assert.deepEqual([r.found, r.active, r.prepaymentRegister, r.vatRegister, r.name], [true, true, true, true, 'Esimerkki Kodit Oy LKV']);
  assert.equal(r.address, 'Kauppiaskatu 3 20100 TURKU');
  assert.equal(parseYtjCompany({ companies: [] }).found, false);
  const geo = createGeo(COORDS);
  assert.ok(geo.within('24100', '20100', 60));
  assert.ok(!geo.within('24100', '20100', 30));
  assert.ok(!createGeo(null).within('24100', '20100', 500));
});

test('kilpailun säännöt', () => {
  const t = Date.parse('2026-10-14T12:00:00Z');
  const req = { id: 'r', status: 'avoin', deadline: '2026-10-21T12:00:00Z', region: 'Pirkanmaa', name: 'M', email: 'm@x', phone: '1', streetAddress: 'Katu 1', consumerToken: 'abc' };
  assert.ok(isOpen(req, t));
  assert.ok(!isOpen(req, Date.parse('2026-10-21T12:00:00Z')));
  assert.ok(!isOpen({ ...req, status: 'valittu' }, t));

  const agent = { status: 'hyvaksytty', regions: ['Pirkanmaa'] };
  assert.ok(canSee(agent, req));
  assert.ok(!canSee({ ...agent, status: 'odottaa_hyvaksyntaa' }, req));
  assert.ok(!canSee({ ...agent, regions: ['Lappi'] }, req));

  const view = requestForAgent(req);
  for (const k of ['streetAddress', 'name', 'email', 'phone', 'consumerToken']) assert.equal(view[k], undefined, k);

  const after = Date.parse('2026-10-22T00:00:00Z');
  assert.equal(bidOutcome(req, { id: 'b1' }, t), 'avoin');
  assert.equal(bidOutcome(req, { id: 'b1' }, after), 'odottaa');
  assert.equal(bidOutcome({ ...req, status: 'valittu', acceptedBidIds: ['b1'] }, { id: 'b1' }, after), 'valittu');
  assert.equal(bidOutcome({ ...req, status: 'valittu', acceptedBidIds: ['b1'] }, { id: 'b2' }, after), 'ei_valittu');
  assert.equal(bidOutcome({ ...req, status: 'valittu', acceptedBidIds: ['b1', 'b2'] }, { id: 'b2' }, after), 'valittu');
  assert.equal(bidOutcome({ ...req, status: 'valmis', acceptedBidIds: ['b1', 'b2'] }, { id: 'b3' }, after), 'ei_valittu');
});
