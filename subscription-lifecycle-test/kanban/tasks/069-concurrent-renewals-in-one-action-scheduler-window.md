---
id: 69
title: 'Concurrent renewals in one Action Scheduler window: no skips, no double charges, offsets stagger'
status: todo
priority: high
created: 2026-08-02T03:43:09.156259788+02:00
updated: 2026-08-02T03:43:19.654835028+02:00
tags:
    - edge-cases
    - day-04
    - has-conflicts
due: "2026-08-06"
estimate: 2h on D4 + 45m follow-up on D5
depends_on:
    - 11
    - 12
    - 5
class: standard
---

> **SLT-IMP-03** · group `implied` · scheduled **D04** (2026-08-06)

> Read `README.md` (environment + isolation contract), `calendar.md` (this day's exact
> ordering — it is binding, not advisory) and `plan-audit.md` before starting.

### ⚠ Conflict resolutions that apply to this task

**`high` · session collision (shared admin session)** — with `SLT-EML-01`, `SLT-EML-02`, `SLT-EML-03`, `SLT-EML-05`, `SLT-EML-10`, `SLT-EML-12`

- *Problem:* More than twenty tasks open `--session admin` by that bare name, in direct violation of audit C09's fix (which only got applied to guest/customer sessions). agent-browser sessions are keyed by name, so these tasks share one browser profile: one task's `agent-browser close --session admin` logs out another mid-run; one task navigating the SPA away from a settings screen invalidates another's snapshot; and any admin session that ever adds to cart (SLT-ADM-01's bulk-action screen, SLT-EML-12's status juggling) puts a cart on the admin user shared by all of them. The failures this produces look like flaky UI, not contamination.
- *Required fix:* Rename every admin session to `admin-<TASK-KEY>` (SLT-ADM-01/02/03/04/05 already do this correctly - copy the pattern). Each task closes only its own session by name; `agent-browser close --all` is reserved to the last task of the day, named explicitly in the calendar. Add this to the isolation contract as rule 9 and add a pass criterion to every task that opens an admin session: 'the session name contains this task's key'.

**`high` · shared-global-setting / undeclared exclusive bracket** — with `SLT-EML-13`, `SLT-CHK-08`, `SLT-CHK-13`, `SLT-SYN-07`, `SLT-SYN-11`, `SLT-SW-09`

- *Problem:* SLT-EML-13 (d4) disables all four ArraySubs admin emails site-wide for a bracket it bounds only as '08:00-09:00 site, under 20 min'. D4 (2026-08-06) carries the heaviest checkout load of the middle of the window: SLT-CHK-08 places two checkouts, SLT-SYN-11 three, SLT-IMP-03 three, SLT-SW-09 two, plus SLT-CHK-13 and SLT-SYN-07. Every admin_new_subscription for a checkout inside the bracket is silently lost, and those tasks' email tables assert it as present. SLT-ADM-03/ADM-05 also drive status transitions on D4 whose admin notifications would vanish. Conversely, if any of those checkouts drifts into the bracket, EML-13's own 'exactly one message' silence proof is contaminated by their customer mail.
- *Required fix:* Fix the bracket at 08:00-08:20 site on D4 and make it the FIRST thing that happens that day - before any product save, cart, checkout or status change. Add a pre-flight step (already half-present as step 1): screenshot Tools -> Scheduled Actions Pending for the next 2h and abort if any renewal/retry/overdue/cancel action is due, AND assert no SLT checkout task is in-progress on the board. Publish the open/close UTC to the registry. Add 'no checkout before 08:30 site on D4' to the D4 row of the calendar.

---
## Objective
Make several SLT subscriptions fall due in one Action Scheduler window and prove all renew, none is skipped, none double-charges, the locks hold, and the crc32 spread offset really staggers the charge legs.

## Scope
- Gateway: Stripe test
- Checkout: block
- Account: new registered x3 (created by this task)
- Plugins: both

## Preconditions
- SLT-PROD-01 done; `SLT Daily Core` ($10.00 day/1) published; SLT-SETUP-02 baseline held.
- ALSO CREATES Customer accounts `slt-conc1/2/3@example.test`, pw `SltQa!2026#Pass`, billing per SLT-SETUP-03 step 4; `slt-*`, so SLT-SETUP-99B clears them.
- The three purchases run back-to-back in ONE 10-min window after 13:00 site on 2026-08-06 (D4). Each account buys once (C08).
- Sessions `customer-conc1/2/3` (C09). NO `wp action-scheduler run`: renewals fire unattended; one that does not is a genuine bug.

