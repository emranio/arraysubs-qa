---
id: 6
title: 'stage-5: immediate cancellation leaves retired renewal order payable'
status: closed
priority: high
created: 2026-08-15T13:54:58.305412421+02:00
updated: 2026-08-15T14:19:29.746338509+02:00
started: 2026-08-15T13:55:56.980147448+02:00
completed: 2026-08-15T14:19:29.746332989+02:00
tags:
    - stage-05
    - stripe
    - cancellation
    - renewal
    - customer-portal
    - payment-migration
claimed_by: bolt-leaf
claimed_at: 2026-08-15T14:19:29.746338409+02:00
class: expedite
---

QA source:
- Progress task: qa/progress task #3, stage-5.
- QA plans: qa/stages/05-checkout/11-stripe-test-card-flow.md and qa/subscription-lifecycle-test/kanban/tasks/030-classic-checkout-with-stripe-sca-card-3ds-at.md.
- Found while browser-verifying the pending Stripe authentication retirement fix for issue #4.

Affected records:
- Subscription #27614; renewal order #27719; Stripe PaymentIntent pi_3U4fzsJG5OzSNVs211sneEhE.
- WordPress customer #470, stripe-sca-fix-20260815@example.test, role customer.

Route and browser context:
- https://mirror-help.arrayhash.com/my-account/view-subscription/27614/
- Browser session qa-fix-stripe-sca.

Reproduction:
1. Create an automatic Stripe renewal that enters authentication_required and publishes the signed verification action for order #27719.
2. Choose immediate customer cancellation in the real portal.
3. Verify Stripe cancellation and reload the cancelled subscription detail.

Expected:
- The exact PI is conclusively cancelled, the subscription becomes cancelled, the exact action context is cleared, and the unpaid renewal order is cancelled/unpayable with the subscription pending-order pointer removed.

Actual:
- PI cancellation and lifecycle status are correct, but order #27719 remains Pending payment, subscription meta _pending_renewal_order_id remains 27719, and the cancelled subscription still renders a Pay link that can reach WooCommerce order payment.

Proof:
- Stripe PI is canceled with amount_received=0; local action URL/intent/retiring/_stripe_intent_id were cleared and attempt result is no_charge.
- Subscription status is arraysubs-cancelled, but order #27719 status is pending and the customer portal displays View Pay.
- Screenshot: qa/artifacts/payment-bug-fixes-20260815/issue-006/cancelled-subscription-payable-renewal.png.

Known scope and counterexamples:
- The remote PI itself cannot be completed after cancellation; the risk is WooCommerce creating a new payment for the still-payable renewal order.
- Previously paid renewal orders #27628/#27663/#27691 are unaffected and must remain processing/paid.
- The fix must fail closed for processing/charged/ambiguous renewal state and must only cancel an exact unpaid renewal order owned solely by this subscription.

Fix plan (2026-08-15):
- Add one shared immediate-cancellation boundary that resolves the exact `_pending_renewal_order_id`, proves sole ownership and renewal identity under the subscription lock, and refuses paid, processing, settlement-unknown, missing, or ambiguous state.
- After any gateway-owned action is conclusively retired, cancel and re-read the exact unpaid renewal order before committing the subscription status; clear only the matching pending-order pointer.
- Keep a final order-pay fail-closed lifecycle guard so a cancelled/expired/waiting-cancellation subscription cannot pay a renewal even if an interrupted cleanup leaves the order open.
- Re-test the existing recovery fixture and a fresh browser cancellation; assert cancelled/unpayable order, cleared pointer, canceled zero-received PI, and unchanged previously paid orders.

Verification (2026-08-15): PASS
- The authorized idempotent recovery call returned `true`. Subscription #27614 remained `arraysubs-cancelled`; `_pending_renewal_order_id` changed from `27719` to empty; waiting-cancellation and all cancellation error records are empty.
- Renewal order #27719 changed from `pending` to `cancelled`, remains unpaid with no paid date, returns `needs_payment=false`, and retains no transaction, Stripe PI/charge, pending-transaction, or settlement-deadline reference. Its exact charge-attempt result remains `no_charge`.
- Previously paid renewal orders #27628, #27663, and #27691 remain `processing` and paid with their original transaction IDs and paid dates.
- The real customer portal now shows order #27719 as `Cancelled` with only `View`; the prior `Pay` action is gone. Direct navigation to its authentic keyed order-pay URL displays: `This order’s status is “Cancelled”—it cannot be paid for.` Browser errors are empty.
- Evidence: `qa/artifacts/payment-bug-fixes-20260815/issue-006/cancelled-renewal-unpayable-after-fix.png` and `qa/artifacts/payment-bug-fixes-20260815/issue-006/cancelled-renewal-order-pay-denied.png`.
