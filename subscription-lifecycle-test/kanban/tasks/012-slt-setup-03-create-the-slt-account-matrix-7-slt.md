---
id: 12
title: SLT-SETUP-03 Create the SLT2 account matrix (9 slt2-* users) and document the guest path
status: blocked
priority: critical
created: 2026-08-22T19:10:00+02:00
updated: 2026-08-23T03:02:41.035707229+02:00
started: 2026-08-22T21:46:03.644775301+02:00
tags:
    - cycle-2
    - granular
    - setup
    - day-00
due: "2026-08-23"
estimate: 1h
depends_on:
    - 10
blocked: true
block_reason: 'Shared issue #2: out-of-phase D00 mutation and missing authoritative registry publication'
class: standard
---

> **SLT-SETUP-03** · group `foundation` · scheduled **D00** (2026-08-23)

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
- Existing users `cust1` (id 5), `customer1` (id 32), `sync-stripe` (id 319) are DOCUMENTED BUT OFF-LIMITS for mutation. Only SLT-CHK-style read-only checks may reference them; no SLT2 task may place an order, cancel a subscription, or edit profile data on them.

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
| A1 | slt2-core | slt2-core@example.test | Pre-existing registered buyer for the daily workhorse and most Stripe happy paths |
| A2 | slt2-trial | slt2-trial@example.test | Trial + free-signup products only |
| A3 | slt2-switch | slt2-switch@example.test | Plan-switching ladder only (upgrade/downgrade/crossgrade) |
| A4 | slt2-flex | slt2-flex@example.test | Flexible-renewal-sync products only |
| A5 | slt2-fail | slt2-fail@example.test | Failing-card / retry / dunning only |
| A6 | slt2-paddle | slt2-paddle@example.test | Paddle sandbox only — never used with Stripe |
| A7 | slt2-admincreated | slt2-admincreated@example.test | Target of an admin-created subscription; never checks out |
| A9 | slt2-flex2 | slt2-flex2@example.test | Flexible-renewal-sync segment 2 purchases |
| A10 | slt2-flex3 | slt2-flex3@example.test | Flexible-renewal-sync segment 3 purchases |
| A8 | (none — created at checkout) | slt2-guest-d0@example.test | The "guest -> new" path; the account is born at checkout, so it is NOT created here |

## Steps
1. `agent-browser --session admin-SLT-SETUP-03 open "https://mirror-help.arrayhash.com/wp-admin/user-new.php"` -> `agent-browser --session admin-SLT-SETUP-03 snapshot -i`.
2. For A1..A7 plus A9 and A10 in the table: fill **Username**, **Email**, **First Name** = `SLT`, **Last Name** = the purpose word (e.g. `Core`), untick **Send User Notification**, set **Password** to `SltQa!2026#Pass` via the *Set New Password* button, set **Role** = `Customer`, click **Add New User**, then re-snapshot and re-open `user-new.php` for the next one.
3. Capture `mailpit-agent latest-id` BEFORE the first Add New User click. With *Send User Notification* unticked, expect no customer account email but exactly one WordPress **New User Registration** notification to the site administrator per created account. Map those messages explicitly so later ArraySubs email reconciliation can exclude them.
4. For A1..A7 plus A9 and A10 set a billing address so block checkout does not stall: `agent-browser --session admin-SLT-SETUP-03 open "https://mirror-help.arrayhash.com/wp-admin/user-edit.php?user_id=<ID>"`, scroll to **Customer billing address**, set First name `SLT`, Last name `<Purpose>`, Address line 1 `1 SLT2 Way`, City `Dhaka`, Country/Region `Bangladesh`, Postcode `1207`, Phone `+8801700000000`, Email = the account email. Update User.
5. Do NOT create A8. Record in the registry that `slt2-guest-d0@example.test` is reserved and must not be pre-created — the checkout task that uses it proves auto-account-creation.
6. Reserve a second guest email `slt2-guest-d5@example.test` in the registry for a later-window guest run.
7. Verify: `wp user list --format=csv --fields=ID,user_login,user_email,roles --allow-root | grep slt2-`.
8. Append all nine user IDs to the `slt2-catalog-registry` page.

## Expected results
1. Exactly 9 users exist with logins `slt2-core`, `slt2-trial`, `slt2-switch`, `slt2-flex`, `slt2-fail`, `slt2-paddle`, `slt2-admincreated`, `slt2-flex2`, and `slt2-flex3`, all with role `customer`.
2. Each has the billing address block populated (country BD, postcode 1207).
3. No user named `slt2-guest-d0` or `slt2-guest-d5` exists.
4. `cust1` (5), `customer1` (32), `sync-stripe` (319) are untouched — their `user_registered` and email are unchanged.
5. The registry page lists all 9 IDs plus the two reserved guest emails.

