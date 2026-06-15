---
id: 170
title: 'stage-05: Renewal Sync checkout total remains full price'
status: closed
priority: critical
created: 2026-06-03T12:50:06.555976032+02:00
updated: 2026-06-03T13:03:14.249232959+02:00
started: 2026-06-03T13:02:54.522160387+02:00
completed: 2026-06-03T13:03:14.249232248+02:00
tags:
    - qa
    - stage-05
    - renewal-sync
    - checkout
    - bug
class: standard
---

QA progress task: #182 stage-05: Renewal Sync checkout
QA plan: qa/stages/05-checkout/15-renewal-sync-checkout.md
Affected subscription ID(s) and order ID(s): N/A — blocked before order placement
Affected WordPress user/customer: ID 317, login sync-prorate, email sync-prorate@example.test, role customer
Exact test URL/admin route: https://mirror-help.arrayhash.com/checkout/ in fresh customer browser session after adding product #197 Basic Monthly
Browser/user context: agent-browser headless Chrome, customer sync-prorate
Reproduction steps: Enable Renewal Sync with First Charge Prorate; log in as sync-prorate; add Basic Monthly product #197; open checkout; select Direct bank transfer.
Expected result: Actual checkout line/order total equals prorated first charge $26.99 and summary shows first full renewal July 1, 2026.
Actual result: Subscription summary says Today's charge: $26.99 prorated until the synced renewal date, but the line item and checkout total remain $29.99.
Concrete proof: agent-browser extraction after BACS selection returned [$29.99, $29.99, Renewals: $29.99 Every month Today's charge: $26.99 prorated until the synced renewal date ... Total price for 1 Basic Monthly item: $29.99, 1 July, 2026 (UTC+6) ($29.99)].
Known scope notes/counterexamples: Gateway support and settings persistence passed; failure appears in actual WooCommerce checkout/cart totals for synced-prorate item before order creation.
