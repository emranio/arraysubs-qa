# SLT2 plan audit

Audit date: 2026-08-22

## Result

The active plan contains **133 granular cards**, not day-level umbrella placeholders.

- 118 corrected historical scenario definitions retained and reset.
- 15 explicit additions: core-only ownership, D12 watch, seven retention cards, Stripe/Paddle product matrices, cross-gateway integrity, preflight, independent layer audit and final parity matrix.
- Prior execution notes/results, numeric fixture/action/order IDs, issue assumptions and “unverified but done” branches removed.
- All cards reset to `todo`; no start/completion timestamps or old evidence count as current proof.
- Prior shared issue board cleared; new regressions go to the mandatory `qa/issues/kanban/` board.

## Board inventory

| Metric | Value |
|---|---:|
| Cards | 133 |
| ID range | 1–133, no gaps/duplicates |
| Critical | 61 |
| High | 63 |
| Medium | 9 |
| Todo at reset | 133 |
| Missing dependencies | 0 |
| Dependency cycles | 0 |

## Schedule audit

- D0–D11: 2026-08-23 through 2026-09-03, exactly 12 calendar days.
- D12: 2026-09-04, read-only tail.
- D13: 2026-09-05, guarded allowlisted teardown.
- Monthly flexible-sync boundaries were recalculated for this start date: 1–24 / 25–27 / 28–month-end, with D1 full, D2 prorate and D6 next-cycle probes.
- Weekly calculations use the fresh D0 week-start setting; when Saturday, the active week is Aug 22–29.
- Future renewal/provider/mail baselines are derived from live IDs/timestamps, never copied authored action IDs.

## Gateway audit

| Track | Required coverage | Priority |
|---|---|---|
| Stripe | Checkout, saved/new method, SCA, decline/retry/recovery, natural renewals, sync, coupons, switches, retention, update, replay, refund/cancel, core-only | Primary/critical |
| Paddle | Hosted checkout, catalog/webhook, remote renewal, product/cart parity, method update, remote price switch, replay, refund/cancel and capability negatives | Required supported parity |
| PayPal | No execution/configuration; secrets unavailable | Deferred by user |
| Mollie | No execution/configuration; secrets unavailable | Deferred by user |
| Manual invoice | Internal engine/invoice/pay-link control only | Not an automatic-gateway track |

Task 117 proves ArraySubs core owns Stripe/Paddle behavior with Pro inactive. Tasks 128/129 execute the product/cart gateway matrices. Task 130 checks cross-gateway integrity. Task 132 independently reconciles browser, HPOS, meta, scheduler, provider and Mailpit. Task 133 refuses final PASS while any matrix cell is missing.

## Product/cart coverage audit

| Area | Atomic owners |
|---|---|
| Simple + block/classic | 1, 2, 5, 9, 18, 128, 129 |
| Variable/variation | 40, 44, 46, 71, 79, 97, 128, 129 |
| Subscription Box/bundle in free | 59, 65, 78, 128, 129 |
| Grouped children | 39, 77, 80, 128, 129 |
| Subscription + regular mixed | 51, 78, 90, 128, 129 |
| Trial/free/signup/lifetime/finite/renewal-price | 3, 4, 6, 7, 19, 20, 31, 34, 37, 38, 58 |
| Quantity/multiple subscriptions | 28, 50, 62, 64, 69, 77 |
| Flexible renewal sync | 8, 13, 14, 21, 22, 27, 28, 35, 40, 44–46, 61–63, 74, 75, 88, 99, 106, 112 |

## Lifecycle/money coverage audit

- Natural invoice/charge, renewal order linkage, multiple renewals and grid/no-drift: 9, 24, 41, 42, 47–49, 53, 57, 63, 67, 69, 106.
- SCA, decline, retry cap, grace, on-hold, recovery and idempotency: 30, 33, 43, 66, 81–83, 85, 101–103.
- Early/late/skip/pause/cancel/expiry/reactivation: 4, 54, 70, 84, 89, 93, 98, 107–111, 123.
- Coupons, discount duration, fees, proration, quantity, switch fee and refund: 16, 17, 19, 25, 32, 45, 50, 51, 58, 62, 73, 86, 87, 105, 114, 122.
- Upgrade/downgrade/crossgrade/variable/customer/admin/Paddle price: 60, 72, 73, 86, 87, 95–97, 104, 105, 111, 124.

## Retention audit

The plan no longer treats retention as one card:

- 121 reasons/defaults/custom/Other/required validation.
- 122 discount eligibility, invalid bounds, exact 3-cycle accounting, no reuse and interaction cases.
- 123 pause eligibility, billing/access suppression, manual/automatic resume, limits/cooldown.
- 124 downgrade target/no-target/incompatible state, switch/proration and Stripe/Paddle renewal.
- 125 contact-support URL/new-tab/security/logging/no-mutation.
- 126 all reason/product/status/history/dismiss/decline/accept/card-order rows.
- 127 eight KPIs, charts, activity, filters/date boundaries/export/source reconciliation.
- 73 and 98 retain their natural multi-cycle and cancel/reactivate end-to-end paths.

## Safety and verdict audit

- Real end-to-end browser proof is mandatory; API/code reads only support it.
- All WP-CLI calls require `--allow-root`; all browser work uses `agent-browser` and isolated sessions.
- Old `SLT` and non-SLT2 records are read-only; teardown targets exact registered IDs only.
- Missing source/config or any failed mandatory assertion creates/updates a shared QA issue and leaves the lifecycle card blocked until rerun passes.
- D11 restores but deletes nothing; D12 is read-only; D13 refuses cleanup if any card is not done.

The catalog lists all 133 cards; the calendar gives exact daily order; the watch schedule governs natural/future checks.
