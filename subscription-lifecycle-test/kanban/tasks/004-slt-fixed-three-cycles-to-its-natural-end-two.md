---
id: 4
title: 'SLT Fixed Three Cycles to its natural end: two renewals, expiry at the final charge, expired mail, no expiring-soon'
status: done
priority: critical
created: 2026-08-02T03:43:03.251927431+02:00
updated: 2026-08-06T20:10:30.758444198+02:00
started: 2026-08-06T20:10:30.758443366+02:00
completed: 2026-08-06T20:10:30.758443366+02:00
tags:
    - renewal
    - day-00
due: "2026-08-02"
estimate: 1h30m
depends_on:
    - 10
    - 11
    - 12
    - 6
class: standard
---

> **SLT-LIFE-04** · group `renewal` · scheduled **D00** (2026-08-02)

> Read `README.md` (environment + isolation contract), `calendar.md` (this day's exact
> ordering — it is binding, not advisory) and `plan-audit.md` before starting.

## Objective
Walk SLT Fixed Three Cycles (day/2, length 3, $7.00) from checkout to termination. `calculateAndSetNextPaymentDate()` (OrderIntegration.php:1489-1502) stamps `_end_date = current_time()`, blanks `_next_payment_date` and flips the post to `arraysubs-expired` *inside* the final renewal payment, so cycle 3 is charged and service ends the same instant. `arraysubs_calculate_end_date_from_length()` has zero callers, so the catalog's "expires 6 days after checkout" contract is unproven. Also proves SLT-REF-05 §3: nothing schedules `arraysubs_send_expiring_soon`.

## Scope
- Gateway: Stripe test
- Checkout: block
- Account: existing (`slt-core`)
- Plugins: free-only

## Preconditions
- SLT-SETUP-01..03 and SLT-PROD-06 done; `_subscription_length=3`, `_subscription_interval=2`, `_subscription_period=day`, `_regular_price=7.00`. `SLT-SETUP-04` is the later coupon setup and is unrelated to this D0 purchase.
- Buy after 12:00 site time (audit C02) and only on D0, or expiry falls outside the window.
- If a SLT-CHK-* task already bought this for `slt-core`, adopt that subscription ID and skip steps 2-3 (`slt-core` never buys the same product twice, C08).

## Test data
| Item | Value |
|---|---|
| Product | SLT Fixed Three Cycles (`slt-fixed-three-cycles`) |
| Account | `slt-core` / `SltQa!2026#Pass` |
| Card | 4242 4242 4242 4242 |
| Amounts | $7.00 today + $7.00 x2 renewals = $21.00 |
| Dates | buy 2026-08-02 at T site; due1 = 08-04 T; due2 = 08-06 T |

