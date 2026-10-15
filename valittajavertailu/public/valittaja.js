// Välittäjän näkymä: avoimet kohteet ja arviot, päättyneet kilpailut, profiili.
import { photoGallery, photoSrc, resizeToJpeg } from '/photos.js';
import {
  loadCatalog, api, el, nodes, fmtDate, timeLeft, projectFacts, formValues, clearErrors, clearErrorOnInput, showErrors,
  commissionText, estimateText,
} from '/forms.js';

const app = document.getElementById('app');
const loginForm = document.getElementById('login');
const loginAlert = document.getElementById('login-alert');
const catalog = await loadCatalog();
const savedNotice = new Set();
let tab = 'avoimet';
let state = null;

const OUTCOMES = {
  odottaa: ['wait', 'Odottaa myyjän valintaa'],
  valittu: ['ok', 'Myyjä valitsi sinut'],
  ei_valittu: ['no', 'Myyjä valitsi toisen välittäjän'],
  peruttu: ['no', 'Myyjä perui pyynnön'],
};

const input = (attrs) => el('input', { type: 'text', ...attrs });
function field(name, label, control, hint) {
  return el('div', { class: 'field', 'data-field': name }, el('label', { for: control.id }, label), control, hint ? el('div', { class: 'hint' }, hint) : null);
}
const num = (v) => (v == null ? '' : String(v).replace('.', ','));

// --- Arviolomake ---------------------------------------------------------------

