---
id: 48
title: Renewal orders are correctly typed and linked to the parent subscription (HPOS)
status: todo
priority: critical
created: 2026-08-02T03:43:07.177422128+02:00
updated: 2026-08-02T03:43:17.490220119+02:00
tags:
    - admin
    - portal
    - day-03
    - has-conflicts
due: "2026-08-05"
estimate: 1h
depends_on:
    - 5
class: standard
---

> **SLT-ADM-06** · group `admin` · scheduled **D03** (2026-08-05)

> Read `README.md` (environment + isolation contract), `calendar.md` (this day's exact
> ordering — it is binding, not advisory) and `plan-audit.md` before starting.

### ⚠ Conflict resolutions that apply to this task

**`high` · session collision (shared admin session)** — with `SLT-EML-01`, `SLT-EML-02`, `SLT-EML-03`, `SLT-EML-05`, `SLT-EML-10`, `SLT-EML-12`

- *Problem:* More than twenty tasks open `--session admin` by that bare name, in direct violation of audit C09's fix (which only got applied to guest/customer sessions). agent-browser sessions are keyed by name, so these tasks share one browser profile: one task's `agent-browser close --session admin` logs out another mid-run; one task navigating the SPA away from a settings screen invalidates another's snapshot; and any admin session that ever adds to cart (SLT-ADM-01's bulk-action screen, SLT-EML-12's status juggling) puts a cart on the admin user shared by all of them. The failures this produces look like flaky UI, not contamination.
- *Required fix:* Rename every admin session to `admin-<TASK-KEY>` (SLT-ADM-01/02/03/04/05 already do this correctly - copy the pattern). Each task closes only its own session by name; `agent-browser close --all` is reserved to the last task of the day, named explicitly in the calendar. Add this to the isolation contract as rule 9 and add a pass criterion to every task that opens an admin session: 'the session name contains this task's key'.

**`low` · duplicate-coverage** — with `SLT-EML-15`, `SLT-REN-02`, `SLT-EML-03`, `SLT-EML-06`, `SLT-CHK-01`, `SLT-EML-07`

- *Problem:* Six overlapping clusters, each spending an execution slot on a code path another task already proves. (a) SLT-EML-15, SLT-REN-02 and SLT-ADM-06 all read the same SUB_CORE renewal cycle: EML-15 reconciles the mail set, REN-02 asserts the schedule re-arm, ADM-06 asserts the order typing/linkage - three tasks, one cycle, three separate evidence sets. (b) SLT-EML-03's Stripe leg re-asserts REN-02's payment_successful and its Paddle leg re-asserts SLT-REN-04's. (c) SLT-EML-06 re-proves new_subscription + admin_new_subscription at a Stripe block checkout, which is SLT-CHK-01's email rows 3 and 4 verbatim, on a new account. (d) SLT-EML-07 and SLT-SW-10 both drive pending-cancellation -> cancelled -> reactivation and both assert the same four emails. (e) SLT-EML-04's four payment_failed pairs are exactly SLT-DUN-01 ER8 plus SLT-DUN-02 ER9. (f) SLT-SYN-11's 'flex section hidden for a Different Renewal Price product' repeats SLT-PROD-05 steps 7-9 and SLT-SYN-01's probe. (g) SLT-LIFE-02's Paddle 'no Renew Early control' negative repeats SLT-CHK-04 ER7 and SLT-PROD-16.
- *Required fix:* Keep one owner per assertion and make the others cite it. (a) EML-15 owns the reconciled mail set for the cycle and publishes it to the registry; REN-02 keeps only the schedule/offset/no-drift assertions; ADM-06 keeps only the HPOS meta assertions and drops its Related-Orders screenshot in favour of ADM-02's. (b) EML-03 keeps only the content assertions (amount, method row, UTC+6 next date, the Paddle ordering hazard) and cites REN-02/REN-04 for 'the renewal fired'. (c) EML-06 keeps only the gating-key and subject-string proof (emails.new_subscription.enabled, admin recipient resolution, the B4 dead-setting verdict) and cites CHK-01 for the checkout. (d) EML-07 owns the email set; SW-10 owns the reason-required / offers-declined / scheduled-cancel-timestamp / reactivation-scheduling-bug half and cites EML-07's mailpit ids. (e) EML-04 places no purchase and becomes the mail-content rider on the DUN ladder (attempt-number visibility, Pay Now link resolution, To: headers) - see the DUN re-day entry. (f) SYN-11 keeps only the force-set-meta half (isEnabled() true, getConfig() null, zero _renewal_sync_* on the subscription) and cites PROD-05 for the UI-absence screenshots. (g) LIFE-02 cites CHK-04's screenshot rather than re-driving the Paddle portal.

---
## Objective
Prove every renewal order is typed as a renewal and linked to its subscription, reading the metas out of HPOS storage rather than trusting the admin UI. `OrderCreation::createRenewalOrder()` stamps `_is_renewal_order`, `_subscription_id`, `_subscription_renewal`, `_renewal_cycle_number` and `_renewal_scheduled_date` (`OrderCreation.php:210-219`). Cover two consecutive cycles of the control subscription; the parent order must instead carry array-valued `_subscription_ids`.

## Scope
- Gateway: Stripe test
- Checkout: N/A
- Account: existing (slt-core)
- Plugins: free-only

