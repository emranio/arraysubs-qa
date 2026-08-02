---
id: 10
title: SLT-SETUP-01 Recon environment, create SLT evidence + classic checkout pages, publish registry
status: todo
priority: critical
created: 2026-08-02T03:43:03.790690132+02:00
updated: 2026-08-02T03:43:03.790690132+02:00
tags:
    - setup
    - day-00
    - has-conflicts
due: "2026-08-02"
estimate: 1h
class: standard
---

> **SLT-SETUP-01** · group `foundation` · scheduled **D00** (2026-08-02)

> Read `README.md` (environment + isolation contract), `calendar.md` (this day's exact
> ordering — it is binding, not advisory) and `plan-audit.md` before starting.

### ⚠ Conflict resolutions that apply to this task

**`unrated` · impossible-timing** — with `SLT-SETUP-02`, `SLT-SETUP-03`, `SLT-SETUP-04`, `SLT-SETUP-05`, `SLT-PROD-01`, `SLT-PROD-02`

- *Problem:* 25 of the 26 authored tasks are stamped day 0. Sixteen of them are multi-step browser-driven product builds (each: open post-new, set 6-15 fields across 3 tabs, publish, reload, verify, meta-dump, guest front-end check, registry append), two are long audit tasks with 15-20 probe steps each (SLT-SYN-01, SLT-SYN-02), one is a live Stripe purchase with a settings flip and restore (SLT-SYN-04), and on top of that D0 is also where the plan's isolation notes demand the D0 purchases of SLT-PROD-01/02/03/06/07/10/12/13/14/15/16. There is a hard serial chain inside the day as well: SETUP-01 -> SETUP-02 -> PROD-16 -> SETUP-05 -> SYN-04, and SETUP-01 -> SETUP-02 -> PROD-14 -> SYN-03 -> SYN-04. This is not executable in one day, and it wastes D4-D9 which sit empty.
- *Required fix:* Adopt the rebalanced D0-D10 calendar in schedule_notes. Only products with a genuinely date-bound purchase stay on D0 (SLT-PROD-01, 06, 07, 13 plus the three foundation tasks); everything else is spread D1-D5 with its purchase deadline recomputed so it still fits the window. Peak day drops from 25 tasks (~30h) to 7 tasks (~7h) and the median day is ~4.5h, inside the 1.6x band.

**`unrated` · duplicate-coverage** — with `SLT-SETUP-05`, `SLT-SYN-04`, `SLT-PROD-02`, `SLT-PROD-04`, `SLT-PROD-09`

- *Problem:* SLT-SETUP-01 builds the classic cart/checkout harness pages (slt-classic-cart, slt-classic-checkout) and binds them on every task whose Scope says 'Checkout: classic' or 'both' - but not a single authored task actually visits them. SLT-SETUP-05 uses /checkout/ (block), SLT-SYN-04's Scope says 'Checkout: block' and it uses /checkout/, and every cart preview (SLT-PROD-02/04/09/12/13/14, SLT-SYN-03) uses /cart/ (block). The 'Checkout: both' scope declarations are therefore unbacked, and two published pages are created and torn down without being exercised.
- *Required fix:* Assign the classic surface explicitly rather than declaratively: route SLT-SYN-04's purchase through /slt-classic-checkout (it is a plain Stripe purchase and is the cleanest classic candidate), route SLT-PROD-04's qty-1/qty-2 signup-fee cart probes through /slt-classic-cart (fee rendering differs between block and classic), and change every remaining 'Checkout: both' to the surface actually used. Never repoint the site's real Cart/Checkout pages - the harness pages are the only permitted classic surface.

**`unrated` · impossible-timing** — with `SLT-PROD-06`, `SLT-PROD-13`

- *Problem:* Clock drift against the authored anchor. The plan is written for D0 = 2026-08-01 with hard D0 purchase deadlines (SLT-PROD-06 'MUST be purchased on D0'; SLT-PROD-13 relies on 2026-08-01 being the Saturday start-of-week). The evidence root /home/server-manager/slt-evidence is empty - no task has executed - and the host clock has already rolled past the start of the window, so a literal D0 is partly or wholly gone before SLT-SETUP-01 runs.
- *Required fix:* Two of the three D0 constraints are softer than authored and can absorb the slip without shifting the window: SLT Fixed Three Cycles ends at start + 6 days, so a 2026-08-02 purchase expires 2026-08-08 (still D7, still observable); SLT Flex Week Segments purchased 2026-08-02 is day 2 of the same Saturday-anchored week cycle, so it stays in segment 1 with the same $14.00 charge and the same 2026-08-08 renewal. Keep the D0=2026-08-01 labels in the calendar but treat them as ordinal slots: if execution actually begins on 2026-08-02, shift every date by +1 and re-verify only two things - that SLT Fixed Three Cycles still expires on or before D9, and that the watch tail still reaches the last renewal (which moves to 2026-08-14).

---
## Objective
Establish the shared ground truth every other SLT task depends on: record the untouched environment, create the SLT-only helper pages (classic cart/checkout, catalog registry), verify Mailpit and Action Scheduler respond, and publish the naming/ID conventions so no later author has to make a design decision.

## Scope
- Gateway: both
- Checkout: both
- Account: N/A
- Plugins: both

## Preconditions
- No prerequisite tasks. This is the first task of the window.
- Verified facts (do NOT re-verify): site timezone UTC+6, `gmt_offset=6`, `timezone_string` empty; site-local midnight 2026-08-01 == `2026-07-31 18:00:00` UTC; currency USD; `woocommerce_price_num_decimals=2`; `woocommerce_calc_taxes=no`; `start_of_week=6` (Saturday); `woocommerce_enable_guest_checkout=no`; `blogname=mirror-help.arrayhash.com`.
- Cart page and Checkout page are BLOCK based (`wp:woocommerce/cart`, `wp:woocommerce/checkout`). Classic checkout does not exist yet — this task creates it.

