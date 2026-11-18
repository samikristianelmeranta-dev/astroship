#!/usr/bin/env python3
"""TiivisTupa-sivuston generaattori.

  python3 build.py            -> dist/    (tuotanto: /slug/index.html, sitemap.xml, robots.txt, llms.txt)
  python3 build.py preview    -> preview/ (esikatselu: litteät .html-tiedostot, suhteelliset linkit)
  python3 build.py wp         -> ../wordpress/tiivistupa-theme/content/pages.json (WordPress-teeman sivusisältö)
"""
import html, json, os, re, shutil, sys, importlib.util
from datetime import date

ROOT = os.path.dirname(os.path.abspath(__file__))
MODE = sys.argv[1] if len(sys.argv) > 1 else 'dist'
PREVIEW = MODE == 'preview'
WP = MODE == 'wp'
OUT = os.path.join(ROOT, 'preview' if PREVIEW else 'dist')
THEME = os.path.join(ROOT, '..', 'wordpress', 'tiivistupa-theme')

# --- Sivuston tiedot: muuta nämä, jos tiedot eivät pidä paikkaansa -------------------
SITE = {
    'brand': 'TiivisTupa',
    'domain': 'https://tiivistupa.fi',          # OLETUS: lopullinen verkkotunnus
    'legal': 'Nordic Ikkunat & Ovet Oy',        # OLETUS: TiivisTupa on saman yhtiön markkinointinimi
    'ytunnus': '3653098-6',
    'locality': 'Masku',
    'region': 'Varsinais-Suomi',
    'phone': '045 7832 5070',                   # OLETUS: sama numero kuin ikkunakauppias.fi (myynti)
    'phone_intl': '+358457832 5070'.replace(' ', ''),
    'email': 'info@ikkunakauppias.fi',          # OLETUS: vaihdetaan, kun TiivisTuvalla on oma osoite
    'form_action': '/tarjouspyynto/',           # OLETUS: lomakkeen vastaanottaja päätetään julkaisualustan mukaan
}

SERVICES = [
    ('ikkunahuolto', 'Ikkunahuolto', 'Tarkastus, säädöt, voitelut ja pienet korjaukset. Hyvä lähtökohta, kun ikkunoiden kunto ei ole tiedossa.'),
    ('ovihuolto', 'Ovihuolto', 'Ulko-, parveke- ja terassiovien käynnin, saranoiden ja lukituksen säätö sekä tiivisteiden tarkastus.'),
    ('ikkunoiden-tiivistys', 'Ikkunoiden tiivistys', 'Kovettuneet tiivisteet vaihdetaan uusiin ja puitteet säädetään. Veto loppuu ja välitila pysyy kuivana.'),
    ('ovien-tiivistys', 'Ovien tiivistys', 'Uudet karmi- ja kynnystiivisteet ja käyntivälin säätö. Eteisen veto ja melu vähenevät.'),
    ('ikkunoiden-korjaus', 'Ikkunoiden korjaus', 'Lahovaurioiden puupaikkaus, lasien ja helojen vaihto sekä vesipeltien korjaus.'),
    ('ikkunapuitteiden-huolto', 'Ikkunapuitteiden huolto', 'Puitteiden kunnostusmaalaus ja puukorjaukset. Ehjä maalipinta suojaa puuta säältä.'),
]
SERVICE_NAME = {s: n for s, n, _ in SERVICES}
CITIES = [  # slug, nimi, missä
    ('salo', 'Salo', 'Salossa'), ('kaarina', 'Kaarina', 'Kaarinassa'), ('naantali', 'Naantali', 'Naantalissa'),
    ('raisio', 'Raisio', 'Raisiossa'), ('parainen', 'Parainen', 'Paraisilla'), ('paimio', 'Paimio', 'Paimiossa'),
    ('lieto', 'Lieto', 'Liedossa'), ('masku', 'Masku', 'Maskussa'),
]
CITY_NAME = {c: n for c, n, _ in CITIES}
CITY_IN = {c: i for c, _, i in CITIES}

e = html.escape


def url(slug):
    """Sisäinen osoite: tuotannossa /slug/, esikatselussa slug.html."""
    slug = slug.strip('/')
    if PREVIEW:
        return (slug or 'index') + '.html'
    return '/' + (slug + '/' if slug else '')


