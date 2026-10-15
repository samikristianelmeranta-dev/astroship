// Arvioiden vertailuluvut myyjälle. Välittäjien hinta-arviot vaihtelevat, ja
// korkein arvio ei tarkoita korkeinta kauppahintaa. Siksi palkkiot lasketaan
// myös samalla viitehinnalla (kaikkien arvioiden mediaani), jotta palkkioita
// voi verrata toisiinsa riippumatta siitä, kuka arvioi kohteen korkeimmalle.

export const midpoint = (bid) => Math.round((bid.estimateLow + bid.estimateHigh) / 2);

// Välityspalkkio euroina (sis. alv) annetulla kauppahinnalla.
export function commissionAt(bid, price) {
  if (bid.commissionType === 'fixed') return bid.commissionFixedEur;
  const byPercent = (price * bid.commissionPercent) / 100;
  return Math.round(Math.max(byPercent, bid.commissionMinEur ?? 0));
}

export function median(values) {
  const sorted = values.filter(Number.isFinite).sort((a, b) => a - b);
  if (!sorted.length) return null;
  const mid = Math.floor(sorted.length / 2);
  return sorted.length % 2 ? sorted[mid] : Math.round((sorted[mid - 1] + sorted[mid]) / 2);
}

// Viitehinta pyöristetään tuhansiin, jotta luku on helppo ymmärtää.
export function referencePrice(bids) {
  const m = median(bids.map(midpoint));
  return m == null ? null : Math.round(m / 1000) * 1000;
}

export function offerFigures(bid, reference) {
  const own = midpoint(bid);
  const ownCost = commissionAt(bid, own) + (bid.extraCostsEur ?? 0);
  const refCost = reference == null ? null : commissionAt(bid, reference) + (bid.extraCostsEur ?? 0);
  return {
    estimateMid: own,
    commissionAtOwn: commissionAt(bid, own),
    netAtOwn: own - ownCost,
    // Kokonaiskulut (palkkio + lisäkulut) samalla viitehinnalla kaikille.
    costAtReference: refCost,
    netAtReference: refCost == null ? null : reference - refCost,
  };
}
