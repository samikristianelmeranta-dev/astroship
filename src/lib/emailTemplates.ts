import type { VisibilityReport, CheckResult } from "./visibilityAnalyzer";

function scoreColor(score: number): string {
  if (score >= 80) return "#16a34a";
  if (score >= 50) return "#d97706";
  return "#dc2626";
}

function checklistRows(checks: CheckResult[]): string {
  return checks
    .map(
      (c) => `
      <tr>
        <td style="padding:10px 0;border-bottom:1px solid #eee;vertical-align:top;width:28px;">
          <span style="display:inline-block;width:20px;height:20px;border-radius:50%;background:${
            c.passed ? "#16a34a" : "#dc2626"
          };color:#fff;font-size:12px;line-height:20px;text-align:center;">${
            c.passed ? "&#10003;" : "&#10007;"
          }</span>
        </td>
        <td style="padding:10px 0;border-bottom:1px solid #eee;">
          <div style="font-weight:600;color:#1e293b;">${c.label}</div>
          <div style="color:#64748b;font-size:13px;margin-top:2px;">${c.detail}</div>
          ${
            !c.passed
              ? `<div style="color:#334155;font-size:13px;margin-top:4px;"><strong>Suositus:</strong> ${c.recommendation}</div>`
              : ""
          }
        </td>
      </tr>`,
    )
    .join("");
}

function scoreBadge(label: string, score: number): string {
  return `
  <td style="padding:16px;text-align:center;width:50%;">
    <div style="font-size:36px;font-weight:800;color:${scoreColor(score)};">${score}<span style="font-size:16px;color:#94a3b8;">/100</span></div>
    <div style="color:#475569;font-size:14px;margin-top:4px;">${label}</div>
  </td>`;
}

function baseWrapper(title: string, bodyHtml: string): string {
  return `<!doctype html>
<html lang="fi">
  <body style="margin:0;background:#f8fafc;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Helvetica,Arial,sans-serif;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f8fafc;padding:32px 0;">
      <tr>
        <td align="center">
          <table role="presentation" width="600" cellpadding="0" cellspacing="0" style="background:#ffffff;border-radius:12px;overflow:hidden;max-width:600px;width:100%;">
            <tr>
              <td style="background:#0f172a;padding:24px 32px;">
                <span style="color:#fff;font-size:18px;font-weight:700;">Kasvukumppani</span>
                <div style="color:#94a3b8;font-size:13px;margin-top:2px;">Hakukone- ja AI-näkyvyystesti</div>
              </td>
            </tr>
            <tr>
              <td style="padding:32px;">
                ${bodyHtml}
              </td>
            </tr>
            <tr>
              <td style="padding:20px 32px;background:#f1f5f9;color:#94a3b8;font-size:12px;">
                Kasvukumppani &middot; ${title}
              </td>
            </tr>
          </table>
        </td>
      </tr>
    </table>
  </body>
</html>`;
}

export function leadNotificationEmail(params: {
  url: string;
  email: string;
  company?: string;
  report: VisibilityReport;
}): { subject: string; html: string } {
  const { url, email, company, report } = params;
  const subject = `Uusi liidi: ${company ? company + " - " : ""}${url}`;

  const body = `
    <h1 style="font-size:20px;color:#0f172a;margin:0 0 16px;">Uusi liidi näkyvyystestistä</h1>
    <table role="presentation" cellpadding="0" cellspacing="0" style="width:100%;margin-bottom:20px;">
      <tr><td style="padding:4px 0;color:#475569;width:140px;">Sivusto</td><td style="padding:4px 0;font-weight:600;"><a href="${url}">${url}</a></td></tr>
      <tr><td style="padding:4px 0;color:#475569;">Sähköposti</td><td style="padding:4px 0;font-weight:600;">${email}</td></tr>
      ${company ? `<tr><td style="padding:4px 0;color:#475569;">Yritys</td><td style="padding:4px 0;font-weight:600;">${company}</td></tr>` : ""}
      <tr><td style="padding:4px 0;color:#475569;">Ajankohta</td><td style="padding:4px 0;font-weight:600;">${new Date().toLocaleString("fi-FI", { timeZone: "Europe/Helsinki" })}</td></tr>
    </table>
    ${
      report.fetchedOk
        ? `<table role="presentation" width="100%" cellpadding="0" cellspacing="0"><tr>${scoreBadge(
            "Hakukonenäkyvyys",
            report.seo.score,
          )}${scoreBadge("AI-näkyvyys", report.ai.score)}</tr></table>`
        : `<p style="color:#dc2626;">Sivustoa ei saatu analysoitua automaattisesti: ${report.fetchError}</p>`
    }
    <p style="color:#475569;font-size:14px;margin-top:20px;">Asiakas saa täyden raportin sähköpostiinsa automaattisesti n. 4 tunnin kuluttua. Ota yhteyttä liidiin mahdollisimman pian.</p>
  `;

  return { subject, html: baseWrapper("Sisäinen liidi-ilmoitus", body) };
}

