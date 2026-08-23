---
id: 104
title: Admin-side repeat of the Basic to Pro upgrade and record-for-record diff against SLT-SW-01
status: todo
priority: medium
created: 2026-08-22T19:10:00+02:00
updated: 2026-08-22T19:10:00+02:00
tags:
    - cycle-2
    - granular
    - plan-switching
    - day-07
due: "2026-08-30"
estimate: 1h 30m
depends_on:
    - 86
    - 60
    - 12
claimed_by: delta-gate
class: standard
---

> **SLT-SW-04** · group `switching` · scheduled **D07** (2026-08-30)

> Read `README.md` (environment + isolation contract), `calendar.md` (this day's exact
> ordering — it is binding, not advisory) and `plan-audit.md` before starting.

## Objective
Repeat the SLT-SW-01 upgrade (Basic $5.00 → Pro $15.00, day/1) on a fresh subscription driven from **wp-admin**, and compare the records field by field with SLT-SW-01. First the negative: the admin screens expose **no plan-switch control** (edit = invoice email, addresses, status only, Product read-only; detail offers only *Cancel Pending Switch*). The admin's lever is the proration order, which `attemptAutoPayment()` leaves "awaiting manual payment from customer or admin".

## Scope
- Gateway: Stripe test (purchase only)
- Checkout: block (page 8) for the purchase, wp-admin for the switch
- Account: admin-created (`slt2-switch4`, created by this task)
- Plugins: free-only

## Preconditions
- SLT-SW-01 complete; its record set is the comparison baseline.
- This task CREATES **`slt2-switch4` / slt2-switch4@example.test**, customer, password `SltQa!2026#Pass`, billing address as SLT-SETUP-03 step 4; it matches `slt2-*`, so SLT-SETUP-99B removes it. `slt2-switch2` and its live Pro subscription belong exclusively to `SLT-SW-06`/`SLT-SW-08` and must not be opened or reused here. A separate account keeps the record-for-record comparison independent; at `one_per_customer=false`, a repeat checkout would create a duplicate rather than migrate.

## Test data
| Item | Value |
|---|---|
| Account | slt2-switch4 (new) |
| Switch | S-BASIC2: Basic $5.00 → Pro $15.00, day/1 |
| Card | `4242 4242 4242 4242` |
| Admin | `admin.php?page=arraysubs-mainadmin#/subscriptions/edit/<id>` and `admin.php?page=arraysubs-mainadmin#/subscriptions/detail/<id>` |
| Sessions | `admin-SLT-SW-04`, `customer-SLT-SW-04` |

## Steps
1. Set `USER_PRE=$(mailpit-agent latest-id)`. In `admin-SLT-SW-04`, create `slt2-switch4` (Send User Notification UNTICKED) and fill its billing address. Record its user ID; require `wp user meta get <UID> _woocommerce_persistent_cart_1 --allow-root` to be empty/absent. Record exactly one admin-only `New User Registration` message after `USER_PRE` and no customer account/password mail.
2. In `customer-SLT-SW-04`, log in as fresh `slt2-switch4`, open `/cart/`, and require both browser and serialized persistent carts empty. If either is contaminated, preserve proof, clear only this fresh task user's cart through the UI, re-prove both empty, and continue; file the QA issue card without stranding the card. Record exact order/subscription counts and set `M0=$(mailpit-agent latest-id)` immediately before adding **SLT2 Plan Basic**. Capture the unpopulated $5.00 checkout, fill 4242 without capturing populated hosted fields, and capture only the safe receipt. Resolve numeric parent order and sole numeric S-BASIC2 through strict receipt post-meta JSON; require reverse parent/customer/product linkage and exact `+1` counts. Poll immutable M0 in repeated ≤60-second calls through the two-minute cutoff and classify the complete four-message WC/ArraySubs checkout set, then record `_last_payment_date`.
3. In `admin-SLT-SW-04`, open `#/subscriptions/edit/<S-BASIC2>`; screenshot the form and confirm there is **no** product/plan field — only Contact, Billing, Shipping, status. Same on `#/subscriptions/detail/<S-BASIC2>`.
4. In the customer session, record the exact pre-switch order set, then portal → **Change Plan** → **Select** SLT2 Plan Pro → Confirm → **Change Plan**; record T1 and preview values. Land on the order-pay page and **do not pay**. Record numeric PRO-ORDER2 from the exact switch response, require exactly one new pending plan-switch order with customer/subscription/target linkage and verified proration math, then set `M1=$(mailpit-agent latest-id)` immediately before the admin completion.
5. In `admin-SLT-SW-04`, open `page=wc-orders&action=edit&id=<PRO-ORDER2>`; screenshot the fee line and `_arraysubs_*` custom fields; set **Order status** = `Completed` → **Update**.
6. Re-dump numeric S-BASIC2 and PRO-ORDER2 meta; read the notes; poll immutable M1 in repeated calls no longer than 60 seconds through the two-minute cutoff, save/show the exact PRO-ORDER2 completed-order message, and classify the complete delta plus unrelated shared-site mail.
7. Build the comparison table vs SLT-SW-01: `_product_id`, `_recurring_amount`, billing period/interval, title, `_next_payment_date` before/after, `_plan_switch_history`, `_store_credit`, order `_arraysubs_*` metas. Dump the exact replacement invoice/charge action IDs/GMTs and verify the unchanged date math. Prove both carts empty, close both task sessions, independently review the parent/switch/admin-completion/comparison evidence, then move through `review` to `done` with Review empty. Any live defect goes only in `qa/issues/` kanban card named `SLT-SW-04-<concise-slug>` with task/stage/plan path; user/product/subscription/parent/switch-order/action/message IDs; user login/email/role; exact routes/sessions; reproduction; expected/actual; and UI/REST/meta/order/queue/Mailpit proof.

