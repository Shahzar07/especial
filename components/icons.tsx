/**
 * Line icons, drawn on a 24 grid with a 1.25 stroke and no fill.
 *
 * They inherit currentColor and carry no colour of their own, so they never
 * introduce a second accent. Stroke weight is deliberately light enough to sit
 * beside a hairline rule without out-weighting it.
 */
type IconProps = { className?: string; size?: number };

const base = {
  viewBox: "0 0 24 24",
  fill: "none",
  stroke: "currentColor",
  strokeWidth: 1.25,
  strokeLinecap: "round" as const,
  strokeLinejoin: "round" as const,
  "aria-hidden": true,
  focusable: false as const,
};

/** Icons default to the block size; the header asks for something smaller. */
const sized = (size = 28) => ({ ...base, width: size, height: size });

/** A fixed run: stacked sheets, counted. */
export function IconEdition({ className, size }: IconProps) {
  return (
    <svg {...sized(size)} className={className}>
      <path d="M4 8.5 12 4l8 4.5" />
      <path d="M4 12l8 4.5 8-4.5" />
      <path d="M4 15.5 12 20l8-4.5" />
    </svg>
  );
}

/** Moulded material: a solid, seen as a form. */
export function IconMaterial({ className, size }: IconProps) {
  return (
    <svg {...sized(size)} className={className}>
      <path d="M12 3.5 20 8v8l-8 4.5L4 16V8z" />
      <path d="M4 8l8 4.5L20 8" />
      <path d="M12 12.5v8" />
    </svg>
  );
}

/** Shipping: a taped carton. */
export function IconShip({ className, size }: IconProps) {
  return (
    <svg {...sized(size)} className={className}>
      <rect x="3.5" y="7.5" width="17" height="12" />
      <path d="M3.5 11.5h17" />
      <path d="M12 7.5v4" />
      <path d="M8.5 4.5h7l1.5 3h-10z" />
    </svg>
  );
}

/** Returns: a path that comes back. */
export function IconReturn({ className, size }: IconProps) {
  return (
    <svg {...sized(size)} className={className}>
      <path d="M7.5 9.5H15a4.5 4.5 0 0 1 0 9H8" />
      <path d="M10.5 6 7 9.5l3.5 3.5" />
    </svg>
  );
}

/** Direct sale: no middleman, one line from maker to hand. */
export function IconDirect({ className, size }: IconProps) {
  return (
    <svg {...sized(size)} className={className}>
      <circle cx="6" cy="12" r="2.5" />
      <circle cx="18" cy="12" r="2.5" />
      <path d="M8.5 12h7" />
    </svg>
  );
}

/** The list: an envelope. */
export function IconMail({ className, size }: IconProps) {
  return (
    <svg {...sized(size)} className={className}>
      <rect x="3.5" y="6" width="17" height="12" />
      <path d="m3.5 7.5 8.5 6 8.5-6" />
    </svg>
  );
}

/** A release, dated. */
export function IconDrop({ className, size }: IconProps) {
  return (
    <svg {...sized(size)} className={className}>
      <rect x="3.5" y="5.5" width="17" height="15" />
      <path d="M3.5 10h17" />
      <path d="M8 3.5v4M16 3.5v4" />
      <path d="M8 14.5h3" />
    </svg>
  );
}

/** Hand finishing. */
export function IconHand({ className, size }: IconProps) {
  return (
    <svg {...sized(size)} className={className}>
      <path d="M9 11V5.75a1.25 1.25 0 0 1 2.5 0V11" />
      <path d="M11.5 10.5V4.75a1.25 1.25 0 0 1 2.5 0V11" />
      <path d="M14 11V6.75a1.25 1.25 0 0 1 2.5 0v6.75" />
      <path d="M9 11V9.75a1.25 1.25 0 0 0-2.5 0v4.75c0 3.5 2.4 6 5.75 6s4.25-2.5 4.25-5" />
    </svg>
  );
}

/** The bag. */
export function IconBag({ className, size }: IconProps) {
  return (
    <svg {...sized(size)} className={className}>
      <path d="M4.5 7.5h15l-1 12.5h-13z" />
      <path d="M8.75 10V6.5a3.25 3.25 0 0 1 6.5 0V10" />
    </svg>
  );
}

/** An account. */
export function IconAccount({ className, size }: IconProps) {
  return (
    <svg {...sized(size)} className={className}>
      <circle cx="12" cy="8.5" r="3.75" />
      <path d="M4.75 20a7.25 7.25 0 0 1 14.5 0" />
    </svg>
  );
}
