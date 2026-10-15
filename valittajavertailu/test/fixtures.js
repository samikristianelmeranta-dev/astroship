export const validRequest = {
  propertyType: 'kerrostalo',
  ownership: 'osake',
  area: '54,5',
  rooms: '2',
  roomLayout: '2h+k+s',
  buildYear: '1985',
  floor: '3/5',
  condition: 'hyva',
  features: ['sauna', 'parveke', 'hissi'],
  maintenanceFee: '240',
  priceExpectation: '210 000',
  timeline: 'kk1_3',
  deadlineDays: '7',
  details: 'Kylpyhuone remontoitu 2019.',
  name: 'Matti Meikäläinen',
  email: 'Matti@Example.fi',
  phone: '040 123 4567',
  streetAddress: 'Esimerkkikatu 5 B 12',
  postalCode: '20100',
  city: 'Turku',
  consent: true,
};

// Y-tunnukset, joiden tarkistusmerkki on oikein.
export const BUSINESS_IDS = ['0112038-9', '1572860-0', '0109862-8'];

export function agent(n, extra = {}) {
  return {
    company: `Välittäjä ${n} Oy LKV`,
    businessId: BUSINESS_IDS[n],
    contactName: `Välittäjä ${n}`,
    email: `valittaja${n}@example.fi`,
    phone: '040 765 432' + n,
    regions: ['Varsinais-Suomi'],
    licensed: true,
    termsAccepted: true,
    password: 'salasana-123',
    ...extra,
  };
}

// Prosenttipalkkio oletuksena; extra voi vaihtaa kiinteään.
export function bid(low, high, extra = {}) {
  return {
    estimateLow: low,
    estimateHigh: high,
    askingPrice: high,
    commissionType: 'percent',
    commissionPercent: '3,5',
    commissionMinEur: 3000,
    extraCostsEur: 0,
    includes: ['photography', 'floorplan', 'privateShowings'],
    contractMonths: 4,
    noSaleNoFee: true,
    saleTimeEstimate: '6–10 viikkoa',
    agentName: 'Liisa Laine, LKV',
    validDays: 30,
    description: 'Alueella on myyty kolme vastaavaa kaksiota viimeisen puolen vuoden aikana 3 800–4 100 €/m².',
    ...extra,
  };
}

// YTJ v3 -vastauksen muotoinen esimerkki (vain tulkinnan kannalta olennaiset kentät).
export const YTJ_SAMPLE = {
  totalResults: 1,
  companies: [
    {
      businessId: { value: '0112038-9' },
      names: [{ name: 'Esimerkki Kodit Oy LKV', type: '1', registrationDate: '2001-01-01' }],
      registeredEntries: [
        { type: '1', descriptions: [{ languageCode: '1', description: 'Rekisterissä' }], register: '1' },
        { type: '1', descriptions: [{ languageCode: '1', description: 'Ennakkoperintärekisterissä' }], register: '4' },
        { type: '1', descriptions: [{ languageCode: '1', description: 'Arvonlisäverorekisterissä' }], register: '5' },
      ],
      addresses: [{ type: 1, street: 'Kauppiaskatu', buildingNumber: '3', postCode: '20100', postOffices: [{ city: 'TURKU', languageCode: '1' }] }],
      companySituations: [],
      endDate: null,
    },
  ],
};

// Pienin kelvollinen JPEG-alku: palvelin tarkistaa muodon tunnisteesta.
export const TINY_JPEG = 'data:image/jpeg;base64,' + Buffer.from([0xff, 0xd8, 0xff, 0xe0, 1, 2, 3]).toString('base64');

// Muutama postinumeroalueen keskipiste sädetestiin.
export const COORDS = { 20100: [60.452, 22.268], 24100: [60.386, 23.125], 96100: [66.5, 25.72] };
