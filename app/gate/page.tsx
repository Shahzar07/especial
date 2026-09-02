import type { Metadata } from "next";
import Image from "next/image";
import Link from "next/link";
import { GateForm } from "@/components/gate-form";
import { site } from "@/lib/config";
import { safeNext } from "@/lib/subscribe";

export const metadata: Metadata = {
  title: "Mailing list",
  description: `Sign up to the ${site.brand} mailing list for drop announcements.`,
  robots: { index: false, follow: false },
};

/**
 * The three strongest product shots. These carry the whole visual argument,
 * so they are eager-loaded and given explicit dimensions (CLS = 0).
 */
const GATE_IMAGES = [
  {
    src: "/products/keychain-front.jpg",
    alt: "Especial Gallery Skeleton Keychain, green figure with a pink skeleton",
  },
  {
    src: "/products/pin-front.jpg",
    alt: "Especial Gallery Brain Pin, yellow enamel with a pink spine",
  },
  {
    src: "/products/keychain-reverse.jpg",
    alt: "Reverse of the Especial Gallery Skeleton Keychain, moulded in solid black",
  },
] as const;

const FOOTER_LINKS = [
  { href: "/gate", label: "Mailing list" },
  { href: "/terms", label: "Terms" },
  { href: "/contact", label: "Orders" },
  { href: "/returns", label: "FAQ" },
] as const;

export default async function GatePage({
  searchParams,
}: {
  searchParams: Promise<{ next?: string }>;
}) {
  const next = safeNext((await searchParams).next);

  return (
    <main className="flex min-h-dvh flex-col items-center justify-center px-[var(--gutter)] py-9 text-center">
      <div className="w-full max-w-[720px]">
        {/* 1 — wordmark */}
        <h1
          className="gate-reveal text-sm font-medium tracking-[var(--tracking-wordmark)] text-ink"
          style={{ ["--step" as string]: 0 }}
        >
          {site.wordmark}
        </h1>

        {/* 2 — product row: three on desktop, one on mobile */}
        <div
          className="gate-reveal mt-8 grid grid-cols-1 gap-2 sm:grid-cols-3"
          style={{ ["--step" as string]: 1 }}
        >
          {GATE_IMAGES.map((image, i) => (
            <div
              key={image.src}
              className={`relative aspect-[4/5] bg-wash ${i > 0 ? "hidden sm:block" : ""}`}
            >
              <Image
                src={image.src}
                alt={image.alt}
                fill
                priority
                sizes="(min-width: 640px) 33vw, 100vw"
                className="object-cover"
              />
            </div>
          ))}
        </div>

        {/* 3 — headline */}
        <h2
          className="gate-reveal display mt-8 text-4xl text-ink"
          style={{ ["--step" as string]: 2 }}
        >
          Sign up for our list
        </h2>

        {/* 4 — field */}
        <div
          className="gate-reveal mx-auto mt-7 max-w-[420px] text-left"
          style={{ ["--step" as string]: 3 }}
        >
          <GateForm next={next} />
        </div>

        {/* 5 — footer links */}
        <nav
          className="gate-reveal mt-9 flex flex-wrap items-center justify-center gap-x-6 gap-y-2"
          style={{ ["--step" as string]: 4 }}
          aria-label="Gate"
        >
          {FOOTER_LINKS.map((link) => (
            <Link
              key={link.label}
              href={link.href}
              className="link-underline text-xs text-ink-muted"
            >
              {link.label}
            </Link>
          ))}
        </nav>
      </div>
    </main>
  );
}
