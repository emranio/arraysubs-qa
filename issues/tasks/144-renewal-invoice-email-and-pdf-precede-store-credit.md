---
id: 144
title: Renewal invoice email and PDF precede store credit order adjustments
status: closed
priority: high
created: 2026-10-29T13:29:33.827674+06:00
updated: 2026-10-29T14:07:07.893694+06:00
started: 2026-10-29T13:29:55.591511+06:00
completed: 2026-10-29T14:07:07.893694+06:00
tags:
    - bug
    - pdf-invoices
    - emails
    - store-credit
    - renewals
class: standard
---

Active QA task2/3, scheduled2026-10-29 Asia/Dhaka, plan qa/pdf-invoice-compatibility-2026-10-29/plan.md. Real local WordPress http://localhost:10013; core2.1.0/Pro1.3.0, Woo11.1.2 HPOS, WPO5.16.6, PHP8.2.27. Subscription23976, customer479 qa_pdf_invoice_20261029 / qa.pdf.invoice.20261029@example.test role customer; parent23975, actual new renewal23993; product23964; isolated subscription credit grant5 log23992, customer credit unchanged0.

Repro: enable WPO attachment to ArraySubs Renewal Invoice, grant isolated subscription5 credit through real CreditManager grant-once API, create25 renewal using actual OrderCreation, dispatch actual arraysubs_renewal_invoice_created event. Expected: invoice email/PDF reflects finalized20 amount after automatic5 credit deduction. Actual: EmailManager listener runs at10 before Pro credit listener10 and CheckoutBuilder20; actual local Mailpit message subject QA PDF RENEWAL ORDERING BEFORE sent order total25 with valid attached invoice23993, then final order total20, subscription/customer credit both0. Evidence qa/pdf-invoice-compatibility-2026-10-29/evidence/renewal-email-ordering-before.json plus actual body.html and invoice PDF file; real event and SMTP transport, no mocked credit callbacks and no real gateway/payment. Admin route http://localhost:10013/wp-admin/admin.php?page=wc-orders&action=edit&id=23993; mailbox http://localhost:10010 browser context pdf-fix-mail. Actual runtime/mail proof completed; mailbox browser inspection is part of remaining root verification. Counterexample: no credit adjustment leaves amount unchanged; automatic-payment subscriptions already suppress manual invoices.

Fix plan: send renewal invoice after supported credit/field order adjustments (priority25 after20 and before lifecycle30), reread durable order, suppress invoice for paid/no-amount-due orders, retain manual unpaid invoice behavior and once-only marker. Regression for25->20 final amount, paid/zero/duplicate/automatic suppression, cross-plugin callback ordering. Actual root after probe must show valid PDF/email total20 and final20, unchanged customer credit. Preserve all preexisting draft and QA entries.

[[2026-10-29]] Thu 13:32
Implementation prepared in /tmp/arraysubs-pdf-email-fix on codex/pdf-email-context. Test-first renewal-invoice-email-ordering.php failed against original listener ordering/unguarded dispatch, then passed24 assertions using the actual core EmailManager constructor and installed Pro StoreCredit/CheckoutBuilder registration priorities. Verifies durable25->20 amount, carried fields before mail and lifecycle after mail, duplicate/sent marker, zero-total/full credit, paid/cancelled/refunded/deleted/foreign/unrelated suppression, automatic-payment suppression/manual opt-out, failed transport retry, and core-alone manual flow. Existing74 email-context assertions still pass. Fix moves invoice callback25 after coupons5/credit10/fields20 and before lifecycle30, rereads canonical related order and requires unpaid/payment-due state. Source2324 lines/test163 lines, clean diff check, no lint/Composer/live changes. Independent review requested; root owns actual after-probe and Mailpit/browser/PDF proof.

[[2026-10-29]] Thu 13:35
Implementation handoff: commit b0b82aca6b30ed0191cbbd304825c447db5cc883 is ready for root integration and actual after-script/Mailpit/PDF verification. Standalone test also passes24 assertions without Pro present. Independent reviewer was explicitly requested; response pending. Product source and focused regression are committed in isolated clean worktree; no live changes by this agent. Claim released for root takeover while actual verification continues.

[[2026-10-29]] Thu 14:07
Fixed in core main0b53fc2. Valid active fixture before-active actual order24017 sent25 PDF while final20; after-active order24022 sends20 PDF matching final20. Real5 credit grant is consumed from fixture subscription only; customer credit remains0 and final subscription balance0. Mailpit browser shows amount20/order24022/attachment; extracted actual PDF shows subtotal25,credit-5,total20. Repeat real event sends zero duplicate invoice messages.24 event-ordering regressions pass with actual Pro registration priorities and standalone core. Paid/zero/nonpayable/autopay suppression and successful-only sent marker retained. Initial pending-subscription after probe correctly suppressed an unpayable lifecycle and is documented as such; no guard weakened. Test status restored to pending and all fixture scheduler actions cleared using central helper. Independent review found no blockers.
