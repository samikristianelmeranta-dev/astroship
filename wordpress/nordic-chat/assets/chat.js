/**
 * Nordic Chat – kevyt chat-ikkuna (ei riippuvuuksia).
 * Keskustelu säilyy välilehden ajan (sessionStorage), joten sivulta toiselle
 * siirtyminen ei katkaise sitä. Varsinainen historia on palvelimella.
 */
(function () {
  'use strict';
  var cfg = window.NordicChat;
  if (!cfg || !cfg.endpoint) return;

  var STORE = 'nordicChat.v1';
  var state = load() || { session: '', log: [], open: false };
  var busy = false;

  function load() {
    try { return JSON.parse(sessionStorage.getItem(STORE)); } catch (e) { return null; }
  }
  function save() {
    try { sessionStorage.setItem(STORE, JSON.stringify(state)); } catch (e) { /* yksityinen tila tms. */ }
  }

  /* ---------- Turvallinen, suppea Markdown: linkit, lihavointi, listat, kappaleet ---------- */
  function esc(s) {
    return String(s).replace(/[&<>"']/g, function (c) {
      return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
    });
  }
  function safeUrl(url) {
    try {
      var u = new URL(url, location.href);
      if (u.protocol === 'mailto:') return u.href;
      if (u.protocol === 'https:' || u.protocol === 'http:') return u.href;
    } catch (e) { /* virheellinen osoite */ }
    return '';
  }
  function inline(s) {
    s = esc(s);
    s = s.replace(/\[([^\]]+)\]\(([^)\s]+)\)/g, function (m, text, url) {
      var href = safeUrl(url.replace(/&amp;/g, '&'));
      if (!href) return text;
      var ext = href.indexOf('mailto:') !== 0 && new URL(href).host !== cfg.host;
      return '<a href="' + esc(href) + '"' + (ext ? ' target="_blank" rel="noopener"' : '') + '>' + text + '</a>';
    });
    s = s.replace(/\*\*([^*]+)\*\*/g, '<strong>$1</strong>');
    s = s.replace(/(^|[\s(])((?:https?:\/\/)[^\s<)]+)/g, function (m, pre, url) {
      var href = safeUrl(url.replace(/&amp;/g, '&'));
      return href ? pre + '<a href="' + esc(href) + '">' + url + '</a>' : m;
    });
    s = s.replace(/([A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[a-z]{2,})(?![^<]*<\/a>)/g, '<a href="mailto:$1">$1</a>');
    return s;
  }
  function render(md) {
    var out = [], list = null;
    String(md).split(/\n/).forEach(function (line) {
      var m = line.match(/^\s*(?:[-*•]|\d+[.)])\s+(.*)$/);
      if (m) {
        if (!list) { list = []; }
        list.push('<li>' + inline(m[1]) + '</li>');
        return;
      }
      if (list) { out.push('<ul>' + list.join('') + '</ul>'); list = null; }
      line = line.replace(/^#+\s*/, '');
      if (line.trim()) out.push('<p>' + inline(line) + '</p>');
    });
    if (list) out.push('<ul>' + list.join('') + '</ul>');
    return out.join('');
  }

  /* ---------- DOM ---------- */
  var root = document.createElement('div');
  root.className = 'nchat';
  root.innerHTML =
    '<button class="nchat-launcher" type="button" aria-expanded="false" aria-controls="nchat-panel">' +
      '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 5h16v11H8l-4 4z" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/><circle cx="9" cy="10.5" r="1.1" fill="currentColor"/><circle cx="12" cy="10.5" r="1.1" fill="currentColor"/><circle cx="15" cy="10.5" r="1.1" fill="currentColor"/></svg>' +
      '<span>Kysy meiltä</span>' +
    '</button>' +
    '<section class="nchat-panel" id="nchat-panel" role="dialog" aria-modal="false" aria-labelledby="nchat-title" hidden>' +
      '<header class="nchat-head">' +
        '<div class="nchat-avatar" aria-hidden="true"><svg viewBox="0 0 24 24"><rect x="12" y="2" width="10" height="10" fill="#FFD866"/><rect x="2.6" y="2.6" width="18.8" height="18.8" fill="none" stroke="currentColor" stroke-width="1.8"/><line x1="12" y1="2" x2="12" y2="22" stroke="currentColor" stroke-width="1.6"/><line x1="2" y1="12" x2="22" y2="12" stroke="currentColor" stroke-width="1.6"/></svg></div>' +
        '<div class="nchat-titles"><p class="nchat-title" id="nchat-title">Nordic Ikkunat &amp; Ovet</p><p class="nchat-sub">Tekoälyavustaja · vastaa heti</p></div>' +
        '<button class="nchat-close" type="button" aria-label="Sulje chat"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M6 6l12 12M18 6L6 18" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg></button>' +
      '</header>' +
      '<div class="nchat-log" role="log" aria-live="polite" aria-relevant="additions"></div>' +
      '<div class="nchat-chips"></div>' +
      '<form class="nchat-form">' +
        '<label class="nchat-sr" for="nchat-input">Kirjoita viesti</label>' +
        '<textarea id="nchat-input" rows="1" maxlength="1500" placeholder="Kirjoita kysymyksesi…" required></textarea>' +
        '<button class="nchat-send" type="submit" aria-label="Lähetä"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 12l16-8-6 16-2.5-6.5z" fill="currentColor"/></svg></button>' +
      '</form>' +
      '<p class="nchat-note">Tekoäly voi erehtyä. Älä kirjoita arkaluonteisia tietoja. ' +
        '<a href="mailto:' + esc(cfg.email) + '">' + esc(cfg.email) + '</a>' +
        (cfg.privacy ? ' · <a href="' + esc(cfg.privacy) + '">Tietosuoja</a>' : '') + '</p>' +
    '</section>';
  document.body.appendChild(root);

  var launcher = root.querySelector('.nchat-launcher');
  var panel = root.querySelector('.nchat-panel');
  var logEl = root.querySelector('.nchat-log');
  var chips = root.querySelector('.nchat-chips');
  var form = root.querySelector('.nchat-form');
  var input = root.querySelector('textarea');

  function bubble(role, text, extraClass) {
    var el = document.createElement('div');
    el.className = 'nchat-msg nchat-' + role + (extraClass ? ' ' + extraClass : '');
    if (role === 'user') {
      el.textContent = text;
    } else {
      el.innerHTML = render(text);
    }
    logEl.appendChild(el);
    logEl.scrollTop = logEl.scrollHeight;
    return el;
  }

  function drawLog() {
    logEl.innerHTML = '';
    bubble('bot', cfg.greeting);
    state.log.forEach(function (m) { bubble(m.role, m.text, m.cls); });
    drawChips();
  }

  function drawChips() {
    chips.innerHTML = '';
    if (state.log.length) { chips.hidden = true; return; }
    chips.hidden = false;
    (cfg.chips || []).forEach(function (q) {
      var b = document.createElement('button');
      b.type = 'button';
      b.className = 'nchat-chip';
      b.textContent = q;
      b.addEventListener('click', function () { send(q); });
      chips.appendChild(b);
    });
  }

  function setOpen(open) {
    state.open = open;
    save();
    panel.hidden = !open;
    launcher.setAttribute('aria-expanded', open ? 'true' : 'false');
    root.classList.toggle('is-open', open);
    document.documentElement.classList.toggle('nchat-open', open);
    if (open) {
      if (!logEl.childNodes.length) drawLog();
      setTimeout(function () { input.focus(); logEl.scrollTop = logEl.scrollHeight; }, 50);
    } else {
      launcher.focus();
    }
  }

  function typing(on) {
    var t = logEl.querySelector('.nchat-typing');
    if (on && !t) {
      t = document.createElement('div');
      t.className = 'nchat-msg nchat-bot nchat-typing';
      t.setAttribute('aria-label', 'Avustaja kirjoittaa');
      t.innerHTML = '<span></span><span></span><span></span>';
      logEl.appendChild(t);
      logEl.scrollTop = logEl.scrollHeight;
    } else if (!on && t) {
      t.remove();
    }
  }

  function send(text) {
    text = String(text || '').trim();
    if (!text || busy) return;
    busy = true;
    form.classList.add('is-busy');
    chips.hidden = true;
    state.log.push({ role: 'user', text: text });
    save();
    bubble('user', text);
    input.value = '';
    autosize();
    typing(true);

    fetch(cfg.endpoint, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ istunto: state.session, viesti: text, sivu: location.href })
    })
      .then(function (r) { return r.json().catch(function () { return {}; }).then(function (d) { return { ok: r.ok, d: d }; }); })
      .then(function (res) {
        typing(false);
        if (res.d && res.d.istunto) state.session = res.d.istunto;
        var reply = (res.d && (res.d.vastaus || res.d.virhe)) ||
          'Yhteys katkesi. Yritä uudelleen, tai lähetä viesti osoitteeseen ' + cfg.email + '.';
        var cls = res.ok ? '' : 'nchat-error';
        state.log.push({ role: 'bot', text: reply, cls: cls });
        save();
        bubble('bot', reply, cls);
        if (res.d && res.d.lahetetty && window.dataLayer) window.dataLayer.push({ event: 'chat_contact_sent' });
      })
      .catch(function () {
        typing(false);
        var msg = 'Yhteys katkesi. Yritä uudelleen, tai lähetä viesti osoitteeseen ' + cfg.email + '.';
        state.log.push({ role: 'bot', text: msg, cls: 'nchat-error' });
        save();
        bubble('bot', msg, 'nchat-error');
      })
      .then(function () {
        busy = false;
        form.classList.remove('is-busy');
        input.focus();
      });
  }

  function autosize() {
    input.style.height = 'auto';
    input.style.height = Math.min(input.scrollHeight, 120) + 'px';
  }

  launcher.addEventListener('click', function () { setOpen(!state.open); });
  root.querySelector('.nchat-close').addEventListener('click', function () { setOpen(false); });
  form.addEventListener('submit', function (e) { e.preventDefault(); send(input.value); });
  input.addEventListener('input', autosize);
  input.addEventListener('keydown', function (e) {
    if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); send(input.value); }
  });
  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape' && state.open) setOpen(false);
  });

  // Muut sivun painikkeet voivat avata chatin: <a href="#chat"> tai data-open-chat
  document.addEventListener('click', function (e) {
    var a = e.target.closest('a[href="#chat"], [data-open-chat]');
    if (a) { e.preventDefault(); setOpen(true); }
  });

  if (state.open) setOpen(true);
})();
