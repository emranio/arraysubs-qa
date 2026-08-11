---
id: 48
title: Renewal orders are correctly typed and linked to the parent subscription (HPOS)
status: done
priority: critical
created: 2026-08-02T03:43:07.177422128+02:00
updated: 2026-08-06T20:10:32.378198239+02:00
started: 2026-08-06T20:10:32.378197227+02:00
completed: 2026-08-06T20:10:32.378197227+02:00
tags:
    - admin
    - portal
    - day-03
due: "2026-08-05"
estimate: 1h
depends_on:
    - 5
    - 1
    - 41
class: standard
---

> **SLT-ADM-06** · group `admin` · scheduled **D03** (2026-08-05)

> Read `README.md` (environment + isolation contract), `calendar.md` (this day's exact
> ordering — it is binding, not advisory) and `plan-audit.md` before starting.

## Objective
Prove every renewal order is typed as a renewal and linked to its subscription, reading the metas out of HPOS storage rather than trusting the admin UI. `OrderCreation::createRenewalOrder()` stamps `_is_renewal_order`, `_subscription_id`, `_subscription_renewal`, `_renewal_cycle_number` and `_renewal_scheduled_date` (`OrderCreation.php:210-219`). Cover two consecutive cycles of the control subscription; the parent order must instead carry array-valued `_subscription_ids`.

## Scope
- Gateway: Stripe test
- Checkout: N/A
- Account: existing (slt-core)
- Plugins: free-only

## Preconditions
- SLT-PROD-01 `SLT Daily Core` ($10.00, day/1) bought D0 after 12:00 site by `slt-core`; P1/S1 ids in `slt-catalog-registry`.
- Renewals #1 (D1 PM) and #2 (D2 PM) must have fired unattended. Run only after renewal #2's exact registered `due+k` gate has settled (normally D3 morning, 2026-08-05; **19:00 on D2 alone is not a sufficient gate**). A missing renewal is a genuine bug: capture evidence, write one standalone markdown file under `issues/`, never create a lifecycle-board bug card, and never force-run a hook.
- SLT-REF-01 §1: invoice leg `due+k-6h`, charge leg `due+k`, `k = crc32('arraysubs-spread-'.S1) % 21600`.

## Test data
| Item | Value |
|---|---|
| Product | SLT Daily Core |
| Account | slt-core / `SltQa!2026#Pass` |
| Objects | S1; P1 parent, R1 cycle 1, R2 cycle 2 |
| Amounts | $10.00/cycle, no fee, no tax |

## Steps
1. Resolve unique numeric registry aliases `S1` and `P1` into shell variables and cross-check customer/product before continuing. Set `ADM06_CORE_PRE=$(mailpit-agent latest-id)`, then run `php -r '$id=(int)$argv[1];$h=(int)sprintf("%u",crc32("arraysubs-spread-".$id));echo $h%21600;' "$S1"` and record `k`.
2. Resolve exactly two paid renewal orders for `$S1` through HPOS `_subscription_id=$S1` plus cycle numbers `2` and `3`; assign numeric `R1`/`R2`, require one row per cycle and reverse/meta consistency, and never use recency. If either is missing after its exact settled gate, write a standalone issue with task/plan, fixture/user/routes, action/order/meta/mail proof, expected/actual, and the surviving cycle counterexample; mark the unavailable assertions `UNVERIFIED` and continue the independent Paddle branch rather than forcing anything.
3. In `admin-SLT-ADM-06`, open the real `admin.php?page=arraysubs-mainadmin#/subscriptions` route, search exact `$S1`, and capture its detail/Related Orders state as `SLT-ADM-06-04-related-orders.png`.
4. Open `.../wp-admin/admin.php?page=wc-orders&_customer_user=<slt-core id>`, search the exact three IDs, and capture `SLT-ADM-06-01-hpos-list.png`. Open exact `$R1` and capture `-02-renewal-R1.png`; repeat for `$R2` as `-02a-renewal-R2.png` and `$P1` as `-03-parent-P1.png`, recording customer, total, tax, status, and dates.
5. From WP root: `wp db query "SELECT order_id,meta_key,meta_value FROM wp_wc_orders_meta WHERE order_id IN ($P1,$R1,$R2) AND (meta_key LIKE '%subscription%' OR meta_key LIKE '%renewal%') ORDER BY order_id" --allow-root`.
6. `wp db query "SELECT id,type,status,total_amount,customer_id FROM wp_wc_orders WHERE id IN ($P1,$R1,$R2)" --allow-root` then `wp db query "SELECT ID,post_type,post_status FROM wp_posts WHERE ID IN ($P1,$R1,$R2) ORDER BY ID" --allow-root` to classify any HPOS placeholder rows. Do not fail merely because `wp_posts` contains draft `shop_order_placehold` rows.
7. `wp post meta list "$S1" --keys=_next_payment_date,_last_payment_date,_completed_payments,_pending_renewal_order_id --allow-root`.
8. `wp eval "foreach(wc_get_order((int) $R1)->get_items() as \$i){echo \$i->get_meta('_subscription_id',true);}" --allow-root`. Inspect the complete bounded delta after `ADM06_CORE_PRE`, require zero task-attributable mail, close `admin-SLT-ADM-06`, and keep this card `in-progress`. If both Stripe renewal orders lack `_arraysubs_subscription_id`, create `issues/critical-plugin-SLT-ADM-06-renewal-orders-missing-arraysubs-subscription-id.md` only after this live proof, including every mandatory task/fixture/user/admin-route/reproduction/expected/actual/HPOS proof field and the Paddle counterexample when available.
9. Deferred follow-up after `SLT-REN-04` has settled the first `SLT Paddle Daily` remote renewal: if `SUB_PAD unavailable`, cite the upstream standalone issue and close only this branch as `UNVERIFIED`; otherwise set `ADM06_PAD_PRE=$(mailpit-agent latest-id)`, reopen `admin-SLT-ADM-06`, cite the exact subscription/order relationship and task-owned report, and repeat steps 4-5 on the relationship-linked Paddle renewal order. Paddle renewals are webhook-driven and may be created retroactively (SLT-REF-09) and Paddle also writes `_arraysubs_subscription_id`. Record which metas are present and whether the cycle number is continuous; inspect the bounded no-mail delta, close the session, independently review both available branches and issue file, and move this card through review to done. Do not substitute recency or create another Paddle purchase.

