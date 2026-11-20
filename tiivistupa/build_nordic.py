#!/usr/bin/env python3
"""Huoltosivut ikkunakauppias.fi:n alle (nordic-theme).

Lukee samat sisältötiedostot kuin TiivisTupa-sivusto ja tuottaa ne ikkunakauppias.fi:n sivupohjaan:
  ../wordpress/nordic-theme/content/huolto.html        Huolto-pääsivu (/huolto/)
  ../wordpress/nordic-theme/content/huolto-sivut.json  6 palvelusivua + 48 aluesivua
  ../wordpress/sivut/huolto.html + huolto-SIVUTIEDOT.txt (käsin liitettävä versio)
TiivisTuvan etusivu jää pois: sen tilalla on /huolto/.
"""
import html, importlib.util, json, os, re

ROOT = os.path.dirname(os.path.abspath(__file__))
WP = os.path.join(ROOT, '..', 'wordpress')
e = html.escape

SERVICES = [
    ('ikkunahuolto', 'Ikkunahuolto', 'Tarkastus, säädöt, voitelut ja pienet korjaukset. Hyvä lähtökohta, kun ikkunoiden kunto ei ole tiedossa.'),
    ('ovihuolto', 'Ovihuolto', 'Ulko-, parveke- ja terassiovien käynnin, saranoiden ja lukituksen säätö sekä tiivisteiden tarkastus.'),
    ('ikkunoiden-tiivistys', 'Ikkunoiden tiivistys', 'Kovettuneet tiivisteet vaihdetaan uusiin ja puitteet säädetään. Veto loppuu ja välitila pysyy kuivana.'),
    ('ovien-tiivistys', 'Ovien tiivistys', 'Uudet karmi- ja kynnystiivisteet ja käyntivälin säätö. Eteisen veto ja melu vähenevät.'),
    ('ikkunoiden-korjaus', 'Ikkunoiden korjaus', 'Lahovaurioiden puupaikkaus, lasien ja helojen vaihto sekä vesipeltien korjaus.'),
    ('ikkunapuitteiden-huolto', 'Ikkunapuitteiden huolto', 'Puitteiden kunnostusmaalaus ja puukorjaukset. Ehjä maalipinta suojaa puuta säältä.'),
]
SERVICE_NAME = {s: n for s, n, _ in SERVICES}
CITIES = [('salo', 'Salo', 'Salossa'), ('kaarina', 'Kaarina', 'Kaarinassa'), ('naantali', 'Naantali', 'Naantalissa'),
          ('raisio', 'Raisio', 'Raisiossa'), ('parainen', 'Parainen', 'Paraisilla'), ('paimio', 'Paimio', 'Paimiossa'),
          ('lieto', 'Lieto', 'Liedossa'), ('masku', 'Masku', 'Maskussa')]
CITY_NAME = {c: n for c, n, _ in CITIES}
CITY_IN = {c: i for c, _, i in CITIES}

BRAND = [('TiivisTuvan', 'Ikkunakauppiaan'), ('TiivisTupa', 'Ikkunakauppias')]


def brand(s):
    for a, b in BRAND:
        s = s.replace(a, b)
    return s


def fix(h):
    """Sisällön HTML ikkunakauppias.fi:n tyyleihin: listat, linkit ja brändi."""
    h = brand(h.strip())
    h = h.replace('<ul>', '<ul class="bullet">').replace('<ol>', '<ol class="steps">')
    h = h.replace('href="/"', 'href="/huolto/"')
    return h


