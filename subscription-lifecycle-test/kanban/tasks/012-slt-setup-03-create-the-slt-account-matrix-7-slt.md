---
id: 12
title: SLT-SETUP-03 Create the SLT account matrix (7 slt-* users) and document the guest path
status: todo
priority: critical
created: 2026-08-02T03:43:04.008417808+02:00
updated: 2026-08-02T03:43:13.83608731+02:00
tags:
    - setup
    - day-00
    - has-conflicts
due: "2026-08-02"
estimate: 1h
depends_on:
    - 10
class: standard
---

> **SLT-SETUP-03** · group `foundation` · scheduled **D00** (2026-08-02)

> Read `README.md` (environment + isolation contract), `calendar.md` (this day's exact
> ordering — it is binding, not advisory) and `plan-audit.md` before starting.

### ⚠ Conflict resolutions that apply to this task

**`unrated` · impossible-timing** — with `SLT-SETUP-01`, `SLT-SETUP-02`, `SLT-SETUP-04`, `SLT-SETUP-05`, `SLT-PROD-01`, `SLT-PROD-02`

- *Problem:* 25 of the 26 authored tasks are stamped day 0. Sixteen of them are multi-step browser-driven product builds (each: open post-new, set 6-15 fields across 3 tabs, publish, reload, verify, meta-dump, guest front-end check, registry append), two are long audit tasks with 15-20 probe steps each (SLT-SYN-01, SLT-SYN-02), one is a live Stripe purchase with a settings flip and restore (SLT-SYN-04), and on top of that D0 is also where the plan's isolation notes demand the D0 purchases of SLT-PROD-01/02/03/06/07/10/12/13/14/15/16. There is a hard serial chain inside the day as well: SETUP-01 -> SETUP-02 -> PROD-16 -> SETUP-05 -> SYN-04, and SETUP-01 -> SETUP-02 -> PROD-14 -> SYN-03 -> SYN-04. This is not executable in one day, and it wastes D4-D9 which sit empty.
- *Required fix:* Adopt the rebalanced D0-D10 calendar in schedule_notes. Only products with a genuinely date-bound purchase stay on D0 (SLT-PROD-01, 06, 07, 13 plus the three foundation tasks); everything else is spread D1-D5 with its purchase deadline recomputed so it still fits the window. Peak day drops from 25 tasks (~30h) to 7 tasks (~7h) and the median day is ~4.5h, inside the 1.6x band.

**`unrated` · same-account-collision** — with `SLT-PROD-12`, `SLT-PROD-13`, `SLT-PROD-15`, `SLT-SYN-03`, `SLT-SYN-04`

- *Problem:* multiple_subscriptions.auto_migrate_on_checkout = true is a baseline the plan never changes, yet three tasks require the SAME account (slt-flex) to buy the SAME product three separate times: SLT-PROD-12 demands three purchases of SLT Flex Month Segments (segments 1/2/3), SLT-PROD-13 three purchases of SLT Flex Week Segments, and SLT-PROD-15 three purchases of three variations of one variable parent. With auto-migrate on, the second and third checkouts are liable to MIGRATE the customer's existing subscription for that product rather than create an independent one - which silently destroys the segment-1 subscription that the earlier purchase created, and makes the whole segment matrix unobservable. On top of that, slt-flex is additionally loaded with SLT Sync Global Daily (SLT-SYN-04) and SLT Sync Excl Probe (SLT-SYN-03) by explicit deviation, so one account would end up owning 9+ concurrent subscriptions and the my-account list becomes ambiguous for every later assertion.
- *Required fix:* Extend SLT-SETUP-03's matrix from 7 to 9 accounts: add A9 slt-flex2 / slt-flex2@example.test and A10 slt-flex3 / slt-flex3@example.test, same password and billing address. Bind: segment-1 purchases -> slt-flex, segment-2 purchases -> slt-flex2, segment-3 purchases -> slt-flex3, and the same 1/2/3 split for the SLT Flex Variable Daily variations. No account ever buys the same product twice. Before the first repeat purchase would have happened, run a one-line probe of auto_migrate behaviour and record it in the registry so the split is evidence-backed.

**`medium` · contradictory-precondition (factually wrong)** — with `SLT-CHK-03`, `SLT-CHK-10`

