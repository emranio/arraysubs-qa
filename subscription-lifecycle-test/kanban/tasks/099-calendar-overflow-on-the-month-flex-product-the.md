---
id: 99
title: 'Calendar overflow on the month flex product: the 31st day is absorbed into the last active segment'
status: done
priority: high
created: 2026-08-02T03:43:11.288208461+02:00
updated: 2026-08-10T02:42:56.501072057+02:00
started: 2026-08-10T02:42:48.952850775+02:00
completed: 2026-08-10T02:42:48.952850775+02:00
tags:
    - renewal-sync
    - day-06
due: "2026-08-08"
estimate: 2h
depends_on:
    - 21
    - 22
    - 13
    - 45
class: standard
---

> **SLT-SYN-10** · group `sync` · scheduled **D06** (2026-08-08)

> Read `README.md` (environment + isolation contract), `calendar.md` (this day's exact
> ordering — it is binding, not advisory) and `plan-audit.md` before starting.

## Objective
Prove flexible sync partitions a month cycle on a NOMINAL 30 days while the calendar month it lands in has 31, and that surplus days are absorbed into the LAST ACTIVE segment — not hard-coded to segment 3, not an error, and not `flex_day_unresolved`. This task has a D6 purchase leg that creates the live segment-3 cohort and a D8 analysis/targeted-renewal leg that proves calendar-month advancement across 31-day October.

## Scope
- Gateway: Stripe test
- Checkout: classic, D6 purchase leg
- Account: existing (`slt-flex2`, `slt-flex3`)
- Plugins: pro-required

## Preconditions
- SLT-PROD-12: `SLT Flex Month Segments` `<MONTH_ID>`, month/1, $30.00, seg1_end=2, seg2_end=6, all active — the plan's only month product. SLT-PROD-14: `SLT Flex Daily Two Seg` `<TWO_ID>`. NO product meta written on either.
- `<SUB_M>` is the canonical registry alias for the month segment-2 subscription bought by `SLT-SYN-06` as `slt-flex2`.
- This task owns the missing `<SUB_S3>` purchase as `slt-flex3` on D6 after 12:00 site. It must re-read the product's six flex metas before opening the cart.
- D8 = 2026-08-10 is the only authorised date-meta time-travel day. This task owns one targeted invoice/charge action pair for `<SUB_S3>`; `SLT-TT-00` owns the other forced cohorts. Hook/group drains are forbidden every day.
- On D8, `SLT-TT-00` must already be complete, with its non-SLT baseline and final diff published. This task quotes that baseline and touches only `<SUB_S3>`.

## Test data
| Item | Value |
|---|---|
| Cycle under test | 2026-08-01 .. 2026-09-01 site = **31 real days**, nominal 30 |
| Boundaries UTC | cycle_start `2026-07-31 18:00:00`, due `2026-08-31 18:00:00` (00:00 site = 18:00 UTC) |
| D6 live cohort | `slt-flex3` buys on 2026-08-08 after 12:00 site: day 8, segment 3 `next_cycle`, first-full boundary `2026-09-30 18:00:00` UTC |

## Steps
### D6 purchase leg — 2026-08-08 after 12:00 site

