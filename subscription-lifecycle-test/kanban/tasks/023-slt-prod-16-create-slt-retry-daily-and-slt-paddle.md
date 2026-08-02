---
id: 23
title: SLT-PROD-16 Create SLT Retry Daily and SLT Paddle Daily, the two gateway-path products
status: todo
priority: critical
created: 2026-08-02T03:43:04.919010019+02:00
updated: 2026-08-02T03:43:15.168822951+02:00
tags:
    - setup
    - products
    - day-01
    - has-conflicts
due: "2026-08-03"
estimate: 45m
depends_on:
    - 10
    - 11
class: standard
---

> **SLT-PROD-16** · group `catalog` · scheduled **D01** (2026-08-03)

> Read `README.md` (environment + isolation contract), `calendar.md` (this day's exact
> ordering — it is binding, not advisory) and `plan-audit.md` before starting.

### ⚠ Conflict resolutions that apply to this task

**`unrated` · shared-global-setting** — with `SLT-SYN-04`, `SLT-SETUP-05`, `SLT-SETUP-02`, `SLT-PROD-12`, `SLT-PROD-13`, `SLT-PROD-14`

- *Problem:* renewals.sync_to_billing_cycle is written by two tasks on the same authored day. SLT-SETUP-02 turns it OFF as a declared window-wide baseline; SLT-SYN-04 turns it back ON (steps 3-15) and only restores it at step 16. Every other day-0 task asserts the OFF baseline while sync is ON: SLT-SETUP-05 pass criterion 'Stripe AND Paddle both offered for SLT Daily Core' is guaranteed to FAIL because maybeHideUnsupportedRenewalSyncGateways() hides arraysubs_paddle on every non-trial, non-lifetime subscription cart once the global switch is on; the guest cart previews in SLT-PROD-01/02/04/09/12/13/14/15 would read altered first-charge amounts and midnight-boundary next-payment dates; and any checkout completed inside the ON window permanently writes _renewal_sync_enabled=yes plus the five _renewal_sync_* metas onto that subscription, which cannot be undone by restoring the setting. Secondary hazard: turning sync ON re-exposes the First Charge select that SLT-SETUP-02 step 3 deliberately never touched, so a careless Save on the General page can write sync_first_charge_mode explicitly.
- *Required fix:* Make SLT-SYN-04 the sole writer of sync_to_billing_cycle and give it an exclusive, fixed bracket: run it on D3 (2026-08-04) 09:00-11:00 site time only. No other SLT task may add to cart, reach checkout, place an order, save a product, or drain Action Scheduler inside that bracket. SLT-SYN-04 must (a) capture the jq settings dump before flipping, (b) never click the First Charge select, (c) restore the switch and prove the jq diff is empty before the bracket is released, (d) post the 'bracket closed' confirmation to the registry page. Schedule SLT-SETUP-05 on D1, two days ahead of the bracket, so its two-gateway assertion runs against the true OFF baseline.

**`unrated` · impossible-timing** — with `SLT-SYN-04`, `SLT-PROD-01`, `SLT-PROD-14`, `SLT-PROD-06`

- *Problem:* SLT-SYN-04's global-sync-ON window is not just a checkout hazard: any renewal that Action Scheduler processes while the switch is ON can pick up sync context and be re-anchored from its checkout anniversary to the site-local midnight boundary. By the time SLT-SYN-04 can realistically run (after SETUP-01/02/PROD-16/SETUP-05/SYN-03 have completed), several day/1 and day/2 subscriptions bought on D0/D1 already have renewals due, and their anniversary times are whatever clock time those checkouts happened. If a checkout was done at 09:30 site on D0, its renewal fires at 09:30 site the next day - inside a morning ON window.
- *Required fix:* Two-part rule. (1) Every SLT purchase on D0, D1 and D2 must be executed AFTER 12:00 site time, so all anniversary renewals land in the afternoon. (2) SLT-SYN-04's ON bracket is fixed at 09:00-11:00 site on D3 and no `wp action-scheduler run` of any kind may be issued during it. Record the exact UTC open/close timestamps of the bracket in the evidence root as SLT-SYN-04-bracket.txt so any anomalous renewal in that interval can be attributed.

**`unrated` · impossible-timing** — with `SLT-SETUP-01`, `SLT-SETUP-02`, `SLT-SETUP-03`, `SLT-SETUP-04`, `SLT-SETUP-05`, `SLT-PROD-01`

