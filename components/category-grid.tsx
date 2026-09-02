import Image from "next/image";
import Link from "next/link";
import { categories } from "@/lib/config";
import { getProductsByCategory } from "@/data/products";

/**
 * Category cells.
 *
 * Compact, and on the same column rhythm as the product grid, so the two read
 * as one page rather than two unrelated layouts. Each cell is a hairline box
 * with a square image over a ruled caption — the same construction as the
 * feature blocks, which is what keeps the page's vocabulary consistent.
 *
 * These were full-width 4:3 panels with the label floating over the image, and
 * at two categories that meant two enormous mostly-empty rectangles.
 */
export function CategoryGrid() {
  // With only a few categories, a full-width auto-fill row leaves empty tracks
  // trailing off to the right. Capping the row to the number of cells keeps
  // them small and the row complete, and the cap lifts on its own once there
  // are enough categories to fill the width.
  const cap = categories.length <= 3
    ? `calc(${categories.length} * 340px + ${categories.length - 1} * var(--grid-gap))`
    : undefined;

  return (
    <div
      className="grid"
      style={{
        gap: "var(--grid-gap)",
        gridTemplateColumns: "repeat(auto-fill, minmax(min(100%, 260px), 1fr))",
        maxWidth: cap,
      }}
    >
      {categories.map((category) => {
        const count = getProductsByCategory(category.slug).length;
        return (
          <Link
            key={category.slug}
            href={`/${category.slug}`}
            className="group block border border-rule"
          >
            <div className="relative aspect-square w-full bg-wash">
              <Image
                src={`/products/category-${category.slug}.jpg`}
                alt=""
                aria-hidden
                fill
                sizes="(min-width: 1280px) 25vw, (min-width: 768px) 33vw, 50vw"
                className="object-cover"
              />
            </div>
            <div className="flex items-baseline justify-between gap-4 border-t border-rule px-4 py-3">
              <span className="link-underline text-sm text-ink">
                {category.title}
              </span>
              <span className="tabular text-xs text-ink-muted">
                {count} {count === 1 ? "item" : "items"}
              </span>
            </div>
          </Link>
        );
      })}
    </div>
  );
}
