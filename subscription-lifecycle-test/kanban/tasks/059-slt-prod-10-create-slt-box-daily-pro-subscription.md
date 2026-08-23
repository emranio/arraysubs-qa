---
id: 59
title: SLT-PROD-10 Create SLT2 Box Daily (free Subscription Box) plus its three eligible children
status: todo
priority: high
created: 2026-08-22T19:10:00+02:00
updated: 2026-08-22T19:10:00+02:00
tags:
    - cycle-2
    - granular
    - setup
    - products
    - day-03
due: "2026-08-26"
estimate: 1h 30m
depends_on:
    - 10
class: standard
---

> **SLT-PROD-10** · group `catalog` · scheduled **D03** (2026-08-26)

> Read `README.md` (environment + isolation contract), `calendar.md` (this day's exact
> ordering — it is binding, not advisory) and `plan-audit.md` before starting.

## Objective
Build the free Subscription Box end to end through the Configure Box modal, honouring the eligibility rules enforced by `BoxConfig::isEligibleChildProduct()`: children must be SIMPLE products; non-subscription children are always eligible; subscription children must match the box period AND interval exactly and must not use a different renewal price. The box itself is priced dynamically — the saver clears `_regular_price`/`_sale_price`/`_price`, sets `_sold_individually=yes`, forces `_trial_length=0` and `_signup_fee=0`, and deletes `_enable_renewal_price`.

## Scope
- Gateway: N/A
- Checkout: N/A (creation and storefront wizard verification only)
- Account: N/A (creation only)
- Plugins: free/core-owned Subscription Box

## Preconditions
- SLT-SETUP-01 complete. `arraysubs_has_module('SubscriptionBox') === 1` verified.
- Box schedule day/2 is chosen so the D4 purchase renews repeatedly inside the window (2026-08-29, 2026-08-31, 2026-09-02 at its anniversary clock, plus spread offset) with no time travel, and so a matching day/2 subscription child is possible.
- Sessions `admin-SLT-PROD-10` and `guest-SLT-PROD-10` are exclusive to this task.

## Test data
| Item | Value |
|---|---|
| Product | SLT2 Box Daily / slug `slt2-box-daily` (type Subscription Box [ArraySubs]) |
| Children | SLT2 Box Item A ($4.00, non-sub), SLT2 Box Item B ($6.00, non-sub), SLT2 Box Sub Item ($5.00, day/2 subscription) |
| Account | N/A |
| Coupon | N/A |
| Card | N/A |
| Amounts | box total is dynamic; a 2-item selection of A+B = $10.00 recurring every 2 days before discounts |

## Steps
1. Capture `mailpit-agent latest-id`.
2. In `agent-browser --session admin-SLT-PROD-10`, create `SLT2 Box Item A`: new product, **Simple product**, **Virtual**, do NOT tick Subscription, **Regular price ($)** `4.00`, slug `slt2-box-item-a`, Publish.
3. In the same admin session create `SLT2 Box Item B` the same way at `6.00`, slug `slt2-box-item-b`.
4. In the same admin session create `SLT2 Box Sub Item`: **Simple product**, **Virtual**, tick **Subscription [ArraySubs]**, **Regular price ($)** `5.00`, Subscription tab: **Billing Period** `Day`, **Billing Interval** `2`, **Subscription Length** `0`, **Trial Length** `0`, no signup fee, **Different Renewal Price** unticked. Slug `slt2-box-sub-item`. Publish. (Interval 2 is mandatory — a mismatch makes it invisible to the box product search.)
5. In the same admin session create a new product: title `SLT2 Box Daily`. **Description**: `SLT2 window product. free Subscription Box, bills every 2 days. Delete on 2026-09-05.`
6. Set the product type dropdown to **Subscription Box [ArraySubs]**. The General tab now shows the **Subscription Box Details** panel.
7. Click **Configure Box** to open the `Configure Subscription Box` modal.
8. In **Box Schedule**: **Billing Period** = `Day`; **Billing Interval** = `2`; **Subscription Length** = `0`; leave **Keep signup fees** UNCHECKED (none of the children carry a fee, and unchecked keeps the recurring total clean). Capture `SLT-PROD-10-01-modal-schedule.png`.
9. Move to **Box Steps** and use the automatically seeded first step (do **not** click **Add Step**, which would create an unintended second step). Set its title to `Pick your items`. **Add Element** of type product and select `SLT2 Box Item A`; add a second product element for `SLT2 Box Item B`; add a third product element and search for `SLT2 Box Sub Item` — it must appear because its day/2 cycle matches. Capture the completed step as `SLT-PROD-10-02-modal-steps.png`. As a negative probe, search for `SLT2 Daily Core` (day/1) and `SLT2 Renewal Price Step` (different renewal price) and confirm neither is offered; capture `SLT-PROD-10-03-ineligible-search-empty.png`.
10. Move to **Discounts & Freebies**: leave **Ranges Based On** at `Total Value`, enter `15` for the required **Max Amount to Configure** drawing scale, and add NO range points. Zero range points is the current UI's no-discount baseline (later tasks may clone it if range pricing needs coverage).
11. Move to **Flexible Renewal Sync**: leave it DISABLED for this box. A day/2 nominal cycle is below `SegmentPlan::MIN_CYCLE_DAYS = 3`, so `syncRenewalSyncMeta()` would write a plan that `getConfig()` then rejects; keeping it off avoids a meaningless half-state.
12. **Save Configuration**, then **Publish** the product with slug `slt2-box-daily`.
13. Reload and confirm the read-only summary shows `Billing: every 2 days · until cancelled`, `Signup fees: Not charged`, `Flexible renewal sync: Store default`, and 1 step / 3 elements; capture `SLT-PROD-10-04-admin-summary.png`.
14. `wp post meta list <BOX_ID> --keys=_arraysubs_subscription_box,_is_subscription,_subscription_period,_subscription_interval,_subscription_length,_trial_length,_signup_fee,_sold_individually,_regular_price,_price,_arraysubs_box_config --allow-root`.
15. Before any storefront/cart/downstream checkout access, append only the box parent and all three child parent IDs to Shop Access rule `<D0_SHOP_ACCESS_RULE_ID>` under `exclusion_product_ids` through **Member Access → Shop Access**. Preserve every other field and every prior exclusion; re-read the raw option and require each of the four new IDs exactly once.
16. As `--session guest-SLT-PROD-10`, open `https://mirror-help.arrayhash.com/product/slt2-box-daily/?slt2-cache-bust=<timestamp>` -> `snapshot -i` -> click the box launcher button and step through the overlay wizard, selecting A x1 and B x1; read the computed total and capture `SLT-PROD-10-05-frontend-wizard-total.png`; then close the overlay WITHOUT adding to cart.
17. Inspect the complete Mailpit delta after step 1 and require zero task-attributable mail, append the box ID, three child IDs, and verified Shop Access exclusions to the registry, and close only `admin-SLT-PROD-10` and `guest-SLT-PROD-10`.

