import Image from "next/image";
import Link from "next/link";
import { ProductCard } from "@/components/product-card";
import { SectionHeading } from "@/components/section-heading";
import { FeatureGrid, SpecTable, type Feature } from "@/components/feature-grid";
import {
  IconDirect,
  IconDrop,
  IconEdition,
  IconHand,
  IconMail,
  IconMaterial,
  IconReturn,
  IconShip,
} from "@/components/icons";
import { categories, site } from "@/lib/config";
import { getFeatured, lookbook } from "@/data/products";
import banners from "@/data/banners.json";

const HOW_WE_WORK: Feature[] = [
  {
    icon: <IconEdition />,
    title: "Fixed runs",
    body: "Every release is made to a set quantity and not reprinted. When a run is gone it stays gone.",
  },
  {
    icon: <IconDrop />,
    title: "One drop time",
    body: "Releases go live for everyone at once. No queue, no raffle, no reserved allocation.",
  },
  {
    icon: <IconDirect />,
    title: "Sold direct",
    body: "Straight from the studio to you. No resellers, no distributors, no marked-up middle.",
  },
  {
    icon: <IconHand />,
    title: "Finished by hand",
    body: "Moulded and struck in small batches, then checked and assembled one at a time.",
  },
];

const BUYING: Feature[] = [
  {
    icon: <IconShip />,
    title: "Ships in two days",
    body: "Tracked from our own studio. Shipping is calculated at checkout.",
  },
  {
    icon: <IconReturn />,
    title: "Thirty-day returns",
    body: "Unopened items come back for a full refund. Opened ones if they arrived damaged.",
  },
  {
    icon: <IconMaterial />,
    title: "Materials listed",
    body: "Every product page states what it is made of and how it was made. Nothing else.",
  },
  {
    icon: <IconMail />,
    title: "List gets it first",
    body: "Drops are announced to the mailing list before they appear anywhere else.",
  },
];

const SPECS: [string, string][] = [
  ["Keychain", "Soft moulded PVC, two-sided, raised outline, flat black reverse"],
  ["Keychain hardware", "Nickel-plated split ring and short chain"],
  ["Pin", "Hard enamel, polished flat, black metal plating"],
  ["Pin fixing", "Single post with a butterfly clutch"],
  ["Packing", "Sealed polybag; the pin on a printed backing card"],
];

export default function HomePage() {
  const releases = getFeatured(4);

  return (
    <>
      {/* 1 — Hero.
             Dark artwork, so every mark set over it is paper rather than ink.
             Laid out from the artwork's real dimensions in data/banners.json,
             so it is never cropped to fit a fixed container — swapping in
             artwork of a different shape needs no change here.
             Art-directed: the portrait crop on mobile carries the type on a
             continuous ink band beneath it, because overlaying it ran the
             headline straight across the object; the wide crop on desktop keeps
             its left side quiet and the type sits in it. */}
      <section className="bg-ink">
        <Link
          href="/product/skeleton-keychain"
          className="group relative block focus-visible:outline-offset-[-4px]"
        >
          <div className="sm:hidden">
            <Image
              src={banners.heroMobile.src}
              alt=""
              aria-hidden
              width={banners.heroMobile.width}
              height={banners.heroMobile.height}
              priority
              sizes="100vw"
              className="h-auto w-full"
            />
          </div>
          <div className="hidden sm:block">
            <Image
              src={banners.hero.src}
              alt=""
              aria-hidden
              width={banners.hero.width}
              height={banners.hero.height}
              priority
              sizes="100vw"
              className="h-auto w-full"
            />
          </div>

          <div className="sm:absolute sm:inset-y-0 sm:left-0 sm:flex sm:items-center">
            <div className="mx-auto w-full max-w-[var(--max-width)] px-[var(--gutter)] pb-8 sm:pb-0">
              <div className="sm:max-w-[42%]">
                <p className="text-xs text-paper/60">Current release</p>
                <h1 className="display mt-2 text-5xl text-paper">
                  Skeleton Keychain
                </h1>

                <div className="mt-5 h-px w-full max-w-[380px] bg-paper/40" />

                <div className="mt-4 flex flex-wrap gap-x-7 gap-y-1">
                  <span className="text-sm text-paper/60">Soft PVC</span>
                  <span className="text-sm text-paper/60">Nickel hardware</span>
                  <span className="text-sm text-paper/60">Sealed polybag</span>
                </div>

                <span className="link-underline mt-5 inline-block text-sm text-paper">
                  See the object
                </span>
              </div>
            </div>
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

        {/* 3 — How the studio works, as a ruled grid rather than prose. */}
        <section className="mt-11">
          <SectionHeading title="How we work" />
          <div className="mt-6">
            <FeatureGrid features={HOW_WE_WORK} />
          </div>
        </section>

        {/* 4 — Category blocks */}
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

        {/* 5 — Lookbook. The greyscale campaign frames, no captions, no links. */}
        <section className="mt-11">
          <SectionHeading title="Lookbook" />
          <div
            className="mt-6 grid grid-cols-2 sm:grid-cols-3"
            style={{ gap: "var(--grid-gap)" }}
          >
            {lookbook.map((frame, i) => (
              <div
                key={frame.src}
                className={`relative aspect-[4/5] bg-wash ${i === 2 ? "hidden sm:block" : ""}`}
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

        {/* 6 — Ordering, as a second ruled grid. */}
        <section className="mt-11">
          <SectionHeading title="Ordering" />
          <div className="mt-6">
            <FeatureGrid features={BUYING} />
          </div>
        </section>

        {/* 7 — The facts, as a ruled table. */}
        <section className="mt-11">
          <SectionHeading title="What the objects are" />
          <div className="mt-6">
            <SpecTable rows={SPECS} />
          </div>
        </section>

        {/* 8 — Editorial block. This is where the organic traffic lands, so the
               long-form copy stays; the ruled blocks above carry the scanning
               reader and this carries the search engine. */}
        <section className="mt-11">
          <div className="prose-editorial" style={{ maxWidth: "var(--reading-width)" }}>
            <h2 className="display text-4xl text-ink">Small runs, made properly</h2>

            <p className="mt-6 text-base text-ink">
              {site.brand} produces collectible objects in short runs. Every
              release is made to a fixed quantity, sold direct, and not
              reprinted. When a run is gone it stays gone, and the next one is a
              different object rather than the same one again in another colour.
            </p>

            <h3 className="display mt-8 text-3xl text-ink">How the drops work</h3>
            <p className="mt-4 text-base text-ink">
              Releases are announced to the mailing list first and go live on the
              site at the same time for everyone. Stock is decremented at
              checkout, so what the grid shows is what is actually left. Items
              marked sold out are not restocked; if a run returns it is because
              the piece has been remade, and it is listed as a new release with
              its own run size.
            </p>

            <h3 className="display mt-8 text-3xl text-ink">Materials and making</h3>
            <p className="mt-4 text-base text-ink">
              Keychains are moulded in soft PVC from hand-built masters, printed
              on the face and left flat black on the reverse, then finished and
              assembled in small batches with nickel-plated hardware. Pins are
              struck in metal and filled with hard enamel, polished flat, and
              mounted on printed backing card. Each product page lists the
              material and the construction, and nothing else — the object
              should make its own argument.
            </p>

            <h3 className="display mt-8 text-3xl text-ink">Shipping and returns</h3>
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