function bidForm(project) {
  const b = project.myBid ?? {};
  const id = (n) => `${n}-${project.id}`;
  const templateSelect = el('select', { id: id('tpl'), 'aria-label': 'Tarjouspohja' },
    el('option', { value: '' }, state.templates.length ? 'Täytä palkkio pohjasta…' : 'Ei tallennettuja pohjia'),
    state.templates.map((t) => el('option', { value: t.id }, t.name)));
  const type = b.commissionType ?? 'percent';
  const radio = (value, label) =>
    el('label', { class: 'check' }, el('input', { type: 'radio', name: 'commissionType', value, checked: type === value }), el('span', {}, label));

  const form = el(
    'form',
    { class: 'bid-form', novalidate: true },
    el('div', { class: 'bid-form-head' }, el('strong', {}, b.id ? 'Oma arviosi' : 'Jätä arvio ja palkkiotarjous'), templateSelect),
    el('div', { class: 'row' },
      field('estimateLow', 'Hinta-arvio alkaen (€, velaton)', input({ id: id('lo'), name: 'estimateLow', inputmode: 'numeric', value: b.estimateLow ?? '' })),
      field('estimateHigh', 'Hinta-arvio enintään (€, velaton)', input({ id: id('hi'), name: 'estimateHigh', inputmode: 'numeric', value: b.estimateHigh ?? '' })),
    ),
    field('askingPrice', 'Ehdotettu pyyntöhinta (€, valinnainen)', input({ id: id('ask'), name: 'askingPrice', inputmode: 'numeric', value: b.askingPrice ?? '' })),
    el('div', { class: 'field', 'data-field': 'commissionType' }, el('span', { class: 'label' }, 'Välityspalkkio (sis. alv)'),
      el('div', { class: 'check-grid' }, radio('percent', 'Prosentti kauppahinnasta'), radio('fixed', 'Kiinteä palkkio'))),
    el('div', { class: 'row', 'data-commission': 'percent', hidden: type !== 'percent' },
      field('commissionPercent', 'Palkkio % (sis. alv)', input({ id: id('pct'), name: 'commissionPercent', inputmode: 'decimal', value: num(b.commissionPercent) })),
      field('commissionMinEur', 'Vähimmäispalkkio € (valinnainen)', input({ id: id('min'), name: 'commissionMinEur', inputmode: 'numeric', value: b.commissionMinEur ?? '' })),
    ),
    el('div', { class: 'row', 'data-commission': 'fixed', hidden: type !== 'fixed' },
      field('commissionFixedEur', 'Kiinteä palkkio € (sis. alv)', input({ id: id('fix'), name: 'commissionFixedEur', inputmode: 'numeric', value: b.commissionFixedEur ?? '' })),
    ),
    el('div', { class: 'row row-3' },
      field('extraCostsEur', 'Lisäkulut € (sis. alv)', input({ id: id('extra'), name: 'extraCostsEur', inputmode: 'numeric', value: b.extraCostsEur ?? 0 }), 'Esim. ilmoitus- tai asiakirjakulut palkkion päälle.'),
      field('contractMonths', 'Toimeksianto (kk)', input({ id: id('months'), name: 'contractMonths', inputmode: 'numeric', value: b.contractMonths ?? 4 })),
      field('validDays', 'Tarjous voimassa (pv)', input({ id: id('valid'), name: 'validDays', inputmode: 'numeric', value: b.validDays ?? 30 })),
    ),
    el('div', { class: 'field', 'data-field': 'includes' },
      el('span', { class: 'label' }, 'Palkkioon sisältyy'),
      el('div', { class: 'check-grid' },
        Object.entries(catalog.bidIncludes).map(([key, label]) =>
          el('label', { class: 'check' }, el('input', { type: 'checkbox', name: 'includes', value: key, checked: b.includes?.includes(key) ?? false }), el('span', {}, label))),
      ),
    ),
    el('label', { class: 'check' }, el('input', { type: 'checkbox', name: 'noSaleNoFee', checked: b.noSaleNoFee ?? false }),
      el('span', {}, 'Ei kauppaa, ei palkkiota eikä kuluja')),
    el('div', { class: 'row' },
      field('saleTimeEstimate', 'Arvioitu myyntiaika', input({ id: id('time'), name: 'saleTimeEstimate', placeholder: 'esim. 6–10 viikkoa', value: b.saleTimeEstimate ?? '' })),
      field('agentName', 'Kohteen välittäjä (valinnainen)', input({ id: id('agent'), name: 'agentName', placeholder: 'esim. Liisa Laine, LKV', value: b.agentName ?? '' })),
    ),
    field('description', 'Perustelut ja markkinointi', el('textarea', { id: id('desc'), name: 'description', rows: 4, placeholder: 'Esim. alueen vertailukaupat, ostajakysyntä ja miten markkinoisit kohdetta. Ei yhteystietoja.' }, b.description ?? '')),
    el('div', { class: 'form-actions' },
      el('div', { class: 'stack-sm' },
        savedNotice.has(project.id) ? el('span', { class: 'saved' }, 'Arvio tallennettu') : el('span', { class: 'hint' }, 'Muut välittäjät eivät näe arviotasi. Voit muuttaa sitä määräaikaan asti.'),
        el('button', { type: 'button', class: 'link-btn', onclick: () => saveTemplate(form) }, 'Tallenna palkkio pohjaksi'),
      ),
      el('button', { type: 'submit', class: 'btn' }, b.id ? 'Päivitä arvio' : 'Jätä arvio'),
    ),
  );
  clearErrorOnInput(form);

  const syncCommission = () => {
    const value = form.querySelector('[name=commissionType]:checked')?.value ?? 'percent';
    form.querySelectorAll('[data-commission]').forEach((row) => (row.hidden = row.dataset.commission !== value));
  };
  form.addEventListener('change', (e) => e.target.name === 'commissionType' && syncCommission());

  templateSelect.addEventListener('change', () => {
    const tpl = state.templates.find((t) => t.id === templateSelect.value);
    if (!tpl) return;
    for (const [k, v] of Object.entries(tpl.fields)) {
      if (k === 'includes') form.querySelectorAll('[name=includes]').forEach((c) => (c.checked = v.includes(c.value)));
      else if (k === 'noSaleNoFee') form.noSaleNoFee.checked = Boolean(v);
      else if (k === 'commissionType') form.querySelectorAll('[name=commissionType]').forEach((r) => (r.checked = r.value === v));
      else if (form.elements[k]) form.elements[k].value = typeof v === 'number' ? num(v) : v;
    }
    syncCommission();
  });

  form.addEventListener('submit', async (e) => {
    e.preventDefault();
    clearErrors(form);
    const { status, data } = await api('PUT', `/api/agent/bids/${project.id}`, formValues(form));
    if (status === 200 || status === 201) {
      savedNotice.add(project.id);
      return load();
    }
    if (data.errors) return showErrors(form, data.errors);
    form.append(el('div', { class: 'form-alert', role: 'alert' }, data.error ?? 'Tallennus epäonnistui.'));
  });
  return form;
}

// Pohjan nimi kysytään sivulla (selaimen prompt-ikkuna ei toimi kaikkialla).
function saveTemplate(form) {
  const row = el('div', { class: 'confirm' });
  const nameInput = input({ id: `tplname-${Date.now()}`, placeholder: 'Pohjan nimi, esim. Kerrostalo perus', 'aria-label': 'Pohjan nimi' });
  row.append(nameInput,
    el('button', { type: 'button', class: 'btn btn-sm', onclick: async () => {
      const { status, data } = await api('POST', '/api/agent/templates', { ...formValues(form), name: nameInput.value });
      if (status === 201) return load();
      row.append(el('span', { class: 'error' }, data.errors?.templateName ?? 'Tallennus epäonnistui.'));
    } }, 'Tallenna'),
    el('button', { type: 'button', class: 'link-btn', onclick: () => row.remove() }, 'Peruuta'));
  form.querySelector('.form-actions').before(row);
  nameInput.focus();
}

