---
id: 59
title: SLT-PROD-10 Create SLT Box Daily (pro Subscription Box) plus its three eligible children
status: done
priority: high
created: 2026-08-02T03:43:08.133330777+02:00
updated: 2026-08-05T11:47:50.3271392+02:00
started: 2026-08-05T11:47:50.157425112+02:00
completed: 2026-08-05T11:47:50.157425112+02:00
tags:
    - setup
    - products
    - day-03
due: "2026-08-05"
estimate: 1h 30m
depends_on:
    - 10
class: standard
---

> **SLT-PROD-10** · group `catalog` · scheduled **D03** (2026-08-05)

> Read `README.md` (environment + isolation contract), `calendar.md` (this day's exact
> ordering — it is binding, not advisory) and `plan-audit.md` before starting.

## Objective
Build the pro Subscription Box end to end through the Configure Box modal, honouring the eligibility rules enforced by `BoxConfig::isEligibleChildProduct()`: children must be SIMPLE products; non-subscription children are always eligible; subscription children must match the box period AND interval exactly and must not use a different renewal price. The box itself is priced dynamically — the saver clears `_regular_price`/`_sale_price`/`_price`, sets `_sold_individually=yes`, forces `_trial_length=0` and `_signup_fee=0`, and deletes `_enable_renewal_price`.

## Scope
- Gateway: N/A
- Checkout: N/A (creation and storefront wizard verification only)
- Account: N/A (creation only)
- Plugins: pro-required

## Preconditions
- SLT-SETUP-01 complete. `arraysubs_has_module('SubscriptionBox') === 1` verified.
- Box schedule day/2 is chosen so the D4 purchase renews repeatedly inside the window (2026-08-08, 2026-08-10, 2026-08-12 at its anniversary clock, plus spread offset) with no time travel, and so a matching day/2 subscription child is possible.
- Sessions `admin-SLT-PROD-10` and `guest-SLT-PROD-10` are exclusive to this task.

## Test data
| Item | Value |
|---|---|
| Product | SLT Box Daily / slug `slt-box-daily` (type Subscription Box [ArraySubs]) |
| Children | SLT Box Item A ($4.00, non-sub), SLT Box Item B ($6.00, non-sub), SLT Box Sub Item ($5.00, day/2 subscription) |
| Account | N/A |
| Coupon | N/A |
| Card | N/A |
| Amounts | box total is dynamic; a 2-item selection of A+B = $10.00 recurring every 2 days before discounts |

## Steps
1. Capture `mailpit-agent latest-id`.
2. In `agent-browser --session admin-SLT-PROD-10`, create `SLT Box Item A`: new product, **Simple product**, **Virtual**, do NOT tick Subscription, **Regular price ($)** `4.00`, slug `slt-box-item-a`, Publish.
3. In the same admin session create `SLT Box Item B` the same way at `6.00`, slug `slt-box-item-b`.
4. In the same admin session create `SLT Box Sub Item`: **Simple product**, **Virtual**, tick **Subscription [ArraySubs]**, **Regular price ($)** `5.00`, Subscription tab: **Billing Period** `Day`, **Billing Interval** `2`, **Subscription Length** `0`, **Trial Length** `0`, no signup fee, **Different Renewal Price** unticked. Slug `slt-box-sub-item`. Publish. (Interval 2 is mandatory — a mismatch makes it invisible to the box product search.)
5. In the same admin session create a new product: title `SLT Box Daily`. **Description**: `SLT window product. Pro subscription box, bills every 2 days. Delete on 2026-08-15.`
6. Set the product type dropdown to **Subscription Box [ArraySubs]**. The General tab now shows the **Subscription Box Details** panel.
7. Click **Configure Box** to open the `Configure Subscription Box` modal.
8. In **Box Schedule**: **Billing Period** = `Day`; **Billing Interval** = `2`; **Subscription Length** = `0`; leave **Keep signup fees** UNCHECKED (none of the children carry a fee, and unchecked keeps the recurring total clean). Capture `SLT-PROD-10-01-modal-schedule.png`.
9. Move to **Box Steps** and use the automatically seeded first step (do **not** click **Add Step**, which would create an unintended second step). Set its title to `Pick your items`. **Add Element** of type product and select `SLT Box Item A`; add a second product element for `SLT Box Item B`; add a third product element and search for `SLT Box Sub Item` — it must appear because its day/2 cycle matches. Capture the completed step as `SLT-PROD-10-02-modal-steps.png`. As a negative probe, search for `SLT Daily Core` (day/1) and `SLT Renewal Price Step` (different renewal price) and confirm neither is offered; capture `SLT-PROD-10-03-ineligible-search-empty.png`.
10. Move to **Discounts & Freebies**: leave **Ranges Based On** at `Total Value`, enter `15` for the required **Max Amount to Configure** drawing scale, and add NO range points. Zero range points is the current UI's no-discount baseline (later tasks may clone it if range pricing needs coverage).
11. Move to **Flexible Renewal Sync**: leave it DISABLED for this box. A day/2 nominal cycle is below `SegmentPlan::MIN_CYCLE_DAYS = 3`, so `syncRenewalSyncMeta()` would write a plan that `getConfig()` then rejects; keeping it off avoids a meaningless half-state.
12. **Save Configuration**, then **Publish** the product with slug `slt-box-daily`.
13. Reload and confirm the read-only summary shows `Billing: every 2 days · until cancelled`, `Signup fees: Not charged`, `Flexible renewal sync: Store default`, and 1 step / 3 elements; capture `SLT-PROD-10-04-admin-summary.png`.
14. `wp post meta list <BOX_ID> --keys=_arraysubs_subscription_box,_is_subscription,_subscription_period,_subscription_interval,_subscription_length,_trial_length,_signup_fee,_sold_individually,_regular_price,_price,_arraysubs_box_config --allow-root`.
15. Before any storefront/cart/downstream checkout access, append only the box parent and all three child parent IDs to Shop Access rule `rule_1784662676378_maa3te08s` under `exclusion_product_ids` through **Member Access → Shop Access**. Preserve every other field and every prior exclusion; re-read the raw option and require each of the four new IDs exactly once.
16. As `--session guest-SLT-PROD-10`, open `https://mirror-help.arrayhash.com/product/slt-box-daily/?slt-cache-bust=<timestamp>` -> `snapshot -i` -> click the box launcher button and step through the overlay wizard, selecting A x1 and B x1; read the computed total and capture `SLT-PROD-10-05-frontend-wizard-total.png`; then close the overlay WITHOUT adding to cart.
17. Inspect the complete Mailpit delta after step 1 and require zero task-attributable mail, append the box ID, three child IDs, and verified Shop Access exclusions to the registry, and close only `admin-SLT-PROD-10` and `guest-SLT-PROD-10`.

