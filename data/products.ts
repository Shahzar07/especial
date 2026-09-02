import type { Product } from "@/lib/types";

const IMG = "/products";

/**
 * The catalogue.
 *
 * Nine products, one per supplied photograph, each sold on its own. The
 * material facts — construction, hardware, finish, packing — were read off the
 * photographs.
 *
 * PRICES AND DIMENSIONS ARE PLACEHOLDERS. They were not visible in the
 * photographs and are set to plausible figures so the storefront reads as a
 * real shop. Confirm both before launch; see README, "Before you launch".
 *
 * The `Product` type lives in lib/types.ts and every consumer reads through the
 * accessors at the bottom, so moving to the Shopify Storefront API (or Stripe
 * products) means rewriting this module alone.
 */

const KEYCHAIN_SIZE = "Approximately 70 × 65 mm";
const PIN_SIZE = "Approximately 32 × 30 mm";

export const products: Product[] = [
  /* ── Keychains ─────────────────────────────────────────────────────────── */
  {
    slug: "skeleton-keychain-green",
    title: "Skeleton Keychain — Green",
    category: "keychains",
    priceCents: 1800,
    currency: "USD",
    images: [
      {
        src: `${IMG}/keychain-front.jpg`,
        alt: "Skeleton Keychain in green, with a pink skeleton and a pink cauliflower head, on a split ring",
        width: 1400,
        height: 1750,
      },
    ],
    variants: [{ id: "standard", label: "Standard", available: true }],
    description:
      "Soft PVC keychain moulded in two layers: a pink skeleton and cauliflower head set into a green body, with a raised black outline. Supplied on a nickel-plated split ring and short chain.",
    details: [
      "Soft moulded PVC, two-sided",
      "Raised outline, flat black reverse",
      "Nickel-plated split ring and chain",
      KEYCHAIN_SIZE,
    ],
    featured: true,
    releasedAt: "2026-08-31",
  },
  {
    slug: "skeleton-keychain-sealed",
    title: "Skeleton Keychain — Sealed",
    category: "keychains",
    priceCents: 2000,
    currency: "USD",
    images: [
      {
        src: `${IMG}/keychain-packaged.jpg`,
        alt: "Skeleton Keychain in green sealed in its clear polybag",
        width: 1400,
        height: 1750,
      },
    ],
    variants: [{ id: "standard", label: "Standard", available: true }],
    description:
      "The green Skeleton Keychain delivered sealed in its original polybag, unopened. For collectors who would rather it stayed that way.",
    details: [
      "Soft moulded PVC, two-sided",
      "Nickel-plated split ring and chain",
      "Sealed polybag, unopened",
      KEYCHAIN_SIZE,
    ],
    featured: true,
    releasedAt: "2026-08-30",
  },
  {
    slug: "skeleton-keychain-blackout",
    title: "Skeleton Keychain — Blackout",
    category: "keychains",
    priceCents: 1800,
    currency: "USD",
    images: [
      {
        src: `${IMG}/keychain-reverse.jpg`,
        alt: "Skeleton Keychain shown from the reverse, moulded in solid black",
        width: 1400,
        height: 1750,
      },
    ],
    variants: [{ id: "standard", label: "Standard", available: true }],
    description:
      "The same moulding, worn the other way round: a flat, unprinted black face with the silhouette raised in relief. No colour, no artwork, just the shape.",
    details: [
      "Soft moulded PVC, unprinted face",
      "Raised silhouette in relief",
      "Nickel-plated split ring and chain",
      KEYCHAIN_SIZE,
    ],
    releasedAt: "2026-08-29",
  },
  {
    slug: "skeleton-keychain-mono",
    title: "Skeleton Keychain — Mono",
    category: "keychains",
    priceCents: 1900,
    currency: "USD",
    images: [
      {
        src: `${IMG}/keychain-mono-front.jpg`,
        alt: "Skeleton Keychain in monochrome, white bones on a grey body",
        width: 1400,
        height: 1750,
      },
    ],
    variants: [{ id: "standard", label: "Standard", available: true }],
    description:
      "The Skeleton Keychain with every colour taken out: white bones and a white cauliflower head on a grey body, black outline. Same mould, different run.",
    details: [
      "Soft moulded PVC, two-sided",
      "Monochrome colourway",
      "Nickel-plated split ring and chain",
      KEYCHAIN_SIZE,
    ],
    featured: true,
    releasedAt: "2026-08-28",
  },
  {
    slug: "skeleton-keychain-mono-sealed",
    title: "Skeleton Keychain — Mono Sealed",
    category: "keychains",
    priceCents: 2100,
    currency: "USD",
    images: [
      {
        src: `${IMG}/keychain-mono-packaged.jpg`,
        alt: "Monochrome Skeleton Keychain sealed in its clear polybag",
        width: 1400,
        height: 1750,
      },
    ],
    variants: [{ id: "standard", label: "Standard", available: true }],
    description:
      "The Mono Skeleton Keychain delivered sealed in its original polybag, unopened.",
    details: [
      "Soft moulded PVC, two-sided",
      "Monochrome colourway",
      "Sealed polybag, unopened",
      KEYCHAIN_SIZE,
    ],
    releasedAt: "2026-08-27",
  },
  {
    slug: "skeleton-keychain-mono-blackout",
    title: "Skeleton Keychain — Mono Blackout",
    category: "keychains",
    priceCents: 1700,
    currency: "USD",
    images: [
      {
        src: `${IMG}/keychain-mono-reverse.jpg`,
        alt: "Monochrome Skeleton Keychain shown from the reverse, unprinted black",
        width: 1400,
        height: 1750,
      },
    ],
    variants: [{ id: "standard", label: "Standard", available: false }],
    description:
      "The Mono run seen from the reverse: an unprinted black face with the silhouette raised in relief. Sold out.",
    details: [
      "Soft moulded PVC, unprinted face",
      "Raised silhouette in relief",
      "Nickel-plated split ring and chain",
      KEYCHAIN_SIZE,
    ],
    releasedAt: "2026-08-26",
  },

  /* ── Pins ──────────────────────────────────────────────────────────────── */
  {
    slug: "brain-pin-yellow",
    title: "Brain Pin — Yellow",
    category: "pins",
    priceCents: 1400,
    currency: "USD",
    images: [
      {
        src: `${IMG}/pin-front.jpg`,
        alt: "Brain Pin — yellow enamel brain split by a pink spine, with two white eyes, in black metal",
        width: 1400,
        height: 1750,
      },
    ],
    variants: [{ id: "standard", label: "Standard", available: true }],
    description:
      "Hard enamel pin in black metal: a yellow brain split by a pink spine, two white eyes, olive shading behind. Polished flat, with a single post and butterfly clutch.",
    details: [
      "Hard enamel, black metal plating",
      "Polished flat",
      "Single post with butterfly clutch",
      PIN_SIZE,
    ],
    featured: true,
    releasedAt: "2026-08-25",
  },
  {
    slug: "brain-pin-carded",
    title: "Brain Pin — Carded",
    category: "pins",
    priceCents: 1600,
    currency: "USD",
    images: [
      {
        src: `${IMG}/pin-packaged.jpg`,
        alt: "Brain Pin mounted on its printed olive backing card, sealed in a polybag",
        width: 1400,
        height: 1750,
      },
    ],
    variants: [{ id: "standard", label: "Standard", available: true }],
    description:
      "The Brain Pin mounted on its printed olive backing card and sealed, as it ships. The card is part of the release rather than packaging to throw away.",
    details: [
      "Hard enamel, black metal plating",
      "Printed backing card",
      "Sealed polybag",
      PIN_SIZE,
    ],
    releasedAt: "2026-08-24",
  },
  {
    slug: "brain-pin-backstamp",
    title: "Brain Pin — Backstamp",
    category: "pins",
    priceCents: 1400,
    currency: "USD",
    images: [
      {
        src: `${IMG}/pin-reverse.jpg`,
        alt: "Reverse of the Brain Pin, showing the plated back, post and butterfly clutch",
        width: 1400,
        height: 1750,
      },
    ],
    variants: [{ id: "standard", label: "Standard", available: true }],
    description:
      "The Brain Pin seen from behind: the plated reverse, the post, and the butterfly clutch it ships with.",
    details: [
      "Hard enamel, black metal plating",
      "Plated reverse",
      "Single post with butterfly clutch",
      PIN_SIZE,
    ],
    releasedAt: "2026-08-23",
  },
];

/* ── Accessors — the only surface the app reads. ──────────────────────────── */

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
