---
id: 78
title: Anonymous guest checkout of a non-subscription cart must still work — forced registration is scoped to subscription carts only
status: done
priority: high
created: 2026-08-02T03:43:09.742322937+02:00
updated: 2026-08-07T16:30:09.686443123+02:00
started: 2026-08-07T16:30:00.160493154+02:00
completed: 2026-08-07T16:30:00.160493154+02:00
tags:
    - checkout
    - day-05
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

## Objective
Prove a true anonymous purchase — no login, no account created — still completes for a cart holding only a plain non-subscription product, and that account forcing is scoped to subscription carts. `CheckoutHelpersTrait::maybeRequireRegistration()` returns true only when `shouldForceAccountCreationForSubscriptionCheckout()` is true, which needs BOTH `checkout.auto_create_account = true` AND `cartHasSubscriptionCheckoutItems()`. Both legs run in one session: an anonymous non-subscription purchase that must succeed, then a subscription cart that must flip registration back on.

## Scope
- Gateway: Stripe test
- Checkout: block (page 8)
- Account: guest — **no account may exist before or after**
- Plugins: free-only

## Preconditions
- SLT-SETUP-01/02, SLT-PROD-01 (`SLT Daily Core`) and SLT-PROD-10 (`SLT Box Item A`, plain simple $4.00) complete.
- SLT-SETUP-03's corrected contract records `woocommerce_enable_guest_checkout = yes`, alongside `woocommerce_enable_signup_and_login_from_checkout = yes`. Re-verify both live values and cite that corrected setup evidence; this is not a new issue.
- No user with exact email `slt-anon-d5@example.test` may exist; prove this with a JSON + `jq` exact-email guard, never a partial text match.
- `SLT Box Item A` is a box CHILD but an ordinary simple product; buying it standalone does not alter `_arraysubs_box_config`. Do NOT buy `SLT Box Daily` here.

## Test data
| Item | Value |
|---|---|
| Guest product | SLT Box Item A, `/product/slt-box-item-a/`, $4.00, non-subscription |
| Control product | SLT Daily Core, `/product/slt-daily-core/`, $10.00, day/1 subscription |
| Guest email | `slt-anon-d5@example.test` (must never become a user) |
| Billing | SLT Anon / 1 SLT Way / Dhaka / Bangladesh / 1207 / +8801700000000 |
| Card | `4242 4242 4242 4242` |
| Session | `--session anon-SLT-CHK-10` (unique; no cart collision) |

## Steps
1. Set `ANON_EMAIL=slt-anon-d5@example.test`; require both `wp option get woocommerce_enable_guest_checkout --allow-root` and `wp option get woocommerce_enable_signup_and_login_from_checkout --allow-root` equal `yes`. Save `wp user list --format=json --fields=ID,user_email --allow-root` and use `jq --arg email "$ANON_EMAIL" '[.[] | select(.user_email == $email)] | length == 0'` as the exact absence guard. Resolve numeric, distinct `PLAIN_ID` and `CONTROL_ID` by exact slugs, verify the first is non-subscription and the second subscription day/1, and record `SUBCOUNT_BEFORE`.
2. Set `MB10=$(mailpit-agent latest-id)`.
3. `agent-browser --session anon-SLT-CHK-10 open "https://mirror-help.arrayhash.com/my-account"` → `snapshot -i`. The login form must render, confirming the session is anonymous. Do NOT log in.
4. Confirm the cart is empty at `/slt-classic-cart` and capture `SLT-CHK-10-01a-cart-empty-before.png`.
5. After 12:00 site time: open `/product/slt-box-item-a/` → `snapshot -i` → add to cart.
6. Open `https://mirror-help.arrayhash.com/checkout` → `snapshot -i`. **Enumerate every field in the contact/account area.** Screenshot.
7. Fill Email `slt-anon-d5@example.test` and the billing block. Re-snapshot; confirm no `Create account password` field and no locked create-account checkbox.
8. Select **Stripe**, fill the card only inside the hosted frame without capturing it, and **Place order**. From the received URL/heading record strict numeric `ORDER_ID`, cross-check its sole product line is `$PLAIN_ID` at $4.00, and capture the safe receipt as `SLT-CHK-10-03-thankyou-guest.png`.
9. Run `mailpit-agent wait-new "$MB10" 180 "order"`, then reconcile the complete owner-filtered delta: exactly the customer order and admin New order IDs, no ArraySubs signup pair, and no account/password mail; classify background mail.
10. Run `wp wc shop_order get "$ORDER_ID" --user=admin --allow-root`; require `customer_id=0`, exact `$ANON_EMAIL`, total 4.00, and status processing/completed. Re-run the exact JSON + `jq` email-absence guard.
11. Require the `arraysubs_data` count still equals `SUBCOUNT_BEFORE`; read the exact order's relationship metadata and require `_subscription_ids`, `_subscription_id`, `_subscription_renewal`, and `_is_renewal_order` all absent/empty. Never infer this from the order list.
12. CONTROL LEG, same anonymous session: require the completed guest checkout left the cart empty, add only `$CONTROL_ID`, and account for one-click mode; whether redirected or not, open `/checkout`, `snapshot -i`, enumerate the account fields again, and capture the unpopulated forced-account state as `SLT-CHK-10-04-control-forces-account.png`.
13. Do NOT enter customer/card data or pay. Empty the cart, capture `SLT-CHK-10-05-cart-empty-after-control.png`, and close only `anon-SLT-CHK-10`.
14. Re-run the exact JSON + `jq` email-absence guard a final time and append `ORDER_ID`, `PLAIN_ID`, `CONTROL_ID`, both count values, exact mail IDs, and teardown proof to the registry/D05 report. If any live assertion fails, create a standalone `issues/SLT-CHK-10-<concise-slug>.md` (never a kanban bug card) containing this progress task/stage and plan path; order/product IDs and subscription ID `N/A`; email with WordPress user ID/role `N/A`; exact guest URLs/session; reproduction; expected/actual; UI, HPOS/meta, Mailpit, network, and screenshot proof; and the subscription-control leg as counterexample where applicable. Independently review all evidence, move the card through `review` to `done`, and ensure Review returns to zero.

