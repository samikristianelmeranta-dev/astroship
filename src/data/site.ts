// Yrityksen perustiedot ja sivuston rakenne — muokkaa tästä yhdestä paikasta.

export const business = {
  name: "Nordic Ikkunat & Ovet Oy",
  brand: "TiivisTupa",
  slogan: "Tiiviimmän kodin puolesta",
  description:
    "TiivisTupa myy, asentaa, tiivistää ja huoltaa ikkunoita ja ulko-ovia Turussa ja koko Varsinais-Suomessa.",
  email: "info@tiivistupa.fi",
  // Lisää puhelinnumero kansainvälisessä muodossa, esim. "+358401234567".
  telephone: "",
  addressLocality: "Turku",
  areaServed: ["Turku", "Varsinais-Suomi"],
  openingHours: "Arkisin klo 8–17",
  openingHoursSchema: "Mo-Fr 08:00-17:00",
  // Lomakkeen vastaanottopalvelun osoite (esim. Formspree / oma API).
  // Tyhjänä lomake avaa sähköpostiohjelman valmiiksi täytetyllä viestillä.
  formEndpoint: "",
};

export const nav = [
  { href: "/ikkunat/", label: "Ikkunat" },
  { href: "/ovet/", label: "Ovet" },
  { href: "/ikkunaremontti/", label: "Ikkunaremontti" },
  { href: "/tiivistys/", label: "Tiivistys" },
  { href: "/huolto/", label: "Huolto" },
  { href: "/taloyhtiot/", label: "Taloyhtiöt" },
  { href: "/alueet/", label: "Alueet" },
  { href: "/yhteystiedot/", label: "Yhteystiedot" },
];

export const footerServices = [
  { href: "/ikkunat/", label: "Ikkunat" },
  { href: "/ovet/", label: "Ulko-ovet" },
  { href: "/ikkunaremontti/", label: "Ikkuna- ja oviremontti" },
  { href: "/tiivistys/", label: "Ikkunoiden ja ovien tiivistys" },
  { href: "/huolto/", label: "Ikkuna- ja ovihuolto" },
  { href: "/taloyhtiot/", label: "Taloyhtiöt ja liikekiinteistöt" },
  { href: "/usein-kysytyt-kysymykset/", label: "Usein kysytyt kysymykset" },
  { href: "/sivukartta/", label: "Sivukartta" },
];

export const cities: Record<string, { name: string; inessive: string; note: string }> = {
  turku: { name: "Turku", inessive: "Turussa", note: "Keskustan kerrostaloista Hirvensalon ja Paattisten omakotitaloihin." },
  kaarina: { name: "Kaarina", inessive: "Kaarinassa", note: "Piikkiöstä Littoisiin ja Kuusistoon — lyhyt matka Turusta." },
  raisio: { name: "Raisio", inessive: "Raisiossa", note: "Rivitalot, omakotitalot ja taloyhtiöt koko Raision alueella." },
  naantali: { name: "Naantali", inessive: "Naantalissa", note: "Myös meren läheisyyden tuulille ja kosteudelle alttiit kohteet." },
  lieto: { name: "Lieto", inessive: "Liedossa", note: "Liedon asemalta Tarvasjoelle, uudet ja vanhat pientalot." },
  salo: { name: "Salo", inessive: "Salossa", note: "Salon keskusta ja ympäröivät kylät Halikosta Perttelin suuntaan." },
  paimio: { name: "Paimio", inessive: "Paimiossa", note: "Pientalot ja maatilojen asuinrakennukset Paimion alueella." },
  parainen: { name: "Parainen", inessive: "Paraisilla", note: "Saariston talot ja kesäasunnot, joissa tiiveys korostuu." },
  masku: { name: "Masku", inessive: "Maskussa", note: "Uudet asuinalueet ja vanhemmat pientalot Maskussa ja Askaisissa." },
  "uusikaupunki": { name: "Uusikaupunki", inessive: "Uudessakaupungissa", note: "Rannikon puutalot ja omakotitalot koko kunnan alueella." },
  laitila: { name: "Laitila", inessive: "Laitilassa", note: "Pientalot, rintamamiestalot ja maaseudun asuinrakennukset." },
  "aura-poytya": { name: "Aura ja Pöytyä", inessive: "Aurassa ja Pöytyällä", note: "Auran, Pöytyän ja lähikuntien kodit." },
};
