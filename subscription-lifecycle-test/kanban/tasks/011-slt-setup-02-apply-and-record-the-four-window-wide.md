---
id: 11
title: SLT-SETUP-02 Apply and record the four window-wide baseline setting changes
status: todo
priority: critical
created: 2026-08-02T03:43:03.929091194+02:00
updated: 2026-08-02T03:43:13.719387688+02:00
tags:
    - setup
    - day-00
    - has-conflicts
due: "2026-08-02"
estimate: 45m
depends_on:
    - 10
class: standard
---

> **SLT-SETUP-02** · group `foundation` · scheduled **D00** (2026-08-02)

> Read `README.md` (environment + isolation contract), `calendar.md` (this day's exact
> ordering — it is binding, not advisory) and `plan-audit.md` before starting.

### ⚠ Conflict resolutions that apply to this task

**`unrated` · shared-global-setting** — with `SLT-SYN-04`, `SLT-SETUP-05`, `SLT-PROD-12`, `SLT-PROD-13`, `SLT-PROD-14`, `SLT-PROD-15`

- *Problem:* renewals.sync_to_billing_cycle is written by two tasks on the same authored day. SLT-SETUP-02 turns it OFF as a declared window-wide baseline; SLT-SYN-04 turns it back ON (steps 3-15) and only restores it at step 16. Every other day-0 task asserts the OFF baseline while sync is ON: SLT-SETUP-05 pass criterion 'Stripe AND Paddle both offered for SLT Daily Core' is guaranteed to FAIL because maybeHideUnsupportedRenewalSyncGateways() hides arraysubs_paddle on every non-trial, non-lifetime subscription cart once the global switch is on; the guest cart previews in SLT-PROD-01/02/04/09/12/13/14/15 would read altered first-charge amounts and midnight-boundary next-payment dates; and any checkout completed inside the ON window permanently writes _renewal_sync_enabled=yes plus the five _renewal_sync_* metas onto that subscription, which cannot be undone by restoring the setting. Secondary hazard: turning sync ON re-exposes the First Charge select that SLT-SETUP-02 step 3 deliberately never touched, so a careless Save on the General page can write sync_first_charge_mode explicitly.
- *Required fix:* Make SLT-SYN-04 the sole writer of sync_to_billing_cycle and give it an exclusive, fixed bracket: run it on D3 (2026-08-04) 09:00-11:00 site time only. No other SLT task may add to cart, reach checkout, place an order, save a product, or drain Action Scheduler inside that bracket. SLT-SYN-04 must (a) capture the jq settings dump before flipping, (b) never click the First Charge select, (c) restore the switch and prove the jq diff is empty before the bracket is released, (d) post the 'bracket closed' confirmation to the registry page. Schedule SLT-SETUP-05 on D1, two days ahead of the bracket, so its two-gateway assertion runs against the true OFF baseline.

**`unrated` · impossible-timing** — with `SLT-SETUP-01`, `SLT-SETUP-03`, `SLT-SETUP-04`, `SLT-SETUP-05`, `SLT-PROD-01`, `SLT-PROD-02`

- *Problem:* 25 of the 26 authored tasks are stamped day 0. Sixteen of them are multi-step browser-driven product builds (each: open post-new, set 6-15 fields across 3 tabs, publish, reload, verify, meta-dump, guest front-end check, registry append), two are long audit tasks with 15-20 probe steps each (SLT-SYN-01, SLT-SYN-02), one is a live Stripe purchase with a settings flip and restore (SLT-SYN-04), and on top of that D0 is also where the plan's isolation notes demand the D0 purchases of SLT-PROD-01/02/03/06/07/10/12/13/14/15/16. There is a hard serial chain inside the day as well: SETUP-01 -> SETUP-02 -> PROD-16 -> SETUP-05 -> SYN-04, and SETUP-01 -> SETUP-02 -> PROD-14 -> SYN-03 -> SYN-04. This is not executable in one day, and it wastes D4-D9 which sit empty.
- *Required fix:* Adopt the rebalanced D0-D10 calendar in schedule_notes. Only products with a genuinely date-bound purchase stay on D0 (SLT-PROD-01, 06, 07, 13 plus the three foundation tasks); everything else is spread D1-D5 with its purchase deadline recomputed so it still fits the window. Peak day drops from 25 tasks (~30h) to 7 tasks (~7h) and the median day is ~4.5h, inside the 1.6x band.

