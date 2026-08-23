---
id: 38
title: SLT-PROD-03 Create SLT2 Trial Four Day, the trial product with an in-window reminder boundary
status: todo
priority: high
created: 2026-08-22T19:10:00+02:00
updated: 2026-08-22T19:10:00+02:00
tags:
    - cycle-2
    - granular
    - setup
    - products
    - day-02
due: "2026-08-25"
estimate: 30m
depends_on:
    - 10
class: standard
---

> **SLT-PROD-03** · group `catalog` · scheduled **D02** (2026-08-25)

> Read `README.md` (environment + isolation contract), `calendar.md` (this day's exact
> ordering — it is binding, not advisory) and `plan-audit.md` before starting.

## Objective
Provide the trial product whose configured 3-days-before reminder boundary lands inside the window. `SLT-CHK-15` buys it after 12:00 site on D2 (2026-08-25), putting the reminder on D3 and conversion on D6. `SLT-EML-09` requires the natural trial-ending action/email exactly once. The product also carries `trials.require_payment_method = true`, so a card must still be collected on the $0.00 order.

## Scope
- Gateway: N/A
- Checkout: N/A (creation and storefront verification only)
- Account: N/A (creation only)
- Plugins: free-only

## Preconditions
- SLT-SETUP-01 complete.
- Deliberate deviation from "e.g. 2 days": 4 days is the shortest trial that leaves a valid 3-days-before reminder window. SLT-PROD-02 keeps the 2-day case as the suppressed-reminder negative.
- Baseline `trials.require_payment_method=true` and `trials.one_trial_per_customer=false` are unchanged.

## Test data
| Item | Value |
|---|---|
| Product | SLT2 Trial Four Day / slug `slt2-trial-four-day` |
| Account | N/A |
| Coupon | N/A |
| Card | N/A |
| Amounts | Regular price $12.00; charge today $0.00; first paid charge $12.00 at the recorded `trial_end+k` gate late 2026-08-29 or shortly after local midnight 2026-08-30; renewal $12.00/day |

## Steps
1. Capture `mailpit-agent latest-id`.
2. `agent-browser --session admin-SLT-PROD-03 open "https://mirror-help.arrayhash.com/wp-admin/post-new.php?post_type=product"` -> `snapshot -i`.
3. **Product title**: `SLT2 Trial Four Day`. **Description**: `SLT2 window product. 4-day free trial, card required. Delete on 2026-09-05.`
4. Type **Simple product**; tick **Virtual**; tick **Subscription [ArraySubs]**.
5. **General** tab: **Regular price ($)** = `12.00`.
6. **Subscription [ArraySubs]** tab: **Billing Period** = `Day`; **Billing Interval** = `1`; **Subscription Length** = `0`; **Trial Length** = `4`; **Trial Period** = `Day`; **Sign-up Fee ($)** empty; **Different Renewal Price** unticked. Capture `SLT-PROD-03-01-subscription-tab.png`.
7. Confirm again that the **Flexible Renewal Sync** section is hidden while a trial is set; capture `SLT-PROD-03-02-flex-hidden-by-trial.png`.
8. Slug `slt2-trial-four-day`. Publish. Reload and re-verify.
9. `wp post meta list <ID> --keys=_is_subscription,_subscription_period,_subscription_interval,_trial_length,_trial_period,_regular_price,_signup_fee --allow-root`.
10. Before any storefront or downstream checkout access, append only this parent product ID to Shop Access rule `<D0_SHOP_ACCESS_RULE_ID>` under `exclusion_product_ids` through **Member Access → Shop Access**. Preserve every other field and every prior exclusion; re-read the raw option and require the ID exactly once.
11. As `--session guest-SLT-PROD-03`, open `https://mirror-help.arrayhash.com/product/slt2-trial-four-day/?slt2-cache-bust=<timestamp>` and confirm the trial is advertised in the price/schedule summary; capture `SLT-PROD-03-03-frontend-trial-summary.png`. Do not add to cart.
12. Append the ID and verified Shop Access exclusion to the registry.

## Expected results
1. Published simple + virtual + subscription, slug `slt2-trial-four-day`.
2. `_trial_length=4`, `_trial_period=day`, `_subscription_period=day`, `_subscription_interval=1`, `_regular_price=12.00`.
3. Flex sync section hidden; `_arraysubs_flex_sync_enabled` absent.
4. Parent product ID is present exactly once in the preserved Shop Access exclusion list before storefront access, and the product page shows the 4-day free trial in the subscription summary.
5. Date arithmetic: trial start = the live checkout timestamp; `_trial_end_date` = start + 4 days; reminder gate = `trial_end−3d` plus the current scheduler offset contract; invoice/charge gates derive from the live end date. Task 34 requires the reminder action and one trial-ending email.

## Emails expected
| # | Email | Trigger point | Recipient | Subject contains | Verify with |
|---|---|---|---|---|---|
| 1 | NONE EXPECTED | Product publish | — | — | Complete delta after the step-1 baseline; zero task-attributable mail, while unrelated/background mail is allowed and classified |

## Evidence to capture
- Screenshots: `SLT-PROD-03-01-subscription-tab.png`, `SLT-PROD-03-02-flex-hidden-by-trial.png`, `SLT-PROD-03-03-frontend-trial-summary.png`.
- Product ID; meta list output; raw Shop Access rule showing the ID exactly once.

## Pass criteria
- [ ] Published with trial 4 day and price 12.00
- [ ] Flex sync hidden by trial
- [ ] Front end advertises the trial
- [ ] Metas exactly as listed
- [ ] Parent product ID is present exactly once in the preserved Shop Access exclusion list before storefront access
- [ ] Zero mail

## Isolation / teardown
- State handoff: `SLT-CHK-15` buys this exactly once as `slt2-trial`. Task 34 requires trial-started, trial-ending and conversion/payment emails according to the live activation path, with exact dedupe and no substitute fixture.
- Restores: close only `admin-SLT-PROD-03` and `guest-SLT-PROD-03`; SLT-SETUP-99A restores the exact pre-window Shop Access rule snapshot. Product deleted by SLT-SETUP-99B.

---

### Fresh-cycle validation contract

- Re-derive every ID, count, option value, gateway capability, scheduler timestamp, and email baseline on this run; no prior-cycle result is evidence.
- Create and mutate only registered `SLT2 ` / `slt2-*` fixtures. Legacy `SLT` and all non-SLT2 data are read-only controls.
- Automatic-gateway scope is Stripe and Paddle only. Stripe is the primary path; run Paddle parity wherever Paddle supports the behavior. Do not test or configure PayPal or Mollie.
- ArraySubs core must own its Stripe/Paddle integration, renewal, retry, webhook, REST, refund, and customer-payment services with Pro inactive; vendor host classes retain their expected ownership.
- Browser-required assertions use Vercel `agent-browser` with isolated task/role sessions and current snapshot refs. WP-CLI always includes `--allow-root`.
- Update the lifecycle card, the matching `qa/progress/` card, and `qa/issues/` for every new regression. Evidence belongs to this fresh cycle only.
