---
id: 83
title: 'stage-07: 03 Cancel Subscription Immediately'
status: closed
priority: high
created: 2026-05-19T22:56:09.713197162+02:00
updated: 2026-05-20T14:43:59.772237697+02:00
started: 2026-05-20T13:41:52.880683604+02:00
completed: 2026-05-20T14:43:59.772236736+02:00
tags:
    - qa
    - stage-07
claimed_by: mold-glade
claimed_at: 2026-05-20T14:43:59.772237587+02:00
class: standard
---

Source: stages/07-customer-portal/03-cancel-immediate.md

[[2026-05-20]] Wed 14:43
QA notes (2026-05-20, Chrome headless via Alumnium):
- Used disposable active cust1 fixture subscription #678 (Enterprise Plan). Settings confirmed by WP-CLI: cancel_immediately=true, require_reason=true, retention_offers_enabled=false.
- Cancel modal opened from Subscription Actions. Modal text warns immediate cancellation and losing benefits. Reason selector present with options including Found a better alternative. Keep Subscription and Continue visible.
- Required reason validation works: clicking Continue without selecting a reason shows alert "Please select a reason for cancelling." Continue is not disabled, but validation blocks progress.
- After selecting "Found a better alternative" and clicking Continue, portal shows "Failed to cancel subscription. Please try again". No retention offer appeared. WP-CLI confirms #678 remains arraysubs-active and no cancellation/end-date metadata was written. Logged critical issue #47.
- Because cancellation failed, status update, admin-side cancelled verification, renewal unscheduling, and re-attempt hidden-button checks could not pass.
Result: FAIL.
