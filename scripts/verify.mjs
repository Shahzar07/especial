/**
 * Acceptance check for BUILDPROMPT §9.
 *
 * Drives a real browser against a running production build and asserts the
 * things that are easy to regress: the gate, the cookie, the crawler
 * allowlist, the zero-radius / zero-shadow / max-weight-600 rules, the serif
 * floor, reduced motion, and responsive overflow.
 *
 *   npm run build && npm start        # in one shell
 *   node scripts/verify.mjs           # in another  (BASE_URL to override)
 */
import { chromium } from "playwright";

const BASE = process.env.BASE_URL ?? "http://localhost:3000";
const EXE = process.env.CHROMIUM_PATH ?? "/opt/pw-browsers/chromium";

let pass = 0;
let fail = 0;
const ok = (label, cond, extra = "") => {
  cond ? pass++ : fail++;
  console.log(`  ${cond ? "PASS" : "FAIL"}  ${label}${extra ? ` — ${extra}` : ""}`);
};
const head = (t) => console.log(`\n── ${t} ──`);


/**
 * Submit the gate and wait until we are actually through it.
 *
 * A fixed sleep here is a race: on a loaded machine the submit had not landed
 * before the next navigation, so the run carried on still gated and failed
 * later with a confusing "no Add to bag button". Wait for the redirect itself.
 */
async function passGate(page, email) {
  await page.goto(`${BASE}/gate`, { waitUntil: "domcontentloaded" });
  await page.waitForSelector("#gate-email", { timeout: 20000 });
  // Let React hydrate. Clicking first submits the no-JS server-action form,
  // which works but navigates differently and makes the wait below flaky.
  await page.waitForTimeout(1200);
  await page.fill("#gate-email", email);
  await page.click('button[type="submit"]');
  await page.waitForFunction(() => !location.pathname.startsWith("/gate"), null, {
    timeout: 30000,
  });
}

/**
 * Every context gets its own client IP.
 *
 * The gate is rate limited to 5 submissions per 10 minutes per IP, which is
 * correct behaviour and exactly what a repeated test run trips: run the suite
 * twice inside the window and the second run is throttled, failing with
 * confusing "still on /gate" errors that look like a product bug. Handing each
 * context a distinct X-Forwarded-For keeps runs hermetic without weakening the
 * limiter itself.
 */
let ipCounter = 0;
// Randomised per run as well as per context. A counter alone restarts from the
// same address every run, so consecutive runs inside the ten-minute window
// land on the same buckets and the later ones get throttled — which reads as a
// broken cookie rather than as the limiter doing its job.
const ipRun = Math.floor(Math.random() * 250);
function freshContext(browser, options = {}) {
  ipCounter += 1;
  return browser.newContext({
    ...options,
    extraHTTPHeaders: {
      ...(options.extraHTTPHeaders ?? {}),
      "x-forwarded-for": `198.51.${ipRun}.${(ipCounter % 250) + 1}`,
    },
  });
}

const browser = await chromium.launch({ executablePath: EXE });

/* 1 ── Gate ------------------------------------------------------------- */
head("Gate: deep link is preserved and restored");
const ctx = await freshContext(browser, { viewport: { width: 1440, height: 900 } });
const page = await ctx.newPage();

await page.goto(`${BASE}/product/skeleton-keychain-green`, { waitUntil: "domcontentloaded" });
ok("clean browser is gated", page.url().includes("/gate"), page.url());
ok("?next= preserved", decodeURIComponent(page.url()).includes("next=/product/skeleton-keychain-green"));

await page.fill("#gate-email", "not-an-email");
await page.click('button[type="submit"]');
await page.waitForTimeout(400);
ok("invalid email blocked", page.url().includes("/gate"));
ok("inline error shown", (await page.textContent("#gate-error")).includes("Enter a valid email address."));
ok("invalid email sets no cookie", !(await ctx.cookies()).some((c) => c.name === "ml_pass"));

await page.fill("#gate-email", "verify@example.com");
await page.click('button[type="submit"]');
try {
  await page.waitForFunction(() => location.pathname === "/product/skeleton-keychain-green", null, { timeout: 15000 });
} catch { /* asserted below */ }
ok("returns to the exact URL", new URL(page.url()).pathname === "/product/skeleton-keychain-green", page.url());