// --- Kysymykset ja kuvapyynnöt ------------------------------------------------------

function questionsBlock(project) {
  const list = project.questions.map((q) =>
    el('div', { class: 'qa' },
      el('p', { class: 'q' }, q.wantsPhotos ? [el('span', { class: 'pill ask' }, 'Kuvapyyntö'), ' '] : null, q.text, q.mine ? el('span', { class: 'hint' }, ' (sinun)') : null),
      q.answer ? el('p', { class: 'a' }, q.answer) : el('p', { class: 'hint' }, 'Odottaa vastausta'),
      q.answerPhotoIds.length ? photoGallery(q.answerPhotoIds) : null));
  const text = el('textarea', { id: `ask-${project.id}`, rows: 2, maxlength: 500, placeholder: 'Esim. Milloin kylpyhuone on remontoitu? Voisitko lisätä kuvan saunasta?', 'aria-label': 'Kysymys myyjälle' });
  const wantsPhotos = el('input', { type: 'checkbox', id: `wants-${project.id}` });
  const status = el('span', { class: 'hint' });
  const button = el('button', { type: 'button', class: 'btn btn-ghost btn-sm' }, 'Lähetä');
  button.addEventListener('click', async () => {
    const { status: s, data } = await api('POST', `/api/agent/questions/${project.id}`, { text: text.value, wantsPhotos: wantsPhotos.checked });
    if (s === 201) return load();
    status.textContent = data.errors?.text ?? data.error ?? 'Lähetys epäonnistui.';
    status.className = 'error';
  });
  return el('details', { class: 'qa-block', open: project.questions.length > 0 },
    el('summary', {}, `Kysy myyjältä tai pyydä kuvia (${project.questions.length})`),
    el('p', { class: 'hint' }, 'Kysymys, vastaus ja lisätyt kuvat näkyvät kaikille kohteen välittäjille ilman nimeäsi. Älä kirjoita yhteystietoja.'),
    list,
    el('div', { class: 'answer-row' }, text, button),
    el('label', { class: 'check', for: `wants-${project.id}` }, wantsPhotos, el('span', {}, 'Pyydän lisää kuvia')),
    status);
}

// --- Kortit -------------------------------------------------------------------

const titleOf = (p) => `${catalog.propertyTypes[p.propertyType] ?? 'Asunto'}, ${String(p.area).replace('.', ',')} m²`;

function openCard(project, approved) {
  return el(
    'article',
    { class: 'card' },
    el('div', { class: 'offer-top' },
      el('div', {},
        el('p', { class: 'eyebrow' }, `${project.city || project.postalCode} · ${project.postalCode} · ${project.region}`),
        el('h3', {}, titleOf(project))),
      el('span', { class: project.myBid ? 'pill ok' : 'pill wait' }, project.myBid ? `Arvioitu ${estimateText(project.myBid)}` : 'Ei vielä arviota')),
    el('p', { class: 'hint' }, `Arvioaika päättyy ${fmtDate(project.deadline)} (${timeLeft(project.deadline)})`),
    projectFacts(project, catalog),
    photoGallery(project.photoIds),
    approved ? questionsBlock(project) : null,
    approved ? bidForm(project) : null,
  );
}

function historyCard(item) {
  const [tone, text] = OUTCOMES[item.outcome];
  return el(
    'article',
    { class: 'card' },
    el('div', { class: 'offer-top' },
      el('div', {},
        el('p', { class: 'eyebrow' }, `${item.request.city || item.request.postalCode} · päättyi ${fmtDate(item.request.deadline)}`),
        el('h3', {}, titleOf(item.request)),
        el('p', { class: 'hint' }, `Arviosi ${estimateText(item.myBid)} · palkkio ${commissionText(item.myBid)}`)),
      el('span', { class: `pill ${tone}` }, text)),
    item.contact
      ? el('div', { class: 'notice' },
          el('strong', {}, 'Myyjän yhteystiedot'),
          el('p', {}, `${item.contact.name}\n${item.contact.address}\n${item.contact.phone}\n${item.contact.email}`),
          item.sharedSelection ? el('p', {}, el('strong', {}, 'Myyjä valitsi kaksi välittäjää arviokäynnille, ja sinä olet toinen heistä.')) : null,
          el('p', {}, 'Ota yhteyttä myyjään mahdollisimman pian ja sovi arviokäynti.'))
      : null,
    item.review ? el('p', {}, el('span', { class: 'stars' }, '★'.repeat(item.review.rating) + '☆'.repeat(5 - item.review.rating)), item.review.text ? ` ${item.review.text}` : '') : null,
  );
}

