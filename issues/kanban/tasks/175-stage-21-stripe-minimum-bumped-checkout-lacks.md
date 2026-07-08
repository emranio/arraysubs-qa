---
id: 175
title: 'stage-21: Stripe minimum bumped checkout lacks Payment Element'
status: closed
priority: critical
created: 2026-07-08T00:49:19.639008561+02:00
updated: 2026-07-08T08:01:59.971850139+02:00
started: 2026-07-08T07:51:20.667284613+02:00
completed: 2026-07-08T08:01:59.971849457+02:00
tags:
    - qa
    - stage-21
    - flexible-renewal-sync
    - checkout
    - stripe
class: standard
---

QA progress task: #193, Stage 21, task 06.
QA plan path: qa/stages/21-flexible-renewal-sync/06-stripe-next-cycle-and-minimum-charge.md

Affected subscription/order IDs: N/A - blocked at checkout before payment submission; no order, subscription, PaymentIntent, or charge was created for the tiny Stripe run.
Affected WordPress user/customer IDs: N/A - fresh guest checkout sessions qa-customer-stripe-tiny-pay and qa-customer-stripe-tiny-below-min; no order/customer was created.

Exact test URL/admin route: customer checkout https://mirror-help.arrayhash.com/checkout/?add-to-cart=8816. Browser context: agent-browser fresh guest sessions. Product #8816 FRS Tiny 0.60 was configured as a monthly flexible-sync subscription at $0.60 with segment-2 boundaries 5/20; display-only below-minimum control temporarily set the price to $0.20 and restored it to $0.60.

Reproduction steps:
1. Configure FRS Tiny 0.60 (#8816) with flexible sync enabled, all segments active, boundaries 5/20, monthly price $0.60.
2. Open a fresh checkout session with Stripe selected.
3. Observe the checkout sidebar and Stripe payment area.
4. For the display-level below-minimum control, temporarily set price to $0.20, open checkout with Stripe selected, then restore price to $0.60.

Expected result: For the $0.60 tiny product, Stripe checkout should show Today's charge $0.50 and render the Payment Element so the 4242 card can be submitted; Stripe should accept the bumped charge and create a 50-cent PaymentIntent. For the $0.20 control, checkout should show raw $0.15 and no bump; payment is intentionally not submitted.

Actual result: Fresh $0.60 Stripe checkout showed Today's charge $0.50, but also displayed the notice "Amount must be at least $0.50 USD" and no Stripe Payment Element iframe was present, so the required PaymentIntent acceptance check could not be performed. The $0.20 control displayed raw $0.15 as expected, but it also displayed the same Stripe minimum notice and no iframe.

Concrete proof:
- qa/artifacts/stage-21-task-193-tiny-stripe-fresh-error.png: $0.60 checkout shows $0.50 total with the minimum-amount notice and no usable Stripe iframe.
- agent-browser extraction returned iframe=false, payment=stripe, notice=["Amount must be at least $0.50 USD"], total $0.50.
- qa/artifacts/stage-21-task-193-tiny-below-min-summary.png: $0.20 display control shows raw $0.15 and the same minimum notice; product price was restored to $0.60 afterward.

Known scope notes/counterexamples: Regular Stripe checkouts for $30.00, $22.26, and next-cycle $30.00 succeeded in tasks #192/#193 and saved off-session payment methods. This appears limited to small Stripe minimum-edge carts where the display amount is at or below the minimum boundary.

[[2026-07-08]] Fix applied: same root cause as #174 — on a fresh guest session no gateway was chosen server-side, so the Stripe minimum bump never applied to the cart total ($0.45), while the Blocks UI preselected Stripe; Stripe's deferred-intent Payment Element refused to init below 50c ("Amount must be at least $0.50 USD", no iframe). arraysubs_get_current_renewal_sync_gateway_context() now defaults to the first available gateway (the one the UI preselects), so the fresh-session total is the bumped $0.50 and the Payment Element renders.
Verified live end-to-end (agent-browser session qa-fix175, product #8816 FRS Tiny 0.60): fresh checkout shows Total $0.50, Stripe Payment Element iframes render (card/expiry/CVC fields usable), no minimum-amount notice. Full payment submitted with 4242 card: Stripe elements/sessions initialized with deferred_intent[amount]=50; order #9076 completed, total $0.50, PaymentIntent pi_3TqoMFJG5OzSNVs21RnNpxXA = 50 usd succeeded (confirmed via Stripe API); subscription #9092 active, next payment 2026-07-31 18:00:00 UTC; order item #562 carries _renewal_sync_cycle_start_date (re-confirms #172); debug.log unchanged at 1719 lines (re-confirms #173). Screenshot: qa/artifacts/fix-175-stripe-bumped-050-element.png.
Below-minimum display control ($0.20 -> raw $0.15, no bump) unchanged by design: full price below the gateway minimum is not bumped; Stripe genuinely cannot charge it and payment is not submitted in that scenario.
