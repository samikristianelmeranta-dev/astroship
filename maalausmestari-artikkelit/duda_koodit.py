"""Tekee jokaisesta artikkelista Dudaan liitettävän HTML-koodin kansioon ./duda-koodit/.

Ajo: python3 duda_koodit.py
Koodi sisältää: rajatut tyylit, artikkelin tekstin (ilman H1:tä – Duda tekee blogin otsikosta H1:n)
ja JSON-LD:n (BlogPosting + BreadcrumbList + FAQPage).
"""

import html
import json
import re
from pathlib import Path

HERE = Path(__file__).parent
OUT = HERE / "duda-koodit"
BASE = "https://www.maalausmestari.fi"
ORG_ID = f"{BASE}/#organization"

STYLE = """<style>
  .mm-artikkeli h2 { font-family: positype_-_anago-blackitalic-webfont, 'Arial Narrow', sans-serif; font-weight: normal;
    color: rgb(2, 164, 103); text-transform: uppercase; line-height: 1.1; margin: 40px 0 16px; }
  .mm-artikkeli p, .mm-artikkeli li { font-family: 'Open Sans', sans-serif; font-weight: 500; color: rgb(0, 0, 0); line-height: 1.6; }
  .mm-artikkeli p { text-align: justify; margin: 0 0 16px; }
  .mm-artikkeli a { color: rgb(0, 0, 0); text-decoration: underline; }
  @media (max-width: 767px) { .mm-artikkeli p { text-align: left; } }
</style>"""


def inline(text, plain=False):
    text = html.escape(text, quote=False)
    if plain:
        return re.sub(r"\*\*(.+?)\*\*", r"\1", re.sub(r"\[([^\]]+)\]\([^)]+\)", r"\1", text))
    text = re.sub(r"\*\*(.+?)\*\*", r"<strong>\1</strong>", text)

    def link(m):
        href = m.group(2)
        if href.startswith(BASE):
            return f'<a href="{href[len(BASE):] or "/"}">{m.group(1)}</a>'
        return f'<a href="{href}" target="_blank" rel="noopener">{m.group(1)}</a>'

    return re.sub(r"\[([^\]]+)\]\(([^)]+)\)", link, text)


def convert(md):
    meta = {}
    m = re.search(r"<!--(.*?)-->", md, re.S)
    if m:
        for line in m.group(1).splitlines():
            if ":" in line:
                k, v = line.split(":", 1)
                meta[k.strip()] = v.strip()
        md = md[m.end():]
    out, h1, faq, in_faq = [], "", [], False
    for block in re.split(r"\n\s*\n", md.strip()):
        lines = block.strip().splitlines()
        if lines[0].startswith("# "):
            h1 = lines[0][2:]
        elif lines[0].startswith("## "):
            in_faq = lines[0].startswith("## Yleisiä kysymyksiä")
            out.append(f"<h2>{inline(lines[0][3:])}</h2>")
        elif all(re.match(r"\d+\. ", l) for l in lines):
            out.append("<ol>\n" + "\n".join(f"  <li>{inline(l.split('. ', 1)[1])}</li>" for l in lines) + "\n</ol>")
        else:
            if in_faq and len(lines) == 2 and lines[0].startswith("**"):
                faq.append((inline(lines[0], True), inline(lines[1], True)))
            out.append("<p>" + "<br>\n".join(inline(l) for l in lines) + "</p>")
    return meta, h1, "\n".join(out), faq


def jsonld(meta, h1, faq):
    url = BASE + meta["URL"]
    graph = [
        {"@type": "BlogPosting", "@id": f"{url}#article", "headline": h1[:110], "url": url,
         "mainEntityOfPage": url, "description": meta.get("Meta description", ""), "inLanguage": "fi-FI",
         "author": {"@id": ORG_ID}, "publisher": {"@id": ORG_ID}},
        {"@type": "BreadcrumbList", "itemListElement": [
            {"@type": "ListItem", "position": 1, "name": "Etusivu", "item": f"{BASE}/"},
            {"@type": "ListItem", "position": 2, "name": "Uutiset", "item": f"{BASE}/uutiset"},
            {"@type": "ListItem", "position": 3, "name": h1, "item": url}]},
    ]
    if faq:
        graph.append({"@type": "FAQPage", "mainEntity": [
            {"@type": "Question", "name": q, "acceptedAnswer": {"@type": "Answer", "text": a}} for q, a in faq]})
    return json.dumps({"@context": "https://schema.org", "@graph": graph}, ensure_ascii=False, indent=2)


def main():
    OUT.mkdir(exist_ok=True)
    for f in sorted(HERE.glob("[0-9]-*.md")):
        meta, h1, body, faq = convert(f.read_text(encoding="utf-8"))
        header = (f"<!--\n  MAALAUSMESTARI – {h1}\n\n"
                  f"  Dudan blogipostauksen asetukset:\n"
                  f"    Otsikko (H1):     {h1}\n"
                  f"    URL:              {meta.get('URL', '')}\n"
                  f"    SEO-otsikko:      {meta.get('Title (≤60)', '')}\n"
                  f"    Meta description: {meta.get('Meta description', '')}\n"
                  f"    Pääkuvan alt:     {meta.get('Pääkuva alt', '')}\n\n"
                  f"  Liitä alla oleva koodi postauksen HTML-widgettiin.\n"
                  f"  JSON-LD: jos Duda tekee blogille jo oman BlogPosting-scheman, poista BlogPosting-lohko.\n-->\n")
        code = (header + STYLE + '\n\n<div class="mm-artikkeli">\n' + body + "\n</div>\n\n"
                + '<script type="application/ld+json">\n' + jsonld(meta, h1, faq) + "\n</script>\n")
        if "[TARKISTA]" in code:
            code = code.replace("-->\n", "  HUOM: koodissa on [TARKISTA]-kohtia – vaihda luvut ennen julkaisua.\n-->\n", 1)
        target = OUT / f.with_suffix(".html").name
        target.write_text(code, encoding="utf-8")
        print(f"{target.name}: {len(faq)} UKK-kysymystä")


if __name__ == "__main__":
    main()