## Test data
| Item | Value |
|---|---|
| Product | N/A |
| Account | admin / @GuDw(0$K7M9t8ehjqDb4Vwj |
| Coupon | N/A |
| Card | N/A |
| Amounts | N/A |

## Steps
1. `mkdir -p /home/server-manager/slt-evidence` — the single evidence root for the whole window. Every screenshot is `<TASK-KEY>-NN-<slug>.png`.
2. Snapshot the untouched settings blob to a file that SLT-SETUP-99 will diff against: from WP root run `wp option get arraysubs_settings --format=json --allow-root > /home/server-manager/slt-evidence/SLT-SETUP-01-arraysubs_settings-D0.json`.
3. Record the WooCommerce/gateway baseline: `wp option get woocommerce_stripe_settings --format=json --allow-root` and `wp option get woocommerce_arraysubs_paddle_settings --format=json --allow-root`, saving both under `/home/server-manager/slt-evidence/`.
4. Confirm Mailpit is reachable and capture the pre-window message id: `mailpit-agent status` then `mailpit-agent latest-id`. Record the id as `MAILPIT_BASE`.
5. Confirm Action Scheduler CLI: `wp action-scheduler status --allow-root`. Note that this install has `run`, `status`, `source`, `clean` but NO `list` — queue inspection is via wp-admin -> Tools -> Scheduled Actions.
6. `agent-browser skills get core` (mandatory before any browser work in this window).
7. `agent-browser --session admin open "https://mirror-help.arrayhash.com/wp-admin"` -> `agent-browser --session admin snapshot -i` -> log in as `admin` if the snapshot shows the login form.
8. Create the classic-checkout harness page: `agent-browser --session admin open "https://mirror-help.arrayhash.com/wp-admin/post-new.php?post_type=page"`; title `SLT Classic Checkout`; in the block editor add a Shortcode block containing `[woocommerce_checkout]`; set the URL slug to `slt-classic-checkout`; Publish.
9. Create the classic-cart harness page the same way: title `SLT Classic Cart`, Shortcode block `[woocommerce_cart]`, slug `slt-classic-cart`, Publish.
10. Create the shared ID registry: new page, title `SLT Catalog Registry`, slug `slt-catalog-registry`, visibility **Private**, body = an empty markdown-style table with header row `| Key | Artifact | Type | WP ID | Notes |`. Publish. Every later SLT task appends its created IDs here.
11. Verify the two harness pages render: `agent-browser --session guest open "https://mirror-help.arrayhash.com/slt-classic-cart"` -> `snapshot -i` (expect the classic empty-cart notice, not a block skeleton).
12. Screenshot the ArraySubs settings landing page for the before-state: `agent-browser --session admin open "https://mirror-help.arrayhash.com/wp-admin/admin.php?page=arraysubs-mainadmin#/settings/general"` -> `agent-browser --session admin screenshot --full /home/server-manager/slt-evidence/SLT-SETUP-01-01-settings-general-before.png`.
13. `agent-browser close --all`.

## Expected results
1. `/home/server-manager/slt-evidence/` exists and holds `SLT-SETUP-01-arraysubs_settings-D0.json` with the pre-window `arraysubs_settings` blob.
2. `mailpit-agent status` reports the sink is up and `MAILPIT_BASE` is recorded as an integer message id.
3. `wp action-scheduler status --allow-root` returns without error and shows pending/complete counts.
4. Page `slt-classic-checkout` exists, is published, and renders the classic checkout shortcode output (not the block checkout).
5. Page `slt-classic-cart` exists, is published, and renders the classic cart shortcode output.
6. Page `slt-catalog-registry` exists, is Private, and contains the registry table header.
7. No existing product, order, subscription, coupon, user, page or setting was modified by this task.

## Emails expected
| # | Email | Trigger point | Recipient | Subject contains | Verify with |
|---|---|---|---|---|---|
| 1 | NONE EXPECTED | Whole task | — | — | `mailpit-agent latest-id` at the end must equal `MAILPIT_BASE` recorded in step 4 |

## Evidence to capture
- Screenshots: `SLT-SETUP-01-01-settings-general-before.png`, `SLT-SETUP-01-02-classic-cart-renders.png`, `SLT-SETUP-01-03-registry-page.png`.
- WP IDs of the three created pages.
- `MAILPIT_BASE` value, `wp action-scheduler status` output, both settings JSON dumps.
- Any console/network errors from the block editor while publishing.

## Pass criteria
- [ ] Evidence root and D0 settings dump exist
- [ ] Mailpit reachable and MAILPIT_BASE recorded
- [ ] Action Scheduler status returns cleanly
- [ ] slt-classic-checkout published and renders classic checkout
- [ ] slt-classic-cart published and renders classic cart
- [ ] slt-catalog-registry published as Private with the header row
- [ ] No non-SLT artifact touched

## Isolation / teardown
- Leaves behind for every later task: the evidence root `/home/server-manager/slt-evidence/`, the classic checkout/cart harness pages (use these whenever a task's Scope says `Checkout: classic`), the private ID registry page, and `MAILPIT_BASE`.
- Conventions fixed here and binding on all SLT tasks: products titled `SLT <Name>` / slug `slt-<name>`; users `slt-<purpose>` / `slt-<purpose>@example.test`; coupons `SLT<PURPOSE>`; every SLT product is **Virtual** (keeps the pro SubscriptionShipping fields out of scope) and has stock management OFF.
- Restores: nothing (this task changes no setting). The three pages are torn down by SLT-SETUP-99.

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
