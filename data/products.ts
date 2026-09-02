import type { Product } from "@/lib/types";

const IMG = "/products";

/**
 * v1 catalogue — static.
 *
 * The `Product` type is defined once in lib/types.ts and every consumer reads
 * through the accessors at the bottom of this file, so moving to the Shopify
 * Storefront API (or Stripe) means rewriting this module alone.
 *
 * ── Photography ──────────────────────────────────────────────────────────────
 * The Skeleton Keychain entries below reference the five supplied product
 * photographs. Drop them into /public/products/ under exactly these filenames
 * and they appear everywhere; see README "Product photography".
 *
 * ── Colourways ───────────────────────────────────────────────────────────────
 * "Green / Pink" is taken directly from the colour photographs. "Glow" is
 * inferred from the two greyscale photographs — if those are simply black and
 * white shots of the same colourway rather than a separate glow-in-the-dark
 * run, delete that variant and its two images.
 */
export const products: Product[] = [
  {
    slug: "skeleton-keychain",
    title: "Skeleton Keychain",
    category: "keychains",
    priceCents: 1800,
    currency: "USD",
    images: [
      {
        src: `${IMG}/skeleton-keychain-front.jpg`,
        alt: "Especial Gallery Skeleton Keychain, front, green body with pink bones",
        width: 1200,
        height: 1500,
      },
      {
        src: `${IMG}/skeleton-keychain-back.jpg`,
        alt: "Especial Gallery Skeleton Keychain, reverse, solid black moulded back",
        width: 1200,
        height: 1500,
      },
      {
        src: `${IMG}/skeleton-keychain-packaged.jpg`,
        alt: "Especial Gallery Skeleton Keychain sealed in its polybag",
        width: 1200,
        height: 1500,
      },
    ],
    variants: [
      { id: "green-pink", label: "Green / Pink", available: true },
      { id: "glow", label: "Glow", available: true },
    ],
    description:
      "A soft PVC keychain moulded in two layers, with a pink skeleton set into a green body and a flat black reverse. Supplied on a nickel-plated split ring and short chain, sealed in a polybag.",
    details: [
      "Soft moulded PVC, two-sided",
      "Nickel-plated split ring and chain",
      "Approximately 65 × 70 mm",
      "Sealed polybag",
    ],
    featured: true,
    releasedAt: "2026-08-14",
  },

  /* ───────────────────────────────────────────────────────────────────────────
   * PLACEHOLDER CATALOGUE
   *
   * Everything below exists so the rails, grids and category pages render at
   * realistic density. None of it is a real Especial Gallery product. Replace
   * or delete before launch — the acceptance criteria do not depend on it.
   * ─────────────────────────────────────────────────────────────────────────── */
  {
    slug: "skeleton-figure",
    title: "Skeleton Figure",
    category: "figures",
    priceCents: 13000,
    currency: "USD",
    images: [
      {
        src: `${IMG}/placeholder-figure-01.jpg`,
        alt: "Placeholder — Skeleton Figure, front",
        width: 1200,
        height: 1500,
      },
      {
        src: `${IMG}/placeholder-figure-02.jpg`,
        alt: "Placeholder — Skeleton Figure, three-quarter view",
        width: 1200,
        height: 1500,
      },
    ],
    variants: [
      { id: "standard", label: "Standard", available: true },
      { id: "glow", label: "Glow", available: false },
    ],
    description:
      "Placeholder record. Cast vinyl figure, hand-finished, supplied in a printed box.",
    details: ["Cast vinyl", "Approximately 200 mm tall", "Printed box"],
    featured: true,
    releasedAt: "2026-07-30",
  },
  {
    slug: "anatomy-print",
    title: "Anatomy Print",
    category: "prints",
    priceCents: 6500,
    currency: "USD",
    images: [
      {
        src: `${IMG}/placeholder-print-01.jpg`,
        alt: "Placeholder — Anatomy Print",
        width: 1200,
        height: 1500,
      },
      {
        src: `${IMG}/placeholder-print-02.jpg`,
        alt: "Placeholder — Anatomy Print, framed detail",
        width: 1200,
        height: 1500,
      },
    ],
    variants: [
      { id: "a2", label: "A2", available: true },
      { id: "a1", label: "A1", available: true },
    ],
    description:
      "Placeholder record. Four-colour screen print on heavyweight uncoated stock, numbered edition.",
    details: ["Screen print, 4 colours", "300gsm uncoated", "Numbered edition of 100"],
    featured: true,
    releasedAt: "2026-07-12",
  },
  {
    slug: "bones-tee",
    title: "Bones Tee",
    category: "apparel",
    priceCents: 4500,
    currency: "USD",
    images: [
      {
        src: `${IMG}/placeholder-tee-01.jpg`,
        alt: "Placeholder — Bones Tee, front",
        width: 1200,
        height: 1500,
      },
      {
        src: `${IMG}/placeholder-tee-02.jpg`,
        alt: "Placeholder — Bones Tee, back",
        width: 1200,
        height: 1500,
      },
    ],
    variants: [
      { id: "s", label: "S", available: true },
      { id: "m", label: "M", available: true },
      { id: "l", label: "L", available: true },
      { id: "xl", label: "XL", available: false },
    ],
    description:
      "Placeholder record. Heavyweight cotton tee, screen printed front and back.",
    details: ["220gsm cotton", "Boxy fit", "Screen printed"],
    featured: true,
    releasedAt: "2026-06-28",
  },
  {
    slug: "glow-keychain-pair",
    title: "Glow Keychain Pair",
    category: "keychains",
    priceCents: 3200,
    currency: "USD",
    images: [
      {
        src: `${IMG}/placeholder-keychain-01.jpg`,
        alt: "Placeholder — Glow Keychain Pair",
        width: 1200,
        height: 1500,
      },
      {
        src: `${IMG}/placeholder-keychain-02.jpg`,
        alt: "Placeholder — Glow Keychain Pair, reverse",
        width: 1200,
        height: 1500,
      },
    ],
    variants: [{ id: "set", label: "Set of two", available: true }],
    description: "Placeholder record. Two soft PVC keychains supplied as a set.",
    details: ["Soft moulded PVC", "Set of two", "Sealed polybag"],
    releasedAt: "2026-06-10",
  },
  {
    slug: "study-print-ii",
    title: "Study Print II",
    category: "prints",
    priceCents: 5500,
    currency: "USD",
    images: [
      {
        src: `${IMG}/placeholder-print-03.jpg`,
        alt: "Placeholder — Study Print II",
        width: 1200,
        height: 1500,
      },
    ],
    variants: [{ id: "a2", label: "A2", available: false }],
    description: "Placeholder record. Two-colour screen print, sold out edition.",
    details: ["Screen print, 2 colours", "300gsm uncoated", "Edition of 50"],
    releasedAt: "2026-05-22",
  },
  {
    slug: "gallery-sweat",
    title: "Gallery Sweat",
    category: "apparel",
    priceCents: 9000,
    compareAtCents: 11000,
    currency: "USD",
    images: [
      {
        src: `${IMG}/placeholder-sweat-01.jpg`,
        alt: "Placeholder — Gallery Sweat, front",
        width: 1200,
        height: 1500,
      },
      {
        src: `${IMG}/placeholder-sweat-02.jpg`,
        alt: "Placeholder — Gallery Sweat, back",
        width: 1200,
        height: 1500,
      },
    ],
    variants: [
      { id: "m", label: "M", available: true },
      { id: "l", label: "L", available: true },
      { id: "xl", label: "XL", available: true },
    ],
    description: "Placeholder record. Loopback cotton crew, embroidered wordmark.",
    details: ["400gsm loopback cotton", "Embroidered wordmark", "Relaxed fit"],
    releasedAt: "2026-05-02",
  },
  {
    slug: "desk-figure-small",
    title: "Desk Figure",
    category: "figures",
    priceCents: 7500,
    currency: "USD",
    images: [
      {
        src: `${IMG}/placeholder-figure-03.jpg`,
        alt: "Placeholder — Desk Figure",
        width: 1200,
        height: 1500,
      },
    ],
    variants: [{ id: "standard", label: "Standard", available: true }],
    description: "Placeholder record. Small cast vinyl figure on a flat base.",
    details: ["Cast vinyl", "Approximately 90 mm tall", "Printed box"],
    releasedAt: "2026-04-18",
  },
];

/* ── Accessors — the only surface the app reads. Swap the source above and
 *    everything downstream keeps working. ─────────────────────────────────── */

export function getAllProducts(): Product[] {
  return [...products].sort(
    (a, b) => Date.parse(b.releasedAt) - Date.parse(a.releasedAt),
  );
}

export function getProduct(slug: string): Product | undefined {
  return products.find((p) => p.slug === slug);
}

export function getProductsByCategory(category: string): Product[] {
  return getAllProducts().filter((p) => p.category === category);
}

export function getFeatured(limit = 4): Product[] {
  return getAllProducts()
    .filter((p) => p.featured)
    .slice(0, limit);
}

export function getNewest(limit = 8): Product[] {
  return getAllProducts().slice(0, limit);
}

/** Related = same category first, then newest, never the product itself. */
export function getRelated(slug: string, limit = 4): Product[] {
  const current = getProduct(slug);
  if (!current) return getNewest(limit);
  const all = getAllProducts().filter((p) => p.slug !== slug);
  const sameCategory = all.filter((p) => p.category === current.category);
  const rest = all.filter((p) => p.category !== current.category);
  return [...sameCategory, ...rest].slice(0, limit);
}
