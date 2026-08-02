---
id: 26
title: SLT-SETUP-05 Verify Paddle sandbox readiness and record the two-gateway capability matrix
status: todo
priority: high
created: 2026-08-02T03:43:05.1550051+02:00
updated: 2026-08-02T03:43:15.565271455+02:00
tags:
    - setup
    - day-01
    - has-conflicts
due: "2026-08-03"
estimate: 1h
depends_on:
    - 11
    - 23
class: standard
---

> **SLT-SETUP-05** · group `foundation` · scheduled **D01** (2026-08-03)

> Read `README.md` (environment + isolation contract), `calendar.md` (this day's exact
> ordering — it is binding, not advisory) and `plan-audit.md` before starting.

### ⚠ Conflict resolutions that apply to this task

**`unrated` · shared-global-setting** — with `SLT-SYN-04`, `SLT-SETUP-02`, `SLT-PROD-12`, `SLT-PROD-13`, `SLT-PROD-14`, `SLT-PROD-15`

- *Problem:* renewals.sync_to_billing_cycle is written by two tasks on the same authored day. SLT-SETUP-02 turns it OFF as a declared window-wide baseline; SLT-SYN-04 turns it back ON (steps 3-15) and only restores it at step 16. Every other day-0 task asserts the OFF baseline while sync is ON: SLT-SETUP-05 pass criterion 'Stripe AND Paddle both offered for SLT Daily Core' is guaranteed to FAIL because maybeHideUnsupportedRenewalSyncGateways() hides arraysubs_paddle on every non-trial, non-lifetime subscription cart once the global switch is on; the guest cart previews in SLT-PROD-01/02/04/09/12/13/14/15 would read altered first-charge amounts and midnight-boundary next-payment dates; and any checkout completed inside the ON window permanently writes _renewal_sync_enabled=yes plus the five _renewal_sync_* metas onto that subscription, which cannot be undone by restoring the setting. Secondary hazard: turning sync ON re-exposes the First Charge select that SLT-SETUP-02 step 3 deliberately never touched, so a careless Save on the General page can write sync_first_charge_mode explicitly.
- *Required fix:* Make SLT-SYN-04 the sole writer of sync_to_billing_cycle and give it an exclusive, fixed bracket: run it on D3 (2026-08-04) 09:00-11:00 site time only. No other SLT task may add to cart, reach checkout, place an order, save a product, or drain Action Scheduler inside that bracket. SLT-SYN-04 must (a) capture the jq settings dump before flipping, (b) never click the First Charge select, (c) restore the switch and prove the jq diff is empty before the bracket is released, (d) post the 'bracket closed' confirmation to the registry page. Schedule SLT-SETUP-05 on D1, two days ahead of the bracket, so its two-gateway assertion runs against the true OFF baseline.

**`unrated` · dependency-inversion** — with `SLT-PROD-14`

- *Problem:* SLT-SETUP-05 declares deps SLT-SETUP-02,SLT-PROD-16 but its step 7, expected result 4 and pass criterion 'Paddle hidden for SLT Flex Daily Next Cycle' all require the product SLT Flex Daily Next Cycle, which is created only by SLT-PROD-14. Run as authored (both on d0, no ordering edge) SLT-SETUP-05 can start before that product exists and its third gateway probe is unrunnable.
- *Required fix:* Add SLT-PROD-14 to SLT-SETUP-05's dependency list (deps become SLT-SETUP-02, SLT-PROD-16, SLT-PROD-14) and schedule both on D1 with SLT-PROD-14 strictly before SLT-SETUP-05.

**`unrated` · impossible-timing** — with `SLT-SETUP-01`, `SLT-SETUP-02`, `SLT-SETUP-03`, `SLT-SETUP-04`, `SLT-PROD-01`, `SLT-PROD-02`

