---
id: 48
title: Renewal orders are correctly typed and linked to the parent subscription (HPOS)
status: todo
priority: critical
created: 2026-08-22T19:10:00+02:00
updated: 2026-08-22T19:10:00+02:00
tags:
    - cycle-2
    - granular
    - admin
    - portal
    - day-03
due: "2026-08-26"
estimate: 1h
depends_on:
    - 5
    - 1
    - 41
class: standard
---

> **SLT-ADM-06** · group `admin` · scheduled **D03** (2026-08-26)

> Read `README.md` (environment + isolation contract), `calendar.md` (this day's exact
> ordering — it is binding, not advisory) and `plan-audit.md` before starting.

## Objective
Prove every renewal order is typed as a renewal and linked to its subscription, reading the metas out of HPOS storage rather than trusting the admin UI. `OrderCreation::createRenewalOrder()` stamps `_is_renewal_order`, `_subscription_id`, `_subscription_renewal`, `_renewal_cycle_number` and `_renewal_scheduled_date` (`OrderCreation.php:210-219`). Cover two consecutive cycles of the control subscription; the parent order must instead carry array-valued `_subscription_ids`.

## Scope
- Gateway: Stripe test
- Checkout: N/A
- Account: existing (slt2-core)
- Plugins: free-only

## Preconditions
- SLT-PROD-01 `SLT2 Daily Core` ($10.00, day/1) bought D0 after 12:00 site by `slt2-core`; P1/S1 ids in `slt2-catalog-registry`.
- Renewals #1 (D1 PM) and #2 (D2 PM) must have fired unattended. Run only after renewal #2's exact registered `due+k` gate has settled (normally D3 morning, 2026-08-26; **19:00 on D2 alone is not a sufficient gate**). A missing renewal is a genuine bug: capture evidence, create or update the mandatory `qa/issues/` kanban card, leave the lifecycle task blocked; never force-run a hook.
- SLT-REF-01 §1: invoice leg `due+k-6h`, charge leg `due+k`, `k = crc32('arraysubs-spread-'.S1) % 21600`.

## Test data
| Item | Value |
|---|---|
| Product | SLT2 Daily Core |
| Account | slt2-core / `SltQa!2026#Pass` |
| Objects | S1; P1 parent, R1 cycle 1, R2 cycle 2 |
| Amounts | $10.00/cycle, no fee, no tax |

## Steps
1. Resolve unique numeric registry aliases `S1` and `P1` into shell variables and cross-check customer/product before continuing. Set `ADM06_CORE_PRE=$(mailpit-agent latest-id)`, then run `php -r '$id=(int)$argv[1];$h=(int)sprintf("%u",crc32("arraysubs-spread-".$id));echo $h%21600;' "$S1"` and record `k`.
2. Resolve exactly two paid renewal orders for `$S1` through HPOS `_subscription_id=$S1` plus cycle numbers `2` and `3`; assign numeric `R1`/`R2`, require one row per cycle and reverse/meta consistency, and never use recency. If either is missing after its exact settled gate, write a dedicated issue with task/plan, fixture/user/routes, action/order/meta/mail proof, expected/actual, and the surviving cycle counterexample; mark the unavailable assertions `BLOCKED` and continue the independent Paddle branch rather than forcing anything.
3. In `admin-SLT-ADM-06`, open the real `admin.php?page=arraysubs-mainadmin#/subscriptions` route, search exact `$S1`, and capture its detail/Related Orders state as `SLT-ADM-06-04-related-orders.png`.
4. Open `.../wp-admin/admin.php?page=wc-orders&_customer_user=<slt2-core id>`, search the exact three IDs, and capture `SLT-ADM-06-01-hpos-list.png`. Open exact `$R1` and capture `-02-renewal-R1.png`; repeat for `$R2` as `-02a-renewal-R2.png` and `$P1` as `-03-parent-P1.png`, recording customer, total, tax, status, and dates.
5. From WP root: `wp db query "SELECT order_id,meta_key,meta_value FROM wp_wc_orders_meta WHERE order_id IN ($P1,$R1,$R2) AND (meta_key LIKE '%subscription%' OR meta_key LIKE '%renewal%') ORDER BY order_id" --allow-root`.
6. `wp db query "SELECT id,type,status,total_amount,customer_id FROM wp_wc_orders WHERE id IN ($P1,$R1,$R2)" --allow-root` then `wp db query "SELECT ID,post_type,post_status FROM wp_posts WHERE ID IN ($P1,$R1,$R2) ORDER BY ID" --allow-root` to classify any HPOS placeholder rows. Do not fail merely because `wp_posts` contains draft `shop_order_placehold` rows.
7. `wp post meta list "$S1" --keys=_next_payment_date,_last_payment_date,_completed_payments,_pending_renewal_order_id --allow-root`.
8. `wp eval "foreach(wc_get_order((int) $R1)->get_items() as \$i){echo \$i->get_meta('_subscription_id',true);}" --allow-root`. Inspect the complete bounded delta after `ADM06_CORE_PRE`, require zero task-attributable mail, close `admin-SLT-ADM-06`, and keep this card `in-progress`. If both Stripe renewal orders lack `_arraysubs_subscription_id`, create `qa/issues/` kanban card named `SLT-ADM-06-renewal-orders-missing-arraysubs-subscription-id` only after this live proof, including every mandatory task/fixture/user/admin-route/reproduction/expected/actual/HPOS proof field and the Paddle counterexample when available.
9. Deferred follow-up after `SLT-REN-04` settles the first `SLT2 Paddle Daily` remote renewal: require numeric `SUB_PAD`, set `ADM06_PAD_PRE=$(mailpit-agent latest-id)`, reopen `admin-SLT-ADM-06`, cite the exact subscription/order relationship and repeat steps 4-5 on the relationship-linked Paddle renewal order. If the source is missing, update the upstream issue and leave this card blocked. Record metas/cycle continuity, inspect the bounded no-mail delta, close the session, and mark done only after both Stripe and Paddle branches pass. Do not substitute recency or create another Paddle purchase.

