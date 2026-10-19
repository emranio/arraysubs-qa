---
id: 55
title: Block checkout once produced two concurrent orders and charges
status: open
priority: high
created: 2026-10-19T09:33:43.389063737+02:00
updated: 2026-10-19T09:33:43.389063737+02:00
tags:
    - stripe
    - checkout
    - needs-repro
    - staging-qa
class: standard
---

Active QA task ID and scheduled day: S01-A (board task #6 in qa/stripe-dev-support-2026-10-18/kanban), phase 1, 2026-10-19 (restart r2).
Plan markdown path: qa/stripe-dev-support-2026-10-18/plan.md.
Affected subscription IDs and order IDs: subscriptions 68293 and 68304; orders 68292 and 68302 (both completed, $7.31 each). Repro fixtures without duplication: 68314/68315 (saved card) and 68329/68330 (new card).
Affected WordPress user/customer: ID 577, sqa-p1-block / sqa-p1-block@example.com, role customer.
Stripe references: pm_1USAscJG5OzSNVs2LsLR34GP charged twice; pi_3USAshJG5OzSNVs213imfFCf (order 68292, created 07:21:15Z) and pi_3USAsqJG5OzSNVs2027MoJGU (order 68302, created 07:21:24Z); customer cus_VT76vZzzT3jCHy.
Exact route: https://mirror-help.arrayhash.com/checkout/ (WooCommerce Block checkout, page 8), agent-browser session sqa-block, desktop Chromium, logged in as the customer; official WooCommerce Stripe Gateway 10.9.0 in test mode; core 4791832 + Pro edaa401 active.
Reproduction (observed once, not reproduced): add SQA Daily Stripe Plan (product 68271) to cart, open Block checkout, enter new card 4242 4242 4242 4242 in the Stripe Payment Element, click Place Order once.
Expected: one Store API checkout, one order, one subscription, one charge.
Actual: two Store API checkout requests were processed about 8 s apart (lifecycle capture shows PHP-FPM pid 100720 from 07:21:13Z and pid 104045 from 07:21:22Z, both path /wp-json/wc/store/v1/checkout). The second started while the first was still finishing (first activated its subscription at 07:21:21Z), so the same cart produced a second order/subscription and the same payment method was charged twice. The browser network list afterwards showed only one non-calc_totals checkout POST, so the origin of the second request is unproven (client re-submit, edge, or agent tooling).
Proof: qa/stripe-dev-support-2026-10-18/capture/lifecycle-events.jsonl lines for subscriptions 68293/68304; order notes on 68292/68302; Stripe PIs listed above; evidence in that folder's fixtures.json.
Scope notes / counterexamples: two immediate repros on the same customer (one saved-card, one new-card, recorded with a HAR) each produced exactly one checkout POST, one order and one charge. Classic checkout (order 68280) did not duplicate. ArraySubs' only Block-checkout script (storeApiCartSubscription.js) registers display filters only and cannot submit checkout. Severity is high if it is product-side because it double-charges; it may also be a WooCommerce Store API concurrency behavior (no idempotency on concurrent checkout of one cart) rather than ArraySubs. Recorded for the lead to decide; no code change made.
Repro video: N/A.
