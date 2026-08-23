---
id: 45
title: 'Segment 2 prorate: prove the arithmetic to the cent on week and month cycles'
status: todo
priority: critical
created: 2026-08-22T19:10:00+02:00
updated: 2026-08-22T19:10:00+02:00
tags:
    - cycle-2
    - granular
    - renewal-sync
    - day-02
due: "2026-08-25"
estimate: 2h
depends_on:
    - 14
    - 21
    - 8
    - 13
class: standard
---

> **SLT-SYN-06** · group `sync` · scheduled **D02** (2026-08-25)

> Read `README.md` (environment + isolation contract), `calendar.md` (this day's exact
> ordering — it is binding, not advisory) and `plan-audit.md` before starting.
## Objective
Prove segment-2 `prorate` to the cent on two cycle lengths, including the deliberate `−1` in `remaining_days` (`renewal-sync-helpers.php:335-342`), and prove proration discounts only the FIRST charge, never the boundary.

## Scope
- Gateway: Stripe test
- Checkout: block
- Account: existing (`slt2-flex2`, created by SLT-SYN-05)
- Plugins: pro-required

## Preconditions
- SLT-SYN-05 done (accounts exist, week seg-1 anchor recorded); SLT-PROD-12 (`SLT2 Flex Month Segments`, month/1 $30.00, seg1_end=24, seg2_end=27) and SLT-PROD-13 done; SLT-SYN-01 done, restores proven.
- Global `sync_to_billing_cycle` OFF; never run in SLT-SYN-04's bracket. Two carts needed (`allow_multiple_in_cart=false`) — complete one purchase, then the other.
- Sessions `slt06cust-SLT-SYN-06` and `admin-SLT-SYN-06` are exclusive to this task.

## Test data
Buy both on **D2 = 2026-08-25** (site time), as `slt2-flex2`, card `4242 4242 4242 4242`.

| | `SLT2 Flex Week Segments` | `SLT2 Flex Month Segments` |
|---|---|---|
| Price / boundaries | $14.00 week/1 / [2,5] | $30.00 month/1 / [24,27] |
| cycle_start → next_payment (+06) | 08-22 → 08-29 | 08-01 → 09-01 |
| `cycle_days` / day_in_cycle | 7 / 4 → **seg 2** | **31** / 25 → **seg 2** |
| `days_until_next` → `remaining=max(1,d−1)` | 4→3 | 7→6 |
| ratio → raw → charge | 3/7 → 6.000000 → **$6.00** | 6/31 → 5.806452 → **$5.81** |
| `_next_payment_date` (UTC) | `2026-08-28 18:00:00` | `2026-08-31 18:00:00` |

All three dates floor to site-TZ midnight before the diff, so purchase clock time is irrelevant. The USD Stripe minimum is 50 minor units — both charges clear it, so the prorate bump (`Hooks.php:453-469`) must NOT fire.

## Steps
1. From WP root re-dump the six `_arraysubs_flex_sync_*` keys for both products to `/home/server-manager/slt-evidence/SLT-SYN-06-plans.csv`; abort unless week=`yes,2,5,yes,yes,yes`, month=`yes,24,27,yes,yes,yes`.
2. `agent-browser --session slt06cust-SLT-SYN-06 open "https://mirror-help.arrayhash.com/my-account/"` → `snapshot -i` → log in as `slt2-flex2`; open `/cart/`, prove it is EMPTY, and capture `SLT-SYN-06-00-cart-empty-before.png`; `MAILID_W=$(mailpit-agent latest-id)`.
3. `open ".../checkout/?add-to-cart=<WEEK_ID>"` → `snapshot -i`; screenshot `SLT-SYN-06-01-week-summary.png`; total due today must read **$6.00**; record any prorate wording verbatim; no bonus-cycle note.
4. Select Stripe explicitly, re-read the total (still $6.00), and pay. Record numeric `ORDER_W`; read `wp post meta get "$ORDER_W" _subscription_ids --format=json --allow-root`, resolve exactly one numeric `SUB_W` through a strict `jq -e` guard, and cross-check parent/customer/product plus the count delta; never use the WooCommerce order meta accessor or recency. `mailpit-agent wait-new "$MAILID_W" 120 "is active"`; classify the complete delta and require the exact WC completed-order, WC admin new-order, customer signup, and admin signup IDs. Reopen `/cart/`, prove it and the persistent-cart user meta are empty, and capture `SLT-SYN-06-01a-cart-empty-between.png` before adding the month product.
5. Set `MAILID_M=$(mailpit-agent latest-id)` only after the week delta is fully classified. `open ".../checkout/?add-to-cart=<MONTH_ID>"` → `snapshot -i`; screenshot `SLT-SYN-06-02-month-summary.png`; total must read **$5.81**. Select Stripe and pay; record numeric `ORDER_M`, resolve exact numeric `SUB_M` through the same post-meta JSON path and strict guard, and cross-check parent/customer/product plus the second count delta. `mailpit-agent wait-new "$MAILID_M" 120 "is active"`; require and save the second exact four-message checkout set. Reopen `/cart/`, prove it and the persistent-cart user meta are empty, and capture `SLT-SYN-06-02a-cart-empty-after.png`.
6. For both subs dump the five `_renewal_sync_*` keys + `_next_payment_date` + `_completed_payments` to `/home/server-manager/slt-evidence/SLT-SYN-06-sub-meta.csv`; in `admin-SLT-SYN-06`, open each exact parent order separately and screenshot its item mirror as `SLT-SYN-06-03-week-item-meta.png` / `-04-month-item-meta.png`.
7. Compute `k` for both subs (README crc32 one-liner). In `admin-SLT-SYN-06`, search Pending by `SUB_W`, re-snapshot, and capture `SLT-SYN-06-05a-week-pending.png`; repeat by `SUB_M` as `SLT-SYN-06-05b-month-pending.png`. Append both users/orders/subscriptions, exact action IDs/times, and future baseline deadlines to the registry and D02 watch report. Close only `slt06cust-SLT-SYN-06` and `admin-SLT-SYN-06`.