- *Problem:* 25 of the 26 authored tasks are stamped day 0. Sixteen of them are multi-step browser-driven product builds (each: open post-new, set 6-15 fields across 3 tabs, publish, reload, verify, meta-dump, guest front-end check, registry append), two are long audit tasks with 15-20 probe steps each (SLT-SYN-01, SLT-SYN-02), one is a live Stripe purchase with a settings flip and restore (SLT-SYN-04), and on top of that D0 is also where the plan's isolation notes demand the D0 purchases of SLT-PROD-01/02/03/06/07/10/12/13/14/15/16. There is a hard serial chain inside the day as well: SETUP-01 -> SETUP-02 -> PROD-16 -> SETUP-05 -> SYN-04, and SETUP-01 -> SETUP-02 -> PROD-14 -> SYN-03 -> SYN-04. This is not executable in one day, and it wastes D4-D9 which sit empty.
- *Required fix:* Adopt the rebalanced D0-D10 calendar in schedule_notes. Only products with a genuinely date-bound purchase stay on D0 (SLT-PROD-01, 06, 07, 13 plus the three foundation tasks); everything else is spread D1-D5 with its purchase deadline recomputed so it still fits the window. Peak day drops from 25 tasks (~30h) to 7 tasks (~7h) and the median day is ~4.5h, inside the 1.6x band.

**`unrated` · same-account-collision** — with `SLT-PROD-02`, `SLT-PROD-04`, `SLT-PROD-09`, `SLT-PROD-10`, `SLT-PROD-12`, `SLT-PROD-13`

- *Problem:* Ten tasks perform cart previews as `--session guest` and each one ends with 'empty the cart'. agent-browser sessions are keyed by name, so every one of these tasks shares ONE cart. Run on the same day (as authored, all on d0) they interleave: a leftover subscription line from SLT-PROD-04 makes SLT-PROD-09's probe-B multi-subscription refusal fire for the wrong reason; SLT-PROD-10's box add-to-cart explicitly EMPTIES the cart first, silently wiping another task's staged preview; SLT-SETUP-05's gateway accordion reading can be taken against a cart that still holds a flex product, which hides Paddle and produces a false failure of its own pass criterion.
- *Required fix:* Give every task its own browser session name: `--session guest-SLT-PROD-04`, `--session guest-SLT-SETUP-05`, etc. Each cart-touching task must additionally assert the cart is EMPTY as its first action and empty it again as its last action, capturing both in evidence. Close only its own session (`agent-browser close --session <name>`); reserve `agent-browser close --all` for the last task of the day.

**`unrated` · duplicate-coverage** — with `SLT-PROD-14`

- *Problem:* The assertion 'Paddle is hidden from checkout for SLT Flex Daily Next Cycle, Stripe is offered' is executed twice: SLT-SETUP-05 step 7 / expected result 4 / pass criterion 3, and SLT-PROD-14 step 10 / expected result 7 / pass criterion 5. Both drive a guest cart, both screenshot the accordion (SLT-SETUP-05-04-checkout-flex-nextcycle-gateways.png and SLT-PROD-14-05-paddle-absent.png). It is the same code path, the same product, the same day, and it also doubles the guest-cart collision surface described above.
- *Required fix:* Keep the probe in SLT-SETUP-05, which owns the gateway capability matrix. Reduce SLT-PROD-14 step 10 to a cart-note check only (the 'covers the full billing cycle starting 4 August, 2026' bonus-access string) and replace its gateway pass criterion with 'gateway gating verified by SLT-SETUP-05; cite that task's evidence id'. Saves one full guest-checkout cycle on the busiest day.

**`unrated` · duplicate-coverage** — with `SLT-SETUP-01`, `SLT-SYN-04`, `SLT-PROD-02`, `SLT-PROD-04`, `SLT-PROD-09`

- *Problem:* SLT-SETUP-01 builds the classic cart/checkout harness pages (slt-classic-cart, slt-classic-checkout) and binds them on every task whose Scope says 'Checkout: classic' or 'both' - but not a single authored task actually visits them. SLT-SETUP-05 uses /checkout/ (block), SLT-SYN-04's Scope says 'Checkout: block' and it uses /checkout/, and every cart preview (SLT-PROD-02/04/09/12/13/14, SLT-SYN-03) uses /cart/ (block). The 'Checkout: both' scope declarations are therefore unbacked, and two published pages are created and torn down without being exercised.
- *Required fix:* Assign the classic surface explicitly rather than declaratively: route SLT-SYN-04's purchase through /slt-classic-checkout (it is a plain Stripe purchase and is the cleanest classic candidate), route SLT-PROD-04's qty-1/qty-2 signup-fee cart probes through /slt-classic-cart (fee rendering differs between block and classic), and change every remaining 'Checkout: both' to the surface actually used. Never repoint the site's real Cart/Checkout pages - the harness pages are the only permitted classic surface.

