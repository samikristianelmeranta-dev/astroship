import {
  REGIONS,
  PROPERTY_TYPES,
  OWNERSHIP,
  ROOMS,
  CONDITIONS,
  FEATURES,
  TIMELINES,
  DEADLINE_OPTIONS,
  MIN_PHOTOS,
  MAX_PHOTOS,
  BID_INCLUDES,
  regionForPostalCode,
} from './catalog.js';

const EMAIL_RE = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
// Suomalainen numero muodossa 040 123 4567 tai +358 40 123 4567.
const PHONE_RE = /^(\+358|0)\d{5,12}$/;

function str(value, max = 200) {
  return typeof value === 'string' ? value.trim().slice(0, max) : '';
}

const bool = (v) => v === true || v === 'on' || v === 'true';
const list = (v, max = 40) => [...new Set((Array.isArray(v) ? v : v ? [v] : []).map((x) => str(x, max)))];

// Tyhjä → null, muuten luku. Hyväksyy pilkun desimaalierottimena.
function num(value) {
  if (value === '' || value == null) return null;
  const n = Number(String(value).replace(/\s/g, '').replace(',', '.'));
  return Number.isFinite(n) ? n : NaN;
}

const inRange = (n, lo, hi) => n === null || (Number.isFinite(n) && n >= lo && n <= hi);

// Estää yhteystietojen jakamisen kysymyksissä ja vastauksissa, jotta kauppa
// ei siirry palvelun ohi ja kuluttajan yksityisyys säilyy.
export function containsContactInfo(text) {
  return (
    /[^\s@]+@[^\s@]+\.[a-z]{2,}/i.test(text) ||
    /https?:\/\/|www\./i.test(text) ||
    /(\+358|\b0)[\d\s-]{6,}\d/.test(text) ||
    /\d[\d\s-]{8,}\d/.test(text)
  );
}

export function normalizePhone(value) {
  return str(value, 40).replace(/[\s\-()]/g, '');
}

function check(errors, condition, field, message) {
  if (!condition) errors[field] = message;
}

const PHOTO_ID_RE = /^[\w-]{8,40}$/;