// --- Profiili -----------------------------------------------------------------

function profilePanel(agent) {
  const logoImg = el('img', { class: 'company-logo lg', alt: '' });
  if (agent.logoPhotoId) photoSrc(agent.logoPhotoId).then((src) => src && (logoImg.src = src));
  const logoInput = el('input', { type: 'file', accept: 'image/*', id: 'logo-input', class: 'visually-hidden' });
  logoInput.addEventListener('change', async () => {
    const file = logoInput.files[0];
    if (!file) return;
    const { status } = await api('POST', '/api/agent/logo', { dataUrl: await resizeToJpeg(file) });
    if (status === 201) load();
  });

  const check = (name, value, label, checked) =>
    el('label', { class: 'check' }, el('input', { type: 'checkbox', name, value, checked }), el('span', {}, label));
  const form = el(
    'form',
    { class: 'card stack', novalidate: true },
    el('div', { class: 'company' }, logoImg, el('div', {}, el('h2', {}, agent.company), el('label', { class: 'link-btn', for: 'logo-input' }, agent.logoPhotoId ? 'Vaihda logo' : 'Lisää logo'), logoInput)),
    field('description', 'Esittely', el('textarea', { id: 'p-desc', name: 'description', rows: 3, maxlength: 1000 }, agent.description ?? ''), 'Näkyy myyjälle arviosi yhteydessä.'),
    el('div', { class: 'row' },
      field('chain', 'Ketju (valinnainen)', input({ id: 'p-chain', name: 'chain', value: agent.chain ?? '' })),
      field('website', 'Verkkosivu', input({ id: 'p-web', name: 'website', value: agent.website ?? '', placeholder: 'https://' }), 'Näytetään vasta, kun myyjä on valinnut sinut.'),
    ),
    el('div', { class: 'field', 'data-field': 'regions' }, el('span', { class: 'label' }, 'Maakunnat'),
      el('div', { class: 'check-grid' }, catalog.regions.map((r) => check('regions', r, r, agent.regions.includes(r))))),
    el('div', { class: 'row' },
      field('homePostalCode', 'Toimiston postinumero', input({ id: 'p-home', name: 'homePostalCode', inputmode: 'numeric', maxlength: 5, value: agent.homePostalCode ?? '' })),
      field('radiusKm', 'Toimintasäde (km)', input({ id: 'p-radius', name: 'radiusKm', inputmode: 'numeric', value: agent.radiusKm ?? '' }),
        catalog.radiusAvailable ? 'Näet myös maakuntiesi ulkopuoliset kohteet tämän säteen sisällä.' : 'Säde otetaan käyttöön, kun postinumeroiden sijainnit on ladattu palvelimelle.'),
    ),
    el('div', { class: 'field' }, el('span', { class: 'label' }, 'Ilmoitukset uusista kohteista'),
      check('notifyEmail', 'true', 'Sähköposti', agent.notifyEmail !== false),
      check('notifySms', 'true', 'Tekstiviesti (myös kun myyjä valitsee sinut)', Boolean(agent.notifySms))),
    el('div', { class: 'form-actions' }, el('span', { class: 'hint', id: 'profile-status' }), el('button', { type: 'submit', class: 'btn' }, 'Tallenna profiili')),
  );
  clearErrorOnInput(form);
  form.addEventListener('submit', async (e) => {
    e.preventDefault();
    clearErrors(form);
    const values = formValues(form);
    const { status, data } = await api('PUT', '/api/agent/profile', {
      ...values, regions: values.regions ?? [], notifyEmail: form.notifyEmail.checked, notifySms: form.notifySms.checked,
    });
    if (status === 200) {
      state.agent = data.agent;
      form.querySelector('#profile-status').textContent = 'Tallennettu.';
      return;
    }
    showErrors(form, data.errors ?? {});
  });

  const prh = agent.prh;
  return el('div', { class: 'stack' },
    form,
    el('div', { class: 'card stack' },
      el('p', { class: 'eyebrow' }, 'Tarkistukset'),
      el('p', {}, prh?.found ? `YTJ: ${prh.name}, ${prh.active ? 'toiminnassa' : 'ei toiminnassa'}${prh.prepaymentRegister ? ', ennakkoperintärekisterissä' : ''}` : 'YTJ-tarkistus odottaa ylläpitoa.'),
      el('p', {}, agent.registryCheckedAt ? `Välitysliikerekisteri tarkistettu ${fmtDate(agent.registryCheckedAt)}` : 'Välitysliikerekisteri (AVI) tarkistetaan ennen hyväksyntää.')),
    state.templates.length
      ? el('div', { class: 'card stack' }, el('p', { class: 'eyebrow' }, 'Palkkiopohjat'),
          state.templates.map((t) => el('div', { class: 'offer-top' }, el('span', {}, `${t.name}${t.fields.commissionType ? ` · ${commissionText(t.fields)}` : ''}`),
            el('button', { type: 'button', class: 'link-btn', onclick: async () => ((await api('DELETE', `/api/agent/templates/${t.id}`)), load()) }, 'Poista'))))
      : null,
    el('div', { class: 'card stack' }, el('p', { class: 'eyebrow' }, `Arviot${state.rating ? ` · ${String(state.rating.avg).replace('.', ',')} / 5 (${state.rating.count})` : ''}`),
      state.reviews.length
        ? state.reviews.map((r) => el('p', {}, el('span', { class: 'stars' }, '★'.repeat(r.rating) + '☆'.repeat(5 - r.rating)), r.text ? ` ${r.text}` : ''))
        : el('p', { class: 'hint' }, 'Myyjät voivat arvioida sinut, kun yhteistyö on päättynyt.')),
  );
}

