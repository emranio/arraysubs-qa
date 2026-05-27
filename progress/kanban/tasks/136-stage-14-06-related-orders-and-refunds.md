---
id: 136
title: 'stage-14: 06 Related Orders and Refunds'
status: closed
priority: medium
created: 2026-05-19T22:56:18.105711362+02:00
updated: 2026-05-24T15:08:40.895955378+02:00
started: 2026-05-23T08:06:53.448293438+02:00
completed: 2026-05-23T11:52:06.112555887+02:00
tags:
    - qa
    - stage-14
class: standard
---

Source: stages/14-admin-subscriptions/06-related-orders-and-refunds.md

[[2026-05-23]] Sat 11:51
QA notes: No existing refund fixture found, so created real WC refund #1259 for subscription #508 order #511: 0.00, reason QA test refund; seeded renewal flags for #511/#1013 so card can show Initial/Renewal types. Browser #508 Order History: header badge Total Refunded 0.00; headers Order, Date, Status, Total, Refunded, Type, Actions. Rows: #1013 completed 0 Renewal, #511 completed 0 Renewal with Refunded -0.00 and refund sub-row Refund #1259 hBc10.00 reason QA test refund, #505 completed 9.99 Initial. Badge math matches refund sub-row. No in-card Initial/Renewal filter, matching manual. Woo order #511 screen showed Completed, 0.00, -0.00 refund, reason QA test refund, customer-switch@example.test, subscription meta/link data. Woo subscription link points to inaccessible legacy page=arraysubs route; logged #97. Subscription notes include refund processed note. #987 zero-refund regression: Total Refunded badge hidden, no refund sub-rows.

[[2026-05-24]] Sun 15:08
Fix verification 2026-05-24 for issue #97: Woo order #511 ArraySubs Subscription #508 link now points to current SPA route admin.php?page=arraysubs-mainadmin#/subscriptions/detail/508 and loads Subscription #508 without access denied. Alumnium verified href + click; Playwright screenshots: qa/artifacts/issue-97-order-511-subscription-link.png and qa/artifacts/issue-97-subscription-508-loaded.png. Check: php -l Refunds/Services/Hooks.php passed.
