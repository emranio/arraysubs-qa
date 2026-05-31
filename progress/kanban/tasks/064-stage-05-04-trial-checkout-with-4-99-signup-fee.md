---
id: 64
title: 'stage-05: 04 Trial Checkout — With $4.99 Signup Fee'
status: closed
priority: high
created: 2026-05-19T22:56:07.038462943+02:00
updated: 2026-05-20T11:58:23.921919558+02:00
started: 2026-05-20T10:28:05.935113419+02:00
completed: 2026-05-20T11:58:23.853895733+02:00
tags:
    - qa
    - stage-05
claimed_by: mold-glade
claimed_at: 2026-05-20T11:58:23.921919247+02:00
class: standard
---

Source: stages/05-checkout/04-trial-checkout-with-signup-fee.md

[[2026-05-20]] Wed 11:58
QA done on active block checkout. Created/reset customer-trial-fee@example.test user #11. Product #206 Signup Fee Weekly had signup fee 4.99 but no trial, so temporarily set trial_length=7 for this task and restored to 0 afterward. Cart showed Signup Fee Weekly, signup fee .99 one-time, trial 7 days free trial, total .99. Checkout summary showed Renewals 9.99 Every week, Signup fee .99 (one-time), Trial 7 days free trial, total .99, and explicit fee line 'Subscription Signup Fee .99'. Next charge date and authorization notice were not visible, consistent with existing summary defects. Initial form fill duplicated values and postcode validation appeared; corrected fields via JS and order placed. Order #390 total .99. Related subscription #406 status Trial, next 27 May 2026 (UTC+6). WP-CLI verified subscription #406 arraysubs-trial, product #206, trial_length 7, signup_fee 4.99, recurring amount 19.99, next_payment_date and trial_end_date 2026-05-27 09:56:21, parent order #390.
