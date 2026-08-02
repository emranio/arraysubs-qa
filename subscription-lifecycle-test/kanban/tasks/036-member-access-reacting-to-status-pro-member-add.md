---
id: 36
title: 'Member access reacting to status: pro_member add/remove across active-on-hold-cancelled, My Features entitlements, SLT URL gate'
status: todo
priority: high
created: 2026-08-02T03:43:06.01030236+02:00
updated: 2026-08-02T03:43:16.446953222+02:00
tags:
    - admin
    - portal
    - day-02
    - has-conflicts
due: "2026-08-04"
estimate: 2h
depends_on:
    - 11
    - 12
    - 23
class: standard
---

> **SLT-MYA-05** · group `admin` · scheduled **D02** (2026-08-04)

> Read `README.md` (environment + isolation contract), `calendar.md` (this day's exact
> ordering — it is binding, not advisory) and `plan-audit.md` before starting.

### ⚠ Conflict resolutions that apply to this task

**`critical` · impossible-timing / cross-group date contradiction** — with `SLT-DUN-01`, `SLT-DUN-02`, `SLT-DUN-03`, `SLT-DUN-04`, `SLT-DUN-05`, `SLT-EML-04`

- *Problem:* SLT-DUN-01 is tagged d0 (buy SLT Retry Daily as slt-fail on 2026-08-02, D=08-03, hold 08-04, cancel 08-07). Four other tasks encode the opposite timeline as fact: SLT-EML-04 ('bought on D2 (2026-08-04 PM) ... D = 2026-08-05 PM ... attempts 08-05/06/07/08 -> watch D4..D7 ... on-hold 08-06 ... cancelled 08-09'), SLT-EML-14 ('Retry Daily fails 08-05 PM -> on-hold 08-06 -> cancelled 08-09'), SLT-ADM-09 ('bought D2 by slt-fail ... renewal failed D3 PM'), and SLT-MYA-05 ('Must finish before 12:00 site on D2 (2026-08-04): the dunning group buys SLT Retry Daily as slt-fail with card 0341 that afternoon and the grant fires only on that activation'). slt-fail + SLT Retry Daily cannot be bought twice (auto-migrate), so exactly one timeline can exist. Additionally MYA-05's pro_member role-mapping rule MUST be written before the checkout - if DUN-01 runs on D0 the role grant never fires and MYA-05 is unrunnable.
- *Required fix:* DUN-01 moves to D2 (2026-08-04), checkout 13:00-14:00 site - which is what four downstream tasks already assume and what the audit's corrected calendar says. Resulting ladder, all fixed: D=08-05 13:00-14:00; failure at D+k (08-05 13:00-20:00, watch D4); on-hold at the first hourly sweep after D+24h = 08-06 ~14:00 (watch D5); retries at +24h/+48h/+72h = 08-06/07/08 (watch D5/D6/D7); 4th charge hits the cap 08-08; cancellation at max(D+96h, on_hold+72h) = 08-09 ~14:00-16:00 (watch D8). Re-day the group: DUN-01 D2, DUN-03 D4, DUN-02 D5 (with reads on D4 and D6), DUN-04 D7, DUN-05 D7 after 16:00 (S2 bought 08-09 16:30, fails 08-10 PM, recovered on the morning of 08-11 before N+24h). MYA-05 stays D2 morning, strictly before 13:00.

**`high` · session collision (shared admin session)** — with `SLT-EML-01`, `SLT-EML-02`, `SLT-EML-03`, `SLT-EML-05`, `SLT-EML-10`, `SLT-EML-12`

- *Problem:* More than twenty tasks open `--session admin` by that bare name, in direct violation of audit C09's fix (which only got applied to guest/customer sessions). agent-browser sessions are keyed by name, so these tasks share one browser profile: one task's `agent-browser close --session admin` logs out another mid-run; one task navigating the SPA away from a settings screen invalidates another's snapshot; and any admin session that ever adds to cart (SLT-ADM-01's bulk-action screen, SLT-EML-12's status juggling) puts a cart on the admin user shared by all of them. The failures this produces look like flaky UI, not contamination.
- *Required fix:* Rename every admin session to `admin-<TASK-KEY>` (SLT-ADM-01/02/03/04/05 already do this correctly - copy the pattern). Each task closes only its own session by name; `agent-browser close --all` is reserved to the last task of the day, named explicitly in the calendar. Add this to the isolation contract as rule 9 and add a pass criterion to every task that opens an admin session: 'the session name contains this task's key'.

