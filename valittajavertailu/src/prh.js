// YTJ:n avoimen rajapinnan (avoindata.prh.fi/opendata-ytj-api/v3) vastauksen tulkinta.
// Tulkitaan rekisterimerkintöjen suomenkielisiä kuvauksia, jotta pienet
// koodimuutokset rajapinnassa eivät riko tarkistusta.

const fi = (list) => (Array.isArray(list) ? list : []).find((d) => String(d.languageCode) === '1' || d.languageCode === 'fi')?.description
  ?? (Array.isArray(list) ? list[0]?.description : undefined);

export function parseYtjCompany(json) {
  const company = json?.companies?.[0];
  if (!company) return { found: false };
  const entries = (company.registeredEntries ?? []).filter((e) => !e.endDate);
  const has = (word) => entries.some((e) => String(fi(e.descriptions ?? e.description) ?? '').toLowerCase().includes(word));
  const name = (company.names ?? []).find((n) => !n.endDate)?.name ?? company.names?.[0]?.name ?? null;
  const address = (company.addresses ?? []).find((a) => a.type === 1 || a.type === '1') ?? company.addresses?.[0];
  const situations = (company.companySituations ?? []).filter((s) => !s.endDate);
  return {
    found: true,
    name,
    active: !company.endDate && situations.length === 0,
    prepaymentRegister: has('ennakkoperintä'),
    vatRegister: has('arvonlisävero'),
    employerRegister: has('työnantaja'),
    address: address
      ? [address.street, address.buildingNumber, address.postCode, (address.postOffices?.[0]?.city ?? '')]
          .filter(Boolean)
          .join(' ')
      : null,
  };
}
