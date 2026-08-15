# SLT-SETUP-99B cannot delete product 12112 while external subscriptions reference it

## Scope

- Task: 119, `SLT-SETUP-99B`
- Severity: critical
- Date: 2026-08-15
- Watch day: D13
- Stage: pre-deletion allowlist and zero-orphan safety reconciliation, before `M0`, tail cancellation, action cancellation, or deletion
- Plan path: `kanban/tasks/119-slt-setup-99b-post-watch-teardown-on-2026-08-15.md`
- Site: `https://mirror-help.arrayhash.com`
- Initial supplied-facts timestamp: `2026-08-15 16:10:01` site / `10:10:01` UTC
- Initial live verification window: `2026-08-15 16:13:05-16:38:00` site / `10:13:05-10:38:00` UTC; browser verification ended at `16:24:45` site and the final exact-ID CLI refresh ended at `16:38:00` site
- Night supplied-facts timestamp: `2026-08-15 21:42:01` site / `15:42:01` UTC
- Night live verification window: `2026-08-15 21:45:12-21:54:58` site / `15:45:12-15:54:58` UTC
- Browser session: `admin-SLT-SETUP-99B`
- Browser routes: `/wp-admin/admin.php?page=arraysubs-mainadmin#/subscriptions/detail/27527` and `/wp-admin/admin.php?page=arraysubs-mainadmin#/subscriptions/detail/27828`
- Runtime checks: read-only WP-CLI from the WordPress root with `--allow-root`
- Gateway: Paddle (`arraysubs_paddle`)
- Checkout type: outside task 119; the originating concurrent fixture was not operated by this teardown runner
- Non-default settings opened by this runner: none

## Verdict

BLOCKED. At the final live read, task-owned product candidate `12112` remains referenced by external subscription/order/customer `27527/27511/469`. A second external relationship, `27828/27827/471`, existed during the mandatory night snapshot and browser verification but was removed later by its concurrent owner. Deleting product `12112` while the persistent `27527` relationship remains would leave an out-of-scope subscription referencing a deleted product and would violate both the preserve-everything-else contract and task 119's zero-orphan assertion.

This finding does not authorize cancellation, deletion, reassignment, migration, or candidate-set expansion for IDs `469`, `471`, `27511`, `27527`, `27827`, or `27828`. This runner preserved all six while live and did not perform the later cleanup of `471/27827/27828`.

## Affected records

- Task-owned product candidate: `12112`, published `SLT Paddle Daily`.
- Excluded subscription: `27527`, currently `arraysubs-on-hold` after being `arraysubs-pending` at the afternoon discovery, product `12112`, customer/author `469`, parent order `27511`, next payment `2026-08-16 09:28:10` UTC, completed payments `0`, recurring amount USD `11.00`.
- Excluded HPOS order: `27511`, currently `wc-cancelled` after being `wc-pending` at the afternoon discovery, customer `469`, USD `11.00`, payment method `arraysubs_paddle`, created `2026-08-15 09:28:07` UTC. Its sole line item is product `12112`, quantity `1`, line total `11`; `_subscription_ids` resolves exactly to `27527`.
- Excluded customer: `469`, login `paddle-phantom-fix-20260815`, email `paddle-phantom-fix-20260815@example.test`, role `customer`.
- Exact Action Scheduler rows whose first argument is `27527`: zero at the live read; this does not make the live post/order/product relationship safe to delete.
- Transient second excluded subscription: `27828`, observed `arraysubs-active`, product `12112`, customer/author `471`, parent order `27827`, next payment `2026-08-16 15:42:36` UTC, completed payments `1`, recurring amount USD `11.00`; absent at the final read.
- Transient second excluded HPOS order: `27827`, observed `wc-processing`, customer `471`, USD `26.00`, payment method `arraysubs_paddle`, created `2026-08-15 15:39:26` UTC. Its `_subscription_ids` resolved exactly to `27828`; its two lines were product `12112` (`SLT Paddle Daily`, quantity `1`, line total `11`) and unrelated product `447` (`Standard Tee`, quantity `1`, line total `15`). It was absent at the final read.
- Transient second excluded customer: `471`, login `core.stripe`, email `core-stripe-final-20260815@example.test`, role `customer`; absent at the final read.
- Historical scheduler rows for absent `27828`: canceled, unattempted `24289/24290`, `24292/24293`, `24294/24295`, and cancellation row `24303`. This runner did not change them.

