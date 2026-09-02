/**
 * Turns the raw product photographs in photos/source/ into the exact assets the
 * storefront needs, in public/products/.
 *
 *   npm run photos
 *
 * The gallery layout puts product imagery straight onto the page with no card,
 * no border and no shadow behind it. That only works if the photograph's own
 * backdrop is indistinguishable from the page, and none of these frames start
 * that way: each was shot on grey seamless at roughly 190-240 luminance, with a
 * lilac cast and visible vignetting — brighter under the light in the middle
 * than at the corners.
 *
 * Correcting that with a single global multiplier is not enough. It can land
 * the corners on the target or the middle on the target, but not both, and
 * whichever way it lands the crop shows up as a pale rectangle sitting on the
 * page. So the backdrop is flat-fielded instead:
 *
 *   1. Estimate the illumination field — the object is masked out, the hole is
 *      filled with the backdrop median, and the result is blurred hard. What is
 *      left is a smooth, per-channel map of how the light actually fell.
 *   2. Divide the frame by that field and multiply up to the target ground.
 *      The backdrop lands on exactly the target everywhere, the cast goes with
 *      it (the field is per-channel), and the object keeps its own shading
 *      because only low-frequency content is removed.
 *   3. Crop to the object, then place it in the output frame at a fixed size,
 *      so every tile reads at the same visual weight however far away the
 *      original was shot.
 *
 * photos/source/ is the master; public/products/ is generated. Re-run after any
 * change to a source photograph.
 */
import sharp from "sharp";
import { mkdir, writeFile } from "node:fs/promises";
import { existsSync } from "node:fs";

const SRC = "photos/source";
const BANNER_SRC = "photos/banner";
const OUT = "public/products";

/** A pixel this far from the backdrop (sum of channel deltas) is the object. */
const OBJECT_DELTA = 70;
/** Working resolution for the mask and the illumination field. */
const FIELD_SIZE = 180;

const hexToRgb = (hex) => [1, 3, 5].map((i) => parseInt(hex.slice(i, i + 2), 16));

/* ── mask + illumination field ───────────────────────────────────────────── */

async function sample(file, size) {
  const { data, info } = await sharp(file)
    .resize(size, size, { fit: "fill" })
    .raw()
    .toBuffer({ resolveWithObject: true });
  return { data, C: info.channels };
}

/** Backdrop colour = median of the four corner patches. */
function backdrop(data, C, S) {
  const at = (x, y) => {
    const o = (y * S + x) * C;
    return [data[o], data[o + 1], data[o + 2]];
  };
  const patch = [];
  const P = Math.max(4, Math.round(S * 0.09));
  for (const [ox, oy] of [[0, 0], [S - P, 0], [0, S - P], [S - P, S - P]]) {
    for (let y = oy; y < oy + P; y++) for (let x = ox; x < ox + P; x++) patch.push(at(x, y));
  }
  const median = (c) => {
    const v = patch.map((p) => p[c]).sort((a, b) => a - b);
    return v[v.length >> 1];
  };
  return [median(0), median(1), median(2)];
}

/** Object mask, dilated so the object's soft edge never leaks into the field. */
function objectMask(data, C, S, bg, dilate = 4) {
  const raw = new Uint8Array(S * S);
  for (let i = 0, p = 0; i < S * S; i++, p += C) {
    const d =
      Math.abs(data[p] - bg[0]) + Math.abs(data[p + 1] - bg[1]) + Math.abs(data[p + 2] - bg[2]);
    if (d > OBJECT_DELTA) raw[i] = 1;
  }
  if (dilate <= 0) return raw;
  const out = new Uint8Array(S * S);
  for (let y = 0; y < S; y++) {
    for (let x = 0; x < S; x++) {
      if (!raw[y * S + x]) continue;
      for (let dy = -dilate; dy <= dilate; dy++) {
        const yy = y + dy;
        if (yy < 0 || yy >= S) continue;
        for (let dx = -dilate; dx <= dilate; dx++) {
          const xx = x + dx;
          if (xx < 0 || xx >= S) continue;
          out[yy * S + xx] = 1;
        }
      }
    }
  }
  return out;
}

