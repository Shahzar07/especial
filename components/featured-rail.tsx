import { ProductCard } from "./product-card";
import type { Product } from "@/lib/types";

/**
 * Four products. A row on desktop; a snapping horizontal scroller on mobile,
 * which is the one place the grid gives way to a rail.
 */
export function FeaturedRail({ products }: { products: Product[] }) {
  return (
    <div
      className="no-scrollbar -mx-[var(--gutter)] flex snap-x snap-mandatory overflow-x-auto px-[var(--gutter)] md:mx-0 md:grid md:grid-cols-4 md:overflow-visible md:px-0"
      style={{ gap: "var(--grid-gap)" }}
    >
      {products.map((product) => (
        <div
          key={product.slug}
          className="w-[62vw] shrink-0 snap-start sm:w-[40vw] md:w-auto"
        >
          <ProductCard
            product={product}
            sizes="(min-width: 768px) 25vw, 62vw"
          />
        </div>
      ))}
    </div>
  );
}
