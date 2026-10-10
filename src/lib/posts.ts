import { getCollection, type CollectionEntry } from "astro:content";
import taxonomies from "@/data/taxonomies.json";
import { PER_PAGE } from "@/data/site";

export type Post = CollectionEntry<"posts">;

export async function getPosts(): Promise<Post[]> {
  const posts = await getCollection("posts", ({ data }) => !data.draft);
  return posts.sort((a, b) => b.data.date.valueOf() - a.data.date.valueOf());
}

export const categories = taxonomies.categories;
export const tags = taxonomies.tags;

export const categoryName = (slug: string) => categories.find((c) => c.slug === slug)?.name ?? slug;
export const tagName = (slug: string) => tags.find((t) => t.slug === slug)?.name ?? slug;

/** Jakaa listan WordPress-tyylisiksi sivuiksi: /polku/, /polku/page/2/ … */
export function pages<T>(list: T[], base: string) {
  const total = Math.max(1, Math.ceil(list.length / PER_PAGE));
  return Array.from({ length: total }, (_, i) => ({
    n: i + 1,
    items: list.slice(i * PER_PAGE, (i + 1) * PER_PAGE),
    total,
    url: (n: number) => (n === 1 ? base : `${base}page/${n}/`),
  }));
}

const fmt = new Intl.DateTimeFormat("fi-FI", { day: "numeric", month: "numeric", year: "numeric" });
export const formatDate = (d: Date) => fmt.format(d);

/** Arvioitu lukuaika minuutteina. */
export const readingTime = (body = "") => Math.max(1, Math.round(body.split(/\s+/).length / 180));
