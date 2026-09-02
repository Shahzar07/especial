import { NextResponse } from "next/server";
import { clientIp, rateLimit } from "@/lib/rate-limit";
import { CheckoutBody, placeOrder, priceOrder } from "@/lib/orders";

export const runtime = "nodejs";

const GENERIC = "We could not place that order. Try again.";

/**
 * Places an order.
 *
 * Wrapped so it always answers with JSON — the gate had exactly this fault,
 * where an unhandled throw produced an empty 500 the client could not parse.
 */
export async function POST(request: Request) {
  try {
    const ip = clientIp(request.headers);
    const limit = rateLimit(`checkout:${ip}`);
    if (!limit.ok) {
      return NextResponse.json(
        { error: "Too many attempts. Try again shortly." },
        { status: 429, headers: { "Retry-After": String(limit.retryAfter) } },
      );
    }

    let payload: unknown;
    try {
      payload = await request.json();
    } catch {
      return NextResponse.json({ error: GENERIC }, { status: 400 });
    }

    const parsed = CheckoutBody.safeParse(payload);
    if (!parsed.success) {
      return NextResponse.json(
        { error: "Check the details above and try again." },
        { status: 400 },
      );
    }

    // Prices come from the catalogue, never from the browser.
    const priced = priceOrder(parsed.data);
    if ("ok" in priced) {
      return NextResponse.json({ error: priced.error }, { status: 409 });
    }

    const origin = new URL(request.url).origin;
    const placed = await placeOrder(priced, parsed.data.customer, origin);

    return NextResponse.json(
      placed.kind === "redirect"
        ? { redirect: placed.url }
        : { orderId: placed.orderId, total: priced.totalCents },
    );
  } catch (error) {
    const reason = (error as Error).message;
    console.error(`[checkout] unhandled failure: ${reason}`);
    return NextResponse.json(
      {
        error: GENERIC,
        ...(process.env.NODE_ENV === "production" ? {} : { reason }),
      },
      { status: 500 },
    );
  }
}
