/**
 * Section title in the display serif with a hairline rule beneath.
 * Serif only appears at 40px+ per the type rules, so this uses text-3xl and up.
 */
export function SectionHeading({
  title,
  href,
  action,
}: {
  title: string;
  href?: string;
  action?: string;
}) {
  return (
    <div className="flex items-end justify-between gap-6 border-b border-rule pb-4">
      <h2 className="display text-3xl text-ink">{title}</h2>
      {href && action && (
        <a href={href} className="link-underline shrink-0 pb-1 text-sm text-ink">
          {action}
        </a>
      )}
    </div>
  );
}
