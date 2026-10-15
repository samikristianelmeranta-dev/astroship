// Ylläpito: kohteet, välittäjät, viestit, laskutus, arviot, mittarit ja jono.
import { loadCatalog, api, el, nodes, fmtEur, fmtDate, commissionText, estimateText } from '/forms.js';
import { photoGallery } from '/photos.js';

const loginForm = document.getElementById('login');
const tokenInput = document.getElementById('token');
const alertBox = document.getElementById('alert');
const content = document.getElementById('content');
const tabsEl = document.getElementById('tabs');
const panel = document.getElementById('panel');
const catalog = await loadCatalog();

const TABS = [
  ['pyynnot', 'Kohteet'],
  ['valittajat', 'Välittäjät'],
  ['viestit', 'Viestit'],
  ['laskutus', 'Laskutus'],
  ['arviot', 'Arviot'],
  ['mittarit', 'Mittarit'],
  ['jono', 'Jono'],
];
let tab = 'pyynnot';
const fmt = (iso) => (iso ? new Date(iso).toLocaleString('fi-FI', { dateStyle: 'short', timeStyle: 'short' }) : '–');
const row = (cells) => el('tr', {}, cells.map((c) => el('td', {}, c ?? '')));
const table = (headers, rows) => el('div', { class: 'table-wrap' }, el('table', {}, el('thead', {}, el('tr', {}, headers.map((h) => el('th', {}, h)))), el('tbody', {}, rows)));
const btn = (label, onclick, cls = 'btn btn-sm') => el('button', { type: 'button', class: cls, onclick }, label);

async function action(path, body = {}) {
  const res = await api('POST', path, body);
  await show();
  if (res.status >= 300) alertInPanel(res.data.error ?? 'Toiminto epäonnistui.');
}
function alertInPanel(text) {
  panel.prepend(el('div', { class: 'form-alert', role: 'alert' }, text));
}

function requestStatus(r) {
  const map = { vahvistamaton: ['no', 'Vahvistamaton'], peruttu: ['no', 'Peruttu'], valittu: ['ok', 'Valittu'], valmis: ['ok', 'Valmis'] };
  if (map[r.status]) return el('span', { class: `pill ${map[r.status][0]}` }, map[r.status][1]);
  if (r.open) return el('span', { class: 'pill wait' }, 'Avoin');
  return el('span', { class: 'pill no' }, r.bids.length ? 'Odottaa valintaa' : 'Ei arvioita');
}

async function requestsPanel() {
  const { data } = await api('GET', '/api/admin/overview');
  return table(['Jätetty', 'Kohde', 'Myyjä', 'Tila', 'Arviot', 'Kysymykset', ''],
    data.requests.map((r) => row([
      fmt(r.createdAt),
      [el('div', {}, `${catalog.propertyTypes[r.propertyType]}, ${r.area} m², ${catalog.rooms[r.rooms] ?? ''}\n${r.streetAddress ?? ''}\n${r.postalCode} ${r.city}, ${r.region}`), photoGallery(r.photoIds)],
      `${r.name}\n${r.email}\n${r.phone}`,
      [requestStatus(r), el('div', {}, r.deadline ? fmtDate(r.deadline) : '')],
      r.bids.length ? r.bids.map((b) => el('div', {}, `${r.acceptedBidIds.includes(b.id) ? '✓ ' : ''}${b.company}: ${estimateText(b)}, ${commissionText(b)}`)) : '–',
      r.questions.length ? r.questions.map((q) => el('div', {}, `${q.company}: ${q.wantsPhotos ? '[kuvapyyntö] ' : ''}${q.text}${q.answer ? `\n→ ${q.answer}` : ''}`)) : '–',
      [el('div', {}, el('a', { href: `/kohde#${r.consumerToken}` }, 'Myyjän näkymä')),
        r.open ? btn('Päätä arvioaika nyt', () => action(`/api/admin/requests/${r.id}/close`)) : null],
    ])));
}