def page_html(d, kind, service=None, city=None, extra=''):
    toc = ''.join(f'<li><a href="#{s["id"]}">{e(brand(s["h2"]))}</a></li>' for s in d['sections'])
    toc += '<li><a href="#ukk">Usein kysytyt kysymykset</a></li>'
    secs = ''.join(f'''
  <section class="article-body" id="{s['id']}">
    <div class="wrap">
      <h2>{e(brand(s['h2']))}</h2>
      {fix(s['html'])}
    </div>
  </section>
''' for s in d['sections'])
    faq = ''.join(f'<div class="faq-item"><h3>{e(brand(f["q"]))}</h3><p>{e(brand(f["a"]))}</p></div>' for f in d['faq'])
    return f'''<main>

  <header class="v4hero is-text">
    <div class="wrap v4hero-wrap">
      <div class="v4hero-text">
        <h1>{e(brand(d['h1']))}</h1>
        <p>{e(brand(d['lead']))}</p>
        <div class="cta-row">
          <a class="btn btn-primary" href="#tarjous" data-quote>Pyyd&auml;&nbsp;ilmainen&nbsp;kartoitus</a>
          <a class="btn btn-outline-pine" href="#sisalto">Lue, mitä työ sisältää</a>
        </div>
      </div>
    </div>
  </header>

  <section class="intro" id="sisalto">
    <div class="wrap">
      <div class="callout">
        <p>Lyhyt vastaus</p>
        <p>{e(brand(d['short_answer']))}</p>
      </div>
      <nav class="toc" aria-label="Sisällys">
        <p class="toc-title">Tällä sivulla</p>
        <ol>{toc}</ol>
      </nav>
    </div>
  </section>
{secs}{extra}
  <section class="article-body" id="ukk">
    <div class="wrap">
      <h2>Usein kysytyt kysymykset</h2>
      <div class="faq-list">{faq}</div>
    </div>
  </section>
{related(kind, service, city)}
  <section class="final-cta" id="tarjous">
    <div class="wrap">
      <h2>Pyydä ilmainen kartoitus ja tarjous</h2>
      <p>Kerro lyhyesti, mitä ikkunoissasi tai ovissasi on vialla. Vastaamme viimeistään seuraavana arkipäivänä ja sovimme kartoituskäynnin. Tarjous on kirjallinen eikä sido sinua mihinkään.</p>
      <div class="cta-row">
        <a class="btn btn-primary" href="#tarjous" data-quote>Pyyd&auml;&nbsp;ilmainen&nbsp;tarjous</a>
      </div>
    </div>
  </section>

</main>
'''


def links(title, items):
    lis = ''.join(f'<li><a href="/{s}/">{e(n)}</a></li>' for n, s in items)
    return f'<h3>{e(title)}</h3><ul class="area-list">{lis}</ul>'


def related(kind, service, city):
    if kind == 'city':
        a = links(f'{SERVICE_NAME[service]} muilla paikkakunnilla', [(CITY_NAME[c], f'{service}-{c}') for c, _, _ in CITIES if c != city] + [('Turku ja Varsinais-Suomi', service)])
        b = links(f'Muut huoltopalvelut {CITY_IN[city]}', [(n, f'{s}-{city}') for s, n, _ in SERVICES if s != service])
        c = links('Uudet ikkunat ja ovet', [(f'Ikkunaremontti {CITY_NAME[city]}', f'ikkunat-ikkunaremontti-{city}'), ('Kaikki huoltopalvelut', 'huolto')])
    elif kind == 'service':
        a = links(f'{SERVICE_NAME[service]} paikkakunnittain', [(CITY_NAME[c], f'{service}-{c}') for c, _, _ in CITIES])
        b = links('Muut huoltopalvelut', [(n, s) for s, n, _ in SERVICES if s != service] + [('Ikkuna- ja ovihuolto', 'huolto')])
        c = links('Kun huolto ei enää riitä', [('Ikkunaremontti', 'ikkunaremontti'), ('Oviremontti', 'oviremontti')])
    else:
        return ''
    return f'''
  <section class="article-body" id="katso-myos">
    <div class="wrap">
      <h2>Katso myös</h2>
      {a}
      {b}
      {c}
    </div>
  </section>
'''


