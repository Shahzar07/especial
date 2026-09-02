import { ProductCard } from "./product-card";
import type { Product } from "@/lib/types";

/**
 * 2-up mobile, 3-up tablet, 4-up desktop — layout.grid.products.
 *
 * A short set drops to 2-up on desktop instead: four columns holding two
 * objects reads as a page that failed to load, not as restraint.
 */
export function ProductGrid({
  products,
  priorityCount = 0,
}: {
  products: Product[];
  priorityCount?: number;
}) {
  const short = products.length <= 2;

  return (
    <div
      className={
        short
          ? "grid grid-cols-1 sm:grid-cols-2"
          : "grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4"
      }
      style={{ gap: "var(--grid-gap)" }}
    >
      {products.map((product, i) => (
        <ProductCard
          key={product.slug}
          product={product}
          sizes={
            short
              ? "(min-width: 640px) 50vw, 100vw"
              : "(min-width: 1024px) 25vw, (min-width: 768px) 33vw, 50vw"
          }
          priority={i < priorityCount}
        />
      ))}
    </div>
  );
}