const cookie = (await ctx.cookies()).find((c) => c.name === "ml_pass");
ok("cookie is httpOnly", Boolean(cookie?.httpOnly));
ok("cookie lasts 180 days", cookie && Math.round((cookie.expires * 1000 - Date.now()) / 86_400_000) === 180);

const tab = await ctx.newPage();
await tab.goto(`${BASE}/keychains`, { waitUntil: "domcontentloaded" });
ok("cookie survives a new tab", !tab.url().includes("/gate"));
await tab.reload({ waitUntil: "domcontentloaded" });
ok("cookie survives a hard refresh", !tab.url().includes("/gate"));
await ctx.clearCookies();
await tab.goto(`${BASE}/keychains`, { waitUntil: "domcontentloaded" });
ok("clearing the cookie re-gates", tab.url().includes("/gate"));

/* 2 ── Crawler allowlist ------------------------------------------------ */
head("Crawlers reach the real page");
for (const [name, ua] of [
  ["Googlebot", "Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)"],
  ["Twitterbot", "Twitterbot/1.0"],
]) {
  const c = await freshContext(browser, { userAgent: ua });
  const p = await c.newPage();
  await p.goto(`${BASE}/`, { waitUntil: "domcontentloaded" });
  ok(`${name} is not gated`, !p.url().includes("/gate"), p.url());
  await c.close();
}

/* 3 ── Visual invariants ------------------------------------------------ */
head("Design rules hold at computed style");
const routes = ["/gate", "/", "/keychains", "/product/skeleton-keychain-green", "/privacy"];
const store = await freshContext(browser, { viewport: { width: 1440, height: 900 } });
const sp = await store.newPage();
await passGate(sp, "rules@example.com");

const violations = [];
for (const w of [320, 768, 1440, 2560]) {
  await sp.setViewportSize({ width: w, height: 900 });
  for (const r of routes) {
    await sp.goto(BASE + r, { waitUntil: "domcontentloaded" });
    const found = await sp.evaluate(() => {
      const bad = [];
      for (const el of document.querySelectorAll("*")) {
        const cs = getComputedStyle(el);
        const text = el.textContent?.trim();
        const fam = cs.fontFamily.toLowerCase();
        const serif = fam.includes("serif") && !fam.includes("sans-serif");
        if (serif && text && parseFloat(cs.fontSize) < 32) bad.push(`serif ${cs.fontSize}`);
        if (text && parseInt(cs.fontWeight, 10) > 600) bad.push(`weight ${cs.fontWeight}`);
        if (cs.boxShadow !== "none") bad.push(`shadow ${cs.boxShadow}`);
        for (const k of ["borderTopLeftRadius", "borderBottomRightRadius"]) {
          if (parseFloat(cs[k]) > 2) bad.push(`radius ${cs[k]}`);
        }
      }
      const de = document.documentElement;
      if (de.scrollWidth > de.clientWidth + 1) bad.push(`overflow ${de.scrollWidth}>${de.clientWidth}`);
      return [...new Set(bad)];
    });
    for (const v of found) violations.push(`${w}px ${r}: ${v}`);
  }
}
ok("no radius >2px, no shadow, no weight >600, no serif <32px, no h-overflow",
   violations.length === 0, violations.slice(0, 6).join(" | "));

/* 4 ── Cart ------------------------------------------------------------- */
head("Cart drawer");
await sp.setViewportSize({ width: 1440, height: 900 });
await sp.goto(`${BASE}/product/skeleton-keychain-green`, { waitUntil: "domcontentloaded" });
await sp.click('button:has-text("Add to bag")');
await sp.waitForTimeout(700);
const drawerState = () => sp.evaluate(() => {
  const d = document.querySelector('[role="dialog"]');
  return { on: d.getBoundingClientRect().left < window.innerWidth - 1, inert: d.hasAttribute("inert") };
});
ok("opens on add to bag", (await drawerState()).on);
await sp.click('[role="dialog"] button[aria-label^="Increase"]');
await sp.waitForTimeout(300);
ok("quantity stepper reaches the subtotal", (await sp.textContent('[role="dialog"]')).includes("$36"));
await sp.keyboard.press("Escape");
await sp.waitForTimeout(800);
const closed = await drawerState();
ok("Escape closes and restores inert", !closed.on && closed.inert);

