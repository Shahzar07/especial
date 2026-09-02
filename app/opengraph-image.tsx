import { ImageResponse } from "next/og";
import { site } from "@/lib/config";

export const alt = `${site.brand} — ${site.tagline}`;
export const size = { width: 1200, height: 630 };
export const contentType = "image/png";

/** Same restraint as the site: paper, ink, a hairline, no decoration. */
export default function OpengraphImage() {
  return new ImageResponse(
    (
      <div
        style={{
          width: "100%",
          height: "100%",
          display: "flex",
          flexDirection: "column",
          justifyContent: "space-between",
          background: "#FFFFFF",
          padding: 72,
          fontFamily: "Helvetica, Arial, sans-serif",
        }}
      >
        <div style={{ fontSize: 26, letterSpacing: 9, color: "#000000" }}>
          {site.wordmark}
        </div>
        <div style={{ display: "flex", flexDirection: "column", gap: 20 }}>
          <div style={{ fontSize: 68, color: "#000000", lineHeight: 1.05 }}>
            {site.tagline}
          </div>
          <div style={{ height: 1, background: "#E6E6E4" }} />
          <div style={{ fontSize: 24, color: "#666666" }}>
            Small-run collectible objects
          </div>
        </div>
      </div>
    ),
    size,
  );
}
