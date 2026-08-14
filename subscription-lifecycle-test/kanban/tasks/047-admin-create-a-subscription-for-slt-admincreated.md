---
id: 47
title: Admin-create a subscription for slt-admincreated and prove it renews unassisted
status: done
priority: critical
created: 2026-08-02T03:43:06.852737686+02:00
updated: 2026-08-11T23:33:19.052370097+02:00
started: 2026-08-05T17:39:06.317073164+02:00
completed: 2026-08-05T17:55:28.795141505+02:00
tags:
    - admin
    - portal
    - day-03
due: "2026-08-05"
estimate: 1h30m
depends_on:
    - 10
    - 11
    - 12
    - 5
    - 61
class: standard
---

> **SLT-ADM-05** · group `admin` · scheduled **D03** (2026-08-05)

> Read `README.md` (environment + isolation contract), `calendar.md` (this day's exact
> ordering — it is binding, not advisory) and `plan-audit.md` before starting.

## Objective
Create a subscription from scratch in wp-admin — no checkout, no gateway — for `slt-admincreated`, prove what the create path omits, arm it so Action Scheduler owns it, and prove it renews unattended.

## Scope
- Gateway: N/A (no gateway — manual payment path)
- Checkout: N/A
- Account: admin-created
- Plugins: free-only

## Preconditions
- SLT-SETUP-01/02/03 and SLT-PROD-01 done; `slt-admincreated` has a billing address and never checks out.
- Act **after 12:00 site time**, clear of the D3 09:00–11:00 SLT-SYN-04 bracket.
- Code facts: the create form has **no Status field**, POSTs `wp/v2/arraysubs_data` as `arraysubs-pending` with no dates (`SubscriptionForm.jsx:449-481`), and `scheduleSubscriptionRenewal()` bails on an empty date (`OrderIntegration.php:1732-1740`).

## Test data
| Item | Value |
|---|---|
| Product | SLT Daily Core, day/1, $10.00 |
| Account | slt-admincreated / slt-admincreated@example.test |
| Session | `--session admin-SLT-ADM-05`, no card/coupon |

## Steps
1. Set `M0=$(mailpit-agent latest-id)`. In `admin-SLT-ADM-05`, open `.../admin.php?page=arraysubs-mainadmin#/subscriptions/form`, `snapshot -i`, and capture `SLT-ADM-05-01-form.png` before submission.
2. **Customer**: type `slt-admincreated` and select the match.
3. Set **Subscription Product** `SLT Daily Core`, **Quantity** 1, **Recurring Amount** `10.00`, **Billing Interval** `1`, **Billing Period** `Day(s)`, **Subscription Length**/**Signup Fee**/**Trial Length** `0`, **Invoice Email** `slt-admincreated@example.test`, billing address per SLT-SETUP-03.
4. **Create Subscription**; capture the `Subscription created successfully!` toast as `SLT-ADM-05-02-toast.png`. Record the exact numeric ID under alias **SUB-A**, assign it to shell variable `SUB_A`, and abort unless `[[ "$SUB_A" =~ ^[0-9]+$ ]]`.
5. Probe (repeat at steps 7 and 8): `wp post get "$SUB_A" --field=post_status --allow-root`, `wp post meta list "$SUB_A" --keys=_next_payment_date,_start_date,_renewal_action_id,_renewal_invoice_action_id --allow-root`; open `admin.php?page=wc-status&tab=action-scheduler&status=pending&s=$SUB_A` and capture the initial empty queue as `SLT-ADM-05-03-queue-empty-pending.png`.
6. `#/subscriptions/edit/$SUB_A` → `Change to...` = **Active** → **Change Status** → confirm.
7. `mailpit-agent wait-new "$M0" 120 "is active"`, then re-run the probe and capture the post-activation queue. **Plan correction (2026-08-05):** the old “active queue stays empty” expectation is obsolete on this runtime. Pending→Active now sends the two activation mails and immediately arms reminder/invoice/renewal actions. The live D3 defect instead is that this daily subscription arms on a one-month cadence; record that under `issues/done-critical-plugin-SLT-ADM-05-admin-created-daily-subscription-arms-at-one-month.md` with the exact task/plan path, `SUB_A`/user/product/admin route, reproduction steps, expected/actual result, meta/queue/mail/screenshots, and scope notes. Do not file the obsolete unscheduled-active issue.
8. Arming recipe: set `HOLD_PRE=$(mailpit-agent latest-id)`, change status to **On Hold**, and `mailpit-agent wait-new "$HOLD_PRE" 120 "on hold"`; then set `REACT_PRE=$(mailpit-agent latest-id)`, change status back to **Active**, and `mailpit-agent wait-new "$REACT_PRE" 120 "reactivated"`; re-run the probe and capture `SLT-ADM-05-05-queue-two-legs.png`.
9. Compute **k** from numeric `$SUB_A` with the README argv-based crc32 command. Record both exact action IDs/GMT values and publish their `invoice−5m`/`charge−5m` deadlines to the registry and D03 watch report. No earlier than five minutes before the invoice leg, publish `ADM05_RENEW_PRE=$(mailpit-agent latest-id)` with SUB-A's numeric ID, D, k and both gates. Run no `wp action-scheduler` command; close the session and keep this card `in-progress`.
10. **Follow-up, watch day D4 (2026-08-06), only after the exact registered charge gate:** reopen `admin-SLT-ADM-05`, confirm the unattended renewal (result 5), inspect the complete Mailpit delta after `ADM05_RENEW_PRE`, and require the exact invoice message for SUB-A while classifying unrelated shared-site mail. Resolve the numeric renewal order through exact `_subscription_id=$SUB_A` plus scheduled-date/cycle metadata, cross-check the reverse relationship, and capture `SLT-ADM-05-06-renewal-order.png`; never use recency. Close the session, independently review the D3/D4 evidence and issue file, and move this card through review to done. The subscription is created on D3, so a D3 renewal assertion is impossible.

