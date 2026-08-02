---
id: 70
title: My Account subscriptions list and detail for slt-core - every field and every self-service action under the frozen baseline
status: todo
priority: critical
created: 2026-08-02T03:43:09.225994237+02:00
updated: 2026-08-02T03:43:19.732210093+02:00
tags:
    - admin
    - portal
    - day-04
    - has-conflicts
due: "2026-08-06"
estimate: 2h
depends_on:
    - 11
    - 12
    - 5
    - 7
class: standard
---

> **SLT-MYA-01** · group `admin` · scheduled **D04** (2026-08-06)

> Read `README.md` (environment + isolation contract), `calendar.md` (this day's exact
> ordering — it is binding, not advisory) and `plan-audit.md` before starting.

### ⚠ Conflict resolutions that apply to this task

**`high` · shared-global-setting / multi-day deviation vs frozen baseline** — with `SLT-LIFE-03`, `SLT-SW-07`, `SLT-SW-10`, `SLT-LIFE-02`, `SLT-MYA-03`, `SLT-MYA-04`

- *Problem:* SLT-LIFE-03 flips two global settings out of baseline - skip_renewal.enabled false->true and skip_renewal.cutoff_days 2->0 - and restores them only at its step 7, which happens two days later (after the shifted cycle charges). That is a 2-3 day site-wide deviation in which every customer portal renders a 'Skip Next Renewal' control. Colliding audits: SLT-MYA-01 expected result 5 lists 'Skip Next Renewal' among the five actions an active subscription must expose - which is wrong against the frozen baseline (skip_renewal.enabled=false) and only accidentally right if MYA-01 happens to run inside LIFE-03's bracket. SLT-ADM-03 asserts the opposite ('Skip Renewal is expectedly unavailable'), so the two tasks contradict each other. SLT-SW-07, SLT-SW-10, SLT-LIFE-02, SLT-MYA-03 and SLT-MYA-04 all screenshot the portal Actions card on D5-D7 and would file the Skip control as unexpected UI.
- *Required fix:* Two changes. (1) Correct SLT-MYA-01 expected result 5 to the four baseline actions - Change Plan, Cancel Subscription, Renew Early, Pause Subscription - and add 'Skip Next Renewal MUST be absent (skip_renewal.enabled=false)'; quote the registry WINDOW BASELINE table as C14 requires. (2) Compress LIFE-03's deviation to a single short bracket: settings ON, perform skip / undo / 5-cycle clamp / undo / final 1-cycle skip, settings RESTORED, all inside one <30 min window on D5 with open/close UTC recorded - the pending skip lives in subscription meta (_skip_cycles_remaining, _original_next_payment_date) and completeSkippedCycles() runs off the renewal path, so the setting does not need to stay on for the shifted cycle to complete. Verify that on the day; if completion does prove to require the flag, move LIFE-03 wholesale to D8-D9 where no portal audit runs. Also correct LIFE-03's internal dates: it is a D5 (2026-08-07) task, so D_now = 08-08, skip1 -> 08-09, skip3 -> 08-11, original due 08-08 shows nothing (watch D7 negative) and the shifted $20.00 charge lands 08-09 PM (watch D8) - which also clears 2026-08-10 for SLT-LIFE-01.

---
## Objective
Audit the customer portal for `slt-core`, who by D4 holds several SLT subscriptions of different shapes: the list table, the detail table, Related Orders, and every self-service action the frozen baseline exposes plus the ones that must not appear.

## Scope
- Gateway: Stripe test
- Checkout: N/A - portal only, no purchase, no action executed
- Account: existing (`slt-core`)
- Plugins: both (free CustomerPortal + pro EarlyRenew row)

## Preconditions
- SLT-SETUP-03 done. slt-core's D0 buys (Daily Core, Fixed Three Cycles, Lifetime One Time) and D3 buys (Signup Fee Daily, Renewal Price Step) complete.
- Quote the registry **WINDOW BASELINE (frozen)** table: `allow_early_renew`, `allow_reactivation`, `pause_subscription.enabled`, `customer_can_pause`, `allow_cancellation`, `retention_offers_enabled` all true - that is why Renew Early and Pause render. All SLT products are Virtual, so no Shipping Address section may appear.

