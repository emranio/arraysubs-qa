---
id: 72
title: 'SLT-SW-00 Seed the plan ladder: slt2-switch buys SLT2 Plan Basic and SLT2 Plan Pro'
status: todo
priority: critical
created: 2026-08-22T19:10:00+02:00
updated: 2026-08-22T19:10:00+02:00
tags:
    - cycle-2
    - granular
    - plan-switching
    - day-04
due: "2026-08-27"
estimate: 45m
depends_on:
    - 60
    - 12
claimed_by: wild-timber
class: standard
---

> **SLT-SW-00** · group `switching` · scheduled **D04** (2026-08-27)

> Read `README.md` (environment + isolation contract), `calendar.md` (this day's exact
> ordering — it is binding, not advisory) and `plan-audit.md` before starting.
## Objective
Every plan-switching task (`SLT-SW-01`..`SLT-SW-10`) assumes `slt2-switch` already owns a live ladder subscription. Nothing in the plan created one — the audit caught this as a dependency inversion. This task seeds it, and only it.

## Scope
- Gateway: Stripe test
- Checkout: block
- Account: existing (`slt2-switch`)
- Plugins: both

## Preconditions
- `SLT-PROD-11` complete: the four-product ladder (Basic / Pro / Enterprise / Peer) exists and is wired for switching.
- `SLT-SETUP-03` complete: `slt2-switch` exists with a billing address.
- Runs on D4 **after 12:00 site time**, before `SLT-SW-09`.
- `slt2-switch` owns no ladder subscription yet. **Verify this first.** With `one_per_customer=false`, `auto_migrate_on_checkout=true` is inert, so a rebuy would create an extra ladder subscription and invalidate the exact-two fixture.

## Test data
| Item | Value |
|---|---|
| Products | SLT2 Plan Basic (day/1, $5.00), SLT2 Plan Pro (day/1, $15.00) |
| Account | slt2-switch / `SltQa!2026#Pass` |
| Card | 4242 4242 4242 4242, 12/34, CVC 123 |
| Sessions | `cust-SLT-SW-00`, `admin-SLT-SW-00` |

## Steps
1. Record `SUBCOUNT_BEFORE=<exact current SLT2 subscription count>`.
2. Confirm `slt2-switch` owns no SLT2 ladder subscription: `wp post list --post_type=arraysubs_data --allow-root` cross-checked against `_customer_id`.
3. `agent-browser --session cust-SLT-SW-00 open ".../my-account/"` → log in as `slt2-switch`.
4. Assert browser/persistent carts EMPTY, capture `SLT-SW-00-01-cart-empty-before.png`, and set `PRE_BASIC=$(mailpit-agent latest-id)`. Add Basic only; accept the one-click block-checkout redirect, capture the $5.00 summary before card entry as `SLT-SW-00-02-basic-checkout.png`, fill the hosted card without capturing it, pay, record numeric `ORDER_BASIC`, and capture `SLT-SW-00-03-basic-receipt.png`.
5. Resolve `SUB_BASIC` only from `ORDER_BASIC._subscription_ids` JSON with a strict one-element numeric guard; require reverse parent/customer/product and `SUBCOUNT_AFTER_BASIC == SUBCOUNT_BEFORE+1`. Reconcile the complete `PRE_BASIC` delta and require WC customer paid-order, WC admin New order, ArraySubs customer signup, and ArraySubs admin signup IDs.
6. Prove both carts empty again, set `PRE_PRO=$(mailpit-agent latest-id)`, and repeat for Pro as a second order: capture `SLT-SW-00-04-pro-checkout.png` before card entry and `-05-pro-receipt.png` after payment. Resolve numeric `SUB_PRO` by the same bidirectional path, require it distinct and `SUBCOUNT_AFTER_PRO == SUBCOUNT_BEFORE+2`, and require the second four-message delta. Capture the final empty state as `SLT-SW-00-06-cart-empty-after.png`.
7. For each subscription record status/date/amount/k and exact invoice/charge action IDs/times. In `admin-SLT-SW-00`, capture both exact details as `SLT-SW-00-07-two-subscriptions.png`; publish both first `charge−5m` deadlines to the registry and D04 report.
8. Publish `SUB_BASIC` and `SUB_PRO` to the `slt2-catalog-registry` page. Every `SLT-SW-*` task reads them from there.
9. Reconcile both owner-filtered purchase deltas, save/show all eight exact IDs, and classify background mail. If exact-two/linkage/runtime isolation fails, create a dedicated issue with task/plan, orders/subscriptions/products/user ID and login/role, exact contexts, reproduction, expected/actual, UI/meta/mail proof, and the first purchase counterexample; create or update the mandatory `qa/issues/` kanban card. Close only `cust-SLT-SW-00` and `admin-SLT-SW-00`, independently review the evidence, move the card through `review` to `done`, and ensure Review returns to zero.

