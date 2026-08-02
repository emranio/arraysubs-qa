---
id: 100
title: 'Subscriptions list: status tabs, search, gateway filter, columns, pagination, delete guardrails'
status: todo
priority: high
created: 2026-08-02T03:43:11.357843805+02:00
updated: 2026-08-02T03:43:22.636881453+02:00
tags:
    - admin
    - portal
    - day-07
    - has-conflicts
due: "2026-08-09"
estimate: 1h15m
depends_on:
    - 89
    - 12
    - 23
class: standard
---

> **SLT-ADM-01** · group `admin` · scheduled **D07** (2026-08-09)

> Read `README.md` (environment + isolation contract), `calendar.md` (this day's exact
> ordering — it is binding, not advisory) and `plan-audit.md` before starting.

### ⚠ Conflict resolutions that apply to this task

**`high` · shared-global-setting / same-day bracket collision** — with `SLT-SW-08`, `SLT-SW-04`, `SLT-SW-02`, `SLT-MYA-04`, `SLT-DUN-05`

- *Problem:* SLT-SW-08 (d7) sets proration.switch_fees.upgrade from 0 to 7.50 globally and restores it in the same task, declaring 'no other SLT switch may run between set and restore'. SLT-SW-04 (d7) performs a Basic->Pro upgrade the same day and asserts its proration order matches SLT-SW-01's record-for-record with 'no switch-fee row'. If SW-04 runs inside SW-08's bracket its order gains a $7.50 'Plan Upgrade switch fee' line and the comparison fails for the wrong reason. The bracket file exists but nothing sequences the two tasks.
- *Required fix:* Fix the D7 order explicitly in the calendar and in both task bodies: SLT-SW-04 completes and its proration order is PAID before SLT-SW-08 opens its bracket. SW-08's step 2 gains a pre-flight assertion: 'SLT-SW-04 is done on the board and no plan_switch order created today is still unpaid'. SW-08's bracket file must record open/close UTC and be posted to the registry so any switch order created inside it can be attributed and re-run.

---
## Objective
Audit the subscriptions list screen against the SLT cohort: status tabs and counts, search, gateway filter, columns, sorting, pagination and both delete guardrails. Nothing is deleted — the destructive path stops at its dialog.

## Scope
- Gateway: both (Stripe and Paddle rows)
- Checkout: N/A
- Account: admin (read-only)
- Plugins: free-only

## Preconditions
- SLT-ADM-04 done — SUB-B is `arraysubs-cancelled`, guaranteeing a cancelled SLT row. SLT-SETUP-03 and SLT-PROD-16 done (`SLT Paddle Daily` owned by `slt-paddle`).
- Statuses today are timing-dependent: expect active (Daily Core, Box, flex), expired (Fixed Three Cycles), cancelled (SUB-B, Retry Daily), on-hold (SUB-A). Record what you see.
- 354 pre-existing subscriptions exist: never select or act on a non-SLT row. No AS command, no cart.

## Test data
| Item | Value |
|---|---|
| URL | `.../admin.php?page=arraysubs-mainadmin#/subscriptions`, 20 per page |
| Searches | `slt-admincreated@example.test`, `slt-paddle`, SUB-B id, `SLT Daily Core` |
| Session | `--session admin-SLT-ADM-01` |

## Steps
1. `mailpit-agent latest-id` → `M0`. Open the list URL → `snapshot -i`; screenshot the toolbar: **Bulk actions** + **Apply**, the placeholder `Subscription ID, customer name, email, username...`, **Gateway**, **Export CSV**.
2. Record each tab label and count; sum the six, compare with **All**. Click every tab: screenshot, verify each row's chip matches, note which SLT rows appear.
3. Verify columns — **ID** (chip in-cell, `#<id>` links to the detail route), **Date**, **Customer**, **Product**, **Next Payment**; no Status column. Sort by **ID** both ways.
4. Run each search term, screenshot each result, clear the box, confirm the list returns.
5. Set **Gateway** = `Stripe`, then `Paddle` (must surface `SLT Paddle Daily`), then `PayPal` (disabled site-wide — record what it returns), then `All Gateways`.
6. Pagination on **All**: read `N items` and `X of Y`, click `›`, `»`, `«`; check button disabling at both ends and URL syncing.
7. Guardrail A: on **Active**, open an **SLT** active row's actions, choose delete, screenshot the refusal.
8. Guardrail B: tick **only** the cancelled SUB-B row, choose **Delete Permanently**, **Apply**, screenshot the `Confirm Bulk Action` dialog, then **Cancel**. Verify SUB-B survives and no `Trash` option exists.
9. Click **Export CSV**; capture the filename and header, confirm `latest-id` = `M0`, close the session.

## Expected results
1. The six per-status counts sum to **All**, and each tab returns only rows whose chip matches it (`arraysubs-active`, `-pending`, `-on-hold`, `-cancelled`, `-expired`, `-trial`).
2. Columns render ID, Date, Customer, Product, Next Payment; the chip sits in the ID cell; `#<id>` opens the detail route; ID sorting reorders both ways.
3. **Next Payment** is empty on a cancelled row (the cancel path clears the meta), and meta + 6 h on an active row.
4. Each search term returns only its cohort and clearing restores the list; record any term that returns nothing.
5. Gateway `Paddle` surfaces `SLT Paddle Daily` and excludes Stripe-only rows; `Stripe` excludes it; `PayPal` is exploratory. Pagination shows 20 rows per full page, `X of Y` = `N items / 20`, and syncs to the URL.
6. Row-level delete on an active SLT subscription is refused with exactly `Cannot delete active or trial subscriptions. Please cancel the subscription first.` Export CSV downloads a file matching the active filter, with no console errors or 4xx/5xx.
7. The bulk dialog opens and is cancelled; SUB-B survives; **Bulk actions** offers `Delete Permanently` only. **File a finding:** `handleBulkAction()` (`libs/data-list/index.js:515-556`) issues `DELETE wp/v2/arraysubs_data/<id>?force=true` per selected id with **no** `onDeleteCheck` guard, bypassing the protection the row action enforces — not proven destructively, by choice.

## Emails expected
| # | Email | Trigger point | Recipient | Subject contains | Verify with |
|---|---|---|---|---|---|
| 1 | NONE EXPECTED | whole task, no delete confirmed | — | — | `latest-id` at step 9 = `M0` |

## Evidence to capture
- Screenshots `SLT-ADM-01-01-toolbar.png`, `-02-tab-<status>.png` per tab, `-03-sort-desc.png`, `-04-search.png`, `-05-gateway-paddle.png`, `-06-pagination.png`, `-07-delete-refused.png`, `-08-bulk-cancelled.png`; tab counts, SLT ids, CSV header row.

## Pass criteria
- [ ] Counts sum to All, tabs status-pure, columns/chip/detail link/ID sorting correct
- [ ] All four search forms exercised; gateway filter isolates the Paddle row
- [ ] Pagination behaves at 20/page; row delete refused with the exact message; bulk dialog cancelled, SUB-B intact, bypass finding filed
- [ ] Export CSV succeeds; zero mail, zero console errors

## Isolation / teardown
- Nothing created, edited or deleted; no setting changed; no AS run.
- If the bulk dialog is ever confirmed by accident, STOP, record the selected ids and file a critical incident — that endpoint force-deletes irrecoverably.


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