// Kuluttajan myyntikohde: asunnon perustiedot, osoite, kuvat ja yhteystiedot.
export function validateListing(input = {}) {
  const errors = {};
  const postalCode = str(input.postalCode, 5);
  const region = str(input.region, 40) || regionForPostalCode(postalCode);
  const propertyType = str(input.propertyType, 30);
  const isHouse = ['omakotitalo', 'erillistalo', 'paritalo', 'vapaa_ajan_asunto'].includes(propertyType);

  const data = {
    propertyType,
    ownership: str(input.ownership, 30),
    area: num(input.area),
    rooms: str(String(input.rooms ?? ''), 2),
    roomLayout: str(input.roomLayout, 60),
    buildYear: input.buildYear === '' || input.buildYear == null ? null : Number.parseInt(input.buildYear, 10),
    floor: str(input.floor, 20),
    condition: str(input.condition, 20) || 'ei_tiedossa',
    features: list(input.features, 20).filter((f) => f in FEATURES),
    plotArea: isHouse ? num(input.plotArea) : null,
    maintenanceFee: num(input.maintenanceFee),
    priceExpectation: num(input.priceExpectation),
    photoIds: list(input.photoIds),
    timeline: str(input.timeline, 20),
    deadlineDays: Number.parseInt(input.deadlineDays, 10),
    details: str(input.details, 2000),
    name: str(input.name, 100),
    email: str(input.email, 200).toLowerCase(),
    phone: normalizePhone(input.phone),
    streetAddress: str(input.streetAddress, 120),
    postalCode,
    city: str(input.city, 100),
    region,
    consent: bool(input.consent),
  };

  check(errors, data.propertyType in PROPERTY_TYPES, 'propertyType', 'Valitse asunnon tyyppi.');
  check(errors, data.ownership in OWNERSHIP, 'ownership', 'Valitse omistusmuoto.');
  check(errors, Number.isFinite(data.area) && data.area >= 10 && data.area <= 2000, 'area', 'Anna asuinpinta-ala (10–2000 m²).');
  check(errors, data.rooms in ROOMS, 'rooms', 'Valitse huoneiden määrä.');
  const year = new Date().getFullYear();
  check(
    errors,
    Number.isInteger(data.buildYear) && data.buildYear >= 1700 && data.buildYear <= year + 2,
    'buildYear',
    `Anna rakennusvuosi väliltä 1700–${year + 2}.`,
  );
  check(errors, data.condition in CONDITIONS, 'condition', 'Valitse kunto.');
  check(errors, inRange(data.plotArea, 1, 1_000_000), 'plotArea', 'Anna tontin pinta-ala neliömetreinä tai jätä tyhjäksi.');
  check(errors, inRange(data.maintenanceFee, 0, 5000), 'maintenanceFee', 'Anna hoitovastike euroina kuukaudessa tai jätä tyhjäksi.');
  check(errors, inRange(data.priceExpectation, 1000, 20_000_000), 'priceExpectation', 'Anna hintatoive euroina tai jätä tyhjäksi.');
  check(
    errors,
    data.photoIds.length >= MIN_PHOTOS && data.photoIds.length <= MAX_PHOTOS && data.photoIds.every((id) => PHOTO_ID_RE.test(id)),
    'photos',
    `Lisää vähintään ${MIN_PHOTOS} ja enintään ${MAX_PHOTOS} kuvaa.`,
  );
  check(errors, data.streetAddress.length >= 3, 'streetAddress', 'Anna asunnon katuosoite.');
  check(errors, data.timeline in TIMELINES, 'timeline', 'Valitse myynnin aikataulu.');
  check(errors, data.deadlineDays in DEADLINE_OPTIONS, 'deadlineDays', 'Valitse arvioiden määräaika.');
  check(errors, !containsContactInfo(data.details), 'details', 'Älä lisää yhteystietoja lisätietoihin. Ne välitetään valitsemillesi välittäjille.');
  check(errors, data.name.length >= 2, 'name', 'Anna nimesi.');
  check(errors, EMAIL_RE.test(data.email), 'email', 'Anna toimiva sähköpostiosoite.');
  check(errors, PHONE_RE.test(data.phone), 'phone', 'Anna puhelinnumero muodossa 040 123 4567.');
  check(errors, /^\d{5}$/.test(data.postalCode), 'postalCode', 'Postinumerossa on 5 numeroa.');
  check(errors, REGIONS.includes(data.region), 'region', 'Valitse maakunta.');
  check(errors, data.consent, 'consent', 'Hyväksy käyttöehdot.');
  if (Number.isFinite(data.area)) data.area = Math.round(data.area * 10) / 10;
  for (const k of ['plotArea', 'maintenanceFee', 'priceExpectation']) if (Number.isFinite(data[k])) data[k] = Math.round(data[k]);

  return { ok: Object.keys(errors).length === 0, errors, data };
}

// Kuvien tunnisteet, kun kuluttaja lisää kuvia julkaistuun kohteeseen.
export function validatePhotoIds(input, { min = 1, max = MAX_PHOTOS } = {}) {
  const photoIds = list(input);
  const ok = photoIds.length >= min && photoIds.length <= max && photoIds.every((id) => PHOTO_ID_RE.test(id));
  return { ok, photoIds, errors: ok ? {} : { photos: `Lisää ${min}–${max} kuvaa.` } };
}

// Toiminta-alue: maakunnat ja/tai kotipostinumero + säde.
function checkArea(errors, data) {
  check(errors, data.regions.every((r) => REGIONS.includes(r)), 'regions', 'Valitse maakunnat listasta.');
  check(errors, data.homePostalCode === '' || /^\d{5}$/.test(data.homePostalCode), 'homePostalCode', 'Postinumerossa on 5 numeroa.');
  check(errors, inRange(data.radiusKm, 0, 300), 'radiusKm', 'Säde on 0–300 km.');
  const hasRadius = /^\d{5}$/.test(data.homePostalCode) && data.radiusKm > 0;
  check(errors, data.regions.length > 0 || hasRadius, 'regions', 'Valitse vähintään yksi maakunta tai anna säde kotipostinumerosta.');
}

export function validateProfile(input = {}) {
  const errors = {};
  const data = {
    description: str(input.description, 1000),
    chain: str(input.chain, 100),
    website: str(input.website, 200),
    regions: list(input.regions),
    homePostalCode: str(input.homePostalCode, 5),
    radiusKm: num(input.radiusKm),
    notifyEmail: bool(input.notifyEmail),
    notifySms: bool(input.notifySms),
  };
  check(errors, data.website === '' || /^https?:\/\/[^\s]+\.[^\s]+$/.test(data.website), 'website', 'Anna osoite muodossa https://…');
  checkArea(errors, data);
  return { ok: Object.keys(errors).length === 0, errors, data };
}