## Expected results
1. SUB-A: `post_status = arraysubs-pending`, title `Subscription #<epoch-ms>`, `_customer_id` = slt-admincreated, `_recurring_amount=10`, `_billing_period=day`.
2. At creation on this runtime: `post_status=arraysubs-pending`, `_next_payment_date` is already seeded, action-id metas are absent, and the pending queue is still empty. Verify the exact seeded timestamp live.
3. After pending→Active on this runtime: status becomes `arraysubs-active`, the customer/admin activation mails are sent, and reminder/invoice/renewal actions arm immediately. The old “active stays unscheduled” bug path is obsolete and must not be re-filed from this task.
4. Live D3 defect: despite `SLT Daily Core` remaining day/1 in the saved metadata, the first seeded/armed next-payment schedule lands one month later. Record that under the dedicated issue file and treat the authored D4 follow-up date as stale unless a later task rewrites this card around the new schedule.
5. Any later follow-up must key off the exact live action timestamps captured from `SUB_A`, not the authored D4 assumption.

## Emails expected
| # | Email | Trigger point | Recipient | Subject contains | Verify with |
|---|---|---|---|---|---|
| 1 | new_subscription + admin_new_subscription | step 6 | customer + admin | `is active` / `New subscription #SUB-A` | `mailpit-agent wait-new "$M0" 120 "active"` |
| 2 | subscription_on_hold | step 8 →On Hold | slt-admincreated | `is on hold` | `mailpit-agent wait-new "$HOLD_PRE" 120 "on hold"` |
| 3 | subscription_reactivated | step 8 →Active | slt-admincreated | `has been reactivated` | `mailpit-agent wait-new "$REACT_PRE" 120 "reactivated"` |
| 4 | renewal_invoice | invoice leg | slt-admincreated | `Invoice for subscription #SUB-A` | watch D4 complete delta after `ADM05_RENEW_PRE`; save/show exact matched id |
| 5 | NONE EXPECTED at creation | step 4 | — | — | Complete delta after `M0` through step 4; zero creation-attributable mail, while unrelated/background mail is allowed and classified |

## Evidence to capture
- Screenshots `SLT-ADM-05-01-form.png`, `-02-toast.png`, `-03-queue-empty-pending.png`, `-04-queue-empty-active.png`, `-05-queue-two-legs.png`, `-06-renewal-order.png`; SUB-A id, probe outputs, k, both action IDs/GMT values and deadlines, `ADM05_RENEW_PRE`, exact Mailpit IDs, relationship-linked renewal order ID.

## Pass criteria
- [ ] D3 live proof recorded: creation leaves the queue empty, pending→active arms actions immediately, and the dedicated one-month scheduling issue is filed with exact evidence
- [ ] Any follow-up keys off the exact live action timestamps captured from `SUB_A`, not the obsolete D4 assumption
- [ ] Card is not left stranded in-progress on the stale unscheduled-active path

