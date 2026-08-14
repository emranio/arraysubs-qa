---
id: 128
title: Fix SLT-SYN-04 stale renewal pointer
status: done
priority: medium
created: 2026-08-14T16:56:51.840270948+02:00
updated: 2026-08-14T17:45:41.239192931+02:00
started: 2026-08-14T17:45:41.111588778+02:00
completed: 2026-08-14T17:45:41.111588778+02:00
tags:
    - bug
    - slt-syn-04
class: standard
---

Investigate issues/done-SLT-SYN-04-successful-renewal-leaves-stale-pending-order-pointer.md against task 61, the renewal lifecycle code, Stripe/provider callbacks, and live subscription 12564/order 20230. Reproduce without mutating production evidence, implement the smallest security-safe shared lifecycle fix, add regression coverage, browser-test the real site, prefix the resolved issue done-, then push all repositories to main.

[[2026-08-14]] Fri 17:09
Root-cause trace: a gateway may call payment_complete() on a freshly loaded order while PaymentProcessor/RenewalProcessor retain the original unpaid WC_Order. The success cleanup deletes the pointer, then the stale pending result path writes it back. Current Stripe ProviderPaymentCompletion + Hooks reproduces this object split; core normalization and the final pending-write boundary both need authoritative order state.

[[2026-08-14]] Fri 17:45
PASS. Pre-fix disposable integration reproduced: paid/processed order pointer resurrected, forged paid result accepted while persisted pending, and late older completion erased a newer pointer. Core 69e0fe8 and pro 09ad013 reload authoritative HPOS order state, guard exact pending writes, and exact-delete only the completing order. Post-fix four-case matrix passed; Pro stale/forged normalizer probes passed; fixtures left zero posts/orders/notes/actions, Mailpit unchanged, settings hash unchanged. Live pointer 12564 -> 20230 was compare-and-deleted under the mutation lock after exact paid/processed/customer/history/no-unpaid preconditions; payments/date stayed unchanged and paid-pointer count is zero. Browser session admin-SLT-SYN-04-FIX verified Active/four payments/order 20230 completed USD 18.00 with empty page errors. Report closed as issues/done-SLT-SYN-04-successful-renewal-leaves-stale-pending-order-pointer.md. Independent diff review: no actionable findings.