export function validateAgent(input = {}) {
  const errors = {};
  const data = {
    company: str(input.company, 150),
    businessId: str(input.businessId, 9),
    contactName: str(input.contactName, 100),
    email: str(input.email, 200).toLowerCase(),
    phone: normalizePhone(input.phone),
    chain: str(input.chain, 100),
    regions: list(input.regions),
    licensed: bool(input.licensed),
    description: str(input.description, 1000),
    homePostalCode: str(input.homePostalCode, 5),
    radiusKm: num(input.radiusKm),
    notifyEmail: input.notifyEmail === undefined ? true : bool(input.notifyEmail),
    notifySms: bool(input.notifySms),
    termsAccepted: bool(input.termsAccepted),
  };
  // Salasanaa ei trimmata eikä palauteta datan mukana tallennettavaksi.
  const password = typeof input.password === 'string' ? input.password : '';

  check(errors, data.company.length >= 2, 'company', 'Anna välitysliikkeen nimi.');
  check(errors, isValidBusinessId(data.businessId), 'businessId', 'Anna kelvollinen Y-tunnus (1234567-8).');
  check(errors, data.contactName.length >= 2, 'contactName', 'Anna yhteyshenkilön nimi.');
  check(errors, EMAIL_RE.test(data.email), 'email', 'Anna toimiva sähköpostiosoite.');
  check(errors, PHONE_RE.test(data.phone), 'phone', 'Anna puhelinnumero muodossa 040 123 4567.');
  checkArea(errors, data);
  check(errors, data.licensed, 'licensed', 'Palvelu on vain rekisteröidyille välitysliikkeille, joissa työskentelee LKV.');
  check(errors, data.termsAccepted, 'termsAccepted', 'Hyväksy välittäjien käyttöehdot.');
  check(errors, password.length >= 10 && password.length <= 200, 'password', 'Salasanassa on oltava vähintään 10 merkkiä.');

  return { ok: Object.keys(errors).length === 0, errors, data, password };
}

