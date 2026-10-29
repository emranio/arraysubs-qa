---
id: 143
title: Renewal orders drop PDF invoice customer identification metadata
status: closed
priority: medium
created: 2026-10-29T13:23:12.177532+06:00
updated: 2026-10-29T14:07:07.857654+06:00
started: 2026-10-29T13:23:54.275296+06:00
completed: 2026-10-29T14:07:07.857654+06:00
tags:
    - bug
    - pdf-invoices
    - renewals
    - integration
class: standard
---

QA task2/3, scheduled2026-10-29 Asia/Dhaka; plan qa/pdf-invoice-compatibility-2026-10-29/plan.md. Real local site http://localhost:10013; ArraySubs2.1.0, Pro1.3.0, Woo11.1.2 HPOS, WP Overnight5.16.6, PHP8.2.27. Customer479 qa_pdf_invoice_20261029 / qa.pdf.invoice.20261029@example.test customer; subscription23976; product23964; parentorder23975; fresh canonical core-built renewal23988. Browser pdf-fix-admin admin ID1 route http://localhost:10013/wp-admin/admin.php?page=wc-orders&action=edit&id=23975 and corresponding id23988.

Repro: enable WPO General Checkout Field label QA Customer VAT and Treat as VAT; set isolated original order customer identification QA-VAT-20261029 through its canonical _wpo_ips_checkout_field meta; build an actual renewal with RecurringBilling OrderCreation::createRenewalOrder(23976). Expected: customer identification retained from original-order snapshot as WPO does for supported subscription renewals, without cloning invoice metadata. Actual: parent meta/VAT helper=QA-VAT-20261029; renewal meta empty and VAT helper=null. Runtime proof qa/pdf-invoice-compatibility-2026-10-29/evidence/vat-values-before.jsonl; admin order snapshots/screenshots being captured before fix. No claim the default Simple PDF automatically prints this optional field. Native WPO subscription bridge explicitly carries _wpo_ips_checkout_field plus _peppol_endpoint_eas/_peppol_endpoint_id, and removes _wcpdf_* document identity. ArraySubs builds fresh orders copying addresses only. Source/runtime proof establishes optional field loss; EDI Peppol external network untested.

Fix plan: narrow WPO integration at existing arraysubs_renewal_order_built hook for every renewal creation path; canonically verify parent relation/customer; preserve existing target meta; copy only the three upstream customer identity keys using Woo data APIs, no invoice number/date/settings, skip empty/corrupt values. Test genuine new renewal admin billing field, helper values, HPOS persistence, inactive plugin behavior and no duplicated invoice metadata. Restore original settings; fixtures only.

[[2026-10-29]] Thu 13:23
Implementation plan: add a small RecurringBilling hook service preserving the three upstream WPO customer identity keys from canonically verified parent orders, including while WPO is temporarily disabled so existing order snapshots survive. Test authorization/identity, existing empty target metadata, corrupt values, independent plugin activation, and exclusion of invoice numbering/date/settings. Root retains all actual site/configuration/browser ownership.

[[2026-10-29]] Thu 13:25
Isolated source implementation at /tmp/arraysubs-pdf-metadata-fix adds PdfInvoiceCustomerMetadata (59 lines) on arraysubs_renewal_order_built priority20. Both original and renewal are verified through arraysubs_order_belongs_to_subscription (including customer identity); an original cannot be its own renewal. Narrow upstream customer identity whitelist only; existing renewal metadata including intentionally empty/null is preserved. Original-order identity is retained even if WPO is temporarily deactivated, without loading or calling any third-party symbol. Standalone regression failed on original tree and now passes36 assertions on PHP8.2.27, covering identity boundaries, original snapshot/leading zeros, no document-number/date/settings cloning, invalid input, repeated events and WC data-store saves. Independent review requested; root owns actual HPOS/browser proof and final merge/closure.

[[2026-10-29]] Thu 13:26
Independent review by pdf_fix_review found no blockers: canonical hook timing, box/bundle parent ownership, whitelist, zero/leading-zero identifiers, explicit overrides, idempotence and inactive-plugin snapshot preservation all align. Isolated change committed on codex/pdf-renewal-metadata; ready for root to integrate and perform actual HPOS/browser verification. Claim released for root takeover; issue remains in-progress until that verification and merge are complete.

[[2026-10-29]] Thu 14:07
Fixed in core main0b53fc2. Browser admin original23975 shows QA Customer VAT=QA-VAT-20261029, old actual renewal23988 lacks it, new actual renewal24009 shows it. Real VAT helper returns preserved value and new invoice number remains empty. Narrow canonical renewal-built service preserves exactly three upstream identity fields, existing target overrides and independent document identity;36 regressions pass including invalid/foreign/empty/leading-zero/idempotent cases. Retains stored billing snapshot with plugin temporarily inactive without runtime dependency. Peppol external network/EDI not claimed tested. Report/evidence and full restoration verification saved in active QA folder.
