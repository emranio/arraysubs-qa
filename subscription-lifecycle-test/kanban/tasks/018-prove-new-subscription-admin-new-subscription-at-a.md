---
id: 18
title: Prove new_subscription + admin_new_subscription at a real Stripe block checkout
status: todo
priority: high
created: 2026-08-22T19:10:00+02:00
updated: 2026-08-22T19:10:00+02:00
tags:
    - cycle-2
    - granular
    - email
    - day-01
due: "2026-08-24"
estimate: 1h
depends_on:
    - 10
    - 11
    - 12
    - 5
class: standard
---

> **SLT-EML-06** · group `emails` · scheduled **D01** (2026-08-24)

> Read `README.md` (environment + isolation contract), `calendar.md` (this day's exact
> ordering — it is binding, not advisory) and `plan-audit.md` before starting.

## Objective
Prove one real Stripe block checkout emits exactly two ArraySubs signup emails — `new_subscription` to the buyer and `admin_new_subscription` to the admin — with the documented subject, recipient, and gating key, and no other ArraySubs lifecycle email. WooCommerce order emails are classified separately as already required below. Creates `slt2-eml` and `S_EML` for EML-07/-08/-10.

## Scope
- Gateway: Stripe test
- Checkout: block
- Account: new registered (this task CREATES `slt2-eml`)
- Plugins: both

## Preconditions
- SLT-SETUP-01/-02/-03 done; SLT-PROD-01 published **SLT2 Daily Core** ($10.00, day/1).
- CREATES one account beyond the SLT-SETUP-03 matrix: `slt2-eml` (Customer, `SltQa!2026#Pass`, billing per SETUP-03 step 4, **Send User Notification off**).
- Runs after 12:00 site time (C02). Sessions `admin-SLT-EML-06`, `cust-SLT-EML-06`; cart empty first and last (C09).

## Test data
| Item | Value |
|---|---|
| Product | SLT2 Daily Core `slt2-daily-core`, $10.00 / 1 day |
| Account | slt2-eml / slt2-eml@example.test / `SltQa!2026#Pass` |
| Card | `4242 4242 4242 4242`, `12/34`, CVC `123` |

## Steps
1. WP root: `wp option get arraysubs_settings --format=json --allow-root | jq '.emails'`; record `new_subscription`, `admin_new_subscription`, `admin_email`, `subscription_activated`. Then at `admin.php?page=wc-settings&tab=email` record enabled + Recipient(s) for both `[ArraySubs] … New Subscription` rows.
2. `PRE_USER=$(mailpit-agent latest-id)`. Create `slt2-eml` at `user-new.php` with **Send User Notification** unticked, then set billing at `user-edit.php`. `mailpit-agent wait-new "$PRE_USER" 60 "New User Registration"`; record the one admin-only message and prove there is no customer message whose subject contains the live WooCommerce phrase `account has been created` and no password/setup message, matching the corrected `SLT-SETUP-03` contract.
3. Customer session → `/my-account/`, log in; prove `/cart/` and persistent-cart meta empty. Open `/cart/?add-to-cart=<Daily Core ID>` and require the frozen `checkout.one_click_mode = subscription_items` behaviour to redirect directly to `/checkout/`; confirm the checkout order summary names **SLT2 Daily Core** at `$10.00`, then open `/cart/` explicitly to record the populated cart line at `$10.00` and return to `/checkout/`.
4. `PRE_BUY=$(mailpit-agent latest-id)`. `/checkout/` → **Credit Card (Stripe)** → card → **Place Order**. Record the order ID.
5. `mailpit-agent wait-new "$PRE_BUY" 120 "is active"`; `mailpit-agent wait-new "$PRE_BUY" 120 "New subscription"`; `show` both, `text` the customer one.
6. Inspect the complete owner-filtered delta after `$PRE_BUY` — classify every message from the checkout, ArraySubs vs Woo core, and preserve unrelated/background mail separately.
7. Read the order's exact subscription linkage from WordPress post meta, not `WC_Order::get_meta()`: `LINK_JSON=$(wp post meta get "$ORDER" _subscription_ids --format=json --allow-root)` then `S_EML=$(jq -er 'if type == "array" and length == 1 and (.[0] | tostring | test("^[0-9]+$")) then .[0] else error("expected exactly one numeric subscription id") end' <<<"$LINK_JSON")`. Abort unless `[[ "$S_EML" =~ ^[0-9]+$ ]]`, then cross-check `_parent_order_id`, `_customer_id`, `_product_id`, `_completed_payments = 1`, and the recorded before/after subscription count. Confirm the resolved ID in `admin.php?page=arraysubs-mainadmin#/subscriptions` by searching the exact numeric ID and opening **View Details**; record `wp post meta list "$S_EML" --keys=_arraysubs_status_change_context,_next_payment_date --allow-root`. `_arraysubs_status_change_context` is a hook-time context, not a durable postcondition on this runtime, so its absence after checkout is expected; use the completed parent order, first completed payment, active state, dates, and exact signup-mail pair as the durable initial-payment proof.
8. `wp db query "SELECT action_id,hook,status,scheduled_date_gmt,args FROM wp_actionscheduler_actions WHERE hook='arraysubs_send_renewal_reminder' AND status='pending' AND JSON_UNQUOTE(JSON_EXTRACT(args,'\$[0]'))='$S_EML' ORDER BY action_id;" --allow-root` — expect no rows: 1-day cycle − 3-day lead is past (`EmailManager.php:779`).
9. Revalidate `emails.subscription_activated.*`: if Settings → Emails renders the control, toggle it only inside a recorded restore bracket and prove the resulting activation email obeys the saved value. Restore exactly. Any rendered control that does not affect the live send creates/updates a mandatory `qa/issues/` kanban card.
10. Reopen the cart, prove it and persistent-cart meta empty, capture the final state, and close only `admin-SLT-EML-06` and `cust-SLT-EML-06`.

