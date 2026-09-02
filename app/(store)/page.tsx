import Image from "next/image";
import Link from "next/link";
import { ProductCard } from "@/components/product-card";
import { SectionHeading } from "@/components/section-heading";
import { categories, site } from "@/lib/config";
import { getFeatured, lookbook } from "@/data/products";

export default function HomePage() {
  const releases = getFeatured(4);

  return (
    <>
      {/* 1 — Hero. One full-bleed 16:9 frame, a single line of type bottom-left,
             no CTA button. The whole thing is the link. */}
      <section>
        <Link href="/keychains" className="group relative block">
          <div className="relative aspect-[4/5] w-full sm:aspect-[16/9]">
            <Image
              src="/products/hero.jpg"
              alt=""
              aria-hidden
              fill
              priority
              sizes="100vw"
              className="object-cover"
            />
          </div>
          <div className="absolute bottom-0 left-0 p-[var(--gutter)]">
            <p className="display text-4xl text-ink">Skeleton Keychain</p>
            <p className="link-underline mt-2 inline-block text-sm text-ink">
              The current drop
            </p>
          </div>
        </Link>
      </section>

      <div className="mx-auto max-w-[var(--max-width)] px-[var(--gutter)]">
        {/* 2 — The release. Two objects, given room. */}
        <section className="mt-9">
          <SectionHeading title="Current release" />
          <div
            className="mt-6 grid grid-cols-1 sm:grid-cols-2"
            style={{ gap: "var(--grid-gap)" }}
          >
            {releases.map((product, i) => (
              <ProductCard
                key={product.slug}
                product={product}
                sizes="(min-width: 640px) 50vw, 100vw"
                priority={i === 0}
              />
            ))}
          </div>
        </section>

        {/* 3 — Category blocks */}
        <section className="mt-11">
          <SectionHeading title="Categories" />
          <div
            className="mt-6 grid grid-cols-1 sm:grid-cols-2"
            style={{ gap: "var(--grid-gap)" }}
          >
            {categories.map((category) => (
              <Link
                key={category.slug}
                href={`/${category.slug}`}
                className="group relative block"
              >
                <div className="relative aspect-[4/3] w-full bg-wash">
                  <Image
                    src={`/products/category-${category.slug}.jpg`}
                    alt=""
                    aria-hidden
                    fill
                    sizes="(min-width: 640px) 50vw, 100vw"
                    className="object-cover"
                  />
                </div>
                <h3 className="absolute bottom-0 left-0 p-5 text-sm tracking-[var(--tracking-wide)] text-ink">
                  <span className="link-underline">{category.title}</span>
                </h3>
              </Link>
            ))}
          </div>
        </section>

        {/* 4 — Lookbook. The greyscale campaign frames, no captions, no links.
               Purely the object, three times. */}
        <section className="mt-11">
          <SectionHeading title="Lookbook" />
          <div
            className="mt-6 grid grid-cols-2 sm:grid-cols-3"
            style={{ gap: "var(--grid-gap)" }}
          >
            {lookbook.map((frame, i) => (
              <div
                key={frame.src}
                className={`relative aspect-[4/5] bg-wash ${
                  i === 2 ? "hidden sm:block" : ""
                }`}
              >
                <Image
                  src={frame.src}
                  alt={frame.alt}
                  fill
                  sizes="(min-width: 640px) 33vw, 50vw"
                  className="object-cover"
                />
              </div>
            ))}
          </div>
        </section>

        {/* 5 — Editorial block. This is where the organic traffic lands, so it
               gets real hierarchy rather than footer treatment. */}
        <section className="mt-11">
          <div
            className="prose-editorial"
            style={{ maxWidth: "var(--reading-width)" }}
          >
            <h2 className="display text-4xl text-ink">
              Small runs, made properly
            </h2>

            <p className="mt-6 text-base text-ink">
              {site.brand} produces collectible objects in short runs. Every
              release is made to a fixed quantity, sold direct, and not
              reprinted. When a run is gone it stays gone, and the next one is a
              different object rather than the same one again in another colour.
            </p>

            <h3 className="display mt-8 text-3xl text-ink">How the drops work</h3>
            <p className="mt-4 text-base text-ink">
              Releases are announced to the mailing list first and go live on the
              site at the same time for everyone. There is no queue, no raffle
              and no reserved allocation. Stock is decremented at checkout, so
              what the grid shows is what is actually left. Items marked sold out
              are not restocked; if a run returns it is because the piece has been
              remade, and it is listed as a new release with its own run size.
            </p>

            <h3 className="display mt-8 text-3xl text-ink">Materials and making</h3>
            <p className="mt-4 text-base text-ink">
              Keychains are moulded in soft PVC from hand-built masters, printed
              on the face and left flat black on the reverse, then finished and
              assembled in small batches with nickel-plated hardware. Pins are
              struck in metal and filled with hard enamel, polished flat, and
              mounted on printed backing card. Each product page lists the
              material, the construction and the finished dimensions, and nothing
              else — the object should make its own argument.
            </p>

            <h3 className="display mt-8 text-3xl text-ink">
              Shipping and returns
            </h3>
            <p className="mt-4 text-base text-ink">
              Orders ship within two working days, tracked, from our own studio.
              Unopened items can be returned within thirty days of delivery for a
              full refund; opened collectibles can be returned if they arrived
              damaged. Full terms are on the{" "}
              <Link href="/returns">returns page</Link>, and anything not covered
              there is answered by writing to{" "}
              <Link href="/contact">{site.email}</Link>.
            </p>
          </div>
        </section>
      </div>
    </>
  );
}
