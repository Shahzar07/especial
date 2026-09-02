/**
 * Gate cookie signing.
 *
 * Uses Web Crypto HMAC-SHA256 so the same code runs in middleware (Edge
 * runtime) and in the route handler. Value format: `<issuedAt>.<signature>`.
 * The payload is only a timestamp — the cookie asserts "this browser passed
 * the gate", nothing more, so there is no PII in it.
 */
import { GATE_MAX_AGE } from "./config";

/**
 * Signing key for the gate cookie.
 *
 * A real deployment sets GATE_SECRET and this uses it. When it is absent the
 * gate does NOT break: it falls back to a fixed value below and warns once.
 *
 * That is a deliberate trade. This cookie is a mailing-list capture, not an
 * access control — the middleware already waves every search and social
 * crawler straight through on a spoofable User-Agent, and the spec is explicit
 * that nothing sensitive may sit behind it. Against that threat model, a
 * forgeable cookie is a far smaller problem than a storefront whose front door
 * returns a 500 to every customer because one environment variable is missing.
 *
 * An earlier version threw instead, and a bootstrap script wired to npm's
 * predev/prestart hooks was supposed to prevent that. It does not survive
 * contact with reality: pnpm and yarn do not run those hooks, and neither does
 * a Docker CMD, a platform that runs `next start` itself, or anyone invoking
 * `next dev` directly. The signing path has to stand on its own.
 */
const UNCONFIGURED_FALLBACK =
  "especial-gallery-unconfigured-gate-key-set-GATE_SECRET-in-your-environment";

let warned = false;

function secret(): string {
  const configured = process.env.GATE_SECRET;
  if (configured && configured.length >= 16) return configured;

  if (!warned) {
    warned = true;
    console.warn(
      [
        "",
        "  [gate] GATE_SECRET is not set (or is shorter than 16 characters).",
        "         The gate still works, but the cookie is signed with a public",
        "         value from the source, so its signature proves nothing and",
        "         anyone could forge it.",
        "",
        "         Fix: set GATE_SECRET in the environment, or run `npm run setup`",
        "         locally to generate one.",
        "",
      ].join("\n"),
    );
  }
  return UNCONFIGURED_FALLBACK;
}

async function key(): Promise<CryptoKey> {
  return crypto.subtle.importKey(
    "raw",
    new TextEncoder().encode(secret()),
    { name: "HMAC", hash: "SHA-256" },
    false,
    ["sign"],
  );
}

function toHex(buf: ArrayBuffer): string {
  return Array.from(new Uint8Array(buf))
    .map((b) => b.toString(16).padStart(2, "0"))
    .join("");
}

async function sign(payload: string): Promise<string> {
  const sig = await crypto.subtle.sign(
    "HMAC",
    await key(),
    new TextEncoder().encode(payload),
  );
  return toHex(sig);
}

/** Constant-time string compare — avoids leaking the signature via timing. */
function safeEqual(a: string, b: string): boolean {
  if (a.length !== b.length) return false;
  let diff = 0;
  for (let i = 0; i < a.length; i++) diff |= a.charCodeAt(i) ^ b.charCodeAt(i);
  return diff === 0;
}

export async function createGateToken(): Promise<string> {
  const issued = Date.now().toString();
  return `${issued}.${await sign(issued)}`;
}

export async function verifyGateToken(
  token: string | undefined,
): Promise<boolean> {
  if (!token) return false;
  const idx = token.lastIndexOf(".");
  if (idx <= 0) return false;

  const issued = token.slice(0, idx);
  const provided = token.slice(idx + 1);
  if (!/^\d+$/.test(issued)) return false;

  // Reject an expired token even if the browser kept sending it.
  const age = (Date.now() - Number(issued)) / 1000;
  if (age < 0 || age > GATE_MAX_AGE) return false;

  return safeEqual(provided, await sign(issued));
}
