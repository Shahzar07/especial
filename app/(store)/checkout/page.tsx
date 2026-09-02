import type { Metadata } from "next";
import Link from "next/link";

export const metadata: Metadata = {
  title: "Checkout",
  robots: { index: false, follow: false },
};

/**
 * Checkout handoff.
 *
 * {{COMMERCE}} was left unresolved in the spec, so v1 stops here rather than
 * pretending to take payment. Wire this page to the real provider by creating
 * the session server-side from the cart and redirecting:
 *
 *   Shopify  — cart/create, then redirect to `cart.checkoutUrl`
 *   Stripe   — checkout.sessions.create, then redirect to `session.url`
 *
 * The cart already carries slug, variantId, quantity and price, which is
 * everything either provider needs.
 */
export default function CheckoutPage() {
  return (
    <div className="mx-auto max-w-[var(--reading-width)] px-[var(--gutter)] py-10">
      <h1 className="display text-3xl text-ink">Checkout</h1>
      <p className="mt-6 text-base text-ink">
        Payment is not connected in this build. The bag, its line items and its
        subtotal are live — the next step is to hand them to a payment provider
        and redirect to the hosted checkout.
      </p>
      <p className="mt-4 text-base text-ink-muted">
        See the note at the top of <code>app/(store)/checkout/page.tsx</code> for
        the two-line handoff.
      </p>
      <Link href="/" className="link-underline mt-7 inline-block text-sm text-accent">
        Back to the store
      </Link>
    </div>
  );
}
