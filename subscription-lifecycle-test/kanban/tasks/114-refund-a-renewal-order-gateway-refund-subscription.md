---
id: 114
title: 'Refund a renewal order: gateway refund, subscription effect, and emails'
status: todo
priority: high
created: 2026-08-22T19:10:00+02:00
updated: 2026-08-22T19:10:00+02:00
tags:
    - cycle-2
    - granular
    - admin
    - portal
    - day-09
due: "2026-09-01"
estimate: 1h30m
depends_on:
    - 48
    - 49
    - 58
    - 20
    - 19
class: standard
---

> **SLT-ADM-08** · group `admin` · scheduled **D09** (2026-09-01)

> Read `README.md` (environment + isolation contract), `calendar.md` (this day's exact
> ordering — it is binding, not advisory) and `plan-audit.md` before starting.

## Objective
Refund a paid renewal order under `auto_gateway_refund = true` and `allow_prorated_refunds = true`, proving three consequences separately: money returns at the gateway; the subscription reacts only to a FULL refund (`Refunds\Hooks::checkForFullRefund()` -> `cancelSubscriptionAfterRefund()`); the right mail goes out. A partial refund must change nothing on the sub.

## Scope
- Gateway: Stripe test
- Checkout: N/A
- Account: existing (slt2-core)
- Plugins: both

## Preconditions
- SLT-ADM-06/07 done. S_FEE = `SLT2 Signup Fee Daily` sub (slt2-core), active; RX = its latest PAID renewal (`$9.00`). S5 = canonical `SLT2 Renewal Price Step` sub (slt2-core), active — preview only, never refunded.
- Baseline unchanged: `refunds.cancellation_behavior = immediate` plus the two above — do NOT change them. Run D9 = 2026-09-01 after the morning watch report.

## Test data
| Item | Value |
|---|---|
| Order | RX, paid renewal of S_FEE, `$9.00` |
| Refunds | `$4.00` `SLT-ADM-08 partial`, then `$5.00` `SLT-ADM-08 full` |

## Steps
1. Resolve numeric S_FEE/S5 and the intended paid cycle; derive numeric RX from S_FEE plus exact scheduled-cycle relationship and require reverse linkage, paid `$9.00`, Stripe transaction, and no prior refunds. Never choose the latest customer order. Record M0, exact order/refund counts, S_FEE status and refund history.
2. In `agent-browser --session admin-SLT-ADM-08`, screenshot `...#/settings/refunds`: both toggles ON; change nothing.
3. In `admin-SLT-ADM-08`, open `...page=wc-orders&action=edit&id=<RX>` -> `snapshot -i`. Click **Refund**, amount `4.00`, reason `SLT-ADM-08 partial`; immediately before **Refund $4.00 via …** set `PARTIAL_PRE=$(mailpit-agent latest-id)`, then use the gateway action (never "manually"). Re-snapshot the refund row and status.
4. Inspect every message newer than `PARTIAL_PRE`; require any WooCommerce refund mail to name `RX` and require zero cancellation subject naming `S_FEE`. Save/show all linked messages. Then run `wp post meta list <S_FEE> --keys=_refund_history,_end_date,_cancelled_date --allow-root`; `wp post list --post_type=arraysubs_data --include=<S_FEE> --fields=ID,post_status --allow-root`.
5. Open `...#/subscriptions/detail/<S_FEE>`; copy the newest note verbatim.
6. Repeat step 3 for the remaining `5.00`, reason `SLT-ADM-08 full`, via the gateway; immediately before the final gateway-refund click set `FULL_PRE=$(mailpit-agent latest-id)`.
7. Poll immutable FULL_PRE in repeated calls no longer than 60 seconds through the two-minute cutoff for exact S_FEE cancellation; then inspect the complete delta, require customer/admin cancellation subjects for S_FEE, correlate refund mail by exact RX, and save/show every linked message.
8. Re-run step 4 plus `--keys=_cancelled_by,_cancellation_reason,_refund_cancellation_order_id`; check Tools -> Scheduled Actions for pending S_FEE actions.
9. Open `/my-account/view-subscription/<S_FEE>/` as `--session cust-adm08-SLT-ADM-08` (`slt2-core`); screenshot status + **Refund History**.
10. On `#/subscriptions/detail/<S5>` click **Prorated Refund**, record the modal's amount, days unused and cycle days, screenshot, then **close it without clicking Process Refund**. If `_last_payment_date` is empty it credits a full cycle (L21).
11. In Stripe logs confirm both refunds reached the exact original transaction and record sanitized refund IDs. Verify exact refund counts/totals and no other order changed; prove S5 unchanged. Close both sessions, independently review partial/full/portal/preview/gateway evidence, publish S_FEE's early terminal state, then move through `review` to `done` with Review empty. Any live defect goes only in `qa/issues/` kanban card named `SLT-ADM-08-<concise-slug>` with task/stage/plan path; subscription/order/transaction/refund/action/message IDs; user ID/login/email/role; exact routes/sessions/timestamps; reproduction; expected/actual; and UI/meta/order/log/Mailpit proof.

