---
id: 18
title: Prove new_subscription + admin_new_subscription at a real Stripe block checkout
status: done
priority: high
created: 2026-08-02T03:43:04.574241475+02:00
updated: 2026-08-05T21:37:49.33872117+02:00
started: 2026-08-03T10:30:22.957780205+02:00
completed: 2026-08-03T10:30:22.957780205+02:00
tags:
    - email
    - day-01
due: "2026-08-03"
estimate: 1h
depends_on:
    - 10
    - 11
    - 12
    - 5
class: standard
---

> **SLT-EML-06** · group `emails` · scheduled **D01** (2026-08-03)

> Read `README.md` (environment + isolation contract), `calendar.md` (this day's exact
> ordering — it is binding, not advisory) and `plan-audit.md` before starting.

## Objective
Prove one real Stripe block checkout emits exactly two ArraySubs signup emails — `new_subscription` to the buyer and `admin_new_subscription` to the admin — with the documented subject, recipient, and gating key, and no other ArraySubs lifecycle email. WooCommerce order emails are classified separately as already required below. Creates `slt-eml` and `S_EML` for EML-07/-08/-10.

## Scope
- Gateway: Stripe test
- Checkout: block
- Account: new registered (this task CREATES `slt-eml`)
- Plugins: both

## Preconditions
- SLT-SETUP-01/-02/-03 done; SLT-PROD-01 published **SLT Daily Core** ($10.00, day/1).
- CREATES one account beyond the SLT-SETUP-03 matrix: `slt-eml` (Customer, `SltQa!2026#Pass`, billing per SETUP-03 step 4, **Send User Notification off**).
- Runs after 12:00 site time (C02). Sessions `admin-SLT-EML-06`, `cust-SLT-EML-06`; cart empty first and last (C09).

## Test data
| Item | Value |
|---|---|
| Product | SLT Daily Core `slt-daily-core`, $10.00 / 1 day |
| Account | slt-eml / slt-eml@example.test / `SltQa!2026#Pass` |
| Card | `4242 4242 4242 4242`, `12/34`, CVC `123` |

## Steps
1. WP root: `wp option get arraysubs_settings --format=json --allow-root | jq '.emails'`; record `new_subscription`, `admin_new_subscription`, `admin_email`, `subscription_activated`. Then at `admin.php?page=wc-settings&tab=email` record enabled + Recipient(s) for both `[ArraySubs] … New Subscription` rows.
2. `PRE_USER=$(mailpit-agent latest-id)`. Create `slt-eml` at `user-new.php` with **Send User Notification** unticked, then set billing at `user-edit.php`. `mailpit-agent wait-new "$PRE_USER" 60 "New User Registration"`; record the one admin-only message and prove there is no customer message whose subject contains the live WooCommerce phrase `account has been created` and no password/setup message, matching the corrected `SLT-SETUP-03` contract.
3. Customer session → `/my-account/`, log in; prove `/cart/` and persistent-cart meta empty. Open `/cart/?add-to-cart=<Daily Core ID>` and require the frozen `checkout.one_click_mode = subscription_items` behaviour to redirect directly to `/checkout/`; confirm the checkout order summary names **SLT Daily Core** at `$10.00`, then open `/cart/` explicitly to record the populated cart line at `$10.00` and return to `/checkout/`.
4. `PRE_BUY=$(mailpit-agent latest-id)`. `/checkout/` → **Credit Card (Stripe)** → card → **Place Order**. Record the order ID.
5. `mailpit-agent wait-new "$PRE_BUY" 120 "is active"`; `mailpit-agent wait-new "$PRE_BUY" 120 "New subscription"`; `show` both, `text` the customer one.
6. Inspect the complete owner-filtered delta after `$PRE_BUY` — classify every message from the checkout, ArraySubs vs Woo core, and preserve unrelated/background mail separately.
7. Read the order's exact subscription linkage from WordPress post meta, not `WC_Order::get_meta()`: `LINK_JSON=$(wp post meta get "$ORDER" _subscription_ids --format=json --allow-root)` then `S_EML=$(jq -er 'if type == "array" and length == 1 and (.[0] | tostring | test("^[0-9]+$")) then .[0] else error("expected exactly one numeric subscription id") end' <<<"$LINK_JSON")`. Abort unless `[[ "$S_EML" =~ ^[0-9]+$ ]]`, then cross-check `_parent_order_id`, `_customer_id`, `_product_id`, `_completed_payments = 1`, and the recorded before/after subscription count. Confirm the resolved ID in `admin.php?page=arraysubs-mainadmin#/subscriptions` by searching the exact numeric ID and opening **View Details**; record `wp post meta list "$S_EML" --keys=_arraysubs_status_change_context,_next_payment_date --allow-root`. `_arraysubs_status_change_context` is a hook-time context, not a durable postcondition on this runtime, so its absence after checkout is expected; use the completed parent order, first completed payment, active state, dates, and exact signup-mail pair as the durable initial-payment proof.
8. `wp db query "SELECT action_id,hook,status,scheduled_date_gmt,args FROM wp_actionscheduler_actions WHERE hook='arraysubs_send_renewal_reminder' AND status='pending' AND JSON_UNQUOTE(JSON_EXTRACT(args,'\$[0]'))='$S_EML' ORDER BY action_id;" --allow-root` — expect no rows: 1-day cycle − 3-day lead is past (`EmailManager.php:779`).
9. B4: `emails.subscription_activated.*` is in defaults (`settings-helpers.php:190-194`) but nothing reads it — if Settings → Emails renders that toggle, file `issues/SLT-EML-06-activated-dead-setting.md`.
10. Reopen the cart, prove it and persistent-cart meta empty, capture the final state, and close only `admin-SLT-EML-06` and `cust-SLT-EML-06`.

