// Myyjän omat kohteet. Kirjautuminen sähköpostiin lähetetyllä kertakäyttöisellä linkillä.
import { api, el, nodes, fmtDate, timeLeft } from '/forms.js';

const app = document.getElementById('app');
const form = document.getElementById('login');
const result = document.getElementById('login-result');

const STATUS = {
  vahvistamaton: ['wait', 'Odottaa vahvistusta'],
  avoin: ['wait', 'Arvioaika käynnissä'],
  valittu: ['ok', 'Välittäjä valittu'],
  valmis: ['ok', 'Valmis'],
  peruttu: ['no', 'Peruttu'],
};

async function show() {
  const { status, data } = await api('GET', '/api/consumer/requests');
  if (status !== 200) {
    form.hidden = false;
    app.hidden = true;
    return;
  }
  form.hidden = true;
  app.hidden = false;
  app.replaceChildren(
    ...nodes(
      el('div', { class: 'page-head' }, el('h1', {}, 'Omat myyntikohteet'), el('button', { type: 'button', class: 'btn btn-ghost btn-sm', onclick: logout }, 'Kirjaudu ulos')),
      el('p', { class: 'hint' }, data.email),
      data.requests.length
        ? data.requests.map((r) => {
            const [tone, label] = r.status === 'avoin' && !r.open ? ['ok', 'Arviot auki'] : STATUS[r.status] ?? ['', r.status];
            return el(
              'a',
              { class: 'card request-link', href: `/kohde#${r.token}` },
              el('div', { class: 'offer-top' },
                el('div', {}, el('p', { class: 'eyebrow' }, `${r.streetAddress} · jätetty ${fmtDate(r.createdAt)}`), el('h3', {}, r.title)),
                el('span', { class: `pill ${tone}` }, label),
              ),
              el('p', { class: 'hint' },
                r.open ? `Arvioita ${r.bidCount} · aikaa ${timeLeft(r.deadline)}` : `Arvioita ${r.bidCount}`),
            );
          })
        : el('p', {}, 'Ei myyntikohteita.'),
    ),
  );
}

async function logout() {
  await api('POST', '/api/logout', {});
  show();
}

form.addEventListener('submit', async (e) => {
  e.preventDefault();
  const button = form.querySelector('button');
  button.disabled = true;
  const { data } = await api('POST', '/api/consumer/login', { email: form.email.value });
  button.disabled = false;
  result.replaceChildren(
    el('div', { class: 'notice' },
      el('strong', {}, 'Tarkista sähköpostisi.'),
      el('p', {}, 'Jos osoitteella on myyntikohteita, lähetimme siihen kirjautumislinkin.'),
      data.devLink ? el('p', {}, 'Kehitystila: ', el('a', { href: data.devLink }, 'avaa kirjautumislinkki')) : null,
    ),
  );
});

// Kirjautumislinkki: #tunnus vaihdetaan istunnoksi ja poistetaan osoitteesta.
const token = location.hash.slice(1);
if (/^[a-f0-9]{32,}$/.test(token)) {
  const { status, data } = await api('POST', '/api/consumer/session', { token });
  history.replaceState(null, '', location.pathname);
  if (status !== 200) result.replaceChildren(el('div', { class: 'form-alert' }, data.error));
}
window.addEventListener('hashchange', () => location.reload());
show();
