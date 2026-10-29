---
id: 140
title: Existing PDF invoice crashes subscription portal related-orders table
status: closed
priority: high
created: 2026-10-29T12:57:50.963579+06:00
updated: 2026-10-29T14:07:07.744969+06:00
started: 2026-10-29T13:07:35.97183+06:00
completed: 2026-10-29T14:07:07.744969+06:00
tags:
    - bug
    - customer-portal
    - pdf-invoices
    - customer-report
class: standard
---

Active QA task: 2; scheduled day: 2026-10-29 Asia/Dhaka. Plan: qa/pdf-invoice-portal-2026-10-29/plan.md. Investigation/reproduction only; no fix requested.

Environment: real local WordPress http://localhost:10013, ArraySubs 2.1.0, Pro 1.3.0 active, WooCommerce 11.1.2, official WP Overnight PDF Invoices & Packing Slips for WooCommerce 5.16.6, PHP 8.2.27. Plugin installed and activated on explicit user instruction. WooCommerce coming-soon baseline yes was temporarily set to no for the isolated customer test and will be restored.

Subscription ID: 23967. Order ID: 23966. Product ID: 23964. WordPress user/customer ID: 479; login qa_pdf_invoice_20261029; email qa.pdf.invoice.20261029@example.test; role customer. Disposable fixtures only, no real purchase or payment.

Exact route/context: http://localhost:10013/my-account/as-view-subscription/23967/?qa_pdf_phase=existing-invoice ; isolated agent-browser pdf-repro-customer logged in as that customer.

Steps: 1. Create the pending subscription and linked pending order using the existing core helper and canonical parent-order linker (qa/pdf-invoice-portal-2026-10-29/fixture.php setup). 2. Open the subscription with no invoice: related order renders View and Pay. 3. Initialize and save its invoice using official wcpdf_get_document("invoice", $order, true) (fixture.php generate). 4. Open the same subscription URL.

Expected: subscription page continues to load, related order includes a valid customer invoice action when an invoice exists. Actual: HTTP 500 and WordPress critical-error page, table stops before its order row. PHP log at 2026-10-29 06:57:08 UTC confirms Uncaught Error: Call to undefined method WPO\IPS\Documents\Invoice::get_endpoint_url() at arraysubs/src/Features/CustomerPortal/views/view-subscription.php:800.

Proof: qa/pdf-invoice-portal-2026-10-29/evidence/02-subscription-no-invoice.png and .txt; 03-subscription-existing-invoice-fatal.png and .txt; runtime-no-invoice.json; runtime-invoice-created.json. Browser Navigation Timing reports responseStatus 500. Before: invoice_exists=false and View/Pay visible. After: invoice_exists=true; Invoice object method is not callable; official endpoint get_document_link is callable. Same order/customer throughout.

Scope/counterexamples: no-invoice portal case passes. The released plugin still defines wcpdf_get_invoice but deprecates it in favor of wcpdf_get_document; the broken URL method is separate. Normal WooCommerce Orders comparison and repeat reproduction are in progress. No claim that merely renaming the document getter fixes URL generation. No Pro-specific invoice integration found.

[[2026-10-29]] Thu 12:59
Control and repeat completed: for the same customer 479 and order 23966, normal /my-account/orders/?qa_pdf_phase=control shows an Invoice action. Clicking that real action opens a PDF in a new browser tab; document.contentType=application/pdf and HTTP status=200 (evidence/04-woocommerce-orders-invoice-visible.png, 05-woocommerce-invoice-opens.png, pdf-browser-response.txt). Reopening the subscription with ?qa_pdf_phase=repeat again reports status=500 and criticalError=true (repeat-browser-response.txt). The confirmed failure is in the core ArraySubs URL generation, not invoice creation or PDF delivery. No-invoice absence follows the PDF plugin default of only showing existing invoices. Both plugins were active for this reproduction; core-only activation was not tested. No source fix made. Corrective direction: use the modern document getter and supported endpoint/document link or ordinary WooCommerce invoice-action integration, respecting customer availability. Changing only the getter leaves the fatal URL method intact.

[[2026-10-29]] Thu 13:01
Final state: woocommerce_coming_soon restored to baseline yes; official PDF plugin 5.16.6 remains active as requested; core and Pro remain active. Disposable fixture records retained for the next fix/retest (no next renewal date, no payment). Valid PDF confirmed with HTTP200, application/pdf, 229192 bytes and %PDF-1.7 header. The headless native PDF viewer screenshot is blank; delivery evidence is the actual Invoice action and response/file metadata. Both core/pro working trees unchanged. Full result and limitations: qa/pdf-invoice-portal-2026-10-29/report.md. All isolated browser sessions closed. Defect remains open; no product fix was made.

[[2026-10-29]] Thu 13:07
User authorized fixing, production hardening, thorough browser verification and an audit for other WP Overnight integration defects on 2026-10-29. New plan: qa/pdf-invoice-compatibility-2026-10-29/plan.md. Fix plan: remove deprecated document lookup and invalid URL construction; consume the official customer invoice action so PDF visibility, access and link settings remain owned by WP Overnight; preserve ArraySubs payment-settlement protections and ownership-filtered order history. Verify customer settings, core/Pro, inactive plugin, actual PDF requests and unauthorized access before closing.

[[2026-10-29]] Thu 14:07
Fixed and merged core main0b53fc2. Active fix plan qa/pdf-invoice-compatibility-2026-10-29/plan.md; final report in that directory/report.md. Existing invoice now renders HTTP200; actual portal download is valid PDF, native/pretty routes work, core-only and Pro cases pass, inactive PDF plugin leaves View/Pay. Controlled provider exceptions do not crash the table. 19 portal regressions plus actual browser/download evidence pass. Deprecated getter and undefined document URL method removed from this rendering path. Both canonical drafts updated appropriately; source files all below3000 lines. Original reproduction evidence retained.
