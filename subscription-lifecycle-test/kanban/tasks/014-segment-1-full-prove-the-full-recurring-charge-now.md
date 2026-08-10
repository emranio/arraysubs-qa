---
id: 14
title: 'Segment 1 full: prove the full recurring charge now and the exact next-cycle boundary date'
status: done
priority: critical
created: 2026-08-02T03:43:04.154415714+02:00
updated: 2026-08-05T21:37:49.299284673+02:00
started: 2026-08-02T16:01:31.662467916+02:00
completed: 2026-08-02T16:01:31.662467916+02:00
tags:
    - renewal-sync
    - day-00
due: "2026-08-02"
estimate: 1.5h
depends_on:
    - 10
    - 11
    - 12
    - 8
class: standard
---

> **SLT-SYN-05** · group `sync` · scheduled **D00** (2026-08-02)

> Read `README.md` (environment + isolation contract), `calendar.md` (this day's exact
> ordering — it is binding, not advisory) and `plan-audit.md` before starting.

## Objective
Prove segment-1 `full` mode on a live Stripe checkout: the first charge is the FULL recurring amount (ratio 1.0, gateway minimum forced 0.0, `Hooks.php:389-392`), the boundary is not rewritten, and `_next_payment_date` is the upcoming week boundary — SLT-SYN-07's anchor.

## Scope
- Gateway: Stripe test
- Checkout: block
- Account: existing (`slt-flex`); verifies the already-created `slt-flex2`/`slt-flex3` handoff
- Plugins: pro-required

## Preconditions
- SLT-SETUP-01/02/03 + SLT-PROD-13 (`SLT Flex Week Segments`, week/1 $14.00, seg1_end=2, seg2_end=5, all active) done.
- SLT-SYN-01A's D0 week-only pass is complete with its canonical restore diff and registry purchase-authorisation handoff. The parent SLT-SYN-01 card remains open for its unrelated D1 month/daily Pass B. No further week-product meta surgery is permitted. Global `sync_to_billing_cycle` is OFF — the product syncs only via `filterSupportsRenewalSync()`. Never run in SLT-SYN-04's bracket.
- SLT-SETUP-03 already created `slt-flex2` + `slt-flex3` (`slt-flexN@example.test`, `customer`, pw `SltQa!2026#Pass`, complete billing profile). Do not create them again. A rebuy would create an ambiguous duplicate at the frozen `one_per_customer=false` baseline. Binding: seg1=`slt-flex`, seg2=`slt-flex2`, seg3=`slt-flex3`.

## Test data
| Item | Value |
|---|---|
| Product / buyer / card | `SLT Flex Week Segments` $14.00 week/1 / `slt-flex` / `4242 4242 4242 4242` |
| Buy on | D0 2026-08-02, after SLT-SYN-01 closes |
| Cycle start | Sat 2026-08-01 00:00 +06 = `2026-07-31 18:00:00` UTC (`start_of_week`=6) |
| Day in cycle | 2 → boundaries [2,5] → **segment 1** → `full` |
| Charge / next payment | **$14.00** / 2026-08-08 00:00 +06 = **`2026-08-07 18:00:00`** UTC |

## Steps
1. `MAILID_A=$(mailpit-agent latest-id)`. Verify existing users `slt-flex2` and `slt-flex3` have role `customer`, complete billing profiles, and no owned subscription yet. This verification must not emit task-attributable mail; inspect the complete delta after `MAILID_A` and classify unrelated shared-site messages separately instead of requiring the global latest ID to remain equal.
2. From WP root dump `_arraysubs_flex_sync_enabled`, `_arraysubs_flex_sync_seg1_end`, `_arraysubs_flex_sync_seg2_end`, `_arraysubs_flex_sync_seg1_active`, `_arraysubs_flex_sync_seg2_active`, `_arraysubs_flex_sync_seg3_active`, and `_regular_price` for `<WEEK_ID>` to `/home/server-manager/slt-evidence/SLT-SYN-05-plan.csv`; abort unless `yes,2,5,yes,yes,yes,14.00`.
3. `agent-browser --session slt05cust-SLT-SYN-05 open "https://mirror-help.arrayhash.com/my-account/"` → `snapshot -i` → log in as `slt-flex`; immediately open `/cart/` and STOP and write a standalone issue file under `issues/` unless it is empty. `MAILID_B=$(mailpit-agent latest-id)`.
4. Open the product, add it to the empty cart, then open `/checkout/` → `snapshot -i`. Screenshot `SLT-SYN-05-01-summary.png`; record the verbatim total-due-today; confirm NO "Today's payment covers the full billing cycle starting …" note (seg 3 only).
5. Confirm **Paddle** absent / **Stripe** present; click the Stripe radio explicitly, then re-read totals (gateway change re-prices). Screenshot `SLT-SYN-05-02-gateways.png`.
6. Pay; screenshot `SLT-SYN-05-03-received.png`; record `ORDER_ID`; `mailpit-agent wait-new "$MAILID_B" 120 "is active"`, then list and map all four expected checkout messages.
7. Get `SUBID` from the order-received page. In isolated session `admin-SLT-SYN-05`, open ArraySubs → Subscriptions (`admin.php?page=arraysubs-mainadmin#/subscriptions`), search the ID, and click **View Details**; screenshot `SLT-SYN-05-04-schedule.png`. Dump the five `_renewal_sync_*` keys + `_next_payment_date` + `_completed_payments` to `/home/server-manager/slt-evidence/SLT-SYN-05-sub-meta.csv`; open the HPOS order edit route and screenshot the order item mirror as `SLT-SYN-05-05-item-meta.png`.
8. Compute `k` (README crc32 one-liner); in `admin-SLT-SYN-05`, screenshot `tools.php?page=action-scheduler&status=pending&s=<SUBID>` as `SLT-SYN-05-06-pending.png`.