**`unrated` · shared-global-setting** — with `SLT-SETUP-99`, `SLT-PROD-16`, `SLT-SYN-04`

- *Problem:* SLT-SETUP-02 flips five booleans ON for the whole window (allow_early_renew, allow_reactivation, pause_subscription.enabled, pause_subscription.customer_can_pause; plus sync OFF) and declares them frozen. Nothing in the plan republishes that baseline where a my-account or customer-action task will see it, so any later task auditing the my-account subscription screen against the site's shipped defaults will file Renew Early / Reactivate / Pause buttons as unexpected UI. The reverse trap also exists: cancellation.retention_offers_enabled has pause/skip OFF while the pause FEATURE is now ON, so the retention modal legitimately shows no pause offer even though pausing works - easy to misfile as a defect. SLT-PROD-16 already relies on the baseline being ON to assert Paddle's Renew Early button stays hidden.
- *Required fix:* SLT-SETUP-02 must append a 'WINDOW BASELINE (frozen)' table to slt-catalog-registry listing all five booleans with prior value / window value / restoring task, and every customer-facing audit task must quote that table in its preconditions instead of the shipped defaults. Add a pass criterion to SLT-SETUP-02: the registry table exists. SLT-SETUP-99A restores all five and proves it with the empty jq diff.

---
## Objective
Flip exactly four global settings that the 10-day plan depends on, record each prior value verbatim, and declare them frozen for the window so no other task touches them. The most consequential is turning global renewal sync OFF: with it ON, `arraysubs_subscription_data_supports_renewal_sync()` returns true for every non-trial, non-lifetime subscription product, and `CheckoutHelpersTrait::maybeHideUnsupportedRenewalSyncGateways()` then removes Paddle from checkout for all of them — making Paddle coverage impossible.

## Scope
- Gateway: both
- Checkout: both
- Account: N/A
- Plugins: both

## Preconditions
- SLT-SETUP-01 complete (pre-window `arraysubs_settings` dump exists).
- Code-verified basis for each change is stated in Steps; do not re-derive.

## Test data
| Item | Value |
|---|---|
| Product | N/A |
| Account | admin |
| Coupon | N/A |
| Card | N/A |
| Amounts | N/A |

