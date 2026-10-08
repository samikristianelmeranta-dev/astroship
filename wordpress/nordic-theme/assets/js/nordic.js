/**
 * Nordic Ikkunat & Ovet – käyttöliittymä ja animaatiot (ei riippuvuuksia).
 * - otsikkopalkin tila vierittäessä + mobiilivalikko
 * - sisällön ilmestymisanimaatiot (IntersectionObserver)
 * - lukulaskurit, UKK-haitarit, lukemisen edistymispalkki
 * - tarjouslomake modaalina + kiinteä mobiili-CTA
 * Kaikki animaatiot kunnioittavat prefers-reduced-motion-asetusta.
 */
(function () {
  'use strict';

  var doc = document;
  var root = doc.documentElement;
  var reduceMotion = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  var $ = function (sel, ctx) { return (ctx || doc).querySelector(sel); };
  var $$ = function (sel, ctx) { return Array.prototype.slice.call((ctx || doc).querySelectorAll(sel)); };

  /* ---------- Otsikkopalkki + lukemisen edistyminen ---------- */
  var header = $('#site-header');
  var progress = $('.scroll-progress span');
  var ticking = false;

  function onScroll() {
    var y = window.scrollY || window.pageYOffset;
    if (header) header.classList.toggle('is-scrolled', y > 12);
    if (progress) {
      var max = root.scrollHeight - window.innerHeight;
      progress.style.transform = 'scaleX(' + (max > 0 ? Math.min(1, y / max) : 0) + ')';
    }
    ticking = false;
  }
  window.addEventListener('scroll', function () {
    if (!ticking) { window.requestAnimationFrame(onScroll); ticking = true; }
  }, { passive: true });
  onScroll();

  /* ---------- Mobiilivalikko ---------- */
  var toggle = $('.nav-toggle');
  var nav = $('#site-nav');
  function setNav(open) {
    if (!toggle) return;
    toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
    doc.body.classList.toggle('nav-open', open);
  }
  if (toggle && nav) {
    toggle.addEventListener('click', function () {
      setNav(toggle.getAttribute('aria-expanded') !== 'true');
    });
    nav.addEventListener('click', function (e) {
      if (e.target.closest('a')) setNav(false);
    });
    doc.addEventListener('keydown', function (e) {
      if (e.key === 'Escape') setNav(false);
    });
  }

  /* ---------- Ilmestymisanimaatiot ---------- */
  var REVEAL = [
    '.page-content > section .section-head', '.sec-head', '.intro .wrap > *',
    '.article-body .wrap > *', '.value-prop .wrap > p', '.prose > *',
    '.type-card', '.audience-card', '.process-item', '.process-step', '.feature-item',
    '.signs-card', '.remontti-card', '.service-card', '.benefit-card', '.why-item',
    '.faq-item', '.compare', '.calc-banner', '.seo-summary-text', '.seo-summary-media',
    '.area-list li', '.sitemap-list li', '.city-row', '.final-cta .wrap > *',
    '.contact-grid > *', '.contact-dark-info', '.info-box', '#tarjous-lomake',
    '.benefits-section h2', '.benefits-intro', '.inputs-panel', '.result-panel', '.cta-band'
  ].join(',');

  function setupReveal() {
    var els = $$(REVEAL).filter(function (el) { return !el.closest('.quote-modal'); });
    if (reduceMotion || !('IntersectionObserver' in window)) return;

    var vh = window.innerHeight;
    var groups = new Map();
    els.forEach(function (el) {
      // Näkyvissä jo latautuessa → ei piiloteta (ei välähdystä).
      if (el.getBoundingClientRect().top < vh * 0.92) return;
      var parent = el.parentElement;
      var n = groups.get(parent) || 0;
      groups.set(parent, n + 1);
      el.style.setProperty('--reveal-delay', Math.min(n, 6) * 70 + 'ms');
      el.classList.add('reveal');
    });

    var io = new IntersectionObserver(function (entries) {
      entries.forEach(function (entry) {
        if (entry.isIntersecting) {
          entry.target.classList.add('is-visible');
          io.unobserve(entry.target);
        }
      });
    }, { rootMargin: '0px 0px -8% 0px', threshold: 0.08 });

    $$('.reveal').forEach(function (el) { io.observe(el); });
  }

  /* ---------- Numerolaskurit (esim. "10+") ---------- */
  function setupCounters() {
    var targets = $$('.hero-trust-item b').filter(function (b) { return /^\d+\+?$/.test(b.textContent.trim()); });
    if (!targets.length || reduceMotion) return;
    targets.forEach(function (b) {
      var text = b.textContent.trim();
      var end = parseInt(text, 10);
      var suffix = text.replace(/^\d+/, '');
      var start = null;
      var dur = 1400;
      b.textContent = '0' + suffix;
      function frame(t) {
        if (!start) start = t;
        var p = Math.min(1, (t - start) / dur);
        var eased = 1 - Math.pow(1 - p, 3);
        b.textContent = Math.round(end * eased) + suffix;
        if (p < 1) window.requestAnimationFrame(frame);
      }
      window.setTimeout(function () { window.requestAnimationFrame(frame); }, 500);
    });
  }

  /* ---------- UKK-haitarit (sivut, joilla on staattinen h3 + p -rakenne) ---------- */
  function setupFaq() {
    $$('.faq-item').forEach(function (item, i) {
      if (item.querySelector('.faq-q')) return; // etusivulla oma toteutus
      var q = item.querySelector('h3');
      if (!q) return;
      var panel = doc.createElement('div');
      panel.className = 'faq-panel';
      panel.id = 'faq-panel-' + i;
      var inner = doc.createElement('div');
      inner.className = 'faq-panel-inner';
      while (q.nextSibling) inner.appendChild(q.nextSibling);
      panel.appendChild(inner);
      item.appendChild(panel);

      var btn = doc.createElement('button');
      btn.type = 'button';
      btn.className = 'faq-toggle';
      btn.setAttribute('aria-expanded', 'false');
      btn.setAttribute('aria-controls', panel.id);
      while (q.firstChild) btn.appendChild(q.firstChild);
      var icon = doc.createElement('span');
      icon.className = 'faq-icon';
      icon.setAttribute('aria-hidden', 'true');
      btn.appendChild(icon);
      q.appendChild(btn);
      item.classList.add('faq-ready');

      btn.addEventListener('click', function () {
        var open = btn.getAttribute('aria-expanded') !== 'true';
        btn.setAttribute('aria-expanded', open ? 'true' : 'false');
        item.classList.toggle('is-open', open);
      });
    });
  }

  /* ---------- Tarjouslomake: modaali tai vieritys sivun omaan lomakkeeseen ---------- */
  var modal = $('#quote-modal');
  var stickyCta = $('#sticky-cta');

  function inlineForm() {
    return $('#tarjous .contact-form-col') || $('#tarjous-lomake') ||
      $('.page-content .fluentform, .page-content form');
  }

  function scrollToEl(el) {
    var top = el.getBoundingClientRect().top + window.scrollY - (header ? header.offsetHeight + 16 : 16);
    window.scrollTo({ top: top, behavior: reduceMotion ? 'auto' : 'smooth' });
    var field = el.querySelector('input:not([type=hidden]), textarea, select');
    if (field) window.setTimeout(function () { field.focus({ preventScroll: true }); }, reduceMotion ? 0 : 600);
  }

  function openQuote(e) {
    var form = inlineForm();
    if (form) {
      if (e) e.preventDefault();
      setNav(false);
      scrollToEl(form);
      return;
    }
    if (modal && typeof modal.showModal === 'function') {
      if (e) e.preventDefault();
      setNav(false);
      modal.showModal();
      doc.body.classList.add('modal-open');
      var field = modal.querySelector('input:not([type=hidden]), textarea, select');
      if (field) window.setTimeout(function () { field.focus(); }, 80);
      if (window.dataLayer) window.dataLayer.push({ event: 'quote_modal_open' });
    }
    // Muuten linkki toimii tavallisena ankkurina (#tarjous).
  }

  doc.addEventListener('click', function (e) {
    var a = e.target.closest('a[href="#tarjous"], a[href="#tarjous-lomake"], [data-quote]');
    if (a) openQuote(e);
  });

  if (modal) {
    modal.addEventListener('click', function (e) {
      // Klikkaus taustaan tai sulkupainikkeeseen sulkee
      if (e.target === modal || e.target.closest('[data-close]')) modal.close();
    });
    modal.addEventListener('close', function () { doc.body.classList.remove('modal-open'); });
  }

  /* ---------- Kiinteä CTA mobiilissa: näkyy heron jälkeen, piiloutuu lomakkeen/CTA:n kohdalla ---------- */
  function setupStickyCta() {
    if (!stickyCta || !('IntersectionObserver' in window)) return;
    var hero = $('.v4hero, .hero');
    var end = $$('#tarjous, .final-cta, .contact-dark, .site-footer');
    var pastHero = !hero;
    var nearEnd = false;
    function update() {
      var show = pastHero && !nearEnd;
      stickyCta.classList.toggle('is-visible', show);
      stickyCta.setAttribute('aria-hidden', show ? 'false' : 'true');
      var link = stickyCta.querySelector('a');
      if (link) link.tabIndex = show ? 0 : -1;
    }
    if (hero) {
      new IntersectionObserver(function (entries) {
        pastHero = !entries[0].isIntersecting && entries[0].boundingClientRect.top < 0;
        update();
      }).observe(hero);
    }
    var visibleEnds = new Set();
    var endIo = new IntersectionObserver(function (entries) {
      entries.forEach(function (en) {
        if (en.isIntersecting) visibleEnds.add(en.target); else visibleEnds.delete(en.target);
      });
      nearEnd = visibleEnds.size > 0;
      update();
    });
    end.forEach(function (el) { endIo.observe(el); });
    update();
  }

  /* ---------- Laskuri: tuloksen "pomppu" kun arvo muuttuu ---------- */
  function setupCalcPulse() {
    var out = $('#savingsAmount');
    if (!out || reduceMotion || !('MutationObserver' in window)) return;
    var box = out.closest('.num-display') || out;
    new MutationObserver(function () {
      box.classList.remove('bump');
      void box.offsetWidth; // käynnistä animaatio uudelleen
      box.classList.add('bump');
    }).observe(out, { childList: true, characterData: true, subtree: true });
  }

  /* ---------- Ankkurilinkkien pehmeä vieritys (otsikkopalkin korkeus huomioiden) ---------- */
  doc.addEventListener('click', function (e) {
    var a = e.target.closest('a[href^="#"]');
    if (!a || e.defaultPrevented) return;
    var id = a.getAttribute('href').slice(1);
    if (!id) { e.preventDefault(); return; }
    var target = doc.getElementById(id);
    if (!target) return;
    e.preventDefault();
    scrollToEl(target);
    if (history.replaceState) history.replaceState(null, '', '#' + id);
  });

  function init() {
    setupFaq();
    setupReveal();
    setupCounters();
    setupStickyCta();
    setupCalcPulse();
    root.classList.add('is-ready');
  }

  // Puhelinlinkkien klikkaukset Analyticsiin
  doc.addEventListener('click', function (e) {
    var a = e.target.closest && e.target.closest('a[href^="tel:"]');
    if (a && window.dataLayer) window.dataLayer.push({ event: 'phone_click', phone_number: a.getAttribute('href').slice(4) });
  });

  if (doc.readyState === 'loading') doc.addEventListener('DOMContentLoaded', init);
  else init();
})();