## Expected results
1. Neither admin screen has a plan/product control. The only admin route is completing the proration order — or the UI-less REST `subscriptions/<id>/update`, which via `trackProductChange()` fires `arraysubs_plan_switch_completed` **without** proration, order, reschedule or title rewrite. Record that asymmetry, do not exercise it.
2. Marking PRO-ORDER2 **Completed** fires `Hooks::onProrationOrderPaid()` (hooked to `woocommerce_order_status_completed`), so the switch applies with no gateway charge.
3. Records are structurally **identical** to SLT-SW-01: `_product_id`=Pro, `_recurring_amount`=`15.00`, day/1 kept, title `SLT2 Plan Pro - Subscription #<S-BASIC2>`, `_arraysubs_switch_processed=yes`, note "Subscription updated after proration payment.", one `type=upgrade` history entry, `_next_payment_date`/`_store_credit` unchanged.
4. Amounts differ legitimately: r comes from this subscription's own `_last_payment_date`, so credit/charge/net differ while satisfying `net = round(15r,2) − round(5r,2)`.
5. Acceptable differences vs SLT-SW-01: no Stripe transaction id, status `completed` not `processing`, and the "completed" order mail instead of "processing". Anything else is a finding.

## Emails expected
| # | Email | Trigger point | Recipient | Subject contains | Verify with |
|---|---|---|---|---|---|
| 1 | WP New User Registration | step 1 admin user creation | admin | `New User Registration` | exactly one after `USER_PRE`; no customer account/password mail |
| 2 | Purchase mail for the first order | step 2 | slt2-switch4@example.test | `Order #` | record ids |
| 3 | NONE from the switch | steps 4-6 | — | — | no switch listener; no lifecycle mail |
| 4 | WooCommerce "completed" mail for PRO-ORDER2 | step 5 | slt2-switch4@example.test | `Order #<PRO-ORDER2>` | exact match in the complete M1 delta |

## Evidence to capture
- `SLT-SW-04-01-no-plan-field.png`, `-02-detail.png`, `-03-order-metas.png`, `-04-completed.png`; the comparison table; all ids and Mailpit ids.

## Pass criteria
- [ ] Admin screens proven to have no plan-switch control (screenshots)
- [ ] Completing the proration order applies the switch with no gateway charge
- [ ] Records match SLT-SW-01 field for field (amount differences explained by r)
- [ ] `_next_payment_date` unchanged; one `upgrade` history entry; only the listed order mails
- [ ] Exact safe parent/switch relationships, sessions, QA issue cards, and independent review close with Review empty

## Isolation / teardown
- Empty the `slt2-switch4` cart, require its persistent-cart meta to be empty/absent, and close only `admin-SLT-SW-04` and `customer-SLT-SW-04`. Adds `slt2-switch4` and one subscription, both removed by SLT-SETUP-99B; register the account in the registry so no other task reuses it. Nothing global changed. `slt2-switch2` remains untouched.

---

### Fresh-cycle validation contract

- Re-derive every ID, count, option value, gateway capability, scheduler timestamp, and email baseline on this run; no prior-cycle result is evidence.
- Create and mutate only registered `SLT2 ` / `slt2-*` fixtures. Legacy `SLT` and all non-SLT2 data are read-only controls.
- Automatic-gateway scope is Stripe and Paddle only. Stripe is the primary path; run Paddle parity wherever Paddle supports the behavior. Do not test or configure PayPal or Mollie.
- ArraySubs core must own its Stripe/Paddle integration, renewal, retry, webhook, REST, refund, and customer-payment services with Pro inactive; vendor host classes retain their expected ownership.
- Browser-required assertions use Vercel `agent-browser` with isolated task/role sessions and current snapshot refs. WP-CLI always includes `--allow-root`.
- Update the lifecycle card, the matching `qa/progress/` card, and `qa/issues/` for every new regression. Evidence belongs to this fresh cycle only.
