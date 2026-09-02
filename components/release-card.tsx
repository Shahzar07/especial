import Image from "next/image";
import Link from "next/link";
import { categories } from "@/lib/config";
import { formatPrice } from "@/lib/money";
import { isSoldOut, type Product } from "@/lib/types";

/**
 * A release, as a wide hairline box: image on the left, the facts on the right.
 *
 * A short release used to run through the tall portrait grid, which at two
 * products meant two half-page tiles and a great deal of air. Laid on its side
 * the same information takes about a fifth of the height and the row reads as a
 * deliberate pair rather than a grid that failed to fill.
 */
export function ReleaseCard({ product }: { product: Product }) {
  const image = product.images[0];
  const soldOut = isSoldOut(product);
  const categoryTitle =
    categories.find((c) => c.slug === product.category)?.title ?? product.category;

  if (!image) return null;

  return (
    <Link
      href={`/product/${product.slug}`}
      className="group flex items-stretch border border-rule"
    >
      <div className="relative aspect-square w-[38%] shrink-0 bg-wash sm:w-[42%]">
        <Image
          src={image.src}
          alt={image.alt}
          fill
          sizes="(min-width: 1024px) 20vw, 40vw"
          className="object-cover"
        />
        {soldOut && (
          <div className="absolute inset-0 flex items-end bg-wash/80 p-3">
            <span className="text-xs text-ink">Sold out</span>
          </div>
        )}
      </div>

      <div className="flex min-w-0 flex-1 flex-col justify-center gap-1 px-5 py-4">
        <h3 className="text-sm text-ink">
          <span className="link-underline">{product.title}</span>
        </h3>
        <p className="text-sm text-ink-muted">{categoryTitle}</p>
        <p className="tabular mt-1 text-sm text-ink">
          {formatPrice(product.priceCents, product.currency)}
        </p>
      </div>
    </Link>
  );
}