def hub():
    spec = importlib.util.spec_from_file_location('etusivu', os.path.join(ROOT, 'content', 'etusivu.py'))
    mod = importlib.util.module_from_spec(spec)
    spec.loader.exec_module(mod)
    d = json.loads(json.dumps(mod.PAGE))
    d['title'] = 'Ikkuna- ja ovihuolto Turku – huolto, tiivistys ja korjaus'
    d['meta'] = 'Ikkuna- ja ovihuolto Turussa ja Varsinais-Suomessa: ikkunahuolto, ovihuolto, tiivistys, korjaus ja puitteiden maalaus. Ilmainen kartoitus ja tarjous.'
    d['h1'] = 'Ikkuna- ja ovihuolto Turussa ja Varsinais-Suomessa'
    d['lead'] = ('Huollamme, tiivistämme, korjaamme ja maalaamme ikkunat ja ovet niin, että veto loppuu ja rakenteet kestävät taas vuosia. '
                 'Ikkunoiden ja ovien huoltoremontti on usein järkevin ja edullisin vaihtoehto silloin, kun karmit ja puitteet ovat vielä terveet. '
                 'Kartoitamme kunnon maksutta ja kerromme suoraan, mikä kannattaa huoltaa ja mikä vaihtaa.')
    d['short_answer'] = ('Ikkuna- ja ovihuolto tarkoittaa, että vanhat ikkunat ja ovet kunnostetaan vaihtamisen sijaan. Siihen kuuluu ikkunahuolto ja ovihuolto, '
                         'ikkunoiden ja ovien tiivistys, ikkunoiden korjaus sekä ikkunapuitteiden maalaus. Huolto kannattaa, kun karmit ja puitteet ovat pääosin terveet: '
                         'se vähentää vetoa, parantaa asumismukavuutta ja pidentää käyttöikää. Teemme huollot Turussa ja koko Varsinais-Suomessa, muun muassa Salossa, '
                         'Kaarinassa, Naantalissa, Raisiossa, Paraisilla, Paimiossa, Liedossa ja Maskussa. Työ alkaa maksuttomalla kartoituskäynnillä, ja saat kirjallisen, '
                         'sitoumuksettoman tarjouksen. Työn osuudesta voi saada kotitalousvähennystä.')
    out = []
    for s in d['sections']:
        if s['id'] == 'mika':
            s['h2'] = 'Mitä ikkuna- ja ovihuolto sisältää?'
        if s['id'] == 'merkit':
            s['h2'] = 'Mistä tunnistat, että ikkunat ja ovet tarvitsevat huoltoa?'
        if s['id'] == 'huolto-vai-vaihto':
            s['h2'] = 'Ikkunahuolto vai uudet ikkunat?'
            s['html'] = re.sub(r'\n<p>TiivisTupa on Nordic.*?</p>', '', s['html'], flags=re.S)
            s['html'] += ('\n<p>Jos ikkunat tai ovet on järkevintä vaihtaa, teemme senkin: katso <a href="/ikkunaremontti/">ikkunaremontti</a> ja '
                          '<a href="/oviremontti/">oviremontti</a>. Päätöksen tueksi kannattaa lukea oppaat <a href="/ikkunaremontti-vai-kunnostus/">ikkunaremontti vai kunnostus</a> ja '
                          '<a href="/merkit-etta-ikkunat-pitaa-uusia/">merkit, että ikkunat pitää uusia</a>.</p>')
        if s['id'] == 'palvelut':
            s['h2'] = 'Huoltopalvelut: ikkunahuolto, ovihuolto, tiivistys ja korjaus'
        if s['id'] == 'eteneminen':
            s['h2'] = 'Näin ikkuna- ja ovihuolto etenee'
        if s['id'] == 'hinta':
            s['h2'] = 'Mitä ikkuna- ja ovihuolto maksaa?'
        if s['id'] == 'alue':
            out.append({'id': 'turku', 'h2': 'Ikkuna- ja ovihuolto Turussa', 'html': '''
<p>Turussa on ikkunoita ja ovia jokaiselta vuosikymmeneltä, ja huollon tarve vaihtelee alueen mukaan. Puutaloalueilla, kuten Port Arthurissa ja Raunistulassa, alkuperäiset puuikkunat halutaan säilyttää, ja työ painottuu tiivistykseen, kittauksiin ja puitteiden maalaukseen. Lähiöiden kerros- ja rivitaloissa, esimerkiksi Runosmäessä ja Varissuolla, tyypillisiä ovat kovettuneet tiivisteet ja säätöä kaipaavat helat. Hirvensalon ja muiden omakotialueiden taloissa huolto on usein ensimmäinen isompi huoltokierros, kun ikkunat ovat olleet käytössä pari vuosikymmentä.</p>
<p>Meren ja Aurajoen läheisyys tuo kosteutta ja tuulta, mikä kuluttaa ulkopuitteiden maalipintaa ja tiivisteitä. Siksi ikkunoiden kunto kannattaa tarkastaa säännöllisesti ja korjata pienet viat ennen kuin puu ehtii vaurioitua.</p>
<ul>
<li><a href="/ikkunahuolto/">Ikkunahuolto Turku</a>: tarkastus, säädöt ja voitelut</li>
<li><a href="/ovihuolto/">Ovihuolto Turku</a>: ulko-, parveke- ja terassiovet</li>
<li><a href="/ikkunoiden-tiivistys/">Ikkunoiden tiivistys Turku</a>: uudet tiivisteet ja säätö</li>
<li><a href="/ovien-tiivistys/">Ovien tiivistys Turku</a>: karmi- ja kynnystiivisteet</li>
<li><a href="/ikkunoiden-korjaus/">Ikkunoiden korjaus Turku</a>: laho, lasit ja helat</li>
<li><a href="/ikkunapuitteiden-huolto/">Ikkunapuitteiden maalaus ja korjaus Turku</a></li>
</ul>
'''})
            s['h2'] = 'Palvelualue: Turku ja koko Varsinais-Suomi'
            grid = ''.join('<h3>%s</h3><ul class="area-list">%s</ul>' % (e(n), ''.join(f'<li><a href="/{sv}-{c}/">{e(sn)} {e(n)}</a></li>' for sv, sn, _ in SERVICES)) for c, n, _ in CITIES)
            s['html'] = ('<p>Tukikohtamme on Maskussa, ja teemme ikkuna- ja ovihuoltoja Turussa ja kaikkialla Varsinais-Suomessa. Jokaisella palvelulla on oma sivunsa jokaiselle paikkakunnalle. '
                         'Niillä kerromme paikallisesta rakennuskannasta, tyypillisistä vioista ja siitä, miten työ käytännössä etenee.</p>' + grid +
                         '<p>Saaristossa ja rannikolla meri-ilmasto kuluttaa tiivisteitä ja maalipintoja sisämaata nopeammin, joten huoltoväli on lyhyempi. Saarikohteissa sovimme käynnit kulkuyhteyksien mukaan. '
                         'Jos paikkakuntaasi ei ole listassa, palvelemme silti. Tiimin ja numerot löydät <a href="/yhteystiedot/">yhteystiedoista</a>.</p>')
            s['raw'] = True
        out.append(s)
    d['sections'] = out
    d['faq'] = [f for f in d['faq'] if 'takana' not in f['q']]
    for f in d['faq']:
        f['q'] = f['q'].replace('Missä TiivisTupa tekee huoltoremontteja?', 'Missä teette ikkuna- ja ovihuoltoja?')
        f['a'] = f['a'].replace('TiivisTupa tekee ikkunoiden ja ovien huoltoremontteja', 'Teemme ikkuna- ja ovihuoltoja')
    d['faq'].insert(0, {'q': 'Mitä ikkuna- ja ovihuolto Turussa sisältää?', 'a': 'Ikkuna- ja ovihuolto Turussa sisältää kuntotarkastuksen, saranoiden ja helojen säädön ja voitelun, tiivisteiden vaihdon tarpeen mukaan sekä pienet korjaukset. Tarvittaessa teemme myös lahokorjaukset, lasien vaihdot ja puitteiden kunnostusmaalauksen. Kartoituskäynti on maksuton.'})
    cards = ''.join(f'<article class="type-card"><h3><a href="/{s}/">{e(n)}</a></h3><p>{e(t)}</p><span class="card-link" aria-hidden="true">Lue lisää &rarr;</span></article>' for s, n, t in SERVICES)
    extra = f'''
  <section class="article-body" id="valitse-palvelu">
    <div class="wrap">
      <h2>Valitse huoltopalvelu</h2>
      <div class="types-grid">{cards}</div>
    </div>
  </section>
'''
    return d, page_html(d, 'hub', extra=extra)


