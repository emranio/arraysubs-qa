# D12 watch mail oracle omits the normal renewal admin New-order message

- Severity: low
- Status: resolved 2026-08-14
- Date found: 2026-08-14
- Watch day: D12
- Originating test task: `D12-WATCH` (source fixture created by `SLT-SYN-04`)
- Plan file: `/home/server-manager/www/arrayhash/mirror-help.arrayhash.com/public/wp-content/plugins/qa/subscription-lifecycle-test/watch-schedule.md`

## Affected records

- Subscription: `12564`, `SLT Sync Global Daily`
- Parent order: `12563`
- Renewal order: `20230`, cycle `4`
- Product: `12125`, `SLT Sync Global Daily`
- WP user: `350`, login `slt-flex`, email `slt-flex@example.test`, role `customer`
- Gateway: Stripe test
- Checkout type: classic checkout for the original subscription purchase
- Settings in play: store global sync restored to its pre-window value `true` / first-charge mode `full`; subscription-level sync remains enabled / `full` as authored.

## Routes and browser context

- D12 oracle: `watch-schedule.md:117`
- Subscription detail: `https://mirror-help.arrayhash.com/wp-admin/admin.php?page=arraysubs-mainadmin#/subscriptions/detail/12564`
- Renewal order: `https://mirror-help.arrayhash.com/wp-admin/admin.php?page=wc-orders&action=edit&id=20230`
- Browser context: authenticated admin session `admin-SLT-EML-14-D12`

## Reproduction steps

1. Read the D12 email cell in `watch-schedule.md`; it requires `payment_successful` for each numeric live-tail subscription that fires “and nothing else.”
2. Resolve the only live-tail subscription that actually fires in the D12 midnight window from parent order `12563` to subscription `12564`, then cross-check customer `350` and product `12125`.
3. From D11 `CHARGE_PRE=0dzEI93xcr54ooH9jm0rho`, let replacement charge action `19946` run naturally through WP-Cron. Do not force it.
4. Inspect the complete Mailpit delta and filter it by exact subscription `12564`, renewal order `20230`, and recipient.
5. Observe both the ArraySubs customer payment-success message and the standard WooCommerce admin New-order message for the same settled renewal.

## Expected result

The D12 watch row either lists both established messages — customer `payment_successful` and admin WooCommerce New order — or explicitly limits “nothing else” to ArraySubs lifecycle-email classes. This lets a strict watcher distinguish expected WooCommerce side effects from unexpected lifecycle mail.

## Actual result

The row says `payment_successful` “and nothing else.” The natural renewal correctly emitted the customer payment-success message plus an attributable WooCommerce admin New-order message, so the literal authored oracle cannot pass even though the observed two-message set matches the suite's established renewal-mail contract.

## Concrete proof

- `watch-schedule.md:117` contains the conflicting D12 wording.
- Mailpit `3mZ5qQRnlkSgC7mWrZDU2Y`: `[mirror-help.arrayhash.com] Payment received for subscription #12564`, to `slt-flex@example.test`, USD `18.00`.
- Mailpit `78wHjHjBvklh23XEhi1QsY`: `[mirror-help.arrayhash.com]: You've got a new order: #20230`, to `admin@mirror-help.arrayhash.com`, one `SLT Sync Global Daily` line at USD `18.00`.
- Order `20230` is relationship-linked to subscription `12564`, so the admin message is attributable to this renewal rather than concurrent card-126 activity.
- The complete D11-baseline-exclusive delta through the phase cutoff has 258 messages; exactly these two belong to the authored renewal and all other 256 were owner-correlated concurrent traffic.
- `kanban/tasks/057-reconcile-the-full-mailpit-set-for-one-slt-daily.md` codifies admin New order plus customer ArraySubs payment-success, with zero customer Woo status mail, as the normal paid-renewal set.
- Browser screenshots `/home/server-manager/slt-evidence/SLT-EML-14-D12-02-sub-12564-details.png` and `/home/server-manager/slt-evidence/SLT-EML-14-D12-03-order-20230.png` show the paid renewal relationship.
- Consolidated transcript: `/home/server-manager/slt-evidence/D12-2026-08-14-early-morning-reconciliation.txt`.

## Scope notes and counterexamples

