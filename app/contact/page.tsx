import type { Metadata } from "next";
import { LegalPage } from "@/components/legal-page";
import { site } from "@/lib/config";

export const metadata: Metadata = { title: "Contact" };

export default function Page() {
  return (
    <LegalPage title="Contact">
      <p>
        Write to <a href={`mailto:${site.email}`}>{site.email}</a>. We answer
        within two working days.
      </p>
      <p>
        For an order query, include the order number from your confirmation
        email. For a damaged delivery, attach photographs of the item and the
        outer packaging.
      </p>
      <p>Press and stockist enquiries go to the same address.</p>
    </LegalPage>
  );
}
