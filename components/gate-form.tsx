"use client";

import { useActionState, useEffect, useRef, useState } from "react";
import { useRouter } from "next/navigation";
import { submitGate, type GateState } from "@/app/gate/actions";
import { EMAIL_ERROR, GENERIC_ERROR } from "@/lib/subscribe";

/**
 * The gate field.
 *
 * Bare underline input, text submit button to its right. Both paths are live:
 *  - JS present  → POST /api/subscribe, then router.replace() on success.
 *  - JS absent   → the surrounding <form action={submitGate}> posts for real.
 *
 * States are Idle / Invalid / Submitting / Success exactly per spec §3.
 */
export function GateForm({ next }: { next: string }) {
  const router = useRouter();
  const inputRef = useRef<HTMLInputElement>(null);

  const [actionState, formAction] = useActionState<GateState, FormData>(
    submitGate,
    {},
  );

  const [email, setEmail] = useState("");
  const [error, setError] = useState<string | null>(null);
  const [status, setStatus] = useState<"idle" | "submitting" | "success">("idle");

  // Surface an error raised by the no-JS action path.
  useEffect(() => {
    if (actionState.error) setError(actionState.error);
  }, [actionState.error]);

  const looksValid = /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email.trim());
  const busy = status !== "idle";

  async function onSubmit(event: React.FormEvent<HTMLFormElement>) {
    // Only intercept once React has hydrated; otherwise the native POST runs.
    event.preventDefault();
    if (busy) return;

    if (!looksValid) {
      setError(EMAIL_ERROR);
      inputRef.current?.focus();
      return;
    }

    setError(null);
    setStatus("submitting");

    try {
      const res = await fetch("/api/subscribe", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ email: email.trim(), next }),
      });
      const data = (await res.json()) as {
        redirect?: string;
        error?: string;
        reason?: string;
      };

      if (!res.ok) {
        // `reason` is only ever sent outside production — it names a server
        // misconfiguration, which is worth seeing while developing and is
        // never shown to a real visitor.
        if (data.reason) console.error(`[gate] ${data.reason}`);
        setError(data.error ?? GENERIC_ERROR);
        setStatus("idle");
        return;
      }

      // Success — fade the field out, then route. No confetti, no checkmark.
      setStatus("success");
      window.setTimeout(() => router.replace(data.redirect ?? "/"), 320);
    } catch {
      setError(GENERIC_ERROR);
      setStatus("idle");
    }
  }

  return (
    <form
      action={formAction}
      onSubmit={onSubmit}
      noValidate
      className="w-full"
      style={{
        opacity: status === "success" ? 0 : 1,
        transition: "opacity var(--duration-base) var(--ease-standard)",
      }}
    >
      <input type="hidden" name="next" value={next} />

      <div className="flex items-end gap-4 border-b border-rule focus-within:border-ink transition-colors duration-[var(--duration-fast)] ease-[var(--ease-standard)]">
        <label htmlFor="gate-email" className="sr-only">
          Email address
        </label>
        <input
          ref={inputRef}
          id="gate-email"
          name="email"
          type="email"
          inputMode="email"
          autoComplete="email"
          autoCapitalize="off"
          spellCheck={false}
          placeholder="Email"
          value={email}
          disabled={busy}
          aria-invalid={error ? true : undefined}
          aria-describedby={error ? "gate-error" : undefined}
          onChange={(e) => {
            setEmail(e.target.value);
            if (error) setError(null);
          }}
          className="min-w-0 flex-1 bg-transparent py-3 text-base text-ink placeholder:text-ink-faint outline-none disabled:text-ink-muted"
        />
        <button
          type="submit"
          disabled={busy}
          className="link-underline shrink-0 py-3 text-sm tracking-[var(--tracking-wide)] text-ink disabled:text-ink-muted"
        >
          {status === "submitting" ? "Entering" : "Enter"}
        </button>
      </div>

      {/* Reserved line so an error never shifts the layout. */}
      <p
        id="gate-error"
        role="status"
        aria-live="polite"
        className="mt-3 min-h-[1.2em] text-left text-xs text-sale"
      >
        {error}
      </p>
    </form>
  );
}
