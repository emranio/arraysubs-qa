---
id: 93
title: 'EARLY renew from the customer portal: full amount, next date anchored to the original due date, legs replaced'
status: done
priority: high
created: 2026-08-02T03:43:10.879397181+02:00
updated: 2026-08-08T12:51:52.971965254+02:00
started: 2026-08-08T12:51:52.971964333+02:00
completed: 2026-08-08T12:51:52.971964333+02:00
tags:
    - renewal
    - day-06
due: "2026-08-08"
estimate: 1h30m
depends_on:
    - 11
    - 5
    - 2
claimed_by: spur-gust
claimed_at: 2026-08-08T12:51:52.971965154+02:00
class: standard
---

> **SLT-LIFE-02** · group `renewal` · scheduled **D06** (2026-08-08)

> Read `README.md` (environment + isolation contract), `calendar.md` (this day's exact
> ordering — it is binding, not advisory) and `plan-audit.md` before starting.

## Objective
Renew a live subscription EARLY from the customer portal and prove SLT-REF-07 Part A: the charge is the ordinary full renewal total, no discount or proration (EarlyRenewManager.php:180); `_next_payment_date` advances one full cycle from the ORIGINAL due date, not the payment moment, because the order carries `_renewal_scheduled_date = D_original` (OrderIntegration.php:1637-1643); the stale legs are replaced, not duplicated. Plus the Paddle negative - `PaddleGateway` declares `early_renewal => false`.

## Scope
- Gateway: Stripe test (Paddle read-only negative)
- Checkout: N/A (portal action)
- Account: existing (`slt-core2`, owner of the classic-checkout control)
- Plugins: pro-required

## Preconditions
- `customer_actions.allow_early_renew = true` from the SLT-SETUP-02 baseline; quote the "WINDOW BASELINE (frozen)" registry table (audit C14) and toggle nothing here.
- Resolve `SUB_CORE2` as the exact live `arraysubs-active` SLT Daily Core subscription created by `SLT-CHK-02` for `slt-core2`; quote its registry row and re-check owner/product IDs. It must be Stripe with a saved token, unsynced, with no pending skip and no open renewal order. **Never target `SUB_CORE`/`S1`; that control spine is reserved for `SLT-EML-05` on D6.**
- **Timing gate:** eligibility returns `invoice_pending` once the invoice leg has run - act BEFORE `due + k - 6h`.

## Test data
| Item | Value |
|---|---|
| Subscription | `SUB_CORE2` (SLT-CHK-02 / slt-core2 / SLT Daily Core, day/1, $10.00), portal `/my-account/view-subscription/SUB_CORE2/` |
| Expected charge | $10.00 (full renewal, no proration) |
| D_original / new date | `SUB_CORE2`'s `_next_payment_date` at click time / D_original + 24h exactly |

## Steps
1. Dump `_next_payment_date,_completed_payments,_recurring_amount,_renewal_sync_enabled,_pending_renewal_order_id` for `SUB_CORE2`; record D_original; compute k and verify now < D_original + k - 6h, else defer a cycle.
2. In isolated `admin-SLT-LIFE-02`, screenshot `tools.php?page=action-scheduler&s=<numeric SUB_CORE2>&status=pending` and record the exact two action IDs/GMTs. `PREV=$(mailpit-agent latest-id)` immediately before the portal mutation.
3. `agent-browser --session customer-SLT-LIFE-02 open` the portal, log in as `slt-core2`; require the cart and `_woocommerce_persistent_cart_1` to be empty, then screenshot the **Early Renewal:** notice naming D_original and the resulting date.
4. Click **Renew Early**; screenshot the dialog (must state $10.00, D_original and the new date); click **Renew Now**; screenshot the success state and the console/network output of `/my-subscriptions/SUB_CORE2/early-renew`.
5. Read numeric `OE` from the successful early-renew REST response and abort if it is absent/non-numeric. Cross-check the exact order's `_subscription_id`/`_subscription_renewal` against numeric `SUB_CORE2`, its customer/product, and the subscription's order relationship; never select an order by recency. Repeat the step-1 dump; read OE's `_arraysubs_early_renewal`, `_renewal_scheduled_date`, `_renewal_cycle_number` and the note naming D_original.
6. In `admin-SLT-LIFE-02`, re-screenshot the exact subscription-filtered pending actions and require both old action IDs gone/cancelled plus exactly one replacement invoice/charge pair at the calculated dates. Poll immutable `PREV` in repeated calls no longer than 60 seconds through the two-minute cutoff for `Payment received for subscription #$SUB_CORE2`, save/show that exact message, and classify every message newer than `PREV`, allowing only WooCommerce admin mail linked to `OE`; re-open the portal and record whether Renew Early is offered again or blocked, and why.
7. Paddle negative uses a separate authenticated session `customer-paddle-SLT-LIFE-02`; screenshot the exact `SUB_PAD` portal page showing no early-renewal control. If the registry says `SUB_PAD unavailable`, record the source issue and mark this branch UNVERIFIED without browsing another account's subscription.
8. Registry note: "SUB_CORE2 cycle N was paid early by SLT-LIFE-02 on 2026-08-08", so the watch does not read the missing unattended renewal as a failure. Empty and verify the `slt-core2` cart plus persistent-cart meta, close `customer-SLT-LIFE-02`, `customer-paddle-SLT-LIFE-02` when opened, and `admin-SLT-LIFE-02`; independently review the exact relationship, schedule replacement, mail, cart, and Paddle-negative evidence; move the card through `review` to `done`, and require Review to return to zero. Any live defect goes only in `issues/SLT-LIFE-02-<concise-slug>.md` with task/stage/plan path; subscription/order/action/message IDs; user IDs/logins/emails/roles; exact routes/sessions/gate; reproduction; expected/actual; and UI/REST/meta/queue/Mailpit proof.

