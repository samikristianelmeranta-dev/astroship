/**
 * Asiakaslupaus-banneri. Pieni kortti, joka liukuu näkyviin, kun kävijä on ollut sivustolla yli 20 sekuntia.
 *  - Aika lasketaan koko käynniltä (myös sivulta toiselle siirryttäessä), ei vain yhdeltä sivulta.
 *  - Kävijä voi sulkea bannerin. Suljettu banneri pysyy poissa 14 päivää.
 *  - Ei aukea toisen ikkunan päälle eikä sen jälkeen, kun kävijä on lähettänyt lomakkeen.
 */
(function () {
  'use strict';
  var el = document.getElementById('promise-banner');
  if (!el) return;

  var KEY = 'nordic_promise';        // localStorage: { closed: aika, done: aika }
  var START = 'nordic_visit_start';  // sessionStorage: käynnin alkuhetki
  var OPEN = 'nordic_promise_open';  // sessionStorage: banneri on jo auki tällä käynnillä
  var DELAY = (parseInt(el.getAttribute('data-delay'), 10) || 20) * 1000;
  var PAUSE_DAYS = 14;

  function read() { try { return JSON.parse(localStorage.getItem(KEY)) || {}; } catch (e) { return {}; } }
  function write(v) { try { localStorage.setItem(KEY, JSON.stringify(v)); } catch (e) { /* yksityinen tila */ } }
  function sget(k) { try { return sessionStorage.getItem(k); } catch (e) { return null; } }
  function sset(k, v) { try { sessionStorage.setItem(k, v); } catch (e) { /* yksityinen tila */ } }
  function track(ev) { if (window.dataLayer) window.dataLayer.push({ event: ev }); }

  var s = read();
  if (s.done || (s.closed && Date.now() - s.closed < PAUSE_DAYS * 864e5)) return;

  var start = parseInt(sget(START), 10);
  if (!start) { start = Date.now(); sset(START, String(start)); }

  function busy() {
    return document.querySelector('dialog[open]') ||
      document.documentElement.classList.contains('nep-lock') ||
      document.body.classList.contains('modal-open') ||
      document.body.classList.contains('nav-open');
  }

  function show() {
    if (busy()) { window.setTimeout(show, 3000); return; } // odotetaan, että avoin ikkuna tai valikko suljetaan
    el.hidden = false;
    // Seuraavalla ruudunpäivityksellä, jotta liukuma animoituu.
    window.requestAnimationFrame(function () { window.requestAnimationFrame(function () { el.classList.add('is-open'); }); });
    if (!sget(OPEN)) { sset(OPEN, '1'); track('promise_banner_open'); }
  }

  function close(remember) {
    el.classList.remove('is-open');
    window.setTimeout(function () { el.hidden = true; }, 350);
    if (remember) { var st = read(); st.closed = Date.now(); write(st); }
  }

  el.addEventListener('click', function (e) {
    if (e.target.closest('[data-promise-close]')) {
      close(true);
      track('promise_banner_close');
    } else if (e.target.closest('[data-quote]')) {
      // Banneri pois tieltä; tarjouslomakkeen avaa teeman nordic.js.
      close(true);
      track('promise_banner_quote');
    } else if (e.target.closest('a')) {
      var st = read(); st.closed = Date.now(); write(st);
      track('promise_banner_about');
    }
  });
  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape' && !el.hidden && el.contains(document.activeElement)) close(true);
  });

  // Lomake lähetetty → banneria ei enää näytetä tälle kävijälle.
  document.addEventListener('submit', function () { var st = read(); st.done = Date.now(); write(st); if (!el.hidden) close(false); }, true);

  // Jo avattu tällä käynnillä: näytetään heti uudella sivulla. Muuten kun 20 sekuntia on täynnä.
  var wait = sget(OPEN) ? 600 : Math.max(0, DELAY - (Date.now() - start));
  window.setTimeout(show, wait);
})();
