import { defineCollection, z } from "astro:content";
import { glob } from "astro/loaders";

// Artikkelit tuodaan WordPressistä: scripts/wp-import.mjs
const posts = defineCollection({
  loader: glob({ pattern: "**/*.md", base: "./src/content/posts" }),
  schema: z.object({
    title: z.string(),
    slug: z.string(),
    path: z.string(),
    date: z.coerce.date(),
    updated: z.coerce.date().optional(),
    seoTitle: z.string().optional(),
    description: z.string().default(""),
    categories: z.array(z.string()).default([]),
    tags: z.array(z.string()).default([]),
    image: z.string().optional(),
    draft: z.boolean().default(false),
    wpId: z.number().optional(),
  }),
});

const pages = defineCollection({
  loader: glob({ pattern: "**/*.md", base: "./src/content/pages" }),
  schema: z.object({
    title: z.string(),
    heading: z.string().optional(),
    path: z.string(),
    updated: z.coerce.date().optional(),
    seoTitle: z.string().optional(),
    description: z.string().default(""),
    wpId: z.number().optional(),
  }),
});

export const collections = { posts, pages };
