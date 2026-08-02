---
id: 117
title: SLT-SETUP-99 Restore baseline settings and delete every SLT artifact
status: todo
priority: critical
created: 2026-08-02T03:43:12.681504954+02:00
updated: 2026-08-02T03:43:24.376764572+02:00
tags:
    - setup
    - day-10
    - has-conflicts
due: "2026-08-12"
estimate: 2h
depends_on:
    - 10
    - 11
    - 12
    - 25
    - 26
    - 5
    - 37
    - 38
    - 58
    - 20
    - 6
    - 7
    - 71
    - 39
    - 59
    - 60
    - 21
    - 8
    - 22
    - 40
    - 23
class: standard
---

> **SLT-SETUP-99** · group `foundation` · scheduled **D10** (2026-08-12)

> Read `README.md` (environment + isolation contract), `calendar.md` (this day's exact
> ordering — it is binding, not advisory) and `plan-audit.md` before starting.

### ⚠ Conflict resolutions that apply to this task

**`unrated` · impossible-timing** — with `SLT-PROD-14`, `SLT-SYN-04`, `SLT-PROD-15`, `SLT-PROD-10`

- *Problem:* SLT-SETUP-99 is scheduled on d10 (2026-08-11) and cancels + permanently deletes every SLT subscription, order, product and user, but the automated renewal watch runs to D12 (2026-08-13) and several subscriptions have renewals due after D10: SLT Flex Daily Two Seg and SLT Flex Daily Next Cycle renew 2026-08-11, the SLT Flex Variable Daily Full/Next Cycle variations renew 2026-08-12, the SLT-SYN-04 globally-synced day/3 subscription renews 2026-08-13, and SLT Box Daily renews 2026-08-11. Any dunning ladder started on D8-D10 also cancels at +3 days, i.e. 2026-08-11..08-13. Deleting on D10 destroys exactly the tail evidence D11 and D12 exist to collect. The task's own precondition notices the clash and then leaves it to the operator.
- *Required fix:* Split SLT-SETUP-99 into two tasks. SLT-SETUP-99A (D10, 2026-08-11): Part 1 settings restore + jq diff, plus cancel ONLY the subscriptions whose evidence is complete (all day/1 workhorses: SLT Daily Core, SLT Signup Fee Daily, SLT Renewal Price Step, SLT Paddle Daily, the plan-ladder rungs, SLT Free Signup Daily, SLT Trial Four Day, SLT Variable Daily tiers) so D11/D12 are not polluted by daily-renewal noise. SLT-SETUP-99B (2026-08-13, after the D12 watch check has been captured): Parts 2-4, cancel the remaining tail cohort and delete all artifacts. Settings restore is safe on D10 because it only affects NEW subscriptions.

**`unrated` · shared-global-setting** — with `SLT-PROD-12`, `SLT-PROD-13`, `SLT-PROD-15`, `SLT-PROD-11`, `SLT-SYN-04`

- *Problem:* The window-wide time-travel policy tells every task to advance time with `wp action-scheduler run --hooks=<hook> --force`. A bare hook drain is site-wide: it fires EVERY due pending action for that hook, including the 13 pre-existing non-SLT active subscriptions (which the isolation contract forbids touching) and every other SLT test's pending renewal invoice / renewal / hold / cancel / expire action. This is the single largest cross-contamination risk in the plan. Tasks that will necessarily drain: any renewal of SLT Flex Month Segments (next payment 2026-09-01 / 2026-10-01, unreachable naturally), the SLT Flex Week Segments segment-3 cohort (next payment 2026-08-15), the SLT Flex Variable Daily Next Cycle tail, the SLT-PROD-11 auto-downgrade case (requires a hand-set _end_date), and SLT-SETUP-99's wind-down. One broad drain on any of those days would prematurely fire the pending renewals of SLT Daily Core, SLT Retry Daily (destroying the 1-day/3-day grace ladder timing), SLT Fixed Three Cycles (destroying its 2026-08-07 expiry contract) and the Box.
- *Required fix:* Ban bare hook drains for the whole window. Mandatory procedure for every time-travel step: (1) screenshot wp-admin -> Tools -> Scheduled Actions filtered to Pending and record EVERY action due within the next 24h, aborting if any non-SLT action is due; (2) move only the target subscription's _next_payment_date and its paired schedule meta; (3) execute the single action by id from the Scheduled Actions screen (Run action) rather than by hook, or invoke the processor for one subscription id via `wp eval` passing that id explicitly; (4) if a hook drain is truly unavoidable, first cancel/park every other pending action for that hook from the Scheduled Actions UI, run the drain, then restore them, and record before/after _next_payment_date for all 13 pre-existing active subscriptions as proof they did not move. Confine all time-travel to D8 (2026-08-09), the single authorized drain day in the calendar.

