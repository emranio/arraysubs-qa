---
id: 100
title: 'Subscriptions list: status tabs, search, gateway filter, columns, pagination, delete guardrails'
status: done
priority: high
created: 2026-08-02T03:43:11.357843805+02:00
updated: 2026-08-09T04:19:54.449306153+02:00
started: 2026-08-09T04:19:54.449305321+02:00
completed: 2026-08-09T04:19:54.449305321+02:00
tags:
    - admin
    - portal
    - day-07
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

## Objective
Audit the subscriptions list screen against the SLT cohort: status tabs and counts, search, gateway filter, columns, sorting, pagination, the active-row delete refusal, and the cancelled-row bulk confirmation boundary. Nothing is deleted — the destructive path stops at its dialog, so this task makes no unverified claim about what the confirmed bulk request would do.

## Scope
- Gateway: both (Stripe and Paddle rows)
- Checkout: N/A
- Account: admin (read-only)
- Plugins: free-only

## Preconditions
- SLT-ADM-04 done — SUB-B is `arraysubs-cancelled`, guaranteeing a cancelled SLT row. SLT-SETUP-03 and SLT-PROD-16 done (`SLT Paddle Daily` owned by `slt-paddle`).
- Statuses today are timing-dependent: expect active (Daily Core, Box, flex), expired (Fixed Three Cycles), cancelled (SUB-B, Retry Daily), on-hold (SUB-A). Record what you see.
- The total is live and changes throughout the window: record the exact current count and registry-owned SLT ID set at task start rather than using the stale original `354` snapshot. Never select or act on a non-SLT row. No AS command, no cart.

## Test data
| Item | Value |
|---|---|
| URL | `.../admin.php?page=arraysubs-mainadmin#/subscriptions`, 20 per page |
| Searches | `slt-admincreated@example.test`, `slt-paddle`, SUB-B id, `SLT Daily Core` |
| Session | `--session admin-SLT-ADM-01` |

## Steps
1. `M0=$(mailpit-agent latest-id)`. Open the list URL → `snapshot -i`; screenshot the toolbar: **Bulk actions** + **Apply**, the placeholder `Subscription ID, customer name, email, username...`, **Gateway**, **Export CSV**.
2. Record each tab label and count; sum the six, compare with **All**. Click every tab: screenshot, verify each row's chip matches, note which SLT rows appear.
3. Verify columns — **ID** (chip in-cell, `#<id>` links to the detail route), **Date**, **Customer**, **Product**, **Next Payment**; no Status column. Sort by **ID** both ways.
4. Run each search term, capture a uniquely named result (`SLT-ADM-01-04-search-email.png`, `-04a-search-login.png`, `-04b-search-id.png`, `-04c-search-product.png`), clear the box after each, and confirm the exact unfiltered count/list returns.
5. Set **Gateway** = `Stripe`, then `Paddle`, then `PayPal` (disabled site-wide — record what it returns), then `All Gateways`. Resolve `SUB_PAD` from the registry first: when available, Paddle must surface that exact `SLT Paddle Daily` row and exclude Stripe rows; if its source task published `SUB_PAD unavailable`, mark only that positive branch UNVERIFIED and cite the source issue without choosing another Paddle record.
6. Pagination on **All**: read `N items` and `X of Y`, click `›`, `»`, `«`; check button disabling at both ends and URL syncing.
7. Guardrail A: on **Active**, open an **SLT** active row's actions, choose delete, screenshot the refusal.
8. Guardrail B: tick **only** the cancelled SUB-B row, choose **Delete Permanently**, **Apply**, screenshot the `Confirm Bulk Action` dialog, then **Cancel**. Verify SUB-B survives and no `Trash` option exists.
9. Click **Export CSV**; capture the filename and header, then inspect the complete Mailpit delta after M0 and require zero message attributable to this read-only task. Unrelated shared-site mail is classified rather than making a global latest-id equality assertion. Verify SUB-B still exists by exact ID, close `admin-SLT-ADM-01`, independently review the full read-only evidence, then move the card through `review` to `done` and require Review to return to zero. Any live non-destructive defect goes only in `issues/SLT-ADM-01-<concise-slug>.md` with task/stage/plan path; affected subscription/product/customer IDs; user login/email/role where relevant; exact route/session/filter/search/page state; reproduction; expected/actual; and UI/CSV/console/network proof. If the bulk dialog is accidentally confirmed, create a separate critical incident file with the exact selected IDs and observed result.