// --- Sivu -----------------------------------------------------------------------

function render() {
  const { agent, open, history, fee } = state;
  const approved = agent.status === 'hyvaksytty';
  const tabs = [['avoimet', `Avoimet (${open.length})`], ['paattyneet', `Päättyneet (${history.length})`], ['profiili', 'Profiili']];
  app.replaceChildren(
    ...nodes(
      el('div', { class: 'page-head' }, el('h1', {}, agent.company), el('button', { type: 'button', class: 'btn btn-ghost btn-sm', onclick: logout }, 'Kirjaudu ulos')),
      approved
        ? null
        : el('div', { class: 'notice warn' }, el('strong', {}, agent.status === 'jaadytetty' ? 'Tilisi on jäädytetty.' : 'Tilisi odottaa hyväksyntää.'),
            el('p', {}, agent.status === 'jaadytetty' ? 'Ota yhteyttä ylläpitoon.' : 'Tarkistamme Y-tunnuksen ja välitysliikerekisterin. Saat sähköpostin, kun tili on hyväksytty. Voit täyttää profiilin jo nyt.')),
      fee?.eur ? el('p', { class: 'hint' }, `Palvelumaksu ${fee.eur} € (alv 0 %) vain, kun myyjä valitsee sinut${fee.startDate ? `, alkaen ${fmtDate(fee.startDate)}` : ''}. Ks. välittäjien käyttöehdot.`) : null,
      el('div', { class: 'tabs', role: 'tablist' }, tabs.map(([key, label]) =>
        el('button', { type: 'button', role: 'tab', class: `tab${tab === key ? ' active' : ''}`, 'aria-selected': String(tab === key), onclick: () => ((tab = key), render()) }, label))),
      tab === 'avoimet'
        ? (open.length ? open.map((p) => openCard(p, approved)) : el('p', { class: 'hint' }, approved ? 'Alueellasi ei ole juuri nyt avoimia myyntikohteita. Saat ilmoituksen uusista.' : ''))
        : null,
      tab === 'paattyneet' ? (history.length ? history.map(historyCard) : el('p', { class: 'hint' }, 'Ei vielä päättyneitä kilpailuja.')) : null,
      tab === 'profiili' ? profilePanel(agent) : null,
    ),
  );
}

async function load() {
  const { status, data } = await api('GET', '/api/agent');
  if (status === 401) {
    loginForm.hidden = false;
    app.hidden = true;
    return;
  }
  state = data;
  loginForm.hidden = true;
  app.hidden = false;
  render();
}

async function logout() {
  await api('POST', '/api/logout', {});
  load();
}

loginForm.addEventListener('submit', async (e) => {
  e.preventDefault();
  loginAlert.hidden = true;
  const { status, data } = await api('POST', '/api/login', formValues(loginForm));
  if (status === 200) return load();
  loginAlert.textContent = data.error ?? 'Kirjautuminen epäonnistui.';
  loginAlert.hidden = false;
});

load();
