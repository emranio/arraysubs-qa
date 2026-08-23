---
id: 34
title: Trial started, trial-ending reminder at 3 days, and paid trial conversion
status: todo
priority: critical
created: 2026-08-22T19:10:00+02:00
updated: 2026-08-22T19:10:00+02:00
tags:
    - cycle-2
    - granular
    - email
    - trial
    - stripe
    - day-02
due: "2026-08-25"
estimate: 1h15m
depends_on:
    - 10
    - 11
    - 12
    - 31
    - 38
class: standard
---

> **SLT-EML-09** · group `emails` · starts **D02** (2026-08-25), with registered D03/D06 follow-ups

## Objective
Use the single Stripe-backed `SLT2 Trial Four Day` checkout to prove the complete trial lifecycle: $0 signup with payment method collection, one trial-started email, one configured trial-ending reminder three days before the live trial end, one paid conversion, correct conversion email path, exact scheduling and no duplicates.

## Scope
- Gateway: Stripe test
- Checkout: observation rider for task 31
- Account: `slt2-trial`
- Plugins: both

## Preconditions
- Task 31 has created exactly one numeric `S_TR` from the task-38 product and published its order, user, payment method, trial-end timestamp, actions and Mailpit baseline.
- Re-query all relationships and timestamps. If the source fixture is missing or duplicated, create/update the mandatory `qa/issues/` card and move this task to blocked; do not buy a substitute or mark it done.

## Steps
1. Resolve and validate numeric `S_TR`, its exact $0 parent order, owner/product, Stripe method, `arraysubs-trial` status, `_trial_end_date`, `_next_payment_date` and completed-payment count.
2. Reconcile the checkout-time Mailpit delta. Require exactly one trial-started customer email naming the subscription/product and no active/new-subscription, renewal, failure or conversion email at signup.
3. Query all exact reminder/invoice/charge/trial-conversion actions for args containing `S_TR`. Derive the configured `trial_end−3d` reminder gate from current settings and the live trial end; publish exact action IDs, timestamps and `gate−5m` baselines to the registry/future-gates file.
4. No earlier than five minutes before the reminder gate, set `TRIAL_ENDING_PRE=$(mailpit-agent latest-id)`. After natural WP Cron runs, require the exact reminder action to complete once and exactly one trial-ending email to the correct customer. Verify subject/body/product/subscription/trial-end date, link and timezone; require no duplicate after a second scheduler sweep.
5. No earlier than five minutes before the conversion charge gate, set `TRIAL_CONVERT_PRE`. Observe the natural charge/conversion path without manually running the hook. Resolve the paid renewal/conversion order by bidirectional relationship and scheduled cycle, never by recency.
6. Require the subscription to become active, completed payments to advance once, next-payment date and both renewal legs to re-arm correctly, and exactly one gateway charge for $12.00.
7. Reconcile the complete post-conversion mail delta. Require the configured trial-converted or payment-received message appropriate to the actual conversion path, plus only the documented order/activation messages. Record exact ordering and reject duplicates.
8. Capture customer/admin subscription views before reminder, after reminder and after conversion; compare dates, status, related order and notes to WP/meta/action/mail evidence.
9. Append all gates, orders, actions and Mailpit IDs to the registry, close exact sessions, independently review the full D2-D6 evidence, and move through review to done only after all three lifecycle phases pass.

## Expected results
1. Signup: one $0 order, one trial subscription, Stripe payment method saved, one trial-started email, no paid-activation email.
2. Reminder: one naturally scheduled action and exactly one trial-ending email at the configured three-day lead, with correct local date and no duplicate.
3. Conversion: one $12.00 paid order/charge, active status, next cycle scheduled, correct conversion/payment email contract and no double activation.
4. Order, subscription, customer, action, gateway and mail identifiers reconcile bidirectionally throughout.

## Evidence / issue contract
- Save all screenshots, action rows, meta dumps, relationship queries and Mailpit messages under the fresh evidence root.
- Missing reminder scheduling/mail, incorrect conversion, duplicate mail/charge or any mismatch creates/updates a mandatory `qa/issues/` kanban card with task/stage/plan, IDs, user context, exact gates/routes/sessions, reproduction, expected/actual and concrete proof; the task stays blocked until fixed and rerun.

## Isolation / teardown
- This task places no extra checkout and mutates no global setting. `S_TR` remains registered for downstream negative/email audits and teardown.

### Fresh-cycle validation contract

- No prior-cycle trial result is evidence; derive every value live.
- Stripe and Paddle are the only automatic gateways in scope; this trial is Stripe primary.
- Browser assertions use `agent-browser`; WP-CLI includes `--allow-root`; both QA boards are updated.
