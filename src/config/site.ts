/**
 * Sivuston keskitetyt asetukset.
 * Muokkaa tätä tiedostoa – kaikki sivut, metatiedot, JSON-LD ja llms.txt
 * lukevat tiedot täältä.
 */

export const site = {
  url: "https://www.samielmeranta.fi",
  lang: "fi",
  locale: "fi_FI",
  name: "Sami Elmeranta",
  tagline: "Tolkun poliitikko",
  description:
    "Sami Elmeranta – Liike Nytin kaarinalainen kaupunginvaltuutettu, kaupunginhallituksen ja Varsinais-Suomen maakuntavaltuuston jäsen. Myynnin johtaja, yrittäjä ja kolmen lapsen isä Piikkiöstä.",
  party: {
    name: "Liike Nyt",
    url: "https://liikenyt.fi",
  },
  location: {
    locality: "Kaarina",
    district: "Piikkiö",
    region: "Varsinais-Suomi",
    country: "FI",
  },
  jobTitle: "Kaupunginvaltuutettu, Kaarina",
  phone: "+358503460444",
  phoneDisplay: "050 346 0444",
  /** Julkinen yhteysosoite. Vaihda omaksesi – älä käytä henkilökohtaista osoitetta. */
  email: "sami@samielmeranta.fi",
  /**
   * Yhteydenottolomakkeen vastaanottaja (esim. Formspree, Basin, Getform).
   * Jätä tyhjäksi, niin lomake korvautuu sähköposti-/puhelinlinkeillä.
   * Muista lisätä domain myös public/_headers -tiedoston form-action -sääntöön.
   */
  formEndpoint: "",
  newsletterUrl: "http://eepurl.com/jrWeaI",
  whatsapp: "https://wa.me/358503460444?text=Hei%20Sami!",
  /** Kuvat kansiossa public/images – korvaa omillasi samalla nimellä. */
  images: {
    portrait: "/images/sami-portrait.webp",
    portraitAlt: "/images/sami-arki.webp",
    og: "/og.png",
  },
  social: [
    { name: "Facebook", icon: "simple-icons:facebook", url: "https://www.facebook.com/Elmeranta/" },
    { name: "Instagram", icon: "simple-icons:instagram", url: "https://www.instagram.com/s.elmeranta/" },
    { name: "TikTok", icon: "simple-icons:tiktok", url: "https://www.tiktok.com/@samielmeranta" },
    { name: "LinkedIn", icon: "simple-icons:linkedin", url: "https://www.linkedin.com/in/sami-kristian-elmeranta-05aa27170/" },
  ],
  writing: [
    { name: "Uusi Suomi -blogit", url: "https://puheenvuoro.uusisuomi.fi/author/sami-elmeranta/" },
    { name: "Turun Sanomat", url: "https://www.ts.fi/lukijoilta/6432868" },
    { name: "Helsingin Sanomat", url: "https://www.hs.fi/mielipide/art-2000011564975.html" },
  ],
  nav: [
    { title: "Politiikka", path: "/politiikka/" },
    { title: "Työelämä", path: "/tyoelama/" },
    { title: "Arki", path: "/arki/" },
    { title: "Blogi", path: "/blogi/" },
  ],
} as const;

export type Site = typeof site;
