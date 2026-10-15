// Hakee postinumeroalueiden keskipisteet Tilastokeskuksen Paavo-aineistosta
// (avoin data, CC BY 4.0, lähde: Tilastokeskus) ja tallentaa ne tiedostoon
// data/postal-coordinates.json muodossa { "20100": [lat, lon], ... }.
// Palvelin lukee tiedoston käynnistyessään; sen jälkeen välittäjien säde toimii.
//
// Käyttö: node scripts/fetch-postal-coordinates.mjs [vuosi]
import { writeFile, mkdir } from 'node:fs/promises';

const years = process.argv[2] ? [process.argv[2]] : [2025, 2024, 2023];
const url = (year) =>
  'https://geo.stat.fi/geoserver/postialue/wfs?service=WFS&version=2.0.0&request=GetFeature' +
  `&typeName=postialue:pno_${year}&outputFormat=application/json&srsName=EPSG:4326&propertyName=posti_alue,geom`;

// Monikulmion karkea keskipiste (pisteiden keskiarvo) riittää etäisyyksiin.
function centroid(geometry) {
  const points = [];
  const walk = (c) => (typeof c[0] === 'number' ? points.push(c) : c.forEach(walk));
  walk(geometry.coordinates);
  const [lon, lat] = points.reduce(([a, b], [x, y]) => [a + x, b + y], [0, 0]).map((v) => v / points.length);
  return [Math.round(lat * 1e4) / 1e4, Math.round(lon * 1e4) / 1e4];
}

for (const year of years) {
  const res = await fetch(url(year));
  if (!res.ok) {
    console.log(`Vuoden ${year} aineistoa ei saatu (${res.status}), kokeillaan seuraavaa.`);
    continue;
  }
  const json = await res.json();
  const out = {};
  for (const f of json.features ?? []) if (f.geometry) out[f.properties.posti_alue] = centroid(f.geometry);
  await mkdir('data', { recursive: true });
  await writeFile('data/postal-coordinates.json', JSON.stringify(out));
  console.log(`Tallennettu ${Object.keys(out).length} postinumeroaluetta (Paavo ${year}).`);
  process.exit(0);
}
console.error('Aineiston haku epäonnistui.');
process.exit(1);