## Expected

Every task-owned product candidate can be deleted without changing or orphaning an out-of-scope subscription, order, or user. An exact inverse-reference query over the 35 task-owned product/variation candidates, excluding the 49 exact task-owned subscription candidates, returns zero rows.

## Actual

The afternoon bounded inverse-reference query returned one row, subscription `27527` on product `12112`. The later night query returned exactly two rows, adding subscription `27828` on the same product. The final query returned only `27527` after external cleanup removed `27828/27827/471`. All 49 task-owned subscription candidates and all 35 task-owned product/variation candidates remain present.

The afternoon live ArraySubs detail UI independently showed:

- `PENDING` subscription `#27527` for `SLT Paddle Daily`.
- Customer `Paddle Phantom Fix QA`, email `paddle-phantom-fix-20260815@example.test`.
- Next payment `16 August, 2026 3:28 PM (UTC+6)` and completed payments `0`.
- Pending initial order `#27511` for USD `11.00`.

The task-specific browser session was closed explicitly. Page errors were empty; console output contained only JQMIGRATE informational entries.

An evening refresh at `2026-08-15 19:14:06-19:24:14` site / `13:14:06-13:24:14` UTC showed the same relationship after its natural state transition:

- `ON HOLD` subscription `#27527`; product, customer, next date, completed payments, and recurring amount unchanged.
- Initial order `#27511` now `cancelled`, still USD `11.00` and still relationship-linked only to `27527`.
- Timeline transition from Pending to On Hold at `15 August, 2026 5:29 PM (UTC+6)` because payment did not complete.
- Zero exact Action Scheduler rows containing `27527` in their args.
- Screenshot: `/home/server-manager/slt-evidence/SLT-SETUP-99B-D13-evening-27527-on-hold.png`; page errors empty, console informational only, named session closed.

The terminal statuses do not make the relationship task-owned and do not authorize deletion. The inverse-reference blocker therefore remains unchanged in substance.

### Night refresh - second external relationship

The mandatory night facts were generated at `2026-08-15 21:42:01` site / `15:42:01` UTC and first recorded `27828` pending against product `12112` and parent order `27827`. A targeted exact-ID read through `21:54:58` site observed the natural Paddle completion:

- Subscription `27828` is now `ACTIVE`, with one completed payment, last payment `15 August, 2026 9:42 PM (UTC+6)`, and next payment `16 August, 2026 9:42 PM (UTC+6)`.
- The ArraySubs detail UI shows product `SLT Paddle Daily`, customer `Core Stripe` / `core-stripe-final-20260815@example.test`, recurring amount USD `11.00`, payment method Paddle, and processing initial order `27827` for USD `26.00`.
- Screenshot: `/home/server-manager/slt-evidence/SLT-SETUP-99B-D13-night-27828-external-active.png`.
- Page errors were empty, console output was JQMIGRATE-only, and isolated session `admin-SLT-SETUP-99B` returned `Browser closed`.
- Four Mailpit messages newer than the supplied night cursor belong exactly to external order/subscription `27827/27828`: `069PwSjxRaokQ41eywMU0P`, `6HFrf902PzkjjE2iyfgAn2`, `4MLNNg4iqy2hw3R6SbiIll`, and `5Llpt8hUQ0lkZNxZawjCJw`. The pre/post browser latest ID was unchanged at `5Llpt8hUQ0lkZNxZawjCJw`.