**`medium` · shared-product-meta / undeclared bracket** — with `SLT-SYN-13`, `SLT-SYN-02`, `SLT-PROD-15`

- *Problem:* SLT-SYN-13 step 2 writes a decoy segment plan onto the SLT Flex Variable Daily PARENT product and deletes it only at step 7, the same day - but between those steps two live checkouts are placed and the window is unbounded in the body. SLT-SYN-02 audits the same product family on the same day (D2). Any other cart or checkout touching that parent inside the decoy window resolves filterRenewalSyncContext() against a plan no task expects, and the decoy's own null-vs-config proof depends on nothing else having read it. Separately SLT-MYA-05 leaves two appended members_access rules and a product-level _arraysubs_features meta live from D2 morning until its step-10 teardown on D7 - a five-day global deviation during which the pre-existing 'Gold members save 15%' rule (which targets pro_member on ALL products) can alter front-end prices for slt-fail.
- *Required fix:* For SYN-13: declare the decoy a bracket - record open/close UTC in slt-evidence/SLT-SYN-13-decoy-bracket.txt, post it to the registry, keep it under 90 minutes, and assert no other SLT task carts or checks out SLT Flex Variable Daily inside it. Add a pass criterion 'decoy removed and getConfig(<PARENT>) is null before the bracket closes'. For MYA-05: shorten the deviation by moving its teardown from D7 to immediately after follow-up B (D5 morning, once the on-hold role removal is captured) and re-adding the rules only if follow-up C needs them; record the bracket in the registry either way, and add an explicit price check on SLT Retry Daily renewals proving the pro_member discount never reached a cron renewal.

---
## Objective
Prove status drives member access on the one subscription that walks the full ladder unattended. `RoleManager::handleStatusChange()` (hooked at `MembersAccess/Services/Hooks.php:54`) adds `pro_member` on activation, removes it on on-hold when `on_hold_behavior='remove'` and again on cancellation; `arraysubs_get_customer_features()` (`restriction-helpers.php:279-289`) counts only active/trial subscriptions, so My Features empties out; an SLT URL rule flips open->blocked in step. Configuration happens BEFORE the purchase; every later observation is read-only.

## Scope
- Gateway: N/A (observation only, no checkout here)
- Checkout: N/A
- Account: existing (`slt-fail`)
- Plugins: both (free MembersAccess + pro FeatureManager)

## Preconditions
- SLT-SETUP-02 done (quote its frozen-baseline registry table). SLT-PROD-16 done; `SLT Retry Daily` exists, **not yet purchased**.
- Role `pro_member` already exists. Must finish before 12:00 site on D2 (2026-08-04): the dunning group buys SLT Retry Daily as `slt-fail` with card `4000 0000 0000 0341` that afternoon and the grant fires only on that activation.

## Test data
| Item | Value |
|---|---|
| Product / account | `SLT Retry Daily` = `PID_RETRY`; slt-fail / `SltQa!2026#Pass`, session `--session customer-MYA-05` |
| Rule 1 | `role_mapping_rules[]` id `slt_role_pro_member`, enabled, cond `has_active_subscription` `[PID_RETRY]`, add_roles `[pro_member]`, on_hold_behavior **`remove`**, no fallback_role |
| Rule 2 | `url_rules[]` id `slt_url_member_area`, enabled, priority 50, pattern `/slt-member-area`, prefix, same cond, action `message`, `SLT member area is restricted.` |
| Features | `_arraysubs_features` on PID_RETRY: `[{"name":"SLT Seats","type":"number","value":5,"enabled":true},{"name":"SLT Priority Support","type":"boolean","value":true,"enabled":true}]` |

