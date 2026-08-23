---
id: 100
title: 'Subscriptions list: status tabs, search, gateway filter, columns, pagination, delete guardrails'
status: todo
priority: high
created: 2026-08-22T19:10:00+02:00
updated: 2026-08-22T19:10:00+02:00
tags:
    - cycle-2
    - granular
    - admin
    - portal
    - day-07
due: "2026-08-30"
estimate: 1h15m
depends_on:
    - 89
    - 12
    - 23
class: standard
---

> **SLT-ADM-01** · group `admin` · scheduled **D07** (2026-08-30)

> Read `README.md` (environment + isolation contract), `calendar.md` (this day's exact
> ordering — it is binding, not advisory) and `plan-audit.md` before starting.

## Objective
Audit the subscriptions list screen against the SLT2 cohort: status tabs and counts, search, gateway filter, columns, sorting, pagination, the active-row delete refusal, and the cancelled-row bulk confirmation boundary. Nothing is deleted — the destructive path stops at its dialog, and the confirmed bulk-delete path is explicitly outside this non-destructive card.

## Scope
- Gateway: both (Stripe and Paddle rows)
- Checkout: N/A
- Account: admin (read-only)
- Plugins: free-only

## Preconditions
- SLT-ADM-04 done — SUB-B is `arraysubs-cancelled`, guaranteeing a cancelled SLT2 row. SLT-SETUP-03 and SLT-PROD-16 done (`SLT2 Paddle Daily` owned by `slt2-paddle`).
- Statuses today are timing-dependent: expect active (Daily Core, Box, flex), expired (Fixed Three Cycles), cancelled (SUB-B, Retry Daily), on-hold (SUB-A). Record what you see.
- The total is live and changes throughout the window: record the exact current count and registry-owned SLT2 ID set at task start rather than using the stale original `354` snapshot. Never select or act on a non-SLT2 row. No AS command, no cart.

## Test data
| Item | Value |
|---|---|
| URL | `.../admin.php?page=arraysubs-mainadmin#/subscriptions`, 20 per page |
| Searches | `slt2-admincreated@example.test`, `slt2-paddle`, SUB-B id, `SLT2 Daily Core` |
| Session | `--session admin-SLT-ADM-01` |

## Steps
1. `M0=$(mailpit-agent latest-id)`. Open the list URL → `snapshot -i`; screenshot the toolbar: **Bulk actions** + **Apply**, the placeholder `Subscription ID, customer name, email, username...`, **Gateway**, **Export CSV**.
2. Record each tab label and count; sum the six, compare with **All**. Click every tab: screenshot, verify each row's chip matches, note which SLT2 rows appear.
3. Verify columns — **ID** (chip in-cell, `#<id>` links to the detail route), **Date**, **Customer**, **Product**, **Next Payment**; no Status column. Sort by **ID** both ways.
4. Run each search term, capture a uniquely named result (`SLT-ADM-01-04-search-email.png`, `-04a-search-login.png`, `-04b-search-id.png`, `-04c-search-product.png`), clear the box after each, and confirm the exact unfiltered count/list returns.
5. Set **Gateway** = `Stripe`, then `Paddle`, then `All Gateways`. Resolve `SUB_PAD` from the registry first: when available, Paddle must surface that exact `SLT2 Paddle Daily` row and exclude Stripe rows; if its source task is blocked, this card is also blocked until Paddle has a valid fresh source subscription.
6. Pagination on **All**: read `N items` and `X of Y`, click `›`, `»`, `«`; check button disabling at both ends and URL syncing.
7. Guardrail A: on **Active**, open an **SLT2** active row's actions, choose delete, screenshot the refusal.
8. Guardrail B: tick **only** the cancelled SUB-B row, choose **Delete Permanently**, **Apply**, screenshot the `Confirm Bulk Action` dialog, then **Cancel**. Verify SUB-B survives and no `Trash` option exists.
9. Click **Export CSV**; capture the filename and header, then inspect the complete Mailpit delta after M0 and require zero message attributable to this read-only task. Unrelated shared-site mail is classified rather than making a global latest-id equality assertion. Verify SUB-B still exists by exact ID, close `admin-SLT-ADM-01`, independently review the full read-only evidence, then move the card through `review` to `done` and require Review to return to zero. Any live non-destructive defect goes only in `qa/issues/` kanban card named `SLT-ADM-01-<concise-slug>` with task/stage/plan path; affected subscription/product/customer IDs; user login/email/role where relevant; exact route/session/filter/search/page state; reproduction; expected/actual; and UI/CSV/console/network proof. If the bulk dialog is accidentally confirmed, create a separate critical `qa/issues/` kanban incident card with the exact selected IDs and observed result.