/** Solve A·x = b for a small dense system by Gaussian elimination with pivoting. */
function solve(A, b, n) {
  const M = A.map((row, i) => [...row, b[i]]);
  for (let col = 0; col < n; col++) {
    let piv = col;
    for (let r = col + 1; r < n; r++) if (Math.abs(M[r][col]) > Math.abs(M[piv][col])) piv = r;
    if (Math.abs(M[piv][col]) < 1e-9) continue;
    [M[col], M[piv]] = [M[piv], M[col]];
    for (let r = 0; r < n; r++) {
      if (r === col) continue;
      const f = M[r][col] / M[col][col];
      for (let c = col; c <= n; c++) M[r][c] -= f * M[col][c];
    }
  }
  return M.map((row, i) => (Math.abs(row[i]) < 1e-9 ? 0 : row[n] / row[i]));
}

/** Quadratic basis in normalised coordinates — vignetting is a 2nd-order surface. */
const basis = (x, y) => [1, x, y, x * x, x * y, y * y];

/**
 * Per-channel illumination field, returned at the source image's own
 * dimensions so it can be divided out pixel for pixel.
 *
 * The field is a least-squares quadratic fitted to the BACKGROUND pixels only.
 * An earlier version filled the object's silhouette with the backdrop median
 * and blurred; because the backdrop is brighter in the middle than at the
 * corners, that pulled the field down exactly where the object sits and left a
 * pale halo around it after division. Fitting a surface to the real background
 * and extrapolating it across the object has no such bias — the model never
 * sees the object at all.
 */
async function illuminationField(file, W, H) {
  const S = FIELD_SIZE;
  const { data, C } = await sample(file, S);
  const bg = backdrop(data, C, S);
  const mask = objectMask(data, C, S, bg);

  const N = 6;
  const coeffs = [];
  for (let c = 0; c < 3; c++) {
    const A = Array.from({ length: N }, () => new Array(N).fill(0));
    const b = new Array(N).fill(0);
    for (let y = 0; y < S; y++) {
      for (let x = 0; x < S; x++) {
        const i = y * S + x;
        if (mask[i]) continue;                    // background only
        const f = basis((x / (S - 1)) * 2 - 1, (y / (S - 1)) * 2 - 1);
        const v = data[i * C + c];
        for (let r = 0; r < N; r++) {
          b[r] += f[r] * v;
          for (let q = 0; q < N; q++) A[r][q] += f[r] * f[q];
        }
      }
    }
    coeffs.push(solve(A, b, N));
  }

  // Evaluate at full resolution.
  const field = new Float32Array(W * H * 3);
  for (let y = 0; y < H; y++) {
    const ny = (y / (H - 1)) * 2 - 1;
    for (let x = 0; x < W; x++) {
      const f = basis((x / (W - 1)) * 2 - 1, ny);
      const o = (y * W + x) * 3;
      for (let c = 0; c < 3; c++) {
        let v = 0;
        for (let r = 0; r < N; r++) v += coeffs[c][r] * f[r];
        field[o + c] = v < 1 ? 1 : v;
      }
    }
  }

  return { field, bg };
}

