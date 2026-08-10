---
id: 28
title: Two-active and one-active partitions, and that META_SEG1_END is positional
status: done
priority: high
created: 2026-08-02T03:43:05.286065313+02:00
updated: 2026-08-05T21:37:49.358357285+02:00
started: 2026-08-03T09:28:27.037026457+02:00
completed: 2026-08-03T09:28:27.037026457+02:00
tags:
    - renewal-sync
    - day-01
due: "2026-08-03"
estimate: 2h
depends_on:
    - 14
    - 22
    - 13
    - 12
class: standard
---

> **SLT-SYN-08** · group `sync` · scheduled **D01** (2026-08-03)

> Read `README.md` (environment + isolation contract), `calendar.md` (this day's exact
> ordering — it is binding, not advisory) and `plan-audit.md` before starting.

## Objective
Prove the 2-active and 1-active partitions on live purchases, and that `_arraysubs_flex_sync_seg1_end` is **POSITIONAL** — it bounds the first ACTIVE segment, not segment 1. On a `day` period `day_in_cycle` is always 1, so the first active segment wins and only the toggles decide.

## Scope
- Gateway: Stripe test
- Checkout: block
- Account: existing `slt-flex2` (2-active) + `slt-flex3` (1-active), from SLT-SETUP-03
- Plugins: pro-required

## Preconditions
- SLT-SYN-05 done as the earlier sync-flow gate; SLT-PROD-14 done — `SLT Flex Daily Two Seg` (day/3 $9.00, seg1 OFF, `seg1_end=1`) and `SLT Flex Daily Next Cycle` (day/3 $9.00, seg3 only); SLT-SYN-01 done, restores proven; `slt-flex2` and `slt-flex3` come from SLT-SETUP-03.
- Global sync OFF; never run in SLT-SYN-04's bracket. One cart at a time (`allow_multiple_in_cart=false`) — two sessions so carts cannot collide.
- Buy both on **D1 = 2026-08-03** after 12:00 site, strictly after `SLT-SYN-01B` restores and authorizes the product metas, card `4242 4242 4242 4242`. For `day`, `cycle_start` = purchase day 00:00 site = `2026-08-02 18:00:00` UTC.

## Test data
| | Two Seg (`slt-flex2`) | Next Cycle (`slt-flex3`) |
|---|---|---|
| actives / boundaries / partition | `[2,3]` / `[1]` / 1..1→seg2, 2..3→seg3 | `[3]` / `[]` / 1..3→seg3 |
| day 1 resolves to | **seg 2** → `prorate` | **seg 3** → `next_cycle` |
| charge today | **$9.00** (the day-period proration helper compares site-local calendar days; a same-day start yields ratio 1.0 even though the checkout clock is after midnight) | **$9.00** |
| cycle_start → next_payment (UTC) | `08-02 18:00` (unchanged) → `08-05 18:00` | `08-05 18:00` (**rewritten**) → `08-08 18:00` |

