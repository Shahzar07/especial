# Especial Gallery

A gated premium storefront built to `BUILDPROMPT.md` and `design.ts`.

Two parts: a full-screen mailing-list gate at the entry of the site, and the
store behind it — home, category listing, product detail and cart — revealed
after submission and remembered for 180 days.

```bash
npm install
npm run dev          # writes .env.local with a generated GATE_SECRET on first run
```

That is the whole setup. `npm run dev` and `npm start` both run
`scripts/ensure-env.mjs` first, which creates `.env.local` with a freshly
generated `GATE_SECRET` when one is missing. It never overwrites an existing
file and does nothing when `GATE_SECRET` is already in the environment, which is
how a real deployment supplies it — see `.env.example` for every variable.

### GATE_SECRET

Set it in production. The gate cookie is signed with it.

**A missing one no longer breaks the gate.** It falls back to a fixed value in
the source and warns once on the server. That is deliberate: this cookie is a
mailing-list capture, not an access control — the middleware already waves every
crawler through on a spoofable User-Agent — so a forgeable cookie is a much
smaller problem than a storefront whose front door 500s at every customer
because one variable is missing.

It used to throw, which surfaced as a bare 500 with an empty body and an
unexplained "Something went wrong on our end." The bootstrap script above was
meant to prevent that, and it is not enough on its own: **pnpm and yarn do not
run npm's `pre*` hooks**, and neither does a Docker `CMD`, a platform that runs
`next start` for you, or anyone invoking `next dev` directly. The signing path
now stands on its own, and the route always answers with JSON regardless.

---

## The catalogue is real

Two products, both photographed:

| Product | Category | Frames |
|---|---|---|
| Skeleton Keychain | Keychains | front, polybag, black reverse |
| Brain Pin | Pins | front, backing card, reverse with clutch |

Three greyscale frames of the keychain carry the home page's lookbook strip.

Everything factual on those pages — materials, construction, hardware, finish,
colours, packaging — was read off the photographs. **Prices and dimensions were
not visible in them and are placeholders.** See "Before you launch".

### There is no "Glow" colourway

An earlier draft listed one, inferred from three greyscale frames. Measuring
them settled it: those frames carry *exactly* 0.0 saturation in every pixel,
which is impossible for a colour photograph of a real grey object. They are
black-and-white treatments of the same green-and-pink keychain, so they became
the lookbook rather than a second variant.

---

## Photography pipeline

`photos/source/` is the master. `public/products/` is generated — never edit it
by hand. Re-run after any change to a source frame:

```bash
npm run photos
```

The gallery layout puts product imagery straight onto the page with no card, no
border and no shadow. That only works if the photograph's own backdrop is
indistinguishable from the page, and none of these frames started that way: each
was shot on grey seamless at roughly 190–240 luminance, with a lilac cast and
visible vignetting — brighter under the light in the middle than at the corners.

A single global multiplier cannot fix that. It can land the corners on the
target or the middle on the target, but not both, and either way the crop shows
up as a pale rectangle floating on the page. So `scripts/prepare-photos.mjs`
flat-fields instead:

1. **Fit the illumination.** A least-squares quadratic surface — vignetting is
   second-order — is fitted per channel to the *background pixels only*, with
   the object masked out and the mask dilated so no soft edge leaks in. The
   model never sees the object, so it carries no bias from it.
2. **Divide it out** and multiply up to the target ground. The backdrop lands on
   exactly that value everywhere, the lilac cast goes with it (the field is
   per-channel), and the object keeps its own shading because only
   low-frequency content is removed.
3. **Snap the residual.** The quadratic models the light well but not exactly,
   and JPEG adds its own noise, so the corrected backdrop still landed 6-7
   levels off — plainly visible on a near-white ground as a faint grey
   rectangle around the object, which is exactly what the crop is. Anything
   within 12 levels of the target on every channel is snapped to the target, so
   the backdrop is bit-identical to the frame and no rectangle can exist. The
   tolerance sits far below anything in the objects themselves.
4. **Crop to the object**, found as the largest connected component of the mask
   — a global bounding box would span the pin *and* the butterfly clutch lying
   loose beside it, scaling the pin down to nothing — then place it in the frame
   at a fixed size, so every tile reads at the same weight however far away the
   original was shot.

