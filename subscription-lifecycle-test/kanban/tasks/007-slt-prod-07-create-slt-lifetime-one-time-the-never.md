---
id: 7
title: SLT-PROD-07 Create SLT Lifetime One Time, the never-renews negative control
status: done
priority: high
created: 2026-08-02T03:43:03.472152178+02:00
updated: 2026-08-02T14:12:50.765794636+02:00
started: 2026-08-02T14:12:50.765793784+02:00
completed: 2026-08-02T14:12:50.765793784+02:00
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

> **SLT-PROD-07** · group `catalog` · scheduled **D00** (2026-08-02)

> Read `README.md` (environment + isolation contract), `calendar.md` (this day's exact
> ordering — it is binding, not advisory) and `plan-audit.md` before starting.

## Objective
Provide the negative control that must NEVER produce a renewal, a renewal invoice, a renewal reminder, or a next-payment date. `_subscription_period = lifetime` forces `_subscription_interval=1` and `_subscription_length=0` on save, `arraysubs_calculate_next_payment_from_date()` returns an empty string, `arraysubs_calculate_end_date_from_length()` returns null, and both the core and pro sync paths bail on lifetime.

## Scope
- Gateway: N/A
- Checkout: N/A (creation and storefront verification only)
- Account: N/A (creation only)
- Plugins: both (pro view supplies the flex negative)

## Preconditions
- SLT-SETUP-01 complete.
- Validation note: the billing-interval range check is skipped for lifetime, but the regular-price > 0 rule still applies.

## Test data
| Item | Value |
|---|---|
| Product | SLT Lifetime One Time / slug `slt-lifetime-one-time` |
| Account | N/A |
| Coupon | N/A |
| Card | N/A |
| Amounts | Regular price $49.00; charge today $49.00; expected renewals: none, ever |

## Steps
1. Capture `mailpit-agent latest-id`.
2. `agent-browser --session admin-SLT-PROD-07 open "https://mirror-help.arrayhash.com/wp-admin/post-new.php?post_type=product"` -> `snapshot -i`.
3. **Product title**: `SLT Lifetime One Time`. **Description**: `SLT window product. Lifetime deal, must never renew. Delete on 2026-08-15.` The post-watch date is binding; this control must remain present through the D12 report on 2026-08-14.
4. Type **Simple product**; tick **Virtual**; tick **Subscription [ArraySubs]**.
5. **General** tab: **Regular price ($)** = `49.00`.
6. **Subscription [ArraySubs]** tab: **Billing Period** = `Lifetime Deal`. Leave **Billing Interval** and **Subscription Length** as displayed — the saver overwrites them to 1 and 0. **Trial Length** = `0`; **Sign-up Fee ($)** empty; **Different Renewal Price** unticked.
7. Screenshot the panel: the **Flexible Renewal Sync to Next Billing Cycle** section must be hidden for the lifetime period (`$arraysubs_flex_section_hidden = ... || 'lifetime' === $arraysubs_flex_period`). This is the third exclusivity negative required by the catalog.
8. Slug `slt-lifetime-one-time`. Publish. Reload and re-verify.
9. `wp post meta list <ID> --keys=_is_subscription,_subscription_period,_subscription_interval,_subscription_length,_trial_length,_regular_price,_arraysubs_flex_sync_enabled --allow-root`.
10. As `--session guest-SLT-PROD-07`, open the product page and confirm the summary shows a one-time/lifetime purchase, not a recurring schedule.
11. Append the ID to the registry.

## Expected results
1. Published simple + virtual + subscription, slug `slt-lifetime-one-time`.
2. `_subscription_period=lifetime`, `_subscription_interval=1` (force-written), `_subscription_length=0` (force-written), `_regular_price=49.00`, `_trial_length=0`.
3. `_arraysubs_flex_sync_enabled` absent and the flex section hidden in the UI.
4. Product page shows a lifetime/one-time summary with no "every N days" phrasing.
5. Contract for the buying task: after checkout the subscription must have an EMPTY `_next_payment_date`, no `_end_date`, no scheduled `arraysubs_generate_renewal_invoice` or `arraysubs_process_renewal` action in Scheduled Actions, and no renewal-related mail for the whole 12-day watch.

## Emails expected
| # | Email | Trigger point | Recipient | Subject contains | Verify with |
|---|---|---|---|---|---|
| 1 | NONE EXPECTED | Product publish | — | — | Complete delta after the step-1 baseline; zero task-attributable mail, while unrelated/background mail is allowed and classified |

## Evidence to capture
- Screenshots: `SLT-PROD-07-01-subscription-tab-lifetime.png`, `SLT-PROD-07-02-flex-hidden-by-lifetime.png`, `SLT-PROD-07-03-frontend.png`.
- Product ID; meta list output showing the forced interval/length.

## Pass criteria
- [ ] Published with period lifetime and price 49.00
- [ ] Interval forced to 1 and length forced to 0 on save
- [ ] Flex sync hidden by lifetime
- [ ] Front end shows no recurring schedule
- [ ] Zero mail

## Isolation / teardown
- State handoff: buy as `slt-core` on D0 and then leave it alone. Every daily renewal-watch task from D1 to D12 must re-assert that this subscription still has no next-payment date, no renewal order, and no renewal mail. Because lifetime products are never sync-eligible, this is also a valid Paddle target if a second Paddle case is needed.
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

[[2026-08-02]] Sun 14:12


## Execution 2026-08-02 — PASS (inherited environment isolation)
- Published product ID 11938, slug `slt-lifetime-one-time`, as a simple virtual subscription at $49.00.
- Reloaded admin UI: period Lifetime Deal; interval force-written to 1; length force-written to 0; trial 0; flex-sync section hidden.
- WP-CLI meta dump confirmed `_subscription_period=lifetime`, `_subscription_interval=1`, `_subscription_length=0`, `_regular_price=49.00`, and no `_arraysubs_flex_sync_enabled`.
- Fresh cache-busted guest browser page showed a one-time $49.00 price and no recurring cadence phrase.
- Mailpit latest ID remained `1vpHEKG6i8l9ZzBoW2BqrI`; product publication sent no mail.
- Registry page 11847 updated via wp-admin with the immutable product ID and lifetime negative-control contract.
- Inherited SLT-PROD-01 environment isolation: appended only product 11938 to rule `rule_1784662676378_maa3te08s`; exclusions now [11927, 11933, 11938]. Exact pre-window rule state remains assigned to SLT-SETUP-99A restoration.
- Evidence: `/home/server-manager/slt-evidence/SLT-PROD-07-*`.
