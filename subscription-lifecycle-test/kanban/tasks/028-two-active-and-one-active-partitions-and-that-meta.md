---
id: 28
title: Two-active and one-active partitions, and that META_SEG1_END is positional
status: todo
priority: high
created: 2026-08-02T03:43:05.286065313+02:00
updated: 2026-08-02T03:43:15.727471075+02:00
tags:
    - renewal-sync
    - day-01
    - has-conflicts
due: "2026-08-03"
estimate: 2h
depends_on:
    - 14
    - 22
    - 13
class: standard
---

> **SLT-SYN-08** · group `sync` · scheduled **D01** (2026-08-03)

> Read `README.md` (environment + isolation contract), `calendar.md` (this day's exact
> ordering — it is binding, not advisory) and `plan-audit.md` before starting.

### ⚠ Conflict resolutions that apply to this task

**`critical` · dependency-inversion / date contradiction** — with `SLT-PROD-14`, `SLT-SYN-01`, `SLT-EML-01`, `SLT-SYN-09`

- *Problem:* SLT-SYN-08 is tagged d0 and buys SLT Flex Daily Two Seg + SLT Flex Daily Next Cycle, but SLT-PROD-14 creates those products on D1 in the corrected calendar and audit C10 forbids purchasing a flex product before SLT-SYN-01's destructive meta surgery has run and been restored. Worse, SYN-08's stated dates encode a D0 purchase (cycle_start 2026-08-01 18:00 UTC, Two Seg next payment 08-04 18:00 UTC) while SLT-EML-01 - which owns the only reachable renewal_reminder in the window - encodes a D1 purchase (SUB_2SEG due 2026-08-06 00:00 site, SUB_NC due 2026-08-09 00:00 site, reminder fires 08-06 00:00-06:00 = watch D4). Both cannot be true and neither product can be bought twice by the same account.
- *Required fix:* SLT-SYN-08 moves to D1 (2026-08-03), purchases after 12:00, strictly after SLT-PROD-14 and after SLT-SYN-01B's restore is proven. That makes EML-01's numbers correct as written (SUB_2SEG due 08-06 00:00 site, SUB_NC due 08-09 00:00 site, reminder 08-06 00:00-06:00 site, watch D4) and SYN-08's own Test data must be recomputed to cycle_start 2026-08-02 18:00 UTC, Two Seg next payment 2026-08-05 18:00 UTC, Next Cycle cycle_start rewritten to 2026-08-05 18:00 UTC and next payment 2026-08-08 18:00 UTC. Knock-on: SLT-SYN-09's SUB_A row is now wrong (it assumes #2 at 08-04 18:00 and #3 at 08-07 18:00). Move SLT-SYN-09 from D6 to D7 (2026-08-09 morning) where the week pair's 08-08 00:00 renewals AND SUB_A's #2 at 08-09 00:00 are both already visible; hand SUB_A's #3 (08-12 00:00) to watch D10 as a grid assertion.

---
## Objective
Prove the 2-active and 1-active partitions on live purchases, and that `_arraysubs_flex_sync_seg1_end` is **POSITIONAL** — it bounds the first ACTIVE segment, not segment 1. On a `day` period `day_in_cycle` is always 1, so the first active segment wins and only the toggles decide.

## Scope
- Gateway: Stripe test
- Checkout: block
- Account: existing `slt-flex2` (2-active) + `slt-flex3` (1-active), from SLT-SYN-05
- Plugins: pro-required

## Preconditions
- SLT-SYN-05 done; SLT-PROD-14 done — `SLT Flex Daily Two Seg` (day/3 $9.00, seg1 OFF, `seg1_end=1`) and `SLT Flex Daily Next Cycle` (day/3 $9.00, seg3 only); SLT-SYN-01 done, restores proven.
- Global sync OFF; never run in SLT-SYN-04's bracket. One cart at a time (`allow_multiple_in_cart=false`) — two sessions so carts cannot collide.
- Buy both on **D0 = 2026-08-02** (site) after SLT-SYN-05, card `4242 4242 4242 4242`. For `day`, `cycle_start` = purchase day 00:00 site = `2026-08-01 18:00:00` UTC.

## Test data
| | Two Seg (`slt-flex2`) | Next Cycle (`slt-flex3`) |
|---|---|---|
| actives / boundaries / partition | `[2,3]` / `[1]` / 1..1→seg2, 2..3→seg3 | `[3]` / `[]` / 1..3→seg3 |
| day 1 resolves to | **seg 2** → `prorate` | **seg 3** → `next_cycle` |
| charge today | **$9.00** (`start <= cycle_start` ⇒ ratio 1.0: prorate = full) | **$9.00** |
| cycle_start → next_payment (UTC) | `08-01 18:00` (unchanged) → `08-04 18:00` | `08-04 18:00` (**rewritten**) → `08-07 18:00` |

## Steps
1. From WP root run SLT-SYN-01's `SegmentPlan::getConfig(<ID>)` `wp eval` probe for both products, tee to `slt-evidence/SLT-SYN-08-config.txt`; also dump the six `_arraysubs_flex_sync_*` keys.
2. **Positional check** — Two Seg: `seg1_end=1`, `seg1_active=no`, `actives=[2,3]`, `boundaries=[1]`, so "seg1_end" bounds segment **2**. Next Cycle: `boundaries` EMPTY despite a stored `seg1_end` — with one active segment the meta is ignored. Screenshot both legends as `SLT-SYN-08-01-positional.png`.
3. `agent-browser --session slt08a open "https://mirror-help.arrayhash.com/my-account/"`; log in as `slt-flex2`; `latest-id` → `MAILID_A`.
4. `open ".../checkout/?add-to-cart=<TWO_SEG_ID>"` → `snapshot -i`; screenshot `SLT-SYN-08-02-twoseg.png`: total **$9.00**, no bonus note, Paddle absent. Select Stripe, pay; record `ORDER_A`, `SUB_A`; `wait-new $MAILID_A 120`.
5. Same in `--session slt08b` as `slt-flex3`; `latest-id` → `MAILID_B`.
6. `open ".../checkout/?add-to-cart=<NEXT_CYCLE_ID>"` → `snapshot -i`; screenshot `SLT-SYN-08-03-nextcycle.png`: total **$9.00**, note naming **5 August, 2026**. Select Stripe, pay; record `ORDER_B`, `SUB_B`; `wait-new $MAILID_B 120`.
7. Dump the five `_renewal_sync_*` keys + `_next_payment_date` + `_completed_payments` for both subs to `SLT-SYN-08-sub-meta.csv`; screenshot both order item mirrors as `SLT-SYN-08-04-item-meta.png`.
8. Compute `k` for both; screenshot both pending queues as `SLT-SYN-08-05-pending.png`.

## Expected results
1. Two Seg: mode `prorate`, amount `9`, total `$9.00`, cycle start `2026-08-01 18:00:00` (unchanged), next payment `2026-08-04 18:00:00`.
2. Next Cycle: mode `next_cycle`, amount `9`, cycle start `2026-08-04 18:00:00` (rewritten), next payment `2026-08-07 18:00:00` — 3 days later, same period, day and price.
3. `getConfig()` matches Test data; `seg1_end=1` on Two Seg bounds segment 2 — SLT-SYN-01's positional finding reproduced at checkout.
4. Only Next Cycle shows the bonus note; both subs `arraysubs-active`, `_completed_payments=1`, orders paid; Paddle absent.
5. Pending per sub: invoice `due +k −6h`, `arraysubs_process_renewal` `+k`, reminder `due −3d +k`. Two Seg's reminder time is already past at signup — record whether a `renews soon` mail fires, as an observation, not a failure.

## Emails expected
| # | Email | Trigger point | Recipient | Subject contains | Verify with |
|---|---|---|---|---|---|
| 1 | `new_subscription` ×2 | order paid | slt-flex2 / slt-flex3 | `is active` | `wait-new $MAILID_A`/`$MAILID_B` |
| 2 | `admin_new_subscription` ×2 | order paid | admin_email | `New subscription #` | same |
| 3 | `renewal_invoice` NONE EXPECTED | order paid | — | — | suppressed for auto-pay subs (REF-04 §4); arrival = bug |

## Evidence to capture
- `SLT-SYN-08-01..05`; `-config.txt`; `-sub-meta.csv`; `ORDER_A/B`, `SUB_A/B`, `k`; Mailpit ids; console errors.

## Pass criteria
- [ ] Two Seg resolves to seg 2 (`prorate`) yet charges $9.00, renewing 2026-08-05 00:00 +06
- [ ] Next Cycle resolves to seg 3, charges $9.00, renewing 2026-08-08 00:00 +06
- [ ] `getConfig()` boundaries `[1]` and `[]`; `seg1_end` proven positional
- [ ] Only Next Cycle shows the bonus note; Paddle absent
- [ ] Both created-mail pairs arrived; no `renewal_invoice`

## Isolation / teardown
- Handed on: `SUB_A` renews 2026-08-05 / 08-08 / 08-11, `SUB_B` 2026-08-08 / 08-11; SLT-SYN-09 owns the `SUB_A` grid proof. Neither may be force-run — both fire unattended.
- Neither account rebuys its product. Restores: none. Close `slt08a`/`slt08b`.


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
