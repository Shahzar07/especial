import type { Metadata } from "next";
import Image from "next/image";
import { notFound } from "next/navigation";
import { Accordion } from "@/components/accordion";
import { PdpBuy } from "@/components/pdp-buy";
import { ProductGrid } from "@/components/product-grid";
import { SectionHeading } from "@/components/section-heading";
import { categories, site } from "@/lib/config";
import { getAllProducts, getProduct, getRelated } from "@/data/products";
import { isSoldOut } from "@/lib/types";

type Params = { slug: string };

export function generateStaticParams(): Params[] {
  return getAllProducts().map((p) => ({ slug: p.slug }));
}

export async function generateMetadata({
  params,
}: {
  params: Promise<Params>;
}): Promise<Metadata> {
  const { slug } = await params;
  const product = getProduct(slug);
  if (!product) return {};

  return {
    title: product.title,
    description: product.description,
    alternates: { canonical: `/product/${product.slug}` },
    openGraph: {
      title: `${product.title} — ${site.brand}`,
      description: product.description,
      images: product.images[0] ? [product.images[0].src] : undefined,
    },
  };
}

export default async function ProductPage({
  params,
}: {
  params: Promise<Params>;
}) {
  const { slug } = await params;
  const product = getProduct(slug);
  if (!product) notFound();

  const related = getRelated(slug, 4);
  const categoryTitle =
    categories.find((c) => c.slug === product.category)?.title ?? product.category;

  // Structured data — this is what makes the PDP eligible for rich results.
  const jsonLd = {
    "@context": "https://schema.org",
    "@type": "Product",
    name: product.title,
    description: product.description,
    image: product.images.map((i) => new URL(i.src, site.domain).toString()),
    brand: { "@type": "Brand", name: site.brand },
    offers: {
      "@type": "Offer",
      price: (product.priceCents / 100).toFixed(2),
      priceCurrency: product.currency,
      availability: isSoldOut(product)
        ? "https://schema.org/OutOfStock"
        : "https://schema.org/InStock",
      url: `${site.domain}/product/${product.slug}`,
    },
  };

  return (
    <div className="mx-auto max-w-[var(--max-width)] px-[var(--gutter)] py-8">
      <script
        type="application/ld+json"
        dangerouslySetInnerHTML={{ __html: JSON.stringify(jsonLd) }}
      />

      <div className="grid grid-cols-1 gap-8 lg:grid-cols-12 lg:gap-[var(--column-gap)]">
        {/* Image column — scrolls */}
        <div className="lg:col-span-7 xl:col-span-8">
          <div className="grid grid-cols-1 gap-2 sm:grid-cols-2">
            {product.images.map((image, i) => (
              <div
                key={image.src}
                className={`relative aspect-[4/5] bg-wash ${
                  product.images.length === 1 ? "sm:col-span-2" : ""
                }`}
              >
                <Image
                  src={image.src}
                  alt={image.alt}
                  fill
                  priority={i === 0}
                  sizes="(min-width: 1024px) 40vw, (min-width: 640px) 50vw, 100vw"
                  className="object-cover"
                />
              </div>
            ))}
          </div>
        </div>

        {/* Detail column — sticks */}
        <div className="lg:col-span-5 xl:col-span-4">
          <div className="lg:sticky lg:top-[calc(var(--header-height)+2rem)]">
            <p className="text-sm text-ink-muted">{categoryTitle}</p>
            <h1 className="mt-2 text-lg text-ink">{product.title}</h1>

            <PdpBuy product={product} />

            <p className="mt-6 text-sm text-ink-muted">{product.description}</p>

            <div className="mt-7">
              <Accordion title="Details" defaultOpen>
                <ul className="space-y-1">
                  {product.details.map((detail) => (
                    <li key={detail}>{detail}</li>
                  ))}
                </ul>
              </Accordion>
              <Accordion title="Shipping">
                Orders ship within two working days, tracked. Shipping is
                calculated at checkout.
              </Accordion>
              <Accordion title="Returns">
                Unopened items can be returned within thirty days of delivery.
                Opened collectibles can be returned if they arrived damaged.
              </Accordion>
            </div>
          </div>
        </div>
      </div>

      {related.length > 0 && (
        <section className="mt-11">
          <SectionHeading title="Related" />
          <div className="mt-6">
            <ProductGrid products={related} />
          </div>
        </section>
      )}
    </div>
  );
}