## Steps
1. Record priors exactly (they are already known; confirm them): `renewals.sync_to_billing_cycle = true`, `renewals.sync_first_charge_mode = "full"`, `customer_actions.allow_early_renew = false`, `customer_actions.allow_reactivation = false`, `pause_subscription.enabled = false`, `pause_subscription.customer_can_pause = false`. Write these into this task's Notes and into `/home/server-manager/slt-evidence/SLT-SETUP-02-priors.txt`.
2. `agent-browser --session admin open "https://mirror-help.arrayhash.com/wp-admin/admin.php?page=arraysubs-mainadmin#/settings/general"` -> `agent-browser --session admin snapshot -i`.
3. In the **Renewal Sync** card, switch **Sync Renewals to Next Billing Cycle** OFF. Do NOT touch the **First Charge** select (it hides when sync is off; its stored value `full` must remain untouched). Justification, code-verified: `arraysubs/src/functions/renewal-sync-helpers.php::arraysubs_is_renewal_sync_supported_gateway()` returns false for `arraysubs_paddle`, and `CheckoutHelpersTrait::maybeHideUnsupportedRenewalSyncGateways()` hides every unsupported gateway whenever the cart holds a sync-eligible item. Turning the global off also gives every non-flex SLT product deterministic anniversary renewals (checkout time + interval) instead of midnight-boundary renewals.
4. In the **Customer Actions** card, switch **Allow Early Renew** ON (pro EarlyRenew module is active; verified `arraysubs_has_module('EarlyRenew') === 1`).
5. In the same card, switch **Allow Reactivation** ON.
6. Save the General settings page and re-snapshot to confirm the save toast.
7. `agent-browser --session admin open "https://mirror-help.arrayhash.com/wp-admin/admin.php?page=arraysubs-mainadmin#/settings/skip-pause"` -> `snapshot -i`.
8. Switch **Enable Pause Subscription** ON, then switch **Allow Customers to Pause** ON. Leave **Maximum Pause Duration (Days)** = 30, **Maximum Pauses per Subscription** = 2, **Cooldown Between Pauses (Days)** = 0, **Require Pause Reason** = on, all at their stored values. Save.
9. Verify from WP root: `wp option get arraysubs_settings --allow-root | grep -o 'sync_to_billing_cycle";b:[01]'` and equivalent greps for `allow_early_renew`, `allow_reactivation`, `pause_subscription` `enabled` / `customer_can_pause`.
10. Dump the post-change blob: `wp option get arraysubs_settings --format=json --allow-root > /home/server-manager/slt-evidence/SLT-SETUP-02-arraysubs_settings-baseline.json`.

## Expected results
1. `renewals.sync_to_billing_cycle` is `false`; `renewals.sync_first_charge_mode` is still the string `full`.
2. `customer_actions.allow_early_renew` is `true`.
3. `customer_actions.allow_reactivation` is `true`.
4. `pause_subscription.enabled` is `true` and `pause_subscription.customer_can_pause` is `true`.
5. Every other key in `arraysubs_settings` is byte-identical to the SLT-SETUP-01 D0 dump — specifically unchanged: `multiple_subscriptions.allow_multiple_in_cart=false`, `multiple_subscriptions.allow_mixed_cart=true`, `trials.require_payment_method=true`, `renewals.grace_days_before_on_hold=1`, `renewals.grace_days_before_cancel=3`, `renewals.invoice_before_due_value=6`/`unit=hours`, all `plan_switching.*`, all `refunds.*`, all `emails.*`.
6. With sync off, checkout for a plain subscription product now offers both Stripe and Paddle.

## Emails expected
| # | Email | Trigger point | Recipient | Subject contains | Verify with |
|---|---|---|---|---|---|
| 1 | NONE EXPECTED | Settings save | — | — | Capture `mailpit-agent latest-id` before step 2; it must be unchanged after step 9 |

## Evidence to capture
- Screenshots: `SLT-SETUP-02-01-general-after.png`, `SLT-SETUP-02-02-skip-pause-after.png`.
- `SLT-SETUP-02-priors.txt` and `SLT-SETUP-02-arraysubs_settings-baseline.json`.
- The grep output proving each of the five booleans.

## Pass criteria
- [ ] sync_to_billing_cycle == false, sync_first_charge_mode still "full"
- [ ] allow_early_renew == true
- [ ] allow_reactivation == true
- [ ] pause_subscription.enabled == true and customer_can_pause == true
- [ ] No other setting differs from the D0 dump
- [ ] Priors recorded in Notes and on disk

## Isolation / teardown
- State handoff: these four changes are the WINDOW-WIDE BASELINE. Every other SLT task treats them as fixed and must NOT flip them. A task needing global sync ON (none is planned) must flip it and restore inside the same task.
- Consequence other authors must assume: non-flex SLT products bill on **anniversary** schedule (checkout timestamp + interval), NOT at site-local midnight. Flex-sync products still sync, because `ArraySubsPro\Features\FlexibleRenewalSync\Services\Hooks::filterSupportsRenewalSync()` grants support per-product regardless of the global switch — which is exactly what SLT-PROD-12/13/14/15 prove.
- Restores: SLT-SETUP-99 sets all four (five booleans) back to the recorded priors on day 10.

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
