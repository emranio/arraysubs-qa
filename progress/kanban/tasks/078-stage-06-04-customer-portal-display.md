---
id: 78
title: 'stage-06: 04 Customer Portal Display'
status: closed
priority: high
created: 2026-05-19T22:56:08.924457573+02:00
updated: 2026-05-22T02:55:32.434164149+02:00
started: 2026-05-20T13:41:52.83015709+02:00
completed: 2026-05-20T14:06:25.85772545+02:00
tags:
    - qa
    - stage-06
claimed_by: mold-glade
claimed_at: 2026-05-22T02:55:32.434163117+02:00
class: standard
---

Source: stages/06-initial-lifecycle/04-customer-portal-display.md

[[2026-05-20]] Wed 14:06
QA complete using #618/customer-pending Basic Monthly control because customer-classic artifact is missing (#35). My Account → Subscriptions URL loaded as ?page_id=9&subscriptions; no empty state. Table headers: Product, Status, Next Payment, Total, Actions. Row: #618 Basic Monthly, Active, 20 June 2026 5:53 PM (UTC+6), 9.99 Every month, View. Detail URL loaded as ?page_id=9&view-subscription=618. Overview rows: Active, Basic Monthly, start 20 May 2026 5:53 PM, next 20 June 2026 5:53 PM, recurring 9.99 Every month, payment method Direct bank transfer with Manage payment methods link. Actions visible: Change Plan, Cancel Subscription, Update Shipping Address. Change Plan opened modal but failed with 'Failed to load plan options. Please try again.' filed issue #39. Cancel Subscription opened confirmation modal with immediate-cancel warning, required reason dropdown, Keep Subscription/Continue; closed without cancelling. Skip/Pause controls absent; settings have no skip_pause config. Related Orders table: #610, 20 May 2026, Completed, 9.99, View, no Pay action. Customer-visible notes shown; private Email sent note hidden, but duplicate payment-success notes visible (covered by #38).

[[2026-05-22]] Fri 02:55
Issue #39 fixed: plan-switch modal now uses fetch for switch-options/preview/execute REST calls. Browser verified customer-pending subscription #618 Change Plan opens Pro Monthly option and Select loads proration preview with amount due 9.33; no confirmation submitted.
