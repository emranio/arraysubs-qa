---
id: 66
title: 'Stripe: a Pay-page bank debit or payment under review is not treated as settling by the grace sweeps'
status: closed
priority: medium
created: 2026-10-19T23:53:15.975220483+02:00
updated: 2026-10-21T01:33:16.439306384+02:00
started: 2026-10-20T09:51:18.266135357+02:00
completed: 2026-10-21T01:33:16.439304892+02:00
tags:
    - stripe
    - grace
    - settlement
    - pay-page
    - staging-qa
class: standard
---

Active QA task ID and scheduled day: N/A (found by code review round 3 during the #56-#64 fix work, 2026-10-19). Fix log qa/stripe-issues-56-64-fix-log.md (Review round 3, R3-ctx); review notes qa/stripe-dev-support-2026-10-18/evidence/fix/rev3-round2-review.md.
Affected subscription IDs and order IDs: N/A (not reproduced live; code-level).
Affected WordPress user/customer: N/A.
Exact route: Pay page of a failed automatic Stripe renewal, paid with a delayed method (SEPA Direct Debit, ACH) or a card payment Stripe Radar holds for review.
Reproduction (code reading):
1. An automatic Stripe renewal fails; the customer pays it on the Pay page with a bank debit, or the card payment goes to review.
2. WooCommerce Stripe stores a transaction id and puts the order on-hold (class-wc-stripe-webhook-handler.php:1367-1374 for payment_intent.processing). No `_arraysubs_pending_deadline` is written, because ArraySubs did not create that charge.
3. arraysubs_is_awaiting_gateway_settlement() (AutomaticPayments PaymentHooksRenewalsTrait::filterAwaitingGatewaySettlement) only honours `_arraysubs_pending_deadline`, so the hourly overdue sweep (RecurringBilling Hooks::processActiveToOnHoldBatch / processOnHoldToCancelBatch) holds and later cancels the subscription, and RenewalProcessor::cancelOverdueSubscription() cancels the settling order.
4. The debit settles days later against a cancelled order and subscription.
Expected: while a renewal order is unpaid with a transaction id in pending/on-hold, grace waits for settlement for a bounded window (as it does for ArraySubs' own processing charges), then resumes.
Actual: grace runs as if nothing was paid.
Proof: code references above.
Scope notes: since 2026-10-19 RenewalProcessor::hasSettlingRenewalPayment() stops a second invoice and charge for that cycle; only the grace sweeps are affected. ArraySubs-created processing charges (markOrderPendingDeadline) and Mollie are not affected.

[[2026-10-21]] Wed 01:33

Closed 2026-10-20 (UTC) after fix + live verification. Changes are in the working tree and NOT committed (user request). Full record: qa/issues-65-69-fix-log.md; investigation qa/fix-65-69/evidence/inv-66-settlement.md.

Fix 2026-10-20 (uncommitted): when WooCommerce Stripe puts an unpaid Stripe renewal order on hold with a charge (bank debit processing, payment held for review, uncaptured authorization), `StripeAsyncFailureTrait::rememberSettlingPayment()` (hooked on `woocommerce_order_status_on-hold`) stores `_arraysubs_settlement_txn` and `_arraysubs_settlement_until` (the method's settlement window, at least 7 days). `PaymentHooksRenewalsTrait::orderIsAwaitingSettlement()` treats the order as settling while it is on hold or pending with that same transaction id and the window has not ended; the subscription-level lookup includes these orders. After the window the grace period resumes; ArraySubs never fails or charges such an order.
Verified live 2026-10-20 07:57 UTC: fixture sub 69756 / invoice 69807 paid with test ACH account 000000000009 (stays processing) using WooCommerce Stripe metadata (`scripts/paypage-like-bank-payment.php`, because the Pay page's test bank settles instantly) → Stripe `payment_intent.processing` → WooCommerce Stripe on-hold with `py_…` → window stamped (+7 days) → `GracePolicy::snapshot()` `awaiting_gateway_settlement=true` (false before the fix). The renewal action run as www-data during settlement made no Stripe call (no second charge). Window moved into the past → false; restored → true. Not tested live: Radar review (needs a Stripe review rule) — same on-hold path in code.
Review round 1 (M1): a paid renewal put on hold (dispute, a review opened after payment, an admin's change) is not stamped as settling; pending/failed holds with a charge still are (probe `qa/fix-65-69/evidence/r1-m1-probe.json`). Related, filed separately: #71 (the Pay page still accepts a second payment while an automatic debit is in flight).
Review round 5 (uncommitted, rolled-back probes; details in the fix log): a failed renewal order that a retry put back into settlement (a new bank debit with a transaction id inside its settlement deadline, Mollie SEPA) now counts as settling, so the next queued attempt neither creates a second order nor starts a second debit while the first one settles (R5-1b).

Review rounds 6-8: a failed invoice keeps the cycle while its retry debit still has a transaction id and its deadline (R6-C10, no second invoice); the #66 settlement window is trusted only while the order is on hold.
