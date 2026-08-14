---
id: 88
title: 'Gateway gating for flexible sync: Paddle hidden from the DOM and blocked at submit, Stripe syncs to the midnight boundary'
status: done
priority: critical
created: 2026-08-02T03:43:10.547468659+02:00
updated: 2026-08-12T17:59:22.036127075+02:00
started: 2026-08-07T19:06:51.204447884+02:00
completed: 2026-08-07T19:06:51.204447884+02:00
tags:
    - renewal-sync
    - day-05
due: "2026-08-07"
estimate: 2h
depends_on:
    - 10
    - 11
    - 26
    - 23
claimed_by: plume-coal
claimed_at: 2026-08-07T19:06:51.204448846+02:00
class: standard
---

> **SLT-SYN-12** · group `sync` · scheduled **D05** (2026-08-07)

> Read `README.md` (environment + isolation contract), `calendar.md` (this day's exact
> ordering — it is binding, not advisory) and `plan-audit.md` before starting.

## Objective
On one product whose flex-sync flag is the only variable, establish which gateways permit Flexible Renewal Sync and how unsupported ones are refused: Paddle gone from the DOM (not CSS-hidden) on a sync-eligible cart, the server validator also blocking a forced `arraysubs_paddle`, Stripe producing a synced sub on the midnight boundary.

## Scope
- Gateway: both (Stripe test, Paddle sandbox)
- Checkout: both (`/checkout/`, `/slt-classic-checkout`)
- Account: existing `slt-paddle`, `slt-flex2`
- Plugins: both

## Preconditions
- SLT-SETUP-01, -02 (sync OFF), -05, SLT-PROD-16 done. Buy after 12:00.
- Contract: `arraysubs_is_renewal_sync_supported_gateway()` is true for `stripe` and manual ids, false for `arraysubs_paddle`; `maybeHideUnsupportedRenewalSyncGateways()` strips them and `validateRenewalSyncGatewaySupport()`/`…StoreApi()` reject at submit.
- Creates one probe; does NOT repeat SLT-SETUP-05's `SLT Flex Daily Next Cycle` check. `maybeHide…` returns all gateways if hiding leaves zero — do not provoke that.

## Test data
| Item | Value |
|---|---|
| Probe | `SLT Flex Gateway Probe` / `slt-flex-gateway-probe`, Simple, Virtual, day/5, $20.00 |
| Flex plan (phase 2) | 3 segments active, seg1_end 1, seg2_end 3 -> `1 / 2-3 / 4-5` |
| Buyers / cards | Paddle `slt-paddle`, Stripe `slt-flex2` (distinct products, no auto-migrate); `4242…4242` |