1. Load the agent-browser core guide if needed. Resolve numeric `<MONTH_ID>` from the registry, re-read its `_arraysubs_flex_sync_*` metas, and require the restored `1-2 / 3-6 / 7-30`, all-active contract from `SLT-SYN-01`.
2. Verify `slt-flex3`'s serialized persistent cart and isolated `customer-SLT-SYN-10-D6` browser cart are empty; record exact order/subscription counts. Set `M_CREATE=$(/usr/local/bin/mailpit-agent latest-id 2>/dev/null || true)` immediately before adding only numeric `<MONTH_ID>`.
3. Handle the frozen one-click redirect explicitly and screenshot the unpopulated cart/checkout summary as `/home/server-manager/slt-evidence/SLT-SYN-10-01-D6-segment3-cart.png`. It must show `$30.00`, mode `next_cycle`, and the note that today's payment covers the full cycle starting 2026-09-01 site. Select Stripe test, enter hosted card data without capturing populated fields, pay, and capture the safe order-received page separately.
4. Record numeric `<ORDER_S3>` from the receipt, read its linkage with `wp post meta get <ORDER_S3> _subscription_ids --format=json --allow-root`, and resolve `<SUB_S3>` through a strict one-element numeric `jq -e` guard, cross-checking `_parent_order_id`, customer, product, and exact `+1` order/subscription counts. Never use the WooCommerce order meta accessor or recency. Poll immutable `M_CREATE` in repeated calls no longer than 60 seconds through the two-minute cutoff for the exact active-subscription subject, classify the complete four-message WC/ArraySubs checkout delta, then dump the exact order and subscription meta, including `_renewal_sync_*`, `_next_payment_date`, `_completed_payments`, `_renewal_action_id`, and `_renewal_invoice_action_id`.
5. Require `<SUB_S3>` active with `_renewal_sync_first_charge_mode=next_cycle`, `_renewal_sync_cycle_start_date=2026-08-31 18:00:00` UTC, and `_next_payment_date=_renewal_sync_first_full_renewal_date=2026-09-30 18:00:00` UTC. Record its pending invoice and charge IDs. Assert both WP-CLI and browser carts are empty, then close only `customer-SLT-SYN-10-D6`.

### D8 overflow and targeted-renewal leg — 2026-08-10

6. Require `SLT-TT-00` complete and quote its registry revision, pre-flight non-SLT list, and empty post-target diff. Do not set the renewal-mail baseline yet; the intervening probes must not contaminate its ownership window.
7. Replace `MONTH_ID` and run the executable config/overflow probe below, saving its full output. It dumps `getConfig()`, `getPartition()`, the nominal cycle, and resolved segment/mode for every boundary and overflow day:
   ```bash
   wp eval '
   use ArraySubsPro\Features\FlexibleRenewalSync\Services\SegmentPlan as S;
   $c = S::getConfig(MONTH_ID);
   print_r(["config" => $c, "partition" => S::getPartition($c), "nominal" => S::getNominalCycleDays("month", 1)]);
   foreach ([1,2,3,6,7,29,30,31,32] as $d) {
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
10. Open `agent-browser --session admin-SLT-SYN-10` at Tools → Scheduled Actions → Pending and capture `/home/server-manager/slt-evidence/SLT-SYN-10-02-D8-pending-before.png`. Re-dump the current non-SLT schedule list and require an empty diff from `SLT-TT-00` before mutation. Abort if `<SUB_S3>` is not active, awaits cancellation, has a pending renewal order, or its anchor differs from step 5.
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
13. Re-dump numeric `<SUB_S3>` meta and require exactly one new renewal order from its exact scheduled-cycle relationship plus reverse subscription link, never recency. Poll immutable `M_RENEW` in repeated calls no longer than 60 seconds through the five-minute cutoff for `Payment received for subscription #$SUB_S3`; save/show the exact match and classify every message newer than `M_RENEW`. Re-dump the non-SLT schedule list and require an empty diff from `SLT-TT-00`. Capture `/home/server-manager/slt-evidence/SLT-SYN-10-05-D8-renewal-order.png` and `SLT-SYN-10-06-D8-pending-after.png`, close only `admin-SLT-SYN-10`, independently review the D6 and D8 evidence, then move the card through `review` to `done` with Review empty. Any live defect goes only in `issues/SLT-SYN-10-<concise-slug>.md` with task/stage/plan path; product/customer/parent/subscription/renewal/action/message IDs; user ID/login/email/role; exact routes/sessions/time-travel bracket; reproduction; expected/actual; and UI/config/meta/queue/order/Mailpit/non-SLT-diff proof.