export function customerConfirmationEmail(params: { url: string }): {
  subject: string;
  html: string;
} {
  const subject = `Kiitos! Analysoimme sivustosi ${params.url}`;
  const body = `
    <h1 style="font-size:20px;color:#0f172a;margin:0 0 16px;">Kiitos, testi on käynnissä!</h1>
    <p style="color:#334155;font-size:15px;line-height:1.6;">
      Aloitimme sivustosi <strong>${params.url}</strong> hakukone- ja AI-näkyvyyden analysoinnin.
      Saat täyden, konkreettisia suosituksia sisältävän raportin tähän sähköpostiin noin
      <strong>4 tunnin kuluessa</strong>.
    </p>
    <p style="color:#334155;font-size:15px;line-height:1.6;">
      Raportissa käymme läpi mm. sivustosi löydettävyyden Googlessa sekä näkyvyyden
      tekoälypohjaisissa hakupalveluissa kuten ChatGPT, Claude ja Perplexity.
    </p>
    <p style="color:#94a3b8;font-size:13px;margin-top:24px;">Jos et tilannut tätä testiä, voit jättää tämän viestin huomiotta.</p>
  `;
  return { subject, html: baseWrapper("Vahvistus", body) };
}

export function customerReportEmail(params: {
  url: string;
  report: VisibilityReport;
}): { subject: string; html: string } {
  const { url, report } = params;
  const subject = `Näkyvyysraporttisi on valmis - ${url}`;

  if (!report.fetchedOk) {
    const body = `
      <h1 style="font-size:20px;color:#0f172a;margin:0 0 16px;">Emme valitettavasti saaneet analysoitua sivustoasi</h1>
      <p style="color:#334155;font-size:15px;line-height:1.6;">${report.fetchError}</p>
      <p style="color:#334155;font-size:15px;line-height:1.6;">Vastaa tähän viestiin, niin autamme selvittämään syyn ja teemme analyysin manuaalisesti.</p>
    `;
    return { subject, html: baseWrapper("Raportti", body) };
  }

  const body = `
    <h1 style="font-size:20px;color:#0f172a;margin:0 0 8px;">Näkyvyysraporttisi</h1>
    <p style="color:#64748b;font-size:14px;margin:0 0 20px;">${url}</p>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f8fafc;border-radius:8px;margin-bottom:24px;">
      <tr>${scoreBadge("Hakukonenäkyvyys (SEO)", report.seo.score)}${scoreBadge(
        "AI-näkyvyys",
        report.ai.score,
      )}</tr>
    </table>

    <h2 style="font-size:16px;color:#0f172a;margin:24px 0 8px;">Hakukonenäkyvyys - ${report.seo.checks.filter((c) => c.passed).length}/${report.seo.checks.length} kunnossa</h2>
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0">${checklistRows(report.seo.checks)}</table>

    <h2 style="font-size:16px;color:#0f172a;margin:24px 0 8px;">AI-näkyvyys - ${report.ai.checks.filter((c) => c.passed).length}/${report.ai.checks.length} kunnossa</h2>
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0">${checklistRows(report.ai.checks)}</table>

    <div style="margin-top:32px;padding:20px;background:#0f172a;border-radius:8px;text-align:center;">
      <p style="color:#fff;font-size:15px;margin:0 0 12px;">Haluatko avun näiden korjaamiseen?</p>
      <p style="color:#94a3b8;font-size:13px;margin:0 0 16px;">Vastaa tähän viestiin, niin varataan lyhyt maksuton puhelu.</p>
    </div>
  `;

  return { subject, html: baseWrapper("Raportti", body) };
}
