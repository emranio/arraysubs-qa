---
id: 45
title: 'Segment 2 prorate: prove the arithmetic to the cent on week and month cycles'
status: done
priority: critical
created: 2026-08-02T03:43:06.653472519+02:00
updated: 2026-08-05T21:37:49.534613845+02:00
started: 2026-08-05T21:05:25.164133639+02:00
completed: 2026-08-05T21:05:25.164133639+02:00
tags:
    - renewal-sync
    - day-02
due: "2026-08-04"
estimate: 2h
depends_on:
    - 14
    - 21
    - 8
    - 13
class: standard
---

> **SLT-SYN-06** · group `sync` · scheduled **D02** (2026-08-04)

> Read `README.md` (environment + isolation contract), `calendar.md` (this day's exact
> ordering — it is binding, not advisory) and `plan-audit.md` before starting.
## Objective
Prove segment-2 `prorate` to the cent on two cycle lengths, including the deliberate `−1` in `remaining_days` (`renewal-sync-helpers.php:335-342`), and prove proration discounts only the FIRST charge, never the boundary.

## Scope
- Gateway: Stripe test
- Checkout: block
- Account: existing (`slt-flex2`, created by SLT-SYN-05)
- Plugins: pro-required

## Preconditions
- SLT-SYN-05 done (accounts exist, seg-1 anchor recorded); SLT-PROD-12 (`SLT Flex Month Segments`, month/1 $30.00, seg1_end=2, seg2_end=6) and SLT-PROD-13 done; SLT-SYN-01 done, restores proven.
- Global `sync_to_billing_cycle` OFF; never run in SLT-SYN-04's bracket. Two carts needed (`allow_multiple_in_cart=false`) — complete one purchase, then the other.
- Sessions `slt06cust-SLT-SYN-06` and `admin-SLT-SYN-06` are exclusive to this task.

## Test data
Buy both on **D2 = 2026-08-04** (site time), as `slt-flex2`, card `4242 4242 4242 4242`.

| | `SLT Flex Week Segments` | `SLT Flex Month Segments` |
|---|---|---|
| Price / boundaries | $14.00 week/1 / [2,5] | $30.00 month/1 / [2,6] |
| cycle_start → next_payment (+06) | 08-01 → 08-08 | 08-01 → 09-01 |
| `cycle_days` / day_in_cycle | 7 / 4 → **seg 2** | **31** / 4 → **seg 2** |
| `days_until_next` → `remaining=max(1,d−1)` | 4→3 | 28→27 |
| ratio → raw → charge | 3/7 → 6.000000 → **$6.00** | 27/31 → 26.129032 → **$26.13** |
| `_next_payment_date` (UTC) | `2026-08-07 18:00:00` | `2026-08-31 18:00:00` |

All three dates floor to site-TZ midnight before the diff, so purchase clock time is irrelevant. The USD Stripe minimum is 50 minor units — both charges clear it, so the prorate bump (`Hooks.php:453-469`) must NOT fire.

