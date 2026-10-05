/**
 * Nordic Kysy meiltä – hakulogiikka.
 * Etsii asiakkaan kysymykseen parhaiten sopivan UKK-vastauksen.
 *
 * Suomen taivutus hoidetaan karkeasti: sanat katkaistaan vartaloiksi ja
 * lisäksi tunnetaan synonyymiryhmiä (esim. hinta ~ maksaa ~ kustannus).
 * Toimii sekä selaimessa (window.NordicFaqMatch) että Nodessa (testit).
 */
(function (root) {
  'use strict';

  var STOP = ('mikä mitä mitkä miten mites kuinka kuka onko ovatko voiko voinko voisiko saako saanko pitääkö ' +
    'kannattaako tarvitseeko tarvitaanko paljon teillä teidän teiltä teille meillä meidän minä minun mulla mun ' +
    'olla olen olisi ovat oletteko joka jotka sekä että jos kun niin kuin vai tai myös vielä nyt sitten sen siitä ' +
    'tämä tää tämän näitä tässä hei moi kiitos haluan haluaisin tietää kertoa kerro voisitteko voitteko tehdä ' +
    'esim ihan aika noin yms jne miksi mistä missä teettekö teette pystyttekö pitäisi').split(' ');
  var STOPSET = {};
  STOP.forEach(function (w) { STOPSET[w] = 1; });

  // Synonyymiryhmät: ensimmäinen on ryhmän nimi, loput vartaloita, jotka tulkitaan samaksi.
  var GROUPS = [
    ['hinta', 'hint', 'hinn', 'maks', 'kust', 'kallis', 'kalli', 'halpa', 'halv', 'edulli', 'euro', 'budjet', 'paljonko', 'mitäkö'],
    ['kesto', 'kest', 'kauan', 'nopea', 'nopeas', 'aikatau', 'toimitusa', 'päivä', 'viikko', 'viiko'],
    ['vähennys', 'kotitalo', 'vähenn', 'verotu', 'vero'],
    ['asennus', 'asen', 'asent'],
    ['talvi', 'talv', 'pakka', 'kylmä'],
    ['lasi', 'lasi', 'kolmilas', 'nelilas', 'lasitu'],
    ['huurre', 'huurt', 'huuru', 'kosteu', 'kondens'],
    ['veto', 'veto', 'vetä', 'vedo', 'tiivi', 'tiivist'],
    ['ovi', 'ovi', 'ove', 'ulko-ov', 'ulkoov'],
    ['ikkuna', 'ikkun'],
    ['takuu', 'takuu', 'takui'],
    ['huolto', 'huol', 'säät', 'korja'],
    ['taloyhtiö', 'taloyht', 'yhtiökok', 'isännöi', 'osakka', 'vastike'],
    ['mökki', 'mökk', 'kesämök', 'vapaa-aj'],
    ['rahoitus', 'rahoit', 'osamaks', 'laina', 'erissä', 'osissa'],
    ['energia', 'energ', 'säästö', 'säästä', 'lämmity', 'u-arvo', 'uarvo'],
    ['ääni', 'ääne', 'äänie', 'melu', 'mete'],
    ['väri', 'vär', 'sävy', 'ral', 'ncs', 'musta', 'valkoi', 'harmaa', 'tumma'],
    ['ilman asennusta', 'ilman$', 'myyt', 'myyd', 'ostaa', 'osta', 'tilaa', 'tilat'],
    ['mittaus', 'mitta', 'mittau', 'kotikäyn', 'käynti', 'arvioint'],
    ['alue', 'alue', 'paikkakun', 'toimialue', 'palvelet'],
    ['lupa', 'lupa', 'rakennusl', 'luvan'],
    ['purku', 'purk', 'poisvien', 'vanhat', 'kierrät', 'jäte'],
    ['palo', 'palo', 'ei30']
  ];
  var CITIES = ['turku', 'turu', 'kaarina', 'kaarin', 'raisio', 'raisi', 'naantali', 'naantal', 'lieto', 'lied', 'liedo',
    'salo', 'salo', 'paimio', 'paimi', 'parainen', 'paraine', 'parais', 'masku', 'mask', 'nousiainen', 'nousiai',
    'laitila', 'laitil', 'uusikaupunki', 'uudenkaupun', 'uusikaupun', 'halikko', 'halikk', 'aura', 'pöytyä', 'pöytyä', 'rusko', 'rusko'];

  function norm(s) {
    return String(s).toLowerCase().replace(/[^a-zåäö0-9+\- ]+/g, ' ').replace(/\s+/g, ' ').trim();
  }

  function stem(w) {
    // Katkaise taivutuspääte karkeasti: pitkistä sanoista enemmän.
    if (w.length <= 4) return w;
    if (w.length <= 6) return w.slice(0, w.length - 1);
    return w.slice(0, Math.max(5, w.length - 3));
  }

  // Sanan synonyymiryhmä (tai null)
  function groupOf(word) {
    for (var i = 0; i < GROUPS.length; i++) {
      for (var j = 1; j < GROUPS[i].length; j++) {
        var g = GROUPS[i][j];
        // "$" lopussa = koko sana (esim. "ilman" mutta ei "ilmanvaihto")
        if (g.charAt(g.length - 1) === '$' ? word === g.slice(0, -1) : word.indexOf(g) === 0) return GROUPS[i][0];
      }
    }
    return null;
  }

  function cityOf(word) {
    for (var i = 0; i < CITIES.length; i++) if (word.indexOf(CITIES[i]) === 0) return CITIES[i].slice(0, 4);
    return null;
  }

  // Tekstin "merkitykset": vartalot + ryhmät + paikkakunnat
  function features(text) {
    var words = norm(text).split(' ').filter(function (w) { return w.length >= 3 && !STOPSET[w]; });
    var out = { stems: [], groups: {}, cities: {}, words: [] };
    words.forEach(function (w) {
      out.stems.push(stem(w));
      var g = groupOf(w); if (g) out.groups[g] = 1;
      out.words.push({ s: stem(w), g: g });
      var c = cityOf(w); if (c) out.cities[c] = 1;
      // Yhdyssanat: "ikkunaremontti" sisältää "ikkuna" + "remontti"
      if (w.length > 9) {
        for (var k = 4; k < w.length - 4; k++) {
          var tail = w.slice(k);
          var g2 = groupOf(tail); if (g2) out.groups[g2] = 1;
        }
      }
    });
    return out;
  }

  function prepare(data) {
    var faq = (data.faq || []).map(function (f) {
      var q = features(f.q);
      return { f: f, q: q, a: features(f.a), qtext: norm(f.q), atext: norm(f.a), key: q.stems.slice().sort().join(' ') };
    });
    var pages = (data.pages || []).map(function (p) {
      return { p: p, t: features(p.t + ' ' + (p.d || '')), ttext: norm(p.t + ' ' + (p.d || '')) };
    });
    return { faq: faq, pages: pages };
  }

  function hasStem(text, s) {
    // Vartalo sanan alussa (ei keskellä sanaa)
    return (' ' + text).indexOf(' ' + s) !== -1;
  }

  /**
   * @return {Array} [{item, score, coverage}] parhaasta huonoimpaan
   */
  function search(index, query) {
    var qf = features(query);
    var qGroups = Object.keys(qf.groups);
    var qCities = Object.keys(qf.cities);
    if (!qf.words.length) return [];

    var results = index.faq.map(function (it) {
      var score = 0;
      qf.stems.forEach(function (s) {
        if (hasStem(it.qtext, s)) score += 3;
        else if (hasStem(it.atext, s)) score += 1;
      });
      qGroups.forEach(function (g) {
        if (it.q.groups[g]) score += 5;
        else if (it.a.groups[g]) score += 1;
      });
      // Kattavuus sanoittain: sana osuu, jos sen vartalo tai synonyymiryhmä löytyy
      var hit = 0;
      qf.words.forEach(function (w) {
        if (hasStem(it.qtext, w.s) || (w.g && it.q.groups[w.g])) hit++;
        else if (hasStem(it.atext, w.s) || (w.g && it.a.groups[w.g])) hit += 0.5;
      });
      // Paikkakunta: oikea kaupunki plussaa, väärä kaupunki miinusta
      var itCities = Object.keys(it.q.cities);
      if (qCities.length) {
        qCities.forEach(function (c) { if (it.q.cities[c] || it.a.cities[c]) score += 4; });
        if (itCities.length && !itCities.some(function (c) { return qCities.indexOf(c) !== -1; })) score -= 6;
      } else if (itCities.length) {
        score -= 3; // yleinen kysymys → yleinen vastaus ennen paikkakuntakohtaista
      }
      // Kuinka suuren osan UKK-kysymyksen omista sanoista asiakkaan kysymys kattaa
      var own = it.q.stems.length + Object.keys(it.q.groups).length;
      if (own) {
        var covered = 0;
        it.q.stems.forEach(function (s) { if (qf.stems.some(function (x) { return x.indexOf(s) === 0 || s.indexOf(x) === 0; })) covered++; });
        Object.keys(it.q.groups).forEach(function (g) { if (qf.groups[g]) covered++; });
        score += 6 * (covered / own);
      }
      // Lähes sama kysymys sanasta sanaan
      if (norm(query) === it.qtext) score += 10;
      return { item: it.f, key: it.key, score: score, coverage: hit / qf.words.length };
    });

    results.sort(function (a, b) { return (b.score - a.score) || (b.coverage - a.coverage); });
    return results;
  }

  function searchPages(index, query) {
    var qf = features(query);
    var qGroups = Object.keys(qf.groups);
    return index.pages.map(function (it) {
      var score = 0;
      qf.stems.forEach(function (s) { if (hasStem(it.ttext, s)) score += 2; });
      qGroups.forEach(function (g) { if (it.t.groups[g]) score += 2; });
      Object.keys(qf.cities).forEach(function (c) { if (it.t.cities[c]) score += 3; });
      return { item: it.p, score: score };
    }).filter(function (r) { return r.score > 0; }).sort(function (a, b) { return b.score - a.score; });
  }

  /**
   * Paras vastaus + liittyvät kysymykset + sivut, tai null jos varma osuma puuttuu.
   */
  function answer(index, query) {
    var res = search(index, query);
    var best = res[0];
    var confident = best && best.score >= 6 && best.coverage > 0.5;
    // Liittyvät kysymykset ilman lähes samoja muotoiluja ("Voiko…" / "Voiko myös…")
    var related = [], seen = {};
    if (confident) seen[best.key] = 1;
    for (var i = confident ? 1 : 0; i < res.length && related.length < 3; i++) {
      if (res[i].score < 4) break;
      if (seen[res[i].key]) continue;
      seen[res[i].key] = 1;
      related.push(res[i].item);
    }
    return {
      best: confident ? best.item : null,
      score: best ? best.score : 0,
      related: related,
      pages: searchPages(index, query).slice(0, 2).map(function (r) { return r.item; })
    };
  }

  var api = { prepare: prepare, answer: answer, search: search, features: features };
  if (typeof module !== 'undefined' && module.exports) module.exports = api;
  else root.NordicFaqMatch = api;
})(typeof self !== "undefined" ? self : globalThis);
