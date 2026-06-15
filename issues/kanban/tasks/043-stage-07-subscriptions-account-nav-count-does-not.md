---
id: 43
title: 'stage-07: Subscriptions account-nav count does not match list total'
status: closed
priority: medium
created: 2026-05-20T14:35:04.065369629+02:00
updated: 2026-05-22T05:29:29.638321942+02:00
started: 2026-05-22T05:27:41.907172213+02:00
completed: 2026-05-22T05:29:29.638320639+02:00
tags:
    - qa
    - stage-07
    - customer-portal
claimed_by: mold-glade
claimed_at: 2026-05-22T05:29:29.638321812+02:00
class: standard
---

Stage 07 Task 01. As cust1, account navigation label shows "Subscriptions 7" while the My Subscriptions table pagination summary shows "Showing 1-10 of 12 subscriptions" and WP data has 12 subscriptions for the customer. Expected: sidebar count should match the actual subscription count or use a clearly documented filtered count.

[[2026-05-22]] Fri 05:28
Plan: verify Stage 07 Task 01 and current cust1 counts; centralize the customer portal status set in MyAccountHooks; use it for both the Subscriptions list query and localized subscriptionCount badge so nav count equals pagination total; syntax-check PHP; browser verify badge count matches table summary.

[[2026-05-22]] Fri 05:29
Fixed: MyAccountHooks now reuses one customer-portal status set for both the list query and localized subscriptionCount. Syntax check passed. agent-browser cust1 check: nav label 'Subscriptions 13' matches summary 'Showing 1-10 of 13 subscriptions'.
