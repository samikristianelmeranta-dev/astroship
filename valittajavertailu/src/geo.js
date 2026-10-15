// Etäisyys postinumeroalueiden keskipisteiden välillä. Koordinaatit tulevat
// Tilastokeskuksen Paavo-aineistosta (scripts/fetch-postal-coordinates.mjs).

export function distanceKm([lat1, lon1], [lat2, lon2]) {
  const rad = Math.PI / 180;
  const a =
    Math.sin(((lat2 - lat1) * rad) / 2) ** 2 +
    Math.cos(lat1 * rad) * Math.cos(lat2 * rad) * Math.sin(((lon2 - lon1) * rad) / 2) ** 2;
  return 6371 * 2 * Math.asin(Math.sqrt(a));
}

// coords: { "20100": [lat, lon], ... } tai null, jos aineistoa ei ole ladattu.
export function createGeo(coords) {
  const lookup = (postalCode) => coords?.[postalCode] ?? null;
  return {
    available: Boolean(coords && Object.keys(coords).length),
    lookup,
    within(fromPostal, toPostal, radiusKm) {
      const a = lookup(fromPostal);
      const b = lookup(toPostal);
      return Boolean(a && b && radiusKm > 0 && distanceKm(a, b) <= radiusKm);
    },
  };
}
