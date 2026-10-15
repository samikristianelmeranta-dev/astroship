// Myyjän näkymä omaan myyntikohteeseen. Tunnus on osoitteen #-osassa.
import { photoGallery, photoSrc, uploaderBlock } from '/photos.js';
import {
  loadCatalog, api, el, nodes, fmtEur, fmtDate, timeLeft, projectFacts, commissionText, estimateText,
} from '/forms.js';

const app = document.getElementById('app');
const token = location.hash.slice(1);
const catalog = await loadCatalog();
let justVerified = false;
// Valinta: yksi tai kaksi välittäjää. Rastit pidetään selaimessa, kunnes myyjä vahvistaa.
const selected = new Set();
let confirmingChoice = false;
let data = null;
let confirmCancel = false;
// Keskeneräinen syöttö (vastaukset, kuvat) säilyy, kun sivu päivittyy taustalla.
let busy = false;
let flash = null; // { tone: 'ok' | 'error', text }

const STATUS_TEXT = {
  vahvistamaton: 'Odottaa vahvistusta',
  avoin: 'Arvioaika käynnissä',
  valittu: 'Välittäjä valittu',
  valmis: 'Valmis',
  peruttu: 'Peruttu',
};

async function post(path, body = {}) {
  const res = await api('POST', `/api/projects/${token}${path}`, body);
  flash = res.status < 300 ? null : { tone: 'error', text: res.data.error ?? Object.values(res.data.errors ?? {})[0] ?? 'Toiminto epäonnistui.' };
  return res;
}

function linkBox() {
  const input = el('input', { type: 'text', readonly: true, value: location.href, id: 'project-link', 'aria-label': 'Kohteen linkki' });
  const button = el('button', { type: 'button', class: 'btn btn-sm' }, 'Kopioi');
  button.addEventListener('click', async () => {
    try {
      await navigator.clipboard.writeText(input.value);
      button.textContent = 'Kopioitu';
    } catch {
      input.select();
    }
  });
  return el(
    'div',
    { class: 'notice' },
    el('strong', {}, 'Kohteesi on nyt julkaistu alueen välittäjille.'),
    el('p', {}, 'Ilmoitamme sähköpostilla, kun välittäjät kysyvät jotain ja kun arviot avautuvat. Pääset tänne myös Omat kohteet -sivulta.'),
    el('div', { class: 'copy-row' }, input, button),
  );
}

function stars(rating) {
  return el('span', { class: 'stars', 'aria-label': `${rating} / 5 tähteä` }, '★'.repeat(Math.round(rating)) + '☆'.repeat(5 - Math.round(rating)));
}

function logo(id, company) {
  if (!id) return el('span', { class: 'logo-fallback', 'aria-hidden': 'true' }, (company ?? '?').slice(0, 1));
  const img = el('img', { class: 'company-logo', alt: '' });
  photoSrc(id).then((src) => src && (img.src = src));
  return img;
}

// Vertailutaulukko arvioista rinnakkain. Kulut lasketaan samalla viitehinnalla.
function comparison(offers, reference) {
  const rows = [
    ['Hinta-arvio (velaton)', (o) => estimateText(o)],
    ['Pyyntöhintaehdotus', (o) => (o.askingPrice ? fmtEur(o.askingPrice) : '–')],
    ['Välityspalkkio (sis. alv)', (o) => commissionText(o)],
    ['Lisäkulut', (o) => (o.extraCostsEur ? fmtEur(o.extraCostsEur) : 'Ei lisäkuluja')],
    [`Kulut yhteensä, kun kauppahinta ${fmtEur(reference)}`, (o) => el('strong', {}, fmtEur(o.costAtReference))],
    [`Sinulle jää ${fmtEur(reference)} kaupasta`, (o) => fmtEur(o.netAtReference)],
    ['Toimeksiannon kesto', (o) => `${o.contractMonths} kk`],
    ['Ei kauppaa, ei kuluja', (o) => (o.noSaleNoFee ? '✓' : '–')],
    ['Arvioitu myyntiaika', (o) => o.saleTimeEstimate],
    ...Object.entries(catalog.bidIncludes).map(([key, label]) => [label, (o) => (o.includes?.includes(key) ? '✓' : '–')]),
    ['Arviot', (o) => (o.rating ? [stars(o.rating.avg), ` ${String(o.rating.avg).replace('.', ',')} (${o.rating.count})`] : 'Ei vielä arvioita')],
  ];
  const cheapest = Math.min(...offers.map((o) => o.costAtReference));
  return el(
    'div',
    { class: 'table-wrap compare-offers' },
    el(
      'table',
      {},
      el('thead', {}, el('tr', {}, el('th', { scope: 'col' }, ''), offers.map((o) => el('th', { scope: 'col', class: o.costAtReference === cheapest ? 'best' : null }, o.company)))),
      el('tbody', {}, rows.map(([label, fn]) => el('tr', {}, el('th', { scope: 'row' }, label), offers.map((o) => el('td', {}, fn(o)))))),
    ),
  );
}

