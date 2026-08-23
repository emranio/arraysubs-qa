---
id: 88
title: 'Gateway gating for flexible sync: Paddle hidden from the DOM and blocked at submit, Stripe syncs to the midnight boundary'
status: todo
priority: critical
created: 2026-08-22T19:10:00+02:00
updated: 2026-08-22T19:10:00+02:00
tags:
    - cycle-2
    - granular
    - renewal-sync
    - day-05
due: "2026-08-28"
estimate: 2h
depends_on:
    - 10
    - 11
    - 26
    - 23
claimed_by: plume-coal
class: standard
---

> **SLT-SYN-12** · group `sync` · scheduled **D05** (2026-08-28)

> Read `README.md` (environment + isolation contract), `calendar.md` (this day's exact
> ordering — it is binding, not advisory) and `plan-audit.md` before starting.

## Objective
On one product whose flex-sync flag is the only variable, establish which gateways permit Flexible Renewal Sync and how unsupported ones are refused: Paddle gone from the DOM (not CSS-hidden) on a sync-eligible cart, the server validator also blocking a forced `arraysubs_paddle`, Stripe producing a synced sub on the midnight boundary.

## Scope
- Gateway: both (Stripe test, Paddle sandbox)
- Checkout: both (`/checkout/`, `/slt2-classic-checkout`)
- Account: existing `slt2-paddle`, `slt2-flex2`
- Plugins: both

## Preconditions
- SLT-SETUP-01, -02 (sync OFF), -05, SLT-PROD-16 done. Buy after 12:00.
- Contract: `arraysubs_is_renewal_sync_supported_gateway()` is true for `stripe` and manual ids, false for `arraysubs_paddle`; `maybeHideUnsupportedRenewalSyncGateways()` strips them and `validateRenewalSyncGatewaySupport()`/`…StoreApi()` reject at submit.
- Creates one probe; does NOT repeat SLT-SETUP-05's `SLT2 Flex Daily Next Cycle` check. `maybeHide…` returns all gateways if hiding leaves zero — do not provoke that.

## Test data
| Item | Value |
|---|---|
| Probe | `SLT2 Flex Gateway Probe` / `slt2-flex-gateway-probe`, Simple, Virtual, day/5, $20.00 |
| Flex plan (phase 2) | 3 segments active, seg1_end 1, seg2_end 3 -> `1 / 2-3 / 4-5` |
| Buyers / cards | Paddle `slt2-paddle`, Stripe `slt2-flex2` (distinct products, no auto-migrate); `4242…4242` |

## Steps
1. Resolve numeric `PADDLE_USER`/`STRIPE_USER`, record `SUBCOUNT_BEFORE`/`ORDERCOUNT_BEFORE`, and set `M0=$(mailpit-agent latest-id)`. In `admin-SLT-SYN-12`, create the probe with flex unticked, record strict numeric `PROBE_ID`, and capture its panel as `SLT-SYN-12-00-flex-off.png`. Save the exact raw Shop Access option before/after appending only `$PROBE_ID`; diff it, require all prior fields unchanged and the new ID once, then reconcile `M0` with zero task-attributable mail before any storefront request.
2. Paddle control in authenticated `customer-paddle-SLT-SYN-12`: require both carts empty, capture the before state, and buy the flex-off control through Paddle. Resolve exact `PAD_ORDER`/`PAD_SUB`, require reverse ownership and reconcile the full mail/count delta. If Paddle is unavailable, preserve safe Stripe evidence, update the upstream QA issue and leave this card blocked; do not invent IDs.
3. Set `SAVE_PRE`; in `admin-SLT-SYN-12` enable the exact flex plan, capture `SLT-SYN-12-01b-flex-on.png`, verify all six keys, and require zero task mail through step 5 using a complete delta.
4. In authenticated `customer-stripe-SLT-SYN-12`, require both carts empty, add only `$PROBE_ID`, handle one-click, enumerate block gateway values and capture `SLT-SYN-12-02-block-gateways.png`; explicitly reopen `/slt2-classic-checkout`, enumerate classic values, and capture `SLT-SYN-12-03-classic-gateways.png`. Require Paddle absent from both DOMs.
5. Record exact order/subscription counts and network buffer, force only the classic request's checked payment value to `arraysubs_paddle`, submit without card data, and capture `SLT-SYN-12-04-forced-paddle-error.png`. Require the verbatim server error, unchanged counts, no transaction, and exact rejected response; never use a recent Orders view as proof.
6. Set `STRIPE_PRE`, reload classic checkout, select Stripe, capture the unpopulated $20 summary, fill the hosted card without capturing it, pay, record numeric `STRIPE_ORDER`, and capture safe receipt `SLT-SYN-12-04a-stripe-receipt.png`. Resolve `STRIPE_SUB` through the strict order relationship; require reverse owner/product linkage and cumulative `SUBCOUNT_AFTER == SUBCOUNT_BEFORE+CONTROL_SUCCESS+1` (same for orders). Reconcile the complete four-message delta, dump exact sync/date metas, capture them/admin schedule as `SLT-SYN-12-05-stripe-meta.png`, and publish both available subs' exact action IDs/gates/deadlines plus Shop Access proof to the registry/D05 report.
7. Prove both carts empty, capture the after state, and close exact sessions. Any live failure creates/updates the mandatory `qa/issues/` kanban card with all required task, fixture, user, route and proof fields. Mark done only after the Paddle flex-off control and all Stripe flex-on hide/server-enforcement assertions pass.

