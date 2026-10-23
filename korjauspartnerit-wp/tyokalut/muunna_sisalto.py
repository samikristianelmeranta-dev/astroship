#!/usr/bin/env python3
"""
Korjauspartnerit: vanhan Avada-sivuston WordPress-viennin muunnos uudelle sivustolle.

Käyttö:
    python3 muunna_sisalto.py vanha-vienti.xml korjauspartnerit-sisalto.xml

Mitä skripti tekee:
  * poistaa Avada/Fusion Builder -lyhytkoodit ja jättää puhtaan HTML:n klassiseen editoriin
  * jakaa Avadan portfoliot palveluihin (palvelu) ja referensseihin (referenssi)
  * siirtää henkilökunnan kenttineen ja täydentää puuttuvat tiedot henkilökuntasivulta
    (titteli, puhelin, kuva, toimipiste, johto)
  * muuntaa RSS-syötteen työpaikat työpaikkailmoituksiksi (tyopaikka)
  * säilyttää sivut, artikkelit, kategoriat, avainsanat, kuvat ja Yoast-metatiedot
  * jättää pois Avadan rakenneosat (fusion_*), ACF-määrittelyt, valikot ja lokit

Tulos tuodaan WordPressiin: Työkalut → Tuo → WordPress (Korjauspartnerit – Työkalut -lisäosa
pitää olla käytössä ennen tuontia, jotta sisältötyypit ovat olemassa).
"""

import html
import re
import sys
import xml.etree.ElementTree as ET
from collections import OrderedDict

NS = {
    'wp': 'http://wordpress.org/export/1.2/',
    'content': 'http://purl.org/rss/1.0/modules/content/',
    'excerpt': 'http://wordpress.org/export/1.2/excerpt/',
    'dc': 'http://purl.org/dc/elements/1.1/',
}

# ---------------------------------------------------------------------------
# Luokittelu
# ---------------------------------------------------------------------------

# Portfoliokategoriat, jotka tarkoittavat referenssiä (muut ovat palveluita).
REF_CATS = {
    'Julkisivusaneeraus': 'kiinteistot',
    'Kattosaneeraus': 'kiinteistot',
    'Linjasaneeraus': 'kiinteistot',
    'Liike- ja toimitilasaneeraus': 'kiinteistot',
    'Märkätilasaneeraus': 'kiinteistot',
    'Piha- ja pihakansisaneeraus': 'kiinteistot',
    'Sähköautolatauspaikkojen suunnittelu': 'kiinteistot',
    'Ullakkohankkeet': 'kiinteistot',
    'Yleisten tilojen saneeraus': 'kiinteistot',
    'Laivateollisuus': 'teollisuus',
    'Elintarviketeollisuus': 'teollisuus',
    'Muu teollisuus': 'teollisuus',
    'Teollisuus: referenssit': 'teollisuus',
}
REF_PARENTS = {'kiinteistot': 'Kiinteistöt', 'teollisuus': 'Teollisuus'}

# Palvelualueiden nimet (vanha kategoria → uusi palvelualue). None = ei palvelualuetta.
SERVICE_AREAS = {
    'Kiinteistöjen tutkimukset': 'Kiinteistöjen tutkimukset',
    'Energia-asiat': 'Energia-asiat',
    'Talotekniset kartoitukset': 'Talotekniset kartoitukset',
    'Rakennuttaminen, projektinjohto ja valvonta': 'Rakennuttaminen, projektinjohto ja valvonta',
    'Rakennusten vuositarkastukset ja kuntoarviot': 'Vuositarkastukset ja kuntoarviot',
    'Suunnittelupalvelut': 'Suunnittelupalvelut',
    'Liike- ja toimitilapalvelut': 'Liike- ja toimitilapalvelut',
    'Teollisuuden palveluiden esittelyt': 'Teollisuuden palvelut',
    'Asiantuntijalausunnot ja juridiset palvelut': 'Asiantuntijalausunnot ja juridiset palvelut',
    'Ei liike-toimitiloille': None,
}

AUDIENCES = OrderedDict([
    ('taloyhtiot', 'Taloyhtiöt'),
    ('liike-ja-toimitilat', 'Liike- ja toimitilat'),
    ('teollisuus', 'Teollisuus'),
])

# Sivut, joilta palveluiden kohderyhmä päätellään (sivun linkit palveluihin).
AUDIENCE_PAGES = {
    '19699': 'taloyhtiot',
    '19702': 'liike-ja-toimitilat',
    '22662': 'teollisuus',
}

# Henkilökuntasivun osiot, joita käytetään (muut osiot olivat piilotettuja vanhoja versioita).
STAFF_SECTIONS = {'Johto': None, 'Uusimaa': 'uusimaa', 'Varsinais-Suomi': 'varsinais-suomi'}
OFFICES = {'uusimaa': 'Uusimaa', 'varsinais-suomi': 'Varsinais-Suomi'}

