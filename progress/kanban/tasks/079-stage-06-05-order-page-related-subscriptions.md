---
id: 79
title: 'stage-06: 05 Order Page Related Subscriptions'
status: closed
priority: high
created: 2026-05-19T22:56:09.198741217+02:00
updated: 2026-05-22T05:05:47.46123545+02:00
started: 2026-05-20T13:41:52.83958807+02:00
completed: 2026-05-20T14:19:20.471875386+02:00
tags:
    - qa
    - stage-06
claimed_by: mold-glade
claimed_at: 2026-05-22T05:05:47.461235139+02:00
class: standard
---

Source: stages/06-initial-lifecycle/05-order-page-related-subscriptions.md

[[2026-05-20]] Wed 14:19
QA notes (2026-05-20, Chrome headless via agent-browser):
- Original Stage 05 classic artifact was unavailable; used active control order #610 / subscription #618, with missing-classic artifact tracked in issue #35.
- My Account order #610 shows order summary, billing address, and Related Subscriptions table. Row: #618 linked to view-subscription=618, status Active, next payment 20 June 2026 (UTC+6), total $29.99 / Every month. Link opens portal detail with same subscription data.
- Thank-you/order-received URL for #610 also shows Related Subscriptions with #618, Active, 20 June 2026 (UTC+6), $29.99 / Every month.
- Trial order #368 as customer-trial shows Related Subscriptions row #384, status Trial, next payment 27 May 2026 (UTC+6), total $19.99 / Every week. Link opens view-subscription=384. Expected task text says Trial 14-Day / 14 days, but real catalog artifact is Trial Weekly / 7 days; tracked in issue #37.
- Non-owner cross-check: customer-trial opening customer-pending order #610 returned Invalid order; no subscription table or IDs exposed.
- Mixed-cart order #582 as customer-mixed shows Basic Monthly + Standard Tee in order details, but Related Subscriptions has only one row: #590 Active, 20 June 2026 (UTC+6), $29.99 / Every month.
Result: PASS with existing documented artifact mismatches.

[[2026-05-22]] Fri 05:05
Issue #37 related doc cleanup: Trial references now use Trial Weekly / 7-day trial instead of old Trial 14-Day / 14-day data.
