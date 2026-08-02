---
id: 80
title: 'EXPLORATORY: SLT Grouped Set rendering, add-to-cart probes, and one order through the grouped form'
status: todo
priority: medium
created: 2026-08-02T03:43:09.920245061+02:00
updated: 2026-08-02T03:43:20.780820995+02:00
tags:
    - checkout
    - day-05
due: "2026-08-07"
estimate: 1h 30m
depends_on:
    - 39
    - 5
    - 58
    - 12
class: standard
---

> **SLT-CHK-12** · group `checkout` · scheduled **D05** (2026-08-07)

> Read `README.md` (environment + isolation contract), `calendar.md` (this day's exact
> ordering — it is binding, not advisory) and `plan-audit.md` before starting.
## Objective
EXPLORATORY. Grouped products get zero handling in either plugin (the `Subscription [ArraySubs]` checkbox is `show_if_simple show_if_variable`), so a grouped parent can never be a subscription. Document what `SLT Grouped Set` renders, whether a subscription child can be bought via the grouped form, and how the order and subscription differ from a direct buy. File what you find; assert no spec.

## Scope
- Gateway: Stripe test
- Checkout: block
- Account: admin-created (this task creates `slt-grouped`)
- Plugins: free-only

## Preconditions
- `SLT-PROD-09` complete: `SLT Grouped Set` with children Daily Core, Signup Fee Daily, Grouped Extra; its refusal text is in the registry.
- `SLT-SETUP-02` baseline; `allow_multiple_in_cart=false`, `allow_mixed_cart=true`.
- **Creates one account**: `slt-grouped` / `slt-grouped@example.test`, Customer, pw `SltQa!2026#Pass`, billing address per `SLT-SETUP-03` step 4 — `slt-core` already owns subs for both subscription children and must not rebuy them.
- Session `grouped-CHK12`; cart empty first and last.

## Test data
| Item | Value |
|---|---|
| Product | SLT Grouped Set (`slt-grouped-set`) |
| Children | Daily Core $10.00/day; Signup Fee Daily $9.00/day + $15.00 fee; Grouped Extra $3.00 |
| Account | slt-grouped (created here) |
| Card | 4242 4242 4242 4242 |
| Order | Daily Core x1 + Grouped Extra x1 = **$13.00** |

## Steps
1. Create the user at `/wp-admin/user-new.php` (untick **Send User Notification**), then set billing address at `user-edit.php`.
2. `PREV=$(mailpit-agent latest-id)`.
3. `agent-browser --session grouped-CHK12 open "https://mirror-help.arrayhash.com/my-account/"` -> log in `slt-grouped`.
4. Open `/slt-grouped-set` -> `snapshot -i`. Per row record: price string, schedule text, fee text, quantity box vs Add-to-cart link.
5. Probe A: qty 1 on **SLT Daily Core** -> submit -> `/cart/` -> snapshot; record line total and whether the schedule renders.
6. Probe B: also qty 1 on **SLT Signup Fee Daily**, submit -> snapshot; record the notice verbatim and which line survived. Remove it.
7. Probe C: cart = Daily Core x1 + Grouped Extra x1 -> `/checkout/` -> `snapshot -i` -> pay Stripe 4242 -> **Place Order**.
8. Record order + sub ID. `wp post meta list <SUB_ID> --keys=_product_id,_variation_id,_billing_period,_billing_interval,_recurring_amount,_signup_fee,_next_payment_date,_renewal_action_id --allow-root`.
9. Diff that dump against the `SLT Daily Core` sub from the direct purchase (quote its task key and sub ID). Any difference is the finding.
10. wp-admin order view: two line items, only the subscription line carries subscription meta, no link to the grouped parent ID.
11. Empty cart; `close --session grouped-CHK12`. Watch: on 2026-08-08 this sub renews $10.00 and the $3.00 line must NOT reappear.

## Expected results
1. The grouped page renders a child table; record whether each subscription child shows a recurring summary or a plain price — the observation is the deliverable, not a verdict.
2. Probe A adds one subscription line at $10.00.
3. Probe B refused: one subscription line survives with a notice matching the `SLT-PROD-09` string.
4. Probe C total **$13.00**, no tax line, status `processing`/`completed`.
5. Exactly ONE subscription, for `SLT Daily Core`: `_product_id`=child ID (never the grouped parent), `_variation_id`=0, `_billing_period=day`, `_billing_interval=1`, `_recurring_amount=10.00`, `_signup_fee` empty/0, `_next_payment_date` 2026-08-08.
6. `SLT Grouped Extra` produces no subscription and no schedule meta.
7. Step 9 diff is empty; any difference is filed as an issue.

## Emails expected
| # | Email | Trigger point | Recipient | Subject contains | Verify with |
|---|---|---|---|---|---|
| 1 | new_subscription | Probe C paid | slt-grouped@example.test | `is active` | `mailpit-agent wait-new "$PREV" 180 "is active"` |
| 2 | admin_new_subscription | same | admin | `New subscription #` | `mailpit-agent list 50` |
| 3 | NONE EXPECTED — new-user notification | step 1 | — | — | latest-id must not move before step 3 |
| 4 | NONE EXPECTED — 2nd subscription mail | Probe C | — | — | Exactly one `is active` for this order |

## Evidence to capture
- `SLT-CHK-12-01-grouped-page.png`, `-02-probe-a.png`, `-03-probe-b-refusal.png`, `-04-probe-c-checkout.png`, `-05-admin-order.png`.
- User/order/sub IDs, meta dump + diff, verbatim refusal notice, Mailpit IDs, console/network errors.

## Pass criteria
- [ ] Grouped page rendering documented per row
- [ ] Probe A/B behaviour recorded with refusal text
- [ ] Probe C totals exactly $13.00
- [ ] One subscription only, keyed to the child product ID
- [ ] Meta diff vs the direct purchase captured
- [ ] Emails 1-2 captured; negatives 3-4 hold

## Isolation / teardown
- Creates `slt-grouped` (deleted by `SLT-SETUP-99B`) and one live daily sub for the watch, cancelled by `SLT-SETUP-99A` on D10.
- Nothing global changed; cart emptied; only `grouped-CHK12` closed. Do NOT flip `allow_multiple_in_cart`.

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
