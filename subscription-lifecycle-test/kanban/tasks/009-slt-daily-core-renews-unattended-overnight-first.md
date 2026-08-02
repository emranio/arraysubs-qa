---
id: 9
title: SLT Daily Core renews unattended overnight — first cycle, spread-offset window, cron-not-CLI proof
status: todo
priority: critical
created: 2026-08-02T03:43:03.652641586+02:00
updated: 2026-08-02T03:43:13.619504805+02:00
tags:
    - renewal
    - day-00
    - has-conflicts
due: "2026-08-02"
estimate: 1h30m
depends_on:
    - 10
    - 11
    - 12
    - 5
class: standard
---

> **SLT-REN-01** · group `renewal` · scheduled **D00** (2026-08-02)

> Read `README.md` (environment + isolation contract), `calendar.md` (this day's exact
> ordering — it is binding, not advisory) and `plan-audit.md` before starting.

### ⚠ Conflict resolutions that apply to this task

**`critical` · duplicate-purchase / control-spine destruction** — with `SLT-CHK-01`

- *Problem:* Both are tagged d0 and both place the SAME purchase: slt-core buys SLT Daily Core on the block checkout with Stripe 4242. CHK-01 step 3-6 and REN-01 step 3-4 are the same checkout. With multiple_subscriptions.auto_migrate_on_checkout=true (frozen baseline) the second checkout does not create a second subscription - CheckoutMigrationTrait migrates the existing one, rewriting _product_id/_recurring_amount and re-anchoring the schedule. That destroys the reference record (CHK-01's meta baseline for CHK-02's field-by-field diff) AND the day/1 renewal spine that REN-02, EML-02, EML-03, EML-05, EML-15, MYA-02, ADM-02, ADM-06 and the whole D1-D12 watch depend on. CHK-01's own precondition ('slt-core owns no SLT Daily Core sub and must never rebuy it - C08') is violated by REN-01.
- *Required fix:* Merge into one owner. SLT-CHK-01 is the sole purchaser and must execute inside REN-01's clock window (13:00-13:30 site, 2026-08-02) so both tasks' timing constraints are satisfied. SLT-REN-01 drops steps 1-5 and becomes an observation leg attached to CHK-01's SUB/ORDER: it keeps steps 6-11 (cron-not-CLI proof, AS leg timestamps, wp_actionscheduler_logs 'via WP Cron' assertion, D1/D2 follow-ups). Publish SUB_CORE=S1 and k to the registry from CHK-01. Add a hard precondition to REN-01: 'places no order'.

---
## Objective
Buy `SLT Daily Core` on D0 and prove its first renewal fires unattended overnight — no click, no drain — inside the spread window `due+k`, with an Action Scheduler trail that separates cron execution from a forced run.

## Scope
- Gateway: Stripe test
- Checkout: block
- Account: existing (`slt-core`)
- Plugins: both

## Preconditions
- SLT-SETUP-01/02/03 + SLT-PROD-01 done; `slt-core` has a billing address.
- Buy **13:00–13:30 site 2026-08-02** — after the 12:00 rule, late enough that the D1 charge leg (13:00–19:00 site) misses the SLT-SYN-04 09:00–11:00 bracket on D3.
- **No `wp action-scheduler run` in this task, any day.** A renewal that does not fire is the finding — capture it, never force it.

## Test data
| Item | Value |
|---|---|
| Product | SLT Daily Core, $10.00/day |
| Account | slt-core / SltQa!2026#Pass |
| Card | 4242 4242 4242 4242, 12/34, CVC 123 |
| Session | `customer-SLT-REN-01` |
| Due | 2026-08-03 ≈ purchase clock time |