## Emails expected
| # | Email | Trigger point | Recipient | Subject contains | Verify with |
|---|---|---|---|---|---|
| 1 | WordPress New User Registration | Each **Add New User** | Site administrator | `New User Registration` | Exactly one admin message per created account |
| 2 | NONE EXPECTED — customer account email | Each **Add New User** with **Send User Notification** off | `slt2-*@example.test` | — | Zero messages addressed to the nine new customers |

## Evidence to capture
- Screenshot `SLT-SETUP-03-01-users-list-slt.png` of `edit.php` filtered to the slt users.
- The `wp user list --allow-root | grep slt2-` output.
- Nine WP user IDs, recorded in the registry.

## Pass criteria
- [ ] 9 slt2-* customers exist with correct emails and role
- [ ] Billing address populated on all 9
- [ ] No guest account pre-created; both guest emails reserved in the registry
- [ ] Exactly 9 WordPress admin registration messages and zero customer account messages
- [ ] Existing users 5 / 32 / 319 untouched

## SLT2 execution — SUPERSEDED / BLOCKED (site date 2026-08-23)

- Created the nine isolated customers through the real WordPress Add User UI: IDs `474` through `482`, mapped A1–A7/A9/A10 exactly as declared. Each user has only the `customer` role and the complete checkout billing block (`1 SLT2 Way`, Dhaka, `BD`, `1207`, phone and matching billing email).
- Reserved guests `slt2-guest-d0` and `slt2-guest-d5` remain absent. Registry page `31301` now records all nine IDs and both reserved emails; existing control users `5`, `32` and `319` still match the pre-task login/email/registration/role snapshot.
- Mailpit baseline was `4M53QIPekuKDdmPjFx8ofM`. The task created exactly nine site-admin `New User Registration` messages, one per login, and zero messages addressed to any `slt2-*@example.test` customer.
- Browser errors were empty; console output contained only WordPress JQMIGRATE informational logs. Evidence: `/home/server-manager/slt-evidence/SLT-SETUP-03-users.json`, `SLT-SETUP-03-mailpit.json`, `SLT-SETUP-03-browser.txt`, and screenshots `SLT-SETUP-03-01`/`02`.
- No subscription, order, product or automatic-gateway operation was performed. Shared Stripe issue #1 remains isolated and does not invalidate this gateway-independent setup result.

## Isolation / teardown
- State handoff: the account matrix. Later tasks MUST use the account whose purpose matches; crossing purposes (e.g. buying a flex product as `slt2-core`) invalidates the isolation guarantee because subscription-per-customer state leaks between tests.
- Restores: nothing changed globally. SLT-SETUP-99B deletes all allowlisted `slt2-*` users (including any created at checkout) after their SLT-owned records are deleted.

---

### Fresh-cycle validation contract

- Re-derive every ID, count, option value, gateway capability, scheduler timestamp, and email baseline on this run; no prior-cycle result is evidence.
- Create and mutate only registered `SLT2 ` / `slt2-*` fixtures. Legacy `SLT` and all non-SLT2 data are read-only controls.
- Automatic-gateway scope is Stripe and Paddle only. Stripe is the primary path; run Paddle parity wherever Paddle supports the behavior. Do not test or configure PayPal or Mollie.
- ArraySubs core must own its Stripe/Paddle integration, renewal, retry, webhook, REST, refund, and customer-payment services with Pro inactive; vendor host classes retain their expected ownership.
- Browser-required assertions use Vercel `agent-browser` with isolated task/role sessions and current snapshot refs. WP-CLI always includes `--allow-root`.
- Update the lifecycle card, the matching `qa/progress/` card, and `qa/issues/` for every new regression. Evidence belongs to this fresh cycle only.

[[2026-08-23]] Sun 02:39

## D00 early-watcher phase-integrity correction — 2026-08-23

- Users 474-482 were created at 01:47:33-01:51:04 site; reserved guests A8/A11 remain absent.
- D00 watch ownership assigns this card to afternoon at approximately 16:10 site, but its browser mutation occurred roughly 13.5-14.5 hours early. Its prior PASS therefore cannot stand under the binding phase rule.
- The authoritative TSV also omitted these identities at completion. The watcher backfilled only exact proven identity/provider rows with `cleanup_approved=no`; this containment does not waive timing or proof defects.
- Shared issue #2 owns the blocker. Do not delete, recreate, rename, or duplicate the fixture. The afternoon owner must use an approved non-duplicating revalidation protocol and rerun every mandatory assertion before unblocking this card.

[[2026-08-23]] Sun 03:02

## Closure-audit normalization

Stale PASS heading/checkmarks were reset, issue #2 linkage was made explicit, and provider-side catalogue wording was corrected where applicable. The lifecycle start timestamp now matches the original `todo -> in-progress` activity event. Status remains blocked; this note is tracking normalization, not fresh test proof.
