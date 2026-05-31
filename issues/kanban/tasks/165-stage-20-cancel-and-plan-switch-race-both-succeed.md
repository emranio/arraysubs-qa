---
id: 165
title: 'stage-20: Cancel and plan switch race both succeed'
status: closed
priority: critical
created: 2026-05-23T21:16:28.316990116+02:00
updated: 2026-05-25T00:01:29.314456858+02:00
started: 2026-05-24T23:46:04.340548357+02:00
completed: 2026-05-25T00:01:24.137103639+02:00
tags:
    - qa
    - stage-20
    - concurrency
    - customer-portal
    - plan-switching
    - cancellation
class: standard
---

Stage 20 Task 04. Dedicated Sub-X #1808 owned by cust1@example.com, Pro Plan #233 Active. Ran near-simultaneous customer REST calls: POST /my-subscriptions/1808/cancel and POST /subscriptions/1808/switch new_product_id=231. Actual: both returned code 200. Cancel response: 'Subscription scheduled for cancellation at the end of your current billing period.' Switch response: 'Plan downgrade completed successfully'. Final state: status arraysubs-active, product changed to #231 Basic Plan, _waiting_cancellation=1, _cancellation_scheduled_date=2026-06-22 19:14:17, no pending_switch. Expected exactly one operation wins, other clean conflict/error; final state should not combine a successful product switch with a scheduled cancellation from the stale pre-switch state.

[[2026-05-23]] Sat 21:17
Extra evidence: notes show both operations at 19:16:10 UTC: cancellation scheduled note #1811 and plan downgrade notes #1812/#1813/#1815. Action state worse: _waiting_cancellation=1 and _cancellation_scheduled_date remains, but arraysubs_cancel_subscription action #1491 is canceled; renewal invoice/process #1492/#1493 were scheduled for new Basic Plan next date. Old renewal reminder #1488 remains pending for 2026-06-20, after old schedule, while new next payment is 2026-05-30. This leaves pending-cancel UI/meta without an executable cancel action.

[[2026-05-23]] Sat 21:22
Browser proof after forced reload: customer #1808 shows Active + Basic Plan + pending cancellation banner, while Change Plan / Cancel Subscription / Skip / Pause still visible. Related orders only parent #1806; no proration order. Admin edit forced reload shows final address from admin race unaffected. This confirms combined state is visible to customer, not only DB.

[[2026-05-25]] Mon 00:01
Fix plan: serialize customer-visible subscription mutations with a shared per-subscription Action Scheduler lock so cancel and plan-switch cannot both commit against stale state. Implementation: added HOOK_SUBSCRIPTION_MUTATION plus arraysubs_acquire_subscription_mutation_lock()/arraysubs_release_subscription_mutation_lock(); wrapped customer cancel, cancellation-flow cancel, and plan-switch execution in the lock and return 409 subscription_conflict when already held. Verification: php -l passed for ActionScheduler.php, subscription-helpers.php, CustomerController.php, CancellationController.php, and SwitchController.php. WP-CLI held-lock REST proof returned 409 for both cancel and switch on #1808. Browser-context Playwright proof as cust1@example.com on /my-account/view-subscription/1808/ also returned 409 for both locked calls; DB remained status=arraysubs-active, _product_id=231, _waiting_cancellation empty, _pending_switch empty. Screenshots: qa/artifacts/issue-165/subscription-1808-browser-before-lock.png and qa/artifacts/issue-165/subscription-1808-browser-after-conflict.png. No subscription mutation lock transients remain.
