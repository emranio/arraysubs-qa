# Pending end-of-period cancellation sets `_cancelled_date` before cancellation occurs

- Severity: low
- Date found: 2026-08-08
- Watch day: D06
- Originating task: `SLT-SW-10`
- Plan file: `/home/server-manager/www/arrayhash/mirror-help.arrayhash.com/public/wp-content/plugins/qa/subscription-lifecycle-test/kanban/tasks/098-pending-cancellation-with-a-required-reason-and.md`

## Affected records

- Subscription: `13402`
- Parent order: `13386`
- Product: `12608` (`SLT Plan Basic`)
- WP user: `369`, login `slt-cancel`, email `slt-cancel@example.test`, role `customer`
- Gateway: Stripe test, Visa ending `4242`
- Checkout type: block checkout
- Settings in play: `cancel_immediately=false`, cancellation reason required, retention discount and downgrade offers enabled, reactivation enabled. No temporary settings bracket was opened.

## Route and context

- Customer route: `https://mirror-help.arrayhash.com/my-account/view-subscription/13402/`
- Admin queue route: `https://mirror-help.arrayhash.com/wp-admin/tools.php?page=action-scheduler&s=13402`
- Browser contexts: `customer-SLT-SW-10` and `admin-SLT-SW-10` (both closed after evidence capture)

## Reproduction

1. Create a registered SLT customer and buy one `SLT Plan Basic` day/1 subscription through the block checkout with Stripe test mode.
2. Resolve the subscription from the completed parent order's sole `_subscription_ids` value; this run resolved order `13386` to subscription `13402`.
3. Open the subscription in the customer portal and choose **Cancel Subscription**.
4. Confirm that **Continue** is disabled until a reason is selected, choose **Found a better alternative**, decline the sole **Stay and Save!** retention offer, and confirm end-of-period cancellation.
5. Confirm the portal still reports **Active** and **Pending Cancellation**, with a future **Cancels On** date.
6. Before that future gate, read `_cancelled_date`, `_cancellation_scheduled_date`, `_waiting_cancellation`, and the subscription post status.

## Expected result

While the subscription is active and waiting for its future end-of-period cancellation, `_cancelled_date` is absent. At `CANCEL_AT`, the natural `arraysubs_cancel_subscription` action changes the status to `arraysubs-cancelled` and sets `_cancelled_date` to the actual transition time.

## Actual result

At `2026-08-08 22:59:43 UTC+6`, subscription `13402` was still `arraysubs-active`, `_waiting_cancellation=1`, and its cancellation action remained pending for `2026-08-09 16:46:26Z` (`22:46:26 UTC+6`). Nevertheless, `_cancelled_date` had already been written as `2026-08-08 16:51:34Z`, the time the customer submitted the request, almost 24 hours before the scheduled status transition. `_end_date` remained absent.

This creates an internally inconsistent active record and can mislead reporting or integrations that interpret `_cancelled_date` as the terminal cancellation timestamp.

## Proof

- Portal screenshot: `/home/server-manager/slt-evidence/SLT-SW-10-03-pending.png` shows **Active**, **Pending Cancellation**, and **Cancels On 9 August 2026 10:46 PM (UTC+6)**.
- Queue screenshot: `/home/server-manager/slt-evidence/SLT-SW-10-04-action.png`.
- Complete handoff: `/home/server-manager/slt-evidence/SLT-SW-10-D06-handoff.txt`.
- Fresh WP-CLI read at `2026-08-08 22:59:43 UTC+6`:

  ```text
  post_status=arraysubs-active
  _waiting_cancellation=1
  _cancellation_scheduled_date=2026-08-09 16:46:26
  _cancelled_date=2026-08-08 16:51:34
  _end_date=<absent>
  16147 arraysubs_cancel_subscription pending 2026-08-09 16:46:26 attempts=0
  16142 arraysubs_generate_renewal_invoice canceled 2026-08-09 16:37:40 attempts=0
  16143 arraysubs_process_renewal canceled 2026-08-09 22:37:40 attempts=0
  ```

- Pending-cancellation mail proves the event was scheduled, not completed:
  - `5ysPqwxhQ2uYV8YpLYgiRW` — `Subscription #13402 scheduled to cancel on 9 August, 2026 10:46 PM (UTC+6)` — customer.
  - `7XC1kVmhoi7fv7YR85qziQ` — same subject — admin.
- No console error, failed network response, renewal order, renewal invoice mail, or renewal payment mail was observed in the task-owned delta.

## Scope and counterexamples

- The customer-facing state, reason enforcement, retention-offer flow, two pending-cancellation messages, and exact unspread cancellation action all behaved correctly.
- Both renewal actions were canceled and no renewal order existed at handoff.
- This run proves the premature timestamp on one Stripe test, block-checkout, end-of-period cancellation. Immediate cancellation and a second pending-cancellation subscription were not exercised as counterexamples in this task.
- Only SLT-owned records were touched.

## D07 natural-transition follow-up

- Immutable cancellation baseline `7ab3FZRTZTYaiAIOBTBgkZ` was captured at `2026-08-09 16:41:26Z`, while the subscription was still active and `_cancelled_date` still held the premature request timestamp `2026-08-08 16:51:34Z`.
- Action `16147` then ran naturally via WP Cron at `16:47:03–16:47:07Z`. The subscription became `arraysubs-cancelled`, and `_cancelled_date` was overwritten to the actual transition time `2026-08-09 16:47:06Z`.
- This narrows the defect to the pending-cancellation interval: the terminal record self-corrects, but the active record exposed the incorrect cancellation timestamp for almost 24 hours.
- D07 evidence: `/home/server-manager/slt-evidence/SLT-SW-10-D07-followup.txt` and `/home/server-manager/slt-evidence/SLT-SW-10-05-cancelled.png`; exact cancellation mails are customer `2hXZkG5AyrsnuG9ToYiey0` and admin `4mPhuiua1mzfLkufmVffNH`.
