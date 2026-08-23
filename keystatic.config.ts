import { config, fields, collection, singleton } from "@keystatic/core";

export default config({
  storage: {
    kind: "local",
  },
  collections: {
    blog: collection({
      label: "Blogi",
      slugField: "title",
      path: "src/content/blog/*",
      format: { contentField: "content" },
      schema: {
        title: fields.slug({
          name: { label: "Otsikko" },
          slug: { label: "Osoite (slug)" },
        }),
        draft: fields.checkbox({
          label: "Luonnos (ei näy julkisesti)",
          defaultValue: false,
        }),
        snippet: fields.text({
          label: "Lyhyt kuvaus (näkyy blogilistauksessa)",
          multiline: true,
        }),
        image: fields.object(
          {
            src: fields.text({
              label: "Kansikuvan polku",
              description:
                "Esim. /blog/tiedosto.jpg. Lisää itse kuvatiedosto public/blog-kansioon ennen kuin viittaat siihen tässä.",
            }),
            alt: fields.text({ label: "Kuvan alt-teksti" }),
          },
          { label: "Kansikuva" }
        ),
        publishDate: fields.date({
          label: "Julkaisupäivä",
          defaultValue: { kind: "today" },
        }),
        author: fields.text({
          label: "Kirjoittaja",
          defaultValue: "Sami Elmeranta",
        }),
        category: fields.text({ label: "Kategoria" }),
        tags: fields.array(fields.text({ label: "Tagi" }), {
          label: "Tagit",
          itemLabel: (props) => props.value || "Tagi",
        }),
        content: fields.markdoc({
          label: "Sisältö",
          extension: "md",
        }),
      },
    }),
  },
  singletons: {
    yhteystiedot: singleton({
      label: "Yhteystiedot",
      path: "src/data/yhteystiedot",
      format: "json",
      schema: {
        email: fields.text({ label: "Sähköposti" }),
        sijainti: fields.text({ label: "Sijainti" }),
        intro: fields.text({
          label: "Esittelyteksti",
          multiline: true,
        }),
      },
    }),
    hinnoittelu: singleton({
      label: "Hinnoittelu",
      path: "src/data/hinnoittelu",
      format: "json",
      schema: {
        paketit: fields.array(
          fields.object(
            {
              nimi: fields.text({ label: "Paketin nimi" }),
              hinta: fields.text({ label: "Hinta" }),
              suosituin: fields.checkbox({
                label: "Suosituin paketti?",
                defaultValue: false,
              }),
              ominaisuudet: fields.array(
                fields.text({ label: "Ominaisuus" }),
                {
                  label: "Ominaisuudet",
                  itemLabel: (props) => props.value || "Ominaisuus",
                }
              ),
              nappiTeksti: fields.text({
                label: "Napin teksti",
                defaultValue: "Kysy tarjous",
              }),
            },
            { label: "Paketti" }
          ),
          {
            label: "Paketit",
            itemLabel: (props) => props.fields.nimi.value || "Paketti",
          }
        ),
      },
    }),
  },
});
