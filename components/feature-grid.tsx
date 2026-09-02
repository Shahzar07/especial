import type { ReactNode } from "react";

export type Feature = {
  icon: ReactNode;
  title: string;
  body: string;
};

/**
 * Hairline grid.
 *
 * The dividing lines are the 1px gaps between cells, showing the container's
 * rule-coloured ground through — so the whole block is ruled like a table
 * rather than built from cards. No radius, no shadow, no fill: the structure
 * comes entirely from the lines, which is what keeps it inside the system
 * while still reading as a designed block rather than loose paragraphs.
 */
export function FeatureGrid({
  features,
  columns = 4,
}: {
  features: Feature[];
  columns?: 2 | 3 | 4;
}) {
  const cols =
    columns === 2
      ? "sm:grid-cols-2"
      : columns === 3
        ? "sm:grid-cols-2 lg:grid-cols-3"
        : "sm:grid-cols-2 lg:grid-cols-4";

  return (
    <div className={`grid grid-cols-1 gap-px border border-rule bg-rule ${cols}`}>
      {features.map((feature) => (
        <div key={feature.title} className="flex flex-col bg-paper p-6">
          <span className="text-ink">{feature.icon}</span>
          <h3 className="mt-5 text-sm text-ink">{feature.title}</h3>
          <p className="mt-2 text-sm leading-[var(--leading-body)] text-ink-muted">
            {feature.body}
          </p>
        </div>
      ))}
    </div>
  );
}

/**
 * Two-column ruled table for hard facts. Same hairline logic, but rows rather
 * than cells, because a spec sheet is read down and not across.
 */
export function SpecTable({ rows }: { rows: [string, string][] }) {
  return (
    <dl className="border-t border-rule">
      {rows.map(([term, value]) => (
        <div
          key={term}
          className="flex flex-col gap-1 border-b border-rule py-4 sm:flex-row sm:gap-6"
        >
          <dt className="text-sm text-ink-muted sm:w-56 sm:shrink-0">{term}</dt>
          <dd className="text-sm text-ink">{value}</dd>
        </div>
      ))}
    </dl>
  );
}
