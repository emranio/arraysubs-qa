---
id: 70
title: My Account subscriptions list and detail for slt2-core - every field and every self-service action under the frozen baseline
status: todo
priority: critical
created: 2026-08-22T19:10:00+02:00
updated: 2026-08-22T19:10:00+02:00
tags:
    - cycle-2
    - granular
    - admin
    - portal
    - day-04
due: "2026-08-27"
estimate: 2h
depends_on:
    - 11
    - 12
    - 5
    - 7
    - 1
class: standard
---

> **SLT-MYA-01** · group `admin` · scheduled **D04** (2026-08-27)

> Read `README.md` (environment + isolation contract), `calendar.md` (this day's exact
> ordering — it is binding, not advisory) and `plan-audit.md` before starting.

## Objective
Audit the customer portal for `slt2-core`, who by D4 holds several SLT2 subscriptions of different shapes: the list table, the detail table, Related Orders, and every self-service action the frozen baseline exposes plus the ones that must not appear.

## Scope
- Gateway: Stripe test
- Checkout: N/A - portal only, no purchase, no action executed
- Account: existing (`slt2-core`)
- Plugins: both (free CustomerPortal + pro EarlyRenew row)

## Preconditions
- SLT-SETUP-03 done. slt2-core's D0 buys (Daily Core, Fixed Three Cycles, Lifetime One Time), D1 Renewal Price Step buy, and D3 Signup Fee Daily buy are complete.
- Quote the registry **WINDOW BASELINE (frozen)** table: `allow_early_renew`, `pause_subscription.enabled`, `customer_can_pause`, `allow_cancellation`, and `retention_offers_enabled` are true, while `skip_renewal.enabled=false`. The retired `customer_actions.allow_reactivation` key must remain absent; reactivation is status/capability-gated and is not expected on these active rows. That is why Renew Early and Pause render while Skip Next Renewal does not. All SLT2 products are Virtual, so no Shipping Address section may appear.

## Test data
| Item | Value |
|---|---|
| Account | slt2-core / `SltQa!2026#Pass`, session `--session customer-MYA-01-SLT-MYA-01` |
| URLs | `https://mirror-help.arrayhash.com/my-account/subscriptions/`, `/my-account/view-subscription/<ID>/` |
| Amounts | Daily Core $10.00/day; Fixed Three Cycles $7.00/2d; Lifetime one-off; Signup Fee $9.00/day; Price Step $5.00/day |

## Steps
1. `MB01=$(mailpit-agent latest-id)`.
2. Open `.../my-account/` -> log in as slt2-core. Snapshot the account menu: `Subscriptions` (count-badge class) and `My Features` must both be present.
3. Resolve the exact registry IDs for every authored slt2-core subscription, then open the list URL, re-snapshot, and capture `SLT-MYA-01-01-list.png`. Record heading/headers and each exact ID's product, status, local next payment, total, and actions.
4. Read `Showing X-Y of N subscriptions`; per-page is 10 (`MyAccountHooks.php:283`) so no pagination nav may render. Open `/my-account/subscriptions/page/2/`.
5. Open the exact **SLT2 Daily Core** detail and capture `SLT-MYA-01-02-detail-daily-core.png`. Record all seven rows and the payment-method target; capture its action panel as `SLT-MYA-01-03-actions-block.png` without clicking any action.
6. Record `Subscription Actions` and `Manage Your Subscription` verbatim, whether a `Shipping Address` section renders, and `Related Orders` (`Order / Date / Status / Total / Actions`) including which rows offer `Pay`.
7. Repeat for exact IDs: capture Lifetime as `SLT-MYA-01-04-detail-lifetime.png`, Fixed Three Cycles as `-05-detail-fixed.png`, Signup Fee Daily as `-06-detail-signup-fee.png`, and Renewal Price Step as `-07-detail-price-step.png`.
8. Resolve a numeric Paddle subscription owned by another SLT2 user from the registry and open its URL, then numeric nonexistent `999999`; capture the two identical denial states as `-08-cross-account-denied.png` and `-09-missing-denied.png`.
9. Inspect every Mailpit message newer than `MB01`, require zero task-attributable mail, classify background mail, and record console/network errors per page. If any portal assertion fails, create a dedicated issue with this task/plan, affected subscription/order/user IDs and login/role, exact URL/context, reproduction, expected/actual, UI/meta/network proof, and a passing sibling-subscription counterexample; create or update the mandatory `qa/issues/` kanban card. Close only `customer-MYA-01-SLT-MYA-01`, independently review the evidence, move the card through `review` to `done`, and ensure Review returns to zero.

## Expected results
1. One row per slt2-core subscription; status label matches the post status; `View` is the only action.
2. Next Payment renders in UTC+6 and equals `_next_payment_date` converted from UTC; Total = `_recurring_amount` in USD, no tax line, plus the schedule string.
3. `Showing 1-N of N subscriptions`, no pagination nav, `/page/2/` empty and notice-free.
4. Detail shows all seven rows, `Stripe` as payment-method title, and the link resolving to `/my-account/payment-methods/`.
5. Active subscriptions expose exactly `Change Plan`, `Cancel Subscription`, `Renew Early`, and `Pause Subscription`. `Skip Next Renewal` MUST be absent because `skip_renewal.enabled=false`; `Undo Scheduled Cancellation`, `Reactivate Subscription`, `Retry Payment`, and `Shipping Address` are also absent.
6. SLT2 Lifetime One Time shows `Next Payment: Lifetime Deal - No recurring payment` and offers no cycle action. During this D4 morning audit, SLT2 Fixed Three Cycles is still active before its late-D4 final charge: `_end_date` is absent, no `End Date:` row is fabricated, and its real next-payment date is shown. Its D4-late expiry is verified by `SLT-LIFE-04` and the D5 watch.
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

### Fresh-cycle validation contract

- Re-derive every ID, count, option value, gateway capability, scheduler timestamp, and email baseline on this run; no prior-cycle result is evidence.
- Create and mutate only registered `SLT2 ` / `slt2-*` fixtures. Legacy `SLT` and all non-SLT2 data are read-only controls.
- Automatic-gateway scope is Stripe and Paddle only. Stripe is the primary path; run Paddle parity wherever Paddle supports the behavior. Do not test or configure PayPal or Mollie.
- ArraySubs core must own its Stripe/Paddle integration, renewal, retry, webhook, REST, refund, and customer-payment services with Pro inactive; vendor host classes retain their expected ownership.
- Browser-required assertions use Vercel `agent-browser` with isolated task/role sessions and current snapshot refs. WP-CLI always includes `--allow-root`.
- Update the lifecycle card, the matching `qa/progress/` card, and `qa/issues/` for every new regression. Evidence belongs to this fresh cycle only.
