/**
 * Brand + deployment constants. Everything the BUILD SPEC left as {{...}}
 * resolves here, so a rebrand or a domain change is a one-file edit.
 */
export const site = {
  brand: "Especial Gallery",
  /** Wordmark is letterspaced; the space is rendered as a gap, not a character. */
  wordmark: "ESPECIAL GALLERY",
  tagline: "Objects for people who look twice.",
  description:
    "Especial Gallery makes small-run collectible objects — glow keychains, figures and prints — produced in numbered drops and sold direct.",
  domain: process.env.NEXT_PUBLIC_SITE_URL ?? "https://especialgallery.com",
  email: "hello@especialgallery.com",
} as const;

export const categories = [
  { slug: "keychains", title: "Keychains" },
  { slug: "figures", title: "Figures" },
  { slug: "prints", title: "Prints" },
  { slug: "apparel", title: "Apparel" },
] as const;

export type CategorySlug = (typeof categories)[number]["slug"];

/** Gate cookie name + lifetime (180 days, per spec). */
export const GATE_COOKIE = "ml_pass";
export const GATE_MAX_AGE = 60 * 60 * 24 * 180;
