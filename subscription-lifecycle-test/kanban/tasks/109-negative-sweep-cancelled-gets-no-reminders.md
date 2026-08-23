---
id: 109
title: 'Negative sweep: cancelled gets no reminders, lifetime gets no renewal mail, expired gets no further mail'
status: todo
priority: critical
created: 2026-08-22T19:10:00+02:00
updated: 2026-08-22T19:10:00+02:00
tags:
    - cycle-2
    - granular
    - email
    - day-08
due: "2026-08-31"
estimate: 2h
depends_on:
    - 6
    - 7
    - 23
    - 68
    - 3
    - 4
    - 54
    - 101
    - 110
class: standard
---

> **SLT-EML-14** · group `emails` · scheduled **D08** (2026-08-31)

> Read `README.md` (environment + isolation contract), `calendar.md` (this day's exact
> ordering — it is binding, not advisory) and `plan-audit.md` before starting.

## Objective
Prove the three standing email negatives with dated evidence: a cancelled subscription gets no renewal reminder or renewal mail after cancellation; the lifetime product gets no renewal-class mail across the 12-day watch; an expired subscription gets nothing after its single `has expired` message. Each negative names the watch days that prove it, plus a positive control so the reminder negative is not vacuous.

## Scope
- Gateway: Stripe test
- Checkout: N/A (observation only)
- Account: existing (`slt2-fail`, `slt2-core`, `slt2-email`)
- Plugins: both

## Preconditions
- SLT-PROD-06, -07, -16 and SLT-EML-13 complete. Dated contract, D0 = 2026-08-23: Retry Daily fails 08-26 PM → on-hold 08-27 → **cancelled 08-30**; Fixed Three Cycles renews 08-25 and 08-27 and **expires at the final 08-27 charge** (`_end_date`).
- Code basis: reminders schedule only at `_next_payment_date − renewal_upcoming.days_before(3) + spread offset`, and only if still future (`EmailManager.php:760-794`); the handler requires status exactly `arraysubs-active` (`:806`); `RenewalScheduler::unschedule()` (`:103-107`) drops the reminder on cancellation. Not a bug, state plainly: for day/1 and day/2 products the 3-day lead exceeds the cycle, so the meaningful reminder cohort is the day/3 and week flex subscriptions.
- **No drain.** Run last in the strict D8 chain, after `SLT-LIFE-01` has closed with an empty shared non-SLT2 schedule diff. This task is read-only and must not begin while any D8 mutation is still in progress.

## Test data
| Item | Value |
|---|---|
| Cancelled | SLT2 Retry Daily **`S_FAIL`** (`slt2-fail@`), cancelled 08-30 |
| Expired | SLT2 Fixed Three Cycles **`S4`** (`slt2-core@`), expired 08-27 |
| Lifetime | SLT2 Lifetime One Time **`SUB_LIFETIME`** (`slt2-core@`) and **H1** (`slt2-email@`) |
| Control | a day/3 or week flex subscription that got `renews soon` |

## Steps
1. Resolve registry aliases `S_FAIL`, `S4`, `SUB_LIFETIME`, and `H1` into same-named shell variables and abort unless every value matches `^[0-9]+$`. Run `wp post list --post_type=arraysubs_data --post__in="$S_FAIL,$S4,$SUB_LIFETIME,$H1" --fields=ID,post_status --allow-root`, then `wp post meta list <numeric ID> --keys=_next_payment_date,_end_date,_pending_renewal_order_id --allow-root` on each.
2. In isolated `admin-SLT-EML-14-D8`, search exact numeric `S_FAIL`, `S4`, `SUB_LIFETIME`, and H1 on Pending and Failed; capture uniquely named results. Open Orders and resolve each fixture's exact relationship-owned order set rather than treating a customer's newest order as proof.
3. Load the authoritative fixture evidence from `slt2-catalog-registry` and the D1–D8 watch reports: the four `DUN_*_PRE` boundaries and every exact S_FAIL failure/hold/cancel id; `LIFE04_REN2_PRE` plus S4's exact expiry id; both lifetime fixtures' checkout ids; H1's declared transition ids; and at least one exact positive-control `renews soon` id. `show` the exact ids and quote the owning task/report evidence. Do not substitute a fixed-size recent-message list for these owner handoffs.
4. **Cancelled negative.** Build the complete S_FAIL timeline from the registered ids and bounded deltas; mark the 08-30 cancellation moment and classify every exact-subscription message.
5. **Positive control.** Show the handed-off `renews soon` message for a flex day/3 or week subscription; record its id, subject and subscription id. Read-only.
6. **Lifetime and expired negatives.** Build the exact `SUB_LIFETIME`, H1 and S4 timelines from their registered owner evidence; mark S4's 08-27 expiry. Re-read `SUB_LIFETIME._next_payment_date`/`_end_date` and `S4._next_payment_date` vs `_end_date` for residue.
7. Write the reconciliation table (id, timestamp, subject, To, fixture, owner baseline/report, verdict) to `/home/server-manager/slt-evidence/SLT-EML-14-sweep.md`. After the D8 table is complete, set `EML14_D8_PRE=$(mailpit-agent latest-id)`, publish its exact value/UTC, then close `admin-SLT-EML-14-D8`; do not retain a session through D12.
8. Declare the follow-up watch days in the notes and the registry: `S_FAIL` → **D8 (08-31)** … **D12 (09-04)**; `S4` → the **D5 (08-28) morning watch** reads the 08-27 `has expired` mail, and **D6–D12** prove silence; `SUB_LIFETIME` → **every** watch day **D1 (08-24)** … **D12 (09-04)**; H1 → **D2 (08-25)** … **D12**.
9. On the morning of **09-04 (watch D12)**, before SLT-SETUP-99B, inspect the complete delta after unchanged `EML14_D8_PRE`; filter by exact subscription id, recipient and lifecycle subject, classify unrelated mail, quote D9–D12 reports, and append the result. In fresh `admin-SLT-EML-14-D12`, re-prove exact statuses/actions/orders, close it, independently review the full window, then move through `review` to `done` with Review empty. Any live defect goes only in `qa/issues/` kanban card named `SLT-EML-14-<concise-slug>` with task/stage/plan path; fixture/subscription/order/action/message IDs; user IDs/logins/emails/roles; exact routes/sessions/watch boundaries; reproduction; expected/actual; and status/meta/queue/order/report/Mailpit proof.