## Test data
| Item | Value |
|---|---|
| Product / buyers | SLT Daily Core $10.00 day/1 / slt-conc1,2,3 |
| Card | 4242 4242 4242 4242 |
| Window T | 2026-08-06, 10-min slot after 13:00 site |
| Legs | due `T+24h`; charge `+k_i`, invoice `+k_i-6h`; `k_i = crc32('arraysubs-spread-'.$id_i) % 21600` |

## Steps
1. Create the three users (untick **Send User Notification**) and set billing addresses.
2. `mailpit-agent latest-id` -> `M0`; record the window's UTC start.
3. For i = 1..3: `agent-browser --session customer-conc<i> open ".../my-account/"`, log in, confirm `/cart/` empty, add `SLT Daily Core`, `/checkout/`, pay. Record order id + click time; all three inside 10 min.
4. Record the three subscription ids, compute the offsets with `php -r 'foreach([<id1>,<id2>,<id3>] as $i){$h=(int)sprintf("%u",crc32("arraysubs-spread-".$i));printf("%d %d\n",$i,$h%21600);}'`, and log the predicted invoice and charge instants BEFORE they fire.
5. Enumerate the natural cohort with `wp post list --post_type=arraysubs_data --post_status=arraysubs-active --fields=ID --allow-root`: every SLT sub due 2026-08-07.
6. `agent-browser --session admin open ".../tools.php?page=action-scheduler&status=pending&s=arraysubs_process_renewal"`; screenshot pending legs. The three must NOT share a timestamp.
7. FOLLOW-UP 2026-08-07 (D5): re-open Scheduled Actions at `status=complete` then `status=failed`, screenshot both, dump `_next_payment_date,_last_payment_date,_completed_payments` for the three.
8. FOLLOW-UP: list orders created 2026-08-07 for the three buyers, count renewal orders each; `mailpit-agent list 50`, count `Payment received for subscription #<id>` each; grep `debug.log` and `wc-logs/*2026-08-07*` for `already_processing`, `lock`, `Fatal`.

## Expected results
1. `k_1..k_3` are pairwise different and the three `arraysubs_process_renewal` actions carry three different timestamps despite due dates within 10 min.
2. All three, plus every cohort subscription due 2026-08-07, end `complete` on both `arraysubs_generate_renewal_invoice` and `arraysubs_process_renewal`.
3. `status=failed` gains **zero** rows referencing an SLT subscription.
4. Exactly ONE renewal order per subscription per cycle, `processing`/`completed`, `$10.00`; no two renewal orders share a `_renewal_cycle_number`.
5. `_completed_payments` +1 each; `_next_payment_date` advances exactly 24 h from `_renewal_scheduled_date`, not charge time — `k` must not accumulate.
6. Exactly one `Payment received for subscription #<id>` each — the `_arraysubs_renewal_payment_success_email_sent` guard holds. An action requeued at `now+2min` on lock contention still completes; one left `pending` is a failure.

## Emails expected
| # | Email | Trigger point | Recipient | Subject contains | Verify with |
|---|---|---|---|---|---|
| 1-3 | new_subscription x3 | each checkout | slt-conc1/2/3 | `is active` | `mailpit-agent wait-new M0 180 "is active"` |
| 4-6 | admin_new_subscription x3 | each checkout | admin_email | `New subscription #` | `mailpit-agent list 50` |
| 7-9 | payment_successful x3 | `due+k_i` on D5 | each buyer | `Payment received for subscription #` | D5 follow-up |

No `Invoice for subscription #` expected (suppressed for automatic-payment subs, SLT-REF-10 L23) — negative check on D5.

## Evidence to capture
- `SLT-IMP-03-01-pending.png`, `-02-complete.png`, `-03-failed.png`, `-04-orders.png`; three subscription ids, all order ids, the offset table, the cohort list, `M0`, Mailpit ids, grep output.

## Pass criteria
- [ ] three distinct offsets and three distinct scheduled timestamps
- [ ] every leg completes; zero SLT rows under `status=failed`
- [ ] exactly one renewal order per subscription per cycle at $10.00
- [ ] `_completed_payments` +1 each, next due exactly +24 h
- [ ] exactly one payment_successful email per subscription
- [ ] no fatal or lock-timeout line in the window

## Isolation / teardown
- Adds three SLT Daily Core subscriptions renewing daily until SLT-SETUP-99A cancels them; register the ids for the D5-D9 watch.
- Creates slt-conc1..3; nothing global changes. Empty each cart, close each session.

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
