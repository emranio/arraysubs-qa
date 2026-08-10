---
id: 10
title: SLT-SETUP-01 Recon environment, create SLT evidence + classic checkout pages, publish registry
status: done
priority: critical
created: 2026-08-02T03:43:03.790690132+02:00
updated: 2026-08-02T13:26:07.383158496+02:00
started: 2026-08-02T13:26:07.383157605+02:00
completed: 2026-08-02T13:26:07.383157605+02:00
tags:
    - setup
    - day-00
due: "2026-08-02"
estimate: 1h
class: standard
---

> **SLT-SETUP-01** · group `foundation` · scheduled **D00** (2026-08-02)

> Read `README.md` (environment + isolation contract), `calendar.md` (this day's exact
> ordering — it is binding, not advisory) and `plan-audit.md` before starting.

## Objective
Establish the shared ground truth every other SLT task depends on: record the untouched environment, create the SLT-only helper pages (classic cart/checkout, catalog registry), verify Mailpit and Action Scheduler respond, and publish the naming/ID conventions so no later author has to make a design decision.

## Scope
- Gateway: both
- Checkout: both
- Account: N/A
- Plugins: both

## Preconditions
- No prerequisite tasks. This is the first task of the window.
- Verified facts (do NOT re-verify): site timezone UTC+6, `gmt_offset=6`, `timezone_string` empty; site-local midnight 2026-08-01 == `2026-07-31 18:00:00` UTC; currency USD; `woocommerce_price_num_decimals=2`; `woocommerce_calc_taxes=no`; `start_of_week=6` (Saturday); `woocommerce_enable_guest_checkout=yes` (ArraySubs separately requires registration for subscription carts); `blogname=mirror-help.arrayhash.com`.
- Cart page and Checkout page are BLOCK based (`wp:woocommerce/cart`, `wp:woocommerce/checkout`). Classic checkout does not exist yet — this task creates it.

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
2. Snapshot the untouched settings blob to a file that SLT-SETUP-99A will diff against: from WP root run `wp option get arraysubs_settings --format=json --allow-root > /home/server-manager/slt-evidence/SLT-SETUP-01-arraysubs_settings-D0.json`.
3. Record the WooCommerce/gateway baseline under `/home/server-manager/slt-evidence/`, but stream each `wp option get ... --format=json --allow-root` result through `jq` before writing it. Replace every API key, publishable/secret key, client token, webhook secret, and nested `secret` value with `[REDACTED_PRESENT]` or `[EMPTY]`. Never write the raw Stripe or Paddle option blob to disk or terminal output.
4. Confirm Mailpit is reachable and capture the pre-window message id: `mailpit-agent status` then `mailpit-agent latest-id`. Record the id as `MAILPIT_BASE`.
5. Confirm Action Scheduler CLI: `wp action-scheduler status --allow-root`. Note that this install has `run`, `status`, `source`, `clean` but NO `list` — queue inspection is via wp-admin -> Tools -> Scheduled Actions.
6. `agent-browser skills get core` (mandatory before any browser work in this window).
7. `agent-browser --session admin-SLT-SETUP-01 open "https://mirror-help.arrayhash.com/wp-admin"` -> `agent-browser --session admin-SLT-SETUP-01 snapshot -i` -> log in as `admin` if the snapshot shows the login form.
8. Create the classic-checkout harness page: `agent-browser --session admin-SLT-SETUP-01 open "https://mirror-help.arrayhash.com/wp-admin/post-new.php?post_type=page"`; title `SLT Classic Checkout`; in the block editor add a Shortcode block containing `[woocommerce_checkout]`; set the URL slug to `slt-classic-checkout`; Publish.
9. Create the classic-cart harness page the same way: title `SLT Classic Cart`, Shortcode block `[woocommerce_cart]`, slug `slt-classic-cart`, Publish.
10. Create the shared ID registry: new page, title `SLT Catalog Registry`, slug `slt-catalog-registry`, visibility **Private**, body = an empty markdown-style table with header row `| Key | Artifact | Type | WP ID | Notes |`. Publish. Every later SLT task appends its created IDs here.
11. Verify the two harness pages render: `agent-browser --session guest-SLT-SETUP-01 open "https://mirror-help.arrayhash.com/slt-classic-cart"` -> `snapshot -i` (expect the classic empty-cart notice, not a block skeleton).
12. Screenshot the ArraySubs settings landing page for the before-state: `agent-browser --session admin-SLT-SETUP-01 open "https://mirror-help.arrayhash.com/wp-admin/admin.php?page=arraysubs-mainadmin#/settings/general"` -> `agent-browser --session admin-SLT-SETUP-01 screenshot --full /home/server-manager/slt-evidence/SLT-SETUP-01-01-settings-general-before.png`.
13. Close only this task's named sessions by explicit name.

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
| 1 | NONE EXPECTED | Whole task | — | — | Complete delta after `MAILPIT_BASE`; zero task-attributable mail, while unrelated/background mail is allowed and classified |

## Evidence to capture
- Screenshots: `SLT-SETUP-01-01-settings-general-before.png`, `SLT-SETUP-01-02-classic-cart-renders.png`, `SLT-SETUP-01-03-registry-page.png`.
- WP IDs of the three created pages.
- `MAILPIT_BASE` value, `wp action-scheduler status` output, and both redacted settings JSON summaries.
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
- Restores: nothing (this task changes no setting). The three pages are torn down by SLT-SETUP-99B.

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

[[2026-08-02]] Sun 13:26
PASS 2026-08-02 (tester hill-tide): created pages 11843/11845/11847 through wp-admin; classic cart and checkout rendered; registry is private with required header; MAILPIT_BASE remained 43I8xZYyWDz2sQZ3c31o0x; Action Scheduler status returned cleanly; no browser/network errors. Evidence: /home/server-manager/slt-evidence/SLT-SETUP-01-* . The former task text caused a global browser close and interrupted a pre-existing office-agent session. The corrected rule is to close only the current task's named sessions; no later task may use a global close.
