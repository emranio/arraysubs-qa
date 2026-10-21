---
id: 64
title: Same-payment-method Stripe events wipe renewal failure and retry state
status: closed
priority: medium
created: 2026-10-19T16:00:57.203090361+02:00
updated: 2026-10-20T00:35:28.115379773+02:00
started: 2026-10-19T20:36:21.123709052+02:00
completed: 2026-10-20T00:35:28.115379142+02:00
tags:
    - stripe
    - retry
    - dunning
    - webhook
    - staging-qa
class: standard
---

Active QA task ID and scheduled day: deep re-verification of #56-#61, round 2 gap A (2026-10-19). No board task; report: qa/stripe-issues-56-61-deep-verification-2026-10-19.md.
Plan markdown path: qa/stripe-dev-support-2026-10-18/plan.md.
Affected subscription IDs and order IDs: 68881 (failed renewal 68903), 68996 (failed renewal 69022, queued retry action 101277); earlier natural cases 68439/68473, 68599/68614, 68665/68694.
Affected WordPress user/customer: 591 sqa-dv60-ladder@example.com, 592 sqa-dv2-reset@example.com (role customer); earlier 579, 581, 585.
Exact route: Stripe webhook payment_method.updated delivered to https://mirror-help.arrayhash.com/wp-json/arraysubs/v1/webhooks/arraysubs_stripe; triggered with WC_Stripe_API::request(['billing_details'=>['name'=>'Sqa dv2 reset test']], 'payment_methods/<bound pm>', 'POST') in WP-CLI; admin detail page (session dv2a-admin) and customer subscription page (dv2a-cust).
Reproduction: have a Stripe renewal fail (failure meta set, Retry Payment visible, listed in Pro Audits > Renewal Failures); trigger any event for the SAME bound PaymentMethod (billing-details edit, Stripe redelivery of an earlier refused checkout event, card-updater event).
Expected: an unchanged binding does not erase the open failure; Retry buttons, the Renewal Failures row and the retry counter stay consistent with the still-unpaid renewal and any queued retry.
Actual: 68881 at 13:13:47Z and 68996 at 13:25:17Z: all failure/retry keys (_last_payment_failure*, _payment_retry_attempts/_last_attempt_at/_next_attempt_at, _renewal_failure_resolved*) deleted; developer recovery reports 0/null; admin and customer Retry Payment buttons disappeared; both subscriptions dropped out of Pro Renewal Failures; the failed orders stay unpaid. On 68996 queued retry 101277 stayed pending, then ran as attempt 0 and skipped the arraysubs_pre_retry_charge_verification filter (no double charge occurred; WC Stripe's own stored-intent checks still ran).
Cause (code reading, verified live): StripeWebhookEventsTrait.php:508-513 calls arraysubs_reset_payment_retry_attempts() after every successful bind; because #56's verifier only lets same-PM binds through, every bind that reaches it is a same-PM bind. The helper is documented for successful charges, gateway-sync reconciliation and cancellation only.
Proof: qa/stripe-dev-support-2026-10-18/evidence/dv2-reset-*.json|txt (before/after state, Stripe events, gateway log, settings before/after diff), screenshots/dv2-reset-*.png.
Scope notes / counterexamples: wipes on 68439/68599/68665 earlier today came from Stripe redeliveries of checkout events that had been refused (HTTP 500) before the checkout binding existed (#56 side effect). The queued-retry effects need a soft category or stop_on_hard_decline=false today because of #57 (used only inside a restored settings window 13:19:41-13:26:08Z, diff all_identical). The woocommerce_payment_token_added rebind listener is never fired on this stack. Severity medium: no money movement, but merchants and customers silently lose recovery affordances.
Repro video: N/A.

Closed 2026-10-19 (UTC) after fix + live verification. Changes are in the working trees and NOT committed (user request). Full record: qa/stripe-issues-56-64-fix-log.md.

Fix 2026-10-19 (uncommitted; log qa/stripe-issues-56-64-fix-log.md): Stripe payment-method events never call `arraysubs_reset_payment_retry_attempts()`; events for the subscription's own card only refresh brand/last4/expiry/wallet when they changed (compare-and-swap). Files: core `StripeWebhookEventsTrait.php`, `StripePaymentMethodsTrait.php`.
Verified live 2026-10-19: T3 (real `payment_method.updated` on 69202's bound card: every failure and retry key identical, derived next retry unchanged, retry 101503 still pending).
Review round 2: card-detail refreshes no longer stamp `_payment_method_updated_at` (that stamp now means "card changed" only).