## Expected results
1. The six per-status counts sum to **All**, and each tab returns only rows whose chip matches it (`arraysubs-active`, `-pending`, `-on-hold`, `-cancelled`, `-expired`, `-trial`).
2. Columns render ID, Date, Customer, Product, Next Payment; the chip sits in the ID cell; `#<id>` opens the detail route; ID sorting reorders both ways.
3. **Next Payment** is empty on a cancelled row (the cancel path clears the meta), and meta + 6 h on an active row.
4. Each search term returns only its cohort and clearing restores the list; record any term that returns nothing.
5. Gateway `Paddle` surfaces the Paddle-owned SLT2 rows and excludes Stripe-only rows; `Stripe` excludes Paddle-only rows. Do not select or probe PayPal or Mollie. Pagination shows 20 rows per full page, `X of Y` = `N items / 20`, and syncs to the URL.
6. Row-level delete on an active SLT2 subscription is refused with exactly `Cannot delete active or trial subscriptions. Please cancel the subscription first.` Export CSV downloads a file matching the active filter, with no console errors or 4xx/5xx.
7. The bulk dialog opens and is cancelled; SUB-B survives; **Bulk actions** offers `Delete Permanently` only. This proves the confirmation boundary, not the behavior of a request that was never submitted. Do not inspect product source and do not file a product issue from an unexecuted destructive hypothesis. File a dedicated issue only if live, non-destructive evidence itself shows a defect.

## Emails expected
| # | Email | Trigger point | Recipient | Subject contains | Verify with |
|---|---|---|---|---|---|
| 1 | NONE EXPECTED | whole task, no delete confirmed | — | — | complete M0 delta contains no task-attributable message |

## Evidence to capture
- Screenshots `SLT-ADM-01-01-toolbar.png`, `-02-tab-<status>.png` per tab, `-03-sort-desc.png`, four uniquely named `-04*` search captures, `-05-gateway-paddle.png` when available, `-06-pagination.png`, `-07-delete-refused.png`, `-08-bulk-cancelled.png`; live tab counts, registry SLT2 ids, CSV filename/header row.

## Pass criteria
- [ ] Counts sum to All, tabs status-pure, columns/chip/detail link/ID sorting correct
- [ ] All four search forms exercised; gateway filter isolates the Paddle row
- [ ] Pagination behaves at 20/page; row delete refused with the exact message; bulk dialog cancelled and SUB-B remains intact
- [ ] Export CSV succeeds; zero mail, zero console errors
- [ ] Exact session closed; any live finding is dedicated; independent review reaches `done` with Review empty

## Isolation / teardown
- Nothing created, edited or deleted; no setting changed; no AS run.
- If the bulk dialog is ever confirmed by accident, STOP, record the selected ids and file a critical incident — that endpoint force-deletes irrecoverably.

---

### Fresh-cycle validation contract

- Re-derive every ID, count, option value, gateway capability, scheduler timestamp, and email baseline on this run; no prior-cycle result is evidence.
- Create and mutate only registered `SLT2 ` / `slt2-*` fixtures. Legacy `SLT` and all non-SLT2 data are read-only controls.
- Automatic-gateway scope is Stripe and Paddle only. Stripe is the primary path; run Paddle parity wherever Paddle supports the behavior. Do not test or configure PayPal or Mollie.
- ArraySubs core must own its Stripe/Paddle integration, renewal, retry, webhook, REST, refund, and customer-payment services with Pro inactive; vendor host classes retain their expected ownership.
- Browser-required assertions use Vercel `agent-browser` with isolated task/role sessions and current snapshot refs. WP-CLI always includes `--allow-root`.
- Update the lifecycle card, the matching `qa/progress/` card, and `qa/issues/` for every new regression. Evidence belongs to this fresh cycle only.
