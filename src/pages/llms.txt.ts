import type { APIContext } from "astro";
import { getCollection } from "astro:content";
import { site } from "@/config/site";
import { values, roles, faq } from "@/data/content";

/**
 * llms.txt – tiivis, koneluettava kuvaus sivustosta tekoälyhakuja ja
 * -avustajia varten (https://llmstxt.org).
 */
export async function GET(_context: APIContext) {
  const posts = (await getCollection("blog", ({ data }) => !data.draft)).sort(
    (a, b) => b.data.publishDate.valueOf() - a.data.publishDate.valueOf(),
  );
  const u = (p: string) => new URL(p, site.url).toString();

  const body = `# ${site.name}

> ${site.description}

- Puolue: ${site.party.name}
- Kotipaikka: ${site.location.district}, ${site.location.locality} (${site.location.region})
- Nykyiset luottamustoimet: ${roles.filter((r) => r.active).map((r) => r.title).join("; ")}
- Yhteys: puhelin ${site.phoneDisplay}, sähköposti ${site.email}

## Sivut

- [Etusivu](${u("/")}): Esittely, arvot, muutostavoitteet ja suosittelut
- [Politiikka](${u("/politiikka/")}): Luottamustoimet ja poliittinen tausta
- [Työelämä & yrittäjyys](${u("/tyoelama/")}): Työura ja yrittäjyys
- [Arki & vapaa-aika](${u("/arki/")}): Perhe, urheilu ja vapaaehtoistyö
- [Ota yhteyttä](${u("/yhteys/")}): Yhteystiedot

## Keskeiset teemat

${values.map((v) => `- ${v.title}: ${v.text}`).join("\n")}

## Usein kysyttyä

${faq.map((f) => `### ${f.q}\n${f.a}`).join("\n\n")}

## Blogikirjoitukset

${posts.map((p) => `- [${p.data.title}](${u(`/blogi/${p.id}/`)}): ${p.data.snippet}`).join("\n")}

## Optional

- [RSS-syöte](${u("/rss.xml")})
- [Sivukartta](${u("/sitemap-index.xml")})
${site.social.map((s) => `- [${s.name}](${s.url})`).join("\n")}
`;

  return new Response(body, { headers: { "Content-Type": "text/plain; charset=utf-8" } });
}
