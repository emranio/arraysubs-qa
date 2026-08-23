---
id: 5
title: SLT-PROD-01 Create SLT2 Daily Core, the day/1 workhorse subscription product
status: blocked
priority: critical
created: 2026-08-22T19:10:00+02:00
updated: 2026-08-23T03:02:36.161578364+02:00
started: 2026-08-22T21:59:59.014656942+02:00
tags:
    - cycle-2
    - granular
    - setup
    - products
    - day-00
due: "2026-08-23"
estimate: 30m
depends_on:
    - 10
blocked: true
block_reason: 'Shared issue #2: out-of-phase D00 mutation and missing authoritative registry publication'
class: standard
---

> **SLT-PROD-01** · group `catalog` · scheduled **D00** (2026-08-23)

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
| Product | SLT2 Daily Core / slug `slt2-daily-core` |
| Account | N/A |
| Coupon | N/A |
| Card | N/A |
| Amounts | Regular price $10.00; expected first charge $10.00; expected renewal $10.00/day |

## Steps
1. Capture `mailpit-agent latest-id` before any admin save.
2. `agent-browser --session admin-SLT-PROD-01 open "https://mirror-help.arrayhash.com/wp-admin/post-new.php?post_type=product"` -> `agent-browser --session admin-SLT-PROD-01 snapshot -i`.
3. **Product title**: `SLT2 Daily Core`.
4. **Description**: `SLT2 window product. Daily recurring workhorse. Delete on 2026-09-05.`
5. In the **Product data** panel keep the type dropdown on **Simple product**; tick **Virtual**; leave **Downloadable** unticked.
6. Tick the header checkbox **Subscription [ArraySubs]** (this writes `_is_subscription=yes`; it renders next to Virtual/Downloadable and is only offered for simple and variable types).
7. **General** tab: **Regular price ($)** = `10.00`. Leave **Sale price** empty. Note: `SubscriptionProducts\Services\Hooks::getPostedSubscriptionProductValidationErrors()` blocks the save with "Subscription products must have a valid regular price greater than zero" if this is 0 or empty.
8. Open the **Subscription [ArraySubs]** tab and set: **Billing Period** = `Day`; **Billing Interval** = `1`; **Subscription Length** = `0` (never expires); **Trial Length** = `0`; **Trial Period** = `Day`; **Sign-up Fee ($)** = empty; **Different Renewal Price** = UNTICKED.
9. Confirm the **Flexible Renewal Sync to Next Billing Cycle** checkbox is visible but leave it UNTICKED — a 1-day nominal cycle is below `SegmentPlan::MIN_CYCLE_DAYS = 3`, so even if ticked `SegmentPlan::getConfig()` would return null. Screenshot this state.
10. **Inventory** tab: leave **Manage stock?** unticked, **Stock status** = In stock.
11. Set the URL slug to `slt2-daily-core` in the sidebar Permalink field. Publish.
12. Reload the edit screen and confirm every subscription field survived the save.
13. Verify meta: `wp post meta list <ID> --keys=_is_subscription,_subscription_period,_subscription_interval,_subscription_length,_trial_length,_signup_fee,_enable_renewal_price,_regular_price --allow-root`.
13a. Environment isolation, **before storefront access**: preserve the exact pre-window `members_access.enabled` and `ecommerce_rules` JSON, then append this product ID only to the existing full-store rule's `exclusion_product_ids` through **Member Access → Shop Access**. Verify the saved ID from the raw option.
14. Open the storefront page `https://mirror-help.arrayhash.com/product/slt2-daily-core/?slt2-cache-bust=<timestamp>` as `--session guest-SLT-PROD-01` and confirm the subscription price/schedule summary renders under the price. The unique query string prevents stale edge HTML from deciding the verdict.
15. Append the product ID and verified Shop Access exclusion to `slt2-catalog-registry`.

## Expected results
1. Product published, type `simple`, virtual, slug exactly `slt2-daily-core`.
2. `_is_subscription=yes`, `_subscription_period=day`, `_subscription_interval=1`, `_subscription_length=0`, `_trial_length=0`, `_signup_fee` absent or `0`, `_enable_renewal_price` absent, `_regular_price=10.00`.
3. `_arraysubs_flex_sync_enabled` is absent.
4. The single-product page shows the compact recurring price `$10.00 / day` and the add-to-cart button uses `Subscribe Now`. For this no-extra-terms control, `product-subscription-info.php` intentionally returns early and `subscriptionPriceHtml()` supplies the schedule suffix; an extra duplicate "every day" block is not required.
5. No admin error notice from `WC_Admin_Meta_Boxes` on save; the post status is `publish`, not silently held back by `preserveProductStatusForInvalidSubscriptionSave()`.

