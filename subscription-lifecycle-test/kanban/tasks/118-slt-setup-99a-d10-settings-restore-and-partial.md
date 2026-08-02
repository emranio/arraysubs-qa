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
- Runs on D10 (2026-08-12) **after** the morning watch report is written, and **after** `SLT-IMP-05` and `SLT-DUN-05` are closed.
- The D0 baseline values recorded by `SLT-SETUP-02` are on the registry page. If they are not, STOP — restoring from memory is not acceptable.

## Test data
| Item | Value |
|---|---|
| Settings to restore | `customer_actions.allow_early_renew` → `false`, `customer_actions.allow_reactivation` → `false`, `pause_subscription.enabled` → `false`, plus any other baseline flag SLT-SETUP-02 recorded |
| Cancel cohort | subscriptions whose tasks are all closed and whose evidence is captured |
| Keep-alive cohort | everything the D11/D12 watch still needs |

## Steps
1. Read the D0 baseline block from `slt-catalog-registry`. Save the current settings first:
   `wp option get arraysubs_settings --format=json --allow-root > evidence/D10/settings-before-restore.json`
2. Restore each recorded flag through the **admin UI**, not the CLI, so the plugin's own save path runs.
3. Diff to prove the restore is exact and complete:
   `wp option get arraysubs_settings --format=json --allow-root | jq -S . > after.json` and compare against the D0 baseline. The diff for the baseline keys must be empty.
4. Build the **cancel cohort** explicitly: for every SLT subscription, confirm every task referencing it is `done` and its evidence is on disk. List them on the registry.
5. Build the **keep-alive cohort** from `watch-schedule.md` rows D11 and D12 — every subscription those rows still assert on. List them on the registry too. The two lists must be disjoint and must together cover every SLT subscription.
6. Cancel **only** the cancel cohort, through the admin UI, one at a time.
7. Count the cancellation emails produced and record their Mailpit IDs — they are an expected teardown side effect, and the D11 watch must not mistake them for a bug.
8. Consolidate evidence: confirm every task's screenshots and dumps are under `evidence/`, and export the registry page to `evidence/registry-final.md`.

## Expected results
1. Every baseline setting matches its D0 value; the `jq` diff over those keys is empty.
2. The cancel and keep-alive cohorts are published, disjoint, and jointly exhaustive.
3. Only cancel-cohort subscriptions are `arraysubs-cancelled`; every keep-alive subscription is untouched and still scheduled.
4. **Nothing is deleted.** Product, coupon, user, order and subscription counts are unchanged apart from the status flips.
5. Cancellation email IDs are recorded for the D11 watch.

## Emails expected
| # | Email | Trigger point | Recipient | Subject contains | Verify with |
|---|---|---|---|---|---|
| 1 | `subscription_cancelled` × cohort size | each cancellation | each subscription's customer | `cancelled` | `mailpit-agent list 60` |
| 2 | `admin_subscription_cancelled` × cohort size | same | admin | `cancelled` | `list 60` |
| 3 | NONE EXPECTED | settings restore | — | — | restoring settings must send nothing |

## Evidence to capture
- `settings-before-restore.json`, the post-restore `jq` diff, both cohort lists, all cancellation Mailpit IDs, `registry-final.md`.

## Pass criteria
- [ ] All baseline settings restored, diff empty
- [ ] Cohorts published, disjoint, exhaustive
- [ ] Only the cancel cohort cancelled; keep-alive cohort still scheduled
- [ ] Zero deletions
- [ ] Cancellation mail IDs recorded for the D11 watch

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
  fire on their own; **a renewal that does not fire is a real bug** — capture evidence before forcing.
- Give this task its own browser session (`agent-browser --session <role>-<TASK-KEY>`). Sessions are
  keyed by name and **share a cart**.
- Never run `wp action-scheduler run` without `--hooks=`; prefer a single action by ID.
- Evidence goes in `qa/subscription-lifecycle-test/evidence/<TASK-KEY>/`.
