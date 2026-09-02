import { CartProvider } from "@/components/cart-provider";
import { CartDrawer } from "@/components/cart-drawer";
import { SiteHeader } from "@/components/site-header";
import { SiteFooter } from "@/components/site-footer";

/**
 * Store chrome. The gate lives outside this group so it renders with no header,
 * no footer and no cart — a clean full viewport.
 */
export default function StoreLayout({
  children,
}: {
  children: React.ReactNode;
}) {
  return (
    <CartProvider>
      <a
        href="#main"
        className="sr-only focus:not-sr-only focus:absolute focus:left-4 focus:top-4 focus:z-[var(--z-gate)] focus:bg-paper focus:px-4 focus:py-2 focus:text-sm focus:text-ink"
      >
        Skip to content
      </a>
      <SiteHeader />
      <main id="main">{children}</main>
      <SiteFooter />
      <CartDrawer />
    </CartProvider>
  );
}
