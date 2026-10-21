---
id: 80
title: 'Order charge attribution: when no item gives a subscription any amount, the single linked subscription owns the whole order total (legacy branch)'
status: closed
priority: low
created: 2026-10-21T01:34:12.128824257+02:00
updated: 2026-10-21T07:18:54.001892964+02:00
started: 2026-10-21T07:18:54.001892133+02:00
completed: 2026-10-21T07:18:54.001892133+02:00
tags:
    - attribution
    - refunds
    - product-decision
class: standard
---

Active QA task ID and scheduled day: N/A (found 2026-10-20 by the #71-#78 fix verification; fix log qa/issues-65-69-fix-log.md).
Affected IDs: N/A (memory probes L1/L4 in scratchpad fix7x2/verify-attribution-*). Affected users: N/A.
Route: arraysubs/src/functions/order-subscription-link.php arraysubs_get_subscription_order_charge_amounts() legacy branch (~:430-464).
Expected (to decide): a subscription that was charged 0.00 (for example a trial line bought with an ordinary product) is not the owner of the ordinary product's money for refund caps and gateway billing-owner results.
Actual: with no non-zero item share, the single linked subscription owns the whole order total. This predates #72 and changes gateway billing-owner results if altered, so it was left for a product decision.
Proof: attribution fixer not_fixed item and verifier LOW in scratchpad fix7x2/result-attribution.json.

[[2026-10-21]] Wed 07:18
Closed as intended (user decision 2026-10-21): the legacy attribution owner rule stays. No change.
