---
id: 42
title: Paddle sandbox subscription renews unattended — establish Paddle-side vs site-side driver
status: done
priority: high
created: 2026-08-02T03:43:06.468088561+02:00
updated: 2026-08-06T20:10:31.792433009+02:00
started: 2026-08-06T20:10:31.792432207+02:00
completed: 2026-08-06T20:10:31.792432207+02:00
tags:
    - renewal
    - day-02
due: "2026-08-04"
estimate: 2h
depends_on:
    - 12
    - 23
    - 26
    - 29
class: standard
---

> **SLT-REN-04** · group `renewal` · scheduled **D02** (2026-08-04)

> Read `README.md` (environment + isolation contract), `calendar.md` (this day's exact
> ordering — it is binding, not advisory) and `plan-audit.md` before starting.
## Objective
Observe the exact `SLT Paddle Daily` subscription created by `SLT-CHK-04` and establish with evidence WHO drives its unattended renewal. The expected driver is Paddle-side: money lands only on `transaction.completed`. Because Paddle's published `next_billed_at` precedes the spread-shifted local charge leg, observe whether that local `arraysubs_process_renewal` action runs as a no-op or is superseded/cancelled by the earlier Paddle webhook; either outcome must prove that the local leg did not charge. `syncNextPaymentDate()` may overwrite `_next_payment_date` from `next_billed_at` without immediately aligning every queued row. Assert the observed model, not AS/meta agreement.

## Scope
- Gateway: Paddle sandbox
- Checkout: block
- Account: existing (`slt-paddle`)
- Plugins: pro-required

## Preconditions
- `SLT-CHK-04` is done and has published the one exact `SUB_PAD`, parent order, next-payment value, Paddle subscription id, offset, and action ids. Re-query the owner/product pair and require exactly one matching subscription before continuing.
- C51 fallback: if `SLT-CHK-04` instead published `SUB_PAD unavailable` with a standalone checkout-impact issue, do not buy or invent a replacement. Re-read that evidence, prove the owner/product query still has zero matching subscriptions, mark every Paddle-renewal assertion `UNVERIFIED (no source subscription)`, and move this fully reviewed execution card to `done`; do not leave it blocked or open a browser session for a nonexistent ID.
- **This task places no order and never touches a cart.** `SLT-CHK-04` is the sole purchaser. A second matching subscription is contamination and must be recorded in a standalone issue file under `issues/` before stopping this task.
- Paddle only, never Stripe here. No `wp action-scheduler run`.

## Test data
| Item | Value |
|---|---|
| Product | SLT Paddle Daily, $11.00/day |
| Account | slt-paddle / SltQa!2026#Pass |
| Card | Paddle sandbox 4242 4242 4242 4242 |
| Subscription | `SUB_PAD`, from `SLT-CHK-04` |
| Sessions | `customer-SLT-REN-04`, `admin-SLT-REN-04` |

## Steps
1. Resolve `SUB_PAD` and its parent order from the `SLT-CHK-04` registry hand-off. Re-query customer, product, gateway, and matching-row count; require `slt-paddle`, `SLT Paddle Daily`, `arraysubs_paddle`, and exactly one row. Record the activation Mailpit ids and webhook delay from the owning task's evidence.
2. `agent-browser --session customer-SLT-REN-04 open ".../my-account/view-subscription/<SUB_PAD>/"` → log in as `slt-paddle`; capture `SLT-REN-04-01-subscription.png` showing the active subscription and Paddle payment method. Do not open a product, cart, or checkout page.
3. Record `_next_payment_date`, `_gateway_paddle_subscription_id`, and `_gateway_status` for `SUB_PAD`.
4. From the Paddle sandbox dashboard/API read `next_billed_at` and `billing_cycle`; **record both next to `_next_payment_date` — disagreement is itself the finding.**
5. Compute `k4`; query the Action Scheduler rows by exact args `[SUB_PAD]` and record both legs' `scheduled_date_gmt`. Publish the exact local/Paddle gates and earliest `gate−5m` deadline to the registry, the late D02 handoff, and the current-day watch report; close both exact task sessions and keep the card `in-progress`. For this late fixture the first gate is invoice action `14853` at `2026-08-06 04:26:48Z`, so capture `PAD_REN_PRE=$(mailpit-agent latest-id)` only inside `[2026-08-06 04:21:48Z, 04:26:48Z)` and store it in both handoff locations.
6. On D4 = 2026-08-06, after the remote `next_billed_at` and local charge gate have resolved, reopen `admin-SLT-REN-04`; read exact `SUB_PAD` meta/private notes and actions `14853`/`14854` status/logs, and capture `SLT-REN-04-02-subscription-notes.png`. Reopen the required customer session and re-read the customer view, exact related orders/notes, and remote subscription. If a renewal `transaction.completed` is observed, run `mailpit-agent wait-new "$PAD_REN_PRE" 180 "Payment received for subscription #$SUB_PAD"`, reconcile every message newer than `PAD_REN_PRE`, resolve the paid renewal order by its exact subscription relationship, and capture it as `SLT-REN-04-03-renewal-order.png`. Record whether action `14854` completed as a no-op or was superseded/cancelled by the earlier webhook; neither path may contain a local charge or retry. If Paddle has not billed inside 24 hours of its published `2026-08-06 10:20:38Z` gate, preserve the same baseline through D5 = 2026-08-07 and report the webhook-dependent results `UNVERIFIED`. Close both task sessions and move the card through review to done after either fully reviewed conclusion; never leave it open solely because sandbox billing did not occur.

