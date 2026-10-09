"""Generoi Maalausmestarin JSON-LD-schemat jokaiselle sitemapin sivulle.

Ajo: python3 generate.py  ->  kirjoittaa koodit kansioon ./koodit/
Kaikki yrityksen tiedot ovat yhdessä paikassa (ORG alla), joten muutokset
tehdään tänne ja ajetaan skripti uudelleen.
"""

import json
import re
import shutil
from pathlib import Path

BASE = "https://www.maalausmestari.fi"
ORG_ID = f"{BASE}/#organization"
SITE_ID = f"{BASE}/#website"
TURKU_ID = f"{BASE}/#toimipiste-turku"
HELSINKI_ID = f"{BASE}/#toimipiste-helsinki"
OUT = Path(__file__).parent / "koodit"

IMAGES = [
    "https://irp.cdn-website.com/dacabec0/dms3rep/multi/talon+maalaus-586b90b5.webp",
    "https://irp.cdn-website.com/dacabec0/dms3rep/multi/tiilikaton+pinnoitus-ed8db2d6.webp",
    "https://irp.cdn-website.com/dacabec0/dms3rep/multi/peltikaton+maalaus-8742761e.webp",
]

REGIONS = {
    "Varsinais-Suomi": "Varsinais-Suomi",
    "Uusimaa": "Uusimaa",
    "Etelä-Karjala": "Etelä-Karjala",
    "Pirkanmaa": "Pirkanmaa",
    "Pohjois-Savo": "Pohjois-Savo",
}


def region(name):
    return {"@type": "AdministrativeArea", "name": name,
            "containedInPlace": {"@type": "Country", "name": "Suomi"}}


def warranty(years):
    return {"@type": "WarrantyPromise",
            "durationOfWarranty": {"@type": "QuantitativeValue", "value": years, "unitCode": "ANN"}}


# ---------------------------------------------------------------- koko sivusto
ORG = {
    "@type": "HousePainter",
    "@id": ORG_ID,
    "name": "Maalausmestari",
    "url": f"{BASE}/",
    "description": ("Kotimainen maalausalan perheyritys, joka on erikoistunut talojen ulkomaalauksiin, "
                    "tiilikattojen pinnoituksiin ja peltikattojen maalauksiin Varsinais-Suomessa ja "
                    "Uudellamaalla. Toimipisteet Turussa ja Helsingissä."),
    "slogan": "Ulkomaalausten vankka ammattilainen.",
    "email": "info@maalausmestari.fi",
    "telephone": "+358503667703",
    "image": IMAGES,
    "address": [
        {"@type": "PostalAddress", "addressLocality": "Turku", "addressRegion": "Varsinais-Suomi", "addressCountry": "FI"},
        {"@type": "PostalAddress", "addressLocality": "Helsinki", "addressRegion": "Uusimaa", "addressCountry": "FI"},
    ],
    "areaServed": [region(r) for r in REGIONS],
    "contactPoint": {
        "@type": "ContactPoint",
        "contactType": "sales",
        "telephone": "+358503667703",
        "email": "info@maalausmestari.fi",
        "areaServed": "FI",
        "availableLanguage": ["fi"],
    },
    "employee": {
        "@type": "Person",
        "name": "Juhana Pöyhtäri",
        "telephone": "+358503667703",
        "email": "juhana.poyhtari@maalausmestari.fi",
        "worksFor": {"@id": ORG_ID},
    },
    "department": [{"@id": TURKU_ID}, {"@id": HELSINKI_ID}],
    "award": "Avainlippu – Suomalaisen Työn Liitto",
    "knowsAbout": ["Talon maalaus", "Ulkomaalaus", "Julkisivumaalaus", "Tiilikaton pinnoitus",
                   "Peltikaton maalaus", "Rapatun talon maalaus", "Hirsitalon maalaus",
                   "Taloyhtiöiden maalaus", "Kotitalousvähennys"],
    "sameAs": ["https://www.instagram.com/maalausmestari.fi/"],
    "hasOfferCatalog": {
        "@type": "OfferCatalog",
        "name": "Maalausliikkeen palvelut",
        "itemListElement": [
            {"@type": "Offer", "itemOffered": {"@id": f"{BASE}/talonmaalaus#service"}, "warranty": warranty(3)},
            {"@type": "Offer", "itemOffered": {"@id": f"{BASE}/tiilikaton-pinnoitusjamaalaus#service"}, "warranty": warranty(5)},
            {"@type": "Offer", "itemOffered": {"@id": f"{BASE}/peltikatonmaalaus#service"}, "warranty": warranty(2)},
            {"@type": "Offer", "itemOffered": {"@id": f"{BASE}/ulkomaalaus#service"}},
            {"@type": "Offer", "itemOffered": {"@id": f"{BASE}/julkisivumaalaus#service"}},
            {"@type": "Offer", "itemOffered": {"@id": f"{BASE}/taloyhtiöt#service"}},
        ],
    },
}

