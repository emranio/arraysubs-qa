---
id: 12
title: SLT-SETUP-03 Create the SLT account matrix (9 slt-* users) and document the guest path
status: done
priority: critical
created: 2026-08-02T03:43:04.008417808+02:00
updated: 2026-08-02T13:48:48.457205796+02:00
started: 2026-08-02T13:48:35.47588564+02:00
completed: 2026-08-02T13:48:35.47588564+02:00
tags:
    - setup
    - day-00
due: "2026-08-02"
estimate: 1h
depends_on:
    - 10
class: standard
---

> **SLT-SETUP-03** · group `foundation` · scheduled **D00** (2026-08-02)

> Read `README.md` (environment + isolation contract), `calendar.md` (this day's exact
> ordering — it is binding, not advisory) and `plan-audit.md` before starting.

## Objective
Provision every customer identity the window needs, one purpose per account, so no test ever reuses another test's customer and no pre-existing site user is mutated. Also nail down what "guest checkout" actually means on this install: `woocommerce_enable_guest_checkout=yes` site-wide, while ArraySubs forces registration for subscription carts. The subscription guest path is therefore "not logged in, account auto-created at checkout" (`checkout.auto_create_account=true`, `woocommerce_enable_signup_and_login_from_checkout=yes`).

## Scope
- Gateway: N/A
- Checkout: N/A
- Account: new registered
- Plugins: both

## Preconditions
- SLT-SETUP-01 complete.
- Verified: no user matching `slt` exists on the site today.
- Existing users `cust1` (id 5), `customer1` (id 32), `sync-stripe` (id 319) are DOCUMENTED BUT OFF-LIMITS for mutation. Only SLT-CHK-style read-only checks may reference them; no SLT task may place an order, cancel a subscription, or edit profile data on them.

## Test data
| Item | Value |
|---|---|
| Product | N/A |
| Account | see matrix below |
| Coupon | N/A |
| Card | N/A |
| Amounts | N/A |

Account matrix (role `customer`, password for all: `SltQa!2026#Pass`):

| Key | Username | Email | Purpose |
|---|---|---|---|
| A1 | slt-core | slt-core@example.test | Pre-existing registered buyer for the daily workhorse and most Stripe happy paths |
| A2 | slt-trial | slt-trial@example.test | Trial + free-signup products only |
| A3 | slt-switch | slt-switch@example.test | Plan-switching ladder only (upgrade/downgrade/crossgrade) |
| A4 | slt-flex | slt-flex@example.test | Flexible-renewal-sync products only |
| A5 | slt-fail | slt-fail@example.test | Failing-card / retry / dunning only |
| A6 | slt-paddle | slt-paddle@example.test | Paddle sandbox only — never used with Stripe |
| A7 | slt-admincreated | slt-admincreated@example.test | Target of an admin-created subscription; never checks out |
| A9 | slt-flex2 | slt-flex2@example.test | Flexible-renewal-sync segment 2 purchases |
| A10 | slt-flex3 | slt-flex3@example.test | Flexible-renewal-sync segment 3 purchases |
| A8 | (none — created at checkout) | slt-guest-d0@example.test | The "guest -> new" path; the account is born at checkout, so it is NOT created here |

## Steps
1. `agent-browser --session admin-SLT-SETUP-03 open "https://mirror-help.arrayhash.com/wp-admin/user-new.php"` -> `agent-browser --session admin-SLT-SETUP-03 snapshot -i`.
2. For A1..A7 plus A9 and A10 in the table: fill **Username**, **Email**, **First Name** = `SLT`, **Last Name** = the purpose word (e.g. `Core`), untick **Send User Notification**, set **Password** to `SltQa!2026#Pass` via the *Set New Password* button, set **Role** = `Customer`, click **Add New User**, then re-snapshot and re-open `user-new.php` for the next one.
3. Capture `mailpit-agent latest-id` BEFORE the first Add New User click. With *Send User Notification* unticked, expect no customer account email but exactly one WordPress **New User Registration** notification to the site administrator per created account. Map those messages explicitly so later ArraySubs email reconciliation can exclude them.
4. For A1..A7 plus A9 and A10 set a billing address so block checkout does not stall: `agent-browser --session admin-SLT-SETUP-03 open "https://mirror-help.arrayhash.com/wp-admin/user-edit.php?user_id=<ID>"`, scroll to **Customer billing address**, set First name `SLT`, Last name `<Purpose>`, Address line 1 `1 SLT Way`, City `Dhaka`, Country/Region `Bangladesh`, Postcode `1207`, Phone `+8801700000000`, Email = the account email. Update User.
5. Do NOT create A8. Record in the registry that `slt-guest-d0@example.test` is reserved and must not be pre-created — the checkout task that uses it proves auto-account-creation.
6. Reserve a second guest email `slt-guest-d5@example.test` in the registry for a later-window guest run.
7. Verify: `wp user list --format=csv --fields=ID,user_login,user_email,roles --allow-root | grep slt-`.
8. Append all nine user IDs to the `slt-catalog-registry` page.

