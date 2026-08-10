---
id: 70
title: My Account subscriptions list and detail for slt-core - every field and every self-service action under the frozen baseline
status: done
priority: critical
created: 2026-08-02T03:43:09.225994237+02:00
updated: 2026-08-06T20:31:32.093082109+02:00
started: 2026-08-06T20:31:32.093081328+02:00
completed: 2026-08-06T20:31:32.093081328+02:00
tags:
    - admin
    - portal
    - day-04
due: "2026-08-06"
estimate: 2h
depends_on:
    - 11
    - 12
    - 5
    - 7
    - 1
class: standard
---

> **SLT-MYA-01** · group `admin` · scheduled **D04** (2026-08-06)

> Read `README.md` (environment + isolation contract), `calendar.md` (this day's exact
> ordering — it is binding, not advisory) and `plan-audit.md` before starting.

## Objective
Audit the customer portal for `slt-core`, who by D4 holds several SLT subscriptions of different shapes: the list table, the detail table, Related Orders, and every self-service action the frozen baseline exposes plus the ones that must not appear.

## Scope
- Gateway: Stripe test
- Checkout: N/A - portal only, no purchase, no action executed
- Account: existing (`slt-core`)
- Plugins: both (free CustomerPortal + pro EarlyRenew row)

## Preconditions
- SLT-SETUP-03 done. slt-core's D0 buys (Daily Core, Fixed Three Cycles, Lifetime One Time), D1 Renewal Price Step buy, and D3 Signup Fee Daily buy are complete.
- Quote the registry **WINDOW BASELINE (frozen)** table: `allow_early_renew`, `allow_reactivation`, `pause_subscription.enabled`, `customer_can_pause`, `allow_cancellation`, and `retention_offers_enabled` are true, while `skip_renewal.enabled=false`. That is why Renew Early and Pause render while Skip Next Renewal does not. All SLT products are Virtual, so no Shipping Address section may appear.

## Test data
| Item | Value |
|---|---|
| Account | slt-core / `SltQa!2026#Pass`, session `--session customer-MYA-01-SLT-MYA-01` |
| URLs | `https://mirror-help.arrayhash.com/my-account/subscriptions/`, `/my-account/view-subscription/<ID>/` |
| Amounts | Daily Core $10.00/day; Fixed Three Cycles $7.00/2d; Lifetime one-off; Signup Fee $9.00/day; Price Step $5.00/day |

## Steps
1. `MB01=$(mailpit-agent latest-id)`.
2. Open `.../my-account/` -> log in as slt-core. Snapshot the account menu: `Subscriptions` (count-badge class) and `My Features` must both be present.
3. Resolve the exact registry IDs for every authored slt-core subscription, then open the list URL, re-snapshot, and capture `SLT-MYA-01-01-list.png`. Record heading/headers and each exact ID's product, status, local next payment, total, and actions.
4. Read `Showing X-Y of N subscriptions`; per-page is 10 (`MyAccountHooks.php:283`) so no pagination nav may render. Open `/my-account/subscriptions/page/2/`.
5. Open the exact **SLT Daily Core** detail and capture `SLT-MYA-01-02-detail-daily-core.png`. Record all seven rows and the payment-method target; capture its action panel as `SLT-MYA-01-03-actions-block.png` without clicking any action.
6. Record `Subscription Actions` and `Manage Your Subscription` verbatim, whether a `Shipping Address` section renders, and `Related Orders` (`Order / Date / Status / Total / Actions`) including which rows offer `Pay`.
7. Repeat for exact IDs: capture Lifetime as `SLT-MYA-01-04-detail-lifetime.png`, Fixed Three Cycles as `-05-detail-fixed.png`, Signup Fee Daily as `-06-detail-signup-fee.png`, and Renewal Price Step as `-07-detail-price-step.png`.
8. Resolve a numeric Paddle subscription owned by another SLT user from the registry and open its URL, then numeric nonexistent `999999`; capture the two identical denial states as `-08-cross-account-denied.png` and `-09-missing-denied.png`.
9. Inspect every Mailpit message newer than `MB01`, require zero task-attributable mail, classify background mail, and record console/network errors per page. If any portal assertion fails, create a standalone issue with this task/plan, affected subscription/order/user IDs and login/role, exact URL/context, reproduction, expected/actual, UI/meta/network proof, and a passing sibling-subscription counterexample; never create a kanban bug card. Close only `customer-MYA-01-SLT-MYA-01`, independently review the evidence, move the card through `review` to `done`, and ensure Review returns to zero.

