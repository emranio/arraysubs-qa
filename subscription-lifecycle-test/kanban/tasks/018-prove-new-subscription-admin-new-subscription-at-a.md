---
id: 18
title: Prove new_subscription + admin_new_subscription at a real Stripe block checkout
status: todo
priority: high
created: 2026-08-02T03:43:04.574241475+02:00
updated: 2026-08-02T03:43:14.589334196+02:00
tags:
    - email
    - day-01
    - has-conflicts
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

### ⚠ Conflict resolutions that apply to this task

**`critical` · shared-global-setting / undeclared exclusive bracket** — with `SLT-EML-12`, `SLT-CHK-09`, `SLT-CPN-04`, `SLT-SYN-14`, `SLT-CHK-05`, `SLT-ADM-05`

- *Problem:* SLT-EML-12 (d3) writes the WooCommerce per-email Subject/Heading/Additional content on arraysubs_new_subscription globally, for a bracket it only vaguely bounds ('run after 12:00'). Every new_subscription email site-wide inside that bracket carries the subject 'SLT-EML-12 {customer_first_name} :: sub ...'. Four other D3 tasks place checkouts and gate on the default subject: SLT-CHK-09 ('mailpit-agent wait-new MB09 180 "is active"'), SLT-CPN-04 ('wait-new $M0 120 "is active"', 18:00-19:00), SLT-SYN-14 ('wait-new M0 180', after 12:00), plus SLT-ADM-05's status-change activation on D3. Any of these landing inside EML-12's bracket exits 124 and files a false 'missing email' bug. EML-12's own admin_new_subscription count (expects exactly 3) is also corrupted by any foreign checkout in the bracket.
- *Required fix:* Make EML-12 a declared exclusive bracket, same pattern as SLT-SYN-04's: fixed window 21:00-21:40 site on D3 (2026-08-05), after CPN-04's 18:00-19:00 slot has closed; open/close UTC timestamps written to slt-evidence/SLT-EML-12-bracket.txt and posted to the registry; no other SLT task may place an order, activate a subscription, or run a checkout inside it. Add a pre-flight step: assert no SLT checkout task is in-progress on the board. Apply the identical treatment to SLT-EML-13's admin-email OFF bracket (see separate entry).

**`low` · duplicate-coverage** — with `SLT-EML-15`, `SLT-REN-02`, `SLT-ADM-06`, `SLT-EML-03`, `SLT-CHK-01`, `SLT-EML-07`

- *Problem:* Six overlapping clusters, each spending an execution slot on a code path another task already proves. (a) SLT-EML-15, SLT-REN-02 and SLT-ADM-06 all read the same SUB_CORE renewal cycle: EML-15 reconciles the mail set, REN-02 asserts the schedule re-arm, ADM-06 asserts the order typing/linkage - three tasks, one cycle, three separate evidence sets. (b) SLT-EML-03's Stripe leg re-asserts REN-02's payment_successful and its Paddle leg re-asserts SLT-REN-04's. (c) SLT-EML-06 re-proves new_subscription + admin_new_subscription at a Stripe block checkout, which is SLT-CHK-01's email rows 3 and 4 verbatim, on a new account. (d) SLT-EML-07 and SLT-SW-10 both drive pending-cancellation -> cancelled -> reactivation and both assert the same four emails. (e) SLT-EML-04's four payment_failed pairs are exactly SLT-DUN-01 ER8 plus SLT-DUN-02 ER9. (f) SLT-SYN-11's 'flex section hidden for a Different Renewal Price product' repeats SLT-PROD-05 steps 7-9 and SLT-SYN-01's probe. (g) SLT-LIFE-02's Paddle 'no Renew Early control' negative repeats SLT-CHK-04 ER7 and SLT-PROD-16.
- *Required fix:* Keep one owner per assertion and make the others cite it. (a) EML-15 owns the reconciled mail set for the cycle and publishes it to the registry; REN-02 keeps only the schedule/offset/no-drift assertions; ADM-06 keeps only the HPOS meta assertions and drops its Related-Orders screenshot in favour of ADM-02's. (b) EML-03 keeps only the content assertions (amount, method row, UTC+6 next date, the Paddle ordering hazard) and cites REN-02/REN-04 for 'the renewal fired'. (c) EML-06 keeps only the gating-key and subject-string proof (emails.new_subscription.enabled, admin recipient resolution, the B4 dead-setting verdict) and cites CHK-01 for the checkout. (d) EML-07 owns the email set; SW-10 owns the reason-required / offers-declined / scheduled-cancel-timestamp / reactivation-scheduling-bug half and cites EML-07's mailpit ids. (e) EML-04 places no purchase and becomes the mail-content rider on the DUN ladder (attempt-number visibility, Pay Now link resolution, To: headers) - see the DUN re-day entry. (f) SYN-11 keeps only the force-set-meta half (isEnabled() true, getConfig() null, zero _renewal_sync_* on the subscription) and cites PROD-05 for the UI-absence screenshots. (g) LIFE-02 cites CHK-04's screenshot rather than re-driving the Paddle portal.

