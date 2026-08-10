---
id: 95
title: Crossgrade SLT Plan Pro to the equal-priced SLT Plan Peer and prove the date does not shift
status: done
priority: high
created: 2026-08-02T03:43:11.018552355+02:00
updated: 2026-08-08T15:58:48.086227636+02:00
started: 2026-08-08T15:58:48.086226554+02:00
completed: 2026-08-08T15:58:48.086226554+02:00
tags:
    - plan-switching
    - day-06
due: "2026-08-08"
estimate: 1h
depends_on:
    - 60
    - 11
    - 12
    - 72
claimed_by: trail-storm
claimed_at: 2026-08-08T15:58:48.086227525+02:00
class: standard
---

> **SLT-SW-03** · group `switching` · scheduled **D06** (2026-08-08)

> Read `README.md` (environment + isolation contract), `calendar.md` (this day's exact
> ordering — it is binding, not advisory) and `plan-audit.md` before starting.

## Objective
Crossgrade `slt-switch`'s **SLT Plan Pro** subscription (`SUB_PRO`, $15.00 day/1) to the equal-priced **SLT Plan Peer** ($15.00 day/1) and prove the money is a wash — classification `crossgrade` via the ±5 % daily-rate tolerance, credit exactly equal to charge, **Amount due $0.00**, no proration order, no store credit — and that the next payment date does not silently move.

## Scope
- Gateway: Stripe test (no charge occurs)
- Checkout: N/A
- Account: existing (`slt-switch`)
- Plugins: free-only

## Preconditions
- SLT-PROD-11 done: Pro's `_arraysubs_crossgrade_products` = [Peer], Peer's = [Pro]; both $15.00 day/1.
- `slt-switch` owns the **active** `SUB_PRO` bought by `SLT-SW-00` — not `SUB_BASIC`, which SLT-SW-01 upgraded.
- No Action Scheduler command may be run in this task. Hook/group drains are forbidden every day; D8 is only the date-meta time-travel day for tasks that explicitly own exact action IDs.

## Test data
| Item | Value |
|---|---|
| Subscription | `SUB_PRO` (slt-switch / SLT Plan Pro) |
| Source → target | Pro $15.00 day/1 → Peer $15.00 day/1 |
| Portal | `/my-account/view-subscription/<SUB_PRO>/`, session `cust-SLT-SW-03` |

Classification and Branch-A maths (`cycle_days=1` both sides, r = 1 − days since `_last_payment_date`):
```
current_daily = new_daily = 15.00 ; tolerance = 0.75/day
=> |new-current| < tolerance -> crossgrade
credit = round(15.00*r,2) = charge = round(15.00*r,2) -> net = 0.00, refund = 0.00
```

## Steps
1. Record from `SUB_PRO`: `_product_id`, `_recurring_amount`, `_last_payment_date`, `_next_payment_date`, `_store_credit`, `_plan_switch_history`. From Tools → Scheduled Actions record BOTH pending legs for `[SUB_PRO]` (`arraysubs_generate_renewal_invoice`, `arraysubs_process_renewal`): action id + scheduled GMT.
2. Compute `offset = crc32('arraysubs-spread-'.<SUB_PRO>)%21600` and confirm the recorded GMTs equal due+offset−6h and due+offset.
3. `M0=$(mailpit-agent latest-id)`.
4. Log in as `slt-switch` / `SltQa!2026#Pass`; open the portal page; **Change Plan**.
5. The modal must expose a second tab **Others** — SLT Plan Peer is a crossgrade and is rendered there with the badge **Change**, not under **Upgrade/Downgrade**. Screenshot both tabs.
6. **Select** SLT Plan Peer; record the preview rows; **Amount due** must read `$0.00` and no "You will receive a credit" line may appear.
7. Confirm → **Change Plan**. Expect the success toast and a reload; no redirect to order-pay.
8. Re-dump the meta; re-read both pending legs for `[SUB_PRO]`; check exact before/after HPOS order counts for `slt-switch` — no order may have been created. Publish the two replacement action IDs/GMTs, exact charge gate, and `charge−300s` deadline to the registry and daily report.
9. Inspect the complete Mailpit delta after M0; require zero message attributable to the crossgrade and classify independently scheduled/background mail by its actual owner. Capture the post-switch portal and browser console/network evidence, then close `cust-SLT-SW-03`; leave the card `in-progress` for the natural-renewal assertion rather than keeping the session open.
10. Inside the next exact `[charge−300s, charge)` interval save `SW03_RENEW_PRE=$(mailpit-agent latest-id)` to the registry. After the natural charge, poll that immutable baseline in repeated calls no longer than 60 seconds through the 10-minute cutoff, resolve the renewal order from numeric `SUB_PRO` plus its scheduled cycle and require the reverse subscription link, and verify paid total `$15.00`, `_renewal_scheduled_date`, action logs `via WP Cron`, and the complete owned Mailpit delta. In fresh `admin-SLT-SW-03-R1` capture the exact order/queue proof, close it, independently review both phases, then move the card through `review` to `done` and require Review to return to zero. Any live defect goes only in `issues/SLT-SW-03-<concise-slug>.md`, never in the lifecycle board, with task/stage/plan path; source/target product, subscription/order/action/message IDs; user ID/login/email/role; exact routes/sessions/gates; reproduction; expected/actual; and portal/meta/queue/order/Mailpit proof.

