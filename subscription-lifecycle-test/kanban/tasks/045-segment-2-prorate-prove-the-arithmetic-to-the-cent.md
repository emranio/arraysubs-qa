---
id: 45
title: 'Segment 2 prorate: prove the arithmetic to the cent on week and month cycles'
status: todo
priority: critical
created: 2026-08-02T03:43:06.653472519+02:00
updated: 2026-08-02T03:43:17.2509829+02:00
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
1. From WP root re-dump the six `_arraysubs_flex_sync_*` keys for both products to `slt-evidence/SLT-SYN-06-plans.csv`; abort unless week=`yes,2,5,yes,yes,yes`, month=`yes,2,6,yes,yes,yes`.
2. `agent-browser --session slt06cust open "https://mirror-help.arrayhash.com/my-account/"` → `snapshot -i` → log in as `slt-flex2`; `mailpit-agent latest-id` → `MAILID_W`.
3. `open ".../checkout/?add-to-cart=<WEEK_ID>"` → `snapshot -i`; screenshot `SLT-SYN-06-01-week-summary.png`; total due today must read **$6.00**; record any prorate wording verbatim; no bonus-cycle note.
4. Select Stripe explicitly, re-read the total (still $6.00), pay; record `ORDER_W`, `SUB_W`; `wait-new $MAILID_W 120 "is active"`.
5. `latest-id` → `MAILID_M`. `open ".../checkout/?add-to-cart=<MONTH_ID>"` → `snapshot -i`; screenshot `SLT-SYN-06-02-month-summary.png`; total must read **$26.13**. Select Stripe, pay; record `ORDER_M`, `SUB_M`; `wait-new $MAILID_M 120 "is active"`.
6. For both subs dump the five `_renewal_sync_*` keys + `_next_payment_date` + `_completed_payments` to `SLT-SYN-06-sub-meta.csv`; screenshot both order item mirrors as `SLT-SYN-06-03-week-item-meta.png` / `-04-month-item-meta.png`.
7. Compute `k` for both subs (README crc32 one-liner); screenshot each pending queue as `SLT-SYN-06-05-pending.png`.

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
| 1 | `new_subscription` ×2 | order paid | slt-flex2@example.test | `is active` | `wait-new $MAILID_W`/`$MAILID_M` |
| 2 | `admin_new_subscription` ×2 | order paid | admin_email | `New subscription #` | same |
| 3 | `renewal_invoice` NONE EXPECTED | either order paid | — | — | suppressed for auto-pay subs (REF-04 §4); arrival = finding |

## Evidence to capture
- `SLT-SYN-06-01..05`; `ORDER_W/M`, `SUB_W/M`, both `k`; both CSVs; Mailpit ids; console errors.

## Pass criteria
- [ ] Week charge exactly $6.00, month exactly $26.13, both mode `prorate`
- [ ] The Test-data arithmetic reproduces from the stored metas
- [ ] Week `_next_payment_date` = `2026-08-07 18:00:00`, identical to SLT-SYN-05's; month = `2026-08-31 18:00:00`
- [ ] Neither cycle-start rewritten; no gateway-minimum bump
- [ ] Both created-mails arrived; no `renewal_invoice`

## Isolation / teardown
- Handed on: `SUB_W` renews for real 2026-08-08 at the FULL $14.00 — SLT-SYN-09 owns that proof. `SUB_M` is due 2026-09-01, outside the window: it belongs to the sole authorized time-travel day (D8, 2026-08-10), never to a bare hook drain.
- `slt-flex2` must not rebuy either product. Restores: none. Close `slt06cust`.


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
  fire on their own; **a renewal that does not fire is a real bug** — capture evidence before forcing.
- Give this task its own browser session (`agent-browser --session <role>-<TASK-KEY>`). Sessions are
  keyed by name and **share a cart**.
- Never run `wp action-scheduler run` without `--hooks=`; prefer a single action by ID.
- Evidence goes in `qa/subscription-lifecycle-test/evidence/<TASK-KEY>/`.