---
## Objective
Prove one real Stripe block checkout emits exactly two ArraySubs signup emails — `new_subscription` to the buyer, `admin_new_subscription` to the admin — with the documented subject, recipient and gating key, and nothing else. Creates `slt-eml` and `S_EML` for EML-07/-08/-10.

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
2. `mailpit-agent latest-id` → `$PRE_USER`. Create `slt-eml` at `user-new.php`, set billing at `user-edit.php`; `mailpit-agent list 20` — nothing new.
3. Customer session → `/my-account/`, log in; `/cart/` empty; `/cart/?add-to-cart=<Daily Core ID>` → line total `$10.00`.
4. `mailpit-agent latest-id` → `$PRE_BUY`. `/checkout/` → **Credit Card (Stripe)** → card → **Place Order**. Record the order ID.
5. `mailpit-agent wait-new "$PRE_BUY" 120 "is active"`; `wait-new "$PRE_BUY" 120 "New subscription"`; `show` both, `text` the customer one.
6. `mailpit-agent list 30` — classify every message from the checkout, ArraySubs vs Woo core.
7. Record `S_EML` from `edit.php?post_type=arraysubs_data`; `wp post meta list <S_EML> --keys=_arraysubs_status_change_context,_next_payment_date --allow-root`.
8. `wp action-scheduler list --hooks=arraysubs_send_renewal_reminder --status=pending --allow-root | grep <S_EML>` — expect nothing: 1-day cycle − 3-day lead is past (`EmailManager.php:779`).
9. B4: `emails.subscription_activated.*` is in defaults (`settings-helpers.php:190-194`) but nothing reads it — if Settings → Emails renders that toggle, file `issues/SLT-EML-06-activated-dead-setting.md`.
10. Empty the cart; close both sessions.

## Expected results
1. `S_EML` is `arraysubs-active`, `_arraysubs_status_change_context = initial_payment`, `_next_payment_date` = checkout time + 1 day.
2. Customer subject exactly `[mirror-help.arrayhash.com] Your subscription #<S_EML> is active`; To `slt-eml@example.test`; body names SLT Daily Core, $10.00, that date; no tax line.
3. Admin subject exactly `[mirror-help.arrayhash.com] New subscription #<S_EML> from SLT Eml`; To `admin@mirror-help.arrayhash.com` (`emails.admin_email` empty → site admin_email); customer not copied.
4. `emails.new_subscription.enabled = true`; `emails.admin_new_subscription = true` (flat bool); both WC rows Enabled.
5. No `renewal_invoice`, `payment_received`, `renewal_reminder` or `trial_started` mail, no pending reminder action, exactly one of each signup mail (`EmailManager.php:330-344` branches must not both fire). Woo core order mail may also arrive; log it apart.

## Emails expected
| # | Email | Trigger point | Recipient | Subject contains | Verify with |
|---|---|---|---|---|---|
| 1 | new_subscription | step 4 | slt-eml@example.test | `subscription #<S_EML> is active` | `wait-new "$PRE_BUY" 120 "is active"` |
| 2 | admin_new_subscription | step 4 | admin@mirror-help… | `New subscription #<S_EML> from SLT Eml` | `wait-new "$PRE_BUY" 120 "New subscription"` |
| 3 | NONE EXPECTED — invoice / payment_received / reminder / trial_started | signup | — | — | absent from `list 30` |
| 4 | NONE EXPECTED | step 2 (user creation) | — | — | nothing after `$PRE_USER` |

## Evidence to capture
- `SLT-EML-06-01-wc-email-rows.png`, `-02-order-received.png`, `-03-mailpit.png`; `S_EML`/order/user ids, both Mailpit ids and baselines, steps 1/7/8 output, console errors.

## Pass criteria
- [ ] `S_EML` active, `initial_payment` context, next payment +1 day
- [ ] Customer + admin subjects/recipients exactly as results 2-3; both gating keys true and WC rows enabled
- [ ] No duplicate signup mail, no renewal/trial/reminder activity, no mail from user creation; B4 verdict recorded

## Isolation / teardown
- Register `slt-eml` + `S_EML` in the registry: **S_EML renews $10.00 daily from D1 until EML-07 cancels it on D2**, so the watch maps those orders/mails here.
- Hand-off: EML-07 (D2) on-hold → pending-cancel → cancelled; -08 (D8) reactivates; -10 (D8) cancels it for good.
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
  fire on their own; **a renewal that does not fire is a real bug** — capture evidence before forcing.
- Give this task its own browser session (`agent-browser --session <role>-<TASK-KEY>`). Sessions are
  keyed by name and **share a cart**.
- Never run `wp action-scheduler run` without `--hooks=`; prefer a single action by ID.
- Evidence goes in `qa/subscription-lifecycle-test/evidence/<TASK-KEY>/`.