## Expected results
1. `S_FAIL` is `arraysubs-cancelled` with zero pending and zero failed action rows. Its exact known mail set is eight `payment_failed` messages (four customer/admin pairs), one customer-only on-hold message, and the customer/admin cancellation pair; it has **zero** `renews soon`, renewal-invoice, or payment-success mail.
2. No message of any kind for `S_FAIL` after its 08-30 cancellation, through 09-04.
3. At least one `renews soon` exists in the window for a longer-cycle subscription, proving the pipeline is live and SR's zero is real.
4. `SUB_LIFETIME` has empty `_next_payment_date`/`_end_date`, no pending or failed action, no order beyond its 08-23 parent and zero renewal-class mail across watch D1–D12; H1 matches exactly what SLT-EML-11/12/13 declared.
5. `S4` received exactly one `has expired` at the 08-27 final charge and nothing after. There is zero naturally scheduled `is ending soon` mail anywhere; the single registry-declared `SLT-LIFE-04` targeted probe is classified separately and is not a natural send. The 09-04 delta adds nothing for any fixture.

## Emails expected
| # | Email | Trigger point | Recipient | Subject contains | Verify with |
|---|---|---|---|---|---|
| 1 | NONE EXPECTED — renewal_reminder for `S_FAIL` | whole window | — | — | zero `renews soon` for `#S_FAIL` |
| 2 | NONE EXPECTED — anything for `S_FAIL` | after 08-30 | — | — | 09-04 delta yields no new `S_FAIL` message |
| 3 | NONE EXPECTED — renewal mail for `SUB_LIFETIME`/H1; naturally scheduled expiring_soon for anything | watch D1 … D12 | — | — | zero `renews soon`, `Invoice for subscription`, `Payment received`; zero natural `is ending soon` after excluding the one registry-declared `SLT-LIFE-04` targeted probe |
| 4 | NONE EXPECTED — anything for `S4` | after its 08-27 expiry mail | — | — | 09-04 delta yields no new `S4` message |
| 5 | Reference only, already sent | 08-26/-06/-09 `S_FAIL`, 08-27 `S4` | slt2-fail@ / slt2-core@ | `Payment failed`, `is on hold`, `has been cancelled`, `has expired` | `mailpit-agent show <id>` |

## Evidence to capture
- `SLT-EML-14-sweep.md` with the full table plus the 09-04 delta.
- Screenshots `-01-statuses.png`, `-02-actions-S_FAIL.png`, `-03-actions-SUB_LIFETIME.png`, `-04-actions-S4.png`, `-05-orders.png`, `-06-mailpit-S_FAIL.png`, `-07-mailpit-S4.png`; IDs `S_FAIL`/`S4`/`SUB_LIFETIME`/H1; every Mailpit id per fixture with timestamp and To; the control `renews soon` id.

## Pass criteria
- [ ] `S_FAIL` cancelled, no pending/failed actions, zero `renews soon` ever
- [ ] Zero mail for `S_FAIL` after 08-30, re-verified 09-04
- [ ] Positive control `renews soon` located, so the negative is non-vacuous
- [ ] `SUB_LIFETIME` inert and mail-silent across watch D1–D12; H1 matches its declarations
- [ ] `S4` has one `has expired` and nothing after; zero natural `expiring_soon`, with the one targeted probe classified separately
- [ ] Watch days recorded in the registry and the 09-04 delta appended
- [ ] D8/D12 sessions close and independent final review reaches `done` with Review empty

## Isolation / teardown
- Fully read-only: no setting changed, no status edited, no order or subscription touched, no drain.
- Handed on: the negative-assertion table is the standing check for watch days D8–D12. SLT-SETUP-99B must not run on 09-04 until step 9's delta is captured — its cancellation mail would contaminate the silence proof. Restores: nothing.

---

### Fresh-cycle validation contract

- Re-derive every ID, count, option value, gateway capability, scheduler timestamp, and email baseline on this run; no prior-cycle result is evidence.
- Create and mutate only registered `SLT2 ` / `slt2-*` fixtures. Legacy `SLT` and all non-SLT2 data are read-only controls.
- Automatic-gateway scope is Stripe and Paddle only. Stripe is the primary path; run Paddle parity wherever Paddle supports the behavior. Do not test or configure PayPal or Mollie.
- ArraySubs core must own its Stripe/Paddle integration, renewal, retry, webhook, REST, refund, and customer-payment services with Pro inactive; vendor host classes retain their expected ownership.
- Browser-required assertions use Vercel `agent-browser` with isolated task/role sessions and current snapshot refs. WP-CLI always includes `--allow-root`.
- Update the lifecycle card, the matching `qa/progress/` card, and `qa/issues/` for every new regression. Evidence belongs to this fresh cycle only.
