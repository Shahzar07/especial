import type { Metadata } from "next";
import { LegalPage } from "@/components/legal-page";
import { site } from "@/lib/config";

export const metadata: Metadata = { title: "Terms" };

export default function Page() {
  return (
    <LegalPage title="Terms">
      <p>
        Ordering from {site.brand} forms a contract on despatch, not on order
        confirmation. If an item turns out to be unavailable after you order, the
        order is cancelled and refunded in full.
      </p>
      <p>
        Products are sold in limited runs. Edition sizes stated on a product page
        are final and the run is not reprinted.
      </p>
      <p>
        Prices are shown in US dollars and exclude shipping and any import duty,
        which is the responsibility of the recipient.
      </p>
      <p>
        All artwork, photography and text on this site remain the property of{" "}
        {site.brand}.
      </p>
    </LegalPage>
  );
}
