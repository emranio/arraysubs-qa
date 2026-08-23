---
id: 10
title: SLT-SETUP-01 Recon environment, create SLT2 evidence + classic checkout pages, publish registry
status: done
priority: critical
created: 2026-08-22T19:10:00+02:00
updated: 2026-08-23T06:54:01.728067851+02:00
started: 2026-08-22T20:48:30.237361981+02:00
completed: 2026-08-22T21:02:18.636885311+02:00
tags:
    - cycle-2
    - granular
    - setup
    - day-00
due: "2026-08-23"
estimate: 1h
class: standard
---

> **SLT-SETUP-01** · group `foundation` · scheduled **D00** (2026-08-23)

> Read `README.md` (environment + isolation contract), `calendar.md` (this day's exact
> ordering — it is binding, not advisory) and `plan-audit.md` before starting.

## Objective
Establish the shared ground truth every other SLT2 task depends on: record the untouched environment, create the SLT2-only helper pages (classic cart/checkout, catalog registry), verify Mailpit and Action Scheduler respond, and publish the naming/ID conventions so no later author has to make a design decision.

## Scope
- Gateway: both
- Checkout: both
- Account: N/A
- Plugins: both

## Preconditions
- No prerequisite tasks. This is the first task of the window.
- No environment value is trusted from the previous cycle. Re-query and record the current timezone, `gmt_offset`, `timezone_string`, currency, decimal precision, tax setting, start-of-week, guest-checkout setting, site title, checkout/cart page IDs and block/shortcode composition before creating fixtures.
- Determine whether classic checkout/cart harness pages already exist under the SLT2 namespace. Reuse only an exact current-cycle registry entry; otherwise create them in this task.

## Test data
| Item | Value |
|---|---|
| Product | N/A |
| Account | use the current local admin credential source in `AGENTS.md` |
| Coupon | N/A |
| Card | N/A |
| Amounts | N/A |

## Steps
1. `mkdir -p /home/server-manager/slt-evidence` — the single evidence root for the whole window. Every screenshot is `<TASK-KEY>-NN-<slug>.png`.
2. Snapshot the untouched settings blob to a file that SLT-SETUP-99A will diff against: from WP root run `wp option get arraysubs_settings --format=json --allow-root > /home/server-manager/slt-evidence/SLT-SETUP-01-arraysubs_settings-D0.json`. Also export the fresh site/Woo date, checkout, currency, tax and week-start facts to `SLT-SETUP-01-environment-D0.json` and derive all calendar arithmetic from that file.
3. Record the WooCommerce/gateway baseline under `/home/server-manager/slt-evidence/`, but stream each `wp option get ... --format=json --allow-root` result through `jq` before writing it. Replace every API key, publishable/secret key, client token, webhook secret, and nested `secret` value with `[REDACTED_PRESENT]` or `[EMPTY]`. Never write the raw Stripe or Paddle option blob to disk or terminal output.
4. Confirm Mailpit is reachable and capture the pre-window message id: `mailpit-agent status` then `mailpit-agent latest-id`. Record the id as `MAILPIT_BASE`.
5. Confirm Action Scheduler CLI: `wp action-scheduler status --allow-root`. Note that this install has `run`, `status`, `source`, `clean` but NO `list` — queue inspection is via wp-admin -> Tools -> Scheduled Actions.
6. `agent-browser skills get core` (mandatory before any browser work in this window).
7. `agent-browser --session admin-SLT-SETUP-01 open "https://mirror-help.arrayhash.com/wp-admin"` -> `agent-browser --session admin-SLT-SETUP-01 snapshot -i` -> log in as `admin` if the snapshot shows the login form.
8. Create the classic-checkout harness page: `agent-browser --session admin-SLT-SETUP-01 open "https://mirror-help.arrayhash.com/wp-admin/post-new.php?post_type=page"`; title `SLT2 Classic Checkout`; in the block editor add a Shortcode block containing `[woocommerce_checkout]`; set the URL slug to `slt2-classic-checkout`; Publish.
9. Create the classic-cart harness page the same way: title `SLT2 Classic Cart`, Shortcode block `[woocommerce_cart]`, slug `slt2-classic-cart`, Publish.
10. Create the shared ID registry: new page, title `SLT2 Catalog Registry`, slug `slt2-catalog-registry`, visibility **Private**, body = an empty markdown-style table with header row `| Key | Artifact | Type | WP ID | Notes |`. Publish. Every later SLT2 task appends its created IDs here.
11. Verify the two harness pages render: `agent-browser --session guest-SLT-SETUP-01 open "https://mirror-help.arrayhash.com/slt2-classic-cart"` -> `snapshot -i` (expect the classic empty-cart notice, not a block skeleton).
12. Screenshot the ArraySubs settings landing page for the before-state: `agent-browser --session admin-SLT-SETUP-01 open "https://mirror-help.arrayhash.com/wp-admin/admin.php?page=arraysubs-mainadmin#/settings/general"` -> `agent-browser --session admin-SLT-SETUP-01 screenshot --full /home/server-manager/slt-evidence/SLT-SETUP-01-01-settings-general-before.png`.
13. Close only this task's named sessions by explicit name.

