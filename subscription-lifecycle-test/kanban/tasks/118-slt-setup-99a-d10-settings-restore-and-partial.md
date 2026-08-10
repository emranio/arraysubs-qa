---
id: 118
title: SLT-SETUP-99A D10 settings restore and partial cancellation — no deletions
status: todo
priority: critical
created: 2026-08-02T03:43:12.749985862+02:00
updated: 2026-08-02T03:43:24.490822262+02:00
tags:
    - setup
    - day-10
due: "2026-08-12"
estimate: 1h30m
depends_on:
    - 11
    - 102
    - 116
    - 106
class: standard
---

> **SLT-SETUP-99A** · group `foundation` · scheduled **D10** (2026-08-12)

> Read `README.md` (environment + isolation contract), `calendar.md` (this day's exact
> ordering — it is binding, not advisory) and `plan-audit.md` before starting.
## Objective
The original `SLT-SETUP-99` deleted every SLT artifact on D10 while the automated watch runs to D12 — the audit rated this critical evidence destruction. It is split in two. This half restores global settings and cancels **only** the subscriptions whose evidence is complete. It deletes nothing.

## Scope
- Gateway: N/A
- Checkout: N/A
- Account: admin only
- Plugins: both

## Preconditions
- Runs on D10 (2026-08-12) **after** the morning watch report is written, and **after** `SLT-SYN-09`'s D10 follow-up, `SLT-IMP-05`, and `SLT-DUN-05` are closed.
- The D0 baseline values recorded by `SLT-SETUP-02` are on the registry page. If they are not, STOP — restoring from memory is not acceptable.

## Test data
| Item | Value |
|---|---|
| Settings to restore | `renewals.sync_to_billing_cycle` → `true`; `customer_actions.allow_early_renew` → `false`; `customer_actions.allow_reactivation` → `false`; `pause_subscription.enabled` → `false`; `pause_subscription.customer_can_pause` → `false` |
| Shop Access to restore | Exact `members_access.enabled` + `ecommerce_rules` snapshot at `/home/server-manager/slt-evidence/SLT-PROD-01-members-access-rules.json` |
| Cancel cohort | subscriptions whose tasks are all closed and whose evidence is captured |
| Keep-alive cohort | everything the D11/D12 watch still needs |

## Steps
1. Read the D0 baseline block from `slt-catalog-registry`. Set `RESTORE_PRE=$(mailpit-agent latest-id)`, then save the current settings first:
   `wp option get arraysubs_settings --format=json --allow-root > /home/server-manager/slt-evidence/SLT-SETUP-99A-settings-before-restore.json`
2. In isolated `admin-SLT-SETUP-99A`, restore all five recorded boolean paths through the **admin UI**, not the CLI, so the plugin's own save path runs. Leave `renewals.sync_first_charge_mode=full` untouched.
3. In the same session, open **Member Access → Shop Access** and restore the exact pre-window rule state captured in
   `SLT-PROD-01-members-access-rules.json`: remove every SLT product exclusion added by this run without
   changing any other rule field. Re-fetch the raw option and require an empty `jq -S` diff over
   `{enabled: .members_access.enabled, ecommerce_rules: .members_access.ecommerce_rules}`.
4. Diff to prove the settings restore is exact and complete: write the sorted post-restore value to `/home/server-manager/slt-evidence/SLT-SETUP-99A-settings-after-restore.json` and compare against the D0 baseline. The diff for the baseline keys must be empty. Inspect every Mailpit message newer than `RESTORE_PRE` and require none to be attributable to the settings/Shop Access saves; classify unrelated natural-watch mail rather than requiring the global latest ID to stay fixed.
5. Build the **cancel cohort** explicitly: for every SLT subscription, confirm every task referencing it is `done` and its evidence is on disk. List them on the registry.
6. Build the **keep-alive cohort** from `watch-schedule.md` rows D11 and D12 — every subscription those rows still assert on. List them on the registry too. The two lists must be disjoint and must together cover every SLT subscription.
7. Set `CANCEL_PRE=$(mailpit-agent latest-id)`, then cancel **only** the cancel cohort, through the admin UI, one at a time. Record the exact subscription ID and click timestamp for every cancellation.
8. Inspect **every** Mailpit message newer than `CANCEL_PRE` (paginate the localhost Mailpit API if the helper's default list is shorter than the full delta). For every cancelled subscription require exactly one customer and one admin cancellation message naming that exact ID; count and save/show all linked IDs, classify unrelated mail, and prove no keep-alive subscription appears. Publish this expected teardown delta for the D11 watch.
9. Consolidate evidence: confirm every task's screenshots and dumps are under `/home/server-manager/slt-evidence/`, and export this D10 registry snapshot to `/home/server-manager/slt-evidence/SLT-SETUP-99A-registry-D10.md` (the registry itself stays live through 99B). Recount products/coupons/users/orders/subscriptions before/after, verify only intended statuses changed, close `admin-SLT-SETUP-99A`, independently review settings/cohorts/cancellations/mail/counts, then move through `review` to `done` with Review empty. Any defect goes only in `issues/SLT-SETUP-99A-<concise-slug>.md` with task/stage/plan path; affected setting/rule and artifact/subscription/action/message IDs; user IDs/logins/emails/roles; exact routes/session/timestamps; reproduction; expected/actual; and before/after JSON/UI/meta/queue/Mailpit/count proof.

## Expected results
1. Every baseline setting matches its D0 value; the `jq` diff over those keys is empty.
2. The captured Shop Access JSON is restored exactly; the full-store rule has no SLT exclusions.
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
- `SLT-SETUP-99A-settings-before-restore.json`, `SLT-SETUP-99A-settings-after-restore.json`, the post-restore `jq` diff, both cohort lists, `RESTORE_PRE`/`CANCEL_PRE`, the complete cancellation delta and all Mailpit IDs, and `SLT-SETUP-99A-registry-D10.md`.

## Pass criteria
- [ ] All baseline settings restored, diff empty
- [ ] Exact pre-window Shop Access JSON restored; zero SLT exclusions remain
- [ ] Cohorts published, disjoint, exhaustive
- [ ] Only the cancel cohort cancelled; keep-alive cohort still scheduled
- [ ] Zero deletions
- [ ] Cancellation mail IDs recorded for the D11 watch
- [ ] Exact admin session closed; standalone findings and independent review reach `done` with Review empty

## Isolation / teardown
- Hands the D11/D12 watch a live keep-alive cohort and an explicit list of expected cancellation mail. `SLT-SETUP-99B` does the actual deletion, on 2026-08-15.

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

[[2026-08-06]] Thu 21:38
Current evidence correction before D10 execution: do not inherit stale authored teardown rows for the missing ladder-switch chain. Task 72 / SLT-SW-00 closed `UNVERIFIED` without creating `SUB_BASIC` / `SUB_PRO`, so tasks 86, 95, and 111 are source-blocked and cards 107 / SLT-EML-08 plus 108 / SLT-EML-10 do not currently hand any real switch/downgrade subscriptions to this D10 cancel cohort. Also treat the authored `SLT-SYN-13` variation fixtures and the authored `SUB_FAIL_RECOVERY` dunning branch as registry-conditional only unless the live August 12, 2026 registry disproves task 46's and task 102's appended closeout notes.
