"use client";

import Link from "next/link";
import { usePathname } from "next/navigation";
import { categories, site } from "@/lib/config";
import { useCart } from "./cart-provider";
import { IconAccount, IconBag } from "./icons";

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
      <div className="mx-auto flex h-full max-w-[var(--max-width)] items-center justify-between gap-3 px-[var(--gutter)] sm:gap-6">
        <Link
          href="/"
          className="link-underline shrink-0 text-xs font-medium tracking-[0.14em] text-ink sm:text-sm sm:tracking-[var(--tracking-wordmark)]"
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

        {/*
          Padding, not size, carries the tap target. The icons stay 20px so they
          sit correctly beside 14px type, while each control measures 44px —
          the accessible minimum for a finger. The negative margin pulls the
          last one back to the gutter so the row still aligns to the page edge.
        */}
        <div className="flex shrink-0 items-center gap-1 sm:-mr-3">
          {/*
            There is no sign-in yet, so this points at the page that actually
            answers order questions rather than at a login that does not exist.
            When accounts arrive it is one href.
          */}
          <Link
            href="/contact"
            aria-label="Account and orders"
            title="Account and orders"
            className="flex items-center p-3 text-ink"
          >
            <IconAccount size={20} />
          </Link>

          <button
            type="button"
            onClick={open}
            title="Bag"
            className="flex items-center gap-2 p-3 text-ink"
            aria-label={`Open bag, ${count} ${count === 1 ? "item" : "items"}`}
          >
            <IconBag size={20} />
            {/*
              A count beside the icon rather than a badge on it: a badge is a
              filled circle, and nothing in this system is round or filled.
              The slot keeps its width so the header does not shift when the
              first item goes in.
            */}
            <span
              aria-hidden
              className="tabular inline-block min-w-[0.75ch] text-sm text-ink"
            >
              {count > 0 ? count : ""}
            </span>
          </button>
        </div>
      </div>
    </header>
  );
}
