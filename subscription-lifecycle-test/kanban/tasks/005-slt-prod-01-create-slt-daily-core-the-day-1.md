---
id: 5
title: SLT-PROD-01 Create SLT Daily Core, the day/1 workhorse subscription product
status: done
priority: critical
created: 2026-08-02T03:43:03.30633431+02:00
updated: 2026-08-02T14:02:26.069201505+02:00
started: 2026-08-02T14:02:26.069200473+02:00
completed: 2026-08-02T14:02:26.069200473+02:00
tags:
    - setup
    - products
    - day-00
due: "2026-08-02"
estimate: 30m
depends_on:
    - 10
class: standard
---

> **SLT-PROD-01** · group `catalog` · scheduled **D00** (2026-08-02)

> Read `README.md` (environment + isolation contract), `calendar.md` (this day's exact
> ordering — it is binding, not advisory) and `plan-audit.md` before starting.

## Objective
Create the plainest possible recurring product — day period, interval 1, no trial, no signup fee, no length limit, no flexible sync — so that renewals genuinely fire on their own once per day for the whole window and every other test has a known-good control to compare against.

## Scope
- Gateway: N/A
- Checkout: N/A (creation and storefront verification only)
- Account: N/A (creation only)
- Plugins: free-only

## Preconditions
- SLT-SETUP-01 complete (conventions, evidence root, registry page).
- Billing period `day` with interval 1 is chosen deliberately: the window is 10 real days, and `arraysubs_calculate_next_payment_from_date()` gives `start + 1 day`, so this product produces up to 9 unattended renewals inside D0..D9 with zero time-travel.

## Test data
| Item | Value |
|---|---|
| Product | SLT Daily Core / slug `slt-daily-core` |
| Account | N/A |
| Coupon | N/A |
| Card | N/A |
| Amounts | Regular price $10.00; expected first charge $10.00; expected renewal $10.00/day |

## Steps
1. Capture `mailpit-agent latest-id` before any admin save.
2. `agent-browser --session admin-SLT-PROD-01 open "https://mirror-help.arrayhash.com/wp-admin/post-new.php?post_type=product"` -> `agent-browser --session admin-SLT-PROD-01 snapshot -i`.
3. **Product title**: `SLT Daily Core`.
4. **Description**: `SLT window product. Daily recurring workhorse. Delete on 2026-08-15.`
5. In the **Product data** panel keep the type dropdown on **Simple product**; tick **Virtual**; leave **Downloadable** unticked.
6. Tick the header checkbox **Subscription [ArraySubs]** (this writes `_is_subscription=yes`; it renders next to Virtual/Downloadable and is only offered for simple and variable types).
7. **General** tab: **Regular price ($)** = `10.00`. Leave **Sale price** empty. Note: `SubscriptionProducts\Services\Hooks::getPostedSubscriptionProductValidationErrors()` blocks the save with "Subscription products must have a valid regular price greater than zero" if this is 0 or empty.
8. Open the **Subscription [ArraySubs]** tab and set: **Billing Period** = `Day`; **Billing Interval** = `1`; **Subscription Length** = `0` (never expires); **Trial Length** = `0`; **Trial Period** = `Day`; **Sign-up Fee ($)** = empty; **Different Renewal Price** = UNTICKED.
9. Confirm the **Flexible Renewal Sync to Next Billing Cycle** checkbox is visible but leave it UNTICKED — a 1-day nominal cycle is below `SegmentPlan::MIN_CYCLE_DAYS = 3`, so even if ticked `SegmentPlan::getConfig()` would return null. Screenshot this state.
10. **Inventory** tab: leave **Manage stock?** unticked, **Stock status** = In stock.
11. Set the URL slug to `slt-daily-core` in the sidebar Permalink field. Publish.
12. Reload the edit screen and confirm every subscription field survived the save.
13. Verify meta: `wp post meta list <ID> --keys=_is_subscription,_subscription_period,_subscription_interval,_subscription_length,_trial_length,_signup_fee,_enable_renewal_price,_regular_price --allow-root`.
13a. Environment isolation, **before storefront access**: preserve the exact pre-window `members_access.enabled` and `ecommerce_rules` JSON, then append this product ID only to the existing full-store rule's `exclusion_product_ids` through **Member Access → Shop Access**. Verify the saved ID from the raw option.
14. Open the storefront page `https://mirror-help.arrayhash.com/product/slt-daily-core/?slt-cache-bust=<timestamp>` as `--session guest-SLT-PROD-01` and confirm the subscription price/schedule summary renders under the price. The unique query string prevents stale edge HTML from deciding the verdict.
15. Append the product ID and verified Shop Access exclusion to `slt-catalog-registry`.

