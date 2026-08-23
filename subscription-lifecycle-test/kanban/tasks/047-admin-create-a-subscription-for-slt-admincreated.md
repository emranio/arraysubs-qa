---
id: 47
title: Admin-create a day/1 subscription and prove natural invoice scheduling
status: todo
priority: critical
created: 2026-08-22T19:10:00+02:00
updated: 2026-08-22T19:10:00+02:00
tags:
    - cycle-2
    - granular
    - admin
    - renewal
    - day-03
due: "2026-08-26"
estimate: 1h30m
depends_on:
    - 5
    - 10
    - 11
    - 12
class: standard
---

> **SLT-ADM-05** · group `admin` · starts **D03** (2026-08-26), with a natural D04 follow-up

## Objective
Create a day/1 subscription in wp-admin with no checkout, activate it through the supported UI, and prove the saved interval drives the reminder/invoice/renewal schedule and natural unpaid-invoice lifecycle. This is an internal engine control, not a third automatic-gateway test.

## Scope
- Gateway: none/manual invoice control
- Checkout: none
- Account: `slt2-admincreated`
- Plugins: free/core

## Preconditions
- Foundation, account and `SLT2 Daily Core` product cards passed. Resolve current IDs from the registry.
- Run outside any shared-settings bracket. Record a fresh Mailpit baseline and exact subscription/action counts.

## Steps
1. In `admin-SLT-ADM-05`, open the real ArraySubs create form and capture every available field before entry.
2. Select `slt2-admincreated` and `SLT2 Daily Core`; set quantity 1, recurring amount $10.00, interval 1, period day, no trial/signup fee/length override, and the exact customer invoice address/email.
3. Create once. Record numeric alias `SUB_A`, require one count increment, and capture the success toast plus exact initial post status, billing meta, dates, payment method and scheduler rows.
4. If created pending, change it to Active through the supported status modal. Re-snapshot after the UI change and reconcile customer/admin activation emails.
5. Require `_billing_period=day`, `_billing_interval=1`, current start date and a next-payment date one day after activation according to site timezone. Query exact reminder, invoice and process-renewal actions by args `[SUB_A]` and record fresh IDs/timestamps/groups.
6. Verify each applicable action is scheduled from the live day/1 next-payment date: reminder only when its configured lead is in the future, invoice at the defined lead, and renewal at the due time plus deterministic spread. A month/other-period cadence is a failure.
7. Publish action gates and `gate−5m` baselines. Do not run or force Action Scheduler.
8. After the exact natural invoice/renewal gates on D04, reopen the admin session. Resolve the resulting renewal invoice/order by exact subscription and scheduled cycle; require the correct $10.00 amount, pending/failed/manual-payment state appropriate to no saved automatic gateway, bidirectional linkage and no gateway charge.
9. Reconcile the full Mailpit delta: activation pair once, renewal-invoice/customer payment link as configured, no automatic payment-received mail, and no duplicate signup/renewal mail.
10. Verify admin detail, customer portal, notes, dates and action history match meta/order evidence. Append all IDs/gates/messages to the registry, close exact sessions, review, and mark done only after the natural D04 path passes.

## Expected results
1. One admin-created subscription exists with the selected user/product/quantity/amount and day/1 billing metadata.
2. Activation produces the documented status and email pair and arms scheduler rows from the day/1 cadence.
3. Natural scheduling creates one correctly linked $10.00 renewal invoice/order at the live gate, without an automatic gateway charge.
4. No month-cadence substitution, missing action, duplicate order/mail or unrelated mutation occurs.

## Evidence / issue contract
- Capture form/toast/detail/queue/order/portal screenshots, exact post/meta/HPOS/action rows and Mailpit IDs under the fresh evidence root.
- Any mismatch creates/updates the mandatory `qa/issues/` kanban card with task/stage/plan, subscription/order/action/product/user IDs, exact routes/sessions/gates, reproduction, expected/actual and concrete proof; the task remains blocked until fixed and rerun.

## Isolation / teardown
- Keep `SUB_A` registered for admin status, capability and invoice audits. D11/D13 own cancellation/deletion.

### Fresh-cycle validation contract

- No prior admin-created schedule or issue is assumed.
- Stripe/Paddle remain the only automatic gateways; this is explicitly a gatewayless engine control.
- Browser work uses `agent-browser`; WP-CLI includes `--allow-root`; both QA boards are updated.
