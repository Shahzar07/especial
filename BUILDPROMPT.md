# BUILD SPEC — Gated premium storefront

> Paste this whole file into Claude Code as the opening message, with `design.ts` in the repo.
> Replace every `{{...}}` before you send it.

---

## 0. Brand inputs (fill these in)

| Key | Value |
|---|---|
| `{{BRAND}}` | brand name / wordmark |
| `{{TAGLINE}}` | one line, max 8 words |
| `{{CATEGORIES}}` | e.g. Figures, Tees, Sweats, Prints |
| `{{ESP}}` | Klaviyo / Mailchimp / Resend Audiences |
| `{{COMMERCE}}` | Shopify Storefront API / Stripe / static JSON for v1 |
| `{{DOMAIN}}` | production domain |

Use your own wordmark, product photography and copy. The reference sites are a **layout and flow reference only** — don't ship their logo, product shots, or body copy.

---

## 1. What we're building

A two-part storefront:

1. **A mailing-list gate** at the entry of the site. First-time visitors landing on any storefront URL see a full-screen email capture. Nothing else is reachable until they submit a valid email.
2. **The store itself** — home, category listing, product detail, cart — revealed after submission and remembered for 180 days.

Reference for structure only: `kawsone.com/password` (gate) and `kaws-one.com` (store depth: category rails, product grid, long-form editorial block at the bottom of home).

Quality bar: Awwwards-tier minimal e-commerce. Premium, restrained, gallery-like. Not funky, not maximal, not "aesthetic". The restraint *is* the design.

---

## 2. Stack

- Next.js 15, App Router, TypeScript, React Server Components
- Tailwind CSS v4, tokens wired via `@theme` from `design.ts`
- Fonts self-hosted as `.woff2` via `next/font/local` (Switzer + Editorial New from Fontshare). No Google Fonts CDN, no FOUT.
- `next/image` everywhere, AVIF + WebP, explicit `sizes`
- Gate state: signed httpOnly cookie, enforced in `middleware.ts`
- Email: `{{ESP}}` server-side only. API key never reaches the client.
- Commerce: `{{COMMERCE}}`. If v1 is static, define the `Product` type once and read from `/data/products.ts` so the swap to a real API is one adapter file.

---

## 3. The gate — exact behaviour

### Middleware

```
middleware.ts matcher: all paths EXCEPT
  /gate, /api/*, /_next/*, /favicon*, /robots.txt, /sitemap.xml,
  /privacy, /terms, /returns, /contact, and any file with an extension
```

Logic:
1. Read cookie `ml_pass`. If present and signature valid → `NextResponse.next()`.
2. If `?preview={{PREVIEW_TOKEN}}` present → set cookie, strip param, continue. (QA + client demos.)
3. If the request User-Agent matches a known search/social crawler → allow through. **This matters:** a hard gate on every route destroys organic traffic and kills link previews. If the client insists on gating crawlers too, tell them the SEO cost in writing first.
4. Otherwise → `NextResponse.redirect('/gate?next=' + encodeURIComponent(pathname + search))`.

### Submit flow

`POST /api/subscribe` — body `{ email, next }`.

1. Validate email server-side (zod). Client-side validation is UX only, never the gate.
2. Rate-limit by IP: 5 attempts / 10 min.
3. Push to `{{ESP}}`. If the ESP call fails, still let the user in but log the failure — never trap a paying customer behind a broken webhook.
4. Set `ml_pass`: httpOnly, secure, sameSite=lax, maxAge 180 days, signed with `GATE_SECRET`.
5. Return `{ redirect: next || '/' }`. Client navigates with `router.replace()`.

### Gate states

- **Idle** — email field empty, submit disabled but focusable
- **Invalid** — inline message under the field: `Enter a valid email address.` No red banner, no shake, no icon. Text is `color.sale`, 13px.
- **Submitting** — button label swaps to `Entering`, field locks, no spinner graphic
- **Success** — field and button fade out over 320ms, then route. No confetti, no checkmark, no "thanks!" screen.

Errors never apologise and never blame. `Something went wrong on our end. Try again.`

---

## 4. Design system

`design.ts` in the repo root is the single source of truth. Wire it into Tailwind v4:

```css
/* app/globals.css */
@import "tailwindcss";

@theme {
  --color-paper: #FFFFFF;
  --color-ink: #000000;
  --color-ink-muted: #666666;
  --color-rule: #E6E6E4;
  --color-accent: #1B34FF;
  --font-sans: "Switzer", "Helvetica Neue", Arial, sans-serif;
  --font-display: "Editorial New", "Times New Roman", serif;
  --radius-none: 0;
  /* …import the rest from design.ts — do not hand-invent values */
}
```

Every colour, size, spacing and duration in the build must resolve to a token. If you need a value that isn't in `design.ts`, add it there first and say why.

### Non-negotiable visual rules

- `border-radius: 0` on every element. The only exception is the 2px focus ring.
- `box-shadow: none` sitewide. Product images sit on paper, not in cards.
- Maximum font weight 600. No 700, no 800.
- Editorial New (serif) appears only at 40px+. Never for body, never for UI, never bold.
- One accent colour, used only for focus rings, active nav state and inline links. Never a button fill, never a badge, never a gradient.
- No gradients anywhere. No blur, no glassmorphism, no translucent header.
- Hover never moves an element. Product hover = crossfade to second image. Link hover = underline. That's the entire hover vocabulary.
- Prices in tabular figures (`font-variant-numeric: tabular-nums`). Never monospace.

### Motion budget

