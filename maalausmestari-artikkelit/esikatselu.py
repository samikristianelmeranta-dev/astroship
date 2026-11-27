"""Tekee artikkeleista esikatselusivun esikatselu.html (sivuston värit ja otsikkotyyli).

Ajo: python3 esikatselu.py
"""

import html
import re
from pathlib import Path

HERE = Path(__file__).parent


def inline(text):
    text = html.escape(text, quote=False)
    text = re.sub(r"\*\*(.+?)\*\*", r"<strong>\1</strong>", text)
    text = re.sub(r"\[([^\]]+)\]\(([^)]+)\)", r'<a href="\2" target="_blank" rel="noopener">\1</a>', text)
    text = text.replace("[TARKISTA]", '<mark title="Tarkista ennen julkaisua">[TARKISTA]</mark>')
    return text


def render(md):
    meta = {}
    m = re.search(r"<!--(.*?)-->", md, re.S)
    if m:
        for line in m.group(1).splitlines():
            if ":" in line:
                k, v = line.split(":", 1)
                meta[k.strip()] = v.strip()
        md = md[m.end():]
    out, h1 = [], ""
    for block in re.split(r"\n\s*\n", md.strip()):
        lines = block.strip().splitlines()
        if not lines:
            continue
        if lines[0].startswith("# "):
            h1 = lines[0][2:]
            out.append(f"<h1>{inline(h1)}</h1>")
        elif lines[0].startswith("## "):
            out.append(f"<h2>{inline(lines[0][3:])}</h2>")
        elif all(re.match(r"\d+\. ", l) for l in lines):
            out.append("<ol>" + "".join(f"<li>{inline(l.split('. ', 1)[1])}</li>" for l in lines) + "</ol>")
        else:
            out.append("<p>" + "<br>".join(inline(l) for l in lines) + "</p>")
    return meta, h1, "\n".join(out)


def main():
    tabs, panels = [], []
    for i, f in enumerate(sorted(HERE.glob("[0-9]-*.md"))):
        meta, h1, body = render(f.read_text(encoding="utf-8"))
        title = meta.get("Title (≤60)", h1)
        desc = meta.get("Meta description", "")
        url = "www.maalausmestari.fi" + meta.get("URL", "").replace("/", " › ", 1) if meta.get("URL") else ""
        tabs.append(f'<button role="tab" data-i="{i}" aria-selected="{str(i == 0).lower()}">{html.escape(h1.split(" –")[0])}</button>')
        panels.append(f"""
<section class="panel" data-i="{i}" {'hidden' if i else ''}>
  <div class="serp" aria-label="Google-hakutuloksen esikatselu">
    <div class="serp-label">Google-esikatselu</div>
    <div class="serp-url">{html.escape(url)}</div>
    <div class="serp-title">{html.escape(title)}</div>
    <div class="serp-desc">{html.escape(desc)}</div>
    <div class="serp-meta">Title {len(title)}/60 · Description {len(desc)}/160</div>
  </div>
  <article>{body}</article>
</section>""")
    page = f"""<!doctype html>
<html lang="fi">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Artikkelien esikatselu</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@400;500;700&family=Barlow+Condensed:ital,wght@1,800&display=swap" rel="stylesheet">
<style>
  :root {{ --green: rgb(2,164,103); --ink: #000; --muted: #5f6368; --bg: #fff; --soft: #f4f7f5; --line: #dfe5e2; }}
  * {{ box-sizing: border-box; }}
  body {{ margin: 0; background: var(--bg); color: var(--ink); font-family: 'Open Sans', sans-serif; font-weight: 500; }}
  .bar {{ position: sticky; top: 0; z-index: 2; background: #fff; border-bottom: 1px solid var(--line); }}
  .bar-inner {{ max-width: 960px; margin: 0 auto; padding: 12px 16px; display: flex; gap: 8px; flex-wrap: wrap; align-items: center; }}
  .bar strong {{ margin-right: auto; font-size: 14px; }}
  button[role=tab] {{ font: inherit; font-size: 14px; border: 1px solid var(--line); background: #fff; padding: 8px 14px; border-radius: 999px; cursor: pointer; }}
  button[role=tab][aria-selected=true] {{ background: var(--green); border-color: var(--green); color: #fff; }}
  main {{ max-width: 960px; margin: 0 auto; padding: 24px 16px 64px; }}
  .serp {{ background: var(--soft); border: 1px solid var(--line); border-radius: 8px; padding: 16px; margin-bottom: 32px; font-family: Arial, sans-serif; font-weight: 400; }}
  .serp-label {{ font-size: 12px; text-transform: uppercase; letter-spacing: .06em; color: var(--muted); margin-bottom: 8px; }}
  .serp-url {{ font-size: 14px; color: #202124; }}
  .serp-title {{ font-size: 20px; color: #1a0dab; margin: 4px 0; }}
  .serp-desc {{ font-size: 14px; color: #4d5156; line-height: 1.5; }}
  .serp-meta {{ font-size: 12px; color: var(--muted); margin-top: 8px; }}
  article h1, article h2 {{ font-family: 'Barlow Condensed', 'Arial Narrow', sans-serif; font-style: italic; font-weight: 800; color: var(--green); text-transform: uppercase; line-height: 1.05; }}
  article h1 {{ font-size: clamp(32px, 6vw, 50px); margin: 0 0 24px; }}
  article h2 {{ font-size: clamp(26px, 4.5vw, 40px); margin: 40px 0 16px; }}
  article p, article li {{ font-size: 16px; line-height: 1.7; }}
  article p {{ text-align: justify; margin: 0 0 16px; }}
  article a {{ color: var(--ink); text-decoration: underline; text-decoration-color: var(--green); text-underline-offset: 3px; }}
  article strong {{ font-weight: 700; }}
  mark {{ background: #ffe58a; padding: 0 3px; }}
  @media (max-width: 600px) {{ article p {{ text-align: left; }} }}
</style>
</head>
<body>
<div class="bar"><div class="bar-inner" role="tablist"><strong>Maalausmestari – artikkeliluonnokset</strong>{''.join(tabs)}</div></div>
<main>{''.join(panels)}</main>
<script>
  document.querySelectorAll('button[role=tab]').forEach(b => b.addEventListener('click', () => {{
    document.querySelectorAll('button[role=tab]').forEach(x => x.setAttribute('aria-selected', x === b));
    document.querySelectorAll('.panel').forEach(p => p.hidden = p.dataset.i !== b.dataset.i);
    window.scrollTo(0, 0);
  }}));
</script>
</body>
</html>
"""
    (HERE / "esikatselu.html").write_text(page, encoding="utf-8")
    print("esikatselu.html")


if __name__ == "__main__":
    main()