## Test data
| Item | Value |
|---|---|
| Account | slt-core / `SltQa!2026#Pass`, session `--session customer-MYA-01` |
| URLs | `https://mirror-help.arrayhash.com/my-account/subscriptions/`, `/my-account/view-subscription/<ID>/` |
| Amounts | Daily Core $10.00/day; Fixed Three Cycles $7.00/2d; Lifetime one-off; Signup Fee $9.00/day; Price Step $5.00/day |

## Steps
1. `mailpit-agent latest-id` -> `MB01`.
2. Open `.../my-account/` -> log in as slt-core. Snapshot the account menu: `Subscriptions` (count-badge class) and `My Features` must both be present.
3. Open the list URL -> `snapshot -i` -> screenshot. Record heading `My Subscriptions`, headers `Product / Status / Next Payment / Total / Actions`, and per row: product, status label, next payment as rendered (site-local UTC+6), Total, actions offered.
4. Read `Showing X-Y of N subscriptions`; per-page is 10 (`MyAccountHooks.php:283`) so no pagination nav may render. Open `/my-account/subscriptions/page/2/`.
5. Open the **SLT Daily Core** detail page; screenshot. Record rows `Subscription #<ID>`, `Status:`, `Product:`, `Start Date:`, `Next Payment:`, `Recurring Amount:`, `Payment Method:` and the `Manage payment methods` target.
6. Record `Subscription Actions` and `Manage Your Subscription` verbatim, whether a `Shipping Address` section renders, and `Related Orders` (`Order / Date / Status / Total / Actions`) including which rows offer `Pay`.
7. Repeat 5-6 for **SLT Lifetime One Time**, **SLT Fixed Three Cycles**, **SLT Signup Fee Daily**, **SLT Renewal Price Step**.
8. Still as slt-core open `/my-account/view-subscription/<an slt-paddle subscription ID>/`, then `/my-account/view-subscription/999999/`.
9. `mailpit-agent latest-id` must equal `MB01`. Close only this session.

## Expected results
1. One row per slt-core subscription; status label matches the post status; `View` is the only action.
2. Next Payment renders in UTC+6 and equals `_next_payment_date` converted from UTC; Total = `_recurring_amount` in USD, no tax line, plus the schedule string.
3. `Showing 1-N of N subscriptions`, no pagination nav, `/page/2/` empty and notice-free.
4. Detail shows all seven rows, `Stripe` as payment-method title, and the link resolving to `/my-account/payment-methods/`.
5. Active subscriptions expose exactly `Change Plan`, `Cancel Subscription`, `Renew Early`, `Skip Next Renewal`, `Pause Subscription`. `Undo Scheduled Cancellation`, `Reactivate Subscription`, `Retry Payment` and `Shipping Address` are absent.
6. SLT Lifetime One Time shows `Next Payment: Lifetime Deal - No recurring payment` and offers no cycle action; SLT Fixed Three Cycles shows an `End Date:` row.
7. Related Orders lists parent plus every renewal with correct statuses/totals; `Pay` appears only on an order still needing payment.
8. Both step-8 probes return `Subscription not found or you do not have permission to view it.`, no PHP fatal.

## Emails expected
| # | Email | Trigger point | Recipient | Subject contains | Verify with |
|---|---|---|---|---|---|
| 1 | NONE EXPECTED | Entire task, read-only browsing | - | - | `mailpit-agent latest-id` at step 9 must equal `MB01` |

## Evidence to capture
- Screenshots `SLT-MYA-01-01-list.png`, `-02-detail-daily-core.png`, `-03-actions-block.png`, `-04-detail-lifetime.png`.
- Notes table: each subscription id with status, `_next_payment_date` (UTC), site-local rendering, amount.
- Console/network errors per page; any PHP notice from step 4 or 8.

## Pass criteria
- [ ] List rows, totals and site-local dates correct; `/page/2/` clean with no pagination nav
- [ ] Five expected actions present, four expected absences confirmed
- [ ] Lifetime text exact; Fixed Three Cycles shows End Date; detail rows complete
- [ ] Related Orders complete, Pay placement correct; invalid-access probes return the notice
- [ ] Zero mail sent

## Isolation / teardown
- Read-only: no action clicked, no cart touched, no setting changed. Hands SLT-MYA-02/03/04 the subscription-ID table.
- Close only `--session customer-MYA-01`; never `close --all`.

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
