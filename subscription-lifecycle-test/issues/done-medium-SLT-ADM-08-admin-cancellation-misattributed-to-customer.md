---
title: Full-refund actor is misattributed across the admin email and portal note
date: 2026-08-11
watch_day: D09
task_id: 114
task_key: SLT-ADM-08
stage: D09
plan_path: qa/subscription-lifecycle-test/kanban/tasks/114-refund-a-renewal-order-gateway-refund-subscription.md
status: fixed
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

## Resolution — 2026-08-14

### Investigation and root cause

The historical evidence and live metadata still agree on `_cancelled_by=system`, reason
`Full refund processed`, and refund order `13590`, so the report was not a false positive.

Two independent assumptions caused the contradictory presentation:

- `AdminSubscriptionCancelledEmail` hard-coded `{customer_name}` after the words `cancelled by`,
  regardless of the persisted actor.
- `AutoNotes::getStatusActorDetails()` understood gateway, `admin:<id>`, and customer actors but
  not explicit `system`; because the refund ran in an authenticated admin request, it fell through
  to the current-user branch and rendered `Admin (admin)`.

### Fix and safety review

- Added the shared core helper `arraysubs_get_cancellation_actor_details()` for normalized,
  translatable System, Admin, Customer, Gateway, and Unknown presentation. User and gateway labels
  are sanitized; stored IDs are passed through `absint`; unexpected values never fall back to the
  currently authenticated user.
- AutoNotes now consumes the stored cancellation actor before considering request context.
- The admin cancellation email now offers `{cancellation_actor}`, defaults its subject to
  `cancelled by {cancellation_actor}`, and states the customer and actor separately in HTML and
  plain-text bodies. The reason remains independently escaped and displayed.
- This is shared lifecycle/email behavior in `arraysubs`; Pro has no duplicate actor formatter.
  No permission, nonce, REST, gateway, cancellation, or mail-recipient contract changed.

### Regression verification

- Read-only checks on existing records resolved `12655` as `System`, admin-cancelled `13917` as
  `Admin (admin)`, and customer-cancelled `13402` as `Customer (SLT Cancel)`.
- Rendering the fixed email against original subscription `12655` without sending produced subject
  `[mirror-help.arrayhash.com] Subscription #12655 cancelled by System`; its plain template also
  contained `cancelled by System`.
- A disposable SLT subscription `26653` was cancelled through the canonical helper with actor
  `system`. Mailpit message `6PjD29mDKopj2lhPnHbzsT` had subject
  `[mirror-help.arrayhash.com] Subscription #26653 cancelled by System`, body
  `Subscription #26653 for SLT Admincreated has been cancelled by System`, and reason
  `Full refund processed`.
- The live admin detail/timeline rendered:
  `Status changed from Active to Cancelled. Cancelled by System. The cancellation took effect immediately. Reason: Full refund processed.`
  Screenshot: `/home/server-manager/slt-evidence/FIX-MEDIUM-SLT-ADM-08-system-actor.png`.
- Exact cleanup removed disposable subscription `26653`, notes `26654`, `26655`, `26657`, `26658`,
  and cancelled reminder action `23416`; post, note relationship, and scheduler queries all returned
  zero afterward. Mailpit retains the test message as immutable evidence.
