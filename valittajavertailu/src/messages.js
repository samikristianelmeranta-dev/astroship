// Sähköposti- ja tekstiviestipohjat. Palauttaa { subject, text, sms? }.
// links: { project(token), agent(), consumerLogin(token), home() }

const SIGNATURE = '\n\nYstävällisin terveisin\nVälittäjävertailu';

const fmtDate = (iso) =>
  new Date(iso).toLocaleString('fi-FI', { weekday: 'short', day: 'numeric', month: 'numeric', hour: '2-digit', minute: '2-digit', timeZone: 'Europe/Helsinki' });

export function buildMessage(kind, v, links) {
  switch (kind) {
    case 'vahvistus':
      return {
        subject: 'Vahvista myyntikohteesi',
        text:
          `Hei ${v.name},\n\nkiitos! Kohteesi (${v.title}) on melkein valmis välittäjille.\n\n` +
          `Julkaise kohde avaamalla tämä linkki:\n${links.project(v.token)}\n\n` +
          `Samalla linkillä vastaat välittäjien kysymyksiin, vertailet arvioita ja valitset välittäjän. Säilytä tämä viesti.\n` +
          `Jos et lähettänyt pyyntöä, voit jättää viestin huomiotta. Vahvistamaton pyyntö poistetaan 3 vuorokauden kuluttua.` +
          SIGNATURE,
      };
    case 'uusi_kohde':
      return {
        subject: `Uusi myyntikohde: ${v.title}`,
        text:
          `Alueellesi on tullut uusi myyntikohde, jolle myyjä etsii välittäjää.\n\n${v.title}\nArvioaika päättyy ${fmtDate(v.deadline)}.\n\n` +
          `Katso kohde ja kuvat ja jätä hinta-arvio ja palkkiotarjous:\n${links.agent()}` +
          SIGNATURE,
        sms: `Välittäjävertailu: uusi myyntikohde ${v.title}. Arvioaika päättyy ${fmtDate(v.deadline)}. ${links.agent()}`,
      };
    case 'kysymys':
      return {
        subject: v.wantsPhotos ? 'Välittäjä pyytää lisää kuvia kohteestasi' : 'Välittäjä kysyi kohteestasi',
        text:
          `Välittäjä ${v.wantsPhotos ? 'pyysi lisää kuvia' : 'kysyi kohteestasi'}:\n\n"${v.question}"\n\n` +
          `Vastaa ${v.wantsPhotos ? 'ja lisää kuvat ' : ''}tästä. Vastauksesi näkyy kaikille kohteen välittäjille, jotta kilpailu pysyy reiluna:\n${links.project(v.token)}` +
          SIGNATURE,
      };
    case 'vastaus':
      return {
        subject: `Myyjä vastasi: ${v.title}`,
        text:
          `Kysymys: "${v.question}"\n` +
          (v.answer ? `Vastaus: "${v.answer}"\n` : '') +
          (v.photoCount ? `Myyjä lisäsi ${v.photoCount} ${v.photoCount === 1 ? 'kuvan' : 'kuvaa'}.\n` : '') +
          `\nKatso kohde:\n${links.agent()}` +
          SIGNATURE,
      };
    case 'lisakuvia':
      return {
        subject: `Uusia kuvia: ${v.title}`,
        text: `Myyjä lisäsi kohteeseen ${v.count} ${v.count === 1 ? 'kuvan' : 'kuvaa'}. Voit tarkentaa arviotasi määräaikaan asti:\n${links.agent()}` + SIGNATURE,
      };
    case 'tarjoukset_auki':
      return {
        subject: `Sait ${v.count} ${v.count === 1 ? 'arvion' : 'arviota'} välittäjiltä`,
        text:
          `Hei ${v.name},\n\narvioaika on päättynyt ja sait ${v.count} ${v.count === 1 ? 'arvion' : 'arviota'} asunnostasi.\n\n` +
          `Vertaile hinta-arvioita ja palkkioita ja valitse yksi tai kaksi välittäjää:\n${links.project(v.token)}` +
          SIGNATURE,
      };
    case 'ei_tarjouksia':
      return {
        subject: 'Arvioaika päättyi',
        text:
          `Hei ${v.name},\n\narvioaika päättyi, mutta arvioita ei valitettavasti tullut.\n` +
          `Voit jättää uuden pyynnön pidemmällä määräajalla:\n${links.home()}` +
          SIGNATURE,
      };
    case 'kilpailu_paattyi':
      return {
        subject: `Arvioaika päättyi: ${v.title}`,
        text: `Arvioaika päättyi kohteessa, johon jätit arvion. Myyjä valitsee nyt välittäjän. Ilmoitamme tuloksen.` + SIGNATURE,
      };
    case 'valittu':
      return {
        subject: `Myyjä valitsi sinut: ${v.title}`,
        text:
          `Onnittelut! Myyjä valitsi sinut kohteeseen ${v.title}.\n\n` +
          (v.shared ? `Myyjä valitsi neuvotteluun kaksi välittäjää, ja sinä olet toinen heistä. Sovi arviokäynti pian.\n\n` : '') +
          `Myyjän yhteystiedot:\n${v.contact.name}\n${v.contact.address}\n${v.contact.phone}\n${v.contact.email}\n\n` +
          `Ota yhteyttä myyjään mahdollisimman pian ja sovi arviokäynti.` +
          (v.fee ? `\n\nPalvelumaksu ${v.fee} € (alv 0 %) laskutetaan kuukausittain.` : '') +
          SIGNATURE,
        sms: `Välittäjävertailu: myyjä valitsi sinut (${v.title}). Yhteystiedot: ${v.contact.name} ${v.contact.phone}`,
      };
    case 'ei_valittu':
      return {
        subject: `Myyjä teki valinnan: ${v.title}`,
        text: `Myyjä valitsi tällä kertaa toisen välittäjän. Kiitos arviostasi.` + SIGNATURE,
      };
    case 'valinta_vahvistus': {
      const two = v.chosen.length > 1;
      return {
        subject: two ? `Valitsit kaksi välittäjää: ${v.chosen.map((c) => c.company).join(' ja ')}` : `Valitsit välittäjän: ${v.chosen[0].company}`,
        text:
          `Hei ${v.name},\n\n` +
          (two
            ? 'valitsit kaksi välittäjää. Olemme välittäneet yhteystietosi molemmille, ja he ottavat sinuun yhteyttä arviokäynnin sopimiseksi. Voit tehdä toimeksiantosopimuksen arviokäyntien jälkeen.\n\n'
            : 'olemme välittäneet yhteystietosi valitsemallesi välittäjälle. Hän ottaa sinuun yhteyttä arviokäynnin sopimiseksi.\n\n') +
          v.chosen.map((c) => `${c.company} (arvio ${c.estimate})\n${c.agent.contactName}\n${c.agent.phone}\n${c.agent.email}`).join('\n\n') +
          `\n\nKohteesi: ${links.project(v.token)}` +
          SIGNATURE,
      };
    }
    case 'arvio_pyynto':
      return {
        subject: 'Miten asunnon myynti sujui?',
        text:
          `Hei ${v.name},\n\nvalitsit ${v.reviewDelayDays} päivää sitten ${v.companies.length > 1 ? `välittäjät ${v.companies.join(' ja ')}` : `välittäjän ${v.companies[0] ?? ''}`}. ` +
          `Kerro, miten yhteistyö sujui, ja arvioi välittäjä. Arviosi auttaa muita myyjiä valitsemaan:\n${links.project(v.token)}` +
          SIGNATURE,
      };
    case 'kirjautuminen':
      return {
        subject: 'Kirjautumislinkki: omat myyntikohteesi',
        text: `Kirjaudu tällä linkillä. Linkki on voimassa 30 minuuttia ja toimii kerran:\n${links.consumerLogin(v.token)}` + SIGNATURE,
      };
    case 'valittaja_hyvaksytty':
      return {
        subject: 'Välittäjätilisi on hyväksytty',
        text: `Hei ${v.contactName},\n\n${v.company} on hyväksytty Välittäjävertailuun. Näet alueesi myyntikohteet täällä:\n${links.agent()}` + SIGNATURE,
      };
    case 'uusi_valittaja':
      return {
        subject: `Uusi välittäjä odottaa hyväksyntää: ${v.company}`,
        text: `${v.company} (${v.businessId}) rekisteröityi. Tarkista tiedot ja välitysliikerekisteri ylläpidossa.` + SIGNATURE,
      };
    case 'jono_avattu':
      return {
        subject: 'Välittäjävertailu toimii nyt alueellasi',
        text: `Hei,\n\nliityit jonoon. Palvelu toimii nyt alueella ${v.region}. Kilpailuta välittäjät täällä:\n${links.home()}` + SIGNATURE,
      };
    default:
      throw new Error(`Tuntematon viestityyppi: ${kind}`);
  }
}
