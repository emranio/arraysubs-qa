---
id: 109
title: 'Negative sweep: cancelled gets no reminders, lifetime gets no renewal mail, expired gets no further mail'
status: done
priority: critical
created: 2026-08-02T03:43:12.077261052+02:00
updated: 2026-08-05T21:31:31.382542042+02:00
started: 2026-08-05T21:31:31.38254101+02:00
completed: 2026-08-05T21:31:31.38254101+02:00
tags:
    - email
    - day-08
due: "2026-08-10"
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

> **SLT-EML-14** · group `emails` · scheduled **D08** (2026-08-10)

> Read `README.md` (environment + isolation contract), `calendar.md` (this day's exact
> ordering — it is binding, not advisory) and `plan-audit.md` before starting.

## Objective
Prove the three standing email negatives with dated evidence: a cancelled subscription gets no renewal reminder or renewal mail after cancellation; the lifetime product gets no renewal-class mail across the 12-day watch; an expired subscription gets nothing after its single `has expired` message. Each negative names the watch days that prove it, plus a positive control so the reminder negative is not vacuous.

## Scope
- Gateway: Stripe test
- Checkout: N/A (observation only)
- Account: existing (`slt-fail`, `slt-core`, `slt-email`)
- Plugins: both

## Preconditions
- SLT-PROD-06, -07, -16 and SLT-EML-13 complete. Dated contract, D0 = 2026-08-02: Retry Daily fails 08-05 PM → on-hold 08-06 → **cancelled 08-09**; Fixed Three Cycles renews 08-04 and 08-06 and **expires at the final 08-06 charge** (`_end_date`).
- Code basis: reminders schedule only at `_next_payment_date − renewal_upcoming.days_before(3) + spread offset`, and only if still future (`EmailManager.php:760-794`); the handler requires status exactly `arraysubs-active` (`:806`); `RenewalScheduler::unschedule()` (`:103-107`) drops the reminder on cancellation. Not a bug, state plainly: for day/1 and day/2 products the 3-day lead exceeds the cycle, so the meaningful reminder cohort is the day/3 and week flex subscriptions.
- **No drain.** Run last in the strict D8 chain, after `SLT-LIFE-01` has closed with an empty shared non-SLT schedule diff. This task is read-only and must not begin while any D8 mutation is still in progress.

## Test data
| Item | Value |
|---|---|
| Cancelled | SLT Retry Daily **`S_FAIL`** (`slt-fail@`), cancelled 08-09 |
| Expired | SLT Fixed Three Cycles **`S4`** (`slt-core@`), expired 08-06 |
| Lifetime | SLT Lifetime One Time **`SUB_LIFETIME`** (`slt-core@`) and **H1** (`slt-email@`) |
| Control | a day/3 or week flex subscription that got `renews soon` |

## Steps
1. Resolve registry aliases `S_FAIL`, `S4`, `SUB_LIFETIME`, and `H1` into same-named shell variables and abort unless every value matches `^[0-9]+$`. Run `wp post list --post_type=arraysubs_data --post__in="$S_FAIL,$S4,$SUB_LIFETIME,$H1" --fields=ID,post_status --allow-root`, then `wp post meta list <numeric ID> --keys=_next_payment_date,_end_date,_pending_renewal_order_id --allow-root` on each.
2. In isolated `admin-SLT-EML-14-D8`, search exact numeric `S_FAIL`, `S4`, `SUB_LIFETIME`, and H1 on Pending and Failed; capture uniquely named results. Open Orders and resolve each fixture's exact relationship-owned order set rather than treating a customer's newest order as proof.
3. Load the authoritative fixture evidence from `slt-catalog-registry` and the D1–D8 watch reports: the four `DUN_*_PRE` boundaries and every exact S_FAIL failure/hold/cancel id; `LIFE04_REN2_PRE` plus S4's exact expiry id; both lifetime fixtures' checkout ids; H1's declared transition ids; and at least one exact positive-control `renews soon` id. `show` the exact ids and quote the owning task/report evidence. Do not substitute a fixed-size recent-message list for these owner handoffs.
4. **Cancelled negative.** Build the complete S_FAIL timeline from the registered ids and bounded deltas; mark the 08-09 cancellation moment and classify every exact-subscription message.
5. **Positive control.** Show the handed-off `renews soon` message for a flex day/3 or week subscription; record its id, subject and subscription id. Read-only.
6. **Lifetime and expired negatives.** Build the exact `SUB_LIFETIME`, H1 and S4 timelines from their registered owner evidence; mark S4's 08-06 expiry. Re-read `SUB_LIFETIME._next_payment_date`/`_end_date` and `S4._next_payment_date` vs `_end_date` for residue.
7. Write the reconciliation table (id, timestamp, subject, To, fixture, owner baseline/report, verdict) to `/home/server-manager/slt-evidence/SLT-EML-14-sweep.md`. After the D8 table is complete, set `EML14_D8_PRE=$(mailpit-agent latest-id)`, publish its exact value/UTC, then close `admin-SLT-EML-14-D8`; do not retain a session through D12.
8. Declare the follow-up watch days in the notes and the registry: `S_FAIL` → **D8 (08-10)** … **D12 (08-14)**; `S4` → the **D5 (08-07) morning watch** reads the 08-06 `has expired` mail, and **D6–D12** prove silence; `SUB_LIFETIME` → **every** watch day **D1 (08-03)** … **D12 (08-14)**; H1 → **D2 (08-04)** … **D12**.
9. On the morning of **08-14 (watch D12)**, before SLT-SETUP-99B, inspect the complete delta after unchanged `EML14_D8_PRE`; filter by exact subscription id, recipient and lifecycle subject, classify unrelated mail, quote D9–D12 reports, and append the result. In fresh `admin-SLT-EML-14-D12`, re-prove exact statuses/actions/orders, close it, independently review the full window, then move through `review` to `done` with Review empty. Any live defect goes only in `issues/SLT-EML-14-<concise-slug>.md` with task/stage/plan path; fixture/subscription/order/action/message IDs; user IDs/logins/emails/roles; exact routes/sessions/watch boundaries; reproduction; expected/actual; and status/meta/queue/order/report/Mailpit proof.

