---
id: 84
title: 'SKIP renewal on S5: one cycle then the max three, with undo, clamp, and the zero-email finding'
status: todo
priority: medium
created: 2026-08-02T03:43:10.208850941+02:00
updated: 2026-08-02T03:43:21.125685619+02:00
tags:
    - renewal
    - day-05
    - has-conflicts
due: "2026-08-07"
estimate: 2h
depends_on:
    - 19
class: standard
---

> **SLT-LIFE-03** · group `renewal` · scheduled **D05** (2026-08-07)

> Read `README.md` (environment + isolation contract), `calendar.md` (this day's exact
> ordering — it is binding, not advisory) and `plan-audit.md` before starting.

### ⚠ Conflict resolutions that apply to this task

**`high` · shared-global-setting / multi-day deviation vs frozen baseline** — with `SLT-MYA-01`, `SLT-SW-07`, `SLT-SW-10`, `SLT-LIFE-02`, `SLT-MYA-03`, `SLT-MYA-04`

- *Problem:* SLT-LIFE-03 flips two global settings out of baseline - skip_renewal.enabled false->true and skip_renewal.cutoff_days 2->0 - and restores them only at its step 7, which happens two days later (after the shifted cycle charges). That is a 2-3 day site-wide deviation in which every customer portal renders a 'Skip Next Renewal' control. Colliding audits: SLT-MYA-01 expected result 5 lists 'Skip Next Renewal' among the five actions an active subscription must expose - which is wrong against the frozen baseline (skip_renewal.enabled=false) and only accidentally right if MYA-01 happens to run inside LIFE-03's bracket. SLT-ADM-03 asserts the opposite ('Skip Renewal is expectedly unavailable'), so the two tasks contradict each other. SLT-SW-07, SLT-SW-10, SLT-LIFE-02, SLT-MYA-03 and SLT-MYA-04 all screenshot the portal Actions card on D5-D7 and would file the Skip control as unexpected UI.
- *Required fix:* Two changes. (1) Correct SLT-MYA-01 expected result 5 to the four baseline actions - Change Plan, Cancel Subscription, Renew Early, Pause Subscription - and add 'Skip Next Renewal MUST be absent (skip_renewal.enabled=false)'; quote the registry WINDOW BASELINE table as C14 requires. (2) Compress LIFE-03's deviation to a single short bracket: settings ON, perform skip / undo / 5-cycle clamp / undo / final 1-cycle skip, settings RESTORED, all inside one <30 min window on D5 with open/close UTC recorded - the pending skip lives in subscription meta (_skip_cycles_remaining, _original_next_payment_date) and completeSkippedCycles() runs off the renewal path, so the setting does not need to stay on for the shifted cycle to complete. Verify that on the day; if completion does prove to require the flag, move LIFE-03 wholesale to D8-D9 where no portal audit runs. Also correct LIFE-03's internal dates: it is a D5 (2026-08-07) task, so D_now = 08-08, skip1 -> 08-09, skip3 -> 08-11, original due 08-08 shows nothing (watch D7 negative) and the shifted $20.00 charge lands 08-09 PM (watch D8) - which also clears 2026-08-10 for SLT-LIFE-01.

**`high` · session collision (shared admin session)** — with `SLT-EML-01`, `SLT-EML-02`, `SLT-EML-03`, `SLT-EML-05`, `SLT-EML-10`, `SLT-EML-12`

- *Problem:* More than twenty tasks open `--session admin` by that bare name, in direct violation of audit C09's fix (which only got applied to guest/customer sessions). agent-browser sessions are keyed by name, so these tasks share one browser profile: one task's `agent-browser close --session admin` logs out another mid-run; one task navigating the SPA away from a settings screen invalidates another's snapshot; and any admin session that ever adds to cart (SLT-ADM-01's bulk-action screen, SLT-EML-12's status juggling) puts a cart on the admin user shared by all of them. The failures this produces look like flaky UI, not contamination.
- *Required fix:* Rename every admin session to `admin-<TASK-KEY>` (SLT-ADM-01/02/03/04/05 already do this correctly - copy the pattern). Each task closes only its own session by name; `agent-browser close --all` is reserved to the last task of the day, named explicitly in the calendar. Add this to the isolation contract as rule 9 and add a pass criterion to every task that opens an admin session: 'the session name contains this task's key'.

---
## Objective
Skip renewals on S5 (SLT Renewal Price Step, day/1, $20.00) for one cycle, then for the maximum three. Proves the date math (`calculateSkippedDate()` adds interval x cycles to the current `_next_payment_date`, SkipManager.php:470-501), the re-queue of both legs, undo/clamp, and that a skip sends NO customer email: `arraysubs_send_subscription_email('subscription_skipped')` resolves an unregistered WC email id (email-helpers.php:29-35).

## Scope
- Gateway: Stripe test (the skip takes no payment)
- Checkout: N/A
- Account: existing (`slt-core`)
- Plugins: free-only

