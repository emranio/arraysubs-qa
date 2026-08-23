---
id: 11
title: SLT-SETUP-02 Apply and record the five window-wide baseline setting changes
status: done
priority: critical
created: 2026-08-22T19:10:00+02:00
updated: 2026-08-23T06:54:03.16896328+02:00
started: 2026-08-22T21:06:10.202479962+02:00
completed: 2026-08-22T21:20:26.10414916+02:00
tags:
    - cycle-2
    - granular
    - setup
    - day-00
due: "2026-08-23"
estimate: 45m
depends_on:
    - 10
class: standard
---

> **SLT-SETUP-02** · group `foundation` · scheduled **D00** (2026-08-23)

> Read `README.md` (environment + isolation contract), `calendar.md` (this day's exact
> ordering — it is binding, not advisory) and `plan-audit.md` before starting.

## Objective
Flip only the explicitly listed global settings that this 12-day plan depends on, record each live prior value verbatim, and declare them frozen for the window so no other task touches them. The most consequential is turning global renewal sync OFF: with it ON, `arraysubs_subscription_data_supports_renewal_sync()` returns true for every non-trial, non-lifetime subscription product, and `CheckoutHelpersTrait::maybeHideUnsupportedRenewalSyncGateways()` then removes Paddle from checkout for all of them — making Paddle coverage impossible.

## Scope
- Gateway: both (configuration baseline only)
- Checkout: N/A (no cart or purchase in this task)
- Account: N/A
- Plugins: both

## Preconditions
- SLT-SETUP-01 complete (pre-window `arraysubs_settings` dump exists).
- Code-verified basis for each change is stated in Steps; revalidate against the current code and runtime before using.

## Test data
| Item | Value |
|---|---|
| Product | N/A |
| Account | admin |
| Coupon | N/A |
| Card | N/A |
| Amounts | N/A |

## Steps
1. Read and record the live prior presence/value of `renewals.sync_to_billing_cycle`, `renewals.sync_first_charge_mode`, `customer_actions.allow_early_renew`, `pause_subscription.enabled`, `pause_subscription.customer_can_pause`, and `pause_subscription.customer_can_resume`. Record both saved presence/value and effective default for absent paths. Do not assume any value. Write the exact snapshot into `/home/server-manager/slt-evidence/SLT-SETUP-02-priors.txt`.
2. `agent-browser --session admin-SLT-SETUP-02 open "https://mirror-help.arrayhash.com/wp-admin/admin.php?page=arraysubs-mainadmin#/settings/general"` -> `agent-browser --session admin-SLT-SETUP-02 snapshot -i`.
3. In the **Renewal Sync** card, switch **Sync Renewals to Next Billing Cycle** OFF. Do NOT touch the **First Charge** select (it hides when sync is off; its stored value `full` must remain untouched). Justification, code-verified: `arraysubs/src/functions/renewal-sync-helpers.php::arraysubs_is_renewal_sync_supported_gateway()` returns false for `arraysubs_paddle`, and `CheckoutHelpersTrait::maybeHideUnsupportedRenewalSyncGateways()` hides every unsupported gateway whenever the cart holds a sync-eligible item. Turning the global off also gives every non-flex SLT2 product deterministic anniversary renewals (checkout time + interval) instead of midnight-boundary renewals.
4. In the **Customer Actions** card, switch **Allow Early Renew** ON (pro EarlyRenew module is active; verified `arraysubs_has_module('EarlyRenew') === 1`).
5. Confirm the retired `customer_actions.allow_reactivation` path is absent and do not create it. Cancelled/expired customer reactivation is governed by the current status/capability filter contract and is tested directly by `SLT-SW-10`; **Allow Resume** under Skip & Pause controls paused-subscription resume and is a different setting.
6. Save the General settings page and re-snapshot to confirm the save toast.
7. `agent-browser --session admin-SLT-SETUP-02 open "https://mirror-help.arrayhash.com/wp-admin/admin.php?page=arraysubs-mainadmin#/settings/skip-pause"` -> `snapshot -i`.
8. Switch **Enable Pause Subscription**, **Allow Customers to Pause**, and **Allow Resume** ON. The resume control is required for the manual-resume rows in the pause matrix and is not cancelled-subscription reactivation. Leave **Maximum Pause Duration (Days)**, **Maximum Pauses per Subscription**, **Cooldown Between Pauses (Days)**, **Require Pause Reason**, and **Access During Pause** at their exact live values. Save.
9. Verify the six current paths and the obsolete-path absence with a WP-CLI JSON projection that reports saved presence/value plus effective value; do not grep PHP serialization or infer an absent key.
10. Dump the post-change blob: `wp option get arraysubs_settings --format=json --allow-root > /home/server-manager/slt-evidence/SLT-SETUP-02-arraysubs_settings-baseline.json`.

## Expected results
1. `renewals.sync_to_billing_cycle` is `false`; `renewals.sync_first_charge_mode` is still the string `full`.
2. `customer_actions.allow_early_renew` is `true`.
3. `customer_actions.allow_reactivation` remains absent; `pause_subscription.customer_can_resume` is `true` for paused-subscription resume coverage.
4. `pause_subscription.enabled` is `true` and `pause_subscription.customer_can_pause` is `true`.
5. Every path outside the declared baseline targets is byte-identical to the SLT-SETUP-01 D0 dump — specifically unchanged: all other `multiple_subscriptions.*`, `trials.*`, grace/invoice settings, `plan_switching.*`, `refunds.*`, `emails.*`, and the remaining pause fields.
6. With sync off, checkout for a plain subscription product now offers both Stripe and Paddle.

