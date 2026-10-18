/* Nordic CRM – kalenteri ja käynnit: viikkonäkymä asentajittain, käynnin varaus, asentajan puhelinnäkymä
   (navigointi, kuvat ennen ja jälkeen, mittauspöytäkirja, luovutuskuittaus allekirjoituksella).
   Käyttää crm.js:n apuvälineitä window.NAPV:n kautta. */
(function () {
  'use strict';
  var V = window.NAPV; if (!V) return;
  var S = V.S, esc = V.esc, api = V.api, toast = V.toast, fail = V.fail, head = V.head, fdate = V.fdate;

  var TYPES = { mittaus: ['Mittauskäynti', 60], asennus: ['Asennus', 480], toimitus: ['Toimitus', 60], huolto: ['Huoltokäynti', 60], muu: ['Käynti', 60] };
  var KINDS = [['ennen', 'Ennen'], ['jalkeen', 'Jälkeen'], ['mittaus', 'Mittaus'], ['muu', 'Muut']];
  var DAYS = ['su', 'ma', 'ti', 'ke', 'to', 'pe', 'la'];

  /* ---------- Päivämäärät (UTC, jotta aikavyöhyke ei siirrä päiviä) ---------- */
  function d(s) { return new Date(s + 'T00:00:00Z'); }
  function iso(x) { return x.toISOString().slice(0, 10); }
  function add(s, n) { var x = d(s); x.setUTCDate(x.getUTCDate() + n); return iso(x); }
  function monday(s) { var x = d(s), w = (x.getUTCDay() + 6) % 7; x.setUTCDate(x.getUTCDate() - w); return iso(x); }
  function hm(dt) { return String(dt).slice(11, 16).replace(':', '.').replace(/^0/, ''); }
  function dayName(s) { var x = d(s); return DAYS[x.getUTCDay()] + ' ' + x.getUTCDate() + '.' + (x.getUTCMonth() + 1) + '.'; }
  function when(v) { return dayName(v.start_at.slice(0, 10)) + (v.all_day ? ' koko päivä' : ' klo ' + hm(v.start_at) + '–' + hm(v.end_at)); }
  function mapUrl(a) { return 'https://www.google.com/maps/dir/?api=1&destination=' + encodeURIComponent(a); }
  function tel(p) { return 'tel:' + String(p || '').replace(/[^\d+]/g, ''); }

  /* ---------- Kalenteri ---------- */
  function viewCalendar(week) {
    var main = V.main();
    week = monday(week || S.calWeek || S.today); S.calWeek = week;
    var end = add(week, 6);
    main.innerHTML = head('Kalenteri', 'Viikko ' + dayName(week) + ' – ' + dayName(end), '<div class="actions" style="margin:0">' +
      '<a class="btn" href="#kalenteri-' + add(week, -7) + '" aria-label="Edellinen viikko">‹</a><a class="btn" href="#kalenteri-' + monday(S.today) + '">Tämä viikko</a>' +
      '<a class="btn" href="#kalenteri-' + add(week, 7) + '" aria-label="Seuraava viikko">›</a><button class="btn p" id="vnew">+ Uusi käynti</button></div>') + '<div class="card"><p class="muted">Ladataan…</p></div>';
    document.getElementById('vnew').addEventListener('click', function () { visitForm({ date: S.today > week && S.today <= end ? S.today : week }); });
    api('visits?from=' + week + '&to=' + end).then(function (list) {
      list = list.filter(function (v) { return v.status !== 'cancelled'; });
      var days = []; for (var i = 0; i < 7; i++) days.push(add(week, i));
      var rows = (V.isOwner() ? S.users.slice() : S.users.filter(function (u) { return u.id === S.me.id; }));
      if (list.some(function (v) { return !v.installer_id; })) rows.push({ id: 0, name: 'Ei asentajaa', ini: '–' });
      var chip = function (v) {
        return '<a class="vchip t-' + v.type + (v.status === 'done' ? ' done' : '') + '" href="#kaynti-' + v.id + '" title="' + esc(v.label + ': ' + v.customer + ', ' + v.address) + '">' +
          '<b>' + (v.all_day ? 'Koko pv' : hm(v.start_at)) + '</b> ' + esc(v.customer) + '<span>' + esc(v.label) + (v.status === 'done' ? ' ✓' : '') + '</span></a>';
      };
      var grid = '<div class="cal" style="grid-template-columns:150px repeat(7,minmax(0,1fr))"><div class="ch"></div>' +
        days.map(function (x) { return '<div class="ch' + (x === S.today ? ' today' : '') + '">' + dayName(x) + '</div>'; }).join('') +
        rows.map(function (u) {
          return '<div class="cu"><b>' + esc(u.name) + '</b>' + (u.role === 'asentaja' ? '<span class="small muted">Asentaja</span>' : '') + '</div>' + days.map(function (x) {
            var vs = list.filter(function (v) { return (v.installer_id || 0) === u.id && v.start_at.slice(0, 10) === x; });
            return '<div class="cc' + (x === S.today ? ' today' : '') + '" data-date="' + x + '" data-user="' + u.id + '">' + vs.map(chip).join('') + '</div>';
          }).join('');
        }).join('') + '</div>';
      var mobile = '<div class="calm">' + days.map(function (x) {
        var vs = list.filter(function (v) { return v.start_at.slice(0, 10) === x; });
        return '<div class="calday' + (x === S.today ? ' today' : '') + '"><h3>' + dayName(x) + '</h3>' + (vs.length ? vs.map(function (v) {
          var u = V.user(v.installer_id); return '<div class="calrow">' + chip(v) + '<span class="av" title="' + esc(u ? u.name : 'Ei asentajaa') + '">' + esc(u ? u.ini : '–') + '</span></div>';
        }).join('') : '<p class="muted small" style="margin:4px 0 0">Ei käyntejä</p>') + '</div>';
      }).join('') + '</div>';
      var box = main.querySelector('.card');
      box.outerHTML = '<div class="card calwrap">' + grid + mobile + '<p class="small muted callegend"><span class="vchip t-mittaus">Mittaus</span><span class="vchip t-asennus">Asennus</span><span class="vchip t-toimitus">Toimitus</span><span class="vchip t-huolto">Huolto</span> Klikkaa tyhjää kohtaa varataksesi käynnin.</p></div>';
      main.querySelectorAll('.cc').forEach(function (c) {
        c.addEventListener('click', function (e) { if (e.target.closest('.vchip')) return; visitForm({ date: c.getAttribute('data-date'), installer_id: +c.getAttribute('data-user') || null }); });
      });
    }).catch(fail);
  }

  /* ---------- Käynnin lomake (uusi tai muokkaus) ---------- */
  function visitForm(pre, existing) {
    pre = pre || {};
    var v = existing || null;
    var projects = S.board.filter(function (p) { return p.stage < 6 || (pre.project_id && p.id === pre.project_id) || (v && p.id === v.project_id); })
      .sort(function (a, b) { return String(a.customer).localeCompare(String(b.customer), 'fi'); });
    var type = v ? v.type : (pre.type || 'mittaus');
    var dur = v ? Math.round((Date.parse(v.end_at.replace(' ', 'T') + 'Z') - Date.parse(v.start_at.replace(' ', 'T') + 'Z')) / 60000) : TYPES[type][1];
    var durs = [[30, '30 min'], [60, '1 h'], [90, '1,5 h'], [120, '2 h'], [180, '3 h'], [240, '4 h'], [360, '6 h'], [480, '8 h'], [0, 'Koko päivä']];
    var allDay = v ? v.all_day : false;
    var m = document.createElement('div'); m.className = 'modal';
    m.innerHTML = '<form class="card" role="dialog" aria-modal="true" aria-label="Käynti"><h2>' + (v ? 'Muokkaa käyntiä' : 'Uusi käynti') + '</h2><div class="fields">' +
      (v || pre.project_id ? '<input type="hidden" name="project_id" value="' + (v ? v.project_id : pre.project_id) + '"><p class="fld wide" style="margin:0"><b>' + esc(v ? v.customer + ' – ' + v.title : (projects.filter(function (p) { return p.id === pre.project_id; })[0] || {}).customer || '') + '</b></p>' :
        '<label class="fld wide">Asiakas ja kohde *<select name="project_id" required><option value="">Valitse</option>' + projects.map(function (p) { return '<option value="' + p.id + '">' + esc(p.customer + ' – ' + p.title + (p.address ? ', ' + p.address : '')) + '</option>'; }).join('') + '</select></label>') +
      '<label class="fld">Tyyppi<select name="type">' + Object.keys(TYPES).map(function (k) { return '<option value="' + k + '"' + (k === type ? ' selected' : '') + '>' + TYPES[k][0] + '</option>'; }).join('') + '</select></label>' +
      '<label class="fld">Päivä *<input name="date" type="date" required value="' + esc(v ? v.start_at.slice(0, 10) : pre.date || S.today) + '"></label>' +
      '<label class="fld">Kellonaika<input name="time" type="time" step="900" value="' + esc(v ? v.start_at.slice(11, 16) : '08:00') + '"></label>' +
      '<label class="fld">Kesto<select name="duration">' + durs.map(function (x) { var sel = allDay ? x[0] === 0 : x[0] === dur; return '<option value="' + x[0] + '"' + (sel ? ' selected' : '') + '>' + x[1] + '</option>'; }).join('') + '</select></label>' +
      (V.isOwner() ? '<label class="fld">Asentaja<select name="installer_id">' + V.opt(S.users, v ? v.installer_id : (pre.installer_id || null), 'Ei valittu') + '</select></label>' : '') +
      '<label class="fld wide">Lisätiedot asentajalle<textarea name="notes" rows="2" placeholder="esim. ovikoodi, pysäköinti, avaimet">' + esc(v ? v.notes : '') + '</textarea></label></div>' +
      '<div class="choices"><label><input type="checkbox" name="notify" checked><span>' + (v ? 'Ilmoita asiakkaalle, jos aika muuttuu' : 'Lähetä asiakkaalle vahvistus sähköpostilla') + ' (muistutus lähtee aina edellisenä päivänä)</span></label></div>' +
      '<div class="actions"><button class="btn p">Tallenna</button><button type="button" class="btn" data-x>Peruuta</button></div></form>';
    document.body.appendChild(m);
    var f = m.querySelector('form');
    var close = function () { m.remove(); };
    m.addEventListener('click', function (e) { if (e.target === m) close(); });
    f.querySelector('[data-x]').addEventListener('click', close);
    f.type.addEventListener('change', function () { if (!v) { var t = TYPES[f.type.value][1]; f.duration.value = String(t >= 480 ? 480 : t); } });
    f.addEventListener('submit', function (e) {
      e.preventDefault();
      var all = f.duration.value === '0';
      var body = { project_id: +f.project_id.value, type: f.type.value, date: f.date.value, time: f.time.value || '08:00', duration: all ? 0 : +f.duration.value, all_day: all, notes: f.notes.value, notify: f.notify.checked };
      if (f.installer_id) body.installer_id = +f.installer_id.value;
      if (!body.project_id) { toast('Valitse asiakas ja kohde', true); return; }
      f.querySelector('.btn.p').disabled = true;
      api(v ? 'visits/' + v.id : 'visits', { method: 'POST', body: body }).then(function (nv) {
        close(); toast(v ? 'Käynti päivitetty' : 'Käynti varattu' + (body.notify && !v ? ', vahvistus lähetetty' : ''));
        S.visitsToday = null;
        return V.reloadBoard().then(function () {
          if (location.hash === '#kaynti-' + nv.id) viewVisit(nv.id); else if (pre.back) location.hash = pre.back; else location.hash = 'kalenteri-' + monday(nv.start_at.slice(0, 10));
          if (/^#(kalenteri|asiakas)/.test(location.hash)) V.render();
        });
      }).catch(function (err) { f.querySelector('.btn.p').disabled = false; fail(err); });
    });
  }

  /* ---------- Asentajan näkymä: yksi käynti ---------- */
  function viewVisit(id) {
    var main = V.main();
    main.innerHTML = '<p class="muted">Ladataan…</p>';
    api('visits/' + id).then(function (v) {
      var u = V.user(v.installer_id);
      var files = function (k) { return v.files.filter(function (f) { return f.kind === k; }); };
      var photoSect = '<div class="sect"><h3>Kuvat</h3><div class="ptabs">' + KINDS.map(function (k) {
        var fs = files(k[0]);
        return '<div class="pkind"><div class="pkh"><b>' + k[1] + '</b> <span class="muted small">' + fs.length + '</span></div><div class="pgrid">' +
          fs.map(function (f) { return '<a href="' + esc(location.pathname + '?tiedosto=' + f.id) + '" target="_blank" rel="noopener" class="ph"><img loading="lazy" alt="' + esc(k[1]) + '" src="' + esc(location.pathname + '?tiedosto=' + f.id) + '"></a>'; }).join('') +
          '<label class="padd" title="Ota kuva"><input type="file" accept="image/*" capture="environment" multiple data-kind="' + k[0] + '" hidden>📷<span>Ota kuva</span></label>' +
          '<label class="padd" title="Valitse kuvista"><input type="file" accept="image/*" multiple data-kind="' + k[0] + '" hidden>🖼️<span>Valitse</span></label></div></div>';
      }).join('') + '</div><p class="small muted" id="upst" hidden></p></div>';

      main.innerHTML = '<div class="vhead"><a href="#kalenteri" class="muted small">‹ Kalenteri</a><h1>' + esc(v.label) + '</h1>' +
        '<p class="vwhen">' + esc(when(v)) + (u ? ' · ' + esc(u.name) : '') + ' <span class="pill ' + (v.status === 'done' ? 'good' : v.status === 'cancelled' ? 'crit' : '') + '">' + (v.status === 'done' ? 'Tehty' : v.status === 'cancelled' ? 'Peruttu' : 'Suunniteltu') + '</span></p></div>' +
        '<div class="card"><h2>' + (V.isOwner() ? '<a href="#asiakas-' + v.customer_id + '">' + esc(v.customer) + '</a>' : esc(v.customer)) + '</h2><p class="muted" style="margin:2px 0 10px">' + esc(v.title) + '</p>' +
        '<p style="margin:0 0 12px">' + esc(v.address || 'Osoite puuttuu') + '</p>' +
        '<div class="vbtns">' + (v.address ? '<a class="btn p" href="' + esc(mapUrl(v.address)) + '" target="_blank" rel="noopener">🧭 Navigoi</a>' : '') + (v.phone ? '<a class="btn" href="' + esc(tel(v.phone)) + '">📞 Soita</a>' : '') + '</div>' +
        (v.notes ? '<div class="vnote">' + esc(v.notes) + '</div>' : '') +
        '<div class="actions" style="margin-top:12px">' + (v.status === 'planned' ? '<button class="btn" id="vdone">✓ Merkitse tehdyksi</button>' : '<button class="btn" id="vreopen">Palauta suunnitelluksi</button>') +
        '<button class="btn" id="vedit">Muokkaa</button>' + (v.status === 'planned' ? '<button class="btn" id="vcancel">Peru käynti</button>' : '') + '</div></div>' +
        (v.type === 'mittaus' || v.protocol ? '<div class="card" style="margin-top:14px" id="proto"></div>' : '') +
        (v.type === 'asennus' || v.type === 'toimitus' || v.handover ? '<div class="card" style="margin-top:14px" id="hand"></div>' : '') +
        '<div class="card" style="margin-top:14px">' + photoSect + '</div>';

      var setStatus = function (st, msg, notify) {
        api('visits/' + v.id, { method: 'POST', body: { status: st, notify: !!notify } }).then(function () { toast(msg); S.visitsToday = null; viewVisit(v.id); }).catch(fail);
      };
      var b;
      if ((b = document.getElementById('vdone'))) b.addEventListener('click', function () { setStatus('done', 'Merkitty tehdyksi'); });
      if ((b = document.getElementById('vreopen'))) b.addEventListener('click', function () { setStatus('planned', 'Palautettu'); });
      if ((b = document.getElementById('vcancel'))) b.addEventListener('click', function () {
        if (confirm('Perutaanko käynti? Asiakas saa ilmoituksen sähköpostilla.')) setStatus('cancelled', 'Käynti peruttu', true);
      });
      document.getElementById('vedit').addEventListener('click', function () { visitForm({}, v); });
      main.querySelectorAll('input[type=file][data-kind]').forEach(function (inp) {
        inp.addEventListener('change', function () { uploadPhotos(v.id, inp.getAttribute('data-kind'), inp.files); });
      });
      if (document.getElementById('proto')) renderProtocol(v);
      if (document.getElementById('hand')) renderHandover(v);
    }).catch(function (e) { main.innerHTML = '<div class="empty">' + esc(e.message) + '</div>'; });
  }

  /* Kuvat pienennetään puhelimessa (enintään 1600 px), jotta lataus on nopea myös mobiiliverkossa. */
  function shrink(file) {
    if (!window.createImageBitmap || !/^image\/(jpeg|png|webp|heic|heif)/.test(file.type)) return Promise.resolve(file);
    return createImageBitmap(file).then(function (bmp) {
      var k = Math.min(1, 1600 / Math.max(bmp.width, bmp.height));
      var c = document.createElement('canvas'); c.width = Math.round(bmp.width * k); c.height = Math.round(bmp.height * k);
      c.getContext('2d').drawImage(bmp, 0, 0, c.width, c.height);
      return new Promise(function (res) { c.toBlob(function (bl) { res(bl || file); }, 'image/jpeg', 0.82); });
    }).catch(function () { return file; });
  }
  function uploadPhotos(id, kind, list) {
    var files = Array.prototype.slice.call(list || []); if (!files.length) return;
    var st = document.getElementById('upst'); st.hidden = false;
    var i = 0;
    var next = function () {
      if (i >= files.length) { toast(files.length === 1 ? 'Kuva tallennettu' : files.length + ' kuvaa tallennettu'); viewVisit(id); return; }
      st.textContent = 'Tallennetaan kuvaa ' + (i + 1) + '/' + files.length + '…';
      shrink(files[i]).then(function (blob) {
        var fd = new FormData(); fd.append('kind', kind); fd.append('file', blob, (files[i].name || 'kuva').replace(/\.\w+$/, '') + '.jpg');
        return api('visits/' + id + '/files', { method: 'POST', form: fd });
      }).then(function () { i++; next(); }).catch(function (e) { fail(e); viewVisit(id); });
    };
    next();
  }

  /* ---------- Mittauspöytäkirja ---------- */
  var OPEN = ['Ikkuna', 'Ulko-ovi', 'Parvekeovi', 'Terassiovi', 'Muu'];
  function renderProtocol(v) {
    var box = document.getElementById('proto');
    var p = v.protocol || { rows: [], seina: '', poisto: '', lisatiedot: '' };
    var rows = p.rows.length ? p.rows.slice() : [{ tila: '', tyyppi: 'Ikkuna', leveys: '', korkeus: '', kpl: 1, huomio: '' }];
    var draw = function () {
      box.innerHTML = '<div style="display:flex;justify-content:space-between;align-items:center;gap:8px;flex-wrap:wrap"><h2 style="margin:0">Mittauspöytäkirja</h2>' +
        (v.protocol ? '<span class="small muted">Tallennettu ' + esc(fdate(v.protocol.paivitetty)) + ' · ' + esc(v.protocol.tekija) + '</span>' : '') + '</div>' +
        '<div class="prows">' + rows.map(function (r, i) {
          return '<div class="prow" data-i="' + i + '"><span class="pn">' + (i + 1) + '</span>' +
            '<label class="fld">Tila<input data-f="tila" value="' + esc(r.tila) + '" placeholder="esim. olohuone"></label>' +
            '<label class="fld">Tyyppi<select data-f="tyyppi">' + OPEN.map(function (o) { return '<option' + (o === r.tyyppi ? ' selected' : '') + '>' + o + '</option>'; }).join('') + '</select></label>' +
            '<label class="fld">Leveys mm<input data-f="leveys" type="number" inputmode="numeric" min="0" value="' + (r.leveys || '') + '"></label>' +
            '<label class="fld">Korkeus mm<input data-f="korkeus" type="number" inputmode="numeric" min="0" value="' + (r.korkeus || '') + '"></label>' +
            '<label class="fld">Kpl<input data-f="kpl" type="number" inputmode="numeric" min="1" value="' + (r.kpl || 1) + '"></label>' +
            '<label class="fld pw">Huomio<input data-f="huomio" value="' + esc(r.huomio) + '" placeholder="esim. aukeaa vasemmalle"></label>' +
            '<button type="button" class="linkbtn small" data-del="' + i + '" aria-label="Poista rivi">Poista</button></div>';
        }).join('') + '</div>' +
        '<button type="button" class="btn sm" id="padd">+ Lisää rivi</button>' +
        '<div class="fields" style="margin-top:12px"><label class="fld">Seinärakenne / karmisyvyys<input id="pseina" value="' + esc(p.seina) + '"></label>' +
        '<label class="fld">Vanhojen poisto<input id="ppoisto" value="' + esc(p.poisto) + '" placeholder="esim. kyllä, 9 ikkunaa"></label>' +
        '<label class="fld wide">Lisätiedot<textarea id="plisa" rows="2">' + esc(p.lisatiedot) + '</textarea></label></div>' +
        '<p class="small muted">Kuvat mittauksesta lisätään alla kohtaan <b>Mittaus</b>.</p>' +
        '<div class="actions"><button class="btn p" id="psave">Tallenna pöytäkirja</button>' + (v.protocol ? '<button class="btn" id="pprint">Tulosta / PDF</button>' : '') + '</div>';
      box.querySelectorAll('[data-f]').forEach(function (inp) {
        inp.addEventListener('input', function () { rows[+inp.closest('.prow').getAttribute('data-i')][inp.getAttribute('data-f')] = inp.value; });
        inp.addEventListener('change', function () { rows[+inp.closest('.prow').getAttribute('data-i')][inp.getAttribute('data-f')] = inp.value; });
      });
      box.querySelectorAll('[data-del]').forEach(function (b) { b.addEventListener('click', function () { rows.splice(+b.getAttribute('data-del'), 1); if (!rows.length) rows.push({ tila: '', tyyppi: 'Ikkuna', kpl: 1 }); draw(); }); });
      document.getElementById('padd').addEventListener('click', function () { var last = rows[rows.length - 1] || {}; rows.push({ tila: '', tyyppi: last.tyyppi || 'Ikkuna', leveys: '', korkeus: '', kpl: 1, huomio: '' }); draw(); var r = box.querySelectorAll('.prow'); r[r.length - 1].querySelector('input').focus(); });
      document.getElementById('psave').addEventListener('click', function (e) {
        e.target.disabled = true;
        var body = { protocol: { rows: rows, seina: document.getElementById('pseina').value, poisto: document.getElementById('ppoisto').value, lisatiedot: document.getElementById('plisa').value } };
        api('visits/' + v.id, { method: 'POST', body: body }).then(function (nv) { v.protocol = nv.protocol; rows = nv.protocol.rows.length ? nv.protocol.rows.slice() : rows; toast('Mittauspöytäkirja tallennettu'); draw(); })
          .catch(function (err) { e.target.disabled = false; fail(err); });
      });
      var pr = document.getElementById('pprint');
      if (pr) pr.addEventListener('click', function () { printProtocol(v); });
    };
    draw();
  }
  function printProtocol(v) {
    var p = v.protocol, w = window.open('', '_blank'); if (!w) { toast('Salli ponnahdusikkunat tulostusta varten', true); return; }
    var total = p.rows.reduce(function (a, r) { return a + (+r.kpl || 1); }, 0);
    w.document.write('<!doctype html><html lang="fi"><head><meta charset="utf-8"><title>Mittauspöytäkirja – ' + esc(v.customer) + '</title><style>body{font-family:Arial,sans-serif;margin:28px;color:#16303B}h1{font-size:20px;margin:0 0 4px}table{border-collapse:collapse;width:100%;margin:14px 0;font-size:13px}th,td{border:1px solid #c9d4d9;padding:6px 8px;text-align:left}th{background:#eef3f5}.m{color:#556;font-size:13px}</style></head><body>' +
      '<h1>Mittauspöytäkirja</h1><div class="m">Nordic Ikkunat &amp; Ovet Oy · ' + esc(when(v)) + ' · ' + esc(p.tekija) + '</div>' +
      '<p><b>' + esc(v.customer) + '</b><br>' + esc(v.address) + '<br>' + esc(v.title) + '</p>' +
      '<table><thead><tr><th>#</th><th>Tila</th><th>Tyyppi</th><th>Leveys mm</th><th>Korkeus mm</th><th>Kpl</th><th>Huomio</th></tr></thead><tbody>' +
      p.rows.map(function (r, i) { return '<tr><td>' + (i + 1) + '</td><td>' + esc(r.tila) + '</td><td>' + esc(r.tyyppi) + '</td><td>' + (r.leveys || '') + '</td><td>' + (r.korkeus || '') + '</td><td>' + r.kpl + '</td><td>' + esc(r.huomio) + '</td></tr>'; }).join('') +
      '</tbody><tfoot><tr><th colspan="5">Yhteensä</th><th>' + total + '</th><th></th></tr></tfoot></table>' +
      (p.seina ? '<p><b>Seinärakenne / karmisyvyys:</b> ' + esc(p.seina) + '</p>' : '') + (p.poisto ? '<p><b>Vanhojen poisto:</b> ' + esc(p.poisto) + '</p>' : '') +
      (p.lisatiedot ? '<p><b>Lisätiedot:</b><br>' + esc(p.lisatiedot).replace(/\n/g, '<br>') + '</p>' : '') + '<script>window.onload=function(){window.print()}<\/script></body></html>');
    w.document.close();
  }

  /* ---------- Luovutuskuittaus ---------- */
  function handoverItems(delivery) {
    return delivery === 'asennus'
      ? ['Ikkunat ja ovet asennettu ja säädetty', 'Toiminta käyty läpi asiakkaan kanssa', 'Työmaa siivottu', 'Vanhat ikkunat ja ovet viety', 'Käyttö- ja huolto-ohjeet luovutettu']
      : ['Tuotteet toimitettu sovitusti', 'Tuotteet tarkastettu vaurioiden varalta', 'Käyttö- ja huolto-ohjeet luovutettu'];
  }
  function renderHandover(v) {
    var box = document.getElementById('hand'), h = v.handover;
    if (h) {
      box.innerHTML = '<h2>Luovutus kuitattu ✓</h2><p class="muted small" style="margin:0 0 10px">' + esc(fdate(h.at)) + ' klo ' + esc(hm(h.at)) + ' · kuittaaja ' + esc(h.name) + ' · kirjasi ' + esc(h.by) + '</p>' +
        '<ul class="hcheck">' + h.items.map(function (i) { return '<li class="' + (h.checklist.indexOf(i) >= 0 ? 'y' : 'n') + '">' + esc(i) + '</li>'; }).join('') + '</ul>' +
        (h.remarks ? '<div class="vnote"><b>Huomautukset:</b> ' + esc(h.remarks) + '</div>' : '') +
        '<img class="sigimg" alt="Asiakkaan allekirjoitus" src="' + esc(location.pathname + '?tiedosto=' + h.file) + '">';
      return;
    }
    var items = handoverItems(v.delivery);
    box.innerHTML = '<h2>Luovutus ja kuittaus</h2><p class="small muted" style="margin:0 0 10px">Käy työ läpi asiakkaan kanssa. Asiakas kuittaa sormella ruutuun, ja hän saa kuittauksesta yhteenvedon sähköpostiin.</p>' +
      '<div class="choices">' + items.map(function (i, n) { return '<label><input type="checkbox" class="hc" value="' + esc(i) + '" checked><span>' + esc(i) + '</span></label>'; }).join('') + '</div>' +
      '<label class="fld wide" style="margin-top:12px">Huomautukset tai puutteet (asiakkaan kanssa todettu)<textarea id="hrem" rows="2" placeholder="Jätä tyhjäksi, jos kaikki on kunnossa"></textarea></label>' +
      '<label class="fld" style="margin-top:10px">Kuittaajan nimi<input id="hname" value="' + esc(v.customer) + '" autocomplete="off"></label>' +
      '<div class="sigbox"><canvas id="sig" aria-label="Allekirjoitusalue"></canvas><span class="sigph" id="sigph">Allekirjoita tähän sormella</span><button type="button" class="linkbtn small" id="sigclr">Tyhjennä</button></div>' +
      '<button class="btn p big" id="hsave">Asiakas kuittaa työn vastaanotetuksi</button>';
    var cv = document.getElementById('sig'), ctx = cv.getContext('2d'), ink = 0, drawing = false, last = null;
    var size = function () {
      var r = cv.getBoundingClientRect(), k = window.devicePixelRatio || 1;
      cv.width = Math.round(r.width * k); cv.height = Math.round(r.height * k);
      ctx.setTransform(k, 0, 0, k, 0, 0); ctx.fillStyle = '#fff'; ctx.fillRect(0, 0, r.width, r.height);
      ctx.lineWidth = 2.4; ctx.lineCap = 'round'; ctx.lineJoin = 'round'; ctx.strokeStyle = '#102530'; ink = 0;
      document.getElementById('sigph').hidden = false;
    };
    size();
    var pos = function (e) { var r = cv.getBoundingClientRect(); return [e.clientX - r.left, e.clientY - r.top]; };
    cv.addEventListener('pointerdown', function (e) { drawing = true; last = pos(e); cv.setPointerCapture(e.pointerId); document.getElementById('sigph').hidden = true; e.preventDefault(); });
    cv.addEventListener('pointermove', function (e) {
      if (!drawing) return; var p = pos(e);
      ctx.beginPath(); ctx.moveTo(last[0], last[1]); ctx.lineTo(p[0], p[1]); ctx.stroke();
      ink += Math.abs(p[0] - last[0]) + Math.abs(p[1] - last[1]); last = p; e.preventDefault();
    });
    ['pointerup', 'pointercancel', 'pointerleave'].forEach(function (ev) { cv.addEventListener(ev, function () { drawing = false; }); });
    document.getElementById('sigclr').addEventListener('click', size);
    document.getElementById('hsave').addEventListener('click', function (e) {
      if (ink < 60) { toast('Pyydä asiakasta allekirjoittamaan ruutuun', true); return; }
      var name = document.getElementById('hname').value.trim();
      if (!name) { toast('Kirjoita kuittaajan nimi', true); return; }
      e.target.disabled = true;
      api('visits/' + v.id + '/handover', { method: 'POST', body: {
        name: name, remarks: document.getElementById('hrem').value,
        checklist: Array.prototype.map.call(box.querySelectorAll('.hc:checked'), function (c) { return c.value; }),
        signature: cv.toDataURL('image/png')
      } }).then(function () { toast('Kuittaus tallennettu – kohde merkitty valmiiksi'); S.visitsToday = null; V.reloadBoard().then(function () { viewVisit(v.id); }); })
        .catch(function (err) { e.target.disabled = false; fail(err); });
    });
  }

  /* ---------- Oma päivä: tämän päivän käynnit ---------- */
  function todayBlock() {
    var mine = (S.visitsToday || []).filter(function (v) { return v.installer_id === S.me.id && v.status !== 'cancelled'; });
    if (!mine.length) return '';
    return '<div class="card" style="margin-bottom:14px"><h2>Tänään <span class="r muted">' + mine.length + ' käyntiä</span></h2>' + mine.map(function (v) {
      return '<div class="vtoday"><a href="#kaynti-' + v.id + '" class="vt-main"><b>' + (v.all_day ? 'Koko pv' : hm(v.start_at)) + ' · ' + esc(v.label) + (v.status === 'done' ? ' ✓' : '') + '</b><span>' + esc(v.customer) + '</span><span class="muted small">' + esc(v.address) + '</span></a>' +
        (v.address ? '<a class="btn sm" href="' + esc(mapUrl(v.address)) + '" target="_blank" rel="noopener">🧭</a>' : '') + (v.phone ? '<a class="btn sm" href="' + esc(tel(v.phone)) + '">📞</a>' : '') + '</div>';
    }).join('') + '</div>';
  }
  function loadToday() {
    return api('visits?from=' + S.today + '&to=' + S.today).then(function (l) { S.visitsToday = l; return l; });
  }

  /* ---------- Asiakaskortin käynnit ---------- */
  function cardBlock(c) {
    var vs = c.visits || [];
    return '<div class="sect"><h3>Käynnit</h3>' + (vs.length ? vs.map(function (v) {
      return '<a class="vline' + (v.status === 'cancelled' ? ' off' : '') + '" href="#kaynti-' + v.id + '"><span class="vchip t-' + v.type + '">' + esc(v.label) + '</span><span>' + esc(when(v)) + (v.installer_id ? ' · ' + esc(V.uname(v.installer_id)) : '') + '</span>' +
        '<span class="muted small">' + (v.status === 'done' ? 'Tehty' + (v.handover ? ', kuitattu' : '') : v.status === 'cancelled' ? 'Peruttu' : (v.confirmed ? 'Vahvistettu' : 'Ei vahvistusta')) + '</span></a>';
    }).join('') : '<p class="muted small">Ei käyntejä.</p>') +
      (c.projects.length ? '<button class="btn sm" id="vbook" style="margin-top:8px">+ Varaa käynti</button>' : '') + '</div>';
  }
  function bindCard(c) {
    var b = document.getElementById('vbook');
    if (b) b.addEventListener('click', function () {
      var p = c.projects[0];
      var type = p.stage < 2 ? (p.delivery === 'omat_mitat' ? 'toimitus' : 'mittaus') : (p.delivery === 'asennus' ? 'asennus' : 'toimitus');
      visitForm({ project_id: p.id, type: type, back: 'asiakas-' + c.id });
    });
  }

  window.NAPV.visits = { viewCalendar: viewCalendar, viewVisit: viewVisit, visitForm: visitForm, todayBlock: todayBlock, loadToday: loadToday, cardBlock: cardBlock, bindCard: bindCard, monday: monday };
})();