/* 4b ── The endpoint always answers with JSON ────────────────────────────── */
head("Subscribe endpoint is always parseable");
{
  // The reported failure was a bare 500 with an empty body when GATE_SECRET was
  // missing: the client's res.json() threw and the visitor saw only a generic
  // apology. Whatever happens, this must come back as JSON.
  const probes = [
    ["valid", { email: `probe-${Date.now()}@example.com`, next: "/" }],
    ["invalid email", { email: "nope", next: "/" }],
    ["missing field", {}],
  ];
  for (const [label, body] of probes) {
    const res = await sp.request.post(`${BASE}/api/subscribe`, {
      data: body,
      headers: { "x-forwarded-for": `198.51.${ipRun}.200` },
    });
    let parsed = null;
    try {
      parsed = await res.json();
    } catch {
      /* left null — that is the failure */
    }
    ok(
      `${label}: JSON body (${res.status()})`,
      parsed !== null && typeof parsed === "object",
      parsed ? "" : "unparseable body",
    );
  }
}

/* 4c ── Checkout ──────────────────────────────────────────────────────────── */
head("Checkout");
{
  const post = (path, body, ip) =>
    sp.request.post(`${BASE}${path}`, {
      data: body,
      headers: { "x-forwarded-for": `198.51.${ipRun}.${ip}` },
    });

  // The catalogue prices the order. A browser sends slugs and quantities; if it
  // could send money, anyone could set their own.
  const tampered = await post("/api/checkout", {
    lines: [{ slug: "skeleton-keychain-green", variantId: "standard", quantity: 1, priceCents: 1 }],
    customer: { email: "a@b.co", name: "T", line1: "1 St", city: "K", postcode: "1", country: "PK" },
  }, 210);
  const tamperedBody = await tampered.json();
  ok(
    "a client-supplied price is ignored",
    tampered.ok() && tamperedBody.total === 2400,
    `total ${tamperedBody.total}`,
  );

  const unknown = await post("/api/checkout", {
    lines: [{ slug: "no-such-product", variantId: "x", quantity: 1 }],
    customer: { email: "a@b.co", name: "T", line1: "1 St", city: "K", postcode: "1", country: "PK" },
  }, 211);
  ok("an unknown product is refused", unknown.status() === 409);

  const badQty = await post("/api/checkout", {
    lines: [{ slug: "skeleton-keychain-green", variantId: "standard", quantity: -5 }],
    customer: { email: "a@b.co", name: "T", line1: "1 St", city: "K", postcode: "1", country: "PK" },
  }, 212);
  ok("a negative quantity is refused", badQty.status() === 400);

  const badEmail = await post("/api/checkout", {
    lines: [{ slug: "skeleton-keychain-green", variantId: "standard", quantity: 1 }],
    customer: { email: "nope", name: "T", line1: "1 St", city: "K", postcode: "1", country: "PK" },
  }, 213);
  ok("an invalid email is refused server-side", badEmail.status() === 400);

  const quote = await post("/api/checkout/quote", {
    lines: [{ slug: "skeleton-keychain-green", variantId: "standard", quantity: 2, priceCents: 1 }],
  }, 214);
  const q = await quote.json();
  ok("the quote prices from the catalogue", q.subtotalCents === 3600, `subtotal ${q.subtotalCents}`);
  ok(
    "shipping is charged below the threshold",
    q.shippingCents === q.flatShippingCents && q.subtotalCents < q.freeShippingOverCents,
    `subtotal ${q.subtotalCents}, shipping ${q.shippingCents}`,
  );

  const big = await post("/api/checkout/quote", {
    lines: [{ slug: "skeleton-keychain-green", variantId: "standard", quantity: 5 }],
  }, 215);
  const b = await big.json();
  ok(
    "shipping is free above the threshold",
    b.subtotalCents >= b.freeShippingOverCents && b.shippingCents === 0,
    `subtotal ${b.subtotalCents}, shipping ${b.shippingCents}`,
  );
}

