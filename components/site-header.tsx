"use client";

import Link from "next/link";
import { usePathname } from "next/navigation";
import { categories, site } from "@/lib/config";
import { useCart } from "./cart-provider";

/**
 * Hairline bar on paper. Never translucent, never blurred, never shadowed.
 * Sticky, and it does not change height or colour on scroll.
 */
export function SiteHeader() {
  const pathname = usePathname();
  const { count, open } = useCart();

  return (
    <header
      className="sticky top-0 z-[var(--z-header)] border-b border-rule bg-paper"
      style={{ height: "var(--header-height)" }}
    >
      <div className="mx-auto flex h-full max-w-[var(--max-width)] items-center justify-between gap-6 px-[var(--gutter)]">
        <Link
          href="/"
          className="link-underline shrink-0 text-xs font-medium tracking-[var(--tracking-wordmark)] text-ink sm:text-sm"
        >
          {site.wordmark}
        </Link>

        <nav aria-label="Categories" className="hidden md:block">
          <ul className="flex items-center gap-6">
            {categories.map((category) => {
              const href = `/${category.slug}`;
              return (
                <li key={category.slug}>
                  <Link
                    href={href}
                    data-active={pathname === href}
                    className="link-underline text-sm tracking-[var(--tracking-wide)] text-ink"
                  >
                    {category.title}
                  </Link>
                </li>
              );
            })}
          </ul>
        </nav>

        <button
          type="button"
          onClick={open}
          className="link-underline shrink-0 text-sm tracking-[var(--tracking-wide)] text-ink"
          aria-label={`Open bag, ${count} ${count === 1 ? "item" : "items"}`}
        >
          Bag <span className="tabular">({count})</span>
        </button>
      </div>
    </header>
  );
}