## Preconditions
- SLT-LIFE-05 done; S5 active, `_recurring_amount=20`.
- **Out-of-baseline change (isolation rule 7).** `skip_renewal.enabled=false` and `cutoff_days=2`; a 1-day cycle can never satisfy `days_until_renewal >= 2` (SkipManager.php:82-95), so both change here and are restored here. `max_cycles` (3) and `customer_can_skip` stay untouched.
- Act after that day's renewal completes and before the next invoice leg (due+k-6h).

## Test data
| Item | Value |
|---|---|
| Subscription | S5 (SLT Renewal Price Step, `slt-core`), $20.00/cycle |
| Settings | `skip_renewal.enabled` false->true; `cutoff_days` 2->0 |
| Portal / settings | `/my-account/view-subscription/S5/`; `admin.php?page=arraysubs-mainadmin#/settings/skip-pause` |
| Dates | D_now = 08-08 T; skip1 -> 08-09 T; skip3 -> 08-11 T |

## Steps
1. Save `wp option get arraysubs_settings --format=json --allow-root | jq .skip_renewal` as `skip-before.json`. Then `agent-browser --session admin open` the settings UI, set **Enable Skip Renewal** ON and **Skip Cutoff (Days)** = `0`, Save, re-dump and diff.
2. Confirm `_pending_renewal_order_id` empty; record `_next_payment_date` (D_now); recompute k. `PREV=$(mailpit-agent latest-id)`.
3. `agent-browser --session life03 open` the portal as `slt-core`; screenshot the **Skip Next Renewal** panel and the **Renew Early** control.
4. **Skip Next Renewal** -> 1 cycle -> confirm. Dump `_next_payment_date,_original_next_payment_date,_skip_cycles_count,_skip_cycles_remaining,_skip_history`; screenshot `tools.php?page=action-scheduler&s=S5&status=pending`.
5. Re-snapshot the portal (Renew Early gone); `wait-new "$PREV" 90 "subscription"` must return 124.
6. **Undo Skip** -> re-dump; **Skip** with **5** cycles -> re-dump (clamp proof); **Undo Skip**; then a fresh **1-cycle** skip as the overnight state.
7. **D+1 (08-09, original due):** no renewal order, no charge. **D+2 (08-10, shifted due+k):** $20.00 charged, skip meta cleared. Restore both settings; prove the jq diff is empty.

## Expected results
1. With `cutoff_days=0` the portal offers **Skip Next Renewal**; with the shipped `2` it refuses ("Cannot skip within 2 day(s) of renewal date.").
2. After the 1-cycle skip: `_original_next_payment_date` = D_now, `_next_payment_date` = D_now + 24h exactly, `_skip_cycles_count`/`_remaining` = 1, one `_skip_history` `skip` entry; both legs re-queued at new_due+k-6h and +k, one row each, no orphans.
3. **Undo Skip** restores D_now and clears `_skip_cycles_*`; 5 cycles clamps to 3 (`_next_payment_date` = D_now + 72h).
4. Skip/undo/modify send zero customer email, add a subscription note each, and hide Renew Early while pending.
5. Nothing fires at the original due moment.
6. At the shifted moment `shouldCompleteSkipAndGenerateNow()` -> `completeSkippedCycles()` (Hooks.php:214-230) generates the invoice in the same pass, $20.00 is charged, `_skip_cycles_remaining=0`, meta cleared. Final settings diff empty.

## Emails expected
| # | Email | Trigger point | Recipient | Subject contains | Verify with |
|---|---|---|---|---|---|
| 1 | NONE EXPECTED | skip / undo / modify (steps 4, 6) | — | — | `wait-new $PREV 90` returns 124 each time |
| 2 | payment_successful + WC order mails | shifted renewal 08-10 | customer + admin | `Payment received for subscription #S5`, `#<order id>` | `wait-new`, `list 20` |
| 3 | NONE EXPECTED: renewal_reminder | — | — | `renews soon` | no reminder AS row (1-day cycle) |

## Evidence to capture
- `skip-before.json`, `skip-after.json`, the empty diff, notes, the three timeout exit codes; S5, shifted order ID, k, meta dumps.
- Screenshots `SLT-LIFE-03-01-settings.png`, `-02-skip-panel.png`, `-03-after-skip1.png`, `-04-pending-shifted.png`, `-05-no-renew-early.png`.

## Pass criteria
- [ ] Skip panel appears only after `enabled=true` + `cutoff_days=0`
- [ ] 1-cycle skip moves the date one interval, stores the original, re-queues both legs
- [ ] Undo restores the date; a 5-cycle request clamps to 3
- [ ] Zero email on skip/undo/modify; Renew Early hidden while pending
- [ ] Nothing at the original due moment; the shifted cycle charges $20.00
- [ ] `skip_renewal` settings restored, jq diff empty

## Isolation / teardown
- S5 returns to a normal daily grid for SLT-LIFE-01 on D8; no skip pending after 08-10.
- Restore `skip_renewal.enabled` -> false and `cutoff_days` -> 2 here; record the change window in the registry.


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
