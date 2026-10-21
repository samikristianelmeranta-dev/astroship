import { defineConfig } from "astro/config";
import tailwindcss from "@tailwindcss/vite";
import mdx from "@astrojs/mdx";
import sitemap from "@astrojs/sitemap";
import icon from "astro-icon";

// Vanhat WordPress-osoitteet → uudet (säilyttää hakukonenäkyvyyden)
const legacyPosts = [
  "kaarinantulevaisuus",
  "vastuullinen-julkinen-talous",
  "mita-pahaa-parakkikouluissa-oikeastaan-on",
  "oodi-kaikille-valmentajille-ja-seura-aktiiveille",
  "elakejarjestelma-ja-tyoelamassa-olevien-epavarmuus",
  "kolme-suurinta-virhetta-joita-kunta-voi-tehda-elinvoiman-kehittamisessa-ja-miten-kaarina-voi-valttaa-ne",
  "miksi-kunnan-pitaa-investoida-viisaasti-ei-eniten",
  "kaarinan-kaupunginhallitus-mita-se-tekee-ja-miksi-sen-tyo-vaikuttaa-jokaisen-kaarinalaisen-arkeen",
  "mita-kunnanvaltuusto-tekee",
  "positiivinen-luottorekisteri",
];

// https://astro.build/config
export default defineConfig({
  site: "https://www.samielmeranta.fi",
  trailingSlash: "always",
  integrations: [
    mdx(),
    sitemap({ filter: (page) => !page.includes("/404") }),
    icon({ include: { lucide: ["*"], "simple-icons": ["*"] } }),
  ],
  redirects: {
    "/politiikkaa": "/politiikka/",
    "/tyoelamayrittajyys": "/tyoelama/",
    "/arkivapaa-aika": "/arki/",
    "/mielipiteetajatukset": "/blogi/",
    "/contact": "/yhteys/",
    "/privacy-policy": "/tietosuoja/",
    "/kokoomus-kaarinassa": "/politiikka/",
    ...Object.fromEntries(legacyPosts.map((slug) => [`/${slug}`, `/blogi/${slug}/`])),
  },
  build: {
    // Ei inline-tyylejä → tiukka Content-Security-Policy toimii
    inlineStylesheets: "never",
  },
  vite: {
    plugins: [tailwindcss()],
    build: { assetsInlineLimit: 0 },
  },
});
