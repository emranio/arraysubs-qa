---
id: 138
title: 'stage-5: Stripe webhooks add duplicate payment-confirmed subscription notes'
status: todo
priority: high
created: 2026-08-15T09:49:15.836861062+02:00
updated: 2026-08-15T09:55:47.101529294+02:00
tags:
    - stage-5
    - stripe
    - webhook
    - subscription-notes
    - payment-migration
class: standard
---

## QA source
- QA progress task: #133, stage-5: Stripe subscription, regular, and mixed purchases.
- Relevant QA plan: qa/stages/05-checkout/11-stripe-test-card-flow.md.
- Additional mixed-cart plan: qa/stages/05-checkout/14-mixed-cart-and-block-checkout.md.

## Affected records
- Subscription #27296; parent order #27280; customer #463, stripe-mig-sub-20260815-hawktwig@example.test, role customer.
- Subscription #27362; parent order #27346; customer #465, stripe-mig-mixed-20260815-hawktwig@example.test, role customer.
- Subscription #27403; parent order #27387; customer #466, stripe-mig-sca-20260815-hawktwig@example.test, role customer.
- Regular-only order #27315 and customer #464 are a counterexample because no subscription exists and therefore no subscription-note row applies.

## Test URL and context
- Staging checkout: https://mirror-help.arrayhash.com/checkout/
- Confirmation examples: https://mirror-help.arrayhash.com/checkout/order-received/27280/ and /27346/ and /27387/
- Browser sessions: stripe-mig-qa-sub, stripe-mig-qa-mixed, stripe-mig-qa-sca.
- Browser: agent-browser isolated customer sessions.

## Reproduction
1. Purchase a paid subscription with Stripe test card 4242, or complete the 3DS challenge with card ending 3184.
2. Wait for charge.succeeded and payment_intent.succeeded webhooks.
3. Query ArraySubs subscription notes for the newly created subscription.
4. Filter for the text Stripe payment confirmed via official webhook for order.

## Expected
One de-duplicated official-webhook confirmation note per successful initial payment. A separate normalized payment-success timeline note is acceptable if intentionally distinct.

## Actual
Every tested subscription contains two identical official-webhook confirmation notes:
- #27296: note #27300 at 07:23:32 UTC and #27301 at 07:23:33 UTC, both for order #27280.
- #27362: note #27366 and #27367 at 07:37:44 UTC, both for order #27346.
- #27403: note #27409 at 07:44:59 UTC and #27410 at 07:45:00 UTC, both for order #27387.

Each scenario also has one distinct Payment successful timeline note, which is not counted as the duplicate.

## Concrete proof
- Stripe webhook pairs were charge.succeeded plus payment_intent.succeeded: internal rows #1152/#1154, #1183/#1185, and SCA rows #1208/#1210 after #1206 requires_action.
- Browser checkout succeeded in all three cases; orders are processing and subscriptions active.
- Evidence report: qa/artifacts/payment-migration-regression-20260815/stripe/report.md.
- Screenshots: qa/artifacts/payment-migration-regression-20260815/stripe/screenshots/.

## Scope notes
- Reproduced three times with subscription-only, mixed-cart, and 3DS/SCA paths.
- Payment, subscription activation, gateway binding, and renewal scheduling still succeeded; impact is duplicate audit/history noise rather than payment failure.
- Concurrent Paddle traffic was separated by gateway and fixture IDs and is not part of this issue.

[[2026-08-15]] Sat 09:55
Historical scope check (2026-08-15): a read-only grouped query found the same identical-note duplication on many pre-migration Stripe subscriptions, beginning at least with subscription #1436 on 2026-05-31 (2 copies) and #4406 on 2026-06-03 (3 copies), plus numerous June-August renewals. Therefore this is a longstanding product/audit-history defect, not introduced by the ArraySubsPro-to-ArraySubs payment migration. The current three fixtures remain valid reproductions; payment correctness is unaffected.
