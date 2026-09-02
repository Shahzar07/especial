import { z } from "zod";
import { getProduct } from "@/data/products";

/** Flat rate until a carrier is wired in. Minor units. */
export const SHIPPING_CENTS = 600;
export const FREE_SHIPPING_OVER_CENTS = 7500;

export const CheckoutBody = z.object({
  lines: z
    .array(
      z.object({
        slug: z.string().min(1).max(120),
        variantId: z.string().min(1).max(120),
        quantity: z.number().int().min(1).max(20),
      }),
    )
    .min(1)
    .max(40),
  customer: z.object({
    email: z.string().trim().toLowerCase().email().max(254),
    name: z.string().trim().min(1).max(120),
    line1: z.string().trim().min(1).max(200),
    line2: z.string().trim().max(200).optional().or(z.literal("")),
    city: z.string().trim().min(1).max(120),
    postcode: z.string().trim().min(1).max(40),
    country: z.string().trim().min(2).max(60),
  }),
});

export type CheckoutInput = z.infer<typeof CheckoutBody>;

export type PricedLine = {
  slug: string;
  variantId: string;
  title: string;
  variantLabel: string;
  quantity: number;
  unitCents: number;
  totalCents: number;
};

export type PricedOrder = {
  lines: PricedLine[];
  subtotalCents: number;
  shippingCents: number;
  totalCents: number;
  currency: "USD";
};

export type PriceFailure = { ok: false; error: string };

/**
 * Rebuilds the order from the catalogue.
 *
 * The client is never trusted for money. The browser sends slugs, variants and
 * quantities only; every price, title and total is looked up and recomputed
 * here. Sending a price from the cart would let anyone set their own.
 *
 * It also re-checks availability, because a variant can sell out between the
 * moment it went into someone's bag and the moment they check out.
 */
export function priceOrder(input: CheckoutInput): PricedOrder | PriceFailure {
  const lines: PricedLine[] = [];

  for (const line of input.lines) {
    const product = getProduct(line.slug);
    if (!product) {
      return { ok: false, error: "Something in your bag is no longer available." };
    }

    const variant = product.variants.find((v) => v.id === line.variantId);
    if (!variant) {
      return { ok: false, error: `${product.title} is no longer offered in that option.` };
    }
    if (!variant.available) {
      return { ok: false, error: `${product.title} (${variant.label}) has sold out.` };
    }

    lines.push({
      slug: product.slug,
      variantId: variant.id,
      title: product.title,
      variantLabel: variant.label,
      quantity: line.quantity,
      unitCents: product.priceCents,
      totalCents: product.priceCents * line.quantity,
    });
  }

  const subtotalCents = lines.reduce((n, l) => n + l.totalCents, 0);
  const shippingCents = subtotalCents >= FREE_SHIPPING_OVER_CENTS ? 0 : SHIPPING_CENTS;

  return {
    lines,
    subtotalCents,
    shippingCents,
    totalCents: subtotalCents + shippingCents,
    currency: "USD",
  };
}

/* ── payment adapter ──────────────────────────────────────────────────────── */

export type Placed =
  | { kind: "redirect"; url: string }
  | { kind: "recorded"; orderId: string };

function orderId(): string {
  // Short, unambiguous, no look-alike characters.
  const alphabet = "23456789ABCDEFGHJKLMNPQRSTUVWXYZ";
  const bytes = crypto.getRandomValues(new Uint8Array(8));
  return "EG-" + Array.from(bytes, (b) => alphabet[b % alphabet.length]).join("");
}

/**
 * Hands the order to a payment provider.
 *
 * Set STRIPE_SECRET_KEY and this creates a real Stripe Checkout Session and
 * returns its URL. With no key the order is recorded and acknowledged instead,
 * so the whole flow — validation, pricing, stock, confirmation — is testable
 * and demonstrable before any account exists. Nothing here ever touches card
 * details; that is the provider's job on their own page.
 */
export async function placeOrder(
  order: PricedOrder,
  customer: CheckoutInput["customer"],
  origin: string,
): Promise<Placed> {
  const key = process.env.STRIPE_SECRET_KEY;

  if (!key) {
    console.info(
      `[order] recorded (no payment provider configured): ${JSON.stringify({
        email: customer.email,
        total: order.totalCents,
        lines: order.lines.map((l) => `${l.quantity}x ${l.title} (${l.variantLabel})`),
      })}`,
    );
    return { kind: "recorded", orderId: orderId() };
  }

  const form = new URLSearchParams();
  form.set("mode", "payment");
  form.set("success_url", `${origin}/checkout/confirmed?session={CHECKOUT_SESSION_ID}`);
  form.set("cancel_url", `${origin}/checkout`);
  form.set("customer_email", customer.email);

  order.lines.forEach((line, i) => {
    form.set(`line_items[${i}][quantity]`, String(line.quantity));
    form.set(`line_items[${i}][price_data][currency]`, order.currency.toLowerCase());
    form.set(`line_items[${i}][price_data][unit_amount]`, String(line.unitCents));
    form.set(
      `line_items[${i}][price_data][product_data][name]`,
      `${line.title} — ${line.variantLabel}`,
    );
  });

  if (order.shippingCents > 0) {
    const i = order.lines.length;
    form.set(`line_items[${i}][quantity]`, "1");
    form.set(`line_items[${i}][price_data][currency]`, order.currency.toLowerCase());
    form.set(`line_items[${i}][price_data][unit_amount]`, String(order.shippingCents));
    form.set(`line_items[${i}][price_data][product_data][name]`, "Shipping");
  }

  const res = await fetch("https://api.stripe.com/v1/checkout/sessions", {
    method: "POST",
    headers: {
      Authorization: `Bearer ${key}`,
      "Content-Type": "application/x-www-form-urlencoded",
    },
    body: form,
  });

  if (!res.ok) {
    throw new Error(`stripe: ${res.status} ${await res.text()}`);
  }

  const session = (await res.json()) as { url?: string };
  if (!session.url) throw new Error("stripe: session created without a url");
  return { kind: "redirect", url: session.url };
}