/* 4d ── Hovering a tile must not blank it ────────────────────────────────── */
head("Product tiles survive hover");
{
  await sp.setViewportSize({ width: 1440, height: 1000 });
  await sp.goto(`${BASE}/keychains`, { waitUntil: "domcontentloaded" });
  await sp.waitForTimeout(1500);

  // The tile crossfades to a second photograph on hover. Products with only
  // one photograph have nothing to fade to, and used to fade to nothing at all.
  const tile = sp.locator("main article").first();
  await tile.hover();
  await sp.waitForTimeout(600);

  const visible = await sp.evaluate(() => {
    const a = document.querySelector("main article");
    const imgs = [...a.querySelectorAll("img")];
    return imgs.some((i) => Number(getComputedStyle(i).opacity) > 0.9 && i.naturalWidth > 0);
  });
  ok("a hovered tile still shows a photograph", visible);

  const anyBlank = await sp.evaluate(() =>
    [...document.querySelectorAll("main article")].some((a) => {
      const imgs = [...a.querySelectorAll("img")];
      return imgs.length > 0 && imgs.every((i) => Number(getComputedStyle(i).opacity) < 0.1);
    }));
  ok("no tile is left with every image transparent", !anyBlank);
}

/* 4e ── Header controls ──────────────────────────────────────────────────── */
head("Header controls");
{
  await sp.goto(`${BASE}/`, { waitUntil: "domcontentloaded" });
  await sp.waitForTimeout(1200);

  const header = await sp.evaluate(() => {
    const bag = document.querySelector("header button");
    const account = document.querySelector("header a[aria-label]");
    const box = (el) => {
      const r = el.getBoundingClientRect();
      return { w: Math.round(r.width), h: Math.round(r.height) };
    };
    return {
      bagLabel: bag?.getAttribute("aria-label") ?? "",
      bagBox: bag ? box(bag) : null,
      accountLabel: account?.getAttribute("aria-label") ?? "",
      accountBox: account ? box(account) : null,
      // Icons carry no text, so the label is the only thing a screen reader has.
      bagHasIcon: Boolean(bag?.querySelector("svg")),
      accountHasIcon: Boolean(account?.querySelector("svg")),
    };
  });

  ok("the bag is an icon with a spoken label", header.bagHasIcon && /bag/i.test(header.bagLabel), header.bagLabel);
  ok("the account is an icon with a spoken label", header.accountHasIcon && /account/i.test(header.accountLabel), header.accountLabel);

  const TAP = 44; // the accessible minimum for a finger
  ok(
    "both controls meet the 44px tap target",
    header.bagBox.h >= TAP && header.bagBox.w >= TAP &&
      header.accountBox.h >= TAP && header.accountBox.w >= TAP,
    `bag ${header.bagBox.w}x${header.bagBox.h}, account ${header.accountBox.w}x${header.accountBox.h}`,
  );
}

/* 5 ── Reduced motion --------------------------------------------------- */
head("prefers-reduced-motion");
const rm = await freshContext(browser, { reducedMotion: "reduce" });
const rp = await rm.newPage();
await rp.goto(`${BASE}/gate`, { waitUntil: "domcontentloaded" });
// The reveal animation is neutralised under reduced motion, not removed, so it
// still needs a frame to land on its end state before the opacity is sampled.
await rp.waitForSelector(".gate-reveal", { timeout: 15000 });
await rp.waitForTimeout(500);
const anim = await rp.evaluate(() => {
  const cs = getComputedStyle(document.querySelector(".gate-reveal"));
  return { dur: parseFloat(cs.animationDuration), opacity: parseFloat(cs.opacity) };
});
ok("gate reveal neutralised", anim.dur <= 0.001, `${anim.dur}s`);
ok("content still visible", anim.opacity === 1);

await browser.close();
console.log(`\n${fail === 0 ? "ALL CHECKS PASSED" : "FAILURES PRESENT"} — ${pass} passed, ${fail} failed`);
process.exit(fail === 0 ? 0 : 1);