Tiles sit on paper, as the design system asks. Banners use the `wash` token: a
full-bleed white frame on a white page has no edge at all, so the hero would
read as an object floating in undefined space rather than as a composition.
`wash` is a tonal band, not a card — still no border, no radius, no shadow.

### The hero banner

The hero runs on hand-made artwork, not on the pipeline's output. Drop a wide
file at `photos/banner/hero.png` and a portrait one at
`photos/banner/hero-mobile.png`, run `npm run photos`, and they are optimised
into `public/products/`. Anything present there wins; the pipeline never
regenerates over it. With nothing supplied it composes a stand-in from the
product cut-outs so the site still builds.

**Supplied artwork is never cropped.** Its real dimensions are written to
`data/banners.json` and the page lays the hero out from those, so artwork of any
shape drops in without touching a stylesheet and nothing composed by hand gets
silently cut to fit a container.

The artwork is dark and the page sets the wordmark over it in white, so leave
the left of the wide file and the lower part of the portrait one quiet. On
mobile the type sits on a continuous ink band *beneath* the image rather than
over it — overlaying it ran the headline straight across the object.

**Dev gotcha.** Next caches optimised images in `.next/cache/images`, keyed on
the request URL. Replacing a file in `public/products/` without changing its
name serves the *old* image until that cache is cleared — `rm -rf
.next/cache/images`. Production builds start clean, so this only bites locally.

---

## Grids

Both the product grid and the category cells take their column count from a
minimum tile width — `repeat(auto-fill, minmax(min(100%, 260px), 1fr))` — rather
than a fixed count per breakpoint. A fixed count breaks at both ends of a small
catalogue: two products in a two-column grid rendered each one half the page
wide and about 900px tall, and a category page holding a single item showed one
enormous tile. Driving it from tile width keeps a product the same size whether
a page holds one or twenty.

Category cells are compact hairline boxes — square image over a ruled caption
with the item count — on that same rhythm, so the categories and the products
read as one page. With only a few categories the row is capped to their own
width so it does not trail off into empty tracks; the cap lifts on its own once
there are enough to fill the width.

## Structure over prose

The home page carries two ruled blocks — "How we work" and "Ordering" — plus a
specification table, in place of what was four paragraphs of running text.

They are built from hairlines, not cards: the container takes the rule colour,
the cells take paper, and the 1px grid gap lets the ground show through as the
dividing line. So the block reads as a ruled table rather than a row of panels,
and it needs no radius, no shadow and no fill to hold together — which is what
keeps it inside a system that forbids all three. Icons are 1px-stroke line
drawings on a 24 grid inheriting `currentColor`, so they never introduce a
second accent.

The long-form copy stays underneath. The ruled blocks serve the reader who
scans; the prose serves the search engine, which is the whole point of the
editorial section.

## Decisions taken where the spec left a blank

| Placeholder | Resolved to | Where |
|---|---|---|
| `{{BRAND}}` | Especial Gallery | `lib/config.ts` |
| `{{TAGLINE}}` | "Objects for people who look twice." | `lib/config.ts` |
| `{{CATEGORIES}}` | Keychains, Pins | `lib/config.ts` |
| `{{ESP}}` | Adapter — Resend / Klaviyo / Mailchimp | `lib/esp.ts` |
| `{{COMMERCE}}` | Static `data/products.ts` behind a `Product` type | `data/products.ts` |
| `{{DOMAIN}}` | `especialgallery.com` — confirmed by the pin's backing card | `lib/config.ts` |

The ESP was left open rather than guessed, so `lib/esp.ts` is an adapter with
three implementations selected by `ESP_PROVIDER`. With `none` the gate works end
to end and logs the address, so nothing blocks on credentials.

### Two deviations, both deliberate

**Fonts.** `design.ts` specifies Switzer + Editorial New from Fontshare, which is
not reachable from this build environment. The build ships `design.ts`'s own
sanctioned substitute — Instrument Sans + Instrument Serif — **self-hosted as
`.woff2` via `next/font/local`**, not from Google's CDN, so the "no CDN, no
FOUT" rule still holds. To switch: drop `Switzer-Variable.woff2` and
`EditorialNew-Regular.woff2` into `public/fonts/` and repoint the two `src`
paths in `lib/fonts.ts`. Nothing else changes — every consumer reads
`--font-sans` / `--font-display`.