# Postmeta, joka säilytetään.
KEEP_META = re.compile(r'^(_thumbnail_id|_wp_attached_file|_wp_attachment_metadata|_wp_attachment_image_alt|_yoast_wpseo_(title|metadesc|focuskw|canonical|meta-robots-noindex|opengraph-title|opengraph-description)|tehtavanimike|puhelinnumero|sahkoposti|kohde)$')

# ---------------------------------------------------------------------------
# Fusion Builder → puhdas HTML
# ---------------------------------------------------------------------------


def attrs(s):
    return dict(re.findall(r'(\w+)="([^"]*)"', s))


def clean(c):
    if not c:
        return ''
    c = c.replace('\r\n', '\n')
    # Piilotetut / luonnostilaiset rakenneosat pois kokonaan.
    c = re.sub(r'\[fusion_builder_container[^\]]*status="draft"[^\]]*\].*?\[/fusion_builder_container\]', '', c, flags=re.S)
    c = re.sub(r'\[fusion_global[^\]]*\]', '', c)
    c = re.sub(r'\[fusion_(code|modal|separator|menu_anchor|fontawesome|post_cards|search|counters_box|counter_box|social_links|sharing|tb_[a-z_]+|breadcrumbs|views_counter)\b[^\]]*\](.*?\[/fusion_\1\])?', '', c, flags=re.S)

    def title(m):
        a = attrs(m.group(1))
        n = a.get('size', '2')
        n = n if n in ('2', '3', '4') else ('2' if n == '1' else '4')
        inner = m.group(2).strip()
        text = re.sub(r'<[^>]+>', '', inner).strip()
        if not text:
            return ''
        # Liian pitkä "otsikko" on oikeasti tekstiä.
        if len(text) > 160:
            return '\n' + inner + '\n'
        inner = re.sub(r'</?(p|h\d|strong|b)[^>]*>', '', inner).strip()
        return f'\n<h{n}>{inner}</h{n}>\n'

    c = re.sub(r'\[fusion_title([^\]]*)\](.*?)\[/fusion_title\]', title, c, flags=re.S)

    def img(m):
        a = attrs(m.group(1))
        src = m.group(2).strip()
        if not src.startswith('http'):
            return ''
        alt = a.get('alt', '')
        link = a.get('link', '')
        tag = f'<img src="{src}" alt="{html.escape(alt)}" />'
        if link and link.startswith('http') and not link.endswith(('.jpg', '.png', '.jpeg', '.webp')):
            tag = f'<a href="{link}">{tag}</a>'
        return f'\n{tag}\n'

    c = re.sub(r'\[fusion_imageframe([^\]]*)\](.*?)\[/fusion_imageframe\]', img, c, flags=re.S)
    c = re.sub(r'\[fusion_image [^\]]*?image="([^"]+)"[^\]]*\](\[/fusion_image\])?', lambda m: f'\n<img src="{m.group(1)}" alt="" />\n', c)
    c = re.sub(r'\[/?fusion_images[^\]]*\]', '', c)

    def btn(m):
        a = attrs(m.group(1))
        t = re.sub(r'<[^>]+>', '', m.group(2)).strip()
        link = a.get('link', '')
        if not t or not link:
            return ''
        return f'\n<p><a class="kp-button" href="{link}">{t}</a></p>\n'

    c = re.sub(r'\[fusion_button([^\]]*)\](.*?)\[/fusion_button\]', btn, c, flags=re.S)
    c = re.sub(r'\[fusion_checklist[^\]]*\]', '<ul class="kp-lista-check">', c)
    c = c.replace('[/fusion_checklist]', '</ul>')
    c = re.sub(r'\[fusion_li_item[^\]]*\](.*?)\[/fusion_li_item\]', lambda m: '<li>' + re.sub(r'</?p[^>]*>', '', m.group(1)).strip() + '</li>', c, flags=re.S)

    def yt(m):
        a = attrs(m.group(0))
        vid = a.get('id', '')
        vid = vid.split('v=')[-1].split('/')[-1]
        return f'\n\nhttps://www.youtube.com/watch?v={vid}\n\n' if vid else ''

    c = re.sub(r'\[fusion_youtube[^\]]*\](.*?\[/fusion_youtube\])?', yt, c, flags=re.S)
    c = re.sub(r'\[gravityform[^\]]*\]', '\n[kp_yhteydenotto]\n', c)
    c = re.sub(r'\[/?fusion_[a-z_0-9]*[^\]]*\]', '\n', c)
    c = re.sub(r'\[/?(year|wpra_feed|wp-rss-aggregator)[^\]]*\]', '', c)

    # Siivous: tyylit, luokat ja tyhjät elementit pois.
    c = re.sub(r' (style|class|data-[a-z-]+|id|width|height|data-fusion-[a-z-]+)="[^"]*"', lambda m: m.group(0) if m.group(0).startswith(' class="kp-') else '', c)
    c = re.sub(r'<span[^>]*>(.*?)</span>', r'\1', c, flags=re.S)
    c = re.sub(r'<div>\s*(.*?)\s*</div>', r'\1', c, flags=re.S)
    c = c.replace('​', '').replace('&nbsp;', ' ')
    c = re.sub(r'<(p|h2|h3|h4|strong|b|em|ul)>\s*</\1>', '', c)
    c = re.sub(r'<ul class="kp-lista-check">\s*</ul>', '', c)
    c = re.sub(r'<h([2-4])>\s*<strong>(.*?)</strong>\s*</h\1>', r'<h\1>\2</h\1>', c, flags=re.S)

    # Peräkkäiset kuvat galleriaksi.
    def gallery(m):
        imgs = re.findall(r'<img [^>]+>', m.group(0))
        return '\n<div class="kp-galleria">' + ''.join(imgs) + '</div>\n'

    c = re.sub(r'(?:\s*<img [^>]+>\s*){3,}', gallery, c)

    c = re.sub(r'[ \t]+\n', '\n', c)
    c = re.sub(r'\n{3,}', '\n\n', c)
    return c.strip()


