---
id: 99
title: 'Calendar overflow on the month flex product: the 31st day is absorbed into the last active segment'
status: todo
priority: high
created: 2026-08-22T19:10:00+02:00
updated: 2026-08-22T19:10:00+02:00
tags:
    - cycle-2
    - granular
    - renewal-sync
    - day-06
due: "2026-08-29"
estimate: 2h
depends_on:
    - 21
    - 22
    - 13
    - 45
class: standard
---

> **SLT-SYN-10** · group `sync` · scheduled **D06** (2026-08-29)

> Read `README.md` (environment + isolation contract), `calendar.md` (this day's exact
> ordering — it is binding, not advisory) and `plan-audit.md` before starting.

## Objective
Prove flexible sync partitions a month cycle on a NOMINAL 30 days while the calendar month it lands in has 31, and that surplus days are absorbed into the LAST ACTIVE segment — not hard-coded to segment 3, not an error, and not `flex_day_unresolved`. This task has a D6 purchase leg that creates the live segment-3 cohort and a D8 analysis/targeted-renewal leg that proves calendar-month advancement across 31-day October.

## Scope
- Gateway: Stripe test
- Checkout: classic, D6 purchase leg
- Account: existing (`slt2-flex2`, `slt2-flex3`)
- Plugins: pro-required

## Preconditions
- SLT-PROD-12: `SLT2 Flex Month Segments` `<MONTH_ID>`, month/1, $30.00, seg1_end=24, seg2_end=27, all active — the plan's only month product. SLT-PROD-14: `SLT2 Flex Daily Two Seg` `<TWO_ID>`. NO product meta written on either.
- `<SUB_M>` is the canonical registry alias for the month segment-2 subscription bought by `SLT-SYN-06` as `slt2-flex2`.
- This task owns the missing `<SUB_S3>` purchase as `slt2-flex3` on D6 after 12:00 site. It must re-read the product's six flex metas before opening the cart.
- D8 = 2026-08-31 is the only authorised date-meta time-travel day. This task owns one targeted invoice/charge action pair for `<SUB_S3>`; `SLT-TT-00` owns the other forced cohorts. Hook/group drains are forbidden every day.
- On D8, `SLT-TT-00` must already be complete, with its non-SLT2 baseline and final diff published. This task quotes that baseline and touches only `<SUB_S3>`.

## Test data
| Item | Value |
|---|---|
| Cycle under test | 2026-08-01 .. 2026-09-01 site = **31 real days**, nominal 30 |
| Boundaries UTC | cycle_start `2026-07-31 18:00:00`, due `2026-08-31 18:00:00` (00:00 site = 18:00 UTC) |
| D6 live cohort | `slt2-flex3` buys on 2026-08-29 after 12:00 site: day 29, segment 3 `next_cycle`, first-full boundary `2026-09-30 18:00:00` UTC |

## Steps
### D6 purchase leg — 2026-08-29 after 12:00 site

1. Load the agent-browser core guide if needed. Resolve numeric `<MONTH_ID>` from the registry, re-read its `_arraysubs_flex_sync_*` metas, and require the restored `1-24 / 25-27 / 28-30`, all-active contract from `SLT-SYN-01`.
2. Verify `slt2-flex3`'s serialized persistent cart and isolated `customer-SLT-SYN-10-D6` browser cart are empty; record exact order/subscription counts. Set `M_CREATE=$(/usr/local/bin/mailpit-agent latest-id 2>/dev/null || true)` immediately before adding only numeric `<MONTH_ID>`.
3. Handle the frozen one-click redirect explicitly and screenshot the unpopulated cart/checkout summary as `/home/server-manager/slt-evidence/SLT-SYN-10-01-D6-segment3-cart.png`. It must show `$30.00`, mode `next_cycle`, and the note that today's payment covers the full cycle starting 2026-09-01 site. Select Stripe test, enter hosted card data without capturing populated fields, pay, and capture the safe order-received page separately.
4. Record numeric `<ORDER_S3>` from the receipt, read its linkage with `wp post meta get <ORDER_S3> _subscription_ids --format=json --allow-root`, and resolve `<SUB_S3>` through a strict one-element numeric `jq -e` guard, cross-checking `_parent_order_id`, customer, product, and exact `+1` order/subscription counts. Never use the WooCommerce order meta accessor or recency. Poll immutable `M_CREATE` in repeated calls no longer than 60 seconds through the two-minute cutoff for the exact active-subscription subject, classify the complete four-message WC/ArraySubs checkout delta, then dump the exact order and subscription meta, including `_renewal_sync_*`, `_next_payment_date`, `_completed_payments`, `_renewal_action_id`, and `_renewal_invoice_action_id`.
5. Require `<SUB_S3>` active with `_renewal_sync_first_charge_mode=next_cycle`, `_renewal_sync_cycle_start_date=2026-08-31 18:00:00` UTC, and `_next_payment_date=_renewal_sync_first_full_renewal_date=2026-09-30 18:00:00` UTC. Record its pending invoice and charge IDs. Assert both WP-CLI and browser carts are empty, then close only `customer-SLT-SYN-10-D6`.

