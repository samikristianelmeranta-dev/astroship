import rss from "@astrojs/rss";
import type { APIContext } from "astro";
import { getCollection } from "astro:content";
import { site } from "@/config/site";

export async function GET(context: APIContext) {
  const posts = (await getCollection("blog", ({ data }) => !data.draft)).sort(
    (a, b) => b.data.publishDate.valueOf() - a.data.publishDate.valueOf(),
  );
  return rss({
    title: `${site.name} – blogi`,
    description: site.description,
    site: context.site ?? site.url,
    customData: `<language>${site.lang}</language>`,
    items: posts.map((p) => ({
      title: p.data.title,
      description: p.data.snippet,
      pubDate: p.data.publishDate,
      link: `/blogi/${p.id}/`,
      categories: [p.data.category, ...p.data.tags],
    })),
  });
}