def strip_heading_dupe(content, title):
    """Poistaa sisällön alusta otsikon, joka toistaa sivun nimen (näkyy jo yläosassa)."""
    m = re.match(r'\s*<h[1-3]>(.*?)</h[1-3]>\s*', content, flags=re.S)
    if m and re.sub(r'\W+', '', m.group(1).lower()) == re.sub(r'\W+', '', title.lower()):
        return content[m.end():]
    return content


def first_paragraph(content, max_len=320):
    text = re.sub(r'<h[1-6][^>]*>.*?</h[1-6]>', '', content, flags=re.S)
    text = re.sub(r'<[^>]+>', ' ', text)
    text = re.sub(r'\[[^\]]+\]', ' ', text)
    text = re.sub(r'\s+', ' ', text).strip()
    if not text:
        return ''
    parts = re.split(r'(?<=[.!?])\s+', text)
    out = ''
    for p in parts:
        if len(out) + len(p) > max_len and out:
            break
        out = (out + ' ' + p).strip()
    return out


# ---------------------------------------------------------------------------
# Luku
# ---------------------------------------------------------------------------


def read_items(path):
    root = ET.parse(path).getroot()
    ch = root.find('channel')
    items = []
    for it in ch.findall('item'):
        items.append({
            'title': it.findtext('title') or '',
            'link': it.findtext('link') or '',
            'guid': it.findtext('guid') or '',
            'id': it.findtext('wp:post_id', namespaces=NS),
            'type': it.findtext('wp:post_type', namespaces=NS),
            'status': it.findtext('wp:status', namespaces=NS),
            'slug': it.findtext('wp:post_name', namespaces=NS) or '',
            'parent': it.findtext('wp:post_parent', namespaces=NS) or '0',
            'order': it.findtext('wp:menu_order', namespaces=NS) or '0',
            'date': it.findtext('wp:post_date', namespaces=NS),
            'date_gmt': it.findtext('wp:post_date_gmt', namespaces=NS),
            'author': it.findtext('dc:creator', namespaces=NS) or '',
            'content': it.findtext('content:encoded', namespaces=NS) or '',
            'excerpt': it.findtext('excerpt:encoded', namespaces=NS) or '',
            'url': it.findtext('wp:attachment_url', namespaces=NS),
            'cats': [(c.get('domain'), c.get('nicename'), c.text or '') for c in it.findall('category')],
            'meta': OrderedDict((m.findtext('wp:meta_key', namespaces=NS), m.findtext('wp:meta_value', namespaces=NS) or '') for m in it.findall('wp:postmeta', namespaces=NS)),
        })
    authors = []
    for a in ch.findall('wp:author', NS):
        authors.append({k: a.findtext('wp:author_' + k, namespaces=NS) or '' for k in ('id', 'login', 'email', 'display_name', 'first_name', 'last_name')})
    cats = []
    for c in ch.findall('wp:category', NS):
        cats.append({'slug': c.findtext('wp:category_nicename', namespaces=NS), 'parent': c.findtext('wp:category_parent', namespaces=NS) or '', 'name': c.findtext('wp:cat_name', namespaces=NS)})
    tags = []
    for t in ch.findall('wp:tag', NS):
        tags.append({'slug': t.findtext('wp:tag_slug', namespaces=NS), 'name': t.findtext('wp:tag_name', namespaces=NS)})
    terms = []
    for t in ch.findall('wp:term', NS):
        terms.append({'tax': t.findtext('wp:term_taxonomy', namespaces=NS), 'slug': t.findtext('wp:term_slug', namespaces=NS), 'parent': t.findtext('wp:term_parent', namespaces=NS) or '', 'name': t.findtext('wp:term_name', namespaces=NS)})
    title = ch.findtext('title')
    return title, authors, cats, tags, terms, items


# ---------------------------------------------------------------------------
# Kirjoitus
# ---------------------------------------------------------------------------


def cdata(s):
    s = '' if s is None else str(s)
    return '<![CDATA[' + s.replace(']]>', ']]]]><![CDATA[>') + ']]>'


def slugify(s):
    s = s.lower()
    for a, b in (('ä', 'a'), ('ö', 'o'), ('å', 'a'), ('é', 'e')):
        s = s.replace(a, b)
    s = re.sub(r'[^a-z0-9]+', '-', s).strip('-')
    return s


