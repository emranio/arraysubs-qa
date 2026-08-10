---
id: 23
title: SLT-PROD-16 Create SLT Retry Daily and SLT Paddle Daily, the two gateway-path products
status: done
priority: critical
created: 2026-08-02T03:43:04.919010019+02:00
updated: 2026-08-03T04:50:22.293314391+02:00
started: 2026-08-03T04:50:22.293312888+02:00
completed: 2026-08-03T04:50:22.293312888+02:00
tags:
    - setup
    - products
    - day-01
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
| Product A | SLT Retry Daily / slug `slt-retry-daily`, $13.00, day/1 |
| Product B | SLT Paddle Daily / slug `slt-paddle-daily`, $11.00, day/1 |
| Account | A -> `slt-fail`; B -> `slt-paddle` |
| Coupon | N/A |
| Card | A: `4000 0000 0000 0341` (attaches fine, declines every off-session renewal); B: Paddle sandbox `4242 4242 4242 4242`, any future expiry |
| Amounts | A $13.00 first charge then failing $13.00 renewals; B $11.00 first charge then $11.00 renewals |

## Steps
1. Capture `M0=$(mailpit-agent latest-id)`. At the end, inspect every message newer than `M0`; classify unrelated/background mail by its actual owner.
2. Create Product A: `agent-browser --session admin-SLT-PROD-16 open "https://mirror-help.arrayhash.com/wp-admin/post-new.php?post_type=product"`; title `SLT Retry Daily`; description `SLT window product. Stripe failing-card dunning path. Delete on 2026-08-15.`; **Simple product**; tick **Virtual**; tick **Subscription [ArraySubs]**; **General** tab **Regular price ($)** `13.00`.
3. Product A **Subscription [ArraySubs]** tab: **Billing Period** `Day`; **Billing Interval** `1`; **Subscription Length** `0`; **Trial Length** `0`; **Sign-up Fee ($)** empty; **Different Renewal Price** unticked; **Flexible Renewal Sync** UNTICKED. Slug `slt-retry-daily`. Publish.
4. Create Product B identically but title `SLT Paddle Daily`, description `SLT window product. Paddle sandbox only. Delete on 2026-08-15.`, **Regular price ($)** `11.00`, slug `slt-paddle-daily`. Publish.
4a. Before either storefront check or any downstream checkout, append both parent product IDs only to Shop Access rule `rule_1784662676378_maa3te08s` under `exclusion_product_ids` through **Member Access → Shop Access**. Preserve every other field and every prior SLT exclusion; re-read the raw option and require each new ID exactly once.
5. Reload both and confirm the subscription fields persisted.
6. `wp post meta list <ID> --keys=_is_subscription,_subscription_period,_subscription_interval,_subscription_length,_trial_length,_signup_fee,_enable_renewal_price,_arraysubs_flex_sync_enabled,_regular_price --allow-root` for both.
7. As `--session guest-SLT-PROD-16`, open both product pages and confirm each renders a plain daily recurring summary with no trial and no fee. Capture each page separately as `SLT-PROD-16-03-retry-frontend.png` and `SLT-PROD-16-04-paddle-frontend.png`; one screenshot of only one page is not evidence for both products.
8. Do NOT purchase in this task — the Paddle catalogue sync check and the gateway list check belong to SLT-SETUP-05, which depends on this task.
9. Append both IDs to the registry, tagging A as `stripe-decline-only` and B as `paddle-only`.
10. Close only `admin-SLT-PROD-16` and `guest-SLT-PROD-16`.

## Expected results
1. Both published simple + virtual + subscription, day/1, length 0, trial 0, no signup fee, no different renewal price, no flex sync meta.
2. `SLT Retry Daily` `_regular_price=13.00`; `SLT Paddle Daily` `_regular_price=11.00`.
3. Neither product carries `_arraysubs_flex_sync_enabled` (mandatory — otherwise Paddle would be hidden).
4. Both storefront pages show a plain "every day" recurring summary.
5. Dunning contract for A when `SLT-DUN-01` buys it on D2 (2026-08-04) with card `4000 0000 0000 0341`: parent order paid $13.00; the D3 renewal attempt fails; `payment_failed` mail goes to customer and admin; the subscription stays `arraysubs-active` for 1 day, moves to `arraysubs-on-hold`, and is later cancelled by the authored grace ladder — all inside the window.
6. Contract for B: bought with Paddle sandbox by `slt-paddle` only; Paddle owns the schedule via `next_billed_at`, so the Renew Early button must stay hidden even though `allow_early_renew` is on (`early_renewal: false`), and SCA/3DS is not applicable (`sca: false`).

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
- State handoff: the primary `SLT Retry Daily` ladder is bought by `slt-fail` on D2 with card `4000 0000 0000 0341`, as owned by `SLT-DUN-01`. A second ladder may reuse the product later only under its separately assigned SLT account and card, never concurrently on the same account. `SLT Paddle Daily` may ONLY be bought by `slt-paddle` with the Paddle sandbox card, and never with Stripe.
- Restores: nothing. Both deleted by SLT-SETUP-99B.

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

## Execution note — D01 early-morning (2026-08-03)

Verdict: **PASS**.

- Published `SLT Retry Daily` product `12108` (`slt-retry-daily`) at USD 13.00/day and `SLT Paddle Daily` product `12112` (`slt-paddle-daily`) at USD 11.00/day; both are simple, virtual, fixed day/1 subscriptions.
- Reloaded both product editors and proved length 0, trial 0, normalized sign-up fee 0, Different Renewal Price OFF, and Flexible Renewal Sync OFF. Raw meta contains neither `_enable_renewal_price` nor `_arraysubs_flex_sync_enabled`.
- Through Member Access -> Shop Access, appended parent IDs `12108` and `12112` exactly once to rule `rule_1784662676378_maa3te08s`. Raw exclusions changed only from `[11927,11933,11938,11943,12087,12093,12099,12102]` to `[11927,11933,11938,11943,12087,12093,12099,12102,12108,12112]`.
- Cache-busted guest pages rendered `$13.00 / day` and `$11.00 / day`, with no trial or sign-up-fee text. Cart remained empty; no order/order line, subscription, checkout, or card action occurred.
- Registry page `11847` contains one `CATALOG (SLT-PROD-16)` section tagging `12108` as `stripe-decline-only` and `12112` as `paddle-only`, with their downstream owners.
- Mailpit remained `42DI8ELEccd8qFsaMtyeag`; zero attributable messages. Admin and guest error logs were empty, and both named sessions were closed.
- Independent evidence review confirmed two separately framed storefront captures: `SLT-PROD-16-03-retry-frontend.png` shows `SLT Retry Daily` at `$13.00 / day`, and `SLT-PROD-16-04-paddle-frontend.png` shows `SLT Paddle Daily` at `$11.00 / day`; both show an empty cart. The earlier `SLT-PROD-16-03-frontends.png` is superseded and is not relied on as pass evidence.
- Evidence: `/home/server-manager/slt-evidence/SLT-PROD-16-facts.txt` plus `SLT-PROD-16-01-retry-subscription-tab.png`, `SLT-PROD-16-02-paddle-subscription-tab.png`, `SLT-PROD-16-03-retry-frontend.png`, and `SLT-PROD-16-04-paddle-frontend.png`.