**Editorial subheads.** §5 asks for editorial `h2`s in the display serif at
31px; `design.ts` says the serif is never used below 32px. `design.ts` is
declared the single source of truth, so the editorial block uses `text-4xl` for
its `h2` and `text-3xl` for its `h3`s — both above the floor, with clearer
hierarchy than one flat size.

### One token added

`z.scrim: 30` in `design.ts`. The cart drawer's dimming scrim has to sit *below*
the drawer panel; with the scrim at `z.overlay` (50) and the panel at `z.drawer`
(40) it covered the panel and swallowed every click inside it. `z.overlay` keeps
its meaning: something that covers a drawer.

---

## How the gate works

`middleware.ts` matches every path except `/gate`, `/api/*`, `/_next/*`, the
static metadata routes, the legal pages, and anything with a file extension.

1. Valid `ml_pass` cookie → through.
2. `?preview=$PREVIEW_TOKEN` → set the cookie, strip the param, continue. Unset
   `PREVIEW_TOKEN` disables the bypass rather than allowing an empty match.
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

If the ESP write fails the visitor is **still admitted** and the failure logged.
A broken webhook must never trap a paying customer at the door.

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
`prefers-reduced-motion`, and that `/api/subscribe` always answers with a
parseable JSON body whatever happens to it.

The suite is hermetic: the gate is rate limited to five submissions per ten
minutes per IP, so each browser context carries its own `X-Forwarded-For` drawn
from a range randomised per run. Without that, a second run inside the window
gets throttled and reports as though the cookie had stopped working.

Last run: **29 passed, 0 failed**.

---

## Checkout

`/checkout` collects contact and shipping details, shows a summary, and places
the order; `/checkout/confirmed` acknowledges it with a reference.

**The catalogue prices the order, never the browser.** The page sends only
slugs, variant ids and quantities. `lib/orders.ts` looks each one up, recomputes
every price and total, and re-checks availability, because a variant can sell
out between going into a bag and being paid for. A line arriving with its own
`priceCents` is ignored — there is a test for exactly that.

The same function backs `/api/checkout/quote`, which fills the summary panel, so
the figure a customer reads and the figure they are charged come from one place
and cannot drift.

Set `STRIPE_SECRET_KEY` and `placeOrder` creates a real Stripe Checkout Session
and returns its URL. Unset, the order is recorded and acknowledged instead, so
validation, pricing, stock and confirmation are all exercisable before an
account exists. Card details never touch this site in either case.

Shipping is a flat `SHIPPING_CENTS`, free over `FREE_SHIPPING_OVER_CENTS`. Both
are constants at the top of `lib/orders.ts`.

Not yet built: no order is persisted anywhere (it is logged), there is no
webhook to confirm a Stripe payment settled, and stock is not decremented.
Those need a datastore, which this build does not have.

## Before you launch

- [ ] **Prices** — `data/products.ts`, both products. Currently placeholders.
- [ ] **Dimensions** — neither product's size was visible in the photographs, so
      no size is claimed on either page. Add one to `details`.
- [ ] **Product name** — "Brain Pin" is descriptive, not confirmed. The backing
      card carries no product name.
- [ ] **Payment** — `{{COMMERCE}}` was never resolved, so `/checkout` stops at a
      documented handoff rather than pretending to take money. The cart carries
      slug, variant, quantity and price: everything Shopify's `cart/create` or
      Stripe's `checkout.sessions.create` needs.
- [ ] **Inventory** — stock is a boolean per variant in the static catalogue.
- [ ] `GATE_SECRET` set to a real random value in production.

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
data/products.ts       the catalogue + the accessors the app reads
photos/source/         master photographs — never referenced by the app
public/products/       generated by npm run photos
app/(store)/           everything behind the gate
app/gate/              the gate, outside the store chrome
scripts/
  prepare-photos.mjs   flat-field correction, cropping, encoding
  verify.mjs           acceptance checks
```
