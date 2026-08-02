---
id: 87
title: Mid-cycle Basic→Pro upgrade, then prove the D6 renewal charges $15.00 on the unchanged due date
status: todo
priority: critical
created: 2026-08-02T03:43:10.441543701+02:00
updated: 2026-08-02T03:43:21.432660934+02:00
tags:
    - plan-switching
    - day-05
    - has-conflicts
due: "2026-08-07"
estimate: 2h
depends_on:
    - 11
    - 12
    - 60
class: standard
---

> **SLT-SW-06** · group `switching` · scheduled **D05** (2026-08-07)

> Read `README.md` (environment + isolation contract), `calendar.md` (this day's exact
> ordering — it is binding, not advisory) and `plan-audit.md` before starting.

### ⚠ Conflict resolutions that apply to this task

**`high` · same-account-collision / duplicate account creation** — with `SLT-SW-04`, `SLT-SW-08`

- *Problem:* SLT-SW-06 (d5) states 'this task also creates slt-switch2 / slt-switch2@example.test' and buys SLT Plan Basic on it, then upgrades to Pro. SLT-SW-04 (d7) states 'this task CREATES slt-switch2 / slt-switch2@example.test' and buys SLT Plan Basic on it again. SLT-SW-08 (d7) then operates on 'slt-switch2, on SLT Plan Pro from SLT-SW-06'. SW-04 either aborts on a duplicate user, or buys SLT Plan Basic a second time on an account that already holds a Pro subscription from the same ladder - and with auto_migrate_on_checkout the checkout-migration ladder in CheckoutMigrationTrait becomes reachable, silently converting SW-08's Pro subscription instead of creating SW-04's Basic one.
- *Required fix:* SLT-SW-04 creates and uses a distinct account, slt-switch4 / slt-switch4@example.test (Customer, SltQa!2026#Pass, SETUP-03 step 4 billing), registered for 99B deletion. SLT-SW-06 remains the sole creator of slt-switch2 and the sole owner of its subscription; SLT-SW-08 continues to inherit it. Add to SW-04's preconditions: 'slt-switch2 belongs to SLT-SW-06/SLT-SW-08 and must not be reused'.

**`high` · session collision (shared admin session)** — with `SLT-EML-01`, `SLT-EML-02`, `SLT-EML-03`, `SLT-EML-05`, `SLT-EML-10`, `SLT-EML-12`

- *Problem:* More than twenty tasks open `--session admin` by that bare name, in direct violation of audit C09's fix (which only got applied to guest/customer sessions). agent-browser sessions are keyed by name, so these tasks share one browser profile: one task's `agent-browser close --session admin` logs out another mid-run; one task navigating the SPA away from a settings screen invalidates another's snapshot; and any admin session that ever adds to cart (SLT-ADM-01's bulk-action screen, SLT-EML-12's status juggling) puts a cart on the admin user shared by all of them. The failures this produces look like flaky UI, not contamination.
- *Required fix:* Rename every admin session to `admin-<TASK-KEY>` (SLT-ADM-01/02/03/04/05 already do this correctly - copy the pattern). Each task closes only its own session by name; `agent-browser close --all` is reserved to the last task of the day, named explicitly in the calendar. Add this to the isolation contract as rule 9 and add a pass criterion to every task that opens an admin session: 'the session name contains this task's key'.

---
## Objective
The group's highest-value assertion: a same-cycle upgrade taken mid-cycle leaves `_next_payment_date` untouched and makes the next unattended renewal charge the NEW price. Basic ($5.00/day) → Pro ($15.00/day) on D5 (2026-08-07); the D6 (2026-08-08) renewal must be $15.00, at the original due moment + the crc32 spread offset. Per L7 the proration order is never auto-charged — unpaid means no switch and D6 still bills $5.00.

## Scope
- Gateway: Stripe test
- Checkout: block
- Account: new registered — **this task also creates `slt-switch2` / `slt-switch2@example.test`**
- Plugins: free-only

## Preconditions
- SLT-SETUP-02, SLT-SETUP-03, SLT-PROD-11 done (Basic's `_arraysubs_upgrade_products` holds Pro).
- `slt-switch` is reserved by SLT-SW-01..05, hence the dedicated account. Sessions `admin`, `cust-SLT-SW-06`; cart empty first and last.

## Test data
| Item | Value |
|---|---|
| Products | SLT Plan Basic $5.00 day/1 → SLT Plan Pro $15.00 day/1 |
| Card | 4242 4242 4242 4242 |
| Account | slt-switch2 / `SltQa!2026#Pass` / Customer |
| Amounts | signup $5.00; proration net = round(15×dr,2) − round(5×dr,2); renewal $15.00 |
| dr | max(0, 1 − round((now − `_last_payment_date`)/86400, 2)) |


## Steps
1. `mailpit-agent latest-id` → `MP0`.
2. Admin `/wp-admin/user-new.php`: create `slt-switch2`, Role Customer, pw `SltQa!2026#Pass`, **Send User Notification unticked**; billing address per SETUP-03 §4.
3. `--session cust-SLT-SW-06`: log in at `/my-account`; open `/cart/`, confirm empty.
4. Open `/checkout/?add-to-cart=<Plan Basic ID>` → `snapshot -i` → card 4242 → **Place order**. Record `ORDER_A`, `SUB_ID`, time.
5. `T0` = `wp post meta list <SUB_ID> --keys=_product_id,_recurring_amount,_billing_interval,_last_payment_date,_next_payment_date --allow-root`; `OFFSET` = `php -r '$h=(int)sprintf("%u",crc32("arraysubs-spread-<SUB_ID>"));printf("%d\n",$h%21600);'`.
6. 30–90 min later open `/my-account/view-subscription/<SUB_ID>/` → **Change Plan** → **SLT Plan Pro** → screenshot **Plan Change Summary** → **Confirm Plan Change**.
7. Response returns `requires_payment: true` + `checkout_url`; open it, record `ORDER_B`, pay with 4242.
8. Re-open the subscription page, screenshot, re-dump the step-5 metas, diff vs `T0`; in **Tools → Scheduled Actions** search `<SUB_ID>` and screenshot the pending renewal rows.
9. **Follow-up D6 at `due+OFFSET+15min`:** snapshot `latest-id`, open the renewal order (Orders filtered to slt-switch2), screenshot total and lines, `list 20`. The D7 watch re-confirms.

## Expected results
1. `SUB_ID` `arraysubs-active`, `_recurring_amount=5.00`, day/1, `_next_payment_date` = placed UTC + 24h; switch classified **upgrade** (5.00 → 15.00 daily rate).
2. `ORDER_B` manual/pending, `_arraysubs_order_type=plan_switch`, `_arraysubs_switch_type=upgrade`, one fee line `Plan Upgrade to SLT Plan Pro - Proration` = round(15×dr,2)−round(5×dr,2); no switch-fee, no tax line.
3. Plan stays Basic until `ORDER_B` is paid; then `_product_id`=Pro, `_recurring_amount=15.00`, day/1.
4. **`_next_payment_date` identical to `T0`**; legs at `+OFFSET`, `+OFFSET−6h`.
5. D6 renewal order totals exactly **$15.00**, `_is_renewal_order=yes`, in `[due+OFFSET, +10min]`; then `_next_payment_date` advances exactly 24h.

## Emails expected
| # | Email | Trigger point | Recipient | Subject contains | Verify with |
|---|---|---|---|---|---|
| 1 | new_subscription, admin_new_subscription, Woo order mail | step 4 paid | customer, admin | `is active` / `New subscription #` | `wait-new MP0 180 "is active"`; `list 20` |
| 2 | Woo order mail for `ORDER_B` only — **no ArraySubs switch mail** | step 7 | customer, admin | `order` | `list 20`; L6 has no listener, so no plan/switch subject |
| 3 | payment_successful | D6 renewal | slt-switch2 | `Payment received for subscription #<SUB_ID>` | `wait-new <step-9 id> 600` |
| 4 | renewal_invoice | 6h pre-due | — | — | **NONE EXPECTED** (L23) |

## Evidence to capture
- `SLT-SW-06-01-switch-modal.png`, `-02-proration-order.png`, `-03-sub-after.png`, `-04-actions.png`, `-05-d6-renewal.png`; `SUB_ID`, order ids, `OFFSET`, `T0` diff, `dr`, Mailpit ids, console errors

## Pass criteria
- [ ] Starts on Basic at $5.00/day under slt-switch2, classified `upgrade`
- [ ] Proration order manual with the correct fee line and no switch fee
- [ ] Plan unchanged until `ORDER_B` paid, then `_recurring_amount=15.00` on Pro
- [ ] `_next_payment_date` unchanged from `T0`; legs at due+OFFSET and −6h
- [ ] D6 renewal total exactly $15.00 inside the offset window
- [ ] Emails 1 and 3 present; no switch mail; no renewal_invoice mail

## Isolation / teardown
- Hands SLT-SW-08 an active day/1 Pro subscription on slt-switch2; SW-08 must not start before this task's D6 evidence exists. `slt-switch2` is deleted by SLT-SETUP-99.
- No global change. If the D6 renewal has not fired by `due+OFFSET+30min`, capture pending actions, `_next_payment_date` and the notes, file an issue — **do not drain Action Scheduler**.

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
