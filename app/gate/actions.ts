"use server";

import { cookies, headers } from "next/headers";
import { redirect } from "next/navigation";
import { createGateToken } from "@/lib/cookie";
import { clientIp } from "@/lib/rate-limit";
import { processSubscription } from "@/lib/subscribe";
import { GATE_COOKIE, GATE_MAX_AGE } from "@/lib/config";

export type GateState = { error?: string };

/**
 * No-JS submission path. Next renders <form action={serverAction}> as a real
 * form POST when JavaScript has not loaded, so the gate — and crucially its
 * validation — still works with scripting disabled (acceptance criteria §9).
 *
 * Shares processSubscription() with /api/subscribe so the two cannot drift.
 */
export async function submitGate(
  _prev: GateState,
  formData: FormData,
): Promise<GateState> {
  const outcome = await processSubscription(
    { email: formData.get("email"), next: formData.get("next") ?? undefined },
    clientIp(await headers()),
  );

  if (!outcome.ok) {
    return { error: outcome.error };
  }

  (await cookies()).set(GATE_COOKIE, await createGateToken(), {
    httpOnly: true,
    secure: process.env.NODE_ENV === "production",
    sameSite: "lax",
    maxAge: GATE_MAX_AGE,
    path: "/",
  });

  redirect(outcome.redirect);
}