## Steps
1. `PREBUY=$(mailpit-agent latest-id)`; record it.
2. `agent-browser --session customer-SLT-REN-01 open ".../my-account/"` → `snapshot -i` → log in; confirm `/cart/` EMPTY.
3. Open `/?p=<Daily Core ID>` → **Add to cart** → `/checkout/`.
4. Select **Stripe**, enter the card, tick **Save payment information to my account** if shown, **Place Order**. Record the click site-time.
5. `mailpit-agent wait-new "$PREBUY" 120 "is active"`, then `mailpit-agent list 20`.
6. From `wp_wc_orders` (`customer_id=<uid>`): record parent `id,status,total_amount,created_via,payment_method`.
7. `wp post list --post_type=arraysubs_data --author=<uid> --fields=ID,post_status` → `SUBID`; `wp post meta list SUBID` — keep `_next_payment_date`, `_completed_payments`, `_payment_gateway`, `_auto_renew`, `_renewal_action_id`.
8. `php -r '$h=(int)sprintf("%u",crc32("arraysubs-spread-SUBID"));echo $h%21600;'` → record `k`.
9. From `wp_actionscheduler_actions WHERE args='[SUBID]'` record `action_id,hook,status,scheduled_date_gmt,last_attempt_gmt`; screenshot **Tools → Scheduled Actions**.
10. **Stop until D1.** The D1 watch (08:10 site, 2026-08-03) precedes the charge leg: it verifies only that both legs are still pending at their computed timestamps.
11. On D1 after `due+k` (worst case 19:15 site) and at the D2 watch (2026-08-04): re-run 6, 7, 9; pull `wp_actionscheduler_logs` for both ids; `list 50`.

## Expected results
1. Parent order paid, `total_amount=10.00`, `created_via=store-api`, `payment_method=stripe`; subscription `arraysubs-active`, `_completed_payments=1`, `_next_payment_date` = purchase time + exactly 24 h.
2. Pending `arraysubs_generate_renewal_invoice` `[SUBID]` at `due + k − 21600s`; pending `arraysubs_process_renewal` `[SUBID]` at `due + k`; `k` matches step 8.
3. After D1 both rows are `complete`, each `last_attempt_gmt` within 90 s of its `scheduled_date_gmt`.
4. Logs for both ids read `action started via WP Cron` / `action complete via WP Cron`, never `via WP CLI`.
5. Renewal order: `_is_renewal_order=yes`, `_subscription_id=SUBID`, `_renewal_cycle_number=1`, `_renewal_scheduled_date` = the D1 due date, $10.00, paid, **`created_via` EMPTY** (renewal orders come from `wc_create_order()`; a `store-api` value means a human placed it).
6. Post-renewal: `_completed_payments=2`, `_last_payment_date` set, `_pending_renewal_order_id` deleted, `_next_payment_date` = D2 same clock time.

## Emails expected
| # | Email | Trigger point | Recipient | Subject contains | Verify with |
|---|---|---|---|---|---|
| 1 | new_subscription | Place Order | slt-core@example.test | `is active` | `wait-new $PREBUY 120 "is active"` |
| 2 | admin_new_subscription | Place Order | admin_email | `New subscription #` | `list 20` |
| 3 | payment_successful | `due+k` on D1 | slt-core@example.test | `Payment received for subscription #` | `list 50` |
| 4 | NONE EXPECTED: renewal_invoice (Stripe auto-renew suppression), renewal_reminder (3-day lead > 1-day cycle), payment_failed. Woo order mail: record-only | D1 legs | — | — | `list 50` |

## Evidence to capture
- `SLT-REN-01-01-checkout.png`, `-02-pending-actions.png`, `-03-renewal-order-D1.png`; SUBID, order ids, action ids, `k`, Mailpit ids, query output.

## Pass criteria
- [ ] Parent order paid $10.00, `created_via=store-api`, `_completed_payments=1`
- [ ] Legs queued at `due+k−6h` and `due+k` with the computed `k`
- [ ] Both actions complete within 90 s, logged `via WP Cron`, not `via WP CLI`
- [ ] Renewal order $10.00 paid, cycle 1, `created_via` empty, `_completed_payments=2`, `_next_payment_date` +24 h
- [ ] Emails 1-3 present, row-4 negatives absent, no drain issued

## Isolation / teardown
- Hands SUBID, `k`, the anniversary time and both action ids to SLT-REN-02; the offset recipe is reused by REN-03/05.
- The D3 invoice leg (06:15–13:15 site) can overlap the SLT-SYN-04 bracket; if `_next_payment_date` re-anchors to `18:00:00` UTC, file against SLT-SYN-04.
- Nothing restored; lives until SLT-SETUP-99A. Close only this session.

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
