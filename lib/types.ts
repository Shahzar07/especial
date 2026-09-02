import type { CategorySlug } from "./config";

export type ProductImage = {
  /** Path under /public. Every image is 4:5 unless it is a hero. */
  src: string;
  alt: string;
  width: number;
  height: number;
};

export type Variant = {
  id: string;
  /** Shown in the swatch. Keep to one or two words. */
  label: string;
  available: boolean;
};

export type Product = {
  slug: string;
  title: string;
  category: CategorySlug;
  /** Minor units (cents) so no float ever touches a price. */
  priceCents: number;
  compareAtCents?: number;
  currency: "USD";
  /**
   * Two images minimum: [0] is the resting state, [1] is the hover crossfade.
   * A single-image product simply never crossfades.
   */
  images: ProductImage[];
  variants: Variant[];
  /** What it is, what it's made of, what size it is. No storytelling (spec §6). */
  description: string;
  details: string[];
  featured?: boolean;
  /** ISO date — drives "newest" ordering on the home grid. */
  releasedAt: string;
};

export function isSoldOut(p: Product): boolean {
  return p.variants.every((v) => !v.available);
}