## Steps
1. `PREV=$(mailpit-agent latest-id)`.
2. `agent-browser --session life04-SLT-LIFE-04 open "https://mirror-help.arrayhash.com/my-account/"` -> log in as `slt-core`, then open `/cart/` and STOP and write a standalone issue file under `issues/` if it is not empty. Open `/product/slt-fixed-three-cycles/` -> `snapshot -i` -> add to cart.
3. Open `https://mirror-help.arrayhash.com/checkout/` (block checkout), pay with the saved Stripe test card or 4242, and Place order. Record O0, resolve S4 from O0's exact `_subscription_ids`, require one ID and cross-check parent order/customer/product, then assign the numeric ID to shell variable `S4`; never select by recency.
4. `wp post meta list "$S4" --keys=_next_payment_date,_end_date,_completed_payments,_subscription_length,_recurring_amount --allow-root`.
5. `php -r '$id=(int)$argv[1];$h=(int)sprintf("%u",crc32("arraysubs-spread-".$id));printf("%d\n",$h%21600);' "$S4"` -> offset k. Abort unless `S4` is numeric.
6. Screenshot `wp-admin/tools.php?page=action-scheduler&s=S4&status=pending`: one invoice leg at due1+k-6h, one charge leg at due1+k, no reminder row, no `arraysubs_send_expiring_soon` row.
7. `mailpit-agent wait-new "$PREV" 120 "is active"`, then inspect and classify the complete owner-filtered delta after `$PREV`.
8. At least five minutes before action `13723` / `due1+k`, store `LIFE04_REN1_PRE=$(mailpit-agent latest-id)` in the registry. **D2 (08-04, after due1+k):** repeat 4 and 6; read renewal order O1 (total, `_renewal_scheduled_date`, `_renewal_cycle_number`), run `mailpit-agent wait-new "$LIFE04_REN1_PRE" 900 "Payment received for subscription #$S4"`, and reconcile every message newer than the baseline.
9. **D3 (08-05), while still active:** run this follow-up before 08:45 site, outside the 09:00–11:00 `SLT-SYN-04` bracket. In task-keyed session `admin-SLT-LIFE-04`, screenshot the site-wide Pending queue for the next five minutes. If a non-SLT action is already overdue or due during that interval, defer until it completes naturally and repeat the pre-flight. Hand-schedule `HOOK_SEND_EXPIRING_SOON` for S4 per SLT-REF-05 §3, query and record its exact args/action ID, re-snapshot immediately before clicking **Run**, then run that ONE verified ID from the Scheduled Actions screen (never a bare drain, C07). Finish and close the session before 08:45; if that gate cannot be met, defer until after 11:00 and re-run the pre-flight.
10. At least five minutes before the exact D4 charge gate learned from the D2 schedule, store `LIFE04_REN2_PRE=$(mailpit-agent latest-id)` in the registry. **D4 (08-06, after due2+k):** repeat 4 and 6; read order O2 and the post status; run `mailpit-agent wait-new "$LIFE04_REN2_PRE" 900 "Payment received for subscription #$S4"`, then list all messages newer than `LIFE04_REN2_PRE` and identify the single `has expired` message by ID.

## Expected results
1. At creation `_end_date` absent, `_completed_payments=1`, `_next_payment_date` = 08-04 at the checkout clock time.
2. Renewal 1 charges $7.00 inside [due1+k, due1+k+5min]; `_completed_payments=2`; next date = 08-06 T.
3. Renewal 2 charges $7.00 inside [due2+k, due2+k+5min] on 08-06; `_completed_payments=3`; since 3 >= length, `_end_date` = the charge moment (08-06, NOT 08-08), `_next_payment_date` empty, status `arraysubs-expired`.
4. Zero pending renewal legs for S4 afterwards and no third renewal order.
5. Total charged $21.00, no tax line; `…is ending soon` arrives only from the step-9 probe.
6. Write a separate standalone issue file under `issues/` if `_end_date` is not the final charge moment, and a second standalone file for "cycle 3 charged, access ends instantly, no notice".

## Emails expected
| # | Email | Trigger point | Recipient | Subject contains | Verify with |
|---|---|---|---|---|---|
| 1 | new_subscription + admin_new_subscription | checkout | customer + admin | `is active` / `New subscription #` | `mailpit-agent wait-new "$PREV" 120` |
| 2 | WC new order + completed order | O0 checkout | admin + customer | `New order #<O0>` / `is on its way` | Complete owner-filtered checkout delta after `$PREV`; save/show exact matching ids |
| 3 | WC new order | O1, O2 renewal orders | admin only | `New order #<order id>` | Complete owner-filtered renewal deltas after `LIFE04_REN1_PRE`/`LIFE04_REN2_PRE`; customer WC order mail is suppressed |
| 4 | payment_successful x2 | renewals 1, 2 | slt-core@example.test | `Payment received for subscription #S4` | `mailpit-agent wait-new "$LIFE04_REN1_PRE" 900 ...` on D2 and `mailpit-agent wait-new "$LIFE04_REN2_PRE" 900 ...` on D4 |
| 5 | subscription_expired | status -> expired | slt-core@example.test | `has expired` | identify exactly once among messages newer than `LIFE04_REN2_PRE` on D4 |
| 6 | expiring_soon probe x1 | targeted step-9 action only | slt-core@example.test | `is ending soon` | capture the single probe message and its action ID |
| 7 | NONE EXPECTED naturally: renewal_reminder, expiring_soon, renewal_invoice | — | — | `renews soon`, extra `is ending soon`, `Invoice for subscription` | absent from the complete task-owned checkout/R1/R2 deltas outside the recorded probe |

