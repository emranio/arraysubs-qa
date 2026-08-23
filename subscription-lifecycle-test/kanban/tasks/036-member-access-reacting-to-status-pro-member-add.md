---
id: 36
title: 'Member access reacting to status: pro_member add/remove across active-on-hold-cancelled, My Features entitlements, SLT2 URL gate'
status: todo
priority: high
created: 2026-08-22T19:10:00+02:00
updated: 2026-08-22T19:10:00+02:00
tags:
    - cycle-2
    - granular
    - admin
    - portal
    - day-02
due: "2026-08-25"
estimate: 2h
depends_on:
    - 11
    - 12
    - 23
class: standard
---

> **SLT-MYA-05** · group `admin` · scheduled **D02** (2026-08-25)

> Read `README.md` (environment + isolation contract), `calendar.md` (this day's exact
> ordering — it is binding, not advisory) and `plan-audit.md` before starting.

## Objective
Prove status drives member access on the one subscription that walks the full ladder unattended. `RoleManager::handleStatusChange()` (hooked at `MembersAccess/Services/Hooks.php:54`) adds `pro_member` on activation, removes it on on-hold when `on_hold_behavior='remove'` and again on cancellation; `arraysubs_get_customer_features()` (`restriction-helpers.php:279-289`) counts only active/trial subscriptions, so My Features empties out; an SLT2 URL rule flips open->blocked in step. Configuration happens BEFORE the purchase; every later observation is read-only.

## Scope
- Gateway: N/A (observation only, no checkout here)
- Checkout: N/A
- Account: existing (`slt2-fail`)
- Plugins: both (free MembersAccess + pro FeatureManager)

## Preconditions
- SLT-SETUP-02 done (quote its frozen-baseline registry table). SLT-PROD-16 done; `SLT2 Retry Daily` exists, **not yet purchased**.
- Role `pro_member` already exists. The **D2 setup leg** must finish before 12:00 site on 2026-08-25; the card then remains `in-progress` for its D5 and D7 observations. The dunning group buys SLT2 Retry Daily as `slt2-fail` with card `4000 0000 0000 0341` that afternoon and the grant fires only on that activation.

## Test data
| Item | Value |
|---|---|
| Product / account | `SLT2 Retry Daily` = `PID_RETRY`; slt2-fail / `SltQa!2026#Pass`, session `--session customer-MYA-05-SLT-MYA-05` |
| Admin session | `--session admin-SLT-MYA-05` for configuration, role evidence, and teardown only |
| Rule 1 | `role_mapping_rules[]` id `slt_role_pro_member`, enabled, cond `has_active_subscription` `[PID_RETRY]`, add_roles `[pro_member]`, on_hold_behavior **`remove`**, no fallback_role |
| Rule 2 | `url_rules[]` id `slt_url_member_area`, enabled, priority 50, pattern `/slt2-member-area`, prefix, same cond, action `message`, `SLT2 member area is restricted.` |
| Features | `_arraysubs_features` on PID_RETRY: `[{"name":"SLT2 Seats","type":"number","value":5,"enabled":true},{"name":"SLT2 Priority Support","type":"boolean","value":true,"enabled":true}]` |

## Steps
1. Dump `arraysubs_settings` to `/home/server-manager/slt-evidence/SLT-MYA-05-priors.json`. Record `UID_FAIL`; `wp user get "$UID_FAIL" --field=roles --allow-root` must read `customer`. Do not set a mail baseline or declare the bracket open yet because this step is read-only.
2. `--session admin-SLT-MYA-05`: prepare page `SLT2 Member Area` (slug `slt2-member-area`, body `SLT2 gated body text`) without publishing. Immediately before clicking **Publish**, set `MB05_SETUP=$(mailpit-agent latest-id)` and record the UTC bracket-open time in `/home/server-manager/slt-evidence/SLT-MYA-05-members-access-bracket.txt` and `slt2-catalog-registry`; this is the first non-baseline mutation. Publish, then open `.../wp-admin/admin.php?page=arraysubs-mainadmin`, use the left menu to reach the Members Access role-mapping and URL-rules screens, add both rules, Save. **Record the admin hash routes used** - do not assume them. The bracket ends immediately after the D5 follow-up B, not on D7.
3. `--session admin-SLT-MYA-05`: edit PID_RETRY, paste the Features JSON into the Features panel, Update. Change nothing else - price, period, interval stay as SLT-PROD-16 left them.
4. `wp option get arraysubs_settings --format=json --allow-root | jq '.members_access'` - both SLT2 rules present and the pre-existing rules untouched.
5. As slt2-fail in `customer-MYA-05-SLT-MYA-05`: `/slt2-member-area` must show the restriction message and `/my-account/features/` must read "You don't have any features yet." Capture `SLT-MYA-05-01-features-empty.png` and `-02-gated-before.png`. Inspect the complete delta after `MB05_SETUP`, classify unrelated/background mail, and require zero task-attributable mail. Close only `admin-SLT-MYA-05` and `customer-MYA-05-SLT-MYA-05` after this D2 setup leg.
6. **Follow-up A, D2 PM after the dunning purchase:** set `MB05_ACTIVE=$(mailpit-agent latest-id)` immediately before the read, reopen `customer-MYA-05-SLT-MYA-05`, and re-authenticate. Roles = `customer,pro_member`; `/my-account/features/` lists `SLT2 Seats 5` and `SLT2 Priority Support` Yes under SLT2 Retry Daily; `/slt2-member-area` shows the body. Capture `SLT-MYA-05-03-features-active.png` and `-04-gated-open.png`; inspect the bounded mail delta and require zero task-attributable mail, then close only the customer session.
7. **Follow-up B, D5 morning (2026-08-28):** set `MB05_HOLD=$(mailpit-agent latest-id)` immediately before the read; reopen both task sessions and re-authenticate. Subscription is `arraysubs-on-hold` after the D4 (08-27 PM) hold sweep: `pro_member` gone, features empty, `/slt2-member-area` blocked. Capture `SLT-MYA-05-05-features-on-hold.png` and `-06-gated-on-hold.png`. Read the newest SLT2 Retry Daily renewal total and prove the pre-existing "Gold members save 15%" rule did not alter the cron renewal.
8. **Immediate teardown after follow-up B:** delete both SLT2 rules and `_arraysubs_features`, trash `SLT2 Member Area`, re-dump and diff `jq -S .members_access` priors vs after -> empty. Append the UTC bracket-close time to `/home/server-manager/slt-evidence/SLT-MYA-05-members-access-bracket.txt` and the registry. Re-read the settings, product meta, and page state to prove the deviation is gone before any later D5 task starts. Inspect the complete delta after `MB05_HOLD`, classify unrelated/background mail, and require zero task-attributable mail; close both task sessions.
9. **Follow-up C, D7 immediately after `SLT-DUN-04` observes the natural cancellation (~14:00–16:00 on 2026-08-30), and before `SLT-DUN-05` reuses `slt2-fail`:** set `MB05_CANCELLED=$(mailpit-agent latest-id)` immediately before the read, reopen the same task-keyed customer session, and re-authenticate. Read-only after teardown: subscription is `arraysubs-cancelled` (grace_days_before_cancel=3), with no `pro_member`, `customer` retained, and no `subscriber` fallback. Capture the cancelled subscription/account state as `SLT-MYA-05-07-cancelled-account.png`; read the newest SLT2 Retry Daily renewal total again; do not recreate either rule, the page, or the feature meta. Inspect the bounded mail delta and require zero task-attributable mail, then close only the customer session. If `SLT-DUN-04` has not closed, leave this follow-up pending rather than asserting cancellation early.

