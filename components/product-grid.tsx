import { ProductCard } from "./product-card";
import type { Product } from "@/lib/types";

/**
 * The product grid.
 *
 * Columns come from a minimum tile width rather than a fixed count, so the
 * layout is driven by how wide a product should be rather than by how many
 * happen to exist. A fixed count breaks at both ends of the catalogue: two
 * products in a two-column grid rendered each one half the page wide and
 * roughly 900px tall, which is what a single item on a category page looked
 * like. This keeps a tile the same size whether the page holds one or twenty.
 */
export function ProductGrid({
  products,
  priorityCount = 0,
}: {
  products: Product[];
  priorityCount?: number;
}) {
  return (
    <div
      className="grid"
      style={{
        gap: "var(--grid-gap)",
        gridTemplateColumns: "repeat(auto-fill, minmax(min(100%, 260px), 1fr))",
      }}
    >
      {products.map((product, i) => (
        <ProductCard
          key={product.slug}
          product={product}
          sizes="(min-width: 1280px) 25vw, (min-width: 768px) 33vw, 50vw"
          priority={i < priorityCount}
        />
      ))}
    </div>
  );
}
