import { ProductCard } from "./product-card";
import type { Product } from "@/lib/types";

/** 2-up mobile, 3-up tablet, 4-up desktop — layout.grid.products. */
export function ProductGrid({
  products,
  priorityCount = 0,
}: {
  products: Product[];
  priorityCount?: number;
}) {
  return (
    <div
      className="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4"
      style={{ gap: "var(--grid-gap)" }}
    >
      {products.map((product, i) => (
        <ProductCard
          key={product.slug}
          product={product}
          priority={i < priorityCount}
        />
      ))}
    </div>
  );
}
