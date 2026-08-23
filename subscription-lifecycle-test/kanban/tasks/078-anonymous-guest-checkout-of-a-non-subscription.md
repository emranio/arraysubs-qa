---
id: 78
title: Anonymous guest checkout of a non-subscription cart must still work — forced registration is scoped to subscription carts only
status: todo
priority: high
created: 2026-08-22T19:10:00+02:00
updated: 2026-08-22T19:10:00+02:00
tags:
    - cycle-2
    - granular
    - checkout
    - day-05
due: "2026-08-28"
estimate: 1h
depends_on:
    - 10
    - 11
    - 5
    - 59
class: standard
---

> **SLT-CHK-10** · group `checkout` · scheduled **D05** (2026-08-28)

> Read `README.md` (environment + isolation contract), `calendar.md` (this day's exact
> ordering — it is binding, not advisory) and `plan-audit.md` before starting.

## Objective
Prove a true anonymous purchase — no login, no account created — still completes for a cart holding only a plain non-subscription product, and that account forcing is scoped to subscription carts. `CheckoutHelpersTrait::maybeRequireRegistration()` returns true only when `shouldForceAccountCreationForSubscriptionCheckout()` is true, which needs BOTH `checkout.auto_create_account = true` AND `cartHasSubscriptionCheckoutItems()`. Both legs run in one session: an anonymous non-subscription purchase that must succeed, then a subscription cart that must flip registration back on.

## Scope
- Gateway: Stripe test
- Checkout: block (page 8)
- Account: guest — **no account may exist before or after**
- Plugins: free-only

## Preconditions
- SLT-SETUP-01/02, SLT-PROD-01 (`SLT2 Daily Core`) and SLT-PROD-10 (`SLT2 Box Item A`, plain simple $4.00) complete.
- SLT-SETUP-03's corrected contract records `woocommerce_enable_guest_checkout = yes`, alongside `woocommerce_enable_signup_and_login_from_checkout = yes`. Re-verify both live values and cite that corrected setup evidence; this is not a new issue.
- No user with exact email `slt2-anon-d5@example.test` may exist; prove this with a JSON + `jq` exact-email guard, never a partial text match.
- `SLT2 Box Item A` is a box CHILD but an ordinary simple product; buying it dedicated does not alter `_arraysubs_box_config`. Do NOT buy `SLT2 Box Daily` here.

## Test data
| Item | Value |
|---|---|
| Guest product | SLT2 Box Item A, `/product/slt2-box-item-a/`, $4.00, non-subscription |
| Control product | SLT2 Daily Core, `/product/slt2-daily-core/`, $10.00, day/1 subscription |
| Guest email | `slt2-anon-d5@example.test` (must never become a user) |
| Billing | SLT2 Anon / 1 SLT2 Way / Dhaka / Bangladesh / 1207 / +8801700000000 |
| Card | `4242 4242 4242 4242` |
| Session | `--session anon-SLT-CHK-10` (unique; no cart collision) |

## Steps
1. Set `ANON_EMAIL=slt2-anon-d5@example.test`; require both `wp option get woocommerce_enable_guest_checkout --allow-root` and `wp option get woocommerce_enable_signup_and_login_from_checkout --allow-root` equal `yes`. Save `wp user list --format=json --fields=ID,user_email --allow-root` and use `jq --arg email "$ANON_EMAIL" '[.[] | select(.user_email == $email)] | length == 0'` as the exact absence guard. Resolve numeric, distinct `PLAIN_ID` and `CONTROL_ID` by exact slugs, verify the first is non-subscription and the second subscription day/1, and record `SUBCOUNT_BEFORE`.
2. Set `MB10=$(mailpit-agent latest-id)`.
3. `agent-browser --session anon-SLT-CHK-10 open "https://mirror-help.arrayhash.com/my-account"` → `snapshot -i`. The login form must render, confirming the session is anonymous. Do NOT log in.
4. Confirm the cart is empty at `/slt2-classic-cart` and capture `SLT-CHK-10-01a-cart-empty-before.png`.
5. After 12:00 site time: open `/product/slt2-box-item-a/` → `snapshot -i` → add to cart.
6. Open `https://mirror-help.arrayhash.com/checkout` → `snapshot -i`. **Enumerate every field in the contact/account area.** Screenshot.
7. Fill Email `slt2-anon-d5@example.test` and the billing block. Re-snapshot; confirm no `Create account password` field and no locked create-account checkbox.
8. Select **Stripe**, fill the card only inside the hosted frame without capturing it, and **Place order**. From the received URL/heading record strict numeric `ORDER_ID`, cross-check its sole product line is `$PLAIN_ID` at $4.00, and capture the safe receipt as `SLT-CHK-10-03-thankyou-guest.png`.
9. Run `mailpit-agent wait-new "$MB10" 180 "order"`, then reconcile the complete owner-filtered delta: exactly the customer order and admin New order IDs, no ArraySubs signup pair, and no account/password mail; classify background mail.
10. Run `wp wc shop_order get "$ORDER_ID" --user=admin --allow-root`; require `customer_id=0`, exact `$ANON_EMAIL`, total 4.00, and status processing/completed. Re-run the exact JSON + `jq` email-absence guard.
11. Require the `arraysubs_data` count still equals `SUBCOUNT_BEFORE`; read the exact order's relationship metadata and require `_subscription_ids`, `_subscription_id`, `_subscription_renewal`, and `_is_renewal_order` all absent/empty. Never infer this from the order list.
12. CONTROL LEG, same anonymous session: require the completed guest checkout left the cart empty, add only `$CONTROL_ID`, and account for one-click mode; whether redirected or not, open `/checkout`, `snapshot -i`, enumerate the account fields again, and capture the unpopulated forced-account state as `SLT-CHK-10-04-control-forces-account.png`.
13. Do NOT enter customer/card data or pay. Empty the cart, capture `SLT-CHK-10-05-cart-empty-after-control.png`, and close only `anon-SLT-CHK-10`.
14. Re-run the exact JSON + `jq` email-absence guard a final time and append `ORDER_ID`, `PLAIN_ID`, `CONTROL_ID`, both count values, exact mail IDs, and teardown proof to the registry/D05 report. If any live assertion fails, create a dedicated `qa/issues/` kanban card named `SLT-CHK-10-<concise-slug>` (create the required QA issue card) containing this progress task/stage and plan path; order/product IDs and subscription ID `N/A`; email with WordPress user ID/role `N/A`; exact guest URLs/session; reproduction; expected/actual; UI, HPOS/meta, Mailpit, network, and screenshot proof; and the subscription-control leg as counterexample where applicable. Independently review all evidence, move the card through `review` to `done`, and ensure Review returns to zero.

