---
id: 98
title: Pending cancellation with a required reason and declined offers, then customer reactivation
status: done
priority: high
created: 2026-08-02T03:43:11.218237973+02:00
updated: 2026-08-09T19:12:33.836930318+02:00
started: 2026-08-08T18:29:52.363965242+02:00
completed: 2026-08-09T19:06:26.62728648+02:00
tags:
    - plan-switching
    - day-06
due: "2026-08-08"
estimate: 1h 30m
depends_on:
    - 11
    - 12
    - 60
class: standard
---

> **SLT-SW-10** · group `switching` · scheduled **D06** (2026-08-08)

> Read `README.md` (environment + isolation contract), `calendar.md` (this day's exact
> ordering — it is binding, not advisory) and `plan-audit.md` before starting.

## Objective
Exercise cancellation on the real `cancel_immediately=false` setting: required reason, all retention offers declined, an end-of-period **pending cancellation** with its two emails, no renewal invoice in that window, the cancel firing at the exact `_next_payment_date` (not spread), then **reactivation**.

## Scope
- Gateway: Stripe test
- Checkout: block
- Account: new registered — **this task creates `slt-cancel`**
- Plugins: free-only

## Preconditions
- SLT-SETUP-02 (`allow_reactivation=true`), SLT-SETUP-03, SLT-PROD-11 done; baseline `cancel_immediately=false`, `require_reason=true`, discount + downgrade offers on.
- Sessions `admin-SLT-SW-10`, `customer-SLT-SW-10`; cart and persistent-cart meta empty first and last. Act on D6 after 12:00 so the cancel lands on D7 in working hours.

## Test data
| Item | Value |
|---|---|
| Product | SLT Plan Basic $5.00 day/1; Stripe successful test Visa fixture ending `4242`, entered only in the hosted field |
| Account | slt-cancel@example.test / `SltQa!2026#Pass` |
| Reason | **Found a better alternative** (`found_alternative`); cancel fires at `_next_payment_date` exactly, no crc32 offset |

## Steps
1. `USER_PRE=$(mailpit-agent latest-id)`; in `admin-SLT-SW-10`, create `slt-cancel` as in SLT-SW-06 step 2. Classify exactly one admin-addressed `New User Registration` after `USER_PRE` and prove there is no customer account/password mail. Then set checkout-only baseline `MP0=$(mailpit-agent latest-id)`.
2. In `customer-SLT-SW-10`, log in, require the browser cart and serialized persistent-cart meta to be empty, and record exact order/subscription counts. Open `/checkout/?add-to-cart=<numeric Plan Basic ID>`, capture the unpopulated $5.00 total, fill 4242 without capturing populated hosted fields, and capture only the safe order-received page. Record the numeric parent order, read its linkage with `wp post meta get <ORDER> _subscription_ids --format=json --allow-root`, and resolve `SUB_ID` through a strict one-element numeric `jq -e` guard, cross-checking `_parent_order_id`, customer, product, and exact `+1` counts. Never use the WooCommerce order meta accessor or recency. Poll immutable `MP0` in repeated calls no longer than 60 seconds through the two-minute cutoff for the exact active-subscription subject, then classify the complete four-message WC/ArraySubs checkout set. Record `_next_payment_date` (`CANCEL_AT`), crc32 `OFFSET`, exact cancellation/invoice/charge action IDs, and their gates.
3. Set `CANCEL_REQ_PRE=$(mailpit-agent latest-id)`. `/my-account/view-subscription/<SUB_ID>/` → **Cancel Subscription**; snapshot the modal (**Continue** disabled until a reason is picked), pick **Found a better alternative** → **Continue**.
4. On **Before You Go...** screenshot each offer card, click **No thanks, continue to cancel**, confirm.
5. Screenshot banner + Status row; `wp post meta list <SUB_ID> --allow-root`, recording `_waiting_cancellation`, `_cancellation_*`, `_cancelled_by`, `_retention_offer_shown`.
6. **Tools → Scheduled Actions**, search `<SUB_ID>`: screenshot the `arraysubs_cancel_subscription` and renewal rows. At `CANCEL_AT − 30 min` (past the 6h lead) confirm no renewal order exists.
7. After the D6 pending-cancellation mail, cart proof, queue handoff, and evidence are complete, close `admin-SLT-SW-10` and `customer-SLT-SW-10`; leave the card `in-progress` without retaining sessions. **Follow-up D7 (2026-08-09): inside exact `[CANCEL_AT−300s, CANCEL_AT)` set `CANCEL_FIRE_PRE=$(mailpit-agent latest-id)`.** After `CANCEL_AT`, poll that immutable baseline in repeated calls no longer than 60 seconds through the 10-minute cutoff. In fresh `admin-SLT-SW-10-R1` and `customer-SLT-SW-10-R1`, reload the exact subscription, capture its cancelled state, prove the recorded cancel action completed `via WP Cron`, reconcile the exact customer/admin cancellation messages, and require no renewal order/charge/invoice for that scheduled cycle.
8. Set `REACT_PRE=$(mailpit-agent latest-id)`, click **Reactivate Subscription**, confirm, screenshot, and poll the immutable baseline in calls no longer than 60 seconds through the two-minute cutoff for the reactivated mail; reconcile its complete owned delta and re-dump metas/Scheduled Actions. Publish the D8 watch exception and exact resulting queue state. If no future legs exist, create only `issues/SLT-SW-10-reactivation-does-not-reschedule-renewal.md` with full live proof; do not repair it. Prove browser/persistent carts empty, close both R1 sessions, independently review both dated phases, then move the card through `review` to `done` with Review empty. Any issue must include task/stage/plan path; customer/product/parent/subscription/action/order/message IDs; user ID/login/email/role; exact routes/sessions/gates; reproduction; expected/actual; and UI/meta/queue/log/Mailpit proof.