## Expected results
1. `/home/server-manager/slt-evidence/` exists and holds `SLT-SETUP-01-arraysubs_settings-D0.json` with the pre-window `arraysubs_settings` blob.
2. `mailpit-agent status` reports the sink is up and `MAILPIT_BASE` is recorded as an integer message id.
3. `wp action-scheduler status --allow-root` returns without error and shows pending/complete counts.
4. Page `slt2-classic-checkout` exists, is published, and renders the classic checkout shortcode output (not the block checkout).
5. Page `slt2-classic-cart` exists, is published, and renders the classic cart shortcode output.
6. Page `slt2-catalog-registry` exists, is Private, and contains the registry table header.
7. No existing product, order, subscription, coupon, user, page or setting was modified by this task.

## Emails expected
| # | Email | Trigger point | Recipient | Subject contains | Verify with |
|---|---|---|---|---|---|
| 1 | NONE EXPECTED | Whole task | — | — | Complete delta after `MAILPIT_BASE`; zero task-attributable mail, while unrelated/background mail is allowed and classified |

## Evidence to capture
- Screenshots: `SLT-SETUP-01-01-settings-general-before.png`, `SLT-SETUP-01-02-classic-cart-renders.png`, `SLT-SETUP-01-03-registry-page.png`.
- WP IDs of the three created pages.
- `MAILPIT_BASE` value, `wp action-scheduler status` output, and both redacted settings JSON summaries.
- Any console/network errors from the block editor while publishing.

## Pass criteria
- [x] Evidence root and D0 settings dump exist
- [x] Mailpit reachable and MAILPIT_BASE recorded
- [x] Action Scheduler status returns cleanly
- [x] slt2-classic-checkout published and renders classic checkout
- [x] slt2-classic-cart published and renders classic cart
- [x] slt2-catalog-registry published as Private with the header row
- [x] No non-SLT2 artifact touched

