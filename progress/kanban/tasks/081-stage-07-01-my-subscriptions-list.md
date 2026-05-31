---
id: 81
title: 'stage-07: 01 My Subscriptions List'
status: closed
priority: high
created: 2026-05-19T22:56:09.493788909+02:00
updated: 2026-05-22T05:29:25.308679752+02:00
started: 2026-05-20T13:41:52.862261696+02:00
completed: 2026-05-20T14:38:57.470583822+02:00
tags:
    - qa
    - stage-07
claimed_by: mold-glade
claimed_at: 2026-05-22T05:29:25.308679401+02:00
class: standard
---

Source: stages/07-customer-portal/01-my-subscriptions-list.md

[[2026-05-20]] Wed 14:38
QA notes (2026-05-20, Chrome headless via Alumnium):
- Stage docs expect cust1@example.com, but actual Stage 00 account is cust1 / cust1@test.local; created Stage 07 QA fixture subscriptions #633-#678 plus empty-state user stage7-empty.
- My Account > Subscriptions for cust1 loads at ?page_id=9&subscriptions. Header columns observed in order: Product, Status, Next Payment, Total, Actions. Product cells show #ID links plus product names; Total cells show amount plus billing schedule; each visible row has View. Newest seeded IDs appear first.
- Visible statuses on page 1 include Active, Expired, Cancelled, Trial, Pending, On Hold. Active/On Hold rows show next-payment dates; Cancelled/Expired/Pending show dash. Trial row #663 incorrectly shows dash despite having _next_payment_date; issue #42. Visual badge colors could not be screenshot-verified because current Alumnium vision output is unavailable in this Codex environment; text/status coverage verified.
- Retention discount row #633 shows discounted total $23.99 / Every month from base $29.99, satisfying discounted-total behavior.
- Pagination summary shows Showing 1-10 of 12 subscriptions and Next/Page 2 controls. Clicking Next changed URL but kept the same rows and same summary; issue #44.
- Empty-state user stage7-empty shows exact message: You have no subscriptions yet. No table rendered.
- Clicking View for #633 navigates to ?page_id=9&view-subscription=633 and opens detail page.
- Sidebar account nav count shows Subscriptions 7 while list total is 12; issue #43.
Result: PASS for list rendering, columns, empty state, View navigation, discounted total; FAIL for pagination, sidebar count, and Trial next date.

[[2026-05-22]] Fri 03:00
Issue #44 fixed: customer portal pagination now reads the Subscriptions endpoint page value and emits Woo endpoint URLs like ?subscriptions=2. Browser verified cust1@test.local page 1 shows first 10 with Showing 1-10 of 13; Next opens ?subscriptions=2 with #633/#306/#271 and Showing 11-13 of 13; page 1 link returns to first-page rows.

[[2026-05-22]] Fri 05:27
Issue #42 fixed: Trial rows now show next-payment/trial-end date. Browser verified cust1 #663 Trial Weekly list row and detail page both show 27 May, 2026 6:31 PM (UTC+6), not dash.

[[2026-05-22]] Fri 05:29
Issue #43 fixed: account nav Subscriptions badge now counts the same statuses as the list. Browser verified 'Subscriptions 13' matches 'Showing 1-10 of 13 subscriptions'.
