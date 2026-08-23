---
id: 35
title: 'UTC+6 midnight-boundary renewal: date correctness in admin, portal, email and order'
status: todo
priority: high
created: 2026-08-22T19:10:00+02:00
updated: 2026-08-22T19:10:00+02:00
tags:
    - cycle-2
    - granular
    - edge-cases
    - day-02
due: "2026-08-25"
estimate: 1.5h on D2 (late evening) + 30m follow-up on D4
depends_on:
    - 10
    - 11
    - 12
    - 5
class: standard
---

> **SLT-IMP-01** · group `implied` · scheduled **D02** (2026-08-25)

> Read `README.md` (environment + isolation contract), `calendar.md` (this day's exact
> ordering — it is binding, not advisory) and `plan-audit.md` before starting.
## Objective
Prove a renewal falling either side of site-local midnight (UTC+6; local 00:00 == 18:00:00 UTC previous day) never renders on the wrong calendar day, and that the instant reads identically in admin, portal and email. The purchase sits at 23:45 site so the day/1 due date is 15 min before local midnight while the 0-6 h spread offset likely pushes the charge past it.

## Scope
- Gateway: Stripe test
- Checkout: block
- Account: new registered (created by this task)
- Plugins: both

## Preconditions
- SLT-SETUP-01/02/03 and SLT-PROD-01 done; `SLT2 Daily Core` ($10.00 day/1) published.
- ALSO CREATES account `slt2-tz` / `slt2-tz@example.test`, Customer, pw `SltQa!2026#Pass`, billing per SLT-SETUP-03 step 4; `slt2-` prefix, so SLT-SETUP-99B removes it.
- Begin browser/user/cart preparation during the D2 night phase, then make the checkout click at 23:45 site inside the 23:40-23:55 window on 2026-08-25 (17:40-17:55 UTC); after 12:00 site, so C02 holds.
- Sessions `customer-slt2-tz-SLT-IMP-01` and `admin-SLT-IMP-01` are exclusive to this task. Create/edit the user and inspect ArraySubs/WooCommerce screens only in the admin session; assert both browser and persistent carts are empty before checkout (C09/C12).

## Test data
| Item | Value |
|---|---|
| Product / account | SLT2 Daily Core $10.00 day/1 / slt2-tz |
| Card | 4242 4242 4242 4242, exp 12/34, CVC 123 |
| Checkout | 2026-08-25 23:45 site = 17:45 UTC |
| Due D | +24 h = 2026-08-26 ~23:45 site = ~17:45 UTC |
| Offset k | `crc32('arraysubs-spread-'.$id) % 21600` |

