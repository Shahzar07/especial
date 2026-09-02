"use client";

import { useId, useState } from "react";

/**
 * Native-feeling accordion built on buttons so the open state is controllable
 * and animatable. Grid-rows trick animates height without a measured pixel
 * value and without moving anything else on the page.
 */
export function Accordion({
  title,
  children,
  defaultOpen = false,
}: {
  title: string;
  children: React.ReactNode;
  defaultOpen?: boolean;
}) {
  const [open, setOpen] = useState(defaultOpen);
  const id = useId();

  return (
    <div className="border-b border-rule">
      <h3>
        <button
          type="button"
          onClick={() => setOpen((v) => !v)}
          aria-expanded={open}
          aria-controls={id}
          className="flex w-full items-center justify-between gap-4 py-4 text-left text-sm text-ink"
        >
          {title}
          <span aria-hidden className="text-ink-muted">
            {open ? "−" : "+"}
          </span>
        </button>
      </h3>
      <div
        id={id}
        role="region"
        hidden={!open}
        className="grid transition-[grid-template-rows] ease-[var(--ease-standard)]"
        style={{
          gridTemplateRows: open ? "1fr" : "0fr",
          transitionDuration: "var(--duration-base)",
        }}
      >
        <div className="overflow-hidden">
          <div className="pb-5 text-sm text-ink-muted">{children}</div>
        </div>
      </div>
    </div>
  );
}