## Steps
1. Resolve numeric `PADDLE_USER`/`STRIPE_USER`, record `SUBCOUNT_BEFORE`/`ORDERCOUNT_BEFORE`, and set `M0=$(mailpit-agent latest-id)`. In `admin-SLT-SYN-12`, create the probe with flex unticked, record strict numeric `PROBE_ID`, and capture its panel as `SLT-SYN-12-00-flex-off.png`. Save the exact raw Shop Access option before/after appending only `$PROBE_ID`; diff it, require all prior fields unchanged and the new ID once, then reconcile `M0` with zero task-attributable mail before any storefront request.
2. Paddle control in authenticated `customer-paddle-SLT-SYN-12`: require both carts empty, capture `SLT-SYN-12-00a-paddle-cart-empty.png`, and set `PAD_PRE`. Add only `$PROBE_ID`, handle one-click, capture the unpopulated offered-gateway/$20 summary as `SLT-SYN-12-01-paddle-offered.png`, pay without capturing populated payment fields, record numeric `PAD_ORDER`, and capture safe receipt `-01a-paddle-receipt.png`. Resolve `PAD_SUB` through exact order `_subscription_ids` JSON, require reverse owner/product linkage and +1 count/order, then reconcile the complete four-message delta. If the documented Paddle prerequisite is unavailable, close only this leg `UNVERIFIED` with exact proof, do not invent IDs, and continue the hide/server/Stripe legs with `CONTROL_SUCCESS=0`; otherwise set it to 1. Prove carts empty and close the session.
3. Set `SAVE_PRE`; in `admin-SLT-SYN-12` enable the exact flex plan, capture `SLT-SYN-12-01b-flex-on.png`, verify all six keys, and require zero task mail through step 5 using a complete delta.
4. In authenticated `customer-stripe-SLT-SYN-12`, require both carts empty, add only `$PROBE_ID`, handle one-click, enumerate block gateway values and capture `SLT-SYN-12-02-block-gateways.png`; explicitly reopen `/slt-classic-checkout`, enumerate classic values, and capture `SLT-SYN-12-03-classic-gateways.png`. Require Paddle absent from both DOMs.
5. Record exact order/subscription counts and network buffer, force only the classic request's checked payment value to `arraysubs_paddle`, submit without card data, and capture `SLT-SYN-12-04-forced-paddle-error.png`. Require the verbatim server error, unchanged counts, no transaction, and exact rejected response; never use a recent Orders view as proof.
6. Set `STRIPE_PRE`, reload classic checkout, select Stripe, capture the unpopulated $20 summary, fill the hosted card without capturing it, pay, record numeric `STRIPE_ORDER`, and capture safe receipt `SLT-SYN-12-04a-stripe-receipt.png`. Resolve `STRIPE_SUB` through the strict order relationship; require reverse owner/product linkage and cumulative `SUBCOUNT_AFTER == SUBCOUNT_BEFORE+CONTROL_SUCCESS+1` (same for orders). Reconcile the complete four-message delta, dump exact sync/date metas, capture them/admin schedule as `SLT-SYN-12-05-stripe-meta.png`, and publish both available subs' exact action IDs/gates/deadlines plus Shop Access proof to the registry/D05 report.
7. Prove both carts empty, capture `SLT-SYN-12-06-cart-empty-after.png`, close only `customer-stripe-SLT-SYN-12` and `admin-SLT-SYN-12`. If any live assertion fails, create a standalone `issues/SLT-SYN-12-<concise-slug>.md` (never a kanban bug card) with task/stage/plan, user/product/order/subscription/action IDs, login/email/role, exact URLs/sessions/gateways, reproduction, expected/actual, UI/option/meta/network/Mailpit/screenshot proof, and flex-off/other-gateway counterexample. Independently review all available/UNVERIFIED evidence and future handoffs, move the card through `review` to `done`, and ensure Review returns to zero.

## Expected results
1. Step 2 (flex OFF): Paddle IS offered, purchase succeeds at **$20.00**, no `_renewal_sync_*` meta, `_next_payment_date` = checkout + 5 days. Paddle works here.
2. Step 4 (flex ON), both checkouts: `arraysubs_paddle` is **absent from the DOM**, not merely hidden — the value list holds `stripe` (plus `bacs`/`cheque` if enabled), never `arraysubs_paddle`.
3. Step 5: refused with **"Renewal sync is not available for the selected payment method. Choose a supported payment method to continue."**; no order, sub or Paddle transaction. So the answer is **both** — hide and block.
4. Step 6: **$20.00**; `_renewal_sync_enabled=yes`, mode `full` (a `day` period always resolves to day 1); `_next_payment_date` = **`2026-08-11 18:00:00` UTC** = 2026-08-12 00:00 site, a midnight boundary unlike result 1's anniversary. No `Renewal Sync signup charge was … but … expected` note.

## Emails expected
| # | Email | Trigger point | Recipient | Subject contains | Verify with |
|---|---|---|---|---|---|
| 1 | WC customer paid-order + WC New order + `new_subscription` + `admin_new_subscription` per successful purchase | steps 2, 6 | customers/admin | exact order/subscription | separate complete `PAD_PRE`/`STRIPE_PRE` deltas; four exact IDs each |
| 2 | NONE, steps 3-5 | save, hide, blocked submit | — | — | complete delta after `SAVE_PRE`; zero task-attributable mail, unrelated shared-site mail classified separately |

## Evidence to capture
- `SLT-SYN-12-01-paddle-offered.png`, `-02-block-gateways.png`, `-03-classic-gateways.png`, `-04-forced-paddle-error.png`, `-05-stripe-meta.png`
- Eval'd gateway arrays; count progression and numeric user/probe/order/sub/action IDs with bidirectional linkage; raw before/after Shop Access diff; safe receipts; both dates/action handoffs; carts, exact Mailpit IDs, console/network, sessions/review proof.