## Expected results
1. **Continue** stays disabled until a reason is picked; only **Stay and Save!** appears (Basic has no downgrade target), yet `_retention_offer_shown` is set.
2. After step 4: still **arraysubs-active**, badge "Pending cancellation", `_waiting_cancellation=1`, `_cancellation_scheduled_date` = `_next_payment_date` exactly, `_cancellation_type=end_of_period`, `_cancelled_by=customer`, reason as chosen.
3. A pending `arraysubs_cancel_subscription` sits at `CANCEL_AT` **with no spread offset** — contrast the renewal legs at `CANCEL_AT ± OFFSET`.
4. No renewal order is created in the window (`blockRenewalDuringPendingCancellation`); no charge is taken.
5. At `CANCEL_AT` the status becomes **arraysubs-cancelled** with `_cancelled_date` set; reactivation returns it to **active**, deleting `_waiting_cancellation`, every `_cancellation_*`, `_cancelled_by`, `_end_date`.
6. **Bug candidate:** reactivation never calls `RenewalScheduler::schedule()` (unlike cancellation-undo), so `_next_payment_date` stays past. Record it and the queue; if no future legs exist, write a standalone issue file under `issues/` — the D8 watch shows whether the hourly sweep recovers it.

## Emails expected
| # | Email | Trigger point | Recipient | Subject contains | Verify with |
|---|---|---|---|---|---|
| 1 | full paid-checkout set | step 2 | customer, admin | active subscription, admin new subscription, WC new order, WC completed order | immutable-baseline polls ≤60 seconds through the two-minute cutoff plus complete delta |
| 2 | pending_cancellation + admin copy | step 4 | customer, admin | `scheduled to cancel on` | immutable-baseline polls ≤60 seconds through the two-minute cutoff |
| 3 | subscription_cancelled + admin copy | `CANCEL_AT` | customer, admin | customer: `has been cancelled`; admin: `cancelled by` | final-five-minute baseline; repeated ≤60-second polls through the 10-minute cutoff |
| 4 | subscription_reactivated | step 8 | slt-cancel | `has been reactivated` | immutable-baseline polls ≤60 seconds through the two-minute cutoff |
| 5 | NONE for declining offers; NONE renewal/invoice mail | step 4, window | — | — | Complete owner-filtered bounded delta between the recorded row-2 and row-3 baselines; zero matching mail |
| 6 | WP New User Registration | setup before `MP0` | admin | `New User Registration` | exactly one after `USER_PRE`; zero customer account/password mail |

## Evidence to capture
- `SLT-SW-10-01-reason.png`, `-02-offers.png`, `-03-pending.png`, `-04-action.png`, `-05-cancelled.png`, `-06-reactive.png`; `SUB_ID`, `CANCEL_AT`, `OFFSET`, metas, `USER_PRE`, setup-mail id, checkout-only `MP0`, later Mailpit ids

## Pass criteria — final D6/D7 accounting
- [x] **PASS** — Reason was enforced; only the discount offer was shown, then declined.
- [x] **FAIL** — The visible pending state and required `_waiting_cancellation`, schedule, type, customer, and reason fields matched expected result 2, but `_cancelled_date` was already stamped while the subscription remained active. Finding: `issues/light-plugin-SLT-SW-10-pending-cancellation-sets-cancelled-date-early.md`.
- [x] **PASS** — Cancel action `16147` was queued exactly at `CANCEL_AT` with no spread; all four historical renewal rows (`16140`/`16141`, superseded immediately by `16142`/`16143`) were cancelled, and no renewal order or charge occurred.
- [x] **PASS / UNVERIFIED** — Natural cancellation completed once via WP Cron and cleared waiting/scheduled/next-payment state. Reactivation and its cleanup assertions are `UNVERIFIED` because both customer routes exposed no Reactivate control; no substitute mutation was used.
- [x] **PASS / UNVERIFIED** — Checkout, pending-cancellation, and cancellation mail sets arrived in order and the no-renewal/no-offer-mail row passed. The reactivation email and post-reactivation schedule are `UNVERIFIED` for the missing-control blocker. Finding: `issues/light-plugin-SLT-SW-10-reactivation-action-missing.md`.
- [x] **PASS** — The admin-only setup registration mail was isolated before `MP0`; no customer account/password mail appeared.
- [x] **PASS with one visual unavailable** — Purchase/linkage/order totals and masked Visa ending `4242` evidence are exact and safe; carts are empty, dated sessions closed, independent review reached `done`, and Review is empty. The unpopulated checkout-page screenshot is unavailable because independent review found Stripe's full test-number hint in it and deleted the unsafe file; no replacement was fabricated.

