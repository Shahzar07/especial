"use client";

import { useEffect, useMemo, useState } from "react";
import Image from "next/image";
import Link from "next/link";
import { useRouter } from "next/navigation";
import { useCart } from "./cart-provider";
import { formatPrice } from "@/lib/money";

type Quote = {
  subtotalCents: number;
  shippingCents: number;
  totalCents: number;
  freeShippingOverCents: number;
  error?: string;
};

type Fields = {
  email: string;
  name: string;
  line1: string;
  line2: string;
  city: string;
  postcode: string;
  country: string;
};

const EMPTY: Fields = {
  email: "",
  name: "",
  line1: "",
  line2: "",
  city: "",
  postcode: "",
  country: "",
};

const LABELS: Record<keyof Fields, string> = {
  email: "Email",
  name: "Full name",
  line1: "Address",
  line2: "Apartment, suite (optional)",
  city: "City",
  postcode: "Postcode",
  country: "Country",
};

const REQUIRED: (keyof Fields)[] = [
  "email",
  "name",
  "line1",
  "city",
  "postcode",
  "country",
];

/** Underline field, matching the gate. */
function Field({
  name,
  value,
  error,
  onChange,
}: {
  name: keyof Fields;
  value: string;
  error?: string;
  onChange: (v: string) => void;
}) {
  const id = `co-${name}`;
  return (
    <div>
      <label htmlFor={id} className="block text-xs text-ink-muted">
        {LABELS[name]}
      </label>
      <input
        id={id}
        name={name}
        type={name === "email" ? "email" : "text"}
        autoComplete={
          { email: "email", name: "name", line1: "address-line1", line2: "address-line2",
            city: "address-level2", postcode: "postal-code", country: "country-name" }[name]
        }
        value={value}
        aria-invalid={error ? true : undefined}
        aria-describedby={error ? `${id}-err` : undefined}
        onChange={(e) => onChange(e.target.value)}
        className="mt-2 w-full border-b border-rule bg-transparent py-2 text-base text-ink outline-none focus:border-ink"
      />
      <p id={`${id}-err`} className="mt-1 min-h-[1.1em] text-xs text-sale">
        {error}
      </p>
    </div>
  );
}

