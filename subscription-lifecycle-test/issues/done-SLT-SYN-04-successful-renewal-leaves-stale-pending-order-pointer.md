# Successful renewal leaves a stale pending-order pointer on subscription 12564

- Severity: medium
- Date found: 2026-08-14
- Watch day: D12
- Originating test task: `SLT-SYN-04`
- Plan file: `/home/server-manager/www/arrayhash/mirror-help.arrayhash.com/public/wp-content/plugins/qa/subscription-lifecycle-test/kanban/tasks/061-slt-syn-04-prove-global-sync-to-billing-cycle-true.md`

## Affected records

- Subscription: `12564`, `SLT Sync Global Daily`
- Parent order: `12563`
- Renewal order: `20230`, cycle `4`
- Product: `12125`, `SLT Sync Global Daily`
- WP user: `350`, login `slt-flex`, email `slt-flex@example.test`, role `customer`
- Gateway: Stripe test, Visa ending `4242`
- Checkout type: classic checkout (`/slt-classic-cart` and `/slt-classic-checkout`) for the original subscription purchase
- Settings in play: the store baseline is restored to its pre-window global-sync value `true` and first-charge mode `full`; this subscription persists its authored `_renewal_sync_enabled=yes` and `_renewal_sync_first_charge_mode=full` schedule.

## Routes and browser context

- Subscription detail: `https://mirror-help.arrayhash.com/wp-admin/admin.php?page=arraysubs-mainadmin#/subscriptions/detail/12564`
- Renewal order: `https://mirror-help.arrayhash.com/wp-admin/admin.php?page=wc-orders&action=edit&id=20230`
- Scheduled Actions search: `https://mirror-help.arrayhash.com/wp-admin/tools.php?page=action-scheduler&s=12564`
- Browser context: authenticated admin session `admin-SLT-EML-14-D12`

## Reproduction steps

1. Start with relationship-resolved subscription `12564`, obtained from parent order `12563`'s singleton `_subscription_ids` value, and cross-check customer `350` and product `12125`.
2. Let its D12 renewal run naturally. Do not force Action Scheduler. Invoice action `16968` completes via WP-Cron and creates renewal order `20230`; replacement charge action `19946` then completes via WP-Cron at the same scheduled gate as cancelled predecessor `16969`.
3. Open subscription `12564` in the ArraySubs admin UI. Confirm it is Active, completed payments are `4`, last payment is 14 August at 02:50 site time, and next payment is 17 August at 00:00 site time.
4. Open order `20230`. Confirm it is Completed, totals USD `18.00` with zero tax, contains one quantity-one `SLT Sync Global Daily` line, and is displayed as a Renewal in the subscription order history.
5. Re-read subscription meta after the successful charge, webhook processing, schedule advancement, and payment-success email. Observe `_pending_renewal_order_id=20230` still present.
6. Compare recent successful SLT renewals `12172` / order `13788` and `13277` / order `13576`; both are active and have no pending-order pointer.

## Expected result

After successful payment, `_pending_renewal_order_id` is deleted while `_completed_payments`, `_last_payment_date`, and `_next_payment_date` advance. The suite's code-verified lifecycle reference records this cleanup contract at `reference/SLT-REF-01-renewal-timeline-offsets-from-next-payment-date-order-sub-st.md:53`.

## Actual result

The payment, order, subscription status, payment count, next-payment date, and email all succeeded, but subscription `12564` still has `_pending_renewal_order_id=20230`. A live all-SLT query found it was the only SLT subscription retaining that meta.

## Concrete proof

- Live read at `2026-08-14 00:38:48 UTC`: subscription `12564` is `arraysubs-active`, `_completed_payments=4`, `_last_payment_date=2026-08-13 20:50:13`, `_next_payment_date=2026-08-16 18:00:00`, and `_pending_renewal_order_id=20230`.
- Parent order `12563` has the singleton relationship `a:1:{i:0;i:12564;}`; subscription `12564` points back to parent `12563`, customer `350`, and product `12125`.
- Renewal order `20230` is `wc-completed`, USD `18.00`, tax `0`, Stripe test, customer `350`; its renewal metas are `_is_renewal_order=yes`, `_subscription_id=_subscription_renewal=12564`, `_renewal_cycle_number=4`, and `_renewal_scheduled_date=2026-08-13 18:00:00`. Item `1771` is product `12125`, quantity `1`, line total `18`, line tax `0`.
- The subscription UI displays `Active`, next payment `17 August, 2026 12:00 AM (UTC+6)`, last payment `14 August, 2026 2:50 AM (UTC+6)`, recurring payment `$18.00 Every 3 days`, and completed payments `4`; its order history displays `#20230`, `completed`, `$18.00`, `Renewal`. The order UI displays the same completed USD `18.00` quantity-one line.
- Actions: invoice `16968` completed via WP-Cron; duplicate-safe respread invoice `19945` completed without a second order; original charge `16969` was cancelled by respread; replacement charge `19946` completed via WP-Cron at `2026-08-13 20:49:23 UTC`. Future invoice/charge actions `21883` and `21884` remain pending for 16 August.
- Mailpit `78wHjHjBvklh23XEhi1QsY`: `[mirror-help.arrayhash.com]: You've got a new order: #20230`, to `admin@mirror-help.arrayhash.com`.
- Mailpit `3mZ5qQRnlkSgC7mWrZDU2Y`: `[mirror-help.arrayhash.com] Payment received for subscription #12564`, to `slt-flex@example.test`; body records USD `18.00`, Stripe, and next payment 17 August 00:00 UTC+6.
- Browser screenshots:
  - `/home/server-manager/slt-evidence/SLT-EML-14-D12-01-sub-12564-list.png`
  - `/home/server-manager/slt-evidence/SLT-EML-14-D12-02-sub-12564-details.png`
  - `/home/server-manager/slt-evidence/SLT-EML-14-D12-03-order-20230.png`
  - `/home/server-manager/slt-evidence/SLT-EML-14-D12-10-actions-12564-pending.png`
