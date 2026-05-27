---
id: 174
title: 'stage-20: 02 Core Plugin Deactivation'
status: closed
priority: high
created: 2026-05-19T22:56:24.499635384+02:00
updated: 2026-05-23T21:07:12.240976013+02:00
started: 2026-05-23T08:06:53.489703112+02:00
completed: 2026-05-23T21:07:12.240974671+02:00
tags:
    - qa
    - stage-20
claimed_by: mold-glade
claimed_at: 2026-05-23T21:07:12.240975923+02:00
class: standard
---

Source: stages/20-edge-and-regression/02-core-plugin-deactivation.md

[[2026-05-23]] Sat 20:59
Baseline: plugins active. 48 subscriptions: active 26, cancelled 10, expired 2, on-hold 3, pending 3, trial 4. Key fixture #1773 active next=2026-06-23 17:50:22 completed=2. Settings baseline: refunds immediate/auto/prorated/min 0; renewal grace 3/7, invoice lead 6h; auto-renew toggle true; store credit enabled; feature manager enabled. cust3 credit 5. Action counts before core deactivate: pending 78, complete 281, canceled 1, failed 1 (pre-existing #1039 from issue #163).

[[2026-05-23]] Sat 21:07
Result: PASS. Deactivated ArraySubs core via Plugins page. Browser success: ArraySubs row changed to Activate; ArraySubsPro stayed active but showed non-fatal dependency warning that ArraySubs was missing/unsupported. Direct admin URLs /admin.php?page=arraysubs-mainadmin#/subscriptions and /admin.php?page=arraysubs-settings returned clean WP permission error, no half-broken app. Customer cust1 portal while core inactive: /my-account/subscriptions/, /my-account/view-subscription/1773/, /my-account/store-credit/ fell back to Blog/Hello world style clean page; standard My Account dashboard still worked. Reactivated ArraySubs via Plugins page; both plugins active afterward. Data intact: 48 subscriptions same counts active 26/cancelled 10/expired 2/on-hold 3/pending 3/trial 4; #1773 active next=2026-06-23 17:50:22 completed=2; key settings matched baseline; cust3 credit still 5; cust2 feature group still present. Customer subscriptions restored: #1773 Pro Plan Active, next 23 June 2026, 9.99/month. Admin subscriptions page loaded 48 rows, not setup wizard. Action counts unchanged from baseline including pre-existing failed #1039/#163.
