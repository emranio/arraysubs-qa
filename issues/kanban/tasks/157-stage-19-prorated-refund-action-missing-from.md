---
id: 157
title: stage-19 prorated refund action missing from subscription detail
status: closed
priority: high
created: 2026-05-23T19:06:37.161896718+02:00
updated: 2026-05-24T22:56:10.477291256+02:00
started: 2026-05-24T22:43:31.128987645+02:00
completed: 2026-05-24T22:56:02.619854297+02:00
tags:
    - qa
    - stage-19
    - refunds
    - admin-ui
class: standard
---

Task: stages/19-refunds/02-prorated-refund-on-cancellation.md

Fixture: active Standard Weekly subscription #1704 with last/next payment dates set halfway through current weekly cycle and refund settings enabled.

Expected: ArraySubs admin subscription detail should expose a Prorated Refund action/button or modal, allowing preview and processing from UI.

Observed with Alumnium on /wp-admin/admin.php?page=arraysubs-mainadmin#/subscriptions/detail/1704: action buttons visible were Cancel Subscription, Edit Subscription, Login as Customer, Skip Renewal, Pause Subscription, Resync from Gateway, Detach Gateway. No Issue Prorated Refund action, preview affordance, amount field, reason field, or cancel-after-refund control appeared.

Code suspect: Refunds\Services\Hooks::addProratedRefundAction() emits PHP markup on an admin hook, but the React SubscriptionDetail route does not render that server-side action area or wire the refund REST endpoints.

Impact: documented prorated refund workflow cannot be completed from browser UI; QA had to use REST endpoint directly.

[[2026-05-24]] Sun 22:55
Fix: React subscription detail route now receives prorated refund availability from detail REST actions and renders a Prorated Refund header action. Clicking opens a shared Modal wired to the existing preview and process REST endpoints, with read-only calculated amount, reason field, default checked Cancel after refund checkbox, and SpinnerButton loading state. Verification: php -l passed, npm run build passed, FPM reloaded. Alumnium saw old SPA bundle after navigate; Playwright fresh-browser fallback verified active refundable subscription #1673 shows Prorated Refund action and modal controls without processing a refund. Screenshots: qa/artifacts/issue-157/subscription-1673-prorated-refund-action.png and qa/artifacts/issue-157/subscription-1673-prorated-refund-modal.png.
