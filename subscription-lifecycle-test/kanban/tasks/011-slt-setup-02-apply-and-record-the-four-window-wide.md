---
id: 11
title: SLT-SETUP-02 Apply and record the four window-wide baseline setting changes
status: done
priority: critical
created: 2026-08-02T03:43:03.929091194+02:00
updated: 2026-08-02T13:34:14.364668566+02:00
started: 2026-08-02T13:34:14.364667784+02:00
completed: 2026-08-02T13:34:14.364667784+02:00
tags:
    - setup
    - day-00
due: "2026-08-02"
estimate: 45m
depends_on:
    - 10
class: standard
---

> **SLT-SETUP-02** · group `foundation` · scheduled **D00** (2026-08-02)

> Read `README.md` (environment + isolation contract), `calendar.md` (this day's exact
> ordering — it is binding, not advisory) and `plan-audit.md` before starting.

## Objective
Flip exactly four global settings that the 10-day plan depends on, record each prior value verbatim, and declare them frozen for the window so no other task touches them. The most consequential is turning global renewal sync OFF: with it ON, `arraysubs_subscription_data_supports_renewal_sync()` returns true for every non-trial, non-lifetime subscription product, and `CheckoutHelpersTrait::maybeHideUnsupportedRenewalSyncGateways()` then removes Paddle from checkout for all of them — making Paddle coverage impossible.

## Scope
- Gateway: both (configuration baseline only)
- Checkout: N/A (no cart or purchase in this task)
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
2. `agent-browser --session admin-SLT-SETUP-02 open "https://mirror-help.arrayhash.com/wp-admin/admin.php?page=arraysubs-mainadmin#/settings/general"` -> `agent-browser --session admin-SLT-SETUP-02 snapshot -i`.
3. In the **Renewal Sync** card, switch **Sync Renewals to Next Billing Cycle** OFF. Do NOT touch the **First Charge** select (it hides when sync is off; its stored value `full` must remain untouched). Justification, code-verified: `arraysubs/src/functions/renewal-sync-helpers.php::arraysubs_is_renewal_sync_supported_gateway()` returns false for `arraysubs_paddle`, and `CheckoutHelpersTrait::maybeHideUnsupportedRenewalSyncGateways()` hides every unsupported gateway whenever the cart holds a sync-eligible item. Turning the global off also gives every non-flex SLT product deterministic anniversary renewals (checkout time + interval) instead of midnight-boundary renewals.
4. In the **Customer Actions** card, switch **Allow Early Renew** ON (pro EarlyRenew module is active; verified `arraysubs_has_module('EarlyRenew') === 1`).
5. In the same card, switch **Allow Reactivation** ON.
6. Save the General settings page and re-snapshot to confirm the save toast.
7. `agent-browser --session admin-SLT-SETUP-02 open "https://mirror-help.arrayhash.com/wp-admin/admin.php?page=arraysubs-mainadmin#/settings/skip-pause"` -> `snapshot -i`.
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
| 1 | NONE EXPECTED | Settings save | — | — | Capture a baseline before step 2 and inspect its complete delta after step 9; require zero task-attributable mail and classify unrelated/background mail |

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
- State handoff: these four controls (five boolean values) are the WINDOW-WIDE BASELINE. Every other SLT task treats them as fixed and must NOT flip them. `SLT-SYN-04` is the sole global-sync exception and must flip it ON and restore it within its exclusive 2026-08-05 bracket.
- Consequence other authors must assume: non-flex SLT products bill on **anniversary** schedule (checkout timestamp + interval), NOT at site-local midnight. Flex-sync products still sync, because `ArraySubsPro\Features\FlexibleRenewalSync\Services\Hooks::filterSupportsRenewalSync()` grants support per-product regardless of the global switch — which is exactly what SLT-PROD-12/13/14/15 prove.
- Restores: SLT-SETUP-99A sets all four (five booleans) back to the recorded priors on day 10.

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

[[2026-08-02]] Sun 13:34
EXECUTED / FAIL 2026-08-02 (hill-tide). Priors: sync_to_billing_cycle=true; sync_first_charge_mode=full; allow_early_renew=false; allow_reactivation=false; pause_subscription.enabled=false; pause_subscription.customer_can_pause=false. Intended window baseline is now installed and verified: false/full/true/true/true/true, and MAILPIT_BASE stayed unchanged. Failure: the General save materialized six unrelated absent defaults; filed issues/SLT-SETUP-02-general-settings-save-materializes-unrelated-defaults.md. Captured the raw before/after proof, then removed only those six unintended keys; the live option now differs from D0 at exactly the five planned boolean paths. Registry page 11847 contains the frozen baseline table. Evidence: /home/server-manager/slt-evidence/SLT-SETUP-02-*.
