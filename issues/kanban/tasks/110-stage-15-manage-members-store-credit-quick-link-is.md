---
id: 110
title: 'stage-15: Manage Members Store Credit quick link is not scoped'
status: closed
priority: high
created: 2026-05-23T12:36:46.35395099+02:00
updated: 2026-05-24T17:34:27.981595697+02:00
started: 2026-05-24T17:25:35.647798258+02:00
completed: 2026-05-24T17:34:27.981594746+02:00
tags:
    - qa
    - stage-15
    - manage-members
    - store-credit
    - routing
claimed_by: shell-quartz
claimed_at: 2026-05-24T17:34:27.981595597+02:00
class: standard
---

Stage 15 task 03 Customer One: Store Credit stat shows 5.00, but clicking Manage Store Credit quick link navigates to #/store-credit/32 and renders the generic Store Credit Management search page with Find a Customer only. It does not preload Customer One, show the 5.00 balance, or show transaction history/adjustments. QA plan expects the quick link to open the dedicated Store Credit page scoped to this customer.

[[2026-05-24]] Sun 17:34
Fixed Store Credit route scoping: /store-credit/:userId now treats route param as source of truth, clears stale customer data on route changes, reloads the scoped customer every time userId changes, guards stale responses, and Clear resets URL to /store-credit. Verified via Alumnium: Manage Members #32 -> Manage Store Credit opens #/store-credit/32 scoped to Customer One with 30.00 balance and history; #/store-credit/33 switches to Customer 2 with 0.00/no history; #/store-credit/32 switches back to Customer One with history. Playwright screenshot qa/artifacts/issue-110/store-credit-customer-one-scoped.png. npm run build passed.
