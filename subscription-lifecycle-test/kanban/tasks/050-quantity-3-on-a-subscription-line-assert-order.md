---
id: 50
title: Quantity 3 on a subscription line — assert order total, _quantity, unit _recurring_amount and the renewal amount
status: todo
priority: high
created: 2026-08-02T03:43:07.311803205+02:00
updated: 2026-08-02T03:43:17.716522617+02:00
tags:
    - checkout
    - day-03
    - has-conflicts
due: "2026-08-05"
estimate: 1h 15m
depends_on:
    - 10
    - 11
    - 12
    - 5
    - 77
class: standard
---

> **SLT-CHK-09** · group `checkout` · scheduled **D03** (2026-08-05)

> Read `README.md` (environment + isolation contract), `calendar.md` (this day's exact
> ordering — it is binding, not advisory) and `plan-audit.md` before starting.

### ⚠ Conflict resolutions that apply to this task

**`critical` · shared-global-setting / undeclared exclusive bracket** — with `SLT-EML-12`, `SLT-CPN-04`, `SLT-SYN-14`, `SLT-CHK-05`, `SLT-ADM-05`, `SLT-EML-06`

- *Problem:* SLT-EML-12 (d3) writes the WooCommerce per-email Subject/Heading/Additional content on arraysubs_new_subscription globally, for a bracket it only vaguely bounds ('run after 12:00'). Every new_subscription email site-wide inside that bracket carries the subject 'SLT-EML-12 {customer_first_name} :: sub ...'. Four other D3 tasks place checkouts and gate on the default subject: SLT-CHK-09 ('mailpit-agent wait-new MB09 180 "is active"'), SLT-CPN-04 ('wait-new $M0 120 "is active"', 18:00-19:00), SLT-SYN-14 ('wait-new M0 180', after 12:00), plus SLT-ADM-05's status-change activation on D3. Any of these landing inside EML-12's bracket exits 124 and files a false 'missing email' bug. EML-12's own admin_new_subscription count (expects exactly 3) is also corrupted by any foreign checkout in the bracket.
- *Required fix:* Make EML-12 a declared exclusive bracket, same pattern as SLT-SYN-04's: fixed window 21:00-21:40 site on D3 (2026-08-05), after CPN-04's 18:00-19:00 slot has closed; open/close UTC timestamps written to slt-evidence/SLT-EML-12-bracket.txt and posted to the registry; no other SLT task may place an order, activate a subscription, or run a checkout inside it. Add a pre-flight step: assert no SLT checkout task is in-progress on the board. Apply the identical treatment to SLT-EML-13's admin-email OFF bracket (see separate entry).

---
## Objective
Buy three units of one subscription product on a single line and pin down which number lives where: checkout charges unit x quantity, but the subscription stores the UNIT price in `_recurring_amount` and the multiplier in `_quantity`, and the renewal order rebuilds the total as `_recurring_amount * _quantity`. Also confirm that with `one_per_product = false` no quantity clamp fires and the one-per-product notice never appears.

## Scope
- Gateway: Stripe test
- Checkout: block (page 8) for the purchase; classic cart harness for the stepper probe
- Account: new registered — **this task creates** `slt-chk-qty`
- Plugins: free-only

## Preconditions
- SLT-SETUP-01/02/03 and SLT-PROD-01 complete. SLT-CHK-06 already proved same-product-twice merges.
- **Creates one user beyond the SLT-SETUP-03 matrix**: `slt-chk-qty` / `slt-chk-qty@example.test`, Customer, `SltQa!2026#Pass`, billing per SLT-SETUP-03 step 4 — no existing slt-* account may buy SLT Daily Core twice.
- Frozen baseline: `one_per_product=false`, `one_per_customer=false`, `allow_multiple_in_cart=false`. Do not change.
- Code contract: `SubscriptionCreationTrait::createSubscription()` sets `quantity = max(1, item->get_quantity())` and `recurring_amount = subscription_data['price']` (unit). `RecurringBilling/Services/OrderCreation.php:100,138-139` sets renewal item qty from `_quantity` and `subtotal = total = price * quantity`.

## Test data
| Item | Value |
|---|---|
| Product | SLT Daily Core, `/slt-daily-core`, $10.00, day/1, no trial, no fee |
| Account | `slt-chk-qty` (created here) |
| Card | `4242 4242 4242 4242`, future expiry, CVC 123 |
| Quantity | 3 |
| Amounts | today $10.00 x 3 = **$30.00**; `_recurring_amount` **10.00**; renewal **$30.00** |
| Session | `--session cust-SLT-CHK-09` |