/** Object bounding box, as the largest connected component of the mask. */
async function objectBox(file) {
  const S = 500;
  const { data, C } = await sample(file, S);
  const bg = backdrop(data, C, S);
  const mask = objectMask(data, C, S, bg, 0);

  // Iterative flood fill — recursion would blow the stack at this size. The pin
  // frames have the butterfly clutch lying loose beside the pin; a global box
  // would span both and scale the pin down to nothing.
  const seen = new Uint8Array(S * S);
  const queue = new Int32Array(S * S);
  let best = null;

  for (let start = 0; start < S * S; start++) {
    if (!mask[start] || seen[start]) continue;
    let head = 0, tail = 0;
    queue[tail++] = start;
    seen[start] = 1;
    let count = 0, x0 = S, y0 = S, x1 = 0, y1 = 0;

    while (head < tail) {
      const i = queue[head++];
      const x = i % S, y = (i / S) | 0;
      count++;
      if (x < x0) x0 = x;
      if (x > x1) x1 = x;
      if (y < y0) y0 = y;
      if (y > y1) y1 = y;
      for (const [dx, dy] of [[1, 0], [-1, 0], [0, 1], [0, -1]]) {
        const nx = x + dx, ny = y + dy;
        if (nx < 0 || ny < 0 || nx >= S || ny >= S) continue;
        const ni = ny * S + nx;
        if (mask[ni] && !seen[ni]) { seen[ni] = 1; queue[tail++] = ni; }
      }
    }
    if (!best || count > best.count) best = { count, x0, y0, x1, y1 };
  }
  if (!best) throw new Error(`${file}: no object found against the backdrop`);

  return {
    x: best.x0 / S, y: best.y0 / S,
    w: (best.x1 - best.x0) / S, h: (best.y1 - best.y0) / S,
  };
}

/* ── build ───────────────────────────────────────────────────────────────── */

/**
 * @param {object} o
 * @param {string} o.src   source file in photos/source
 * @param {string} o.out   output file name
 * @param {number} o.width  output width
 * @param {number} o.height output height
 * @param {[number,number]} [o.fit]    object box as a fraction of the frame
 * @param {[number,number]} [o.anchor] object centre, 0-1 of the frame
 * @param {boolean} [o.mono] force greyscale
 * @param {string}  [o.bg]   frame ground
 */
async function build({
  src, out, width, height,
  fit = [0.8, 0.72], anchor = [0.5, 0.5], mono = false, bg: ground = "#ffffff",
}) {
  const file = `${SRC}/${src}`;
  const meta = await sharp(file).metadata();
  const W = meta.width, H = meta.height;

  const target = hexToRgb(ground);
  const [{ field }, box] = await Promise.all([
    illuminationField(file, W, H),
    objectBox(file),
  ]);

  // Divide out the illumination, multiply up to the target ground.
  const { data: srcPx, info } = await sharp(file)
    .toColourspace("srgb")
    .raw()
    .toBuffer({ resolveWithObject: true });

  const C = info.channels;
  const corrected = Buffer.alloc(W * H * 3);
  for (let i = 0, p = 0, q = 0; i < W * H; i++, p += C, q += 3) {
    for (let c = 0; c < 3; c++) {
      const f = field[q + c];
      const v = (srcPx[p + c] * target[c]) / f;
      corrected[q + c] = v > 255 ? 255 : v < 0 ? 0 : v;
    }
  }

  // Object rectangle, padded so the crop never shaves an antialiased edge.
  const pad = 0.02;
  const ox = Math.max(0, (box.x - pad) * W);
  const oy = Math.max(0, (box.y - pad) * H);
  const ow = Math.min(W - ox, (box.w + pad * 2) * W);
  const oh = Math.min(H - oy, (box.h + pad * 2) * H);

  // Fit inside a box constrained on BOTH axes: scaling by the longest edge
  // alone strands a wide object in a tall 4:5 tile with far too much air.
  const scale = Math.min((width * fit[0]) / ow, (height * fit[1]) / oh);

  const placedW = Math.max(1, Math.round(ow * scale));
  const placedH = Math.max(1, Math.round(oh * scale));

  // Stays raw all the way through — re-decoding an intermediate JPEG here would
  // throw away the flat-field correction's precision for nothing.
  const objectLayer = await sharp(corrected, { raw: { width: W, height: H, channels: 3 } })
    .extract({
      left: Math.round(ox), top: Math.round(oy),
      width: Math.round(ow), height: Math.round(oh),
    })
    .resize(placedW, placedH, { fit: "fill", kernel: "lanczos3" })
    .toBuffer();

  let pipeline = sharp({ create: { width, height, channels: 3, background: ground } })
    .composite([{
      input: objectLayer,
      raw: { width: placedW, height: placedH, channels: 3 },
      left: Math.round(width * anchor[0] - placedW / 2),
      top: Math.round(height * anchor[1] - placedH / 2),
    }]);

  if (mono) pipeline = pipeline.greyscale();

  await pipeline
    .jpeg({ quality: 88, mozjpeg: true, chromaSubsampling: "4:4:4" })
    .toFile(`${OUT}/${out}`);

  console.log(`  ${out.padEnd(30)} ${width}x${height}  ground ${ground}`);
}

