/**
 * In-memory fixed-window rate limiter: 5 attempts / 10 min per IP (spec §3).
 *
 * Deliberately dependency-free and process-local. That is correct for a single
 * instance and for local dev; on a multi-instance deploy each instance keeps
 * its own counter, so swap the two functions below for Upstash/Redis before
 * scaling horizontally. The limiter is abuse dampening, not a security control.
 */
const WINDOW_MS = 10 * 60 * 1000;
const MAX_ATTEMPTS = 5;

type Entry = { count: number; resetAt: number };
const hits = new Map<string, Entry>();

/** Bound memory so a spray of unique IPs cannot grow the map without limit. */
function sweep(now: number) {
  if (hits.size < 10_000) return;
  for (const [k, v] of hits) if (v.resetAt <= now) hits.delete(k);
}

export function rateLimit(ip: string): {
  ok: boolean;
  remaining: number;
  retryAfter: number;
} {
  const now = Date.now();
  sweep(now);

  const entry = hits.get(ip);
  if (!entry || entry.resetAt <= now) {
    hits.set(ip, { count: 1, resetAt: now + WINDOW_MS });
    return { ok: true, remaining: MAX_ATTEMPTS - 1, retryAfter: 0 };
  }

  entry.count += 1;
  const ok = entry.count <= MAX_ATTEMPTS;
  return {
    ok,
    remaining: Math.max(0, MAX_ATTEMPTS - entry.count),
    retryAfter: ok ? 0 : Math.ceil((entry.resetAt - now) / 1000),
  };
}

/** Trust the left-most XFF hop; behind Vercel/Cloudflare this is the client. */
export function clientIp(headers: Headers): string {
  const xff = headers.get("x-forwarded-for");
  if (xff) return xff.split(",")[0]!.trim();
  return headers.get("x-real-ip") ?? "unknown";
}