- Browser page-error collection was empty; console output contained routine logs only, and no failed network response relevant to this finding was observed.
- Consolidated read-only transcript: `/home/server-manager/slt-evidence/D12-2026-08-14-early-morning-reconciliation.txt`.

## Scope notes and counterexamples

- The D12 money path itself passed: one renewal order, correct total/item/linkage, natural WP-Cron completion, active status, payment count `3 -> 4`, exact three-day schedule advance, and exact admin/customer mail.
- The stale pointer is a post-payment cleanup defect and may affect idempotency or the next invoice cycle; the next-cycle impact has not yet been observed, so severity is medium rather than high.
- Recent paid Stripe renewal controls `12172` (order `13788`) and `13277` (order `13576`) have absent pending-order pointers, showing this is not the normal settled state.
- The global respread replaced action IDs while preserving their authored timestamps; it did not create a duplicate cycle-4 order.
- The unrelated `SUB_W1=12039` missing D12 charge is governed by the pre-existing QA-plan timing issue `issues/medium-SLT-SYN-09-d12-watch-expects-sub-w1-one-day-early.md`, not this product finding.

## Resolution

Resolved on `2026-08-14` by core commit `69e0fe8` and pro commit `09ad013`.

### Confirmed root cause

This was a write-after-delete race, not a missed cleanup or renewal-respread defect. Stripe's verified provider-completion service paid a freshly loaded WooCommerce order while the scheduled renewal call retained its original pending `WC_Order`. Paid-order processing correctly advanced the subscription and deleted the pointer. The pro and core result normalizers then read the stale caller object as pending, and `RenewalProcessor::handleManualPaymentPending()` wrote order `20230` back into `_pending_renewal_order_id` before the action returned.

The database chronology corroborated that sequence: the new pointer meta row was inserted after the paid lifecycle/email notes and immediately before the Process Renewal success note. Order `20230` was already `completed`, `_subscription_payment_processed=yes`, and the sole cycle-4 order, excluding an incomplete lifecycle or duplicate invoice as explanations.

### Fix

- Core reloads the exact order through the WooCommerce data store before normalizing automatic payment results. Persisted paid state wins over a stale pending object, while a gateway string that claims `paid` for an unpaid order is downgraded to `pending`.
- The final pending-pointer writer reloads and validates the exact renewal, subscription relationship, and customer ownership. It cannot resurrect a paid-and-processed order or overwrite a newer pending invoice, while genuine pending, on-hold, failed, requires-action, and paid-but-unprocessed replay states remain linked.
- Successful paid-order cleanup uses an exact-value delete, so delayed completion of an older order cannot erase a newer order's pointer.
- Pro reloads the provider-completed order before returning its canonical paid/pending result and prevents gateway payload fields from overriding the verified status, message, or order ID.

### Regression and security proof

The disposable pre-fix run reproduced all three defects: separately loaded payment left its paid/processed order pointer present, an unpaid order's forged `status=paid` was accepted, and an older paid order erased a newer pending pointer. The same four-case run after the fix proved:

- separately loaded paid order: `completed`, processed `yes`, payments `1 -> 2`, pointer absent;
- forged paid result with persisted pending order: normalized to `pending`, pointer retained, payments unchanged;
- genuine asynchronous pending result: pending order and pointer retained, payments unchanged;
- older paid order plus newer unpaid order: newer pointer retained exactly.

The premium normalizer was also probed directly with order `20230` represented by a deliberately stale pending object; it returned canonical `paid`. A forged paid claim against persisted-pending control order `26323` returned canonical `pending` with the awaiting-confirmation message.

Evidence:

- `/home/server-manager/slt-evidence/SLT-SYN-04-pre-fix-regression-expanded.txt`
- `/home/server-manager/slt-evidence/SLT-SYN-04-post-fix-regression.txt`
- `/home/server-manager/slt-evidence/SLT-SYN-04-pro-normalizer.txt`

Every disposable subscription, order, note, and scheduled action was absent after cleanup. Mailpit stayed at `3fSVVzMmDFtiJU2RGRmFWU`, and the ArraySubs settings hash stayed `7f1af9d9c8ee89b3f4dcba0d49eda75b59d8efe426f9fea0977add9f4aa8052a` throughout.

### Live repair and browser verification

Under the canonical subscription-mutation lock, the live repair revalidated that pointer `12564 -> 20230` still matched the exact paid, processed, customer-bound renewal in subscription history and that no unpaid renewal existed. It then compare-and-deleted only that value. Payments remained `4`, next payment remained `2026-08-16 18:00:00 UTC`, order `20230` remained completed/processed, and a site-wide scan found zero pointers to paid orders; four unrelated unpaid/cancelled pointers were left untouched.

The authenticated admin UI then showed subscription `12564` Active with four completed payments, the unchanged next/last-payment dates, and order `20230` as a completed USD `18.00` renewal. The WooCommerce order page showed Completed, customer `350`, subscription `12564`, product `12125`, quantity one, and USD `18.00`. Browser page errors were empty, console output was routine JQMIGRATE/list logging only, and session `admin-SLT-SYN-04-FIX` was closed.

Evidence:

- `/home/server-manager/slt-evidence/SLT-SYN-04-data-repair.txt`
- `/home/server-manager/slt-evidence/SLT-SYN-04-fix-subscription-12564.png`
- `/home/server-manager/slt-evidence/SLT-SYN-04-fix-order-20230.png`
