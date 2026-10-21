import { site } from "@/config/site";

/** Yhteiset schema.org-tunnisteet, joihin muut rakenteet viittaavat. */
export const ids = {
  person: `${site.url}/#person`,
  website: `${site.url}/#website`,
};

export const absolute = (path: string) => new URL(path, site.url).toString();

export function personSchema() {
  return {
    "@type": "Person",
    "@id": ids.person,
    name: site.name,
    givenName: "Sami",
    familyName: "Elmeranta",
    description: site.description,
    url: site.url,
    image: absolute(site.images.og),
    jobTitle: site.jobTitle,
    telephone: site.phone,
    email: `mailto:${site.email}`,
    affiliation: {
      "@type": "PoliticalParty",
      name: site.party.name,
      url: site.party.url,
    },
    memberOf: [
      { "@type": "GovernmentOrganization", name: "Kaarinan kaupunginvaltuusto" },
      { "@type": "GovernmentOrganization", name: "Kaarinan kaupunginhallitus" },
      { "@type": "GovernmentOrganization", name: "Varsinais-Suomen maakuntavaltuusto" },
    ],
    homeLocation: {
      "@type": "Place",
      address: {
        "@type": "PostalAddress",
        addressLocality: site.location.locality,
        addressRegion: site.location.region,
        addressCountry: site.location.country,
      },
    },
    knowsAbout: [
      "Kuntapolitiikka",
      "Julkinen talous",
      "Yrittäjyys",
      "Myynti ja markkinointi",
      "Kaarina",
      "Varsinais-Suomi",
    ],
    sameAs: [...site.social.map((s) => s.url), site.writing[0].url],
  };
}

export function websiteSchema() {
  return {
    "@type": "WebSite",
    "@id": ids.website,
    url: site.url,
    name: site.name,
    description: site.description,
    inLanguage: site.lang,
    publisher: { "@id": ids.person },
  };
}

export function breadcrumbSchema(items: { name: string; path: string }[]) {
  return {
    "@type": "BreadcrumbList",
    itemListElement: [{ name: "Etusivu", path: "/" }, ...items].map((item, i) => ({
      "@type": "ListItem",
      position: i + 1,
      name: item.name,
      item: absolute(item.path),
    })),
  };
}

export function faqSchema(faq: { q: string; a: string }[]) {
  return {
    "@type": "FAQPage",
    mainEntity: faq.map(({ q, a }) => ({
      "@type": "Question",
      name: q,
      acceptedAnswer: { "@type": "Answer", text: a },
    })),
  };
}
