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
  function bindCard(c) {
    var box = document.getElementById('ccard');
    var ow = document.getElementById('owner');
    if (ow) ow.addEventListener('change', function () { api('customers/' + c.id, { method: 'POST', body: { owner_id: +ow.value } }).then(function () { toast('Vastuumyyjä vaihdettu'); }).catch(fail); });
    box.querySelectorAll('[data-proj]').forEach(function (el) {
      var pid = +el.getAttribute('data-proj');
      el.querySelectorAll('[data-f]').forEach(function (f) {
        f.addEventListener('change', function () {
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

  var SOURCES = ['Puhelin', 'Sähköposti', 'Messut', 'Paikan päällä', 'Roll-up tai kyltti', 'Suosittelu', 'Google-haku', 'Facebook', 'Isännöitsijä', 'Muu'];
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
  window.addEventListener('hashchange', function () { render(); window.scrollTo(0, 0); });

  api('bootstrap').then(function (b) {
    S.me = b.me; S.users = b.users; S.stages = b.stages; S.today = b.today;
    return Promise.all([api('board'), api('tasks'), api('customers')]);
  }).then(function (r) { S.board = r[0]; S.tasks = r[1]; S.customers = r[2]; render(); })
    .catch(function (e) { main.innerHTML = '<div class="empty">CRM:n lataus epäonnistui: ' + esc(e.message) + ' Päivitä sivu tai kirjaudu uudelleen.</div>'; });
})();