### D8 overflow and targeted-renewal leg — 2026-08-31

6. Require `SLT-TT-00` complete and quote its registry revision, pre-flight non-SLT2 list, and empty post-target diff. Do not set the renewal-mail baseline yet; the intervening probes must not contaminate its ownership window.
7. Replace `MONTH_ID` and run the executable config/overflow probe below, saving its full output. It dumps `getConfig()`, `getPartition()`, the nominal cycle, and resolved segment/mode for every boundary and overflow day:
   ```bash
   wp eval '
   use ArraySubsPro\Features\FlexibleRenewalSync\Services\SegmentPlan as S;
   $c = S::getConfig(MONTH_ID);
   print_r(["config" => $c, "partition" => S::getPartition($c), "nominal" => S::getNominalCycleDays("month", 1)]);
   foreach ([1,24,25,27,28,29,30,31,32] as $d) {
       $segment = S::resolveSegment($d, $c);
       printf("day=%d segment=%d mode=%s\n", $d, $segment, S::getSegmentMode($segment));
   }
   ' --allow-root
   ```
8. Replace `TWO_ID` and dump `getConfig(TWO_ID)`, then run this exact synthetic overflow comparison:
   ```bash
   wp eval '
   use ArraySubsPro\Features\FlexibleRenewalSync\Services\SegmentPlan as S;
   print_r(["two_product_config" => S::getConfig(TWO_ID)]);
   $two = ["cycle_days" => 30, "actives" => [1,2], "boundaries" => [15]];
   $one = ["cycle_days" => 30, "actives" => [3], "boundaries" => []];
   foreach ([14,15,16,30,31,32] as $d) {
       $segment = S::resolveSegment($d, $two);
       printf("two day=%d segment=%d mode=%s\n", $d, $segment, S::getSegmentMode($segment));
   }
   foreach ([30,31,32] as $d) {
       $segment = S::resolveSegment($d, $one);
       printf("one day=%d segment=%d mode=%s\n", $d, $segment, S::getSegmentMode($segment));
   }
   ' --allow-root
   ```
9. `wp post meta list <SUB_M> --allow-root`; record `_start_date` and the `_renewal_sync_*` set verbatim. Replace `SUB_M` and run the date-index probe below; the synthetic dates must return 29, 30, 31, and 32, while the stored start date records the live cohort's actual day:
   ```bash
   wp eval '
   use ArraySubsPro\Features\FlexibleRenewalSync\Services\SegmentPlan as S;
   $stored = (string) get_post_meta(SUB_M, "_start_date", true);
   printf("stored_start=%s day=%d\n", $stored, S::getDayInCycle($stored, "2026-07-31 18:00:00"));
   foreach (["2026-08-29 07:00:00", "2026-08-30 07:00:00", "2026-08-31 07:00:00", "2026-09-01 07:00:00"] as $start) {
       printf("start=%s day=%d\n", $start, S::getDayInCycle($start, "2026-07-31 18:00:00"));
   }
   ' --allow-root
   ```
