import { loadCatalog, fillSelect, formValues, clearErrors, clearErrorOnInput, showErrors, postJson, api, el, track } from '/forms.js';
import { createUploader } from '/photos.js';

const form = document.getElementById('listing-form');
const steps = [...form.querySelectorAll('.step')];
const bars = [...form.querySelectorAll('.progress span')];
const prevBtn = document.getElementById('prev');
const nextBtn = document.getElementById('next');
const alertBox = document.getElementById('form-alert');
let current = 0;

// Kentät, jotka tarkistetaan ennen kuin vaiheesta pääsee eteenpäin.
const STEP_FIELDS = [
  ['propertyType', 'ownership', 'buildYear', 'area', 'rooms', 'roomLayout', 'floor', 'plotArea', 'condition', 'maintenanceFee', 'features'],
  ['streetAddress', 'postalCode', 'region', 'photos', 'timeline', 'priceExpectation', 'deadlineDays', 'details'],
  ['name', 'email', 'phone', 'consent'],
];

const catalog = await loadCatalog();

const typeChoices = document.getElementById('type-choices');
for (const [key, label] of Object.entries(catalog.propertyTypes)) {
  typeChoices.append(
    el('div', { class: 'choice' },
      el('input', { type: 'radio', name: 'propertyType', id: `t-${key}`, value: key }),
      el('label', { for: `t-${key}` }, label)),
  );
}
const features = document.getElementById('features');
for (const [key, label] of Object.entries(catalog.features)) {
  features.append(el('label', { class: 'check' }, el('input', { type: 'checkbox', name: 'features', value: key }), el('span', {}, label)));
}

fillSelect(form.ownership, catalog.ownership);
fillSelect(form.rooms, catalog.rooms);
fillSelect(form.condition, catalog.conditions, 'Valitse…');
form.condition.value = 'ei_tiedossa';
fillSelect(form.timeline, catalog.timelines);
fillSelect(form.region, catalog.regions);
fillSelect(form.deadlineDays, catalog.deadlineOptions);
form.deadlineDays.value = '7';
document.getElementById('photos-hint').textContent = `(vähintään ${catalog.minPhotos}, enintään ${catalog.maxPhotos})`;

// Tyyppikohtaiset kentät: kerros kerros- ja rivitaloille, tontti taloille, vastike osakkeille.
function syncTypeFields() {
  const type = form.querySelector('input[name=propertyType]:checked')?.value;
  for (const node of form.querySelectorAll('[data-types]')) node.hidden = !type || !node.dataset.types.split(' ').includes(type);
  for (const node of form.querySelectorAll('[data-ownership]')) node.hidden = form.ownership.value !== node.dataset.ownership;
}
form.addEventListener('change', (e) => {
  if (e.target.name === 'propertyType') {
    // Kerrostalo on lähes aina osake; talot kiinteistöjä. Esivalinta, jota voi muuttaa.
    if (!form.ownership.value) form.ownership.value = ['kerrostalo', 'rivitalo'].includes(e.target.value) ? 'osake' : 'kiinteisto_oma';
  }
  if (e.target.name === 'propertyType' || e.target.name === 'ownership') syncTypeFields();
});

const uploader = createUploader({
  input: document.getElementById('photo-input'),
  dropzone: document.getElementById('dropzone'),
  list: document.getElementById('thumbs'),
  max: catalog.maxPhotos,
});

// Kattavuus: toimiiko palvelu alueella ja montako välittäjää kohteen näkee.
const coverageBox = document.getElementById('coverage');
let inPilot = true;

async function updateCoverage() {
  const code = form.postalCode.value.trim();
  if (!/^\d{5}$/.test(code)) {
    coverageBox.hidden = true;
    inPilot = true;
    return;
  }
  const params = new URLSearchParams({ postalCode: code, region: form.region.value });
  const { data } = await api('GET', `/api/coverage?${params}`);
  inPilot = data.inPilot;
  coverageBox.hidden = false;
  coverageBox.className = `coverage ${data.inPilot ? (data.agents ? 'ok' : 'empty') : 'out'}`;
  if (!data.inPilot) {
    const email = el('input', { type: 'email', id: 'waitlist-email', placeholder: 'sähköpostiosoite', autocomplete: 'email', 'aria-label': 'Sähköposti jonoa varten' });
    const button = el('button', { type: 'button', class: 'btn btn-sm' }, 'Liity jonoon');
    const status = el('span', { class: 'hint' });
    button.addEventListener('click', async () => {
      const res = await api('POST', '/api/waitlist', { email: email.value, postalCode: code });
      status.textContent = res.status === 201 ? 'Kiitos! Ilmoitamme, kun palvelu avautuu alueellasi.' : res.data.errors?.email ?? 'Tarkista sähköpostiosoite.';
    });
    coverageBox.replaceChildren(
      el('strong', {}, `Toimimme toistaiseksi alueella ${data.pilotRegions.join(', ')}.`),
      el('p', {}, `Jätä sähköpostisi, niin kerromme, kun palvelu avautuu alueella ${data.region ?? 'sinun alueellasi'}.`),
      el('div', { class: 'copy-row' }, email, button),
      status,
    );
  } else if (data.agents) {
    coverageBox.replaceChildren(el('strong', {}, `Alueellasi ${data.agents} ${data.agents === 1 ? 'välittäjä' : 'välittäjää'}`), el('p', {}, 'He saavat kohteesi heti, kun vahvistat sen.'));
  } else {
    coverageBox.replaceChildren(
      el('strong', {}, 'Alueellasi ei ole vielä välittäjiä palvelussa.'),
      el('p', {}, 'Voit silti jättää pyynnön. Uudet välittäjät näkevät avoimet kohteet heti hyväksynnän jälkeen.'),
    );
  }
}

