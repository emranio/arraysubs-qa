---
id: 82
title: 'stage-07: 02 View Subscription Detail'
status: closed
priority: high
created: 2026-05-19T22:56:09.574783285+02:00
updated: 2026-05-22T05:30:58.916025975+02:00
started: 2026-05-20T13:41:52.877719853+02:00
completed: 2026-05-20T14:41:25.085715921+02:00
tags:
    - qa
    - stage-07
claimed_by: mold-glade
claimed_at: 2026-05-22T05:30:58.916025684+02:00
class: standard
---

Source: stages/07-customer-portal/02-view-subscription-detail.md

[[2026-05-20]] Wed 14:41
QA notes (2026-05-20, Chrome headless via agent-browser):
- Subscription A #633 (retention Basic Monthly): overview rows observed in order Status, Product, Start Date, Next Payment, Recurring Amount, Payment Method. Status Active; Product Basic Monthly; next payment 20 June 2026 6:30 PM (UTC+6); Recurring Amount $23.99 Every month with line "Discounted from $29.99 for the next 1 renewal(s)." Payment method Direct bank transfer with Manage payment methods link. Actions: Change Plan and Cancel Subscription visible; no Reactivate.
- Skip/Pause expected section is missing on #633: no Manage Your Subscription, no Skip Next Renewal, no Pause Subscription; logged issue #46.
- Subscription B #638 (coupon Pro Monthly): Coupon Discount row visible: "50% off (HALFOFF3)" and "2 renewal cycle(s) remaining".
- Subscription D #648 (Lifetime Deal): Next Payment reads exactly "Lifetime Deal — No recurring payment". No End Date row shown.
- Shipping subscription #643: Shipping Address section visible, address shows Cust One / 77 Stage Seven St / Austin, TX, 78701 / US. Text: "Shipping is charged on each renewal order." Update Shipping Address button visible.
- Related Orders on #633/#638/#643 show columns Order, Date, Status, Total, Actions with View links. No refund history present because fixtures have no refunds. Subscription Notes show SYSTEM author and timestamps.
- Cancelled subscription #668 still renders Subscription Actions with Reactivate Subscription, contrary to task expectation that cancelled actions section is hidden; logged issue #45.
Result: PASS for overview/retention/coupon/lifetime/shipping/related-orders/notes; FAIL for missing skip/pause and cancelled action visibility.

[[2026-05-22]] Fri 03:03
Issue #46 fixed: fixture/settings now match Stage 07 Task 02. Enabled pause_subscription.customer_can_pause, cleared prior #633 pause cooldown/meta and skip state, confirmed #633 active. Browser verified #633 detail shows Manage Your Subscription with Skip Next Renewal and Pause Subscription buttons, no active Skipping/Subscription Paused indicator.

[[2026-05-22]] Fri 05:30
Issue #45 rechecked: cancelled #668 now has no Subscription Actions and no Reactivate Subscription button in customer portal; backend reactivation helper returns no. Already fixed by current terminal reactivation gating.
