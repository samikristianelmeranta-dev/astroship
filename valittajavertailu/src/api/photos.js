// Kuvat: myyjän kohdekuvat ja välittäjien logot.
import { MAX_PHOTO_BASE64 } from '../catalog.js';
import { canSee, isPublished } from '../tender.js';
import { reply, notFound, safeEqual, JPEG_DATA_URL } from './util.js';

export async function savePhoto(ctx, dataUrl, extra) {
  const match = JPEG_DATA_URL.exec(String(dataUrl ?? ''));
  if (!match) return { error: reply(422, { error: 'Kuvan on oltava JPEG-muodossa.' }) };
  if (match[1].length > MAX_PHOTO_BASE64) return { error: reply(413, { error: 'Kuva on liian suuri.' }) };
  const saved = await ctx.stores.photos.insert({ requestId: null, type: 'image/jpeg', bytes: Math.floor((match[1].length * 3) / 4), ...extra });
  await ctx.photoFiles.write(saved.id, match[1]);
  return { photo: saved };
}

export function photoRoutes(ctx) {
  return [
    // Selain on jo pienentänyt kuvan ja muuntanut sen JPEGiksi, jolloin myös
    // sijaintitiedot (EXIF) ovat poistuneet.
    ['POST', /^\/api\/photos$/, async ({ body }) => {
      const { error, photo } = await savePhoto(ctx, body.dataUrl, { kind: 'kohde' });
      return error ?? reply(201, { id: photo.id });
    }],

    // Logot ovat julkisia. Kohdekuvan näkevät ylläpito, kohteen näkevät välittäjät
    // ja kuluttaja omalla tunnuksellaan.
    ['GET', /^\/api\/photos\/([\w-]+)$/, async ({ params: [id], query, session }) => {
      const photo = (await ctx.stores.photos.all()).find((p) => p.id === id);
      if (!photo) return notFound();
      let allowed = photo.kind === 'logo';
      if (!allowed) {
        const request = photo.requestId && (await ctx.stores.requests.all()).find((r) => r.id === photo.requestId);
        if (!request) return notFound();
        const agent = await ctx.currentAgent(session);
        allowed =
          session?.role === 'admin' ||
          Boolean(agent && isPublished(request) && canSee(agent, request, ctx.geo)) ||
          safeEqual(query.get('t') ?? '', request.consumerToken ?? '');
      }
      if (!allowed) return notFound();
      const base64 = await ctx.photoFiles.read(id);
      return base64 ? reply(200, null, { file: { type: photo.type, base64 } }) : notFound();
    }],
  ];
}
