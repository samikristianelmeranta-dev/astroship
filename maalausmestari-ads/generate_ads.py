"""Maalausmestari – Google Ads -kampanjat (Uusimaa + Varsinais-Suomi), talon maalaus.

Ajo: python3 generate_ads.py -> kirjoittaa CSV:t kansioon ./editor/ ja tarkistaa merkkirajat.
CSV:t voi liittää Google Ads Editoriin (Tili -> Tuo -> Liitä teksti / CSV).
"""

import csv
import sys
from pathlib import Path

BASE = "https://www.maalausmestari.fi"
OUT = Path(__file__).parent / "editor"

LIMITS = {"headline": 30, "description": 90, "path": 15, "sitelink": 25,
          "sitelink_desc": 35, "callout": 25, "snippet": 25}
errors = []


def check(kind, text, where):
    if len(text) > LIMITS[kind]:
        errors.append(f"{where}: '{text}' {len(text)}/{LIMITS[kind]}")
    return text


# ------------------------------------------------------------------ alueet
CAMPAIGNS = {
    "Uusimaa": {
        "name": "Haku | Talon maalaus | Uusimaa",
        "loc": "Uudellamaalla",
        "loc_head": "Uudellamaalla",
        "city_main": "Espoo",
        "cities_pair": "Vantaa & Kerava",
        # Helsinki rajattu pois näkyvyysalueelta (asiakkaan toive)
        "exclude_locations": ["Helsinki"],
        "extra_negatives": ["helsinki", "helsingissä", "helsingin"],
        "cities": {"Espoo": "espoo", "Vantaa": "vantaa", "Kerava": "kerava",
                   "Järvenpää": "järvenpää", "Tuusula": "tuusula", "Nurmijärvi": "nurmijärvi",
                   "Hyvinkää": "hyvinkää", "Kirkkonummi": "kirkkonummi", "Lohja": "lohja", "Vihti": "vihti"},
        "brand_city": "Maalausmestari Uusimaa",
    },
    "Varsinais-Suomi": {
        "name": "Haku | Talon maalaus | Varsinais-Suomi",
        "loc": "Varsinais-Suomessa",
        "loc_head": "Turun seudulla",
        "city_main": "Turku",
        "cities_pair": "Kaarina & Raisio",
        "cities": {"Turku": "turku", "Kaarina": "kaarina", "Raisio": "raisio", "Naantali": "naantali",
                   "Lieto": "lieto", "Parainen": "parainen", "Salo": "salo", "Uusikaupunki": "uusikaupunki",
                   "Masku": "masku", "Paimio": "paimio", "Rusko": "rusko", "Aura": "aura",
                   "Mynämäki": "mynämäki", "Nousiainen": "nousiainen", "Laitila": "laitila"},
        "brand_city": "Maalausmestari Turku",
    },
}

# ------------------------------------------------------------------ avainsanat
GENERAL_KW = ["talon maalaus", "talon ulkomaalaus", "omakotitalon maalaus", "talon maalaus urakka",
              "ulkomaalaus", "maalausliike", "puutalon maalaus", "rapatun talon maalaus",
              "hirsitalon maalaus", "julkisivun maalaus", "julkisivumaalaus", "talon maalari"]
PRICE_KW = ["talon maalaus hinta", "talon maalauksen hinta", "mitä maksaa talon maalaus",
            "paljonko maksaa talon maalaus", "omakotitalon maalaus hinta", "talon ulkomaalaus hinta",
            "ulkomaalaus hinta"]
ROW_KW = ["rivitalon maalaus", "rivitalon ulkomaalaus", "taloyhtiön ulkomaalaus",
          "rivitalon maalaus hinta", "taloyhtiön julkisivun maalaus"]
CITY_TEMPLATES = ["talon maalaus {c}", "maalausliike {c}", "talon ulkomaalaus {c}", "ulkomaalaus {c}"]

