// Tarjouskilpailun säännöt. Puhtaita funktioita, joita käytetään sekä
// palvelimella että testeissä.

const DAY_MS = 24 * 60 * 60 * 1000;

export function deadlineFrom(now, days) {
  return new Date(now + days * DAY_MS).toISOString();
}

// Kilpailu on auki, kunnes määräaika umpeutuu tai kuluttaja on jo valinnut.
export function isOpen(request, now) {
  return request.status === 'avoin' && now < Date.parse(request.deadline);
}

// Välittäjä näkee kohteen vain, jos ylläpito on hyväksynyt hänet ja kohde on
// hänen maakunnissaan tai säteen sisällä kotipostinumerosta (jos postinumeroiden
// koordinaatit ovat käytössä).
export function canSee(agent, request, geo) {
  if (agent.status !== 'hyvaksytty') return false;
  if (agent.regions?.includes(request.region)) return true;
  return Boolean(geo?.within(agent.homePostalCode, request.postalCode, agent.radiusKm));
}

// Pyyntö on julkaistu, kun kuluttaja on vahvistanut sähköpostinsa.
export const isPublished = (request) => !['vahvistamaton', 'peruttu'].includes(request.status);
export const isDecided = (request) => request.status === 'valittu' || request.status === 'valmis';

// Myyjä voi valita yhden tai kaksi välittäjää.
export const MAX_CHOSEN = 2;

export const acceptedIds = (request) => request.acceptedBidIds ?? [];

// Mitä välittäjä näkee kohteesta: ei nimeä, katuosoitetta, sähköpostia eikä puhelinta.
// Myyjän hintatoive näkyy, jotta arviot voi suhteuttaa siihen.
const AGENT_FIELDS = [
  'id', 'propertyType', 'ownership', 'area', 'rooms', 'roomLayout', 'buildYear', 'floor', 'condition',
  'features', 'plotArea', 'maintenanceFee', 'priceExpectation', 'timeline', 'details', 'photoIds',
  'postalCode', 'city', 'region', 'deadline', 'createdAt', 'status',
];

export function requestForAgent(request) {
  return Object.fromEntries(AGENT_FIELDS.filter((k) => k in request).map((k) => [k, request[k]]));
}

export function contactOf(request) {
  return {
    name: request.name,
    email: request.email,
    phone: request.phone,
    address: `${request.streetAddress ?? ''}, ${request.postalCode} ${request.city ?? ''}`.trim(),
  };
}

export function publicAgent(agent) {
  const { passwordHash, ...rest } = agent;
  return rest;
}

// Välittäjän oman tarjouksen tila. Ei koskaan kerro muiden tarjouksista.
export function bidOutcome(request, bid, now) {
  if (isOpen(request, now)) return 'avoin';
  if (isDecided(request)) return acceptedIds(request).includes(bid.id) ? 'valittu' : 'ei_valittu';
  if (request.status === 'peruttu') return 'peruttu';
  return 'odottaa';
}