## Expected results
1. Exactly 9 users exist with logins `slt-core`, `slt-trial`, `slt-switch`, `slt-flex`, `slt-fail`, `slt-paddle`, `slt-admincreated`, `slt-flex2`, and `slt-flex3`, all with role `customer`.
2. Each has the billing address block populated (country BD, postcode 1207).
3. No user named `slt-guest-d0` or `slt-guest-d5` exists.
4. `cust1` (5), `customer1` (32), `sync-stripe` (319) are untouched — their `user_registered` and email are unchanged.
5. The registry page lists all 9 IDs plus the two reserved guest emails.

## Emails expected
| # | Email | Trigger point | Recipient | Subject contains | Verify with |
|---|---|---|---|---|---|
| 1 | WordPress New User Registration | Each **Add New User** | Site administrator | `New User Registration` | Exactly one admin message per created account |
| 2 | NONE EXPECTED — customer account email | Each **Add New User** with **Send User Notification** off | `slt-*@example.test` | — | Zero messages addressed to the nine new customers |

## Evidence to capture
- Screenshot `SLT-SETUP-03-01-users-list-slt.png` of `edit.php` filtered to the slt users.
- The `wp user list --allow-root | grep slt-` output.
- Nine WP user IDs, recorded in the registry.

## Pass criteria
- [ ] 9 slt-* customers exist with correct emails and role
- [ ] Billing address populated on all 9
- [ ] No guest account pre-created; both guest emails reserved in the registry
- [ ] Exactly 9 WordPress admin registration messages and zero customer account messages
- [ ] Existing users 5 / 32 / 319 untouched

## Isolation / teardown
- State handoff: the account matrix. Later tasks MUST use the account whose purpose matches; crossing purposes (e.g. buying a flex product as `slt-core`) invalidates the isolation guarantee because subscription-per-customer state leaks between tests.
- Restores: nothing changed globally. SLT-SETUP-99B deletes all allowlisted `slt-*` users (including any created at checkout) after their SLT-owned records are deleted.

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

[[2026-08-02]] Sun 13:48
### Execution result — 2026-08-02

**Verdict: EXECUTED / PASS AGAINST CORRECTED CONTRACT**

- Created and browser-verified the conflict-adjusted nine-account matrix: IDs 347 through 355, all role customer, exact emails, and complete BD billing profiles.
- Confirmed slt-guest-d0 and slt-guest-d5 remain uncreated and reserved on registry page 11847; documented auto-migrate=true and the three-account flex split.
- Rechecked protected users 5, 32, and 319 unchanged.
- The original zero-mail expectation was a plan defect: unchecking Send User Notification suppressed customer mail, while WordPress emitted one admin-only New User Registration message per created user. The task contract was corrected directly; the captured set is exactly nine admin messages and zero customer messages. See `issues/qa-plan-SLT-SETUP-03-admin-new-user-mail-despite-notification-checkbox-off.md`.
- A long-lived browser session logged seven copies of a user-profile.min.js serialize TypeError. A fresh isolated reproduction was clean across load, validation failure, successful create, edit load, and update, so this intermittent observation was recorded but not filed as a separate bug.
- Temporary reproduction user ID 356 owned no posts and was deleted; no probe account remains.
- Evidence: /home/server-manager/slt-evidence/SLT-SETUP-03-facts.txt, SLT-SETUP-03-user-matrix.tsv, SLT-SETUP-03-mailpit-after-users.json, SLT-SETUP-03-browser-errors.json, SLT-SETUP-03-01-users-list-slt.png, and SLT-SETUP-03-02-account-registry.png.
