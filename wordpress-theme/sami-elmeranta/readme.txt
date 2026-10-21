=== Sami Elmeranta – Liike Nyt ===

Klassinen WordPress-teema (toimii Classic Editorin kanssa).

== Asennus ==

1. Ulkoasu → Teemat → Lisää uusi → Lataa teema → valitse zip → Asenna → Ota käyttöön.
2. Lisäosat → Lisää uusi → asenna ja ota käyttöön "Classic Editor".
3. Asetukset → Lukeminen: "Etusivu näyttää: staattisen sivun",
   Etusivu = "Etusivu", Artikkelisivu = "Blogi".
4. Asetukset → Kestolinkit: valitse "Artikkelin nimi" ja tallenna
   (tallennus myös aktivoi osoitteen /llms.txt).
5. Ulkoasu → Mukauta → "Sami Elmeranta – asetukset":
   julkinen sähköposti, lomakkeen vastaanottaja, somet, puolue, kuvat, etusivun tekstit.

== Sivut ==

Vanhat sivut saavat uuden ulkoasun automaattisesti osoitteen (slugin) perusteella:

* politiikkaa          → Politiikka
* tyoelamayrittajyys   → Työelämä & yrittäjyys (julkaise sivu, se on luonnoksena!)
* arkivapaa-aika       → Arki & vapaa-aika
* contact              → Ota yhteyttä (sisältää oman, turvallisen lomakkeen)

Uudelle sivulle voit valita saman ulkoasun kohdasta Sivun attribuutit → Sivupohja.

Nämä sivupohjat EIVÄT näytä sivun vanhaa editorisisältöä (vanhat koodit ja
popupit jäävät piiloon). Jos haluat lisätekstiä sivulle, kirjoita se editoriin ja
lisää mukautettu kenttä  nayta_sisalto = 1.

Arki-sivun galleria: lisää sivulle galleria (Lisää media → Luo galleria),
kuvatekstit näkyvät kuvien alla.

Muut sivut (esim. tietosuojaseloste) ja blogikirjoitukset näytetään editorin
sisällöllä normaalisti.

Poista tai siirrä luonnoksiksi Kokoomus-aiheiset sivut, esim. "Kokoomus Kaarinassa".

== Tekstien muokkaus ==

* Etusivun otsikot, puolue, yhteystiedot, somet, kuvat: Ulkoasu → Mukauta.
* Arvot, muutostavoitteet, luottamustoimet, työura, suosittelut ja UKK:
  tiedosto inc/content.php.
* Valikot: Ulkoasu → Valikot (sijainnit "Päävalikko" ja "Alavalikko").
  Ilman valikkoa näytetään oletuslinkit.
* Logo: Ulkoasu → Mukauta → Sivuston tiedot → Logo (muuten "SE"-merkki).

== Brändivärit ==

Liike Nytin graafisen ohjeistuksen värit:
  Fuksia #B3125D (pääväri, painikkeet, linkit)
  Vaalea fuksia #DE3478 (korostukset, tummat taustat)
  Sininen #5D538C · Liila #744668 (liukuvärit, kuvakehykset)

Värit ja fontit määritellään tiedostossa src/main.css (@theme). Muutoksen jälkeen
käännä CSS:
  npx @tailwindcss/cli -i src/main.css -o assets/css/main.css --minify
(Tai muokkaa suoraan assets/css/main.css:stä arvot --color-brand jne.)

== Tietoturva (sisäänrakennettu) ==

* WordPress-version piilotus, XML-RPC pois, pingbackit pois
* Käyttäjätunnusten urkinnan esto (?author=, REST /users, sivukartta)
* Yleinen kirjautumisvirheviesti
* Tietoturvaotsakkeet (nosniff, X-Frame-Options, Referrer-Policy,
  Permissions-Policy, HSTS https-yhteydellä, frame-ancestors)
* SVG-latausten esto
* Yhteydenottolomake: nonce, roskapostiansa, aikaraja, IP-kohtainen rajoitus,
  kenttien validointi, otsakeinjektion esto. Kaikki tulosteet escapoidaan.
* Teema ei lataa mitään ulkoisilta palvelimilta (fontit ja ikonit mukana)

Suositellaan lisäksi:
* wp-config.php:  define( 'DISALLOW_FILE_EDIT', true );
* Kaksivaiheinen tunnistautuminen (esim. "Two Factor" -lisäosa)
* Automaattiset päivitykset ja varmuuskopiot
* Vain tarpeelliset lisäosat

== Tekoäly- ja hakukoneoptimointi ==

* JSON-LD: Person (puolue, luottamustoimet, somet), WebSite, ProfilePage,
  FAQPage, BlogPosting, BreadcrumbList
* /llms.txt generoituu automaattisesti sivuista ja artikkeleista
* robots.txt sallii tekoälyhaut (ChatGPT, Claude, Perplexity …) ja kertoo sivukartan
* Metakuvaukset, Open Graph ja Twitter-kortit (jos Yoast/Rank Math on käytössä,
  ne hoitavat metatagit ja teema tuottaa vain Person/FAQ-rakenteen)
* Artikkelin ote (Excerpt) = hakukoneiden ja somen kuvaus – kannattaa täyttää