## Steps
1. From WP root run SLT-SYN-01's `SegmentPlan::getConfig(<ID>)` `wp eval` probe for both products, tee to `/home/server-manager/slt-evidence/SLT-SYN-08-config.txt`; also dump the six `_arraysubs_flex_sync_*` keys.
2. **Positional check** — use the isolated authenticated session `admin-SLT-SYN-08`. Open the Two Seg product, re-snapshot, and capture its legend as `SLT-SYN-08-01a-two-seg-positional.png`: `seg1_end=1`, `seg1_active=no`, `actives=[2,3]`, `boundaries=[1]`, so "seg1_end" bounds segment **2**. Then open the Next Cycle product, re-snapshot, and capture `SLT-SYN-08-01b-next-cycle-positional.png`: `boundaries` EMPTY despite a stored `seg1_end` — with one active segment the meta is ignored. Do not edit or save either product.
3. `agent-browser --session slt08a-SLT-SYN-08 open "https://mirror-help.arrayhash.com/my-account/"`; log in as `slt-flex2`; open `/cart/` and require the persistent cart to be EMPTY; `MAILID_A=$(mailpit-agent latest-id)`.
4. `open ".../checkout/?add-to-cart=<TWO_SEG_ID>"` → `snapshot -i`; screenshot `SLT-SYN-08-02-twoseg.png`: total **$9.00**, no bonus note, Paddle absent. Select Stripe and pay; record `ORDER_2SEG`, resolve `SUB_2SEG` from that exact order's `_subscription_ids`, require one ID, and cross-check parent order/customer/product plus the count delta. `mailpit-agent wait-new "$MAILID_A" 120`, inspect the complete owner-filtered delta, then require the cart and persistent-cart meta EMPTY and capture `SLT-SYN-08-02b-two-seg-cart-empty-after.png` in this same session.
5. Same in `--session slt08b-SLT-SYN-08` as `slt-flex3`; open `/cart/` and require the persistent cart to be EMPTY; `MAILID_B=$(mailpit-agent latest-id)`.
6. `open ".../checkout/?add-to-cart=<NEXT_CYCLE_ID>"` → `snapshot -i`; screenshot `SLT-SYN-08-03-nextcycle.png`: total **$9.00**, note naming **6 August, 2026**. Select Stripe and pay; record `ORDER_NC`, resolve `SUB_NC` from that exact order's `_subscription_ids`, require one ID, and cross-check parent order/customer/product plus the count delta. `mailpit-agent wait-new "$MAILID_B" 120`, inspect the complete owner-filtered delta, then require the cart and persistent-cart meta EMPTY and capture `SLT-SYN-08-03b-next-cycle-cart-empty-after.png` in this same session.
7. Dump the five `_renewal_sync_*` keys + `_next_payment_date` + `_completed_payments` for both subs to `/home/server-manager/slt-evidence/SLT-SYN-08-sub-meta.csv`. In `admin-SLT-SYN-08`, open and re-snapshot each exact order separately; capture the Two Seg item mirror as `SLT-SYN-08-04a-two-seg-item-meta.png` and the Next Cycle item mirror as `SLT-SYN-08-04b-next-cycle-item-meta.png`.
8. Compute `k` for both. Search the Pending queue by the first exact numeric subscription ID, re-snapshot, and capture `SLT-SYN-08-05a-two-seg-pending.png`; repeat with the second exact numeric subscription ID as `SLT-SYN-08-05b-next-cycle-pending.png`. Never reuse a stale queue snapshot or claim that one filtered view proves both subscriptions.

## Expected results
1. Two Seg: mode `prorate`, amount `9`, total `$9.00`, cycle start `2026-08-02 18:00:00` (unchanged), next payment `2026-08-05 18:00:00` (08-06 site).
2. Next Cycle: mode `next_cycle`, amount `9`, cycle start `2026-08-05 18:00:00` (rewritten), next payment `2026-08-08 18:00:00` (08-09 site) — 3 days later, same period, day and price.
3. `getConfig()` matches Test data; `seg1_end=1` on Two Seg bounds segment 2 — SLT-SYN-01's positional finding reproduced at checkout.
4. Only Next Cycle shows the bonus note; both subs `arraysubs-active`, `_completed_payments=1`, orders paid; Paddle absent.
5. Pending per sub: invoice `due +k −6h`, `arraysubs_process_renewal` `+k`, reminder `due −3d +k`. Two Seg's reminder time is already past at signup — record whether a `renews soon` mail fires, as an observation, not a failure.