## Steps
1. `mailpit-agent latest-id` -> `MB05`. Dump `arraysubs_settings` to `/home/server-manager/slt-evidence/SLT-MYA-05-priors.json`. Record `UID_FAIL`; `wp user get UID_FAIL --field=roles` must read `customer`.
2. `--session admin`: create page `SLT Member Area` (slug `slt-member-area`, body `SLT gated body text`); Publish. Then open `.../wp-admin/admin.php?page=arraysubs-mainadmin`, use the left menu to reach the Members Access role-mapping and URL-rules screens, add both rules, Save. **Record the admin hash routes used** - do not assume them.
3. `--session admin`: edit PID_RETRY, paste the Features JSON into the Features panel, Update. Change nothing else - price, period, interval stay as SLT-PROD-16 left them.
4. `wp option get arraysubs_settings --format=json --allow-root | jq '.members_access'` - both SLT rules present and the pre-existing rules untouched.
5. As slt-fail in `customer-MYA-05`: `/slt-member-area` must show the restriction message and `/my-account/features/` must read "You don't have any features yet." Screenshot.
6. **Follow-up A, D2 PM after the dunning purchase:** roles = `customer,pro_member`; `/my-account/features/` lists `SLT Seats 5` and `SLT Priority Support` Yes under SLT Retry Daily; `/slt-member-area` shows the body.
7. **Follow-up B, D4 morning (2026-08-06):** subscription is `arraysubs-on-hold` one day after the 08-05 PM failure: `pro_member` gone, features empty, `/slt-member-area` blocked.
8. **Follow-up C, D7 morning (2026-08-09):** subscription is `arraysubs-cancelled` (grace_days_before_cancel=3): no `pro_member`, `customer` retained, no `subscriber` fallback.
9. At each follow-up also read the newest SLT Retry Daily renewal total: the pre-existing "Gold members save 15%" rule targets `pro_member` on all products, but `DiscountHooks` only filters prices for a logged-in user.
10. **Teardown right after step 8:** delete both SLT rules and `_arraysubs_features`, trash `SLT Member Area`, re-dump and diff `jq -S .members_access` priors vs after -> empty.

## Expected results
1. Before purchase: roles `customer`, features empty, `/slt-member-area` restricted.
2. On activation `pro_member` is added, the features page lists both SLT features (`5`, Yes) per subscription, and `/slt-member-area` renders the body.
3. On on-hold (2026-08-06): `pro_member` removed, features empty, `/slt-member-area` shows `SLT member area is restricted.`
4. On cancelled (2026-08-09): `pro_member` still absent, `customer` retained, no fallback role added.
5. Every SLT Retry Daily renewal total matches the dunning group's expected amount - no member discount reaches a cron renewal - and the final `members_access` jq diff is empty.

## Emails expected
| # | Email | Trigger point | Recipient | Subject contains | Verify with |
|---|---|---|---|---|---|
| 1 | NONE EXPECTED | Rule/product/page edits and every read-only check; the payment_failed / on_hold / cancelled mails belong to the dunning task, reference their ids only | - | - | `latest-id` after step 5 equals `MB05` |

## Evidence to capture
- Screenshots `SLT-MYA-05-01-features-empty.png`, `-02-gated-before.png`, `-03-features-active.png`, `-04-gated-open.png`; `UID_FAIL`, `PID_RETRY`, subscription id, both rule ids, the admin hash routes; role output at each checkpoint; priors JSON and the empty final diff.

## Pass criteria
- [ ] pro_member added on activation, removed on on-hold (`on_hold_behavior=remove` honoured), still absent after cancellation with `customer` retained
- [ ] My Features populated when active, empty when on-hold/cancelled; SLT URL gate open when active, message-blocked otherwise
- [ ] No member discount reached any renewal total; members_access diff empty after teardown, page and meta removed

## Isolation / teardown
- Declared non-baseline change: two appended rules inside `members_access`, recorded in Notes and the registry and restored in step 10. Hands the dunning group the configuration timestamp so any role change can be attributed.
- Never buys, cancels, pays or drains anything. `SLT Member Area` carries the `SLT ` prefix, so SLT-SETUP-99B's sweep covers it if step 10 is interrupted.

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
