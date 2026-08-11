---
id: 106
title: 'Renewal execution after a synced first charge: second charge full on the boundary, third on the grid'
status: in-progress
priority: critical
created: 2026-08-02T03:43:11.848514479+02:00
updated: 2026-08-11T20:09:58.113811579+02:00
tags:
    - renewal-sync
    - day-07
due: "2026-08-09"
estimate: 2h
depends_on:
    - 14
    - 45
    - 28
class: standard
---

> **SLT-SYN-09** · group `sync` · scheduled **D07** (2026-08-09)

> Read `README.md` (environment + isolation contract), `calendar.md` (this day's exact
> ordering — it is binding, not advisory) and `plan-audit.md` before starting.

## Objective
Prove a synced first charge does not distort the schedule: the SECOND charge is the FULL recurring amount on the boundary (even where #1 was prorated to $6.00), and the THIRD stays on the grid because the next due derives from `_renewal_scheduled_date`, not payment time (`OrderIntegration.php:1629-1652` → `:1472-1526`).

## Scope
- Gateway: Stripe test
- Checkout: N/A (unattended renewals)
- Account: existing `slt-flex` + `slt-flex2`
- Plugins: pro-required

## Preconditions
- SLT-SYN-05 (`SUB_W1`, week seg-1, #1 $14.00), SLT-SYN-06 (`SUB_W`, week seg-2, #1 $6.00), SLT-SYN-08 (`SUB_2SEG`, `SLT Flex Daily Two Seg`, #1 $9.00) done, `k` recorded.
- The preceding unattended-watch phases saved four immutable, task-specific Mailpit baselines in the registry and evidence root: `SYN09_2SEG_D4_PRE` (D3 21:42), `SYN09_W1_D6_PRE` and `SYN09_W_D6_PRE` (D5 21:42), and `SYN09_2SEG_D7_PRE` (D6 21:42). Each was recorded before its exact pending charge action. If one is missing, do not substitute a recent-message list: mark only that event `UNVERIFIED` and preserve the other three proofs.
- **Act on D7 = 2026-08-09 after 07:00 site.** Renewals fire at boundary + `k` (0–6 h), so an earlier read proves nothing.
- **Nothing may be force-run.** A renewal not fired by `boundary + k + 15 min` is a real bug — capture evidence and write one standalone markdown file under `issues/`; never create a lifecycle-board bug card, and a bare `--hooks=` drain is forbidden.

## Test data
| Sub | #1 | #2 due UTC / amount | #3 due UTC / amount |
|---|---|---|---|
| `SUB_W1` week seg-1 | $14.00 `full` | `08-07 18:00` +k / **$14.00** | `08-14 18:00`, past D9 / $14.00 |
| `SUB_W` week seg-2 | $6.00 `prorate` | `08-07 18:00` +k / **$14.00** | `08-14 18:00`, past D9 / $14.00 |
| `SUB_2SEG` day/3 two-seg | $9.00 | `08-05 18:00` +k, fired D4 / **$9.00** | `08-08 18:00` +k / **$9.00** |

`SUB_2SEG` after #3: `_next_payment_date = 2026-08-11 18:00:00` (2026-08-12 00:00 +06) — exactly 3 days, no drift to the payment clock.

## Steps
1. Resolve registry aliases `SUB_W1`, `SUB_W`, and `SUB_2SEG` into same-named shell variables; require exactly one registry match for each, abort unless all three match `^[0-9]+$` and are distinct, then cross-check their recorded parent order, customer, and product relationships. Recompute `k` from each numeric ID with the README argv-based crc32 one-liner; write the window `[boundary+k, +15min]` into the notes BEFORE looking at results.
2. Per sub open the exact numeric subscription-filtered completed actions in `admin-SLT-SYN-09-D7`; capture uniquely named `SLT-SYN-09-01-completed-<alias>.png`; confirm the exact invoice/charge action IDs, gates, and `via WP Cron` logs.
3. For every expected cycle, resolve the renewal order from numeric subscription plus `_renewal_scheduled_date`/cycle and require reverse linkage, never customer recency. Capture uniquely named `SLT-SYN-09-02-orders-<alias>-<cycle>.png` and record total, status, `_is_renewal_order`, cycle number, scheduled date, and order-mail ID.
4. Per sub dump `_next_payment_date`, `_last_payment_date`, `_completed_payments`, `_pending_renewal_order_id`, `_payment_retry_attempts` to `/home/server-manager/slt-evidence/SLT-SYN-09-after.csv`; screenshot each schedule panel `SLT-SYN-09-03-sched.png`.
5. Consume the four registered baselines separately. For each, inspect every newer message and require exactly one subject tied to the expected subscription: `SUB_2SEG` after `SYN09_2SEG_D4_PRE`, `SUB_W1` after `SYN09_W1_D6_PRE`, `SUB_W` after `SYN09_W_D6_PRE`, and `SUB_2SEG` after `SYN09_2SEG_D7_PRE`. Save/show each exact `Payment received for subscription #<id>` match and classify its complete baseline delta; confirm no `Payment failed`, `on hold`, or `Invoice for subscription` in those deltas. This is **four renewal-success messages for four renewal events**, even though only three distinct subscriptions are involved.
6. Re-open each pending queue; screenshot `SLT-SYN-09-04-pending.png`; confirm re-queued legs sit at the NEW due + SAME `k`.
7. Publish the exact D10 charge action/gate and `charge−300s` deadline, then close `admin-SLT-SYN-09-D7`. Inside the D9 watch's exact `[charge−300s,charge)` interval save immutable `SYN09_2SEG_D10_PRE` with its action ID. Follow-up on **D10 = 2026-08-12**: poll in repeated calls no longer than 60 seconds through the 10-minute cutoff, require/save/show the exact payment-success match, and resolve linked #4 from numeric SUB_2SEG plus scheduled-cycle relationship/reverse link. In fresh `admin-SLT-SYN-09-D10` capture the order/action proof, verify the due/next-date grid, close it, independently review all five renewal events, then move through `review` to `done` with Review empty. Any defect goes only in `issues/SLT-SYN-09-<concise-slug>.md` with task/stage/plan path; product/customer/subscription/parent/renewal/action/message IDs; user login/email/role; exact routes/sessions/gates; reproduction; expected/actual; and UI/meta/queue/log/order/Mailpit proof.

## Expected results
1. `SUB_W` charge #2 is exactly **$14.00**, not $6.00 — proration hit the signup only; `_renewal_sync_initial_recurring_amount` stays `6`, never reused.
2. `SUB_W1` #2 is exactly **$14.00**; both week renewal orders carry `_renewal_scheduled_date = 2026-08-07 18:00:00`.
3. Both week subs: `_next_payment_date = 2026-08-14 18:00:00`, `_completed_payments = 2`, `arraysubs-active`, orders paid, `_pending_renewal_order_id` cleared.
4. `SUB_2SEG`: #2 `$9.00` at `2026-08-05 18:00:00 +k`, #3 `$9.00` at `2026-08-08 18:00:00 +k`, `_completed_payments = 3`, `_next_payment_date = 2026-08-11 18:00:00`; consecutive dues exactly 259200 s apart — the grid holds.
5. Every renewal fired inside `[due+k, due+k+15min]`, and the same `k` is reused for the re-queued legs (the offset is permanent per sub). No retries, no on-hold, no failed orders, no tax lines.

## Emails expected
| # | Email | Trigger point | Recipient | Subject contains | Verify with |
|---|---|---|---|---|---|
| 1 | `payment_successful` ×4 by D7: `SUB_2SEG` D4 and D7, `SUB_W1` D6, `SUB_W` D6 | renewal ok | slt-flex / slt-flex2 | `Payment received for subscription #<exact ID>` | four distinct registered pre-event baselines; exact match plus full delta for each |
| follow-up | `payment_successful` ×1 for `SUB_2SEG` #4 | D10 renewal ok | slt-flex2 | `Payment received for subscription #<SUB_2SEG>` | `SYN09_2SEG_D10_PRE`; exact match plus full delta |
| 2 | `renewal_invoice` NONE EXPECTED | invoice leg | — | — | suppressed for auto-pay subs (REF-04 §4); arrival = bug |
| 3 | `payment_failed`/`subscription_on_hold` NONE EXPECTED | — | — | — | absent from each complete registered owner delta; presence tied to an exact target is failure |

## Evidence to capture
- `SLT-SYN-09-01..04`; `-after.csv`; renewal order IDs and totals; the three `k` values and windows; all five registered baseline values and their pending action IDs; exact-match/full-delta Mailpit ids; any failed AS rows.

## Pass criteria
- [ ] `SUB_W` charge #2 is $14.00, not $6.00
- [ ] Both week subs land #2 at `2026-08-07 18:00:00 +k`, next due `2026-08-14 18:00:00`
- [ ] `SUB_2SEG` #2/#3 are $9.00, next due `2026-08-11 18:00:00`, dues 259200 s apart, same `k`
- [ ] Four bounded `payment_successful` mails through D7; no invoice, failed or on-hold mail
- [ ] D10 `SUB_2SEG` #4 and its bounded payment-success mail proved before closing the card
- [ ] Nothing was force-run
- [ ] Every order/action is relationship-exact, phase sessions close, and D10 review reaches `done` with Review empty

## Isolation / teardown
- Handed on: `SUB_2SEG` (due 2026-08-12 site, then 2026-08-15) and both week subs (due 2026-08-14) stay alive into the watch tail — they must NOT be cancelled by the D10 wind-down (plan-audit's SLT-SETUP-99 split).
- Restores: none; read-only. Close only the exact D7/D10 admin sessions named above.


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

## D06 21:42 handoff — 2026-08-08

`SYN09_2SEG_D7_PRE=5tA67qq4BNBf5zTQDGTVct` was captured at `2026-08-08 21:42:33` site and appended/read back on registry page `11847`. Relationship pre-state: `SUB_2SEG=12172`, active, completed payments `2`, next `2026-08-08 18:00:00Z`, exact pending order `13273` at `$9.00`, exact charge action `15005` pending/unattempted at `2026-08-08 18:11:39Z` / D7 `00:11:39` site. The same timestamped cursor was separately registered as the D7 watch baseline for `SUB_NC=12193`, order `13302`, action `14003` at D7 `00:40:35`; do not substitute a later recent-message list. Evidence: `/home/server-manager/slt-evidence/D06-2026-08-08-evening-natural-gates.txt`.

[[2026-08-09]] Sun 02:16
D07 early-morning preparation started at 2026-08-09 06:20 site. No renewal result was read before the authored after-07:00 gate; task remains read-only and nothing will be forced.

[[2026-08-09]] Sun 03:24
D07 authored post-07:00 leg executed from gate-open poll 2026-08-09 07:00:13 site; fresh result read 07:00:36. PASS for live branches: SUB_W1=12039 settled cycle #2 naturally for $14.00 in its k=18112 window, order 13170 / mail 668D3zIwlFM3x6xEqZfmMs, and requeued 15836/15837 at the same offset; SUB_2SEG=12172 settled cycles #2/#3 naturally for $9.00 each, orders 12714/13273, current completed-payments 3 / next 2026-08-11 18:00:00Z / same k=699, and bounded D7 mail 5r2hikZq4ipuKQ5y8P4o5Y. UNVERIFIED only where authored sources are absent: SUB_W / SYN09_W_D6_PRE are unavailable, and SYN09_2SEG_D4_PRE was never recorded; no substitute cursor or subscription was used. Evidence: /home/server-manager/slt-evidence/SLT-SYN-09-D07-read.txt, /home/server-manager/slt-evidence/SLT-SYN-09-after.csv, and task-prefixed screenshots listed there. Nothing forced or mutated; exact sessions closed. Keep in progress. Exact next gate: D9 captures SYN09_2SEG_D10_PRE for pending action 16167 inside 2026-08-12 00:06:39-00:11:38 site; D10 charge gate is 00:11:39 site, followed by the authored bounded mail/order/action proof and only then review -> done.

[[2026-08-09]] Sun 03:35
Independent D07 self-review completed. Raw complete Mailpit inventories are now persisted in /home/server-manager/slt-evidence/SLT-SYN-09-mail-deltas-D07.txt and mechanically match the live 63-message/16-message cursor slices. Low incidental admin finding: issues/light-plugin-SLT-SYN-09-order-editor-sample-permalink-403.md, with sanitized proof /home/server-manager/slt-evidence/SLT-SYN-09-order-editor-network.txt; it does not change the passed lifecycle assertions. Visual limitations carried to the D10 closeout: SLT-SYN-09-02-orders-SUB_2SEG-2.png is blank, and the 04 files duplicate the All-search captures rather than distinct Pending-filter views. The relationship-exact structured/order-history/action proof remains valid, but D10 must recapture order 12714 and distinct Pending-filter views before final review -> done. Keep in progress at the already-recorded D10 gate.

[[2026-08-10]] Mon 15:33
D08 evening gate review: SLT-SYN-09 is not eligible today. Keep in progress without mutation. The D9 21:42 invocation must remain alive until the exact 2026-08-12 00:06:39-00:11:38 site window and capture immutable SYN09_2SEG_D10_PRE tied to SUB_2SEG=12172 and the freshly verified pending charge row, currently action 16167 at 00:11:39. D10 then verifies the persisted mail/order/action/grid result, recaptures order 12714 and genuine Pending-filter views, preserves the documented source-gap UNVERIFIED verdicts, and reviews to done.

[[2026-08-10]] Mon 22:43 site
D08 night revalidation: keep SLT-SYN-09 in progress without mutation. SUB_2SEG=12172 remains active with three completed payments and next 2026-08-11 18:00:00Z; exact action 16167 is pending/unattempted for 2026-08-11 18:11:39Z / D10 00:11:39 site. Exact next gate: the D9 invocation must capture immutable SYN09_2SEG_D10_PRE inside 2026-08-12 00:06:39-00:11:38 site, then D10 verifies persisted evidence through 00:21:39. Before review to done, recapture order 12714 and genuine distinct Pending-filter views, and retain the documented SUB_W and missing D4-baseline UNVERIFIED limits.

[[2026-08-11]] Tue 20:09
D09 night immutable handoff completed inside the authored window. SYN09_2SEG_D10_PRE=3y7ZwXRbvO1bMTiuPnorLN captured 2026-08-12 00:08:17-00:08:19 site while SUB_2SEG=12172 remained active at payments=3 / next=2026-08-11 18:00:00Z, relationship order 13788 remained wc-pending USD 9.00 cycle 4, and action 16167 remained pending/unattempted for 00:11:39 site. Browser-only append/readback on private registry page 11847 produced exact marker cardinality 1 and closed admin-SLT-SYN-09-D9. Separate pre-06:10 labels D10_NC_PRE, D10_CORE2_PRE, D10_W1_REM_PRE, and D10_WQ_REM_PRE own actions 16177, 16930, 15838, and 15849. Evidence: /home/server-manager/slt-evidence/SLT-SYN-09-D09-prebaseline.txt and task-prefixed registry screenshots. Nothing forced; D10 2026-08-12 06:10 site must verify persisted action/order/mail/grid evidence, recapture order 12714 and genuine Pending-filter views, preserve the documented source gaps, then self-review and move review -> done.
