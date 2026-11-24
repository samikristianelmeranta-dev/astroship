"""X-postauskalenteri: joulukuu 2026.

Käyttö: python3 tools/x_kalenteri.py <tulos.md> <tulos.json>
Tarkistaa, että jokainen postaus mahtuu X:n 280 merkkiin (linkki = 23 merkkiä, emoji = 2).
"""
import json
import re
import sys

SITE = "https://www.samielmeranta.fi"

# (päivä, kellonaika, tyyppi, [postaukset], kuvaidea)
POSTS = [
    ("1.12.", "8.00", "Avaus", [
        "Joulukuu alkaa ja eduskuntavaaleihin on 138 päivää.\n\nMinulla ei ole isoa kampanjabudjettia eikä ison puolueen koneistoa. Minulla on kengät, puhelin ja halu korjata asioita.\n\nOlen ehdolla Varsinais-Suomessa. 👇\n" + SITE + "/olen-eduskuntavaaliehdokas-2027/",
    ], "Profiilikuva Liike Nyt -kehyksellä"),
    ("2.12.", "7.30", "Kärkevä", [
        "Suomen julkiset menot ovat EU:n suurimmat suhteessa talouteen.\n\nSilti terveyskeskukseen jonotetaan viikkoja ja kouluista leikataan.\n\nOngelma ei ole se, ettei rahaa olisi. Ongelma on se, mihin se katoaa.",
    ], "Ei kuvaa, teksti toimii yksin"),
    ("3.12.", "12.00", "Kysely", [
        "Mikä on Suomen talouden suurin jarru juuri nyt?\n\n(Kyselyn vaihtoehdot: Työn verotus / Byrokratia ja luvat / Jäykät työmarkkinat / Julkisen sektorin koko)\n\nPerustele kommenteissa. 👇",
    ], "X:n kysely, 4 vaihtoehtoa, kesto 3 päivää"),
    ("4.12.", "7.30", "Kärkevä", [
        "Vuonna 2025 Suomessa haettiin 3 906 konkurssia.\n\nJokaisen luvun takana on yrittäjä, joka on pantannut kotinsa, ja työntekijöitä, jotka menettivät työnsä.\n\nTämä ei ole tilasto. Tämä on hätähuuto.",
    ], "Ei kuvaa"),
    ("5.12.", "10.00", "Yrittäjät", [
        "Palkansaaja maksaa eläkemaksun todellisesta palkastaan.\n\nYrittäjä maksaa YEL-maksua työtulosta, jota ei välttämättä ole olemassa.\n\nMiksi sama logiikka ei kelpaa yrittäjälle? YEL todellisiin tuloihin. Heti.\n" + SITE + "/yel-maksu-2027/",
    ], "Linkin esikatselukuva"),
    ("6.12.", "9.00", "Juhlapäivä", [
        "Hyvää itsenäisyyspäivää 🇫🇮\n\nVeteraanit rakensivat maan, jossa jokainen voi tehdä työtä ja rakentaa tulevaisuutensa.\n\nMeidän tehtävämme on pitää huoli, että sama lupaus pitää myös lastemme kohdalla.",
    ], "Oma kuva, esim. kynttilät ikkunalla"),
    ("7.12.", "19.00", "Ketju (6 osaa)", [
        "🧵 Miksi Suomen talous ei kasva?\n\nSyitä ei ole yksi, vaan viisi. Ja niistä puhutaan aivan liian vähän. 1/6",
        "1. Nokia ja paperiteollisuus jättivät aukon, jota ei ole täytetty.\n\nEmme tarvitse yhtä uutta jättiä. Tarvitsemme tuhansia kasvavia pk-yrityksiä. 2/6",
        "2. Investoinnit. Kun luvat kestävät vuosia ja tulevaisuus näyttää sumealta, raha menee Ruotsiin tai jää pankkiin. 3/6",
        "3. Työn verotus. Kun ylityö tai sivukeikka ei näy tilillä, sitä ei tehdä.\n\nMitä enemmän ihmisille jää käteen omasta työstään, sen paremmin meillä menee. 4/6",
        "4. Julkiset menot ovat EU:n suurimmat, mutta tulokset eivät.\n\n5. Työmarkkinat ovat niin jäykät, että pienen firman on vaikea palkata ketään. 5/6",
        "Ratkaisut ovat tiedossa. Puuttuu rohkeus.\n\nKirjoitin aiheesta pidemmin tänne: " + SITE + "/miksi-suomen-talous-ei-kasva/ 6/6",
    ], "Ensimmäiseen osaan some-banneri 1200x630"),
    ("8.12.", "7.30", "Kärkevä", [
        "Kansanedustajan palkkio on noin 7 600 € kuussa. En pidä sitä ongelmana.\n\nOngelma ovat ne sivupalkkiot hallituksista, säätiöistä ja yhtiöistä, joita samat ihmiset keräävät vuodesta toiseen.\n\nPolitiikka on palvelutehtävä, ei hillotolppa.",
    ], "Ei kuvaa"),
    ("9.12.", "12.00", "Kärkevä", [
        "Työttömyys on EU:n korkeimpia, ja silti yrittäjät kertovat, etteivät löydä tekijöitä.\n\nKun järjestelmä tekee lyhyen keikan vastaanottamisesta tappiollista, vika ei ole ihmisissä.\n\nVika on järjestelmässä.",
    ], "Ei kuvaa"),
    ("10.12.", "7.30", "Ratkaisu", [
        "Uusi yritys toimisi kolme ensimmäistä vuotta ilman yhteisöveroa. Se on Liike Nytin esitys, ja se on hyvä.\n\nValtio ei menetä euroakaan yrityksistä, joita ei muuten olisi edes perustettu.\n\n#LiikeNyt",
    ], "Liike Nyt -mainos 1080x1080"),
    ("11.12.", "16.00", "Kaarina", [
        "Kaarinan Lemunniemeen suunnitellaan golfkenttää. ⛳\n\nYksityinen investointi, uusia palveluja ja lisää vetovoimaa ilman, että veronmaksaja maksaa.\n\nTällaisia hankkeita kunnat tarvitsevat lisää, ei vähemmän.\n" + SITE + "/golfkentta-kaarinaan/",
    ], "Golfpuiston havainnekuva (mainitse Ark Takala oy)"),
    ("12.12.", "10.00", "Verotus", [
        "Yleinen arvonlisävero on Suomessa nyt 25,5 %, yksi Euroopan korkeimmista.\n\nVeronkiristykset eivät ole pelastaneet taloutta. Ne ovat vain hidastaneet sitä.\n\nKevennetään työn ja kulutuksen verotusta, niin raha jää kiertämään Suomeen.",
    ], "Ei kuvaa"),
    ("13.12.", "19.00", "Osallistava", [
        "Jos saisit poistaa Suomesta yhden turhan säännön, lupaprosessin tai lomakkeen, mikä se olisi?\n\nKerään vastaukset ja vien ne mukanani. 👇",
    ], "Ei kuvaa"),
    ("14.12.", "7.30", "Kärkevä", [
        "Valtion velka kasvaa joka ikinen päivä.\n\nJokainen velkaeuro on lasku, jonka lähetämme lapsillemme.\n\nEn halua olla se sukupolvi, joka söi tulevaisuuden etukäteen.",
    ], "Ei kuvaa"),
    ("15.12.", "12.00", "Ratkaisu", [
        "Byrokratia ei näy budjetissa, mutta se näkyy kaikkialla muualla: lupajonoissa, yrittäjän illoissa ja investoinneissa, jotka menivät Ruotsiin.\n\nEhdotan: lupa määräajassa. Jos viranomainen ei ehdi, lupa katsotaan myönnetyksi.",
    ], "Ei kuvaa"),
    ("16.12.", "7.30", "Maahanmuutto", [
        "Sosiaaliturva pitää ansaita.\n\nSuomeen muuttavan pitää ensin tehdä töitä ja maksaa veroja, ennen kuin hän saa täyden pääsyn tukiin. Tanskassa näin on jo tehty.\n\nKyse ei ole kovuudesta. Kyse on reiluudesta niitä kohtaan, jotka maksavat.",
    ], "Ei kuvaa"),
    ("17.12.", "12.00", "Kärkevä", [
        "Maahanmuutosta pitää voida puhua ilman, että joku huutaa heti rasistia tai suvakkia.\n\nMinun mittarini on yksinkertainen: johtaako se työhön?\n\nJos johtaa, tervetuloa. Jos ei, politiikkaa pitää muuttaa.",
    ], "Ei kuvaa"),
    ("18.12.", "7.30", "Vaalit", [
        "Varsinais-Suomesta valitaan huhtikuussa 18 kansanedustajaa, yksi enemmän kuin viimeksi.\n\nLiike Nytillä niistä on tällä hetkellä nolla.\n\nTavoite on muuttaa se luku. 💪\n" + SITE + "/varsinais-suomen-vaalipiiri-2027/",
    ], "Kangasbannerin esikatselukuva"),
    ("19.12.", "10.00", "Kärkevä", [
        "Hyvinvointialueiden piti hillitä kustannuksia. Nyt alueet ovat alijäämäisiä ja palveluja leikataan.\n\nKun uudistus tekee asiasta kalliimman ja huonomman, se ei ole uudistus.\n\nSe on virhe, joka pitää uskaltaa korjata.",
    ], "Ei kuvaa"),
    ("20.12.", "11.00", "Maahanmuutto", [
        "Kielteisen turvapaikkapäätöksen saaneen pitää poistua maasta.\n\nJos päätöksiä ei panna täytäntöön, koko järjestelmä menettää uskottavuutensa, myös niiden silmissä, jotka oikeasti tarvitsevat suojaa.\n\nPalautukset toimiviksi.",
    ], "Ei kuvaa"),
    ("21.12.", "7.30", "Yrittäjät", [
        "Pienyrittäjä tekee 60 tunnin viikkoja, palkkaa ensimmäisen työntekijän ja saa palkinnoksi pinon lisää paperia.\n\nEnsimmäisen työntekijän palkkaamisesta pitää tehdä helppoa. Se on halvin työllisyystoimi, joka on olemassa.",
    ], "Ei kuvaa"),
    ("22.12.", "19.00", "Ketju (4 osaa)", [
        "🧵 Jos pääsen eduskuntaan, nämä viisi asiaa vien eteenpäin ensimmäisenä. 1/4",
        "1. YEL todellisiin tuloihin.\n2. Uusille yrityksille kolme vuotta ilman yhteisöveroa. 2/4",
        "3. Luvat määräaikaan: jos viranomainen ei ehdi, lupa katsotaan myönnetyksi.\n4. Ensimmäisen työntekijän palkkaaminen helpoksi. 3/4",
        "5. Poliitikkojen sivupalkkiot julki ja kuriin.\n\nJos tämä kuulostaa hyvältä, jaa eteenpäin. Ilman isoa budjettia jokainen jako on minulle kullanarvoinen. 🙏 4/4",
    ], "Ensimmäiseen osaan pysty-some-kuva 1080x1350"),
    ("23.12.", "18.00", "Kiitos", [
        "Aatonaatto.\n\nKiitos kaikille, jotka ovat tänä vuonna jakaneet, kommentoineet ja tulleet juttelemaan.\n\nAloitin tämän matkan pienellä budjetilla ja isolla innolla. Kumpikaan ei ole muuttunut. 🙏",
    ], "Oma kuva"),
    ("24.12.", "10.00", "Juhlapäivä", [
        "Hyvää joulua! 🎄\n\nTänään ei politiikkaa. Halataan läheisiä, syödään liikaa ja muistetaan niitä, joille joulu on yksinäinen.\n\nSoita yksi puhelu. Se voi olla jonkun päivän paras hetki.",
    ], "Jouluinen oma kuva"),
    ("25.12.", "-", "Tauko", [
        "(Ei postausta. Joulupäivänä hiljaisuus toimii paremmin kuin politiikka.)",
    ], "-"),
    ("26.12.", "12.00", "Maahanmuutto", [
        "Kansalaisuus on luottamuksen osoitus, ei automaatti.\n\nSuomen tai ruotsin kielen taito, työhistoria ja nuhteeton tausta ovat kohtuullisia vaatimuksia.\n\nKuka on eri mieltä, ja miksi? 👇",
    ], "Ei kuvaa"),
    ("27.12.", "19.00", "Kärkevä", [
        "Vuosi 2026 Suomessa lyhyesti: työttömyys EU:n kärjessä, konkurssiaalto ja velka, joka vain kasvaa.\n\nVuosi 2027: vaalit.\n\nMe saamme päättää, jatkuuko sama meno vai kokeillaanko jotain uutta.",
    ], "Ei kuvaa"),
    ("28.12.", "12.00", "Mainos", [
        "EU:n suurimmat julkiset menot. EU:n korkeimpia työttömyyslukuja. Konkursseja ennätystahtia. Kasvu on laahannut lähes 20 vuotta.\n\nEhkä olisi aika kokeilla jotain uutta?\n\n#eduskuntavaalit2027",
    ], "Some-mainos 1080x1350 (Tolkun poliitikko -mainos)"),
    ("29.12.", "7.30", "Esittely", [
        "Olen yrittäjä, kolmen lapsen isä ja kaarinalainen kuntapoliitikko. En ole urapoliitikko.\n\nJuuri siksi uskon, että eduskunta tarvitsee lisää ihmisiä, jotka tietävät, mitä palkanmaksupäivä ja kassakriisi oikeasti tarkoittavat.",
    ], "Profiilikuva"),
    ("30.12.", "12.00", "Verotus", [
        "Keskituloisella jopa noin puolet jokaisesta lisäansaitusta eurosta menee veroihin ja maksuihin.\n\nSitten ihmetellään, miksi kukaan ei halua tehdä ylitöitä tai ottaa vastaan lisävuoroja.\n\nTyön verotusta on kevennettävä. Piste.",
    ], "Ei kuvaa"),
    ("31.12.", "16.00", "Juhlapäivä", [
        "Hyvää uutta vuotta! 🎆\n\nVaaleihin on 108 päivää. Vuonna 2027 suomalaiset päättävät, mihin suuntaan tämä maa menee.\n\nPidetään huolta toisistamme ja tehdään siitä hyvä vuosi.",
    ], "Oma kuva tai flyer A5"),
]