NEGATIVES = ["itse", "ohje", "ohjeet", "kurssi", "koulutus", "työpaikka", "työpaikat", "rekry", "palkka",
             "harjoittelu", "sisämaalaus", "sisämaalaukset", "huoneiston", "asunnon maalaus", "seinän maalaus",
             "tapetointi", "keittiö", "kaappien", "huonekalu", "auto", "auton", "vene", "taulu", "taide",
             "maalikauppa", "rautakauppa", "bauhaus", "k-rauta", "byggmax", "värikartta", "maaliruisku",
             "airless", "vuokraus", "vuokra", "käytetty", "tori", "telineet vuokra", "painepesuri",
             "kalkkimaali", "pellavaöljymaali"]

# Kaikki mainokset ohjaavat samalle sivulle (asiakkaan toive)
LANDING = f"{BASE}/talonmaalaus"
LP = {"general": LANDING, "price": LANDING, "row": LANDING}


# ------------------------------------------------------------------ mainokset
def rsa(region, group):
    c = CAMPAIGNS[region]
    common = [
        "Pyydä ilmainen tarjous",
        "Maksuton arviokäynti",
        "Kiinteä hinta – ei yllätyksiä",
        "3 vuoden takuu työlle",
        "Yli 1000 maalattua kohdetta",
        "Kotimainen perheyritys",
        "Avainlippu-yritys",
        "Vastaus seuraavana arkipäivänä",
        "Tikkurilan ja Teknoksen maalit",
        "Varaa ensi kesän maalaus nyt",
    ]
    if group == "Talon maalaus":
        heads = [f"Talon maalaus {c['loc_head']}", f"Talon maalaus {c['city_main']}",
                 f"Talon maalaus {c['cities_pair']}", *common, "Tarjous netistä helposti", c["brand_city"]]
        pins = {heads[0]: "1", heads[1]: "1"}
        descs = [
            f"Talon ulkomaalaus ammattitaidolla {c['loc']}. Kiinteä hinta ja 3 vuoden takuu.",
            "Kutsu meidät maksuttomalle arviokäynnille – vastaamme viimeistään seuraavana arkipäivänä.",
            "Kotimainen perheyritys, yli 1000 maalattua kohdetta. Tikkurilan ja Teknoksen laatumaalit.",
            "Varaa ensi kesän talon maalaus jo nyt. Opastamme myös kotitalousvähennyksen hakemisessa.",
        ]
        paths = ("talon-maalaus", c["city_main"].lower())
        url = LP["general"]
    elif group == "Talon maalaus – paikkakunnat":
        heads = [f"Talon maalaus {{LOCATION(City):{c['city_main']}}}",
                 f"Maalausliike {c['loc_head']}", f"Talon maalaus {c['city_main']}", *common,
                 "Paikallinen maalausliike", c["brand_city"]]
        pins = {heads[0]: "1", heads[2]: "1"}
        descs = [
            "Paikallinen maalausliike lähelläsi. Talon ulkomaalaus kiinteällä hinnalla ja takuulla.",
            "Kutsu meidät maksuttomalle arviokäynnille – vastaamme viimeistään seuraavana arkipäivänä.",
            "Kotimainen perheyritys, yli 1000 maalattua kohdetta. Tikkurilan ja Teknoksen laatumaalit.",
            "Varaa ensi kesän talon maalaus jo nyt. Opastamme myös kotitalousvähennyksen hakemisessa.",
        ]
        paths = ("talon-maalaus", "paikkakunnat")
        url = LANDING
    elif group == "Talon maalaus – hinta":
        heads = ["Mitä talon maalaus maksaa?", "Tarjous netistä helposti", "Talon maalaus – hinta-arvio",
                 f"Talon maalaus {c['loc_head']}", *common, "Selkeä ja kiinteä urakkahinta", "Ei piilokuluja"]
        pins = {heads[0]: "1", heads[1]: "1", heads[2]: "1"}
        descs = [
            "Pyydä talon maalauksesta tarkka ja kiinteä tarjous maksutta – tiedät hinnan etukäteen.",
            "Kiinteähintainen urakkasopimus ilman piilokuluja. Talon maalaukselle 3 vuoden takuu.",
            "Kutsu meidät maksuttomalle arviokäynnille – vastaamme viimeistään seuraavana arkipäivänä.",
            "Opastamme kotitalousvähennyksen hakemisessa. Kotimainen perheyritys, yli 1000 kohdetta.",
        ]
        paths = ("talon-maalaus", "hinta")
        url = LP["price"]
    else:  # rivitalot
        heads = ["Rivitalon maalaus", "Taloyhtiön ulkomaalaus", f"Rivitalon maalaus {c['city_main']}",
                 f"Ulkomaalaus {c['loc_head']}", *common, "Isännöitsijän kumppani", "Pyydä taloyhtiölle tarjous"]
        pins = {heads[0]: "1", heads[1]: "1"}
        descs = [
            f"Rivitalojen ja taloyhtiöiden ulkomaalaukset {c['loc']}. Kiinteä urakkahinta.",
            "Kutsu meidät maksuttomalle arviokäynnille – vastaamme viimeistään seuraavana arkipäivänä.",
            "Kotimainen perheyritys, yli 1000 maalattua kohdetta. Tikkurilan ja Teknoksen laatumaalit.",
            "Julkisivut, katot ja muut ulkopinnat samalta urakoitsijalta – selkeä aikataulu ja takuu.",
        ]
        paths = ("rivitalot", "taloyhtiot")
        url = LP["row"]

    heads = heads[:15]
    where = f"{region}/{group}"
    for h in heads:
        # {LOCATION(City):X} lasketaan oletustekstin mukaan
        shown = h.replace("{LOCATION(City):", "").replace("}", "") if "LOCATION" in h else h
        check("headline", shown, where)
    for d in descs:
        check("description", d, where)
    for p in paths:
        check("path", p, where)
    return heads, pins, descs, paths, url