## Expected results
1. `S_FAIL` is `arraysubs-cancelled` with zero pending and zero failed action rows. Its exact known mail set is eight `payment_failed` messages (four customer/admin pairs), one customer-only on-hold message, and the customer/admin cancellation pair; it has **zero** `renews soon`, renewal-invoice, or payment-success mail.
2. No message of any kind for `S_FAIL` after its 08-09 cancellation, through 08-14.
3. At least one `renews soon` exists in the window for a longer-cycle subscription, proving the pipeline is live and SR's zero is real.
4. `SUB_LIFETIME` has empty `_next_payment_date`/`_end_date`, no pending or failed action, no order beyond its 08-02 parent and zero renewal-class mail across watch D1–D12; H1 matches exactly what SLT-EML-11/12/13 declared.
5. `S4` received exactly one `has expired` at the 08-06 final charge and nothing after. There is zero naturally scheduled `is ending soon` mail anywhere; the single registry-declared `SLT-LIFE-04` targeted probe is classified separately and is not a natural send. The 08-14 delta adds nothing for any fixture.

## Emails expected
| # | Email | Trigger point | Recipient | Subject contains | Verify with |
|---|---|---|---|---|---|
| 1 | NONE EXPECTED — renewal_reminder for `S_FAIL` | whole window | — | — | zero `renews soon` for `#S_FAIL` |
| 2 | NONE EXPECTED — anything for `S_FAIL` | after 08-09 | — | — | 08-14 delta yields no new `S_FAIL` message |
| 3 | NONE EXPECTED — renewal mail for `SUB_LIFETIME`/H1; naturally scheduled expiring_soon for anything | watch D1 … D12 | — | — | zero `renews soon`, `Invoice for subscription`, `Payment received`; zero natural `is ending soon` after excluding the one registry-declared `SLT-LIFE-04` targeted probe |
| 4 | NONE EXPECTED — anything for `S4` | after its 08-06 expiry mail | — | — | 08-14 delta yields no new `S4` message |
| 5 | Reference only, already sent | 08-05/-06/-09 `S_FAIL`, 08-06 `S4` | slt-fail@ / slt-core@ | `Payment failed`, `is on hold`, `has been cancelled`, `has expired` | `mailpit-agent show <id>` |

## Evidence to capture
- `SLT-EML-14-sweep.md` with the full table plus the 08-14 delta.
- Screenshots `-01-statuses.png`, `-02-actions-S_FAIL.png`, `-03-actions-SUB_LIFETIME.png`, `-04-actions-S4.png`, `-05-orders.png`, `-06-mailpit-S_FAIL.png`, `-07-mailpit-S4.png`; IDs `S_FAIL`/`S4`/`SUB_LIFETIME`/H1; every Mailpit id per fixture with timestamp and To; the control `renews soon` id.

## Pass criteria
- [ ] `S_FAIL` cancelled, no pending/failed actions, zero `renews soon` ever
- [ ] Zero mail for `S_FAIL` after 08-09, re-verified 08-14
- [ ] Positive control `renews soon` located, so the negative is non-vacuous
- [ ] `SUB_LIFETIME` inert and mail-silent across watch D1–D12; H1 matches its declarations
- [ ] `S4` has one `has expired` and nothing after; zero natural `expiring_soon`, with the one targeted probe classified separately
- [ ] Watch days recorded in the registry and the 08-14 delta appended
- [ ] D8/D12 sessions close and independent final review reaches `done` with Review empty

## Isolation / teardown
- Fully read-only: no setting changed, no status edited, no order or subscription touched, no drain.
- Handed on: the negative-assertion table is the standing check for watch days D8–D12. SLT-SETUP-99B must not run on 08-14 until step 9's delta is captured — its cancellation mail would contaminate the silence proof. Restores: nothing.


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

[[2026-08-05]] Wed 21:31
UNVERIFIED (no S_FAIL source fixture) on 2026-08-05.

This card cannot start as authored because step 1 requires `S_FAIL`, `S4`, `SUB_LIFETIME`, and `H1` all to resolve to numeric subscription ids before any timeline or Mailpit reconciliation begins. Upstream task #33 already completed the authored missed-fixture branch on 2026-08-05: registry page 11847 stores `S_FAIL unavailable`, the D03 watch report directs downstream ladder-only assertions to close `UNVERIFIED` without a substitute, and live verification still shows no ArraySubs subscription row for `slt-fail` user 351 on product 12108. The exact owner/product query returns only subscription 11959 for `slt-core` (customer 347 / product 11927), so the authored S_FAIL failure/hold/cancel mail sequence and the post-cancellation silence window can never exist for this task. Closing the full card rather than partially rewriting it around other fixtures, because the task body makes the cancelled-S_FAIL branch a mandatory prerequisite and explicitly aborts if any fixture alias is missing.
