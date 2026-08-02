---
id: 92
title: 'Orphan and edge states: product trashed, price edited, customer deleted mid-cycle (exploratory)'
status: todo
priority: medium
created: 2026-08-02T03:43:10.813396385+02:00
updated: 2026-08-02T03:43:21.952702156+02:00
tags:
    - edge-cases
    - day-06
due: "2026-08-08"
estimate: 3h on D6 + 45m follow-up on D7
depends_on:
    - 10
    - 11
    - 12
class: standard
---

> **SLT-IMP-04** · group `implied` · scheduled **D06** (2026-08-08)

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
- CREATES three products (Simple, Virtual, **Subscription [ArraySubs]**, day/1, no trial or fee, description `SLT window product. Orphan probe. Delete 2026-08-13.`) — `SLT Orphan Trash` $6.00, `SLT Orphan Price` $8.00, `SLT Orphan User` $7.00 — plus accounts `slt-orph1/2/3@example.test`, pw `SltQa!2026#Pass`.
- Never touch another task's artifacts. Sessions `customer-orph1/2/3` (C09). No `wp action-scheduler run` (C07).

## Test data
| Item | Value |
|---|---|
| S1 | SLT Orphan Trash $6.00 / slt-orph1 — trashed |
| S2 | SLT Orphan Price $8.00 / slt-orph2 — price -> $19.00 |
| S3 | SLT Orphan User $7.00 / slt-orph3 — user deleted |
| Card / timing | 4242 4242 4242 4242; buy after 12:00 site, 2026-08-08 |
| Renewal due | 2026-08-09 (D7) = purchase + 24 h + `k` |

## Steps
1. Create the three products and users. `mailpit-agent latest-id` -> `M0`.
2. Buy one product per account with the success card. Record S1/S2/S3, parent order ids, each `_next_payment_date`, `_recurring_amount` and `k = crc32('arraysubs-spread-'.$id)%21600` with its charge instant.
3. TRASH: `/wp-admin/edit.php?post_type=product`, hover `SLT Orphan Trash` -> **Trash**. Screenshot the list, S1's admin panel, `/my-account/view-subscription/<S1>/`.
4. PRICE EDIT: open `SLT Orphan Price`, **Regular price ($)** `8.00` -> `19.00`, **Update**. Screenshot; re-read S2's `_recurring_amount`, admin total, portal.
5. USER DELETE: `users.php` -> `slt-orph3` -> **Delete**; screenshot the options, choose **Attribute all content to** `admin`, submit.
6. After each mutation dump `wp post meta list <SUB_ID>` and `wp post list --post_type=arraysubs_data --include=<SUB_ID> --fields=ID,post_status,post_author`, then screenshot `tools.php?page=action-scheduler&status=pending` to prove the legs survived.
7. FOLLOW-UP 2026-08-09 (D7): per subscription record whether the renewal fired, order id and total, status, order notes verbatim, any `status=failed` row and message, each new Mailpit message with its `To:`. Grep `debug.log`; screenshot `#/audits/renewal-failures`.

## Expected results
Exploratory — record the ACTUAL answer with proof; do not assert one.
1. S1: does `arraysubs_process_renewal` still run? Is a renewal order created, with what line item and total once `wc_get_product()` returns a trashed product? Does the subscription go on-hold, fail or stay `arraysubs-active`? Does the portal render or fatal?
2. S2: does the D7 renewal charge `$8.00` (stored on the subscription) or `$19.00` (live price)? Record `_recurring_amount` before/after and the order total. Whichever wins, portal display and charged amount MUST agree — a mismatch is a defect.
3. S3: does the subscription survive with `post_author` reattributed? Is the order's `customer_id` reset to 0? Does the renewal fire, and where does `payment_successful` go — dead address, admin, nowhere? Any fatal or "customer not found"?
4. All three: whether each Scheduled Actions row ends `complete` or `failed`, the message if failed, and whether Renewal Failures captured anything.

## Emails expected
| # | Email | Trigger point | Recipient | Subject contains | Verify with |
|---|---|---|---|---|---|
| 1-3 | new_subscription x3 | each checkout | slt-orph1/2/3 | `is active` | `mailpit-agent wait-new M0 180 "is active"` |
| ? | UNKNOWN — record what arrives | D7 renewals | actual `To:` | actual subject | `mailpit-agent list 50`, `show <id>` |

The mutations must send NOTHING. Negative check: `latest-id` unchanged across steps 3-5.

## Evidence to capture
- `SLT-IMP-04-01-trashed.png`, `-02-price.png`, `-03-user-delete.png`, `-04-pending.png`, `-05..07-portal.png`, `-08-failures.png`; meta dumps before/after each mutation, order notes, Mailpit ids with `To:`, the D7 log slice.

## Pass criteria
- [ ] all three behaviours documented with before/after dumps
- [ ] each leg's D7 outcome recorded, plus any Renewal Failures row
- [ ] the S2 charged amount recorded and compared to the portal display
- [ ] the S3 email recipient recorded verbatim
- [ ] any PHP fatal, 500 or unhandled notice filed as an issue
- [ ] no non-SLT artifact touched

## Isolation / teardown
- Deliberate end states, in the registry: `SLT Orphan Trash` trashed, `SLT Orphan Price` $19.00, `slt-orph3` deleted.
- S1/S2 renew daily until SLT-SETUP-99A cancels them; register the ids for the watch.
- All artifacts are `SLT `/`slt-` prefixed so SLT-SETUP-99B removes them; the trashed product must be emptied from Trash.

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