## Evidence to capture
- Screenshots `SLT-LIFE-04-01-checkout.png`, `-02-pending-d0.png`, `-03-renewal1.png`, `-03a-probe-preflight.png`, `-03b-probe-exact-action.png`, `-04-expired.png`, `-05-pending-empty.png`.
- S4, O0/O1/O2, k, the three meta dumps, Mailpit IDs, checkout console errors.

## Pass criteria
- [ ] `_end_date` absent at creation, stamped at the final charge moment
- [ ] Two unattended $7.00 renewals inside [due+k, due+k+5min]
- [ ] Status `arraysubs-expired` immediately after payment 3
- [ ] Zero pending legs and no third renewal order after expiry
- [ ] Exact phase-specific mail set above, including one targeted probe with its safe queue pre-flight and every natural negative

## Isolation / teardown
- S4 is terminal by D4. `SLT-SETUP-99A` neither cancels nor deletes the already-expired record; `SLT-SETUP-99B` deletes it on 2026-08-15 after the watch.
- The step-9 probe adds one AS row and `_arraysubs_expiring_soon_sent_for` - record both. No settings changed.

## D0 execution checkpoint — 2026-08-02

**PASS / future legs armed.** Product `11933` was purchased by `slt-core` after the empty-cart pre-flight. Initial order `12016` completed for exactly `$7.00` (USD, Stripe, `store-api`, one line item, zero tax), creating active subscription `12017` at `2026-08-02 13:45:58Z` (`19:45:58 UTC+6`). Creation meta is `_billing_period=day`, `_billing_interval=2`, `_subscription_length=3`, `_recurring_amount=7`, `_completed_payments=1`, `_next_payment_date=2026-08-04 13:45:58Z`, and no `_end_date`.

The spread is `k=11685` seconds. Pending action `13722` (`arraysubs_generate_renewal_invoice`, group `arraysubs-billing`) is due `2026-08-04 11:00:43Z`; pending action `13723` (`arraysubs_process_renewal`, group `arraysubs-renewals`) is due `2026-08-04 17:00:43Z`. There is no reminder or expiring-soon action. Actions `13720`/`13721` were created and immediately cancelled during gateway synchronization before the two final pending legs were installed; they are historical, not extra pending work.

Mailpit captured exactly the four expected checkout messages: customer completed order `1KzrrRTVt7d72gJMu8m4IT`, admin new order `4Hp7mZXVLXp8Ng79jePWce`, customer active subscription `5keHjraWKEDzSkCVK8P6lL`, and admin new subscription `1TEtyKteMSIQsmEdMmsvrj`. Evidence: `/home/server-manager/slt-evidence/SLT-LIFE-04-01-checkout.png`, `SLT-LIFE-04-02-pending-d0.png`, and `SLT-LIFE-04-D0-facts.txt`.

Timed handoff: inspect renewal #1 only after action `13723`'s `2026-08-04 17:00:43Z` execution window; the next due remains anchored to `2026-08-06 13:45:58Z`, with the second charge expected at `2026-08-06 17:00:43Z`. Keep this card in progress until the D3 probe and D4 final-expiry assertions are complete.

## D2/D3 execution checkpoint — 2026-08-05

**R1 PASS; targeted probe PASS; final natural leg still armed.** Invoice action `13722` and charge action `13723` ran unattended through WP Cron. The charge started at `2026-08-04 17:01:04Z`, 21 seconds after its `17:00:43Z` gate, and completed at `17:01:19Z`. Exact renewal order `12435` completed for USD `$7.00`, zero tax, one product-`11933` line, `_subscription_id=12017`, `_renewal_cycle_number=2`, and `_renewal_scheduled_date=2026-08-04 13:45:58Z`. Subscription `12017` is still active with `_completed_payments=2`, `_next_payment_date=2026-08-06 13:45:58Z`, and no `_end_date`.