## Emails expected
| # | Email | Trigger point | Recipient | Subject contains | Verify with |
|---|---|---|---|---|---|
| 1 | WC completed order ×2 | each paid virtual checkout | slt-flex2 / slt-flex3 | `is on its way` | Complete the owner-filtered deltas after `$MAILID_A` / `$MAILID_B`; save/show both exact ids |
| 2 | WC new order ×2 | each paid checkout | admin_email | `New order #` | Same complete owner-filtered deltas; save/show both exact ids |
| 3 | `new_subscription` ×2 | order paid | slt-flex2 / slt-flex3 | `is active` | `mailpit-agent wait-new "$MAILID_A" 120` / `mailpit-agent wait-new "$MAILID_B" 120` |
| 4 | `admin_new_subscription` ×2 | order paid | admin_email | `New subscription #` | Same complete owner-filtered deltas; save/show both exact ids |
| 5 | `renewal_reminder` OBSERVATION ONLY for Two Seg | its computed `due−3d+k` is already past when the D1 checkout schedules it | slt-flex2 | `renews soon` | Record presence/absence and exact action/mail timing from the complete `$MAILID_A` delta; neither outcome alone fails this task |
| 6 | `renewal_invoice` NONE EXPECTED | order paid | — | — | suppressed for auto-pay subs (REF-04 §4); arrival = bug |

## Evidence to capture
- `SLT-SYN-08-01a`, `-01b`, `-02`, `-02b`, `-03`, `-03b`, `-04a`, `-04b`, `-05a`, and `-05b`; `-config.txt`; `-sub-meta.csv`; `ORDER_2SEG`/`ORDER_NC`, `SUB_2SEG`/`SUB_NC`, both `k` values; Mailpit ids; console errors.

## Pass criteria
- [ ] Two Seg resolves to seg 2 (`prorate`) yet charges $9.00, renewing 2026-08-06 00:00 site (`2026-08-05 18:00:00` UTC)
- [ ] Each task-keyed customer cart and persistent-cart meta proved empty immediately before and after its purchase
- [ ] Next Cycle resolves to seg 3, charges $9.00, renewing 2026-08-09 00:00 site (`2026-08-08 18:00:00` UTC)
- [ ] `getConfig()` boundaries `[1]` and `[]`; `seg1_end` proven positional
- [ ] Only Next Cycle shows the bonus note; Paddle absent
- [ ] Both four-message checkout sets arrived (WC completed order, WC new order, customer subscription, admin subscription); the possible immediate Two Seg reminder is classified as an observation; no `renewal_invoice`

## Isolation / teardown
- Handed on: `SUB_2SEG` renews 2026-08-06 / 08-09 / 08-12 site, `SUB_NC` 2026-08-09 / 08-12 site; SLT-SYN-09 owns the `SUB_2SEG` grid proof. Neither may be force-run — both fire unattended.
- Neither account rebuys its product. Restores: none. Close only `admin-SLT-SYN-08`, `slt08a-SLT-SYN-08`, and `slt08b-SLT-SYN-08`.


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

---

### D01 execution — 2026-08-03

**PASS.** Read-only configuration/UI checks reproduced product `12099` as actives `[2,3]`, boundaries `[1]`, and product `12102` as actives `[3]`, boundaries `[]`; neither product was edited or saved. The previously documented disabled-row rendering issue remained visible and is already covered by `issues/SLT-SYN-01-disabled-segments-remain-visible.md`, so no duplicate finding was filed.

Two Seg checkout: user `354` (`slt-flex2`), order `12162`, sub `12172`; paid USD 9.00, active, completed payments 1, mode `prorate`, cycle start `2026-08-02 18:00:00Z`, next payment `2026-08-05 18:00:00Z`. `k=699`; pending invoice/charge actions `13994`/`13995`. The authored immediate-reminder observation was absent: no action and no reminder mail.

Next Cycle checkout: user `355` (`slt-flex3`), order `12183`, sub `12193`; paid USD 9.00, active, completed payments 1, mode `next_cycle`, cycle start `2026-08-05 18:00:00Z`, next payment `2026-08-08 18:00:00Z`. `k=2435`; pending reminder/invoice/charge actions `14001`/`14002`/`14003`.

Both task-keyed browser and persistent carts were empty before and after. Each exact baseline delta contained the required four messages; no renewal invoice arrived. Browser errors were empty; the known block-checkout dependency warning remains covered by the existing `SLT-CHK-01` issue. Consolidated evidence: `/home/server-manager/slt-evidence/SLT-SYN-08-facts.txt` and `/home/server-manager/slt-evidence/SLT-SYN-08-sub-meta.csv` plus all twelve authored artifacts.
