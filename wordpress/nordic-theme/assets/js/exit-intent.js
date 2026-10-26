/**
 * Poistumisikkuna. Aukeaa enintään kerran 14 päivässä:
 *  - tietokoneella, kun hiiri poistuu ikkunan yläreunasta (kohti välilehtiä tai osoiteriviä)
 *  - puhelimessa, kun kävijä on lukenut sivua ja vierittää nopeasti takaisin ylös
 * Ei aukea toisen ikkunan päälle eikä sen jälkeen, kun kävijä on lähettänyt lomakkeen.
 */
(function () {
  'use strict';
  var dlg = document.getElementById('exit-modal');
  if (!dlg || typeof dlg.showModal !== 'function') return;

  var KEY = 'nordic_exit';
  var PAUSE_DAYS = 14;
  var MIN_SECONDS = 8;
  var start = Date.now();
  var shown = false;

  function read() { try { return JSON.parse(localStorage.getItem(KEY)) || {}; } catch (e) { return {}; } }
  function write(v) { try { localStorage.setItem(KEY, JSON.stringify(v)); } catch (e) { /* yksityinen tila */ } }
  function track(ev) { if (window.dataLayer) window.dataLayer.push({ event: ev }); }

  var s = read();
  if (s.done || (s.shown && Date.now() - s.shown < PAUSE_DAYS * 864e5)) return;

  function busy() {
    return document.querySelector('dialog[open]') ||
      document.documentElement.classList.contains('nep-lock') ||
      document.body.classList.contains('modal-open') ||
      document.body.classList.contains('nav-open');
  }

  function open() {
    if (shown || busy() || Date.now() - start < MIN_SECONDS * 1000) return;
    shown = true;
    var st = read(); st.shown = Date.now(); write(st);
    dlg.showModal();
    document.body.classList.add('modal-open');
    track('exit_popup_open');
    detach();
  }

  dlg.addEventListener('close', function () { document.body.classList.remove('modal-open'); });
  dlg.addEventListener('click', function (e) {
    if (e.target === dlg || e.target.closest('[data-exit-close]')) {
      dlg.close();
      track('exit_popup_close');
    } else if (e.target.closest('[data-exit-yes]')) {
      // Suljetaan ensin, sitten teeman oma tarjouslomake avautuu (nordic.js käsittelee [data-quote]).
      dlg.close();
      track('exit_popup_yes');
    }
  });

  // Lomake lähetetty → ei enää poistumisikkunaa tälle kävijälle.
  document.addEventListener('submit', function () { var st = read(); st.done = Date.now(); write(st); }, true);

  // Tietokone: hiiri poistuu ikkunan yläreunasta.
  function onMouseOut(e) {
    if (!e.relatedTarget && e.clientY <= 4) open();
  }

  // Puhelin: luettu yli 35 % sivusta ja vieritetään nopeasti ylös.
  var lastY = window.scrollY, lastT = Date.now(), maxY = 0;
  function onScroll() {
    var y = window.scrollY, t = Date.now();
    maxY = Math.max(maxY, y);
    var depth = maxY / Math.max(1, document.documentElement.scrollHeight - window.innerHeight);
    var speed = (lastY - y) / Math.max(1, t - lastT); // px/ms ylöspäin
    if (depth > 0.35 && speed > 2.2 && lastY - y > 140 && Date.now() - start > 20000) open();
    lastY = y; lastT = t;
  }

  var coarse = window.matchMedia && window.matchMedia('(pointer: coarse)').matches;
  function detach() {
    document.removeEventListener('mouseout', onMouseOut);
    window.removeEventListener('scroll', onScroll);
  }
  if (coarse) window.addEventListener('scroll', onScroll, { passive: true });
  else document.addEventListener('mouseout', onMouseOut);
})();
