---
id: 36
title: 'Member access reacting to status: pro_member add/remove across active-on-hold-cancelled, My Features entitlements, SLT URL gate'
status: done
priority: high
created: 2026-08-02T03:43:06.01030236+02:00
updated: 2026-08-05T21:37:49.433669406+02:00
started: 2026-08-05T21:06:45.261977593+02:00
completed: 2026-08-05T21:06:45.261977593+02:00
tags:
    - admin
    - portal
    - day-02
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

## Objective
Prove status drives member access on the one subscription that walks the full ladder unattended. `RoleManager::handleStatusChange()` (hooked at `MembersAccess/Services/Hooks.php:54`) adds `pro_member` on activation, removes it on on-hold when `on_hold_behavior='remove'` and again on cancellation; `arraysubs_get_customer_features()` (`restriction-helpers.php:279-289`) counts only active/trial subscriptions, so My Features empties out; an SLT URL rule flips open->blocked in step. Configuration happens BEFORE the purchase; every later observation is read-only.

## Scope
- Gateway: N/A (observation only, no checkout here)
- Checkout: N/A
- Account: existing (`slt-fail`)
- Plugins: both (free MembersAccess + pro FeatureManager)

## Preconditions
- SLT-SETUP-02 done (quote its frozen-baseline registry table). SLT-PROD-16 done; `SLT Retry Daily` exists, **not yet purchased**.
- Role `pro_member` already exists. The **D2 setup leg** must finish before 12:00 site on 2026-08-04; the card then remains `in-progress` for its D5 and D7 observations. The dunning group buys SLT Retry Daily as `slt-fail` with card `4000 0000 0000 0341` that afternoon and the grant fires only on that activation.

## Test data
| Item | Value |
|---|---|
| Product / account | `SLT Retry Daily` = `PID_RETRY`; slt-fail / `SltQa!2026#Pass`, session `--session customer-MYA-05-SLT-MYA-05` |
| Admin session | `--session admin-SLT-MYA-05` for configuration, role evidence, and teardown only |
| Rule 1 | `role_mapping_rules[]` id `slt_role_pro_member`, enabled, cond `has_active_subscription` `[PID_RETRY]`, add_roles `[pro_member]`, on_hold_behavior **`remove`**, no fallback_role |
| Rule 2 | `url_rules[]` id `slt_url_member_area`, enabled, priority 50, pattern `/slt-member-area`, prefix, same cond, action `message`, `SLT member area is restricted.` |
| Features | `_arraysubs_features` on PID_RETRY: `[{"name":"SLT Seats","type":"number","value":5,"enabled":true},{"name":"SLT Priority Support","type":"boolean","value":true,"enabled":true}]` |

## Steps
1. Dump `arraysubs_settings` to `/home/server-manager/slt-evidence/SLT-MYA-05-priors.json`. Record `UID_FAIL`; `wp user get "$UID_FAIL" --field=roles --allow-root` must read `customer`. Do not set a mail baseline or declare the bracket open yet because this step is read-only.
2. `--session admin-SLT-MYA-05`: prepare page `SLT Member Area` (slug `slt-member-area`, body `SLT gated body text`) without publishing. Immediately before clicking **Publish**, set `MB05_SETUP=$(mailpit-agent latest-id)` and record the UTC bracket-open time in `/home/server-manager/slt-evidence/SLT-MYA-05-members-access-bracket.txt` and `slt-catalog-registry`; this is the first non-baseline mutation. Publish, then open `.../wp-admin/admin.php?page=arraysubs-mainadmin`, use the left menu to reach the Members Access role-mapping and URL-rules screens, add both rules, Save. **Record the admin hash routes used** - do not assume them. The bracket ends immediately after the D5 follow-up B, not on D7.
3. `--session admin-SLT-MYA-05`: edit PID_RETRY, paste the Features JSON into the Features panel, Update. Change nothing else - price, period, interval stay as SLT-PROD-16 left them.
4. `wp option get arraysubs_settings --format=json --allow-root | jq '.members_access'` - both SLT rules present and the pre-existing rules untouched.
5. As slt-fail in `customer-MYA-05-SLT-MYA-05`: `/slt-member-area` must show the restriction message and `/my-account/features/` must read "You don't have any features yet." Capture `SLT-MYA-05-01-features-empty.png` and `-02-gated-before.png`. Inspect the complete delta after `MB05_SETUP`, classify unrelated/background mail, and require zero task-attributable mail. Close only `admin-SLT-MYA-05` and `customer-MYA-05-SLT-MYA-05` after this D2 setup leg.
6. **Follow-up A, D2 PM after the dunning purchase:** set `MB05_ACTIVE=$(mailpit-agent latest-id)` immediately before the read, reopen `customer-MYA-05-SLT-MYA-05`, and re-authenticate. Roles = `customer,pro_member`; `/my-account/features/` lists `SLT Seats 5` and `SLT Priority Support` Yes under SLT Retry Daily; `/slt-member-area` shows the body. Capture `SLT-MYA-05-03-features-active.png` and `-04-gated-open.png`; inspect the bounded mail delta and require zero task-attributable mail, then close only the customer session.
7. **Follow-up B, D5 morning (2026-08-07):** set `MB05_HOLD=$(mailpit-agent latest-id)` immediately before the read; reopen both task sessions and re-authenticate. Subscription is `arraysubs-on-hold` after the D4 (08-06 PM) hold sweep: `pro_member` gone, features empty, `/slt-member-area` blocked. Capture `SLT-MYA-05-05-features-on-hold.png` and `-06-gated-on-hold.png`. Read the newest SLT Retry Daily renewal total and prove the pre-existing "Gold members save 15%" rule did not alter the cron renewal.
8. **Immediate teardown after follow-up B:** delete both SLT rules and `_arraysubs_features`, trash `SLT Member Area`, re-dump and diff `jq -S .members_access` priors vs after -> empty. Append the UTC bracket-close time to `/home/server-manager/slt-evidence/SLT-MYA-05-members-access-bracket.txt` and the registry. Re-read the settings, product meta, and page state to prove the deviation is gone before any later D5 task starts. Inspect the complete delta after `MB05_HOLD`, classify unrelated/background mail, and require zero task-attributable mail; close both task sessions.
9. **Follow-up C, D7 immediately after `SLT-DUN-04` observes the natural cancellation (~14:00–16:00 on 2026-08-09), and before `SLT-DUN-05` reuses `slt-fail`:** set `MB05_CANCELLED=$(mailpit-agent latest-id)` immediately before the read, reopen the same task-keyed customer session, and re-authenticate. Read-only after teardown: subscription is `arraysubs-cancelled` (grace_days_before_cancel=3), with no `pro_member`, `customer` retained, and no `subscriber` fallback. Capture the cancelled subscription/account state as `SLT-MYA-05-07-cancelled-account.png`; read the newest SLT Retry Daily renewal total again; do not recreate either rule, the page, or the feature meta. Inspect the bounded mail delta and require zero task-attributable mail, then close only the customer session. If `SLT-DUN-04` has not closed, leave this follow-up pending rather than asserting cancellation early.

