# D12 watch expects SUB_W1 renewal 24 hours before its persisted weekly boundary

- Severity: medium
- Date found: 2026-08-12
- Watch day: D10
- Originating test task: `SLT-SYN-09` (source fixture from `SLT-SYN-05`)
- Plan file: `/home/server-manager/www/arrayhash/mirror-help.arrayhash.com/public/wp-content/plugins/qa/subscription-lifecycle-test/watch-schedule.md`

## Affected records

- Subscription: `12039` (`SUB_W1`)
- Parent order: `12029`
- Most recent renewal order: `13170` (cycle 2); future cycle-3 order: N/A, not generated yet
- Product: `11943`, `SLT Flex Week Segments`
- WP user: `350`, login `slt-flex`, email `slt-flex@example.test`, role `customer`
- Gateway: Stripe test
- Checkout: block / Store API, existing account
- Settings in play: product flexible renewal sync enabled; first charge `full`; cycle start `2026-07-31 18:00:00Z`; segment boundaries `2` and `5`; all segments active. Global sync was off at purchase. The persisted subscription schedule is not changed by the restored global baseline.

## Routes and contexts

- Admin subscription detail: `https://mirror-help.arrayhash.com/wp-admin/admin.php?page=arraysubs-mainadmin#/subscriptions/detail/12039`
- Scheduled Actions search: `https://mirror-help.arrayhash.com/wp-admin/tools.php?page=action-scheduler&status=pending&s=12039`
- Product: `https://mirror-help.arrayhash.com/product/slt-flex-week-segments/`
- Original customer session: `slt05cust-SLT-SYN-05`
- Evidence sessions: `admin-SLT-SYN-09-D7`, `admin-SLT-SYN-09-D10`

## Reproduction

1. Read the D12 row in `watch-schedule.md`; it expects `SUB_W1` renewal #2 during 2026-08-14 00:00–06:00 site time, using base due `2026-08-13 18:00:00Z + k`.
2. Resolve `SUB_W1` through its recorded parent-order relationship to subscription `12039`; do not select by recency.
3. Open subscription `12039` in the admin UI and observe `_next_payment_date=2026-08-14 18:00:00Z`.
4. Open Scheduled Actions filtered to `12039`. Observe pending invoice `15836` at `2026-08-14 17:01:52Z` and pending charge `15837` at `2026-08-14 23:01:52Z`, with zero attempts.
5. Convert the charge timestamp to UTC+6: `2026-08-15 05:01:52`, which is D13 and exactly 86400 seconds later than the D12 row's implied gate.
6. Compare the previous weekly cycle and the true D12 counterexample `12564` described below.

## Expected result

The watch plan follows the persisted weekly boundary: through D12 it checks `12039` is alive and scheduled, then observes the actual cycle-3 renewal on D13 before `SLT-SETUP-99B` teardown.

## Actual result

The D12 row expects the renewal one site-local day early. A compliant D12 watcher would falsely score the missing charge, while the actual final renewal could be missed because it occurs on D13 at `05:01:52` site time.

## Proof

- `watch-schedule.md:117` states the early D12 base date `2026-08-13 18:00:00Z`.
- `/home/server-manager/slt-evidence/SLT-SYN-09-D07-read.txt` and `/home/server-manager/slt-evidence/SLT-SYN-09-after.csv` record subscription `12039` with next due `2026-08-14 18:00:00Z` and charge action `15837` at `2026-08-14 23:01:52Z`.
- `/home/server-manager/slt-evidence/SLT-SYN-09-04-pending-SUB_W1-D10-recapture.png` shows the pending action in the D10 browser recapture.
- `kanban/tasks/106-renewal-execution-after-a-synced-first-charge.md` agrees with the live schedule.
- `/home/server-manager/slt-evidence/SLT-SYN-05-facts.txt` and screenshots `SLT-SYN-05-01-summary.png`, `SLT-SYN-05-03-received.png`, `SLT-SYN-05-04-schedule.png`, and `SLT-SYN-05-06-pending.png` prove the source schedule.
- The prior natural cycle completed with admin mail `4CoCtNzSHoxwAFLn38JuZ1` and customer mail `668D3zIwlFM3x6xEqZfmMs`.
- D10 reminder action `15838` ran at 2026-08-12 05:01:52 site and sent Mailpit message `5rjgeXk7T8tmfLQKPgb9Ct`, further corroborating a charge exactly three days later at 2026-08-15 05:01:52 site.

## Scope and counterexamples

- This is a QA-plan timing defect, not a product runtime defect. Subscription `12039` and its pending actions agree with one another and with the prior weekly cycle.
- `12564` (`SLT Sync Global Daily`) is a genuine D12-midnight event: next due `2026-08-13 18:00:00Z`, pending charge `16969` at `2026-08-13 20:49:23Z` / D12 `02:49:23` site. Evidence: `/home/server-manager/slt-evidence/SLT-IMP-05-pending-72h.txt` and the D10 facts snapshot.
- `12749` has the same next boundary as `12039`, with charge `15848` at `2026-08-14 23:19:30Z` / D13 `05:19:30` site. The D12 row correctly calls it outside the window, making its treatment of `12039` internally inconsistent.
- Conditional `SUB_W` does not exist, so only numeric `SUB_W1=12039` is affected.

## Resolution (2026-08-14)

Disposition: confirmed QA-plan timing defect. The product scheduler is correct; no ArraySubs or ArraySubs Pro runtime code was changed.

- Live revalidation at D13 `01:23` site still showed subscription `12039` active with `_next_payment_date=2026-08-14 18:00:00Z`, two completed payments, and a stable `k=18112` seconds (`05:01:52`). The admin UI rendered that stored boundary as `15 August, 2026 12:00 AM (UTC+6)`.
- The current respread action pair preserves the same schedule: invoice `21879` completed naturally at `2026-08-14 17:01:52Z`, charge `21880` remained pending and unattempted for `2026-08-14 23:01:52Z` / D13 `05:01:52` site, and relationship-linked cycle-3 order `26536` was pending for `$14.00`.
- Prior cycle order `13170` is completed for `$14.00` with `_renewal_scheduled_date=2026-08-07 18:00:00`; the seven-day advance to the persisted cycle-3 boundary confirms that runtime scheduling did not drift.
- `watch-schedule.md` now makes the persisted boundary authoritative, removes `SUB_W1`/conditional `SUB_W` from the D12 paid-renewal expectation, requires their late-D12 invoice and D13 `due+k` charge to be carried forward, and forbids scoring the D12 silence as a missing renewal.
- The corrected row retains Sync Global Daily as the true D12-midnight event. Existing `calendar.md` and task `119` D13 gates were cross-checked: teardown cannot start until a signed D13 report reconciles `21879/21880` (or a documented replacement) and all other final natural tails.
- Browser verification used the authenticated admin subscription detail and Scheduled Actions routes. Screenshots: `/home/server-manager/slt-evidence/FIX-MEDIUM-SLT-SYN-09-current-boundary.png` and `/home/server-manager/slt-evidence/FIX-MEDIUM-SLT-SYN-09-pending-actions.png`; browser error collection was empty.
- The verification was read-only. Subscription, order, scheduler, settings, and Mailpit state were not mutated.
