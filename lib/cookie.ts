/**
 * Gate cookie signing.
 *
 * Uses Web Crypto HMAC-SHA256 so the same code runs in middleware (Edge
 * runtime) and in the route handler. Value format: `<issuedAt>.<signature>`.
 * The payload is only a timestamp — the cookie asserts "this browser passed
 * the gate", nothing more, so there is no PII in it.
 */
import { GATE_MAX_AGE } from "./config";

function secret(): string {
  const s = process.env.GATE_SECRET;
  if (!s || s.length < 16) {
    throw new Error(
      "GATE_SECRET is missing or shorter than 16 characters, so the gate " +
        "cookie cannot be signed. Run `npm run setup` to generate one for " +
        "local development, or set GATE_SECRET in the deployment environment.",
    );
  }
  return s;
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
