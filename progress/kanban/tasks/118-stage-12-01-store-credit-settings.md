---
id: 118
title: 'stage-12: 01 Store Credit Settings'
status: closed
priority: medium
created: 2026-05-19T22:56:15.424011034+02:00
updated: 2026-05-24T09:27:58.576284145+02:00
started: 2026-05-23T08:06:53.428767188+02:00
completed: 2026-05-23T09:14:16.72722723+02:00
tags:
    - qa
    - stage-12
class: standard
---

Source: stages/12-store-credit/01-store-credit-settings.md

[[2026-05-23]] Sat 09:14
QA done. Currency symbol observed via WC: $ (wp returned HTML entity &#36;). 1.1 PASS settings page loads with Store Credit Settings, Credit Application, Credit Expiration, Credit Purchase. 1.2/1.4 partial FAIL logged #71: numeric fields persist but Allow at Checkout and Enable Credit Purchases did not persist from browser switch clicks; set via WP-CLI to continue. 1.3/1.4 guidance FAIL logged #72: missing 7-day/3:00 AM expiration text and missing Store Credit product creation hint. 1.5 PASS Store Credit product type appears when purchase enabled. 1.6 FAIL logged #73: master disabled still shows Manage Credits/Credit History/Settings and Store Credit product type. Final settings restored enabled=true, auto_apply=true, apply_at_checkout=true, min_order=5, expiration=365, enable_purchase=true, min/max/default=10/500/50. 1.7 PASS all four Store Credit emails listed enabled; Manage editors open for Added/Used/Expiring/Expired. Debug log: no fresh related errors; only old 2026-05-22 Action Scheduler WP-CLI fatal lines.

[[2026-05-24]] Sun 09:14
Issue #71 fix verified 2026-05-24 by shell-quartz. Store Credit secondary switches now toggle and persist from browser clicks; final settings restored to Stage 12 fixture values.

[[2026-05-24]] Sun 09:18
Issue #72 fix verified: Store Credit Settings guidance now includes the 7-day expiring-soon warning, daily 3:00 AM expiration job timing, and Store Credit product creation requirement. Browser verified on /wp-admin/admin.php?page=arraysubs-mainadmin#/store-credit/settings.

[[2026-05-24]] Sun 09:27
Issue #73 fix verified: master Store Credit switch now hides Manage Credits/Credit History tabs and hides Store Credit product type when disabled; re-enabling restores tabs and product type. Final Stage 12 fixture restored with store_credit.enabled=true and enable_purchase=true.