function prhCell(prh) {
  if (!prh) return '–';
  if (prh.error) return el('span', { class: 'pill no', title: prh.error }, 'YTJ: ei tarkistettu');
  if (!prh.found) return el('span', { class: 'pill no' }, 'YTJ: ei löydy');
  return [
    el('div', {}, prh.name ?? ''),
    el('span', { class: `pill ${prh.active ? 'ok' : 'no'}` }, prh.active ? 'Toiminnassa' : 'Ei toiminnassa'), ' ',
    el('span', { class: `pill ${prh.prepaymentRegister ? 'ok' : 'wait'}` }, prh.prepaymentRegister ? 'Ennakkoperintä' : 'Ei ennakkoperintää'),
    prh.address ? el('div', { class: 'hint' }, prh.address) : null,
  ];
}

function approveCell(a) {
  if (a.status === 'hyvaksytty') return [el('span', { class: 'pill ok' }, 'Hyväksytty'), ' ', btn('Jäädytä', () => action(`/api/admin/agents/${a.id}/suspend`), 'link-btn')];
  const box = el('input', { type: 'checkbox', id: `registry-${a.id}` });
  return [
    a.status === 'jaadytetty' ? el('div', {}, el('span', { class: 'pill no' }, 'Jäädytetty')) : null,
    el('label', { class: 'check', for: `registry-${a.id}` }, box, el('span', {}, 'Välitysliike tarkistettu AVI:n välitysliikerekisteristä')),
    btn('Hyväksy', () => action(`/api/admin/agents/${a.id}/approve`, { registryChecked: box.checked })),
  ];
}

async function agentsPanel() {
  const { data } = await api('GET', '/api/admin/overview');
  return table(['Yritys', 'Y-tunnus / YTJ', 'Yhteystiedot', 'Alue', 'Arvio', 'Tila'],
    data.agents.map((a) => row([
      `${a.company}${a.chain ? ` (${a.chain})` : ''}\nRekisteröityi ${fmt(a.createdAt)}`,
      [el('div', {}, a.businessId), prhCell(a.prh), btn('Tarkista YTJ uudelleen', () => action(`/api/admin/agents/${a.id}/prh`), 'link-btn')],
      `${a.contactName}\n${a.email}\n${a.phone}`,
      `${a.regions.join(', ')}${a.radiusKm ? `\n${a.radiusKm} km postinumerosta ${a.homePostalCode}` : ''}`,
      a.rating ? `${String(a.rating.avg).replace('.', ',')} / 5 (${a.rating.count})` : '–',
      approveCell(a),
    ])));
}

async function outboxPanel() {
  const { data } = await api('GET', '/api/admin/outbox');
  return [
    el('p', { class: 'hint' }, 'Kaikki palvelun lähettämät viestit. Tila "kehitys" tarkoittaa, ettei viestipalvelua ole määritetty (POSTMARK_TOKEN) eikä viestiä lähetetty.'),
    table(['Aika', 'Kanava', 'Vastaanottaja', 'Tyyppi', 'Aihe ja sisältö', 'Tila'],
      data.messages.map((m) => row([fmt(m.createdAt), m.channel === 'sms' ? 'Tekstiviesti' : 'Sähköposti', m.to, m.kind,
        el('details', {}, el('summary', {}, m.subject || m.text.slice(0, 60)), el('pre', { class: 'mail' }, m.text)),
        el('span', { class: `pill ${m.status === 'lähetetty' ? 'ok' : m.status === 'virhe' ? 'no' : 'wait'}`, title: m.error ?? '' }, m.status)]))),
  ];
}

async function feesPanel() {
  const { data } = await api('GET', '/api/admin/fees');
  return [
    el('div', { class: 'offer-top' },
      el('p', {}, `Palvelumaksu ${data.feeEur} € (alv 0 %) jokaisesta valinnasta${data.startDate ? `, alkaen ${fmtDate(data.startDate)}` : ''}.`),
      el('a', { class: 'btn btn-sm', href: '/api/admin/fees.csv' }, 'Lataa CSV')),
    el('h2', {}, 'Kuukausittain'),
    table(['Kuukausi', 'Yritys', 'Y-tunnus', 'Valintoja', 'Yhteensä', 'Laskuttamatta'],
      data.summary.map((s) => row([s.month, s.company, s.businessId, s.count, fmtEur(s.totalEur), fmtEur(s.uninvoicedEur)]))),
    el('h2', {}, 'Maksut'),
    table(['Päivä', 'Yritys', 'Kohde', 'Summa', ''],
      data.fees.map((f) => row([fmt(f.createdAt), f.company, f.listing, fmtEur(f.amountEur),
        f.invoiced ? el('span', { class: 'pill ok' }, 'Laskutettu') : btn('Merkitse laskutetuksi', () => action(`/api/admin/fees/${f.id}/invoiced`))]))),
  ];
}

