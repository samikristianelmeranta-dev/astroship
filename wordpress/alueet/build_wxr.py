#!/usr/bin/env python3
"""Kokoaa alueen sivut WordPressin tuontitiedostoksi (WXR).

Käyttö: python3 build_wxr.py <alue> [kohdekansio]
Lähde:  alueet/<alue>/sivut.json + <slug>.html, energialaskuri _laskuri-alkuperainen.html
"""
import json
import os
import sys
from xml.sax.saxutils import escape

HERE = os.path.dirname(os.path.abspath(__file__))

ALUEET = {
    'uusimaa':    {'nimi': 'Uusimaa',    'otsikko': 'Ikkunakauppias.fi Uusimaa',    'domain': 'https://uusimaa.ikkunakauppias.fi'},
    'meri-lappi': {'nimi': 'Meri-Lappi', 'otsikko': 'Ikkunakauppias.fi Meri-Lappi', 'domain': 'https://merilappi.ikkunakauppias.fi'},
    'satakunta':  {'nimi': 'Satakunta',  'otsikko': 'Ikkunakauppias.fi Satakunta',  'domain': 'https://satakunta.ikkunakauppias.fi'},
}

# Valikon järjestys (energialaskuri 6.)
JARJESTYS = ['etusivu', 'ikkunat', 'ulko-ovet', 'ikkunaremontit', 'oviremontit', 'energiansaastolaskuri',
             'ikkuna-asennus', 'uudet-ikkunat-vai-huolto', 'yhteystiedot']

LASKURI = {
    'slug': 'energiansaastolaskuri',
    'title': 'Energiansäästölaskuri – ikkunat ja ovet',
    'description': 'Laske, paljonko säästät lämmityksessä uusilla ikkunoilla ja ovilla. Laskuri näyttää arvion euroina ja kilowattitunteina vuodessa.',
}


def cdata(s):
    return '<![CDATA[' + s.replace(']]>', ']]]]><![CDATA[>') + ']]>'


def item(i, page, content, base):
    slug = page['slug']
    pid = 2000 + i
    date = '2026-10-06 12:00:00'
    return f"""  <item>
    <title>{escape(page['title'])}</title>
    <link>{base}/{'' if slug == 'etusivu' else slug + '/'}</link>
    <pubDate>Tue, 06 Oct 2026 12:00:00 +0000</pubDate>
    <dc:creator>{cdata('admin')}</dc:creator>
    <guid isPermaLink="false">{base}/?page_id={pid}</guid>
    <description></description>
    <content:encoded>{cdata(content)}</content:encoded>
    <excerpt:encoded>{cdata(page['description'])}</excerpt:encoded>
    <wp:post_id>{pid}</wp:post_id>
    <wp:post_date>{date}</wp:post_date>
    <wp:post_date_gmt>{date}</wp:post_date_gmt>
    <wp:post_modified>{date}</wp:post_modified>
    <wp:post_modified_gmt>{date}</wp:post_modified_gmt>
    <wp:comment_status>closed</wp:comment_status>
    <wp:ping_status>closed</wp:ping_status>
    <wp:post_name>{slug}</wp:post_name>
    <wp:status>publish</wp:status>
    <wp:post_parent>0</wp:post_parent>
    <wp:menu_order>{i}</wp:menu_order>
    <wp:post_type>page</wp:post_type>
    <wp:post_password></wp:post_password>
    <wp:is_sticky>0</wp:is_sticky>
    <wp:postmeta>
      <wp:meta_key>{cdata('_nordic_meta_description')}</wp:meta_key>
      <wp:meta_value>{cdata(page['description'])}</wp:meta_value>
    </wp:postmeta>
  </item>
"""


def build(alue, out_dir):
    a = ALUEET[alue]
    src = os.path.join(HERE, alue)
    pages = {p['slug']: p for p in json.load(open(os.path.join(src, 'sivut.json'), encoding='utf-8'))}
    pages['energiansaastolaskuri'] = LASKURI
    items = []
    for i, slug in enumerate(JARJESTYS, start=1):
        if slug == 'energiansaastolaskuri':
            content = open(os.path.join(HERE, '_laskuri-alkuperainen.html'), encoding='utf-8').read()
        else:
            content = open(os.path.join(src, slug + '.html'), encoding='utf-8').read()
        items.append(item(i, pages[slug], content.strip() + '\n', a['domain']))
    xml = f"""<?xml version="1.0" encoding="UTF-8"?>
<rss version="2.0"
  xmlns:excerpt="http://wordpress.org/export/1.2/excerpt/"
  xmlns:content="http://purl.org/rss/1.0/modules/content/"
  xmlns:wfw="http://wellformedweb.org/CommentAPI/"
  xmlns:dc="http://purl.org/dc/elements/1.1/"
  xmlns:wp="http://wordpress.org/export/1.2/">
<channel>
  <title>{escape(a['otsikko'])}</title>
  <link>{a['domain']}</link>
  <description>Nordic Ikkunat &amp; Ovet – {escape(a['nimi'])}, sivut</description>
  <pubDate>Tue, 06 Oct 2026 12:00:00 +0000</pubDate>
  <language>fi-FI</language>
  <wp:wxr_version>1.2</wp:wxr_version>
  <wp:base_site_url>{a['domain']}</wp:base_site_url>
  <wp:base_blog_url>{a['domain']}</wp:base_blog_url>
  <wp:author>
    <wp:author_id>1</wp:author_id>
    <wp:author_login>{cdata('admin')}</wp:author_login>
    <wp:author_email>{cdata('info@ikkunakauppias.fi')}</wp:author_email>
    <wp:author_display_name>{cdata('admin')}</wp:author_display_name>
    <wp:author_first_name>{cdata('')}</wp:author_first_name>
    <wp:author_last_name>{cdata('')}</wp:author_last_name>
  </wp:author>
{''.join(items)}</channel>
</rss>
"""
    os.makedirs(out_dir, exist_ok=True)
    path = os.path.join(out_dir, f'nordic-{alue}-sivut.xml')
    open(path, 'w', encoding='utf-8').write(xml)
    return path


if __name__ == '__main__':
    alue = sys.argv[1]
    out = sys.argv[2] if len(sys.argv) > 2 else os.path.join(HERE, '..', 'dist', 'alueet', alue)
    print(build(alue, out))