def x_len(text):
    """X:n laskutapa likimäärin: linkki 23 merkkiä, emoji ja muut laajat merkit 2."""
    text = re.sub(r"https?://\S+", "x" * 23, text)
    return sum(2 if ord(c) > 0x2FFF else 1 for c in text)


if __name__ == "__main__":
    md_out, json_out = sys.argv[1], sys.argv[2]
    rows, lines = [], ["# X-postaukset: joulukuu 2026", "", "Sami Elmeranta, eduskuntavaaliehdokas, Liike Nyt, Varsinais-Suomi.", ""]
    for day, time, kind, tweets, image in POSTS:
        lens = [x_len(t) for t in tweets]
        assert max(lens) <= 280, (day, lens)
        rows.append({"day": day, "time": time, "kind": kind, "tweets": tweets, "lens": lens, "image": image})
        lines += [f"## {day} klo {time} · {kind}", ""]
        for i, t in enumerate(tweets):
            if len(tweets) > 1:
                lines.append(f"**Osa {i + 1}**")
                lines.append("")
            lines += [t, "", f"_{lens[i]}/280 merkkiä_", ""]
        lines += [f"Kuva: {image}", "", "---", ""]
    open(md_out, "w", encoding="utf-8").write("\n".join(lines))
    json.dump(rows, open(json_out, "w", encoding="utf-8"), ensure_ascii=False)
    print(len(rows), "päivää, pisin", max(max(r["lens"]) for r in rows), "merkkiä")