function offerCard(offer, { project, winners, cheapestId, highestId, maxChosen, reference }) {
  const chosen = project.acceptedBidIds.includes(offer.id);
  const decided = ['valittu', 'valmis'].includes(project.status);
  const picking = !decided && project.status === 'avoin';
  const isSelected = selected.has(offer.id);
  const card = el(
    'article',
    { class: `card offer${chosen || isSelected ? ' chosen' : ''}${decided && !chosen ? ' dim' : ''}` },
    el(
      'div',
      { class: 'offer-top' },
      el('div', { class: 'company' }, logo(offer.logoPhotoId, offer.company),
        el('div', {}, el('h3', {}, offer.company),
          el('div', { class: 'hint' }, [offer.agentName, offer.chain].filter(Boolean).join(' · ')),
          offer.rating ? el('div', { class: 'hint' }, stars(offer.rating.avg), ` ${String(offer.rating.avg).replace('.', ',')} (${offer.rating.count} arviota)`) : null)),
      el('div', { class: 'pills' },
        chosen ? el('span', { class: 'pill ok' }, 'Valittu') : null,
        !chosen && offer.id === cheapestId ? el('span', { class: 'pill' }, 'Edullisin palkkio') : null,
        !chosen && offer.id === highestId ? el('span', { class: 'pill' }, 'Korkein arvio') : null,
        offer.verified ? el('span', { class: 'pill ok', title: 'Y-tunnus ja välitysliikerekisteri tarkistettu' }, 'Tarkistettu') : null,
      ),
    ),
    el('p', { class: 'eyebrow' }, 'Hinta-arvio, velaton'),
    el('div', { class: 'estimate' }, estimateText(offer)),
    offer.askingPrice ? el('p', { class: 'hint' }, `Ehdotettu pyyntöhinta ${fmtEur(offer.askingPrice)}`) : null,
    el('div', { class: 'offer-figures' },
      el('div', {}, el('span', {}, 'Välityspalkkio'), el('strong', {}, commissionText(offer))),
      el('div', {}, el('span', {}, `Kulut yhteensä ${fmtEur(reference)} kaupasta`), el('strong', {}, fmtEur(offer.costAtReference))),
      el('div', {}, el('span', {}, 'Toimeksianto'), el('strong', {}, `${offer.contractMonths} kk${offer.noSaleNoFee ? ', ei kauppaa ei kuluja' : ''}`)),
      el('div', {}, el('span', {}, 'Myyntiaika-arvio'), el('strong', {}, offer.saleTimeEstimate)),
    ),
    offer.description ? el('p', {}, offer.description) : null,
    offer.companyDescription ? el('p', { class: 'hint' }, offer.companyDescription) : null,
  );

  const winner = winners.find((w) => w.bidId === offer.id);
  if (chosen && winner) {
    card.append(
      el('div', { class: 'notice' },
        el('strong', {}, 'Välittäjän yhteystiedot'),
        el('p', {}, [`${winner.contactName}, ${winner.company}`, winner.phone, winner.email, winner.website].filter(Boolean).join('\n')),
        el('p', {}, 'Olemme luovuttaneet yhteystietosi välittäjälle. Hän ottaa sinuun yhteyttä ja sopii arviokäynnin.'),
      ),
    );
  }

  if (picking) {
    const full = !isSelected && selected.size >= maxChosen;
    card.append(
      el('div', { class: 'pick-row' },
        el('button', {
          type: 'button',
          class: isSelected ? 'btn pick on' : 'btn btn-ghost pick',
          'aria-pressed': String(isSelected),
          disabled: full || confirmingChoice,
          onclick: () => {
            if (isSelected) selected.delete(offer.id);
            else selected.add(offer.id);
            draw();
          },
        }, isSelected ? '✓ Valittu' : 'Valitse'),
        full ? el('span', { class: 'hint' }, `Voit valita enintään ${maxChosen}.`) : null,
      ),
    );
  }
  return card;
}