## Expected results
1. Three children published: A ($4.00) and B ($6.00) as plain simple products, Sub Item as a day/2 simple subscription at $5.00.
2. `SLT Box Daily` published with type `arraysubs_subscription_box`, `_arraysubs_subscription_box=yes`, `_is_subscription=yes`.
3. Engine meta mirrored from the modal: `_subscription_period=day`, `_subscription_interval=2`, `_subscription_length=0`; forced values `_trial_length=0`, `_signup_fee=0`, `_sold_individually=yes`; `_regular_price` and `_price` EMPTY; `_enable_renewal_price` absent.
4. `_arraysubs_box_config` holds valid JSON with one step and three elements.
5. `_arraysubs_flex_sync_enabled` is absent (sync left off in the modal).
6. The product search inside the modal offered `SLT Box Sub Item` but NOT `SLT Daily Core` (cycle mismatch) and NOT `SLT Renewal Price Step` (different renewal price).
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
- [x] Three eligible children published with the exact prices/cycle
- [x] Box published with type arraysubs_subscription_box and 1 step / 3 elements
- [x] Engine meta mirrored and forced values correct (no stored price, sold individually)
- [x] Ineligible products absent from the modal search (both reasons)
- [x] Wizard computes $10.00 every 2 days for A+B
- [x] Box and all three child parent IDs are each present exactly once in the preserved Shop Access exclusion list
- [x] Zero mail, nothing added to cart

## Isolation / teardown
- State handoff: buy as `slt-core`. Behaviour later tasks must assume: adding a box EMPTIES the cart first; contents are added to the order at zero cost while the box line carries the whole recurring total; free trials are switched off for everything inside a box; the frozen contents live on `_arraysubs_box_contents`.
- Restores: overlay closed, cart untouched; SLT-SETUP-99A restores the exact pre-window Shop Access rule snapshot. Box and all three children are deleted by SLT-SETUP-99B.

## D03 execution result — 2026-08-05

PASS. Published parent `12600` and children `12591`, `12594`, and `12597` with the exact authored types, prices, and day/2 matching cycle. The saved parent is `arraysubs_subscription_box`, dynamically priced, sold individually, and contains one `Pick your items` step with the exact three child IDs; its renewal-price and flexible-sync keys are absent. The modal excluded both negative probes and all subscription-box REST requests returned HTTP 200.

Two QA-plan defects were corrected in this task only: the first step is automatically seeded, so it must be reused rather than adding an unintended second step; and the current no-discount UI requires a `15` Max Amount drawing scale with zero range points. No product source was inspected or changed.

Through the real Member Access UI, Shop Access exclusions moved from 18 to 22 by appending only `12600`, `12591`, `12594`, and `12597`, each exactly once, before storefront access. The cache-busted guest wizard computed `$10.00 / 2 days` for A x1 plus B x1. It was closed without Add to Cart and the final cart was visibly empty. Mailpit stayed at `56kcLytDylTWndyI4kEeYS`; browser error buffers were empty. Registry page `11847` contains exactly one handoff block. Evidence: `/home/server-manager/slt-evidence/SLT-PROD-10-facts.txt` and the task-prefixed screenshots.

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