## Expected results
1. R1/R2 exist in `wp_wc_orders`, `total_amount = 10.00`, customer_id = slt2-core. If matching `wp_posts` rows exist, they must be non-authoritative HPOS placeholder drafts (`shop_order_placehold`), not real order records carrying the order truth.
2. R1/R2 both have `_is_renewal_order = yes`, `_subscription_id = _subscription_renewal = <S1>`.
3. `_renewal_cycle_number` = `2` on R1, `3` on R2 — the parent/initial payment is cycle 1, then renewals increment without a gap.
4. `_renewal_scheduled_date` = that cycle's logical due date (UTC); the order's `date_created` is earlier by about `6h - k` and must NOT equal it (record both and the delta).
5. P1 carries serialized `_subscription_ids` containing `<S1>` and no `_is_renewal_order`/`_renewal_cycle_number`.
6. `_arraysubs_subscription_id` is ABSENT on R1/R2, yet `findRefundableOrder()` and `refund-helpers.php:202` look renewal orders up by that key — record, hand to SLT-ADM-08.
7. On S1: `_pending_renewal_order_id` deleted, `_last_payment_date` set, `_completed_payments` >= 2, `_next_payment_date` advanced 1 day per cycle from `_renewal_scheduled_date`.

## Emails expected
| # | Email | Trigger point | Recipient | Subject contains | Verify with |
|---|---|---|---|---|---|
| 1 | NONE EXPECTED | Whole task (read-only) | — | — | task-local complete delta from a start baseline; zero task-attributable mail, classify renewal/background mail under its owner |

## Evidence to capture
- Screenshots `SLT-ADM-06-01-hpos-list.png`, `-02-renewal-R1.png`, `-02a-renewal-R2.png`, `-03-parent-P1.png`, `-04-related-orders.png`.
- IDs S1/P1/R1/R2, slt2-core user ID, `k`, `ADM06_CORE_PRE`, optional `ADM06_PAD_PRE`, and bounded classified deltas; steps 5-8 output in `/home/server-manager/slt-evidence/SLT-ADM-06-meta.txt`; any PHP notice/console error.

## Pass criteria
- [ ] R1/R2 in `wp_wc_orders`; any matching `wp_posts` rows are only HPOS placeholder drafts; `_is_renewal_order=yes` and both link metas = S1 on each
- [ ] `_renewal_cycle_number` 2 then 3
- [ ] `_renewal_scheduled_date` = logical due date, differs from `date_created`
- [ ] P1 has array `_subscription_ids`, no renewal metas
- [ ] `_arraysubs_subscription_id` absence recorded
- [ ] Schedule metas consistent; Related Orders lists P1/R1/R2; no tax line; zero mail
- [ ] Stripe and Paddle relationship branches both completed; any missing source or compatibility link keeps the card blocked with a linked issue

## Isolation / teardown
- Read-only: nothing modified, re-run or refunded. Hands SLT-ADM-07 the R1/R2 ids and `k`, and SLT-ADM-08 the dedicated missing-`_arraysubs_subscription_id` issue path. Close only `admin-SLT-ADM-06` after each dated leg. Artifacts are removed by SLT-SETUP-99A/99B.

---

### Fresh-cycle validation contract

- Re-derive every ID, count, option value, gateway capability, scheduler timestamp, and email baseline on this run; no prior-cycle result is evidence.
- Create and mutate only registered `SLT2 ` / `slt2-*` fixtures. Legacy `SLT` and all non-SLT2 data are read-only controls.
- Automatic-gateway scope is Stripe and Paddle only. Stripe is the primary path; run Paddle parity wherever Paddle supports the behavior. Do not test or configure PayPal or Mollie.
- ArraySubs core must own its Stripe/Paddle integration, renewal, retry, webhook, REST, refund, and customer-payment services with Pro inactive; vendor host classes retain their expected ownership.
- Browser-required assertions use Vercel `agent-browser` with isolated task/role sessions and current snapshot refs. WP-CLI always includes `--allow-root`.
- Update the lifecycle card, the matching `qa/progress/` card, and `qa/issues/` for every new regression. Evidence belongs to this fresh cycle only.