## Expected results
1. Parent order paid $11.00, `payment_method=arraysubs_paddle`, `_gateway_paddle_subscription_id` set, subscription `arraysubs-active`, `_gateway_status=active`.
2. Exact local action `14854` either completes at `due+k4` via WP Cron as a no-op (with the awaiting-Paddle note where emitted) or is superseded/cancelled by the earlier Paddle webhook. In both cases it charges nothing locally, `_payment_retry_attempts` remains 0/absent, and no retry is queued — Paddle gets NO ArraySubs retry ladder.
3. The paid renewal order comes from the webhook branch: `_is_renewal_order=yes`, `_subscription_id=SUB_PAD`, $11.00, paid, with `_paddle_transaction_id` + `_last_gateway_transaction_id`. Note whether it is the invoice-leg order or a retroactive one.
4. After the webhook `_next_payment_date` equals `next_billed_at` (UTC) even where that differs from the core-computed value — Paddle wins.
5. The queued legs may now disagree with `_next_payment_date` until the next hourly sweep. Record it; do NOT assert agreement.
6. Verdict written verbatim in the report: renewal is **Paddle-driven**, local legs are bookkeeping. A charge at `due+k4` with no `transaction.completed` contradicts the contract and must be written as a standalone markdown file under `issues/`, never as a lifecycle-board card.
7. If sandbox does not bill inside 24 h, results 3–4 are `UNVERIFIED` with that reason; 1, 2, 5 still stand. On a failure path, `_last_payment_failure` as a Unix timestamp (core writes a UTC string) is a bug candidate, not a test failure.

## Emails expected
| # | Email | Trigger point | Recipient | Subject contains | Verify with |
|---|---|---|---|---|---|
| 1 | new_subscription + admin_new_subscription | `SLT-CHK-04` webhook confirms checkout | customer/admin | `is active` | cite the exact owning-task Mailpit ids; do not trigger another signup |
| 2 | payment_successful | renewal `transaction.completed` | slt-paddle@example.test | `Payment received for subscription #SUB_PAD` | after webhook: `mailpit-agent wait-new "$PAD_REN_PRE" 180 ...`, then inspect every newer message |
| 3 | NONE EXPECTED: renewal_invoice (automatic+auto-renew), payment_failed, renewal_reminder | D3 | — | — | inspect every message newer than `PAD_REN_PRE` |

## Evidence to capture
- Cite `SLT-CHK-04-02-overlay.png`; capture `SLT-REN-04-01-subscription.png`, `-02-subscription-notes.png`, `-03-renewal-order.png`; `SUB_PAD`, Paddle subscription id, `next_billed_at` vs `_next_payment_date` at each read, `k4`, `PAD_REN_PRE`, AS rows + logs, order ids, Mailpit ids.

## Pass criteria
- [ ] Paid $11.00 parent order, active subscription with a Paddle id
- [ ] Local charge leg outcome reconciled exactly (no-op completion or earlier-webhook supersession/cancellation), with no local charge
- [ ] No ArraySubs retry action or counter
- [ ] Renewal money arrived via `transaction.completed`; paid $11.00 order with Paddle ids
- [ ] `_next_payment_date` matches `next_billed_at`; AS/meta disagreement recorded, not asserted away
- [ ] Driver verdict stated; emails 1–2 present, row-3 negatives absent

## Isolation / teardown
- `SLT Paddle Daily` and `slt-paddle` stay Paddle-only all window. No order or subscription is created by this task.
- Nothing restored; `SUB_PAD` is cancelled at SLT-SETUP-99A. Close only `customer-SLT-REN-04` and `admin-SLT-REN-04` after each dated leg.

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

## D03 setup checkpoint — 2026-08-05

