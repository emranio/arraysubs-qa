---
id: 78
title: Anonymous guest checkout of a non-subscription cart must still work — forced registration is scoped to subscription carts only
status: todo
priority: high
created: 2026-08-02T03:43:09.742322937+02:00
updated: 2026-08-02T03:43:20.638724923+02:00
tags:
    - checkout
    - day-05
    - has-conflicts
due: "2026-08-07"
estimate: 1h
depends_on:
    - 10
    - 11
    - 5
    - 59
class: standard
---

> **SLT-CHK-10** · group `checkout` · scheduled **D05** (2026-08-07)

> Read `README.md` (environment + isolation contract), `calendar.md` (this day's exact
> ordering — it is binding, not advisory) and `plan-audit.md` before starting.

### ⚠ Conflict resolutions that apply to this task

**`medium` · contradictory-precondition (factually wrong)** — with `SLT-CHK-03`, `SLT-SETUP-03`

- *Problem:* SLT-CHK-03's objective and precondition assert 'a logged-out visitor cannot check out anonymously - woocommerce_enable_guest_checkout=no'. The README's verified environment baseline says the option is `yes`, and SLT-CHK-10 carries an explicit documentation correction ('That is FALSE - verified yes on 2026-08-02, alongside woocommerce_enable_signup_and_login_from_checkout=yes') and files an issue against SLT-SETUP-03 for the same claim. CHK-03 runs two days before CHK-10, so it will observe an offered guest path for a non-subscription cart, or reason about the wrong mechanism and file a false bug against the checkout registration force.
- *Required fix:* Rewrite CHK-03's objective and precondition to the correct mechanism: guest checkout IS enabled site-wide; registration is forced only for subscription carts, via woocommerce_checkout_registration_required (SubscriptionCheckout/Services/Hooks.php:103, CheckoutHelpersTrait.php:93-100) gated on checkout.auto_create_account=true AND cartHasSubscriptionCheckoutItems(). Keep the assertion 'no continue-as-guest option for THIS cart' and add step 1a: `wp option get woocommerce_enable_guest_checkout --allow-root` must print `yes`. Correct SLT-SETUP-03's objective in the catalog at the same time so CHK-10's issue is a confirmation rather than a discovery.

---
## Objective
Prove a true anonymous purchase — no login, no account created — still completes for a cart holding only a plain non-subscription product, and that account forcing is scoped to subscription carts. `CheckoutHelpersTrait::maybeRequireRegistration()` returns true only when `shouldForceAccountCreationForSubscriptionCheckout()` is true, which needs BOTH `checkout.auto_create_account = true` AND `cartHasSubscriptionCheckoutItems()`. Both legs run in one session: an anonymous non-subscription purchase that must succeed, then a subscription cart that must flip registration back on.

## Scope
- Gateway: Stripe test
- Checkout: block (page 8)
- Account: guest — **no account may exist before or after**
- Plugins: free-only

## Preconditions
- SLT-SETUP-01/02, SLT-PROD-01 (`SLT Daily Core`) and SLT-PROD-10 (`SLT Box Item A`, plain simple $4.00) complete.
- **Documentation correction to carry into evidence:** SLT-SETUP-03's Objective claims `woocommerce_enable_guest_checkout = no`. That is FALSE — verified `yes` on 2026-08-02, alongside `woocommerce_enable_signup_and_login_from_checkout = yes`. Re-verify and file the correction.
- No user with email `slt-anon-d5@example.test` may exist (`wp user list … | grep slt-anon` returns nothing).
- `SLT Box Item A` is a box CHILD but an ordinary simple product; buying it standalone does not alter `_arraysubs_box_config`. Do NOT buy `SLT Box Daily` here.

## Test data
| Item | Value |
|---|---|
| Guest product | SLT Box Item A, `/slt-box-item-a`, $4.00, non-subscription |
| Control product | SLT Daily Core, `/slt-daily-core`, $10.00, day/1 subscription |
| Guest email | `slt-anon-d5@example.test` (must never become a user) |
| Billing | SLT Anon / 1 SLT Way / Dhaka / Bangladesh / 1207 / +8801700000000 |
| Card | `4242 4242 4242 4242` |
| Session | `--session anon-SLT-CHK-10` (unique; no cart collision) |

