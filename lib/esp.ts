/**
 * Email service provider adapter.
 *
 * The BUILD SPEC left {{ESP}} unresolved, so this is written as a swappable
 * adapter rather than a hard dependency: pick the provider with ESP_PROVIDER
 * and the rest of the app never changes. Adding a provider means adding one
 * case below and nothing else.
 *
 * SERVER ONLY. Every key is read from process.env inside these functions and
 * no value here is ever serialised to the client.
 */
export type EspResult = { ok: true } | { ok: false; reason: string };

type Provider = "resend" | "klaviyo" | "mailchimp" | "none";

function provider(): Provider {
  const p = (process.env.ESP_PROVIDER ?? "none").toLowerCase();
  return p === "resend" || p === "klaviyo" || p === "mailchimp" ? p : "none";
}

async function resend(email: string): Promise<EspResult> {
  const key = process.env.RESEND_API_KEY;
  const audience = process.env.RESEND_AUDIENCE_ID;
  if (!key || !audience) return { ok: false, reason: "resend: missing config" };

  const res = await fetch(
    `https://api.resend.com/audiences/${audience}/contacts`,
    {
      method: "POST",
      headers: {
        Authorization: `Bearer ${key}`,
        "Content-Type": "application/json",
      },
      body: JSON.stringify({ email, unsubscribed: false }),
    },
  );
  return res.ok
    ? { ok: true }
    : { ok: false, reason: `resend: ${res.status} ${await res.text()}` };
}

async function klaviyo(email: string): Promise<EspResult> {
  const key = process.env.KLAVIYO_API_KEY;
  const listId = process.env.KLAVIYO_LIST_ID;
  if (!key || !listId) return { ok: false, reason: "klaviyo: missing config" };

  const res = await fetch(
    "https://a.klaviyo.com/api/profile-subscription-bulk-create-jobs",
    {
      method: "POST",
      headers: {
        Authorization: `Klaviyo-API-Key ${key}`,
        "Content-Type": "application/json",
        accept: "application/json",
        revision: "2024-10-15",
      },
      body: JSON.stringify({
        data: {
          type: "profile-subscription-bulk-create-job",
          attributes: {
            profiles: {
              data: [
                {
                  type: "profile",
                  attributes: {
                    email,
                    subscriptions: { email: { marketing: { consent: "SUBSCRIBED" } } },
                  },
                },
              ],
            },
          },
          relationships: { list: { data: { type: "list", id: listId } } },
        },
      }),
    },
  );
  return res.ok
    ? { ok: true }
    : { ok: false, reason: `klaviyo: ${res.status} ${await res.text()}` };
}

async function mailchimp(email: string): Promise<EspResult> {
  const key = process.env.MAILCHIMP_API_KEY;
  const listId = process.env.MAILCHIMP_LIST_ID;
  if (!key || !listId) return { ok: false, reason: "mailchimp: missing config" };

  // Mailchimp encodes the datacentre in the key suffix, e.g. "...-us21".
  const dc = key.split("-")[1];
  if (!dc) return { ok: false, reason: "mailchimp: key has no datacentre suffix" };

  const res = await fetch(
    `https://${dc}.api.mailchimp.com/3.0/lists/${listId}/members`,
    {
      method: "POST",
      headers: {
        Authorization: `Bearer ${key}`,
        "Content-Type": "application/json",
      },
      body: JSON.stringify({ email_address: email, status: "subscribed" }),
    },
  );
  return res.ok
    ? { ok: true }
    : { ok: false, reason: `mailchimp: ${res.status} ${await res.text()}` };
}

/**
 * Never throws. Spec §3.3: if the ESP call fails the visitor still gets in —
 * a broken webhook must not trap a paying customer at the door. The caller
 * logs the reason and proceeds.
 */
export async function subscribeToList(email: string): Promise<EspResult> {
  try {
    switch (provider()) {
      case "resend":
        return await resend(email);
      case "klaviyo":
        return await klaviyo(email);
      case "mailchimp":
        return await mailchimp(email);
      default:
        // Unconfigured: the gate still works end to end in dev.
        console.info(`[esp] no provider configured — would subscribe: ${email}`);
        return { ok: true };
    }
  } catch (err) {
    return { ok: false, reason: `esp threw: ${(err as Error).message}` };
  }
}
