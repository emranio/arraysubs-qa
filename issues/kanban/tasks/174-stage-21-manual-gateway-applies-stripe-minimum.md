---
id: 174
title: 'stage-21: Manual gateway applies Stripe minimum bump'
status: closed
priority: high
created: 2026-07-08T00:48:57.166581503+02:00
updated: 2026-07-08T08:01:59.851064712+02:00
started: 2026-07-08T07:51:20.499018169+02:00
completed: 2026-07-08T08:01:59.851063921+02:00
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

Affected subscription/order IDs: N/A - manual gateway control was stopped at checkout before order placement.
Affected WordPress user/customer IDs: N/A - fresh guest checkout session qa-customer-stripe-tiny; no order/customer was created.

Exact test URL/admin route: customer checkout https://mirror-help.arrayhash.com/checkout/?add-to-cart=8816. Browser context: agent-browser session qa-customer-stripe-tiny after product #8816 FRS Tiny 0.60 was configured as a monthly flexible-sync subscription at $0.60 with segment-2 boundaries 5/20.

Reproduction steps:
1. Confirm site date D=8, L=31 and raw prorated amount for FRS Tiny 0.60 is round(0.60 * 23 / 31, 2) = $0.45.
2. Open checkout with Stripe selected; observe gateway minimum bump to $0.50.
3. Switch the payment method to Direct bank transfer in the same checkout.

Expected result: With a manual gateway selected, the raw gateway-unbumped prorated first charge should be shown: $0.45.

Actual result: Direct bank transfer still showed the Stripe-minimum-bumped amount: previous price $0.60, discounted price $0.50, Today's charge $0.50 prorated until the synced renewal date, total $0.50.

Concrete proof: agent-browser checkout summary extraction and screenshot qa/artifacts/stage-21-task-193-tiny-gateway-switch.png show BACS selected while the sidebar remains $0.50 instead of raw $0.45.

Known scope notes/counterexamples: The Stripe bump itself is expected for this cart because full price $0.60 is above the Stripe minimum and the raw prorate is below $0.50. The defect is that the same bump leaks to Direct bank transfer, which should be gateway-aware and show the raw prorated value.

[[2026-07-08]] Fix applied (two parts, both in arraysubs):
1. arraysubs/src/functions/renewal-sync-helpers.php — arraysubs_get_current_renewal_sync_gateway_context() now falls back to the first available gateway (what the checkout UI preselects) when no payment method is chosen yet, instead of returning an empty gateway context.
2. arraysubs/src/Features/SubscriptionCheckout/Services/Hooks.php — new recalculateTotalsForChosenGateway() on woocommerce_store_api_checkout_update_draft: when Block Checkout PATCHes a payment-method change, cart totals were calculated before the session update, so the PATCH response carried the previous gateway's pricing (the leak: Stripe bump persisting on BACS). Totals are now recalculated after the session update.
Verified live in browser (agent-browser session qa-fix175) on FRS Tiny 0.60 (#8816): Stripe selected -> Today's charge $0.50 / Total $0.50; switch to Direct bank transfer -> Today's charge $0.45 / Total $0.45 (raw prorate, no Stripe bump); switch back to Stripe -> $0.50 again. Store API check: fresh cart 50 (Stripe default), PATCH bacs response 45, PATCH stripe response 50 — no lag. Screenshot: qa/artifacts/fix-174-bacs-raw-045.png.
