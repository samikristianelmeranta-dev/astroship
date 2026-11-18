#!/usr/bin/env python3
import json, re, sys, itertools
CITIES=['salo','kaarina','naantali','raisio','parainen','paimio','lieto','masku']
SERV=['ikkunahuolto','ovihuolto','ikkunoiden-tiivistys','ovien-tiivistys','ikkunoiden-korjaus','ikkunapuitteiden-huolto']
SLUGS={''}|set(SERV)|{f'{s}-{c}' for s in SERV for c in CITIES}
def text(p):
    t=' '.join([p['lead'],p['short_answer']]+[s['h2']+' '+s['html'] for s in p['sections']]+[f['q']+' '+f['a'] for f in p['faq']])
    return re.sub(r'<[^>]+>',' ',t)
def words(p): return re.findall(r'\w+',text(p).lower())
def sh(p):
    w=words(p); return {' '.join(w[i:i+8]) for i in range(len(w)-7)}
def main(path):
    d=json.load(open(path)); err=[]
    pages={'main':d['main']}; pages.update(d['cities'])
    for c in CITIES:
        if c not in d['cities']: err.append(f'puuttuu kaupunki {c}')
    for k,p in pages.items():
        for f in ['title','meta','h1','lead','short_answer','sections','faq']:
            if f not in p: err.append(f'{k}: kenttä {f} puuttuu'); 
        if any(f not in p for f in ['title','meta','h1','lead','short_answer','sections','faq']): continue
        n=len(words(p)); lo,hi=(1800,2400) if k=='main' else (850,1150)
        if not lo<=n<=hi: err.append(f'{k}: sanamäärä {n}, pitää olla {lo}–{hi}')
        if not 45<=len(p['title'])<=62: err.append(f'{k}: title {len(p["title"])} merkkiä (45–62)')
        if not 130<=len(p['meta'])<=158: err.append(f'{k}: meta {len(p["meta"])} merkkiä (130–158)')
        ns=len(p['sections']); nf=len(p['faq'])
        if k=='main' and not (8<=ns<=10 and 8<=nf<=10): err.append(f'{k}: osioita {ns}, ukk {nf}')
        if k!='main' and not (6<=ns<=7 and 5<=nf<=6): err.append(f'{k}: osioita {ns}, ukk {nf}')
        ids=[s['id'] for s in p['sections']]
        if len(set(ids))!=len(ids) or any(not re.fullmatch(r'[a-z0-9-]+',i) for i in ids): err.append(f'{k}: osioiden id:t')
        raw=' '.join(s['html'] for s in p['sections'])
        for tag in set(re.findall(r'</?([a-zA-Z0-9]+)',raw)):
            if tag not in ('p','h3','ul','ol','li','strong','a'): err.append(f'{k}: kielletty tagi <{tag}>')
        if re.search(r'class=|style=',raw): err.append(f'{k}: class/style-attribuutti')
        for h in re.findall(r'href="([^"]*)"',raw):
            if h=='#tarjous': continue
            if not (h.startswith('/') and h.endswith('/') and h.strip('/') in SLUGS): err.append(f'{k}: tuntematon linkki {h}')
        for f in p['faq']:
            if '<' in f['a']: err.append(f'{k}: HTML ukk-vastauksessa')
        t=text(p)+' '+p['title']+' '+p['meta']+' '+p['h1']
        for m in re.findall(r'[\d  ]+\s?(?:€|euroa|e/h)',t):
            if not re.search(r'150|1[  ]?600',m): err.append(f'{k}: hinta "{m.strip()}"')
        for bad in ['takuu','sertifi','—','alkaen','vuoden kokemu','markkinoiden','@','http']:
            if bad in t.lower(): err.append(f'{k}: kielletty ilmaus "{bad}"')
        print(f'{k:9s} {n:5d} sanaa  title {len(p["title"])}  meta {len(p["meta"])}  osiot {ns}  ukk {nf}')
    S={k:sh(p) for k,p in pages.items() if 'sections' in p}
    for a,b in itertools.combinations(S,2):
        if not S[a] or not S[b]: continue
        o=len(S[a]&S[b])/min(len(S[a]),len(S[b]))
        if o>0.12: err.append(f'päällekkäisyys {a}~{b}: {o:.0%} (max 12 %)')
    for e in err: print('VIRHE',e)
    print('OK' if not err else f'{len(err)} virhettä'); return 0 if not err else 1
if __name__=='__main__': sys.exit(main(sys.argv[1]))