// Kiinteä palkki valinnan vahvistamiseen.
function choiceBar({ offers, maxChosen }) {
  if (!selected.size) return null;
  const names = offers.filter((o) => selected.has(o.id)).map((o) => o.company);
  const bar = el('div', { class: 'choice-bar', role: 'region', 'aria-label': 'Valinnan vahvistus' });
  if (!confirmingChoice) {
    bar.append(
      el('div', {}, el('strong', {}, `Valittu ${selected.size}/${maxChosen}: `), names.join(' ja '),
        selected.size < maxChosen && offers.length > 1 ? el('div', { class: 'hint' }, 'Voit valita myös toisen välittäjän ja tavata molemmat arviokäynnillä.') : null),
      el('button', { type: 'button', class: 'btn', onclick: () => ((confirmingChoice = true), draw()) }, 'Jatka'),
    );
  } else {
    bar.append(
      el('div', {}, selected.size > 1
        ? `Yhteystietosi ja osoitteesi luovutetaan molemmille: ${names.join(' ja ')}. Muut välittäjät saavat tiedon, ettei heitä valittu. Valintaa ei voi muuttaa.`
        : `Yhteystietosi ja osoitteesi luovutetaan välittäjälle ${names[0]}. Muut välittäjät saavat tiedon, ettei heitä valittu. Valintaa ei voi muuttaa.`),
      el('div', { class: 'choice-actions' },
        el('button', { type: 'button', class: 'link-btn', onclick: () => ((confirmingChoice = false), draw()) }, 'Takaisin'),
        el('button', { type: 'button', class: 'btn', onclick: accept }, 'Vahvista valinta')),
    );
  }
  return bar;
}

function statusBox({ project, open, bidCount, offers }) {
  if (project.status === 'vahvistamaton') {
    return el('div', { class: 'notice warn' }, el('strong', {}, 'Kohdetta ei ole vielä vahvistettu.'), el('p', {}, 'Avaa sähköpostiisi lähetetty linkki.'));
  }
  if (project.status === 'peruttu') {
    return el('div', { class: 'notice warn' }, el('strong', {}, 'Peruit tämän pyynnön.'), el('p', {}, 'Voit jättää uuden pyynnön milloin tahansa.'));
  }
  if (open) {
    return el(
      'div',
      { class: 'card status-box' },
      el('span', { class: 'label' }, 'Arvioaikaa jäljellä'),
      el('span', { class: 'big' }, timeLeft(project.deadline)),
      el('span', { class: 'label' }, `Arvioita saapunut: ${bidCount}`),
      el('p', { class: 'hint' }, `Arviot avautuvat sinulle ${fmtDate(project.deadline)}. Välittäjät eivät näe toistensa arvioita eivätkä palkkioita.`),
    );
  }
  if (!offers.length) {
    return el('div', { class: 'notice warn' }, el('strong', {}, 'Arvioaika päättyi, eikä arvioita saapunut.'), el('p', {}, 'Voit jättää uuden pyynnön pidemmällä määräajalla.'));
  }
  if (project.status === 'valmis') return el('div', { class: 'notice' }, el('strong', {}, 'Kiitos!'), el('p', {}, 'Kohde on merkitty valmiiksi. Onnea asuntokauppaan.'));
  if (project.status === 'valittu') {
    return project.acceptedBidIds.length > 1
      ? el('div', { class: 'notice' }, el('strong', {}, 'Valitsit kaksi välittäjää.'), el('p', {}, 'Molemmat ottavat sinuun yhteyttä ja sopivat arviokäynnin. Tee toimeksiantosopimus sen kanssa, joka vakuuttaa sinut. Kerro lopuksi, kumpi hoiti myynnin, ja arvioi hänet.'))
      : el('div', { class: 'notice' }, el('strong', {}, 'Olet valinnut välittäjän.'), el('p', {}, 'Välittäjä ottaa sinuun yhteyttä ja sopii arviokäynnin. Kun myynti on hoidettu, arvioi välittäjä.'));
  }
  return el(
    'div',
    { class: 'notice' },
    el('strong', {}, `Sait ${offers.length} ${offers.length === 1 ? 'arvion' : 'arviota'}.`),
    el('p', {}, offers.length > 1
      ? 'Vertaile hinta-arvioita ja palkkioita ja valitse yksi tai kaksi välittäjää arviokäynnille. Voit myös jättää kaikki valitsematta.'
      : 'Valitse välittäjä, jos tarjous sopii sinulle. Voit myös jättää sen valitsematta.'),
  );
}

