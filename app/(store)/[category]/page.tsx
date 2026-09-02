import type { Metadata } from "next";
import Link from "next/link";
import { notFound } from "next/navigation";
import { ProductGrid } from "@/components/product-grid";
import { categories, site } from "@/lib/config";
import { getProductsByCategory } from "@/data/products";

type Params = { category: string };

export function generateStaticParams(): Params[] {
  return categories.map((c) => ({ category: c.slug }));
}

export async function generateMetadata({
  params,
}: {
  params: Promise<Params>;
}): Promise<Metadata> {
  const { category } = await params;
  const match = categories.find((c) => c.slug === category);
  if (!match) return {};
  return {
    title: match.title,
    description: `${match.title} from ${site.brand}. ${site.description}`,
    alternates: { canonical: `/${match.slug}` },
  };
}

export default async function CategoryPage({
  params,
}: {
  params: Promise<Params>;
}) {
  const { category } = await params;
  const match = categories.find((c) => c.slug === category);
  if (!match) notFound();

  const products = getProductsByCategory(match.slug);

  return (
    <div className="mx-auto max-w-[var(--max-width)] px-[var(--gutter)] py-8">
      <h1 className="display text-3xl text-ink">{match.title}</h1>

      {/* Filter bar: a single row of text toggles, never dropdowns. */}
      <nav
        aria-label="Filter by category"
        className="mt-6 flex flex-wrap items-center gap-x-6 gap-y-2 border-b border-rule pb-4"
      >
        <Link href="/" className="link-underline text-sm text-ink">
          All
        </Link>
        {categories.map((c) => (
          <Link
            key={c.slug}
            href={`/${c.slug}`}
            data-active={c.slug === match.slug}
            aria-current={c.slug === match.slug ? "page" : undefined}
            className="link-underline text-sm text-ink"
          >
            {c.title}
          </Link>
        ))}
        <span className="tabular ml-auto text-xs text-ink-muted">
          {products.length} {products.length === 1 ? "item" : "items"}
        </span>
      </nav>

      <div className="mt-6">
        {products.length > 0 ? (
          <ProductGrid products={products} priorityCount={4} />
        ) : (
          <p className="py-9 text-sm text-ink-muted">
            Nothing in this category yet.
          </p>
        )}
      </div>
    </div>
  );
}