**SOURCE VERIFIED; D04/D05 NATURAL WATCH ARMED.** Exact owner/product preflight returns only `SUB_PAD=12639` for user `352` (`slt-paddle`), product `12112`, gateway `arraysubs_paddle`, and parent order `12629`. The source subscription is `arraysubs-active`, `_gateway_status=active`, one completed payment, local `_next_payment_date=2026-08-06 10:20:38Z`, and Paddle subscription id `sub_01kz8q1025tryjfgxvn5e3v4gf`. The source checkout activation messages remain customer-active `2q7ZRgzwykMkBKlw2ZgsdQ` and admin-new-subscription `50GJjz3ekgXoIfje5d6UwY`; no duplicate order or signup was triggered.

The redacted Paddle API returned HTTP `200`, status `active`, one-day billing cycle, and `next_billed_at=2026-08-06T10:20:38.143985Z`, matching local NPD at stored-second precision. With `k4=370s`, final pending invoice action `14853` is `2026-08-06 04:26:48Z` and final pending charge action `14854` is `2026-08-06 10:26:48Z`. The canceled originals `14851`/`14852` are historical synchronization rows. The real authenticated Scheduled Actions screen displayed exactly the two final pending rows; neither was run or forced.

The real customer account showed subscription `12639` Active, USD `11.00` every day, Paddle, masked Visa ending `4242`, completed parent order `12629`, and no Renew Early control. Browser errors were empty. Evidence: `/home/server-manager/slt-evidence/SLT-REN-04-armed-facts.txt`, `SLT-REN-04-01-subscription.png`, and supplemental `SLT-REN-04-01b-actions-armed.png`.

QA-plan-only correction: the late D03 purchase moved all renewal gates one day later than the original authored D03/D04 wording. The exact immutable baseline window is `[2026-08-06 04:21:48Z, 04:26:48Z)` before the earliest invoice gate. Remote billing is due `10:20:38.143985Z`; local charge is due `10:26:48Z`. The final read must establish whether action `14854` completes as a no-op or is superseded/cancelled by the earlier Paddle webhook; either path must show zero local charge/retry. If no Paddle transaction has arrived by `2026-08-07 10:20:38Z`, close with webhook-dependent assertions `UNVERIFIED` rather than leaving the card open.

Both exact task sessions were closed after publication. This card remains in progress and unclaimed solely for its explicit future natural gates; it must not be forced.

[[2026-08-05]] Wed 14:35
Board hygiene checkpoint: no actionable step is available right now; the current body already records the exact next gate/window and required natural-watch constraints. Parking this future-gated card back in todo so In Progress reflects only currently active execution.

[[2026-08-05]] Wed 14:45
D03 watcher correction: the 18:35 board-hygiene note is superseded; this future-gated card remains in-progress. Capture D4 PAD_REN_PRE only 04:21:48Z-04:26:47Z, observe invoice 14853, Paddle remote billing at 10:20:38Z, and local action 14854 at 10:26:48Z without forcing.

[[2026-08-05]] Wed 15:01
Board hygiene checkpoint: parked future-gated or watch-only work returned to todo so in-progress reflects only actively worked cards.

[[2026-08-05]] Wed 15:30
Board correction: restored to in-progress. Next gate D4: PAD_REN_PRE 2026-08-06 04:21:48Z-04:26:47Z (10:21:48-10:26:47 site); observe invoice 14853, remote next_billed_at 10:20:38Z, and local action 14854 at 10:26:48Z without forcing.

[[2026-08-05]] Wed 16:41
D4/D5 Paddle natural-renewal follow-up: baseline 2026-08-06 04:21:48Z-04:26:47Z; observe remote 10:20:38Z and local actions 14853/14854 without forcing.

[[2026-08-05]] Wed 16:46
Board hygiene: returned to todo because this card is not in an active execution window right now. Resume only at the exact gate or follow-up already recorded on the card.

[[2026-08-05]] Wed 17:26
D4/D5 Paddle natural renewal; consume immutable baseline and observe remote/local ordering without forcing.

[[2026-08-05]] Wed 17:44
D4 Paddle renewal follow-up: baseline 2026-08-06 04:21:48Z–04:26:47Z; observe remote billing 10:20:38Z and action 14854 at 10:26:48Z.

[[2026-08-06]] Thu 20:10
Closed from completed D3/D4 evidence. D4 Paddle renewal proved in watch-reports/D04-2026-08-06.md: invoice action 14853 completed, local charge leg 14854 canceled, relationship-owned renewal order 12891 completed at 11.00, payment mail 60Ay42w4QVdSR5h1Te1gtF observed. Driver verdict: Paddle-driven, local legs bookkeeping only.