function answerForm(q) {
  const input = el('textarea', { id: `answer-${q.id}`, rows: 2, maxlength: 1000, placeholder: q.wantsPhotos ? 'Kirjoita halutessasi saate kuville…' : 'Kirjoita vastaus…', 'aria-label': 'Vastaus' });
  const photos = q.wantsPhotos ? uploaderBlock({ id: `answer-photos-${q.id}`, text: 'Lisää pyydetyt kuvat' }) : null;
  const button = el('button', { type: 'button', class: 'btn btn-sm' }, 'Vastaa');
  const status = el('span', { class: 'error', role: 'status' });
  const wrap = el('div', { class: 'stack' }, photos?.node, el('div', { class: 'answer-row' }, input, button), status);
  wrap.addEventListener('input', () => (busy = true));
  wrap.addEventListener('change', () => (busy = true));
  // Virhe näytetään paikallisesti, jotta kirjoitettu vastaus ja ladatut kuvat säilyvät.
  button.addEventListener('click', async () => {
    if (photos?.uploader.pending()) return (status.textContent = 'Odota, kuvat latautuvat vielä.');
    button.disabled = true;
    const res = await api('POST', `/api/projects/${token}/questions/${q.id}/answer`, { answer: input.value, photoIds: photos?.uploader.ids() ?? [] });
    button.disabled = false;
    if (res.status !== 200) return (status.textContent = res.data.error ?? Object.values(res.data.errors ?? {})[0] ?? 'Lähetys epäonnistui.');
    flash = { tone: 'ok', text: 'Vastaus lähetetty välittäjille.' };
    busy = false;
    render();
  });
  return wrap;
}

function questionsSection({ project, questions, open }) {
  if (!questions.length) {
    return open
      ? el('div', { class: 'card' }, el('p', { class: 'eyebrow' }, 'Välittäjien kysymykset'), el('p', { class: 'hint' }, 'Välittäjät voivat kysyä asunnosta lisätietoja tai pyytää lisää kuvia. Ilmoitamme sähköpostilla, kun kysymyksiä tulee.'))
      : null;
  }
  return el(
    'div',
    { class: 'card stack' },
    el('p', { class: 'eyebrow' }, `Välittäjien kysymykset (${questions.length})`),
    el('p', { class: 'hint' }, 'Vastauksesi ja lisäämäsi kuvat näkyvät kaikille kohteen välittäjille, jotta kilpailu pysyy reiluna. Älä kirjoita yhteystietoja.'),
    questions.map((q) => {
      const item = el('div', { class: 'qa' },
        el('p', { class: 'q' }, q.wantsPhotos ? [el('span', { class: 'pill ask' }, 'Kuvapyyntö'), ' '] : null, q.text));
      if (q.answer) {
        item.append(el('p', { class: 'a' }, q.answer));
        if (q.answerPhotoIds.length) item.append(el('p', { class: 'hint' }, `Lisäsit ${q.answerPhotoIds.length} ${q.answerPhotoIds.length === 1 ? 'kuvan' : 'kuvaa'}.`));
      }
      if (open && project.status === 'avoin' && (!q.answer || q.wantsPhotos)) item.append(answerForm(q));
      else if (!q.answer) item.append(el('p', { class: 'hint' }, 'Ei vastausta.'));
      return item;
    }),
  );
}

// Myyjä voi lisätä kuvia arvioaikana myös ilman pyyntöä.
function addPhotosSection({ project, open }) {
  if (!open || project.status !== 'avoin') return null;
  const block = uploaderBlock({ id: 'more-photos', text: 'Lisää kuvia kohteeseen' });
  block.node.addEventListener('change', () => (busy = true));
  const button = el('button', { type: 'button', class: 'btn btn-sm' }, 'Tallenna kuvat');
  const status = el('span', { class: 'error', role: 'status' });
  button.addEventListener('click', async () => {
    if (block.uploader.pending()) return (status.textContent = 'Odota, kuvat latautuvat vielä.');
    const ids = block.uploader.ids();
    if (!ids.length) return (status.textContent = 'Valitse ensin kuvat.');
    button.disabled = true;
    const res = await api('POST', `/api/projects/${token}/photos`, { photoIds: ids });
    button.disabled = false;
    if (res.status !== 201) return (status.textContent = res.data.error ?? Object.values(res.data.errors ?? {})[0] ?? 'Tallennus epäonnistui.');
    flash = { tone: 'ok', text: 'Kuvat lisätty. Välittäjät näkevät ne heti.' };
    busy = false;
    render();
  });
  return el('details', { class: 'card' }, el('summary', {}, 'Lisää kuvia'), el('p', { class: 'hint' }, 'Esimerkiksi piha, kylpyhuone tai taloyhtiön yhteiset tilat. Uudet kuvat näkyvät kaikille kohteen välittäjille.'), block.node, el('div', { class: 'form-actions' }, status, button));
}