## Expected results
1. `S_EML` is `arraysubs-active`, its completed parent order and `_completed_payments = 1` prove the initial payment, and `_next_payment_date` = checkout time + 1 day. The hook-time `_arraysubs_status_change_context` need not remain in post meta after processing.
2. Customer subject exactly `[mirror-help.arrayhash.com] Your subscription #<S_EML> is active`; To `slt2-eml@example.test`; body names SLT2 Daily Core, $10.00, that date; no tax line.
3. Admin subject exactly `[mirror-help.arrayhash.com] New subscription #<S_EML> from SLT2 Eml`; To `admin@mirror-help.arrayhash.com` (`emails.admin_email` empty → site admin_email); customer not copied.
4. `emails.new_subscription.enabled = true`; `emails.admin_new_subscription = true` (flat bool); both WC rows Enabled.
5. No `renewal_invoice`, `payment_received`, `renewal_reminder` or `trial_started` mail, no pending reminder action, exactly one of each signup mail (`EmailManager.php:330-344` branches must not both fire). The normal WC admin new-order and customer completed-order messages also arrive; require and log both apart from the ArraySubs assertion.

## Emails expected
| # | Email | Trigger point | Recipient | Subject contains | Verify with |
|---|---|---|---|---|---|
| 1 | new_subscription | step 4 | slt2-eml@example.test | `subscription #<S_EML> is active` | `mailpit-agent wait-new "$PRE_BUY" 120 "is active"` |
| 2 | admin_new_subscription | step 4 | admin@mirror-help… | `New subscription #<S_EML> from SLT2 Eml` | `mailpit-agent wait-new "$PRE_BUY" 120 "New subscription"` |
| 3 | WC New order | paid checkout | admin | `New order #<ORDER>` | Complete owner-filtered delta after `$PRE_BUY`; save/show the exact matching id and classify it outside the two-message ArraySubs assertion |
| 4 | WC Completed order | paid virtual checkout | slt2-eml@example.test | `is on its way` | Complete owner-filtered delta after `$PRE_BUY`; save/show the exact matching id and classify it outside the two-message ArraySubs assertion |
| 5 | NONE EXPECTED — invoice / payment_received / reminder / trial_started | signup | — | — | Absent from the complete owner-filtered delta after `$PRE_BUY` |
| 6 | WP New User Registration | step 2 admin user creation | admin | `New User Registration` | one admin-only message after `$PRE_USER`; no customer account/password mail |

## Evidence to capture
- `SLT-EML-06-01-wc-email-rows.png`, `-02-order-received.png`, `-03-mailpit.png`; `S_EML`/order/user ids, all four checkout Mailpit ids plus the setup-mail id and both baselines, steps 1/7/8 output, console errors.

## Pass criteria
- [ ] `S_EML` active after its completed initial-payment order, `_completed_payments = 1`, exact signup-mail pair present, next payment +1 day; post-run context probe recorded without requiring ephemeral `_arraysubs_status_change_context` meta to persist
- [ ] Customer + admin ArraySubs subjects/recipients exactly as results 2-3; both gating keys true and WC rows enabled; the separate WC new-order and completed-order messages are present and classified
- [ ] No duplicate signup mail or renewal/trial/reminder activity; exactly one admin registration notice and no customer account mail from user creation; B4 verdict recorded
- [ ] Cart and persistent-cart meta empty before and after checkout; exact task sessions closed

## Isolation / teardown
- Register `slt2-eml` + `S_EML` in the registry: **S_EML renews $10.00 daily after its D1 purchase; EML-07 schedules end-of-period cancellation late D3 after that day's renewal, and the cancel action fires at the D4 `_next_payment_date`**, so the D2/D3/D4 watch maps those orders/mails here.
- Hand-off: EML-07 (D3) on-hold → active → pending-cancel, then D4 cancelled; -08 (D8) reactivates; -10 (D8) cancels it for good.
- Restores: cart emptied, sessions closed, no global setting written; deleted by SLT-SETUP-99B.

---

### Fresh-cycle validation contract

- Re-derive every ID, count, option value, gateway capability, scheduler timestamp, and email baseline on this run; no prior-cycle result is evidence.
- Create and mutate only registered `SLT2 ` / `slt2-*` fixtures. Legacy `SLT` and all non-SLT2 data are read-only controls.
- Automatic-gateway scope is Stripe and Paddle only. Stripe is the primary path; run Paddle parity wherever Paddle supports the behavior. Do not test or configure PayPal or Mollie.
- ArraySubs core must own its Stripe/Paddle integration, renewal, retry, webhook, REST, refund, and customer-payment services with Pro inactive; vendor host classes retain their expected ownership.
- Browser-required assertions use Vercel `agent-browser` with isolated task/role sessions and current snapshot refs. WP-CLI always includes `--allow-root`.
- Update the lifecycle card, the matching `qa/progress/` card, and `qa/issues/` for every new regression. Evidence belongs to this fresh cycle only.