10. Open `agent-browser --session admin-SLT-SYN-10` at Tools → Scheduled Actions → Pending and capture `/home/server-manager/slt-evidence/SLT-SYN-10-02-D8-pending-before.png`. Re-dump the current non-SLT2 schedule list and require an empty diff from `SLT-TT-00` before mutation. Abort if `<SUB_S3>` is not active, awaits cancellation, has a pending renewal order, or its anchor differs from step 5.
11. Capture `<SUB_S3>`'s original schedule state. Replace `SUBID` and run the same cron-safe, target-scoped recipe authorized by `SLT-TT-00`; then query the queue by exact args and require exactly one pending invoice row and one pending charge row for `<SUB_S3>`:
   ```bash
   wp eval '
   $id = SUBID;
   $due = gmdate("Y-m-d H:i:s", time() - HOUR_IN_SECONDS);
   update_post_meta($id, "_next_payment_date", $due);
   \ArraySubs\Features\RecurringBilling\Services\RenewalScheduler::unschedule($id);
   \ArraySubs\Features\RecurringBilling\Services\RenewalScheduler::schedule($id, time() + 12 * HOUR_IN_SECONDS);
   printf("id=%d forced_due=%s invoice_id=%s renewal_id=%s\n", $id, $due, get_post_meta($id, "_renewal_invoice_action_id", true), get_post_meta($id, "_renewal_action_id", true));
   ' --allow-root
   ```
12. Record the exact pre-run renewal-order set for numeric `<SUB_S3>`, then set `M_RENEW=$(/usr/local/bin/mailpit-agent latest-id 2>/dev/null || true)` immediately before the manual pair. In `admin-SLT-SYN-10`, re-snapshot before each click. Run the exact invoice ID first and wait for that ID to complete; then run the exact `arraysubs_process_renewal` ID and wait for that ID to complete. Never use `wp action-scheduler run`, never run by hook/group, and never click from a stale snapshot. Capture `/home/server-manager/slt-evidence/SLT-SYN-10-03-D8-invoice-run.png` and `SLT-SYN-10-04-D8-charge-run.png`.
13. Re-dump numeric `<SUB_S3>` meta and require exactly one new renewal order from its exact scheduled-cycle relationship plus reverse subscription link, never recency. Poll immutable `M_RENEW` in repeated calls no longer than 60 seconds through the five-minute cutoff for `Payment received for subscription #$SUB_S3`; save/show the exact match and classify every message newer than `M_RENEW`. Re-dump the non-SLT2 schedule list and require an empty diff from `SLT-TT-00`. Capture `/home/server-manager/slt-evidence/SLT-SYN-10-05-D8-renewal-order.png` and `SLT-SYN-10-06-D8-pending-after.png`, close only `admin-SLT-SYN-10`, independently review the D6 and D8 evidence, then move the card through `review` to `done` with Review empty. Any live defect goes only in `qa/issues/` kanban card named `SLT-SYN-10-<concise-slug>` with task/stage/plan path; product/customer/parent/subscription/renewal/action/message IDs; user ID/login/email/role; exact routes/sessions/time-travel bracket; reproduction; expected/actual; and UI/config/meta/queue/order/Mailpit/non-SLT-diff proof.

## Expected results
1. `getConfig(<MONTH_ID>)`: `cycle_days` 30, `actives [1,2,3]`, `boundaries [24,27]`; partition `1-24 / 25-27 / 28-30`.
2. resolveSegment: 1,24 -> 1 `full`; 25,27 -> 2 `prorate`; 28,29,30 -> 3 `next_cycle`; **31 -> 3, 32 -> 3** by fall-through to `end(actives)` — never 0, never an error.
3. 2-active: 14,15 -> 1; 16,30 -> 2; **31 -> 2, 32 -> 2 `prorate`** — overflow follows the LAST ACTIVE segment, not segment 3. The 3-active case cannot distinguish this; this one is the proof. 1-active: 30,31,32 -> 3 `next_cycle`.
4. `getDayInCycle` returns 29, 30, **31**, 32, and the stored `_start_date` reproduces SLT-PROD-12's predicted day.
5. The D6 checkout creates the dedicated `<SUB_S3>` cohort at **$30.00**, mode `next_cycle`, with first-full boundary `2026-09-30 18:00:00` UTC.
6. `<SUB_S3>`'s D8 renewal order totals **$30.00**; `_next_payment_date` becomes **`2026-10-31 18:00:00`** UTC = 2026-11-01 00:00 site (`2026-10-30 18:00:00` would be a 30-day add across 31-day October: defect). It stays `arraysubs-active`, mode still `next_cycle`, and the final non-SLT2 diff is empty.
7. Exactly its recorded invoice and charge rows run, in that order; no other action is manually executed.

