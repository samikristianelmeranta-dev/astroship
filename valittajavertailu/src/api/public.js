// Julkiset reitit: luettelot, maakunta, kattavuus, tapahtumalaskuri, terveys.
import {
  REGIONS, PROPERTY_TYPES, OWNERSHIP, ROOMS, CONDITIONS, FEATURES, TIMELINES, DEADLINE_OPTIONS,
  MIN_PHOTOS, MAX_PHOTOS, BID_INCLUDES, FUNNEL_EVENTS, regionForPostalCode,
} from '../catalog.js';
import { canSee } from '../tender.js';
import { reply } from './util.js';

// Selaimesta saa kirjata vain nämä; muut kirjaa palvelin itse.
const CLIENT_EVENTS = new Set(['etusivu', 'lomake_1', 'lomake_2', 'lomake_3']);

export function publicRoutes(ctx) {
  return [
    ['GET', /^\/api\/catalog$/, async () =>
      reply(200, {
        regions: REGIONS,
        propertyTypes: PROPERTY_TYPES,
        ownership: OWNERSHIP,
        rooms: ROOMS,
        conditions: CONDITIONS,
        features: FEATURES,
        timelines: TIMELINES,
        deadlineOptions: DEADLINE_OPTIONS,
        minPhotos: MIN_PHOTOS,
        maxPhotos: MAX_PHOTOS,
        bidIncludes: BID_INCLUDES,
        funnelEvents: FUNNEL_EVENTS,
        pilotRegions: ctx.config.pilotRegions,
        successFeeEur: ctx.config.successFeeEur,
        radiusAvailable: ctx.geo.available,
      })],

    ['GET', /^\/api\/region$/, async ({ query }) => reply(200, { region: regionForPostalCode(query.get('postalCode')) })],

    // Kertoo lomakkeelle, toimiiko palvelu alueella ja montako välittäjää kohteen näkee.
    ['GET', /^\/api\/coverage$/, async ({ query }) => {
      const postalCode = String(query.get('postalCode') ?? '');
      const region = String(query.get('region') ?? '') || regionForPostalCode(postalCode);
      const probe = { region, postalCode };
      const agents = (await ctx.stores.agents.all()).filter((a) => canSee(a, probe, ctx.geo)).length;
      return reply(200, { region, inPilot: ctx.inPilot(region), pilotRegions: ctx.config.pilotRegions, agents });
    }],

    ['POST', /^\/api\/events$/, async ({ body }) => {
      if (CLIENT_EVENTS.has(body.name)) await ctx.track(body.name);
      return reply(204, null);
    }],

    ['GET', /^\/api\/health$/, async () => {
      await ctx.stores.requests.all();
      return reply(200, { ok: true });
    }],
  ];
}