## Expected results
1. Step 2 (flex OFF): Paddle IS offered, purchase succeeds at **$20.00**, no `_renewal_sync_*` meta, `_next_payment_date` = checkout + 5 days. Paddle works here.
2. Step 4 (flex ON), both checkouts: `arraysubs_paddle` is **absent from the DOM**, not merely hidden — the value list holds `stripe` (plus `bacs`/`cheque` if enabled), never `arraysubs_paddle`.
3. Step 5: refused with **"Renewal sync is not available for the selected payment method. Choose a supported payment method to continue."**; no order, sub or Paddle transaction. So the answer is **both** — hide and block.
4. Step 6: **$20.00**; `_renewal_sync_enabled=yes`, mode `full` (a `day` period always resolves to day 1); `_next_payment_date` = **`2026-09-01 18:00:00` UTC** = 2026-09-02 00:00 site, a midnight boundary unlike result 1's anniversary. No `Renewal Sync signup charge was … but … expected` note.

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
- [ ] Stripe leg `_renewal_sync_enabled=yes`, mode `full`, next payment `2026-09-01 18:00:00`
- [ ] Matrix filed (Stripe YES, manual YES, Paddle NO); no mismatch note; only the listed mails
- [ ] Probe excluded exactly once from Shop Access before storefront access; both sessions end with empty cart/persistent-cart state and are closed
- [ ] Exact successful-purchase counts/mail sets and forced-submit zero delta proved; QA issue cards only; final evidence reviewed to done

## Isolation / teardown
- New artifacts: 1 `SLT ` product, 2 subs, 2 orders — ids to the registry for 99B. Both carts and persistent-cart metas emptied; exact sessions closed.
- The probe's flex meta is mutated once, at step 3, before any flex-synced sub exists on it; the step-2 Paddle sub was bought while flex was off, so its schedule is unaffected. Never re-toggle it. The only global mutation is appending the task-owned product ID to the preserved Shop Access exclusion list; SETUP-99A restores the exact pre-window snapshot. No gateway toggle changed.
- Handoff: Stripe leg renews 2026-09-02 midnight+spread, Paddle on its anniversary; D10/D11 watch expects both.

---

### Fresh-cycle validation contract

- Re-derive every ID, count, option value, gateway capability, scheduler timestamp, and email baseline on this run; no prior-cycle result is evidence.
- Create and mutate only registered `SLT2 ` / `slt2-*` fixtures. Legacy `SLT` and all non-SLT2 data are read-only controls.
- Automatic-gateway scope is Stripe and Paddle only. Stripe is the primary path; run Paddle parity wherever Paddle supports the behavior. Do not test or configure PayPal or Mollie.
- ArraySubs core must own its Stripe/Paddle integration, renewal, retry, webhook, REST, refund, and customer-payment services with Pro inactive; vendor host classes retain their expected ownership.
- Browser-required assertions use Vercel `agent-browser` with isolated task/role sessions and current snapshot refs. WP-CLI always includes `--allow-root`.
- Update the lifecycle card, the matching `qa/progress/` card, and `qa/issues/` for every new regression. Evidence belongs to this fresh cycle only.
