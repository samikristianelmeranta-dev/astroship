# Maalausmestari – Google Ads -suunnitelma (talon maalaus)

Tavoite: yhteydenotot (tarjouspyynnöt, soitot) talon maalauksesta. Kaksi erillistä hakukampanjaa:
**Uusimaa** ja **Varsinais-Suomi**. CSV:t Google Ads Editoriin: `editor/` (luo uudelleen: `python3 generate_ads.py`,
skripti tarkistaa merkkirajat).

## Rakenne (sama molemmissa kampanjoissa)

Kaikki mainokset ja avainsanat ohjaavat sivulle **https://www.maalausmestari.fi/talonmaalaus**.
Sivulinkit (laajennukset) ohjaavat omille sivuilleen.

| Mainosryhmä | Avainsanat | Laskeutumissivu |
|---|---|---|
| Talon maalaus | talon maalaus, talon ulkomaalaus, omakotitalon maalaus, maalausliike, puutalon/rapatun/hirsitalon maalaus, julkisivumaalaus… (lauseke + tarkka) | `/talonmaalaus` |
| Talon maalaus – paikkakunnat | "talon maalaus espoo", "maalausliike turku"… (asiakkaan 11 hakusanaa × 8 kuntaa Uusimaa / 9 kuntaa Varsinais-Suomi, ks. `hakusanat.md`) | `/talonmaalaus` |
| Talon maalaus – hinta | talon maalaus hinta, mitä maksaa talon maalaus… | `/talonmaalaus` |
| Rivitalot ja taloyhtiöt (pienempi paino) | rivitalon maalaus, taloyhtiön ulkomaalaus… | `/talonmaalaus` |

Jokaisessa ryhmässä yksi responsiivinen hakumainos: 15 otsikkoa ja 4 kuvausta. Sijaintiotsikko on kiinnitetty
paikalle 1, muut otsikot vaihtelevat vapaasti. Kampanjatason negatiiviset avainsanat (40 kpl) karsivat
itse tekijät, työnhakijat, sisämaalaukset ja maalikaupat.

## Kampanja-asetukset

- **Verkosto:** vain Google-haku (hakukumppanit ja Display pois päältä).
- **Sijainti:** Uusimaa (**Helsinki poissuljettu**) / Varsinais-Suomi (`editor/sijainnit.csv`). Uusimaan kampanjassa
  myös negatiiviset sanat *helsinki, helsingissä, helsingin*, eikä mainosteksteissä mainita Helsinkiä. Kohdistusvalinta *"Ihmiset, jotka ovat sijainnissa tai käyvät siellä
  säännöllisesti"*. **Ei** "kiinnostuneet sijainnista".
- **Kieli:** suomi + ruotsi + englanti (selaimen kieli on usein englanti, ja suomenkieliset avainsanat rajaavat jo).
- **Budjetti:** aloita esim. 20–40 €/pv per kampanja, Uudellemaalle isompi osuus. Klikkihinnat kannattaa
  tarkistaa Keyword Plannerista ennen käynnistystä.
- **Hintastrategia:** ensin *Maksimoi klikkaukset* klikkihintakatolla 2–3 viikkoa, sitten *Maksimoi konversiot*,
  kun konversioita on kertynyt (n. 15–30/kk). Myöhemmin tavoite-CPA.
- **Ajoitus:** maalaussesonki on keväästä syksyyn, mutta ensi kesän urakoiden tarjouspyynnöt kerätään
  talvella ja keväällä. Siksi mainoksissa on "Varaa ensi kesän maalaus nyt". Nosta budjettia helmi–toukokuussa.

## Konversioseuranta (pakollinen ennen käynnistystä)

1. Lomakkeen lähetys: kiitossivun `kiitos-otayhteyttä` lataus = **ensisijainen konversio**.
2. Klikkaus puhelinnumeroon (`tel:`). Korjaa ensin linkin muoto: `tel:+358503667703` ilman välilyöntejä.
3. Puhelut mainoksesta (puhelulaajennus), kesto vähintään 60 s.
4. Toissijaiset: WhatsApp-painike, sähköpostilinkki, Leadoo-hintalaskurin liidi.
5. Ota käyttöön *parannetut konversiot liideille* (enhanced conversions).

## Laajennukset (`editor/laajennukset.csv`)

- **Sivulinkit:** Pyydä tarjous, Hintalaskuri, Referenssit ja galleria, Mitä talon maalaus maksaa,
  Kotitalousvähennys, Tiilikaton pinnoitus.
- **Lisätiedot:** Maksuton arviokäynti, Kiinteä urakkahinta, 3 vuoden takuu, Kotimainen perheyritys,
  Avainlippu-yritys, Yli 1000 kohdetta, Tikkurila & Teknos, Vastaus 1 arkipäivässä.
- **Rakenteinen katkelma:** Palvelut: Talon maalaus, Julkisivumaalaus, Tiilikaton pinnoitus, Peltikaton maalaus,
  Rivitalot ja taloyhtiöt.
- **Puhelu** 050 366 7703, aikataulutettuna aukioloaikoihin.
- **Kuvat:** sivuston talo- ja kattokuvat (neliö ja vaaka).
- **Sijainti:** linkitä Google-yritysprofiili(t), jos Turulle ja Helsingille on omat profiilit.

## Laskeutumissivut – korjaa ennen mainontaa

- Varmista, että `/talonmaalaus`-sivulla on tarjouslomake ja puhelinnumero heti näkyvissä mobiilissa.
- Lomakkeen englanninkielinen virheviesti ja `locale="ENGLISH"` suomeksi.
- Sivun nopeus: herovideo ja kolmannen osapuolen skriptit hidastavat sivua, mikä nostaa klikkihintaa
  laatupisteiden kautta.

## Avoimet kysymykset

1. Onko Turulla ja Helsingillä eri puhelinnumerot tai eri yhteyshenkilöt? Puhelulaajennus kannattaa
   tehdä kampanjakohtaisesti.
2. Kuukausibudjetti yhteensä?
3. Onko Google-yritysprofiileja ja GA4/Tag Manager jo käytössä?
4. Halutaanko erillinen pieni brändikampanja ("maalausmestari")?