def write_wxr(path, title, authors, cats, tags, terms, items):
    out = []
    w = out.append
    w('<?xml version="1.0" encoding="UTF-8" ?>')
    w('<!-- Korjauspartnerit: muunnettu sisältö uudelle sivustolle (muunna_sisalto.py). -->')
    w('<rss version="2.0" xmlns:excerpt="http://wordpress.org/export/1.2/excerpt/" xmlns:content="http://purl.org/rss/1.0/modules/content/" xmlns:wfw="http://wellformedweb.org/CommentAPI/" xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:wp="http://wordpress.org/export/1.2/">')
    w('<channel>')
    w(f'\t<title>{html.escape(title)}</title>')
    w('\t<link>https://korjauspartnerit.fi</link>')
    w('\t<language>fi</language>')
    w('\t<wp:wxr_version>1.2</wp:wxr_version>')
    w('\t<wp:base_site_url>https://korjauspartnerit.fi</wp:base_site_url>')
    w('\t<wp:base_blog_url>https://korjauspartnerit.fi</wp:base_blog_url>')
    for a in authors:
        w('\t<wp:author>' + ''.join(f'<wp:author_{k}>{cdata(a[k]) if k != "id" else a[k]}</wp:author_{k}>' for k in ('id', 'login', 'email', 'display_name', 'first_name', 'last_name')) + '</wp:author>')
    for c in cats:
        w(f'\t<wp:category><wp:category_nicename>{cdata(c["slug"])}</wp:category_nicename><wp:category_parent>{cdata(c["parent"])}</wp:category_parent><wp:cat_name>{cdata(c["name"])}</wp:cat_name></wp:category>')
    for t in tags:
        w(f'\t<wp:tag><wp:tag_slug>{cdata(t["slug"])}</wp:tag_slug><wp:tag_name>{cdata(t["name"])}</wp:tag_name></wp:tag>')
    for t in terms:
        w(f'\t<wp:term><wp:term_taxonomy>{cdata(t["tax"])}</wp:term_taxonomy><wp:term_slug>{cdata(t["slug"])}</wp:term_slug><wp:term_parent>{cdata(t.get("parent", ""))}</wp:term_parent><wp:term_name>{cdata(t["name"])}</wp:term_name></wp:term>')
    for d in items:
        w('\t<item>')
        w(f'\t\t<title>{cdata(d["title"])}</title>')
        w(f'\t\t<link>{html.escape(d.get("link", ""))}</link>')
        w(f'\t\t<dc:creator>{cdata(d["author"])}</dc:creator>')
        w(f'\t\t<guid isPermaLink="false">{html.escape(d.get("guid") or ("https://korjauspartnerit.fi/?p=" + str(d["id"])))}</guid>')
        w('\t\t<description></description>')
        w(f'\t\t<content:encoded>{cdata(d["content"])}</content:encoded>')
        w(f'\t\t<excerpt:encoded>{cdata(d.get("excerpt", ""))}</excerpt:encoded>')
        w(f'\t\t<wp:post_id>{d["id"]}</wp:post_id>')
        w(f'\t\t<wp:post_date>{cdata(d["date"])}</wp:post_date>')
        w(f'\t\t<wp:post_date_gmt>{cdata(d.get("date_gmt") or d["date"])}</wp:post_date_gmt>')
        w('\t\t<wp:comment_status>closed</wp:comment_status>')
        w('\t\t<wp:ping_status>closed</wp:ping_status>')
        w(f'\t\t<wp:post_name>{cdata(d["slug"])}</wp:post_name>')
        w(f'\t\t<wp:status>{cdata(d["status"])}</wp:status>')
        w(f'\t\t<wp:post_parent>{d.get("parent", "0")}</wp:post_parent>')
        w(f'\t\t<wp:menu_order>{d.get("order", "0")}</wp:menu_order>')
        w(f'\t\t<wp:post_type>{cdata(d["type"])}</wp:post_type>')
        w('\t\t<wp:post_password></wp:post_password>')
        w('\t\t<wp:is_sticky>0</wp:is_sticky>')
        if d.get('url'):
            w(f'\t\t<wp:attachment_url>{cdata(d["url"])}</wp:attachment_url>')
        for dom, nice, name in d.get('cats', []):
            w(f'\t\t<category domain="{dom}" nicename="{html.escape(nice)}">{cdata(name)}</category>')
        for k, v in d.get('meta', {}).items():
            if isinstance(v, (list, tuple)):
                v = php_serialize_list(v)
            w(f'\t\t<wp:postmeta><wp:meta_key>{cdata(k)}</wp:meta_key><wp:meta_value>{cdata(v)}</wp:meta_value></wp:postmeta>')
        w('\t</item>')
    w('</channel>')
    w('</rss>')
    with open(path, 'w', encoding='utf-8') as f:
        f.write('\n'.join(out) + '\n')


