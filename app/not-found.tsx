import Link from "next/link";
import { site } from "@/lib/config";

export default function NotFound() {
  return (
    <main className="flex min-h-dvh flex-col items-center justify-center px-[var(--gutter)] text-center">
      <p className="text-sm tracking-[var(--tracking-wordmark)] text-ink">
        {site.wordmark}
      </p>
      <h1 className="display mt-7 text-4xl text-ink">Page not found</h1>
      <p className="mt-4 max-w-[42ch] text-base text-ink-muted">
        This page has moved or never existed.
      </p>
      <Link href="/" className="link-underline mt-7 text-sm text-accent">
        Back to the store
      </Link>
    </main>
  );
}
