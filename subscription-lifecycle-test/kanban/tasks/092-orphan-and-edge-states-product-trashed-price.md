---
id: 92
title: 'Orphan and edge states: product trashed, price edited, customer deleted mid-cycle (exploratory)'
status: todo
priority: medium
created: 2026-08-22T19:10:00+02:00
updated: 2026-08-22T19:10:00+02:00
tags:
    - cycle-2
    - granular
    - edge-cases
    - day-06
due: "2026-08-29"
estimate: 3h on D6 + 45m follow-up on D7
depends_on:
    - 10
    - 11
    - 12
claimed_by: trail-storm
class: standard
---

> **SLT-IMP-04** · group `implied` · scheduled **D06** (2026-08-29)

> Read `README.md` (environment + isolation contract), `calendar.md` (this day's exact
> ordering — it is binding, not advisory) and `plan-audit.md` before starting.
## Objective
Exploratory. Document what happens to a LIVE subscription when its product is trashed mid-cycle, its price edited mid-cycle, or its customer deleted with a renewal scheduled. Neither plugin has a contract for these, so the deliverable is observed behaviour with proof, not pass/fail.

## Scope
- Gateway: Stripe test
- Checkout: block
- Account: new registered x3 (created by this task)
- Plugins: both

## Preconditions
- SLT-SETUP-01/02/03 done. Every artifact mutated here is created here, so no other task's evidence is at risk.
- CREATES three products (Simple, Virtual, **Subscription [ArraySubs]**, day/1, no trial or fee, description `SLT2 window product. Orphan probe. Delete 2026-09-05.`) — `SLT2 Orphan Trash` $6.00, `SLT2 Orphan Price` $8.00, `SLT2 Orphan User` $7.00 — plus accounts `slt2-orph1/2/3@example.test`, pw `SltQa!2026#Pass`.
- Never touch another task's artifacts. Sessions `customer-orph1-SLT-IMP-04`, `customer-orph2-SLT-IMP-04`, and `customer-orph3-SLT-IMP-04` (C09). No `wp action-scheduler run` (C07).

## Test data
| Item | Value |
|---|---|
| `SUB_ORPH_TRASH` | SLT2 Orphan Trash $6.00 / slt2-orph1 — trashed |
| `SUB_ORPH_PRICE` | SLT2 Orphan Price $8.00 / slt2-orph2 — price -> $19.00 |
| `SUB_ORPH_USER` | SLT2 Orphan User $7.00 / slt2-orph3 — user deleted |
| Card / timing | 4242 4242 4242 4242; buy after 12:00 site, 2026-08-29 |
| Renewal due | 2026-08-30 (D7) = purchase + 24 h + `k` |

