import type { Metadata } from "next";
import { CheckoutForm } from "@/components/checkout-form";

export const metadata: Metadata = {
  title: "Checkout",
  robots: { index: false, follow: false },
};

export default function CheckoutPage() {
  return (
    <div className="mx-auto max-w-[var(--max-width)] px-[var(--gutter)] py-8">
      <h1 className="display text-3xl text-ink">Checkout</h1>
      <div className="mt-4 border-t border-rule" />
      <CheckoutForm />
    </div>
  );
}
