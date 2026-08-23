---
id: 14
title: 'Segment 1 full: prove the full recurring charge now and the exact next-cycle boundary date'
status: blocked
priority: critical
created: 2026-08-22T19:10:00+02:00
updated: 2026-08-22T22:35:28.271485616+02:00
tags:
    - cycle-2
    - granular
    - renewal-sync
    - day-00
due: "2026-08-23"
estimate: 1.5h
depends_on:
    - 10
    - 11
    - 12
    - 8
    - 131
class: standard
---

## Current execution blocker — 2026-08-23 site date

Blocked by critical shared issue `qa/issues` #1 / preflight task `131`. Flex product `31363`, customer `477`, live week boundary, D0 `$14.00` cart proof and registry table are ready; the Stripe purchase itself was not attempted. Retry only after task 131 passes, preserving the real D0 segment-1 timing and exact provider/action baselines.

> **SLT-SYN-05** · group `sync` · scheduled **D00** (2026-08-23)

> Read `README.md` (environment + isolation contract), `calendar.md` (this day's exact
> ordering — it is binding, not advisory) and `plan-audit.md` before starting.

## Objective
Prove segment-1 `full` mode on a live Stripe checkout: the first charge is the FULL recurring amount (ratio 1.0, gateway minimum forced 0.0, `Hooks.php:389-392`), the boundary is not rewritten, and `_next_payment_date` is the upcoming week boundary — SLT-SYN-07's anchor.

## Scope
- Gateway: Stripe test
- Checkout: block
- Account: existing (`slt2-flex`); verifies the already-created `slt2-flex2`/`slt2-flex3` handoff
- Plugins: pro-required

## Preconditions
- SLT-SETUP-01/02/03 + SLT-PROD-13 (`SLT2 Flex Week Segments`, week/1 $14.00, seg1_end=2, seg2_end=5, all active) done.
- SLT-SYN-01A's D0 week-only pass is complete with its canonical restore diff and registry purchase-authorisation handoff. The parent SLT-SYN-01 card remains open for its unrelated D1 month/daily Pass B. No further week-product meta surgery is permitted. Global `sync_to_billing_cycle` is OFF — the product syncs only via `filterSupportsRenewalSync()`. Never run in SLT-SYN-04's bracket.
- SLT-SETUP-03 already created `slt2-flex2` + `slt2-flex3` (`slt2-flexN@example.test`, `customer`, pw `SltQa!2026#Pass`, complete billing profile). Do not create them again. A rebuy would create an ambiguous duplicate at the frozen `one_per_customer=false` baseline. Binding: seg1=`slt2-flex`, seg2=`slt2-flex2`, seg3=`slt2-flex3`.

## Test data
| Item | Value |
|---|---|
| Product / buyer / card | `SLT2 Flex Week Segments` $14.00 week/1 / `slt2-flex` / `4242 4242 4242 4242` |
| Buy on | D0 2026-08-23, after SLT-SYN-01 closes |
| Cycle start | Sat 2026-08-22 00:00 +06 = `2026-08-21 18:00:00` UTC (`start_of_week`=6) |
| Day in cycle | 2 → boundaries [2,5] → **segment 1** → `full` |
| Charge / next payment | **$14.00** / 2026-08-29 00:00 +06 = **`2026-08-28 18:00:00`** UTC |

## Steps
1. `MAILID_A=$(mailpit-agent latest-id)`. Verify existing users `slt2-flex2` and `slt2-flex3` have role `customer`, complete billing profiles, and no owned subscription yet. This verification must not emit task-attributable mail; inspect the complete delta after `MAILID_A` and classify unrelated shared-site messages separately instead of requiring the global latest ID to remain equal.
2. From WP root dump `_arraysubs_flex_sync_enabled`, `_arraysubs_flex_sync_seg1_end`, `_arraysubs_flex_sync_seg2_end`, `_arraysubs_flex_sync_seg1_active`, `_arraysubs_flex_sync_seg2_active`, `_arraysubs_flex_sync_seg3_active`, and `_regular_price` for `<WEEK_ID>` to `/home/server-manager/slt-evidence/SLT-SYN-05-plan.csv`; abort unless `yes,2,5,yes,yes,yes,14.00`.
3. `agent-browser --session slt05cust-SLT-SYN-05 open "https://mirror-help.arrayhash.com/my-account/"` → `snapshot -i` → log in as `slt2-flex`; immediately open `/cart/` and STOP and write a QA issue card under `qa/issues/` unless it is empty. `MAILID_B=$(mailpit-agent latest-id)`.
4. Open the product, add it to the empty cart, then open `/checkout/` → `snapshot -i`. Screenshot `SLT-SYN-05-01-summary.png`; record the verbatim total-due-today; confirm NO "Today's payment covers the full billing cycle starting …" note (seg 3 only).
5. Confirm **Paddle** absent / **Stripe** present; click the Stripe radio explicitly, then re-read totals (gateway change re-prices). Screenshot `SLT-SYN-05-02-gateways.png`.
6. Pay; screenshot `SLT-SYN-05-03-received.png`; record `ORDER_ID`; `mailpit-agent wait-new "$MAILID_B" 120 "is active"`, then list and map all four expected checkout messages.
7. Get `SUBID` from the order-received page. In isolated session `admin-SLT-SYN-05`, open ArraySubs → Subscriptions (`admin.php?page=arraysubs-mainadmin#/subscriptions`), search the ID, and click **View Details**; screenshot `SLT-SYN-05-04-schedule.png`. Dump the five `_renewal_sync_*` keys + `_next_payment_date` + `_completed_payments` to `/home/server-manager/slt-evidence/SLT-SYN-05-sub-meta.csv`; open the HPOS order edit route and screenshot the order item mirror as `SLT-SYN-05-05-item-meta.png`.
8. Compute `k` (README crc32 one-liner); in `admin-SLT-SYN-05`, screenshot `tools.php?page=action-scheduler&status=pending&s=<SUBID>` as `SLT-SYN-05-06-pending.png`.

