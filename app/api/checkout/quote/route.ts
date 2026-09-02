import { NextResponse } from "next/server";
import { z } from "zod";
import { priceOrder, SHIPPING_CENTS, FREE_SHIPPING_OVER_CENTS } from "@/lib/orders";

export const runtime = "nodejs";

const Body = z.object({
  lines: z
    .array(
      z.object({
        slug: z.string().min(1).max(120),
        variantId: z.string().min(1).max(120),
        quantity: z.number().int().min(1).max(20),
      }),
    )
    .max(40),
});

/**
 * Totals for the summary panel, computed from the catalogue.
 *
 * The page could add the numbers up itself, but then the figure a customer
 * reads and the figure they are charged come from two different places and are
 * free to drift. One source.
 */
export async function POST(request: Request) {
  try {
    const parsed = Body.safeParse(await request.json());
    if (!parsed.success) {
      return NextResponse.json({ error: "Bad request" }, { status: 400 });
    }

    const shape = {
      freeShippingOverCents: FREE_SHIPPING_OVER_CENTS,
      flatShippingCents: SHIPPING_CENTS,
    };

    if (parsed.data.lines.length === 0) {
      return NextResponse.json({
        lines: [],
        subtotalCents: 0,
        shippingCents: 0,
        totalCents: 0,
        currency: "USD",
        ...shape,
      });
    }

    const priced = priceOrder({
      lines: parsed.data.lines,
      customer: {
        email: "quote@example.com",
        name: "quote",
        line1: "quote",
        city: "quote",
        postcode: "quote",
        country: "quote",
      },
    });

    if ("ok" in priced) {
      return NextResponse.json({ error: priced.error }, { status: 409 });
    }

    return NextResponse.json({ ...priced, ...shape });
  } catch {
    return NextResponse.json({ error: "Bad request" }, { status: 400 });
  }
}
