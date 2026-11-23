/**
 * Pipon mallikuva: sijoittaa merkin pipokuvan painoalueelle (assets/pipo-pohja.png).
 * Käyttö: node mainokset/mallikuva-pipo.cjs <merkki.png> <tulos.png>
 */
const path = require("path");
const sharp = require("sharp");

(async () => {
  const bg = path.join(__dirname, "assets/pipo-pohja.png");
  // Peitetään pohjakuvan mallilogo pipon värillä ja asetetaan merkki painoalueelle.
  const cover = await sharp({ create: { width: 356, height: 178, channels: 4, background: { r: 22, g: 22, b: 22, alpha: 1 } } }).png().toBuffer();
  const badge = await sharp(process.argv[2]).resize({ width: 356, height: 178, fit: "contain", background: { r: 0, g: 0, b: 0, alpha: 0 } }).toBuffer();
  await sharp(bg).composite([{ input: cover, left: 124, top: 409 }, { input: badge, left: 124, top: 409 }]).png().toFile(process.argv[3]);
})();
