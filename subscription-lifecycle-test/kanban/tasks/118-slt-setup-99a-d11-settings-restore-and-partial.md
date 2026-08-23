---
id: 118
title: SLT-SETUP-99A D11 settings restore and partial cancellation — no deletions
status: todo
priority: critical
created: 2026-08-22T19:10:00+02:00
updated: 2026-08-22T19:10:00+02:00
tags:
    - cycle-2
    - granular
    - setup
    - day-11
due: "2026-09-03"
estimate: 1h30m
depends_on:
    - 11
    - 102
    - 116
    - 106
    - 117
    - 127
    - 132
    - 133
class: standard
---

> **SLT-SETUP-99A** · group `foundation` · scheduled **D11** (2026-09-03)

> Read `README.md` (environment + isolation contract), `calendar.md` (this day's exact
> ordering — it is binding, not advisory) and `plan-audit.md` before starting.
## Objective
Restore every window-wide setting after the 12-day D0-D11 execution window and cancel **only** subscriptions whose execution evidence is complete. Preserve the registered tail needed by the D12 read-only watch. Delete nothing in this task.

## Scope
- Gateway: N/A
- Checkout: N/A
- Account: admin only
- Plugins: both

## Preconditions
- Runs on D11 (2026-09-03) **after** that day's scenario execution and watch report, and after every D0-D11 task required by the cancel-cohort calculation has either passed or has a linked open issue and remains blocked.
- The D0 baseline values recorded by `SLT-SETUP-02` are on the registry page. If they are not, STOP — restoring from memory is not acceptable.

## Test data
| Item | Value |
|---|---|
| Settings to restore | Every current path changed by `SLT-SETUP-02`, to its exact D0 saved presence/value from `SLT-SETUP-02-priors.txt`; never create the retired `customer_actions.allow_reactivation` key |
| Shop Access to restore | Exact `members_access.enabled` + `ecommerce_rules` snapshot at `/home/server-manager/slt-evidence/SLT-PROD-01-members-access-rules.json` |
| Cancel cohort | subscriptions whose tasks are all closed and whose evidence is captured |
| Keep-alive cohort | everything the D11/D12 watch still needs |

## Steps
1. Read the D0 baseline block from `slt2-catalog-registry`. Set `RESTORE_PRE=$(mailpit-agent latest-id)`, then save the current settings first:
   `wp option get arraysubs_settings --format=json --allow-root > /home/server-manager/slt-evidence/SLT-SETUP-99A-settings-before-restore.json`
2. In isolated `admin-SLT-SETUP-99A`, restore every current path changed by `SLT-SETUP-02` through the **admin UI**, not the CLI, so the plugin's own save path runs. Preserve the exact prior presence/value semantics for unchanged fields, leave `renewals.sync_first_charge_mode` at its D0 value, and prove the retired `customer_actions.allow_reactivation` path is still absent.
3. In the same session, open **Member Access → Shop Access** and restore the exact pre-window rule state captured in
   `SLT-PROD-01-members-access-rules.json`: remove every SLT2 product exclusion added by this run without
   changing any other rule field. Re-fetch the raw option and require an empty `jq -S` diff over
   `{enabled: .members_access.enabled, ecommerce_rules: .members_access.ecommerce_rules}`.
