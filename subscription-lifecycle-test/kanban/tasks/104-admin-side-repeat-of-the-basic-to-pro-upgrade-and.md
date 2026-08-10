---
id: 104
title: Admin-side repeat of the Basic to Pro upgrade and record-for-record diff against SLT-SW-01
status: done
priority: medium
created: 2026-08-02T03:43:11.662273007+02:00
updated: 2026-08-09T08:01:17.120304361+02:00
started: 2026-08-09T08:01:17.120303129+02:00
completed: 2026-08-09T08:01:17.120303129+02:00
tags:
    - plan-switching
    - day-07
due: "2026-08-09"
estimate: 1h 30m
depends_on:
    - 86
    - 60
    - 12
claimed_by: delta-gate
claimed_at: 2026-08-09T08:01:17.120304261+02:00
class: standard
---

> **SLT-SW-04** · group `switching` · scheduled **D07** (2026-08-09)

> Read `README.md` (environment + isolation contract), `calendar.md` (this day's exact
> ordering — it is binding, not advisory) and `plan-audit.md` before starting.

## Objective
Repeat the SLT-SW-01 upgrade (Basic $5.00 → Pro $15.00, day/1) on a fresh subscription driven from **wp-admin**, and compare the records field by field with SLT-SW-01. First the negative: the admin screens expose **no plan-switch control** (edit = invoice email, addresses, status only, Product read-only; detail offers only *Cancel Pending Switch*). The admin's lever is the proration order, which `attemptAutoPayment()` leaves "awaiting manual payment from customer or admin".

## Scope
- Gateway: Stripe test (purchase only)
- Checkout: block (page 8) for the purchase, wp-admin for the switch
- Account: admin-created (`slt-switch4`, created by this task)
- Plugins: free-only

## Preconditions
- SLT-SW-01 complete; its record set is the comparison baseline.
- This task CREATES **`slt-switch4` / slt-switch4@example.test**, customer, password `SltQa!2026#Pass`, billing address as SLT-SETUP-03 step 4; it matches `slt-*`, so SLT-SETUP-99B removes it. `slt-switch2` and its live Pro subscription belong exclusively to `SLT-SW-06`/`SLT-SW-08` and must not be opened or reused here. A separate account keeps the record-for-record comparison independent; at `one_per_customer=false`, a repeat checkout would create a duplicate rather than migrate.

## Test data
| Item | Value |
|---|---|
| Account | slt-switch4 (new) |
| Switch | S-BASIC2: Basic $5.00 → Pro $15.00, day/1 |
| Card | `4242 4242 4242 4242` |
| Admin | `admin.php?page=arraysubs-mainadmin#/subscriptions/edit/<id>` and `admin.php?page=arraysubs-mainadmin#/subscriptions/detail/<id>` |
| Sessions | `admin-SLT-SW-04`, `customer-SLT-SW-04` |

## Steps
1. Set `USER_PRE=$(mailpit-agent latest-id)`. In `admin-SLT-SW-04`, create `slt-switch4` (Send User Notification UNTICKED) and fill its billing address. Record its user ID; require `wp user meta get <UID> _woocommerce_persistent_cart_1 --allow-root` to be empty/absent. Record exactly one admin-only `New User Registration` message after `USER_PRE` and no customer account/password mail.
2. In `customer-SLT-SW-04`, log in as fresh `slt-switch4`, open `/cart/`, and require both browser and serialized persistent carts empty. If either is contaminated, preserve proof, clear only this fresh task user's cart through the UI, re-prove both empty, and continue; file the standalone finding without stranding the card. Record exact order/subscription counts and set `M0=$(mailpit-agent latest-id)` immediately before adding **SLT Plan Basic**. Capture the unpopulated $5.00 checkout, fill 4242 without capturing populated hosted fields, and capture only the safe receipt. Resolve numeric parent order and sole numeric S-BASIC2 through strict receipt post-meta JSON; require reverse parent/customer/product linkage and exact `+1` counts. Poll immutable M0 in repeated ≤60-second calls through the two-minute cutoff and classify the complete four-message WC/ArraySubs checkout set, then record `_last_payment_date`.
3. In `admin-SLT-SW-04`, open `#/subscriptions/edit/<S-BASIC2>`; screenshot the form and confirm there is **no** product/plan field — only Contact, Billing, Shipping, status. Same on `#/subscriptions/detail/<S-BASIC2>`.
4. In the customer session, record the exact pre-switch order set, then portal → **Change Plan** → **Select** SLT Plan Pro → Confirm → **Change Plan**; record T1 and preview values. Land on the order-pay page and **do not pay**. Record numeric PRO-ORDER2 from the exact switch response, require exactly one new pending plan-switch order with customer/subscription/target linkage and verified proration math, then set `M1=$(mailpit-agent latest-id)` immediately before the admin completion.
5. In `admin-SLT-SW-04`, open `page=wc-orders&action=edit&id=<PRO-ORDER2>`; screenshot the fee line and `_arraysubs_*` custom fields; set **Order status** = `Completed` → **Update**.
6. Re-dump numeric S-BASIC2 and PRO-ORDER2 meta; read the notes; poll immutable M1 in repeated calls no longer than 60 seconds through the two-minute cutoff, save/show the exact PRO-ORDER2 completed-order message, and classify the complete delta plus unrelated shared-site mail.
7. Build the comparison table vs SLT-SW-01: `_product_id`, `_recurring_amount`, billing period/interval, title, `_next_payment_date` before/after, `_plan_switch_history`, `_store_credit`, order `_arraysubs_*` metas. Dump the exact replacement invoice/charge action IDs/GMTs and verify the unchanged date math. Prove both carts empty, close both task sessions, independently review the parent/switch/admin-completion/comparison evidence, then move through `review` to `done` with Review empty. Any live defect goes only in `issues/SLT-SW-04-<concise-slug>.md` with task/stage/plan path; user/product/subscription/parent/switch-order/action/message IDs; user login/email/role; exact routes/sessions; reproduction; expected/actual; and UI/REST/meta/order/queue/Mailpit proof.

