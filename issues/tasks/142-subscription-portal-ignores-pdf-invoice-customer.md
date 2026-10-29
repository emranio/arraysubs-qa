---
id: 142
title: Subscription portal ignores PDF invoice customer visibility settings
status: closed
priority: medium
created: 2026-10-29T13:16:23.619629+06:00
updated: 2026-10-29T14:07:07.819779+06:00
started: 2026-10-29T14:07:07.793614+06:00
completed: 2026-10-29T14:07:07.819778+06:00
tags:
    - bug
    - pdf-invoices
    - customer-portal
    - settings
class: standard
---

Active QA task2/3, scheduled2026-10-29 Asia/Dhaka, plan qa/pdf-invoice-compatibility-2026-10-29/plan.md. Environment actual http://localhost:10013; ArraySubs2.1.0/Pro1.3.0 active, Woo11.1.2, official WP Overnight5.16.6, PHP8.2.27. Customer479 qa_pdf_invoice_20261029 / qa.pdf.invoice.20261029@example.test customer role; subscription23976; pending parent order23975 and real core-built pending renewal23984; product23964. Isolated browser pdf-fix-customer.

Repro: set WP Overnight Allow My Account invoice download=Always, open http://localhost:10013/my-account/as-view-subscription/23976/?qa_pdf=always-before with no generated invoice on either order. Expected: invoice action offered for eligible orders in accordance with WP Overnight, like the native WooCommerce Orders table. Actual: only View/Pay (renewal Pay remains guarded) and no Invoice. Evidence qa/pdf-invoice-compatibility-2026-10-29/evidence/always-before.png and .txt, policy-always-before.json confirms native_actions includes invoice while invoice_exists=false. Native Orders browser control captured during same run. Invoice exists-only default is a counterexample where absent button is expected; Always/status-specific do not require a preexisting invoice. Source also bypasses Never/disabled policies, custom button labels and download/new-window preference by hardcoding existence, Invoice label and target blank; existing-invoice cases initially hit issue140 before the policy can apply.

Fix plan: reuse official WC invoice actions, leave provider customer availability, label, PDF/XML, access-key, pretty-link and target behavior authoritative; never create invoices from list rendering. Browser matrix must cover available/always/custom allowed and denied/never/document disabled, download/open modes, active/inactive PDF and core/Pro. Preserve existing View/Pay/payment settlement and no duplicated Cancel action. No claim that every source-observed variation was reproduced before fixing; final evidence will enumerate actual runs.

[[2026-10-29]] Thu 14:07
Fixed in core main0b53fc2 via native provider invoice actions. Browser matrix passes available(existing/absent),Always absent,custom pending,custom completed denied/allowed,Never existing,document disabled,custom escaped label,download empty target,display target_blank+safe rel,pretty links,plugin inactive,core-only and Pro. Native View/Pay remain and Cancel is not introduced. Pre/post state proves listing does not initialize invoices; actual first download creates invoice7 and repeat keeps7. Optional XML path is regression-covered; no installed EDI extension so no EDI end-to-end claim. Evidence and limitations in active report.
