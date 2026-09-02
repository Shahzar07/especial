import { NextResponse } from "next/server";
import { createGateToken } from "@/lib/cookie";
import { clientIp } from "@/lib/rate-limit";
import { processSubscription, GENERIC_ERROR } from "@/lib/subscribe";
import { GATE_COOKIE, GATE_MAX_AGE } from "@/lib/config";

export const runtime = "nodejs";

/**
 * JSON endpoint used by the enhanced (JS) gate form.
 *
 * Everything is wrapped so this route ALWAYS answers with JSON. An unhandled
 * throw here — a missing GATE_SECRET was the real case — produces a 500 with an
 * empty body, the client's res.json() then throws too, and the visitor is told
 * only "Something went wrong on our end" while the actual cause sits in a
 * server log nobody is watching. A misconfigured deployment should be obvious.
 */
export async function POST(request: Request) {
  try {
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
  } catch (error) {
    const reason = (error as Error).message;
    console.error(`[subscribe] unhandled failure: ${reason}`);

    // The reason is echoed outside production only. It names server
    // configuration, which is not a visitor's business and not safe to leak.
    return NextResponse.json(
      {
        error: GENERIC_ERROR,
        ...(process.env.NODE_ENV === "production" ? {} : { reason }),
      },
      { status: 500 },
    );
  }
}
