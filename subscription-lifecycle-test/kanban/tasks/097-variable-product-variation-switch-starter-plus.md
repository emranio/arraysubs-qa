---
id: 97
title: Variable-product variation switch (Starter→Plus) plus on-hold and pending-cancellation switch refusals
status: todo
priority: high
created: 2026-08-02T03:43:11.150512461+02:00
updated: 2026-08-02T03:43:22.346336248+02:00
tags:
    - plan-switching
    - day-06
    - has-conflicts
due: "2026-08-08"
estimate: 2h 30m
depends_on:
    - 11
    - 12
    - 71
class: standard
---

> **SLT-SW-07** · group `switching` · scheduled **D06** (2026-08-08)

> Read `README.md` (environment + isolation contract), `calendar.md` (this day's exact
> ordering — it is binding, not advisory) and `plan-audit.md` before starting.

### ⚠ Conflict resolutions that apply to this task

**`high` · shared-global-setting / multi-day deviation vs frozen baseline** — with `SLT-LIFE-03`, `SLT-MYA-01`, `SLT-SW-10`, `SLT-LIFE-02`, `SLT-MYA-03`, `SLT-MYA-04`

- *Problem:* SLT-LIFE-03 flips two global settings out of baseline - skip_renewal.enabled false->true and skip_renewal.cutoff_days 2->0 - and restores them only at its step 7, which happens two days later (after the shifted cycle charges). That is a 2-3 day site-wide deviation in which every customer portal renders a 'Skip Next Renewal' control. Colliding audits: SLT-MYA-01 expected result 5 lists 'Skip Next Renewal' among the five actions an active subscription must expose - which is wrong against the frozen baseline (skip_renewal.enabled=false) and only accidentally right if MYA-01 happens to run inside LIFE-03's bracket. SLT-ADM-03 asserts the opposite ('Skip Renewal is expectedly unavailable'), so the two tasks contradict each other. SLT-SW-07, SLT-SW-10, SLT-LIFE-02, SLT-MYA-03 and SLT-MYA-04 all screenshot the portal Actions card on D5-D7 and would file the Skip control as unexpected UI.
- *Required fix:* Two changes. (1) Correct SLT-MYA-01 expected result 5 to the four baseline actions - Change Plan, Cancel Subscription, Renew Early, Pause Subscription - and add 'Skip Next Renewal MUST be absent (skip_renewal.enabled=false)'; quote the registry WINDOW BASELINE table as C14 requires. (2) Compress LIFE-03's deviation to a single short bracket: settings ON, perform skip / undo / 5-cycle clamp / undo / final 1-cycle skip, settings RESTORED, all inside one <30 min window on D5 with open/close UTC recorded - the pending skip lives in subscription meta (_skip_cycles_remaining, _original_next_payment_date) and completeSkippedCycles() runs off the renewal path, so the setting does not need to stay on for the shifted cycle to complete. Verify that on the day; if completion does prove to require the flag, move LIFE-03 wholesale to D8-D9 where no portal audit runs. Also correct LIFE-03's internal dates: it is a D5 (2026-08-07) task, so D_now = 08-08, skip1 -> 08-09, skip3 -> 08-11, original due 08-08 shows nothing (watch D7 negative) and the shifted $20.00 charge lands 08-09 PM (watch D8) - which also clears 2026-08-10 for SLT-LIFE-01.

**`high` · session collision (shared admin session)** — with `SLT-EML-01`, `SLT-EML-02`, `SLT-EML-03`, `SLT-EML-05`, `SLT-EML-10`, `SLT-EML-12`

- *Problem:* More than twenty tasks open `--session admin` by that bare name, in direct violation of audit C09's fix (which only got applied to guest/customer sessions). agent-browser sessions are keyed by name, so these tasks share one browser profile: one task's `agent-browser close --session admin` logs out another mid-run; one task navigating the SPA away from a settings screen invalidates another's snapshot; and any admin session that ever adds to cart (SLT-ADM-01's bulk-action screen, SLT-EML-12's status juggling) puts a cart on the admin user shared by all of them. The failures this produces look like flaky UI, not contamination.
- *Required fix:* Rename every admin session to `admin-<TASK-KEY>` (SLT-ADM-01/02/03/04/05 already do this correctly - copy the pattern). Each task closes only its own session by name; `agent-browser close --all` is reserved to the last task of the day, named explicitly in the calendar. Add this to the isolation contract as rule 9 and add a pass criterion to every task that opens an admin session: 'the session name contains this task's key'.

---
## Objective
Cover the edges the ladder cannot reach: switching a **variable** subscription between variations of one parent, and switching while **on-hold** and while **pending-cancellation**. Starter ($6.00 day/1) → Plus ($11.00 day/2): sticker rises but daily rate falls (6.00 → 5.50, ±0.30 band) so it must classify **downgrade**, and the interval change forces the cycle-change branch — full new price charged, next payment re-anchored to now + 2 days.

