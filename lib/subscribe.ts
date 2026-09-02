import { z } from "zod";
import { subscribeToList } from "./esp";
import { rateLimit } from "./rate-limit";

export const EMAIL_ERROR = "Enter a valid email address.";
export const GENERIC_ERROR = "Something went wrong on our end. Try again.";
export const RATE_ERROR = "Too many attempts. Try again shortly.";

const Body = z.object({
  email: z.string().trim().toLowerCase().email().max(254),
  next: z.string().optional(),
});

export type SubscribeOutcome =
  | { ok: true; redirect: string }
  | { ok: false; status: number; error: string; retryAfter?: number };

/**
 * Only ever redirect to a path on this origin. Without this, /gate?next=
 * turns the storefront into an open redirect. Protocol-relative "//evil.com"
 * and "/\evil.com" are both rejected.
 */
export function safeNext(next: unknown): string {
  if (typeof next !== "string" || next.length === 0) return "/";
  if (!next.startsWith("/")) return "/";
  if (next.startsWith("//") || next.startsWith("/\\")) return "/";
  return next;
}

/**
 * The one implementation of the gate submission. Both the JSON route handler
 * and the no-JS server action call this, so the two paths cannot drift.
 */
export async function processSubscription(
  input: unknown,
  ip: string,
): Promise<SubscribeOutcome> {
  const limit = rateLimit(ip);
  if (!limit.ok) {
    return {
      ok: false,
      status: 429,
      error: RATE_ERROR,
      retryAfter: limit.retryAfter,
    };
  }

  // Server-side validation is the gate; the client check is UX only.
  const parsed = Body.safeParse(input);
  if (!parsed.success) {
    return { ok: false, status: 400, error: EMAIL_ERROR };
  }

  const { email, next } = parsed.data;

  // Spec §3.3 — a failed ESP write must never trap a customer at the door.
  const result = await subscribeToList(email);
  if (!result.ok) {
    console.error(
      `[subscribe] ESP write failed, admitting anyway: ${result.reason}`,
    );
  }

  return { ok: true, redirect: safeNext(next) };
}
