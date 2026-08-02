---
id: 39
title: SLT-PROD-09 Create SLT Grouped Set, a grouped product with two subscription children
status: todo
priority: medium
created: 2026-08-02T03:43:06.254717075+02:00
updated: 2026-08-02T03:43:16.693646344+02:00
tags:
    - setup
    - products
    - day-02
    - has-conflicts
due: "2026-08-04"
estimate: 45m
depends_on:
    - 10
    - 5
    - 58
class: standard
---

> **SLT-PROD-09** · group `catalog` · scheduled **D02** (2026-08-04)

> Read `README.md` (environment + isolation contract), `calendar.md` (this day's exact
> ordering — it is binding, not advisory) and `plan-audit.md` before starting.

### ⚠ Conflict resolutions that apply to this task

**`unrated` · shared-global-setting** — with `SLT-SYN-04`, `SLT-SETUP-05`, `SLT-SETUP-02`, `SLT-PROD-12`, `SLT-PROD-13`, `SLT-PROD-14`

- *Problem:* renewals.sync_to_billing_cycle is written by two tasks on the same authored day. SLT-SETUP-02 turns it OFF as a declared window-wide baseline; SLT-SYN-04 turns it back ON (steps 3-15) and only restores it at step 16. Every other day-0 task asserts the OFF baseline while sync is ON: SLT-SETUP-05 pass criterion 'Stripe AND Paddle both offered for SLT Daily Core' is guaranteed to FAIL because maybeHideUnsupportedRenewalSyncGateways() hides arraysubs_paddle on every non-trial, non-lifetime subscription cart once the global switch is on; the guest cart previews in SLT-PROD-01/02/04/09/12/13/14/15 would read altered first-charge amounts and midnight-boundary next-payment dates; and any checkout completed inside the ON window permanently writes _renewal_sync_enabled=yes plus the five _renewal_sync_* metas onto that subscription, which cannot be undone by restoring the setting. Secondary hazard: turning sync ON re-exposes the First Charge select that SLT-SETUP-02 step 3 deliberately never touched, so a careless Save on the General page can write sync_first_charge_mode explicitly.
- *Required fix:* Make SLT-SYN-04 the sole writer of sync_to_billing_cycle and give it an exclusive, fixed bracket: run it on D3 (2026-08-04) 09:00-11:00 site time only. No other SLT task may add to cart, reach checkout, place an order, save a product, or drain Action Scheduler inside that bracket. SLT-SYN-04 must (a) capture the jq settings dump before flipping, (b) never click the First Charge select, (c) restore the switch and prove the jq diff is empty before the bracket is released, (d) post the 'bracket closed' confirmation to the registry page. Schedule SLT-SETUP-05 on D1, two days ahead of the bracket, so its two-gateway assertion runs against the true OFF baseline.

**`unrated` · impossible-timing** — with `SLT-SETUP-01`, `SLT-SETUP-02`, `SLT-SETUP-03`, `SLT-SETUP-04`, `SLT-SETUP-05`, `SLT-PROD-01`

- *Problem:* 25 of the 26 authored tasks are stamped day 0. Sixteen of them are multi-step browser-driven product builds (each: open post-new, set 6-15 fields across 3 tabs, publish, reload, verify, meta-dump, guest front-end check, registry append), two are long audit tasks with 15-20 probe steps each (SLT-SYN-01, SLT-SYN-02), one is a live Stripe purchase with a settings flip and restore (SLT-SYN-04), and on top of that D0 is also where the plan's isolation notes demand the D0 purchases of SLT-PROD-01/02/03/06/07/10/12/13/14/15/16. There is a hard serial chain inside the day as well: SETUP-01 -> SETUP-02 -> PROD-16 -> SETUP-05 -> SYN-04, and SETUP-01 -> SETUP-02 -> PROD-14 -> SYN-03 -> SYN-04. This is not executable in one day, and it wastes D4-D9 which sit empty.
- *Required fix:* Adopt the rebalanced D0-D10 calendar in schedule_notes. Only products with a genuinely date-bound purchase stay on D0 (SLT-PROD-01, 06, 07, 13 plus the three foundation tasks); everything else is spread D1-D5 with its purchase deadline recomputed so it still fits the window. Peak day drops from 25 tasks (~30h) to 7 tasks (~7h) and the median day is ~4.5h, inside the 1.6x band.

**`unrated` · same-account-collision** — with `SLT-SETUP-05`, `SLT-PROD-02`, `SLT-PROD-04`, `SLT-PROD-10`, `SLT-PROD-12`, `SLT-PROD-13`

