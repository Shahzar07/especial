import Link from "next/link";
import { site } from "@/lib/config";

/**
 * Legal pages sit outside the gate (spec §3 matcher) so they are always
 * reachable — including from a payment provider or an email footer. They carry
 * a minimal wordmark bar rather than the full store chrome, because the cart
 * does not exist on this side of the gate.
 */
export function LegalPage({
  title,
  children,
}: {
  title: string;
  children: React.ReactNode;
}) {
  return (
    <>
      <header
        className="border-b border-rule"
        style={{ height: "var(--header-height)" }}
      >
        <div className="mx-auto flex h-full max-w-[var(--max-width)] items-center px-[var(--gutter)]">
          <Link
            href="/"
            className="link-underline text-xs font-medium tracking-[var(--tracking-wordmark)] text-ink sm:text-sm"
          >
            {site.wordmark}
          </Link>
        </div>
      </header>

      <main className="mx-auto px-[var(--gutter)] py-10" style={{ maxWidth: "calc(var(--reading-width) + 2 * var(--gutter))" }}>
        <h1 className="display text-3xl text-ink">{title}</h1>
        <div className="prose-editorial mt-7 space-y-5 text-base text-ink">
          {children}
        </div>
        <Link href="/" className="link-underline mt-9 inline-block text-sm text-accent">
          Back to the store
        </Link>
      </main>
    </>
  );
}
