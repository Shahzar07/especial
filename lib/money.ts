/**
 * Prices are stored in minor units and formatted once, here. Tabular figures
 * are applied by the `.tabular` class at the call site, never by a monospace
 * font (spec §7).
 */
export function formatPrice(cents: number, currency: "USD" = "USD"): string {
  return new Intl.NumberFormat("en-US", {
    style: "currency",
    currency,
    // Whole-dollar prices read cleaner without ".00" in a gallery grid.
    minimumFractionDigits: cents % 100 === 0 ? 0 : 2,
    maximumFractionDigits: 2,
  }).format(cents / 100);
}
