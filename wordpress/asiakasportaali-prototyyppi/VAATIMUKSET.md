# Asiakasportaali "Oma kohde" – sovitut asiat

Toteutus: WordPress-lisäosa `nordic-asiakasportaali` ikkunakauppias.fi:hin, puhelimeen asennettava verkkosovellus (PWA),
kirjautuminen sähköpostin kertakäyttölinkillä. Prototyyppi: `oma-kohde-artifact.html`.

## Vaiheet
1. Tarjouspyyntö → kohde luodaan automaattisesti, asiakas saa linkin.
2. Mittauskäynti → asentaja tekee tarjouksen puhelimella TAI tarjous tuodaan Skaalan tarjoustyökalusta (selvitettävä: API/vienti, muuten PDF:n tuonti).
3. Asiakas saa tarjouksen linkkiin: hyväksy / kysy tarkennusta / en ole kiinnostunut.
   - Hyväksynnässä valinta "Pyydän, että ikkunat tilataan heti" (14 vrk peruutusoikeus, erillinen pyyntö).
   - Muistutukset: 3 pv (sähköposti), 7 pv (sähköposti/tekstiviesti), soittotehtävä myyjälle, muistutus ennen voimassaolon päättymistä. Loppuvat, kun asiakas vastaa.
4. Projekti: tilaus Skaalalle, valmistus, toimitus, asennus, luovutus. Dokumentit (sopimus, mittauspöytäkirja, laskut työn osuus eriteltynä, takuutodistus, huolto-ohjeet, kuvat) ja viestit.
5. Luovutus: kiitoslahja ja suosittelu.

## Kiitoslahja
- Jokainen asiakas: 50 € lahjakortti, asiakas valitsee Kotipizza tai S-ryhmä.
- Edellytys: remontti luovutettu ja maksettu. Yksi lahja per kohde.
- Palvelu EI anna koodeja: valinnasta lähtee sähköposti osoitteeseen suosittelu@ikkunakauppias.fi (asiakas, yhteystiedot, kohde, valinta, syy).
  Henkilökunta lähettää lahjakortin ja merkitsee sen portaalissa lähetetyksi → asiakas saa vahvistuksen.
- Lahjaa ei sidota arvosteluun.

## Suosittelu
- Asiakas saa oman suosittelulinkin/-koodin (jako WhatsApp, kopioi linkki, sähköposti). Ystävän yhteystietoja ei kysytä.
- Kun suositeltu kauppa on luovutettu ja maksettu:
  - suosittelija saa 50 € lahjakortin (Kotipizza / S-ryhmä, oma valinta)
  - uusi asiakas saa 50 € suosittelulahjan JA lisäksi tavallisen 50 € kiitoslahjan (yhteensä 100 €)
- Molemmat valinnat → suosittelu@ikkunakauppias.fi, käsittely kuten kiitoslahjassa.
- Ei itsesuosittelua eikä saman talouden suosittelua. Suositteluiden määrää ei rajoiteta.
- Yksityishenkilön palkkion verokohtelu tarkistetaan kirjanpitäjältä.

## Integraatiot (selvitettävänä)
- Skaalan tarjoustyökalu: API / sähköinen vienti / PDF-tuonti (tarvitaan esimerkkitarjous).
- Tavoittaja (tekstiviestit): API-ohjeet; vastaanottonumero vastauksille valinnainen.
- Joiner: mihin käytössä ja onko API/webhookit.
- Sopimuksen hyväksyntä: painike + aikaleima vai sähköinen allekirjoitus.
- Laskutusohjelma.

## Maksutapa (asiakas valitsee tarjouksen hyväksynnässä)
- **Lasku (Holvi):** 30 % tilattaessa, 70 % luovutuksessa.
  - Holvilla ei löytynyt julkista laskutusrajapintaa (vain kirjanpito- ja PSD2-rajapinnat). Ratkaisu: portaali kokoaa laskun tiedot
    (asiakas, osoite, rivit, työn osuus, viite) "Laskutus"-listaan, henkilökunta kopioi ne Holviin ja merkitsee laskun maksetuksi.
    Maksetuksi merkintä vie projektia eteenpäin. Kysytään Holvilta laskutusrajapinnasta; jos sellainen on, laskut luodaan automaattisesti.
- **Skaala-rahoitus (OP):** valinnasta tulee myyjälle tehtävä tehdä rahoitushakemus asiakkaan kanssa OP:n järjestelmässä.
  Henkilökunta merkitsee rahoituksen myönnetyksi, jolloin asiakas saa ilmoituksen ja projekti etenee.
  OP:n kuluttajarahoitukselle ei löytynyt julkista rajapintaa; kysytään Skaalalta/OP:lta.