## Expected results
1. `S_EML` is `arraysubs-active`, its completed parent order and `_completed_payments = 1` prove the initial payment, and `_next_payment_date` = checkout time + 1 day. The hook-time `_arraysubs_status_change_context` need not remain in post meta after processing.
2. Customer subject exactly `[mirror-help.arrayhash.com] Your subscription #<S_EML> is active`; To `slt-eml@example.test`; body names SLT Daily Core, $10.00, that date; no tax line.
3. Admin subject exactly `[mirror-help.arrayhash.com] New subscription #<S_EML> from SLT Eml`; To `admin@mirror-help.arrayhash.com` (`emails.admin_email` empty → site admin_email); customer not copied.
4. `emails.new_subscription.enabled = true`; `emails.admin_new_subscription = true` (flat bool); both WC rows Enabled.
5. No `renewal_invoice`, `payment_received`, `renewal_reminder` or `trial_started` mail, no pending reminder action, exactly one of each signup mail (`EmailManager.php:330-344` branches must not both fire). The normal WC admin new-order and customer completed-order messages also arrive; require and log both apart from the ArraySubs assertion.

## Emails expected
| # | Email | Trigger point | Recipient | Subject contains | Verify with |
|---|---|---|---|---|---|
| 1 | new_subscription | step 4 | slt-eml@example.test | `subscription #<S_EML> is active` | `mailpit-agent wait-new "$PRE_BUY" 120 "is active"` |
| 2 | admin_new_subscription | step 4 | admin@mirror-help… | `New subscription #<S_EML> from SLT Eml` | `mailpit-agent wait-new "$PRE_BUY" 120 "New subscription"` |
| 3 | WC New order | paid checkout | admin | `New order #<ORDER>` | Complete owner-filtered delta after `$PRE_BUY`; save/show the exact matching id and classify it outside the two-message ArraySubs assertion |
| 4 | WC Completed order | paid virtual checkout | slt-eml@example.test | `is on its way` | Complete owner-filtered delta after `$PRE_BUY`; save/show the exact matching id and classify it outside the two-message ArraySubs assertion |
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
- Register `slt-eml` + `S_EML` in the registry: **S_EML renews $10.00 daily after its D1 purchase; EML-07 schedules end-of-period cancellation late D3 after that day's renewal, and the cancel action fires at the D4 `_next_payment_date`**, so the D2/D3/D4 watch maps those orders/mails here.
- Hand-off: EML-07 (D3) on-hold → active → pending-cancel, then D4 cancelled; -08 (D8) reactivates; -10 (D8) cancels it for good.
- Restores: cart emptied, sessions closed, no global setting written; deleted by SLT-SETUP-99B.

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

---

### D01 execution — 2026-08-03

**PASS after QA-plan corrections C60-C63.** Preflight after 12:00 site time proved both signup gates enabled, the two WooCommerce email rows enabled with Customer/admin recipients, and `checkout.one_click_mode=subscription_items`. User `360` (`slt-eml`, `slt-eml@example.test`, customer) was created with Send User Notification unticked. Its complete post-creation mail delta contained only admin registration message `1c010ymF8ad7jbcTPmBnng`; no customer account or password/setup mail appeared.

The real Stripe block checkout completed order `12253` for USD 10.00 and exact linked subscription `12263`. The HPOS order is completed, owned by user `360`, and has exact post-meta linkage `[12263]`. The subscription is active with parent `12253`, product `11927`, completed payments `1`, start `2026-08-03 08:10:09Z`, next payment `2026-08-04 08:10:09Z`, and no durable status-context row, consistent with corrected hook-time semantics.

The complete checkout delta after `PRE_BUY=1c010ymF8ad7jbcTPmBnng` contained exactly the required four messages: WC completed-order `6ZGRYmDC8R9wliDXZkcl68`, WC admin new-order `131xQbXMlxaN38e5wb4IHR`, customer ArraySubs signup `73OKvBGS3xKv9A51HKIsoT`, and admin ArraySubs signup `4GNRPXmw4J932lbqnS1BoW`. Subjects, recipients, product, amount, dates, gateway, and no-tax content all matched; no duplicate or forbidden lifecycle mail appeared. No reminder action exists. Natural invoice/charge actions `14036`/`14037` are pending for D2 14:05:42/20:05:42 site; capture `O1_PRE` by 20:00:42 and never force them.

B4 found no exposed Emails route or `subscription_activated` toggle, so no issue was warranted. Exact-ID admin search/detail, receipt, Mailpit, network, and final empty-cart checks passed. The final persistent-cart row contains a serialized empty cart. Browser errors were empty; only the already-filed WooCommerce dependency warning appeared. Both task-keyed sessions were closed and only the unrelated `office-agent` session remained. Consolidated evidence: `/home/server-manager/slt-evidence/SLT-EML-06-facts.txt` plus screenshots `-01` through `-05`.
