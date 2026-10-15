// Lomakkeiden yhteiset apufunktiot.

export async function loadCatalog() {
  const res = await fetch('/api/catalog');
  return res.json();
}

export function fillSelect(select, options, placeholder = 'Valitse…') {
  const entries = Array.isArray(options) ? options.map((o) => [o, o]) : Object.entries(options);
  select.replaceChildren(new Option(placeholder, ''), ...entries.map(([value, label]) => new Option(label, value)));
}

export function formValues(form) {
  const values = {};
  for (const el of form.elements) {
    if (!el.name) continue;
    if (el.type === 'checkbox') {
      if (form.querySelectorAll(`[name="${el.name}"]`).length > 1) {
        values[el.name] ??= [];
        if (el.checked) values[el.name].push(el.value);
      } else {
        values[el.name] = el.checked;
      }
    } else if (el.type === 'radio') {
      if (el.checked) values[el.name] = el.value;
    } else {
      values[el.name] = el.value;
    }
  }
  return values;
}

export function clearErrors(form) {
  form.querySelectorAll('.field.invalid').forEach((f) => f.classList.remove('invalid'));
  form.querySelectorAll('.error').forEach((e) => e.remove());
}

// Poistaa kentän virheen heti, kun käyttäjä korjaa sitä.
export function clearErrorOnInput(form) {
  const handler = (e) => {
    const wrap = e.target.closest('[data-field]');
    if (!wrap?.classList.contains('invalid')) return;
    wrap.classList.remove('invalid');
    wrap.querySelectorAll('.error').forEach((x) => x.remove());
  };
  form.addEventListener('input', handler);
  form.addEventListener('change', handler);
}

// Näyttää virheet kenttien alla. Palauttaa ensimmäisen virheellisen kentän.
export function showErrors(form, errors) {
  let first = null;
  for (const [field, message] of Object.entries(errors)) {
    const wrap = form.querySelector(`[data-field="${field}"]`);
    if (!wrap) continue;
    wrap.classList.add('invalid');
    const el = document.createElement('div');
    el.className = 'error';
    el.textContent = message;
    wrap.append(el);
    first ??= wrap;
  }
  first?.querySelector('input, select, textarea')?.focus();
  return first;
}

// Kirjaa nimettömän tapahtuman suppilomittariin (vain päiväkohtainen määrä).
export function track(name) {
  fetch('/api/events', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ name }), keepalive: true }).catch(() => {});
}

export function postJson(url, body) {
  return api('POST', url, body);
}

export async function api(method, url, body) {
  const res = await fetch(url, {
    method,
    headers: { 'Content-Type': 'application/json' },
    body: body === undefined ? undefined : JSON.stringify(body),
  });
  return { status: res.status, data: await res.json().catch(() => ({})) };
}

// Rakentaa DOM-elementin turvallisesti: tekstit menevät aina textContentina.
export function el(tag, attrs = {}, ...children) {
  const node = document.createElement(tag);
  for (const [k, v] of Object.entries(attrs)) {
    if (v == null || v === false) continue;
    if (k.startsWith('on')) node.addEventListener(k.slice(2), v);
    else if (k === 'class') node.className = v;
    else node.setAttribute(k, v === true ? '' : v);
  }
  node.append(...nodes(children));
  return node;
}

// Litistää sisäkkäiset listat ja pudottaa tyhjät, jotta ehdollisia osia voi antaa suoraan.
export const nodes = (...items) => items.flat(Infinity).filter((c) => c != null && c !== false);

export const fmtEur = (n) =>
  new Intl.NumberFormat('fi-FI', { style: 'currency', currency: 'EUR', maximumFractionDigits: 0 }).format(n);

export const fmtDate = (iso) =>
  new Date(iso).toLocaleString('fi-FI', { weekday: 'short', day: 'numeric', month: 'numeric', hour: '2-digit', minute: '2-digit' });

export function timeLeft(iso) {
  const ms = Date.parse(iso) - Date.now();
  if (ms <= 0) return 'päättynyt';
  const h = Math.floor(ms / 36e5);
  if (h >= 48) return `${Math.ceil(ms / 864e5)} päivää`;
  if (h >= 1) return `${h} tuntia`;
  return `${Math.max(1, Math.ceil(ms / 6e4))} minuuttia`;
}

export const fmtNum = (n) => new Intl.NumberFormat('fi-FI', { maximumFractionDigits: 1 }).format(n);

// Myyntikohteen tiedot luettavassa muodossa (välittäjän, myyjän ja ylläpidon näkymät).
// Katuosoite näkyy vain, jos se on mukana datassa (myyjä itse ja ylläpito).
export function projectFacts(p, catalog) {
  const label = (list, key) => catalog[list]?.[key] ?? key;
  const place = [p.streetAddress, `${p.postalCode} ${p.city ?? ''}`.trim()].filter(Boolean).join(', ');
  const rows = [
    ['Asunto', `${label('propertyTypes', p.propertyType)}, ${fmtNum(p.area)} m²`],
    ['Huoneet', [label('rooms', p.rooms), p.roomLayout].filter(Boolean).join(', ')],
    ['Omistusmuoto', label('ownership', p.ownership)],
    ['Rakennusvuosi', p.buildYear],
    ['Kerros', p.floor],
    ['Kunto', p.condition && p.condition !== 'ei_tiedossa' ? label('conditions', p.condition) : null],
    ['Varustelu', p.features?.length ? p.features.map((f) => label('features', f)).join(', ') : null],
    ['Tontti', p.plotArea ? `${fmtNum(p.plotArea)} m²` : null],
    ['Hoitovastike', p.maintenanceFee != null ? `${fmtEur(p.maintenanceFee)}/kk` : null],
    ['Myyjän hintatoive', p.priceExpectation ? fmtEur(p.priceExpectation) : null],
    ['Myyntiaikataulu', label('timelines', p.timeline)],
    ['Sijainti', `${place}, ${p.region}`],
    ['Lisätiedot', p.details],
  ].filter(([, v]) => v != null && v !== '');
  return el('dl', { class: 'facts' }, rows.map(([k, v]) => [el('dt', {}, k), el('dd', {}, String(v))]));
}

// Välityspalkkio luettavassa muodossa, esim. "3,2 % (väh. 3 500 €)" tai "Kiinteä 4 900 €".
export function commissionText(b) {
  if (b.commissionType === 'fixed') return `Kiinteä ${fmtEur(b.commissionFixedEur)}`;
  const pct = `${fmtNum(b.commissionPercent)} %`;
  return b.commissionMinEur ? `${pct} (väh. ${fmtEur(b.commissionMinEur)})` : pct;
}

export const estimateText = (b) => `${fmtEur(b.estimateLow)} – ${fmtEur(b.estimateHigh)}`;
