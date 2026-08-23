---
id: 23
title: SLT-PROD-16 Create SLT2 Retry Daily and SLT2 Paddle Daily, the two gateway-path products
status: todo
priority: critical
created: 2026-08-22T19:10:00+02:00
updated: 2026-08-22T19:10:00+02:00
tags:
    - cycle-2
    - granular
    - setup
    - products
    - day-01
due: "2026-08-24"
estimate: 45m
depends_on:
    - 10
    - 11
class: standard
---

> **SLT-PROD-16** · group `catalog` · scheduled **D01** (2026-08-24)

> Read `README.md` (environment + isolation contract), `calendar.md` (this day's exact
> ordering — it is binding, not advisory) and `plan-audit.md` before starting.

## Objective
Create the two products whose only distinguishing feature is the gateway they are bought with: one wired to Stripe's always-declines-off-session card so the failure / on-hold / cancel dunning ladder runs on a real daily schedule, and one reserved exclusively for Paddle sandbox. Both are plain day/1 subscriptions with no trial, no fee and no flex sync, so any behavioural difference is attributable to the gateway alone.

## Scope
- Gateway: N/A (the products reserve later Stripe and Paddle paths)
- Checkout: N/A (creation and storefront verification only)
- Account: N/A (creation only)
- Plugins: both

## Preconditions
- SLT-SETUP-01 and SLT-SETUP-02 complete. Global sync OFF is what makes Paddle selectable at all; SLT-SETUP-05 verifies that after this task.
- Neither product may enable flexible sync — a sync-eligible cart would hide Paddle again and break the Paddle product's whole purpose.
- Dunning timing that these products must produce, from the unchanged baseline: renewal due -> stays `arraysubs-active` for `grace_days_before_on_hold = 1` day -> `arraysubs-on-hold` -> `grace_days_before_cancel = 3` days -> `arraysubs-cancelled`. The renewal invoice is generated `invoice_before_due_value = 6` hours before due.

## Test data
| Item | Value |
|---|---|
| Product A | SLT2 Retry Daily / slug `slt2-retry-daily`, $13.00, day/1 |
| Product B | SLT2 Paddle Daily / slug `slt2-paddle-daily`, $11.00, day/1 |
| Account | A -> `slt2-fail`; B -> `slt2-paddle` |
| Coupon | N/A |
| Card | A: `4000 0000 0000 0341` (attaches fine, declines every off-session renewal); B: Paddle sandbox `4242 4242 4242 4242`, any future expiry |
| Amounts | A $13.00 first charge then failing $13.00 renewals; B $11.00 first charge then $11.00 renewals |

## Steps
1. Capture `M0=$(mailpit-agent latest-id)`. At the end, inspect every message newer than `M0`; classify unrelated/background mail by its actual owner.
2. Create Product A: `agent-browser --session admin-SLT-PROD-16 open "https://mirror-help.arrayhash.com/wp-admin/post-new.php?post_type=product"`; title `SLT2 Retry Daily`; description `SLT2 window product. Stripe failing-card dunning path. Delete on 2026-09-05.`; **Simple product**; tick **Virtual**; tick **Subscription [ArraySubs]**; **General** tab **Regular price ($)** `13.00`.
3. Product A **Subscription [ArraySubs]** tab: **Billing Period** `Day`; **Billing Interval** `1`; **Subscription Length** `0`; **Trial Length** `0`; **Sign-up Fee ($)** empty; **Different Renewal Price** unticked; **Flexible Renewal Sync** UNTICKED. Slug `slt2-retry-daily`. Publish.
4. Create Product B identically but title `SLT2 Paddle Daily`, description `SLT2 window product. Paddle sandbox only. Delete on 2026-09-05.`, **Regular price ($)** `11.00`, slug `slt2-paddle-daily`. Publish.
4a. Before either storefront check or any downstream checkout, append both parent product IDs only to Shop Access rule `<D0_SHOP_ACCESS_RULE_ID>` under `exclusion_product_ids` through **Member Access → Shop Access**. Preserve every other field and every prior SLT2 exclusion; re-read the raw option and require each new ID exactly once.
5. Reload both and confirm the subscription fields persisted.
6. `wp post meta list <ID> --keys=_is_subscription,_subscription_period,_subscription_interval,_subscription_length,_trial_length,_signup_fee,_enable_renewal_price,_arraysubs_flex_sync_enabled,_regular_price --allow-root` for both.
7. As `--session guest-SLT-PROD-16`, open both product pages and confirm each renders a plain daily recurring summary with no trial and no fee. Capture each page separately as `SLT-PROD-16-03-retry-frontend.png` and `SLT-PROD-16-04-paddle-frontend.png`; one screenshot of only one page is not evidence for both products.
8. Do NOT purchase in this task — the Paddle catalogue sync check and the gateway list check belong to SLT-SETUP-05, which depends on this task.
9. Append both IDs to the registry, tagging A as `stripe-decline-only` and B as `paddle-only`.
10. Close only `admin-SLT-PROD-16` and `guest-SLT-PROD-16`.

