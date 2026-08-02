---
id: 4
title: 'SLT Fixed Three Cycles to its natural end: two renewals, expiry at the final charge, expired mail, no expiring-soon'
status: todo
priority: critical
created: 2026-08-02T03:43:03.251927431+02:00
updated: 2026-08-02T03:43:13.137045529+02:00
tags:
    - renewal
    - day-00
    - has-conflicts
due: "2026-08-02"
estimate: 1h30m
depends_on:
    - 10
    - 11
    - 12
    - 6
class: standard
---

> **SLT-LIFE-04** · group `renewal` · scheduled **D00** (2026-08-02)

> Read `README.md` (environment + isolation contract), `calendar.md` (this day's exact
> ordering — it is binding, not advisory) and `plan-audit.md` before starting.

### ⚠ Conflict resolutions that apply to this task

**`high` · session/cart collision (persistent cart)** — with `SLT-CHK-01`, `SLT-CHK-14`, `SLT-CHK-11`, `SLT-CHK-13`, `SLT-MYA-02`, `SLT-ADM-02`

- *Problem:* Audit C09's fix - one named agent-browser session per task - isolates GUEST carts only. WooCommerce persists a logged-in customer's cart to user meta (_woocommerce_persistent_cart_<blog_id>) and restores it into any session that authenticates as that user. Several tasks therefore share a cart despite having distinct session names: on D0 slt-core is used concurrently by SLT-CHK-01 (cust-SLT-CHK-01), SLT-CHK-14 (core-CHK14) and SLT-LIFE-04 (life04); on D2 slt-trial by SLT-CHK-15 (trial-CHK15) and SLT-EML-09 (cust-SLT-EML-09); on D4/D5 slt-core by SLT-CHK-13 (core-CHK13), SLT-CHK-11 (core-CHK11), SLT-MYA-02 and SLT-ADM-02. A leftover subscription line leaking across sessions makes allow_multiple_in_cart=false reject the next add-to-cart for the wrong reason, or - worse - a two-subscription cart reaches checkout and the wrong subscription is created.
- *Required fix:* Add a standing rule to the isolation contract: never run two tasks concurrently under the same slt-* login, and serialise same-account tasks within a day (the calendar's intra-day ordering is binding, not advisory). Every task that logs in must, as its first browser action after login, assert the cart is EMPTY and treat a non-empty cart as a STOP condition with an issue filed - not as something to silently empty. Add a WP-CLI pre-flight to same-account days: `wp user meta get <uid> _woocommerce_persistent_cart_1 --allow-root` must be empty before the task's checkout, and empty again at teardown.

**`medium` · contradictory-expected-result** — with `SLT-EML-08`, `SLT-EML-14`, `SLT-PROD-06`

- *Problem:* SLT-LIFE-04 derives from code (OrderIntegration.php:1489-1502) that SLT Fixed Three Cycles stamps _end_date at the moment of the FINAL renewal charge and flips to arraysubs-expired inside that payment - so with a D0 (2026-08-02) purchase the expiry is 2026-08-06, not the catalog's 'expires 6 days after checkout' (which LIFE-04 itself proves is unbacked - arraysubs_calculate_end_date_from_length() has zero callers). SLT-EML-08 states 'S_FIX expired 2026-08-08' and hunts for the 'has expired' message dated 08-08; SLT-EML-14 states 'Fixed Three Cycles renews 08-04 and 08-06 and expires 08-08 (_end_date)'; SLT-PROD-06's title still says 'expires on 2026-08-07' (the pre-shift anchor). Three different dates for one event; EML-08 and EML-14 will both report a missing email.
- *Required fix:* Adopt LIFE-04's code-derived model as authoritative and restate the dates everywhere for D0 = 2026-08-02: renewal #1 2026-08-04, renewal #2 2026-08-06, _end_date = the 08-06 charge moment, status arraysubs-expired 08-06, subscription_expired mail 08-06 PM (readable on watch D5, 2026-08-07). Update SLT-EML-08 step 1 to search 08-06, SLT-EML-14's dated contract, SLT-PROD-06's title and objective, and the watch schedule. LIFE-04's 'file an issue if _end_date is not the final charge moment' stays as the open question.

**`medium` · action-scheduler policy / broad-fire risk** — with `SLT-EML-01`, `SLT-EML-10`, `SLT-LIFE-01`, `SLT-ADM-05`, `SLT-SETUP-99`

- *Problem:* No task in the index issues a bare `wp action-scheduler run --hooks=<hook> --force`, so the largest hazard the audit named is currently absent - but the 'D8 is the only authorized Action Scheduler day' rule is broken by tasks that legitimately need to run one action: SLT-LIFE-04 step 9 hand-schedules HOOK_SEND_EXPIRING_SOON and runs it by id on D3 (2026-08-05) - which is also SLT-SYN-04's exclusive bracket day; SLT-EML-01 step 8 queues a duplicate reminder action on D3 and lets wp-cron claim it; SLT-ADM-05/ADM-03 depend on cron claiming their legs on D3/D4. Residual broad-fire risks that DO exist: (a) SLT-LIFE-01 back-dates S5's legs and relies on the per-minute runner, whose batch will claim any other action already due in that same tick; (b) SLT-EML-10 schedules HOOK_SEND_EXPIRING_SOON at time()-60; (c) SLT-SETUP-99's step 7 cancels pending actions found by searching the Scheduled Actions screen, which can match non-SLT rows; (d) SLT-ADM-01's bulk 'Delete Permanently' path issues DELETE wp/v2/arraysubs_data/<id>?force=true per selected id with no onDeleteCheck guard - one accidental confirm force-deletes irrecoverably.
- *Required fix:* Refine the rule into three tiers and publish it in the README isolation contract. (1) BANNED on every day, no exceptions: any `wp action-scheduler run` without a specific action id, and any `--hooks=` drain. (2) PERMITTED on any day: running ONE action by id from Tools -> Scheduled Actions, and queueing a single-subscription action and letting the per-minute cron claim it - provided the task first screenshots the Pending queue for the next 60 minutes and aborts if any non-SLT action is due. (3) D8 ONLY: editing _next_payment_date / _end_date / _renewal_scheduled_date to move an event in time, always paired with the 13 non-SLT _next_payment_date before/after proof. Under this rule LIFE-04 step 9, EML-01 step 8, EML-10 and ADM-05/03 are legal where they are; LIFE-01 and SETUP-99 stay on D8/D10 with the pre-flight. For SETUP-99, replace 'search and cancel' with 'cancel by action id, taken from the per-subscription action-id metas recorded in the registry'. For SLT-ADM-01, keep the bulk dialog cancelled and file the missing-guard finding as a bug, as authored.

---
## Objective
Walk SLT Fixed Three Cycles (day/2, length 3, $7.00) from checkout to termination. `calculateAndSetNextPaymentDate()` (OrderIntegration.php:1489-1502) stamps `_end_date = current_time()`, blanks `_next_payment_date` and flips the post to `arraysubs-expired` *inside* the final renewal payment, so cycle 3 is charged and service ends the same instant. `arraysubs_calculate_end_date_from_length()` has zero callers, so the catalog's "expires 6 days after checkout" contract is unproven. Also proves SLT-REF-05 §3: nothing schedules `arraysubs_send_expiring_soon`.

## Scope
- Gateway: Stripe test
- Checkout: block
- Account: existing (`slt-core`)
- Plugins: free-only

## Preconditions
- SLT-SETUP-01..04 and SLT-PROD-06 done; `_subscription_length=3`, `_subscription_interval=2`, `_subscription_period=day`, `_regular_price=7.00`.
- Buy after 12:00 site time (audit C02) and only on D0, or expiry falls outside the window.
- If a SLT-CHK-* task already bought this for `slt-core`, adopt that subscription ID and skip steps 2-3 (`slt-core` never buys the same product twice, C08).

## Test data
| Item | Value |
|---|---|
| Product | SLT Fixed Three Cycles (`slt-fixed-three-cycles`) |
| Account | `slt-core` / `SltQa!2026#Pass` |
| Card | 4242 4242 4242 4242 |
| Amounts | $7.00 today + $7.00 x2 renewals = $21.00 |
| Dates | buy 2026-08-02 at T site; due1 = 08-04 T; due2 = 08-06 T |

## Steps
1. `PREV=$(mailpit-agent latest-id)`.
2. `agent-browser --session life04 open "https://mirror-help.arrayhash.com/?p=<PROD06_ID>"` -> `snapshot -i` -> add to cart.
3. Open `https://mirror-help.arrayhash.com/checkout/` (page 8, block), log in as `slt-core`, pay with 4242, Place order. Record order O0 and subscription S4.
4. `wp post meta list S4 --keys=_next_payment_date,_end_date,_completed_payments,_subscription_length,_recurring_amount --allow-root`.
5. `php -r '$h=(int)sprintf("%u",crc32("arraysubs-spread-S4"));printf("%d\n",$h%21600);'` -> offset k.
6. Screenshot `wp-admin/tools.php?page=action-scheduler&s=S4&status=pending`: one invoice leg at due1+k-6h, one charge leg at due1+k, no reminder row, no `arraysubs_send_expiring_soon` row.
7. `mailpit-agent wait-new "$PREV" 120 "is active"`, then `mailpit-agent list 10`.
8. **D2 (08-04, after due1+k):** repeat 4 and 6; read renewal order O1 (total, `_renewal_scheduled_date`, `_renewal_cycle_number`).
9. **D3 (08-05), while still active:** hand-schedule `HOOK_SEND_EXPIRING_SOON` for S4 per SLT-REF-05 §3, then run that ONE action by ID from the Scheduled Actions screen (never a bare drain, C07).
10. **D4 (08-06, after due2+k):** repeat 4 and 6; read order O2 and the post status.

## Expected results
1. At creation `_end_date` absent, `_completed_payments=1`, `_next_payment_date` = 08-04 at the checkout clock time.
2. Renewal 1 charges $7.00 inside [due1+k, due1+k+5min]; `_completed_payments=2`; next date = 08-06 T.
3. Renewal 2 charges $7.00 on 08-06; `_completed_payments=3`; since 3 >= length, `_end_date` = the charge moment (08-06, NOT 08-08), `_next_payment_date` empty, status `arraysubs-expired`.
4. Zero pending renewal legs for S4 afterwards and no third renewal order.
5. Total charged $21.00, no tax line; `…is ending soon` arrives only from the step-9 probe.
6. File an issue if `_end_date` is not the final charge moment, and a second for "cycle 3 charged, access ends instantly, no notice".

## Emails expected
| # | Email | Trigger point | Recipient | Subject contains | Verify with |
|---|---|---|---|---|---|
| 1 | new_subscription + admin_new_subscription | checkout | customer + admin | `is active` / `New subscription #` | `wait-new $PREV 120` |
| 2 | WC new order + processing order | O0, O1, O2 | admin + customer | `#<order id>` | `list 20` |
| 3 | payment_successful x2 | renewals 1, 2 | slt-core@example.test | `Payment received for subscription #S4` | `wait-new` D2, D4 |
| 4 | subscription_expired | status -> expired | slt-core@example.test | `has expired` | `wait-new` D4 |
| 5 | NONE EXPECTED: renewal_reminder (due-3d+k past), expiring_soon (unscheduled), renewal_invoice (Stripe suppression, EmailManager.php:504-510) | — | — | `renews soon`, `is ending soon`, `Invoice for subscription` | absent in `list 30` |

## Evidence to capture
- Screenshots `SLT-LIFE-04-01-checkout.png`, `-02-pending-d0.png`, `-03-renewal1.png`, `-04-expired.png`, `-05-pending-empty.png`.
- S4, O0/O1/O2, k, the three meta dumps, Mailpit IDs, checkout console errors.

## Pass criteria
- [ ] `_end_date` absent at creation, stamped at the final charge moment
- [ ] Two unattended $7.00 renewals inside [due+k, due+k+5min]
- [ ] Status `arraysubs-expired` immediately after payment 3
- [ ] Zero pending legs and no third renewal order after expiry
- [ ] Exactly the 5 email rows, every negative included

## Isolation / teardown
- S4 is terminal by D4; SLT-SETUP-99A only deletes it.
- The step-9 probe adds one AS row and `_arraysubs_expiring_soon_sent_for` - record both. No settings changed.


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