## Expected results
1. One row per slt-core subscription; status label matches the post status; `View` is the only action.
2. Next Payment renders in UTC+6 and equals `_next_payment_date` converted from UTC; Total = `_recurring_amount` in USD, no tax line, plus the schedule string.
3. `Showing 1-N of N subscriptions`, no pagination nav, `/page/2/` empty and notice-free.
4. Detail shows all seven rows, `Stripe` as payment-method title, and the link resolving to `/my-account/payment-methods/`.
5. Active subscriptions expose exactly `Change Plan`, `Cancel Subscription`, `Renew Early`, and `Pause Subscription`. `Skip Next Renewal` MUST be absent because `skip_renewal.enabled=false`; `Undo Scheduled Cancellation`, `Reactivate Subscription`, `Retry Payment`, and `Shipping Address` are also absent.
6. SLT Lifetime One Time shows `Next Payment: Lifetime Deal - No recurring payment` and offers no cycle action. During this D4 morning audit, SLT Fixed Three Cycles is still active before its late-D4 final charge: `_end_date` is absent, no `End Date:` row is fabricated, and its real next-payment date is shown. Its D4-late expiry is verified by `SLT-LIFE-04` and the D5 watch.
7. Related Orders lists parent plus every renewal with correct statuses/totals; `Pay` appears only on an order still needing payment.
8. Both step-8 probes return `Subscription not found or you do not have permission to view it.`, no PHP fatal.

## Emails expected
| # | Email | Trigger point | Recipient | Subject contains | Verify with |
|---|---|---|---|---|---|
| 1 | NONE EXPECTED | Entire task, read-only browsing | - | - | Complete delta after `MB01`; zero task-attributable mail, while unrelated/background mail is allowed and classified |

## Evidence to capture
- Screenshots `SLT-MYA-01-01` through `-09` named in steps 3-8.
- Notes table: each subscription id with status, `_next_payment_date` (UTC), site-local rendering, amount.
- Console/network errors per page; any PHP notice from step 4 or 8.

## Pass criteria
- [ ] List rows, totals and site-local dates correct; `/page/2/` clean with no pagination nav
- [ ] Four expected active-subscription actions present, five expected absences confirmed
- [ ] Lifetime text exact; Fixed Three Cycles correctly has no End Date before its final charge; detail rows complete
- [ ] Related Orders complete, Pay placement correct; invalid-access probes return the notice
- [ ] Zero mail sent
- [ ] Exact session closed and fully evidenced read reviewed to done

## Isolation / teardown
- Read-only: no action clicked, no cart touched, no setting changed. Hands SLT-MYA-02/03/04 the subscription-ID table.
- Close only `--session customer-MYA-01-SLT-MYA-01`; never `close --all`.

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

[[2026-08-06]] Thu 20:15
State-window note: not started before the source state changed. This task expects the pre-final-charge D4 portal view while Fixed Three Cycles is still active, but subscription 12017 already expired at 2026-08-06 17:01:16Z per the recovered D4 watch report. Do not sign it off from post-expiry UI evidence.

[[2026-08-06]] Thu 20:30
Blocked on 2026-08-06: the required pre-final-charge D4 frozen baseline is no longer observable. Task note already confirms subscription 12017 expired at 2026-08-06 17:01:16Z from the recovered D4 watch report, so this portal audit cannot be signed off without invalid post-expiry evidence.

[[2026-08-06]] Thu 20:31
UNVERIFIED closeout on 2026-08-06: final permitted D4 pre-expiry observation window was missed. Per suite README verdict policy, the execution task is closed with preserved evidence instead of being stranded in blocked. No valid post-expiry portal evidence can satisfy the original fixed-baseline assertions.