4. Diff to prove the settings restore is exact and complete: write the sorted post-restore value to `/home/server-manager/slt-evidence/SLT-SETUP-99A-settings-after-restore.json` and compare against the D0 baseline. The diff for the baseline keys must be empty. Inspect every Mailpit message newer than `RESTORE_PRE` and require none to be attributable to the settings/Shop Access saves; classify unrelated natural-watch mail rather than requiring the global latest ID to stay fixed.
5. Build the **cancel cohort** explicitly: for every SLT2 subscription, confirm every task referencing it is `done` and its evidence is on disk. List them on the registry.
6. Build the **keep-alive cohort** from the D12 row in `watch-schedule.md` — every subscription that read-only watch still asserts on. List them on the registry too. The two lists must be disjoint and must together cover every SLT2 subscription.
7. Set `CANCEL_PRE=$(mailpit-agent latest-id)`, then cancel **only** the cancel cohort, through the admin UI, one at a time. Record the exact subscription ID and click timestamp for every cancellation.
8. Inspect **every** Mailpit message newer than `CANCEL_PRE` (paginate the localhost Mailpit API if the helper's default list is shorter than the full delta). For every cancelled subscription require exactly one customer and one admin cancellation message naming that exact ID; count and save/show all linked IDs, classify unrelated mail, and prove no keep-alive subscription appears. Publish this expected teardown delta for the D11 watch.
9. Consolidate evidence: confirm every task's screenshots and dumps are under `/home/server-manager/slt-evidence/`, and export this D11 registry snapshot to `/home/server-manager/slt-evidence/SLT-SETUP-99A-registry-D11.md` (the registry itself stays live through 99B). Recount products/coupons/users/orders/subscriptions before/after, verify only intended statuses changed, close `admin-SLT-SETUP-99A`, independently review settings/cohorts/cancellations/mail/counts, then move through `review` to `done` with Review empty. Any defect creates or updates the mandatory `qa/issues/` kanban card with task/stage/plan path; affected setting/rule and artifact/subscription/action/message IDs; user IDs/logins/emails/roles; exact routes/session/timestamps; reproduction; expected/actual; and before/after JSON/UI/meta/queue/Mailpit/count proof.

## Expected results
1. Every baseline setting matches its D0 value; the `jq` diff over those keys is empty.
2. The captured Shop Access JSON is restored exactly; the full-store rule has no SLT2 exclusions.
3. The cancel and keep-alive cohorts are published, disjoint, and jointly exhaustive.
4. Only cancel-cohort subscriptions are `arraysubs-cancelled`; every keep-alive subscription is untouched and still scheduled.
5. **Nothing is deleted.** Product, coupon, user, order and subscription counts are unchanged apart from the status flips.
6. Cancellation email IDs are recorded for the D11 watch.

## Emails expected
| # | Email | Trigger point | Recipient | Subject contains | Verify with |
|---|---|---|---|---|---|
| 1 | `subscription_cancelled` × cohort size | each cancellation | each subscription's customer | exact subscription ID + `cancelled` | complete paginated `CANCEL_PRE` delta; one exact match per cohort member |
| 2 | `admin_subscription_cancelled` × cohort size | same | admin | exact subscription ID + `cancelled` | same complete delta; one admin match per cohort member |
| 3 | NONE EXPECTED | settings/Shop Access restore | — | — | no attributable message in the complete `RESTORE_PRE` delta; unrelated mail classified |

## Evidence to capture
- `SLT-SETUP-99A-settings-before-restore.json`, `SLT-SETUP-99A-settings-after-restore.json`, the post-restore `jq` diff, both cohort lists, `RESTORE_PRE`/`CANCEL_PRE`, the complete cancellation delta and all Mailpit IDs, and `SLT-SETUP-99A-registry-D11.md`.

## Pass criteria
- [ ] All baseline settings restored, diff empty
- [ ] Exact pre-window Shop Access JSON restored; zero SLT2 exclusions remain
- [ ] Cohorts published, disjoint, exhaustive
- [ ] Only the cancel cohort cancelled; keep-alive cohort still scheduled
- [ ] Zero deletions
- [ ] Cancellation mail IDs recorded for the D11 watch
- [ ] Exact admin session closed; QA issue cards and independent review reach `done` with Review empty

## Isolation / teardown
- Hands the D11/D12 watch a live keep-alive cohort and an explicit list of expected cancellation mail. `SLT-SETUP-99B` does the actual deletion, on 2026-09-05.

---

### Fresh-cycle validation contract

- Re-derive every ID, count, option value, gateway capability, scheduler timestamp, and email baseline on this run; no prior-cycle result is evidence.
- Create and mutate only registered `SLT2 ` / `slt2-*` fixtures. Legacy `SLT` and all non-SLT2 data are read-only controls.
- Automatic-gateway scope is Stripe and Paddle only. Stripe is the primary path; run Paddle parity wherever Paddle supports the behavior. Do not test or configure PayPal or Mollie.
- ArraySubs core must own its Stripe/Paddle integration, renewal, retry, webhook, REST, refund, and customer-payment services with Pro inactive; vendor host classes retain their expected ownership.
- Browser-required assertions use Vercel `agent-browser` with isolated task/role sessions and current snapshot refs. WP-CLI always includes `--allow-root`.
- Update the lifecycle card, the matching `qa/progress/` card, and `qa/issues/` for every new regression. Evidence belongs to this fresh cycle only.