## Emails expected
| # | Email | Trigger point | Recipient | Subject contains | Verify with |
|---|---|---|---|---|---|
| 1 | NONE EXPECTED | Settings save | — | — | Capture a baseline before step 2 and inspect its complete delta after step 9; require zero task-attributable mail and classify unrelated/background mail |

## Evidence to capture
- Screenshots: `SLT-SETUP-02-01-general-after.png`, `SLT-SETUP-02-02-skip-pause-after.png`.
- `SLT-SETUP-02-priors.txt` and `SLT-SETUP-02-arraysubs_settings-baseline.json`.
- The JSON projection proving current saved/effective values and obsolete-path absence.

## Pass criteria
- [x] sync_to_billing_cycle == false, sync_first_charge_mode still "full"
- [x] allow_early_renew == true
- [x] retired allow_reactivation remains absent; customer_can_resume == true
- [x] pause_subscription.enabled == true and customer_can_pause == true
- [x] No other setting differs from the D0 dump
- [x] Priors recorded in Notes and on disk

## Isolation / teardown
- State handoff: the declared current controls are the WINDOW-WIDE BASELINE. Every other SLT2 task treats them as fixed and must NOT flip them. `SLT-SYN-04` is the sole global-sync exception and must flip it ON and restore it within its exclusive 2026-08-26 bracket. No task creates the retired `customer_actions.allow_reactivation` key.
- Consequence other authors must assume: non-flex SLT2 products bill on **anniversary** schedule (checkout timestamp + interval), NOT at site-local midnight. Flex-sync products still sync, because `ArraySubsPro\Features\FlexibleRenewalSync\Services\Hooks::filterSupportsRenewalSync()` grants support per-product regardless of the global switch — which is exactly what SLT-PROD-12/13/14/15 prove.
- Restores: SLT-SETUP-99A restores every listed key to its exact recorded presence/value on D11.

---

### Fresh-cycle validation contract

- Re-derive every ID, count, option value, gateway capability, scheduler timestamp, and email baseline on this run; no prior-cycle result is evidence.
- Create and mutate only registered `SLT2 ` / `slt2-*` fixtures. Legacy `SLT` and all non-SLT2 data are read-only controls.
- Automatic-gateway scope is Stripe and Paddle only. Stripe is the primary path; run Paddle parity wherever Paddle supports the behavior. Do not test or configure PayPal or Mollie.
- ArraySubs core must own its Stripe/Paddle integration, renewal, retry, webhook, REST, refund, and customer-payment services with Pro inactive; vendor host classes retain their expected ownership.
- Browser-required assertions use Vercel `agent-browser` with isolated task/role sessions and current snapshot refs. WP-CLI always includes `--allow-root`.
- Update the lifecycle card, the matching `qa/progress/` card, and `qa/issues/` for every new regression. Evidence belongs to this fresh cycle only.

## SLT2 execution — PASS (site date 2026-08-23)

- Captured every live prior presence/value before mutation in `/home/server-manager/slt-evidence/SLT-SETUP-02-priors.txt`. The five target booleans all required a real change; `renewals.sync_first_charge_mode` was already `"full"`; retired `customer_actions.allow_reactivation` was absent.
- Through isolated real wp-admin browser session `admin-SLT-SETUP-02`, saved General with global renewal sync OFF and Early Renew ON, then saved Skip & Pause with Pause, customer Pause, and Resume ON. All untouched pause values remained 30 days / 2 pauses / 0 cooldown / reason required / no access. Screenshot `SLT-SETUP-02-02-skip-pause-after.png` visibly contains the `Settings saved!` toast.
- The post-save WP-CLI projection proves all six current paths and obsolete-path absence. Recursive comparison with the untouched D0 blob found exactly five differences and no others: the five declared booleans only. Evidence: `SLT-SETUP-02-settings-projection.json` and `SLT-SETUP-02-arraysubs_settings-baseline.json`.
- EarlyRenew remained active. Mailpit baseline/latest both remained `4M53QIPekuKDdmPjFx8ofM`, so settings saves emitted zero mail. Action Scheduler had 0 new failed, 0 stuck in-progress over 10 minutes, and 0 pending overdue over 5 minutes. `debug.log` did not change and the browser reported no errors (only existing JQMIGRATE/list diagnostics).
- No QA issue was created. These five values are now the frozen window baseline; only the explicitly bracketed sync task may temporarily alter global renewal sync before restoring it.

[[2026-08-23]] Sun 02:48

## D00 early-morning watcher reconciliation — 2026-08-23

- The D00 facts snapshot and current-cycle settings evidence were reconciled. This watcher opened no settings/plugin/date/email bracket and performed no save; task 131 remains separately blocked on issue #1. Evidence: `/home/server-manager/slt-evidence/SLT-SETUP-02-settings-projection.json` and `automation/logs/D00-2026-08-23-early-morning-facts.txt`.

[[2026-08-23]] Sun 03:02

## Closure-audit tracking normalization

The lifecycle `started` timestamp was reconciled to the original `todo -> in-progress` activity event. No verdict, site state, or evidence changed.

[[2026-08-23]] Sun 06:54
## D00 late-morning read-only reconciliation — 2026-08-23

The six declared setting values and obsolete-path absence remain correct. The only full-object differences from the D00 post-save snapshot are the declared Shop Access exclusions for SLT2 product IDs `31340`, `31347`, `31357`, `31363`; no undeclared settings drift exists. This watcher opened no settings bracket and performed no save. Fresh evidence is recorded in `/home/server-manager/slt-evidence/SLT-WATCH-D00-LATE-scheduler-mail-reconciliation.json` and the merged D00 watch report.
