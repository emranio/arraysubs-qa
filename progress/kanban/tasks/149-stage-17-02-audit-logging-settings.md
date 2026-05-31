---
id: 149
title: 'stage-17: 02 Audit Logging Settings'
status: closed
priority: medium
created: 2026-05-19T22:56:20.655109042+02:00
updated: 2026-05-24T19:51:58.871745792+02:00
started: 2026-05-23T08:06:53.462202715+02:00
completed: 2026-05-23T15:23:19.306678935+02:00
tags:
    - qa
    - stage-17
class: standard
---

Source: stages/17-audits-and-logs/02-audit-logging-settings.md

[[2026-05-23]] Sat 15:23
QA complete. Product/Coupon/Email/Settings logging OFF/ON behavior verified. Product #197 price restored to 29.99. Coupon QA-AUDIT-COUPON checkout created order #1350 and subscription #1367; Coupon row visible after re-enable. Email OFF suppressed On Hold email audit; Email ON restored Subscription Reactivated rows. Settings OFF suppressed renewals.grace_days_before_on_hold=4; Settings ON logged restore 4 -> 3 with changes modal. Cross-check: Order #1350 and Subscription #1367 rows visible. Issues filed: #124 missing always-on note, #125 duplicate reactivation email audit rows. Final toggles all ON.

[[2026-05-24]] Sun 19:48
Issue #124 fixed and closed. Logging Settings popover now includes the expected always-on note for Subscription, Member, Order, and System-level entries. Build passed and browser screenshot captured.

[[2026-05-24]] Sun 19:51
Issue #125 fixed and closed. Reactivating #697 now creates one new Email audit row instead of duplicate Subscription Reactivated email rows. Browser Activity Audits verification captured.