## Steps
1. `USER_PRE=$(mailpit-agent latest-id)`. In `agent-browser --session admin-SLT-IMP-01`, create the user at `/wp-admin/user-new.php` (untick **Send User Notification**) and set billing at `user-edit.php?user_id=<ID>`. Classify the setup-only delta before the timed checkout phase: exactly one admin-addressed `New User Registration` is allowed, and no customer-addressed account/password mail may exist. Record the exact user ID and setup-mail ID. Do **not** set checkout baseline `M0` yet.
2. `agent-browser --session customer-slt2-tz-SLT-IMP-01 open ".../my-account/"`; log in as slt2-tz.
3. Open `/cart/`; confirm the browser cart is empty, prove `_woocommerce_persistent_cart_1` is empty for the exact user ID, and capture the empty cart. Open the `SLT2 Daily Core` page and **Add to cart**. If the frozen one-click setting redirects straight to block checkout, record that fact and explicitly reopen `/cart/` before returning to `/checkout/`.
4. During the 23:40-23:44 site preparation window, set `M0=$(mailpit-agent latest-id)` and `SUB_COUNT_PRE=<exact current SLT2 subscription count>` immediately before opening `/checkout/` (page 8, block). Verify the total and select Stripe. Capture `SLT-IMP-01-00-checkout-ready.png` **before entering any card data**. Then fill the hosted Stripe fields and prepare the current Place Order control, but do not click yet. Never capture or retain a screenshot containing a full card number.
5. At 23:45 site exactly, refresh the interactive snapshot and click the current **Place Order** ref. Record the click UTC and numeric receipt order as `ORDER_TZ`; capture only the safe receipt as `SLT-IMP-01-00b-order-received.png`. Reopen `/cart/`, prove it and the exact user's persistent-cart meta are empty, and capture `SLT-IMP-01-00a-cart-empty-after.png`.
6. `mailpit-agent wait-new "$M0" 180 "is active"`; inspect the complete owner-filtered delta and require four exact checkout messages: WC Completed order to `slt2-tz@example.test`, WC New order to the admin address, ArraySubs customer signup, and ArraySubs admin signup. Save/show all four exact IDs and require no `renewal_invoice`.
7. Resolve the subscription only from the numeric receipt order: `wp post meta get "$ORDER_TZ" _subscription_ids --format=json --allow-root`, guard with `jq -e 'type == "array" and length == 1 and (.[0] | type == "number")'`, assign its sole value to canonical alias **`S_TZ`**, and abort unless both IDs are numeric. Cross-check the subscription's reverse `_order_id`, `_customer_id`, product/line identity, and `SUB_COUNT_POST == SUB_COUNT_PRE + 1`; never use recency or the WooCommerce order meta accessor. Run `wp post meta list "$S_TZ" --keys=_next_payment_date,_last_payment_date,_completed_payments,_renewal_action_id --allow-root`.
8. `php -r '$id=(int)$argv[1];$h=(int)sprintf("%u",crc32("arraysubs-spread-".$id));printf("%d\n",$h%21600);' "$S_TZ"`. Record predicted invoice (`D+k-6h`) and charge (`D+k`) instants in UTC **and** site time before they fire.
9. Screenshot the same date in three places: (a) in `admin-SLT-IMP-01`, open the real `admin.php?page=arraysubs-mainadmin#/subscriptions` route, search the exact `S_TZ`, and capture its row + single-subscription panel; (b) in `customer-slt2-tz-SLT-IMP-01`, open `/my-account/subscriptions/` and `/my-account/view-subscription/<S_TZ>/`; (c) back in the admin session, search exact `ORDER_TZ` on `admin.php?page=wc-orders` and capture its Date column.
10. Save the exact signup message with `mailpit-agent html <id>` and record every date string. In `admin-SLT-IMP-01`, open the local Mailpit UI at the exact message and capture `SLT-IMP-01-05-email.png`. Publish `ORDER_TZ`, `S_TZ`, both exact renewal action IDs/times, `k`, and the exact `charge−5m` deadline to the registry and D02 watch report. At least five minutes before that charge gate store `REN_PRE=$(mailpit-agent latest-id)` in both places. Close only `customer-slt2-tz-SLT-IMP-01` and `admin-SLT-IMP-01` after the D2 leg, and keep this card `in-progress`; this is a natural unattended event, so if the gate falls in the overnight gap D4 verifies persisted evidence.
11. FOLLOW-UP 2026-08-27 (D4) am after the exact gate: resolve `S_TZ` from the registry back to the same numeric shell variable, reopen the same two task sessions, then run `mailpit-agent wait-new "$REN_PRE" 900 "Payment received for subscription #$S_TZ"`, reconcile every message newer than `REN_PRE`, repeat step 9, open the exact renewal order, and record its Date column and `_renewal_scheduled_date`. Close both task sessions and move the card through review only after the D4 evidence is complete.

## Expected results
1. `_next_payment_date` == checkout + 24 h to the minute, a UTC MySQL string ~`2026-08-26 17:45:00`.
2. Admin, portal and the "is active" mail all render it as **2026-08-26 23:45** site — never `17:45`, never `2026-08-27`.
3. The charge leg is queued at `D+k`; when `k > 900 s` the renewal order is created on site-local **2026-08-27** while the due date shown everywhere still reads 2026-08-26 23:45. A UI showing the DUE date as 2026-08-27 is a failure.
4. The renewal order's HPOS `date_created` renders in site time and matches the "Payment received" mail timestamp within 2 min.
5. `_renewal_scheduled_date` == `2026-08-26 17:45:00` UTC (due date, NOT charge time); cycle 2 due == `2026-08-27 17:45:00` UTC — `k` must not accumulate.
6. No date on any surface is off by exactly 6 h or exactly 1 day.