function reviewSection({ project, review, offers }) {
  if (!['valittu', 'valmis'].includes(project.status)) return null;
  const chosenOffers = offers.filter((o) => project.acceptedBidIds.includes(o.id));
  if (review) {
    const company = offers.find((o) => o.id === review.bidId)?.company;
    return el('div', { class: 'card' }, el('p', { class: 'eyebrow' }, company ? `Arviosi: ${company}` : 'Arviosi'), el('p', {}, stars(review.rating)), review.text ? el('p', {}, review.text) : null);
  }
  // Kahdesta valitusta kysytään, kumpi hoiti myynnin.
  let reviewBidId = chosenOffers.length === 1 ? chosenOffers[0].id : null;
  const whoDidIt = chosenOffers.length > 1
    ? el('div', { class: 'field', 'data-field': 'bidId' },
        el('span', { class: 'label' }, 'Kumpi välittäjä hoiti myynnin?'),
        el('div', { class: 'choice-grid' }, chosenOffers.map((o) =>
          el('div', { class: 'choice' },
            el('input', { type: 'radio', name: 'reviewBid', id: `rb-${o.id}`, value: o.id, onchange: () => (reviewBidId = o.id) }),
            el('label', { for: `rb-${o.id}` }, o.company)))))
    : null;
  const company = chosenOffers.length === 1 ? chosenOffers[0].company : 'välittäjä';
  let rating = 0;
  const starButtons = [1, 2, 3, 4, 5].map((n) =>
    el('button', { type: 'button', class: 'star-btn', 'aria-label': `${n} tähteä`, onclick: () => {
      rating = n;
      starButtons.forEach((b, i) => b.classList.toggle('on', i < n));
    } }, '★'),
  );
  const text = el('textarea', { id: 'review-text', rows: 3, maxlength: 1000, placeholder: 'Miten yhteistyö sujui? (valinnainen)' });
  return el(
    'div',
    { class: 'card stack' },
    el('p', { class: 'eyebrow' }, 'Miten myynti sujui?'),
    el('p', {}, chosenOffers.length > 1 ? 'Kerro, kumpi välittäjä hoiti myynnin, ja arvioi hänet. Arviosi näkyy muille myyjille.' : `Arvioi ${company}. Arviosi näkyy muille myyjille.`),
    whoDidIt,
    el('div', { class: 'star-row' }, starButtons),
    text,
    el('div', { class: 'form-actions' },
      project.status === 'valittu'
        ? el('button', { type: 'button', class: 'btn btn-ghost btn-sm', onclick: async () => ((await post('/complete')).status === 200 && (flash = { tone: 'ok', text: 'Merkitty valmiiksi.' }), render()) }, 'Merkitse valmiiksi ilman arviota')
        : el('span'),
      el('button', { type: 'button', class: 'btn', onclick: async () => {
        const res = await post('/review', { rating, text: text.value, bidId: reviewBidId });
        if (res.status === 201) flash = { tone: 'ok', text: 'Kiitos arviosta!' };
        render();
      } }, 'Lähetä arvio'),
    ),
  );
}

function cancelSection({ project }) {
  if (!['avoin', 'vahvistamaton'].includes(project.status)) return null;
  if (!confirmCancel) return el('p', { class: 'hint' }, el('button', { type: 'button', class: 'link-btn', onclick: () => ((confirmCancel = true), draw()) }, 'Peru pyyntö'));
  return el('div', { class: 'confirm' },
    el('span', {}, 'Perutaanko pyyntö? Välittäjät eivät voi enää jättää arviota, eikä tätä voi kumota.'),
    el('button', { type: 'button', class: 'btn btn-sm', onclick: async () => ((await post('/cancel')), (confirmCancel = false), render()) }, 'Peru pyyntö'),
    el('button', { type: 'button', class: 'link-btn', onclick: () => ((confirmCancel = false), draw()) }, 'Älä peru'),
  );
}