## Isolation / teardown
- Hands SUB-A and the arming recipe (pending→Active inert; On Hold→Active arms) to SLT-ADM-02/03.
- Expected tail, not a bug: the unpaid renewal order drives SUB-A to on-hold ~1 day later and cancelled ~3 days after. Record it; do not intervene.
- No setting changed; close only `admin-SLT-ADM-05` after each dated leg. SUB-A is deleted by SLT-SETUP-99B.


---

## D3 checkpoint — 2026-08-05

- `M0=2NWHnHe5PRDFyk9da17atS`; created subscription `SUB_A=12760` for user `353` (`slt-admincreated` / `slt-admincreated@example.test`, role `customer`) on product `11927` (`SLT Daily Core`).
- Creation proof: `SUBCOUNT` moved `373 -> 374`. `SUB_A` was created `arraysubs-pending` with `_billing_period=day`, `_billing_interval=1`, `_recurring_amount=10`, `_product_id=11927`, `_customer_id=353`, `_next_payment_date=2026-09-05 13:03:41`, and no action-id metas. Pending action search for `12760` returned **No items found**.
- Pending→Active no longer matches the authored stale path. Activation sent customer mail `38K3YDfsNAdXfackwV6Dzu` and admin mail `5aGyzH2h9TWehGZqHuRDH9`, flipped the subscription to `arraysubs-active`, and immediately armed:
  - reminder `14923` at `2026-09-02 14:29:37Z`
  - invoice `14921` at `2026-09-05 08:29:37Z`
  - renewal `14922` at `2026-09-05 14:29:37Z`
- Product bug filed separately at `qa/subscription-lifecycle-test/issues/done-critical-plugin-SLT-ADM-05-admin-created-daily-subscription-arms-at-one-month.md`: this day/1 subscription arms on a one-month cadence.
- Plan correction applied on D3: do not file the obsolete unscheduled-active issue from this card, and do not trust the authored D4 follow-up date. Any later watch must key off the live September action timestamps above.
- Safe evidence captured so far: `SLT-ADM-05-01-form.png`, `SLT-ADM-05-02-toast.png`, `SLT-ADM-05-03-queue-empty-pending.png`, `SLT-ADM-05-04-queue-active-has-legs.png`.

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

[[2026-08-05]] Wed 15:09
Board handoff: D3 plan correction applied. SUB_A=12760 proved the old unscheduled-active path obsolete; separate product bug filed at qa/subscription-lifecycle-test/issues/done-critical-plugin-SLT-ADM-05-admin-created-daily-subscription-arms-at-one-month.md. Future follow-up must key off live action ids 14923/14921/14922 (September schedule), not the authored D4 assumption.

[[2026-08-05]] Wed 15:30
Board correction: restored to in-progress. Product finding moved the next exact actions to September: invoice 14921 at 2026-09-05 08:29:37Z and charge 14922 at 14:29:37Z; D4 authored renewal is impossible and remains tied to issues/done-critical-plugin-SLT-ADM-05-admin-created-daily-subscription-arms-at-one-month.md.

[[2026-08-05]] Wed 16:41
Product defect filed. Authored D4 renewal cannot occur; future observed gates are actions 14923 at 2026-09-02 14:29:37Z, 14921 at 2026-09-05 08:29:37Z, and 14922 at 14:29:37Z.

[[2026-08-05]] Wed 16:46
Board hygiene: returned to todo because this card is not in an active execution window right now. Resume only at the exact gate or follow-up already recorded on the card.

[[2026-08-05]] Wed 17:26
Defect filed; future observed gates are 14923 on 2026-09-02 and 14921/14922 on 2026-09-05.

[[2026-08-05]] Wed 17:44
Future authored follow-up: reminder 14923 on 2026-09-02 14:29:37Z; invoice baseline 2026-09-05 08:24:37Z–08:29:36Z; actions 14921/14922.

[[2026-08-11]] Tue 23:33
Fix verification (2026-08-11): confirmed REST meta-ordering root cause; core now emits creation after persisted meta and helper callers insert complete meta atomically. Browser subscription 13892 retained day/1 with a next-day date and correctly aligned actions 17345/17346; checkout order 13909 and Pro Box/Bundle child controls 13913/13917 passed. Resolved report renamed to issues/done-critical-plugin-SLT-ADM-05-admin-created-daily-subscription-arms-at-one-month.md.