## Expected results
1. Partial refund: order shows `-$4.00`, status stays Processing/Completed (NOT `refunded`); Stripe log shows a gateway refund.
2. After it: S_FEE still `arraysubs-active`, `_end_date`/`_cancelled_date` absent, `_refund_history` has one entry `amount = 4`, no cancellation mail.
3. Note reads `Refund processed: $4.00 for order #<RX>. Reason: SLT-ADM-08 partial`.
4. Full refund: RX fully refunded `$9.00`, status `refunded`; S_FEE flips to `arraysubs-cancelled` with `_end_date`/`_cancelled_date` set, `_cancelled_by = system`, `_cancellation_reason` names the full refund, `_refund_cancellation_order_id = <RX>`, both `_refund_history` entries present, no pending action.
5. My-account shows S_FEE cancelled with both refunds under Refund History; the prorated preview returns a figure and changes nothing.

## Emails expected
| # | Email | Trigger point | Recipient | Subject contains | Verify with |
|---|---|---|---|---|---|
| 1 | WooCommerce (partially) refunded | steps 3, 6 | slt2-core | `refunded`, order `RX` | complete `PARTIAL_PRE` / `FULL_PRE` deltas, correlated by order id |
| 2 | `subscription_cancelled` | full refund cancels S_FEE | slt2-core | `subscription #<S_FEE> has been cancelled` | immutable-baseline polls ≤60 seconds through the two-minute cutoff; exact match plus full delta |
| 3 | `admin_subscription_cancelled` | same | admin | `Subscription #<S_FEE> cancelled by` | complete `FULL_PRE` delta; exact id and admin `To:` |
| 4 | NONE EXPECTED after the partial | step 3 | — | `cancelled` | No cancellation subject between steps 3-6 |

## Evidence to capture
- Screenshots `SLT-ADM-08-01-settings.png`, `-02-partial.png`, `-03-note.png`, `-04-full.png`, `-05-history.png`, `-06-preview.png`; RX id, Stripe refund ids, `_refund_history`, `M0`/`PARTIAL_PRE`/`FULL_PRE`, exact-match/full-delta Mailpit ids, note texts.
- Revalidate refund-order resolution on the current build using both relationship meta shapes; require the preview to resolve the exact renewal order without relying on a previous missing-link observation.

## Pass criteria
- [ ] Both refunds executed at Stripe (log + ids), not "manually"
- [ ] Partial leaves S_FEE active, writes the exact note, no cancellation mail
- [ ] Full refund sets order `refunded`, cancels S_FEE with the listed metas
- [ ] `subscription_cancelled` + `admin_subscription_cancelled` arrive
- [ ] Refund History correct in my-account; no renewal left scheduled
- [ ] Prorated preview read; modal closed unprocessed
- [ ] Exact renewal/refund relationships, sessions, QA issue cards, and review close with Review empty

## Isolation / teardown
- S_FEE ends cancelled a day early — record it in the registry so SLT-SETUP-99A skips it and D11/D12 expect no further renewal. No setting written; S5 and all other SLT2 subscriptions untouched. Close only `admin-SLT-ADM-08` and `cust-adm08-SLT-ADM-08`.

---

### Fresh-cycle validation contract

- Re-derive every ID, count, option value, gateway capability, scheduler timestamp, and email baseline on this run; no prior-cycle result is evidence.
- Create and mutate only registered `SLT2 ` / `slt2-*` fixtures. Legacy `SLT` and all non-SLT2 data are read-only controls.
- Automatic-gateway scope is Stripe and Paddle only. Stripe is the primary path; run Paddle parity wherever Paddle supports the behavior. Do not test or configure PayPal or Mollie.
- ArraySubs core must own its Stripe/Paddle integration, renewal, retry, webhook, REST, refund, and customer-payment services with Pro inactive; vendor host classes retain their expected ownership.
- Browser-required assertions use Vercel `agent-browser` with isolated task/role sessions and current snapshot refs. WP-CLI always includes `--allow-root`.
- Update the lifecycle card, the matching `qa/progress/` card, and `qa/issues/` for every new regression. Evidence belongs to this fresh cycle only.
