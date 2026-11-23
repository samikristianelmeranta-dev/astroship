"""Artikkelien esikatselu- ja koodisivu (Claude-artifact) downloads/-HTML-tiedostoista.

Käyttö: python3 tools/artikkelisivu.py <tulos.html> slug1 [slug2 ...]
"""
import json
import re
import sys

out, slugs = sys.argv[1], sys.argv[2:]
arts = []
for s in slugs:
    fm = open(f"src/content/blog/{s}.md", encoding="utf-8").read().split("---", 2)[1]
    g = lambda k: re.search(rf'^{k}: "(.*)"$', fm, re.M).group(1).replace('\\"', '"')
    tags = re.findall(r'"([^"]+)"', re.search(r"^tags: \[(.*)\]$", fm, re.M).group(1))
    html = open(f"downloads/{s}.html", encoding="utf-8").read().strip()
    words = len(re.sub(r"<[^>]+>", " ", html).split())
    arts.append({"slug": s, "title": g("title"), "snippet": g("snippet"), "tags": tags, "html": html, "words": words})

data = json.dumps(arts, ensure_ascii=False).replace("</", "<\\/")
tpl = open("tools/artikkelisivu.html", encoding="utf-8").read()
open(out, "w", encoding="utf-8").write(tpl.replace("/*DATA*/[]", data))
print(out, [a["words"] for a in arts])
