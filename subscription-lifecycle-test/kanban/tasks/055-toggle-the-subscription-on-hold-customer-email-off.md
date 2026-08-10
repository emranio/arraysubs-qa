---
id: 55
title: Toggle the Subscription On Hold customer email OFF, prove silence, restore ON, prove delivery
status: done
priority: critical
created: 2026-08-02T03:43:07.78919049+02:00
updated: 2026-08-05T21:37:49.548332115+02:00
started: 2026-08-05T15:43:34.227715339+02:00
completed: 2026-08-05T15:43:34.227715339+02:00
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
- **THIS TASK ALSO CREATES** (deleted by SLT-SETUP-99B): user `slt-email` / `slt-email@example.test`, Customer, `SltQa!2026#Pass`, First name `SLT`, Last name `Email`, billing per SLT-SETUP-03 step 4; and a subscription on `SLT Lifetime One Time` (**H1**), the harness for SLT-EML-12/13. Do not cancel H1.
- Lifetime is deliberate (SLT-PROD-07 ER5): empty `_next_payment_date`, no scheduled action, so status juggling moves no renewal. `trigger()` honours `is_enabled()` (`BaseSubscriptionEmail.php:136`); on-hold mail fires at `EmailManager.php:370-372`, no dedupe meta, repeatable.

## Test data
| Item | Value |
|---|---|
| Product | SLT Lifetime One Time ($49.00, `lifetime`) |
| Card | 4242 4242 4242 4242, future expiry, CVC 123 |
| Section | `arraysubs_subscription_on_hold` |

## Steps
1. Record whether `woocommerce_arraysubs_subscription_on_hold_settings` exists and preserve its exact prior value in `/home/server-manager/slt-evidence/SLT-EML-11-prior.txt` (the expected baseline is absent); this presence flag governs exact restoration in step 11. Record `SUBCOUNT_BEFORE=<exact current SLT subscription count>`.
2. `USER_PRE=$(mailpit-agent latest-id)`; in `admin-SLT-EML-11` open `/wp-admin/user-new.php` → `snapshot -i`; create `slt-email`, **Send User Notification** UNTICKED, Role Customer; at `user-edit.php?user_id=<ID>` fill billing (SLT / Email / Dhaka / Bangladesh / 1207), Update User. Record the numeric user ID, capture `SLT-EML-11-01-user.png`, classify the one admin-only `New User Registration` message after `USER_PRE`, and prove no customer account/password mail arrived.
3. `MP0=$(mailpit-agent latest-id)`; in `cust-SLT-EML-11` log in as `slt-email` at `/my-account/`; assert both browser and persistent carts EMPTY.
4. Open `/checkout/?add-to-cart=<Lifetime ID>`; if the frozen one-click setting redirects, record it and continue on block checkout. Capture the $49.00 order summary before card entry as `SLT-EML-11-02-checkout.png`, fill the hosted card fields without capturing them, pay, record numeric `ORDER_H1`, and capture the safe receipt as `SLT-EML-11-02a-order-received.png`.
5. `mailpit-agent wait-new "$MP0" 180 "is active"`; inspect the complete owner-filtered MP0 delta and require the exact WC customer paid-order, WC admin New order, ArraySubs customer signup, and ArraySubs admin signup IDs. Resolve `H1` only with `wp post meta get "$ORDER_H1" _subscription_ids --format=json --allow-root` plus a strict one-element numeric `jq -e` guard; require reverse `_parent_order_id=$ORDER_H1`, exact user/product, and `SUBCOUNT_AFTER == SUBCOUNT_BEFORE + 1`. Append H1, order/user IDs and all four mail IDs to `slt-catalog-registry`.
6. `wp post meta list "$H1" --keys=_next_payment_date,_end_date --allow-root` must show both empty; in `admin-SLT-EML-11`, search Scheduled Actions by numeric `$H1`, require nothing pending, and capture `SLT-EML-11-03-h1-no-actions.png`.
7. Require no other SLT status-transition task in progress and no conflicting action in the next 20 minutes. Record the UTC OFF-bracket open time in the registry and `/home/server-manager/slt-evidence/SLT-EML-11-bracket.txt`. Set `OFF_SAVE_PRE=$(mailpit-agent latest-id)`. **OFF:** open `/wp-admin/admin.php?page=wc-settings&tab=email&section=arraysubs_subscription_on_hold`, untick **Enable this email notification**, Save, re-read the disabled option, capture `SLT-EML-11-04-onhold-off.png`, and require zero setting-save-attributable mail in the bounded `OFF_SAVE_PRE` delta.
8. `MP1=$(mailpit-agent latest-id)`; at `admin.php?page=arraysubs-mainadmin#/subscriptions`, search exact ID H1, open **View Details**, set **Status** = `On hold`, and save. There is no `post.php` fallback for this subscription post type.
9. `mailpit-agent wait-new "$MP1" 180 "is on hold"` must **exit 124**; inspect the complete MP1 delta and require no on-hold message attributable to H1. Then set `REACT_OFF_PRE=$(mailpit-agent latest-id)`, change H1 to `Active`, and require the normal `has been reactivated` customer mail; save/show the exact id. The reactivation message is expected and is independent of the on-hold email's disabled state.
10. Set `ON_SAVE_PRE=$(mailpit-agent latest-id)`. **ON.** Same URL: re-tick **Enable**, keep Subject/Heading/Additional content blank, Save, re-read enabled state, capture `SLT-EML-11-05-onhold-on.png`, and require zero setting-save-attributable mail in the bounded `ON_SAVE_PRE` delta.
11. `MP2=$(mailpit-agent latest-id)`; set H1 `On hold`; `mailpit-agent wait-new "$MP2" 180 "is on hold"` must succeed; save the exact matched ID, inspect the complete MP2 delta, and `mailpit-agent html <matched-id>`. In exact session `mail-SLT-EML-11`, open that message in the local Mailpit UI and capture `SLT-EML-11-06-mailpit-html.png`, then close only that session. Set `REACT_ON_PRE=$(mailpit-agent latest-id)`, change H1 to `Active`, require/save the exact `has been reactivated` mail, and prove the final status active. Restore the option to its exact step-1 state: delete it if it was absent, otherwise restore the preserved value; prove an exact presence/value comparison, record the UTC bracket close (must be within 20 minutes), and inspect the complete setting-save deltas for attributable mail. Empty/prove both task carts, close only `admin-SLT-EML-11`, `cust-SLT-EML-11`, and `mail-SLT-EML-11`, independently review the evidence, move the card through `review` to `done`, and ensure Review returns to zero.