## Expected results
1. The six per-status counts sum to **All**, and each tab returns only rows whose chip matches it (`arraysubs-active`, `-pending`, `-on-hold`, `-cancelled`, `-expired`, `-trial`).
2. Columns render ID, Date, Customer, Product, Next Payment; the chip sits in the ID cell; `#<id>` opens the detail route; ID sorting reorders both ways.
3. **Next Payment** is empty on a cancelled row (the cancel path clears the meta), and meta + 6 h on an active row.
4. Each search term returns only its cohort and clearing restores the list; record any term that returns nothing.
5. Gateway `Paddle` surfaces `SLT Paddle Daily` and excludes Stripe-only rows; `Stripe` excludes it; `PayPal` is exploratory. Pagination shows 20 rows per full page, `X of Y` = `N items / 20`, and syncs to the URL.
6. Row-level delete on an active SLT subscription is refused with exactly `Cannot delete active or trial subscriptions. Please cancel the subscription first.` Export CSV downloads a file matching the active filter, with no console errors or 4xx/5xx.
7. The bulk dialog opens and is cancelled; SUB-B survives; **Bulk actions** offers `Delete Permanently` only. This proves the confirmation boundary, not the behavior of a request that was never submitted. Do not inspect product source and do not file a product issue from an unexecuted destructive hypothesis. File a standalone issue only if live, non-destructive evidence itself shows a defect.

## Emails expected
| # | Email | Trigger point | Recipient | Subject contains | Verify with |
|---|---|---|---|---|---|
| 1 | NONE EXPECTED | whole task, no delete confirmed | — | — | complete M0 delta contains no task-attributable message |

## Evidence to capture
- Screenshots `SLT-ADM-01-01-toolbar.png`, `-02-tab-<status>.png` per tab, `-03-sort-desc.png`, four uniquely named `-04*` search captures, `-05-gateway-paddle.png` when available, `-06-pagination.png`, `-07-delete-refused.png`, `-08-bulk-cancelled.png`; live tab counts, registry SLT ids, CSV filename/header row.

## Pass criteria
- [ ] Counts sum to All, tabs status-pure, columns/chip/detail link/ID sorting correct
- [ ] All four search forms exercised; gateway filter isolates the Paddle row
- [ ] Pagination behaves at 20/page; row delete refused with the exact message; bulk dialog cancelled and SUB-B intact; no unverified bypass claim filed
- [ ] Export CSV succeeds; zero mail, zero console errors
- [ ] Exact session closed; any live finding is standalone; independent review reaches `done` with Review empty

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
  fire on their own; **a renewal that does not fire is a real bug** — capture evidence and do not force a natural-watch action.
- Give this task its own browser session (`agent-browser --session <role>-<TASK-KEY>`). Sessions are
  keyed by name and **share a cart**.
- Never run a bare or `--hooks=` Action Scheduler drain. Run one known action ID at a time only when the task explicitly authorizes it and after the required queue pre-flight; natural-watch actions are never forced.
- Evidence goes under `/home/server-manager/slt-evidence/` using task-key-prefixed filenames.

[[2026-08-06]] Thu 20:37
Source-block note on 2026-08-06: this card expects card 89 / SLT-ADM-04 to have produced a cancelled SUB-B row. Card 89 is now source-blocked because prerequisite card 63 / SLT-ADM-03 closed UNVERIFIED without creating SUB-B, so this admin-list audit must not assume that cancelled-row guardrail exists until a later valid execution recreates it.

[[2026-08-09]] Sun 04:19
D07 execution completed 2026-08-09 07:25-08:15 site. Strict verdict FAIL. PASS: live counts All 379 = Active 34 + Pending 12 + On Hold 1 + Cancelled 317 + Expired 15 + Trial 0; tab purity; columns/chip/detail route; ID sorting; exact email/login searches; clearing; Stripe exclusion; pagination; All export; focused Active export retest 34/34 Active; zero task mail; clean console/errors/network; read-only isolation. FAIL: exact product-title search SLT Daily Core returns zero; Paddle filter returns zero despite exact live Paddle subscriptions 12639/13344; every filtered zero state shows false first-product onboarding copy. Issues: issues/SLT-ADM-01-product-title-search-returns-zero.md, issues/SLT-ADM-01-paddle-gateway-filter-returns-zero.md, issues/SLT-ADM-01-zero-result-shows-first-product-onboarding.md. UNVERIFIED and closed without substitution: SUB-B numeric search/bulk dialog/survival because SUB-B never existed; active-row exact refusal because active 12760 exposed no Delete action. No row selected, no destructive Apply, and no site state changed. Corrected evidence: /home/server-manager/slt-evidence/SLT-ADM-01-D07-read.txt; Active export /home/server-manager/slt-evidence/SLT-ADM-01-export-active-retest.csv (35 lines including header). Exact session closed.
