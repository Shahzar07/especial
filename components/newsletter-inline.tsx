"use client";

import { useState } from "react";
import { EMAIL_ERROR, GENERIC_ERROR } from "@/lib/subscribe";

/**
 * Footer mailing-list field. Same bare-underline pattern as the gate.
 * Posts to the same endpoint; a visitor already past the gate simply has their
 * cookie refreshed.
 */
export function NewsletterInline() {
  const [email, setEmail] = useState("");
  const [message, setMessage] = useState<{ text: string; error: boolean } | null>(null);
  const [busy, setBusy] = useState(false);

  async function onSubmit(event: React.FormEvent<HTMLFormElement>) {
    event.preventDefault();
    if (busy) return;

    if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email.trim())) {
      setMessage({ text: EMAIL_ERROR, error: true });
      return;
    }

    setBusy(true);
    setMessage(null);
    try {
      const res = await fetch("/api/subscribe", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ email: email.trim(), next: "/" }),
      });
      const data = (await res.json()) as { error?: string };
      if (res.ok) {
        setEmail("");
        setMessage({ text: "You are on the list.", error: false });
      } else {
        setMessage({ text: data.error ?? GENERIC_ERROR, error: true });
      }
    } catch {
      setMessage({ text: GENERIC_ERROR, error: true });
    } finally {
      setBusy(false);
    }
  }

  return (
    <form onSubmit={onSubmit} noValidate>
      <div className="flex items-end gap-3 border-b border-rule focus-within:border-ink transition-colors duration-[var(--duration-fast)]">
        <label htmlFor="footer-email" className="sr-only">
          Email address
        </label>
        <input
          id="footer-email"
          name="email"
          type="email"
          inputMode="email"
          autoComplete="email"
          placeholder="Email"
          value={email}
          disabled={busy}
          onChange={(e) => {
            setEmail(e.target.value);
            if (message?.error) setMessage(null);
          }}
          className="min-w-0 flex-1 bg-transparent py-2 text-sm text-ink placeholder:text-ink-faint outline-none"
        />
        <button
          type="submit"
          disabled={busy}
          className="link-underline shrink-0 py-2 text-sm text-ink disabled:text-ink-muted"
        >
          {busy ? "Adding" : "Sign up"}
        </button>
      </div>
      <p
        role="status"
        aria-live="polite"
        className={`mt-2 min-h-[1.2em] text-xs ${message?.error ? "text-sale" : "text-ink-muted"}`}
      >
        {message?.text}
      </p>
    </form>
  );
}