DEPARTMENTS = [
    {
        "@type": "HousePainter",
        "@id": TURKU_ID,
        "name": "Maalausmestari Turku",
        "url": f"{BASE}/varsinais-suomi",
        "parentOrganization": {"@id": ORG_ID},
        "image": IMAGES[0],
        "address": {"@type": "PostalAddress", "addressLocality": "Turku", "addressRegion": "Varsinais-Suomi", "addressCountry": "FI"},
        "areaServed": region("Varsinais-Suomi"),
    },
    {
        "@type": "HousePainter",
        "@id": HELSINKI_ID,
        "name": "Maalausmestari Helsinki",
        "url": f"{BASE}/uusimaab84c772c",
        "parentOrganization": {"@id": ORG_ID},
        "image": IMAGES[0],
        "address": {"@type": "PostalAddress", "addressLocality": "Helsinki", "addressRegion": "Uusimaa", "addressCountry": "FI"},
        "areaServed": region("Uusimaa"),
    },
]

WEBSITE = {
    "@type": "WebSite",
    "@id": SITE_ID,
    "url": f"{BASE}/",
    "name": "Maalausmestari",
    "inLanguage": "fi-FI",
    "publisher": {"@id": ORG_ID},
}

# ---------------------------------------------------------------- palvelut
SERVICES = {
    "talonmaalaus": ("Talon maalaus", "Talon maalaus",
                     "Laadukas talon maalaus kotimaiselta perheyritykseltä. Korkealaatuiset maalit, "
                     "kiinteähintainen urakkasopimus ja 3 vuoden takuu.", 3),
    "ulkomaalaus": ("Ulkomaalaus", "Ulkomaalaus",
                    "Talojen ulkomaalaukset ripeästi ja ensiluokkaisella työjäljellä – talon maalaus, "
                    "julkisivumaalaus ja kattojen maalaus samalta maalausliikkeeltä.", None),
    "julkisivumaalaus": ("Julkisivumaalaus", "Julkisivumaalaus",
                         "Julkisivun maalaus ja kunnostus omakotitaloihin, rivitaloihin ja taloyhtiöille "
                         "Tikkurilan ja Teknoksen laadukkailla tuotteilla.", None),
    "tiilikaton-pinnoitusjamaalaus": ("Tiilikaton pinnoitus ja maalaus", "Tiilikaton pinnoitus",
                                      "Tiilikaton pinnoitus suojaa kattoa säältä ja kulutukselta ja parantaa sen "
                                      "ulkonäköä. Käytämme Ormax-tiilikattomaalia. Työlle 5 vuoden takuu.", 5),
    "peltikatonmaalaus": ("Peltikaton maalaus", "Peltikaton maalaus",
                          "Peltikaton maalaus pidentää katon käyttöikää ja ehkäisee ruostumista. Tarkka "
                          "pintakäsittely ja laadukkaat maalit. Työlle 2 vuoden takuu.", 2),
    "taloyhtiöt": ("Taloyhtiöiden maalaukset", "Taloyhtiön ulkomaalaus",
                   "Ulkomaalaukset taloyhtiöille ja rivitaloille kiinteähintaisella urakkasopimuksella – "
                   "julkisivut, katot ja muut ulkopinnat.", None),
}

