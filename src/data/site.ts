export const site = {
  name: "TiliHub Oy",
  shortName: "TiliHub",
  url: "https://www.tilihub.fi",
  tagline: "Tilitoimisto TiliHub Oy on täyden palvelun sähköinen taloushallinnon kumppani",
  phone: "+358 44 080 3344",
  phoneHref: "tel:+358440803344",
  email: "info@tilihub.fi",
  whatsapp: "https://wa.me/358440803344?text=Hei%2C%20tarvitsisin%20lis%C3%A4tietoja.",
  area: "Kaarina / Turku & koko Suomi",
  locality: "Kaarina",
  region: "Varsinais-Suomi",
  founded: "2025",
  facebook: "https://www.facebook.com/profile.php?id=61579294262007",
  instagram: "https://www.instagram.com/tilihub_oy",
  privacyPdf: "https://www.tilihub.fi/wp-content/uploads/2025/08/tietosuojaseloste_tilihub.pdf",
  software: ["Fennoa", "Procountor", "Netvisor", "NocFo"],
  areas: ["Turku", "Kaarina", "Raisio", "Naantali", "Lieto", "Varsinais-Suomi", "Koko Suomi digitaalisesti"],
};

// Sama rakenne kuin WordPressin päävalikossa.
export const nav = [
  { title: "Kirjanpito", href: "/kirjanpitopalvelut/" },
  { title: "Palkanlaskenta", href: "/palkanlaskenta/" },
  { title: "Tilinpäätös", href: "/tilinpaatos/" },
  { title: "Hinnasto", href: "/kirjanpidon-hinnat/" },
  { title: "Artikkelit", href: "/blogi/" },
  { title: "Tietoa meistä", href: "/tietoa-meista/" },
];

export const categoryNames: Record<string, string> = {
  kirjanpito: "Kirjanpito",
  taloushallinto: "Taloushallinto",
  tilitoimisto: "Tilitoimisto",
  "accounting-finland": "Accounting Finland",
  uncategorized: "Muut",
};

// WordPressin oletus: 10 artikkelia sivulla (/page/2/ jne.)
export const PER_PAGE = 10;
