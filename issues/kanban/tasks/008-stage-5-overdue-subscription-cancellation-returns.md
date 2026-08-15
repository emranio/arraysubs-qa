---
id: 8
title: 'stage-5: overdue subscription cancellation returns update_failed'
status: closed
priority: high
created: 2026-08-15T18:09:40.528105353+02:00
updated: 2026-08-15T18:10:12.720047172+02:00
started: 2026-08-15T18:09:46.252261389+02:00
completed: 2026-08-15T18:10:12.720046301+02:00
tags:
    - stage-05
    - stripe
    - cancellation
    - customer-portal
    - payment-migration
class: standard
---

QA source:
- Progress task: qa/progress task #7, stage-5.
- QA plan: qa/stages/05-checkout/12-paypal-and-paddle-flows.md.
- Found during final customer cleanup after the real Stripe renewal and cancellation regression.

Affected records:
- Subscription #27766; unpaid renewal order #27798.
- WordPress customer #471, core-stripe-final-20260815@example.test, role customer.

Route and browser context:
- https://mirror-help.arrayhash.com/my-account/view-subscription/27766/
- Browser session: core-stripe-fix, authenticated disposable customer.

Reproduction:
1. Start from active Stripe subscription #27766 whose canonical next-payment boundary is already overdue and whose failed renewal/retry state is still armed.
2. Open the customer subscription view and use the normal cancel action.
3. Complete the shared customer-portal cancellation confirmation.
4. Observe the cancellation REST request.

Expected:
- A valid but elapsed cancellation boundary cannot be scheduled in the past. Cancellation should safely fall back to immediate cancellation under the existing mutation lock, cancel the unpaid renewal, clear retry/payment-action state, and remove pending scheduled actions.

Actual:
- Before the fix, the customer cancellation REST request returned HTTP 500 with code update_failed. The subscription remained active and the overdue retry pipeline remained armed.

Concrete proof:
- Browser network observation captured the same cancellation endpoint returning 500 before the patch and 200 after the patch.
- Before fix, the canonical boundary parsed successfully but was less than or equal to the current timestamp, and arraysubs_schedule_cancellation_under_lock rejected it.
- After fix, subscription #27766 became Cancelled; unpaid renewal #27798 became Cancelled and unpayable; retry/action metadata was empty; pending scheduled actions were zero.
- Screenshot: qa/artifacts/payment-bug-fixes-20260815/stripe-overdue-cancellation-fixed.png.

Known scope and counterexamples:
- Paddle subscription #27828 had a future valid boundary and correctly entered pending end-of-period cancellation through the same portal UI.
- Missing or malformed boundaries remain fail-closed. Only a valid elapsed canonical boundary receives the immediate-cancellation fallback.
- The records were disposable QA fixtures and were removed only after the fixed state was verified.


Fix plan and implementation:
- Keep missing or malformed boundaries fail-closed.
- Inside the existing cancellation mutation lock, detect a valid canonical boundary that is already elapsed.
- Route only that elapsed-boundary case through the existing immediate-cancellation helper so renewal cleanup, provider cancellation, metadata cleanup, and scheduler cleanup stay centralized.
- Re-run the same customer browser flow and compare the endpoint response plus local order/subscription/actions state.

Resolution verification:
- Implemented in arraysubs/src/functions/cancellation-helpers.php.
- The same customer action changed from HTTP 500 update_failed to HTTP 200.
- Browser displayed the cancelled subscription state with no native dialog or JavaScript error.
- The unpaid renewal became cancelled/unpayable, all retry and payment-action markers were empty, and no invoice/process/retry actions remained.
- A future-boundary Paddle subscription continued to schedule end-of-period cancellation, proving the fallback is narrowly scoped.
- Closed after real browser retest; no lint or PHPCS was run per workspace instructions.