async function reviewsPanel() {
  const { data } = await api('GET', '/api/admin/reviews');
  return table(['Aika', 'Yritys', 'Arvio', 'Teksti', ''],
    data.reviews.map((r) => row([fmt(r.createdAt), r.company, '★'.repeat(r.rating), r.text,
      r.hidden ? btn('Näytä', () => action(`/api/admin/reviews/${r.id}/show`)) : btn('Piilota', () => action(`/api/admin/reviews/${r.id}/hide`), 'btn btn-ghost btn-sm')])));
}

async function funnelPanel() {
  const { data } = await api('GET', '/api/admin/funnel?days=30');
  const max = Math.max(1, ...data.steps.map((s) => s.count));
  return [
    el('p', { class: 'hint' }, `Viimeiset ${data.days} päivää. Vain päiväkohtaiset määrät, ei evästeitä eikä henkilötietoja.`),
    el('div', { class: 'card funnel' },
      data.steps.map((s, i) => {
        const prev = data.steps[i - 1]?.count;
        const bar = el('span', { class: 'funnel-bar' });
        bar.style.setProperty('--w', `${(s.count / max) * 100}%`);
        return el('div', { class: 'funnel-row' },
          el('span', { class: 'funnel-label' }, s.label),
          el('span', { class: 'funnel-track' }, bar),
          el('span', { class: 'funnel-count' }, String(s.count), prev ? el('small', {}, ` ${Math.round((s.count / prev) * 100)} %`) : null));
      })),
  ];
}

async function waitlistPanel() {
  const { data } = await api('GET', '/api/admin/waitlist');
  const regions = Object.entries(data.byRegion);
  return [
    el('p', { class: 'hint' }, `Pilottialueet: ${catalog.pilotRegions?.join(', ') ?? 'kaikki'}. Kun avaat uuden alueen (PILOT_REGIONS), lähetä jonossa oleville ilmoitus.`),
    regions.length
      ? table(['Maakunta', 'Odottaa', ''], regions.map(([region, n]) => row([region, n, btn('Lähetä "palvelu avattu" -viesti', () => action('/api/admin/waitlist/notify', { region }))])))
      : el('p', {}, 'Jonossa ei ole ketään.'),
    table(['Aika', 'Sähköposti', 'Postinumero', 'Maakunta', 'Ilmoitettu'],
      data.waitlist.map((w) => row([fmt(w.createdAt), w.email, w.postalCode, w.region, fmt(w.notifiedAt)]))),
  ];
}

const PANELS = { pyynnot: requestsPanel, valittajat: agentsPanel, viestit: outboxPanel, laskutus: feesPanel, arviot: reviewsPanel, mittarit: funnelPanel, jono: waitlistPanel };

async function show() {
  const probe = await api('GET', '/api/admin/overview');
  if (probe.status === 401) {
    loginForm.hidden = false;
    content.hidden = true;
    return;
  }
  loginForm.hidden = true;
  content.hidden = false;
  tabsEl.replaceChildren(...TABS.map(([key, label]) =>
    el('button', { type: 'button', role: 'tab', class: `tab${tab === key ? ' active' : ''}`, 'aria-selected': String(tab === key), onclick: () => ((tab = key), show()) }, label)));
  panel.replaceChildren(...nodes(await PANELS[tab]()));
}

loginForm.addEventListener('submit', async (e) => {
  e.preventDefault();
  alertBox.hidden = true;
  const { status, data } = await api('POST', '/api/admin/login', { token: tokenInput.value.trim() });
  if (status === 200) return show();
  alertBox.textContent = data.error ?? 'Kirjautuminen epäonnistui.';
  alertBox.hidden = false;
});

show();