## Expected results
1. Three children published: A ($4.00) and B ($6.00) as plain simple products, Sub Item as a day/2 simple subscription at $5.00.
2. `SLT2 Box Daily` published with type `arraysubs_subscription_box`, `_arraysubs_subscription_box=yes`, `_is_subscription=yes`.
3. Engine meta mirrored from the modal: `_subscription_period=day`, `_subscription_interval=2`, `_subscription_length=0`; forced values `_trial_length=0`, `_signup_fee=0`, `_sold_individually=yes`; `_regular_price` and `_price` EMPTY; `_enable_renewal_price` absent.
4. `_arraysubs_box_config` holds valid JSON with one step and three elements.
5. `_arraysubs_flex_sync_enabled` is absent (sync left off in the modal).
6. The product search inside the modal offered `SLT2 Box Sub Item` but NOT `SLT2 Daily Core` (cycle mismatch) and NOT `SLT2 Renewal Price Step` (different renewal price).
7. The box and three child parent IDs are each present exactly once in the preserved Shop Access exclusion list before storefront access.
8. The storefront wizard opens, accepts A+B and shows a recurring total of `$10.00` every 2 days; no cart item is created.

## Emails expected
| # | Email | Trigger point | Recipient | Subject contains | Verify with |
|---|---|---|---|---|---|
| 1 | NONE EXPECTED | All four product publishes and the wizard preview | — | — | Complete delta after the step-1 baseline; zero task-attributable mail, while unrelated/background mail is allowed and classified |

## Evidence to capture
- Screenshots: `SLT-PROD-10-01-modal-schedule.png`, `SLT-PROD-10-02-modal-steps.png`, `SLT-PROD-10-03-ineligible-search-empty.png`, `SLT-PROD-10-04-admin-summary.png`, `SLT-PROD-10-05-frontend-wizard-total.png`.
- Box ID + three child IDs; full meta dump; the `_arraysubs_box_config` JSON; raw Shop Access rule showing all four IDs exactly once; REST errors from `arraysubs/v1/` during the modal (network tab).

## Pass criteria
- [ ] Three eligible children published with the exact prices/cycle
- [ ] Box published with type arraysubs_subscription_box and 1 step / 3 elements
- [ ] Engine meta mirrored and forced values correct (no stored price, sold individually)
- [ ] Ineligible products absent from the modal search (both reasons)
- [ ] Wizard computes $10.00 every 2 days for A+B
- [ ] Box and all three child parent IDs are each present exactly once in the preserved Shop Access exclusion list
- [ ] Zero mail, nothing added to cart

## Isolation / teardown
- State handoff: buy as `slt2-core`. Behaviour later tasks must assume: adding a box EMPTIES the cart first; contents are added to the order at zero cost while the box line carries the whole recurring total; free trials are switched off for everything inside a box; the frozen contents live on `_arraysubs_box_contents`.
- Restores: overlay closed, cart untouched; SLT-SETUP-99A restores the exact pre-window Shop Access rule snapshot. Box and all three children are deleted by SLT-SETUP-99B.

---

### Fresh-cycle validation contract

- Re-derive every ID, count, option value, gateway capability, scheduler timestamp, and email baseline on this run; no prior-cycle result is evidence.
- Create and mutate only registered `SLT2 ` / `slt2-*` fixtures. Legacy `SLT` and all non-SLT2 data are read-only controls.
- Automatic-gateway scope is Stripe and Paddle only. Stripe is the primary path; run Paddle parity wherever Paddle supports the behavior. Do not test or configure PayPal or Mollie.
- ArraySubs core must own its Stripe/Paddle integration, renewal, retry, webhook, REST, refund, and customer-payment services with Pro inactive; vendor host classes retain their expected ownership.
- Browser-required assertions use Vercel `agent-browser` with isolated task/role sessions and current snapshot refs. WP-CLI always includes `--allow-root`.
- Update the lifecycle card, the matching `qa/progress/` card, and `qa/issues/` for every new regression. Evidence belongs to this fresh cycle only.