## Steps
1. `USER_PRE=$(mailpit-agent latest-id)`. In `admin-SLT-IMP-04`, create the three users with **Send User Notification** unticked and billing per SLT-SETUP-03; classify exactly three admin-addressed `New User Registration` messages after `USER_PRE`, one per user, and prove there is no customer account/password mail. Create the three products and immediately append only their exact IDs to Shop Access rule `<D0_SHOP_ACCESS_RULE_ID>` under `exclusion_product_ids` through **Member Access → Shop Access**, preserving every field and prior exclusion. Re-read the raw option and require each ID exactly once before storefront access.
2. Buy one product per account with the success card, using `customer-orph1-SLT-IMP-04`, `customer-orph2-SLT-IMP-04`, and `customer-orph3-SLT-IMP-04` respectively. Immediately before each independent checkout save a distinct baseline (`BUY_PRE_TRASH`, `BUY_PRE_PRICE`, or `BUY_PRE_USER`) and exact total subscription/order counts. Before each purchase require that session's browser cart and serialized persistent-cart meta empty and capture a task/product-keyed empty-cart image. Handle the frozen one-click redirect explicitly, capture each unpopulated checkout total before card entry, fill the hosted card without capturing populated card fields, and capture only the safe order-received page afterward. Resolve the sole numeric subscription through that receipt order's `_subscription_ids` post-meta JSON plus a strict one-element guard; require its reverse parent/customer/product linkage and cumulative subscription/order counts of exactly `+1`, `+2`, then `+3`, never recency. Poll each immutable `BUY_PRE_*` in repeated calls no longer than 60 seconds through the two-minute cutoff for the exact active-subscription subject, then classify the complete four-message WC/ArraySubs checkout delta before starting the next checkout. After each purchase reopen the cart and prove both browser and persistent carts empty. Publish the three aliases, customer/product/parent-order/subscription ids, each `_next_payment_date`, `_recurring_amount` and `k = crc32('arraysubs-spread-'.$id)%21600` with its exact invoice/charge action IDs and gates.
3. TRASH: `/wp-admin/edit.php?post_type=product`, hover `SLT2 Orphan Trash` -> **Trash**. Screenshot the list, `SUB_ORPH_TRASH`'s admin panel, `/my-account/view-subscription/<SUB_ORPH_TRASH>/`.
4. PRICE EDIT: open `SLT2 Orphan Price`, **Regular price ($)** `8.00` -> `19.00`, **Update**. Screenshot; re-read `SUB_ORPH_PRICE`'s `_recurring_amount`, admin total, portal.
5. USER DELETE: `users.php` -> `slt2-orph3` -> **Delete**; screenshot the options, choose **Attribute all content to** `admin`, submit.
6. After each mutation dump `wp post meta list <SUB_ID> --allow-root` and `wp post list --post_type=arraysubs_data --include=<SUB_ID> --fields=ID,post_status,post_author --allow-root`, then screenshot the exact subscription-filtered Pending queue in `admin-SLT-IMP-04` to prove the legs survived. Publish each numeric action ID/gate and its `charge−300s` deadline. Close the D6 admin and all three customer sessions after the mutations and handoff are complete. At the final scheduled watch phase inside each subscription's exact `[charge−300s, charge)` interval, save a distinct `REN_PRE_TRASH`, `REN_PRE_PRICE`, or `REN_PRE_USER` to the registry and task evidence; never reuse one subscription's baseline as another's or take it earlier than the final five minutes.
7. FOLLOW-UP 2026-08-30 (D7): use fresh sessions `admin-SLT-IMP-04-R1`, `customer-orph1-SLT-IMP-04-R1`, and `customer-orph2-SLT-IMP-04-R1` (the deleted third user has no customer session). For each subscription poll its immutable `REN_PRE_*` in repeated calls no longer than 60 seconds through the 10-minute cutoff, inspect every newer message, and correlate by exact subscription/order id, recording an empty delta when none arrives; do not use a global recent-message list as proof. Resolve any renewal order from the exact subscription/cycle relationship and require its reverse subscription link, never recency. Record whether the action fired, order id and total, status, order notes verbatim, any `status=failed` row and message, and each correlated message with its `To:`. Read only the task-correlated `debug.log` slice; screenshot `#/audits/renewal-failures`. Confirm the two surviving users' browser and persistent carts remain empty, close the three R1 sessions, independently review all three branches, then move the card through `review` to `done` and require Review to return to zero.

## Expected results
Exploratory — record the ACTUAL answer with proof; do not assert one.
1. `SUB_ORPH_TRASH`: does `arraysubs_process_renewal` still run? Is a renewal order created, with what line item and total once `wc_get_product()` returns a trashed product? Does the subscription go on-hold, fail or stay `arraysubs-active`? Does the portal render or fatal?
2. `SUB_ORPH_PRICE`: does the D7 renewal charge `$8.00` (stored on the subscription) or `$19.00` (live price)? Record `_recurring_amount` before/after and the order total. Whichever wins, portal display and charged amount MUST agree — a mismatch is a defect.
3. `SUB_ORPH_USER`: does the subscription survive with `post_author` reattributed? Is the order's `customer_id` reset to 0? Does the renewal fire, and where does `payment_successful` go — dead address, admin, nowhere? Any fatal or "customer not found"?
4. All three: whether each Scheduled Actions row ends `complete` or `failed`, the message if failed, and whether Renewal Failures captured anything.

