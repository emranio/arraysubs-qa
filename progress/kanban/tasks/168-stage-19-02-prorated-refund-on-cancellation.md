---
id: 168
title: 'stage-19: 02 Prorated Refund on Cancellation (Standard Weekly)'
status: closed
priority: medium
created: 2026-05-19T22:56:23.647709623+02:00
updated: 2026-05-24T23:01:03.395135199+02:00
started: 2026-05-23T08:06:53.482876283+02:00
completed: 2026-05-23T19:22:15.204732825+02:00
tags:
    - qa
    - stage-19
class: standard
---

Source: stages/19-refunds/02-prorated-refund-on-cancellation.md

[[2026-05-23]] Sat 18:53
Starting Stage 19 Task 02 prorated refund on cancellation. Will avoid PayPal/Paddle; Stripe/manual only if needed. No lint/phpcs.

[[2026-05-23]] Sat 19:22
QA complete with failures logged. Fixture: cust2@example.com user #49, Standard Weekly subscription #1704, Stripe order #1701, charge ch_3TaIxpJG5OzSNVs20Nyau2zl. Half-cycle preview returned 7-day cycle but days_used=3/days_unused=4/refund=11.42 instead of expected 3.5/10.00; logged issue #156. Admin subscription detail lacked prorated refund UI; logged #157, so backend REST processed refund with reason 'QA prorated test'. REST created Woo refund #1707 and Stripe refund re_3TaIxpJG5OzSNVs20vuEzRna for 1.42 and cancelled subscription. Browser verified subscription Cancelled, related order row #1701 with refunded -1.42 and refund sub-row, notes/timeline with refund + cancellation, WC order page refund row/Stripe refund ID, My Account Orders net .57, My Account Subscriptions Cancelled, Analytics Revenue Returns nonzero today (5.00), webhook event charge.refunded evt_3TaIxpJG5OzSNVs20zgWh33U recorded. Webhook/order/sub notes incorrectly reported external refund as /bin/bash.00; logged #158. Future invoice/process actions cleared; renewal reminder stayed pending, appended evidence to existing issue #101, then manually cancelled reminder [1704,3] for site hygiene. Mailbox/body proof remains blocked by #137.

[[2026-05-24]] Sun 22:43
Follow-up issue #156 fixed fractional prorated refund math. Browser-authenticated REST preview with controlled half-cycle data returned 7 cycle days, 3.5 used, 3.5 unused, daily_rate 2.8557, refund_amount 10.00; fixture meta restored afterward.

[[2026-05-24]] Sun 22:55
Follow-up issue #157 fixed admin subscription-detail prorated refund UI. Active refundable subscription #1673 now shows Prorated Refund action; modal preview exposes amount, reason, Cancel after refund, and Process Refund. No refund processed during verification.

[[2026-05-24]] Sun 23:01
Follow-up issue #158 fixed Stripe refund webhook amount extraction for charge refund lists, direct refund objects, nested event objects, and amount_refunded fallback. WP-CLI reflection test verifies major-unit amounts no longer resolve to 0.00 for representative Stripe payloads.