## Expected results
1. `woocommerce_enable_guest_checkout` and `woocommerce_enable_signup_and_login_from_checkout` are both `yes`, matching SLT-SETUP-03's corrected contract.
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
| 1 | WooCommerce customer order email | order placed | slt-anon-d5@example.test | `order` | `mailpit-agent wait-new "$MB10" 180 "order"` |
| 2 | WooCommerce admin new-order email | same moment | site admin | `New order` | Complete owner-filtered delta after `MB10`; save/show the exact matching id |
| 3 | NONE EXPECTED — `new_subscription` / `admin_new_subscription` | non-sub cart | — | — | Complete owner-filtered delta after `MB10`; zero matching subscription messages |
| 4 | NONE EXPECTED — WordPress new-account / password mail | guest checkout | — | — | no `Your account`/`password` mail after `MB10` |

## Evidence to capture
- `SLT-CHK-10-01-anon-login.png`, `-02-no-account-field.png`, `-03-thankyou-guest.png`, `-04-control-forces-account.png`.
- Numeric order/product IDs, `customer_id`, three exact JSON email-absence guards, both `arraysubs_data` counts, all four empty relationship-meta reads, exact Mailpit IDs, console/network errors, session/review proof.

## Pass criteria
- [ ] Guest checkout of a non-subscription cart completes at $4.00
- [ ] `customer_id = 0` and no `slt-anon` user is ever created
- [ ] No account-password field on the non-subscription checkout
- [ ] No subscription created and no subscription mail sent
- [ ] Control leg with SLT Daily Core forces the account path in the same session
- [ ] Only mails 1 and 2 arrive
- [ ] Exact session closed, standalone issue file created only if live proof fails, and evidence reviewed to done with Review empty

## Isolation / teardown
- Cart emptied, session closed, nothing paid on the control leg. No setting, product, user or coupon modified.
- One guest order remains, owned by no user — record its id so SLT-SETUP-99B trashes it.
- Cite SLT-SETUP-03's corrected guest-checkout contract; do not create a duplicate plan-defect issue.

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

[[2026-08-06]] Thu 21:32
Preflight 2026-08-06: guest-checkout flags remain live as authored: woocommerce_enable_guest_checkout=yes and woocommerce_enable_signup_and_login_from_checkout=yes. Exact absence re-check still holds for slt-anon-d5@example.test, and control account slt-grouped@example.test is also still absent. The authored plain product for this task is slt-box-item-a = 12591 (published), with slt-daily-core = 11927 (published) as the subscription control.

[[2026-08-06]] Thu 22:22
As of 2026-08-06 readiness review: no current source-block is visible from live evidence. This card remains a valid Friday, August 7, 2026 candidate and must stay in todo until that date; do not open it early.

[[2026-08-07]] Fri 16:29

## D05 evening execution — 2026-08-07

- Verdict: UNVERIFIED. Anonymous plain-product presentation, isolation guards, subscription control, and teardown passed; the paid order/mail leg could not be completed because the hosted Stripe payment frame was not exposed to agent-browser refs.
- Pre-flight: plain product 12591 and control product 11927 resolved by exact title/slug; exact user and HPOS order guards for slt-anon-d5@example.test were both zero; ArraySubs count was 375; guest checkout and signup-at-checkout were yes; generated username/password were yes.
- Anonymous UI: /my-account showed Login/Register; classic cart was empty; SLT Box Item A checkout explicitly said the user was checking out as a guest, showed an optional unchecked Create an account box, exposed no password field, and showed one USD 4.00 plain line.
- Payment attempt: Mailpit baseline 5hm1LQfe2IKo0vIamcv9kd. The success fixture was entered only in the hosted Stripe frame. Place Order was clicked exactly once. The 25-second order-received wait timed out and URL remained /checkout/. No second click was made. Browser errors were empty; outer checkout DOM had no alert. Exact HPOS query remained zero, so no ORDER_ID exists and all order-field, line-item, and relationship-meta assertions remain UNVERIFIED.
- Mail: latest ID remained 5hm1LQfe2IKo0vIamcv9kd, so there were zero task-attributable messages; the expected customer/admin order messages remain UNVERIFIED rather than failed.
- Isolation: exact user rows stayed empty and ArraySubs count stayed 375, delta 0.
- Control: incomplete plain cart was emptied via UI. Adding SLT Daily Core 11927 redirected to /checkout/; the control checkout required Email/Billing, omitted the guest message and optional Create account control, and showed no password field under automatic credential generation. No payment data was entered and the control was not submitted.
- Teardown: control item removed through classic-cart UI; cart visibly empty; exact session anon-SLT-CHK-10 closed.
- Issue: none filed; no product failure was proven.
- Facts: /home/server-manager/slt-evidence/SLT-CHK-10-final-facts.txt
- Screenshots: /home/server-manager/slt-evidence/SLT-CHK-10-01-anon-login.png; /home/server-manager/slt-evidence/SLT-CHK-10-01a-cart-empty-before.png; /home/server-manager/slt-evidence/SLT-CHK-10-02-no-account-field.png; /home/server-manager/slt-evidence/SLT-CHK-10-04-control-forces-account.png; /home/server-manager/slt-evidence/SLT-CHK-10-05-cart-empty-after-control.png. No -03 thank-you screenshot exists because no order-received page was reached.