## Expected results
1. `_renewal_sync_enabled=yes`, `_renewal_sync_first_charge_mode=full`, `_renewal_sync_cycle_start_date=2026-07-31 18:00:00` (NOT rewritten); `_renewal_sync_initial_recurring_amount=14`; order total exactly `$14.00`, no tax line.
2. `_renewal_sync_first_full_renewal_date = _next_payment_date = 2026-08-07 18:00:00`.
3. `_completed_payments=1`; sub `arraysubs-active`; virtual order `completed`; no bonus note; Paddle absent.
4. Pending: `arraysubs_generate_renewal_invoice[<SUBID>]` at `2026-08-07 18:00:00 +k −6h`; `arraysubs_process_renewal[<SUBID>]` at `+k`; `arraysubs_send_renewal_reminder[<SUBID>,3]` at `2026-08-04 18:00:00 +k` — assert windows, not points.

## Emails expected
| # | Email | Trigger point | Recipient | Subject contains | Verify with |
|---|---|---|---|---|---|
| 1 | NONE EXPECTED | existing-user/profile verification | — | — | Complete delta after `MAILID_A`; zero task-attributable mail, while unrelated/background mail is allowed and classified |
| 2 | WC completed order | paid virtual checkout | slt-flex@example.test | `is on its way` | Complete owner-filtered delta after `MAILID_B`; save/show the exact matching id |
| 3 | WC new order | paid checkout | admin | `New order #<ORDER_ID>` | Complete owner-filtered delta after `MAILID_B`; save/show the exact matching id |
| 4 | `new_subscription` | order paid | slt-flex@example.test | `is active` | `mailpit-agent wait-new "$MAILID_B" 120` |
| 5 | `admin_new_subscription` | order paid | admin_email | `New subscription #` | Complete owner-filtered delta after `MAILID_B`; save/show the exact matching id |
| 6 | `renewal_invoice` NONE EXPECTED | order paid | — | — | suppressed for auto-pay subs (REF-04 §4); arrival = finding |

## Evidence to capture
- `SLT-SYN-05-01..06`; `ORDER_ID`, `SUBID`, `k`, user IDs; both CSVs; Mailpit ids; console errors.

## Pass criteria
- [x] Charge exactly $14.00, mode `full`, cycle start `2026-07-31 18:00:00`
- [x] `_next_payment_date` = `2026-08-07 18:00:00` = `_renewal_sync_first_full_renewal_date`
- [x] No seg-3 note; Paddle hidden, Stripe offered
- [x] Three scheduled actions at due+k; all four checkout mails arrived; no `renewal_invoice`
- [x] Existing `slt-flex2`/`slt-flex3` verified with zero mail and zero owned subscriptions

## Isolation / teardown
- Handed on: `SUBID` renews for real 2026-08-08 (SLT-SYN-09 owns it); `2026-08-08 00:00 +06` is SLT-SYN-07's anchor; `slt-flex2`/`slt-flex3` go to SLT-SYN-06/07/08. `slt-flex` never rebuys it.
- Restores: none. Close only `slt05cust-SLT-SYN-05` and `admin-SLT-SYN-05`; `SLT-SETUP-99B` deletes users, order, and subscription on 2026-08-15.

## Execution record — 2026-08-02

**PASS.** The purchase-authorisation dump for product `11943` matched `enabled=yes`, boundaries `2/5`, all three segments active, and price `14.00`. Existing users `slt-flex2` (ID `354`) and `slt-flex3` (ID `355`) had the correct customer profiles, complete billing data, zero subscriptions, and emitted no mail. `slt-flex` (ID `350`) passed the empty-cart pre-flight and bought the product in segment 1 through the block checkout with Stripe.

The cart and checkout rendered `$14.00 full first charge`, next charge `8 August, 2026 (UTC+6) ($14.00)`, and no segment-3 coverage note. Stripe was offered; Paddle was absent. Order `12029` completed for USD `$14.00` (`store-api`, one line, zero tax), creating active subscription `12039`. Its sync mirror is `_renewal_sync_enabled=yes`, mode `full`, cycle start `2026-07-31 18:00:00Z`, initial recurring amount `14`, first full renewal and `_next_payment_date` both `2026-08-07 18:00:00Z`, and `_completed_payments=1`.

Spread `k=18112` seconds produced exactly three pending actions: reminder `13730` at `2026-08-04 23:01:52Z`, invoice `13731` at `2026-08-07 17:01:52Z`, and renewal `13732` at `2026-08-07 23:01:52Z`. Historical invoice/renewal rows `13728`/`13729` were created and cancelled during gateway synchronization before the final pair was installed; they are not pending. Mailpit captured customer completed order `4f4eU1t2llGX4wxj5WtrZb`, admin new order `0UBg9DN3Nxbopet6SX4Agl`, customer active subscription `4guhTRIYPtbMddgW2UEOD8`, and admin new subscription `5fgHhewvPf9iNzEKiXWCHx`, with no renewal invoice.

Evidence: `/home/server-manager/slt-evidence/SLT-SYN-05-01-summary.png` through `SLT-SYN-05-06-pending.png`, `SLT-SYN-05-plan.csv`, `SLT-SYN-05-sub-meta.csv`, and `SLT-SYN-05-facts.txt`. Subscription `12039` is handed to `SLT-SYN-09` for its natural 2026-08-08 renewal and remains in the tail cohort through D12.


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