/* ── cut-outs and the dark banner ─────────────────────────────────────────── */

/**
 * The object alone, on transparency, cropped to its own silhouette.
 *
 * The tiles can get away with keeping the photograph's rectangle because their
 * ground is corrected to the same white as the page. A dark banner cannot: a
 * white rectangle on black is exactly what it sounds like. So the object is
 * flat-fielded to a known white, thresholded against it, reduced to its largest
 * connected component, and the resulting mask becomes the alpha channel. The
 * mask is blurred by a pixel or two before it is applied, which keeps the
 * moulded edge from going hard and jagged against the black.
 */
async function cutout(file, { threshold = 60, feather = 1.6, pad = 0.01 } = {}) {
  const meta = await sharp(file).metadata();
  const W = meta.width, H = meta.height;

  const { field } = await illuminationField(file, W, H);
  const { data: srcPx, info } = await sharp(file).toColourspace("srgb").raw()
    .toBuffer({ resolveWithObject: true });
  const C = info.channels;

  // Flat-field to pure white so the threshold has one known ground.
  const rgb = Buffer.alloc(W * H * 3);
  for (let i = 0, p = 0, q = 0; i < W * H; i++, p += C, q += 3) {
    for (let c = 0; c < 3; c++) {
      const v = (srcPx[p + c] * 255) / (field[q + c] || 1);
      rgb[q + c] = v > 255 ? 255 : v < 0 ? 0 : v;
    }
  }

  const mask = new Uint8Array(W * H);
  for (let i = 0, q = 0; i < W * H; i++, q += 3) {
    const d = (255 - rgb[q]) + (255 - rgb[q + 1]) + (255 - rgb[q + 2]);
    if (d > threshold) mask[i] = 1;
  }

  // Largest connected component, at full resolution.
  const seen = new Uint8Array(W * H);
  const queue = new Int32Array(W * H);
  let best = null;
  for (let start = 0; start < W * H; start++) {
    if (!mask[start] || seen[start]) continue;
    let head = 0, tail = 0;
    queue[tail++] = start; seen[start] = 1;
    let count = 0, x0 = W, y0 = H, x1 = 0, y1 = 0;
    const members = [];
    while (head < tail) {
      const i = queue[head++];
      const x = i % W, y = (i / W) | 0;
      count++; members.push(i);
      if (x < x0) x0 = x; if (x > x1) x1 = x;
      if (y < y0) y0 = y; if (y > y1) y1 = y;
      if (x + 1 < W && mask[i + 1] && !seen[i + 1]) { seen[i + 1] = 1; queue[tail++] = i + 1; }
      if (x - 1 >= 0 && mask[i - 1] && !seen[i - 1]) { seen[i - 1] = 1; queue[tail++] = i - 1; }
      if (y + 1 < H && mask[i + W] && !seen[i + W]) { seen[i + W] = 1; queue[tail++] = i + W; }
      if (y - 1 >= 0 && mask[i - W] && !seen[i - W]) { seen[i - W] = 1; queue[tail++] = i - W; }
    }
    if (!best || count > best.count) best = { count, x0, y0, x1, y1, members };
  }
  if (!best) throw new Error(`${file}: nothing to cut out`);

  // Keep only the winning component.
  const keep = new Uint8Array(W * H);
  for (const i of best.members) keep[i] = 255;

  const px = Math.round(Math.max(W, H) * pad);
  const left = Math.max(0, best.x0 - px);
  const top = Math.max(0, best.y0 - px);
  const width = Math.min(W - left, best.x1 - best.x0 + px * 2);
  const height = Math.min(H - top, best.y1 - best.y0 + px * 2);

  const alpha = await sharp(Buffer.from(keep), { raw: { width: W, height: H, channels: 1 } })
    .blur(feather)
    .extract({ left, top, width, height })
    .raw()
    .toBuffer();

  const colour = await sharp(rgb, { raw: { width: W, height: H, channels: 3 } })
    .extract({ left, top, width, height })
    .raw()
    .toBuffer();

  const rgba = Buffer.alloc(width * height * 4);
  for (let i = 0; i < width * height; i++) {
    rgba[i * 4] = colour[i * 3];
    rgba[i * 4 + 1] = colour[i * 3 + 1];
    rgba[i * 4 + 2] = colour[i * 3 + 2];
    rgba[i * 4 + 3] = alpha[i];
  }
  return { rgba, width, height };
}

