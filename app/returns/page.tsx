import type { Metadata } from "next";
import { LegalPage } from "@/components/legal-page";
import { site } from "@/lib/config";

export const metadata: Metadata = { title: "Returns" };

export default function Page() {
  return (
    <LegalPage title="Returns">
      <p>
        Unopened items can be returned within thirty days of delivery for a full
        refund of the item price. Return shipping is paid by the customer unless
        the item arrived damaged or incorrect.
      </p>
      <p>
        Opened collectibles can be returned only if they arrived damaged. Send a
        photograph of the damage and the packaging to {site.email} within seven
        days of delivery.
      </p>
      <p>
        Refunds are issued to the original payment method within five working
        days of the return arriving.
      </p>
      <p>
        Orders ship within two working days, tracked. Delivery estimates are
        shown at checkout.
      </p>
    </LegalPage>
  );
}
