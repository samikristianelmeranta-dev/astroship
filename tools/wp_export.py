"""Blogiartikkelit WordPressiin: HTML-tiedostot ja WXR-tuontitiedosto (luonnoksina).

Käyttö (vaatii python-markdown-kirjaston):
    python3 tools/wp_export.py <tuontitiedosto.xml> <ensimmäinen post_id> slug1 [slug2 ...]

Kirjoittaa downloads/<slug>.html jokaisesta artikkelista ja koostaa niistä
downloads/<tuontitiedosto.xml>. Sisäiset linkit muutetaan WordPress-osoitteiksi.
"""
import re
import sys
from datetime import datetime, timezone
from email.utils import format_datetime

import markdown

SITE = "https://www.samielmeranta.fi"
# Astro-osoite → vanhan WordPressin osoite, kun ne eroavat.
WP_SLUG = {"yrittaminen-ja-tyonteko-ovat-parhaita-kavereita": "yrittaminen-ja-tyonteko"}
PAGES = {"/yhteys/": "/contact/"}

HEADER = """<?xml version="1.0" encoding="UTF-8" ?>
<!-- WordPress-tuontitiedosto: uudet artikkelit luonnoksina. Työkalut → Tuo → WordPress. -->
<rss version="2.0"
	xmlns:excerpt="http://wordpress.org/export/1.2/excerpt/"
	xmlns:content="http://purl.org/rss/1.0/modules/content/"
	xmlns:wfw="http://wellformedweb.org/CommentAPI/"
	xmlns:dc="http://purl.org/dc/elements/1.1/"
	xmlns:wp="http://wordpress.org/export/1.2/"
>
<channel>
	<title>Sami Elmeranta</title>
	<link>{site}</link>
	<description>Tolkun poliitikko</description>
	<language>fi</language>
	<wp:wxr_version>1.2</wp:wxr_version>
	<wp:base_site_url>{site}</wp:base_site_url>
	<wp:base_blog_url>{site}</wp:base_blog_url>
	<wp:author><wp:author_id>2</wp:author_id><wp:author_login><![CDATA[Sami Elmeranta]]></wp:author_login><wp:author_display_name><![CDATA[Sami Elmeranta]]></wp:author_display_name><wp:author_first_name><![CDATA[Sami]]></wp:author_first_name><wp:author_last_name><![CDATA[Elmeranta]]></wp:author_last_name></wp:author>
	<wp:category><wp:term_id>15</wp:term_id><wp:category_nicename><![CDATA[politiikka]]></wp:category_nicename><wp:category_parent><![CDATA[]]></wp:category_parent><wp:cat_name><![CDATA[Politiikka]]></wp:cat_name></wp:category>
	<generator>https://wordpress.org/?v=6.8</generator>
""".format(site=SITE)


def cdata(s):
    return "<![CDATA[" + s.replace("]]>", "]]]]><![CDATA[>") + "]]>"


def nicename(s):
    s = s.lower()
    for a, b in (("ä", "a"), ("ö", "o"), ("å", "a"), ("'", "")):
        s = s.replace(a, b)
    return re.sub(r"[^a-z0-9]+", "-", s).strip("-")


def front(fm, key):
    return re.search(rf'^{key}: "(.*)"$', fm, re.M).group(1).replace('\\"', '"')


def to_html(body):
    html = markdown.markdown(body, extensions=["tables"])

    def link(m):
        href = m.group(1)
        if href.startswith("/blogi/"):
            slug = href.strip("/").split("/")[1]
            return f'<a href="{SITE}/{WP_SLUG.get(slug, slug)}/">'
        if href in PAGES:
            return f'<a href="{SITE}{PAGES[href]}">'
        if href.startswith("http"):
            return f'<a href="{href}" target="_blank" rel="noopener noreferrer">'
        return m.group(0)

    return re.sub(r'<a href="([^"]+)">', link, html)


def item(slug, pid):
    md = open(f"src/content/blog/{slug}.md", encoding="utf-8").read()
    fm, body = md.split("---", 2)[1:]
    title, snippet = front(fm, "title"), front(fm, "snippet")
    dt = datetime.strptime(front(fm, "publishDate"), "%Y-%m-%d %H:%M")
    tags = re.findall(r'"([^"]+)"', re.search(r"^tags: \[(.*)\]$", fm, re.M).group(1))
    content = to_html(body.strip())
    open(f"downloads/{slug}.html", "w", encoding="utf-8").write(content + "\n")
    wp_slug = WP_SLUG.get(slug, slug)
    cats = '\t\t<category domain="category" nicename="politiikka">' + cdata("Politiikka") + "</category>\n"
    cats += "".join(f'\t\t<category domain="post_tag" nicename="{nicename(t)}">{cdata(t)}</category>\n' for t in tags)
    return f"""	<item>
		<title>{cdata(title)}</title>
		<link>{SITE}/{wp_slug}/</link>
		<pubDate>{format_datetime(dt.replace(tzinfo=timezone.utc))}</pubDate>
		<dc:creator>{cdata("Sami Elmeranta")}</dc:creator>
		<guid isPermaLink="false">{SITE}/?p={pid}</guid>
		<description></description>
		<content:encoded>{cdata(content)}</content:encoded>
		<excerpt:encoded>{cdata(snippet)}</excerpt:encoded>
		<wp:post_id>{pid}</wp:post_id>
		<wp:post_date>{cdata(dt.strftime("%Y-%m-%d %H:%M:%S"))}</wp:post_date>
		<wp:post_date_gmt>{cdata("0000-00-00 00:00:00")}</wp:post_date_gmt>
		<wp:comment_status>{cdata("closed")}</wp:comment_status>
		<wp:ping_status>{cdata("closed")}</wp:ping_status>
		<wp:post_name>{cdata(wp_slug)}</wp:post_name>
		<wp:status>{cdata("draft")}</wp:status>
		<wp:post_parent>0</wp:post_parent>
		<wp:menu_order>0</wp:menu_order>
		<wp:post_type>{cdata("post")}</wp:post_type>
		<wp:post_password>{cdata("")}</wp:post_password>
		<wp:is_sticky>0</wp:is_sticky>
{cats}	</item>
"""


if __name__ == "__main__":
    out, start, slugs = sys.argv[1], int(sys.argv[2]), sys.argv[3:]
    items = "".join(item(s, start + i) for i, s in enumerate(slugs))
    open(f"downloads/{out}", "w", encoding="utf-8").write(HEADER + items + "</channel>\n</rss>\n")
    print(f"downloads/{out}: {len(slugs)} artikkelia")