## Steps
1. `wp option get woocommerce_enable_guest_checkout --allow-root`; `wp user list --format=csv --fields=ID,user_email --allow-root | grep slt-anon` (expect empty); `wp post list --post_type=arraysubs_data --post_status=any --format=count --allow-root` → `SUBCOUNT_BEFORE`.
2. `mailpit-agent latest-id` → `MB10`.
3. `agent-browser --session anon-SLT-CHK-10 open "https://mirror-help.arrayhash.com/my-account"` → `snapshot -i`. The login form must render, confirming the session is anonymous. Do NOT log in.
4. Confirm the cart is empty at `/slt-classic-cart`.
5. After 12:00 site time: open `/slt-box-item-a` → `snapshot -i` → add to cart.
6. Open `https://mirror-help.arrayhash.com/checkout` → `snapshot -i`. **Enumerate every field in the contact/account area.** Screenshot.
7. Fill Email `slt-anon-d5@example.test` and the billing block. Re-snapshot; confirm no `Create account password` field and no locked create-account checkbox.
8. Select **Stripe**, fill the card in the UPE frame, **Place order**. Record the order id. Screenshot.
9. `mailpit-agent wait-new MB10 180 "order"`.
10. `wp wc order get <ORDER_ID> --user=admin --allow-root` → `customer_id` must be `0`. Re-run the `grep slt-anon` check (still empty).
11. Re-run the `arraysubs_data` count — must equal `SUBCOUNT_BEFORE`; confirm the order has no `_subscription_id` meta.
12. CONTROL LEG, same anonymous session: open `/slt-daily-core` → add to cart → open `/checkout` → `snapshot -i`. Enumerate the account fields again. Screenshot.
13. Do NOT pay. Empty the cart, then `agent-browser --session anon-SLT-CHK-10 close`.
14. Re-run the `grep slt-anon` check a final time.

## Expected results
1. `woocommerce_enable_guest_checkout` is `yes`; SLT-SETUP-03's statement is wrong and is corrected in `issues/`.
2. Steps 6-7: guest purchase is offered; no `Create account password` field; account creation is not mandatory.
3. Order placed successfully, total exactly `$4.00`, no tax line, status `processing` or `completed`.
4. `order.customer_id = 0` — a true guest order, not an auto-created account.
5. No user matching `slt-anon` exists at step 10 or step 14.
6. `arraysubs_data` count unchanged; the order carries no subscription meta.
7. Step 12 CONTROL: with a subscription in the cart the same anonymous session now forces account creation — proving the rule is cart-scoped, not site-wide.
8. No ArraySubs cart error on the non-subscription cart (those rules engage only when `subscription_count > 0`).

## Emails expected
| # | Email | Trigger point | Recipient | Subject contains | Verify with |
|---|---|---|---|---|---|
| 1 | WooCommerce customer order email | order placed | slt-anon-d5@example.test | `order` | `mailpit-agent wait-new MB10 180 "order"` |
| 2 | WooCommerce admin new-order email | same moment | site admin | `New order` | `mailpit-agent list 20` |
| 3 | NONE EXPECTED — `new_subscription` / `admin_new_subscription` | non-sub cart | — | — | negative sweep of `list 30` |
| 4 | NONE EXPECTED — WordPress new-account / password mail | guest checkout | — | — | no `Your account`/`password` mail after `MB10` |

## Evidence to capture
- `SLT-CHK-10-01-anon-login.png`, `-02-no-account-field.png`, `-03-thankyou-guest.png`, `-04-control-forces-account.png`.
- Order id, `customer_id`, both user greps, both `arraysubs_data` counts, Mailpit ids, console/network errors.

## Pass criteria
- [ ] Guest checkout of a non-subscription cart completes at $4.00
- [ ] `customer_id = 0` and no `slt-anon` user is ever created
- [ ] No account-password field on the non-subscription checkout
- [ ] No subscription created and no subscription mail sent
- [ ] Control leg with SLT Daily Core forces the account path in the same session
- [ ] Only mails 1 and 2 arrive

## Isolation / teardown
- Cart emptied, session closed, nothing paid on the control leg. No setting, product, user or coupon modified.
- One guest order remains, owned by no user — record its id so SLT-SETUP-99B trashes it.
- File `issues/SLT-CHK-10-guest-checkout-doc-mismatch.md` against SLT-SETUP-03's claim.

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