## Isolation / teardown
- Leaves behind for every later task: the evidence root `/home/server-manager/slt-evidence/`, the classic checkout/cart harness pages (use these whenever a task's Scope says `Checkout: classic`), the private ID registry page, and `MAILPIT_BASE`.
- Conventions fixed here and binding on all SLT2 tasks: products titled `SLT2 <Name>` / slug `slt2-<name>`; users `slt2-<purpose>` / `slt2-<purpose>@example.test`; coupons `SLT2<PURPOSE>`; every SLT2 product is **Virtual** (keeps the pro SubscriptionShipping fields out of scope) and has stock management OFF.
- Restores: nothing (this task changes no setting). The three pages are torn down by SLT-SETUP-99B.

---

### Fresh-cycle validation contract

- Re-derive every ID, count, option value, gateway capability, scheduler timestamp, and email baseline on this run; no prior-cycle result is evidence.
- Create and mutate only registered `SLT2 ` / `slt2-*` fixtures. Legacy `SLT` and all non-SLT2 data are read-only controls.
- Automatic-gateway scope is Stripe and Paddle only. Stripe is the primary path; run Paddle parity wherever Paddle supports the behavior. Do not test or configure PayPal or Mollie.
- ArraySubs core must own its Stripe/Paddle integration, renewal, retry, webhook, REST, refund, and customer-payment services with Pro inactive; vendor host classes retain their expected ownership.
- Browser-required assertions use Vercel `agent-browser` with isolated task/role sessions and current snapshot refs. WP-CLI always includes `--allow-root`.
- Update the lifecycle card, the matching `qa/progress/` card, and `qa/issues/` for every new regression. Evidence belongs to this fresh cycle only.

## SLT2 execution — PASS (site date 2026-08-23)

- Environment: WordPress 7.0.2, PHP 8.3.31, WooCommerce 10.9.4, ArraySubs 1.8.12, ArraySubsPro 1.1.3; site offset +06:00 with empty `timezone_string`, `start_of_week=6`, USD, 2 decimals, tax disabled, guest checkout enabled. Canonical cart page 7 and checkout page 8 are blocks.
- Created by real wp-admin browser flow: page 31296 `SLT2 Classic Checkout` (publish), page 31298 `SLT2 Classic Cart` (publish), page 31301 `SLT2 Catalog Registry` (private). Content hashes and shortcode/header assertions pass in `/home/server-manager/slt-evidence/SLT-SETUP-01-pages-D0.json`.
- Browser proof: classic cart showed “Your cart is currently empty”; classic checkout rendered billing/order/payment fields using the pre-existing admin cart without placing an order; the private registry rendered the exact header; ArraySubs General rendered without browser errors. Screenshots 01 through 05 are under `/home/server-manager/slt-evidence/`.
- Mailpit baseline `30BE92Vx7EI97pft28Ay78` remained the latest ID: zero task-attributable messages.
- Action Scheduler after baseline: 0 new failed, 0 stuck in-progress, and 0 pending overdue by more than five minutes. The 23 historical ArraySubs failures all predate this cycle; newest is 2026-08-20. No current failure was waived.
- Isolation: no users or HPOS orders were created; `arraysubs_settings` remained byte-identical. The only non-page rows during the window were four `subscription_id=0` scheduled-job notes matched exactly to successful system-cron actions and classified in `SLT-SETUP-01-background-events-D0.json`.
- No new debug-log entry, browser error, or QA issue was found.

[[2026-08-23]] Sun 02:48

## D00 early-morning watcher reconciliation — 2026-08-23

- Fresh authenticated read-only registry view confirmed exact setup pages 31296, 31298, and 31301. The later account/product TSV divergence is isolated under shared issue #2 and does not alter this foundation result. No site mutation occurred. Evidence: `/home/server-manager/slt-evidence/SLT-WATCH-D00-EARLY-03-catalog-registry-current.png`.

[[2026-08-23]] Sun 03:02

## Closure-audit tracking normalization

The lifecycle `started` timestamp was reconciled to the original `todo -> in-progress` activity event. No verdict, site state, or evidence changed.

[[2026-08-23]] Sun 06:54
## D00 late-morning read-only reconciliation — 2026-08-23

Exact-ID checks confirmed page `31296` and page `31298` remain published and registry page `31301` remains private. No page, registry entity, setting, order, subscription, or non-SLT2 record was mutated. Fresh evidence is recorded in `/home/server-manager/slt-evidence/SLT-WATCH-D00-LATE-scheduler-mail-reconciliation.json` and the merged D00 watch report.
