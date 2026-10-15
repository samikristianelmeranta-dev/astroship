// Kohdetyypit, maakunnat, tarjouksen sisältö ja postinumeroiden karkea maakuntajako.

export const REGIONS = [
  'Uusimaa',
  'Varsinais-Suomi',
  'Satakunta',
  'Kanta-Häme',
  'Pirkanmaa',
  'Päijät-Häme',
  'Kymenlaakso',
  'Etelä-Karjala',
  'Etelä-Savo',
  'Pohjois-Savo',
  'Pohjois-Karjala',
  'Keski-Suomi',
  'Etelä-Pohjanmaa',
  'Pohjanmaa',
  'Keski-Pohjanmaa',
  'Pohjois-Pohjanmaa',
  'Kainuu',
  'Lappi',
  'Ahvenanmaa',
];

export const PROPERTY_TYPES = {
  kerrostalo: 'Kerrostalo',
  rivitalo: 'Rivitalo',
  paritalo: 'Paritalo',
  omakotitalo: 'Omakotitalo',
  erillistalo: 'Erillistalo',
  vapaa_ajan_asunto: 'Vapaa-ajan asunto',
};

// Asunto-osake (taloyhtiö) vai kiinteistö (oma tai vuokratontti). Vaikuttaa
// varainsiirtoveroon, tarvittaviin asiakirjoihin ja välittäjän työhön.
export const OWNERSHIP = {
  osake: 'Asunto-osake (taloyhtiö)',
  kiinteisto_oma: 'Kiinteistö, oma tontti',
  kiinteisto_vuokra: 'Kiinteistö, vuokratontti',
};

export const ROOMS = {
  1: 'Yksiö',
  2: 'Kaksio',
  3: '3 huonetta',
  4: '4 huonetta',
  5: '5 huonetta tai enemmän',
};

export const CONDITIONS = {
  uusi: 'Uusi tai uudenveroinen',
  hyva: 'Hyvä',
  tyydyttava: 'Tyydyttävä',
  valttava: 'Välttävä, remontoitava',
  ei_tiedossa: 'En osaa sanoa',
};

// Asunnon varustelu: rastit lomakkeella.
export const FEATURES = {
  sauna: 'Sauna',
  parveke: 'Parveke tai terassi',
  hissi: 'Hissi',
  piha: 'Oma piha',
  autopaikka: 'Autopaikka tai autotalli',
  remontoitu: 'Remontoitu viime vuosina',
};

export const TIMELINES = {
  heti: 'Mahdollisimman pian',
  kk1_3: '1–3 kuukauden sisällä',
  kk3_6: '3–6 kuukauden sisällä',
  selvitys: 'Selvittelen vielä',
};

export const MIN_PHOTOS = 3;
export const MAX_PHOTOS = 15;
// Base64-merkkijonon enimmäispituus (~2,5 Mt kuvaa). Selain pienentää kuvat ennen lähetystä.
export const MAX_PHOTO_BASE64 = 3_400_000;

// Kuinka monta päivää välittäjillä on aikaa jättää arvio.
export const DEADLINE_OPTIONS = {
  3: '3 päivää',
  5: '5 päivää',
  7: '1 viikko',
  14: '2 viikkoa',
};

// Postinumeron kahden ensimmäisen numeron perusteella arvioitu maakunta.
// Jako on likimääräinen: alueiden rajoilla käyttäjä voi korjata maakunnan itse.
const PREFIX_RANGES = [
  [0, 10, 'Uusimaa'],
  [11, 14, 'Kanta-Häme'],
  [15, 19, 'Päijät-Häme'],
  [20, 21, 'Varsinais-Suomi'],
  [22, 22, 'Ahvenanmaa'],
  [23, 25, 'Varsinais-Suomi'],
  [26, 29, 'Satakunta'],
  [30, 31, 'Kanta-Häme'],
  [32, 32, 'Varsinais-Suomi'],
  [33, 39, 'Pirkanmaa'],
  [40, 44, 'Keski-Suomi'],
  [45, 49, 'Kymenlaakso'],
  [50, 52, 'Etelä-Savo'],
  [53, 56, 'Etelä-Karjala'],
  [57, 58, 'Etelä-Savo'],
  [59, 59, 'Etelä-Karjala'],
  [60, 64, 'Etelä-Pohjanmaa'],
  [65, 66, 'Pohjanmaa'],
  [67, 67, 'Keski-Pohjanmaa'],
  [68, 68, 'Pohjanmaa'],
  [69, 69, 'Keski-Pohjanmaa'],
  [70, 74, 'Pohjois-Savo'],
  [75, 75, 'Pohjois-Karjala'],
  [76, 76, 'Etelä-Savo'],
  [77, 79, 'Pohjois-Savo'],
  [80, 83, 'Pohjois-Karjala'],
  [84, 86, 'Pohjois-Pohjanmaa'],
  [87, 89, 'Kainuu'],
  [90, 93, 'Pohjois-Pohjanmaa'],
  [94, 99, 'Lappi'],
];

// Kolminumeroiset poikkeukset kaksinumeroiseen jakoon.
const PREFIX3_OVERRIDES = {
  314: 'Varsinais-Suomi', // Somero
  315: 'Varsinais-Suomi', // Koski Tl
};

export function regionForPostalCode(postalCode) {
  if (!/^\d{5}$/.test(String(postalCode ?? ''))) return null;
  const override = PREFIX3_OVERRIDES[String(postalCode).slice(0, 3)];
  if (override) return override;
  const prefix = Number(String(postalCode).slice(0, 2));
  const hit = PREFIX_RANGES.find(([lo, hi]) => prefix >= lo && prefix <= hi);
  return hit ? hit[2] : null;
}

// Pilottialueet: palvelu toimii näissä, muualla kuluttaja voi liittyä jonoon.
// null = koko Suomi. Palvelin voi korvata tämän PILOT_REGIONS-ympäristömuuttujalla.
export const DEFAULT_PILOT_REGIONS = null;

// Mitä välityspalkkioon sisältyy (vakiomuotoinen tarjous).
export const BID_INCLUDES = {
  photography: 'Ammattivalokuvaus',
  floorplan: 'Pohjapiirros',
  virtualTour: '3D- tai videoesittely',
  styling: 'Stailaus',
  socialAds: 'Somemainonta',
  privateShowings: 'Yksityisesittelyt',
  conditionCheck: 'Kuntotarkastus',
  documents: 'Asiakirjojen hankinta',
};

// Arvonlisäveron yleinen verokanta, jolla prosenttipalkkiot muutetaan sisältämään alv.
export const VAT_RATE = 0.255;

// Palvelumaksu välittäjälle jokaisesta valinnasta (oletus, korvattavissa).
export const DEFAULT_SUCCESS_FEE_EUR = 149;

// Kuinka monen päivän päästä valinnasta kuluttajalta kysytään, miten myynti eteni.
export const REVIEW_DELAY_DAYS = 60;

// Seurattavat tapahtumat (vain päiväkohtaiset määrät, ei henkilötietoja).
export const FUNNEL_EVENTS = {
  etusivu: 'Etusivun katselu',
  lomake_1: 'Lomake: asunto',
  lomake_2: 'Lomake: osoite ja kuvat',
  lomake_3: 'Lomake: yhteystiedot',
  lahetetty: 'Pyyntö lähetetty',
  vahvistettu: 'Sähköposti vahvistettu',
  tarjous: 'Arvio jätetty',
  valittu: 'Välittäjä valittu',
};