## Expected results
1. `woocommerce_enable_guest_checkout` and `woocommerce_enable_signup_and_login_from_checkout` are both `yes`, matching SLT-SETUP-03's corrected contract.
2. Steps 6-7: guest purchase is offered; no `Create account password` field; account creation is not mandatory.
3. Order placed successfully, total exactly `$4.00`, no tax line, status `processing` or `completed`.
4. `order.customer_id = 0` — a true guest order, not an auto-created account.
5. No user matching `slt2-anon` exists at step 10 or step 14.
6. `arraysubs_data` count unchanged; the order carries no subscription meta.
7. Step 12 CONTROL: with a subscription in the cart the same anonymous session now forces account creation — proving the rule is cart-scoped, not site-wide.
8. No ArraySubs cart error on the non-subscription cart (those rules engage only when `subscription_count > 0`).

## Emails expected
| # | Email | Trigger point | Recipient | Subject contains | Verify with |
|---|---|---|---|---|---|
| 1 | WooCommerce customer order email | order placed | slt2-anon-d5@example.test | `order` | `mailpit-agent wait-new "$MB10" 180 "order"` |
| 2 | WooCommerce admin new-order email | same moment | site admin | `New order` | Complete owner-filtered delta after `MB10`; save/show the exact matching id |
| 3 | NONE EXPECTED — `new_subscription` / `admin_new_subscription` | non-sub cart | — | — | Complete owner-filtered delta after `MB10`; zero matching subscription messages |
| 4 | NONE EXPECTED — WordPress new-account / password mail | guest checkout | — | — | no `Your account`/`password` mail after `MB10` |

## Evidence to capture
- `SLT-CHK-10-01-anon-login.png`, `-02-no-account-field.png`, `-03-thankyou-guest.png`, `-04-control-forces-account.png`.
- Numeric order/product IDs, `customer_id`, three exact JSON email-absence guards, both `arraysubs_data` counts, all four empty relationship-meta reads, exact Mailpit IDs, console/network errors, session/review proof.

## Pass criteria
- [ ] Guest checkout of a non-subscription cart completes at $4.00
- [ ] `customer_id = 0` and no `slt2-anon` user is ever created
- [ ] No account-password field on the non-subscription checkout
- [ ] No subscription created and no subscription mail sent
- [ ] Control leg with SLT2 Daily Core forces the account path in the same session
- [ ] Only mails 1 and 2 arrive
- [ ] Exact session closed, QA issue card created only if live proof fails, and evidence reviewed to done with Review empty

## Isolation / teardown
- Cart emptied, session closed, nothing paid on the control leg. No setting, product, user or coupon modified.
- One guest order remains, owned by no user — record its id so SLT-SETUP-99B trashes it.
- Cite SLT-SETUP-03's corrected guest-checkout contract; do not create a duplicate plan-defect issue.

---

### Fresh-cycle validation contract

- Re-derive every ID, count, option value, gateway capability, scheduler timestamp, and email baseline on this run; no prior-cycle result is evidence.
- Create and mutate only registered `SLT2 ` / `slt2-*` fixtures. Legacy `SLT` and all non-SLT2 data are read-only controls.
- Automatic-gateway scope is Stripe and Paddle only. Stripe is the primary path; run Paddle parity wherever Paddle supports the behavior. Do not test or configure PayPal or Mollie.
- ArraySubs core must own its Stripe/Paddle integration, renewal, retry, webhook, REST, refund, and customer-payment services with Pro inactive; vendor host classes retain their expected ownership.
- Browser-required assertions use Vercel `agent-browser` with isolated task/role sessions and current snapshot refs. WP-CLI always includes `--allow-root`.
- Update the lifecycle card, the matching `qa/progress/` card, and `qa/issues/` for every new regression. Evidence belongs to this fresh cycle only.