## Expected results
1. Before purchase: roles `customer`, features empty, `/slt-member-area` restricted.
2. On activation `pro_member` is added, the features page lists both SLT features (`5`, Yes) per subscription, and `/slt-member-area` renders the body.
3. On the D5 read after the 2026-08-06 on-hold transition: `pro_member` removed, features empty, `/slt-member-area` shows `SLT member area is restricted.`
4. On cancelled (2026-08-09): `pro_member` still absent, `customer` retained, no fallback role added.
5. Every SLT Retry Daily renewal total matches the dunning group's expected amount - no member discount reaches a cron renewal - and the final `members_access` jq diff is empty.
6. The rules/features/page deviation closes immediately after the D5 on-hold proof; D7 is a read-only role check with no configuration reintroduced.

## Emails expected
| # | Email | Trigger point | Recipient | Subject contains | Verify with |
|---|---|---|---|---|---|
| 1 | NONE EXPECTED | Rule/product/page edits and every read-only check; the payment_failed / on_hold / cancelled mails belong to the dunning task, reference their ids only | - | - | Complete bounded deltas after `MB05_SETUP`, `MB05_ACTIVE`, `MB05_HOLD`, and `MB05_CANCELLED`; zero mail attributable to this task, while dunning and unrelated/background mail is classified by owner |

## Evidence to capture
- Screenshots `SLT-MYA-05-01` through `-07` for the before/active/on-hold/cancelled surfaces; `UID_FAIL`, `PID_RETRY`, subscription id, both rule ids, the admin hash routes; all four per-leg mail baselines; role output at each checkpoint; priors JSON, the empty final diff, and the bracket file with D2-open/D5-close UTC timestamps.

## Pass criteria
- [ ] pro_member added on activation, removed on on-hold (`on_hold_behavior=remove` honoured), still absent after cancellation with `customer` retained
- [ ] My Features populated when active, empty when on-hold/cancelled; SLT URL gate open when active, message-blocked otherwise
- [ ] No member discount reached any renewal total; members_access diff empty after teardown, page and meta removed
- [ ] Registry/evidence bracket closed immediately after D5 follow-up B; D7 follow-up stayed read-only

## Isolation / teardown
- Declared non-baseline change: two appended rules inside `members_access`, recorded in Notes and the registry and restored in step 8 immediately after the D5 on-hold proof. Hands the dunning group the bracket timestamps so any role change can be attributed.
- Never buys, cancels, pays or drains anything. `SLT Member Area` carries the `SLT ` prefix, so SLT-SETUP-99B's sweep covers it if step 8 is interrupted.
- At each dated follow-up reopen only the required task-keyed session name and re-authenticate; never depend on an earlier phase's cookies. Close both task sessions after D2 setup and D5 teardown, and close the customer session after D2 follow-up A and D7 follow-up C. Never touch unrelated sessions.

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

[[2026-08-05]] Wed 21:06
UNVERIFIED (no S_FAIL source fixture) on 2026-08-05.

Live verification confirms `slt-fail` exists as user 351 but owns no ArraySubs subscription rows, so the `SLT Retry Daily` source purchase from `SLT-DUN-01` never happened. The current `members_access` option contains only the pre-existing non-SLT rules; task-specific rule ids `slt_role_pro_member` / `slt_url_member_area` are absent, which means the `SLT-MYA-05` setup bracket was never opened either. The D03 suite report explicitly states `SLT-DUN-01` has no valid `S_FAIL` fixture and that no replacement fixture may be fabricated. Without that activation, the authored D2/D5/D7 role and feature observations cannot occur, and no later recovery path authorizes creating them out of sequence. This card closes without adding rules, pages, features, or a late checkout.