def main():
    pages = []
    for s, _, _ in SERVICES:
        d = json.load(open(os.path.join(ROOT, 'content', s + '.json'), encoding='utf-8'))
        pages.append({'slug': s, 'title': brand(d['main']['title']), 'meta': brand(d['main']['meta']), 'content': page_html(d['main'], 'service', s)})
        for c, _, _ in CITIES:
            p = d['cities'][c]
            pages.append({'slug': f'{s}-{c}', 'title': brand(p['title']), 'meta': brand(p['meta']), 'content': page_html(p, 'city', s, c)})
    d, hub_html = hub()
    theme = os.path.join(WP, 'nordic-theme', 'content')
    json.dump({'hub': {'slug': 'huolto', 'title': d['title'], 'meta': d['meta']}, 'pages': pages},
              open(os.path.join(theme, 'huolto-sivut.json'), 'w', encoding='utf-8'), ensure_ascii=False)
    open(os.path.join(theme, 'huolto.html'), 'w', encoding='utf-8').write(hub_html)
    open(os.path.join(WP, 'sivut', 'huolto.html'), 'w', encoding='utf-8').write(hub_html)
    open(os.path.join(WP, 'sivut', 'huolto-SIVUTIEDOT.txt'), 'w', encoding='utf-8').write(
        f'''HUOLTO-SIVU – SIVUN TIEDOT WORDPRESSIIN

huolto.html
  Otsikko (title):   {d['title']}
  Osoite (slug):     /huolto/
  Ote (kuvaus):      {d['meta']}  [{len(d['meta'])} merkkiä]
  H1 sivulla:        {d['h1']}

Sisältö liitetään Mukautettu HTML -lohkoon nykyisen sisällön tilalle. Saman voi tehdä yhdellä painalluksella
kohdasta Ulkoasu → Huoltosivut → "Päivitä Huolto-sivun sisältö" (teema 2.13.0).
''')
    words = len(re.findall(r'\w+', re.sub(r'<[^>]+>', ' ', hub_html)))
    left = sum(p['content'].count('TiivisT') for p in pages) + hub_html.count('TiivisT')
    print(len(pages), 'alasivua; huolto-sivu', words, 'sanaa; title', len(d['title']), 'meta', len(d['meta']), '; TiivisTupa-mainintoja jäljellä', left)


if __name__ == '__main__':
    main()
