import type { Metadata } from "next";
import { LegalPage } from "@/components/legal-page";
import { site } from "@/lib/config";

export const metadata: Metadata = { title: "Privacy" };

export default function Page() {
  return (
    <LegalPage title="Privacy">
      <p>
        We collect an email address when you join the mailing list, and the name,
        address and contact details needed to fulfil an order. Payment card
        details are handled by our payment provider and never reach our servers.
      </p>
      <p>
        The mailing list is used to announce releases and restocks. Every message
        carries an unsubscribe link, and unsubscribing removes the address from
        the list entirely.
      </p>
      <p>
        A single cookie, <code>ml_pass</code>, records that this browser has
        passed the mailing-list sign-up. It contains a timestamp and a signature,
        no personal data, and expires after 180 days.
      </p>
      <p>
        To ask what we hold about you, or to have it deleted, write to{" "}
        {site.email}.
      </p>
    </LegalPage>
  );
}