## Pass criteria
- [ ] Paddle offered and purchasable while flex is OFF
- [ ] `arraysubs_paddle` absent from the DOM on block and classic when flex is ON
- [ ] Forced `arraysubs_paddle` blocked with the verbatim message, zero orders
- [ ] Stripe leg `_renewal_sync_enabled=yes`, mode `full`, next payment `2026-08-11 18:00:00`
- [ ] Matrix filed (Stripe YES, manual YES, Paddle NO); no mismatch note; only the listed mails
- [ ] Probe excluded exactly once from Shop Access before storefront access; both sessions end with empty cart/persistent-cart state and are closed
- [ ] Exact successful-purchase counts/mail sets and forced-submit zero delta proved; standalone findings only; final evidence reviewed to done

## Isolation / teardown
- New artifacts: 1 `SLT ` product, 2 subs, 2 orders — ids to the registry for 99B. Both carts and persistent-cart metas emptied; exact sessions closed.
- The probe's flex meta is mutated once, at step 3, before any flex-synced sub exists on it; the step-2 Paddle sub was bought while flex was off, so its schedule is unaffected. Never re-toggle it. The only global mutation is appending the task-owned product ID to the preserved Shop Access exclusion list; SETUP-99A restores the exact pre-window snapshot. No gateway toggle changed.
- Handoff: Stripe leg renews 2026-08-12 midnight+spread, Paddle on its anniversary; D10/D11 watch expects both.

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

[[2026-08-06]] Thu 21:29
Preflight 2026-08-06: buyers remain usable for the authored cross-account probe. slt-paddle user 352 currently owns sub 12639 and orders 12891/12629; slt-flex2 user 354 currently owns sub 12172 and orders 12714/12162. The probe slug slt-flex-gateway-probe does not already exist. Shop Access rule rule_1784662676378_maa3te08s exists with 27 exclusion product ids and will need a single-ID append when the probe is created. Persistent carts for both users are still present but empty.

[[2026-08-06]] Thu 22:11
Additional preflight on Thursday, August 6, 2026. This card remains future-dated for Friday, August 7, 2026 and was not started early. Live re-check confirms the authored buyer accounts still match the intended isolation model:
- `slt-paddle` / user `352` / `slt-paddle@example.test` currently owns exactly `1` subscription and `2` shop orders; `_woocommerce_persistent_cart_1` is present but its `cart` array is empty.
- `slt-flex2` / user `354` / `slt-flex2@example.test` currently owns exactly `1` subscription and `2` shop orders; `_woocommerce_persistent_cart_1` is present but its `cart` array is empty.
- Probe slug `slt-flex-gateway-probe` still resolves to zero existing products, so the D5 run should not hit a slug collision before product creation.
- No additional source block is visible from this preflight. Unlike the ladder-switch chain, this card still appears runnable once Friday, August 7, 2026 begins; keep it in `todo` until that date rather than opening it early on D4.

[[2026-08-07]] Fri 23:05
Final D05 window closed UNVERIFIED. At 23:04:51 site only 55 minutes remained for an authored two-hour sequence whose date-sensitive checkout, product/Shop Access mutations, and teardown cannot safely spill past midnight. The probe slug remains absent; users 352/354 and subscriptions 12639/12172 remain intact; no product, rule, flex meta, cart, checkout, order, subscription, action, user, or mail mutation was started. This is a scheduling limitation, not a product defect. Evidence: `/home/server-manager/slt-evidence/D05-night-source-block-and-window-close.txt`.

[[2026-08-12]] Wed 17:59
D10 watch verdict: UNVERIFIED — no source fixture. Issue: issues/SLT-SYN-12-d10-watch-expects-missing-source.md

[[2026-08-14]] Fri
## Downstream oracle correction

The missing-source outcome remains `UNVERIFIED`; it is not converted into a product pass and no late fixture was created. Task 88 is now named in the authoritative source-outcome overlays. The D7 reminder, D10 Stripe/Paddle renewal, calendar handoff, and D13 teardown membership are all conditional on relationship-resolved numeric task-88 fixtures, so this run's absent product/order/subscription/action chain is omitted without a false failure or fabricated cleanup target. Resolution evidence is in `issues/done-medium-SLT-SYN-12-d10-watch-expects-missing-source.md`.
