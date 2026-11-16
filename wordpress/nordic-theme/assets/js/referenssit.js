/* Referenssit: kuva isona klikattaessa (nuolet ja Esc toimivat). */
(function () {
  'use strict';
  var links = Array.prototype.slice.call(document.querySelectorAll('[data-ref-full]'));
  if (!links.length || typeof HTMLDialogElement === 'undefined') return;
  var dlg = document.createElement('dialog');
  dlg.className = 'ref-lightbox';
  dlg.innerHTML = '<button type="button" class="ref-lb-close" aria-label="Sulje">×</button>' +
    '<button type="button" class="ref-lb-prev" aria-label="Edellinen kuva">‹</button>' +
    '<figure><img alt=""><figcaption></figcaption></figure>' +
    '<button type="button" class="ref-lb-next" aria-label="Seuraava kuva">›</button>';
  document.body.appendChild(dlg);
  var img = dlg.querySelector('img'), cap = dlg.querySelector('figcaption'), cur = 0;
  function group(i) { var art = links[i].closest('.ref'); return links.filter(function (l) { return l.closest('.ref') === art; }); }
  function show(i) {
    cur = i;
    img.src = links[i].getAttribute('data-ref-full');
    img.alt = links[i].getAttribute('data-ref-caption');
    cap.textContent = links[i].getAttribute('data-ref-caption');
  }
  function step(d) { var g = group(cur), k = g.indexOf(links[cur]); show(links.indexOf(g[(k + d + g.length) % g.length])); }
  links.forEach(function (a, i) {
    a.addEventListener('click', function (e) { e.preventDefault(); show(i); dlg.showModal(); document.body.classList.add('modal-open'); });
  });
  dlg.addEventListener('close', function () { document.body.classList.remove('modal-open'); });
  dlg.addEventListener('click', function (e) {
    if (e.target === dlg || e.target.closest('.ref-lb-close')) dlg.close();
    else if (e.target.closest('.ref-lb-prev')) step(-1);
    else if (e.target.closest('.ref-lb-next')) step(1);
  });
  dlg.addEventListener('keydown', function (e) { if (e.key === 'ArrowLeft') step(-1); if (e.key === 'ArrowRight') step(1); });
})();