OTHER_PAGES = {
    "galleria": ("Galleria", "ImageGallery", "Kuvia Maalausmestarin toteuttamista talon- ja kattomaalauksista."),
    "uutiset": ("Uutiset", "CollectionPage", "Maalausmestarin uutiset, artikkelit ja vinkit talon ulkomaalaukseen."),
    "toimipisteet": ("Toimipisteet", "ContactPage", "Maalausmestarin toimipisteet Turussa ja Helsingissä."),
    "tarjouspyyntö": ("Tarjouspyyntö", "ContactPage",
                      "Pyydä tarjous talon maalauksesta, tiilikaton pinnoituksesta tai peltikaton maalauksesta. "
                      "Vastaamme viimeistään seuraavana arkipäivänä."),
    "laskuri": ("Hintalaskuri", "WebPage", "Laske hinta-arvio talon tai katon maalaukselle."),
    "tietosuojalauseke": ("Tietosuojalauseke", "WebPage", "Maalausmestarin tietosuojalauseke."),
}

# ---------------------------------------------------------------- alueet
REGION_PAGES = {
    "varsinais-suomi": ("Maalausliike Varsinais-Suomi – Turku", "Varsinais-Suomi", TURKU_ID),
    "uusimaab84c772c": ("Maalausliike Uusimaa – Helsinki", "Uusimaa", HELSINKI_ID),
    "turku": ("Maalausliike Turku", "Varsinais-Suomi", TURKU_ID),
    "helsinki": ("Maalausliike Helsinki", "Uusimaa", HELSINKI_ID),
    "lappeenranta": ("Maalausliike Lappeenranta", "Etelä-Karjala", None),
    "pirkanmaa": ("Maalausliike Pirkanmaa", "Pirkanmaa", None),
    "pohjois-savo": ("Maalausliike Pohjois-Savo", "Pohjois-Savo", None),
}

# kaupunki: (nimi, inessiivi/adessiivi, maakunta)
CITIES = {
    "aura": ("Aura", "Aurassa", "Varsinais-Suomi"),
    "kaarina": ("Kaarina", "Kaarinassa", "Varsinais-Suomi"),
    "laitila": ("Laitila", "Laitilassa", "Varsinais-Suomi"),
    "lieto": ("Lieto", "Liedossa", "Varsinais-Suomi"),
    "masku": ("Masku", "Maskussa", "Varsinais-Suomi"),
    "mynämäki": ("Mynämäki", "Mynämäellä", "Varsinais-Suomi"),
    "naantali": ("Naantali", "Naantalissa", "Varsinais-Suomi"),
    "nousiainen": ("Nousiainen", "Nousiaisissa", "Varsinais-Suomi"),
    "paimio": ("Paimio", "Paimiossa", "Varsinais-Suomi"),
    "parainen": ("Parainen", "Paraisilla", "Varsinais-Suomi"),
    "raisio": ("Raisio", "Raisiossa", "Varsinais-Suomi"),
    "rusko": ("Rusko", "Ruskolla", "Varsinais-Suomi"),
    "salo": ("Salo", "Salossa", "Varsinais-Suomi"),
    "turku": ("Turku", "Turussa", "Varsinais-Suomi"),
    "uusikaupunki": ("Uusikaupunki", "Uudessakaupungissa", "Varsinais-Suomi"),
    "espoo": ("Espoo", "Espoossa", "Uusimaa"),
    "helsinki": ("Helsinki", "Helsingissä", "Uusimaa"),
    "hyvinkää": ("Hyvinkää", "Hyvinkäällä", "Uusimaa"),
    "järvenpää": ("Järvenpää", "Järvenpäässä", "Uusimaa"),
    "kerava": ("Kerava", "Keravalla", "Uusimaa"),
    "kirkkonummi": ("Kirkkonummi", "Kirkkonummella", "Uusimaa"),
    "lohja": ("Lohja", "Lohjalla", "Uusimaa"),
    "nurmijärvi": ("Nurmijärvi", "Nurmijärvellä", "Uusimaa"),
    "tuusula": ("Tuusula", "Tuusulassa", "Uusimaa"),
    "vantaa": ("Vantaa", "Vantaalla", "Uusimaa"),
    "vihti": ("Vihti", "Vihdissä", "Uusimaa"),
    "lappeenranta": ("Lappeenranta", "Lappeenrannassa", "Etelä-Karjala"),
    "imatra": ("Imatra", "Imatralla", "Etelä-Karjala"),
    "ruokolahti": ("Ruokolahti", "Ruokolahdella", "Etelä-Karjala"),
    "luumäki": ("Luumäki", "Luumäellä", "Etelä-Karjala"),
    "savitaipale": ("Savitaipale", "Savitaipaleella", "Etelä-Karjala"),
    "lemi": ("Lemi", "Lemillä", "Etelä-Karjala"),
}