## Emails expected
| # | Email | Trigger point | Recipient | Subject contains | Verify with |
|---|---|---|---|---|---|
| 1 | new_subscription | checkout | slt2-tz@example.test | `is active` | `mailpit-agent wait-new "$M0" 180 "is active"` |
| 2 | admin_new_subscription | checkout | site admin_email | `New subscription #` | Complete owner-filtered checkout delta after `M0`; save/show the exact matching id |
| 2a | WC New order | checkout | site admin_email | `New order #<ORDER_TZ>` | Same complete owner-filtered `M0` delta; save/show exact id |
| 2b | WC Completed order | checkout | slt2-tz@example.test | `is on its way` | Same complete owner-filtered `M0` delta; save/show exact id |
| 3 | payment_successful | `D+k` on 08-26/06 | slt2-tz@example.test | `Payment received for subscription #<S_TZ>` | D4 `mailpit-agent wait-new "$REN_PRE" 900 ...`, then inspect every newer message |

renewal_invoice NOT expected — suppressed for automatic-payment subs with auto-renew on (SLT-REF-10 L23). Negative check: no subject containing `Invoice for subscription #<SUB_ID>`.

## Evidence to capture
- `SLT-IMP-01-00-checkout-ready.png` captured before card entry, `-00a-cart-empty-after.png`, `-00b-order-received.png`, `-01-admin-sub.png`, `-02-portal-list.png`, `-03-portal-single.png`, `-04-order-list.png`, `-05-email.png`, D4 repeats `-06`..`-09`. No image may contain a full card number.
- Subscription id, parent + renewal order ids, computed k, `USER_PRE`, classified setup-mail ID, checkout-only `M0`, renewal baseline `REN_PRE`, all resulting Mailpit ids, both meta dumps, final cart/persistent-cart proof, console errors.

## Pass criteria
- [ ] `_next_payment_date` is checkout + 24 h in UTC
- [ ] admin, portal, email all show 2026-08-26 23:45 site
- [ ] renewal fires inside `[D+k, D+k+5min]`
- [ ] renewal order date matches the email timestamp in site time
- [ ] `_renewal_scheduled_date` is the due date; cycle 2 due is exactly +24 h
- [ ] no 6 h or 1 day discrepancy anywhere; no console errors
- [ ] Setup mail isolated before the just-in-time `M0`; no customer account/password mail; all four exact checkout messages recorded; cart and persistent-cart meta empty after checkout
- [ ] Exact receipt order and its sole HPOS post-meta-linked subscription cross-check in both directions; future action IDs and `charge−5m` baseline handed off before the D2 sessions close

## Isolation / teardown
- Hands SLT-IMP-05 canonical `S_TZ`, the midnight-adjacent subscription; register its exact ID so the D2-D9 watch expects it.
- Creates user `slt2-tz`; nothing global changes. Empty both cart representations and close only `customer-slt2-tz-SLT-IMP-01` and `admin-SLT-IMP-01` after each dated leg.

---

### Fresh-cycle validation contract

- Re-derive every ID, count, option value, gateway capability, scheduler timestamp, and email baseline on this run; no prior-cycle result is evidence.
- Create and mutate only registered `SLT2 ` / `slt2-*` fixtures. Legacy `SLT` and all non-SLT2 data are read-only controls.
- Automatic-gateway scope is Stripe and Paddle only. Stripe is the primary path; run Paddle parity wherever Paddle supports the behavior. Do not test or configure PayPal or Mollie.
- ArraySubs core must own its Stripe/Paddle integration, renewal, retry, webhook, REST, refund, and customer-payment services with Pro inactive; vendor host classes retain their expected ownership.
- Browser-required assertions use Vercel `agent-browser` with isolated task/role sessions and current snapshot refs. WP-CLI always includes `--allow-root`.
- Update the lifecycle card, the matching `qa/progress/` card, and `qa/issues/` for every new regression. Evidence belongs to this fresh cycle only.
