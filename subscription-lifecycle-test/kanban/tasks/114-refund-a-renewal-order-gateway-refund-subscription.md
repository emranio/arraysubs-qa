---
id: 114
title: 'Refund a renewal order: gateway refund, subscription effect, and emails'
status: todo
priority: high
created: 2026-08-02T03:43:12.458047506+02:00
updated: 2026-08-02T03:43:23.925598623+02:00
tags:
    - admin
    - portal
    - day-09
    - has-conflicts
due: "2026-08-11"
estimate: 1h30m
depends_on:
    - 48
    - 49
    - 58
    - 20
class: standard
---

> **SLT-ADM-08** · group `admin` · scheduled **D09** (2026-08-11)

> Read `README.md` (environment + isolation contract), `calendar.md` (this day's exact
> ordering — it is binding, not advisory) and `plan-audit.md` before starting.

### ⚠ Conflict resolutions that apply to this task

**`high` · dependency-gap / unowned purchases** — with `SLT-ADM-07`, `SLT-MYA-04`, `SLT-SW-01`, `SLT-SW-03`, `SLT-SW-02`, `SLT-EML-08`

- *Problem:* Five purchases that multiple tasks treat as preconditions are owned by no task key in the index - they existed only as free-text 'purchases owned by other groups' rows in the superseded calendar. (a) S_FEE: slt-core's SLT Signup Fee Daily subscription, required by SLT-ADM-07 ('bought D3 by slt-core'), SLT-MYA-04 and SLT-ADM-08 (which refunds and cancels it). (b) S-BASIC and S-PRO: slt-switch's SLT Plan Basic and SLT Plan Pro subscriptions 'bought D4', required by SLT-SW-01, SW-03, SW-02 and SLT-EML-08. (c) SLT Flex Month Segments segment-3 by slt-flex3 on 2026-08-08, required by SLT-SYN-10 (SUB_S3, _next_payment_date 2026-09-30 18:00:00). (d) The D8 time-travel renewals for month segment-1/segment-2, week segment-3 (SLT-SYN-07's tail, due 2026-08-15) and the flex-variable tail - audit C17 mandates one dedicated D8 owner and none exists. (e) SLT-SYN-10 also references SUB_S2 which SLT-SYN-06 does buy, so only seg-3 is missing.
- *Required fix:* Assign explicit owners. Add step 0 to SLT-ADM-07: 'slt-core buys SLT Signup Fee Daily on D3 after 12:00 (order + subscription ids to the registry)'. Create SLT-SW-00 on D4: 'slt-switch buys SLT Plan Basic and SLT Plan Pro on Stripe after 12:00' as the ladder canvas for SW-01/02/03 and EML-08. Add step 0 to SLT-SYN-10: 'slt-flex3 buys SLT Flex Month Segments on 2026-08-08 (D6) after 12:00 - day-in-cycle 8, past both boundaries, resolves to segment 3, next payment 2026-10-01 00:00 site = 2026-09-30 18:00 UTC'. Create SLT-TT-00 on D8 as the single time-travel owner: pre-flight pending-queue screenshot + the 13 non-SLT _next_payment_date snapshot, then the month seg1/seg2 and week seg3 renewals and the flex-variable tail, single-action-by-id only, then the post-drain non-SLT diff proof - and have SYN-10, SW-02, EML-08, EML-10 and LIFE-01 quote its snapshot instead of each taking their own.

---
## Objective
Refund a paid renewal order under `auto_gateway_refund = true` and `allow_prorated_refunds = true`, proving three consequences separately: money returns at the gateway; the subscription reacts only to a FULL refund (`Refunds\Hooks::checkForFullRefund()` -> `cancelSubscriptionAfterRefund()`); the right mail goes out. A partial refund must change nothing on the sub.

## Scope
- Gateway: Stripe test
- Checkout: N/A
- Account: existing (slt-core)
- Plugins: both

## Preconditions
- SLT-ADM-06/07 done. S_FEE = `SLT Signup Fee Daily` sub (slt-core), active; RX = its latest PAID renewal (`$9.00`). S_STEP = `SLT Renewal Price Step` sub (slt-core), active — preview only, never refunded.
- Baseline unchanged: `refunds.cancellation_behavior = immediate` plus the two above — do NOT change them. Run D9 = 2026-08-11 after the morning watch report.

## Test data
| Item | Value |
|---|---|
| Order | RX, paid renewal of S_FEE, `$9.00` |
| Refunds | `$4.00` `SLT-ADM-08 partial`, then `$5.00` `SLT-ADM-08 full` |

## Steps
1. `mailpit-agent latest-id` -> `M0`. Record RX total, S_FEE status and `_refund_history` (empty).
2. Screenshot `...#/settings/refunds`: both toggles ON; change nothing.
3. Open `...page=wc-orders&action=edit&id=<RX>` -> `snapshot -i`. Click **Refund**, amount `4.00`, reason `SLT-ADM-08 partial`, then **Refund $4.00 via …** (gateway, never "manually"). Re-snapshot the refund row and status.
4. `mailpit-agent list 20`; `wp post meta list <S_FEE> --keys=_refund_history,_end_date,_cancelled_date`; `wp post list --post_type=arraysubs_data --include=<S_FEE> --fields=ID,post_status`.
5. Open `...#/subscriptions/detail/<S_FEE>`; copy the newest note verbatim.
6. Repeat step 3 for the remaining `5.00`, reason `SLT-ADM-08 full`, via the gateway.
7. `mailpit-agent wait-new M0 180 "has been cancelled"`, then `list 30`; record every id/subject since M0.
8. Re-run step 4 plus `--keys=_cancelled_by,_cancellation_reason,_refund_cancellation_order_id`; check Tools -> Scheduled Actions for pending S_FEE actions.
9. Open `/my-account/view-subscription/<S_FEE>/` as `--session cust-adm08` (`slt-core`); screenshot status + **Refund History**.
10. On `#/subscriptions/detail/<S_STEP>` click **Prorated Refund**, record the modal's amount, days unused and cycle days, screenshot, then **close it without clicking Process Refund**. If `_last_payment_date` is empty it credits a full cycle (L21).
11. In `...page=wc-status&tab=logs` (source `stripe`) confirm both refunds reached Stripe; record ids.

## Expected results
1. Partial refund: order shows `-$4.00`, status stays Processing/Completed (NOT `refunded`); Stripe log shows a gateway refund.
2. After it: S_FEE still `arraysubs-active`, `_end_date`/`_cancelled_date` absent, `_refund_history` has one entry `amount = 4`, no cancellation mail.
3. Note reads `Refund processed: $4.00 for order #<RX>. Reason: SLT-ADM-08 partial`.
4. Full refund: RX fully refunded `$9.00`, status `refunded`; S_FEE flips to `arraysubs-cancelled` with `_end_date`/`_cancelled_date` set, `_cancelled_by = system`, `_cancellation_reason` names the full refund, `_refund_cancellation_order_id = <RX>`, both `_refund_history` entries present, no pending action.
5. My-account shows S_FEE cancelled with both refunds under Refund History; the prorated preview returns a figure and changes nothing.

## Emails expected
| # | Email | Trigger point | Recipient | Subject contains | Verify with |
|---|---|---|---|---|---|
| 1 | WooCommerce (partially) refunded | steps 3, 6 | slt-core | `refunded` | `mailpit-agent list 20` each time |
| 2 | `subscription_cancelled` | full refund cancels S_FEE | slt-core | `has been cancelled` | `wait-new M0 180` |
| 3 | `admin_subscription_cancelled` | same | admin | `cancelled by` | `mailpit-agent list 30` |
| 4 | NONE EXPECTED after the partial | step 3 | — | `cancelled` | No cancellation subject between steps 3-6 |

## Evidence to capture
- Screenshots `SLT-ADM-08-01-settings.png`, `-02-partial.png`, `-03-note.png`, `-04-full.png`, `-05-history.png`, `-06-preview.png`; RX id, Stripe refund ids, `_refund_history`, Mailpit ids, note texts.
- Carry the SLT-ADM-06 finding: `findRefundableOrder()` looks renewals up by `_arraysubs_subscription_id`, which Stripe renewals lack — say whether the preview resolved an order.

## Pass criteria
- [ ] Both refunds executed at Stripe (log + ids), not "manually"
- [ ] Partial leaves S_FEE active, writes the exact note, no cancellation mail
- [ ] Full refund sets order `refunded`, cancels S_FEE with the listed metas
- [ ] `subscription_cancelled` + `admin_subscription_cancelled` arrive
- [ ] Refund History correct in my-account; no renewal left scheduled
- [ ] Prorated preview read; modal closed unprocessed

## Isolation / teardown
- S_FEE ends cancelled a day early — record it in the registry so SLT-SETUP-99A skips it and D11/D12 expect no further renewal. No setting written; S_STEP and all other SLT subscriptions untouched. Close `cust-adm08`.

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
