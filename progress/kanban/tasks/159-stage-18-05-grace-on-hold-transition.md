---
id: 159
title: 'stage-18: 05 Grace → On-Hold Transition'
status: closed
priority: high
created: 2026-05-19T22:56:22.205614703+02:00
updated: 2026-05-24T21:32:40.326445949+02:00
started: 2026-05-23T08:06:53.472428907+02:00
completed: 2026-05-23T17:12:06.207724809+02:00
tags:
    - qa
    - stage-18
class: standard
---

Source: stages/18-renewal-followup/05-grace-to-on-hold-transition.md

[[2026-05-23]] Sat 17:12
QA complete with failures. Continued with member-decline fixture #1467 from Task 18.04. Pre-test: Active, Standard Weekly, Stripe decline PM/display 0341, next 2026-05-30 13:48:22 UTC after prior cleanup, completed_payments 2. Time-traveled _next_payment_date to 2026-05-19 15:06:39 UTC (4 days overdue), ran generate action #1286 -> renewal order #1495 pending, ran process action #1300 -> Stripe card_declined and order #1495 failed. Expected immediate post-process status Active failed again: #1467 became On Hold before overdue checker; covered by #139. Ran overdue checker #1290; #1467 remained On Hold with pending renewal #1495, but _on_hold_date stayed empty; logged #142. On-Hold email audit had already fired in the immediate transition path. Access-restriction subcheck could not prove On-Hold behavior for Standard Weekly because current member-access rules only target Pro Plan #233 / premium-content; logged #143. Browser/CLI recovery used plugin customer retry with working Stripe PM because Alumnium cannot type into Stripe iframe; order #1495 moved processing, #1467 restored Active, pending cleared, completed_payments 3, next 2026-05-26 15:06:39 UTC, browser timeline shows Renewal Payment Successful and reactivation. Failure reason/category still remained until cleanup; covered by #140. Cleanup for Task 18.06: reset #1467 Active, decline PM/display 0341, cleared failure/retry/on_hold meta; next 2026-05-26 15:06:39 UTC, completed_payments 3.

[[2026-05-24]] Sun 21:24
Issue #142 follow-up verified: overdue checker now sets _on_hold_date when #1467 moved active -> on-hold for renewal order #3029. DB proof: _on_hold_date=2026-05-24 19:07:33 UTC. UI proof: qa/artifacts/issue-142/subscription-1467-on-hold-date-set.png.

[[2026-05-24]] Sun 21:32
Issue #143 follow-up fixed: Standard Weekly access fixture exists at /standard-weekly-content/ with URL rule s18_url_standard_weekly_content requiring active product #200. Verified active member-stripe/#1436 can view content, on-hold member-decline/#1467 is restricted. Screenshots: qa/artifacts/issue-143/active-standard-weekly-content-visible.png and qa/artifacts/issue-143/on-hold-standard-weekly-restricted.png.
