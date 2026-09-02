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

const browser = await chromium.launch({ executablePath: EXE });

/* 1 ── Gate ------------------------------------------------------------- */
head("Gate: deep link is preserved and restored");
const ctx = await browser.newContext({ viewport: { width: 1440, height: 900 } });
const page = await ctx.newPage();

await page.goto(`${BASE}/product/skeleton-keychain`, { waitUntil: "networkidle" });
ok("clean browser is gated", page.url().includes("/gate"), page.url());
ok("?next= preserved", decodeURIComponent(page.url()).includes("next=/product/skeleton-keychain"));

await page.fill("#gate-email", "not-an-email");
await page.click('button[type="submit"]');
await page.waitForTimeout(400);
ok("invalid email blocked", page.url().includes("/gate"));
ok("inline error shown", (await page.textContent("#gate-error")).includes("Enter a valid email address."));
ok("invalid email sets no cookie", !(await ctx.cookies()).some((c) => c.name === "ml_pass"));

await page.fill("#gate-email", "verify@example.com");
await page.click('button[type="submit"]');
try {
  await page.waitForFunction(() => location.pathname === "/product/skeleton-keychain", null, { timeout: 15000 });
} catch { /* asserted below */ }
ok("returns to the exact URL", new URL(page.url()).pathname === "/product/skeleton-keychain", page.url());

const cookie = (await ctx.cookies()).find((c) => c.name === "ml_pass");
ok("cookie is httpOnly", Boolean(cookie?.httpOnly));
ok("cookie lasts 180 days", cookie && Math.round((cookie.expires * 1000 - Date.now()) / 86_400_000) === 180);

const tab = await ctx.newPage();
await tab.goto(`${BASE}/keychains`, { waitUntil: "networkidle" });
ok("cookie survives a new tab", !tab.url().includes("/gate"));
await tab.reload({ waitUntil: "networkidle" });
ok("cookie survives a hard refresh", !tab.url().includes("/gate"));
await ctx.clearCookies();
await tab.goto(`${BASE}/keychains`, { waitUntil: "networkidle" });
ok("clearing the cookie re-gates", tab.url().includes("/gate"));

/* 2 ── Crawler allowlist ------------------------------------------------ */
head("Crawlers reach the real page");
for (const [name, ua] of [
  ["Googlebot", "Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)"],
  ["Twitterbot", "Twitterbot/1.0"],
]) {
  const c = await browser.newContext({ userAgent: ua });
  const p = await c.newPage();
  await p.goto(`${BASE}/`, { waitUntil: "networkidle" });
  ok(`${name} is not gated`, !p.url().includes("/gate"), p.url());
  await c.close();
}

/* 3 ── Visual invariants ------------------------------------------------ */
head("Design rules hold at computed style");
const routes = ["/gate", "/", "/keychains", "/product/skeleton-keychain", "/privacy"];
const store = await browser.newContext({ viewport: { width: 1440, height: 900 } });
const sp = await store.newPage();
await sp.goto(`${BASE}/gate`, { waitUntil: "networkidle" });
await sp.fill("#gate-email", "rules@example.com");
await sp.click('button[type="submit"]');
await sp.waitForTimeout(1200);

const violations = [];
for (const w of [320, 768, 1440, 2560]) {
  await sp.setViewportSize({ width: w, height: 900 });
  for (const r of routes) {
    await sp.goto(BASE + r, { waitUntil: "networkidle" });
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
await sp.goto(`${BASE}/product/skeleton-keychain`, { waitUntil: "networkidle" });
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

/* 5 ── Reduced motion --------------------------------------------------- */
head("prefers-reduced-motion");
const rm = await browser.newContext({ reducedMotion: "reduce" });
const rp = await rm.newPage();
await rp.goto(`${BASE}/gate`, { waitUntil: "networkidle" });
const anim = await rp.evaluate(() => {
  const cs = getComputedStyle(document.querySelector(".gate-reveal"));
  return { dur: parseFloat(cs.animationDuration), opacity: parseFloat(cs.opacity) };
});
ok("gate reveal neutralised", anim.dur <= 0.001, `${anim.dur}s`);
ok("content still visible", anim.opacity === 1);

await browser.close();
console.log(`\n${fail === 0 ? "ALL CHECKS PASSED" : "FAILURES PRESENT"} — ${pass} passed, ${fail} failed`);
process.exit(fail === 0 ? 0 : 1);