- *Problem:* 25 of the 26 authored tasks are stamped day 0. Sixteen of them are multi-step browser-driven product builds (each: open post-new, set 6-15 fields across 3 tabs, publish, reload, verify, meta-dump, guest front-end check, registry append), two are long audit tasks with 15-20 probe steps each (SLT-SYN-01, SLT-SYN-02), one is a live Stripe purchase with a settings flip and restore (SLT-SYN-04), and on top of that D0 is also where the plan's isolation notes demand the D0 purchases of SLT-PROD-01/02/03/06/07/10/12/13/14/15/16. There is a hard serial chain inside the day as well: SETUP-01 -> SETUP-02 -> PROD-16 -> SETUP-05 -> SYN-04, and SETUP-01 -> SETUP-02 -> PROD-14 -> SYN-03 -> SYN-04. This is not executable in one day, and it wastes D4-D9 which sit empty.
- *Required fix:* Adopt the rebalanced D0-D10 calendar in schedule_notes. Only products with a genuinely date-bound purchase stay on D0 (SLT-PROD-01, 06, 07, 13 plus the three foundation tasks); everything else is spread D1-D5 with its purchase deadline recomputed so it still fits the window. Peak day drops from 25 tasks (~30h) to 7 tasks (~7h) and the median day is ~4.5h, inside the 1.6x band.

**`unrated` · shared-global-setting** — with `SLT-SETUP-02`, `SLT-SETUP-99`, `SLT-SYN-04`

- *Problem:* SLT-SETUP-02 flips five booleans ON for the whole window (allow_early_renew, allow_reactivation, pause_subscription.enabled, pause_subscription.customer_can_pause; plus sync OFF) and declares them frozen. Nothing in the plan republishes that baseline where a my-account or customer-action task will see it, so any later task auditing the my-account subscription screen against the site's shipped defaults will file Renew Early / Reactivate / Pause buttons as unexpected UI. The reverse trap also exists: cancellation.retention_offers_enabled has pause/skip OFF while the pause FEATURE is now ON, so the retention modal legitimately shows no pause offer even though pausing works - easy to misfile as a defect. SLT-PROD-16 already relies on the baseline being ON to assert Paddle's Renew Early button stays hidden.
- *Required fix:* SLT-SETUP-02 must append a 'WINDOW BASELINE (frozen)' table to slt-catalog-registry listing all five booleans with prior value / window value / restoring task, and every customer-facing audit task must quote that table in its preconditions instead of the shipped defaults. Add a pass criterion to SLT-SETUP-02: the registry table exists. SLT-SETUP-99A restores all five and proves it with the empty jq diff.

**`high` · dependency-inversion (product creation after first consumer)** — with `SLT-PROD-04`, `SLT-PROD-05`, `SLT-PROD-08`, `SLT-PROD-09`, `SLT-PROD-10`, `SLT-PROD-11`

- *Problem:* The corrected calendar in plan-audit places several catalog tasks later than the first new-index task that depends on them. SLT-SETUP-04 (coupons) is D3 but SLT-CPN-01/02 need it on D1 18:00-19:00. SLT-PROD-05 is D3 but SLT-LIFE-05 buys it on D1. SLT-PROD-16 is D1 but SLT-DUN-01 (corrected to D2 13:00) and SLT-CHK-04 (D2) need it, and SLT-MYA-05 needs it on D2 morning. SLT-PROD-09 is D5 but SLT-CPN-04 (D3) and SLT-CHK-12 (D5) depend on it. SLT-PROD-10 and SLT-PROD-11 are D4 but SLT-CHK-13 (D4), SLT-CHK-10 (D5) and SLT-SW-09 (D4, which explicitly says PROD-11 must be done 'before this task starts on D4') need them earlier in the day or before. SLT-PROD-08 is D5 but SLT-CHK-11 buys its variations on D5. SLT-PROD-15 is D2 and SLT-SYN-13 buys its variations on D2 - correct only if SYN-02's audit sits strictly between them.
- *Required fix:* Adopt the rebalanced calendar in this report: SETUP-04 and PROD-05 to D1 morning; PROD-16 to D1 morning (ahead of SETUP-05, which also gains PROD-14 as a dependency per audit C03); PROD-02/03/09/15 and SYN-02 to D2 morning; PROD-04/10/11 to D3 after the SYN-04 bracket closes; PROD-08 to D4 morning. Add an explicit intra-day ordering line to every day's calendar row ('creations and audits before 12:00, purchases after 12:00') and make it a pass criterion that each consuming task quotes the creating task's registry entry.

---
## Objective
Create the two products whose only distinguishing feature is the gateway they are bought with: one wired to Stripe's always-declines-off-session card so the failure / on-hold / cancel dunning ladder runs on a real daily schedule, and one reserved exclusively for Paddle sandbox. Both are plain day/1 subscriptions with no trial, no fee and no flex sync, so any behavioural difference is attributable to the gateway alone.

## Scope
- Gateway: both
- Checkout: both
- Account: N/A (creation only)
- Plugins: both

## Preconditions
- SLT-SETUP-01 and SLT-SETUP-02 complete. Global sync OFF is what makes Paddle selectable at all; SLT-SETUP-05 verifies that after this task.
- Neither product may enable flexible sync — a sync-eligible cart would hide Paddle again and break the Paddle product's whole purpose.
- Dunning timing that these products must produce, from the unchanged baseline: renewal due -> stays `arraysubs-active` for `grace_days_before_on_hold = 1` day -> `arraysubs-on-hold` -> `grace_days_before_cancel = 3` days -> `arraysubs-cancelled`. The renewal invoice is generated `invoice_before_due_value = 6` hours before due.

