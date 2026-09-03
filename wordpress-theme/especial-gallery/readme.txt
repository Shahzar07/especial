=== Especial Gallery ===

Contributors: especialgallery
Requires at least: 6.0
Tested up to: 6.7
Requires PHP: 7.4
License: GNU General Public License v2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Tags: e-commerce, portfolio, one-column, two-columns, custom-menu, custom-logo, featured-images, full-width-template, translation-ready, accessibility-ready

A gated premium storefront theme, converted from a Next.js application.

== Description ==

Especial Gallery is a self-contained storefront theme built on a strict
white-cube design system: no shadows, no rounded corners, no cards, and colour
reserved for the product photography and the focus ring.

It ships a complete shop with no plugin required — a product catalogue, a bag,
a server-priced checkout and an order record — plus a mailing-list gate that
stands in front of the store and remembers where a visitor was headed.

= What you get on activation =

* A product post type with price, options, gallery and specifications
* Product categories at the root of the site (/keychains, not /category/keychains)
* A bag held in the browser, so product pages stay cacheable
* A checkout priced entirely on the server, optionally handing off to Stripe
* Orders recorded in the admin, with email to the shop and the customer
* A mailing-list gate with a signed cookie, rate limiting and an ESP adapter
* A one-click demo importer that produces a working, clickable shop

== Installation ==

1. Appearance → Themes → Add New → Upload Theme, choose the zip, Install, Activate.
2. Follow the notice to Appearance → Theme setup and press "Import demo content".
3. Set your brand and hero artwork in Appearance → Customize → Especial Gallery.
4. Set shipping, currency and payment in Appearance → Shop settings.

The importer is safe to run more than once. Every object is matched before it
is created, so nothing is duplicated and nothing you have edited is overwritten.

== Frequently Asked Questions ==

= Do I need WooCommerce? =

No. The theme ships its own catalogue, bag and checkout and is a complete shop
on its own. If WooCommerce is installed the theme detects it and restyles its
pages to the same design system, but nothing here depends on it.

= How does the gate work? =

Every front-end request except the gate itself, the legal pages, admin and
feeds requires a signed cookie. Everyone else is sent to the gate, which
remembers where they were going and returns them there after they sign up.

**It is a mailing-list capture, not access control.** Search engines and social
unfurlers are let through on a User-Agent match, which is deliberately
spoofable, so that shared links still rank and still preview. Never put
anything genuinely sensitive behind it.

You are not shown the gate while logged in as an editor. Use a private window,
or set a preview token in the Customizer, to see what a visitor sees.

= Where is the money handled? =

Never in the browser. The bag sends only slugs, options and quantities; the
catalogue supplies every price and the server recomputes every total, and it
re-checks availability because a variant can sell out between going into a bag
and being paid for. A line arriving with its own price is ignored.

With a Stripe secret key set, checkout creates a real Checkout Session and the
customer pays on Stripe's own page. With no key the order is recorded and
acknowledged instead, so the whole flow is testable before an account exists.
Card details never touch this site either way.

Put the key in wp-config.php as EG_STRIPE_SECRET_KEY rather than in the
settings field — it then stays out of the database and out of a database export.

= Can I turn the gate off? =

Appearance → Customize → Especial Gallery → Mailing-list gate.

== Changelog ==

= 1.0.0 =
* Initial release. Converted from the Especial Gallery Next.js application.
