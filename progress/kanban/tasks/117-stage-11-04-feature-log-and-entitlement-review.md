---
id: 117
title: 'stage-11: 04 Feature Log and Entitlement Review'
status: closed
priority: medium
created: 2026-05-19T22:56:15.248852279+02:00
updated: 2026-05-24T09:08:23.790326062+02:00
started: 2026-05-23T08:06:53.426602776+02:00
completed: 2026-05-23T09:04:16.973113228+02:00
tags:
    - qa
    - stage-11
class: standard
---

Source: stages/11-feature-manager/04-feature-log-and-entitlement-review.md

[[2026-05-23]] Sat 09:04
QA done. 4.1 FAIL logged as qa/issues #70: subscription detail #271 has no Feature Log / View Features entry point. Manage Members profile #5 exposes Show Features link and opens #/subscriptions/feature-log?user_id=5. Admin per-sub view PASS: cust1/cust1@test.local, Enterprise #678 + Pro #271 tables, Pro Seats 3 / 5. Combined admin view PASS: Seats 20, Storage 110, API Calls Unlimited, Priority Support Yes, Plan Tier Enterprise, Custom Domain No. Restored per_subscription. Shop Manager PASS: feature log visible, no permission error. Customer direct admin URL PASS blocked with 'Sorry, you are not allowed to access this page.' Tampered customer URL user_id=6 still shows only cust1 entitlements. Debug log has no fresh related errors; only old 2026-05-22 Action Scheduler WP-CLI fatal lines.

[[2026-05-24]] Sun 09:08
Issue #70 fix verified 2026-05-24 by shell-quartz. Subscription detail #271 now exposes Feature Log entry point and opens cust1 user_id=5 feature log with Enterprise Plan and Pro Plan tables.
