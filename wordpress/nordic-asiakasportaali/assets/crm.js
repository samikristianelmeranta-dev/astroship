/* Nordic CRM – henkilökunnan sovellus. Tiedot haetaan /wp-json/nap/v1/ -rajapinnasta. */
(function () {
  'use strict';
  var C = window.NAP || {};
  var main = document.getElementById('main');
  var S = { me: null, users: [], stages: [], today: '', board: [], tasks: [], customers: [], taskFilter: 'omat', q: '', src: '' };

  function esc(s) { return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) { return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]; }); }
  function eur(n) { return Math.round(+n || 0).toLocaleString('fi-FI') + ' €'; }
  function fdate(d) { if (!d) return ''; var p = String(d).slice(0, 10).split('-'); return (+p[2]) + '.' + (+p[1]) + '.' + (p[0] !== S.today.slice(0, 4) ? p[0] : ''); }
  function ftime(dt) { if (!dt) return ''; return fdate(dt) + ' klo ' + String(dt).slice(11, 16).replace(':', '.'); }
  function user(id) { for (var i = 0; i < S.users.length; i++) if (S.users[i].id === id) return S.users[i]; return null; }
  function uname(id) { var u = user(id); return u ? u.name : ''; }
  function isOwner() { return S.me && S.me.role === 'osakas'; }
  function toast(m, err) { var t = document.getElementById('toast'); t.textContent = m; t.className = 'toast on' + (err ? ' err' : ''); clearTimeout(t._h); t._h = setTimeout(function () { t.className = 'toast'; }, 2800); }

  function api(path, opts) {
    opts = opts || {};
    var init = { method: opts.method || 'GET', credentials: 'same-origin', headers: { 'X-WP-Nonce': C.nonce } };
    if (opts.body) { init.headers['Content-Type'] = 'application/json'; init.body = JSON.stringify(opts.body); }
    return fetch(C.root + path, init).then(function (r) {
      return r.json().catch(function () { return {}; }).then(function (j) {
        if (!r.ok) { var e = new Error((j && j.message) || 'Toiminto epäonnistui (' + r.status + ').'); throw e; }
        return j;
      });
    });
  }
  function fail(e) { toast(e.message || 'Toiminto epäonnistui.', true); }

  /* ---------- Navigaatio ---------- */
  var VIEWS = [
    ['paiva', 'Oma päivä', 'M3 12l9-8 9 8M5 10v10h14V10'],
    ['putki', 'Myyntiputki', 'M4 5h4v14H4zM10 5h4v9h-4zM16 5h4v6h-4z'],
    ['asiakkaat', 'Asiakkaat', 'M16 11a4 4 0 10-8 0 4 4 0 008 0zM4 21c1-4 4-6 8-6s7 2 8 6'],
    ['tehtavat', 'Tehtävät', 'M4 6l2 2 4-4M4 14l2 2 4-4M13 6h7M13 14h7'],
    ['uusi', 'Uusi tarjouspyyntö', 'M12 5v14M5 12h14']
  ];
  function route() { var h = (location.hash || '#paiva').slice(1); var m = /^asiakas-(\d+)$/.exec(h); if (m) return { v: 'asiakkaat', id: +m[1] }; m = /^uusi-(\d+)$/.exec(h); return m ? { v: 'uusi', id: +m[1] } : { v: h || 'paiva' }; }
  function drawNav(v) {
    var open = S.tasks.filter(function (t) { return t.assignee_id === S.me.id && !t.done; }).length;
    document.getElementById('nav').innerHTML = VIEWS.map(function (x) {
      return '<a href="#' + x[0] + '" class="' + (x[0] === v ? 'on' : '') + '"' + (x[0] === v ? ' aria-current="page"' : '') + '><svg class="ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="' + x[2] + '"/></svg>' + x[1] + (x[0] === 'tehtavat' && open ? '<span class="cnt">' + open + '</span>' : '') + '</a>';
    }).join('');
    document.getElementById('who').innerHTML = '<b>' + esc(S.me.name) + '</b>' + (isOwner() ? 'Osakas' : 'Asentaja') +
      (C.admin ? ' · <a href="' + esc(C.admin) + '">Asetukset</a>' : '') + ' · <a href="' + esc(C.logout) + '">Kirjaudu ulos</a>';
  }
  function head(title, sub, right) { return '<div class="head"><div><h1>' + esc(title) + '</h1>' + (sub ? '<p>' + esc(sub) + '</p>' : '') + '</div>' + (right || '') + '</div>'; }

  /* ---------- Tehtävärivi ---------- */
  function taskRow(t) {
    var late = !t.done && t.due_date && t.due_date < S.today, today = t.due_date === S.today;
    return '<div class="task ' + (t.done ? 'done' : '') + '"><input type="checkbox" data-task="' + t.id + '" ' + (t.done ? 'checked' : '') + ' aria-label="Merkitse tehdyksi: ' + esc(t.title) + '">' +
      '<div><div class="t">' + esc(t.title) + '</div><div class="m">' + (t.customer_id ? '<a href="#asiakas-' + t.customer_id + '">' + esc(t.customer) + '</a>' + (t.address ? ' · ' + esc(t.address) : '') : '') + '</div></div>' +
      '<div class="r"><div>' + (late ? '<span class="pill crit">Myöhässä ' + fdate(t.due_date) + '</span>' : today ? '<span class="pill gold">Tänään</span>' : esc(fdate(t.due_date))) + '</div><div class="muted" style="margin-top:3px">' + esc((uname(t.assignee_id) || '').split(' ')[0]) + '</div></div></div>';
  }
  document.addEventListener('change', function (e) {
    var id = e.target.getAttribute && e.target.getAttribute('data-task');
    if (!id) return;
    var done = e.target.checked;
    api('tasks/' + id, { method: 'POST', body: { done: done } }).then(function (t) {
      S.tasks = S.tasks.map(function (x) { return x.id === t.id ? t : x; });
      toast(done ? 'Tehtävä merkitty tehdyksi' : 'Tehtävä palautettu');
      render();
    }).catch(function (err) { e.target.checked = !done; fail(err); });
  });

  /* ---------- Näkymät ---------- */
  function viewDay() {
    var mine = S.tasks.filter(function (t) { return t.assignee_id === S.me.id && !t.done; });
    var late = mine.filter(function (t) { return t.due_date && t.due_date < S.today; }).length;
    var offers = S.board.filter(function (p) { return p.stage === 2; });
    var running = S.board.filter(function (p) { return p.stage >= 3 && p.stage < 6; });
    var fresh = S.board.filter(function (p) { return p.stage === 0; });
    var tiles = [
      ['Omat tehtävät', mine.length, late ? late + ' myöhässä' : 'ei myöhässä olevia'],
      ['Uudet tarjouspyynnöt', fresh.length, 'odottaa yhteydenottoa'],
      ['Avoimet tarjoukset', offers.length, eur(offers.reduce(function (a, p) { return a + p.value; }, 0)) + ' odottaa'],
      ['Käynnissä', running.length, 'hyväksytty → asennus']
    ];
    var warn = S.me.twofa === false ? '<div class="warn">Kaksivaiheinen tunnistautuminen ei ole käytössä tunnuksellasi. Ota se käyttöön profiilistasi.</div>' : '';
    main.innerHTML = head('Hei, ' + S.me.name.split(' ')[0], 'Tehtävät ja tilanne yhdellä silmäyksellä') + warn +
      '<div class="grid g4">' + tiles.map(function (x) { return '<div class="card tile"><div class="lbl">' + x[0] + '</div><div class="val num">' + x[1] + '</div><div class="sub">' + esc(x[2]) + '</div></div>'; }).join('') + '</div>' +
      '<div class="grid g2" style="margin-top:14px"><div class="card"><h2>Omat tehtävät <span class="r muted">' + mine.length + ' avointa</span></h2>' +
      (mine.length ? mine.map(taskRow).join('') : '<div class="empty">Ei avoimia tehtäviä.</div>') + '</div>' +
      '<div class="card"><h2>Uusimmat tarjouspyynnöt</h2>' + (fresh.length ? fresh.slice(0, 6).map(function (p) {
        return '<div class="task" style="grid-template-columns:minmax(0,1fr) auto"><div><div class="t"><a href="#asiakas-' + p.customer_id + '">' + esc(p.customer) + '</a></div><div class="m">' + esc(p.title) + (p.address ? ' · ' + esc(p.address) : '') + '</div></div><div class="r muted">' + esc(fdate(p.updated_at)) + '</div></div>';
      }).join('') : '<div class="empty">Ei uusia tarjouspyyntöjä.</div>') + '</div></div>';
  }

  function viewBoard() {
    main.innerHTML = head('Myyntiputki', 'Kohteet vaiheen mukaan. Nuoli siirtää seuraavaan vaiheeseen, ja asiakas saa ilmoituksen.') +
      '<div class="board">' + S.stages.map(function (s, i) {
        var items = S.board.filter(function (p) { return p.stage === i; });
        var sum = items.reduce(function (a, p) { return a + p.value; }, 0);
        return '<div class="col"><h3><span>' + esc(s) + '</span><span class="muted num">' + items.length + '</span></h3><div class="sum num">' + (sum ? eur(sum) : '') + '</div>' +
          items.map(function (p) {
            var o = user(p.owner_id);
            return '<div class="deal" data-cust="' + p.customer_id + '" tabindex="0" role="link"><b>' + esc(p.customer) + '</b><div class="m">' + esc(p.address) + '</div><div class="m">' + esc(p.title) + '</div>' +
              '<div class="f"><span class="num small">' + (p.value ? eur(p.value) : '–') + '</span><span style="display:flex;gap:4px;align-items:center">' + (o ? '<span class="av" title="' + esc(o.name) + '">' + esc(o.ini) + '</span>' : '') +
              (i < 6 ? '<button class="mv" data-mv="' + p.id + '" aria-label="Siirrä vaiheeseen ' + esc(S.stages[i + 1]) + '">→</button>' : '') + '</span></div></div>';
          }).join('') + (items.length ? '' : '<div class="muted small" style="padding:6px">Ei kohteita</div>') + '</div>';
      }).join('') + '</div>';
  }
  main.addEventListener('click', function (e) {
    var mv = e.target.closest('[data-mv]');
    if (mv) {
      e.stopPropagation();
      var p = S.board.filter(function (x) { return x.id === +mv.getAttribute('data-mv'); })[0];
      if (p.stage === 2 && !p.approval) { S.openApprove = p.id; location.hash = 'asiakas-' + p.customer_id; return; }
      mv.disabled = true;
      api('projects/' + p.id, { method: 'POST', body: { stage: p.stage + 1 } }).then(function (np) {
        p.stage = np.stage; toast(p.customer + ' → ' + S.stages[np.stage]); render();
      }).catch(function (err) { mv.disabled = false; fail(err); });
      return;
    }
    var d = e.target.closest('[data-cust]');
    if (d) location.hash = 'asiakas-' + d.getAttribute('data-cust');
  });
  main.addEventListener('keydown', function (e) { var d = e.target.closest && e.target.closest('[data-cust]'); if (d && e.key === 'Enter') location.hash = 'asiakas-' + d.getAttribute('data-cust'); });

  function loadCustomers() {
    return api('customers' + (S.q ? '?q=' + encodeURIComponent(S.q) : '')).then(function (l) { S.customers = l; });
  }
  function viewCustomers(id) {
    var srcs = []; S.customers.forEach(function (c) { if (c.source && srcs.indexOf(c.source) < 0) srcs.push(c.source); });
    var list = S.customers.filter(function (c) { return !S.src || c.source === S.src; });
    if (!id && list[0] && window.innerWidth > 860) id = list[0].id;
    main.innerHTML = head('Asiakkaat', 'Yhteystiedot, kohteet ja koko historia', '<a class="btn p" href="#uusi">+ Uusi tarjouspyyntö</a>') +
      '<div class="cust"><div class="card"><div class="search"><input id="q" type="search" placeholder="Hae nimellä, osoitteella tai puhelimella" aria-label="Hae asiakasta" value="' + esc(S.q) + '">' +
      '<select id="src" aria-label="Lähde"><option value="">Kaikki lähteet</option>' + srcs.map(function (s) { return '<option ' + (s === S.src ? 'selected' : '') + '>' + esc(s) + '</option>'; }).join('') + '</select></div>' +
      '<div class="clist">' + (list.length ? list.map(function (c) {
        return '<a href="#asiakas-' + c.id + '" class="' + (c.id === id ? 'on' : '') + '"><span><b>' + esc(c.name) + '</b><span class="small muted">' + esc(c.address) + '</span></span><span class="pill ' + (c.stage === 6 ? 'good' : c.stage === 2 ? 'gold' : '') + '">' + esc(S.stages[c.stage]) + '</span></a>';
      }).join('') : '<div class="empty">Ei asiakkaita. Uudet tarjouspyynnöt tulevat tänne automaattisesti.</div>') + '</div></div>' +
      '<div class="card" id="ccard">' + (id ? '<p class="muted">Ladataan…</p>' : '<div class="empty">Valitse asiakas listasta.</div>') + '</div></div>';
    var q = document.getElementById('q'), t;
    q.addEventListener('input', function () { clearTimeout(t); t = setTimeout(function () { S.q = q.value.trim(); loadCustomers().then(function () { viewCustomers(route().id); var n = document.getElementById('q'); n.focus(); n.setSelectionRange(n.value.length, n.value.length); }); }, 300); });
    document.getElementById('src').addEventListener('change', function (e) { S.src = e.target.value; viewCustomers(route().id); });
    if (id) loadCard(id);
  }

  function opt(users, sel, empty) {
    return (empty ? '<option value="0">' + esc(empty) + '</option>' : '') + users.map(function (u) { return '<option value="' + u.id + '" ' + (u.id === sel ? 'selected' : '') + '>' + esc(u.name) + '</option>'; }).join('');
  }
  function loadCard(id) {
    api('customers/' + id).then(function (c) {
      var box = document.getElementById('ccard'); if (!box) return;
      var owners = S.users.filter(function (u) { return u.role === 'osakas'; });
      box.innerHTML =
        '<div style="display:flex;justify-content:space-between;gap:10px;flex-wrap:wrap"><div><h2>' + esc(c.name) + '</h2><div class="small muted">' + esc([c.address, c.city].filter(Boolean).join(', ')) + '</div></div>' +
        '<span class="small muted">Asiakas ' + esc(fdate(c.created_at)) + ' alkaen</span></div>' +
        '<dl class="kv"><dt>Puhelin</dt><dd class="num">' + esc(c.phone || '–') + '</dd><dt>Sähköposti</dt><dd>' + esc(c.email || '–') + '</dd>' +
        '<dt>Lähde</dt><dd>' + esc(c.source || '–') + (c.referred_by ? ' · suositteli <a href="#asiakas-' + c.referred_by.id + '">' + esc(c.referred_by.name) + '</a>' : '') + '</dd>' +
        '<dt>Vastuumyyjä</dt><dd>' + (isOwner() ? '<select id="owner" aria-label="Vastuumyyjä">' + opt(owners, c.owner_id, 'Ei valittu') + '</select>' : esc(uname(c.owner_id) || '–')) + '</dd>' +
        '<dt>Suosittelukoodi</dt><dd>' + esc(c.ref_code) + '</dd></dl>' +
        '<div class="sect"><h3>Kohteet</h3>' + (c.projects.length ? c.projects.map(function (p) {
          return '<div class="proj" data-proj="' + p.id + '"><div style="display:flex;justify-content:space-between;gap:8px;flex-wrap:wrap"><b>' + esc(p.title) + '</b><span class="pill ' + (p.stage === 6 ? 'good' : '') + '">' + esc(S.stages[p.stage]) + '</span></div>' +
            '<div class="small muted">' + esc(p.address) + '</div>' + (p.details ? '<div class="small" style="white-space:pre-line;margin-top:6px">' + esc(p.details) + '</div>' : '') +
            apprBox(p, c) +
            '<div class="fields" style="margin-top:10px">' +
            '<label class="fld">Vaihe<select data-f="stage">' + S.stages.map(function (s, i) { return '<option value="' + i + '" ' + (i === p.stage ? 'selected' : '') + '>' + esc(s) + '</option>'; }).join('') + '</select></label>' +
            '<label class="fld">Arvo (€, sis. alv)<input data-f="value" type="number" min="0" step="10" value="' + (p.value || '') + '"></label>' +
            '<label class="fld">Asentaja<select data-f="installer_id">' + opt(S.users, p.installer_id, 'Ei valittu') + '</select></label></div></div>';
        }).join('') : '<div class="empty">Ei kohteita.</div>') + '</div>' +
        '<div class="sect"><h3>Avoimet tehtävät</h3>' + (c.tasks.length ? c.tasks.map(taskRow).join('') : '<p class="muted small">Ei avoimia tehtäviä.</p>') +
        '<form class="form" id="ctask"><input class="grow" name="title" placeholder="Uusi tehtävä, esim. Soita ' + esc(c.name.split(' ')[0]) + 'lle" aria-label="Tehtävä" required>' +
        (isOwner() ? '<select name="assignee_id" aria-label="Vastuuhenkilö">' + opt(S.users, S.me.id) + '</select>' : '') +
        '<input name="due_date" type="date" aria-label="Päivämäärä" value="' + esc(S.today) + '"><button class="btn p">Lisää</button></form></div>' +
        '<div class="sect"><h3>Historia</h3><form class="form" id="cnote" style="margin:0 0 12px"><input class="grow" name="text" placeholder="Kirjaa muistiinpano, esim. puhelu tai sovittu asia" aria-label="Muistiinpano" required><button class="btn">Tallenna</button></form>' +
        (c.events.length ? '<ul class="hist">' + c.events.map(function (e) { return '<li><div class="d">' + esc(ftime(e.at)) + (e.by ? ' · ' + esc(e.by) : '') + (e.for_customer ? ' · näkyy asiakkaalle' : '') + '</div>' + esc(e.text) + '</li>'; }).join('') + '</ul>' : '<p class="muted small">Ei tapahtumia.</p>') + '</div>' +
        '<div class="sect"><h3>Suosittelut</h3>' + (c.referrals.length ? c.referrals.map(function (r) { return '<div class="small"><a href="#asiakas-' + r.id + '">' + esc(r.name) + '</a> · <span class="pill ' + (r.stage === 6 ? 'good' : '') + '">' + esc(S.stages[r.stage]) + '</span></div>'; }).join('') : '<p class="muted small">Ei vielä suositeltuja asiakkaita.</p>') + '</div>' +
        '<div class="actions"><a class="btn" href="#uusi-' + c.id + '">+ Uusi kohde</a><button class="btn p" id="invite">Lähetä Oma kohde -linkki</button><button class="btn" id="editc">Muokkaa yhteystietoja</button></div><div id="editbox"></div>';
      bindCard(c);
    }).catch(function (e) { var b = document.getElementById('ccard'); if (b) b.innerHTML = '<div class="empty">' + esc(e.message) + '</div>'; });
  }
  var HOW = [['puhelin', 'Puhelimessa'], ['paikan', 'Paikan päällä'], ['sahkoposti', 'Sähköpostilla'], ['viesti', 'WhatsAppissa tai tekstiviestillä'], ['paperi', 'Allekirjoitti paperisen tarjouksen']];
  var PAY = [['holvi', 'Lasku (Holvi)'], ['rahoitus', 'Skaala-rahoitus (OP)'], ['auki', 'Ei vielä sovittu']];
  function apprBox(p, c) {
    var a = p.approval;
    if (a) return '<div class="appr"><b>✓ Tarjous hyväksytty ' + esc(fdate(a.at)) + '</b> · ' + esc(eur(a.value)) + '<div class="small">Asiakas hyväksyi ' + esc(a.how_label) + ' · kirjasi ' + esc(a.by_name) +
      ' · ' + esc(a.pay_label) + '</div>' + (a.note ? '<div class="small muted">' + esc(a.note) + '</div>' : '') + '</div>';
    if (p.stage >= 3) return '';
    return '<div class="apprw" data-apprw="' + p.id + '"><button type="button" class="btn sm" data-approve="' + p.id + '">✓ Hyväksy tarjous asiakkaan puolesta</button></div>';
  }
  function openApprove(c, pid) {
    var w = document.querySelector('[data-apprw="' + pid + '"]'); if (!w) return;
    var p = c.projects.filter(function (x) { return x.id === pid; })[0];
    w.innerHTML = '<form class="apprf"><h3>Tarjouksen hyväksyntä asiakkaan puolesta</h3><div class="fields">' +
      '<label class="fld">Hyväksytty hinta (€, sis. alv) *<input name="value" type="number" min="1" step="1" required value="' + (p.value || '') + '"></label>' +
      '<label class="fld">Miten asiakas hyväksyi *<select name="how" required><option value="">Valitse</option>' + HOW.map(function (h) { return '<option value="' + h[0] + '">' + h[1] + '</option>'; }).join('') + '</select></label>' +
      '<label class="fld">Maksutapa<select name="payment">' + PAY.map(function (h) { return '<option value="' + h[0] + '"' + (h[0] === 'auki' ? ' selected' : '') + '>' + h[1] + '</option>'; }).join('') + '</select></label>' +
      '<label class="fld wide">Lisätiedot<textarea name="note" rows="2" placeholder="esim. sovittu, että ulko-ovi vaihdetaan malliin X"></textarea></label></div>' +
      '<div class="choices">' +
      '<label><input type="checkbox" name="confirm"' + (c.email ? ' checked' : ' disabled') + '><span>Lähetä asiakkaalle vahvistus sähköpostilla' + (c.email ? ' (' + esc(c.email) + ')' : ' (ei sähköpostia)') + '</span></label></div>' +
      '<div class="actions"><button class="btn p">Merkitse hyväksytyksi</button><button type="button" class="btn" data-cancel>Peruuta</button></div></form>';
    var f = w.querySelector('form');
    f.value.focus();
    w.querySelector('[data-cancel]').addEventListener('click', function () { loadCard(c.id); });
    f.addEventListener('submit', function (e) {
      e.preventDefault();
      if (!f.how.value) { toast('Valitse, miten asiakas hyväksyi', true); return; }
      f.querySelector('.btn.p').disabled = true;
      api('projects/' + pid + '/approve', { method: 'POST', body: { value: f.value.value, how: f.how.value, payment: f.payment.value, note: f.note.value, confirm: f.confirm.checked } })
        .then(function (np) {
          S.board = S.board.map(function (x) { return x.id === np.id ? Object.assign(x, np) : x; });
          return api('tasks').then(function (t) { S.tasks = t; drawNav('asiakkaat'); });
        }).then(function () { toast('Tarjous merkitty hyväksytyksi'); loadCard(c.id); })
        .catch(function (err) { f.querySelector('.btn.p').disabled = false; fail(err); });
    });
  }
  function bindCard(c) {
    var box = document.getElementById('ccard');
    box.querySelectorAll('[data-approve]').forEach(function (b) { b.addEventListener('click', function () { openApprove(c, +b.getAttribute('data-approve')); }); });
    if (S.openApprove) { var oa = S.openApprove; S.openApprove = null; openApprove(c, oa); var w = document.querySelector('[data-apprw="' + oa + '"]'); if (w) w.scrollIntoView({ block: 'center' }); }
    var ow = document.getElementById('owner');
    if (ow) ow.addEventListener('change', function () { api('customers/' + c.id, { method: 'POST', body: { owner_id: +ow.value } }).then(function () { toast('Vastuumyyjä vaihdettu'); }).catch(fail); });
    box.querySelectorAll('[data-proj]').forEach(function (el) {
      var pid = +el.getAttribute('data-proj');
      el.querySelectorAll('[data-f]').forEach(function (f) {
        f.addEventListener('change', function () {
          var proj = c.projects.filter(function (x) { return x.id === pid; })[0];
          if (f.getAttribute('data-f') === 'stage' && +f.value >= 3 && proj.stage < 3 && !proj.approval) {
            f.value = proj.stage; openApprove(c, pid); toast('Kirjaa ensin asiakkaan hyväksyntä'); return;
          }
          var body = {}; body[f.getAttribute('data-f')] = f.value;
          api('projects/' + pid, { method: 'POST', body: body }).then(function (np) {
            S.board = S.board.map(function (x) { return x.id === np.id ? Object.assign(x, np) : x; });
            toast(f.getAttribute('data-f') === 'stage' ? 'Vaihe: ' + S.stages[np.stage] : 'Tallennettu'); loadCard(c.id);
          }).catch(fail);
        });
      });
    });
    document.getElementById('ctask').addEventListener('submit', function (e) {
      e.preventDefault(); var f = e.target;
      api('tasks', { method: 'POST', body: { title: f.title.value, assignee_id: f.assignee_id ? +f.assignee_id.value : S.me.id, due_date: f.due_date.value, customer_id: c.id, project_id: c.projects[0] ? c.projects[0].id : null } })
        .then(function (t) { S.tasks.push(t); toast('Tehtävä lisätty'); drawNav('asiakkaat'); loadCard(c.id); }).catch(fail);
    });
    document.getElementById('cnote').addEventListener('submit', function (e) {
      e.preventDefault(); api('customers/' + c.id + '/note', { method: 'POST', body: { text: e.target.text.value } }).then(function () { toast('Muistiinpano tallennettu'); loadCard(c.id); }).catch(fail);
    });
    document.getElementById('invite').addEventListener('click', function (e) {
      e.target.disabled = true;
      api('customers/' + c.id + '/invite', { method: 'POST' }).then(function () { toast('Kirjautumislinkki lähetetty: ' + c.email); loadCard(c.id); }).catch(function (err) { e.target.disabled = false; fail(err); });
    });
    document.getElementById('editc').addEventListener('click', function () {
      document.getElementById('editbox').innerHTML = '<form class="card" id="cedit" style="margin-top:12px"><div class="fields">' +
        [['name', 'Nimi'], ['email', 'Sähköposti'], ['phone', 'Puhelin'], ['address', 'Katuosoite'], ['city', 'Paikkakunta'], ['source', 'Lähde']].map(function (f) {
          return '<label class="fld">' + f[1] + '<input name="' + f[0] + '" value="' + esc(c[f[0]] || '') + '"' + (f[0] === 'email' ? ' type="email"' : '') + '></label>';
        }).join('') + '</div><div class="actions"><button class="btn p">Tallenna</button></div></form>';
      document.getElementById('cedit').addEventListener('submit', function (e) {
        e.preventDefault(); var f = e.target, body = {};
        ['name', 'email', 'phone', 'address', 'city', 'source'].forEach(function (k) { body[k] = f[k].value; });
        api('customers/' + c.id, { method: 'POST', body: body }).then(function () { toast('Tiedot tallennettu'); loadCustomers().then(function () { viewCustomers(c.id); }); }).catch(fail);
      });
    });
  }

  function viewTasks() {
    var l = S.tasks.slice();
    if (S.taskFilter === 'omat') l = l.filter(function (t) { return t.assignee_id === S.me.id; });
    if (S.taskFilter === 'myohassa') l = l.filter(function (t) { return !t.done && t.due_date && t.due_date < S.today; });
    l.sort(function (a, b) { return (a.done - b.done) || String(a.due_date || '9999').localeCompare(String(b.due_date || '9999')); });
    main.innerHTML = head('Tehtävät', 'Soitot, hakemukset, lahjat ja muut muistettavat') +
      '<div class="card"><h2>Tehtävät <span class="r"><span class="seg" id="tf">' + [['omat', 'Omat'], ['kaikki', 'Kaikki'], ['myohassa', 'Myöhässä']].map(function (f) { return '<button type="button" data-f="' + f[0] + '" class="' + (S.taskFilter === f[0] ? 'on' : '') + '">' + f[1] + '</button>'; }).join('') + '</span></span></h2>' +
      (l.length ? l.map(taskRow).join('') : '<div class="empty">Ei tehtäviä tällä rajauksella.</div>') +
      '<form class="form" id="ntask"><input class="grow" name="title" placeholder="Uusi tehtävä" aria-label="Tehtävä" required>' + (isOwner() ? '<select name="assignee_id" aria-label="Vastuuhenkilö">' + opt(S.users, S.me.id) + '</select>' : '') +
      '<input name="due_date" type="date" value="' + esc(S.today) + '" aria-label="Päivämäärä"><button class="btn p">Lisää</button></form></div>';
    document.getElementById('tf').addEventListener('click', function (e) { var b = e.target.closest('button'); if (b) { S.taskFilter = b.getAttribute('data-f'); viewTasks(); } });
    document.getElementById('ntask').addEventListener('submit', function (e) {
      e.preventDefault(); var f = e.target;
      api('tasks', { method: 'POST', body: { title: f.title.value, assignee_id: f.assignee_id ? +f.assignee_id.value : S.me.id, due_date: f.due_date.value } })
        .then(function (t) { S.tasks.push(t); toast('Tehtävä lisätty'); render(); }).catch(fail);
    });
  }

  /* ---------- Viestin jäsennys: puhelin, sähköposti, osoite, nimi ja tuotteet liitetystä viestistä ---------- */
  var CITIES = ['Turku', 'Masku', 'Raisio', 'Naantali', 'Kaarina', 'Lieto', 'Rusko', 'Nousiainen', 'Mynämäki', 'Taivassalo', 'Kustavi', 'Uusikaupunki',
    'Laitila', 'Vehmaa', 'Pyhäranta', 'Salo', 'Paimio', 'Sauvo', 'Parainen', 'Aura', 'Pöytyä', 'Marttila', 'Koski Tl', 'Loimaa', 'Oripää', 'Somero',
    'Rauma', 'Eura', 'Säkylä', 'Pori', 'Kemiönsaari', 'Tampere', 'Helsinki', 'Espoo', 'Vantaa', 'Hämeenlinna', 'Forssa'];
  var CITY_FORMS = { Turussa: 'Turku', Turusta: 'Turku', Turkuun: 'Turku', Liedossa: 'Lieto', Liedosta: 'Lieto', 'Mynämäellä': 'Mynämäki', 'Mynämäeltä': 'Mynämäki',
    Uudessakaupungissa: 'Uusikaupunki', Tampereella: 'Tampere', Tampereelta: 'Tampere', Helsingissä: 'Helsinki', Helsingistä: 'Helsinki', Kemiönsaarella: 'Kemiönsaari',
    Pöytyällä: 'Pöytyä', Marttilassa: 'Marttila', Somerolla: 'Somero', Kaarinassa: 'Kaarina', Paraisilla: 'Parainen', Paraisten: 'Parainen' };
  var STREET = '(?:tie|katu|kuja|polku|väylä|ranta|kaari|rinne|mäki|puisto|tanhua|kaarre|raitti|aukio|piha|kulma|linja|niitty|vainio|pelto|ahde|tori|kallio|laakso|lenkki|reitti|haka|nummi|kumpu|kangas|kylä|tanhuantie|gatan|vägen|stigen)';
  function parseMessage(raw) {
    var out = {}, text = String(raw || '').replace(/\r/g, '').trim();
    if (!text) return out;
    // WhatsAppin kopioimat viestit: "[17.10.2026 14.32] Nimi: viesti" tai vienti "17.10.2026 klo 14.32 - Nimi: viesti"
    var wa = /^\s*(?:\[[^\]]{6,30}\]|\d{1,2}[./]\d{1,2}[./]\d{2,4},?\s+(?:klo\s+)?\d{1,2}[.:]\d{2}(?:[.:]\d{2})?\s+-)\s*([^:\n]{2,40}):\s?/;
    var lines = text.split('\n').map(function (l) {
      var m = wa.exec(l);
      if (m) { if (!out.name && !/^\+?\d[\d\s-]+$/.test(m[1])) out.name = m[1].trim(); out.whatsapp = true; return l.slice(m[0].length); }
      return l;
    });
    text = lines.join('\n').trim();
    out.message = text;
    var em = /[\w.+-]+@[\w-]+(?:\.[\w-]+)+/.exec(text);
    if (em) out.email = em[0].toLowerCase();
    var ph = /(?:\+358|00358|(?:^|[^\d])0)[\s-]?\(?\d{1,3}\)?(?:[\s-]?\d){5,8}(?!\d)/.exec(text);
    if (ph) out.phone = ph[0].replace(/^[^\d+]+/, '').replace(/\s{2,}/g, ' ').trim();
    var st = new RegExp('([A-ZÅÄÖ][a-zåäöA-ZÅÄÖ-]*' + STREET + ')\\s+(\\d+(?:\\s?[A-Za-z]{1,2})?(?:\\s?\\d+)?(?:-\\d+)?)\\b').exec(text) ||
      new RegExp('([A-ZÅÄÖ][a-zåäö-]+\\s(?:' + STREET.slice(3, -1) + '))\\s+(\\d+[A-Za-z]?)\\b').exec(text);
    if (st) out.address = (st[1] + ' ' + st[2]).trim();
    var pc = /\b\d{5}\s+([A-ZÅÄÖ][a-zåäö-]+)/.exec(text);
    if (pc) out.city = pc[1];
    if (!out.city && st) { var after = /^,?\s*([A-ZÅÄÖ][a-zåäö-]+)/.exec(text.slice(st.index + st[0].length)); if (after && /^,/.test(text.slice(st.index + st[0].length).trim())) out.city = after[1]; }
    if (!out.city) Object.keys(CITY_FORMS).some(function (f) { if (new RegExp('(^|[^\\wåäö])' + f + '(?![\\wåäö])').test(text)) { out.city = CITY_FORMS[f]; return true; } return false; });
    if (!out.city) CITIES.some(function (c) { if (new RegExp('(^|[^\\wåäö])' + c + '(ssa|ssä|sta|stä|lla|llä|lta|ltä|an|en|in|un|ön|än)?(?![\\wåäö])', 'i').test(text)) { out.city = c; return true; } return false; });
    if (!out.name) {
      var sig = /(?:[Tt]erveisin|[Tt]erv\.|\b[Tt]\.|[Yy]st\.\s*terv\.?)\s*,?\s*\n?\s*([A-ZÅÄÖ][a-zåäö-]+(?:\s[A-ZÅÄÖ][a-zåäö-]+)?)/.exec(text) ||
        /(?:[Oo]len|[Nn]imeni on|[Tt]äällä)\s+([A-ZÅÄÖ][a-zåäö-]+(?:\s[A-ZÅÄÖ][a-zåäö-]+)?)/.exec(text);
      if (sig) out.name = sig[1];
    }
    var prod = [], w = /(\d+)\s*(?:kpl\s*|kappaletta\s*)?(?:uut\w*\s*)?ikkun/i.exec(text);
    if (w) prod.push(w[1] + ' ikkunaa'); else if (/ikkun/i.test(text)) prod.push('ikkunat');
    if (/ulko-?ov/i.test(text)) prod.push('ulko-ovi');
    if (/parveke-?ov/i.test(text)) prod.push('parvekeovi');
    if (/terassi-?ov|terassin ov/i.test(text)) prod.push('terassiovi');
    if (/autotalli/i.test(text)) prod.push('autotallin ovi');
    if (!prod.length && /\bov(i|ia|et|en)\b/i.test(text)) prod.push('ovet');
    if (prod.length) out.title = prod.join(', ').replace(/, ([^,]*)$/, ' ja $1');
    return out;
  }
  function digits(s) { return String(s || '').replace(/\D/g, '').replace(/^358/, '0').slice(-9); }
  function findExisting(p) {
    return S.customers.filter(function (c) {
      return (p.email && c.email && c.email.toLowerCase() === p.email) || (p.phone && c.phone && digits(c.phone).length >= 7 && digits(c.phone) === digits(p.phone));
    })[0];
  }

  var SOURCES = ['Puhelin', 'WhatsApp', 'Tekstiviesti', 'Messenger', 'Sähköposti', 'Messut', 'Paikan päällä', 'Roll-up tai kyltti', 'Suosittelu', 'Google-haku', 'Facebook', 'Isännöitsijä', 'Muu'];
  function viewNew(cid) {
    if (cid && (!S.newFor || S.newFor.id !== cid)) {
      main.innerHTML = head('Uusi tarjouspyyntö', 'Ladataan…');
      api('customers/' + cid).then(function (c) { S.newFor = c; viewNew(cid); }).catch(fail);
      return;
    }
    var c = cid ? S.newFor : null;
    var owners = S.users.filter(function (u) { return u.role === 'osakas'; });
    var f2 = function (name, label, type, extra) { return '<label class="fld">' + label + '<input name="' + name + '" type="' + type + '" ' + (extra || '') + '></label>'; };
    main.innerHTML = head('Uusi tarjouspyyntö', c ? 'Uusi kohde asiakkaalle ' + c.name + '.' : 'Puhelimessa, sähköpostilla, messuilla tai paikan päällä saatu tarjouspyyntö. Verkkosivun lomakkeelta ne tulevat automaattisesti.') +
      '<form class="card" id="nc">' +
      '<label class="fld wide paste">Liitä asiakkaan viesti <span class="muted small">(WhatsApp, tekstiviesti, Messenger, sähköposti: puhelin, sähköposti, osoite ja tuotteet poimitaan kenttiin)</span>' +
        '<textarea name="paste" rows="4" placeholder="Pitkä painallus viestiin → Kopioi, ja liitä tähän"></textarea></label><div id="parsed" class="parsed" hidden></div>' +
      (c ? '<p><b>' + esc(c.name) + '</b><br><span class="muted">' + esc([c.phone, c.email].filter(Boolean).join(' · ')) + '</span></p>' :
        '<h2>Asiakas</h2><div class="fields">' + f2('name', 'Nimi *', 'text', 'required autocomplete="off"') + f2('phone', 'Puhelin', 'tel') + f2('email', 'Sähköposti', 'email') + '</div>') +
      '<h2 class="mt">Kohde</h2><div class="fields">' +
      f2('address', 'Kohteen katuosoite', 'text', c ? 'value="' + esc(c.address) + '"' : '') + f2('city', 'Paikkakunta', 'text', c ? 'value="' + esc(c.city) + '"' : '') +
      f2('title', 'Tuotteet', 'text', 'placeholder="esim. 8 ikkunaa ja ulko-ovi"') +
      '<label class="fld wide">Mitä asiakas tarvitsee<textarea name="message" rows="3" placeholder="Talon ikä, ikkunoiden määrä, toiveet, aikataulu…"></textarea></label>' +
      '<label class="fld">Mistä asiakas tuli<select name="source">' + SOURCES.map(function (x) { return '<option>' + x + '</option>'; }).join('') + '</select></label>' +
      '<label class="fld" id="refw" hidden>Suosittelija<select name="referrer_id"><option value="">Valitse asiakas</option>' +
        S.customers.filter(function (x) { return !c || x.id !== c.id; }).map(function (x) { return '<option value="' + x.id + '">' + esc(x.name) + (x.city ? ', ' + esc(x.city) : '') + '</option>'; }).join('') + '</select></label>' +
      (isOwner() && !c ? '<label class="fld">Vastuumyyjä<select name="owner_id">' + opt(owners, S.me.id) + '</select></label>' : '') +
      '</div><h2 class="mt">Seuraava askel</h2><div class="choices">' +
      '<label><input type="radio" name="task" value="call" checked><span>Soita ja sovi mittaus (tehtävä seuraavalle arkipäivälle)</span></label>' +
      '<label><input type="radio" name="task" value="measure"><span>Mittauskäynti on jo sovittu</span></label>' +
      '<input class="date" type="date" name="measure_date" aria-label="Mittauspäivä">' +
      '<label><input type="radio" name="task" value="none"><span>Ei tehtävää</span></label>' +
      '<label class="mt"><input type="checkbox" name="send_link" checked><span>Lähetä asiakkaalle kiitosviesti ja Oma kohde -linkki <span class="muted small">(vaatii sähköpostin)</span></span></label>' +
      '</div><div class="actions"><button class="btn p">Tallenna tarjouspyyntö</button>' + (c ? '<a class="btn" href="#asiakas-' + c.id + '">Peruuta</a>' : '') + '</div></form>';
    var form = document.getElementById('nc');
    function applyParsed(text, fromShare) {
      var p = parseMessage(text), got = [];
      var set = function (k, label) {
        if (!form[k] || !p[k]) return;
        if (form[k].value && !form[k].dataset.auto) return; // käsin kirjoitettua ei ylikirjoiteta
        form[k].value = p[k]; form[k].dataset.auto = '1'; got.push(label);
      };
      if (!c) { set('name', 'nimi'); set('phone', 'puhelin'); set('email', 'sähköposti'); }
      set('address', 'osoite'); set('city', 'paikkakunta'); set('title', 'tuotteet');
      if (p.message && (!form.message.value || form.message.dataset.auto)) { form.message.value = p.message; form.message.dataset.auto = '1'; }
      if (p.whatsapp || fromShare) form.source.value = 'WhatsApp';
      else if (p.email && !p.phone) form.source.value = 'Sähköposti';
      var box = document.getElementById('parsed'), ex = !c && findExisting(p);
      box.hidden = false;
      box.innerHTML = (got.length ? 'Poimittiin: <b>' + esc(got.join(', ')) + '</b>. Tarkista kentät ennen tallennusta.' : 'Viestistä ei löytynyt yhteystietoja. Täydennä kentät käsin.') +
        (fromShare ? ' Lähteeksi valittiin WhatsApp, vaihda tarvittaessa.' : '') +
        (ex ? '<br>Sama ' + (p.email && ex.email && ex.email.toLowerCase() === p.email ? 'sähköposti' : 'puhelinnumero') + ' on jo asiakkaalla <b>' + esc(ex.name) + '</b>. <a href="#uusi-' + ex.id + '" id="useex">Lisää kohde hänelle</a>' : '');
      var u = document.getElementById('useex');
      if (u) u.addEventListener('click', function () { S.prefill = { text: form.paste.value, share: fromShare }; });
    }
    form.paste.addEventListener('input', function () { applyParsed(form.paste.value, false); });
    form.addEventListener('input', function (e) { if (e.target !== form.paste && e.target.dataset) delete e.target.dataset.auto; });
    if (S.prefill) { var pf = S.prefill; S.prefill = null; form.paste.value = pf.text; applyParsed(pf.text, pf.share); }
    form.source.addEventListener('change', function () { document.getElementById('refw').hidden = form.source.value !== 'Suosittelu'; });
    form.measure_date.addEventListener('focus', function () { form.task.value = 'measure'; });
    form.addEventListener('submit', function (e) {
      e.preventDefault();
      var body = { customer_id: c ? c.id : 0, task: form.task.value, send_link: form.send_link.checked };
      ['address', 'city', 'title', 'message', 'source'].forEach(function (k) { body[k] = form[k].value; });
      if (!c) ['name', 'phone', 'email'].forEach(function (k) { body[k] = form[k].value.trim(); });
      if (!c && !body.phone && !body.email) { toast('Anna puhelinnumero tai sähköposti', true); return; }
      if (body.source === 'Suosittelu' && form.referrer_id.value) body.referrer_id = +form.referrer_id.value;
      if (body.task === 'measure') {
        if (!form.measure_date.value) { toast('Valitse mittauspäivä', true); return; }
        body.measure_date = form.measure_date.value;
      }
      if (form.owner_id) body.owner_id = +form.owner_id.value;
      form.querySelector('button.p').disabled = true;
      api('customers', { method: 'POST', body: body }).then(function (r) {
        toast(r.existing ? 'Kohde lisätty olemassa olevalle asiakkaalle' : 'Tarjouspyyntö tallennettu' + (r.mailed ? ', linkki lähetetty' : ''));
        S.newFor = null;
        return Promise.all([api('board').then(function (b) { S.board = b; }), api('tasks').then(function (t) { S.tasks = t; }), loadCustomers()]).then(function () { location.hash = 'asiakas-' + r.id; });
      }).catch(function (err) { form.querySelector('button.p').disabled = false; fail(err); });
    });
  }

  function render() {
    var r = route();
    drawNav(r.v);
    if (r.v === 'putki') return viewBoard();
    if (r.v === 'asiakkaat') return viewCustomers(r.id);
    if (r.v === 'tehtavat') return viewTasks();
    if (r.v === 'uusi') return viewNew(r.id);
    return viewDay();
  }
  window.addEventListener('hashchange', function () { if (!S.me) return; render(); window.scrollTo(0, 0); });

  api('bootstrap').then(function (b) {
    S.me = b.me; S.users = b.users; S.stages = b.stages; S.today = b.today;
    return Promise.all([api('board'), api('tasks'), api('customers')]);
  }).then(function (r) {
    S.board = r[0]; S.tasks = r[1]; S.customers = r[2];
    // Androidin Jaa-valikko: /crm/?jaa_teksti=... → Uusi tarjouspyyntö viesti valmiiksi liitettynä
    var q = new URLSearchParams(location.search), shared = [q.get('jaa_otsikko'), q.get('jaa_teksti'), q.get('jaa_linkki')].filter(Boolean).join('\n');
    if (shared) {
      S.prefill = { text: shared, share: true };
      history.replaceState(null, '', location.pathname + '#uusi');
    }
    render();
  })
    .catch(function (e) { main.innerHTML = '<div class="empty">CRM:n lataus epäonnistui: ' + esc(e.message) + ' Päivitä sivu tai kirjaudu uudelleen.</div>'; });
})();