**`unrated` · shared-global-setting** — with `SLT-SETUP-02`, `SLT-PROD-16`, `SLT-SYN-04`

- *Problem:* SLT-SETUP-02 flips five booleans ON for the whole window (allow_early_renew, allow_reactivation, pause_subscription.enabled, pause_subscription.customer_can_pause; plus sync OFF) and declares them frozen. Nothing in the plan republishes that baseline where a my-account or customer-action task will see it, so any later task auditing the my-account subscription screen against the site's shipped defaults will file Renew Early / Reactivate / Pause buttons as unexpected UI. The reverse trap also exists: cancellation.retention_offers_enabled has pause/skip OFF while the pause FEATURE is now ON, so the retention modal legitimately shows no pause offer even though pausing works - easy to misfile as a defect. SLT-PROD-16 already relies on the baseline being ON to assert Paddle's Renew Early button stays hidden.
- *Required fix:* SLT-SETUP-02 must append a 'WINDOW BASELINE (frozen)' table to slt-catalog-registry listing all five booleans with prior value / window value / restoring task, and every customer-facing audit task must quote that table in its preconditions instead of the shipped defaults. Add a pass criterion to SLT-SETUP-02: the registry table exists. SLT-SETUP-99A restores all five and proves it with the empty jq diff.

**`critical` · evidence-destruction / teardown vs watch window** — with `SLT-CHK-14`, `SLT-CHK-13`, `SLT-EML-14`, `SLT-SYN-09`, `SLT-SYN-13`, `SLT-SYN-12`

