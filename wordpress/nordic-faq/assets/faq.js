/**
 * Nordic Kysy meiltä – UKK-chat (ei riippuvuuksia, ei ulkoisia palveluita).
 * Vastaukset haetaan selaimessa sivuston omista UKK-kysymyksistä
 * (faq-match.js). Aineisto ladataan vasta, kun ikkuna avataan ensimmäisen kerran.
 * Keskustelu säilyy välilehden ajan (sessionStorage).
 */
(function () {
  'use strict';
  var cfg = window.NordicFaq;
  var M = window.NordicFaqMatch;
  if (!cfg || !cfg.data || !M) return;

  var STORE = 'nordicFaq.v1';
  var state = load() || { log: [], open: false };
  var index = null, loading = null;

  function load() {
    try { return JSON.parse(sessionStorage.getItem(STORE)); } catch (e) { return null; }
  }
  function save() {
    state.log = state.log.slice(-30);
    try { sessionStorage.setItem(STORE, JSON.stringify(state)); } catch (e) { /* yksityinen tila tms. */ }
  }

  function esc(s) {
    return String(s).replace(/[&<>"']/g, function (c) {
      return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
    });
  }
  // Vastausteksti: kappaleet ja sähköpostiosoitteet linkeiksi
  function text(s) {
    return String(s).split(/\n+/).filter(function (p) { return p.trim(); }).map(function (p) {
      return '<p>' + esc(p).replace(/([A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[a-z]{2,})/g, '<a href="mailto:$1">$1</a>') + '</p>';
    }).join('');
  }
  function localUrl(u) {
    try { var x = new URL(u, location.href); return /^https?:$/.test(x.protocol) ? x.href : ''; } catch (e) { return ''; }
  }

  var ICON_DOC = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M6 3h8l4 4v14H6z M14 3v4h4 M9 12h6 M9 16h6" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/></svg>';

  /* ---------- Aineisto ---------- */
  function data() {
    if (index) return Promise.resolve(index);
    if (!loading) {
      loading = fetch(cfg.data, { credentials: 'omit' })
        .then(function (r) { if (!r.ok) throw new Error(r.status); return r.json(); })
        .then(function (d) { index = M.prepare(d); return index; })
        .catch(function (e) { loading = null; throw e; });
    }
    return loading;
  }

  /* ---------- DOM ---------- */
  var root = document.createElement('div');
  root.className = 'nfaq';
  root.innerHTML =
    '<button class="nfaq-launcher" type="button" aria-expanded="false" aria-controls="nfaq-panel">' +
      '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 5h16v11H8l-4 4z" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/><circle cx="9" cy="10.5" r="1.1" fill="currentColor"/><circle cx="12" cy="10.5" r="1.1" fill="currentColor"/><circle cx="15" cy="10.5" r="1.1" fill="currentColor"/></svg>' +
      '<span>Kysy meiltä</span>' +
    '</button>' +
    '<section class="nfaq-panel" id="nfaq-panel" role="dialog" aria-modal="false" aria-labelledby="nfaq-title" hidden>' +
      '<header class="nfaq-head">' +
        '<div class="nfaq-avatar" aria-hidden="true"><svg viewBox="0 0 24 24"><rect x="12" y="2" width="10" height="10" fill="#FFD866"/><rect x="2.6" y="2.6" width="18.8" height="18.8" fill="none" stroke="currentColor" stroke-width="1.8"/><line x1="12" y1="2" x2="12" y2="22" stroke="currentColor" stroke-width="1.6"/><line x1="2" y1="12" x2="22" y2="12" stroke="currentColor" stroke-width="1.6"/></svg></div>' +
        '<div class="nfaq-titles"><p class="nfaq-title" id="nfaq-title">Nordic Ikkunat &amp; Ovet</p><p class="nfaq-sub">Usein kysytyt kysymykset</p></div>' +
        '<button class="nfaq-close" type="button" aria-label="Sulje"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M6 6l12 12M18 6L6 18" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg></button>' +
      '</header>' +
      '<div class="nfaq-log" role="log" aria-live="polite" aria-relevant="additions"></div>' +
      '<div class="nfaq-chips"></div>' +
      '<form class="nfaq-form">' +
        '<label class="nfaq-sr" for="nfaq-input">Kirjoita kysymys</label>' +
        '<textarea id="nfaq-input" rows="1" maxlength="300" placeholder="Kirjoita kysymyksesi…" required></textarea>' +
        '<button class="nfaq-send" type="submit" aria-label="Kysy"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 12l16-8-6 16-2.5-6.5z" fill="currentColor"/></svg></button>' +
      '</form>' +
      '<p class="nfaq-note">Vastaukset sivustomme usein kysytyistä kysymyksistä · ' +
        '<a href="mailto:' + esc(cfg.email) + '">' + esc(cfg.email) + '</a></p>' +
    '</section>';
  document.body.appendChild(root);

  var launcher = root.querySelector('.nfaq-launcher');
  var panel = root.querySelector('.nfaq-panel');
  var logEl = root.querySelector('.nfaq-log');
  var chips = root.querySelector('.nfaq-chips');
  var form = root.querySelector('.nfaq-form');
  var input = root.querySelector('#nfaq-input');

  function scroll(el) {
    // Pitkä vastaus: näytetään vastauksen alku, ei loppu
    if (el && el.offsetHeight > logEl.clientHeight - 40) logEl.scrollTop = el.offsetTop - 12;
    else logEl.scrollTop = logEl.scrollHeight;
  }

  function add(html, cls) {
    var el = document.createElement('div');
    el.className = cls || 'nfaq-msg nfaq-bot';
    el.innerHTML = html;
    logEl.appendChild(el);
    return el;
  }

  function chipList(questions, label) {
    if (!questions || !questions.length) return null;
    if (label) add(esc(label), 'nfaq-label');
    var wrap = add('', 'nfaq-related');
    questions.forEach(function (q) {
      var b = document.createElement('button');
      b.type = 'button';
      b.className = 'nfaq-chip';
      b.textContent = q;
      b.addEventListener('click', function () { ask(q); });
      wrap.appendChild(b);
    });
    return wrap;
  }

  function quoteButton() {
    return '<a class="nfaq-btn nfaq-btn-primary" href="' + esc(cfg.contact) + '" data-quote>Pyydä ilmainen tarjous</a>';
  }

  // Yksi lokin rivi ruudulle. Palauttaa ensimmäisen lisätyn elementin.
  function draw(m) {
    if (m.type === 'user') {
      var u = document.createElement('div');
      u.className = 'nfaq-msg nfaq-user';
      u.textContent = m.text;
      logEl.appendChild(u);
      return u;
    }
    if (m.type === 'answer') {
      var src = localUrl(m.item.u);
      var el = add(
        '<p class="nfaq-q">' + esc(m.item.q) + '</p>' + text(m.item.a) +
        (src ? '<div class="nfaq-src">' + ICON_DOC + '<span>Lue lisää: <a href="' + esc(src) + '">' + esc(m.item.t) + '</a></span></div>' : '') +
        '<div class="nfaq-actions">' + quoteButton() + '</div>',
        'nfaq-msg nfaq-bot nfaq-answer'
      );
      chipList(m.related, 'Liittyvät kysymykset');
      return el;
    }
    if (m.type === 'miss') {
      var pages = (m.pages || []).map(function (p) {
        var href = localUrl(p.u);
        return href ? '<li><a href="' + esc(href) + '">' + esc(p.t) + '</a></li>' : '';
      }).join('');
      var miss = add(
        '<p>En löytänyt tähän valmista vastausta.' + (m.related && m.related.length ? ' Tarkoititko jotain näistä?' : '') + '</p>',
        'nfaq-msg nfaq-bot'
      );
      chipList(m.related);
      var box = add(
        (pages ? '<p>Näiltä sivuilta voi löytyä apua:</p><ul>' + pages + '</ul>' : '') +
        '<p>Voit myös lähettää kysymyksen meille, niin vastaamme sähköpostilla.</p>' +
        '<div class="nfaq-actions"><button class="nfaq-btn nfaq-btn-ghost" type="button" data-ask>Lähetä kysymys</button>' + quoteButton() + '</div>',
        'nfaq-msg nfaq-bot'
      );
      box.querySelector('[data-ask]').addEventListener('click', function () { askForm(box, m.text); });
      return miss;
    }
    if (m.type === 'sent') {
      return add('<p>Kiitos! Kysymys on lähetetty. Vastaamme osoitteeseen <strong>' + esc(m.email) + '</strong> mahdollisimman pian.</p>');
    }
    if (m.type === 'error') {
      return add('<p>' + esc(m.text) + '</p>', 'nfaq-msg nfaq-bot nfaq-error');
    }
  }

  function askForm(box, question) {
    var btn = box.querySelector('[data-ask]');
    if (btn) btn.remove();
    if (box.querySelector('form')) return;
    var f = document.createElement('form');
    f.className = 'nfaq-ask';
    f.noValidate = true;
    f.innerHTML =
      '<label>Kysymys<textarea name="kysymys" maxlength="3000" required></textarea></label>' +
      '<label>Nimi<input name="nimi" autocomplete="name"></label>' +
      '<label>Sähköposti<input name="sahkoposti" type="email" autocomplete="email" required></label>' +
      '<label class="nfaq-hp" aria-hidden="true">Verkkosivu<input name="verkkosivu" tabindex="-1" autocomplete="off"></label>' +
      '<p class="nfaq-ask-error" role="alert"></p>' +
      '<button class="nfaq-btn nfaq-btn-primary" type="submit">Lähetä</button>';
    box.appendChild(f);
    f.kysymys.value = question || '';
    var err = f.querySelector('.nfaq-ask-error');
    f.addEventListener('submit', function (e) {
      e.preventDefault();
      var email = f.sahkoposti.value.trim();
      if (f.kysymys.value.trim().length < 3) { err.textContent = 'Kirjoita kysymys.'; f.kysymys.focus(); return; }
      if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) { err.textContent = 'Tarkista sähköpostiosoite.'; f.sahkoposti.focus(); return; }
      err.textContent = '';
      f.classList.add('is-busy');
      fetch(cfg.send, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          kysymys: f.kysymys.value.trim(), nimi: f.nimi.value.trim(), sahkoposti: email,
          verkkosivu: f.verkkosivu.value, sivu: location.href
        })
      })
        .then(function (r) { return r.json().catch(function () { return {}; }); })
        .then(function (d) {
          if (!d || !d.ok) throw new Error((d && d.virhe) || '');
          f.remove();
          var m = { type: 'sent', email: email };
          state.log.push(m);
          save();
          scroll(draw(m));
          if (window.dataLayer) window.dataLayer.push({ event: 'faq_question_sent' });
        })
        .catch(function (e2) {
          f.classList.remove('is-busy');
          err.textContent = e2.message || ('Lähetys ei onnistunut. Lähetä kysymys osoitteeseen ' + cfg.email + '.');
        });
    });
    f.sahkoposti.focus();
    scroll(box);
  }

  function drawAll() {
    logEl.innerHTML = '';
    add(text(cfg.greeting));
    state.log.forEach(draw);
    chips.innerHTML = '';
    chips.hidden = !!state.log.length;
    (cfg.quick || []).forEach(function (q) {
      var b = document.createElement('button');
      b.type = 'button';
      b.className = 'nfaq-chip';
      b.textContent = q;
      b.addEventListener('click', function () { ask(q); });
      chips.appendChild(b);
    });
    logEl.scrollTop = logEl.scrollHeight;
  }

  function typing(on) {
    var t = logEl.querySelector('.nfaq-typing');
    if (on && !t) {
      t = add('<span></span><span></span><span></span>', 'nfaq-msg nfaq-bot nfaq-typing');
      t.setAttribute('aria-label', 'Haetaan vastausta');
      logEl.scrollTop = logEl.scrollHeight;
    } else if (!on && t) {
      t.remove();
    }
  }

  function ask(q) {
    q = String(q || '').trim();
    if (!q) return;
    chips.hidden = true;
    var um = { type: 'user', text: q };
    state.log.push(um);
    save();
    draw(um);
    input.value = '';
    autosize();
    typing(true);
    var started = Date.now();
    data().then(function (ix) {
      var res = M.answer(ix, q);
      var m = res.best
        ? { type: 'answer', item: res.best, related: res.related.map(function (r) { return r.q; }) }
        : { type: 'miss', text: q, related: res.related.map(function (r) { return r.q; }), pages: res.pages };
      // Pieni tauko, ettei vastaus välähdä ennen kysymystä
      setTimeout(function () {
        typing(false);
        state.log.push(m);
        save();
        scroll(draw(m));
        if (window.dataLayer) window.dataLayer.push({ event: res.best ? 'faq_answer' : 'faq_no_answer' });
      }, Math.max(0, 350 - (Date.now() - started)));
    }, function () {
      typing(false);
      var m = { type: 'error', text: 'Vastauksia ei saatu ladattua. Yritä hetken päästä uudelleen tai lähetä kysymys osoitteeseen ' + cfg.email + '.' };
      draw(m);
      logEl.scrollTop = logEl.scrollHeight;
    });
  }

  function setOpen(open, keepFocus) {
    state.open = open;
    save();
    panel.hidden = !open;
    launcher.setAttribute('aria-expanded', open ? 'true' : 'false');
    root.classList.toggle('is-open', open);
    document.documentElement.classList.toggle('nfaq-open', open);
    if (open) {
      if (!logEl.childNodes.length) drawAll();
      data().catch(function () { /* näytetään virhe vasta kysyttäessä */ });
      setTimeout(function () { input.focus(); }, 50);
    } else if (!keepFocus) {
      launcher.focus();
    }
  }

  function autosize() {
    input.style.height = 'auto';
    input.style.height = Math.min(input.scrollHeight, 120) + 'px';
  }

  launcher.addEventListener('click', function () { setOpen(!state.open); });
  root.querySelector('.nfaq-close').addEventListener('click', function () { setOpen(false); });
  form.addEventListener('submit', function (e) { e.preventDefault(); ask(input.value); });
  input.addEventListener('input', autosize);
  input.addEventListener('keydown', function (e) {
    if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); ask(input.value); }
  });
  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape' && state.open) setOpen(false);
  });
  // Tarjouspainike: teeman tarjouslomake avautuu, joten chat suljetaan alta pois
  root.addEventListener('click', function (e) {
    if (e.target.closest('[data-quote]')) setOpen(false, true);
  });
  // Muut sivun painikkeet voivat avata ikkunan: <a href="#chat"> tai data-open-chat
  document.addEventListener('click', function (e) {
    var a = e.target.closest('a[href="#chat"], [data-open-chat]');
    if (a) { e.preventDefault(); setOpen(true); }
  });

  if (state.open) setOpen(true, true);
})();
