import type { Metadata } from "next";
import { sans, display } from "@/lib/fonts";
import { site } from "@/lib/config";
import "./globals.css";

export const metadata: Metadata = {
  metadataBase: new URL(site.domain),
  title: {
    default: `${site.brand} — ${site.tagline}`,
    template: `%s — ${site.brand}`,
  },
  description: site.description,
  openGraph: {
    type: "website",
    siteName: site.brand,
    title: `${site.brand} — ${site.tagline}`,
    description: site.description,
    url: site.domain,
  },
  twitter: { card: "summary_large_image" },
  robots: { index: true, follow: true },
};

export default function RootLayout({
  children,
}: {
  children: React.ReactNode;
}) {
  return (
    <html lang="en" className={`${sans.variable} ${display.variable}`}>
      <body>{children}</body>
    </html>
  );
}