**`high` · dependency-inversion (product creation after first consumer)** — with `SLT-PROD-04`, `SLT-PROD-05`, `SLT-PROD-08`, `SLT-PROD-09`, `SLT-PROD-10`, `SLT-PROD-11`

- *Problem:* The corrected calendar in plan-audit places several catalog tasks later than the first new-index task that depends on them. SLT-SETUP-04 (coupons) is D3 but SLT-CPN-01/02 need it on D1 18:00-19:00. SLT-PROD-05 is D3 but SLT-LIFE-05 buys it on D1. SLT-PROD-16 is D1 but SLT-DUN-01 (corrected to D2 13:00) and SLT-CHK-04 (D2) need it, and SLT-MYA-05 needs it on D2 morning. SLT-PROD-09 is D5 but SLT-CPN-04 (D3) and SLT-CHK-12 (D5) depend on it. SLT-PROD-10 and SLT-PROD-11 are D4 but SLT-CHK-13 (D4), SLT-CHK-10 (D5) and SLT-SW-09 (D4, which explicitly says PROD-11 must be done 'before this task starts on D4') need them earlier in the day or before. SLT-PROD-08 is D5 but SLT-CHK-11 buys its variations on D5. SLT-PROD-15 is D2 and SLT-SYN-13 buys its variations on D2 - correct only if SYN-02's audit sits strictly between them.
- *Required fix:* Adopt the rebalanced calendar in this report: SETUP-04 and PROD-05 to D1 morning; PROD-16 to D1 morning (ahead of SETUP-05, which also gains PROD-14 as a dependency per audit C03); PROD-02/03/09/15 and SYN-02 to D2 morning; PROD-04/10/11 to D3 after the SYN-04 bracket closes; PROD-08 to D4 morning. Add an explicit intra-day ordering line to every day's calendar row ('creations and audits before 12:00, purchases after 12:00') and make it a pass criterion that each consuming task quotes the creating task's registry entry.

---
## Objective
Prove that, with global renewal sync now off, Paddle is actually selectable at checkout, that `PaddleProductSync` pushed the SLT Paddle product into the Paddle sandbox catalogue, and publish the capability matrix that tells every later author which gateway can legitimately be used for which behaviour.

## Scope
- Gateway: both
- Checkout: block
- Account: N/A
- Plugins: pro-required

## Preconditions
- SLT-SETUP-02 complete (global sync OFF — without it Paddle is hidden for every sync-eligible subscription product).
- SLT-PROD-16 complete (`SLT Paddle Daily` and `SLT Retry Daily` exist).
- Verified gateway config: Stripe enabled, `testmode: yes`, UPE/accordion, saved cards on; Paddle id `arraysubs_paddle`, enabled, `test_mode: yes`, sandbox api_key/client_token/seller_id/webhook_secret set.

## Test data
| Item | Value |
|---|---|
| Product | SLT Paddle Daily ($11.00, day/1), SLT Daily Core ($10.00, day/1) |
| Account | N/A (guest browse only, no order placed) |
| Coupon | N/A |
| Card | N/A — do NOT complete a purchase in this task |
| Amounts | none charged |