## Expected results
1. Neither admin screen has a plan/product control. The only admin route is completing the proration order — or the UI-less REST `subscriptions/<id>/update`, which via `trackProductChange()` fires `arraysubs_plan_switch_completed` **without** proration, order, reschedule or title rewrite. Record that asymmetry, do not exercise it.
2. Marking PRO-ORDER2 **Completed** fires `Hooks::onProrationOrderPaid()` (hooked to `woocommerce_order_status_completed`), so the switch applies with no gateway charge.
3. Records are structurally **identical** to SLT-SW-01: `_product_id`=Pro, `_recurring_amount`=`15.00`, day/1 kept, title `SLT Plan Pro - Subscription #<S-BASIC2>`, `_arraysubs_switch_processed=yes`, note "Subscription updated after proration payment.", one `type=upgrade` history entry, `_next_payment_date`/`_store_credit` unchanged.
4. Amounts differ legitimately: r comes from this subscription's own `_last_payment_date`, so credit/charge/net differ while satisfying `net = round(15r,2) − round(5r,2)`.
5. Acceptable differences vs SLT-SW-01: no Stripe transaction id, status `completed` not `processing`, and the "completed" order mail instead of "processing". Anything else is a finding.

## Emails expected
| # | Email | Trigger point | Recipient | Subject contains | Verify with |
|---|---|---|---|---|---|
| 1 | WP New User Registration | step 1 admin user creation | admin | `New User Registration` | exactly one after `USER_PRE`; no customer account/password mail |
| 2 | Purchase mail for the first order | step 2 | slt-switch4@example.test | `Order #` | record ids |
| 3 | NONE from the switch | steps 4-6 | — | — | no switch listener; no lifecycle mail |
| 4 | WooCommerce "completed" mail for PRO-ORDER2 | step 5 | slt-switch4@example.test | `Order #<PRO-ORDER2>` | exact match in the complete M1 delta |

## Evidence to capture
- `SLT-SW-04-01-no-plan-field.png`, `-02-detail.png`, `-03-order-metas.png`, `-04-completed.png`; the comparison table; all ids and Mailpit ids.

## Pass criteria
- [ ] Admin screens proven to have no plan-switch control (screenshots)
- [ ] Completing the proration order applies the switch with no gateway charge
- [ ] Records match SLT-SW-01 field for field (amount differences explained by r)
- [ ] `_next_payment_date` unchanged; one `upgrade` history entry; only the listed order mails
- [ ] Exact safe parent/switch relationships, sessions, standalone findings, and independent review close with Review empty

## Isolation / teardown
- Empty the `slt-switch4` cart, require its persistent-cart meta to be empty/absent, and close only `admin-SLT-SW-04` and `customer-SLT-SW-04`. Adds `slt-switch4` and one subscription, both removed by SLT-SETUP-99B; register the account in the registry so no other task reuses it. Nothing global changed. `slt-switch2` remains untouched.

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

[[2026-08-06]] Thu 20:38
Source-block note on 2026-08-06: this admin-side comparison task requires card 86 / SLT-SW-01 to have completed first. Card 86 is now source-blocked because card 72 / SLT-SW-00 never created the ladder fixtures, so this card cannot start until a later valid execution creates SUB_BASIC and completes the baseline customer-side switch.

[[2026-08-09]] Sun 08:01
D07 post-noon final source recheck at 2026-08-09 12:00:26 UTC+6 — UNVERIFIED. /home/server-manager/slt-evidence/SLT-SW-04-D07-source-recheck.txt proves zero exact numeric registry match for SUB_BASIC and zero ArraySubs subscriptions for required owner 349; therefore SLT-SW-01 has no customer-side Basic-to-Pro record set for the mandatory comparison baseline. Dependency chain: SLT-SW-00 fixture absent -> SLT-SW-01 baseline absent -> SLT-SW-04 comparison impossible. No slt-switch4 user, checkout, subscription, switch order, session, mail baseline, action, or setting was created or changed. Future impact: rerun from step 1 only after SUB_BASIC and a completed SLT-SW-01 baseline exist.

[[2026-08-09]] Sun 12:17
Independent-review correction: discard the original zero-subscription SQL because it filtered `arraysubs_subscription`. The corrected query uses live post type `arraysubs_data` with `_customer_id=349` and still returns zero rows; the missing numeric registry handoff and source-blocked card 86 independently agree. The UNVERIFIED closure is unchanged. Full correction: `/home/server-manager/slt-evidence/SLT-SW-04-D07-source-recheck.txt`.