## Expected results
1. Week: mode `prorate`, `_renewal_sync_initial_recurring_amount=6`, order total `$6.00`, no tax line.
2. Month: mode `prorate`, `_renewal_sync_initial_recurring_amount=5.81`, order total `$5.81`.
3. Week `_renewal_sync_cycle_start_date=2026-08-21 18:00:00`; month = `2026-07-31 18:00:00`. Neither is rewritten (that is `next_cycle`-only).
4. Week `_next_payment_date = _renewal_sync_first_full_renewal_date = 2026-08-28 18:00:00` — **identical to SLT-SYN-05's**: prorate discounts the charge, not the boundary. Month = `2026-08-31 18:00:00` (2026-09-01 00:00 +06).
5. Both subs `arraysubs-active`, `_completed_payments=1`, orders `processing`/`completed`.
6. Pending per sub: invoice at `due +k −6h`, `arraysubs_process_renewal` at `+k`, reminder at `due −3d +k` — windows, not points.

## Emails expected
| # | Email | Trigger point | Recipient | Subject contains | Verify with |
|---|---|---|---|---|---|
| 1 | `new_subscription` ×2 | order paid | slt2-flex2@example.test | `is active` | `mailpit-agent wait-new "$MAILID_W" 120 "is active"` / `mailpit-agent wait-new "$MAILID_M" 120 "is active"` |
| 2 | `admin_new_subscription` ×2 | order paid | admin_email | `New subscription #` | same |
| 3 | WC New order ×2 | each paid checkout | admin_email | `New order #` | each complete owner-filtered delta; save/show exact ids |
| 4 | WC Completed order ×2 | each paid virtual checkout | slt2-flex2@example.test | `is on its way` | each complete owner-filtered delta; save/show exact ids |
| 5 | `renewal_invoice` NONE EXPECTED | either order paid | — | — | suppressed for auto-pay subs (REF-04 §4); arrival = finding |

## Evidence to capture
- `SLT-SYN-06-00..05b` including before/between/after empty-cart shots and distinct queue views; `ORDER_W/M`, `SUB_W/M`, both `k`; both CSVs; all eight checkout Mailpit ids; persistent-cart proof; console errors.

## Pass criteria
- [ ] Week charge exactly $6.00, month exactly $5.81, both mode `prorate`
- [ ] The Test-data arithmetic reproduces from the stored metas
- [ ] Week `_next_payment_date` = `2026-08-28 18:00:00`, identical to SLT-SYN-05's; month = `2026-08-31 18:00:00`
- [ ] Neither cycle-start rewritten; no gateway-minimum bump
- [ ] Both complete four-message checkout sets arrived; no `renewal_invoice`
- [ ] Same-session cart and persistent-cart meta empty before, between, and after the two purchases

## Isolation / teardown
- Handed on: `SUB_W` renews for real 2026-08-29 at the FULL $14.00 — SLT-SYN-09 owns that proof. `SUB_M` is due 2026-09-01, outside the window: it belongs to the sole authorized time-travel day (D8, 2026-08-31), never to a bare hook drain.
- `slt2-flex2` must not rebuy either product. Restores: none. Close only `slt06cust-SLT-SYN-06` and `admin-SLT-SYN-06`.

---

### Fresh-cycle validation contract

- Re-derive every ID, count, option value, gateway capability, scheduler timestamp, and email baseline on this run; no prior-cycle result is evidence.
- Create and mutate only registered `SLT2 ` / `slt2-*` fixtures. Legacy `SLT` and all non-SLT2 data are read-only controls.
- Automatic-gateway scope is Stripe and Paddle only. Stripe is the primary path; run Paddle parity wherever Paddle supports the behavior. Do not test or configure PayPal or Mollie.
- ArraySubs core must own its Stripe/Paddle integration, renewal, retry, webhook, REST, refund, and customer-payment services with Pro inactive; vendor host classes retain their expected ownership.
- Browser-required assertions use Vercel `agent-browser` with isolated task/role sessions and current snapshot refs. WP-CLI always includes `--allow-root`.
- Update the lifecycle card, the matching `qa/progress/` card, and `qa/issues/` for every new regression. Evidence belongs to this fresh cycle only.