async function accept() {
  const res = await post('/accept', { bidIds: [...selected] });
  confirmingChoice = false;
  if (res.status === 200) {
    selected.clear();
    flash = { tone: 'ok', text: 'Valinta tehty. Lähetimme vahvistuksen ja välittäjien yhteystiedot sähköpostiisi.' };
  }
  render();
}

async function render() {
  if (!/^[a-f0-9]{32,}$/.test(token)) {
    app.replaceChildren(el('div', { class: 'notice warn' }, el('strong', {}, 'Linkki on puutteellinen.'), el('p', {}, 'Avaa kohteesi koko linkillä tai kirjaudu Omat kohteet -sivulla.')));
    return;
  }
  const res = await api('GET', `/api/projects/${token}`);
  if (res.status !== 200) {
    app.replaceChildren(el('div', { class: 'notice warn' }, el('strong', {}, 'Kohdetta ei löytynyt.'), el('p', {}, 'Tarkista, että linkki on kokonainen. Vahvistamattomat pyynnöt poistetaan 3 vuorokauden kuluttua.')));
    return;
  }
  data = res.data;
  draw();
}

// Piirtää sivun viimeksi haetuista tiedoista (valinnan rastit eivät hae uudelleen).
let drawn = false;
function draw() {
  // Sisääntuloanimaatiot vain ensimmäisellä piirrolla, ei jokaisella valinnan muutoksella.
  app.classList.toggle('settled', drawn);
  drawn = true;
  const { project, offers, winners, maxChosen, referencePrice } = data;
  const cheapestId = offers[0]?.id;
  const highestId = offers.length > 1 ? offers.reduce((a, b) => (b.estimateHigh > a.estimateHigh ? b : a)).id : null;
  const title = `${catalog.propertyTypes[project.propertyType] ?? 'Asunto'}, ${String(project.area).replace('.', ',')} m²`;
  app.replaceChildren(
    ...nodes(
      el('div', { class: 'page-head' },
        el('h1', {}, title),
        el('span', { class: `pill ${project.status === 'avoin' ? 'wait' : project.status === 'peruttu' ? 'no' : 'ok'}` }, STATUS_TEXT[project.status] ?? project.status),
      ),
      el('p', { class: 'hint' }, [project.streetAddress, project.city].filter(Boolean).join(', ')),
      flash ? el('div', { class: flash.tone === 'ok' ? 'notice' : 'form-alert', role: 'status' }, flash.text) : null,
      justVerified ? linkBox() : null,
      statusBox(data),
      offers.length > 1 ? comparison(offers, referencePrice) : null,
      offers.length
        ? el('p', { class: 'hint' }, `Kulut on laskettu kaikille samalla kauppahinnalla ${fmtEur(referencePrice)}, joka on välittäjien arvioiden mediaani. Näin palkkiot ovat vertailukelpoisia, vaikka arviot eroavat. Korkein arvio ei tarkoita korkeinta kauppahintaa: kysy perustelut arviokäynnillä.`)
        : null,
      offers.map((o) => offerCard(o, { project, winners, cheapestId, highestId, maxChosen, reference: referencePrice })),
      reviewSection(data),
      questionsSection(data),
      addPhotosSection(data),
      el('div', { class: 'card' }, el('p', { class: 'eyebrow' }, 'Kohteesi tiedot'), projectFacts(project, catalog), photoGallery(project.photoIds, { token })),
      cancelSection(data),
      choiceBar(data),
    ),
  );
}

// Ensimmäinen avaus sähköpostin linkistä julkaisee kohteen.
if (/^[a-f0-9]{32,}$/.test(token)) {
  const { data: verify } = await api('POST', `/api/projects/${token}/verify`, {});
  justVerified = Boolean(verify?.verified);
}
render();
// Päivitä laskuri ja arvioiden määrä minuutin välein, jos sivulla ei ole keskeneräistä syöttöä.
setInterval(() => selected.size || confirmCancel || busy || render(), 60_000);
// Tunnus on osoitteen #-osassa; toisen kohteen linkki ei lataa sivua uudelleen itsestään.
window.addEventListener('hashchange', () => location.reload());
