/**
 * Generates neutral 4:5 placeholder imagery so the grid, rails and PDP render
 * at real dimensions (and CLS stays at 0) before real photography lands.
 *
 * Run: node scripts/gen-placeholders.mjs
 * Delete a generated file the moment you drop the real photograph over it.
 */
import sharp from "sharp";
import { mkdir } from "node:fs/promises";

const OUT = "public/products";
const W = 1200;
const H = 1500;

// Files that are waiting on the supplied Especial Gallery photography.
const AWAITING = [
  ["skeleton-keychain-front.jpg", "skeleton-keychain-front.jpg", "Supplied photo 3 — front, green / pink"],
  ["skeleton-keychain-back.jpg", "skeleton-keychain-back.jpg", "Supplied photo 2 — black reverse"],
  ["skeleton-keychain-packaged.jpg", "skeleton-keychain-packaged.jpg", "Supplied photo 1 — sealed polybag"],
  ["skeleton-keychain-glow-front.jpg", "skeleton-keychain-glow-front.jpg", "Supplied photo 5 — glow colourway"],
  ["skeleton-keychain-glow-packaged.jpg", "skeleton-keychain-glow-packaged.jpg", "Supplied photo 4 — glow, polybag"],
];

// Generic placeholder catalogue imagery.
const GENERIC = [
  "placeholder-figure-01.jpg", "placeholder-figure-02.jpg", "placeholder-figure-03.jpg",
  "placeholder-print-01.jpg", "placeholder-print-02.jpg", "placeholder-print-03.jpg",
  "placeholder-tee-01.jpg", "placeholder-tee-02.jpg",
  "placeholder-keychain-01.jpg", "placeholder-keychain-02.jpg",
  "placeholder-sweat-01.jpg", "placeholder-sweat-02.jpg",
];

const esc = (s) => s.replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;");

function svg({ title, note }) {
  return `<svg xmlns="http://www.w3.org/2000/svg" width="${W}" height="${H}" viewBox="0 0 ${W} ${H}">
  <rect width="${W}" height="${H}" fill="#F5F5F3"/>
  <rect x="40.5" y="40.5" width="${W - 81}" height="${H - 81}" fill="none" stroke="#E6E6E4" stroke-width="1"/>
  <line x1="40" y1="${H / 2}" x2="${W - 40}" y2="${H / 2}" stroke="#E6E6E4" stroke-width="1"/>
  <text x="${W / 2}" y="${H / 2 - 34}" text-anchor="middle"
        font-family="Helvetica, Arial, sans-serif" font-size="30" font-weight="500"
        letter-spacing="1" fill="#666666">${esc(title)}</text>
  <text x="${W / 2}" y="${H / 2 + 56}" text-anchor="middle"
        font-family="Helvetica, Arial, sans-serif" font-size="24"
        fill="#9A9A9A">${esc(note)}</text>
</svg>`;
}

await mkdir(OUT, { recursive: true });

for (const [file, title, note] of AWAITING) {
  await sharp(Buffer.from(svg({ title, note })))
    .jpeg({ quality: 86, mozjpeg: true })
    .toFile(`${OUT}/${file}`);
}

for (const file of GENERIC) {
  await sharp(Buffer.from(svg({ title: "Placeholder", note: file })))
    .jpeg({ quality: 86, mozjpeg: true })
    .toFile(`${OUT}/${file}`);
}

// Home hero, 16:9.
await sharp(
  Buffer.from(
    `<svg xmlns="http://www.w3.org/2000/svg" width="2560" height="1440" viewBox="0 0 2560 1440">
      <rect width="2560" height="1440" fill="#F5F5F3"/>
      <rect x="60.5" y="60.5" width="2439" height="1319" fill="none" stroke="#E6E6E4" stroke-width="1"/>
      <text x="1280" y="712" text-anchor="middle" font-family="Helvetica, Arial, sans-serif"
            font-size="40" font-weight="500" fill="#666666">Hero — replace with 16:9 campaign image</text>
      <text x="1280" y="775" text-anchor="middle" font-family="Helvetica, Arial, sans-serif"
            font-size="28" fill="#9A9A9A">public/products/hero.jpg</text>
    </svg>`,
  ),
)
  .jpeg({ quality: 86, mozjpeg: true })
  .toFile(`${OUT}/hero.jpg`);

// Category blocks, 4:3.
for (const c of ["keychains", "figures", "prints", "apparel"]) {
  await sharp(
    Buffer.from(
      `<svg xmlns="http://www.w3.org/2000/svg" width="1600" height="1200" viewBox="0 0 1600 1200">
        <rect width="1600" height="1200" fill="#F5F5F3"/>
        <rect x="40.5" y="40.5" width="1519" height="1119" fill="none" stroke="#E6E6E4" stroke-width="1"/>
        <text x="800" y="612" text-anchor="middle" font-family="Helvetica, Arial, sans-serif"
              font-size="34" fill="#9A9A9A">category-${c}.jpg</text>
      </svg>`,
    ),
  )
    .jpeg({ quality: 86, mozjpeg: true })
    .toFile(`${OUT}/category-${c}.jpg`);
}

console.log("placeholders written to", OUT);