## Expected results
1. Before purchase: roles `customer`, features empty, `/slt2-member-area` restricted.
2. On activation `pro_member` is added, the features page lists both SLT2 features (`5`, Yes) per subscription, and `/slt2-member-area` renders the body.
3. On the D5 read after the 2026-08-27 on-hold transition: `pro_member` removed, features empty, `/slt2-member-area` shows `SLT2 member area is restricted.`
4. On cancelled (2026-08-30): `pro_member` still absent, `customer` retained, no fallback role added.
5. Every SLT2 Retry Daily renewal total matches the dunning group's expected amount - no member discount reaches a cron renewal - and the final `members_access` jq diff is empty.
6. The rules/features/page deviation closes immediately after the D5 on-hold proof; D7 is a read-only role check with no configuration reintroduced.

## Emails expected
| # | Email | Trigger point | Recipient | Subject contains | Verify with |
|---|---|---|---|---|---|
| 1 | NONE EXPECTED | Rule/product/page edits and every read-only check; the payment_failed / on_hold / cancelled mails belong to the dunning task, reference their ids only | - | - | Complete bounded deltas after `MB05_SETUP`, `MB05_ACTIVE`, `MB05_HOLD`, and `MB05_CANCELLED`; zero mail attributable to this task, while dunning and unrelated/background mail is classified by owner |

## Evidence to capture
- Screenshots `SLT-MYA-05-01` through `-07` for the before/active/on-hold/cancelled surfaces; `UID_FAIL`, `PID_RETRY`, subscription id, both rule ids, the admin hash routes; all four per-leg mail baselines; role output at each checkpoint; priors JSON, the empty final diff, and the bracket file with D2-open/D5-close UTC timestamps.

## Pass criteria
- [ ] pro_member added on activation, removed on on-hold (`on_hold_behavior=remove` honoured), still absent after cancellation with `customer` retained
- [ ] My Features populated when active, empty when on-hold/cancelled; SLT2 URL gate open when active, message-blocked otherwise
- [ ] No member discount reached any renewal total; members_access diff empty after teardown, page and meta removed
- [ ] Registry/evidence bracket closed immediately after D5 follow-up B; D7 follow-up stayed read-only

## Isolation / teardown
- Declared non-baseline change: two appended rules inside `members_access`, recorded in Notes and the registry and restored in step 8 immediately after the D5 on-hold proof. Hands the dunning group the bracket timestamps so any role change can be attributed.
- Never buys, cancels, pays or drains anything. `SLT2 Member Area` carries the `SLT ` prefix, so SLT-SETUP-99B's sweep covers it if step 8 is interrupted.
- At each dated follow-up reopen only the required task-keyed session name and re-authenticate; never depend on an earlier phase's cookies. Close both task sessions after D2 setup and D5 teardown, and close the customer session after D2 follow-up A and D7 follow-up C. Never touch unrelated sessions.

---

### Fresh-cycle validation contract

- Re-derive every ID, count, option value, gateway capability, scheduler timestamp, and email baseline on this run; no prior-cycle result is evidence.
- Create and mutate only registered `SLT2 ` / `slt2-*` fixtures. Legacy `SLT` and all non-SLT2 data are read-only controls.
- Automatic-gateway scope is Stripe and Paddle only. Stripe is the primary path; run Paddle parity wherever Paddle supports the behavior. Do not test or configure PayPal or Mollie.
- ArraySubs core must own its Stripe/Paddle integration, renewal, retry, webhook, REST, refund, and customer-payment services with Pro inactive; vendor host classes retain their expected ownership.
- Browser-required assertions use Vercel `agent-browser` with isolated task/role sessions and current snapshot refs. WP-CLI always includes `--allow-root`.
- Update the lifecycle card, the matching `qa/progress/` card, and `qa/issues/` for every new regression. Evidence belongs to this fresh cycle only.
