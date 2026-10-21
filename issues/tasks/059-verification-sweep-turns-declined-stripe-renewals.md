---
id: 59
title: Verification sweep turns declined Stripe renewals into authentication cases and locks Pay Now
status: closed
priority: high
created: 2026-10-19T11:38:58.100915746+02:00
updated: 2026-10-20T00:35:28.106707759+02:00
started: 2026-10-19T20:29:42.053324943+02:00
completed: 2026-10-20T00:35:28.106707188+02:00
tags:
    - stripe
    - recovery
    - scheduler
    - staging-qa
class: standard
---

Active QA task ID and scheduled day: S03-A (board task #9 in qa/stripe-dev-support-2026-10-18/kanban), phase 2, 2026-10-19.
Plan markdown path: qa/stripe-dev-support-2026-10-18/plan.md.
Affected subscription IDs and order IDs: subscription 68439; failed renewal order 68473 (pi_3USCo3JG5OzSNVs20MNA9LRC, status requires_payment_method, last_payment_error code card_declined / decline_code generic_decline, not an authentication failure). Actions: 100903 arraysubs_sweep_stripe_renewal_verifications (queue runner, 09:30:05Z) then 100937 arraysubs_send_stripe_renewal_verification args [68439,68473,"pi_3USCo3JG5OzSNVs20MNA9LRC",1] (09:30:11Z).
Affected WordPress user/customer: ID 579, sqa-p2-decline / sqa-p2-decline@example.com, role customer.
Exact route: "Pay Now" link in the "Payment failed for subscription #68439" email (Mailpit 2a73AYLye38cWmerwEr4eX) -> https://mirror-help.arrayhash.com/checkout/order-pay/68473/?pay_for_order=true&key=... ; browser session sqa-decline (customer, logged in). Core 4791832 + Pro edaa401, WooCommerce Stripe Gateway 10.9.0 test mode.
Reproduction: have a Stripe renewal fail with an ordinary decline (card 0341); wait for the next arraysubs_sweep_stripe_renewal_verifications run (every 10 minutes); open the failure email's Pay Now link, choose "Use a new payment method", enter 4242 4242 4242 4242 and click Pay for order.
Expected: an ordinary declined renewal stays an ordinary unpaid order; the customer can pay it with a new reusable card (one collection) as the failure email asks ("Please update your payment method").
Actual: after the sweep, order 68473 and subscription 68439 carry _arraysubs_payment_action_intent_id = pi_3USCo3…, _arraysubs_payment_action_url and (order) _arraysubs_payment_action_required_at = 2026-10-19 09:30:06. isRenewalActionRoute() therefore treats the Pay page as an authentication-verification page, and the payment is refused with "Use the saved Stripe payment method shown for this renewal verification." Only the declining card (Visa 0341) is offered. Order 68473 stays failed. No verification email was sent to this customer.
Cause (code reading): PaymentHooksScheduledJobsTrait::sweepStripeRenewalVerifications() (src/Features/AutomaticPayments/Services/Traits/PaymentHooksScheduledJobsTrait.php:572-615) selects every pending/failed/on-hold Stripe renewal order, and StripeRequiresActionTrait::recoverRequiresActionNotice() queues a requires-action notice for any order with a pi_ id, without checking that the PaymentIntent is requires_action or failed with authentication_required.
Proof: order/subscription meta above (WP-CLI); action rows 100903/100937; screenshots s03-decline-paynow-page.png, s03-decline-paynow-filled.png, s03-decline-paynow-after.png.
Scope notes / counterexamples: the genuine authentication case (subscription 68466, order 68494) was queued by its own process_renewal path (action 100934) and did receive the verification email. Together with #56 (Stripe portal card changes refused) and #58, a customer with an ordinary declined Stripe renewal currently has no working self-service path to pay with a different card. No duplicate charge occurred.
Repro video: N/A.

Reproduced on three more ordinary declines: orders 68614 (sub 68599), 68550 (sub 68512) and 68553 (sub 68525) all received _arraysubs_payment_action_required_at = 2026-10-19 09:52:04 from the next sweep.

Deep re-verification 2026-10-19: CONFIRMED WITH CORRECTIONS (high, high confidence). Fresh order 68822 marked by sweep 101129 (11:37:04Z) and then refused a new card; pre-sweep control 68819 paid normally. Corrections: the sweep selects only renewals with _arraysubs_charge_attempt_gateway=stripe; delivery marks the outbox 'superseded' but never clears the action meta (clearTerminalFailureActionContext not called), so the lock is permanent; My Account Pay link is locked too and the saved declining card is refused as well; also hits soft-retry windows (68553); affected orders today: 68473, 68550, 68553, 68614, 68694, 68822, 68903. Escape via Link/Store API exists (#63) and pre-sweep payment leads to #62 - fix together. Full report: qa/stripe-issues-56-61-deep-verification-2026-10-19.md

Closed 2026-10-19 (UTC) after fix + live verification. Changes are in the working trees and NOT committed (user request). Full record: qa/stripe-issues-56-64-fix-log.md.

Fix 2026-10-19 (uncommitted; log qa/stripe-issues-56-64-fix-log.md): the verification sweep publishes a requires-action notice only for provider-proven authentication intents (local context, or a remote intent in `requires_action` / authentication failure whose metadata names this renewal); ordinary declines end as outbox `superseded` without markers. Delivery and the sweep clear stale markers of zero-money terminal declines (re-check throttled to once per order per hour). Subscription-level markers count only when they name this order. A customer's own on-session 3DS on the Pay page is no longer published as an off-session verification. Files: core `StripeRequiresActionTrait.php`, `Services/StripeSubscriptionCompatibility.php`.
Verified live 2026-10-19: sweep 101447 cleared stale markers on 68473, 68550, 68614, 68694, 68903, 69022 (genuine authentication orders kept theirs); T10 (new decline 69222: outbox `superseded`, no markers, Pay page offers every method); T1 (68473 Pay page unlocked and paid once); M2 (abandoned on-session 3DS on 68614 published no markers; the abandoned PI was cancelled when the order was paid).
Review round 2: a customer-abandoned on-session 3DS attempt on the renewal PaymentIntent no longer blocks later attempts. Live PASS 2026-10-19 20:47 on 69222: retry cancelled the abandoned PI (0 received) and charged the card on file (declined generic_decline → retry 2 scheduled).
Review round 3: a customer's unfinished Pay-page attempt gets 30 minutes (from when the requires-action webhook first saw it) before a renewal attempt cancels it. Live PASS 2026-10-19 21:49 on 69406: retry deferred while the 3DS challenge was open, customer completed, one charge. A second abandoned visit (WooCommerce-created PaymentIntent) is filed as #65.
