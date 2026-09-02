/**
 * Makes a fresh clone runnable.
 *
 * The gate cookie is signed with GATE_SECRET. Without it the site renders, the
 * gate renders, and then the first submission fails with an opaque error — the
 * worst possible place to discover a missing variable. This runs before dev and
 * start, and writes a .env.local with a generated secret if there isn't one.
 *
 * It never overwrites an existing file, and it does nothing when GATE_SECRET is
 * already in the environment, which is how a real deployment supplies it.
 */
import { existsSync, readFileSync, writeFileSync } from "node:fs";
import { randomBytes } from "node:crypto";

const FILE = ".env.local";

if (process.env.GATE_SECRET) {
  process.exit(0); // supplied by the platform
}

if (existsSync(FILE)) {
  const body = readFileSync(FILE, "utf8");
  const match = body.match(/^GATE_SECRET=(.*)$/m);
  if (match && match[1].trim().length >= 16) process.exit(0);

  const secret = randomBytes(32).toString("hex");
  const next = match
    ? body.replace(/^GATE_SECRET=.*$/m, `GATE_SECRET=${secret}`)
    : `${body.replace(/\n*$/, "\n")}GATE_SECRET=${secret}\n`;
  writeFileSync(FILE, next);
  console.log(`[env] ${FILE} had no usable GATE_SECRET — generated one.`);
  process.exit(0);
}

writeFileSync(
  FILE,
  [
    "# Generated for local development. Not committed, not shared.",
    "# Production must supply its own GATE_SECRET through the platform.",
    `GATE_SECRET=${randomBytes(32).toString("hex")}`,
    "",
    "# Optional: enables ?preview=<token> to bypass the gate.",
    "PREVIEW_TOKEN=local-preview",
    "",
    "# Mailing list: resend | klaviyo | mailchimp | none",
    "# 'none' logs the address and still admits the visitor.",
    "ESP_PROVIDER=none",
    "",
    "NEXT_PUBLIC_SITE_URL=http://localhost:3000",
    "",
  ].join("\n"),
);
console.log(`[env] wrote ${FILE} with a generated GATE_SECRET.`);