// Välittäjän vakiomuotoinen tarjous: hinta-arvio, välityspalkkio ja mitä siihen sisältyy.
// Kaikki eurot sisältävät arvonlisäveron.
export function validateBid(input = {}, { requireAll = true } = {}) {
  const errors = {};
  const includes = list(input.includes, 30).filter((x) => x in BID_INCLUDES);
  const commissionType = input.commissionType === 'fixed' ? 'fixed' : 'percent';
  const data = {
    estimateLow: num(input.estimateLow),
    estimateHigh: num(input.estimateHigh),
    askingPrice: num(input.askingPrice),
    commissionType,
    commissionPercent: commissionType === 'percent' ? num(input.commissionPercent) : null,
    commissionMinEur: commissionType === 'percent' ? num(input.commissionMinEur) : null,
    commissionFixedEur: commissionType === 'fixed' ? num(input.commissionFixedEur) : null,
    extraCostsEur: num(input.extraCostsEur) ?? 0,
    includes,
    contractMonths: num(input.contractMonths) ?? 4,
    noSaleNoFee: bool(input.noSaleNoFee),
    saleTimeEstimate: str(input.saleTimeEstimate, 60),
    agentName: str(input.agentName, 100),
    validDays: num(input.validDays) ?? 30,
    description: str(input.description, 3000),
  };
  const lo = data.estimateLow;
  const hi = data.estimateHigh;
  check(errors, Number.isFinite(lo) && lo >= 1000 && lo <= 20_000_000, 'estimateLow', 'Anna arvion alaraja euroina.');
  check(
    errors,
    Number.isFinite(hi) && hi <= 20_000_000 && (!Number.isFinite(lo) || (hi >= lo && hi <= lo * 1.5)),
    'estimateHigh',
    'Yläraja on vähintään alaraja ja enintään 50 % sitä suurempi.',
  );
  check(errors, inRange(data.askingPrice, 1000, 20_000_000), 'askingPrice', 'Anna pyyntöhinta euroina tai jätä tyhjäksi.');
  if (commissionType === 'percent') {
    check(errors, Number.isFinite(data.commissionPercent) && data.commissionPercent >= 0.5 && data.commissionPercent <= 10, 'commissionPercent', 'Palkkio on 0,5–10 % (sis. alv).');
    check(errors, inRange(data.commissionMinEur, 0, 100_000), 'commissionMinEur', 'Vähimmäispalkkio 0–100 000 € tai tyhjä.');
  } else {
    check(errors, Number.isFinite(data.commissionFixedEur) && data.commissionFixedEur >= 300 && data.commissionFixedEur <= 200_000, 'commissionFixedEur', 'Kiinteä palkkio on 300–200 000 € (sis. alv).');
  }
  check(errors, Number.isFinite(data.extraCostsEur) && data.extraCostsEur >= 0 && data.extraCostsEur <= 50_000, 'extraCostsEur', 'Lisäkulut 0–50 000 €.');
  check(errors, Number.isInteger(data.contractMonths) && data.contractMonths >= 1 && data.contractMonths <= 12, 'contractMonths', 'Toimeksiannon kesto 1–12 kuukautta.');
  check(errors, Number.isInteger(data.validDays) && data.validDays >= 7 && data.validDays <= 180, 'validDays', 'Voimassaolo 7–180 päivää.');
  check(errors, !containsContactInfo(data.description), 'description', 'Älä lisää yhteystietoja tai linkkejä. Ne välitetään, kun myyjä valitsee sinut.');
  if (requireAll) {
    check(errors, data.saleTimeEstimate.length >= 2, 'saleTimeEstimate', 'Arvioi myyntiaika, esim. 6–10 viikkoa.');
    check(errors, data.description.length >= 30, 'description', 'Perustele arvio lyhyesti (vähintään 30 merkkiä), esim. alueen vertailukaupat.');
  }
  for (const k of ['estimateLow', 'estimateHigh', 'askingPrice', 'commissionMinEur', 'commissionFixedEur', 'extraCostsEur']) {
    if (Number.isFinite(data[k])) data[k] = Math.round(data[k]);
  }
  if (Number.isFinite(data.commissionPercent)) data.commissionPercent = Math.round(data.commissionPercent * 100) / 100;
  return { ok: Object.keys(errors).length === 0, errors, data };
}

// Kysymys tai vastaus tarjouspyyntöön.
export function validateMessageText(input, { min = 5, max = 1000, field = 'text' } = {}) {
  const text = str(input, max);
  const errors = {};
  check(errors, text.length >= min, field, `Kirjoita vähintään ${min} merkkiä.`);
  check(errors, !containsContactInfo(text), field, 'Älä lisää puhelinnumeroita, sähköposteja tai linkkejä.');
  return { ok: Object.keys(errors).length === 0, errors, text };
}

export function validateReview(input = {}) {
  const errors = {};
  const data = { rating: num(input.rating), text: str(input.text, 1000) };
  check(errors, Number.isInteger(data.rating) && data.rating >= 1 && data.rating <= 5, 'rating', 'Valitse 1–5 tähteä.');
  return { ok: Object.keys(errors).length === 0, errors, data };
}

export function validateWaitlist(input = {}) {
  const errors = {};
  const data = {
    email: str(input.email, 200).toLowerCase(),
    postalCode: str(input.postalCode, 5),
  };
  data.region = regionForPostalCode(data.postalCode);
  check(errors, EMAIL_RE.test(data.email), 'email', 'Anna toimiva sähköpostiosoite.');
  check(errors, /^\d{5}$/.test(data.postalCode), 'postalCode', 'Postinumerossa on 5 numeroa.');
  return { ok: Object.keys(errors).length === 0, errors, data };
}

// Y-tunnuksen tarkistusmerkki: painot 7,9,10,5,8,4,2; jakojäännös 11.
export function isValidBusinessId(value) {
  const m = /^(\d{7})-(\d)$/.exec(String(value ?? ''));
  if (!m) return false;
  const weights = [7, 9, 10, 5, 8, 4, 2];
  const sum = [...m[1]].reduce((acc, d, i) => acc + Number(d) * weights[i], 0);
  const rem = sum % 11;
  if (rem === 1) return false;
  const expected = rem === 0 ? 0 : 11 - rem;
  return expected === Number(m[2]);
}