# ------------------------------------------------------------------ laajennukset
SITELINKS = [
    ("Pyydä tarjous", "Vastaamme seuraavana arkipäivänä", "Maksuton arviokäynti", f"{BASE}/tarjouspyyntö"),
    ("Hintalaskuri", "Laske maalauksen hinta-arvio", "Arvio talon maalauksen hinnasta", f"{BASE}/laskuri"),
    ("Referenssit ja galleria", "Katso toteutettuja kohteita", "Yli 1000 maalattua kohdetta", f"{BASE}/galleria"),
    ("Mitä talon maalaus maksaa", "Mistä urakkahinta muodostuu", "Lue hinnoittelun perusteet",
     f"{BASE}/mita-maksaa-talon-maalaus"),
    ("Kotitalousvähennys", "Näin hyödynnät vähennyksen", "Opastamme hakemisessa",
     f"{BASE}/kotitalousvahennys-talon-maalaus-2026"),
    ("Tiilikaton pinnoitus", "5 vuoden takuu työlle", "Ormax-tiilikattomaali", f"{BASE}/tiilikaton-pinnoitusjamaalaus"),
]
CALLOUTS = ["Maksuton arviokäynti", "Kiinteä urakkahinta", "3 vuoden takuu", "Kotimainen perheyritys",
            "Avainlippu-yritys", "Yli 1000 kohdetta", "Tikkurila & Teknos", "Vastaus 1 arkipäivässä"]
SNIPPET = ("Palvelut", ["Talon maalaus", "Julkisivumaalaus", "Tiilikaton pinnoitus", "Peltikaton maalaus",
                        "Rivitalot ja taloyhtiöt"])

for t, d1, d2, _ in SITELINKS:
    check("sitelink", t, "sitelink")
    check("sitelink_desc", d1, "sitelink")
    check("sitelink_desc", d2, "sitelink")
for co in CALLOUTS:
    check("callout", co, "callout")
for v in SNIPPET[1]:
    check("snippet", v, "snippet")

GROUPS = ["Talon maalaus", "Talon maalaus – paikkakunnat", "Talon maalaus – hinta", "Rivitalot ja taloyhtiöt"]