- *Problem:* SLT-SETUP-99 is authored as a single d10 task that cancels AND permanently deletes every SLT subscription, order, product, coupon, page and user. With D10 = 2026-08-12 and the watch running to D12 = 2026-08-14, that deletes exactly the evidence D11 and D12 exist to collect. Events after D10: SUB_W1 + SUB_W (both week flex subs) renew 2026-08-14 00:00 site - the last scheduled events in the whole window and SYN-09's 'second charge full on the boundary' proof; the SLT-SYN-04 globally-synced day/3 subscription renews 08-14; SLT-SYN-13's Full and Next Cycle variations renew 08-13; SLT-CHK-13's Box Daily renews 08-12; SLT-CHK-14's lifetime negative control must be asserted on all 12 watch days including 08-13 and 08-14 (its own isolation note wrongly says '99A/99B'); SLT-EML-14 step 9 mandates a delta sweep on the morning of 08-14 and explicitly states 99B must not run before it, because a cancellation mail would contaminate the silence proof.
- *Required fix:* Split, as audit C06 directs, with the dates shifted +1. SLT-SETUP-99A on D10 (2026-08-12), after that morning's watch read and after SLT-DUN-05's recovery evidence is closed: Part 1 settings restore (five booleans, empty jq diff) plus cancellation of the COMPLETED-EVIDENCE COHORT ONLY - the day/1 workhorses (SLT Daily Core spine and its clones, Signup Fee Daily, Renewal Price Step, Paddle Daily, plan-ladder rungs, Free Signup Daily, Trial Four Day, Variable tiers, all CPN and CHK day/1 subs, IMP-03 concurrency subs, DUN-05's S2). No deletions. SLT-SETUP-99B on 2026-08-15 (Sat), strictly after the D12 watch report and SLT-EML-14's 08-14 delta are written: cancel the TAIL COHORT (both week flex subs, Sync Global Daily, SYN-13's two variation subs, SYN-12's two probes, SYN-14's qty sub, Box Daily, the lifetime controls, the flex month subs) then Parts 2-4 deletion. Correct SLT-CHK-14's and SLT-CHK-13's isolation notes to name 99B only. Publish the two cohort lists to the registry on D9 so the watcher can assert on D11/D12 that every 99A-cancelled subscription shows no renewal after its cancellation timestamp.

**`medium` · action-scheduler policy / broad-fire risk** — with `SLT-LIFE-04`, `SLT-EML-01`, `SLT-EML-10`, `SLT-LIFE-01`, `SLT-ADM-05`

- *Problem:* No task in the index issues a bare `wp action-scheduler run --hooks=<hook> --force`, so the largest hazard the audit named is currently absent - but the 'D8 is the only authorized Action Scheduler day' rule is broken by tasks that legitimately need to run one action: SLT-LIFE-04 step 9 hand-schedules HOOK_SEND_EXPIRING_SOON and runs it by id on D3 (2026-08-05) - which is also SLT-SYN-04's exclusive bracket day; SLT-EML-01 step 8 queues a duplicate reminder action on D3 and lets wp-cron claim it; SLT-ADM-05/ADM-03 depend on cron claiming their legs on D3/D4. Residual broad-fire risks that DO exist: (a) SLT-LIFE-01 back-dates S5's legs and relies on the per-minute runner, whose batch will claim any other action already due in that same tick; (b) SLT-EML-10 schedules HOOK_SEND_EXPIRING_SOON at time()-60; (c) SLT-SETUP-99's step 7 cancels pending actions found by searching the Scheduled Actions screen, which can match non-SLT rows; (d) SLT-ADM-01's bulk 'Delete Permanently' path issues DELETE wp/v2/arraysubs_data/<id>?force=true per selected id with no onDeleteCheck guard - one accidental confirm force-deletes irrecoverably.
- *Required fix:* Refine the rule into three tiers and publish it in the README isolation contract. (1) BANNED on every day, no exceptions: any `wp action-scheduler run` without a specific action id, and any `--hooks=` drain. (2) PERMITTED on any day: running ONE action by id from Tools -> Scheduled Actions, and queueing a single-subscription action and letting the per-minute cron claim it - provided the task first screenshots the Pending queue for the next 60 minutes and aborts if any non-SLT action is due. (3) D8 ONLY: editing _next_payment_date / _end_date / _renewal_scheduled_date to move an event in time, always paired with the 13 non-SLT _next_payment_date before/after proof. Under this rule LIFE-04 step 9, EML-01 step 8, EML-10 and ADM-05/03 are legal where they are; LIFE-01 and SETUP-99 stay on D8/D10 with the pre-flight. For SETUP-99, replace 'search and cancel' with 'cancel by action id, taken from the per-subscription action-id metas recorded in the registry'. For SLT-ADM-01, keep the bulk dialog cancelled and file the missing-guard finding as a bug, as authored.

---
## Objective
Return the shared staging site to its 2026-08-01 state: restore the five baseline booleans to their recorded priors, cancel every SLT subscription and its scheduled actions, and permanently delete every SLT-prefixed product, coupon, page, user, order and subscription — while proving that no pre-existing artifact was touched.

## Scope
- Gateway: both
- Checkout: N/A
- Account: N/A
- Plugins: both

## Preconditions
- Every SLT execution and watch task is finished. Run this only on day 10 (2026-08-11) or later; the renewal watch runs to D12, so if watch tasks are still open, restore the settings (Part 1) and defer the deletions (Parts 2-4) until the watch closes — settings restoration is safe to do first because it only affects NEW subscriptions.
- `/home/server-manager/slt-evidence/SLT-SETUP-02-priors.txt` and `SLT-SETUP-01-arraysubs_settings-D0.json` exist.
- The `slt-catalog-registry` page holds every created ID.

## Test data
| Item | Value |
|---|---|
| Product | every product whose title starts `SLT ` |
| Account | every user whose login starts `slt-` (including checkout-created guests `slt-guest-d0@example.test`, `slt-guest-d5@example.test`) |
| Coupon | SLTPCT20REC, SLTFIX5FIRST, SLTREC3, SLTREC3NOINIT, SLTFEEPROBE, SLTNOSUB |
| Card | N/A |
| Amounts | N/A |

## Steps
**Part 1 — restore the window-wide baseline (do this first).**
1. `agent-browser --session admin open "https://mirror-help.arrayhash.com/wp-admin/admin.php?page=arraysubs-mainadmin#/settings/general"` -> `snapshot -i`.
2. Switch **Sync Renewals to Next Billing Cycle** back ON. Confirm the **First Charge** select reappears reading `Charge the full recurring amount` (stored value `full`); do not change it.
3. Switch **Allow Early Renew** back OFF and **Allow Reactivation** back OFF. Save.
4. `agent-browser --session admin open "https://mirror-help.arrayhash.com/wp-admin/admin.php?page=arraysubs-mainadmin#/settings/skip-pause"`; switch **Allow Customers to Pause** OFF, then **Enable Pause Subscription** OFF. Save.
5. Diff against D0: `wp option get arraysubs_settings --format=json --allow-root > /home/server-manager/slt-evidence/SLT-SETUP-99-arraysubs_settings-restored.json` then `diff <(jq -S . /home/server-manager/slt-evidence/SLT-SETUP-01-arraysubs_settings-D0.json) <(jq -S . /home/server-manager/slt-evidence/SLT-SETUP-99-arraysubs_settings-restored.json)`.

**Part 2 — wind down SLT subscriptions and their scheduled actions.**
6. `agent-browser --session admin open "https://mirror-help.arrayhash.com/wp-admin/edit.php?post_type=arraysubs_data"`; filter/search for the SLT customers; for each SLT subscription set the status to Cancelled from the admin edit screen. Cancel before deleting so gateway-side subscriptions (Stripe, Paddle) are closed rather than orphaned.
7. In wp-admin -> Tools -> Scheduled Actions, search each SLT subscription id and cancel any remaining pending `arraysubs_generate_renewal_invoice`, `arraysubs_process_renewal`, `arraysubs_hold_subscription`, `arraysubs_cancel_subscription`, `arraysubs_expire_subscription`, `arraysubs_send_renewal_reminder`, `arraysubs_send_expiring_soon`, `arraysubs_process_trial_conversion` action for it. Screenshot the empty result set afterwards.
8. Delete the SLT subscription posts (Trash then Empty Trash) using the ids from the registry ONLY. Never bulk-delete by status.

**Part 3 — delete SLT orders, products, coupons, pages, users.**
9. Orders: `agent-browser --session admin open "https://mirror-help.arrayhash.com/wp-admin/admin.php?page=wc-orders"`, filter by each SLT customer, move their orders to trash, then permanently delete. Do not touch any order belonging to a non-SLT customer.
10. Products: `https://mirror-help.arrayhash.com/wp-admin/edit.php?post_type=product&s=SLT` — verify every result is SLT-prefixed, then Move to Trash and Empty Trash. Include the Subscription Box, its three children, the grouped parent and `SLT Grouped Extra`, both variable parents (their variations delete with them) and all four plan rungs.
11. Coupons: `https://mirror-help.arrayhash.com/wp-admin/edit.php?post_type=shop_coupon&s=SLT` — trash and permanently delete the six SLT coupons only. The eight pre-existing coupons must remain.
12. Pages: delete `SLT Classic Checkout`, `SLT Classic Cart`, and `SLT Catalog Registry` (export the registry contents to `/home/server-manager/slt-evidence/SLT-SETUP-99-registry-final.md` first).
13. Users: `https://mirror-help.arrayhash.com/wp-admin/users.php?s=slt-` — delete every `slt-*` user, choosing **Delete all content** (their orders are already gone). Confirm `cust1` (5), `customer1` (32) and `sync-stripe` (319) are NOT in the selection.

**Part 4 — prove the site is back to baseline.**
14. `wp post list --post_type=product --format=count --allow-root` (expect 64), `wp post list --post_type=shop_order --format=count --allow-root` / the HPOS order count (expect 437), `wp post list --post_type=arraysubs_data --format=count --allow-root` (expect 354), `wp post list --post_type=shop_coupon --format=count --allow-root` (expect 8).
15. `wp user list --allow-root | grep -c slt-` (expect 0) and `wp post list --post_type=product --allow-root | grep -c SLT` (expect 0).
16. `mailpit-agent latest-id` — record the final id for the record; do NOT purge Mailpit, the captured messages are the window's evidence.

## Expected results
1. `renewals.sync_to_billing_cycle=true`, `renewals.sync_first_charge_mode="full"`, `customer_actions.allow_early_renew=false`, `customer_actions.allow_reactivation=false`, `pause_subscription.enabled=false`, `pause_subscription.customer_can_pause=false`.
2. The jq diff between the D0 dump and the restored dump is EMPTY. Any residual difference is reported verbatim, not silently accepted.
3. Zero pending Action Scheduler entries reference any SLT subscription id.
4. Product count is back to 64, subscription count 354, coupon count 8, order count 437.
5. No user matching `slt-` exists; users 5, 32 and 319 still exist with unchanged emails.
6. `SLT Classic Checkout`, `SLT Classic Cart` and `SLT Catalog Registry` pages no longer exist, and the registry has been exported to disk first.
7. Mailpit still holds the window's messages.

## Emails expected
| # | Email | Trigger point | Recipient | Subject contains | Verify with |
|---|---|---|---|---|---|
| 1 | Subscription cancelled | Each SLT subscription cancelled in step 6 | the SLT customer + admin | `has been cancelled` / `cancelled by` | `mailpit-agent list 50` — these are expected side effects of teardown, count them and record the ids |
| 2 | NONE EXPECTED beyond #1 | Product/coupon/page/user deletion | — | — | No new message ids after the last cancellation; verify with `mailpit-agent latest-id` before and after step 9 |

## Evidence to capture
- Screenshots: `SLT-SETUP-99-01-general-restored.png`, `SLT-SETUP-99-02-skip-pause-restored.png`, `SLT-SETUP-99-03-scheduled-actions-empty.png`, `SLT-SETUP-99-04-product-search-empty.png`, `SLT-SETUP-99-05-users-empty.png`.
- `SLT-SETUP-99-arraysubs_settings-restored.json`, the jq diff output (expected empty), `SLT-SETUP-99-registry-final.md`, all four count outputs.

## Pass criteria
- [ ] All five baseline booleans restored to the recorded priors
- [ ] jq diff against the D0 settings dump is empty
- [ ] All SLT subscriptions cancelled before deletion and no pending actions remain
- [ ] Counts back to 64 products / 354 subscriptions / 437 orders / 8 coupons
- [ ] Zero slt-* users; users 5, 32, 319 intact
- [ ] Registry exported to disk before its page was deleted
- [ ] Mailpit evidence preserved (not purged)

## Isolation / teardown
- This task leaves behind only `/home/server-manager/slt-evidence/` (screenshots, settings dumps, the exported registry) and the Mailpit message history. Both are intentionally kept as the window's audit trail.
- If any deletion cannot be completed (e.g. a Stripe or Paddle subscription refuses to cancel), STOP and record the blocker with ids rather than force-deleting the local post — an orphaned local record is recoverable, an orphaned live gateway subscription that keeps charging is not.

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