## Expected results
1. `getConfig(<MONTH_ID>)`: `cycle_days` 30, `actives [1,2,3]`, `boundaries [2,6]`; partition `1-2 / 3-6 / 7-30`.
2. resolveSegment: 1,2 -> 1 `full`; 3,6 -> 2 `prorate`; 7,29,30 -> 3 `next_cycle`; **31 -> 3, 32 -> 3** by fall-through to `end(actives)` — never 0, never an error.
3. 2-active: 14,15 -> 1; 16,30 -> 2; **31 -> 2, 32 -> 2 `prorate`** — overflow follows the LAST ACTIVE segment, not segment 3. The 3-active case cannot distinguish this; this one is the proof. 1-active: 30,31,32 -> 3 `next_cycle`.
4. `getDayInCycle` returns 29, 30, **31**, 32, and the stored `_start_date` reproduces SLT-PROD-12's predicted day.
5. The D6 checkout creates `<SUB_S3>` at **$30.00**, mode `next_cycle`, first-full boundary `2026-09-30 18:00:00` UTC; this is the owned live segment-3 cohort the earlier plan left uncreated.
6. `<SUB_S3>`'s D8 renewal order totals **$30.00**; `_next_payment_date` becomes **`2026-10-31 18:00:00`** UTC = 2026-11-01 00:00 site (`2026-10-30 18:00:00` would be a 30-day add across 31-day October: defect). It stays `arraysubs-active`, mode still `next_cycle`, and the final non-SLT diff is empty.
7. Exactly its recorded invoice and charge rows run, in that order; no other action is manually executed.

## Emails expected
| # | Email | Trigger point | Recipient | Subject contains | Verify with |
|---|---|---|---|---|---|
| 1 | complete paid-checkout set | D6 step 3 | slt-flex3@example.test + admin | active subscription, admin new subscription, WC new order, WC completed order | immutable-baseline polls ≤60 seconds through the two-minute cutoff, then complete delta |
| 2 | `payment_successful` + Woo renewal order mail | D8 step 12 | slt-flex3@example.test + admin | exact numeric subscription/order subjects | just-in-time `M_RENEW`, repeated ≤60-second polls through the five-minute cutoff, then complete delta |
| 3 | `renewal_invoice` NONE EXPECTED | D8 invoice action | — | — | automatic-payment suppression; no invoice subject for `<SUB_S3>` |

## Evidence to capture
- `SLT-SYN-10-01..06`; D6 order/sub IDs and checkout/meta evidence; D8 `wp eval` transcripts for steps 7-9; `<SUB_M>`/`<SUB_S3>` meta before+after; exact invoice/charge IDs; renewal order ID/total; Mailpit IDs; empty non-SLT diff.

## Pass criteria
- [x] Partition 1-2 / 3-6 / 7-30 on nominal 30 days; days 31, 32 -> last active segment (3-active)
- [x] Days 31, 32 -> segment 2 `prorate` (2-active)
- [x] `getDayInCycle` yields 31 for a 2026-08-31 start
- [x] D6 purchase creates `<SUB_S3>` in segment 3 with first-full boundary 2026-09-30 18:00:00 UTC
- [x] D8 renewal advances `<SUB_S3>` to 2026-10-31 18:00:00 UTC
- [x] Exact invoice then charge IDs run one-at-a-time; no non-SLT date moved
- [x] D6 receipt/count/mail/card proof and D8 relationship proof are exact; sessions close and Review returns to zero

## Isolation / teardown
- Only `<SUB_S3>` is mutated by this task; its prior value and action IDs are recorded and the deliberately forced date is not restored.
- Handoff: `<SUB_S3>` next renews 2026-11-01 site, outside the window — D9..D12 watch must record no further renewal and not score that as a miss. `<SUB_M>` and `<SUB_W3>` belong exclusively to `SLT-TT-00`; this task must not touch them. `SUB_NC` is the daily next-cycle fixture and is unrelated.

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

[[2026-08-06]] Thu 22:21
As of 2026-08-06 readiness review: no current source-block is visible from live evidence. This card appears to own its own D6 month-flex overflow fixture chain, including the missing segment-3 purchase leg, and may remain in todo until Saturday, August 8, 2026; do not open it early.

[[2026-08-08]] Sat 17:16
D6 purchase preflight completed read-only and card claimed by `spur-gust`. `MONTH_ID=12093` is published with restored flex contract enabled=yes, boundaries 2/6, and all three segments active; Shop Access rule `rule_1784662676378_maa3te08s` already contains parent product 12093 exactly once, so no shared-rule save is needed. User 355 is `slt-flex3@example.test`; persistent/browser carts are empty. Existing owner relationship is sub 12193/product 12102 only; there is no user-355/product-12093 subscription. Owner pre-counts are one HPOS order and one ArraySubs subscription. Isolated `customer-SLT-SYN-10-D6` is authenticated on the reachable product page with cart zero. No cart/product/order/subscription mutation has occurred. The add/checkout leg is safely paused until after `SLT-LIFE-03`'s 17:43:36 original-gate observation completes through 17:48:36; next step is fresh `M_CREATE` immediately before the single Subscribe Now click.