def absurl(slug):
    slug = slug.strip('/')
    return SITE['domain'] + '/' + (slug + '/' if slug else '')


def fix_links(h):
    def rep(m):
        href = m.group(1)
        if href.startswith('#'):
            return m.group(0)
        return 'href="%s"' % url(href)
    return re.sub(r'href="([^"]*)"', rep, h)


WINDOW_SVG = '''<svg viewBox="0 0 320 360" role="img" aria-label="Ikkuna, jonka ulkopuolella on pakkasta ja sisäpuolella lämmin valo">
<rect x="10" y="10" width="300" height="340" rx="6" fill="#0a3540"/>
<rect x="30" y="30" width="260" height="300" fill="#f7fbfb"/>
<rect x="46" y="46" width="106" height="268" fill="#9fc6d2"/>
<rect x="168" y="46" width="106" height="268" fill="#f5ad1b"/>
<g fill="#ffffff"><circle cx="70" cy="80" r="4"/><circle cx="118" cy="120" r="3"/><circle cx="84" cy="170" r="4"/><circle cx="130" cy="215" r="3"/><circle cx="66" cy="250" r="3"/><circle cx="110" cy="285" r="4"/><circle cx="98" cy="62" r="2.5"/><circle cx="134" cy="160" r="2.5"/></g>
<rect x="168" y="46" width="106" height="268" fill="none" stroke="#c98700" stroke-width="6" stroke-dasharray="2 9" stroke-linecap="round"/>
<rect x="152" y="30" width="16" height="300" fill="#f7fbfb"/>
<rect x="156" y="150" width="8" height="46" rx="4" fill="#0a3540"/>
<rect x="0" y="338" width="320" height="22" rx="4" fill="#14262b"/>
</svg>'''

LOGO_SVG = '<svg viewBox="0 0 34 34" aria-hidden="true"><rect x="1" y="1" width="32" height="32" rx="5" fill="#f5ad1b"/><path d="M7 17 17 8l10 9v10H7z" fill="#0a3540"/><rect x="14" y="18" width="6" height="9" fill="#f5ad1b"/></svg>'


def load_pages():
    pages = []  # dict: slug, kind, service, city, data
    spec = importlib.util.spec_from_file_location('etusivu', os.path.join(ROOT, 'content', 'etusivu.py'))
    mod = importlib.util.module_from_spec(spec)
    spec.loader.exec_module(mod)
    pages.append({'slug': '', 'kind': 'front', 'service': None, 'city': None, 'data': mod.PAGE})
    for s, _, _ in SERVICES:
        path = os.path.join(ROOT, 'content', s + '.json')
        if not os.path.exists(path):
            print('PUUTTUU', path)
            continue
        d = json.load(open(path, encoding='utf-8'))
        pages.append({'slug': s, 'kind': 'service', 'service': s, 'city': None, 'data': d['main']})
        for c, _, _ in CITIES:
            if c in d.get('cities', {}):
                pages.append({'slug': f'{s}-{c}', 'kind': 'city', 'service': s, 'city': c, 'data': d['cities'][c]})
    return pages


