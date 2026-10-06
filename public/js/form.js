/**
 * TiivisTupa – tarjouslomake.
 * Jos lomakkeella on data-endpoint, tiedot lähetetään sinne (JSON POST).
 * Muuten avataan sähköpostiohjelma valmiiksi täytetyllä viestillä.
 */
(function () {
  'use strict';

  function serialize(form) {
    var lines = [];
    Array.prototype.forEach.call(form.elements, function (el) {
      if (!el.name || el.type === 'submit') return;
      if (el.type === 'checkbox' && !el.checked) return;
      if (el.value.trim()) lines.push(el.name + ': ' + el.value.trim());
    });
    return lines;
  }

  function done(form) {
    form.querySelector('.ff-message-success').hidden = false;
    form.querySelector('.ff-btn-submit').hidden = true;
    form.reset();
  }

  document.addEventListener('submit', function (e) {
    var form = e.target.closest('.tt-form');
    if (!form) return;
    e.preventDefault();

    var error = form.querySelector('.tt-form-error');
    var invalid = Array.prototype.filter.call(form.elements, function (el) {
      var bad = el.willValidate && !el.checkValidity();
      var group = el.closest('.ff-el-group');
      if (group) group.classList.toggle('ff-el-is-error', bad);
      return bad;
    });
    error.hidden = invalid.length === 0;
    if (invalid.length) { invalid[0].focus(); return; }

    var endpoint = form.dataset.endpoint;
    var lines = serialize(form);
    if (endpoint) {
      var data = {};
      Array.prototype.forEach.call(new FormData(form).entries(), function (p) { data[p[0]] = p[1]; });
      fetch(endpoint, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
        body: JSON.stringify(data)
      }).then(function (r) {
        if (!r.ok) throw new Error(r.status);
        done(form);
      }).catch(function () {
        error.textContent = 'Lähetys epäonnistui. Kokeile uudelleen tai lähetä sähköpostia: ' + form.dataset.mailto;
        error.hidden = false;
      });
      return;
    }

    var subject = 'Tarjouspyyntö – ' + (form.elements['Palvelu'] ? form.elements['Palvelu'].value : 'TiivisTupa');
    window.location.href = 'mailto:' + form.dataset.mailto +
      '?subject=' + encodeURIComponent(subject) +
      '&body=' + encodeURIComponent(lines.join('\n'));
    done(form);
  });
})();