## Expected results
1. The portal shows the early-renewal notice and an enabled **Renew Early** button (baseline on, status active, Stripe `early_renewal => true`).
2. OE total is exactly $10.00 - no proration, no discount, no tax line.
3. OE carries `_arraysubs_early_renewal = yes`, anchor `_renewal_scheduled_date` = D_original and a note naming it; reaches a paid status (`processing` or `completed`, recorded exactly); `_completed_payments` +1.
4. `_next_payment_date` = D_original + 24h EXACTLY, NOT click_time + 24h - paying early does not shorten the paid-through period.
5. Old legs gone; one invoice and one charge leg remain, at (D_original+24h)+k-6h and +k, k unchanged.
6. The Paddle subscription shows no early-renewal UI at all.

## Emails expected
| # | Email | Trigger point | Recipient | Subject contains | Verify with |
|---|---|---|---|---|---|
| 1 | payment_successful + WC New order (admin) | early charge succeeds, OE becomes paid | slt-core2 + admin | `Payment received for subscription #SUB_CORE2`, `New order #OE` | immutable-baseline polls ≤60 seconds through the two-minute cutoff; correlate the complete delta by `SUB_CORE2`/`OE` |
| 2 | NONE EXPECTED: customer WC processing/completed mail for OE, renewal_invoice (Stripe suppression), new_subscription, renewal_reminder (1-day cycle) | — | — | `order is now processing`, `order is complete`, `Invoice for subscription`, `is active`, `renews soon` | absent from the complete `PREV` delta |

## Evidence to capture
- Screenshots `SLT-LIFE-02-01-notice.png`, `-02-dialog.png`, `-03-success.png`, `-04-pending-before.png`, `-05-pending-after.png`, `-06-paddle-no-button.png`.
- `SUB_CORE2`, owner/product IDs, OE, D_original, k, meta dumps, order meta + note, Mailpit IDs.

## Pass criteria
- [x] Renew Early offered; dialog states the amount and both dates
- [x] Charge exactly $10.00, no proration or discount
- [x] `_next_payment_date` = D_original + 1 cycle, not payment time + 1 cycle
- [x] OE anchored to D_original; one invoice + one charge leg at the new date + same k
- [x] Exactly the 2 email rows, negatives included
- [x] Paddle shows no early-renewal control (or marked UNVERIFIED)
- [x] Exact task sessions closed and independent review reaches `done` with Review empty

## Isolation / teardown
- `SUB_CORE2` keeps its daily grid, one cycle further on; the daily watch must be told (step 8) that this cycle was paid manually. `SUB_CORE`/`S1` is untouched.
- Nothing to restore - `allow_early_renew` is baseline, reverted by SLT-SETUP-99A.

## Execution note — D06 afternoon, 2026-08-08

- **PASS.** At 2026-08-08 10:35:16Z / 16:35:16 site, `SUB_CORE2=11991` (user 357, product 11927, Stripe) was renewed once from the customer portal before invoice action 15774. The dialog and success state proved the full `$10.00` amount and original-due-date anchor.
- Exact relationship resolution produced sole matching `OE=13257`, `wc-completed`, `$10.00`, one product-11927 line and no coupon/fee/tax rows. Metadata carries `_arraysubs_early_renewal=yes`, `_renewal_cycle_number=7`, `_renewal_scheduled_date=2026-08-08 13:23:13`, and both subscription links `11991`; completed payments advanced 6→7 and next payment advanced exactly 24h to `2026-08-09 13:23:13Z`.
- Old legs 15774/15775 were canceled unattempted; sole replacements 16010/16011 retain `k=20,473s` at `2026-08-09 13:04:26Z` and `19:04:26Z`. Immutable Mailpit baseline `57lxgHohFqevl55xhVeR3P` yielded exactly payment mail `5Og5lU3hYA9w6agG5zFt5x` and admin order mail `1v4wEPpLXvCUvaKkQCezmY`, with all authored negative subjects absent.
- Paddle subscription 12639 showed no early-renewal UI. Cart/persistent-cart remained empty, browser errors were empty, relevant REST/document/cart responses were 200, and exact sessions were closed. Registry page 11847 now carries the D7 no-unattended-renewal exception.
- Capture limitation only: the browser recorder did not expose the successful early-renew REST JSON body, so `OE` was resolved by the exact pre/post relationship set and exhaustively cross-checked rather than copied from that body. This did not leave a pass criterion unresolved.
- Evidence: `/home/server-manager/slt-evidence/SLT-LIFE-02-evidence.md`; screenshots `/home/server-manager/slt-evidence/SLT-LIFE-02-01-notice.png` through `SLT-LIFE-02-06-paddle-no-button.png` (plus `SLT-LIFE-02-04-portal-after.png`).


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