def jsonld(page):
    d, slug = page['data'], page['slug']
    org = {
        '@type': 'HomeAndConstructionBusiness', '@id': SITE['domain'] + '/#yritys', 'name': SITE['brand'],
        'legalName': SITE['legal'], 'url': SITE['domain'] + '/', 'telephone': SITE['phone_intl'], 'email': SITE['email'],
        'taxID': SITE['ytunnus'],
        'address': {'@type': 'PostalAddress', 'addressLocality': SITE['locality'], 'addressRegion': SITE['region'], 'addressCountry': 'FI'},
        'areaServed': [{'@type': 'City', 'name': 'Turku'}] + [{'@type': 'City', 'name': n} for _, n, _ in CITIES]
                      + [{'@type': 'AdministrativeArea', 'name': 'Varsinais-Suomi'}],
        'knowsAbout': [n for _, n, _ in SERVICES] + ['Ikkunoiden ja ovien huoltoremontti'],
    }
    crumbs = [('Etusivu', '')]
    if page['kind'] in ('service', 'city'):
        crumbs.append((SERVICE_NAME[page['service']], page['service']))
    if page['kind'] == 'city':
        crumbs.append((CITY_NAME[page['city']], slug))
    if page['kind'] == 'hub':
        crumbs.append(('Palvelualue', slug))
    graph = [org, {
        '@type': 'WebPage', '@id': absurl(slug) + '#sivu', 'url': absurl(slug), 'name': d['title'], 'description': d['meta'],
        'inLanguage': 'fi', 'isPartOf': {'@id': SITE['domain'] + '/#sivusto'}, 'about': {'@id': SITE['domain'] + '/#yritys'},
        'dateModified': date.today().isoformat(),
    }, {
        '@type': 'BreadcrumbList', 'itemListElement': [
            {'@type': 'ListItem', 'position': i + 1, 'name': n, 'item': absurl(s)} for i, (n, s) in enumerate(crumbs)],
    }]
    if page['kind'] == 'front':
        graph.append({'@type': 'WebSite', '@id': SITE['domain'] + '/#sivusto', 'url': SITE['domain'] + '/', 'name': SITE['brand'],
                      'inLanguage': 'fi', 'publisher': {'@id': SITE['domain'] + '/#yritys'}})
    if page['kind'] in ('front', 'service', 'city'):
        area = ([{'@type': 'City', 'name': CITY_NAME[page['city']]}] if page['city'] else
                [{'@type': 'City', 'name': 'Turku'}, {'@type': 'AdministrativeArea', 'name': 'Varsinais-Suomi'}])
        graph.append({'@type': 'Service', '@id': absurl(slug) + '#palvelu',
                      'name': d['h1'], 'serviceType': SERVICE_NAME.get(page['service'], 'Ikkunoiden ja ovien huoltoremontti'),
                      'description': d['meta'], 'provider': {'@id': SITE['domain'] + '/#yritys'}, 'areaServed': area, 'url': absurl(slug)})
    if d.get('faq'):
        graph.append({'@type': 'FAQPage', '@id': absurl(slug) + '#ukk', 'mainEntity': [
            {'@type': 'Question', 'name': f['q'], 'acceptedAnswer': {'@type': 'Answer', 'text': f['a']}} for f in d['faq']]})
    return json.dumps({'@context': 'https://schema.org', '@graph': graph}, ensure_ascii=False, separators=(',', ':'))


def header(page):
    cur = page['service'] if page['kind'] in ('service', 'city') else None
    nav = ''.join('<li><a href="%s"%s>%s</a></li>' % (url(s), ' aria-current="page"' if s == cur else '', e(n)) for s, n, _ in SERVICES)
    nav += '<li><a href="%s"%s>Palvelualue</a></li>' % (url('palvelualue'), ' aria-current="page"' if page['kind'] == 'hub' else '')
    flag = '<div class="preview-flag">Esikatselu: sisältö ja yhteystiedot ovat luonnoksia, lomake ei lähetä viestejä.</div>' if PREVIEW else ''
    return f'''{flag}<header class="site-head">
<div class="wrap head-row">
<a class="logo" href="{url('')}" aria-label="TiivisTupa, etusivu">{LOGO_SVG}<span>Tiivis<b>Tupa</b></span></a>
<div class="head-actions"><span class="head-phone">{e(SITE['phone'])}</span><a class="btn btn-primary btn-small" href="#tarjous">Pyydä tarjous</a></div>
</div>
<nav class="nav" aria-label="Palvelut"><div class="wrap"><ul>{nav}</ul></div></nav>
</header>'''


def hero(page):
    d = page['data']
    crumbs = [('Etusivu', '')]
    if page['kind'] in ('service', 'city'):
        crumbs.append((SERVICE_NAME[page['service']], page['service']))
    if page['kind'] == 'city':
        crumbs.append((CITY_NAME[page['city']], None))
    if page['kind'] == 'hub':
        crumbs.append(('Palvelualue', None))
    bc = ''
    if len(crumbs) > 1:
        parts = []
        for i, (n, s) in enumerate(crumbs):
            last = i == len(crumbs) - 1
            parts.append(f'<span aria-current="page">{e(n)}</span>' if last else f'<a href="{url(s)}">{e(n)}</a>')
        bc = '<nav class="crumbs" aria-label="Murupolku">' + '<span aria-hidden="true">›</span>'.join(parts) + '</nav>'
    art = f'<div class="hero-art">{WINDOW_SVG}</div>' if page['kind'] in ('front', 'service') else ''
    cls = 'hero' if art else 'hero is-city'
    place = CITY_IN[page['city']] if page['city'] else 'Turussa ja Varsinais-Suomessa'
    return f'''<section class="{cls}"><div class="wrap hero-grid"><div class="hero-text">
{bc}<h1>{e(d['h1'])}</h1>
<p class="lead">{e(d['lead'])}</p>
<div class="cta-row"><a class="btn btn-primary" href="#tarjous">Pyydä maksuton kartoitus</a><a class="btn btn-ghost" href="#sisalto">Lue, mitä työ sisältää</a></div>
<ul class="hero-points"><li>Maksuton kartoituskäynti</li><li>Kirjallinen tarjous</li><li>Kotitalousvähennys työstä</li><li>Palvelemme {e(place)}</li></ul>
</div>{art}</div></section><div class="seal" aria-hidden="true"></div>'''