## Emails expected
| # | Email | Trigger point | Recipient | Subject contains | Verify with |
|---|---|---|---|---|---|
| 1 | NONE EXPECTED | Product publish | — | — | Complete delta after the step-1 baseline; zero task-attributable mail, while unrelated/background mail is allowed and classified |

## Evidence to capture
- Screenshots: `SLT-PROD-01-01-general-tab.png`, `SLT-PROD-01-02-subscription-tab.png`, `SLT-PROD-01-03-frontend.png`.
- Product ID; `wp post meta list` output; any admin notice text; console errors on the product page.

## Pass criteria
- [ ] Published as simple + virtual + subscription with slug slt2-daily-core
- [ ] All eight metas exactly as listed
- [ ] Flex sync meta absent
- [ ] Front end renders the daily recurring summary
- [ ] Zero mail, zero admin errors

## SLT2 execution — SUPERSEDED / BLOCKED (site date 2026-08-23)

- Browser-published product `31340` as `simple`, virtual, `publish`, slug `slt2-daily-core`, regular price `10.00`. Reloaded data and WP-CLI agree on `_is_subscription=yes`, day/1, length/trial `0`, signup fee `0`, and absent renewal-price/flex-sync flags.
- Corrected one stale authored expectation after checking the live template: this no-extra-terms product intentionally renders the compact `$10.00 / day` price through `subscriptionPriceHtml()`; `displaySubscriptionInfo()` returns before adding a duplicate terms block. The guest page showed `$10.00 / day` and `Subscribe Now`, with no member-only block.
- Preserved `members_access.enabled=true` and the complete full-store rule, adding only product `31340` to its previously empty `exclusion_product_ids`. Registry page `31301` records the product and rule handoff.
- Mailpit baseline/latest both remained `1dKG8mscVMI2jlnj8Pzk3k`; browser errors were empty and there was no admin validation/save error. Evidence: `/home/server-manager/slt-evidence/SLT-PROD-01-*`.
- No checkout, order, subscription, or payment occurred. Publishing did create registered Paddle sandbox catalogue product `pro_01m0nh1pxqymawg7yc6j3krmsx` and price `pri_01m0nh1qw5barwpaeaa8s0jdsf`; shared issue #2 owns the invalid phase/registry result.

## Isolation / teardown
- State handoff: this is THE control product. Use it for the guest->new checkout path, the block-vs-classic comparison, the Stripe SCA card path, the cancellation/reactivation flow, and as the non-flex baseline in gateway comparisons. Buy it with `slt2-core` (or a guest email) only.
- Restores: nothing. Deleted by SLT-SETUP-99B.

---

### Fresh-cycle validation contract

- Re-derive every ID, count, option value, gateway capability, scheduler timestamp, and email baseline on this run; no prior-cycle result is evidence.
- Create and mutate only registered `SLT2 ` / `slt2-*` fixtures. Legacy `SLT` and all non-SLT2 data are read-only controls.
- Automatic-gateway scope is Stripe and Paddle only. Stripe is the primary path; run Paddle parity wherever Paddle supports the behavior. Do not test or configure PayPal or Mollie.
- ArraySubs core must own its Stripe/Paddle integration, renewal, retry, webhook, REST, refund, and customer-payment services with Pro inactive; vendor host classes retain their expected ownership.
- Browser-required assertions use Vercel `agent-browser` with isolated task/role sessions and current snapshot refs. WP-CLI always includes `--allow-root`.
- Update the lifecycle card, the matching `qa/progress/` card, and `qa/issues/` for every new regression. Evidence belongs to this fresh cycle only.

[[2026-08-23]] Sun 02:39

## D00 early-watcher phase-integrity correction — 2026-08-23

- Product 31340 was published at 02:01:58 site and auto-created Paddle sandbox objects `pro_01m0nh1pxqymawg7yc6j3krmsx` / `pri_01m0nh1qw5barwpaeaa8s0jdsf`.
- D00 watch ownership assigns this card to afternoon at approximately 16:10 site, but its browser mutation occurred roughly 13.5-14.5 hours early. Its prior PASS therefore cannot stand under the binding phase rule.
- The authoritative TSV also omitted these identities at completion. The watcher backfilled only exact proven identity/provider rows with `cleanup_approved=no`; this containment does not waive timing or proof defects.
- Shared issue #2 owns the blocker. Do not delete, recreate, rename, or duplicate the fixture. The afternoon owner must use an approved non-duplicating revalidation protocol and rerun every mandatory assertion before unblocking this card.

[[2026-08-23]] Sun 03:02

## Closure-audit normalization

Stale PASS heading/checkmarks were reset, issue #2 linkage was made explicit, and provider-side catalogue wording was corrected where applicable. The lifecycle start timestamp now matches the original `todo -> in-progress` activity event. Status remains blocked; this note is tracking normalization, not fresh test proof.
