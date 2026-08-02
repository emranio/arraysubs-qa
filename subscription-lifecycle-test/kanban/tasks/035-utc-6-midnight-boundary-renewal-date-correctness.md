---
id: 35
title: 'UTC+6 midnight-boundary renewal: date correctness in admin, portal, email and order'
status: todo
priority: high
created: 2026-08-02T03:43:05.931103855+02:00
updated: 2026-08-02T03:43:16.351125108+02:00
tags:
    - edge-cases
    - day-02
due: "2026-08-04"
estimate: 1.5h on D1 (late evening) + 30m follow-up on D3
depends_on:
    - 10
    - 11
    - 12
    - 5
class: standard
---

> **SLT-IMP-01** · group `implied` · scheduled **D02** (2026-08-04)

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
- SLT-SETUP-01/02/03 and SLT-PROD-01 done; `SLT Daily Core` ($10.00 day/1) published.
- ALSO CREATES account `slt-tz` / `slt-tz@example.test`, Customer, pw `SltQa!2026#Pass`, billing per SLT-SETUP-03 step 4; `slt-` prefix, so SLT-SETUP-99B removes it.
- Checkout runs 23:40-23:55 site on 2026-08-03 (17:40-17:55 UTC); after 12:00 site, so C02 holds.
- Session `customer-slt-tz` only; assert the cart is empty first (C09).

## Test data
| Item | Value |
|---|---|
| Product / account | SLT Daily Core $10.00 day/1 / slt-tz |
| Card | 4242 4242 4242 4242, exp 12/34, CVC 123 |
| Checkout | 2026-08-03 23:45 site = 17:45 UTC |
| Due D | +24 h = 2026-08-04 ~23:45 site = ~17:45 UTC |
| Offset k | `crc32('arraysubs-spread-'.$id) % 21600` |

## Steps
1. Create the user at `/wp-admin/user-new.php` (untick **Send User Notification**); set billing at `user-edit.php?user_id=<ID>`.
2. `mailpit-agent latest-id` -> `M0`.
3. `agent-browser --session customer-slt-tz open ".../my-account/"`; log in as slt-tz.
4. Open `/cart/`; confirm empty; screenshot. Open the `SLT Daily Core` page; **Add to cart**.
5. At 23:45 site exactly open `/checkout/` (page 8, block), fill the Stripe card fields, click **Place Order**. Record the UTC of the click.
6. `mailpit-agent wait-new M0 180 "is active"`; record the id.
7. `wp post meta list <SUB_ID> --keys=_next_payment_date,_last_payment_date,_completed_payments,_renewal_action_id --allow-root`.
8. `php -r '$h=(int)sprintf("%u",crc32("arraysubs-spread-<SUB_ID>"));printf("%d\n",$h%21600);'`. Record predicted invoice (`D+k-6h`) and charge (`D+k`) instants in UTC **and** site time before they fire.
9. Screenshot the same date in three places: (a) `admin.php?page=arraysubs#/subscriptions` row + single-subscription panel; (b) `/my-account/subscriptions/` and `/my-account/view-subscription/<SUB_ID>/`; (c) `admin.php?page=wc-orders` Date column.
10. `mailpit-agent html <id>`; record every date string in the mail.
11. FOLLOW-UP 2026-08-05 (D3) am: repeat step 9, run `mailpit-agent list 50`, open the renewal order, record its Date column and `_renewal_scheduled_date`.

## Expected results
1. `_next_payment_date` == checkout + 24 h to the minute, a UTC MySQL string ~`2026-08-04 17:45:00`.
2. Admin, portal and the "is active" mail all render it as **2026-08-04 23:45** site — never `17:45`, never `2026-08-05`.
3. The charge leg is queued at `D+k`; when `k > 900 s` the renewal order is created on site-local **2026-08-05** while the due date shown everywhere still reads 2026-08-04 23:45. A UI showing the DUE date as 2026-08-05 is a failure.
4. The renewal order's HPOS `date_created` renders in site time and matches the "Payment received" mail timestamp within 2 min.
5. `_renewal_scheduled_date` == `2026-08-04 17:45:00` UTC (due date, NOT charge time); cycle 2 due == `2026-08-05 17:45:00` UTC — `k` must not accumulate.
6. No date on any surface is off by exactly 6 h or exactly 1 day.

## Emails expected
| # | Email | Trigger point | Recipient | Subject contains | Verify with |
|---|---|---|---|---|---|
| 1 | new_subscription | checkout | slt-tz@example.test | `is active` | `mailpit-agent wait-new M0 180 "is active"` |
| 2 | admin_new_subscription | checkout | site admin_email | `New subscription #` | `mailpit-agent list 50` |
| 3 | payment_successful | `D+k` on 08-04/05 | slt-tz@example.test | `Payment received for subscription #` | D3 `mailpit-agent list 50` |

renewal_invoice NOT expected — suppressed for automatic-payment subs with auto-renew on (SLT-REF-10 L23). Negative check: no subject containing `Invoice for subscription #<SUB_ID>`.

## Evidence to capture
- `SLT-IMP-01-01-admin-sub.png`, `-02-portal-list.png`, `-03-portal-single.png`, `-04-order-list.png`, `-05-email.png`, D3 repeats `-06`..`-09`.
- Subscription id, parent + renewal order ids, computed k, `M0` and all Mailpit ids, both meta dumps, console errors.

## Pass criteria
- [ ] `_next_payment_date` is checkout + 24 h in UTC
- [ ] admin, portal, email all show 2026-08-04 23:45 site
- [ ] renewal fires inside `[D+k, D+k+5min]`
- [ ] renewal order date matches the email timestamp in site time
- [ ] `_renewal_scheduled_date` is the due date; cycle 2 due is exactly +24 h
- [ ] no 6 h or 1 day discrepancy anywhere; no console errors

## Isolation / teardown
- Hands SLT-IMP-05 a midnight-adjacent subscription; register its id so the D2-D9 watch expects it.
- Creates user `slt-tz`; nothing global changes. Empty the cart, close the session.

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