- *Problem:* Ten tasks perform cart previews as `--session guest` and each one ends with 'empty the cart'. agent-browser sessions are keyed by name, so every one of these tasks shares ONE cart. Run on the same day (as authored, all on d0) they interleave: a leftover subscription line from SLT-PROD-04 makes SLT-PROD-09's probe-B multi-subscription refusal fire for the wrong reason; SLT-PROD-10's box add-to-cart explicitly EMPTIES the cart first, silently wiping another task's staged preview; SLT-SETUP-05's gateway accordion reading can be taken against a cart that still holds a flex product, which hides Paddle and produces a false failure of its own pass criterion.
- *Required fix:* Give every task its own browser session name: `--session guest-SLT-PROD-04`, `--session guest-SLT-SETUP-05`, etc. Each cart-touching task must additionally assert the cart is EMPTY as its first action and empty it again as its last action, capturing both in evidence. Close only its own session (`agent-browser close --session <name>`); reserve `agent-browser close --all` for the last task of the day.

**`unrated` · duplicate-coverage** — with `SLT-SETUP-01`, `SLT-SETUP-05`, `SLT-SYN-04`, `SLT-PROD-02`, `SLT-PROD-04`

- *Problem:* SLT-SETUP-01 builds the classic cart/checkout harness pages (slt-classic-cart, slt-classic-checkout) and binds them on every task whose Scope says 'Checkout: classic' or 'both' - but not a single authored task actually visits them. SLT-SETUP-05 uses /checkout/ (block), SLT-SYN-04's Scope says 'Checkout: block' and it uses /checkout/, and every cart preview (SLT-PROD-02/04/09/12/13/14, SLT-SYN-03) uses /cart/ (block). The 'Checkout: both' scope declarations are therefore unbacked, and two published pages are created and torn down without being exercised.
- *Required fix:* Assign the classic surface explicitly rather than declaratively: route SLT-SYN-04's purchase through /slt-classic-checkout (it is a plain Stripe purchase and is the cleanest classic candidate), route SLT-PROD-04's qty-1/qty-2 signup-fee cart probes through /slt-classic-cart (fee rendering differs between block and classic), and change every remaining 'Checkout: both' to the surface actually used. Never repoint the site's real Cart/Checkout pages - the harness pages are the only permitted classic surface.

**`high` · dependency-inversion (product creation after first consumer)** — with `SLT-PROD-04`, `SLT-PROD-05`, `SLT-PROD-08`, `SLT-PROD-10`, `SLT-PROD-11`, `SLT-PROD-15`

- *Problem:* The corrected calendar in plan-audit places several catalog tasks later than the first new-index task that depends on them. SLT-SETUP-04 (coupons) is D3 but SLT-CPN-01/02 need it on D1 18:00-19:00. SLT-PROD-05 is D3 but SLT-LIFE-05 buys it on D1. SLT-PROD-16 is D1 but SLT-DUN-01 (corrected to D2 13:00) and SLT-CHK-04 (D2) need it, and SLT-MYA-05 needs it on D2 morning. SLT-PROD-09 is D5 but SLT-CPN-04 (D3) and SLT-CHK-12 (D5) depend on it. SLT-PROD-10 and SLT-PROD-11 are D4 but SLT-CHK-13 (D4), SLT-CHK-10 (D5) and SLT-SW-09 (D4, which explicitly says PROD-11 must be done 'before this task starts on D4') need them earlier in the day or before. SLT-PROD-08 is D5 but SLT-CHK-11 buys its variations on D5. SLT-PROD-15 is D2 and SLT-SYN-13 buys its variations on D2 - correct only if SYN-02's audit sits strictly between them.
- *Required fix:* Adopt the rebalanced calendar in this report: SETUP-04 and PROD-05 to D1 morning; PROD-16 to D1 morning (ahead of SETUP-05, which also gains PROD-14 as a dependency per audit C03); PROD-02/03/09/15 and SYN-02 to D2 morning; PROD-04/10/11 to D3 after the SYN-04 bracket closes; PROD-08 to D4 morning. Add an explicit intra-day ordering line to every day's calendar row ('creations and audits before 12:00, purchases after 12:00') and make it a pass criterion that each consuming task quotes the creating task's registry entry.

---
## Objective
Provide the grouped product and pin the real behaviour: ArraySubs has NO grouped-product handling at all — the header **Subscription [ArraySubs]** checkbox is registered `show_if_simple show_if_variable`, so a grouped parent can never itself be a subscription. Its children are ordinary simple subscription products added to the cart individually, which means the grouped page is also the cleanest way to exercise `multiple_subscriptions.allow_multiple_in_cart = false` (baseline, unchanged), where adding two subscription children at once must be refused.

## Scope
- Gateway: both
- Checkout: both
- Account: N/A (creation only)
- Plugins: free-only

## Preconditions
- SLT-SETUP-01, SLT-PROD-01 (`SLT Daily Core`) and SLT-PROD-04 (`SLT Signup Fee Daily`) complete.
- This task also creates one plain non-subscription child so the mixed-cart rule (`allow_mixed_cart = true`, unchanged) is exercisable.