def php_serialize_list(values):
    parts = []
    for i, v in enumerate(values):
        v = str(v)
        parts.append(f'i:{i};s:{len(v.encode("utf-8"))}:"{v}";')
    return f'a:{len(values)}:{{' + ''.join(parts) + '}'


# ---------------------------------------------------------------------------
# Muunnos
# ---------------------------------------------------------------------------


def base_name(url):
    name = url.split('/')[-1]
    return re.sub(r'-\d+x\d+(?=\.\w+$)', '', name).lower()


def parse_staff_page(content):
    """Henkilökuntasivun osiot → {nimi: {osio, titteli, puhelin, email, kuva}}."""
    c = clean(content)
    sec = None
    people = OrderedDict()
    for block in re.split(r'\n(?=<img )|\n(?=<h2>)', c):
        m = re.match(r'<h2>(.*?)</h2>', block)
        if m:
            sec = m.group(1).strip()
            continue
        if sec not in STAFF_SECTIONS or not block.startswith('<img'):
            continue
        img = re.search(r'src="([^"]+)"', block).group(1)
        t = re.sub(r'<img[^>]*>', '', block)
        nm = re.search(r'<(?:strong|b)>(.*?)</(?:strong|b)>', t, re.S)
        if not nm:
            continue
        name = re.sub(r'\s+', ' ', nm.group(1)).strip()
        email = re.search(r'mailto:([^"]+)', t)
        tel = re.search(r'tel:([^"]+)', t)
        rest = re.sub(r'<a[^>]*>.*?</a>', '', re.sub(r'<(strong|b)>.*?</(strong|b)>', '', t, flags=re.S), flags=re.S)
        lines = [l.strip() for l in re.sub(r'<[^>]+>', '\n', rest).split('\n') if l.strip()]
        phone_lines = [l for l in lines if re.fullmatch(r'[\d +]{7,}', l)]
        title_lines = [l for l in lines if l not in phone_lines]
        p = people.setdefault(name, {'osiot': [], 'titteli': '', 'puhelin': '', 'email': '', 'kuva': ''})
        p['osiot'].append(sec)
        if not p['titteli'] and title_lines:
            p['titteli'] = ', '.join(title_lines)
        if not p['puhelin']:
            p['puhelin'] = phone_lines[0] if phone_lines else (re.sub(r'^\+358', '0', tel.group(1)) if tel else '')
        p['email'] = p['email'] or (email.group(1).strip() if email else '')
        p['kuva'] = p['kuva'] or img
    return people


def format_phone(p):
    digits = re.sub(r'\D', '', p)
    if digits.startswith('358'):
        digits = '0' + digits[3:]
    if len(digits) == 10:
        return f'{digits[:3]} {digits[3:6]} {digits[6:]}'
    if len(digits) == 9:
        return f'{digits[:3]} {digits[3:6]} {digits[6:]}'
    return p.strip()


