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
import { mkdir } from "node:fs/promises";

const SRC = "photos/source";
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
// Hero: the colour object, large, on the right so the lower left stays clear
// for the type block the page sets into it. The greyscale frames are stronger
// as a lookbook than as the first thing anyone sees — the green and pink is
// what makes this object worth looking at twice.
await build({
  src: "keychain-front.jpg", out: "hero.jpg", ...BANNER,
  fit: [0.40, 0.80], anchor: [0.70, 0.46], bg: WASH,
});
// Mobile hero is a separate crop, not the 16:9 one squeezed. Cropping the wide
// banner to a portrait viewport cuts the object in half; and on mobile the type
// sits below the image rather than over it, so the object can be centred.
await build({
  src: "keychain-front.jpg", out: "hero-mobile.jpg",
  width: 1200, height: 1500, fit: [0.86, 0.62], anchor: [0.5, 0.48], bg: WASH,
});
await build({
  src: "keychain-front.jpg", out: "category-keychains.jpg", ...BLOCK,
  fit: [0.62, 0.68], anchor: [0.5, 0.42], bg: WASH,
});
await build({
  src: "pin-front.jpg", out: "category-pins.jpg", ...BLOCK,
  fit: [0.46, 0.62], anchor: [0.5, 0.42], bg: WASH,
});

console.log("\nDone.");