## Scope
- Gateway: Stripe test
- Checkout: block
- Account: new registered — **this task creates `slt-switch3` / `slt-switch3@example.test`**
- Plugins: free-only

## Preconditions
- SLT-SETUP-02/03 and SLT-PROD-08 done. Sessions `admin`, `cust-SLT-SW-07`; cart empty first and last.
- Switch targets live on the VARIATION: `getAvailableSwitchOptions()` reads `_arraysubs_*_products` from `_variation_id` with no parent fallback.

## Test data
| Item | Value |
|---|---|
| Product | SLT Variable Daily: Starter $6.00 day/1 → Plus $11.00 day/2 (fee $4) |
| Account | slt-switch3 / `SltQa!2026#Pass` / Customer; card 4242 4242 4242 4242 |
| Amounts | signup $6.00; switch charge $11.00, credit round(6×dr,2), net max(0, 11 − credit), where dr = max(0, 1 − round((now − `_last_payment_date`)/86400, 2)) |

## Steps
1. `latest-id` → `MP0`; create `slt-switch3` as in SLT-SW-06 step 2.
2. Admin → SLT Variable Daily → **Variations** → **Starter** → **Subscription Plan Switching** → **Downgrade to** = **Plus** → **Save changes**; reload and verify `_arraysubs_downgrade_products` on the Starter variation.
3. `cust-SLT-SW-07`: log in, cart empty, product page, `SLT Tier` = **Starter**, add to cart, checkout 4242. Record `SUB_ID`, `ORDER_A`, `T0` (SW-06 §5 metas + `_variation_id`).
4. **Probe A.** Admin: set the subscription **On hold**. Customer: reload `/my-account/view-subscription/<SUB_ID>/`, screenshot the Actions card.
5. Admin: back to **Active**; confirm **Change Plan** returns and record whether `_next_payment_date` moved.
6. Customer: **Change Plan** → select Plus → screenshot **Plan Change Summary** → **Confirm Plan Change** → open the `checkout_url`, record `ORDER_B`, pay 4242.
7. Re-dump metas, diff vs `T0`, screenshot the page.
8. **Probe B.** Customer: **Cancel Subscription** → reason **Just need a temporary break** → **Continue** → **Before You Go...** → **No thanks, continue to cancel**. Screenshot banner + Actions card.
9. Click **Undo Scheduled Cancellation**; confirm **Change Plan** is back; `list 20`.

## Expected results
1. Starter's `_arraysubs_downgrade_products` holds exactly the Plus variation ID after the AJAX save + reload.
2. **Probe A:** while on-hold **Change Plan** is absent (server: "Plan switching is only available for active subscriptions"); no proration order; Active restores it.
3. Classified **downgrade** despite the higher sticker price (record the modal grouping and `_arraysubs_switch_type`); `ORDER_B` charges the full **$11.00**, credit round(6×dr,2), net max(0, 11 − credit), **no $4.00 fee line**, no tax line.
4. After payment `_variation_id`=Plus, `_recurring_amount=11.00`, day/2, `_next_payment_date` = switch + 2 days, legs re-queued at that date + the crc32 offset.
5. **Probe B:** stays `arraysubs-active`, `_waiting_cancellation=1`, `_cancellation_scheduled_date` = `_next_payment_date`, banner "scheduled to cancel on …", **Change Plan** gone (409). Undo clears both metas, re-queues the legs, restores it.

## Emails expected
| # | Email | Trigger point | Recipient | Subject contains | Verify with |
|---|---|---|---|---|---|
| 1 | new_subscription, admin copy, Woo order mail | step 3 | customer, admin | `is active` | `wait-new MP0 180` |
| 2 | subscription_on_hold; log anything step 5 sends | steps 4-5 | slt-switch3 | `is on hold` | `wait-new <pre-4> 120` |
| 3 | pending_cancellation + admin copy | step 8 | customer, admin | `scheduled to cancel on` | `wait-new <pre-8> 180` |
| 4 | NONE for the switch or undo | steps 6, 9 | — | — | `list 20` — no new id at either point (L6: no listener) |

## Evidence to capture
- `SLT-SW-07-01-links.png`, `-02-onhold.png`, `-03-modal.png`, `-04-proration.png`, `-05-pending.png`, `-06-undo.png`; `SUB_ID`, variation IDs, order ids, `T0` diff, Mailpit ids, console errors

## Pass criteria
- [ ] Variation-level switch links save and drive the modal
- [ ] On-hold blocks switching, no proration order; Active restores it
- [ ] Classified `downgrade`; charge $11.00, credit round(6×dr,2), no fee/tax line
- [ ] After the switch: Plus, $11.00, day/2, next payment = switch + 2 days
- [ ] Pending cancellation blocks switching, undo restores it; emails 1-3 present and none for the switch or undo

## Isolation / teardown
- Ends with an active day/2 Plus subscription on slt-switch3, no pending cancellation or switch; leave it for the watch. Starter keeps its downgrade link — note it in the registry.
- Two admin status changes are made and reverted here; if hold/resume moves `_next_payment_date`, record before/after.

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
