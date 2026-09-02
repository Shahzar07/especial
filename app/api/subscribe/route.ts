import { NextResponse } from "next/server";
import { createGateToken } from "@/lib/cookie";
import { clientIp } from "@/lib/rate-limit";
import { processSubscription, GENERIC_ERROR } from "@/lib/subscribe";
import { GATE_COOKIE, GATE_MAX_AGE } from "@/lib/config";

export const runtime = "nodejs";

/** JSON endpoint used by the enhanced (JS) gate form. */
export async function POST(request: Request) {
  let payload: unknown;
  try {
    payload = await request.json();
  } catch {
    return NextResponse.json({ error: GENERIC_ERROR }, { status: 400 });
  }

  const outcome = await processSubscription(payload, clientIp(request.headers));

  if (!outcome.ok) {
    return NextResponse.json(
      { error: outcome.error },
      {
        status: outcome.status,
        ...(outcome.retryAfter
          ? { headers: { "Retry-After": String(outcome.retryAfter) } }
          : {}),
      },
    );
  }

  const response = NextResponse.json({ redirect: outcome.redirect });
  response.cookies.set(GATE_COOKIE, await createGateToken(), {
    httpOnly: true,
    secure: process.env.NODE_ENV === "production",
    sameSite: "lax",
    maxAge: GATE_MAX_AGE,
    path: "/",
  });
  return response;
}
