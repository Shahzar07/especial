import Link from "next/link";
import { categories, site } from "@/lib/config";
import { NewsletterInline } from "./newsletter-inline";

const COLUMNS = [
  {
    title: "Shop",
    links: categories.map((c) => ({ href: `/${c.slug}`, label: c.title })),
  },
  {
    title: "Help",
    links: [
      { href: "/contact", label: "Contact" },
      { href: "/returns", label: "Returns" },
      { href: "/contact", label: "Order status" },
    ],
  },
  {
    title: "Legal",
    links: [
      { href: "/terms", label: "Terms" },
      { href: "/privacy", label: "Privacy" },
    ],
  },
] as const;

export function SiteFooter() {
  return (
    <footer className="mt-11 border-t border-rule">
      <div className="mx-auto max-w-[var(--max-width)] px-[var(--gutter)] py-8">
        <div className="grid grid-cols-2 gap-8 md:grid-cols-4">
          {COLUMNS.map((column) => (
            <div key={column.title}>
              <h2 className="text-xs text-ink-muted">{column.title}</h2>
              <ul className="mt-4 space-y-2">
                {column.links.map((link, i) => (
                  <li key={`${link.href}-${i}`}>
                    <Link href={link.href} className="link-underline text-sm text-ink">
                      {link.label}
                    </Link>
                  </li>
                ))}
              </ul>
            </div>
          ))}

          <div>
            <h2 className="text-xs text-ink-muted">Mailing list</h2>
            <p className="mt-4 max-w-[32ch] text-sm text-ink-muted">
              Drop announcements and restocks. No more than twice a month.
            </p>
            <div className="mt-4">
              <NewsletterInline />
            </div>
          </div>
        </div>

        <div className="mt-10 flex flex-col gap-6 border-t border-rule pt-6 md:flex-row md:items-center md:justify-between">
          <p className="text-xs tracking-[var(--tracking-wordmark)] text-ink">
            {site.wordmark}
          </p>

          {/* Payment marks — wordmarks, not logos, so nothing is hotlinked. */}
          <ul className="flex flex-wrap items-center gap-x-4 gap-y-2" aria-label="Accepted payment methods">
            {["Visa", "Mastercard", "Amex", "PayPal", "Apple Pay"].map((mark) => (
              <li key={mark} className="text-xs text-ink-muted">
                {mark}
              </li>
            ))}
          </ul>

          <p className="text-xs text-ink-muted">
            © {new Date().getFullYear()} {site.brand}
          </p>
        </div>
      </div>
    </footer>
  );
}