## Steps
1. Create `slt-chk-qty` at `/wp-admin/user-new.php` exactly as SLT-SETUP-03 step 2 (Send User Notification UNTICKED); set billing address via `user-edit.php`.
2. `mailpit-agent latest-id` → `MB09`.
3. `agent-browser --session cust-SLT-CHK-09 open "https://mirror-help.arrayhash.com/my-account"` → `snapshot -i` → log in as `slt-chk-qty` / `SltQa!2026#Pass`.
4. Confirm the cart is empty at `https://mirror-help.arrayhash.com/slt-classic-cart`.
5. After 12:00 site time: open `/slt-daily-core` → `snapshot -i` → set **Quantity** to `3` → add to cart.
6. Open `https://mirror-help.arrayhash.com/cart` → `snapshot -i`; read line qty and subtotal; screenshot.
7. Open `https://mirror-help.arrayhash.com/checkout` → `snapshot -i`; confirm the summary and recurring line; select **Stripe** and fill the card.
8. Record the wall-clock site time (UTC+6) immediately before **Place order** — the start anchor.
9. Click **Place order**; re-snapshot the thank-you page; record the order id.
10. `mailpit-agent wait-new MB09 180 "is active"`.
11. From WP root: `wp post list --post_type=arraysubs_data --field=ID --allow-root | tail -5`, then `wp post meta list <SUB_ID> --keys=_quantity,_recurring_amount,_subscription_price,_product_id,_next_payment_date,_status --allow-root`.
12. `wp wc order get <ORDER_ID> --user=admin --allow-root`; record total, line qty, line total.
13. Follow-up 2026-08-06/07 (daily watch): compute `offset = crc32('arraysubs-spread-'.<SUB_ID>) % 21600` (SLT-REF-01); invoice leg at `due+offset-6h`, charge leg at `due+offset`. Record the window, then open the renewal order and record its total and line quantity.
14. Empty the cart; `agent-browser --session cust-SLT-CHK-09 close`.

## Expected results
1. Cart line qty `3`, line subtotal `$30.00`, total `$30.00`, no tax line.
2. No `One Subscription per Product is enabled…` notice appears (gated on `one_per_product`, false).
3. Order total exactly `30.00`; single line item with `quantity = 3`, `total = 30.00`.
4. Exactly ONE `arraysubs_data` post created for this order.
5. `_quantity = 3`; `_recurring_amount = 10.00` (unit, NOT 30.00); `_subscription_price = 10.00`; `_product_id` = SLT Daily Core.
6. `_next_payment_date` = step-8 timestamp + 1 day (anniversary; global sync OFF per SLT-SETUP-02), stored UTC.
7. Status reaches `arraysubs-active`.
8. The renewal order created inside `[due, due+6h]` totals `$30.00` with line qty 3 — proving renewal multiplies unit x quantity rather than renewing one unit or replaying the parent order total.

## Emails expected
| # | Email | Trigger point | Recipient | Subject contains | Verify with |
|---|---|---|---|---|---|
| 1 | `new_subscription` | status → active on payment | slt-chk-qty@example.test | `is active` | `mailpit-agent wait-new MB09 180 "is active"` |
| 2 | `admin_new_subscription` | same moment | site admin | `New subscription #` | `mailpit-agent list 20` |
| 3 | WooCommerce order email | order processing/completed | customer | `order` | `mailpit-agent list 20` |
| 4 | NONE EXPECTED — `renewal_upcoming` | 3-day lead exceeds the 1-day cycle | — | — | explicit absence check in `mailpit-agent list 50` |

## Evidence to capture
- `SLT-CHK-09-01-cart-qty3.png`, `-02-checkout-summary.png`, `-03-thankyou.png`, `-04-subscription-admin.png`, `-05-renewal-order.png`.
- Order/subscription ids, step-11 meta, computed offset, Mailpit ids, console/network errors.

## Pass criteria
- [ ] Checkout charges $30.00 with line quantity 3
- [ ] `_quantity = 3` and `_recurring_amount = 10.00`
- [ ] Exactly one subscription created
- [ ] No one-per-product clamp or notice
- [ ] Renewal order totals $30.00, qty 3, inside the computed window
- [ ] Emails 1-3 arrive, email 4 does not

## Isolation / teardown
- Handed on: the subscription stays ACTIVE and renews daily; it joins the day/1 cohort cancelled by SLT-SETUP-99A.
- Creates user `slt-chk-qty` — add to the registry so SLT-SETUP-99B deletes it.
- No global setting touched; cart emptied, session closed.

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