def convert(src, dst):
    title, authors, cats, tags, terms, items = read_items(src)
    by_id = {d['id']: d for d in items}
    attachments = {base_name(d['url']): d['id'] for d in items if d['type'] == 'attachment' and d['url']}

    out_items = []
    new_terms = []
    term_seen = set()

    def add_term(tax, slug, name, parent=''):
        if (tax, slug) in term_seen:
            return
        term_seen.add((tax, slug))
        new_terms.append({'tax': tax, 'slug': slug, 'name': name, 'parent': parent})

    for t in terms:
        if t['tax'] == 'hakusanat':
            add_term('hakusanat', t['slug'], t['name'], t['parent'])
    for slug, name in OFFICES.items():
        add_term('toimipiste', slug, name)
    for slug, name in AUDIENCES.items():
        add_term('kohderyhma', slug, name)
    for slug, name in REF_PARENTS.items():
        add_term('referenssityyppi', slug, name)

    def keep_meta(meta):
        return OrderedDict((k, v) for k, v in meta.items() if k and KEEP_META.match(k) and v != '')

    # --- Kohderyhmät palveluille sivujen linkeistä ---
    audience_links = {}
    for pid, aud in AUDIENCE_PAGES.items():
        if pid in by_id:
            for slug in re.findall(r'/palvelut/([a-z0-9-]+)/?', by_id[pid]['content']):
                audience_links.setdefault(slug, set()).add(aud)

    # --- Liitteet ---
    for d in items:
        if d['type'] == 'attachment':
            d2 = dict(d)
            d2['meta'] = keep_meta(d['meta'])
            d2['content'] = d['content']
            out_items.append(d2)

    # --- Sivut ---
    page_overrides = {
        '2': {'content': ''},  # Etusivu – sivupohja hoitaa
        '20049': {'content': ''},  # Ajankohtaista – artikkelilistaus
        '21439': {'status': 'draft'},  # Contact us – ohjataan Yhteystietoihin
        '19460': {'status': 'draft'},  # Ota yhteyttä – ohjataan Yhteystietoihin
    }
    for d in items:
        if d['type'] != 'page' or d['status'] not in ('publish', 'draft', 'private'):
            continue
        d2 = dict(d)
        c = clean(d['content'])
        c = strip_heading_dupe(c, d['title'])
        if d['id'] == '18795':  # Henkilökunta: johdanto, hakemisto tulee sivupohjasta
            intro = re.findall(r'(Tältä sivulta löydät.*?)(?:\n\n|$)|(Voit myös soittaa.*?)(?:\n\n|$)', c, flags=re.S)
            paras = [a or b for a, b in intro]
            c = ''.join(f'<p>{p.strip()}</p>\n' for p in paras)
        elif d['id'] == '18798':  # Yhteystiedot: johdanto, muut tiedot tulevat asetuksista
            m = re.search(r'<h3>Ota yhteyttä kaikissa.*?</h3>\s*(.*?)\n', c, flags=re.S)
            c = '<p class="kp-ingressi">Ota yhteyttä kaikissa kiinteistökehittämisen palveluiden kysymyksissä!</p>\n'
            if m:
                c += f'<p>{m.group(1).strip()}</p>\n'
        elif d['id'] == '18800':  # Työpaikkana: kovakoodatut työpaikat → dynaaminen listaus
            c = re.sub(r'<h3>Etsimme nyt:</h3>.*?(?=<h2>)', '<h2>Avoimet työpaikat</h2>\n\n[kp_tyopaikat]\n\n<p>Voit myös jättää <strong>avoimen hakemuksen</strong> tai ottaa meihin yhteyttä sähköpostilla <a href="mailto:hakemus@korjauspartnerit.fi">hakemus@korjauspartnerit.fi</a>.</p>\n\n', c, flags=re.S)
        elif d['id'] == '19699':
            c += '\n\n<h2>Palvelumme taloyhtiöille</h2>\n\n[kp_palvelut asiakasryhma="taloyhtiot" ryhmittele="1"]'
        elif d['id'] == '19702':
            c += '\n\n<h2>Palvelumme liike- ja toimitiloille</h2>\n\n[kp_palvelut asiakasryhma="liike-ja-toimitilat" ryhmittele="1"]'
        elif d['id'] == '18756':
            c += '\n\n[kp_referenssit tyyppi="kiinteistot" suodatin="1"]'
        d2['content'] = c
        d2.update(page_overrides.get(d['id'], {}))
        d2['meta'] = keep_meta(d['meta'])
        lead = first_paragraph(d2['content'], 260) if d2['content'] else ''
        if lead and d['id'] not in ('2', '20049', '18795', '18798'):
            d2['meta']['kp_ingressi'] = lead
        out_items.append(d2)

    # --- Artikkelit ---
    for d in items:
        if d['type'] != 'post' or d['status'] not in ('publish', 'draft', 'private', 'future'):
            continue
        d2 = dict(d)
        d2['content'] = strip_heading_dupe(clean(d['content']), d['title'])
        d2['meta'] = keep_meta(d['meta'])
        if not d2['slug']:
            d2['slug'] = slugify(d['title'])
        out_items.append(d2)

    # --- Portfoliot → palvelut ja referenssit ---
    for d in items:
        if d['type'] != 'avada_portfolio' or d['status'] not in ('publish', 'draft', 'private'):
            continue
        names = [n for dom, nice, n in d['cats'] if dom == 'portfolio_category']
        d2 = dict(d)
        d2['meta'] = keep_meta(d['meta'])
        content = strip_heading_dupe(clean(d['content']), d['title'])
        new_cats = []
        if any(n in REF_CATS for n in names):
            d2['type'] = 'referenssi'
            groups = set()
            for n in names:
                if n not in REF_CATS:
                    continue
                parent = REF_CATS[n]
                groups.add(parent)
                if n == 'Teollisuus: referenssit':
                    continue
                slug = slugify(n)
                add_term('referenssityyppi', slug, n, parent)
                new_cats.append(('referenssityyppi', slug, n))
            for g in groups:
                new_cats.append(('referenssityyppi', g, REF_PARENTS[g]))
            if 'teollisuus' in groups:
                new_cats.append(('kohderyhma', 'teollisuus', AUDIENCES['teollisuus']))
            elif 'Liike- ja toimitilasaneeraus' in names or re.search(r'\b(KOy|Kiinteistö Oy)\b', d['title']):
                new_cats.append(('kohderyhma', 'liike-ja-toimitilat', AUDIENCES['liike-ja-toimitilat']))
            else:
                new_cats.append(('kohderyhma', 'taloyhtiot', AUDIENCES['taloyhtiot']))
            # Referenssin alku: "<h3>Hanke</h3><ul><li>Palvelu</li></ul>" → kohde-kenttä + selkeä rakenne.
            m = re.match(r'\s*(?:<h3>\s*(.*?)\s*</h3>|([^<\n][^\n]{0,120}))\s*<ul[^>]*>(.*?)</ul>', content, flags=re.S)
            if m:
                services = [re.sub(r'<[^>]+>', '', s).strip() for s in re.findall(r'<li>(.*?)</li>', m.group(3), flags=re.S)]
                hanke = re.sub(r'<[^>]+>', '', m.group(1) or m.group(2)).strip()
                if not d2['meta'].get('kohde'):
                    d2['meta']['kohde'] = hanke
                content = (f'<h2>{hanke}</h2>\n<ul class="kp-lista-check">' + ''.join(f'<li>{s}</li>' for s in services if s) + '</ul>\n' + content[m.end():])
            content = re.sub(r'<h3>Kohteen tiedot</h3>\s*(<ul[^>]*>.*?</ul>)', lambda mm: '<div class="kp-laatikko kp-laatikko--hiekka"><h3>Kohteen tiedot</h3>' + mm.group(1).replace('<ul class="kp-lista-check">', '<ul class="kp-lista-nuoli">').replace('<ul>', '<ul class="kp-lista-nuoli">') + '</div>', content, flags=re.S)
            vm = re.search(r'Valmistunut\s*:?\s*([0-9/.\-–]+(?:\s*\d{4})?)', content)
            if vm:
                d2['meta']['kp_valmistunut'] = vm.group(1).strip()
            content = content.replace('<h3>Projektikuvaus</h3>', '<h2>Projektikuvaus</h2>')
        else:
            d2['type'] = 'palvelu'
            for n in names:
                area = SERVICE_AREAS.get(n, n)
                if area:
                    slug = slugify(area)
                    add_term('palvelualue', slug, area)
                    new_cats.append(('palvelualue', slug, area))
            auds = audience_links.get(d['slug'], set())
            if not auds:
                if 'Teollisuuden palveluiden esittelyt' in names:
                    auds = {'teollisuus'}
                elif 'Ei liike-toimitiloille' in names:
                    auds = {'taloyhtiot'}
                elif 'Liike- ja toimitilapalvelut' in names:
                    auds = {'liike-ja-toimitilat'}
                else:
                    auds = {'taloyhtiot', 'liike-ja-toimitilat'}
            for a in sorted(auds):
                new_cats.append(('kohderyhma', a, AUDIENCES[a]))
            # Alun kohderyhmärivi ("taloyhtiöille, liike- ja toimitiloille…") ei sovi ingressiksi.
            lead_src = re.sub(r'^\s*(?:<h\d>)?\s*[a-zäö][^<\n]*(?:</h\d>)?\s*', '', content, count=1)
            lead = first_paragraph(lead_src, 240)
            if lead:
                d2['meta']['kp_ingressi'] = lead
        d2['content'] = content
        d2['cats'] = new_cats
        d2['link'] = d['link']
        out_items.append(d2)

    # --- Henkilökunta ---
    staff_page = parse_staff_page(by_id['18795']['content']) if '18795' in by_id else {}
    johto_order = {n: i + 1 for i, n in enumerate([n for n, p in staff_page.items() if 'Johto' in p['osiot']])}
    seen_names = set()
    for d in items:
        if d['type'] != 'henkilokunta' or d['status'] != 'publish':
            continue
        d2 = dict(d)
        d2['meta'] = keep_meta(d['meta'])
        c = clean(d['content'])
        d2['content'] = '' if not re.sub(r'<[^>]+>|\s', '', c) else c
        name = d['title'].strip()
        seen_names.add(name)
        info = staff_page.get(name)
        cats2 = [(dom, nice, n) for dom, nice, n in d['cats'] if dom == 'hakusanat']
        if info:
            if not d2['meta'].get('tehtavanimike') and info['titteli']:
                d2['meta']['tehtavanimike'] = info['titteli']
            if not d2['meta'].get('puhelinnumero') and info['puhelin']:
                d2['meta']['puhelinnumero'] = format_phone(info['puhelin'])
            if not d2['meta'].get('sahkoposti') and info['email']:
                d2['meta']['sahkoposti'] = info['email']
            if not d2['meta'].get('_thumbnail_id'):
                att = attachments.get(base_name(info['kuva']))
                if att:
                    d2['meta']['_thumbnail_id'] = att
            for sec in info['osiot']:
                office = STAFF_SECTIONS.get(sec)
                if office:
                    cats2.append(('toimipiste', office, OFFICES[office]))
            if name in johto_order:
                d2['meta']['kp_johto'] = '1'
                d2['order'] = str(johto_order[name])
        if not d2['meta'].get('_thumbnail_id'):
            att = attachments.get(slugify(name) + '.png') or attachments.get(slugify(name) + '.jpg')
            if att:
                d2['meta']['_thumbnail_id'] = att
        if d2['meta'].get('puhelinnumero'):
            d2['meta']['puhelinnumero'] = format_phone(d2['meta']['puhelinnumero'])
        d2['cats'] = list(OrderedDict.fromkeys(cats2))
        out_items.append(d2)

    # Henkilökuntasivulla mainitut, joilla ei ollut omaa henkilökorttia.
    next_id = 990001
    for name, info in staff_page.items():
        if name in seen_names:
            continue
        meta = OrderedDict()
        if info['titteli']:
            meta['tehtavanimike'] = info['titteli']
        if info['puhelin']:
            meta['puhelinnumero'] = format_phone(info['puhelin'])
        if info['email']:
            meta['sahkoposti'] = info['email']
        att = attachments.get(base_name(info['kuva']))
        if att:
            meta['_thumbnail_id'] = att
        cats2 = []
        for sec in info['osiot']:
            office = STAFF_SECTIONS.get(sec)
            if office:
                cats2.append(('toimipiste', office, OFFICES[office]))
        order = '0'
        if name in johto_order:
            meta['kp_johto'] = '1'
            order = str(johto_order[name])
        out_items.append({
            'id': str(next_id), 'type': 'henkilokunta', 'status': 'publish', 'title': name, 'slug': slugify(name),
            'author': 'korjauspartnerit', 'date': '2026-10-23 12:00:00', 'date_gmt': '2026-10-23 09:00:00',
            'content': '', 'excerpt': '', 'parent': '0', 'order': order, 'cats': cats2, 'meta': meta,
            'link': f'https://korjauspartnerit.fi/henkilokunta/{slugify(name)}/',
        })
        next_id += 1

    # --- Työpaikat (RSS-syötteestä) ---
    for d in items:
        if d['type'] != 'wprss_feed_item' or d['status'] != 'publish':
            continue
        link = d['meta'].get('wprss_item_permalink') or d['meta'].get('_wpra_url', '')
        meta = OrderedDict()
        if link:
            meta['kp_hakulinkki'] = link
        c = clean(d['content'])
        loc = re.search(r'\b(Turu[nsa]|Helsin[gk]i[nsä]*|Lappeenrann?a)', d['title'] + ' ' + re.sub(r'<[^>]+>', ' ', c))
        if loc:
            meta['kp_sijainti'] = {'Turun': 'Turku', 'Turussa': 'Turku', 'Turku': 'Turku'}.get(loc.group(1), loc.group(1))
        if 'avoin hakemus' in d['title'].lower():
            meta['kp_sijainti'] = 'Koko Suomi'
            meta['kp_hakuemail'] = 'hakemus@korjauspartnerit.fi'
        out_items.append({
            'id': d['id'], 'type': 'tyopaikka', 'status': 'publish', 'title': d['title'], 'slug': d['slug'].replace('__trashed', ''),
            'author': d['author'], 'date': d['date'], 'date_gmt': d['date_gmt'], 'content': c,
            'excerpt': '', 'parent': '0', 'order': '0', 'cats': [], 'meta': meta,
            'link': f'https://korjauspartnerit.fi/tyopaikat/{d["slug"]}/',
        })

    # Työpaikkana-sivulla mainitut avoimet tehtävät.
    if '18800' in by_id:
        for m in re.finditer(r'\n([^\n<]{10,140}?)\s+<a href="(https://forms\.jobilla\.com/[^"]+)">', clean(by_id['18800']['content'])):
            job, link = m.group(1).strip(), m.group(2)
            job_title = re.sub(r'\s+(Turun|Helsingin) toimistollemme$', '', job)
            city = 'Turku' if 'Turun' in job else ('Helsinki' if 'Helsingin' in job else '')
            meta = OrderedDict([('kp_hakulinkki', link)])
            if city:
                meta['kp_sijainti'] = city
            out_items.append({
                'id': str(next_id), 'type': 'tyopaikka', 'status': 'publish', 'title': job_title, 'slug': slugify(job_title),
                'author': 'korjauspartnerit', 'date': '2026-10-23 12:00:00', 'date_gmt': '2026-10-23 09:00:00',
                'content': f'<p class="kp-ingressi">Haemme {job_title.lower()} {"Turun" if city == "Turku" else "Helsingin"} toimistollemme.</p>\n<p>Lue lisää tehtävästä ja hae Jobillan kautta oheisesta Hae paikkaa -painikkeesta.</p>',
                'excerpt': '', 'parent': '0', 'order': '0', 'cats': [], 'meta': meta,
                'link': f'https://korjauspartnerit.fi/tyopaikat/{slugify(job_title)}/',
            })
            next_id += 1

    # Viittaukset vanhoihin portfolio-osoitteisiin päivitetään referenssien osalta.
    ref_slugs = {d['slug'] for d in out_items if d['type'] == 'referenssi'}
    for d in out_items:
        if d['type'] in ('page', 'post', 'palvelu', 'referenssi'):
            d['content'] = re.sub(r'(https?://korjauspartnerit\.fi)?/palvelut/([a-z0-9-]+)/', lambda m: (m.group(1) or '') + ('/referenssit/' if m.group(2) in ref_slugs else '/palvelut/') + m.group(2) + '/', d['content'])

    write_wxr(dst, title, authors, [c for c in cats if c['slug'] not in ('uncategorized',)], tags, new_terms, out_items)

    counts = OrderedDict()
    for d in out_items:
        counts[d['type']] = counts.get(d['type'], 0) + 1
    print('Kirjoitettu', dst)
    for k, v in counts.items():
        print(f'  {k}: {v}')


if __name__ == '__main__':
    if len(sys.argv) != 3:
        print(__doc__)
        sys.exit(1)
    convert(sys.argv[1], sys.argv[2])