### Final external-cleanup reconciliation

At `2026-08-15 22:11:03` site / `16:11:03` UTC, exact reads found subscription `27828`, order `27827`, and user `471` absent. All seven exact scheduler rows that still name `27828` were canceled and unattempted. Six messages newer than the browser cutoff were fully external: four scheduled/final cancellation messages for `27828` (`03ZTIliqxKiSgg9SFwRq7u`, `6gb2qHPil9MPhrBR4l6k3T`, `2gTG4Ei8Jkiq9EzJBxaqnJ`, `3lFshAps0yTNN5UVzuosi7`) and two cancellation messages for unrelated `27766` (`7i1iscC3G75vqN4A6jazmm`, `3cJ7p0udt9b4JxrA0gz7N2`). No message or cleanup action was triggered by this runner.

The current bounded inverse-reference result is again one row, `27527 -> 12112 -> 469/27511`. That persistent relationship remains sufficient to block product deletion.

## Reproduction

1. Start from the exact 35 product/variation IDs and 49 subscription IDs already enumerated in `watch-reports/D13-2026-08-15.md`.
2. Query only `arraysubs_data` posts whose `_product_id` is in that exact product list and whose subscription ID is not in the exact 49-subscription list.
3. Resolve the returned row by exact ID and read only `_customer_id`, `_product_id`, `_parent_order_id`, `_next_payment_date`, and completed-payment metadata.
4. Resolve exact user `469`, HPOS order `27511`, its `_subscription_ids`, and its sole line item.
5. Open the exact subscription detail route in isolated session `admin-SLT-SETUP-99B` and compare the UI relationship with the read-only database result.
6. Confirm that `469`, `27511`, and `27527` do not appear in the exact user, order, or subscription candidate sets enumerated during D13.

## Concrete safety proof

- The final current inverse-reference result contains one and only one excluded subscription: `27527 -> product 12112 -> customer 469 -> parent order 27511`. The transcript preserves the time-bounded two-row night result before external cleanup.
- A final bounded read at `2026-08-15 10:38:00` UTC returned the same sole inverse relationship and reconfirmed the four exact records remained live.
- The exact order line is product `12112`, quantity `1`, USD `11.00`; the order-to-subscription relationship contains only `27527`.
- No scheduler row for `27527` was cancelled or run.
- Night evidence transcript: `/home/server-manager/slt-evidence/SLT-SETUP-99B-D13-night-blocker-refresh.txt`.
- `M0` and `M1` remain unset because the mutating phase never began.
- From afternoon cursor `3N6JcM0eoDrvQYq84uTBap` through bounded cutoff `6qYLejWgcjbrZNIW3BKfmp`, all nine newer Mailpit messages belong to unrelated subscription `27614` and orders `27598`/`27628`; none names task 119, `12039`, `12172`, `12749`, `27511`, or `27527`.
- Tail subscriptions cancelled: none.
- WordPress artifacts, settings, scheduled actions, users, Mailpit messages, and prior evidence changed by this runner: none. The evening refresh added only the read-only browser screenshot cited above.
- At the evening refresh, Mailpit latest remained `2EQ62uMdXL6UyqXgfGHegm`, identical to the supplied evening facts before the browser read; the read-only refresh emitted no mail.

## Related blockers and safe retry requirement

- The independent ownership-closure blocker remains in `issues/SLT-SETUP-99B-unallowlisted-subscription-notes.md`: 343 unallowlisted subscription-note posts are still owned by the teardown users.
- Board card `138` is separately still `todo`; this runner did not alter it or create a remediation card.
- A safe retry must first prove that no unallowlisted live subscription references any exact teardown product. Cleanup or disposition of `469`, `27511`, and `27527` belongs to their owner and is outside task 119; task 119 must not act on them.
- Product `12112` must remain live while the persistent `27527` relationship exists. No broader deletion selection or inferred allowlist expansion is permitted.
