// Viestipalvelut. Ilman asetuksia palautetaan null: viestit kirjataan vain
// ylläpidon "Lähetetyt viestit" -listaan (kehitystila).

// Postmark: https://postmarkapp.com/developer/api/email-api
function postmark({ token, from, stream = 'outbound' }) {
  return async ({ to, subject, text }) => {
    const res = await fetch('https://api.postmarkapp.com/email', {
      method: 'POST',
      headers: { Accept: 'application/json', 'Content-Type': 'application/json', 'X-Postmark-Server-Token': token },
      body: JSON.stringify({ From: from, To: to, Subject: subject, TextBody: text, MessageStream: stream }),
      signal: AbortSignal.timeout(10_000),
    });
    if (!res.ok) throw new Error(`Postmark ${res.status}: ${(await res.text()).slice(0, 200)}`);
  };
}

// 46elks: https://46elks.com/docs/send-sms
function elks({ user, password, from }) {
  return async ({ to, text }) => {
    const res = await fetch('https://api.46elks.com/a1/sms', {
      method: 'POST',
      headers: {
        Authorization: `Basic ${Buffer.from(`${user}:${password}`).toString('base64')}`,
        'Content-Type': 'application/x-www-form-urlencoded',
      },
      body: new URLSearchParams({ from, to: to.replace(/^0/, '+358'), message: text }),
      signal: AbortSignal.timeout(10_000),
    });
    if (!res.ok) throw new Error(`46elks ${res.status}: ${(await res.text()).slice(0, 200)}`);
  };
}

export function messengerFromEnv(env = process.env) {
  if (!env.POSTMARK_TOKEN) return null;
  const email = postmark({ token: env.POSTMARK_TOKEN, from: env.MAIL_FROM ?? 'Välittäjävertailu <noreply@valittajavertailu.fi>' });
  const sms = env.ELKS_USER
    ? elks({ user: env.ELKS_USER, password: env.ELKS_PASSWORD, from: env.SMS_FROM ?? 'Valittajat' })
    : async () => {
        throw new Error('Tekstiviestipalvelua ei ole määritetty (ELKS_USER).');
      };
  return { live: true, email, sms };
}

// YTJ:n avoin rajapinta. Palauttaa raakavastauksen; tulkinta src/prh.js.
export async function ytjLookup(businessId) {
  const url = `https://avoindata.prh.fi/opendata-ytj-api/v3/companies?businessId=${encodeURIComponent(businessId)}`;
  const res = await fetch(url, { headers: { Accept: 'application/json' }, signal: AbortSignal.timeout(8000) });
  if (!res.ok) throw new Error(`YTJ ${res.status}`);
  return res.json();
}