## Test data
| Item | Value |
|---|---|
| Product A | SLT Retry Daily / slug `slt-retry-daily`, $13.00, day/1 |
| Product B | SLT Paddle Daily / slug `slt-paddle-daily`, $11.00, day/1 |
| Account | A -> `slt-fail`; B -> `slt-paddle` |
| Coupon | N/A |
| Card | A: `4000 0000 0000 0341` (attaches fine, declines every off-session renewal); B: Paddle sandbox `4242 4242 4242 4242`, any future expiry |
| Amounts | A $13.00 first charge then failing $13.00 renewals; B $11.00 first charge then $11.00 renewals |

## Steps
1. Capture `mailpit-agent latest-id`.
2. Create Product A: `agent-browser --session admin open "https://mirror-help.arrayhash.com/wp-admin/post-new.php?post_type=product"`; title `SLT Retry Daily`; description `SLT window product. Stripe failing-card dunning path. Delete on 2026-08-11.`; **Simple product**; tick **Virtual**; tick **Subscription [ArraySubs]**; **General** tab **Regular price ($)** `13.00`.
3. Product A **Subscription [ArraySubs]** tab: **Billing Period** `Day`; **Billing Interval** `1`; **Subscription Length** `0`; **Trial Length** `0`; **Sign-up Fee ($)** empty; **Different Renewal Price** unticked; **Flexible Renewal Sync** UNTICKED. Slug `slt-retry-daily`. Publish.
4. Create Product B identically but title `SLT Paddle Daily`, description `SLT window product. Paddle sandbox only. Delete on 2026-08-11.`, **Regular price ($)** `11.00`, slug `slt-paddle-daily`. Publish.
5. Reload both and confirm the subscription fields persisted.
6. `wp post meta list <ID> --keys=_is_subscription,_subscription_period,_subscription_interval,_subscription_length,_trial_length,_signup_fee,_enable_renewal_price,_arraysubs_flex_sync_enabled,_regular_price --allow-root` for both.
7. As `--session guest`, open both product pages and confirm each renders a plain daily recurring summary with no trial and no fee.
8. Do NOT purchase in this task — the Paddle catalogue sync check and the gateway list check belong to SLT-SETUP-05, which depends on this task.
9. Append both IDs to the registry, tagging A as `stripe-decline-only` and B as `paddle-only`.

## Expected results
1. Both published simple + virtual + subscription, day/1, length 0, trial 0, no signup fee, no different renewal price, no flex sync meta.
2. `SLT Retry Daily` `_regular_price=13.00`; `SLT Paddle Daily` `_regular_price=11.00`.
3. Neither product carries `_arraysubs_flex_sync_enabled` (mandatory — otherwise Paddle would be hidden).
4. Both storefront pages show a plain "every day" recurring summary.
5. Dunning contract for A when bought on D0 with card `4000 0000 0000 0341`: parent order paid $13.00; the D1 renewal attempt fails; `payment_failed` mail to customer and admin; the subscription stays `arraysubs-active` for 1 day, moves to `arraysubs-on-hold`, and is `arraysubs-cancelled` 3 days after that — all inside the window.
6. Contract for B: bought with Paddle sandbox by `slt-paddle` only; Paddle owns the schedule via `next_billed_at`, so the Renew Early button must stay hidden even though `allow_early_renew` is on (`early_renewal: false`), and SCA/3DS is not applicable (`sca: false`).

## Emails expected
| # | Email | Trigger point | Recipient | Subject contains | Verify with |
|---|---|---|---|---|---|
| 1 | NONE EXPECTED | Both publishes | — | — | `mailpit-agent latest-id` unchanged from step 1 |

## Evidence to capture
- Screenshots: `SLT-PROD-16-01-retry-subscription-tab.png`, `SLT-PROD-16-02-paddle-subscription-tab.png`, `SLT-PROD-16-03-frontends.png`.
- Both product IDs; both meta dumps.

## Pass criteria
- [ ] Both published as plain day/1 subscriptions at $13.00 and $11.00
- [ ] Neither has flex sync, trial, fee or different renewal price
- [ ] Both storefront summaries show a plain daily schedule
- [ ] Registry tags each product with its exclusive gateway
- [ ] Zero mail, nothing purchased

## Isolation / teardown
- State handoff: `SLT Retry Daily` may ONLY be bought by `slt-fail` with card `4000 0000 0000 0341`, and must be bought on D0 or D1 so the full active -> on-hold -> cancelled ladder completes before D9. A second copy of the ladder using `4000 0000 0000 9995` (insufficient funds) may reuse the same product on a different day, but never on the same account concurrently. `SLT Paddle Daily` may ONLY be bought by `slt-paddle` with the Paddle sandbox card, and never with Stripe.
- Restores: nothing. Both deleted by SLT-SETUP-99.

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
