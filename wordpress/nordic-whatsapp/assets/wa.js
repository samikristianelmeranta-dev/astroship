/* Nordic WhatsApp: painike + valikko (Myynti / Asennus) */
(function () {
  'use strict';
  var cfg = window.NordicWA;
  if (!cfg || !cfg.contacts || !cfg.contacts.length) return;
  var ICON = '<svg class="nwa-ico" viewBox="0 0 32 32" aria-hidden="true"><path fill="currentColor" d="M16 3C8.8 3 3 8.7 3 15.8c0 2.5.7 4.9 2 6.9L3 29l6.5-2c1.9 1 4.1 1.6 6.5 1.6 7.2 0 13-5.7 13-12.8S23.2 3 16 3zm0 23.3c-2.1 0-4.1-.6-5.8-1.7l-.4-.2-3.9 1.2 1.2-3.7-.3-.4a10.4 10.4 0 0 1-1.7-5.7C5.1 10 10 5.3 16 5.3S26.9 10 26.9 15.8 22 26.3 16 26.3zm6-7.8c-.3-.2-1.9-.9-2.2-1s-.5-.2-.7.2-.8 1-1 1.2-.4.2-.7.1a8.6 8.6 0 0 1-4.3-3.7c-.3-.6.3-.5 1-1.7.1-.2 0-.4 0-.5l-1-2.4c-.3-.6-.5-.5-.7-.5h-.6c-.2 0-.5.1-.8.4-.3.3-1 1-1 2.5s1.1 2.9 1.2 3.1c.1.2 2.1 3.2 5.1 4.5 1.9.8 2.6.9 3.6.7.6-.1 1.9-.8 2.1-1.5.3-.7.3-1.4.2-1.5-.1-.1-.3-.2-.6-.3z"/></svg>';
  function esc(s) { return String(s).replace(/[&<>"']/g, function (c) { return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]; }); }
  function link(c) {
    // Aloitusviestiin sivu, jolta kävijä tulee, jotta tiedätte mistä on kyse
    var text = (c.text ? c.text + '\n\n' : '') + '(' + document.title.split(' – ')[0] + ': ' + location.href + ')';
    return 'https://wa.me/' + c.num + '?text=' + encodeURIComponent(text);
  }

  var root = document.createElement('div');
  root.className = 'nwa';
  var single = cfg.contacts.length === 1;
  var html = '<button class="nwa-launcher" type="button" aria-expanded="false" aria-controls="nwa-panel" aria-label="WhatsApp">' + ICON + '<span>' + esc(cfg.button || 'WhatsApp') + '</span></button>';
  if (!single) {
    html += '<div class="nwa-panel" id="nwa-panel" role="dialog" aria-label="WhatsApp" hidden><div class="nwa-head"><p class="nwa-title">Kenelle haluat kirjoittaa?</p><button class="nwa-close" type="button" aria-label="Sulje">&times;</button></div>';
    cfg.contacts.forEach(function (c) {
      html += '<a class="nwa-option" target="_blank" rel="noopener" data-nwa="' + esc(c.key) + '" href="' + esc(link(c)) + '">' + ICON + '<span><strong>' + esc(c.label) + '</strong><small>' + esc(c.desc) + '</small></span></a>';
    });
    html += (cfg.hours ? '<p class="nwa-hours">' + esc(cfg.hours) + '</p>' : '') + '</div>';
  }
  root.innerHTML = html;
  document.body.appendChild(root);

  var btn = root.querySelector('.nwa-launcher');
  var panel = root.querySelector('.nwa-panel');
  function setOpen(open) {
    if (!panel) return;
    panel.hidden = !open;
    btn.setAttribute('aria-expanded', open ? 'true' : 'false');
  }
  btn.addEventListener('click', function () {
    if (single) {
      track(cfg.contacts[0].key);
      window.open(link(cfg.contacts[0]), '_blank', 'noopener');
      return;
    }
    setOpen(panel.hidden);
  });
  if (panel) panel.querySelector('.nwa-close').addEventListener('click', function () { setOpen(false); btn.focus(); });
  document.addEventListener('keydown', function (e) { if (e.key === 'Escape') setOpen(false); });
  document.addEventListener('click', function (e) {
    var a = e.target.closest('[data-nwa]');
    if (a) { track(a.getAttribute('data-nwa')); setOpen(false); }
    else if (panel && !panel.hidden && !root.contains(e.target)) setOpen(false);
  });
  function track(key) { if (window.dataLayer) window.dataLayer.push({ event: 'whatsapp_click', whatsapp_target: key }); }
})();