## Expected results
1. Product published, type `simple`, virtual, slug exactly `slt-daily-core`.
2. `_is_subscription=yes`, `_subscription_period=day`, `_subscription_interval=1`, `_subscription_length=0`, `_trial_length=0`, `_signup_fee` absent or `0`, `_enable_renewal_price` absent, `_regular_price=10.00`.
3. `_arraysubs_flex_sync_enabled` is absent.
4. The single-product page shows the recurring schedule text "every day" (rendered by `displaySubscriptionInfo()` at `woocommerce_single_product_summary` priority 11) and the add-to-cart button uses the subscription button text.
5. No admin error notice from `WC_Admin_Meta_Boxes` on save; the post status is `publish`, not silently held back by `preserveProductStatusForInvalidSubscriptionSave()`.

## Emails expected
| # | Email | Trigger point | Recipient | Subject contains | Verify with |
|---|---|---|---|---|---|
| 1 | NONE EXPECTED | Product publish | — | — | Complete delta after the step-1 baseline; zero task-attributable mail, while unrelated/background mail is allowed and classified |

## Evidence to capture
- Screenshots: `SLT-PROD-01-01-general-tab.png`, `SLT-PROD-01-02-subscription-tab.png`, `SLT-PROD-01-03-frontend.png`.
- Product ID; `wp post meta list` output; any admin notice text; console errors on the product page.

## Pass criteria
- [ ] Published as simple + virtual + subscription with slug slt-daily-core
- [ ] All eight metas exactly as listed
- [ ] Flex sync meta absent
- [ ] Front end renders the daily recurring summary
- [ ] Zero mail, zero admin errors

## Isolation / teardown
- State handoff: this is THE control product. Use it for the guest->new checkout path, the block-vs-classic comparison, the Stripe SCA card path, the cancellation/reactivation flow, and as the non-flex baseline in gateway comparisons. Buy it with `slt-core` (or a guest email) only.
- Restores: nothing. Deleted by SLT-SETUP-99B.

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

[[2026-08-02]] Sun 14:02
### Execution result — 2026-08-02

**Verdict: EXECUTED / PASS AFTER ENVIRONMENT ISOLATION REMEDIATION**

- Published product ID 11927 through wp-admin as simple, virtual, in-stock, and subscription with slug slt-daily-core.
- Reloaded editor values and WP-CLI metadata match the task contract: day/1, unlimited, no trial, signup fee 0, price 10.00, renewal-price and flex-sync metas absent.
- The origin-fresh storefront renders $10.00 / day and Subscribe Now with no browser errors; the complete Mailpit delta contains no message attributable to this task.
- The unmodified environment initially failed the storefront criterion because pre-existing Shop Access rule rule_1784662676378_maa3te08s targets all products and blocked both guest and authenticated slt-core. Logged `issues/qa-plan-SLT-PROD-01-members-access-all-products-rule-blocks-slt-checkouts.md`.
- To keep downstream checkout QA viable, added only ID 11927 to that rule exclusion through wp-admin. The D0 rule JSON is preserved, the deviation and exact SETUP-99 restoration obligation are recorded on registry page 11847, and no non-SLT product was changed.
- Cloudflare served stale pre-exclusion HTML at the canonical URL after the save; an origin-fresh query-string MISS showed the correct open state. Both observations are retained in the issue.
- The resulting plan correction made this a binding suite-wide rule, added exact restoration to SLT-SETUP-99A, and
  browser-verified that the four current SLT products remain purchasable while a non-SLT counterexample is
  still blocked. The Member Access partial-save path was also fixed so rule saves no longer materialize
  unrelated runtime defaults.
- Evidence: /home/server-manager/slt-evidence/SLT-PROD-01-facts.txt, SLT-PROD-01-meta.json, SLT-PROD-01-01-general-tab.png, SLT-PROD-01-02-subscription-tab.png, SLT-PROD-01-03-frontend.png, SLT-PROD-01-03-frontend-blocked-before-exclusion.png, SLT-PROD-01-04-customer-blocked.png, and SLT-PROD-01-06-registry.png.