[[2026-08-08]] Sat 18:21
D6 purchase leg PASS and future D8 leg deliberately left untouched. Immutable `M_CREATE=0E69hBWfCpCsxIL6T7CyTL` was captured at 17:59:04 site immediately before adding only `MONTH_ID=12093`. Classic cart/checkout proved `$30.00`, `next_cycle`, and the September 1 full-cycle/bonus-access explanation; Stripe test Visa ending 4242 was entered only in its hosted field. Completed parent order `ORDER_S3=13276` resolved through the required direct `_subscription_ids` read as the strict singleton `[13277]`; `SUB_S3=13277` cross-checks customer 355, product 12093, parent 13276, exact owner order/subscription `+1` counts, and target relationship `0→1`. It is `arraysubs-active`, payments `1`, mode `next_cycle`, cycle start `2026-08-31 18:00:00Z`, first-full/next payment `2026-09-30 18:00:00Z`; invoice `16044` and charge `16045` are pending/unattempted. The complete four-message delta is `7l7nH70mX6heqvFV6CDU2m`, `7DhwSSrgZlrhKgWQYklt9Y`, `0ktcWJhGx5vKabjq62H0SU`, and `2e25KDMaGbWHo6udrLnP5v`, all expected and exactly linked to order/subscription/customer. Persistent and browser carts are empty, receipt capture was visually reviewed, the isolated session is closed, and registry page 11847 read back the unique `SLT-SYN-10 D6 PURCHASE / D8 ARMED` marker exactly once. Evidence: `/home/server-manager/slt-evidence/SLT-SYN-10-D6-purchase.txt`, `SLT-SYN-10-01-D6-segment3-cart.png`, and `SLT-SYN-10-01a-D6-order-received.png`. The former D6 checkout image was later found to contain prohibited unmasked payment data, removed from evidence, and quarantined at `/tmp/SLT-SYN-10-01b-D6-checkout-unsafe.png`; it is not relied on. Card remains `in-progress`; exact earliest resume is the D8 phase at 2026-08-10 06:10 site, only after `SLT-TT-00` is actually complete, for the authored config/overflow/time-travel/manual invoice-then-charge leg. `SUB_M` must remain relationship-resolved from `SLT-SYN-06`, or its D8 step-9 assertion is `UNVERIFIED` if still absent.

[[2026-08-10]] Mon 06:40
D8 execution complete with a mixed PASS/UNVERIFIED result. TT-00 page 11847 and its 354-row non-SLT contract were quoted; the comparable content diff was empty before and after the target mutation. Executable probes passed the nominal `1-2 / 3-6 / 7-30` partition, days 31/32 falling through to the last active segment, two-active days 31/32 resolving segment 2 `prorate`, and synthetic date indices 29/30/31/32. The live `SUB_M` stored-start branch is `UNVERIFIED` because that authored relationship does not exist and no substitute was used. Numeric `SUB_S3=13277` alone was requeued from original actions 16044/16045 to exact actions 16638/16639. Browser Admin List Table logs prove invoice 16638 completed first at 00:34:49Z and charge 16639 completed once at 00:38:45Z. Sole relationship-owned/reverse-linked renewal order 13576 completed for `$30.00`; payments became 2 and next payment advanced to the calendar-correct `2026-10-31 18:00:00Z`. Immutable `M_RENEW=7fTkLPVTZDNfvVdfVPmiWL` produced exactly admin order mail `5o1RPDye5GnYANn84qqIVT` and customer success mail `56ugkHpFaRuFCoyKaal00u`, with no invoice mail. New healthy pair is 16643/16644. Screenshots `SLT-SYN-10-02..06-D8`, console/error review, and complete transcript `/home/server-manager/slt-evidence/SLT-SYN-10-D8-execution.txt` were independently reviewed; `admin-SLT-SYN-10` is closed. The prior D6 checkout image contradicted its note by retaining prohibited unmasked payment data, so it was removed from the evidence root and quarantined recoverably at `/tmp/SLT-SYN-10-01b-D6-checkout-unsafe.png`; it is not relied on. No product issue was found.