## Preconditions
- SLT-PROD-01 `SLT Daily Core` ($10.00, day/1) bought D0 after 12:00 site by `slt-core`; P1/S1 ids in `slt-catalog-registry`.
- Renewals #1 (D1 PM) and #2 (D2 PM) must have fired unattended. Run **after 19:00 site on D2 = 2026-08-04**. A missing renewal is a genuine bug: capture evidence, file it, never force-run a hook.
- SLT-REF-01 §1: invoice leg `due+k-6h`, charge leg `due+k`, `k = crc32('arraysubs-spread-'.S1) % 21600`.

## Test data
| Item | Value |
|---|---|
| Product | SLT Daily Core |
| Account | slt-core / `SltQa!2026#Pass` |
| Objects | S1; P1 parent, R1 cycle 1, R2 cycle 2 |
| Amounts | $10.00/cycle, no fee, no tax |

## Steps
1. `php -r '$h=(int)sprintf("%u",crc32("arraysubs-spread-<S1>"));echo $h%21600;'` — record `k` (seconds).
2. `agent-browser --session admin open "https://mirror-help.arrayhash.com/wp-admin/admin.php?page=arraysubs-mainadmin#/subscriptions/detail/<S1>"` -> `snapshot -i`. Record status, Next payment, Related Orders.
3. Open `.../wp-admin/admin.php?page=wc-orders&_customer_user=<slt-core id>` -> `snapshot -i`. Record R1/R2 ids, dates, statuses, totals.
4. Open `...page=wc-orders&action=edit&id=<R1>` -> `snapshot -i`: customer slt-core, total `$10.00`, no tax row, status Completed/Processing. Repeat for R2 and P1.
5. From WP root: `wp db query "SELECT order_id,meta_key,meta_value FROM wp_wc_orders_meta WHERE order_id IN (<P1>,<R1>,<R2>) AND (meta_key LIKE '%subscription%' OR meta_key LIKE '%renewal%') ORDER BY order_id" --allow-root`.
6. `wp db query "SELECT id,type,status,total_amount,customer_id FROM wp_wc_orders WHERE id IN (<P1>,<R1>,<R2>)"` then `wp db query "SELECT COUNT(*) FROM wp_posts WHERE ID IN (<R1>,<R2>)"` (both `--allow-root`).
7. `wp post meta list <S1> --keys=_next_payment_date,_last_payment_date,_completed_payments,_pending_renewal_order_id --allow-root`.
8. `wp eval 'foreach(wc_get_order(<R1>)->get_items() as $i){echo $i->get_meta("_subscription_id",true);}' --allow-root`.
9. Deferred follow-up for the daily watch (D3 = 2026-08-05 on): repeat steps 4-5 on the first `SLT Paddle Daily` renewal order (`slt-paddle`). Paddle renewals are webhook-driven and may be created retroactively (SLT-REF-09) and Paddle also writes `_arraysubs_subscription_id`. Record which metas are present and whether the cycle number is continuous.

## Expected results
1. R1/R2 exist in `wp_wc_orders`, `total_amount = 10.00`, customer_id = slt-core; `wp_posts` count is 0.
2. R1/R2 both have `_is_renewal_order = yes`, `_subscription_id = _subscription_renewal = <S1>`.
3. `_renewal_cycle_number` = `1` on R1, `2` on R2 — incrementing, no gap.
4. `_renewal_scheduled_date` = that cycle's logical due date (UTC); the order's `date_created` is earlier by about `6h - k` and must NOT equal it (record both and the delta).
5. P1 carries serialized `_subscription_ids` containing `<S1>` and no `_is_renewal_order`/`_renewal_cycle_number`.
6. `_arraysubs_subscription_id` is ABSENT on R1/R2, yet `findRefundableOrder()` and `refund-helpers.php:202` look renewal orders up by that key — record, hand to SLT-ADM-08.
7. On S1: `_pending_renewal_order_id` deleted, `_last_payment_date` set, `_completed_payments` >= 2, `_next_payment_date` advanced 1 day per cycle from `_renewal_scheduled_date`.

## Emails expected
| # | Email | Trigger point | Recipient | Subject contains | Verify with |
|---|---|---|---|---|---|
| 1 | NONE EXPECTED | Whole task (read-only) | — | — | `mailpit-agent latest-id` at step 1 must be unchanged at the end |

## Evidence to capture
- Screenshots `SLT-ADM-06-01-hpos-list.png`, `-02-renewal-R1.png`, `-03-parent-P1.png`, `-04-related-orders.png`.
- Ids S1/P1/R1/R2, slt-core user id, `k`; steps 5-8 output in `/home/server-manager/slt-evidence/SLT-ADM-06-meta.txt`; any PHP notice/console error.

## Pass criteria
- [ ] R1/R2 in `wp_wc_orders`, absent from `wp_posts`; `_is_renewal_order=yes` and both link metas = S1 on each
- [ ] `_renewal_cycle_number` 1 then 2
- [ ] `_renewal_scheduled_date` = logical due date, differs from `date_created`
- [ ] P1 has array `_subscription_ids`, no renewal metas
- [ ] `_arraysubs_subscription_id` absence recorded
- [ ] Schedule metas consistent; Related Orders lists P1/R1/R2; no tax line; zero mail

## Isolation / teardown
- Read-only: nothing modified, re-run or refunded. Hands SLT-ADM-07 the R1/R2 ids and `k`, SLT-ADM-08 the missing-`_arraysubs_subscription_id` finding. Artifacts removed by SLT-SETUP-99A/99B.

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