def body_sections(page):
    d = page['data']
    toc = ''.join(f'<li><a href="#{s["id"]}">{e(s["h2"])}</a></li>' for s in d['sections'])
    toc += '<li><a href="#ukk">Usein kysytyt kysymykset</a></li>'
    out = [f'''<section class="band" id="sisalto"><div class="wrap intro">
<div class="answer"><p class="label">Lyhyt vastaus</p><p>{e(d['short_answer'])}</p></div>
<nav class="toc" aria-label="Sisällys"><p class="label">Tällä sivulla</p><ol>{toc}</ol></nav>
</div></section>''']
    secs = d['sections']
    cut = max(3, len(secs) // 2)

    def block(items):
        return '<section class="band is-paper"><div class="wrap">' + ''.join(
            f'<div class="section prose" id="{s["id"]}"><h2>{e(s["h2"])}</h2>{fix_links(s["html"].strip())}</div>' for s in items) + '</div></section>'
    out.append(block(secs[:cut]))
    if page['kind'] == 'front':
        cards = ''.join(f'<a class="card" href="{url(s)}"><h3>{e(n)}</h3><p>{e(t)}</p><span class="more">Lue lisää →</span></a>' for s, n, t in SERVICES)
        out.append(f'<section class="band"><div class="wrap"><div class="band-head"><h2>Valitse palvelu</h2><p>Kuusi palvelua, joista kootaan juuri sinun talosi tarvitsema huoltoremontti.</p></div><div class="cards">{cards}</div></div></section>')
    else:
        out.append('<section class="cta-band"><div class="wrap"><p>Etkö ole varma, mitä ikkunasi tai ovesi tarvitsevat? Kartoitus on maksuton.</p><a class="btn btn-primary" href="#tarjous">Pyydä kartoitus</a></div></section>')
    out.append(block(secs[cut:]))
    faq = ''.join(f'<div class="faq-item"><h3>{e(f["q"])}</h3><p>{e(f["a"])}</p></div>' for f in d['faq'])
    out.append(f'<section class="band" id="ukk"><div class="wrap"><div class="band-head"><h2>Usein kysytyt kysymykset</h2></div><div class="faq">{faq}</div></div></section>')
    out.append(related(page))
    return ''.join(out)


def related(page):
    cols = []
    if page['kind'] == 'city':
        s, c = page['service'], page['city']
        cols.append((f'{SERVICE_NAME[s]} muilla paikkakunnilla',
                     [(f'{SERVICE_NAME[s]} {CITY_NAME[c2]}', f'{s}-{c2}') for c2, _, _ in CITIES if c2 != c] + [(f'{SERVICE_NAME[s]} Turku ja Varsinais-Suomi', s)]))
        cols.append((f'Muut palvelut {CITY_IN[c]}', [(f'{n} {CITY_NAME[c]}', f'{s2}-{c}') for s2, n, _ in SERVICES if s2 != s]))
    elif page['kind'] == 'service':
        s = page['service']
        cols.append((f'{SERVICE_NAME[s]} paikkakunnittain', [(f'{SERVICE_NAME[s]} {n}', f'{s}-{c}') for c, n, _ in CITIES]))
        cols.append(('Muut palvelut', [(n, s2) for s2, n, _ in SERVICES if s2 != s] + [('Ikkunoiden ja ovien huoltoremontti', '')]))
    else:
        cols.append(('Palvelut', [(n, s) for s, n, _ in SERVICES]))
        cols.append(('Paikkakunnat', [(f'Ikkunahuolto {n}', f'ikkunahuolto-{c}') for c, n, _ in CITIES] + [('Kaikki aluesivut', 'palvelualue')]))
    h = ''.join('<div><h3>%s</h3><ul>%s</ul></div>' % (e(t), ''.join(f'<li><a href="{url(s)}">{e(n)}</a></li>' for n, s in links)) for t, links in cols)
    return f'<section class="band is-paper"><div class="wrap"><div class="band-head"><h2>Katso myös</h2></div><div class="links">{h}</div></div></section>'


def quote(page):
    opts = '<option value="">Valitse</option><option>Turku</option>' + ''.join(
        '<option%s>%s</option>' % (' selected' if c == page['city'] else '', e(n)) for c, n, _ in CITIES) + '<option>Muu Varsinais-Suomi</option>'
    checks = ''.join('<label><input type="checkbox" name="palvelu" value="%s"%s> %s</label>' % (e(n), ' checked' if s == page['service'] else '', e(n)) for s, n, _ in SERVICES)
    return f'''<div class="seal" aria-hidden="true"></div><section class="quote" id="tarjous"><div class="wrap quote-grid">
<div class="quote-text"><h2>Pyydä maksuton kartoitus ja tarjous</h2>
<p>Kerro lyhyesti, mitä ikkunoissasi tai ovissasi on vialla. Vastaamme viimeistään seuraavana arkipäivänä ja sovimme kartoituskäynnin. Tarjous on kirjallinen eikä sido sinua mihinkään.</p>
<ul class="contact-list"><li><span>Puhelin</span><b>{e(SITE['phone'])}</b></li><li><span>Sähköposti</span><b>{e(SITE['email'])}</b></li><li><span>Tukikohta</span>{e(SITE['locality'])}, {e(SITE['region'])}</li></ul></div>
<form class="quote-form" id="quote-form" action="{e(SITE['form_action'])}" method="post" novalidate>
<div class="field"><label for="q-nimi">Nimi</label><input id="q-nimi" name="nimi" type="text" autocomplete="name" required></div>
<div class="field"><label for="q-puhelin">Puhelin</label><input id="q-puhelin" name="puhelin" type="tel" autocomplete="tel" required></div>
<div class="field"><label for="q-email">Sähköposti</label><input id="q-email" name="email" type="email" autocomplete="email"></div>
<div class="field"><label for="q-kunta">Paikkakunta</label><select id="q-kunta" name="paikkakunta">{opts}</select></div>
<fieldset class="field full"><legend>Mitä palvelua tarvitset?</legend><div class="checks">{checks}</div></fieldset>
<div class="field full"><label for="q-viesti">Kuvaile tilanne</label><textarea id="q-viesti" name="viesti" placeholder="Esim. omakotitalo 1982, 14 ikkunaa, makuuhuoneissa vetää ja ulkopuitteiden maali hilseilee."></textarea></div>
<div class="field full"><button class="btn btn-primary" type="submit" id="q-laheta">Lähetä tarjouspyyntö</button></div>
<p class="form-status" id="q-status" hidden></p>
<p class="form-note">Käytämme tietojasi vain tarjouspyyntösi käsittelyyn.</p>
</form></div></section>'''


def footer():
    sv = ''.join(f'<li><a href="{url(s)}">{e(n)}</a></li>' for s, n, _ in SERVICES)
    ct = ''.join(f'<li><a href="{url("ikkunahuolto-" + c)}">{e(n)}</a></li>' for c, n, _ in CITIES)
    return f'''<footer class="site-foot"><div class="wrap foot-grid">
<div><h2>TiivisTupa</h2><p>Ikkunoiden ja ovien huoltoremontit Turussa ja Varsinais-Suomessa: huolto, tiivistys, korjaus ja puitteiden maalaus.</p></div>
<div><h2>Palvelut</h2><ul>{sv}</ul></div>
<div><h2>Paikkakunnat</h2><ul>{ct}<li><a href="{url('palvelualue')}">Kaikki aluesivut</a></li></ul></div>
<div><h2>Yhteystiedot</h2><ul><li>{e(SITE['phone'])}</li><li>{e(SITE['email'])}</li><li>{e(SITE['locality'])}, {e(SITE['region'])}</li></ul></div>
</div><div class="wrap foot-bottom"><span>TiivisTupa on {e(SITE['legal'])}:n markkinointinimi. Y-tunnus {e(SITE['ytunnus'])}.</span><span>© {date.today().year} TiivisTupa</span></div></footer>'''


SCRIPT = '''<script>
(function(){var f=document.getElementById('quote-form');if(!f)return;var s=document.getElementById('q-status');
f.addEventListener('submit',function(ev){var n=document.getElementById('q-nimi'),p=document.getElementById('q-puhelin');
if(!n.value.trim()||!p.value.trim()){ev.preventDefault();s.hidden=false;s.textContent='Täytä vähintään nimi ja puhelinnumero.';(n.value.trim()?p:n).focus();return;}
if(%s){ev.preventDefault();s.hidden=false;s.textContent='Esikatselussa lomake ei lähetä viestiä. Julkaistulla sivustolla pyyntö lähtee TiivisTuvalle.';}});})();
</script>''' % ('true' if PREVIEW else 'false')

FONTS = '<link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin><link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:opsz,wght@12..96,600;12..96,700&family=Source+Sans+3:wght@400;600;700&display=swap">'


def render(page, css):
    d, slug = page['data'], page['slug']
    title = d['title'] + ' | TiivisTupa'
    main = header(page) + '<main>' + hero(page) + (page.get('body') or body_sections(page)) + quote(page) + '</main>' + footer() + SCRIPT
    ld = f'<script type="application/ld+json">{jsonld(page)}</script>'
    if PREVIEW and slug == '':
        # Artifact-kuori lisää doctypen ja headin itse.
        return f'<title>TiivisTupa-sivusto</title>\n<meta name="claude:viewport-fit-sides" content="cover">\n{FONTS}\n<style>{css}</style>\n{main}\n{ld}'
    style = f'<style>{css}</style>' if PREVIEW else '<link rel="stylesheet" href="/assets/site.css">'
    seo = '' if PREVIEW else f'''<link rel="canonical" href="{absurl(slug)}">
<meta name="robots" content="index,follow,max-snippet:-1,max-image-preview:large">
<meta property="og:type" content="website"><meta property="og:site_name" content="TiivisTupa"><meta property="og:locale" content="fi_FI">
<meta property="og:title" content="{e(d['title'])}"><meta property="og:description" content="{e(d['meta'])}"><meta property="og:url" content="{absurl(slug)}">
<meta name="twitter:card" content="summary"><meta name="geo.region" content="FI-19"><meta name="geo.placename" content="{e(CITY_NAME.get(page['city'], 'Turku'))}">
<link rel="icon" href="/assets/favicon.svg" type="image/svg+xml">'''
    return f'''<!doctype html>
<html lang="fi"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover">
<title>{e(title)}</title><meta name="description" content="{e(d['meta'])}">
{seo}
{FONTS}
{style}
{ld}
</head><body>{main}</body></html>'''


def hub_page():
    rows = ''
    for c, n, inn in CITIES:
        chips = ''.join(f'<li><a href="{url(s + "-" + c)}">{e(sn)} {e(n)}</a></li>' for s, sn, _ in SERVICES)
        rows += f'<div class="section prose" id="{c}" style="max-width:none"><h2>{e(n)}</h2><ul class="chips">{chips}</ul></div>'
    body = f'''<section class="band is-paper" id="sisalto"><div class="wrap"><div class="prose"><p>TiivisTupa tekee ikkunoiden ja ovien huoltoremontteja Turussa ja koko Varsinais-Suomessa. Tältä sivulta löydät jokaisen palvelun paikkakuntakohtaisen sivun. Aluesivuilla kerromme, millaista rakennuskantaa paikkakunnalla on, mitkä viat ovat siellä tyypillisiä ja miten työ käytännössä etenee.</p><p>Jos paikkakuntaasi ei ole listassa, palvelemme silti. Tukikohtamme on Maskussa, ja liikumme koko maakunnan alueella.</p></div></div></section>
<section class="band"><div class="wrap">{rows}</div></section>'''
    data = {'title': 'Palvelualue: ikkunoiden ja ovien huolto Varsinais-Suomessa',
            'meta': 'TiivisTuvan palvelualue: ikkunahuolto, ovihuolto, tiivistykset, korjaukset ja puitteiden maalaus Salossa, Kaarinassa, Naantalissa, Raisiossa ja muualla.',
            'h1': 'Palvelualue: Turku ja Varsinais-Suomi',
            'lead': 'Kaikki paikkakuntakohtaiset sivut yhdessä paikassa. Valitse paikkakunta ja palvelu.', 'faq': []}
    return {'slug': 'palvelualue', 'kind': 'hub', 'service': None, 'city': None, 'data': data, 'body': body}


def export_wp():
    pages = load_pages() + [hub_page()]
    out = []
    for p in pages:
        d = p['data']
        body = hero(p) + (p.get('body') or body_sections(p))
        out.append({'slug': p['slug'] or 'etusivu', 'front': p['slug'] == '', 'kind': p['kind'], 'service': p['service'], 'city': p['city'],
                    'seo_title': d['title'], 'meta': d['meta'], 'h1': d['h1'], 'content': body})
    os.makedirs(os.path.join(THEME, 'content'), exist_ok=True)
    json.dump(out, open(os.path.join(THEME, 'content', 'pages.json'), 'w', encoding='utf-8'), ensure_ascii=False)
    print(len(out), 'sivua -> pages.json')


def main():
    if WP:
        return export_wp()
    if os.path.exists(OUT):
        shutil.rmtree(OUT)
    os.makedirs(OUT)
    css = open(os.path.join(ROOT, 'assets', 'site.css'), encoding='utf-8').read()
    pages = load_pages() + [hub_page()]
    for p in pages:
        h = render(p, css)
        if PREVIEW:
            path = os.path.join(OUT, (p['slug'] or 'index') + '.html')
        else:
            path = os.path.join(OUT, p['slug'], 'index.html')
            os.makedirs(os.path.dirname(path), exist_ok=True)
        open(path, 'w', encoding='utf-8').write(h)
    if not PREVIEW:
        os.makedirs(os.path.join(OUT, 'assets'))
        shutil.copy(os.path.join(ROOT, 'assets', 'site.css'), os.path.join(OUT, 'assets', 'site.css'))
        open(os.path.join(OUT, 'assets', 'favicon.svg'), 'w').write(LOGO_SVG.replace('<svg ', '<svg xmlns="http://www.w3.org/2000/svg" '))
        today = date.today().isoformat()
        sm = ''.join(f'<url><loc>{absurl(p["slug"])}</loc><lastmod>{today}</lastmod></url>' for p in pages)
        open(os.path.join(OUT, 'sitemap.xml'), 'w').write(f'<?xml version="1.0" encoding="UTF-8"?><urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">{sm}</urlset>')
        open(os.path.join(OUT, 'robots.txt'), 'w').write(f'User-agent: *\nAllow: /\n\nSitemap: {SITE["domain"]}/sitemap.xml\n')
        ll = [f'# {SITE["brand"]}', '', '> TiivisTupa tekee ikkunoiden ja ovien huoltoremontteja Turussa ja Varsinais-Suomessa: ikkunahuolto, ovihuolto, ikkunoiden ja ovien tiivistys, ikkunoiden korjaus sekä ikkunapuitteiden maalaus ja korjaus. Tukikohta on Maskussa. Kartoituskäynti on maksuton ja tarjous kirjallinen.', '', '## Palvelut', '']
        for p in pages:
            if p['kind'] in ('front', 'service'):
                ll.append(f'- [{p["data"]["h1"]}]({absurl(p["slug"])}): {p["data"]["meta"]}')
        ll += ['', '## Paikkakuntasivut', '']
        for p in pages:
            if p['kind'] == 'city':
                ll.append(f'- [{p["data"]["h1"]}]({absurl(p["slug"])}): {p["data"]["meta"]}')
        ll += ['', '## Yhteystiedot', '', f'- Puhelin: {SITE["phone"]}', f'- Sähköposti: {SITE["email"]}', f'- Yritys: {SITE["legal"]}, Y-tunnus {SITE["ytunnus"]}, {SITE["locality"]}', '']
        open(os.path.join(OUT, 'llms.txt'), 'w', encoding='utf-8').write('\n'.join(ll))
    print(len(pages), 'sivua ->', OUT)


if __name__ == '__main__':
    main()