## Expected results
1. Both published simple + virtual + subscription, day/1, length 0, trial 0, no signup fee, no different renewal price, no flex sync meta.
2. `SLT2 Retry Daily` `_regular_price=13.00`; `SLT2 Paddle Daily` `_regular_price=11.00`.
3. Neither product carries `_arraysubs_flex_sync_enabled` (mandatory — otherwise Paddle would be hidden).
4. Both storefront pages show a plain "every day" recurring summary.
5. Dunning contract for A when `SLT-DUN-01` buys it on D2 (2026-08-25) with card `4000 0000 0000 0341`: parent order paid $13.00; the D3 renewal attempt fails; `payment_failed` mail goes to customer and admin; the subscription stays `arraysubs-active` for 1 day, moves to `arraysubs-on-hold`, and is later cancelled by the authored grace ladder — all inside the window.
6. Contract for B: bought with Paddle sandbox by `slt2-paddle` only; Paddle owns the schedule via `next_billed_at`, so the Renew Early button must stay hidden even though `allow_early_renew` is on (`early_renewal: false`), and SCA/3DS is not applicable (`sca: false`).

## Emails expected
| # | Email | Trigger point | Recipient | Subject contains | Verify with |
|---|---|---|---|---|---|
| 1 | NONE EXPECTED | Both publishes | — | — | Complete delta after `M0`; zero message attributable to this task, while unrelated/background mail is allowed and classified |

## Evidence to capture
- Screenshots: `SLT-PROD-16-01-retry-subscription-tab.png`, `SLT-PROD-16-02-paddle-subscription-tab.png`, `SLT-PROD-16-03-retry-frontend.png`, `SLT-PROD-16-04-paddle-frontend.png`.
- Both product IDs; both meta dumps; raw Shop Access rule showing both IDs exactly once.

## Pass criteria
- [ ] Both published as plain day/1 subscriptions at $13.00 and $11.00
- [ ] Neither has flex sync, trial, fee or different renewal price
- [ ] Both storefront summaries show a plain daily schedule
- [ ] Registry tags each product with its exclusive gateway
- [ ] Both parent product IDs are present exactly once in the preserved Shop Access exclusion list before storefront access
- [ ] Zero mail, nothing purchased

## Isolation / teardown
- State handoff: the primary `SLT2 Retry Daily` ladder is bought by `slt2-fail` on D2 with card `4000 0000 0000 0341`, as owned by `SLT-DUN-01`. A second ladder may reuse the product later only under its separately assigned SLT2 account and card, never concurrently on the same account. `SLT2 Paddle Daily` may ONLY be bought by `slt2-paddle` with the Paddle sandbox card, and never with Stripe.
- Restores: nothing. Both deleted by SLT-SETUP-99B.

---

### Fresh-cycle validation contract

- Re-derive every ID, count, option value, gateway capability, scheduler timestamp, and email baseline on this run; no prior-cycle result is evidence.
- Create and mutate only registered `SLT2 ` / `slt2-*` fixtures. Legacy `SLT` and all non-SLT2 data are read-only controls.
- Automatic-gateway scope is Stripe and Paddle only. Stripe is the primary path; run Paddle parity wherever Paddle supports the behavior. Do not test or configure PayPal or Mollie.
- ArraySubs core must own its Stripe/Paddle integration, renewal, retry, webhook, REST, refund, and customer-payment services with Pro inactive; vendor host classes retain their expected ownership.
- Browser-required assertions use Vercel `agent-browser` with isolated task/role sessions and current snapshot refs. WP-CLI always includes `--allow-root`.
- Update the lifecycle card, the matching `qa/progress/` card, and `qa/issues/` for every new regression. Evidence belongs to this fresh cycle only.
