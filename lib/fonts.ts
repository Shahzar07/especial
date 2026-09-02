import localFont from "next/font/local";

/**
 * Fonts are self-hosted .woff2 loaded through next/font/local — no CDN request,
 * no FOUT, and a size-adjusted fallback so swapping in the real faces cannot
 * shift layout.
 *
 * design.ts specifies Switzer + Editorial New from Fontshare. Fontshare is not
 * reachable from this build environment, so this ships design.ts's own
 * sanctioned substitute — Instrument Sans + Instrument Serif — self-hosted
 * rather than pulled from Google's CDN.
 *
 * To move to Switzer + Editorial New: drop Switzer-Variable.woff2 and
 * EditorialNew-Regular.woff2 into /public/fonts and point the two `src` paths
 * below at them. Nothing else in the codebase changes — every consumer reads
 * --font-sans / --font-display.
 */
export const sans = localFont({
  src: [{ path: "../public/fonts/instrument-sans-var.woff2", weight: "400 600", style: "normal" }],
  variable: "--font-sans-src",
  display: "swap",
  preload: true,
  fallback: ["Helvetica Neue", "Helvetica", "Arial", "sans-serif"],
  adjustFontFallback: "Arial",
});

export const display = localFont({
  src: [{ path: "../public/fonts/instrument-serif-400.woff2", weight: "400", style: "normal" }],
  variable: "--font-display-src",
  display: "swap",
  preload: true,
  fallback: ["Times New Roman", "Times", "serif"],
  adjustFontFallback: "Times New Roman",
});