## Expected results
1. `_renewal_sync_enabled=yes`, `_renewal_sync_first_charge_mode=full`, `_renewal_sync_cycle_start_date=2026-08-21 18:00:00` (NOT rewritten); `_renewal_sync_initial_recurring_amount=14`; order total exactly `$14.00`, no tax line.
2. `_renewal_sync_first_full_renewal_date = _next_payment_date = 2026-08-28 18:00:00`.
3. `_completed_payments=1`; sub `arraysubs-active`; virtual order `completed`; no bonus note; Paddle absent.
4. Pending: `arraysubs_generate_renewal_invoice[<SUBID>]` at `2026-08-28 18:00:00 +k −6h`; `arraysubs_process_renewal[<SUBID>]` at `+k`; `arraysubs_send_renewal_reminder[<SUBID>,3]` at `2026-08-25 18:00:00 +k` — assert windows, not points.

## Emails expected
| # | Email | Trigger point | Recipient | Subject contains | Verify with |
|---|---|---|---|---|---|
| 1 | NONE EXPECTED | existing-user/profile verification | — | — | Complete delta after `MAILID_A`; zero task-attributable mail, while unrelated/background mail is allowed and classified |
| 2 | WC completed order | paid virtual checkout | slt2-flex@example.test | `is on its way` | Complete owner-filtered delta after `MAILID_B`; save/show the exact matching id |
| 3 | WC new order | paid checkout | admin | `New order #<ORDER_ID>` | Complete owner-filtered delta after `MAILID_B`; save/show the exact matching id |
| 4 | `new_subscription` | order paid | slt2-flex@example.test | `is active` | `mailpit-agent wait-new "$MAILID_B" 120` |
| 5 | `admin_new_subscription` | order paid | admin_email | `New subscription #` | Complete owner-filtered delta after `MAILID_B`; save/show the exact matching id |
| 6 | `renewal_invoice` NONE EXPECTED | order paid | — | — | suppressed for auto-pay subs (REF-04 §4); arrival = finding |

## Evidence to capture
- `SLT-SYN-05-01..06`; `ORDER_ID`, `SUBID`, `k`, user IDs; both CSVs; Mailpit ids; console errors.

## Pass criteria
- [ ] Charge exactly $14.00, mode `full`, cycle start `2026-08-21 18:00:00`
- [ ] `_next_payment_date` = `2026-08-28 18:00:00` = `_renewal_sync_first_full_renewal_date`
- [ ] No seg-3 note; Paddle hidden, Stripe offered
- [ ] Three scheduled actions at due+k; all four checkout mails arrived; no `renewal_invoice`
- [ ] Existing `slt2-flex2`/`slt2-flex3` verified with zero mail and zero owned subscriptions

## Isolation / teardown
- Handed on: `SUBID` renews for real 2026-08-29 (SLT-SYN-09 owns it); `2026-08-29 00:00 +06` is SLT-SYN-07's anchor; `slt2-flex2`/`slt2-flex3` go to SLT-SYN-06/07/08. `slt2-flex` never rebuys it.
- Restores: none. Close only `slt05cust-SLT-SYN-05` and `admin-SLT-SYN-05`; `SLT-SETUP-99B` deletes users, order, and subscription on 2026-09-05.

---

### Fresh-cycle validation contract

- Re-derive every ID, count, option value, gateway capability, scheduler timestamp, and email baseline on this run; no prior-cycle result is evidence.
- Create and mutate only registered `SLT2 ` / `slt2-*` fixtures. Legacy `SLT` and all non-SLT2 data are read-only controls.
- Automatic-gateway scope is Stripe and Paddle only. Stripe is the primary path; run Paddle parity wherever Paddle supports the behavior. Do not test or configure PayPal or Mollie.
- ArraySubs core must own its Stripe/Paddle integration, renewal, retry, webhook, REST, refund, and customer-payment services with Pro inactive; vendor host classes retain their expected ownership.
- Browser-required assertions use Vercel `agent-browser` with isolated task/role sessions and current snapshot refs. WP-CLI always includes `--allow-root`.
- Update the lifecycle card, the matching `qa/progress/` card, and `qa/issues/` for every new regression. Evidence belongs to this fresh cycle only.
