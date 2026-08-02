---
id: 73
title: 'Retention: accept the 20%-for-3-cycles discount and prove exactly 3 discounted renewals, plus a downgrade offer'
status: todo
priority: critical
created: 2026-08-02T03:43:09.416971494+02:00
updated: 2026-08-02T03:43:20.077923845+02:00
tags:
    - plan-switching
    - day-04
    - has-conflicts
due: "2026-08-06"
estimate: 2h
depends_on:
    - 11
    - 12
    - 60
class: standard
---

> **SLT-SW-09** · group `switching` · scheduled **D04** (2026-08-06)

> Read `README.md` (environment + isolation contract), `calendar.md` (this day's exact
> ordering — it is binding, not advisory) and `plan-audit.md` before starting.

### ⚠ Conflict resolutions that apply to this task

**`high` · session collision (shared admin session)** — with `SLT-EML-01`, `SLT-EML-02`, `SLT-EML-03`, `SLT-EML-05`, `SLT-EML-10`, `SLT-EML-12`

- *Problem:* More than twenty tasks open `--session admin` by that bare name, in direct violation of audit C09's fix (which only got applied to guest/customer sessions). agent-browser sessions are keyed by name, so these tasks share one browser profile: one task's `agent-browser close --session admin` logs out another mid-run; one task navigating the SPA away from a settings screen invalidates another's snapshot; and any admin session that ever adds to cart (SLT-ADM-01's bulk-action screen, SLT-EML-12's status juggling) puts a cart on the admin user shared by all of them. The failures this produces look like flaky UI, not contamination.
- *Required fix:* Rename every admin session to `admin-<TASK-KEY>` (SLT-ADM-01/02/03/04/05 already do this correctly - copy the pattern). Each task closes only its own session by name; `agent-browser close --all` is reserved to the last task of the day, named explicitly in the calendar. Add this to the isolation contract as rule 9 and add a pass criterion to every task that opens an admin session: 'the session name contains this task's key'.

**`high` · shared-global-setting / undeclared exclusive bracket** — with `SLT-EML-13`, `SLT-CHK-08`, `SLT-CHK-13`, `SLT-SYN-07`, `SLT-SYN-11`, `SLT-IMP-03`

- *Problem:* SLT-EML-13 (d4) disables all four ArraySubs admin emails site-wide for a bracket it bounds only as '08:00-09:00 site, under 20 min'. D4 (2026-08-06) carries the heaviest checkout load of the middle of the window: SLT-CHK-08 places two checkouts, SLT-SYN-11 three, SLT-IMP-03 three, SLT-SW-09 two, plus SLT-CHK-13 and SLT-SYN-07. Every admin_new_subscription for a checkout inside the bracket is silently lost, and those tasks' email tables assert it as present. SLT-ADM-03/ADM-05 also drive status transitions on D4 whose admin notifications would vanish. Conversely, if any of those checkouts drifts into the bracket, EML-13's own 'exactly one message' silence proof is contaminated by their customer mail.
- *Required fix:* Fix the bracket at 08:00-08:20 site on D4 and make it the FIRST thing that happens that day - before any product save, cart, checkout or status change. Add a pre-flight step (already half-present as step 1): screenshot Tools -> Scheduled Actions Pending for the next 2h and abort if any renewal/retry/overdue/cancel action is due, AND assert no SLT checkout task is in-progress on the board. Publish the open/close UTC to the registry. Add 'no checkout before 08:30 site on D4' to the D4 row of the calendar.

---
## Objective
Prove retention on real renewals: accept the 20%-off-for-3-cycles offer in the cancel flow and show the discount is charged for **exactly three** renewals with the fourth back at full price; and on a second subscription accept the **downgrade** offer and show it becomes a pending switch applied at the next renewal.

## Scope
- Gateway: Stripe test
- Checkout: block
- Account: new registered — **this task creates `slt-retain` + `slt-retain2`**
- Plugins: free-only

## Preconditions
- SLT-SETUP-02/03 and **SLT-PROD-11 done before this task starts on D4**; if PROD-11 slips, run on D5 and shift every watch day by +1. Two accounts: `hasUsedDiscountOffer()` allows one discount per subscription.
- Baseline `cancellation.retention_offers`: discount on (20%, 3 cycles), downgrade on, pause/skip/contact off; `cancel_immediately=false`, `require_reason=true`.
- Sessions `admin`, `cust-SLT-SW-09a/b`; carts empty first and last; buy after 12:00.

