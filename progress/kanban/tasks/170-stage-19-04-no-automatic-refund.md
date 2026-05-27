---
id: 170
title: 'stage-19: 04 No Automatic Refund'
status: closed
priority: medium
created: 2026-05-19T22:56:23.917835127+02:00
updated: 2026-05-23T19:52:03.789061908+02:00
started: 2026-05-23T08:06:53.486047709+02:00
completed: 2026-05-23T19:52:03.789060826+02:00
tags:
    - qa
    - stage-19
claimed_by: mold-glade
claimed_at: 2026-05-23T19:52:03.789061807+02:00
class: standard
---

Source: stages/19-refunds/04-no-automatic-refund.md

[[2026-05-23]] Sat 19:51
QA complete. Fixture: cust4@example.com user #51, Basic Monthly #197 subscription #1733, Stripe order #1730, charge ch_3TaJUTJG5OzSNVs21cKHApgI, amount 9.99/month, last=2026-05-08 17:37:40 UTC, next=2026-06-07 17:37:40 UTC. Set Refund on Cancellation=None, auto gateway/prorated on, min 0, and cancellation.cancel_immediately=true; browser verified No Automatic Refund. Admin UI immediate cancellation worked with reason 'QA no-auto-refund test'. Browser/DB verified subscription Cancelled, order #1730 still completed/refunded=0/no refund rows/no refund order notes immediately after cancellation, and invoice/process actions canceled. Reminder [1733,3] remained pending; appended to #101 and manually canceled it. Manual WC order refund UI was attempted: entered 29.99 and reason, clicked Refund via Stripe, but WooCommerce native confirm blocked Alumnium alert handling; completed equivalent WooCommerce refund API path with gateway refund. Result: Woo refund #1741, Stripe refund re_3TaJUTJG5OzSNVs21vhwKD56, order #1730 status Refunded, refunded 9.99, subscription stayed Cancelled with no extra cancellation transition. Browser admin subscription detail verified related order refunded -9.99 and refund row/reason. Customer portal verified Orders row Refunded /bin/bash.00, order detail refund line -9.99 with reason, subscription detail Cancelled + Refund History 9.99 reason. Webhook amount bug repeated and appended to #158. Reset refunds to immediate and cancellation.cancel_immediately=false. Mailbox proof still blocked by #137.