form.postalCode.addEventListener('input', async () => {
  const code = form.postalCode.value.trim();
  if (!/^\d{5}$/.test(code)) return updateCoverage();
  const { region } = await (await fetch(`/api/region?postalCode=${code}`)).json();
  if (region) form.region.value = region;
  updateCoverage();
});
form.region.addEventListener('change', updateCoverage);

// Etusivun tai kaupunkisivun pikalomakkeelta tulleet valinnat.
try {
  const quick = JSON.parse(sessionStorage.getItem('vv-quickstart') ?? 'null');
  sessionStorage.removeItem('vv-quickstart');
  if (quick?.propertyType) form.querySelector(`#t-${quick.propertyType}`)?.click();
  if (quick?.postalCode) {
    form.postalCode.value = quick.postalCode;
    form.postalCode.dispatchEvent(new Event('input'));
  }
} catch {}

const toNum = (v) => Number(String(v ?? '').replace(/\s/g, '').replace(',', '.'));

// Kevyt selainpuolen tarkistus; palvelin tekee lopullisen validoinnin.
function localErrors(fields, v) {
  const e = {};
  const need = (f, msg) => fields.includes(f) && (e[f] = msg);
  if (!v.propertyType) need('propertyType', 'Valitse asunnon tyyppi.');
  if (!v.ownership) need('ownership', 'Valitse omistusmuoto.');
  if (!(toNum(v.area) >= 10)) need('area', 'Anna asuinpinta-ala.');
  if (!v.rooms) need('rooms', 'Valitse huoneiden määrä.');
  if (!(Number(v.buildYear) >= 1700 && Number(v.buildYear) <= new Date().getFullYear() + 2)) need('buildYear', 'Anna rakennusvuosi.');
  if (v.streetAddress.trim().length < 3) need('streetAddress', 'Anna asunnon katuosoite.');
  if (!/^\d{5}$/.test(v.postalCode)) need('postalCode', 'Postinumerossa on 5 numeroa.');
  if (!v.region) need('region', 'Valitse maakunta.');
  if (!inPilot) need('region', 'Palvelu ei vielä toimi tällä alueella. Liity jonoon yllä.');
  if (uploader.pending()) need('photos', 'Odota hetki, kuvat latautuvat vielä.');
  else if (uploader.failed()) need('photos', 'Poista kuvat, joiden lataus epäonnistui, tai yritä uudelleen.');
  else if (v.photoIds.length < catalog.minPhotos) need('photos', `Lisää vähintään ${catalog.minPhotos} kuvaa. Puhelimella otetut kuvat riittävät.`);
  if (!v.timeline) need('timeline', 'Valitse myynnin aikataulu.');
  if (!v.deadlineDays) need('deadlineDays', 'Valitse arvioiden määräaika.');
  if (v.name.trim().length < 2) need('name', 'Anna nimesi.');
  if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(v.email)) need('email', 'Anna toimiva sähköpostiosoite.');
  if (v.phone.replace(/\D/g, '').length < 6) need('phone', 'Anna puhelinnumero.');
  if (!v.consent) need('consent', 'Hyväksy käyttöehdot.');
  return e;
}

function show(step) {
  current = step;
  steps.forEach((s, i) => (s.hidden = i !== step));
  bars.forEach((b, i) => b.classList.toggle('done', i <= step));
  prevBtn.hidden = step === 0;
  nextBtn.textContent = step === steps.length - 1 ? 'Lähetä välittäjille' : 'Jatka';
  window.scrollTo({ top: 0, behavior: 'smooth' });
  track(`lomake_${step + 1}`);
}

prevBtn.addEventListener('click', () => show(current - 1));

form.addEventListener('submit', async (event) => {
  event.preventDefault();
  clearErrors(form);
  alertBox.hidden = true;
  const values = { ...formValues(form), photoIds: uploader.ids() };
  const errors = localErrors(STEP_FIELDS[current], values);
  if (Object.keys(errors).length) return showErrors(form, errors);
  if (current < steps.length - 1) return show(current + 1);

  nextBtn.disabled = true;
  try {
    const { status, data } = await postJson('/api/requests', values);
    if (status === 201) {
      form.hidden = true;
      document.getElementById('sent-email').textContent = data.email;
      if (data.devLink) {
        document.getElementById('dev-link-a').href = data.devLink;
        document.getElementById('dev-link').hidden = false;
      }
      document.getElementById('sent').hidden = false;
      window.scrollTo({ top: 0 });
      return;
    }
    if (data.errors) {
      const firstStep = STEP_FIELDS.findIndex((fields) => fields.some((f) => f in data.errors));
      if (firstStep >= 0) show(firstStep);
      showErrors(form, data.errors);
    } else {
      alertBox.textContent = data.error ?? 'Lähetys epäonnistui. Yritä uudelleen.';
      alertBox.hidden = false;
    }
  } catch {
    alertBox.textContent = 'Yhteysvirhe. Tarkista verkkoyhteys ja yritä uudelleen.';
    alertBox.hidden = false;
  } finally {
    nextBtn.disabled = false;
  }
});

clearErrorOnInput(form);
syncTypeFields();
show(0);