## Isolation / teardown
- Leaves `slt-cancel` with one reactivated subscription; do not repair its schedule by hand — the unrepaired state is the evidence. SLT-SETUP-99B deletes it.
- No global setting changed; the Reactivate button exists only via the SLT-SETUP-02 baseline, never toggled here.
- Empty the cart, verify the persistent-cart meta is empty, and close only the exact D6/D7 sessions named in the steps.

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
As of 2026-08-06 readiness review: no current source-block is visible from live evidence. This card appears to own its own D6 fixture chain and may remain in todo until Saturday, August 8, 2026; do not open it early.

[[2026-08-08]] Sat 22:56 UTC+6
D6 leg complete and card intentionally remains `in-progress` for D7. Created user 369, parent order 13386, and strict sole subscription 13402 for product 12608. Pending cancellation is active after required reason `found_alternative` and declining the sole `Stay and Save!` offer. Cancel action 16147 is pending exactly at `CANCEL_AT=2026-08-09 16:46:26 UTC / 22:46:26 UTC+6`; renewal actions 16142/16143 are canceled. D7 gates: no-renewal-order proof at 16:16:26 UTC / 22:16:26 UTC+6; immutable `CANCEL_FIRE_PRE` inside `[16:41:26,16:46:26 UTC)` / `[22:41:26,22:46:26 UTC+6)`; natural-fire verification through 16:56:26 UTC / 22:56:26 UTC+6. Full IDs, mail deltas, metas, screenshots, and isolation proof: `/home/server-manager/slt-evidence/SLT-SW-10-D06-handoff.txt`. D6 sessions close without retention.

[[2026-08-08]] Sat 22:59 UTC+6
Independent D6 self-review found one metadata failure: `_cancelled_date=2026-08-08 16:51:34Z` is already present while subscription 13402 remains `arraysubs-active`, `_waiting_cancellation=1`, `_end_date` absent, and cancel action 16147 pending for the future `CANCEL_AT`. Every other D6 purchase, reason, offer, pending-state, queue, mail, cart, and session criterion passed. Finding: `issues/light-plugin-SLT-SW-10-pending-cancellation-sets-cancelled-date-early.md`. Card remains `in-progress` for the authored D7 cancellation/reactivation leg.

[[2026-08-09]] Sun 19:06
D7 follow-up complete at 23:04 UTC+6. No-renewal checkpoint passed; CANCEL_FIRE_PRE=7ab3FZRTZTYaiAIOBTBgkZ captured at 22:41:26; action 16147 completed once via WP Cron at 22:47:03-22:47:07; sub 13402 became cancelled with zero renewal orders/actions/failures and exact cancellation mails 2hXZkG5AyrsnuG9ToYiey0 / 4mPhuiua1mzfLkufmVffNH. Natural cancellation PASS. Reactivation FAIL/blocked because both cache-busted customer routes expose no Reactivate action despite customer_actions.allow_reactivation=true; no substitute mutation used, all post-reactivation assertions UNVERIFIED. Findings: issues/light-plugin-SLT-SW-10-pending-cancellation-sets-cancelled-date-early.md and issues/light-plugin-SLT-SW-10-reactivation-action-missing.md. Evidence: /home/server-manager/slt-evidence/SLT-SW-10-D07-followup.txt plus 05/05b/05c screenshots. Private registry page 11847 read back exactly one D7-final/D8-exception block. Carts empty, exact R1 sessions closed, stale task marker/auth file removed, agent-browser reports no active sessions. Self-review confirmed IDs, gates, UI, action log, state, queue/order zeroes, bounded mail delta, issues, registry, isolation, and terminal UNVERIFIED handling; execution is complete FAIL rather than stranded.

[[2026-08-09]] Sun 19:12
Post-close independent artifact correction: deleted exact D6 checkout screenshot SLT-SW-10-00a-basic-checkout.png because its Stripe test-mode instruction visibly retained a full test PAN. D6 handoff and D7 evidence now mark that checkout visual unavailable; no replacement was fabricated. Safe receipt, strict relationship/linkage, totals, gateway label, and masked Visa ending 4242 evidence remain. Task verdict and lifecycle findings are unchanged.