## Steps
1. Re-save `SLT Paddle Daily` once to trigger the sync: `agent-browser --session admin open "https://mirror-help.arrayhash.com/wp-admin/post.php?post=<SLT Paddle Daily ID>&action=edit"` -> click **Update** -> re-snapshot.
2. Verify the sync metas landed: `wp post meta list <ID> --keys=_arraysubs_gateway_paddle_product_id,_arraysubs_gateway_paddle_price_id,_arraysubs_gateway_paddle_synced_at --allow-root`.
3. If the price id is empty, check the log source: `wp option get woocommerce_arraysubs_paddle_settings --allow-root` and the WooCommerce log source `arraysubs_paddle_sync` at `/wp-admin/admin.php?page=wc-status&tab=logs`. Record the failure rather than fixing gateway credentials.
4. Add `SLT Daily Core` to a clean guest cart via the helper link and open block checkout: `agent-browser --session guest open "https://mirror-help.arrayhash.com/checkout/?add-to-cart=<SLT Daily Core ID>"` -> `agent-browser --session guest snapshot -i`.
5. Read the payment-method accordion from the snapshot and record which gateways are offered. This is the direct proof of the SLT-SETUP-02 rationale.
6. Empty the cart, then repeat step 4 with `SLT Paddle Daily`.
7. Empty the cart, then repeat with `SLT Flex Daily Next Cycle` (from SLT-PROD-14) — this one IS sync-eligible via the per-product pro override, so Paddle must be hidden here.
8. Do not place any order. `agent-browser --session guest open "https://mirror-help.arrayhash.com/cart/"` and remove all items; close with `agent-browser close --all`.
9. Append the capability matrix (Expected results 5) to `slt-catalog-registry`.

## Expected results
1. `SLT Paddle Daily` has non-empty `_arraysubs_gateway_paddle_product_id` (`pro_...`) and `_arraysubs_gateway_paddle_price_id` (`pri_...`) plus a `_arraysubs_gateway_paddle_synced_at` timestamp.
2. Block checkout with `SLT Daily Core` offers BOTH `Stripe` and `Paddle` (proves the baseline change achieved its purpose).
3. Block checkout with `SLT Paddle Daily` offers both gateways.
4. Block checkout with `SLT Flex Daily Next Cycle` offers Stripe but NOT Paddle — `arraysubs_is_renewal_sync_supported_gateway('arraysubs_paddle')` is hard-coded false and `maybeHideUnsupportedRenewalSyncGateways()` removes it while a sync-eligible item is in the cart.
5. The recorded matrix reads: Stripe — automatic payments yes, SCA yes, renewal sync yes, early renew yes, trials yes, recurring coupon discounts yes. Paddle — automatic payments yes, `sca: false`, renewal sync NO (gateway hidden on sync carts), `early_renewal: false` (Paddle owns `next_billed_at`, so the Renew Early button stays hidden even with the baseline toggle on), trials yes, `different_billing_cycles: false` (never put two different-cycle Paddle subscriptions in one cart), `retention_amount_update: false`.
6. Cart is empty at the end; no order, no subscription, no customer created.

## Emails expected
| # | Email | Trigger point | Recipient | Subject contains | Verify with |
|---|---|---|---|---|---|
| 1 | NONE EXPECTED | Whole task (no order placed) | — | — | Capture `mailpit-agent latest-id` at step 1; it must be unchanged at step 8 |

## Evidence to capture
- Screenshots: `SLT-SETUP-05-01-paddle-sync-meta.png`, `SLT-SETUP-05-02-checkout-daily-core-gateways.png`, `SLT-SETUP-05-03-checkout-paddle-daily-gateways.png`, `SLT-SETUP-05-04-checkout-flex-nextcycle-gateways.png`.
- `wp post meta list` output for the three Paddle metas.
- Any `arraysubs_paddle_sync` log lines; console/network errors from the Paddle overlay script.

## Pass criteria
- [ ] Paddle product id and price id present on SLT Paddle Daily
- [ ] Stripe AND Paddle both offered for SLT Daily Core
- [ ] Paddle hidden for SLT Flex Daily Next Cycle
- [ ] Capability matrix recorded in the registry
- [ ] No order/subscription/customer created; zero mail

## Isolation / teardown
- State handoff: the capability matrix is binding. No SLT task may schedule Paddle for early renew, SCA, flexible-renewal-sync, or a mixed-cycle multi-subscription cart; those combinations are gateway-unsupported by design and must be filed as expected negatives, not bugs.
- Restores: cart emptied; nothing else changed. Paddle catalogue objects created in the sandbox are left in place (sandbox-only, no cleanup path from WP).

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