The exact task-owned R1 mail pair is admin new-order message `30B2GoExh433hNkOz2h2cd` and customer payment-success message `5iI0kTdzUty97gtzZS2FG7`. The required `LIFE04_REN1_PRE` cursor was missed contemporaneously. A complete chronological Mailpit read proves `56ocFeowqMe8oB9jx94783` was the immediately preceding message and only those two messages followed through the R1 gate; this is explicitly a post-hoc reconstruction, not a claim that the baseline was captured on time.

For the D3 probe, the real Pending queue and DB preflight were quiet for the next five minutes. Exact action `14773` (`arraysubs_send_expiring_soon`, args `[12017]`, group `arraysubs-emails`) was scheduled 12 hours ahead, re-verified in the UI, and run only through its authenticated exact-row Admin List Table route. It completed at `2026-08-05 06:46:04Z`, emitted exactly one post-baseline message `4fF85EPlTg99cFq6urDuhS` (`Your subscription #12017 is ending soon`), and wrote `_arraysubs_expiring_soon_sent_for=2026-08-06 13:45:58|7` plus `_arraysubs_expiring_soon_sent_at=2026-08-05 06:46:03`.

Evidence: `/home/server-manager/slt-evidence/SLT-LIFE-04-D03-facts.txt` and the required `-03-renewal1.png`, `-03a-probe-preflight.png`, `-03b-probe-exact-action.png`, plus supplemental `-03c-probe-complete.png`. Browser page errors were empty.

Timed handoff: capture `LIFE04_REN2_PRE` only during `[2026-08-06 16:55:43Z, 17:00:43Z)` (`[22:55:43, 23:00:43)` site), then observe charge action `14568` naturally after `17:00:43Z`. Do not force it. Keep this card in progress until O2, cycle-3 expiry, the exact final mail delta, and the empty replacement queue are proven.


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

[[2026-08-05]] Wed 08:51
Checkpoint handoff: D2 renewal 1 and D3 targeted probe reconciled PASS in SLT-LIFE-04-D03-facts.txt. Final natural charge remains action 14568 at 2026-08-06 17:00:43Z; capture LIFE04_REN2_PRE only during 16:55:43Z-17:00:42Z, never force. Claim released while waiting.

[[2026-08-05]] Wed 14:35
Board hygiene checkpoint: no actionable step is available right now; the current body already records the exact next gate/window and required natural-watch constraints. Parking this future-gated card back in todo so In Progress reflects only currently active execution.

[[2026-08-05]] Wed 14:45
D03 watcher correction: the 18:35 board-hygiene note is superseded by the explicit phase contract; a future authored gate stays in-progress. Next gate remains D4 2026-08-06 LIFE04_REN2_PRE 16:55:43Z-17:00:42Z, then natural action 14568 after 17:00:43Z.

[[2026-08-05]] Wed 15:01
Board hygiene checkpoint: parked future-gated or watch-only work returned to todo so in-progress reflects only actively worked cards.

[[2026-08-05]] Wed 15:30
Board correction: restored to in-progress. Next gate D4: capture baseline 2026-08-06 16:55:43Z-17:00:42Z (22:55:43-23:00:42 site), then observe natural action 14568 at/after 17:00:43Z; never force.

[[2026-08-05]] Wed 16:41
D4 final-charge follow-up: capture baseline 2026-08-06 16:55:43Z-17:00:42Z for natural action 14568 at 17:00:43Z; verify final payment, expiry, and mail.

[[2026-08-05]] Wed 16:46
Board hygiene: returned to todo because this card is not in an active execution window right now. Resume only at the exact gate or follow-up already recorded on the card.

[[2026-08-05]] Wed 17:26
D4 baseline 2026-08-06 16:55:43Z-17:00:42Z; natural action 14568 at 17:00:43Z.

[[2026-08-05]] Wed 17:44
D4 follow-up: capture baseline 2026-08-06 16:55:43Z–17:00:42Z; observe natural action 14568 after 17:00:43Z.

[[2026-08-06]] Thu 20:10
Closed from completed D0/D2/D3/D4 evidence. Final D4 charge/expiry proved in watch-reports/D04-2026-08-06.md: renewal order 12929 completed at 7.00, subscription 12017 expired immediately, payment mail 1YCZEsnunjb685g8ZYqhWx and expired mail 7LFSsjbiAzEYI2AHiljER5 observed.
