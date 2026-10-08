# Nordic WhatsApp – myynti ja asennus

Vihreä WhatsApp-painike sivuston vasempaan alakulmaan. Kävijä valitsee **Myynti** tai
**Asennus**, ja keskustelu aukeaa oikeaan numeroon valmiin aloitusviestin kanssa.
Viestiin lisätään automaattisesti sivu, jolta kävijä kirjoittaa.

Kysy meiltä -chat pysyy oikeassa alakulmassa, joten painikkeet eivät mene päällekkäin.

## Asennus

1. Lisäosat → Lisää uusi → Lataa lisäosa → `nordic-whatsapp-1.0.0.zip` → Asenna → Ota käyttöön.
2. Asetukset → **WhatsApp**: tarkista numerot, otsikot, aloitusviestit ja vastausaika.
3. Paina sivun alareunan **Testaa**-painikkeita: keskustelun pitää aueta oikeaan numeroon.

Numeron voi kirjoittaa muodossa `050 123 4567` tai `+358 50 123 4567`.
Jos asennuksen numeron jättää tyhjäksi, painike avaa suoraan myynnin keskustelun.

## Lyhytkoodi

`[nordic_whatsapp]` lisää molemmat painikkeet sivun sisältöön, esim. Yhteystiedot-sivulle.

## Seuranta

Jokainen klikkaus lähettää Analyticsiin (dataLayer) tapahtuman `whatsapp_click`
tiedolla `whatsapp_target` = `sales` / `inst`.

## Tietosuoja

Painike on pelkkä linkki: mitään ei ladata WhatsAppista ennen klikkausta, eikä se aseta
evästeitä. Tietosuojaselosteeseen kannattaa lisätä maininta, että WhatsAppin kautta
lähetetyt viestit käsittelee myös WhatsApp (Meta Platforms Ireland Ltd.).
