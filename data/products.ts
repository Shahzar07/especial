import type { Product } from "@/lib/types";

const IMG = "/products";

/**
 * The catalogue — every entry is a real Especial Gallery product, shot from the
 * photographs in photos/source/ and processed by scripts/prepare-photos.mjs.
 *
 * PRICES AND DIMENSIONS ARE PLACEHOLDERS. Everything else here was read off the
 * photographs; those two were not visible in them. Set them before launch —
 * see README, "Before you launch".
 *
 * The `Product` type lives in lib/types.ts and every consumer reads through the
 * accessors at the bottom of this file, so moving to the Shopify Storefront API
 * (or Stripe) means rewriting this module alone.
 */
export const products: Product[] = [
  {
    slug: "skeleton-keychain",
    title: "Skeleton Keychain",
    category: "keychains",
    priceCents: 1800, // placeholder
    currency: "USD",
    images: [
      {
        src: `${IMG}/keychain-front.jpg`,
        alt: "Especial Gallery Skeleton Keychain — green figure with a pink skeleton and a pink cauliflower head, on a split ring",
        width: 1400,
        height: 1750,
      },
      {
        src: `${IMG}/keychain-packaged.jpg`,
        alt: "Especial Gallery Skeleton Keychain sealed in its clear polybag",
        width: 1400,
        height: 1750,
      },
      {
        src: `${IMG}/keychain-reverse.jpg`,
        alt: "Reverse of the Especial Gallery Skeleton Keychain, moulded in solid black",
        width: 1400,
        height: 1750,
      },
    ],
    variants: [{ id: "green-pink", label: "Green / Pink", available: true }],
    description:
      "A soft PVC keychain moulded in two layers: a pink skeleton and cauliflower head set into a green body, with a flat black reverse and a raised black outline. Supplied on a nickel-plated split ring and short chain, sealed in a polybag.",
    details: [
      "Soft moulded PVC, two-sided",
      "Raised outline, flat black reverse",
      "Nickel-plated split ring and chain",
      "Sealed polybag",
    ],
    featured: true,
    releasedAt: "2026-08-31",
  },
  {
    slug: "brain-pin",
    title: "Brain Pin",
    category: "pins",
    priceCents: 1400, // placeholder
    currency: "USD",
    images: [
      {
        src: `${IMG}/pin-front.jpg`,
        alt: "Especial Gallery Brain Pin — yellow enamel brain with a pink spine and two white eyes, in black metal",
        width: 1400,
        height: 1750,
      },
      {
        src: `${IMG}/pin-packaged.jpg`,
        alt: "Especial Gallery Brain Pin mounted on its printed olive backing card",
        width: 1400,
        height: 1750,
      },
      {
        src: `${IMG}/pin-reverse.jpg`,
        alt: "Reverse of the Especial Gallery Brain Pin, showing the post and butterfly clutch",
        width: 1400,
        height: 1750,
      },
    ],
    variants: [{ id: "standard", label: "Standard", available: true }],
    description:
      "A hard enamel pin in black metal: a yellow brain split by a pink spine, with two white eyes and olive shading behind. Single post with a butterfly clutch, mounted on a printed backing card.",
    details: [
      "Hard enamel, black metal plating",
      "Single post with butterfly clutch",
      "Printed backing card",
      "Sealed polybag",
    ],
    featured: true,
    releasedAt: "2026-08-31",
  },
];

/**
 * Greyscale campaign frames of the keychain. Not products — they carry the
 * lookbook strip on the home page.
 */
export const lookbook = [
  {
    src: `${IMG}/lookbook-01.jpg`,
    alt: "Skeleton Keychain photographed in black and white, front",
    width: 1400,
    height: 1750,
  },
  {
    src: `${IMG}/lookbook-02.jpg`,
    alt: "Skeleton Keychain in its polybag, black and white",
    width: 1400,
    height: 1750,
  },
  {
    src: `${IMG}/lookbook-03.jpg`,
    alt: "Reverse of the Skeleton Keychain, black and white",
    width: 1400,
    height: 1750,
  },
] as const;

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
