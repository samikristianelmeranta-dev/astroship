import type { APIRoute } from "astro";
import { Resend } from "resend";
import {
  analyzeVisibility,
  type VisibilityReport,
} from "@/lib/visibilityAnalyzer";
import {
  customerConfirmationEmail,
  customerReportEmail,
  leadNotificationEmail,
} from "@/lib/emailTemplates";

export const prerender = false;

const REPORT_DELAY_MS = 4 * 60 * 60 * 1000; // 4 tuntia
const EMAIL_RE = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

function jsonResponse(body: unknown, status = 200): Response {
  return new Response(JSON.stringify(body), {
    status,
    headers: { "Content-Type": "application/json" },
  });
}

export const POST: APIRoute = async ({ request }) => {
  let payload: {
    url?: string;
    email?: string;
    company?: string;
    botcheck?: string;
  };
  try {
    payload = await request.json();
  } catch {
    return jsonResponse({ error: "Virheellinen pyyntö." }, 400);
  }

  const { url, email, company, botcheck } = payload;

  // Honeypot: bots fill hidden fields, real users leave it empty.
  if (botcheck) {
    return jsonResponse({ success: true });
  }

  if (!url || typeof url !== "string" || url.trim().length < 3) {
    return jsonResponse({ error: "Anna kelvollinen verkko-osoite." }, 400);
  }
  if (!email || typeof email !== "string" || !EMAIL_RE.test(email.trim())) {
    return jsonResponse({ error: "Anna kelvollinen sähköpostiosoite." }, 400);
  }

  const apiKey = import.meta.env.RESEND_API_KEY;
  const fromEmail =
    import.meta.env.RESEND_FROM_EMAIL || "raportit@kasvukumppani.fi";
  const leadEmail =
    import.meta.env.LEAD_NOTIFICATION_EMAIL || "liidit@kasvukumppani.fi";

  if (!apiKey) {
    console.error("RESEND_API_KEY puuttuu ympäristömuuttujista.");
    return jsonResponse(
      {
        error:
          "Palvelu ei ole juuri nyt käytettävissä. Yritä myöhemmin uudelleen.",
      },
      500,
    );
  }

  const resend = new Resend(apiKey);

  let report: VisibilityReport;
  try {
    report = await analyzeVisibility(url);
  } catch (err) {
    console.error("Analyysi epäonnistui", err);
    return jsonResponse(
      {
        error:
          "Sivustoa ei voitu analysoida. Tarkista osoite ja yritä uudelleen.",
      },
      500,
    );
  }

  const normalizedUrl = report.url;
  const scheduledAt = new Date(Date.now() + REPORT_DELAY_MS).toISOString();

  const lead = leadNotificationEmail({
    url: normalizedUrl,
    email: email.trim(),
    company: company?.trim(),
    report,
  });
  const confirmation = customerConfirmationEmail({ url: normalizedUrl });
  const fullReport = customerReportEmail({ url: normalizedUrl, report });

  const results = await Promise.allSettled([
    resend.emails.send({
      from: fromEmail,
      to: leadEmail,
      replyTo: email.trim(),
      subject: lead.subject,
      html: lead.html,
    }),
    resend.emails.send({
      from: fromEmail,
      to: email.trim(),
      subject: confirmation.subject,
      html: confirmation.html,
    }),
    resend.emails.send({
      from: fromEmail,
      to: email.trim(),
      subject: fullReport.subject,
      html: fullReport.html,
      scheduledAt,
    }),
  ]);

  results.forEach((result, i) => {
    if (result.status === "rejected") {
      console.error(`Sähköpostin lähetys #${i} epäonnistui`, result.reason);
    }
  });

  const leadSent = results[0].status === "fulfilled";
  if (!leadSent) {
    return jsonResponse(
      {
        error:
          "Analyysi onnistui, mutta liidi-ilmoituksen lähetys epäonnistui.",
      },
      500,
    );
  }

  return jsonResponse({
    success: true,
    seoScore: report.seo.score,
    aiScore: report.ai.score,
  });
};