/**
 * Composes cut-out objects onto a flat ground.
 * `placements` size each object by a fraction of the canvas height and put its
 * centre at a fraction of the canvas.
 */
async function composeBanner({ out, width, height, ground = "#000000", placements }) {
  const layers = [];
  for (const place of placements) {
    const cut = await cutout(`${SRC}/${place.src}`);
    const targetH = Math.round(height * place.scale);
    const targetW = Math.max(1, Math.round((cut.width / cut.height) * targetH));

    const resized = await sharp(cut.rgba, {
      raw: { width: cut.width, height: cut.height, channels: 4 },
    })
      .resize(targetW, targetH, { fit: "fill", kernel: "lanczos3" })
      .png()
      .toBuffer();

    layers.push({
      input: resized,
      left: Math.round(width * place.at[0] - targetW / 2),
      top: Math.round(height * place.at[1] - targetH / 2),
    });
  }

  await sharp({ create: { width, height, channels: 3, background: ground } })
    .composite(layers)
    .jpeg({ quality: 90, mozjpeg: true, chromaSubsampling: "4:4:4" })
    .toFile(`${OUT}/${out}`);

  console.log(`  ${out.padEnd(30)} ${width}x${height}  ground ${ground}`);
}

/* ── assets ──────────────────────────────────────────────────────────────── */

const TILE = { width: 1400, height: 1750 };   // 4:5, every product image
const BANNER = { width: 2560, height: 1440 }; // 16:9, home hero
const BLOCK = { width: 1800, height: 1350 };  // 4:3, category blocks

/**
 * Tiles sit on paper, exactly as the design system asks — the object with
 * nothing behind it. Banners use the `wash` token: a full-bleed white frame on
 * a white page has no edge at all, so the hero would read as an object floating
 * in undefined space rather than as a composition. wash is a tonal band, not a
 * card — still no border, no radius, no shadow.
 */
const WASH = "#f5f5f3";
const INK = "#000000";

await mkdir(OUT, { recursive: true });

console.log("Product tiles");
await build({ src: "keychain-front.jpg", out: "keychain-front.jpg", ...TILE });
await build({ src: "keychain-packaged.jpg", out: "keychain-packaged.jpg", ...TILE });
await build({ src: "keychain-reverse.jpg", out: "keychain-reverse.jpg", ...TILE });
await build({ src: "pin-front.jpg", out: "pin-front.jpg", ...TILE });
await build({ src: "pin-packaged.jpg", out: "pin-packaged.jpg", ...TILE });
await build({ src: "pin-reverse.jpg", out: "pin-reverse.jpg", ...TILE });

