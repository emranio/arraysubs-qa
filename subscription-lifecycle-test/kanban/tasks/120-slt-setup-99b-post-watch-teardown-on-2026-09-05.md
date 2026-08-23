---
id: 120
title: SLT-SETUP-99B Post-watch teardown on 2026-09-05 — delete every SLT2 artifact
status: todo
priority: high
created: 2026-08-22T19:10:00+02:00
updated: 2026-08-22T19:10:00+02:00
tags:
    - cycle-2
    - granular
    - setup
    - day-13
due: "2026-09-05"
estimate: 1h30m
depends_on:
    - 1
    - 2
    - 3
    - 4
    - 5
    - 6
    - 7
    - 8
    - 9
    - 10
    - 11
    - 12
    - 13
    - 14
    - 15
    - 16
    - 17
    - 18
    - 19
    - 20
    - 21
    - 22
    - 23
    - 24
    - 25
    - 26
    - 27
    - 28
    - 29
    - 30
    - 31
    - 32
    - 33
    - 34
    - 35
    - 36
    - 37
    - 38
    - 39
    - 40
    - 41
    - 42
    - 43
    - 44
    - 45
    - 46
    - 47
    - 48
    - 49
    - 50
    - 51
    - 52
    - 53
    - 54
    - 55
    - 56
    - 57
    - 58
    - 59
    - 60
    - 61
    - 62
    - 63
    - 64
    - 65
    - 66
    - 67
    - 68
    - 69
    - 70
    - 71
    - 72
    - 73
    - 74
    - 75
    - 76
    - 77
    - 78
    - 79
    - 80
    - 81
    - 82
    - 83
    - 84
    - 85
    - 86
    - 87
    - 88
    - 89
    - 90
    - 91
    - 92
    - 93
    - 94
    - 95
    - 96
    - 97
    - 98
    - 99
    - 100
    - 101
    - 102
    - 103
    - 104
    - 105
    - 106
    - 107
    - 108
    - 109
    - 110
    - 111
    - 112
    - 113
    - 114
    - 115
    - 116
    - 117
    - 118
    - 119
    - 121
    - 122
    - 123
    - 124
    - 125
    - 126
    - 127
    - 128
    - 129
    - 130
    - 131
    - 132
    - 133
class: standard
---

> **SLT-SETUP-99B** · group `foundation` · scheduled **D13** (2026-09-05)

> Read `README.md` (environment + isolation contract), `calendar.md` (this day's exact
> ordering — it is binding, not advisory) and `plan-audit.md` before starting.
## Objective
Remove every artifact this plan created, returning the shared staging site to its pre-plan state. Runs **2026-09-05**, strictly after the D12 watch report and `SLT-EML-14`'s final delta sweep are written.

> **The execution date is 2026-09-05.** Running this on 2026-09-03 or 2026-09-04 destroys the last renewals of the window and invalidates the watch.

## Scope
- Gateway: N/A
- Checkout: N/A
- Account: admin only
- Plugins: both

## Preconditions
- `SLT-SETUP-99A` closed: settings already restored, evidence already consolidated.
- The D12 watch report (`watch-reports/D12-2026-09-04.md`) exists and is signed off.
- `SLT-EML-14`'s 2026-09-04 delta sweep is complete.
- **Every non-teardown SLT2 task on the board is `done`.** If any task is `todo`, `in-progress`, `review`, or `blocked`, STOP — deleting its fixtures makes it unrunnable. This run does not use waivers for unfinished work.

## Test data
| Item | Value |
|---|---|
| Delete | only the exact registry IDs created by this run: SLT2 products/coupons/users/orders/subscriptions plus the classic cart, classic checkout, and registry pages |
| Preserve | **everything else on the site**, without exception |

