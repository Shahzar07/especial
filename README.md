# Especial Gallery

A gated premium storefront built to `BUILDPROMPT.md` and `design.ts`.

Two parts: a full-screen mailing-list gate at the entry of the site, and the
store behind it — home, category listing, product detail and cart — revealed
after submission and remembered for 180 days.

```bash
cp .env.example .env.local     # then set GATE_SECRET
npm install
npm run dev
```

---

## Decisions taken where the spec left a blank

`BUILDPROMPT.md` shipped with `{{...}}` placeholders. These are the values used;
each is a one-file change.

| Placeholder | Resolved to | Where |
|---|---|---|
| `{{BRAND}}` | Especial Gallery | `lib/config.ts` |
| `{{TAGLINE}}` | "Objects for people who look twice." | `lib/config.ts` |
| `{{CATEGORIES}}` | Keychains, Figures, Prints, Apparel | `lib/config.ts` |
| `{{ESP}}` | Pluggable adapter — Resend / Klaviyo / Mailchimp | `lib/esp.ts` |
| `{{COMMERCE}}` | Static `data/products.ts` behind a `Product` type | `data/products.ts` |
| `{{DOMAIN}}` | `especialgallery.com` | `lib/config.ts` / `NEXT_PUBLIC_SITE_URL` |

The ESP was left open rather than guessed, so `lib/esp.ts` is an adapter with
three implementations selected by `ESP_PROVIDER`. With `none` the gate still
works end to end and logs the address, so nothing blocks on credentials.

### Two deviations, both deliberate

**Fonts.** `design.ts` specifies Switzer + Editorial New from Fontshare, which
is not reachable from this build environment. The build ships `design.ts`'s own
sanctioned substitute — Instrument Sans + Instrument Serif — **self-hosted as
`.woff2` via `next/font/local`**, not from Google's CDN, so the "no CDN, no
FOUT" rule still holds. To switch: drop `Switzer-Variable.woff2` and
`EditorialNew-Regular.woff2` into `public/fonts/` and repoint the two `src`
paths in `lib/fonts.ts`. Nothing else changes — every consumer reads
`--font-sans` / `--font-display`.

**Editorial subheads.** §5 asks for editorial `h2`s in the display serif at
31px; `design.ts` says the serif is never used below 32px. `design.ts` is
declared the single source of truth, so the editorial block uses `text-4xl`
for its `h2` and `text-3xl` for its `h3`s — both above the floor, and with
clearer hierarchy than one flat size.

### One token added

`z.scrim: 30` in `design.ts`. The cart drawer's dimming scrim has to sit
*below* the drawer panel; with the scrim at `z.overlay` (50) and the panel at
`z.drawer` (40) the scrim covered the panel and swallowed every click inside
it. `z.overlay` keeps its meaning: something that covers a drawer.

---

## Product photography

**The five supplied product photographs are not in this repo** — they were
attached to the conversation as images and never landed on disk, so they could
not be committed. Every slot that expects one currently holds a generated
placeholder that names the file it is waiting for.

Drop the real photographs into `public/products/` under exactly these names and
they appear on the gate, the grid and the PDP with no code change:

| File | Which photo |
|---|---|
| `skeleton-keychain-front.jpg` | Front, green body / pink bones |
| `skeleton-keychain-back.jpg` | Solid black moulded reverse |
| `skeleton-keychain-packaged.jpg` | Sealed in its polybag |
| `skeleton-keychain-glow-front.jpg` | Glow colourway, front |
| `skeleton-keychain-glow-packaged.jpg` | Glow colourway, polybag |

Shoot or crop to **4:5** (1200×1500 or larger). `hero.jpg` is **16:9**, and
`category-*.jpg` are **4:3**.

Everything in `data/products.ts` below the `PLACEHOLDER CATALOGUE` comment is
invented so the rails and grids render at realistic density. None of it is a
real product — replace or delete it before launch.

The `Glow` variant on the keychain is **inferred** from the two greyscale
photographs. If those are simply black-and-white shots of the same colourway
rather than a separate run, delete that variant.

Regenerate placeholders after changing the catalogue with `npm run gen:placeholders`.

---

## How the gate works

`middleware.ts` matches every path except `/gate`, `/api/*`, `/_next/*`, the
static metadata routes, the legal pages, and anything with a file extension.

1. Valid `ml_pass` cookie → through.
2. `?preview=$PREVIEW_TOKEN` → set the cookie, strip the param, continue.
   Unset `PREVIEW_TOKEN` disables the bypass rather than allowing an empty match.
3. A search or social crawler (`lib/crawlers.ts`) → through. **Gating crawlers
   deletes organic traffic and breaks link unfurls**, which is why the editorial
   block on the home page can rank at all.
4. Otherwise → `/gate?next=<original path>`.

The cookie is `<issuedAt>.<HMAC-SHA256>`, signed with `GATE_SECRET`, verified in
constant time, httpOnly, `sameSite=lax`, `secure` in production, 180 days. The
payload is a timestamp — no personal data.

**This is a mailing-list capture, not access control.** User-Agent matching is
spoofable by design so crawlers get through. Never put anything genuinely
sensitive behind it.

### Both submit paths are live

`lib/subscribe.ts` holds the one implementation. Two callers share it so they
cannot drift:

- **JS present** → `POST /api/subscribe`, then `router.replace()`.
- **JS absent** → the server action in `app/gate/actions.ts`, which Next renders
  as a real form POST. Validation still runs, and an invalid address still sets
  no cookie.

`next` is validated as a same-origin path on both paths, so `?next=` cannot be
used as an open redirect. Rate limit is 5 attempts / 10 min per IP, in-process —
swap `lib/rate-limit.ts` for Redis before running more than one instance.

If the ESP write fails the visitor is **still admitted** and the failure is
logged. A broken webhook must never trap a paying customer at the door.

---

## Verifying

```bash
npm run build
npm start                 # one shell
npm run verify            # another
```

`scripts/verify.mjs` drives a real browser and asserts the acceptance criteria:
gate redirect and `?next=` round-trip, invalid email setting no cookie, cookie
persistence across tabs and refreshes, re-gating after a clear, the crawler
allowlist, and — at 320 / 768 / 1440 / 2560 across five routes — no computed
`border-radius` over 2px, no `box-shadow`, no font weight over 600, no display
serif under 32px, and no horizontal overflow. Plus the cart drawer and
`prefers-reduced-motion`.

Last run: **19 passed, 0 failed.**

---

## What is not built

- **Payment.** `{{COMMERCE}}` was never resolved, so `/checkout` stops at a
  handoff rather than pretending to take money. The cart already carries slug,
  variant, quantity and price — everything Shopify's `cart/create` or Stripe's
  `checkout.sessions.create` needs. See the note at the top of that file.
- **Real inventory.** Stock is a boolean per variant in the static catalogue.

## Structure

```
design.ts              single source of truth for the visual system
app/globals.css        design.ts mirrored into Tailwind v4 @theme
middleware.ts          gate enforcement
lib/
  config.ts            brand, categories, domain — every {{...}} lands here
  cookie.ts            HMAC signing, constant-time verify
  subscribe.ts         the one submit implementation
  esp.ts               Resend / Klaviyo / Mailchimp adapter
  crawlers.ts          search + social allowlist
  fonts.ts             self-hosted woff2 via next/font/local
data/products.ts       static catalogue + the accessors the app reads
app/(store)/           everything behind the gate
app/gate/              the gate, outside the store chrome
scripts/verify.mjs     acceptance checks
```