console.log("Lookbook (greyscale campaign frames)");
await build({ src: "keychain-mono-front.jpg", out: "lookbook-01.jpg", ...TILE, bg: WASH, mono: true });
await build({ src: "keychain-mono-packaged.jpg", out: "lookbook-02.jpg", ...TILE, bg: WASH, mono: true });
await build({ src: "keychain-mono-reverse.jpg", out: "lookbook-03.jpg", ...TILE, bg: WASH, mono: true });

console.log("Banners");
/**
 * Hero.
 *
 * Hand-made artwork in photos/banner/ always wins; the pipeline only optimises
 * it and never regenerates over it, so re-running this script can never destroy
 * supplied artwork.
 *
 * Supplied artwork is NEVER cropped. Its own aspect ratio is preserved and
 * written to data/banners.json, and the page lays the hero out from those
 * dimensions — so artwork of any shape drops in without touching a stylesheet,
 * and nothing anyone composed by hand gets silently cut off to fit a container.
 *
 * With nothing supplied, a stand-in is composed from the product cut-outs on
 * ink so the site still builds. The page sets the wordmark over it in white
 * either way, which is why the artwork wants a quiet area for the type.
 */
async function hero({ out, supplied, fallback }) {
  const candidates = [`${BANNER_SRC}/${supplied}.png`, `${BANNER_SRC}/${supplied}.jpg`];
  const file = candidates.find((f) => existsSync(f));

  if (file) {
    // Cap the long edge: past this the file cost outruns any visible gain,
    // since next/image re-encodes and downscales per viewport anyway.
    const meta = await sharp(file).metadata();
    const MAX = 2600;
    const scale = Math.min(1, MAX / Math.max(meta.width, meta.height));
    const width = Math.round(meta.width * scale);
    const height = Math.round(meta.height * scale);

    await sharp(file)
      .resize(width, height, { fit: "fill", kernel: "lanczos3" })
      .jpeg({ quality: 93, mozjpeg: true, chromaSubsampling: "4:4:4" })
      .toFile(`${OUT}/${out}`);

    console.log(`  ${out.padEnd(30)} ${width}x${height}  supplied, uncropped`);
    return { src: `/products/${out}`, width, height };
  }

  await composeBanner({ ...fallback, out, ground: INK });
  console.log(`    (stand-in — drop ${BANNER_SRC}/${supplied}.png to replace)`);
  return { src: `/products/${out}`, width: fallback.width, height: fallback.height };
}

const banners = {
  hero: await hero({
    out: "hero.jpg", supplied: "hero",
    fallback: {
      width: 2560, height: 1097,
      placements: [
        { src: "keychain-front.jpg", scale: 0.72, at: [0.62, 0.5] },
        { src: "pin-front.jpg", scale: 0.4, at: [0.84, 0.34] },
        { src: "keychain-reverse.jpg", scale: 0.46, at: [0.88, 0.72] },
      ],
    },
  }),
  heroMobile: await hero({
    out: "hero-mobile.jpg", supplied: "hero-mobile",
    fallback: {
      width: 1200, height: 1500,
      placements: [
        { src: "keychain-front.jpg", scale: 0.4, at: [0.46, 0.3] },
        { src: "pin-front.jpg", scale: 0.22, at: [0.78, 0.55] },
        { src: "keychain-reverse.jpg", scale: 0.26, at: [0.26, 0.62] },
      ],
    },
  }),
};

await writeFile("data/banners.json", JSON.stringify(banners, null, 2) + "\n");
console.log("  data/banners.json written");

await build({
  src: "keychain-front.jpg", out: "category-keychains.jpg", ...BLOCK,
  fit: [0.62, 0.68], anchor: [0.5, 0.42], bg: WASH,
});
await build({
  src: "pin-front.jpg", out: "category-pins.jpg", ...BLOCK,
  fit: [0.46, 0.62], anchor: [0.5, 0.42], bg: WASH,
});

console.log("\nDone.");