**Restore-first failure rule:** after step 7 disables the email, any browser, transition, mail, or evidence failure jumps immediately to step 10 and the exact step-1 option restoration in step 11 before diagnosis. Record a fully evidenced product failure only in a standalone `issues/*.md` file; never create a kanban bug card.

## Expected results
1. `slt-email` exists, role `customer`, billing set; creation emitted exactly one admin-only `New User Registration` notice and no customer account/password mail. H1 is `arraysubs-active`, `_recurring_amount` `49.00`, `_next_payment_date` EMPTY, no pending action.
2. OFF: Active→On hold yields zero H1 on-hold messages (exit 124). The subsequent On hold→Active transition still sends exactly one customer `subscription_reactivated` message; that separate email gate is not under test here.
3. ON: Active→On hold yields exactly ONE message, subject `[mirror-help.arrayhash.com] Your subscription #<H1> is on hold`, To `slt-email@example.test`, body naming `SLT Lifetime One Time`; the cleanup On hold→Active again sends exactly one reactivation message.
4. The ON proof runs with `enabled => yes` and blank subject/heading; final storage presence/value exactly matches the step-1 baseline (normally the temporary row is deleted).

## Emails expected
| # | Email | Trigger point | Recipient | Subject contains | Verify with |
|---|---|---|---|---|---|
| 0 | WP New User Registration | Step 2 admin user creation | admin | `New User Registration` | exactly one after `USER_PRE`; no customer account/password mail |
| 1 | new_subscription + admin_new_subscription + WC paid-order + WC New order | Step 4 checkout | slt-email@ / admin | `is active` / `New subscription #` / exact order | complete owner-filtered delta after `MP0`; require four exact IDs |
| 2 | NONE EXPECTED | Step 8, OFF | — | — | `mailpit-agent wait-new "$MP1" 180 "is on hold"` exits **124** |
| 3 | subscription_reactivated | Step 9 cleanup while on-hold email is OFF | slt-email@example.test | `has been reactivated` | exact complete delta after `REACT_OFF_PRE` |
| 4 | subscription_on_hold | Step 11, restored ON | slt-email@example.test | `is on hold` | exact complete delta after MP2 |
| 5 | subscription_reactivated | Step 11 cleanup | slt-email@example.test | `has been reactivated` | exact complete delta after `REACT_ON_PRE` |

## Evidence to capture
- Screenshots `SLT-EML-11-01-user.png`, `-02-checkout.png`, `-02a-order-received.png`, `-03-h1-no-actions.png`, `-04-onhold-off.png`, `-05-onhold-on.png`, `-06-mailpit-html.png`.
- IDs (user, exact receipt order, **H1**, count delta/bidirectional linkage, `MP0/MP1/MP2`, `OFF_SAVE_PRE`/`ON_SAVE_PRE`, `REACT_OFF_PRE`, `REACT_ON_PRE`, every delivered ID); UTC bracket open/close; exact before/after option presence/value proof; cart/session/review proof.

