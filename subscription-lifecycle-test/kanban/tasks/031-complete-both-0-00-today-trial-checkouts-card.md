---
id: 31
title: 'Complete both $0.00-today trial checkouts: card still collected, first real charge scheduled'
status: todo
priority: critical
created: 2026-08-02T03:43:05.664470268+02:00
updated: 2026-08-02T03:43:15.949514485+02:00
tags:
    - checkout
    - day-02
    - has-conflicts
due: "2026-08-04"
estimate: 1h 30m
depends_on:
    - 37
    - 38
    - 10
    - 12
class: standard
---

> **SLT-CHK-15** · group `checkout` · scheduled **D02** (2026-08-04)

> Read `README.md` (environment + isolation contract), `calendar.md` (this day's exact
> ordering — it is binding, not advisory) and `plan-audit.md` before starting.

### ⚠ Conflict resolutions that apply to this task

**`high` · session/cart collision (persistent cart)** — with `SLT-CHK-01`, `SLT-CHK-14`, `SLT-LIFE-04`, `SLT-CHK-11`, `SLT-CHK-13`, `SLT-MYA-02`

- *Problem:* Audit C09's fix - one named agent-browser session per task - isolates GUEST carts only. WooCommerce persists a logged-in customer's cart to user meta (_woocommerce_persistent_cart_<blog_id>) and restores it into any session that authenticates as that user. Several tasks therefore share a cart despite having distinct session names: on D0 slt-core is used concurrently by SLT-CHK-01 (cust-SLT-CHK-01), SLT-CHK-14 (core-CHK14) and SLT-LIFE-04 (life04); on D2 slt-trial by SLT-CHK-15 (trial-CHK15) and SLT-EML-09 (cust-SLT-EML-09); on D4/D5 slt-core by SLT-CHK-13 (core-CHK13), SLT-CHK-11 (core-CHK11), SLT-MYA-02 and SLT-ADM-02. A leftover subscription line leaking across sessions makes allow_multiple_in_cart=false reject the next add-to-cart for the wrong reason, or - worse - a two-subscription cart reaches checkout and the wrong subscription is created.
- *Required fix:* Add a standing rule to the isolation contract: never run two tasks concurrently under the same slt-* login, and serialise same-account tasks within a day (the calendar's intra-day ordering is binding, not advisory). Every task that logs in must, as its first browser action after login, assert the cart is EMPTY and treat a non-empty cart as a STOP condition with an issue filed - not as something to silently empty. Add a WP-CLI pre-flight to same-account days: `wp user meta get <uid> _woocommerce_persistent_cart_1 --allow-root` must be empty before the task's checkout, and empty again at teardown.

**`medium` · contradictory-expected-result** — with `SLT-EML-09`, `SLT-EML-01`

- *Problem:* SLT-CHK-15 expected result 7 requires SLT Trial Four Day's subscription to carry `_renewal_reminder_action_id` due 2026-08-05 (trial end 08-08 minus the 3-day lead) and asserts it exists. SLT-EML-09 step 4 asserts the opposite - 'wp action-scheduler list --hooks=arraysubs_send_renewal_reminder ... | grep <S_TR>' must return nothing - and expected result 3 says 'No pending arraysubs_send_renewal_reminder or arraysubs_send_expiring_soon action for S_TR'. Both tasks buy/attach to the same subscription on D2. One of them will file a bug the other declares correct.
- *Required fix:* Separate the action from the mail. Per SLT-REF-05 / EmailManager.php:806 the reminder handler requires post_status exactly `arraysubs-active`, and a trialling subscription is `arraysubs-trial` - so the ACTION may legitimately be scheduled while the MAIL is legitimately never sent. Restate CHK-15 ER7 as 'an arraysubs_send_renewal_reminder action for S_TR exists at trial_end - 3d + k; record whether it does'. Restate EML-09 ER3 as 'no reminder MAIL for S_TR on 2026-08-05; whether the action exists is recorded, not asserted'. Make the D4 watch row carry both as an explicit paired check.

---
## Objective
Run both $0.00-today checkouts as `slt-trial`: `SLT Free Signup Daily` (2-day trial, $8.00) on block checkout, `SLT Trial Four Day` (4-day trial, $12.00) on the classic harness page. Prove `require_payment_method=true` still collects and stores a card on a zero-total order, the sub opens `arraysubs-trial`, and the first real charge lands at trial end + offset.

## Scope
- Gateway: Stripe test
- Checkout: both
- Account: existing
- Plugins: free-only

## Preconditions
- `SLT-PROD-02`, `SLT-PROD-03` complete; neither has a signup fee (a fee forces payment). Quote both IDs from the registry.
- `SLT-SETUP-01` (harness pages), `SLT-SETUP-02`, `SLT-SETUP-03` (`slt-trial` + address). Buy A first, then B (`SLT-PROD-03` handoff).
- The trial reminder is `RenewalReminderEmail` in trial context on `renewal_upcoming.days_before=3`; `trial_ending.days_before` is inert (REF-04 B3).
- **Execute after 12:00 site time**. Session `trial-CHK15`; cart empty first/last.

## Test data
| Item | Value |
|---|---|
| A | SLT Free Signup Daily — $0.00 today, 2-day trial, then $8.00/day |
| B | SLT Trial Four Day — $0.00 today, 4-day trial, then $12.00/day |
| Account | slt-trial / `SltQa!2026#Pass` |
| Card | 4242 4242 4242 4242 |
| Dates | A: trial end 08-06, $8.00. B: trial end 08-08, $12.00, reminder 08-05 |

## Steps
1. `PREV1=$(mailpit-agent latest-id)`.
2. `agent-browser --session trial-CHK15 open "https://mirror-help.arrayhash.com/my-account/"` -> log in `slt-trial`.
3. Add **SLT Free Signup Daily** -> `/cart/` -> `snapshot -i`: $0.00, no fee row, trial summary.
4. `/checkout/` -> `snapshot -i`. **Negative probe first**: leave card fields empty, **Place Order**, screenshot and quote the blocking message.
5. Enter 4242, **Place Order**. Record order + sub ID. `mailpit-agent wait-new "$PREV1" 180 "free trial for"`.
6. `PREV2=$(mailpit-agent latest-id)`. Empty cart, add **SLT Trial Four Day**, `/slt-classic-cart` -> `snapshot -i` ($0.00), then `/slt-classic-checkout` -> card still required -> pay 4242 -> **Place Order**.
7. Both subs: `wp post meta list <SUB_ID> --keys=_billing_period,_billing_interval,_recurring_amount,_signup_fee,_trial_length,_trial_end_date,_next_payment_date,_payment_method,_renewal_reminder_action_id --allow-root`.
8. Confirm a reusable method stored: `_payment_method=stripe` + the order's Stripe token meta.
9. Per sub compute `k = crc32('arraysubs-spread-'.SUBID) % 21600`; invoice `trial_end+k−6h`, charge `trial_end+k`. Verify both rows in Scheduled Actions.
10. Empty cart; `close --session trial-CHK15`.
11. Watch: 08-05 B reminder; 08-06 A converts ($8.00); 08-08 B converts ($12.00).

## Expected results
1. Both orders total exactly **$0.00**, no tax or fee line, `processing`/`completed`.
2. Step-4 probe: refused without a card, message quoted. If it succeeds, that contradicts `require_payment_method=true` and is filed.
3. Both subs open `arraysubs-trial`, not `arraysubs-active`.
4. A: `_trial_length=2`, `_trial_period=day`, `_trial_end_date` 08-06, `_recurring_amount=8.00`, `_billing_interval=1`, `_signup_fee` empty/0. B: `_trial_length=4`, `_trial_end_date` 08-08, `_recurring_amount=12.00`. On both `_next_payment_date` = trial end.
5. `_payment_method=stripe` on both with a stored Stripe token, so off-session charging works at trial end.
6. Both renewal legs pending at the step-9 times.
7. B has `_renewal_reminder_action_id` due 08-05 (trial end − 3 days); A has none, its due point being past at checkout — that suppression is A's purpose.

## Emails expected
| # | Email | Trigger point | Recipient | Subject contains | Verify with |
|---|---|---|---|---|---|
| 1 | trial_started (A) | A paid | slt-trial@example.test | `free trial for SLT Free Signup Daily` | `wait-new "$PREV1" 180 "free trial"` |
| 2 | trial_started (B) | B paid | slt-trial@example.test | `free trial for SLT Trial Four Day` | `wait-new "$PREV2" 180 "SLT Trial Four Day"` |
| 3 | NONE EXPECTED — new_subscription | either order | — | — | No `is active` to slt-trial today; that is conversion day |
| 4 | NONE EXPECTED — reminder for A | ever | — | — | 2-day trial vs 3-day lead: no `ends soon` for product A |

## Evidence to capture
- `SLT-CHK-15-01-cart-a.png`, `-02-no-card-refused.png`, `-03-received-a.png`, `-04-classic-cart-b.png`, `-05-classic-checkout-b.png`, `-06-card.png`.
- Order/sub IDs, meta dumps, offsets, no-card message, Mailpit IDs, console/network errors.

## Pass criteria
- [ ] Both orders total exactly $0.00, no fee line
- [ ] Checkout refuses a $0.00 order with no card (block + classic)
- [ ] Both subs in `arraysubs-trial` with the right `_trial_end_date`
- [ ] A payment method stored on both
- [ ] First charges at trial end + offset ($8.00 08-06, $12.00 08-08)
- [ ] Reminder action exists for B, not for A
- [ ] Emails 1-2 captured; negatives 3-4 hold

## Isolation / teardown
- Two live trial subs for the watch; cancelled by `SLT-SETUP-99A` on D10.
- Nothing global changed; cart emptied; `trial-CHK15` closed.

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
