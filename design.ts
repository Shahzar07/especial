/**
 * design.ts — single source of truth for the storefront's visual system.
 *
 * Direction: "Gallery" — the site behaves like a white-cube exhibition space.
 * Stark white walls, true black ink, zero decoration, no cards, no shadows,
 * no rounded corners. The products are the only colour on the page.
 * One accent (cobalt) exists purely as an interaction signal, never as decor.
 *
 * Rules of the system:
 *  - Nothing floats. No box-shadow anywhere. Depth comes from whitespace only.
 *  - Nothing is rounded. radius = 0 everywhere except the focus ring.
 *  - Product imagery sits directly on the page background, never inside a card.
 *  - Hover never moves an element. Hover swaps the product image or underlines a link.
 *  - Colour is reserved for the product photography and the focus ring.
 */

export const color = {
  // Base
  paper: '#FFFFFF',        // page background, product image backdrop
  ink: '#000000',          // headings, body, primary buttons
  inkMuted: '#666666',     // captions, categories, secondary meta
  inkFaint: '#9A9A9A',     // disabled, placeholder text
  rule: '#E6E6E4',         // 1px hairlines, input underlines, dividers
  wash: '#F5F5F3',         // sold-out overlays, image placeholder, sale badge bg

  // Interaction — the only saturated value in the system
  accent: '#1B34FF',       // focus ring, active nav, inline links
  accentSoft: '#E8EBFF',   // focus halo / selected swatch fill

  // State
  sale: '#B4231F',         // discounted price only. never used for anything else
  positive: '#1F6B3A',     // "in stock", order confirmed
} as const;

export const font = {
  /**
   * Two families, clearly distinct.
   * Primary  — Switzer (Fontshare, free): neutral modern grotesque. UI, body, nav, prices.
   * Display  — Editorial New (Fontshare, free): high-contrast serif. Gate headline +
   *            section titles ONLY, at 40px and above. Never below 32px, never bold.
   *
   * Licensed swap if the client has budget: Söhne (primary) + Canela (display).
   * Google-only fallback if Fontshare is blocked: Instrument Sans + Instrument Serif.
   */
  sans: '"Switzer", "Helvetica Neue", Helvetica, Arial, sans-serif',
  display: '"Editorial New", "Times New Roman", Times, serif',

  weight: {
    regular: 400,
    medium: 500,
    semibold: 600, // maximum weight in the system. never use 700+
  },

  // Tracking. Grotesques tighten as they grow; the wordmark is the one wide element.
  tracking: {
    wordmark: '0.22em',
    tight: '-0.02em',   // display sizes 40px+
    normal: '0',
    wide: '0.04em',      // nav items, buttons, size swatches
  },

  leading: {
    display: 1.02,
    heading: 1.12,
    body: 1.55,
    ui: 1.2,
  },
} as const;

/**
 * Type scale — 16px base, 1.25 ratio (Elements of Typographic Style).
 * Display sizes use clamp() so the gate headline and hero scale fluidly.
 */
export const type = {
  xs: '0.8125rem',   // 13px — legal, footnotes, form hints
  sm: '0.875rem',    // 14px — nav, buttons, product meta, price
  base: '1rem',      // 16px — body
  lg: '1.25rem',     // 20px — lead paragraph, product title on PDP
  xl: '1.5625rem',   // 25px — subsection heading
  '2xl': 'clamp(1.75rem, 1.2rem + 2vw, 1.9375rem)',   // 31px
  '3xl': 'clamp(2rem, 1.4rem + 3vw, 2.4375rem)',      // 39px — section titles
  '4xl': 'clamp(2.5rem, 1.6rem + 4.5vw, 3.0625rem)',  // 49px — gate headline
  '5xl': 'clamp(3rem, 1.8rem + 6vw, 4.75rem)',        // 76px — editorial hero only
} as const;

/**
 * Spacing — 4px base, 8px rhythm. Section padding is deliberately large:
 * whitespace is the only luxury signal this design uses.
 */
export const space = {
  0: '0',
  1: '0.25rem',   // 4
  2: '0.5rem',    // 8
  3: '0.75rem',   // 12
  4: '1rem',      // 16
  5: '1.5rem',    // 24
  6: '2rem',      // 32
  7: '3rem',      // 48
  8: '4rem',      // 64
  9: '6rem',      // 96
  10: '8rem',     // 128
  11: '12rem',    // 192 — between major home sections on desktop
} as const;

export const layout = {
  // Page gutter grows with viewport; content is never edge-to-edge on desktop.
  gutter: 'clamp(1.25rem, 4vw, 4rem)',
  maxWidth: '1680px',          // full-bleed sections
  readingWidth: '68ch',        // editorial/SEO copy — never exceed
  columns: 12,
  columnGap: 'clamp(1rem, 2vw, 2rem)',

  grid: {
    products: {
      mobile: 2,     // 2-up, tight gutter
      tablet: 3,
      desktop: 4,
      gap: 'clamp(0.75rem, 2vw, 2rem)',
    },
    imageRatio: '4 / 5',       // every product image, no exceptions
    heroRatio: '16 / 9',
  },

  header: {
    height: '64px',
    heightScrolled: '56px',
    // Header is a hairline-bottom bar on paper. Never translucent, never blurred.
    border: `1px solid ${color.rule}`,
  },
} as const;

export const radius = {
  none: '0',
  // The only rounded thing in the system:
  focus: '2px',
} as const;

/** Shadows are intentionally empty. If a component needs one, the layout is wrong. */
export const shadow = {
  none: 'none',
} as const;

export const border = {
  hairline: `1px solid ${color.rule}`,
  ink: `1px solid ${color.ink}`,
  focus: `2px solid ${color.accent}`,
  focusOffset: '2px',
} as const;

/**
 * Motion — one orchestrated moment per page, nothing else.
 * Gate: staggered reveal on load. Store: image crossfade on hover, drawer slide.
 * No scroll-triggered fade-and-slide-up on sections. No hover lift on cards.
 * Every value below must be neutralised under prefers-reduced-motion.
 */
export const motion = {
  duration: {
    instant: '80ms',
    fast: '180ms',    // underlines, focus, button states
    base: '320ms',    // image crossfade, accordion
    slow: '520ms',    // drawer, gate reveal step
  },
  ease: {
    standard: 'cubic-bezier(0.22, 0.61, 0.36, 1)',
    exit: 'cubic-bezier(0.4, 0, 1, 1)',
  },
  gateStagger: '90ms',   // delay between each gate element entering
} as const;

export const breakpoint = {
  sm: '640px',
  md: '768px',
  lg: '1024px',
  xl: '1280px',
  '2xl': '1536px',
} as const;

export const z = {
  base: 0,
  sticky: 10,
  header: 20,
  /**
   * A drawer's own dimming scrim. It must sit *below* the drawer panel,
   * otherwise it intercepts every click inside the panel. `overlay` remains
   * the layer for something that covers a drawer (a modal above one).
   */
  scrim: 30,
  drawer: 40,
  overlay: 50,
  gate: 100,   // the gate sits above everything
} as const;

export const design = {
  color,
  font,
  type,
  space,
  layout,
  radius,
  shadow,
  border,
  motion,
  breakpoint,
  z,
} as const;

export type Design = typeof design;
export default design;