- *Problem:* SLT-CHK-03's objective and precondition assert 'a logged-out visitor cannot check out anonymously - woocommerce_enable_guest_checkout=no'. The README's verified environment baseline says the option is `yes`, and SLT-CHK-10 carries an explicit documentation correction ('That is FALSE - verified yes on 2026-08-02, alongside woocommerce_enable_signup_and_login_from_checkout=yes') and files an issue against SLT-SETUP-03 for the same claim. CHK-03 runs two days before CHK-10, so it will observe an offered guest path for a non-subscription cart, or reason about the wrong mechanism and file a false bug against the checkout registration force.
- *Required fix:* Rewrite CHK-03's objective and precondition to the correct mechanism: guest checkout IS enabled site-wide; registration is forced only for subscription carts, via woocommerce_checkout_registration_required (SubscriptionCheckout/Services/Hooks.php:103, CheckoutHelpersTrait.php:93-100) gated on checkout.auto_create_account=true AND cartHasSubscriptionCheckoutItems(). Keep the assertion 'no continue-as-guest option for THIS cart' and add step 1a: `wp option get woocommerce_enable_guest_checkout --allow-root` must print `yes`. Correct SLT-SETUP-03's objective in the catalog at the same time so CHK-10's issue is a confirmation rather than a discovery.

---
## Objective
Provision every customer identity the window needs, one purpose per account, so no test ever reuses another test's customer and no pre-existing site user is mutated. Also nail down what "guest checkout" actually means on this install: `woocommerce_enable_guest_checkout=no`, so an anonymous purchase is impossible — the guest path is "not logged in, account auto-created at checkout" (`checkout.auto_create_account=true`, `woocommerce_enable_signup_and_login_from_checkout=yes`).

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
| A8 | (none — created at checkout) | slt-guest-d0@example.test | The "guest -> new" path; the account is born at checkout, so it is NOT created here |

## Steps
1. `agent-browser --session admin open "https://mirror-help.arrayhash.com/wp-admin/user-new.php"` -> `agent-browser --session admin snapshot -i`.
2. For each of A1..A7 in the table: fill **Username**, **Email**, **First Name** = `SLT`, **Last Name** = the purpose word (e.g. `Core`), untick **Send User Notification**, set **Password** to `SltQa!2026#Pass` via the *Set New Password* button, set **Role** = `Customer`, click **Add New User**, then re-snapshot and re-open `user-new.php` for the next one.
3. Capture `mailpit-agent latest-id` BEFORE the first Add New User click — with *Send User Notification* unticked no mail may be sent, and this is the negative check.
4. For A1..A7 set a billing address so block checkout does not stall: `agent-browser --session admin open "https://mirror-help.arrayhash.com/wp-admin/user-edit.php?user_id=<ID>"`, scroll to **Customer billing address**, set First name `SLT`, Last name `<Purpose>`, Address line 1 `1 SLT Way`, City `Dhaka`, Country/Region `Bangladesh`, Postcode `1207`, Phone `+8801700000000`, Email = the account email. Update User.
5. Do NOT create A8. Record in the registry that `slt-guest-d0@example.test` is reserved and must not be pre-created — the checkout task that uses it proves auto-account-creation.
6. Reserve a second guest email `slt-guest-d5@example.test` in the registry for a later-window guest run.
7. Verify: `wp user list --format=csv --fields=ID,user_login,user_email,roles --allow-root | grep slt-`.
8. Append all seven user IDs to the `slt-catalog-registry` page.

## Expected results
1. Exactly 7 users exist with logins `slt-core`, `slt-trial`, `slt-switch`, `slt-flex`, `slt-fail`, `slt-paddle`, `slt-admincreated`, all with role `customer`.
2. Each has the billing address block populated (country BD, postcode 1207).
3. No user named `slt-guest-d0` or `slt-guest-d5` exists.
4. `cust1` (5), `customer1` (32), `sync-stripe` (319) are untouched — their `user_registered` and email are unchanged.
5. The registry page lists all 7 IDs plus the two reserved guest emails.

## Emails expected
| # | Email | Trigger point | Recipient | Subject contains | Verify with |
|---|---|---|---|---|---|
| 1 | NONE EXPECTED | Each **Add New User** | — | — | `mailpit-agent latest-id` after step 7 must equal the id captured in step 3; if it moved, open it with `mailpit-agent show latest` and record which account leaked a notification |

## Evidence to capture
- Screenshot `SLT-SETUP-03-01-users-list-slt.png` of `edit.php` filtered to the slt users.
- The `wp user list | grep slt-` output.
- Seven WP user IDs, recorded in the registry.

## Pass criteria
- [ ] 7 slt-* customers exist with correct emails and role
- [ ] Billing address populated on all 7
- [ ] No guest account pre-created; both guest emails reserved in the registry
- [ ] Zero mail sent (latest-id unchanged)
- [ ] Existing users 5 / 32 / 319 untouched

## Isolation / teardown
- State handoff: the account matrix. Later tasks MUST use the account whose purpose matches; crossing purposes (e.g. buying a flex product as `slt-core`) invalidates the isolation guarantee because subscription-per-customer state leaks between tests.
- Restores: nothing changed globally. SLT-SETUP-99 deletes all `slt-*` users (including any created at checkout) and reassigns their content to nobody.

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