## Steps
1. From WP root re-dump the six `_arraysubs_flex_sync_*` keys for both products to `/home/server-manager/slt-evidence/SLT-SYN-06-plans.csv`; abort unless week=`yes,2,5,yes,yes,yes`, month=`yes,2,6,yes,yes,yes`.
2. `agent-browser --session slt06cust-SLT-SYN-06 open "https://mirror-help.arrayhash.com/my-account/"` → `snapshot -i` → log in as `slt-flex2`; open `/cart/`, prove it is EMPTY, and capture `SLT-SYN-06-00-cart-empty-before.png`; `MAILID_W=$(mailpit-agent latest-id)`.
3. `open ".../checkout/?add-to-cart=<WEEK_ID>"` → `snapshot -i`; screenshot `SLT-SYN-06-01-week-summary.png`; total due today must read **$6.00**; record any prorate wording verbatim; no bonus-cycle note.
4. Select Stripe explicitly, re-read the total (still $6.00), and pay. Record numeric `ORDER_W`; read `wp post meta get "$ORDER_W" _subscription_ids --format=json --allow-root`, resolve exactly one numeric `SUB_W` through a strict `jq -e` guard, and cross-check parent/customer/product plus the count delta; never use the WooCommerce order meta accessor or recency. `mailpit-agent wait-new "$MAILID_W" 120 "is active"`; classify the complete delta and require the exact WC completed-order, WC admin new-order, customer signup, and admin signup IDs. Reopen `/cart/`, prove it and the persistent-cart user meta are empty, and capture `SLT-SYN-06-01a-cart-empty-between.png` before adding the month product.
5. Set `MAILID_M=$(mailpit-agent latest-id)` only after the week delta is fully classified. `open ".../checkout/?add-to-cart=<MONTH_ID>"` → `snapshot -i`; screenshot `SLT-SYN-06-02-month-summary.png`; total must read **$26.13**. Select Stripe and pay; record numeric `ORDER_M`, resolve exact numeric `SUB_M` through the same post-meta JSON path and strict guard, and cross-check parent/customer/product plus the second count delta. `mailpit-agent wait-new "$MAILID_M" 120 "is active"`; require and save the second exact four-message checkout set. Reopen `/cart/`, prove it and the persistent-cart user meta are empty, and capture `SLT-SYN-06-02a-cart-empty-after.png`.
6. For both subs dump the five `_renewal_sync_*` keys + `_next_payment_date` + `_completed_payments` to `/home/server-manager/slt-evidence/SLT-SYN-06-sub-meta.csv`; in `admin-SLT-SYN-06`, open each exact parent order separately and screenshot its item mirror as `SLT-SYN-06-03-week-item-meta.png` / `-04-month-item-meta.png`.
7. Compute `k` for both subs (README crc32 one-liner). In `admin-SLT-SYN-06`, search Pending by `SUB_W`, re-snapshot, and capture `SLT-SYN-06-05a-week-pending.png`; repeat by `SUB_M` as `SLT-SYN-06-05b-month-pending.png`. Append both users/orders/subscriptions, exact action IDs/times, and future baseline deadlines to the registry and D02 watch report. Close only `slt06cust-SLT-SYN-06` and `admin-SLT-SYN-06`.

## Expected results
1. Week: mode `prorate`, `_renewal_sync_initial_recurring_amount=6`, order total `$6.00`, no tax line.
2. Month: mode `prorate`, `_renewal_sync_initial_recurring_amount=26.13`, order total `$26.13`.
3. Both `_renewal_sync_cycle_start_date=2026-07-31 18:00:00` — neither rewritten (that is `next_cycle`-only).
4. Week `_next_payment_date = _renewal_sync_first_full_renewal_date = 2026-08-07 18:00:00` — **identical to SLT-SYN-05's**: prorate discounts the charge, not the boundary. Month = `2026-08-31 18:00:00` (2026-09-01 00:00 +06).
5. Both subs `arraysubs-active`, `_completed_payments=1`, orders `processing`/`completed`.
6. Pending per sub: invoice at `due +k −6h`, `arraysubs_process_renewal` at `+k`, reminder at `due −3d +k` — windows, not points.

## Emails expected
| # | Email | Trigger point | Recipient | Subject contains | Verify with |
|---|---|---|---|---|---|
| 1 | `new_subscription` ×2 | order paid | slt-flex2@example.test | `is active` | `mailpit-agent wait-new "$MAILID_W" 120 "is active"` / `mailpit-agent wait-new "$MAILID_M" 120 "is active"` |
| 2 | `admin_new_subscription` ×2 | order paid | admin_email | `New subscription #` | same |
| 3 | WC New order ×2 | each paid checkout | admin_email | `New order #` | each complete owner-filtered delta; save/show exact ids |
| 4 | WC Completed order ×2 | each paid virtual checkout | slt-flex2@example.test | `is on its way` | each complete owner-filtered delta; save/show exact ids |
| 5 | `renewal_invoice` NONE EXPECTED | either order paid | — | — | suppressed for auto-pay subs (REF-04 §4); arrival = finding |