# ---------------------------------------------------------------- artikkelit
# slug: (otsikko, dateModified sitemapista)
ARTICLES = {
    "mita-maksaa-talon-maalaus": ("Mitä maksaa talon maalaus?", "2026-05-05T13:02:07+00:00"),
    "talon-maalaus-ohjeet-ja-vinkit": ("Talon maalaus – ohjeet ja vinkit", "2026-05-05T13:00:54+00:00"),
    "kotitalousvahennys-talon-maalaus-2026": ("Kotitalousvähennys talon maalauksesta 2026", "2026-05-04T09:53:41+00:00"),
    "rivitalon-maalaus-helsinki-mita-maksaa-rivitalon-maalaus-helsingissa": (
        "Rivitalon maalaus Helsinki – mitä maksaa rivitalon maalaus Helsingissä?", "2026-04-24T09:54:29+00:00"),
    "kuinka-kauan-talon-maalaus-kestaa-helsingissa": ("Kuinka kauan talon maalaus kestää Helsingissä?", "2026-05-04T12:53:57+00:00"),
    "talon-maalaus-helsingissa-mista-urakkahinta-muodostuu": (
        "Talon maalaus Helsingissä – mistä urakkahinta muodostuu?", "2026-04-24T09:59:20+00:00"),
    "puutalon-ulkomaalaus-vaihe-vaiheelta": ("Puutalon ulkomaalaus vaihe vaiheelta", "2025-11-24T12:48:40+00:00"),
    "maalipinnan-hilseily-ja-halkeilu-syyt-ja-ratkaisut": (
        "Maalipinnan hilseily ja halkeilu – syyt ja ratkaisut", "2025-11-24T12:48:39+00:00"),
    "miksi-kannattaa-valita-paikallinen-ulkomaalausyritys": (
        "Miksi kannattaa valita paikallinen ulkomaalausyritys?", "2025-11-24T12:48:40+00:00"),
    "rivitalon-maalaus-hinta-mita-se-maksaa-ja-mista-se-koostuu": (
        "Rivitalon maalaus hinta – mitä se maksaa ja mistä se koostuu?", "2025-11-24T12:48:40+00:00"),
    "talon-maalaus-saaristo-olosuhteissa-turun-saaristo-rannikko": (
        "Talon maalaus saaristo-olosuhteissa – Turun saaristo ja rannikko", "2025-11-24T12:48:39+00:00"),
    "voiko-hirsitalon-maalata": ("Voiko hirsitalon maalata?", "2025-11-24T12:48:40+00:00"),
    "talon-maalaaminen-uudella-varilla-varin-vaihtaminen-taloon": (
        "Talon maalaaminen uudella värillä – värin vaihtaminen taloon", "2025-11-24T12:48:38+00:00"),
    "talon-ulkomaalaus-milloin-ulkomaalaus-kannattaa-tehda": (
        "Talon ulkomaalaus – milloin ulkomaalaus kannattaa tehdä?", "2025-11-24T12:48:40+00:00"),
    "puutalon-maalaus-maalaamalla-puutalon-saannollisesti-pidat-julkisivun-kauniina": (
        "Puutalon maalaus – maalaamalla puutalon säännöllisesti pidät julkisivun kauniina", "2025-11-24T12:48:39+00:00"),
    "talon-ulkomaalaus-on-tarkea-osa-rakennuksen-yllapitoa": (
        "Talon ulkomaalaus on tärkeä osa rakennuksen ylläpitoa", "2025-11-25T09:17:22+00:00"),
    "kauanko-talon-maalaus-kestaa": ("Kauanko talon maalaus kestää?", "2025-11-24T12:48:41+00:00"),
    "ulkomaalaukset-turun-alueella-takuutyona": ("Ulkomaalaukset Turun alueella takuutyönä", "2025-11-24T12:48:39+00:00"),
    "talon-maalaus-varsinais-suomessa-kaarina-parainen-ja-lahialueet": (
        "Talon maalaus Varsinais-Suomessa – Kaarina, Parainen ja lähialueet", "2025-11-24T12:48:40+00:00"),
    "maalausliike-maalausmestari-kiittaa-asiakkaitaan-vuodesta-2023": (
        "Maalausliike Maalausmestari kiittää asiakkaitaan vuodesta 2023", "2025-11-24T12:48:39+00:00"),
    "rapatun-talon-maalaus": ("Rapatun talon maalaus", "2025-11-24T12:48:40+00:00"),
    "talon-maalaus-nostaa-talon-arvoa-ja-arvokkuutta": (
        "Talon maalaus nostaa talon arvoa ja arvokkuutta", "2025-11-24T12:48:41+00:00"),
    "tiilikaton-pinnoitus-ja-maalaus": ("Tiilikaton pinnoitus ja maalaus", "2025-11-24T12:48:39+00:00"),
    "talonulkomaalaus": ("Talon ulkomaalaus", "2025-11-24T12:48:39+00:00"),
    "maalausmestari-yhteistyohon-tiilikattovalmistaja-bmi-ormaxin-kanssa": (
        "Maalausmestari yhteistyöhön tiilikattovalmistaja BMI Ormaxin kanssa", "2025-11-24T12:48:39+00:00"),
    "tiedote": ("Tiedote", "2025-11-24T12:48:41+00:00"),
    "teenko-itse-vai-tilaanko-ammattilaiselta": ("Teenkö itse vai tilaanko ammattilaiselta?", "2025-11-24T12:48:41+00:00"),
    "hyvaa-alkanutta-vuotta-2020": ("Hyvää alkanutta vuotta 2020", "2025-11-24T12:48:41+00:00"),
}


