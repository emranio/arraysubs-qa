---
id: 5
title: 'stage-6: Initial payment record and email integrity'
status: closed
priority: critical
created: 2026-08-15T09:45:27.538435471+02:00
updated: 2026-08-15T11:40:01.75798681+02:00
started: 2026-08-15T10:20:33.787848961+02:00
completed: 2026-08-15T10:20:33.787848961+02:00
tags:
    - stage-06
    - lifecycle
    - orders
    - subscriptions
    - qa
class: standard
---

References: ../stages/06-initial-lifecycle/01-subscription-record-after-checkout.md, ../stages/06-initial-lifecycle/03-subscription-detail-screen.md, ../stages/06-initial-lifecycle/05-order-page-related-subscriptions.md, ../stages/06-initial-lifecycle/06-emails-on-creation.md

Independently audit admin order/subscription detail, customer portal, exact line-item and subscription cardinality, initial statuses, payment metadata shapes, renewal actions, and creation mail.

[[2026-08-15]] Sat 10:20
Final 2026-08-15 purchase audit: Stripe and Paddle successful orders, subscription cardinality, gateway bindings, portal/admin records, and rendered Mailpit messages passed. Stripe SCA completed one challenge against one order/subscription. Open high findings are formal issues 1 (longstanding duplicate Stripe confirmation notes) and 3 (initial SCA incorrectly sends renewal-verification wording). Full report: qa/artifacts/payment-migration-regression-20260815/final/report.md

[[2026-08-15]] Sat 11:40
Issue-fix follow-up: abandoned Paddle authorization presentation was fixed and passed a fresh real overlay-return flow. Disposable order #27511/subscription #27527 retained only the safe draft provider session; admin/customer showed no connected authorization, zero actions/mail/payment, while existing paid Paddle #7809 still showed its connected Visa 5556 binding and controls. Formal issue #2 is closed; evidence is under qa/artifacts/payment-bug-fixes-20260815/issue-002/.
