// Etusivun vuorovaikutus: pikalomake, palkkiolaskuri, animaatiot.

import { track } from '/forms.js';

track('etusivu');

const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

// --- Header vaihtuu vaaleaksi, kun hero on ohitettu ---------------------------
const header = document.getElementById('site-header');
const hero = document.getElementById('hero');
const stickyCta = document.getElementById('sticky-cta');
const finalCta = document.getElementById('aloita');
let heroVisible = true;
let finalVisible = false;
const updateSticky = () => stickyCta.classList.toggle('show', !heroVisible && !finalVisible);
const onScroll = () => {
  const heroBottom = hero.getBoundingClientRect().bottom;
  header.classList.toggle('scrolled', heroBottom < header.offsetHeight);
  heroVisible = heroBottom > window.innerHeight * 0.25;
  updateSticky();
};
window.addEventListener('scroll', onScroll, { passive: true });
onScroll();
new IntersectionObserver(([e]) => {
  finalVisible = e.isIntersecting;
  updateSticky();
}).observe(finalCta);

// --- Pikalomake: asunnon tyyppi ja postinumero viedään lomakkeelle ------------
const quick = document.getElementById('quickstart');
quick.addEventListener('submit', (e) => {
  e.preventDefault();
  const propertyType = quick.querySelector('input[name=propertyType]:checked')?.value ?? 'kerrostalo';
  const postal = quick.postalCode.value.replace(/\s/g, '');
  const note = document.getElementById('qs-note');
  if (postal && !/^\d{5}$/.test(postal)) {
    note.textContent = 'Postinumerossa on viisi numeroa, esim. 00100.';
    note.classList.add('error');
    quick.postalCode.focus();
    return;
  }
  try {
    sessionStorage.setItem('vv-quickstart', JSON.stringify({ propertyType, postalCode: postal }));
  } catch {}
  location.href = '/kilpailuta';
});

// --- Palkkiolaskuri ---------------------------------------------------------------
const PERCENTS = [2, 2.5, 3, 3.5, 4];
const price = document.getElementById('calc-price');
const min = document.getElementById('calc-min');
const results = document.getElementById('calc-results');
const eur = (n) => new Intl.NumberFormat('fi-FI', { maximumFractionDigits: 0 }).format(n);
const pct = (n) => new Intl.NumberFormat('fi-FI', { minimumFractionDigits: 1 }).format(n);

export function commissions(salePrice, minimum) {
  return PERCENTS.map((p) => ({ percent: p, eur: Math.round(Math.max((salePrice * p) / 100, minimum)) }));
}

function renderCalc() {
  document.getElementById('calc-price-out').textContent = `${eur(Number(price.value))} €`;
  document.getElementById('calc-min-out').textContent = `${eur(Number(min.value))} €`;
  const rows = commissions(Number(price.value), Number(min.value));
  const cheapest = rows[0].eur;
  results.replaceChildren(
    ...rows.map((r) => {
      const card = document.createElement('div');
      card.className = `result${r.eur === cheapest ? ' best' : ''}`;
      const h3 = document.createElement('h3');
      h3.textContent = `${pct(r.percent)} % palkkio`;
      const save = document.createElement('div');
      save.className = 'save';
      save.textContent = `${eur(r.eur)} €`;
      const meta = document.createElement('div');
      meta.className = 'meta';
      meta.textContent = r.eur === Math.round(Number(min.value)) && Number(min.value) > 0 ? 'vähimmäispalkkio' : 'sis. alv';
      const right = document.createElement('div');
      right.className = 'meta-right';
      right.textContent = r.eur > cheapest ? `+${eur(r.eur - cheapest)} €` : 'edullisin';
      card.append(h3, save, meta, right);
      return card;
    }),
  );
}
for (const input of [price, min]) input.addEventListener('input', renderCalc);
renderCalc();

// --- Vieritettäessä paljastuvat osat -------------------------------------------
// Näkyvissä jo latautuessa olevat jätetään rauhaan, jotta sivu on valmis heti.
if (!reduceMotion && 'IntersectionObserver' in window) {
  const io = new IntersectionObserver(
    (entries) => {
      for (const e of entries) {
        if (!e.isIntersecting) continue;
        e.target.classList.add('in');
        io.unobserve(e.target);
      }
    },
    { rootMargin: '0px 0px -8% 0px' },
  );
  const groups = new Map();
  for (const el of document.querySelectorAll('.reveal')) {
    if (el.getBoundingClientRect().top < window.innerHeight) {
      el.classList.remove('reveal');
      continue;
    }
    // Saman rivin sisarukset porrastetaan.
    const i = groups.get(el.parentElement) ?? 0;
    groups.set(el.parentElement, i + 1);
    el.style.setProperty('--delay', `${Math.min(i, 5) * 0.08}s`);
    io.observe(el);
  }
} else {
  document.querySelectorAll('.reveal').forEach((el) => el.classList.remove('reveal'));
}

// --- Aikajana ja suljettujen arvioiden demo -----------------------------------
const steps = [...document.querySelectorAll('#timeline li')];
const demo = document.getElementById('bid-demo');
const clock = document.getElementById('bid-clock');
const rows = [...demo.querySelectorAll('.bid-row')];
let timers = [];

function playDemo() {
  timers.forEach(clearTimeout);
  timers = [];
  if (reduceMotion) return;
  const at = (ms, fn) => timers.push(setTimeout(fn, ms));
  rows.forEach((r) => r.classList.add('sealed'));
  steps.forEach((s) => s.classList.remove('active'));
  at(0, () => steps[0]?.classList.add('active'));
  at(500, () => steps[1]?.classList.add('active'));
  // Määräaika laskee nollaan, sitten arviot avautuvat edullisimmasta alkaen.
  for (let s = 3; s >= 0; s--) {
    at(600 + (3 - s) * 700, () => (clock.textContent = s ? `Määräaika päättyy 00:0${s}` : 'Määräaika päättyi'));
  }
  at(3100, () => steps[2]?.classList.add('active'));
  rows
    .slice()
    .sort((a, b) => a.dataset.order - b.dataset.order)
    .forEach((r, i) => at(3300 + i * 350, () => r.classList.remove('sealed')));
  at(4600, () => {
    clock.textContent = 'Arviot auki';
    steps[3]?.classList.add('active');
  });
}

new IntersectionObserver(
  ([e], obs) => {
    if (!e.isIntersecting) return;
    playDemo();
    obs.disconnect();
  },
  { threshold: 0.5 },
).observe(demo);
document.getElementById('bid-replay').addEventListener('click', playDemo);
