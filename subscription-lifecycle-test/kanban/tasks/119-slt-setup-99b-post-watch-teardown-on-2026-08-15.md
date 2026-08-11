---
id: 119
title: SLT-SETUP-99B Post-watch teardown on 2026-08-15 — delete every SLT artifact
status: todo
priority: high
created: 2026-08-02T03:43:12.818482128+02:00
updated: 2026-08-11T22:43:39+02:00
tags:
    - setup
    - day-13
due: "2026-08-15"
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
    - 118
class: standard
---

> **SLT-SETUP-99B** · group `foundation` · scheduled **D13** (2026-08-15)

> Read `README.md` (environment + isolation contract), `calendar.md` (this day's exact
> ordering — it is binding, not advisory) and `plan-audit.md` before starting.
## Objective
Remove every artifact this plan created, returning the shared staging site to its pre-plan state. Runs **2026-08-15**, strictly after the D12 watch report and `SLT-EML-14`'s final delta sweep are written.

> **The execution date is 2026-08-15.** Running this on 2026-08-13 or 2026-08-14 destroys the last renewals of the window and invalidates the watch.

## Scope
- Gateway: N/A
- Checkout: N/A
- Account: admin only
- Plugins: both

## Preconditions
- `SLT-SETUP-99A` closed: settings already restored, evidence already consolidated.
- The D12 watch report (`watch-reports/D12-2026-08-14.md`) exists and is signed off.
- `SLT-EML-14`'s 2026-08-14 delta sweep is complete.
- **Every non-teardown SLT task on the board is `done`.** If any task is `todo`, `in-progress`, `review`, or `blocked`, STOP — deleting its fixtures makes it unrunnable. This run does not use waivers for unfinished work.

## Test data
| Item | Value |
|---|---|
| Delete | only the exact registry IDs created by this run: SLT products/coupons/users/orders/subscriptions plus the classic cart, classic checkout, and registry pages |
| Preserve | **everything else on the site**, without exception |

## Steps
1. Record `M0=$(/usr/local/bin/mailpit-agent latest-id 2>/dev/null || true)`, then cancel the tail cohort that `SLT-SETUP-99A` deliberately kept alive. Build the list exclusively from 99A's published keep-alive registry and require one numeric subscription ID for every member; an authored alias is never enough. The expected live set includes `SUB_W1`, `SUB_W3`, `SUB_2SEG` when retained per task 106, Sync Global Daily, the two `SLT-SYN-12` probes, `SLT-SYN-14`'s quantity subscription, both primary lifetime controls, and both flex-month subscriptions (`SUB_M`, `SUB_S3`) when present. Include `SUB_W`, Box Daily, any `SLT-SYN-11` probe, or either `SLT-SYN-13` variation only when the live registry proves a numeric fixture exists despite source tasks #45/#65/#75/#46 closing `UNVERIFIED`; otherwise omit that branch without failing teardown. Inspect every message after `M0` (paginate the localhost Mailpit API if needed), reconcile each cancellation message to one exact numeric tail subscription, and prove no subscription outside the published tail appears. Then record `M1=$(/usr/local/bin/mailpit-agent latest-id 2>/dev/null || true)` as the deletion-step baseline.
2. Export the final registry, exact deletion-ID allowlists, and a full pre-deletion inventory to `/home/server-manager/slt-evidence/SLT-SETUP-99B-registry-final.md`, `/home/server-manager/slt-evidence/SLT-SETUP-99B-delete-allowlists.json`, and `/home/server-manager/slt-evidence/SLT-SETUP-99B-inventory-before.txt`. Split counts and IDs into SLT and non-SLT sets.
3. In isolated `admin-SLT-SETUP-99B`, open Tools → Scheduled Actions and search each exact SLT subscription ID from the allowlist. Cancel one verified pending row at a time by exact action ID after checking args; never run or drain a hook/group. Capture the non-SLT pending-action set before/after and require an empty diff.
4. Before deleting anything, prove ownership is closed under the allowlists. For every allowlisted `slt-*` user, enumerate all authored posts/pages, HPOS orders, subscriptions, comments, and other discoverable WordPress content. Every owned object must either appear in the exact SLT deletion allowlist or be absent. If any non-SLT or unallowlisted object is owned by one of these users, STOP before deletion, preserve all live artifacts, and record the exact user/object IDs in a standalone issue file; never reassign or delete that object.
5. Delete only allowlisted IDs, one verified artifact at a time, in dependency order: subscriptions → orders → products → coupons → pages → users. Reassign nothing. Prefix searches are discovery aids only and must never become bulk-delete selections.
6. Take the post-deletion inventory. Compare it with both (a) the exact non-SLT pre-deletion ID manifests from step 2 and (b) the plan-start reference counts in `README.md` (64 products, 354 subscriptions, 437 orders, 8 coupons). The ID-manifest diff is the pass/fail isolation assertion; the static counts are a drift reference and must never be forced by deleting unrelated data.
7. Confirm zero orphans: no `arraysubs_data` post referencing a deleted product or user, no scheduled action referencing a deleted subscription, no `wp_wc_orders` row referencing a deleted customer.
8. Verify the non-SLT ID manifests and counts are unchanged from the pre-deletion snapshot, and that the exact D0 pre-existing active-subscription ID set (record its live size rather than hard-coding 13) is still active/scheduled. Any unrelated drift is reported/excluded by ID; never delete to force a count.
9. Reconfirm the pre-window Shop Access snapshot still matches exactly and contains no deleted allowlisted IDs. Inspect every Mailpit message newer than `M1` and require none attributable to steps 2–9; classify unrelated mail. Close `admin-SLT-SETUP-99B`, independently review allowlist closure/deletion/non-SLT/orphan/action/mail evidence, move this terminal card through `review` to `done`, and require the entire board to have `review=0`, `blocked=0`, `todo=0`, `in-progress=0`. Any failure goes only in `issues/SLT-SETUP-99B-<concise-slug>.md` with task/stage/plan path; all affected allowlisted/unallowlisted IDs; users/logins/emails/roles; exact routes/session/timestamps; reproduction; expected/actual; and inventories/ownership/action/orphan/Mailpit proof. If ownership closure or an allowlist check fails, make no deletion and close the card only after a safe evidenced retry succeeds.

