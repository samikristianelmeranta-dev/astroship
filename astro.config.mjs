import { defineConfig } from "astro/config";
import tailwindcss from "@tailwindcss/vite";
import mdx from "@astrojs/mdx";
import sitemap from "@astrojs/sitemap";
import icon from "astro-icon";

// https://astro.build/config
export default defineConfig({
  site: "https://www.tilihub.fi",
  // WordPressin osoitteet päättyvät kauttaviivaan (/sivu/) – pidetään samana.
  trailingSlash: "always",
  build: {
    format: "directory",
  },
  integrations: [mdx(), sitemap(), icon()],
  vite: {
    plugins: [tailwindcss()],
  },
});
