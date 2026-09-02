import Image from "next/image";
import Link from "next/link";
import { categories } from "@/lib/config";
import { formatPrice } from "@/lib/money";
import { isSoldOut, type Product } from "@/lib/types";

/**
 * Product tile. Image, title, category, price — nothing else. No wishlist,
 * no quick-view, no rating, no badge row (spec §5).
 *
 * Hover crossfades to the second image. The element never moves.
 */
export function ProductCard({
  product,
  sizes = "(min-width: 1024px) 25vw, (min-width: 768px) 33vw, 50vw",
  priority = false,
}: {
  product: Product;
  sizes?: string;
  priority?: boolean;
}) {
  const [primary, secondary] = product.images;
  const soldOut = isSoldOut(product);
  const categoryTitle =
    categories.find((c) => c.slug === product.category)?.title ?? product.category;

  if (!primary) return null;

  return (
    <article>
      <Link href={`/product/${product.slug}`} className="group block">
        <div className="relative aspect-[4/5] overflow-hidden bg-wash">
          <Image
            src={primary.src}
            alt={primary.alt}
            fill
            sizes={sizes}
            priority={priority}
            className="object-cover transition-opacity duration-[var(--duration-base)] ease-[var(--ease-standard)] group-hover:opacity-0"
          />
          {secondary && (
            <Image
              src={secondary.src}
              alt=""
              aria-hidden
              fill
              sizes={sizes}
              className="object-cover opacity-0 transition-opacity duration-[var(--duration-base)] ease-[var(--ease-standard)] group-hover:opacity-100"
            />
          )}

          {soldOut && (
            <div className="absolute inset-0 flex items-end bg-wash/80 p-3">
              <span className="text-xs text-ink">Sold out</span>
            </div>
          )}
        </div>

        <div className="mt-3">
          <h3 className="text-sm text-ink">{product.title}</h3>
          <p className="mt-1 text-sm text-ink-muted">{categoryTitle}</p>
          <p className="mt-1 text-sm">
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
        </div>
      </Link>
    </article>
  );
}
