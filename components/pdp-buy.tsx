"use client";

import { useState } from "react";
import { useCart } from "./cart-provider";
import { formatPrice } from "@/lib/money";
import { isSoldOut, type Product } from "@/lib/types";

/**
 * Variant swatches + add to bag. Swatches are square with a 1px ink border
 * when selected — no fill, no radius, no accent colour (accent is reserved
 * for focus, active nav and inline links).
 */
export function PdpBuy({ product }: { product: Product }) {
  const { add } = useCart();
  const soldOut = isSoldOut(product);

  const firstAvailable = product.variants.find((v) => v.available);
  const [selected, setSelected] = useState(firstAvailable?.id ?? product.variants[0]?.id ?? "");

  const variant = product.variants.find((v) => v.id === selected);
  const canBuy = Boolean(variant?.available) && !soldOut;

  return (
    <div>
      <p className="mt-3 text-lg">
        {product.compareAtCents ? (
          <>
            <span className="tabular text-sale">
              {formatPrice(product.priceCents, product.currency)}
            </span>{" "}
            <span className="tabular text-ink-muted line-through">
              {formatPrice(product.compareAtCents, product.currency)}
            </span>
          </>
        ) : (
          <span className="tabular text-ink">
            {formatPrice(product.priceCents, product.currency)}
          </span>
        )}
      </p>

      {product.variants.length > 1 && (
        <fieldset className="mt-6">
          <legend className="text-xs text-ink-muted">Option</legend>
          <div className="mt-3 flex flex-wrap gap-2">
            {product.variants.map((v) => {
              const isSelected = v.id === selected;
              return (
                <button
                  key={v.id}
                  type="button"
                  onClick={() => setSelected(v.id)}
                  disabled={!v.available}
                  aria-pressed={isSelected}
                  className={`min-w-12 px-4 py-2 text-sm tracking-[var(--tracking-wide)] transition-colors duration-[var(--duration-fast)] ${
                    isSelected
                      ? "border border-ink text-ink"
                      : "border border-rule text-ink"
                  } ${!v.available ? "text-ink-faint line-through" : ""}`}
                >
                  {v.label}
                </button>
              );
            })}
          </div>
        </fieldset>
      )}

      <button
        type="button"
        disabled={!canBuy}
        onClick={() => variant && add(product, variant.id)}
        className="mt-6 w-full bg-ink px-5 py-4 text-sm tracking-[var(--tracking-wide)] text-paper disabled:bg-wash disabled:text-ink-muted"
      >
        {canBuy ? "Add to bag" : "Sold out"}
      </button>
    </div>
  );
}