## Evidence to capture
- `SLT-SYN-06-00..05b` including before/between/after empty-cart shots and distinct queue views; `ORDER_W/M`, `SUB_W/M`, both `k`; both CSVs; all eight checkout Mailpit ids; persistent-cart proof; console errors.

## Pass criteria
- [ ] Week charge exactly $6.00, month exactly $26.13, both mode `prorate`
- [ ] The Test-data arithmetic reproduces from the stored metas
- [ ] Week `_next_payment_date` = `2026-08-07 18:00:00`, identical to SLT-SYN-05's; month = `2026-08-31 18:00:00`
- [ ] Neither cycle-start rewritten; no gateway-minimum bump
- [ ] Both complete four-message checkout sets arrived; no `renewal_invoice`
- [ ] Same-session cart and persistent-cart meta empty before, between, and after the two purchases

## Isolation / teardown
- Handed on: `SUB_W` renews for real 2026-08-08 at the FULL $14.00 — SLT-SYN-09 owns that proof. `SUB_M` is due 2026-09-01, outside the window: it belongs to the sole authorized time-travel day (D8, 2026-08-10), never to a bare hook drain.
- `slt-flex2` must not rebuy either product. Restores: none. Close only `slt06cust-SLT-SYN-06` and `admin-SLT-SYN-06`.


---

### Verified environment facts (2026-08-01/02 — do not re-derive)

- **Nothing fires at `_next_payment_date`.** Every scheduled leg is shifted by
  `crc32('arraysubs-spread-'.$subscription_id) % 21600` (0-6 h). Charge fires at `due + offset`,
  invoice at `due + offset - 6h`. The stored date never moves. **Assert a window, not a point.**
- Currency `USD`. **Taxes are OFF** (`woocommerce_calc_taxes = no`) — never assert a tax line.
- Orders use **HPOS** (`wp_wc_orders`), not `wp_posts`.
- `woocommerce_enable_guest_checkout = yes`, but ArraySubs force-requires registration for
  **subscription** carts via `woocommerce_checkout_registration_required`
  (`SubscriptionCheckout/Services/Hooks.php:103`, `CheckoutHelpersTrait.php:93-100`).
- WooCommerce **grouped** products have zero handling in either plugin — grouped tasks are
  exploratory: document behaviour, do not assert a spec.
- WP-Cron runs every minute from `/etc/cron.d/mirror-help-arrayhash-wordpress`. Scheduled actions
  fire on their own; **a renewal that does not fire is a real bug** — capture evidence and do not force a natural-watch action.
- Give this task its own browser session (`agent-browser --session <role>-<TASK-KEY>`). Sessions are
  keyed by name and **share a cart**.
- Never run a bare or `--hooks=` Action Scheduler drain. Run one known action ID at a time only when the task explicitly authorizes it and after the required queue pre-flight; natural-watch actions are never forced.
- Evidence goes under `/home/server-manager/slt-evidence/` using task-key-prefixed filenames.

[[2026-08-05]] Wed 21:05
UNVERIFIED (missed D02 purchase window) on 2026-08-05.

This task depended on D02 purchases under `SLT-SYN-06`. Live verification on 2026-08-05 found no ArraySubs subscriptions owned by `slt-flex2` for `SLT Flex Week Segments` (`11943`) or `SLT Flex Month Segments` (`12093`). The D03 suite report and evening automation log explicitly state that `SYN-06` remained unexercised at the overnight boundary and that missing D2 flex fixtures are execution gaps unless a later authored recovery path permits creation. No such recovery path exists here, so this card closes without creating late synced purchases.