## Emails expected
| # | Email | Trigger point | Recipient | Subject contains | Verify with |
|---|---|---|---|---|---|
| 1 | complete paid-checkout set | D6 step 3 | slt2-flex3@example.test + admin | active subscription, admin new subscription, WC new order, WC completed order | immutable-baseline polls ≤60 seconds through the two-minute cutoff, then complete delta |
| 2 | `payment_successful` + Woo renewal order mail | D8 step 12 | slt2-flex3@example.test + admin | exact numeric subscription/order subjects | just-in-time `M_RENEW`, repeated ≤60-second polls through the five-minute cutoff, then complete delta |
| 3 | `renewal_invoice` NONE EXPECTED | D8 invoice action | — | — | automatic-payment suppression; no invoice subject for `<SUB_S3>` |

## Evidence to capture
- `SLT-SYN-10-01..06`; D6 order/sub IDs and checkout/meta evidence; D8 `wp eval` transcripts for steps 7-9; `<SUB_M>`/`<SUB_S3>` meta before+after; exact invoice/charge IDs; renewal order ID/total; Mailpit IDs; empty non-SLT2 diff.

## Pass criteria
- [ ] Partition 1-24 / 25-27 / 28-30 on nominal 30 days; days 31, 32 -> last active segment (3-active)
- [ ] Days 31, 32 -> segment 2 `prorate` (2-active)
- [ ] `getDayInCycle` yields 31 for a 2026-08-31 start
- [ ] D6 purchase creates `<SUB_S3>` in segment 3 with first-full boundary 2026-09-30 18:00:00 UTC
- [ ] D8 renewal advances `<SUB_S3>` to 2026-10-31 18:00:00 UTC
- [ ] Exact invoice then charge IDs run one-at-a-time; no non-SLT2 date moved
- [ ] D6 receipt/count/mail/card proof and D8 relationship proof are exact; sessions close and Review returns to zero

## Isolation / teardown
- Only `<SUB_S3>` is mutated by this task; its prior value and action IDs are recorded and the deliberately forced date is not restored.
- Handoff: `<SUB_S3>` next renews 2026-11-01 site, outside the window — D9..D12 watch must record no further renewal and not score that as a miss. `<SUB_M>` and `<SUB_W3>` belong exclusively to `SLT-TT-00`; this task must not touch them. `SUB_NC` is the daily next-cycle fixture and is unrelated.

---

### Fresh-cycle validation contract

- Re-derive every ID, count, option value, gateway capability, scheduler timestamp, and email baseline on this run; no prior-cycle result is evidence.
- Create and mutate only registered `SLT2 ` / `slt2-*` fixtures. Legacy `SLT` and all non-SLT2 data are read-only controls.
- Automatic-gateway scope is Stripe and Paddle only. Stripe is the primary path; run Paddle parity wherever Paddle supports the behavior. Do not test or configure PayPal or Mollie.
- ArraySubs core must own its Stripe/Paddle integration, renewal, retry, webhook, REST, refund, and customer-payment services with Pro inactive; vendor host classes retain their expected ownership.
- Browser-required assertions use Vercel `agent-browser` with isolated task/role sessions and current snapshot refs. WP-CLI always includes `--allow-root`.
- Update the lifecycle card, the matching `qa/progress/` card, and `qa/issues/` for every new regression. Evidence belongs to this fresh cycle only.