## Test data
| Item | Value |
|---|---|
| A | slt-retain buys **SLT Plan Basic** $5.00 day/1; discounted renewal **$4.00** (line −$1.00) |
| B | slt-retain2 buys **SLT Plan Peer** $15.00 day/1; downgrade target **SLT Plan Basic** $5.00. Card 4242 4242 4242 4242 |
| Watch | A renews D5/D6/D7 at $4.00 and **D8 (08-10) at $5.00** — reported on watch D6-**D9** |

## Steps
1. `latest-id` → `MP0`; create both accounts as in SLT-SW-06 step 2.
2. `cust-SLT-SW-09a`: log in, cart empty, `/checkout/?add-to-cart=<Plan Basic ID>`, pay 4242. Record `SUB_A`, its crc32 `OFFSET` and `_next_payment_date`.
3. `/my-account/view-subscription/<SUB_A>/` → **Cancel Subscription** → reason **Too expensive** → **Continue**.
4. On **Before You Go...** screenshot the offers, accept **Stay and Save!** ("20% off for the next 3 billing cycles"), screenshot the result.
5. `wp post meta list <SUB_A> --allow-root`: record the five `_retention_discount_*` keys, `_retention_offer_type`, `_waiting_cancellation`; reload and screenshot the **Recurring Amount** row — $4.00, "Discounted from $5.00 for the next 3 renewal(s)."
6. `cust-SLT-SW-09b`: log in, cart empty, buy **SLT Plan Peer** with 4242. Record `SUB_B`.
7. Repeat the cancel flow on `<SUB_B>` but accept **Switch to a smaller plan** → **SLT Plan Basic**; screenshot the banner, dump `_retention_offer_type`, `_waiting_cancellation`, `_recurring_amount` and the pending-switch meta.
8. `list 20`. Hand off: on D6-D9 the watch records each renewal order id, total, whether a `Retention Discount` line exists, and `_retention_discount_remaining`.

## Expected results
1. `SUB_A` after step 4: `_retention_discount_type=percent`, `_amount=20`, `_remaining=3`, `_base_amount=5.00`, `_effective_amount=4.00`, `_retention_offer_type=coupon`; `_waiting_cancellation` **deleted**, still `arraysubs-active`.
2. Renewals 1-3 (D5-D7) each total **$4.00** with a `Retention Discount` line of **−$1.00**; `_retention_discount_remaining` goes 3→2→1→0, only after each order is paid.
3. At 0 the five keys are deleted and renewal 4 (D8, watch D9) totals **$5.00** with **no** discount line — exactly three discounted.
4. `SUB_B` after step 7: `_retention_offer_type=downgrade`, `_waiting_cancellation` deleted, still active, `_recurring_amount` **15.00**, pending switch to Basic ("Plan switch to SLT Plan Basic scheduled for …").
5. `SUB_B` renewal 1 charges **$15.00**; the switch applies once it is paid, so renewal 2 charges **$5.00** and `_recurring_amount` becomes 5.00.

## Emails expected
| # | Email | Trigger point | Recipient | Subject contains | Verify with |
|---|---|---|---|---|---|
| 1 | new_subscription, admin copy, Woo order mail | steps 2, 6 | customers, admin | `is active` | `wait-new MP0 180` |
| 2 | retention_discount_accepted | step 4 | slt-retain | `Your retention discount for SLT Plan Basic is active` | `wait-new <pre-4> 180` |
| 3 | NONE for the downgrade offer, NONE pending-cancellation | steps 3-4, 7 | — | — | only discount/coupon offers email; accepting one clears `_waiting_cancellation` before it is scheduled |

## Evidence to capture
- `SLT-SW-09-01-offers.png`, `-02-accepted.png`, `-03-recurring.png`, `-04-downgrade.png`, `-05-pending.png`; `SUB_A`, `SUB_B`, offsets, metas, every renewal order id + total, Mailpit ids

## Pass criteria
- [ ] Discount stores 20% / remaining 3 / effective $4.00, clears the pending cancellation, Recurring Amount reads $4.00 from $5.00
- [ ] D5-D7 renewals total $4.00 with a −$1.00 Retention Discount line; D8 totals $5.00 with none, discount metas gone
- [ ] Downgrade offer creates a pending switch, sends no email, leaves $15.00 recurring
- [ ] `SUB_B` renewal 1 = $15.00, renewal 2 = $5.00; email 2 present, email 3 absent

## Isolation / teardown
- Both subscriptions stay active through watch day D9 (2026-08-11); SLT-SETUP-99 must not cancel them earlier.
- No global setting changed. Do not accept a second offer on either subscription — it is refused and would confuse the cycle count. Both accounts are deleted by SLT-SETUP-99.

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