## Pass criteria
- [x] slt-email created with one admin registration notice and no customer account mail; H1 active with no pending action
- [x] OFF ⇒ on-hold transition sends nothing (exit 124); cleanup reactivation mail arrives exactly once
- [x] ON ⇒ exactly one on-hold mail with the default subject/H1 values; cleanup reactivation mail arrives exactly once
- [x] Exact prior option presence/value restored; H1 left `arraysubs-active`; both carts empty
- [x] Receipt links bidirectionally to the sole new subscription; complete four-message checkout set captured
- [x] Bracket closes within 20 minutes; exact sessions close and card moves through review to done

## Isolation / teardown
- Global setting touched: the on-hold `enabled` flag, OFF only inside the recorded ≤20-minute bracket. Do **not** run this bracket after 2026-08-05 — SLT Retry Daily reaches on-hold 2026-08-06 and must send its own mail.
- Handed on: `slt-email` and **H1** for SLT-EML-12/13. Restores: the option's exact prior presence/value, not merely an equivalent enabled UI state.


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

[[2026-08-05]] Wed 15:14
Evening phase resumed at 2026-08-05 19:15 site; prior option snapshot exists, continuing with H1 fixture creation before any settings mutation.

[[2026-08-05]] Wed 15:40

Execution result:

- Step 1 baseline: `woocommerce_arraysubs_subscription_on_hold_settings` absent; `SUBCOUNT_BEFORE=374`.
- Step 2 user fixture: created `slt-email` / `slt-email@example.test`, user `366`; one admin-only registration message `4zTBZIbUwNmtTyHbNEJQyg`; no customer account/password email.
- Steps 3-6 checkout fixture: browser and persistent carts empty before purchase; lifetime product `11938` purchased through block checkout; `ORDER_H1=12776`; `H1=12786`; receipt proved `Active`; `_subscription_ids=[12786]`; `_parent_order_id=12776`; `_product_id=11938`; `_customer_id=366`; `_next_payment_date` empty; subscription count `375`; no scheduled actions for `12786`.
- Checkout delta after `MP0`: customer order `5XOaTAYBFJyTVhRsZTLKg4`, admin order `6JaHFgPJg2mB9B6CHoJkU5`, customer active-subscription `7DpzyRLLUSM8MeFXe29920`, admin new-subscription `6D64eL8nnqYiCdIJnyhl17`.
- OFF bracket: opened `2026-08-05T13:28:16Z`; `OFF_SAVE_PRE=6D64eL8nnqYiCdIJnyhl17`; saved disabled option as `{\"enabled\":\"no\",\"subject\":\"\",\"heading\":\"\",\"additional_content\":\"Your subscription has been put on hold. This may be due to a payment issue. Please update your payment method to reactivate your subscription.\",\"email_type\":\"html\"}`; no save-attributable mail.
- OFF proof: changing `12786` Active → On Hold produced `mailpit-agent wait-new ... 'is on hold'` timeout exit `124`; no H1 on-hold mail. Cleanup Active reactivation produced `5IjmIMJmgHp1YJ1JXGzwAR`.
- ON proof: `ON_SAVE_PRE=5IjmIMJmgHp1YJ1JXGzwAR`; saved enabled option as `{\"enabled\":\"yes\",\"subject\":\"\",\"heading\":\"\",\"additional_content\":\"Your subscription has been put on hold. This may be due to a payment issue. Please update your payment method to reactivate your subscription.\",\"email_type\":\"html\"}`; no save-attributable mail. `MP2=5IjmIMJmgHp1YJ1JXGzwAR`; changing `12786` Active → On Hold produced exactly one customer mail `7HKh3ExovX2AdMoKSn5Ivh`, subject `[mirror-help.arrayhash.com] Your subscription #12786 is on hold`, rendered HTML captured at `SLT-EML-11-06-mailpit-html.png`.
- Final cleanup: `REACT_ON_PRE=7HKh3ExovX2AdMoKSn5Ivh`; reactivation mail `6soupUAJRLgbGnsIycTWcM`; final subscription status `arraysubs-active`; exact option baseline restored to absent; persistent cart empty and browser cart showed `Your cart is currently empty!`; bracket closed `2026-08-05T13:40:04Z` (duration `00:11:48`).
- Registry updated on private page `SLT Catalog Registry` (`11847`) with H1, mail IDs, negative proof, and bracket timestamps.

[[2026-08-05]] Wed 15:43
Evidence complete: OFF gate proved silence, ON gate proved exactly one on-hold mail, H1 restored active, option baseline restored absent, carts empty, sessions closed.