One orchestrated moment per page:
- **Gate**: on load, wordmark → image row → headline → field reveal, 90ms stagger, opacity + 8px rise. Once. Never repeats.
- **Store**: no scroll-triggered section animations at all. Only user-triggered motion — image crossfade, drawer slide, accordion.
- `@media (prefers-reduced-motion: reduce)` sets every duration to 0.01ms.

---

## 5. Pages

### `/gate` — full viewport, no header, no footer nav

```
┌──────────────────────────────────────────┐
│                                          │
│                {{BRAND}}                 │  wordmark, 0.22em tracking
│                                          │
│   ┌────────┐  ┌────────┐  ┌────────┐     │  3 product images, 4:5
│   │        │  │        │  │        │     │  equal width, tight gutter
│   └────────┘  └────────┘  └────────┘     │  desktop only; mobile = 1
│                                          │
│          Sign up for our list            │  Editorial New, 49px
│                                          │
│   Email ─────────────────────────  →     │  underline input, no box
│                                          │
│   Mailing list   Terms   Orders   FAQ    │  13px, ink-muted
└──────────────────────────────────────────┘
```

- Vertically centred, single column, everything centre-aligned. This is the one page in the site that centres.
- Input is a bare underline (`border-bottom: 1px solid rule`, transparent background, no padding box). On focus the underline becomes `ink`, and the focus ring is visible for keyboard users only (`:focus-visible`).
- Submit is a text button to the right of the field, not a filled block.
- The three images are your strongest product shots on white. They're the whole visual argument — pick them carefully.
- Mobile: one image, wordmark tightens, everything else identical.

### `/` — home

Sections in order:
1. **Hero** — one full-bleed 16:9 image or a 12s muted looping video, with the wordmark or a single line of type overlaid bottom-left. No CTA button. Clicking anywhere enters the featured collection.
2. **Featured rail** — 4 products, horizontal on desktop, horizontal-scroll on mobile with snap. Section title in Editorial New, left-aligned, hairline rule beneath.
3. **Category blocks** — one full-width image per `{{CATEGORIES}}` entry, title overlaid, 2-up on desktop.
4. **Grid** — newest 8–12 products.
5. **Editorial block** — the long-form SEO copy. Constrained to `readingWidth` (68ch), left-aligned, generous leading, h2s in Editorial New at 31px. This is where the search traffic comes from; it must not look like a footer afterthought.
6. **Footer** — 4 link columns, wordmark, mailing-list input (same underline pattern as the gate), payment marks, copyright.

### `/[category]` — listing

Grid per `layout.grid.products`. Each tile: image (4:5), title, category, price. Nothing else — no wishlist heart, no compare, no quick-view, no rating stars. Sold-out items show a `wash` overlay with the word `Sold out` in ink at 13px. Filter bar is a single row of text toggles, not dropdowns.

### `/product/[slug]` — PDP

Two-column on desktop: image column scrolls, detail column sticks. Detail column order — title, price, size/variant swatches (square, 1px ink border when selected), Add to bag (full-width black block, white text, no radius), then accordions for Details / Shipping / Returns. Related products at the bottom, 4-up.

### Cart

Right-hand drawer, slides in 520ms. Line items with quantity steppers, subtotal, checkout block. Empty state: `Your bag is empty.` plus a link to the newest collection.

---

## 6. Copy rules

- Sentence case everywhere. No ALL-CAPS eyebrow labels above headings.
- Buttons name their outcome: `Add to bag`, `Enter`, `Continue to checkout`. Never `Submit`.
- No emoji. No exclamation marks. No `→` appended to link text.
- Product descriptions: what it is, what it's made of, what size it is. No storytelling.

---

## 7. Do not build

These read as templated/AI-generated and are explicitly out of scope:

- Cream `#F4F1EA` backgrounds or terracotta `#D97757` accents
- Rounded cards with soft grey shadows for product tiles
- Tracked-out ALL-CAPS eyebrow labels
- `01 / 02 / 03` numbered section markers
- Fade-and-slide-up on every section as you scroll
- Meta strings joined with middle dots (`Figures · New · $130`)
- Monospace for small labels or prices
- Gradient washes, glass headers, hover lift on cards
- A newsletter modal that pops up *after* the gate

---

## 8. Build order

1. Scaffold, fonts, `design.ts` → Tailwind `@theme`, base layout, focus styles
2. `/gate` complete with real validation + `/api/subscribe` + cookie
3. `middleware.ts` + crawler allowlist + `?preview=` bypass
4. Product data layer and `Product` type
5. Home, listing, PDP
6. Cart drawer + checkout handoff
7. Polish: 404, legal pages, OG images, sitemap, Lighthouse pass

---

## 9. Acceptance criteria

- Direct-loading any store URL in a clean browser lands on `/gate` with `?next=` preserved, and returns to that exact URL after submit
- Cookie survives a hard refresh and a new tab; clearing it re-gates
- Submitting an invalid email never sets the cookie, even with JS disabled
- Lighthouse ≥ 95 performance, ≥ 100 accessibility on gate and home
- CLS < 0.05 — every image has explicit dimensions
- Full keyboard traversal with a visible focus ring on every interactive element
- `prefers-reduced-motion` removes all non-essential motion
- Nothing in the codebase has a `border-radius` or `box-shadow` other than the focus ring
- Renders correctly at 320px, 768px, 1440px, 2560px

---

## Appendix — if this ships on Shopify instead

Shopify has a native password page (Online Store → Preferences → password protection) that does the gate for you, but it only accepts a password, not an email. To keep the email-capture flow: use a custom `password.liquid` styled to this spec, embed the Klaviyo/Shopify Forms signup, and set a `customer_gate` attribute on submit. The design system above ports directly — build it as a Dawn fork with the tokens as CSS custom properties in `base.css`.