## Expected results
1. Two new `arraysubs_data` posts, both `arraysubs-active`, owned by `slt2-switch`.
2. `SUB_BASIC` recurring $5.00, `SUB_PRO` recurring $15.00, both day/1.
3. Two separate parent orders, each `completed` (paid virtual-only products), correctly linked both ways.
4. Neither purchase migrated the other — the count increased by exactly 2.
5. Both subscriptions have invoice and charge legs queued at `due+k−6h` and `due+k`.

## Emails expected
| # | Email | Trigger point | Recipient | Subject contains | Verify with |
|---|---|---|---|---|---|
| 1 | WC New order ×2 | each paid order | admin | `New order #` | Complete owner-filtered deltas after the two purchase baselines; save/show both exact ids |
| 2 | WC Completed order ×2 | each virtual-only order → completed | slt2-switch@example.test | `is on its way` | Complete owner-filtered deltas after the two purchase baselines; save/show both exact ids |
| 3 | `new_subscription` ×2 | each → active | slt2-switch@example.test | `is active` | separate `PRE_BASIC` / `PRE_PRO` waits and complete deltas |
| 4 | `admin_new_subscription` ×2 | same | admin | `New subscription #` | Complete owner-filtered deltas after the two purchase baselines; save/show both exact ids |
| 5 | NONE EXPECTED | — | — | — | no renewal, invoice, trial or reminder mail; day/1 cycle is shorter than the 3-day reminder lead |

## Evidence to capture
- Safe named empty-cart, checkout, receipt, and two-subscription captures from steps 4-7.
- Count/bidirectional linkage, `SUB_BASIC/SUB_PRO`, orders, `PRE_BASIC/PRE_PRO`, eight mail IDs, both k/action/deadline sets, session/review proof.
- Registry page updated.

## Pass criteria
- [ ] Exactly two new active subscriptions for `slt2-switch`, $5.00 and $15.00
- [ ] Neither purchase migrated the other
- [ ] Both orders linked two-way; both AS leg pairs queued
- [ ] Mail set matches rows 1-4; row 5 negatives hold
- [ ] Registry updated with both subscription IDs
- [ ] Exact sessions closed and purchase evidence reviewed to done

## Isolation / teardown
- Hands the whole `SLT-SW-*` group its ladder. **Do not cancel** — `SLT-SW-01` upgrades `SUB_BASIC`, `SLT-SW-03` crossgrades `SUB_PRO`. Belongs to the D11 cancellation cohort. Nothing global changed; cart and persistent-cart meta left empty; exact task session closed.

---

### Fresh-cycle validation contract

- Re-derive every ID, count, option value, gateway capability, scheduler timestamp, and email baseline on this run; no prior-cycle result is evidence.
- Create and mutate only registered `SLT2 ` / `slt2-*` fixtures. Legacy `SLT` and all non-SLT2 data are read-only controls.
- Automatic-gateway scope is Stripe and Paddle only. Stripe is the primary path; run Paddle parity wherever Paddle supports the behavior. Do not test or configure PayPal or Mollie.
- ArraySubs core must own its Stripe/Paddle integration, renewal, retry, webhook, REST, refund, and customer-payment services with Pro inactive; vendor host classes retain their expected ownership.
- Browser-required assertions use Vercel `agent-browser` with isolated task/role sessions and current snapshot refs. WP-CLI always includes `--allow-root`.
- Update the lifecycle card, the matching `qa/progress/` card, and `qa/issues/` for every new regression. Evidence belongs to this fresh cycle only.
