---
id: 141
title: Subscription emails omit WooCommerce order context for PDF invoice attachments
status: closed
priority: high
created: 2026-10-29T13:10:09.716363+06:00
updated: 2026-10-29T14:07:07.781449+06:00
started: 2026-10-29T13:10:56.507025+06:00
completed: 2026-10-29T14:07:07.781448+06:00
tags:
    - bug
    - pdf-invoices
    - emails
    - integration
class: standard
---

Active QA: task 2 in qa/pdf-invoice-compatibility-2026-10-29/plan.md; scheduled day 2026-10-29 Asia/Dhaka. Environment actual local WordPress http://localhost:10013, PHP8.2.27, ArraySubs2.1.0/Pro1.3.0, Woo11.1.2, WP Overnight5.16.6. Existing disposable subscription23967, order23966, product23964, customer479 qa_pdf_invoice_20261029 / qa.pdf.invoice.20261029@example.test, role customer. Admin context for WooCommerce Settings -> Emails and PDF Invoices Attach to settings; exact admin routes /wp-admin/admin.php?page=wc-settings&tab=email&section=arraysubs_renewal_invoice and /wp-admin/admin.php?page=wpo_wcpdf_options_page&tab=documents&section=invoice. No existing customer data altered.

Read-only runtime repro: populate RenewalInvoiceEmail with order_id23966/subscription23967; internal protected order is WC_Order23966 but inherited WC_Email::$object remains null. Observe woocommerce_email_attachments arguments: order=null. Expected: actual order object supplied for selected PDF invoice email attachments. Actual: WPO Main::attach_document_to_email returns early for nonobject order, preventing attachments. Evidence being saved in qa/pdf-invoice-compatibility-2026-10-29/email-probe.php and evidence/email-context-before.json. Browser configured attachment/send proof pending; no claim of browser completion yet. Source BaseSubscriptionEmail::populate_placeholders assigns only $this->order, inherited get_attachments passes $this->object. Counterexample: native Woo email supplies WC_Order. Scope includes all core subscription emails deriving from that base and order-aware Pro subclasses; no claim for email types without an associated order. Fix plan: synchronize the standard WooCommerce object with the current validated order, clear it on each trigger with missing/deleted order and invalid subscription, preserve nonpersistent native previews, verify same singleton cannot retain another customer order; test real WPO PDF attachment and browser email previews.

[[2026-10-29]] Thu 13:15
Implementation prepared in /tmp/arraysubs-pdf-email-fix on codex/pdf-email-context. Test-first standalone regression failed on the original implementation, then passed 61 assertions across 25 core email classes: actual WC attachment order context, replacement on next customer, absent/deleted order cleanup, canonical parent/renewal and guest identity handling, unrelated/cross-customer rejection, every invalid-subscription trigger including admin overrides, native zero-ID preview preservation. Existing email-locale-defaults core checks also pass (Pro sibling absent in isolated checkout). No browser/site/config mutations by this agent. Root owns real WPO attachment/UI verification and final merge/changelog. An unrelated existing lifecycle-email-events standalone script fails before executing due missing LocalizedEmailOptions trait include, also present on baseline; not changed here.

[[2026-10-29]] Thu 13:19
Scope extended to Pro Store Credit Used (the only order-aware Pro credit email, which extends WC_Email directly). Read-only runtime evidence pro-email-context-before.json shows its actual trigger with email disabled retains null WC object for fixture order23966; no sends/config mutation. Pro fix committed separately in /tmp/arraysubspro-pdf-email-fix on codex/pdf-credit-email-context: reload durable order, reject unsaved/deleted/changed-owner snapshots, clear prior PDF/recipient before any early return, expose valid order after customer resolution. Test-first regression passes28 assertions, including singleton recipient isolation. Other three Pro credit emails remain orderless. Independent reviewer caught a redundant unverified reload in RenewalRequiresVerificationEmail; core follow-up removes it and drops order ID/amount/action URL when an explicitly supplied order is rejected, preserving valid linked and subscription-level fallback authentication URLs. Root handles integration/browser testing; no site/code deployment by this agent.

[[2026-10-29]] Thu 13:21
Implementation handoff complete. Core commits36a38df and1d44d23 in /tmp/arraysubs-pdf-email-fix; Pro commit6704509 in /tmp/arraysubspro-pdf-email-fix. Independent pdf_fix_review reviewed all final diffs and reported no remaining blocker. Final focused regressions:74 core assertions across25 email classes,483 existing core locale assertions,28 Pro assertions. Production files545/163/336 lines and tests222/97 lines. Root is integrating and owns actual configured WPO attachment/browser/mail tests and final issue closure; claim released for root takeover.

[[2026-10-29]] Thu 14:07
Fixed in core main0b53fc2 and Pro main126f590. Actual SMTP BEFORE core email had zero attachments; AFTER has valid invoice23966 PDF. Actual Pro Store Credit Used for order24022 has PDF attachment1; browser Mailpit confirms invoice filename/order/credit5. Standard order context is fresh, canonically verified and cleared for missing/deleted/foreign orders; unsafe verification-email reload removed. Tests74 core context,28 Pro context,559 locale/merchant overrides across29 classes and existing lifecycle/core+Pro outcome contracts pass. Negative and preview paths retained. Plan/report/evidence qa/pdf-invoice-compatibility-2026-10-29/. Config restored and PDF plugin active. No screenshots sign-off claimed due independent Chrome capture failure on blank pages; actual browser/Mailpit/attachment observations recorded.