- This is a QA-plan oracle defect, not a product runtime defect. No unexpected customer Woo processing/completed mail appeared.
- Prior lifecycle tasks and reports consistently treat the admin New-order message as an expected renewal side effect; the contradiction is isolated to the literal D12 shorthand.
- Concurrent card-126/shared-site mail was excluded by exact IDs, recipients, and record relationships.
- The separate D12 date defect for `SUB_W1=12039` remains filed at `issues/medium-SLT-SYN-09-d12-watch-expects-sub-w1-one-day-early.md`.

## Resolution

The report was confirmed as a QA-oracle defect, not a product-runtime failure. Committed ArraySubs history intentionally suppresses only WooCommerce customer order-status mail for renewal orders; task `57` independently codifies the normal paid-renewal pair as one admin WooCommerce New-order message plus one customer ArraySubs `payment_successful` message. The later D12 shorthand collapsed that pair to `payment_successful` “and nothing else.”

The QA contract is now explicit and centralized:

- `watch-schedule.md` defines the successful automatic-renewal contract before the daily table, corrects D1 so the admin New-order message belongs to settlement rather than invoice creation, and makes the D12 row require the exact admin/customer pair.
- `reference/SLT-REF-04-complete-email-inventory-class-template-trigger-recipient-su.md` records WooCommerce's admin renewal companion separately from ArraySubs lifecycle classes.
- `watch-reports/D12-2026-08-14.md` marks the exact mail row `PASS` under the corrected oracle while preserving the report's overall `FAIL` for the separate stale `_pending_renewal_order_id` product defect.
- The D13 teardown handoff now requires a signed reconciliation of every applicable final gate carried forward by D12 before task `119` may cancel or delete the tail cohort.

The dependency review also found an uncommitted cross-feature regression in the pending critical fixes: new credit-only plan-switch filters had been routed through the renewal callback and would have suppressed the normal renewal admin New-order recipient. `EmailManager` now keeps the original five customer-renewal filters separate and routes the new admin/refund filters through a credit-only-only callback. `PlanManager::isProrationOrderCreditOnlySettlement()` accepts cancelled terminal authentication only when explicitly requested by that callback. The classifier still requires the exact signed order contract, HMAC, order layout, credit reservation or signed release, zero-payment invariants, and terminal state; raw settlement metadata cannot suppress mail.

## Fix verification

- Live HPOS and WordPress reads reconfirmed subscription `12564`, customer `350`, product `12125`, completed cycle-4 renewal `20230`, exact bidirectional relationship metadata, USD `18.00`, Stripe, and the natural action chain. The separate stale pending-order pointer remains untouched.
- Retained Mailpit evidence contains exactly two messages for this settlement: admin New order `78wHjHjBvklh23XEhi1QsY` and customer payment success `3mZ5qQRnlkSgC7mWrZDU2Y`. Exact-subject and event-window searches found no third attributable message.
- A live recipient-filter matrix against renewal `20230` preserved `new_order`, `cancelled_order`, `failed_order`, and `customer_refunded_order`; suppressed the five established customer renewal messages; and preserved all nine recipients for ordinary order `12563` and unsigned legacy switch order `20124`.
- A disposable, officially signed credit-only fixture produced subscription `26289`, paid source order `26292`, and switch audit order `26294` with credit `20.00`, target charge `10.00`, net `0.00`, and refund credit `10.00`. All nine intended WooCommerce recipients were suppressed while pending/on-hold; the cancelled terminal contract remained authenticated and its admin recipient stayed suppressed. A raw-meta forgery preserved the sentinel recipient. Cleanup removed all three records and the disposable user, and Mailpit remained exactly at `3fSVVzMmDFtiJU2RGRmFWU` before and after.
- Browser session `admin-D12-WATCH-FIX` reloaded the real staging subscription and order. The subscription shows renewal `20230` completed for USD `18.00`; the order shows customer `350`, product `12125`, quantity `1`, total USD `18.00`, and linked subscription `12564`. Browser errors were empty; screenshots are `/home/server-manager/slt-evidence/D12-WATCH-fix-subscription-12564.png` and `/home/server-manager/slt-evidence/D12-WATCH-fix-final-order-20230.png`.
