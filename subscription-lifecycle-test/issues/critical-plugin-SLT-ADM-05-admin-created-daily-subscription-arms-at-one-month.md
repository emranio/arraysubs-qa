---
title: SLT-ADM-05 admin-created daily subscription arms one month later instead of one day later
date: 2026-08-05
task_id: 47
task_key: SLT-ADM-05
stage: D03
plan_path: qa/subscription-lifecycle-test/kanban/tasks/047-admin-create-a-subscription-for-slt-admincreated.md
status: open
severity: high
---

## Task / stage / plan

- QA progress task: `#47` / `SLT-ADM-05`
- Stage: `D03`
- Plan path: `qa/subscription-lifecycle-test/kanban/tasks/047-admin-create-a-subscription-for-slt-admincreated.md`

## Affected IDs

- Subscription ID(s): `12760`
- Order ID(s): `N/A`
- Product ID(s): `11927` (`SLT Daily Core`)

## Affected user / customer context

- WordPress user ID(s): `353`
- Login / email: `slt-admincreated` / `slt-admincreated@example.test`
- Role(s): `customer`

## Gateway, checkout, and settings context

- Gateway: `N/A` — this is the authored admin-created/manual-payment path; subscription `12760` has no gateway payment method.
- Checkout type: `N/A` — no storefront, cart, or checkout was used.
- Task-specific temporary settings: none.
- Window-wide non-default settings in play: `renewals.sync_to_billing_cycle=false`, `customer_actions.allow_early_renew=true`, `customer_actions.allow_reactivation=true`, `pause_subscription.enabled=true`, and `pause_subscription.customer_can_pause=true`. The completed `SLT-SYN-04` bracket had already restored its temporary setting before this task began.

## Exact routes / browser context

- Browser context: `agent-browser --session admin-SLT-ADM-05`
- Create route: `https://mirror-help.arrayhash.com/wp-admin/admin.php?page=arraysubs-mainadmin#/subscriptions/form`
- Edit route: `https://mirror-help.arrayhash.com/wp-admin/admin.php?page=arraysubs-mainadmin#/subscriptions/edit/12760`
- Pending actions route: `https://mirror-help.arrayhash.com/wp-admin/tools.php?page=action-scheduler&status=pending&s=12760`

## Reproduction steps

1. Open the ArraySubs admin create-subscription form in `admin-SLT-ADM-05`.
2. Select customer `slt-admincreated` and product `SLT Daily Core`.
3. Leave the authored values exactly as tasked: quantity `1`, recurring amount `10.00`, billing interval `1`, billing period `Day(s)`, length `0`, signup fee `0`, trial `0`, invoice email `slt-admincreated@example.test`, billing address prefilled from the customer.
4. Click `Create Subscription`.
5. Resolve the new subscription row by exact customer/product/status. The created subscription is `12760`.
6. Inspect raw meta with `wp post meta list 12760 --keys=_next_payment_date,_start_date,_renewal_action_id,_renewal_invoice_action_id,_customer_id,_product_id,_recurring_amount,_billing_period,_billing_interval --allow-root`.
7. Change status from `Pending` to `Active` in the real edit UI and confirm the modal.
8. Re-check raw meta and search the Scheduled Actions screen for `12760`.

## Expected result

- Because the product is day/1, arming should schedule the first renewal one day later, not one month later.
- Any `_next_payment_date` and action schedule should align with the day/1 interval.

## Actual result

- Creation already seeds `_next_payment_date=2026-09-05 13:03:41` even while the subscription is still `arraysubs-pending`.
- After `Pending -> Active`, the subscription arms immediately, but it arms on a one-month cadence:
  - `_renewal_invoice_action_id=14921`
  - `_renewal_action_id=14922`
  - extra reminder action `14923`
  - pending invoice action at `2026-09-05 08:29:37Z`
  - pending renewal action at `2026-09-05 14:29:37Z`
- The product and subscription metadata still say day/1:
  - `_billing_period=day`
  - `_billing_interval=1`
  - `_product_id=11927`
  - `_recurring_amount=10`

## Concrete proof

- Raw meta before activation:
  - `post_status=arraysubs-pending`
  - `_next_payment_date=2026-09-05 13:03:41`
  - `_renewal_action_id` absent
  - `_renewal_invoice_action_id` absent
- Raw meta after activation:
  - `post_status=arraysubs-active`
  - `_next_payment_date=2026-09-05 13:03:41`
  - `_renewal_invoice_action_id=14921`
  - `_renewal_action_id=14922`
- Pending Action Scheduler rows after activation:
  - `14923 arraysubs_send_renewal_reminder pending 2026-09-02 14:29:37 +0000`
  - `14921 arraysubs_generate_renewal_invoice pending 2026-09-05 08:29:37 +0000`
  - `14922 arraysubs_process_renewal pending 2026-09-05 14:29:37 +0000`
- Activation mail proof:
  - customer mail `38K3YDfsNAdXfackwV6Dzu` — `[mirror-help.arrayhash.com] Your subscription #12760 is active`
  - admin mail `5aGyzH2h9TWehGZqHuRDH9` — `[mirror-help.arrayhash.com] New subscription #12760 from SLT Admincreated`
- Screenshots:
  - `/home/server-manager/slt-evidence/SLT-ADM-05-01-form.png`
  - `/home/server-manager/slt-evidence/SLT-ADM-05-02-toast.png`
  - `/home/server-manager/slt-evidence/SLT-ADM-05-03-queue-empty-pending.png`
  - `/home/server-manager/slt-evidence/SLT-ADM-05-04-queue-active-has-legs.png`

## Known scope / counterexamples

- This issue is specific to the admin-created daily subscription path exercised here.
- The activation behavior itself is no longer the old “unscheduled active” path from the authored task; activation does arm actions now.
- Counterexample on the same task path: the created subscription/product still carry `day` / `1`, so the one-month schedule is not explained by the saved interval metadata.