export function CheckoutForm() {
  const router = useRouter();
  const { lines, setQuantity, remove } = useCart();

  const [fields, setFields] = useState<Fields>(EMPTY);
  const [errors, setErrors] = useState<Partial<Record<keyof Fields, string>>>({});
  const [formError, setFormError] = useState<string | null>(null);
  const [busy, setBusy] = useState(false);
  const [quote, setQuote] = useState<Quote | null>(null);

  // Only slug/variant/quantity ever leave the browser; the server prices it.
  const payloadLines = useMemo(
    () => lines.map((l) => ({ slug: l.slug, variantId: l.variantId, quantity: l.quantity })),
    [lines],
  );

  useEffect(() => {
    let cancelled = false;
    (async () => {
      try {
        const res = await fetch("/api/checkout/quote", {
          method: "POST",
          headers: { "Content-Type": "application/json" },
          body: JSON.stringify({ lines: payloadLines }),
        });
        const data = (await res.json()) as Quote;
        if (!cancelled) setQuote(res.ok ? data : { ...data, error: data.error });
      } catch {
        if (!cancelled) setQuote(null);
      }
    })();
    return () => {
      cancelled = true;
    };
  }, [payloadLines]);

  function validate(): boolean {
    const next: Partial<Record<keyof Fields, string>> = {};
    for (const key of REQUIRED) {
      if (!fields[key].trim()) next[key] = "Required.";
    }
    if (fields.email.trim() && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(fields.email.trim())) {
      next.email = "Enter a valid email address.";
    }
    setErrors(next);
    return Object.keys(next).length === 0;
  }

  async function onSubmit(event: React.FormEvent<HTMLFormElement>) {
    event.preventDefault();
    if (busy || lines.length === 0) return;
    setFormError(null);
    if (!validate()) return;

    setBusy(true);
    try {
      const res = await fetch("/api/checkout", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ lines: payloadLines, customer: fields }),
      });
      const data = (await res.json()) as {
        redirect?: string;
        orderId?: string;
        error?: string;
        reason?: string;
      };

      if (!res.ok) {
        if (data.reason) console.error(`[checkout] ${data.reason}`);
        setFormError(data.error ?? "We could not place that order. Try again.");
        setBusy(false);
        return;
      }

      if (data.redirect) {
        window.location.assign(data.redirect); // hosted payment page
        return;
      }

      // Recorded without a payment provider. Hand the id to the confirmation
      // page, then clear the bag — in that order, so a failed navigation never
      // loses someone's order.
      const id = data.orderId ?? "";
      router.push(`/checkout/confirmed?order=${encodeURIComponent(id)}`);
      for (const line of lines) remove(line.key);
    } catch {
      setFormError("We could not place that order. Try again.");
      setBusy(false);
    }
  }

  if (lines.length === 0) {
    return (
      <div className="py-9">
        <p className="text-base text-ink">Your bag is empty.</p>
        <Link href="/keychains" className="link-underline mt-4 inline-block text-sm text-accent">
          See the newest collection
        </Link>
      </div>
    );
  }

  return (
    <form onSubmit={onSubmit} noValidate className="mt-8 grid gap-9 lg:grid-cols-12 lg:gap-[var(--column-gap)]">
      {/* Details */}
      <div className="lg:col-span-7">
        <h2 className="text-sm tracking-[var(--tracking-wide)] text-ink">Contact</h2>
        <div className="mt-4 grid gap-5 sm:grid-cols-2">
          <div className="sm:col-span-2">
            <Field name="email" value={fields.email} error={errors.email}
              onChange={(v) => setFields((f) => ({ ...f, email: v }))} />
          </div>
        </div>

        <h2 className="mt-8 text-sm tracking-[var(--tracking-wide)] text-ink">Shipping address</h2>
        <div className="mt-4 grid gap-5 sm:grid-cols-2">
          <div className="sm:col-span-2">
            <Field name="name" value={fields.name} error={errors.name}
              onChange={(v) => setFields((f) => ({ ...f, name: v }))} />
          </div>
          <div className="sm:col-span-2">
            <Field name="line1" value={fields.line1} error={errors.line1}
              onChange={(v) => setFields((f) => ({ ...f, line1: v }))} />
          </div>
          <div className="sm:col-span-2">
            <Field name="line2" value={fields.line2} onChange={(v) => setFields((f) => ({ ...f, line2: v }))} />
          </div>
          <Field name="city" value={fields.city} error={errors.city}
            onChange={(v) => setFields((f) => ({ ...f, city: v }))} />
          <Field name="postcode" value={fields.postcode} error={errors.postcode}
            onChange={(v) => setFields((f) => ({ ...f, postcode: v }))} />
          <div className="sm:col-span-2">
            <Field name="country" value={fields.country} error={errors.country}
              onChange={(v) => setFields((f) => ({ ...f, country: v }))} />
          </div>
        </div>
      </div>

      {/* Summary */}
      <div className="lg:col-span-5">
        <div className="border border-rule">
          <h2 className="border-b border-rule px-5 py-4 text-sm tracking-[var(--tracking-wide)] text-ink">
            Your bag
          </h2>

          <ul>
            {lines.map((line) => (
              <li key={line.key} className="flex gap-4 border-b border-rule px-5 py-4">
                <div className="relative aspect-[4/5] w-14 shrink-0 bg-wash">
                  {line.image && (
                    <Image src={line.image} alt="" aria-hidden fill sizes="56px" className="object-cover" />
                  )}
                </div>
                <div className="min-w-0 flex-1">
                  <p className="text-sm text-ink">{line.title}</p>
                  <p className="mt-1 text-xs text-ink-muted">{line.variantLabel}</p>
                  <div className="mt-2 flex items-center justify-between gap-3">
                    <div className="flex items-center border border-rule">
                      <button type="button" aria-label={`Decrease quantity of ${line.title}`}
                        onClick={() => setQuantity(line.key, line.quantity - 1)}
                        className="px-2 py-0.5 text-sm text-ink">−</button>
                      <span className="tabular min-w-7 text-center text-xs" aria-live="polite">{line.quantity}</span>
                      <button type="button" aria-label={`Increase quantity of ${line.title}`}
                        onClick={() => setQuantity(line.key, line.quantity + 1)}
                        className="px-2 py-0.5 text-sm text-ink">+</button>
                    </div>
                    <span className="tabular text-sm text-ink">
                      {formatPrice(line.priceCents * line.quantity)}
                    </span>
                  </div>
                </div>
              </li>
            ))}
          </ul>

          <dl className="px-5 py-4">
            <div className="flex items-center justify-between">
              <dt className="text-sm text-ink-muted">Subtotal</dt>
              <dd className="tabular text-sm text-ink">
                {quote ? formatPrice(quote.subtotalCents) : "—"}
              </dd>
            </div>
            <div className="mt-2 flex items-center justify-between">
              <dt className="text-sm text-ink-muted">Shipping</dt>
              <dd className="tabular text-sm text-ink">
                {!quote ? "—" : quote.shippingCents === 0 ? "Free" : formatPrice(quote.shippingCents)}
              </dd>
            </div>
            <div className="mt-4 flex items-center justify-between border-t border-rule pt-4">
              <dt className="text-sm text-ink">Total</dt>
              <dd className="tabular text-base text-ink">
                {quote ? formatPrice(quote.totalCents) : "—"}
              </dd>
            </div>
          </dl>

          {quote && quote.shippingCents > 0 && (
            <p className="px-5 pb-4 text-xs text-ink-muted">
              Free shipping over {formatPrice(quote.freeShippingOverCents)}.
            </p>
          )}
        </div>

        <p role="status" aria-live="polite" className="mt-3 min-h-[1.2em] text-xs text-sale">
          {formError ?? quote?.error}
        </p>

        <button
          type="submit"
          disabled={busy || Boolean(quote?.error)}
          className="mt-2 w-full bg-ink px-5 py-4 text-sm tracking-[var(--tracking-wide)] text-paper disabled:bg-wash disabled:text-ink-muted"
        >
          {busy ? "Placing order" : "Place order"}
        </button>

        <p className="mt-3 text-xs text-ink-muted">
          Card details are entered on the payment provider&rsquo;s own page. They
          never reach this site.
        </p>
      </div>
    </form>
  );
}
