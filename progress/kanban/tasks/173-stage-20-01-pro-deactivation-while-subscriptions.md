---
id: 173
title: 'stage-20: 01 Pro Deactivation While Subscriptions Are Running'
status: closed
priority: high
created: 2026-05-19T22:56:24.373426708+02:00
updated: 2026-05-24T23:37:09.805605022+02:00
started: 2026-05-23T08:06:53.488892605+02:00
completed: 2026-05-23T20:58:24.420620352+02:00
tags:
    - qa
    - stage-20
class: standard
---

Source: stages/20-edge-and-regression/01-pro-deactivation-while-running.md

[[2026-05-23]] Sat 20:46
Stage 20 Task 01 baseline captured. Seeded missing prereqs: cust1@example.com #54 active Stripe-like Pro Plan sub #1773, cust2@example.com #49 active feature sub #1778, cust3@example.com #50 store credit 5. Pro active. MenuConfig pro items visible: Manage Members, Store Credit, Checkout Builder, Skip & Pause, Feature Manager, Audits/Gateway/Scheduled-Job Logs. Customer #1773 portal before deactivation: Change Plan, Cancel, Skip, Pause, Update payment method, Auto-renew On, Store Credit and My Features menu items. Action counts before: pending 78, complete 278, canceled 4.

[[2026-05-23]] Sat 20:58
Result: PASS with defect #163. Deactivated ArraySubsPro via Plugins page, success notice shown; dashboard, Woo orders, ArraySubs subscriptions loaded with no fatal. Pro settings persisted in arraysubs_settings while Pro inactive. MenuConfig after Pro-off hid Manage Members, Store Credit, Checkout Builder, Feature Manager, Gateway Logs/Scheduled-Job Logs; free Subscriptions/Settings/Retention/Member Access remained. Cust1 portal after Pro-off hid Store Credit/My Features and auto-renew/card/update controls; direct /my-account/store-credit/ fell back to blog page. Free manual renewal path worked: forced #1773 due, generated pending renewal order #1784, customer paid via Check in browser, admin completed payment; #1773 advanced next date to 2026-06-23 17:50:22 and completed_payments=2. Reactivated Pro via Plugins page; Pro menu items returned, cust1 auto-renew/card/actions returned, cust3 Store Credit browser showed 5.00, cust2 feature entitlements still present. Debug log had no new 18:40+ PHP errors. Defect: #163 because arraysubs_cleanup_webhook_events failed with no callback while Pro inactive. Email body proof still blocked by #137.

[[2026-05-24]] Sun 23:37
Follow-up issue #163 fixed Pro-owned gateway scheduled jobs during Pro deactivation. Deactivation now unschedules cleanup/reconcile gateway maintenance hooks and deletes failed orphan rows for those hooks. Verified with real deactivate/reactivate: failed cleanup action #1039 removed, pending #1957 removed while Pro inactive, Pro active again with single pending cleanup #2033 and no failed cleanup rows. Screenshot: qa/artifacts/issue-163/action-scheduler-failed-cleanup-filter-empty.png.