## Expected results
1. Zero plan-allowlisted products, coupons, users, subscriptions, orders, or pages remain. Any unallowlisted object that happens to match an `SLT`/`slt-` discovery prefix is preserved and reported by exact ID rather than deleted.
2. Every allowlisted user passes the ownership-closure preflight; no non-SLT or unallowlisted content is reassigned or deleted.
3. The exact non-SLT pre/post ID manifests are unchanged. If unrelated activity changed totals since plan start, that drift is listed by ID; if there was no external drift, totals return to 64 products, 354 subscriptions, 437 orders, and 8 coupons.
4. No orphaned scheduled actions, subscriptions, or orders.
5. The 13 pre-existing active subscriptions are untouched and still have future renewal legs queued.

## Emails expected
| # | Email | Trigger point | Recipient | Subject contains | Verify with |
|---|---|---|---|---|---|
| 1 | `subscription_cancelled` × tail cohort | step 1 cancellations | each customer | `cancelled` | compare every message after `M0` with the exact tail list |
| 2 | `admin_subscription_cancelled` × tail cohort | step 1 cancellations | admin | `cancelled by` | compare every message after `M0` with the exact tail list |
| 3 | NONE EXPECTED | steps 2-9 | — | — | no message attributable to inventory, action cancellation, ownership checks, deletion, or verification in the complete `M1` delta; unrelated mail classified |

## Evidence to capture
- `SLT-SETUP-99B-registry-final.md`, delete allowlists, per-user ownership-closure results, inventory before/after files, the baseline and non-SLT ID diffs, the orphan checks, exact cancelled action IDs, and `M0`/`M1` plus every tail-cancellation Mailpit ID from step 1.

## Pass criteria
- [ ] Every plan-allowlisted SLT artifact deleted; every unallowlisted prefix match preserved and reported
- [ ] Every allowlisted user passed the ownership-closure preflight; no unrelated object reassigned or deleted
- [ ] Exact non-SLT pre/post ID manifests unchanged; plan-start count drift, if any, explained by preserved unrelated IDs
- [ ] Zero orphaned actions, subscriptions, or orders
- [ ] Pre-existing active subscriptions untouched and still scheduled
- [ ] No mail sent by the deletion steps themselves
- [ ] Exact admin session closed; terminal review leaves every board column empty except `done`

## Isolation / teardown
- Terminal task. After it, the plan directory (board, reports, issues, evidence) is the only trace the run leaves — and that is intentional and kept.

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
Current evidence correction before D13 teardown: rebuild the real tail only from 118's published keep-alive cohort plus the live registry. Task 106 still requires `SUB_2SEG` to survive past D10 when 118 publishes it. Tasks 45, 65, 75, and 46 closed without `SUB_W`, Box Daily, the three `SLT-SYN-11` probes, or the two `SLT-SYN-13` variations, so each of those branches is conditional-only and must be omitted unless a later valid execution publishes its numeric subscription ID and dates. Separately, do not add ladder-switch or auto-downgrade artifacts unless a later valid execution recreates task 72's missing `SUB_BASIC` / `SUB_PRO` chain and 118 publishes those live fixtures explicitly.