## Steps
1. Record `M0=$(/usr/local/bin/mailpit-agent latest-id 2>/dev/null || true)`, then cancel the exact tail cohort that `SLT-SETUP-99A` deliberately kept alive. Build the list only from the fresh D11 registry and signed D12 watch report; do not use a historical fixture list or infer by prefix. Reconcile every cancellation message to one exact registered subscription and prove no subscription outside that list appears. Then record `M1=$(/usr/local/bin/mailpit-agent latest-id 2>/dev/null || true)` as the deletion-step baseline.
2. Export the final registry, exact deletion-ID allowlists, and a full pre-deletion inventory to `/home/server-manager/slt-evidence/SLT-SETUP-99B-registry-final.md`, `/home/server-manager/slt-evidence/SLT-SETUP-99B-delete-allowlists.json`, and `/home/server-manager/slt-evidence/SLT-SETUP-99B-inventory-before.txt`. Split counts and IDs into SLT2 and non-SLT2 sets.
3. In isolated `admin-SLT-SETUP-99B`, open Tools → Scheduled Actions and search each exact SLT2 subscription ID from the allowlist. Cancel one verified pending row at a time by exact action ID after checking args; never run or drain a hook/group. Capture the non-SLT2 pending-action set before/after and require an empty diff.
4. Before deleting anything, prove ownership is closed under the allowlists. For every allowlisted `slt2-*` user, enumerate all authored posts/pages, HPOS orders, subscriptions, comments, and other discoverable WordPress content. Every owned object must either appear in the exact SLT2 deletion allowlist or be absent. If any non-SLT2 or unallowlisted object is owned by one of these users, STOP before deletion, preserve all live artifacts, and record the exact user/object IDs in a QA issue card; never reassign or delete that object.
5. Delete only allowlisted IDs, one verified artifact at a time, in dependency order: subscriptions → orders → products → coupons → pages → users. Reassign nothing. Prefix searches are discovery aids only and must never become bulk-delete selections.
6. Take the post-deletion inventory. Compare it with the exact D0 and pre-deletion non-SLT2 ID manifests. The ID-manifest diff is the pass/fail isolation assertion; no historical hard-coded count may be used or forced by deleting unrelated data.
7. Confirm zero orphans: no `arraysubs_data` post referencing a deleted product or user, no scheduled action referencing a deleted subscription, no `wp_wc_orders` row referencing a deleted customer.
8. Verify the non-SLT2 ID manifests and counts are unchanged from the pre-deletion snapshot, and that the exact D0 pre-existing active-subscription ID set is still active/scheduled. Any unrelated drift is reported/excluded by ID; never delete to force a count.
9. Reconfirm the pre-window Shop Access snapshot still matches exactly and contains no deleted allowlisted IDs. Inspect every Mailpit message newer than `M1` and require none attributable to steps 2–9; classify unrelated mail. Close `admin-SLT-SETUP-99B`, independently review allowlist closure/deletion/non-SLT/orphan/action/mail evidence, move this terminal card through `review` to `done`, and require the entire board to have `review=0`, `blocked=0`, `todo=0`, `in-progress=0`. Any failure goes only in `qa/issues/` kanban card named `SLT-SETUP-99B-<concise-slug>` with task/stage/plan path; all affected allowlisted/unallowlisted IDs; users/logins/emails/roles; exact routes/session/timestamps; reproduction; expected/actual; and inventories/ownership/action/orphan/Mailpit proof. If ownership closure or an allowlist check fails, make no deletion and close the card only after a safe evidenced retry succeeds.

## Expected results
1. Zero plan-allowlisted products, coupons, users, subscriptions, orders, or pages remain. Any unallowlisted object that happens to match an `SLT`/`slt2-` discovery prefix is preserved and reported by exact ID rather than deleted.
2. Every allowlisted user passes the ownership-closure preflight; no non-SLT2 or unallowlisted content is reassigned or deleted.
3. The exact non-SLT2 pre/post ID manifests are unchanged. If unrelated activity changed totals since plan start, that drift is listed and preserved by exact ID.
4. No orphaned scheduled actions, subscriptions, or orders.
5. Every subscription in the fresh D0 pre-existing active set is untouched and still has the expected future renewal legs queued.

## Emails expected
| # | Email | Trigger point | Recipient | Subject contains | Verify with |
|---|---|---|---|---|---|
| 1 | `subscription_cancelled` × tail cohort | step 1 cancellations | each customer | `cancelled` | compare every message after `M0` with the exact tail list |
| 2 | `admin_subscription_cancelled` × tail cohort | step 1 cancellations | admin | `cancelled by` | compare every message after `M0` with the exact tail list |
| 3 | NONE EXPECTED | steps 2-9 | — | — | no message attributable to inventory, action cancellation, ownership checks, deletion, or verification in the complete `M1` delta; unrelated mail classified |

## Evidence to capture
- `SLT-SETUP-99B-registry-final.md`, delete allowlists, per-user ownership-closure results, inventory before/after files, the baseline and non-SLT2 ID diffs, the orphan checks, exact cancelled action IDs, and `M0`/`M1` plus every tail-cancellation Mailpit ID from step 1.

## Pass criteria
- [ ] Every plan-allowlisted SLT2 artifact deleted; every unallowlisted prefix match preserved and reported
- [ ] Every allowlisted user passed the ownership-closure preflight; no unrelated object reassigned or deleted
- [ ] Exact non-SLT2 pre/post ID manifests unchanged; plan-start count drift, if any, explained by preserved unrelated IDs
- [ ] Zero orphaned actions, subscriptions, or orders
- [ ] Pre-existing active subscriptions untouched and still scheduled
- [ ] No mail sent by the deletion steps themselves
- [ ] Exact admin session closed; terminal review leaves every board column empty except `done`

## Isolation / teardown
- Terminal task. After it, the plan directory (board, reports, issues, evidence) is the only trace the run leaves — and that is intentional and kept.

---

### Fresh-cycle validation contract

- Re-derive every ID, count, option value, gateway capability, scheduler timestamp, and email baseline on this run; no prior-cycle result is evidence.
- Create and mutate only registered `SLT2 ` / `slt2-*` fixtures. Legacy `SLT` and all non-SLT2 data are read-only controls.
- Automatic-gateway scope is Stripe and Paddle only. Stripe is the primary path; run Paddle parity wherever Paddle supports the behavior. Do not test or configure PayPal or Mollie.
- ArraySubs core must own its Stripe/Paddle integration, renewal, retry, webhook, REST, refund, and customer-payment services with Pro inactive; vendor host classes retain their expected ownership.
- Browser-required assertions use Vercel `agent-browser` with isolated task/role sessions and current snapshot refs. WP-CLI always includes `--allow-root`.
- Update the lifecycle card, the matching `qa/progress/` card, and `qa/issues/` for every new regression. Evidence belongs to this fresh cycle only.