def main():
    OUT.mkdir(exist_ok=True)
    kw_rows, ad_rows, neg_rows, ext_rows = [], [], [], []
    for region, c in CAMPAIGNS.items():
        camp = c["name"]
        for kw in GENERAL_KW:
            for mt in ("Phrase", "Exact"):
                kw_rows.append([camp, "Talon maalaus", kw, mt, LP["general"]])
        for kw in PRICE_KW:
            for mt in ("Phrase", "Exact"):
                kw_rows.append([camp, "Talon maalaus – hinta", kw, mt, LP["price"]])
        for kw in ROW_KW:
            kw_rows.append([camp, "Rivitalot ja taloyhtiöt", kw, "Phrase", LP["row"]])
        for city in c["cities"]:
            for tpl in CITY_TEMPLATES:
                kw_rows.append([camp, "Talon maalaus – paikkakunnat", tpl.format(c=city.lower()), "Phrase",
                                LANDING])

        for g in GROUPS:
            heads, pins, descs, paths, url = rsa(region, g)
            row = [camp, g, "Responsive search ad", url]
            for i in range(15):
                h = heads[i] if i < len(heads) else ""
                row += [h, pins.get(h, "")]
            row += descs + list(paths)
            ad_rows.append(row)

        for n in NEGATIVES + c.get("extra_negatives", []):
            neg_rows.append([camp, n, "Campaign Negative Phrase"])

    with open(OUT / "sijainnit.csv", "w", newline="", encoding="utf-8") as f:
        w = csv.writer(f)
        w.writerow(["Campaign", "Location", "Kohdistus"])
        for region, c in CAMPAIGNS.items():
            w.writerow([c["name"], f"{region}, Suomi", "Kohdistettu (ihmiset sijainnissa)"])
            for loc in c.get("exclude_locations", []):
                w.writerow([c["name"], f"{loc}, Suomi", "Poissuljettu"])
    with open(OUT / "avainsanat.csv", "w", newline="", encoding="utf-8") as f:
        w = csv.writer(f)
        w.writerow(["Campaign", "Ad group", "Keyword", "Criterion Type", "Final URL"])
        w.writerows(kw_rows)
    with open(OUT / "mainokset.csv", "w", newline="", encoding="utf-8") as f:
        w = csv.writer(f)
        hdr = ["Campaign", "Ad group", "Ad type", "Final URL"]
        for i in range(1, 16):
            hdr += [f"Headline {i}", f"Headline {i} position"]
        hdr += [f"Description {i}" for i in range(1, 5)] + ["Path 1", "Path 2"]
        w.writerow(hdr)
        w.writerows(ad_rows)
    with open(OUT / "negatiiviset.csv", "w", newline="", encoding="utf-8") as f:
        w = csv.writer(f)
        w.writerow(["Campaign", "Keyword", "Criterion Type"])
        w.writerows(neg_rows)
    with open(OUT / "laajennukset.csv", "w", newline="", encoding="utf-8") as f:
        w = csv.writer(f)
        w.writerow(["Tyyppi", "Teksti", "Kuvausrivi 1", "Kuvausrivi 2", "URL"])
        for t, d1, d2, u in SITELINKS:
            w.writerow(["Sivulinkki", t, d1, d2, u])
        for co in CALLOUTS:
            w.writerow(["Lisätieto (callout)", co, "", "", ""])
        w.writerow([f"Rakenteinen katkelma: {SNIPPET[0]}", "; ".join(SNIPPET[1]), "", "", ""])
        w.writerow(["Puhelu", "+358503667703", "", "", ""])

    print(f"avainsanoja {len(kw_rows)}, mainoksia {len(ad_rows)}, negatiivisia {len(neg_rows)}")
    if errors:
        print("MERKKIRAJAT YLITTYVÄT:\n" + "\n".join(errors))
        sys.exit(1)
    print("Merkkirajat OK")


if __name__ == "__main__":
    main()
