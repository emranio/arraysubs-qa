---
id: 55
title: Toggle the Subscription On Hold customer email OFF, prove silence, restore ON, prove delivery
status: todo
priority: critical
created: 2026-08-02T03:43:07.78919049+02:00
updated: 2026-08-02T03:43:18.234890148+02:00
tags:
    - email
    - day-03
due: "2026-08-05"
estimate: 1h 30m
depends_on:
    - 10
    - 11
    - 12
    - 7
class: standard
---

> **SLT-EML-11** · group `emails` · scheduled **D03** (2026-08-05)

> Read `README.md` (environment + isolation contract), `calendar.md` (this day's exact
> ordering — it is binding, not advisory) and `plan-audit.md` before starting.
## Objective
Prove the WooCommerce per-email Enable/Disable checkbox is the effective gate for an ArraySubs customer email: turn `Subscription On Hold` OFF, fire it, prove silence; restore ON, fire it again, prove delivery. Record and restore the prior state here.

## Scope
- Gateway: Stripe test
- Checkout: block
- Account: new registered
- Plugins: free-only

## Preconditions
- SLT-SETUP-01/-02/-03 and SLT-PROD-07 complete. Check out **after 12:00 site time**.
- **THIS TASK ALSO CREATES** (deleted by SLT-SETUP-99): user `slt-email` / `slt-email@example.test`, Customer, `SltQa!2026#Pass`, First name `SLT`, Last name `Email`, billing per SLT-SETUP-03 step 4; and a subscription on `SLT Lifetime One Time` (**H1**), the harness for SLT-EML-12/13. Do not cancel H1.
- Lifetime is deliberate (SLT-PROD-07 ER5): empty `_next_payment_date`, no scheduled action, so status juggling moves no renewal. `trigger()` honours `is_enabled()` (`BaseSubscriptionEmail.php:136`); on-hold mail fires at `EmailManager.php:370-372`, no dedupe meta, repeatable.

## Test data
| Item | Value |
|---|---|
| Product | SLT Lifetime One Time ($49.00, `lifetime`) |
| Card | 4242 4242 4242 4242, future expiry, CVC 123 |
| Section | `arraysubs_subscription_on_hold` |

## Steps
1. Dump `woocommerce_arraysubs_subscription_on_hold_settings` to `/home/server-manager/slt-evidence/SLT-EML-11-prior.txt` (expect "Could not get" — row absent).
2. `--session admin` `/wp-admin/user-new.php` → `snapshot -i`; create `slt-email`, **Send User Notification** UNTICKED, Role Customer; at `user-edit.php?user_id=<ID>` fill billing (SLT / Email / Dhaka / Bangladesh / 1207), Update User.
3. `MP0=$(mailpit-agent latest-id)`; `--session cust-SLT-EML-11` log in as `slt-email` at `/my-account/`; assert `/cart/` EMPTY.
4. `/checkout/?add-to-cart=<Lifetime ID>` → `snapshot -i` → pay $49.00 with 4242. Record order ID and subscription **H1**.
5. `wait-new $MP0 120 "is active"`; `list 20`; append H1, order and user IDs to `slt-catalog-registry`.
6. `wp post meta list H1 --keys=_next_payment_date,_end_date --allow-root` both empty; Tools → Scheduled Actions search `H1` = nothing pending. Screenshot.
7. **OFF.** `/wp-admin/admin.php?page=wc-settings&tab=email&section=arraysubs_subscription_on_hold` → untick **Enable this email notification** → Save. Screenshot; record UTC open.
8. `MP1=$(mailpit-agent latest-id)`; at `page=arraysubs-mainadmin#/subscriptions` open H1, **Status** = `On hold`, save (fallback `post.php?post=H1&action=edit`).
9. `wait-new $MP1 180 "is on hold"` must **exit 124**; `list 20` shows no new id to slt-email@. Set H1 `Active`; `latest-id` unchanged.
10. **ON.** Same URL: re-tick **Enable**, keep Subject/Heading/Additional content blank, Save. Screenshot; record UTC close.
11. `MP2=$(mailpit-agent latest-id)`; set H1 `On hold`; `wait-new $MP2 180 "is on hold"` must succeed; `html latest`. Set H1 `Active`, close the session, dump the row to `-after.txt`.

## Expected results
1. `slt-email` exists, role `customer`, billing set, zero mail at creation; H1 is `arraysubs-active`, `_recurring_amount` `49.00`, `_next_payment_date` EMPTY, no pending action.
2. OFF: Active→On hold yields zero messages (exit 124); On hold→Active yields zero in both states.
3. ON: exactly ONE message, subject `[mirror-help.arrayhash.com] Your subscription #<H1> is on hold`, To `slt-email@example.test`, body naming `SLT Lifetime One Time`.
4. Final row `enabled => yes`, blank subject/heading; the row existing where it did not is accepted residue.

## Emails expected
| # | Email | Trigger point | Recipient | Subject contains | Verify with |
|---|---|---|---|---|---|
| 1 | new_subscription + admin_new_subscription + Woo order | Step 4 checkout | slt-email@ / admin | `is active` | `wait-new $MP0 120` |
| 2 | NONE EXPECTED | Step 8, OFF | — | — | `wait-new $MP1 180 "is on hold"` exits **124** |
| 3 | NONE EXPECTED | Step 9, On hold→Active | — | — | `latest-id` unchanged |
| 4 | subscription_on_hold | Step 11, restored ON | slt-email@example.test | `is on hold` | `wait-new $MP2 180 "is on hold"` succeeds |

## Evidence to capture
- Screenshots `SLT-EML-11-01-user.png`, `-02-checkout.png`, `-03-h1-no-actions.png`, `-04-onhold-off.png`, `-05-onhold-on.png`, `-06-mailpit-html.png`.
- IDs (user, order, **H1**, `MP0/MP1/MP2`, delivered id); UTC bracket open/close; option dumps.

## Pass criteria
- [ ] slt-email created, zero notification mail; H1 active with no pending action
- [ ] OFF ⇒ on-hold transition sends nothing (exit 124); On hold→Active sends nothing
- [ ] ON ⇒ exactly one on-hold mail, exact default subject, H1's real values
- [ ] Option row restored to enabled=yes; H1 left `arraysubs-active`

## Isolation / teardown
- Global setting touched: the on-hold `enabled` flag, OFF for the recorded bracket only. Do **not** run this bracket after 2026-08-05 — SLT Retry Daily reaches on-hold 2026-08-06 and must send its own mail.
- Handed on: `slt-email` and **H1** for SLT-EML-12/13. Restores: the on-hold checkbox only.


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
