# Cancelled subscription exposes no customer reactivation action while reactivation is enabled

- Severity: medium
- Date found: 2026-08-09
- Watch day: D07
- Originating task: `SLT-SW-10`
- Plan file: `/home/server-manager/www/arrayhash/mirror-help.arrayhash.com/public/wp-content/plugins/qa/subscription-lifecycle-test/kanban/tasks/098-pending-cancellation-with-a-required-reason-and.md`

## Affected records

- Subscription: `13402`
- Parent order: `13386`
- Renewal order: N/A; exact relationship count remained zero
- Product: `12608` (`SLT Plan Basic`)
- WP user: `369`, login `slt-cancel`, email `slt-cancel@example.test`, role `customer`
- Gateway: Stripe test, saved Visa ending `4242`
- Checkout type: block checkout
- Settings in play: `customer_actions.allow_reactivation=true`, `cancel_immediately=false`, cancellation reason required, retention discount and downgrade offers enabled. No temporary settings bracket was opened.

## Routes and browser contexts

- Customer detail: `https://mirror-help.arrayhash.com/my-account/view-subscription/13402/?slt_cache=20260809T165626Z`
- Customer list: `https://mirror-help.arrayhash.com/my-account/subscriptions/?slt_cache=20260809T165626Z`
- Admin detail countercheck: `https://mirror-help.arrayhash.com/wp-admin/admin.php?page=arraysubs-mainadmin&slt_cache=20260809T165626Z#/subscriptions/detail/13402`
- Browser contexts: `customer-SLT-SW-10-R1` and `admin-SLT-SW-10-R1`

## Reproduction

1. With customer reactivation enabled and immediate cancellation disabled, create an SLT customer and buy one `SLT Plan Basic` day/1 subscription through block checkout with Stripe test mode.
2. Resolve the subscription from the completed parent order's sole `_subscription_ids` relationship. This run resolved order `13386` to subscription `13402` for user `369` and product `12608`.
3. From the customer subscription detail, choose cancellation, select **Found a better alternative**, decline the retention offer, and confirm end-of-period cancellation.
4. Let the exact `arraysubs_cancel_subscription` action run naturally at the stored cancellation timestamp. In this run action `16147` completed via WP Cron at `2026-08-09 16:47:03–16:47:07Z`.
5. Confirm that the subscription is `arraysubs-cancelled`, then reload the exact customer detail with a cache-busting query string.
6. Inspect the customer subscriptions list and the cache-busted admin detail as counterchecks.

## Expected result

Because `customer_actions.allow_reactivation=true`, the cancelled subscription exposes **Reactivate Subscription** to its owner. Clicking it should allow the authored confirmation flow, return the subscription to active, clear cancellation/end metadata, send exactly one customer reactivation email, and permit the resulting renewal queue to be inspected.

## Actual result

The customer detail has no Subscription Actions section, button, or link containing `Reactivate`. The customer subscriptions list shows only **View** in the Actions column. Programmatic DOM reads on both cache-busted customer routes returned `reactivateText=false` and an empty matching action list. The cache-busted admin detail also exposes no reactivation action.

The task therefore cannot take `REACT_PRE`, click the authored customer action, capture the required `SLT-SW-10-06-reactive.png`, observe a reactivation email, or test whether reactivation reschedules renewal legs. No substitute admin/status/meta mutation was used. Subscription `13402` remains cancelled with an empty next-payment date and no renewal actions, so the post-reactivation scheduling assertion is `UNVERIFIED` rather than evidence for the separately anticipated no-reschedule bug.

## Proof

- `/home/server-manager/slt-evidence/SLT-SW-10-05-cancelled.png` visibly shows the cancelled customer detail with no actions section.
- `/home/server-manager/slt-evidence/SLT-SW-10-05b-no-reactivation-list.png` visibly shows the cancelled list row with **View** as its only action.
- `/home/server-manager/slt-evidence/SLT-SW-10-05c-admin-after-cancel.png` is the cache-busted admin countercheck.
- `/home/server-manager/slt-evidence/SLT-SW-10-D07-followup.txt` contains the immutable baseline, natural action, mail, state, and missing-control evidence.
- Browser diagnostics on the cancelled customer detail reported no page errors; its console contained only the standard JQMIGRATE informational messages, with no task-related JavaScript exception or failed request.
- Live settings read: `customer_actions.allow_reactivation=true`.
- Stable post-cancel state after the authored `22:56:26` site cutoff: `arraysubs-cancelled`; `_next_payment_date` empty; `_completed_payments=1`; `_waiting_cancellation`, `_cancellation_scheduled_date`, `_end_date`, pending-renewal-order meta, and renewal action-id metas absent; `_cancelled_date=2026-08-09 16:47:06Z`.
- Action `16147`: scheduled `16:46:26Z`, one attempt, started via WP Cron `16:47:03Z`, completed via WP Cron `16:47:07Z`.
- Exact cancellation mail pair: customer `2hXZkG5AyrsnuG9ToYiey0` and admin `4mPhuiua1mzfLkufmVffNH`; no extra task-owned mail through cutoff.
- Exact relationship-linked renewal-order count: `0`; current renewal invoice/process action count: `0`.

## Scope and counterexamples

- The cancellation action, status transition, customer/admin cancellation mail, and suppression of the renewal order all worked correctly.
- The missing action reproduces on both customer routes after cache-busting and is not explained by the live reactivation setting, which is true.
- The cache-busted admin detail correctly reports the cancelled state but also offers no reactivation action. This is a surface countercheck only; the authored operation is customer-side.
- No second cancelled subscription or another gateway was used as a counterexample in this task.
- Only SLT-owned records were read or changed, and the watcher performed no manual subscription-state, date, action, order, or settings mutation.