# ---------------------------------------------------------------- apurit
def url(path):
    return f"{BASE}/{path}" if path else f"{BASE}/"


def breadcrumb(path, trail):
    """trail = [(nimi, polku), ...] ilman etusivua."""
    items = [("Etusivu", "")] + trail
    return {
        "@type": "BreadcrumbList",
        "@id": f"{url(path)}#breadcrumb",
        "itemListElement": [
            {"@type": "ListItem", "position": i + 1, "name": n, "item": url(p)}
            for i, (n, p) in enumerate(items)
        ],
    }


def webpage(path, name, description, page_type="WebPage", **extra):
    node = {
        "@type": page_type,
        "@id": f"{url(path)}#webpage",
        "url": url(path),
        "name": name,
        "description": description,
        "inLanguage": "fi-FI",
        "isPartOf": {"@id": SITE_ID},
        "breadcrumb": {"@id": f"{url(path)}#breadcrumb"},
    }
    node.update(extra)
    return node


def service(path, name, service_type, description, area, years=None, audience=None):
    node = {
        "@type": "Service",
        "@id": f"{url(path)}#service",
        "name": name,
        "serviceType": service_type,
        "description": description,
        "url": url(path),
        "provider": {"@id": ORG_ID},
        "areaServed": area,
        "brand": [{"@type": "Brand", "name": b} for b in ("Tikkurila", "Teknos", "Ormax")]
        if service_type != "Tiilikaton pinnoitus" else {"@type": "Brand", "name": "Ormax"},
    }
    if years:
        node["offers"] = {"@type": "Offer", "warranty": warranty(years)}
    if audience:
        node["audience"] = {"@type": "Audience", "audienceType": audience}
    return node