## Emails expected
| # | Email | Trigger point | Recipient | Subject contains | Verify with |
|---|---|---|---|---|---|
| 1-3 | paid-checkout set x3 | each checkout | slt2-orph1/2/3 + admin | exact active subscription, admin new subscription, WC new order, WC completed order subjects | distinct immutable `BUY_PRE_*`; repeated ≤60-second polls through the two-minute cutoff plus complete per-checkout delta |
| ? | UNKNOWN — record what arrives | D7 renewals | actual `To:` | actual subject | inspect every message newer than that subscription's distinct `REN_PRE_*`; correlate by exact subscription/order id |
| setup | WP New User Registration ×3 | before the first `BUY_PRE_*` | admin | `New User Registration` | exactly three after `USER_PRE`; zero customer account/password mail |

The mutations must send NOTHING. Capture a boundary immediately before step 3 and inspect its complete delta after step 5; require zero message attributable to the three mutations, while classifying independently scheduled/background mail under its actual owner.

## Evidence to capture
- `SLT-IMP-04-01-trashed.png`, `-02-price.png`, `-03-user-delete.png`, `-04-pending.png`, `-05..07-portal.png`, `-08-failures.png`; meta dumps before/after each mutation, raw Shop Access proof, per-session cart/persistent-cart proof, `USER_PRE`, three setup-mail IDs, all three `BUY_PRE_*` and `REN_PRE_*` values, order notes, correlated Mailpit ids with `To:`, the D7 log slice.

## Pass criteria
- [ ] all three behaviours documented with before/after dumps
- [ ] each leg's D7 outcome recorded, plus any Renewal Failures row
- [ ] the `SUB_ORPH_PRICE` charged amount recorded and compared to the portal display
- [ ] the `SUB_ORPH_USER` email recipient recorded verbatim
- [ ] Any PHP fatal, 500 or unhandled notice has its own mandatory `qa/issues/` kanban card and this lifecycle task remains blocked
- [ ] no non-SLT2 artifact touched
- [ ] All three products excluded exactly once from Shop Access before storefront access; setup mail isolated; carts/persistent-cart metas empty and exact sessions closed
- [ ] Every renewal is relationship-exact, every dated phase closes its own sessions, and independent review reaches `done` with Review empty

## Isolation / teardown
- Deliberate end states, in the registry: `SLT2 Orphan Trash` trashed, `SLT2 Orphan Price` $19.00, `slt2-orph3` deleted.
- `SUB_ORPH_TRASH` and `SUB_ORPH_PRICE` renew daily until SLT-SETUP-99A cancels them; register all three semantic aliases for the watch. Do not replace these names with generic numbered aliases that could collide with another task's evidence.
- All artifacts are `SLT `/`slt2-` prefixed so SLT-SETUP-99B removes them; the trashed product must be emptied from Trash. The only global mutation is appending the three task-owned product IDs to the preserved Shop Access exclusion list; SETUP-99A restores the exact pre-window snapshot.
- Any non-exploratory defect (fatal/500, inconsistent portal-vs-charge amount, corrupt linkage, wrong recipient, or stuck action) goes only in `qa/issues/` kanban card named `SLT-IMP-04-<concise-slug>` with task/stage/plan path; all affected customer/product/order/subscription/action/message IDs; user login/email/role; exact URLs/sessions/gates; reproduction; expected safety invariant; actual result; and UI/meta/queue/log/Mailpit proof.

---

### Fresh-cycle validation contract

- Re-derive every ID, count, option value, gateway capability, scheduler timestamp, and email baseline on this run; no prior-cycle result is evidence.
- Create and mutate only registered `SLT2 ` / `slt2-*` fixtures. Legacy `SLT` and all non-SLT2 data are read-only controls.
- Automatic-gateway scope is Stripe and Paddle only. Stripe is the primary path; run Paddle parity wherever Paddle supports the behavior. Do not test or configure PayPal or Mollie.
- ArraySubs core must own its Stripe/Paddle integration, renewal, retry, webhook, REST, refund, and customer-payment services with Pro inactive; vendor host classes retain their expected ownership.
- Browser-required assertions use Vercel `agent-browser` with isolated task/role sessions and current snapshot refs. WP-CLI always includes `--allow-root`.
- Update the lifecycle card, the matching `qa/progress/` card, and `qa/issues/` for every new regression. Evidence belongs to this fresh cycle only.
