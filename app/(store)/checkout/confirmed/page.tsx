import type { Metadata } from "next";
import Link from "next/link";
import { site } from "@/lib/config";

export const metadata: Metadata = {
  title: "Order confirmed",
  robots: { index: false, follow: false },
};

export default async function ConfirmedPage({
  searchParams,
}: {
  searchParams: Promise<{ order?: string; session?: string }>;
}) {
  const params = await searchParams;
  // Shown back to the customer, so it is constrained rather than trusted.
  const reference = (params.order ?? params.session ?? "").replace(/[^A-Za-z0-9_-]/g, "").slice(0, 64);

  return (
    <div className="mx-auto max-w-[var(--reading-width)] px-[var(--gutter)] py-10">
      <h1 className="display text-4xl text-ink">Order confirmed</h1>

      <p className="mt-6 text-base text-ink">
        Thank you. A confirmation is on its way to the address you gave us.
      </p>

      {reference && (
        <dl className="mt-8 border-t border-rule">
          <div className="flex items-baseline justify-between gap-6 border-b border-rule py-4">
            <dt className="text-sm text-ink-muted">Reference</dt>
            <dd className="tabular text-sm text-ink">{reference}</dd>
          </div>
        </dl>
      )}

      <p className="mt-6 text-sm text-ink-muted">
        Orders ship within two working days, tracked. Anything to ask, write to{" "}
        <Link href="/contact" className="link-underline text-accent">
          {site.email}
        </Link>
        .
      </p>

      <Link href="/" className="link-underline mt-9 inline-block text-sm text-ink">
        Back to the store
      </Link>
    </div>
  );
}