def script(graph, comment):
    body = json.dumps({"@context": "https://schema.org", "@graph": graph}, ensure_ascii=False, indent=2)
    return f"<!-- {comment} -->\n<script type=\"application/ld+json\">\n{body}\n</script>\n"


def filename(path):
    s = path.replace("/", "--").translate(str.maketrans("äöå", "aoa")) or "etusivu"
    return f"{s}.html"


# ---------------------------------------------------------------- generointi
def build():
    pages = {}  # suhteellinen tiedostopolku -> (sivun polku, sisältö)

    def add(folder, path, graph):
        rel = f"{folder}/{filename(path)}"
        pages[rel] = (path, script(graph, f"Maalausmestari – JSON-LD sivulle {url(path)}"))

    # koko sivusto (Head HTML, kaikki sivut)
    pages["00-koko-sivusto.html"] = ("*", script([ORG, *DEPARTMENTS, WEBSITE],
                                                 "Maalausmestari – koko sivuston JSON-LD (Site Settings → Head HTML)"))

    # etusivu – FAQPage tulee jo Dudan accordion-widgetiltä (schema: true), ei tuplata
    add("1-paasivut", "", [
        webpage("", "Maalausliike Turku & Helsinki – Maalausmestari",
                "Kotimainen perheyritys, hyvän palvelun maalausliike. Maalaamme taloja ja kattoja "
                "Varsinais-Suomessa (Turku) ja Uudellamaalla (Helsinki). Yli 1000 maalattua kohdetta.",
                about={"@id": ORG_ID},
                primaryImageOfPage={"@type": "ImageObject", "url": IMAGES[0]}),
        breadcrumb("", []),
    ])

    for path, (name, stype, desc, years) in SERVICES.items():
        audience = "Taloyhtiöt ja isännöitsijät" if path == "taloyhtiöt" else None
        add("1-paasivut", path, [
            webpage(path, name, desc, about={"@id": f"{url(path)}#service"},
                    mainEntity={"@id": f"{url(path)}#service"}),
            breadcrumb(path, [(name, path)]),
            service(path, name, stype, desc,
                    [region("Varsinais-Suomi"), region("Uusimaa")], years, audience),
        ])

    article_urls = [url(s) for s in ARTICLES]
    for path, (name, ptype, desc) in OTHER_PAGES.items():
        extra = {"about": {"@id": ORG_ID}}
        if path == "uutiset":
            extra["mainEntity"] = {
                "@type": "ItemList",
                "itemListElement": [{"@type": "ListItem", "position": i + 1, "url": u}
                                    for i, u in enumerate(article_urls)],
            }
        if path == "toimipisteet":
            extra["mainEntity"] = [{"@id": TURKU_ID}, {"@id": HELSINKI_ID}]
        if path == "tarjouspyyntö":
            extra["mainEntity"] = {"@id": ORG_ID}
        add("1-paasivut", path, [webpage(path, name, desc, ptype, **extra), breadcrumb(path, [(name, path)])])

    for path, (name, reg, dept) in REGION_PAGES.items():
        desc = (f"Maalausmestari – talon maalaus, tiilikaton pinnoitus ja peltikaton maalaus alueella {reg}. "
                "Kiinteähintainen urakkasopimus ja takuutyö.")
        graph = [
            webpage(path, name, desc, about={"@id": dept or ORG_ID}, mainEntity={"@id": f"{url(path)}#service"}),
            breadcrumb(path, [(name, path)]),
            service(path, name, "Ulkomaalaus", desc, region(reg)),
        ]
        add("2-alueet", path, graph)

    for slug, (city, loc, reg) in CITIES.items():
        path = f"talonmaalaus/{slug}"
        name = f"Talon maalaus {loc}"
        desc = (f"Talon maalaus {loc} kotimaiselta perheyritykseltä. Laadukkaat Tikkurilan ja Teknoksen maalit, "
                "kiinteähintainen urakkasopimus ja 3 vuoden takuu.")
        area = {"@type": "City", "name": city, "containedInPlace": region(reg)}
        add("3-paikkakunnat", path, [
            webpage(path, name, desc, about={"@id": f"{url(path)}#service"}, mainEntity={"@id": f"{url(path)}#service"}),
            breadcrumb(path, [("Talon maalaus", "talonmaalaus"), (city, path)]),
            service(path, name, "Talon maalaus", desc, area, 3),
        ])

    # vahingossa julkaistu kopiosivu – schema tiilikaton pinnoitukselle Uudellamaalla
    path = "talonmaalaus/copy-of-tiilikaton-pinnoitus-uusimaa"
    desc = "Tiilikaton pinnoitus Uudellamaalla Ormax-tiilikattomaalilla. Työlle 5 vuoden takuu."
    add("3-paikkakunnat", path, [
        webpage(path, "Tiilikaton pinnoitus Uusimaa", desc, mainEntity={"@id": f"{url(path)}#service"}),
        breadcrumb(path, [("Talon maalaus", "talonmaalaus"), ("Tiilikaton pinnoitus Uusimaa", path)]),
        service(path, "Tiilikaton pinnoitus Uusimaa", "Tiilikaton pinnoitus", desc, region("Uusimaa"), 5),
    ])

    for path, (title, modified) in ARTICLES.items():
        add("4-artikkelit", path, [
            webpage(path, title, title, mainEntity={"@id": f"{url(path)}#article"}),
            breadcrumb(path, [("Uutiset", "uutiset"), (title, path)]),
            {
                "@type": "BlogPosting",
                "@id": f"{url(path)}#article",
                "headline": title[:110],
                "url": url(path),
                "mainEntityOfPage": {"@id": f"{url(path)}#webpage"},
                "dateModified": modified,
                "inLanguage": "fi-FI",
                "author": {"@id": ORG_ID},
                "publisher": {"@id": ORG_ID},
                "isPartOf": {"@id": f"{BASE}/uutiset#webpage"},
            },
        ])
    return pages


def main():
    if OUT.exists():
        shutil.rmtree(OUT)
    pages = build()
    for rel, (_, content) in pages.items():
        f = OUT / rel
        f.parent.mkdir(parents=True, exist_ok=True)
        f.write_text(content, encoding="utf-8")
    # yksi tiedosto kaikesta, tarkistusta varten
    index = ["# Sivu -> tiedosto", ""]
    index += [f"{url(p) if p != '*' else 'KAIKKI SIVUT'}  ->  {rel}" for rel, (p, _) in sorted(pages.items())]
    (OUT / "HAKEMISTO.txt").write_text("\n".join(index) + "\n", encoding="utf-8")
    print(f"{len(pages)} tiedostoa -> {OUT}")


if __name__ == "__main__":
    main()