## Expected results
1. `switch_type` = `crossgrade` (equal daily rates fall inside the ±5 % band), option shown on the **Others** tab.
2. Preview: credit == charge to the cent, **Amount due $0.00**, no switch fee.
3. No proration order anywhere (`net_amount > 0` is false, so `createProrationOrder()` is never called) and no payment page.
4. `_product_id`=Peer, `_recurring_amount`=`15.00`, `_billing_period=day`, `_billing_interval=1`, title `SLT Plan Peer - Subscription #<SUB_PRO>`.
5. `_next_payment_date` is **byte-identical** to step 1 — Branch A returns the current date unchanged.
6. Both renewal legs are re-created: `RenewalScheduler::unschedule()`+`schedule()` runs because `new_next_payment_date` is non-empty, so each action **id changes** while its **scheduled GMT is identical** to step 1 (same due, same crc32 offset). A shifted GMT is a real bug — write a standalone markdown file under `issues/` with both queue screenshots; do not create a lifecycle-board card.
7. `_store_credit` unchanged (`refund_amount` is 0, not negative); `_plan_switch_history` gains one `type=crossgrade` entry; status stays `arraysubs-active`.
8. The next daily renewal still charges **$15.00** at the same time of day as before the crossgrade.

## Emails expected
| # | Email | Trigger point | Recipient | Subject contains | Verify with |
|---|---|---|---|---|---|
| 1 | NONE EXPECTED | whole task | — | — | complete M0 delta contains zero crossgrade-attributable message; unrelated mail is classified |
| 2 | payment_successful + WC admin new-order mail | next natural renewal | customer + admin | exact numeric subscription/order subjects | final-five-minute `SW03_RENEW_PRE`, repeated ≤60-second polls through the 10-minute cutoff, complete owned delta |

## Evidence to capture
- `SLT-SW-03-01-others-tab.png`, `-02-preview-zero.png`, `-03-portal-after.png`, `-04-queue-before.png`, `-05-queue-after.png`, `-06-renewal-15.00.png`; `SUB_PRO` id; before/after meta; both action ids + GMTs; `SW03_RENEW_PRE`; exact renewal order and Mailpit ids.

## Pass criteria
- [ ] Classified crossgrade and offered on the **Others** tab with badge **Change**
- [ ] Credit == charge, Amount due $0.00, no order, no payment page
- [ ] Product/title/recurring amount updated to SLT Plan Peer at $15.00
- [ ] `_next_payment_date` byte-identical; both legs keep the same scheduled GMT (ids may change)
- [ ] `_store_credit` unchanged; one `crossgrade` history entry
- [ ] Complete M0 delta contains zero crossgrade-attributable mail
- [ ] Next relationship-exact natural renewal is $15.00 with cron proof; phase sessions close and Review returns to zero

## Isolation / teardown
- Hands `SUB_PRO` to SLT-SW-02 sitting on **SLT Plan Peer** (Peer's downgrade list = [Basic]). Do not switch it again.
- Nothing global changed; no Action Scheduler command issued. Close only the exact current and R1 sessions named above.

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

[[2026-08-06]] Thu 20:37
Source-block note on 2026-08-06: prerequisite card 72 / SLT-SW-00 was closed UNVERIFIED after the D4 same-day ladder-seeding checkout window was missed. SUB_PRO does not exist as authored, so this crossgrade card cannot start until a later valid ladder-seeding execution creates and publishes the real fixture.

[[2026-08-08]] Sat 15:58
D6 final closeout: UNVERIFIED because the authored SUB_PRO source does not exist. User 349 slt-switch owns zero ArraySubs subscriptions and exact customer-349/product-12611 count is zero; browser /my-account/subscriptions/ says You have no subscriptions yet. Products 12611 Pro and 12620 Peer remain published and cross-linked, but no product or late ladder mutation was made. Session closed. Evidence: /home/server-manager/slt-evidence/SLT-SW-03-D06-source-block.txt and /home/server-manager/slt-evidence/SLT-SW-03-no-source.png. This is a source-fixture miss from SLT-SW-00, not a product defect; no issue filed.
