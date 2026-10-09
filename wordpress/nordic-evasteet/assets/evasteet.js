/* Nordic Evästeet 1.0.0 */
(function () {
  var cfg = window.nordicEvasteet || { cookie: 'nordic_consent', policy: 1, days: 365, secure: true };
  var POLICY = parseInt(cfg.policy, 10) || 1, DAYS = parseInt(cfg.days, 10) || 365, SECURE = !!cfg.secure && cfg.secure !== '0';
  var box = document.getElementById('nordic-evasteet');
  if (!box) return;
  var prefs = document.getElementById('nev-prefs');
  var stats = document.getElementById('nev-stats');
  var mkt = document.getElementById('nev-mkt');
  var btnPrefs = box.querySelector('[data-nev="prefs"]');
  var btnSave = box.querySelector('[data-nev="save"]');
  var lastFocus = null;

  function read() {
    var m = document.cookie.match(new RegExp('(?:^|; )' + cfg.cookie + '=([^;]*)'));
    if (!m) return null;
    try {
      var c = JSON.parse(decodeURIComponent(m[1]));
      return c && c.v === POLICY ? c : null;
    } catch (e) { return null; }
  }

  function gtagUpdate(c) {
    window.dataLayer = window.dataLayer || [];
    var g = typeof window.gtag === 'function' ? window.gtag : function () { window.dataLayer.push(arguments); };
    var s = c.s ? 'granted' : 'denied', k = c.m ? 'granted' : 'denied';
    g('consent', 'update', { analytics_storage: s, ad_storage: k, ad_user_data: k, ad_personalization: k });
    window.dataLayer.push({ event: 'nordic_consent_update', consent_statistics: s, consent_marketing: k });
  }

  function consentApi(c) {
    if (typeof window.wp_set_consent !== 'function') return;
    window.wp_set_consent('functional', 'allow');
    window.wp_set_consent('preferences', 'allow');
    window.wp_set_consent('statistics', c.s ? 'allow' : 'deny');
    window.wp_set_consent('statistics-anonymous', c.s ? 'allow' : 'deny');
    window.wp_set_consent('marketing', c.m ? 'allow' : 'deny');
  }

  function removeGaCookies() {
    var host = location.hostname.split('.');
    var domains = ['', location.hostname];
    for (var i = host.length - 2; i > 0; i--) domains.push('.' + host.slice(i).join('.'));
    document.cookie.split('; ').forEach(function (kv) {
      var name = kv.split('=')[0];
      if (!/^(_ga|_gid|_gat)/.test(name)) return;
      domains.forEach(function (d) {
        document.cookie = name + '=; Max-Age=0; path=/' + (d ? '; domain=' + d : '');
      });
    });
  }

  function save(s, m) {
    var prev = read();
    var c = { v: POLICY, s: s ? 1 : 0, m: m ? 1 : 0, t: Math.floor(Date.now() / 1000) };
    document.cookie = cfg.cookie + '=' + encodeURIComponent(JSON.stringify(c)) +
      '; Max-Age=' + (DAYS * 86400) + '; path=/; SameSite=Lax' + (SECURE ? '; Secure' : '');
    gtagUpdate(c);
    consentApi(c);
    if (!c.s) removeGaCookies();
    document.dispatchEvent(new CustomEvent('nordic:consent', { detail: { statistics: !!c.s, marketing: !!c.m } }));
    hide();
    /* Suostumus peruttu: ladataan sivu uudelleen, jotta jo käynnissä olevat tagit pysähtyvät. */
    if (prev && ((prev.s && !c.s) || (prev.m && !c.m))) location.reload();
  }

  function showPrefs(on) {
    prefs.hidden = !on;
    btnSave.hidden = !on;
    btnPrefs.hidden = on;
    btnPrefs.setAttribute('aria-expanded', on ? 'true' : 'false');
  }

  function show(withPrefs) {
    var c = read();
    if (stats) stats.checked = !!(c && c.s);
    if (mkt) mkt.checked = !!(c && c.m);
    showPrefs(!!withPrefs);
    lastFocus = document.activeElement;
    box.hidden = false;
    document.documentElement.classList.add('nev-open-banner');
    var first = box.querySelector('.nev-btn');
    if (withPrefs && first) first.focus();
  }

  function hide() {
    box.hidden = true;
    document.documentElement.classList.remove('nev-open-banner');
    if (lastFocus && lastFocus.focus && lastFocus !== document.body) lastFocus.focus();
  }

  box.addEventListener('click', function (e) {
    var b = e.target.closest('[data-nev]');
    if (!b) return;
    var a = b.getAttribute('data-nev');
    if (a === 'all') save(true, !!mkt);
    else if (a === 'none') save(false, false);
    else if (a === 'prefs') { showPrefs(true); if (stats) stats.focus(); }
    else if (a === 'save') save(stats && stats.checked, mkt && mkt.checked);
  });

  box.addEventListener('keydown', function (e) {
    if (e.key === 'Escape' && read()) hide();
  });

  document.addEventListener('click', function (e) {
    var a = e.target.closest('a[href$="#evasteasetukset"], .nev-open');
    if (!a) return;
    e.preventDefault();
    show(true);
  });

  window.nordicConsent = {
    get: function () { var c = read(); return c ? { statistics: !!c.s, marketing: !!c.m } : null; },
    open: function () { show(true); }
  };

  var current = read();
  if (current) consentApi(current);
  else show(false);
  if (location.hash === '#evasteasetukset') show(true);
})();
