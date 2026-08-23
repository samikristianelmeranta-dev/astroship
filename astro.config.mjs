import { defineConfig } from "astro/config";
import tailwindcss from "@tailwindcss/vite";
import mdx from "@astrojs/mdx";
import sitemap from "@astrojs/sitemap";
import icon from "astro-icon";
import react from "@astrojs/react";
import keystatic from "@keystatic/astro";

// Keystatic injects dynamic (non-prerendered) admin routes. This site is
// deployed as a fully static build, so the CMS is only wired in during
// local development (`pnpm dev` -> /keystatic) and left out of `pnpm build`.
const isDev = process.env.NODE_ENV !== "production";

// https://astro.build/config
export default defineConfig({
  site: "https://kasvukumppani.fi",
  integrations: [
    mdx(),
    sitemap(),
    icon(),
    ...(isDev ? [react(), keystatic()] : []),
  ],
  vite: {
    plugins: [tailwindcss()],
    optimizeDeps: {
      exclude: ["@keystatic/astro/api"],
    },
  },
});
