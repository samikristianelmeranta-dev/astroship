// Ajastetut työt. Palvelin ajaa nämä minuutin välein.
import { isOpen, acceptedIds } from './tender.js';

const DAY = 864e5;
const ORPHAN_PHOTO_MS = DAY;
const UNVERIFIED_TTL_MS = 3 * DAY;
const LOGIN_CLEANUP_MS = DAY;

export async function runJobs(ctx) {
  const { requests, bids, agents, photos, logins } = ctx.stores;
  const t = ctx.now();
  const done = { closed: 0, reviewRequests: 0, photosRemoved: 0, unverifiedRemoved: 0 };
  const byId = Object.fromEntries((await agents.all()).map((a) => [a.id, a]));
  const allBids = await bids.all();

  for (const r of await requests.all()) {
    // 1. Määräaika päättyi: myyjälle arviot auki, arvion jättäneille tieto.
    if (r.status === 'avoin' && r.deadline && !isOpen(r, t) && !r.closedNotifiedAt) {
      const claimed = await requests.update(r.id, (x) => (x.closedNotifiedAt ? null : { closedNotifiedAt: ctx.iso() }));
      if (!claimed) continue;
      const own = allBids.filter((b) => b.requestId === r.id);
      await ctx.notify({ email: r.email }, own.length ? 'tarjoukset_auki' : 'ei_tarjouksia', { name: r.name, count: own.length, token: r.consumerToken });
      for (const b of own) {
        const agent = byId[b.agentId];
        if (agent && agent.notifyEmail !== false) {
          await ctx.notify({ email: agent.email }, 'kilpailu_paattyi', { title: ctx.listingTitle(r) });
        }
      }
      done.closed += 1;
    }

    // 2. Arviopyyntö, kun valinnasta on kulunut sovittu aika.
    if (r.status === 'valittu' && r.acceptedAt && !r.reviewRequestedAt && t - Date.parse(r.acceptedAt) >= ctx.config.reviewDelayDays * DAY) {
      const claimed = await requests.update(r.id, (x) => (x.reviewRequestedAt ? null : { reviewRequestedAt: ctx.iso() }));
      if (!claimed) continue;
      const companies = acceptedIds(r)
        .map((id) => byId[allBids.find((b) => b.id === id)?.agentId]?.company)
        .filter(Boolean);
      await ctx.notify({ email: r.email }, 'arvio_pyynto', {
        name: r.name, companies, token: r.consumerToken, reviewDelayDays: ctx.config.reviewDelayDays,
      });
      done.reviewRequests += 1;
    }

    // 3. Vahvistamattomat kohteet poistetaan kuvineen.
    if (r.status === 'vahvistamaton' && t - Date.parse(r.createdAt) > UNVERIFIED_TTL_MS) {
      for (const id of r.photoIds ?? []) {
        await photos.remove(id);
        await ctx.photoFiles.remove?.(id);
      }
      await requests.remove(r.id);
      done.unverifiedRemoved += 1;
    }
  }

  // 4. Kuvat, joita ei liitetty mihinkään kohteeseen.
  for (const p of await photos.all()) {
    if (p.kind !== 'logo' && !p.requestId && t - Date.parse(p.createdAt) > ORPHAN_PHOTO_MS) {
      await photos.remove(p.id);
      await ctx.photoFiles.remove?.(p.id);
      done.photosRemoved += 1;
    }
  }

  // 5. Vanhentuneet kirjautumislinkit.
  for (const l of await logins.all()) {
    if (Date.parse(l.expiresAt) + LOGIN_CLEANUP_MS < t) await logins.remove(l.id);
  }
  return done;
}