## Expected results
1. R1/R2 exist in `wp_wc_orders`, `total_amount = 10.00`, customer_id = slt-core. If matching `wp_posts` rows exist, they must be non-authoritative HPOS placeholder drafts (`shop_order_placehold`), not real order records carrying the order truth.
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
- IDs S1/P1/R1/R2, slt-core user ID, `k`, `ADM06_CORE_PRE`, optional `ADM06_PAD_PRE`, and bounded classified deltas; steps 5-8 output in `/home/server-manager/slt-evidence/SLT-ADM-06-meta.txt`; any PHP notice/console error.

## Pass criteria
- [ ] R1/R2 in `wp_wc_orders`; any matching `wp_posts` rows are only HPOS placeholder drafts; `_is_renewal_order=yes` and both link metas = S1 on each
- [ ] `_renewal_cycle_number` 2 then 3
- [ ] `_renewal_scheduled_date` = logical due date, differs from `date_created`
- [ ] P1 has array `_subscription_ids`, no renewal metas
- [ ] `_arraysubs_subscription_id` absence recorded
- [ ] Schedule metas consistent; Related Orders lists P1/R1/R2; no tax line; zero mail
- [ ] Missing compatibility link is filed only from exact live HPOS proof; Paddle branch is completed or explicitly `UNVERIFIED`; card closes through review

## Isolation / teardown
- Read-only: nothing modified, re-run or refunded. Hands SLT-ADM-07 the R1/R2 ids and `k`, and SLT-ADM-08 the standalone missing-`_arraysubs_subscription_id` issue path. Close only `admin-SLT-ADM-06` after each dated leg. Artifacts are removed by SLT-SETUP-99A/99B.

---

### Verified environment facts (2026-08-01/02 — do not re-derive)

- **Nothing fires at `_next_payment_date`.** Every scheduled leg is shifted by
  `crc32('arraysubs-spread-'.$subscription_id) % 21600` (0-6 h). Charge fires at `due + offset`,
  invoice at `due + offset - 6h`. The stored date never moves. **Assert a window, not a point.**
- Currency `USD`. **Taxes are OFF** (`woocommerce_calc_taxes = no`) — never assert a tax line.
- Orders use **HPOS**: authoritative order data is in `wp_wc_orders`, while this environment may also keep draft `shop_order_placehold` placeholder rows in `wp_posts`.
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

[[2026-08-05]] Wed 16:54
Stripe/HPOS leg complete on 2026-08-05. Evidence: /home/server-manager/slt-evidence/SLT-ADM-06-meta.txt and screenshots SLT-ADM-06-01..04. Standalone issue filed: qa/subscription-lifecycle-test/issues/critical-plugin-SLT-ADM-06-renewal-orders-missing-arraysubs-subscription-id.md. Plan corrected for HPOS placeholder rows in wp_posts. Remaining work is the authored Paddle counterexample branch after SLT-REN-04 settles the first real Paddle renewal; resume then.

[[2026-08-05]] Wed 17:26
Stripe/HPOS leg complete; resume only after SLT-REN-04 settles the first Paddle renewal, then execute the authored Paddle counterexample branch.

[[2026-08-05]] Wed 17:44
Resume after SLT-REN-04 settles the first Paddle renewal; audit the relationship-owned Paddle renewal order.

[[2026-08-06]] Thu 20:10
Closed after consuming the settled Paddle branch. Stripe/HPOS leg was already captured in SLT-ADM-06-meta.txt and the existing standalone issue. Paddle counterexample now verified on renewal order 12891: _is_renewal_order=yes, _subscription_id=12639, _subscription_renewal=12639, _renewal_cycle_number=2, _renewal_scheduled_date=2026-08-06 10:20:38, and _arraysubs_subscription_id remains absent.
