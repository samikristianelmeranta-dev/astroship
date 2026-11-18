/* Nordic-laskuri: tarjouslaskuri CRM:n sisällä. Tuottaa erittelyn, kotitalousvähennysarvion ja PDF:n.
   Käyttö: var L = window.NAPLaskuri.mount(elementti, { state, asiakas, kohde, numero, laskija, jspdf }); */
(function () {
  'use strict';
  var ROWS = [
    { id: 'tuote-ikk', name: 'Ikkunat', unit: 'kpl', from: 'ikkunat', product: true, group: 'Tuotteet ja asennus' },
    { id: 'asennus-ikk', name: 'Ikkunoiden asennus', unit: 'kpl', from: 'ikkunat' },
    { id: 'tuote-ovi', name: 'Ovet', unit: 'kpl', from: 'ovet', product: true },
    { id: 'asennus-ovi', name: 'Ovien asennus', unit: 'kpl', from: 'ovet' },
    { id: 'tiiv-ikk', name: 'Ikkunoiden tiivistys', unit: 'kpl', from: 'ikkunat', group: 'Huolto ja korjaus' },
    { id: 'tiiv-ovi', name: 'Ovien tiivistys', unit: 'kpl', from: 'ovet' },
    { id: 'saato-ikk', name: 'Ikkunoiden säätö', unit: 'kpl', from: 'ikkunat' },
    { id: 'saato-ovi', name: 'Ovien säätö', unit: 'kpl', from: 'ovet' },
    { id: 'uretaani', name: 'Uretaanien vaihto', unit: 'kpl', from: 'ikkunat' },
    { id: 'maalaus', name: 'Puitteiden maalaus', unit: 'kpl', from: 'ikkunat' },
    { id: 'pellitys', name: 'Pellitys', unit: 'kpl', from: 'ikkunat' },
    { id: 'listoitus', name: 'Listoitus', unit: 'kpl', from: 'ikkunat' },
    { id: 'korjaus', name: 'Ikkunan / oven korjaustyöt', unit: 'kpl' },
    { id: 'tarvikkeet', name: 'Tarvikkeet', unit: 'erä', product: true, ph: 'tarvike' },
    { id: 'tunti', name: 'Tuntityö', unit: 'h', price: 48 }
  ];
  var UNITS = ['kpl', 'm', 'm²', 'h', 'erä'];
  var KEY = 'nap-laskuri-hinnat-v1';
  var COMPANY = 'Nordic Ikkunat & Ovet Oy · Y-tunnus 3653098-6 · 045 7832 5070 · info@ikkunakauppias.fi · ikkunakauppias.fi';
  var KTV_NOTE = 'Arvio: 35 % työn osuudesta, omavastuu 150 € ja enintään 1 600 € henkilöä kohden vuodessa. Lopullinen kotitalousvähennys määräytyy verottajan ohjeen mukaisesti.';
  var MATERIALS_NOTE = 'Asennusten ja työvaiheiden hinnat sisältävät tarvikkeet.';
  var seq = 0;

  function num(v) { var n = parseFloat(String(v).replace(/\s/g, '').replace(',', '.')); return isFinite(n) && n > 0 ? n : 0; }
  function eur(n) { return n.toLocaleString('fi-FI', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' €'; }
  function qtyText(n) { return n.toLocaleString('fi-FI', { maximumFractionDigits: 2 }); }
  function esc(s) { return String(s).replace(/[&<>"]/g, function (c) { return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[c]; }); }
  function str(v) { return typeof v === 'string' ? v : (typeof v === 'number' ? String(v) : ''); }
  function loadPrices() { try { return JSON.parse(localStorage.getItem(KEY) || '{}') || {}; } catch (e) { return {}; } }
  // jsPDF:n vakiofontti tuntee vain länsieurooppalaisen merkistön.
  function pdfSafe(s) { return String(s).replace(/[   ]/g, ' ').replace(/−/g, '-').replace(/[^\x09\x0a\x20-\x7e¡-ÿ€–—…’”]/g, '?'); }

  var jspdfLoading = null;
  function loadJsPdf(url) {
    if (window.jspdf) { return Promise.resolve(); }
    if (!jspdfLoading) {
      jspdfLoading = new Promise(function (ok, bad) {
        var s = document.createElement('script'); s.src = url; s.onload = function () { ok(); };
        s.onerror = function () { jspdfLoading = null; bad(new Error('PDF-kirjaston lataus epäonnistui.')); };
        document.head.appendChild(s);
      });
    }
    return jspdfLoading;
  }

  function mount(root, opts) {
    opts = opts || {};
    var uid = 'lk' + (++seq) + '-';
    var rows = [], customSeq = 0, listeners = [];
    var saved = loadPrices();
    function q(name) { return root.querySelector('[data-lk="' + name + '"]'); }

    root.classList.add('lk');
    root.innerHTML =
      '<div class="lk-fields">' +
        fld('laskija', 'Tarjouksen laskija / asiantuntija') + fld('numero', 'Tarjousnumero') +
        fld('asiakas', 'Asiakas') + fld('kohde', 'Kohde / osoite') +
        fld('ikkunat', 'Ikkunoita, kpl', 'num') + fld('ovet', 'Ovia, kpl', 'num') +
      '</div>' +
      '<p class="lk-hint">Hinta / yks. on asiakkaan hinta tarvikkeineen. Kohtaan "Josta tarvikkeet" kirjoitetaan, paljonko yksikköhinnasta on tarvikkeita. Loppu lasketaan työksi, josta kotitalousvähennys saadaan. Ikkunat, ovet ja Tarvikkeet-rivi ovat tuotteita, joista vähennystä ei saa.</p>' +
      '<div class="lk-rows" data-lk="rows"><div class="lk-row lk-head" aria-hidden="true"><span></span><span>Toimenpide</span><span class="r">Määrä</span><span>Yks.</span><span class="r">Hinta / yks.</span><span class="r">Josta tarvikkeet</span><span class="r">Yhteensä</span><span></span></div></div>' +
      '<div><button type="button" class="btn sm" data-lk="add">+ Lisää muu työ</button></div>' +
      '<div class="lk-fields">' +
        '<label class="fld" for="' + uid + 'alvmode">Syötetyt hinnat ovat<select id="' + uid + 'alvmode" data-lk="alvmode"><option value="incl">verollisia (sis. ALV)</option><option value="excl">verottomia (ALV 0 %)</option></select></label>' +
        fld('alv', 'ALV-%', 'num') +
        '<label class="fld" for="' + uid + 'hakijat">Kotitalousvähennyksen hakijoita<select id="' + uid + 'hakijat" data-lk="hakijat"><option value="1">1 henkilö</option><option value="2">2 henkilöä (esim. puolisot)</option></select></label>' +
        fld('muut', 'Matkakulut ja muut vähennykseen kuulumattomat, € (sis. ALV)', 'num') +
      '</div>' +
      '<label class="lk-check"><input type="checkbox" data-lk="ktvon" checked> Näytä arvio kotitalousvähennyksestä tarjouksessa</label>' +
      '<label class="fld" for="' + uid + 'lisatiedot">Lisätiedot tarjoukseen (esim. aikataulu ja ehdot)<textarea id="' + uid + 'lisatiedot" data-lk="lisatiedot" rows="3"></textarea></label>' +
      '<div class="lk-sum" data-lk="summary" aria-live="polite"></div>';
    function fld(name, label, cls) {
      return '<label class="fld" for="' + uid + name + '">' + label + '<input type="text" id="' + uid + name + '" data-lk="' + name + '"' + (cls ? ' class="' + cls + '" inputmode="decimal"' : '') + ' autocomplete="off"></label>';
    }
    var rowsEl = q('rows');

    function addRow(def) {
      var custom = !def.id, id = def.id || 'muu-' + (++customSeq), sv = saved[id];
      if (def.group) { var g = document.createElement('div'); g.className = 'lk-row lk-group'; g.textContent = def.group; rowsEl.appendChild(g); }
      var el = document.createElement('div'); el.className = 'lk-row';
      el.innerHTML =
        '<input type="checkbox" id="' + uid + 'on-' + id + '" aria-label="Valitse tarjoukseen">' +
        (custom ? '<input type="text" class="lk-name" placeholder="Muu työ, kuvaus" aria-label="Työn kuvaus">' : '<label class="lk-name" for="' + uid + 'on-' + id + '"></label>') +
        '<div class="lk-cell"><span class="lk-m">Määrä</span><input type="text" class="num" inputmode="decimal" data-c="qty" aria-label="Määrä"></div>' +
        '<div class="lk-cell"><span class="lk-m">Yksikkö</span><select data-c="unit" aria-label="Yksikkö">' + UNITS.map(function (u) { return '<option>' + u + '</option>'; }).join('') + '</select></div>' +
        '<div class="lk-cell"><span class="lk-m">Hinta / yks.</span><input type="text" class="num" inputmode="decimal" data-c="price" aria-label="Hinta per yksikkö, euroa"></div>' +
        '<div class="lk-cell"><span class="lk-m">Josta tarvikkeet</span><input type="text" class="num" inputmode="decimal" data-c="mat" aria-label="Josta tarvikkeita per yksikkö, euroa"></div>' +
        '<div class="lk-total"></div>' +
        (custom ? '<button type="button" class="lk-del" aria-label="Poista rivi">×</button>' : '<span></span>');
      rowsEl.appendChild(el);
      var r = { id: id, custom: custom, el: el, from: def.from, product: !!def.product, label: def.name,
        on: el.querySelector('input[type="checkbox"]'), nameEl: custom ? el.querySelector('input.lk-name') : null,
        qty: el.querySelector('[data-c="qty"]'), unit: el.querySelector('[data-c="unit"]'), price: el.querySelector('[data-c="price"]'),
        mat: el.querySelector('[data-c="mat"]'), total: el.querySelector('.lk-total') };
      if (!custom) { el.querySelector('label.lk-name').textContent = def.name; }
      r.unit.value = (sv && sv.unit) || def.unit || 'kpl';
      r.price.value = sv && sv.price ? sv.price : (def.price !== undefined ? String(def.price) : '');
      r.mat.value = sv && sv.mat && !r.product ? sv.mat : '';
      if (r.product) { r.mat.disabled = true; r.mat.placeholder = def.ph || 'tuote'; }
      r.on.addEventListener('change', function () {
        if (r.on.checked && r.qty.value === '' && r.from) { var c = q(r.from).value; if (num(c)) { r.qty.value = c; } }
        update();
      });
      [r.qty, r.price, r.unit, r.mat].forEach(function (i) {
        i.addEventListener('input', function () { if ((i === r.qty || i === r.price) && i.value !== '' && !r.on.checked) { r.on.checked = true; } update(); });
        i.addEventListener('change', update);
      });
      if (custom) {
        r.nameEl.addEventListener('input', update);
        el.querySelector('.lk-del').addEventListener('click', function () { removeRow(r); update(); });
      }
      rows.push(r);
      return r;
    }
    function removeRow(r) { rowsEl.removeChild(r.el); rows = rows.filter(function (x) { return x !== r; }); }

    function compute() {
      var items = rows.filter(function (r) { return r.on.checked; }).map(function (r) {
        var qn = num(r.qty.value), p = num(r.price.value), m = r.product ? p : Math.min(num(r.mat.value), p);
        return { name: r.custom ? (r.nameEl.value.trim() || 'Muu työ') : r.label, qty: qn, unit: r.unit.value, price: p, total: qn * p, matTotal: qn * m, product: r.product };
      });
      var total = items.reduce(function (s, i) { return s + i.total; }, 0);
      var rate = num(q('alv').value) / 100, incl = q('alvmode').value === 'incl';
      var net = incl ? total / (1 + rate) : total, gross = incl ? total : total * (1 + rate);
      var mats = items.reduce(function (s, i) { return s + i.matTotal; }, 0);
      var materials = incl ? mats : mats * (1 + rate), other = num(q('muut').value);
      var work = Math.max(0, gross - materials - other), kv = null;
      if (q('ktvon').checked && items.length && gross) {
        var n = parseInt(q('hakijat').value, 10) || 1, per = Math.min(1600, Math.max(0, 0.35 * (work / n) - 150));
        kv = { n: n, ded: Math.round(per * n * 100) / 100 };
      }
      return { items: items, rate: rate, net: net, gross: gross, vat: gross - net, materials: materials, other: other, work: work, kv: kv,
        laskija: q('laskija').value.trim(), numero: q('numero').value.trim(), asiakas: q('asiakas').value.trim(), kohde: q('kohde').value.trim(),
        ikkunat: num(q('ikkunat').value), ovet: num(q('ovet').value), lisatiedot: q('lisatiedot').value.trim(), date: new Date().toLocaleDateString('fi-FI') };
    }
    function ktvLabel(kv) { return 'Arvio kotitalousvähennyksestä' + (kv.n > 1 ? ' (' + kv.n + ' hakijaa)' : ''); }
    function siteLine(c) { return [c.ikkunat ? c.ikkunat + ' ikkunaa' : '', c.ovet ? c.ovet + ' ovea' : ''].filter(Boolean).join(', '); }

    function update() {
      var p = {};
      rows.forEach(function (r) {
        var t = num(r.qty.value) * num(r.price.value);
        r.el.classList.toggle('on', r.on.checked); r.total.textContent = t ? eur(t) : '–';
        if (!r.custom) { p[r.id] = { price: r.price.value, unit: r.unit.value, mat: r.mat.value }; }
      });
      try { localStorage.setItem(KEY, JSON.stringify(p)); } catch (e) {}
      var c = compute(), h;
      if (!c.items.length) {
        h = '<p class="lk-hint">Rastita toimenpiteet, jotka kuuluvat tarjoukseen. Erittelyssä näkyvät vain valitut rivit.</p>';
      } else {
        h = '<h4>Tarjouksen erittely</h4><table>';
        c.items.forEach(function (i) {
          h += '<tr><td>' + esc(i.name) + '<span class="d">' + qtyText(i.qty) + ' ' + esc(i.unit) + ' × ' + eur(i.price) + (i.matTotal && !i.product ? ', josta tarvikkeet ' + eur(i.matTotal) : '') + '</span></td><td>' + eur(i.total) + '</td></tr>';
        });
        h += '<tr class="s"><td>Veroton hinta</td><td>' + eur(c.net) + '</td></tr><tr class="s"><td>ALV ' + qtyText(c.rate * 100) + ' %</td><td>' + eur(c.vat) + '</td></tr>' +
          '<tr class="t"><td>Yhteensä</td><td>' + eur(c.gross) + '</td></tr></table><p class="lk-hint">' + MATERIALS_NOTE + '</p>';
        if (c.kv) {
          h += '<div class="lk-ktv"><div><span>Tuotteiden ja tarvikkeiden osuus</span><span>' + eur(c.materials) + '</span></div>' +
            (c.other ? '<div><span>Matka- ja muut kulut</span><span>' + eur(c.other) + '</span></div>' : '') +
            '<div><span>Työn osuus</span><span>' + eur(c.work) + '</span></div>' +
            '<div class="b"><span>' + ktvLabel(c.kv) + '</span><span>' + eur(c.kv.ded) + '</span></div>' +
            '<div class="b"><span>Hinta vähennyksen jälkeen</span><span>' + eur(c.gross - c.kv.ded) + '</span></div><small>' + KTV_NOTE + '</small></div>';
        }
      }
      q('summary').innerHTML = h;
      listeners.forEach(function (f) { f(c); });
    }

    function getState() {
      var s = { rows: rows.map(function (r) { return { id: r.custom ? '' : r.id, name: r.custom ? r.nameEl.value : '', on: r.on.checked, qty: r.qty.value, unit: r.unit.value, price: r.price.value, mat: r.mat.value }; }) };
      ['laskija', 'numero', 'asiakas', 'kohde', 'ikkunat', 'ovet', 'alvmode', 'alv', 'hakijat', 'muut', 'lisatiedot'].forEach(function (k) { s[k] = q(k).value; });
      s.ktvon = q('ktvon').checked;
      return s;
    }
    function setState(s) {
      s = s && typeof s === 'object' ? s : {};
      rows.filter(function (r) { return r.custom; }).forEach(removeRow);
      ['laskija', 'numero', 'asiakas', 'kohde', 'ikkunat', 'ovet', 'muut', 'lisatiedot'].forEach(function (k) { q(k).value = str(s[k]); });
      q('alvmode').value = s.alvmode === 'excl' ? 'excl' : 'incl'; q('alv').value = str(s.alv) || '25,5';
      q('hakijat').value = s.hakijat === '2' ? '2' : '1'; q('ktvon').checked = s.ktvon !== false;
      rows.forEach(function (r) { r.on.checked = false; r.qty.value = ''; });
      (Array.isArray(s.rows) ? s.rows : []).slice(0, 60).forEach(function (d) {
        if (!d || typeof d !== 'object') { return; }
        var r = d.id ? rows.filter(function (x) { return x.id === d.id; })[0] : addRow({ unit: 'kpl' });
        if (!r) { return; }
        if (r.custom) { r.nameEl.value = str(d.name); }
        r.on.checked = d.on === true; r.qty.value = str(d.qty); r.price.value = str(d.price); r.mat.value = r.product ? '' : str(d.mat);
        if (UNITS.indexOf(d.unit) !== -1) { r.unit.value = d.unit; }
      });
      update();
    }

    function buildPdf(c, extra) {
      extra = extra || {};
      var doc = new window.jspdf.jsPDF({ unit: 'mm', format: 'a4' });
      var W = 210, H = 297, L = 18, R = W - 18, y = 0;
      var T = function (s, x, yy, o) { doc.text(pdfSafe(s), x, yy, o || {}); };
      function footer() {
        doc.setFont('helvetica', 'normal'); doc.setFontSize(8); doc.setTextColor(90, 105, 110);
        doc.setDrawColor(203, 217, 220); doc.line(L, H - 16, R, H - 16); T(COMPANY, W / 2, H - 11, { align: 'center' });
      }
      function need(h) { if (y + h > H - 22) { footer(); doc.addPage(); y = 20; } }
      doc.setFillColor(22, 48, 59); doc.rect(0, 0, W, 30, 'F'); doc.setFillColor(255, 216, 102); doc.rect(0, 30, W, 1.6, 'F');
      doc.setFont('helvetica', 'bold'); doc.setFontSize(18); doc.setTextColor(255, 255, 255); T('Nordic ', L, 18.5);
      doc.setTextColor(255, 216, 102); T('Ikkunat & Ovet Oy', L + doc.getTextWidth('Nordic '), 18.5);
      doc.setTextColor(255, 255, 255); doc.setFontSize(16); T('TARJOUS', R, 16, { align: 'right' });
      doc.setFont('helvetica', 'normal'); doc.setFontSize(10); T((c.numero ? 'Nro ' + c.numero + '  ·  ' : '') + c.date, R, 23, { align: 'right' });

      y = 44; doc.setTextColor(22, 48, 59); doc.setFontSize(11);
      [['Asiakas', c.asiakas], ['Kohde', c.kohde], ['Kohteessa', siteLine(c)], ['Tarjouksen laatija', c.laskija], ['Voimassa', extra.validUntil ? extra.validUntil + ' asti' : '']].forEach(function (p) {
        if (!p[1]) { return; }
        doc.setFont('helvetica', 'bold'); T(p[0], L, y); doc.setFont('helvetica', 'normal');
        var lines = doc.splitTextToSize(pdfSafe(p[1]), R - L - 42); doc.text(lines, L + 42, y); y += 6 * lines.length;
      });
      y += 6;
      var cQty = 122, cPrice = 156;
      doc.setFillColor(235, 241, 243); doc.rect(L, y - 5.5, R - L, 8, 'F'); doc.setFont('helvetica', 'bold'); doc.setFontSize(9.5);
      T('Toimenpide', L + 2, y); T('Määrä', cQty, y, { align: 'right' }); T('Hinta / yks.', cPrice, y, { align: 'right' }); T('Yhteensä', R - 2, y, { align: 'right' });
      y += 8; doc.setFont('helvetica', 'normal'); doc.setFontSize(10.5);
      c.items.forEach(function (i) {
        var lines = doc.splitTextToSize(pdfSafe(i.name), 82); need(6 * lines.length + 3);
        doc.text(lines, L + 2, y); T(qtyText(i.qty) + ' ' + i.unit, cQty, y, { align: 'right' }); T(eur(i.price), cPrice, y, { align: 'right' }); T(eur(i.total), R - 2, y, { align: 'right' });
        y += 6 * lines.length; doc.setDrawColor(203, 217, 220); doc.line(L, y - 2.5, R, y - 2.5); y += 2.5;
      });
      need(40); y += 4; doc.setFontSize(10.5); doc.setTextColor(77, 99, 105);
      T('Veroton hinta', cPrice, y, { align: 'right' }); T(eur(c.net), R - 2, y, { align: 'right' }); y += 6;
      T('ALV ' + qtyText(c.rate * 100) + ' %', cPrice, y, { align: 'right' }); T(eur(c.vat), R - 2, y, { align: 'right' }); y += 4;
      doc.setDrawColor(22, 48, 59); doc.setLineWidth(0.5); doc.line(100, y, R, y); doc.setLineWidth(0.2); y += 8;
      doc.setTextColor(22, 48, 59); doc.setFont('helvetica', 'bold'); doc.setFontSize(15);
      T('Yhteensä', cPrice, y, { align: 'right' }); T(eur(c.gross), R - 2, y, { align: 'right' }); y += 7;
      doc.setFont('helvetica', 'normal'); doc.setFontSize(9.5); doc.setTextColor(77, 99, 105); T(MATERIALS_NOTE, R - 2, y, { align: 'right' }); y += 11;
      if (c.kv) {
        doc.setFontSize(8.5);
        var note = doc.splitTextToSize(pdfSafe(KTV_NOTE), R - L - 12), bh = 33.5 + (c.other ? 6.5 : 0) + note.length * 4;
        need(bh + 6);
        doc.setDrawColor(203, 217, 220); doc.rect(L, y, R - L, bh); doc.setFillColor(29, 94, 121); doc.rect(L, y, 1.6, bh, 'F');
        var by = y + 7; doc.setFontSize(10.5); doc.setTextColor(22, 48, 59);
        T('Tuotteiden ja tarvikkeiden osuus', L + 6, by); T(eur(c.materials), R - 4, by, { align: 'right' }); by += 6.5;
        if (c.other) { T('Matka- ja muut kulut', L + 6, by); T(eur(c.other), R - 4, by, { align: 'right' }); by += 6.5; }
        T('Työn osuus', L + 6, by); T(eur(c.work), R - 4, by, { align: 'right' }); by += 6.5;
        doc.setFont('helvetica', 'bold');
        T(ktvLabel(c.kv), L + 6, by); T(eur(c.kv.ded), R - 4, by, { align: 'right' }); by += 6.5;
        T('Hinta vähennyksen jälkeen', L + 6, by); T(eur(c.gross - c.kv.ded), R - 4, by, { align: 'right' }); by += 6;
        doc.setFont('helvetica', 'normal'); doc.setFontSize(8.5); doc.setTextColor(77, 99, 105); doc.text(note, L + 6, by);
        y += bh + 10;
      }
      if (c.lisatiedot) {
        doc.setTextColor(22, 48, 59); doc.setFont('helvetica', 'bold'); doc.setFontSize(10.5); need(14); T('Lisätiedot', L, y); y += 6;
        doc.setFont('helvetica', 'normal');
        doc.splitTextToSize(pdfSafe(c.lisatiedot), R - L).forEach(function (line) { need(6); doc.text(line, L, y); y += 5.5; });
      }
      footer();
      return doc.output('blob');
    }

    ROWS.forEach(addRow);
    q('add').addEventListener('click', function () { var r = addRow({ unit: 'kpl' }); r.on.checked = true; update(); r.nameEl.focus(); });
    ['laskija', 'numero', 'asiakas', 'kohde', 'ikkunat', 'ovet', 'alvmode', 'alv', 'hakijat', 'muut', 'ktvon', 'lisatiedot'].forEach(function (k) {
      q(k).addEventListener('input', update); q(k).addEventListener('change', update);
    });
    var init = opts.state && typeof opts.state === 'object' ? opts.state : {};
    setState(init);
    // Uusi tarjous: esitäytetään asiakas, kohde, laskija ja numero.
    ['asiakas', 'kohde', 'laskija', 'numero'].forEach(function (k) { if (!q(k).value && opts[k]) { q(k).value = opts[k]; } });
    if (opts.newNumber && opts.numero) { q('numero').value = opts.numero; }
    update();

    return {
      compute: compute, getState: getState, setState: setState,
      onChange: function (f) { listeners.push(f); f(compute()); },
      fileName: function () {
        var c = compute(), who = (c.asiakas || 'tarjous').toLowerCase().replace(/[äå]/g, 'a').replace(/ö/g, 'o').replace(/[^a-z0-9]+/g, '-').replace(/^-|-$/g, '').slice(0, 40) || 'tarjous';
        return 'nordic-tarjous-' + (c.numero ? c.numero.replace(/[^A-Za-z0-9]+/g, '-') + '-' : '') + who + '.pdf';
      },
      /** Palauttaa lupauksen PDF-tiedostosta (Blob). */
      pdf: function (extra) { var c = compute(); return loadJsPdf(opts.jspdf).then(function () { return buildPdf(c, extra); }); }
    };
  }

  window.NAPLaskuri = { mount: mount };
})();
