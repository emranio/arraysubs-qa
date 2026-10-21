---
id: 63
title: Store API order payment (Link) bypasses the renewal verification lock and leaves stale action markers
status: closed
priority: medium
created: 2026-10-19T16:00:57.076706302+02:00
updated: 2026-10-20T00:35:28.113406625+02:00
started: 2026-10-19T20:29:42.059209108+02:00
completed: 2026-10-20T00:35:28.113405914+02:00
tags:
    - stripe
    - store-api
    - express-checkout
    - staging-qa
class: standard
---

Active QA task ID and scheduled day: deep re-verification of #56-#61, round 2 gap B (2026-10-19). No board task; report: qa/stripe-issues-56-61-deep-verification-2026-10-19.md.
Plan markdown path: qa/stripe-dev-support-2026-10-18/plan.md.
Affected subscription IDs and order IDs: subscription 68770, renewal order 68822 (locked by the #59 sweep at 11:37:04Z).
Affected WordPress user/customer: 589 sqa-dv59-after@example.com, role customer; Stripe customer cus_VTB1PicZN8Kd3H.
Exact route: My Account > Orders > Pay for order 68822 (/checkout/order-pay/68822/?pay_for_order=true&key=...), "Pay securely with Link" (Stripe test sandbox account sqa-dv2-link-1792414105@example.com, card 4242), which posts to POST /wp-json/wc/store/v1/checkout/68822. Browser session dv2b-cust (customer).
Reproduction: open a renewal order that carries _arraysubs_payment_action_* markers (the classic Pay form refuses new cards there, #58/#59); click Pay securely with Link and pay.
Expected: the renewal-action rules apply on every payment route (or the order is not locked in the first place), the subscription stays bound to a reusable method, and order/customer data is not overwritten by wallet data.
Actual: the order was paid at 12:49:46Z (pi_3USG0ZJG5OzSNVs21Rq8uGbe, ch_3USG0ZJG5OzSNVs21H2c5wUt) with no ArraySubs refusal or log. The old declined PI stayed requires_payment_method with 0 received (one collection, no duplicate). Subscription 68770 was silently rebound to the Link PaymentMethod pm_1USG0TJG5OzSNVs2Y8ki6bwm (customer=null, not reusable off-session; the delegate's own probe now returns renewal_payment_method_binding_invalid). The _arraysubs_payment_action_* markers stayed on the order and subscription. WooCommerce Store API replaced the order's and the customer's billing email with the Link email and cleared billing and shipping phone. ArraySubs logged ERROR "Subscription checkout creation failed for order #68822 (arraysubs_checkout_recovery_ambiguous_item)" (failed closed).
Cause (code reading, verified live): the renewal-action gate is only on woocommerce_before_pay_action (StripeSubscriptionCompatibility.php:96, 446-455), which the Store API CheckoutOrder route never fires; ArraySubs' Store API validators do not check the lock (orderRequiresReusableMethod is false for renewals; isRenewalActionRoute cannot match a REST request). Rebinding is #62's StripeOrderContextBridge path, reached three times (payment_complete, official charge.succeeded webhook, metadata retry 101229). The paid-sibling cleanup refuses to retire a plain-decline PI, so the markers persist.
Proof: qa/stripe-dev-support-2026-10-18/evidence/dv2-express-*.json|txt, snapshots/sub-68770-dv2-express-after-link-pay.json, screenshots/dv2-express-*.png, capture/lifecycle-events.jsonl (renewed + grace_recovered with path /wp-json/wc/store/v1/checkout/68822).
Scope notes / counterexamples: Amazon Pay and a direct owner POST to the route bypass the same way by code but were not tested (Amazon sandbox needs buyer credentials). The stale subscription-level markers will probably lock the next renewal's classic Pay page (code-derived, unverified; next renewal actions 101230/101231 on 2026-10-20). Severity: the bypass alone is medium; the rebinding part is tracked in #62 (high).
Repro video: N/A.

Closed 2026-10-19 (UTC) after fix + live verification. Changes are in the working trees and NOT committed (user request). Full record: qa/stripe-issues-56-64-fix-log.md.

Fix 2026-10-19 (uncommitted; log qa/stripe-issues-56-64-fix-log.md): Store API order payment is refused with 409 `arraysubs_renewal_verification_required` (at `rest_dispatch_request`, before WooCommerce writes anything; route match case-insensitive) while the renewal's PI awaits authentication; the check fails closed when Stripe cannot be read. Express checkout buttons are hidden on automatic renewal Pay pages. When a renewal is paid another way, its unfinished zero-money Stripe PI is cancelled. Renewal orders no longer enter checkout subscription creation. Files: core `Services/StripeSubscriptionCompatibility.php`, `StripeRequiresActionTrait.php`, `SubscriptionCreationTrait.php`.
Verified live 2026-10-19: T6 (68846: `POST /wp-json/wc/store/v1/checkout/68846` and the unversioned route -> 409, order and billing data unchanged; control 68614 -> normal 400); express buttons absent in T1/T5/T6; B1/M2 abandoned PIs cancelled with 0 received. Known WooCommerce core behaviour: the Store API still overwrites billing data for unlocked renewals (express hidden mitigates it).
Review round 2: the cleanup of the renewal's own unfinished PaymentIntent also runs on `woocommerce_payment_complete`, so a success webhook that arrives before WooCommerce marks the order paid cannot skip it. Renewal invoices whose payment is still settling no longer get a second invoice/charge.
Review round 3: the settling guard also covers a paid invoice whose renewal processing has not run yet. Pay-page bank debits are still not "settling" for the grace sweeps: filed as #66.
Review round 4: a stale settlement deadline is cleared before every new charge attempt; a refunded or cancelled paid invoice no longer blocks billing, and a note explains a pause when a paid invoice waits over an hour for processing.
