---
title: Full-refund actor is misattributed across the admin email and portal note
date: 2026-08-11
watch_day: D09
task_id: 114
task_key: SLT-ADM-08
stage: D09
plan_path: qa/subscription-lifecycle-test/kanban/tasks/114-refund-a-renewal-order-gateway-refund-subscription.md
status: open
severity: medium
---

## Task / date / plan

- Date found: `2026-08-11`
- Watch day: `D09`
- Originating task: card `#114` / `SLT-ADM-08`
- Plan file: `qa/subscription-lifecycle-test/kanban/tasks/114-refund-a-renewal-order-gateway-refund-subscription.md`

## Affected IDs

- Subscription ID: `12655` (`SLT Signup Fee Daily`)
- Fully refunded renewal order ID: `13590`
- WooCommerce refund IDs: `13729`, `13732`
- Product ID: `12577`
- Cancellation Mailpit IDs: customer `0kbJQu2TgMz0F67CzCQ7Ng`, admin `6szoJav661zHrZnZTwtNG1`

## Affected WordPress user / customer

- WordPress user ID: `347`
- Login / email: `slt-core` / `slt-core@example.test`
- Role: `customer`

## Gateway, checkout, and settings context

- Gateway: Stripe test
- Checkout type: `N/A` — cancellation was the automatic consequence of a full HPOS gateway refund
- Relevant settings: `refunds.cancellation_behavior=immediate`, `auto_gateway_refund=true`, `allow_prorated_refunds=true`
- Subscription metadata records `_cancelled_by=system` and `_cancellation_reason=Full refund processed`.

## Exact routes and browser context

- Refund route: `https://mirror-help.arrayhash.com/wp-admin/admin.php?page=wc-orders&action=edit&id=13590`
- Subscription route: `https://mirror-help.arrayhash.com/wp-admin/admin.php?page=arraysubs-mainadmin#/subscriptions/detail/12655`
- Mail context: task delta after immutable cursor `FULL_PRE=7I45BYNAnxiNrYm5uSvwHw`
- Browser/user context: WordPress administrator in `admin-SLT-ADM-08`; Mailpit read through `mailpit-agent`
- Cancellation timestamp: `2026-08-11 00:48:43Z` (`06:48:43 UTC+6`)

## Reproduction steps

1. With cancellation-on-full-refund enabled, fully refund Stripe renewal order `13590` in two gateway refunds totaling `$9.00`.
2. Re-read subscription `12655` and confirm it is `arraysubs-cancelled`, with `_cancelled_by=system` and reason `Full refund processed`.
3. Inspect the complete Mailpit delta after `FULL_PRE`.
4. Open the admin cancellation message `6szoJav661zHrZnZTwtNG1` and compare its actor wording with the persisted cancellation provenance.

## Expected result

The admin notification should attribute the cancellation to the system/full-refund automation, consistent with `_cancelled_by=system`, or omit an actor if that provenance cannot be rendered accurately.

## Actual result

The admin subject is `[mirror-help.arrayhash.com] Subscription #12655 cancelled by SLT Core`, and the body says `Subscription #12655 from SLT Core has been cancelled.` The customer portal note instead says `Canceled by Admin (admin)`. These surfaces present two different human actors even though the persisted actor is `system` and the cancellation reason is `Full refund processed`.

## Concrete proof

- Admin Mailpit ID `6szoJav661zHrZnZTwtNG1`, subject and body quoted above, sent to `admin@mirror-help.arrayhash.com` at `2026-08-11T00:48:43Z`.
- Persisted meta at the same gate:
  - `_cancelled_by=system`
  - `_cancellation_reason=Full refund processed`
  - `_refund_cancellation_order_id=13590`
  - `_cancelled_date=2026-08-11 00:48:43`
- Customer Mailpit ID `0kbJQu2TgMz0F67CzCQ7Ng` correctly states only that subscription `12655` was cancelled and gives reason `Full refund processed`.
- Portal note visible in `/home/server-manager/slt-evidence/SLT-ADM-08-05-history.png`: `Status changed from Active to Cancelled. Canceled by Admin (admin). The cancellation took effect immediately. Reason: Full refund processed.`
- Full-refund order/mail/UI proof: `/home/server-manager/slt-evidence/SLT-ADM-08-04-full.png`, `/home/server-manager/slt-evidence/SLT-ADM-08-05-history.png`.
- Complete execution record: `/home/server-manager/slt-evidence/SLT-ADM-08-D09-execution.md`.

## Scope notes and counterexamples

- The cancellation itself is correct: subscription `12655` is cancelled, both renewal actions are cancelled, and the customer-facing reason is accurate.
- The discrepancy is confined to actor attribution in the admin notification and customer-visible note for this system-driven full-refund path.
- The customer cancellation message is a same-event counterexample with accurate non-actor wording.
