"use client";

import { useEffect, useRef } from "react";
import Image from "next/image";
import Link from "next/link";
import { formatPrice } from "@/lib/money";
import { useCart } from "./cart-provider";

/** Right-hand drawer, slides in over motion.duration.slow (520ms). */
export function CartDrawer() {
  const { lines, subtotalCents, isOpen, close, setQuantity, remove } = useCart();
  const panelRef = useRef<HTMLDivElement>(null);
  const closeRef = useRef<HTMLButtonElement>(null);

  // Escape closes; focus moves into the panel; the page behind cannot scroll.
  useEffect(() => {
    if (!isOpen) return;

    const onKeyDown = (event: KeyboardEvent) => {
      if (event.key === "Escape") {
        close();
        return;
      }
      if (event.key !== "Tab") return;

      // Trap focus inside the drawer while it is open.
      const focusable = panelRef.current?.querySelectorAll<HTMLElement>(
        'a[href], button:not([disabled]), input, [tabindex]:not([tabindex="-1"])',
      );
      if (!focusable?.length) return;
      const first = focusable[0]!;
      const last = focusable[focusable.length - 1]!;

      if (event.shiftKey && document.activeElement === first) {
        event.preventDefault();
        last.focus();
      } else if (!event.shiftKey && document.activeElement === last) {
        event.preventDefault();
        first.focus();
      }
    };

    document.addEventListener("keydown", onKeyDown);
    const previousOverflow = document.body.style.overflow;
    document.body.style.overflow = "hidden";
    closeRef.current?.focus();

    return () => {
      document.removeEventListener("keydown", onKeyDown);
      document.body.style.overflow = previousOverflow;
    };
  }, [isOpen, close]);

  return (
    <>
      <div
        onClick={close}
        aria-hidden
        className="fixed inset-0 z-[var(--z-scrim)] bg-ink/20 transition-opacity ease-[var(--ease-standard)]"
        style={{
          opacity: isOpen ? 1 : 0,
          pointerEvents: isOpen ? "auto" : "none",
          transitionDuration: "var(--duration-slow)",
        }}
      />

      <div
        ref={panelRef}
        role="dialog"
        aria-modal="true"
        aria-label="Bag"
        aria-hidden={!isOpen}
        inert={!isOpen}
        className="fixed right-0 top-0 z-[var(--z-drawer)] flex h-dvh w-full max-w-[420px] flex-col border-l border-rule bg-paper transition-transform ease-[var(--ease-standard)]"
        style={{
          transform: isOpen ? "translateX(0)" : "translateX(100%)",
          transitionDuration: "var(--duration-slow)",
        }}
      >
        <div className="flex items-center justify-between border-b border-rule px-5 py-4">
          <h2 className="text-sm tracking-[var(--tracking-wide)] text-ink">Bag</h2>
          <button
            ref={closeRef}
            type="button"
            onClick={close}
            className="link-underline text-sm text-ink"
          >
            Close
          </button>
        </div>

        {lines.length === 0 ? (
          <div className="flex flex-1 flex-col items-start justify-center gap-3 px-5">
            <p className="text-sm text-ink">Your bag is empty.</p>
            <Link href="/keychains" onClick={close} className="link-underline text-sm text-accent">
              See the newest collection
            </Link>
          </div>
        ) : (
          <>
            <ul className="flex-1 overflow-y-auto">
              {lines.map((line) => (
                <li key={line.key} className="flex gap-4 border-b border-rule px-5 py-4">
                  <Link
                    href={`/product/${line.slug}`}
                    onClick={close}
                    className="relative block aspect-[4/5] w-16 shrink-0 bg-wash"
                  >
                    {line.image && (
                      <Image
                        src={line.image}
                        alt=""
                        aria-hidden
                        fill
                        sizes="64px"
                        className="object-cover"
                      />
                    )}
                  </Link>

                  <div className="min-w-0 flex-1">
                    <Link
                      href={`/product/${line.slug}`}
                      onClick={close}
                      className="link-underline text-sm text-ink"
                    >
                      {line.title}
                    </Link>
                    <p className="mt-1 text-sm text-ink-muted">{line.variantLabel}</p>

                    <div className="mt-3 flex items-center justify-between gap-4">
                      <div className="flex items-center border border-rule">
                        <button
                          type="button"
                          onClick={() => setQuantity(line.key, line.quantity - 1)}
                          aria-label={`Decrease quantity of ${line.title}`}
                          className="px-3 py-1 text-sm text-ink"
                        >
                          −
                        </button>
                        <span className="tabular min-w-8 text-center text-sm" aria-live="polite">
                          {line.quantity}
                        </span>
                        <button
                          type="button"
                          onClick={() => setQuantity(line.key, line.quantity + 1)}
                          aria-label={`Increase quantity of ${line.title}`}
                          className="px-3 py-1 text-sm text-ink"
                        >
                          +
                        </button>
                      </div>

                      <span className="tabular text-sm text-ink">
                        {formatPrice(line.priceCents * line.quantity)}
                      </span>
                    </div>

                    <button
                      type="button"
                      onClick={() => remove(line.key)}
                      className="link-underline mt-3 text-xs text-ink-muted"
                    >
                      Remove
                    </button>
                  </div>
                </li>
              ))}
            </ul>

            <div className="border-t border-rule px-5 py-5">
              <div className="flex items-center justify-between">
                <span className="text-sm text-ink">Subtotal</span>
                <span className="tabular text-sm text-ink">{formatPrice(subtotalCents)}</span>
              </div>
              <p className="mt-2 text-xs text-ink-muted">
                Shipping and taxes calculated at checkout.
              </p>
              <Link
                href="/checkout"
                onClick={close}
                className="mt-4 block w-full bg-ink px-5 py-4 text-center text-sm tracking-[var(--tracking-wide)] text-paper"
              >
                Continue to checkout
              </Link>
            </div>
          </>
        )}
      </div>
    </>
  );
}