## Test data
| Item | Value |
|---|---|
| Product | SLT Grouped Set / slug `slt-grouped-set`; child `SLT Grouped Extra` / slug `slt-grouped-extra` ($3.00, non-subscription) |
| Account | N/A |
| Coupon | N/A |
| Card | N/A |
| Amounts | children: $10.00/day, $9.00/day + $15.00 fee, $3.00 one-off |

## Steps
1. Capture `mailpit-agent latest-id`.
2. Create the plain child first: `agent-browser --session admin open "https://mirror-help.arrayhash.com/wp-admin/post-new.php?post_type=product"`; title `SLT Grouped Extra`; **Simple product**; tick **Virtual**; do NOT tick **Subscription [ArraySubs]**; **Regular price ($)** `3.00`; slug `slt-grouped-extra`; Publish.
3. New product: title `SLT Grouped Set`. **Description**: `SLT window product. Grouped parent with subscription children. Delete on 2026-08-11.`
4. Set the product type dropdown to **Grouped product**. Confirm in the snapshot that the **Subscription [ArraySubs]** header checkbox and the Subscription tab are now HIDDEN — grouped parents are out of scope for the engine by design. Screenshot.
5. **Linked Products** tab -> **Grouped products** field: search and add `SLT Daily Core`, `SLT Signup Fee Daily`, `SLT Grouped Extra` (in that order).
6. Slug `slt-grouped-set`. Publish. Reload and confirm all three children persisted.
7. `wp post meta list <GROUPED_ID> --keys=_children --allow-root` and `wp post list --post_type=product --name=slt-grouped-set --field=ID --allow-root`.
8. As `--session guest`, open `https://mirror-help.arrayhash.com/slt-grouped-set` -> `snapshot -i`. Confirm each subscription child shows its own recurring price summary in the grouped table.
9. Add-to-cart probe A: set quantity 1 on `SLT Daily Core` only, submit, snapshot the cart. Empty the cart.
10. Add-to-cart probe B: set quantity 1 on BOTH `SLT Daily Core` and `SLT Signup Fee Daily`, submit, snapshot. With `allow_multiple_in_cart=false` the second subscription must be refused by `SubscriptionCheckout\Services\CartValidation`; record the exact notice text and which item won. Empty the cart.
11. Add-to-cart probe C: `SLT Daily Core` + `SLT Grouped Extra` (mixed cart) — permitted by `allow_mixed_cart=true`. Snapshot totals, then empty the cart.
12. Append the grouped ID and the extra child ID to the registry.

## Expected results
1. `SLT Grouped Extra` published as a plain simple product, `_is_subscription` absent, price $3.00.
2. `SLT Grouped Set` published as type `grouped` with `_children` containing exactly the three child IDs.
3. The grouped parent offers no subscription checkbox and no Subscription tab.
4. The grouped storefront table renders per-child recurring summaries for the two subscription children and a plain price for the extra.
5. Probe A: cart holds one subscription line, total $10.00 plus no fee (fee belongs to the other child).
6. Probe B: the cart ends up with only ONE subscription line and a WooCommerce notice explaining that multiple subscriptions are not allowed; record the verbatim text.
7. Probe C: cart holds one subscription line ($10.00) and one regular line ($3.00), total $13.00, no error.
8. Cart empty at the end; no order created.

## Emails expected
| # | Email | Trigger point | Recipient | Subject contains | Verify with |
|---|---|---|---|---|---|
| 1 | NONE EXPECTED | Product publish and all three cart probes | — | — | `mailpit-agent latest-id` unchanged from step 1 |

## Evidence to capture
- Screenshots: `SLT-PROD-09-01-grouped-no-subscription-controls.png`, `SLT-PROD-09-02-frontend-grouped-table.png`, `SLT-PROD-09-03-probe-b-multiple-refused.png`, `SLT-PROD-09-04-probe-c-mixed-cart.png`.
- Grouped ID, extra child ID, `_children` meta; verbatim refusal notice.

## Pass criteria
- [ ] Grouped parent published with exactly three children
- [ ] No subscription controls on the grouped parent (documented)
- [ ] Probe A single-subscription add works
- [ ] Probe B two-subscription add is refused with a captured notice
- [ ] Probe C mixed cart totals $13.00
- [ ] Zero mail, cart left empty

## Isolation / teardown
- State handoff: the refusal notice text from probe B is the reference string for any later multi-subscription-cart test. Do NOT flip `allow_multiple_in_cart` to change this — it is deliberately left at the site default so the refusal path stays testable all window.
- Restores: cart emptied. Grouped parent and `SLT Grouped Extra` deleted by SLT-SETUP-99; the two subscription children are owned by SLT-PROD-01/04.

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
